<?php

namespace App\Http\Middleware;

use App\EofficeApp\OpenApi\Services\OpenApiService;
use Closure;

class OpenApiMiddleware
{
    private $openApiService;

    public function __construct(OpenApiService $openApiService)
    {
        $this->openApiService = $openApiService;
    }

    public function handle($request, Closure $next)
    {

        $response = $next($request);

        $this->openApiService->openLog($response);
        //echo 'controller-end';
        // 执行动作
        return $response;
    }
}
