<?php

namespace Electra\Web\Http;

use Electra\Utility\Arrays;

class Payload
{
  /** @var array */
  protected $params;

  /**
   * @param array $params
   *
   * @param array $expectedTypes
   *
   * @return static
   */
  public static function create(array $params, array $expectedTypes = null)
  {
    $payload = new static();
    $payload->setParams(self::castParams($params, $expectedTypes));
    return $payload;
  }

  /**
   * @param string $name
   *
   * @return string | integer | array
   */
  public function get(string $name)
  {
    return Arrays::getByKey($name, $this->getAll());
  }

  /** @return array */
  public function getAll(): array
  {
    return $this->params;
  }

  /**
   * @param array $params
   *
   * @return $this
   */
  public function setParams(array $params)
  {
    $this->params = $params;
    return $this;
  }

  /**
   * @param array $params
   * @param array $expectedTypes
   *
   * @return array
   */
  private static function castParams(array $params, ?array $expectedTypes)
  {
    if (!$expectedTypes)
    {
      return $params;
    }

    foreach ($params as $key => $paramValue)
    {
      if (
        is_numeric($paramValue)
        && Arrays::getByKey($key, $expectedTypes) == 'integer'
      )
      {
        $params[$key] = (int)$paramValue;
      }
      if (
        is_numeric($paramValue)
        && Arrays::getByKey($key, $expectedTypes) == 'double'
      )
      {
        $params[$key] = (float)$paramValue;
      }

      if (
        is_string($paramValue)
        && Arrays::getByKey($key, $expectedTypes) == 'array'
      )
      {
        $params[$key] = json_decode($paramValue, true);
      }
    }

    return $params;
  }


}
