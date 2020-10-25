<?php

namespace Electra\Web\Http;

use Electra\Core\Event\AbstractPayload;
use Electra\Utility\Objects;

class Payload extends AbstractPayload
{
  /**
   * @param array      $data
   *
   * @return Payload|object
   */
  public static function create($data = [])
  {
    return Objects::copyAllProperties((object)$data, new static());
  }
}
