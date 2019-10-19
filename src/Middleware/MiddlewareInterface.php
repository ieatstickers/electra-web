<?php

namespace Electra\Web\Middleware;

use Electra\Web\Endpoint\EndpointInterface;
use Electra\Web\Http\Request;

interface MiddlewareInterface
{
  /**
   * @param EndpointInterface $endpoint
   * @param Request $request
   * @return bool
   */
  public function run(EndpointInterface $endpoint, Request $request): bool;
}