<?php

namespace Electra\Web\Application;

use Electra\Core\Exception\ElectraException;
use Electra\Utility\Arrays;

class RouteParams
{
  protected static $routeParams;

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

    $intValue = (int)$value;

    if ($intValue == 0 && $value !== "0")
    {
      self::$routeParams[$key] = $value;
    }
    else
    {
      self::$routeParams[$key] = $intValue;
    }

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
      $matches = [];
      preg_match_all('/{[A-z]+}/', $path, $matches);

      if ($matches)
      {
        $matches = $matches[0];
      }

      foreach ($matches as $key => $value)
      {
        $value = ltrim($value, '{');
        $value = rtrim($value, '}');
        RouteParams::add($value, $params[$key]);
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
