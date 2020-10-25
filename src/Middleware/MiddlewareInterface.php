<?php

namespace Electra\Web\Middleware;

use Electra\Core\Context\ContextAwareInterface;
use Electra\Core\Event\EventInterface;

interface MiddlewareInterface extends ContextAwareInterface
{
  /**
   * @param EventInterface $event
   *
   * @return bool
   */
  public function run(EventInterface $event): bool;
}
