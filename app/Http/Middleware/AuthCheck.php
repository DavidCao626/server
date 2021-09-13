<?php

namespace App\Http\Middleware;

use Closure;
use App\EofficeApp\Auth\Services\AuthService as Auth;
use Illuminate\Http\JsonResponse;
class AuthCheck
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth = $this->auth->check();

        if (isset($auth['code'])) {
            return new JsonResponse(error_response($auth['code'][0], $auth['code'][1]), 401);
        }
        return $next($request);
    }
}
