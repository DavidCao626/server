<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Cache;
use Lang;

class ModulePermissionsCheck
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
        $prefix = $request->segment(2);

        $ignores = ['system', 'server-manage', 'mobile'];
        if(in_array($prefix, $ignores)) {
            return $next($request);
        }
        // 集成中心及部分子模块  无需判断模块权限
        $integrations = ['integration-center', 'high-meter-integration', 'yonyou-voucher'];
        if(in_array($prefix, $integrations)) {
            return $next($request);
        }
        $module = $this->toCamelCase($prefix);

        $moduleId = config('module.' . $module);

        if($module){
            $token = $this->getTokenForRequest($request);
            $user = Cache::get($token);
            $menus = $user->menus['menu'] ?? [];
            if (ecache('Lang:Local')->get($token)) {
                Lang::setLocale(ecache('Lang:Local')->get($token));
            }
            if(empty($menus) || ($moduleId && !in_array($moduleId, $menus))) {
                $moduleLang = trans('common.0x000024',['module'=> trans('common.' . strtolower($module))]);

                return new JsonResponse(error_response('0x000024', 'common', $moduleLang), 200);
            }
        }

        return $next($request);
    }
    /**
     * 获取token
     * @return type
     */
    private function getTokenForRequest($request)
    {
        $token = $request->input('api_token');

        if (empty($token)) {
            $token = $request->bearerToken();
        }

        if (empty($token)) {
            $token = $request->getPassword();
        }

        return $token;
    }
    /**
     * 转驼峰
     *
     * @param string $str
     * @param string $delimter
     *
     * @return string
     */
    private function toCamelCase($str, $delimter = '-')
    {
        $array = explode($delimter, $str);

        $name = array_reduce($array, function($carry, $item) {
            return $carry . ucfirst($item);
        });

        return $name;
    }
}
