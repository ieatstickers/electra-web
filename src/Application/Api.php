<?php

namespace Electra\Web\Application;

use Electra\Utility\Objects;
use Electra\Web\Endpoint\EndpointInterface;
use Electra\Web\Http\Request;
use Electra\Web\Http\Response;

class Api
{
  /** @var EndpointInterface[] */
  protected $endpoints = [];

  /**
   * @param EndpointInterface $endpoint
   * @return $this
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
   * @throws \Exception
   */
  public function run()
  {
    foreach ($this->endpoints as $endpoint)
    {
      Router::match($endpoint->getHttpMethods(), $endpoint->getUri(), function() use ($endpoint)
      {
        // Hydrate payload from request params
        $payloadClass = $endpoint->getPayloadClass();
        $payload = new $payloadClass();
        $request = Request::capture();
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