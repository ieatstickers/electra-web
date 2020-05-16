<?php

namespace Electra\Web\Application;

use Electra\Core\Event\AbstractPayload;
use Electra\Core\Exception\ElectraException;
use Electra\Core\MessageBag\MessageBag;
use Electra\Core\MessageBag\Message;
use Electra\Utility\Arrays;
use Electra\Utility\Objects;
use Electra\Web\Endpoint\EndpointInterface;
use Electra\Web\Http\Request;
use Electra\Web\Http\Response;
use Electra\Web\Middleware\MiddlewareInterface;

class Api
{
  /** @var EndpointInterface[] */
  protected $endpoints = [];
  /** @var MiddlewareInterface[] */
  protected $middleware = [];

  /**
   * @param EndpointInterface $endpoint
   * @return $this
   * @throws \Exception
   */
  public function addEndpoint($endpoint)
  {
    // If endpoint is not an
    if (!($endpoint instanceof EndpointInterface))
    {
      $endpointClass = get_class($endpoint);
      $interfaceClass = EndpointInterface::class;
      throw new \Exception("Endpoint $endpointClass must implement $interfaceClass");
    }

    $this->endpoints[] = $endpoint;

    return $this;
  }

  /**
   * @param MiddlewareInterface $middleware
   * @return $this
   * @throws \Exception
   */
  public function addMiddleware($middleware)
  {
    // If endpoint is not an
    if (!($middleware instanceof MiddlewareInterface))
    {
      $middlewareClass = get_class($middleware);
      $middlewareInterface = MiddlewareInterface::class;
      throw new \Exception("Middleware $middlewareClass must implement $middlewareInterface");
    }

    $this->middleware[] = $middleware;

    return $this;
  }

  /** @throws \Exception */
  public function run()
  {
    foreach ($this->endpoints as $endpoint)
    {
      Router::match($endpoint->getHttpMethods(), $endpoint->getUri(), function(...$routeParams) use ($endpoint)
      {
        $request = Request::capture();
        RouteParams::clear();

        if ($routeParams)
        {
          $matches = [];
          preg_match_all('/{[A-z]+}/', $endpoint->getUri(), $matches);

          if ($matches)
          {
            $matches = $matches[0];
          }

          foreach ($matches as $key => $value)
          {
            $value = ltrim($value, '{');
            $value = rtrim($value, '}');
            RouteParams::add($value, $routeParams[$key]);
          }
        }

        foreach ($this->middleware as $middleware)
        {
          $result = $middleware->run($endpoint, $request);

          if (!$result)
          {
            throw (new ElectraException('Request rejected by middleware'))
              ->addMetaData('middleware', get_class($middleware));
          }
        }

        // Hydrate payload from request params
        /** @var AbstractPayload $payloadClass */
        $payloadClass = $endpoint->getPayloadClass();
        $payload = $payloadClass::create();

        $requestParams = array_merge(RouteParams::getAll(), $request->all());

        $expectedPropertyTypes = $payload->getPropertyTypes();

        foreach ($requestParams as $key => $requestParam)
        {
          if (
            is_numeric($requestParam)
            && Arrays::getByKey($key, $expectedPropertyTypes) == 'integer'
          )
          {
            $requestParams[$key] = (int)$requestParam;
          }
          if (
            is_numeric($requestParam)
            && Arrays::getByKey($key, $expectedPropertyTypes) == 'double'
          )
          {
            $requestParams[$key] = (float)$requestParam;
          }

          if (
            is_string($requestParam)
            && Arrays::getByKey($key, $expectedPropertyTypes) == 'array'
          )
          {
            $requestParams[$key] = json_decode($requestParam, true);
          }
        }

        $payload = Objects::hydrate($payload, (object)$requestParams);
        $eventResponse = null;

        // Execute task
        try {
          $eventResponse = $endpoint->execute($payload);
        }
        catch (\Exception $exception)
        {
          if ($exception instanceof ElectraException && $exception->getDisplayMessage())
          {
            MessageBag::addMessage(Message::error($exception->getDisplayMessage()));
          }
          else
          {
            MessageBag::addMessage(Message::error($exception->getMessage()));
          }
        }
        // Serialize and send response
        $response = [
          'data' => $eventResponse,
          'messages' => MessageBag::getAllMessages()
        ];
        return Response::create(json_encode($response))->send();
      });
    }

    Router::init();
  }
}