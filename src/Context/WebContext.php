<?php

namespace Electra\Web\Context;

use Electra\Web\Http\Request;
use Electra\Web\Http\Response;

trait WebContext
{
  /** @var Request */
  private $request;
  /** @var Response */
  private $response;

  /** @return Request */
  public function request(): Request
  {
    if (!$this->request)
    {
      $this->request = Request::capture();
    }

    return $this->request;
  }

  /** @return Response */
  public function response(): Response
  {
    if (!$this->response)
    {
      $this->response = Response::create();
    }

    return $this->response;
  }

}
