<?php

namespace Electra\Web\Application;

use Bramus\Router\Router as BramusRouter;

class Router
{
  /** @var BramusRouter */
  private static $bramusRouter;

  /** @return BramusRouter */
  private static function getBramusRouter()
  {
    if (!self::$bramusRouter)
    {
      self::$bramusRouter = new BramusRouter();
    }

    return self::$bramusRouter;
  }

  /**
   * @param string $uri
   * @param $callback
   * @throws \Exception
   */
  public static function get(string $uri, $callback)
  {
    self::match(['get'], $uri, $callback);
  }

  /**
   * @param string $uri
   * @param $callback
   * @throws \Exception
   */
  public static function post(string $uri, $callback)
  {
    self::match(['post'], $uri, $callback);
  }

  /**
   * @param string $uri
   * @param $callback
   * @throws \Exception
   */
  public static function put(string $uri, $callback)
  {
    self::match(['put'], $uri, $callback);
  }

  /**
   * @param string $uri
   * @param $callback
   * @throws \Exception
   */
  public static function patch(string $uri, $callback)
  {
    self::match(['patch'], $uri, $callback);
  }

  /**
   * @param string $uri
   * @param $callback
   * @throws \Exception
   */
  public static function delete(string $uri, $callback)
  {
    self::match(['delete'], $uri, $callback);
  }

  /**
   * @param string $uri
   * @param $callback
   * @throws \Exception
   */
  public static function options(string $uri, $callback)
  {
    self::match(['options'], $uri, $callback);
  }

  /**
   * @param array $requestMethods
   * @param string $uri
   * @param callable $callback
   * @throws \Exception
   */
  public static function match(array $requestMethods, string $uri, callable $callback)
  {
    $validHttpMethods = self::getValidHttpMethods();

    foreach ($requestMethods as $httpMethod)
    {
      if (!in_array(strtolower($httpMethod), $validHttpMethods))
      {
        $availableOptions = implode(', ', $validHttpMethods);

        throw new \Exception("Cannot register route. Invalid http method supplied: $httpMethod. Available options: $availableOptions");
      }

      self::getBramusRouter()->{$httpMethod}(self::replaceRoutePathPlaceholders($uri), $callback);
    }
  }

  /**
   * @param string $uri
   * @param callable $callback
   * @throws \Exception
   */
  public static function any(string $uri, callable $callback)
  {
    self::match(self::getValidHttpMethods(), $uri, $callback);

  }

  /**
   * @param callable $callback
   */
  public static function fallback(callable $callback)
  {
    self::getBramusRouter()->set404($callback);
  }

  /**
   * @param AbstractMiddleware $middleware
   * @return void
   * @throws \Exception
   */
  public static function registerMiddleware(AbstractMiddleware $middleware)
  {
    $validHttpMethods = self::getValidHttpMethods();
    $middlewareHttpMethods = $middleware->getHttpMethods();

    foreach ($middlewareHttpMethods as $httpMethod)
    {
      if (!in_array($httpMethod, $validHttpMethods))
      {
        $availableOptions = implode(', ', $validHttpMethods);

        throw new \Exception("Cannot register route. Invalid http method supplied: $httpMethod. Available options: $availableOptions");
      }
    }

    self::getBramusRouter()->before(
      strtoupper(implode('|', $middlewareHttpMethods)),
      $middleware->getRoutePattern(),
      function() use ($middleware)
      {
        $result = $middleware->run();

        if (!$result)
        {
          exit;
        }
      }
    );
  }

  /**
   * @return void
   */
  public static function init()
  {
    self::getBramusRouter()->run();
  }

  /**
   * @return array
   */
  private static function getValidHttpMethods(): array
  {
    return ['get', 'post', 'put', 'patch', 'delete', 'options'];
  }

  /**
   * @param string $routePath
   * @return string
   *
   * Bramus allows regex in the uri to match parameters e.g. /products/{productId:([0-9]+)}
   * This method converts placeholders e.g. /products/{productId:int}
   */
  private static function replaceRoutePathPlaceholders(string $routePath): string
  {
    // Get all placeholders
    preg_match_all(
      "/(\{([^\}]+)\})/",
      $routePath,
      $matches);

    // For each placeholder
    foreach ($matches[2] as $match)
    {
      // Split by colon
      $matchParts = explode(':', $match);

      // Default type to null
      $type = null;

      if (count($matchParts) == 2)
      {
        // Set type
        $type = $matchParts[1];
      }

      $typeRegex = [
        'int' => '(\d+)',
        'string' => '([a-z0-9_-]+)'
      ];

      // If we have a replacement
      if ($type && isset($typeRegex[$type]))
      {
        // Make the replacement
        $routePath = str_replace('{' . $match . '}', $typeRegex[$type], $routePath);
      }
    }

    return $routePath;
  }
}