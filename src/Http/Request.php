<?php

namespace Electra\Web\Http;

use Illuminate\Http\Request as IlluminateRequest;

class Request extends IlluminateRequest
{
  /** @return string */
  public function getOriginDomain(): string
  {
    $origin = $this->headers->get('origin') ?? $_SERVER['HTTP_ORIGIN'];
    return parse_url($origin, PHP_URL_HOST);
  }
}
