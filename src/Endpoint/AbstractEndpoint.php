<?php

namespace Electra\Web\Endpoint;

use Electra\Core\Event\AbstractEvent;

abstract class AbstractEndpoint extends AbstractEvent
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

  /**
   * @return string
   *
   * The URI that will be registed in the route
   */
  abstract public function getUri(): string;
}