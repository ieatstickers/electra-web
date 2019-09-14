<?php

namespace Electra\Web\Endpoint;

use Electra\Core\Event\AbstractEvent;

abstract class AbstractEndpoint extends AbstractEvent implements EndpointInterface
{
  /**
   * @return array
   *
   * The http methods the route will be registered for
   */
  public function getHttpMethods(): array
  {
    return ['get'];
  }
}