<?php

namespace App\Http\Middleware;

use Closure;
use Request;
use App\EofficeApp\System\Route\Services\RouteVisitRecordService;

class RouteVisitsRecord
{
    /**
     * Create a new instance.
     *
     * @param  App\EofficeApp\IpRules\Repositories\IpRulesRepository  $repository
     * @return void
     */
    public function __construct(
        RouteVisitRecordService $routeVisitRecordService
    ) {
        $this->routeVisitRecordService = $routeVisitRecordService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->saveRouteVisitRecord();
        return $next($request);
    }

    /**
     * 保存路由访问记录
     * @return bool
     */
    public function saveRouteVisitRecord()
    {
        $path = Request::path();
        $param = Request::route()[2];

        if (!empty($param)) {
            $keys = array_map(function($v) {
                return '{'.$v.'}';
            }, array_keys($param));

            $path = str_replace(array_values($param), $keys, $path);
        }

        $method = Request::method();

        $where = [
            'route' => [$path],
            'method' => [$method]
        ];

        $visitTimes = $this->routeVisitRecordService->getRouteVisitRecordTimes($where);

        if ($visitTimes > 0) {
            return $this->routeVisitRecordService->addRouteVisitRecordTimes($where);;
        }

        $data = [
            'route'         => $path,
            'method'        => $method,
            'visit_times'   => 1,
        ];
        return $this->routeVisitRecordService->addRouteVisitRecord($data);
    }
}
