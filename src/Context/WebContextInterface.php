<?php

namespace Electra\Web\Context;

use Electra\Core\Context\ContextInterface;
use Electra\Web\Http\Request;

interface WebContextInterface extends ContextInterface
{
  /** @return Request */
  public function request(): Request;
}
