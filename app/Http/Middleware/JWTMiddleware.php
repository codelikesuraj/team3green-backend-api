<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return error_response('token has expired', ['token has expired'], Response::HTTP_UNAUTHORIZED);
        } catch (TokenInvalidException $e) {
            return error_response('token is invalid', ['token is invalid'], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return error_response('token not found', ['token not found'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
