<?php

namespace Electra\Web\Application;

use Electra\Utility\Objects;
use Electra\Web\Http\Request;
use Electra\Web\Http\Response;
use Electra\Web\Endpoint\AbstractEndpoint;

class Api
{
  /** @var AbstractEndpoint[] */
  protected $endpoints = [];

  /**
   * @param AbstractEndpoint $endpoint
   * @return $this
   */
  public function addEndpoint(AbstractEndpoint $endpoint)
  {
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