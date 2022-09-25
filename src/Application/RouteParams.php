<?php

namespace Electra\Web\Application;

use Electra\Core\Exception\ElectraException;
use Electra\Utility\Arrays;

class RouteParams
{
  /** @var array */
  protected static $routeParams = [];

  /**
   * @param string $key
   * @param $value
   * @return array
   * @throws ElectraException
   */
  public static function add(string $key, $value): array
  {
    if (isset(self::$routeParams[$key]))
    {
      throw (new ElectraException('Duplicate route parameter key'))
        ->addMetaData('routeParameterKey', $key);
    }

    self::$routeParams[$key] = is_numeric($value) ? (int)$value : $value;

    return self::$routeParams;
  }

  /**
   * @param string $path
   * @param mixed  ...$params
   *
   * @throws ElectraException
   */
  public static function capture(string $path, ...$params)
  {
    if ($params)
    {
      $params = Arrays::getByKey(0, $params);
      $matches = [];
      preg_match_all('/{[A-z]+}/', $path, $matches);

      if ($matches)
      {
        $matches = Arrays::getByKey(0, $matches);
      }

      foreach ($matches as $key => $value)
      {
        $value = ltrim($value, '{');
        $value = rtrim($value, '}');
        RouteParams::add($value, Arrays::getByKey($key, $params));
      }
    }
  }

  /**
   * @param string $key
   * @return mixed|null
   */
  public static function get(string $key)
  {
    return Arrays::getByKey($key, self::$routeParams);
  }

  /** @return array */
  public static function getAll(): array
  {
    return self::$routeParams;
  }
}
