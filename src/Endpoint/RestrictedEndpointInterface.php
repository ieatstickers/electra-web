<?php

namespace Electra\Web\Endpoint;

use Electra\Core\Event\AbstractPayload;

interface RestrictedEndpointInterface
{
  /**
   * @param AbstractPayload $payload
   *
   * @return bool
   */
  public function hasAccess($payload): bool;
}
