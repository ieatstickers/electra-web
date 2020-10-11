<?php

namespace Electra\Web\Middleware;

use Electra\Core\Context\ContextAwareInterface;
use Electra\Core\Event\EventInterface;

interface MiddlewareInterface extends ContextAwareInterface
{
  /**
   * @param EventInterface $endpoint
   * @return bool
   */
  public function run(EventInterface $endpoint): bool;
}
