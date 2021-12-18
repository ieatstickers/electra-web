<?php

namespace Electra\Web\Context;

use Electra\Core\Context\ContextInterface;
use Electra\Web\Http\Request;
use Electra\Web\Http\Response;

interface WebContextInterface extends ContextInterface
{
  /** @return Request */
  public function request(): Request;

  /** @return Response */
  public function response(): Response;
}
