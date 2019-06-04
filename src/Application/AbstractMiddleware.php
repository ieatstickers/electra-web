<?php

namespace Electra\Web\Application;

abstract class AbstractMiddleware
{
  /**
   * @return array
   *
   * The http methods this middleware will run for. Defaults to all.
   */
  public function getHttpMethods(): array
  {
    return [ 'get', 'post', 'put', 'patch', 'delete', 'options' ];
  }

  /**
   * @return string
   *
   * The regex pattern that matches the URIs of the routes this middleware will
   * run for. Defaults to all routes/uris.
   */
  public function getRoutePattern()
  {
    return '.*';
  }

  /**
   * @return bool
   *
   * Middlware logic. If this method returns true, the request will continue to
   * the next middleware. If false is returned, the request will be interrupted.
   */
  abstract public function run(): bool;

  /**
   * @return mixed
   *
   * If the run method returns false, this method will be called so a relevant
   * response can be returned.
   */
  abstract public function onFailure();
}