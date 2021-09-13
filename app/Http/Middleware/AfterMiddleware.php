<?php

namespace App\Http\Middleware;

use Closure;

class AfterMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        //echo 'controller-end';
        // 执行动作
        return $response;
    }
}
