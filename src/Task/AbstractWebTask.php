<?php

namespace Electra\Web\Task;

use Electra\Core\Task\AbstractTask;

abstract class AbstractWebTask extends AbstractTask
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