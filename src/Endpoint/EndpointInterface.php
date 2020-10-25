<?php

namespace Electra\Web\Endpoint;

interface EndpointInterface
{
  /** @return bool */
  public function requiresAuth(): bool;
}
