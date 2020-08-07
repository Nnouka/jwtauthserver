<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 7/28/20
 * Time: 6:22 PM
 */

namespace App\Http\Middleware;


use App\CustomObjects\HttpStatus;
use App\Exceptions\ApiException;
use App\Services\AuthService;
use Closure;

class AuthenticateUserWithJwt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return  AuthService::validateJwt($request) ? $next($request) : ApiException::report(
            "Invalid token", HttpStatus::HTTP_UNAUTHORIZED, $request->getRequestUri()
        );
    }
}