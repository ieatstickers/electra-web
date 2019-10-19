<?php

namespace Electra\Web\Application;

use Electra\Core\Event\AbstractPayload;
use Electra\Core\Exception\ElectraException;
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
      Router::match($endpoint->getHttpMethods(), $endpoint->getUri(), function() use ($endpoint)
      {
        $request = Request::capture();

        foreach ($this->middleware as $middleware)
        {
          $result = $middleware->run($endpoint,$request);

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
        $requestParams = $request->all();
        $payload = Objects::hydrate($payload, (object)$requestParams);
        // Execute task
        $taskResponse = $endpoint->execute($payload);
        // Serialize and send response
        return Response::create(json_encode($taskResponse))->send();
      });
    }

    Router::init();
  }
}