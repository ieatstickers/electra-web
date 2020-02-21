<?php

namespace Electra\Web\Middleware;

use Electra\Core\Exception\ElectraException;
use Electra\Core\Exception\ElectraUnauthorizedException;
use Electra\Jwt\Context\ElectraJwtContext;
use Electra\Jwt\ElectraJwt;
use Electra\Jwt\Event\ElectraJwtEvents;
use Electra\Jwt\Event\ParseJwt\ParseJwtPayload;
use Electra\Web\Endpoint\EndpointInterface;
use Electra\Web\Http\Request;

class JwtMiddleware implements MiddlewareInterface
{
  /**
   * @param EndpointInterface $endpoint
   * @param Request $request
   * @return bool
   * @throws ElectraException
   * @throws \Exception
   */
  public function run(EndpointInterface $endpoint, Request $request): bool
  {
    // If auth header is set
    if ($authHeaderValue = $request->header('Authorization'))
    {
      [$schema, $jwt] = explode(' ', $authHeaderValue);

      // Parse token
      $parseJwtPayload = ParseJwtPayload::create();
      $parseJwtPayload->jwt = $jwt;
      $parseJwtPayload->secret = ElectraJwtContext::getSecret();
      $parseJwtResponse = ElectraJwtEvents::parseJwt($parseJwtPayload);

      if ($parseJwtResponse->token && $parseJwtResponse->token->verified)
      {
        ElectraJwtContext::setToken($parseJwtResponse->token);
      }
    }

    // If endpoint is authenticated & no token is set
    if (
      $endpoint->requiresAuth()
      && !ElectraJwtContext::getToken()
    )
    {
      throw (new ElectraUnauthorizedException('Unauthorized'))
        ->addMetaData('endpoint', get_class($endpoint))
        ->addMetaData('token', ElectraJwtContext::getToken());
    }

    return true;
  }
}