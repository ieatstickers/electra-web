<?php

namespace Electra\Web\Endpoint;

use Electra\Core\Event\EventInterface;

interface EndpointInterface extends EventInterface
{
  /**
   * @return array
   *
   * The http methods the route will be registered for
   */
  public function getHttpMethods(): array;

  /**
   * @return string
   *
   * The URI that will be registed in the route
   */
  public function getUri(): string;
}