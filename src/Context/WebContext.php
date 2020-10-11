<?php

namespace Electra\Web\Context;

use Electra\Web\Http\Cookies;
use Electra\Web\Http\Request;

trait WebContext
{
  /** @var Request */
  private $request;

  /** @return Request */
  public function request(): Request
  {
    if (!$this->request)
    {
      $this->request = Request::capture();
    }

    return $this->request;
  }

}
