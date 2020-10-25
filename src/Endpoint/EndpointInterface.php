<?php

namespace Electra\Web\Endpoint;

use Electra\Core\Event\AbstractPayload;

interface EndpointInterface
{
  /** @return bool */
  public function requiresAuth(): bool;

  /**
   * @param AbstractPayload $payload
   *
   * @return bool
   */
  public function hasAccess($payload): bool;
}
