<?php

namespace Electra\Web\Application;

use Electra\Core\Context\Context;
use Electra\Core\Context\ContextAware;
use Electra\Core\Context\ContextInterface;
use Electra\Core\Event\AbstractPayload;
use Electra\Core\Event\EventInterface;
use Electra\Core\Event\Type\TypeInterface;
use Electra\Core\Exception\ElectraException;
use Electra\Utility\Arrays;
use Electra\Utility\Objects;
use Electra\Web\Context\WebContextInterface;
use Electra\Web\Http\Payload;
use Electra\Web\Http\Response;
use Electra\Web\Middleware\MiddlewareInterface;

/**
 * Class Application
 *
 * @package Electra\Web\Application
 * @method  WebContextInterface getContext()
 */
class Application
{
  use ContextAware;
  /**
   * @var array[]
   *
   * [
   *   "/example/path" => [
   *     "httpMethod" => "get" | "put" | "post" | "delete" | "patch" | "options",
   *     "eventClass" => "MyProject\Example\ExampleEvent" | callable(ContextInterface $ctx)
   *   ]
   * ]
   */
  protected $endpoints = [];

  /**
   * @var string|callable[] // Fqns of the middleware class
   */
  protected $middleware = [];

  /** @var callable[] */
  protected $responseMutators = [];

  /** @var string | callable */
  protected $on500;

  /**
   * Application constructor.
   *
   * @param ContextInterface $context
   */
  protected function __construct($context = null) {
    $this->setContext($context);
  }

  /**
   * @param Context $context
   *
   * @return static
   */
  public static function create($context = null)
  {
    return new static($context);
  }

  /**
   * @param string            $path
   * @param string | callable $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  public function get(string $path, $endpoint)
  {
    return $this->addEndpoint('get', $path, $endpoint);
  }

  /**
   * @param string $path
   * @param string | callable $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  public function post(string $path, $endpoint)
  {
    return $this->addEndpoint('post', $path, $endpoint);
  }

  /**
   * @param string            $path
   * @param string | callable $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  public function put(string $path, $endpoint)
  {
    return $this->addEndpoint('put', $path, $endpoint);
  }

  /**
   * @param string            $path
   * @param string | callable $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  public function patch(string $path, $endpoint)
  {
    return $this->addEndpoint('patch', $path, $endpoint);
  }

  /**
   * @param string            $path
   * @param string | callable $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  public function delete(string $path, $endpoint)
  {
    return $this->addEndpoint('delete', $path, $endpoint);
  }

  /**
   * @param string            $path
   * @param string | callable $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  public function options(string $path, $endpoint)
  {
    return $this->addEndpoint('options', $path, $endpoint);
  }

  /**
   * @param string | callable $middleware
   *
   * @return $this
   * @throws \Exception
   */
  public function addMiddleware($middleware)
  {
    $this->middleware[] = $middleware;

    return $this;
  }

  /**
   * @param callable $mutator
   *
   * @return $this
   */
  public function addResponseMutator(callable $mutator)
  {
    $this->responseMutators[] = $mutator;

    return $this;
  }

  /**
   * @param string | callable $endpoint
   *
   * @return $this
   */
  public function on404($endpoint)
  {
    Router::fallback(function () use ($endpoint) {
      return $this->executeEndpoint($endpoint);
    });

    return $this;
  }

  /**
   * @param string | callable $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  public function on500($endpoint)
  {
    if (!is_string($endpoint) && !is_callable($endpoint))
    {
      throw new \Exception("Cannot register on500 - endpoint must be an event fqns or a callable");
    }

    $this->on500 = $endpoint;

    return $this;
  }

  /** @throws \Exception */
  public function run()
  {
    foreach ($this->endpoints as $methodPath => $endpointConfig)
    {
      Router::match([$endpointConfig['httpMethod']], $endpointConfig['path'], function(...$routeParams) use ($methodPath, $endpointConfig)
      {
        $endpoint = $endpointConfig['endpoint'];
        // Capture route params
        RouteParams::capture($endpointConfig['path'], $routeParams);
        // Execute endpoint
        return $this->executeEndpoint($endpoint);
      });
    }

    Router::init();
  }

  /**
   * @param string | callable $endpoint
   * @param \Exception        $on500Exception
   *
   * @return Response
   * @throws \Exception
   */
  private function executeEndpoint($endpoint, $on500Exception = null)
  {
    // Execute endpoint
    try {
      // If endpoint is a string (fqns of an event)
      if (is_string($endpoint))
      {
        $endpointResponse = $this->executeEvent($endpoint, $on500Exception);
      }
      else
      {
        $endpointResponse = $this->executeCallable($endpoint, $on500Exception);
      }
    }
    catch (\Exception $exception)
    {
      if ($this->on500 && !$on500Exception)
      {
        return $this->executeEndpoint($this->on500, $exception);
      }

      throw $exception;
    }

    // If endpoint response is an Electra http response, return it
    if ($endpointResponse instanceof Response)
    {
      return $endpointResponse->send();
    }

    // Generate response by running any response mutators
    $responseContent = $this->generateResponse($endpointResponse);

    return $this
      ->getContext()
      ->response()
      ->setContent($responseContent)
      ->send();
  }

  /**
   * @param string $httpMethod
   * @param string $path
   * @param string $endpoint
   *
   * @return $this
   * @throws \Exception
   */
  private function addEndpoint(string $httpMethod, string $path, $endpoint)
  {
    if (isset($this->endpoints["{$httpMethod}::{$path}"]))
    {
      throw new \Exception("Cannot register route - duplicate path: {$httpMethod}::{$path}");
    }

    if (!is_string($endpoint) && !is_callable($endpoint))
    {
      throw new \Exception("Cannot register route - endpoint must be an event fqns or a callable: {$httpMethod}::{$path}");
    }

    $this->endpoints["{$httpMethod}::{$path}"] = [
      'httpMethod' => $httpMethod,
      'path' => $path,
      'endpoint' => $endpoint
    ];

    return $this;
  }

  /**
   * @param array $params
   * @param array $expectedTypes
   *
   * @return array
   */
  private function castParams(array $params, array $expectedTypes)
  {
    if (!$expectedTypes)
    {
      return $params;
    }

    foreach ($params as $key => $paramValue)
    {
      /** @var TypeInterface $expectedType */
      $expectedType = Arrays::getByKey($key, $expectedTypes);

      if (is_array($expectedType) && isset($expectedType[0]))
      {
        $expectedType = $expectedType[0];
      }

      if ($params[$key] === 'null') $params[$key] = null;

      if ($expectedType instanceof TypeInterface)
      {
        $params[$key] = $expectedType->cast($params[$key]);
      }
    }

    return $params;
  }

  /**
   * @param callable        $callable
   * @param \Exception|null $on500Exception
   *
   * @return mixed
   * @throws ElectraException
   */
  private function executeCallable(callable $callable, \Exception $on500Exception = null)
  {
    // Run all middleware (will throw an exception if request is rejected)
    $this->runMiddleware($callable);

    // Hydrate payload from request params
    $payload = Payload::create();
    $requestParams = array_merge(RouteParams::getAll(), $this->getContext()->request()->all());
    $payload = Objects::copyAllProperties((object)$this->castParams($requestParams, $payload->getPropertyTypes()), $payload);

    if ($on500Exception)
    {
      $payload->exception = $on500Exception;
    }

    return $callable($payload);
  }

  /**
   * @param string          $endpoint
   * @param \Exception|null $on500Exception
   *
   * @return mixed
   * @throws ElectraException
   */
  private function executeEvent(string $endpoint, \Exception $on500Exception = null)
  {
    // Instantiate class
    /** @var EventInterface $event */
    $event = new $endpoint();

    // Throw an error if it's not an event
    if (!($event instanceof EventInterface))
    {
      $endpoint = get_class($event);
      $eventInterface = EventInterface::class;
      throw new ElectraException("Route event class $endpoint must implement $eventInterface");
    }

    $event->setContext($this->getContext());

    // Run all middleware (will throw an exception if request is rejected)
    $this->runMiddleware($event);

    // Hydrate payload from request params
    /** @var AbstractPayload $payloadClass */
    $payloadClass = $event->getPayloadClass();
    $requestParams = array_merge(RouteParams::getAll(), $this->getContext()->request()->all());

    if ($payloadClass)
    {
      $eventPayload = $payloadClass::create();
      $eventPayload = Objects::hydrate(
        $eventPayload,
        (object)$this->castParams($requestParams, $eventPayload->getPropertyTypes())
      );
    }
    else
    {
      $eventPayload = Payload::create();
      $eventPayload = Objects::copyAllProperties(
        (object)$this->castParams($requestParams, $eventPayload->getPropertyTypes()),
        $eventPayload
      );
    }

    $endpointResponse = null;

    if ($on500Exception)
    {
      $eventPayload->exception = $on500Exception;
    }

    // Execute event
    return $event->execute($eventPayload);
  }

  /**
   * @param mixed $response
   *
   * @return mixed
   */
  private function generateResponse($response)
  {
    foreach ($this->responseMutators as $mutator)
    {
      $response = $mutator($response);
    }

    if (is_string($response))
    {
      $responseContent = $response;
    }
    else if (method_exists($response, '__toString'))
    {
      $responseContent = $response->__toString();
    }
    else if ($response instanceof \JsonSerializable)
    {
      $responseContent = $response->jsonSerialize();
    }
    else
    {
      $responseContent = json_encode($response);
    }

    return $responseContent;
  }

  /**
   * @param $event
   *
   * @return $this
   * @throws ElectraException
   */
  private function runMiddleware($event)
  {
    foreach($this->middleware as $middlewareItem)
    {
      /** @var MiddlewareInterface $middleware */
      $middleware = null;

      // Middleware class
      if(is_string($middlewareItem))
      {
        $middleware = new $middlewareItem();

        if(!($middleware instanceof MiddlewareInterface))
        {
          $middlewareClass = get_class($middleware);
          $middlewareInterface = MiddlewareInterface::class;
          throw new \Exception("Middleware $middlewareClass must implement $middlewareInterface");
        }

        $middleware->setContext($this->getContext());

        // Set context on middleware
        $result = $middleware->run($event);
      }
      // Middleware callable
      else if(is_callable($middlewareItem))
      {
        $result = $middlewareItem($event);
      }
      else
      {
        throw new ElectraException('Middle must be a fqns of a middleware class or a callable');
      }

      if(!is_bool($result))
      {
        throw new ElectraException('Middleware must return a boolean');
      }

      if (!$result)
      {
        $middlewareClass = $middleware ? get_class($middleware) : 'callable';
        throw (new ElectraException("Request rejected by middleware:"))
          ->addMetaData('middleware', $middlewareClass);
      }
    }

    return $this;
  }
}
