<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 7/28/20
 * Time: 6:37 PM
 */

namespace App\Http\Middleware;


use App\CustomObjects\HttpStatus;
use App\Exceptions\ApiException;
use App\Services\AuthService;
use Closure;

class HasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (!AuthService::validateJwt($request)) {
           return  ApiException::report(
               "Invalid token", HttpStatus::HTTP_UNAUTHORIZED, $request->getRequestUri()
           );
        } else {
            if ($roles == null || count($roles) == 0 ) return $next($request);
            $authed = AuthService::getClaims();
            foreach ($roles as $role) {
                if (in_array($role, $authed["roles"])) {
                    return $next($request);
                }
            }
            return ApiException::report(
                "Access denied", HttpStatus::HTTP_FORBIDDEN, $request->getRequestUri()
            );
        }
    }
}