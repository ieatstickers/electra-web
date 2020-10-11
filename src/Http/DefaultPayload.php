<?php

namespace Electra\Web\Http;

use Electra\Core\Event\AbstractPayload;
use Electra\Utility\Objects;

class DefaultPayload extends AbstractPayload
{
  /**
   * @param array      $data
   *
   * @return static
   */
  public static function create($data = [])
  {
    return Objects::copyAllProperties((object)$data, new static());
  }

  /**
   * @param string $name
   *
   * @return string | integer | array
   */
  public function get(string $name)
  {
    return Objects::getProperty($name, $this);
  }
}
