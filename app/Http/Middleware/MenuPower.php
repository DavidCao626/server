<?php

namespace App\Http\Middleware;

use Cache;
use Closure;
use Illuminate\Http\JsonResponse;
use Lang;
class MenuPower {

    public function handle($request, Closure $next)
    {
        //获取token
        $token = $request->input('api_token');

        if (empty($token)) {
            $token = $request->bearerToken();
        }

        if (empty($token)) {
            $token = $request->getPassword();
        }

        if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'DingTalk') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Eoffice') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false)) {
            if (!empty($token) && Cache::has($token)) {
                $userInfo = Cache::get($token);

                $userId = isset($userInfo->user_id) ? $userInfo->user_id : '';

                Lang::setLocale(app('App\EofficeApp\Lang\Services\LangService')->getUserLocale($userId));

                if (!empty($userId)) {
                    // 每天重新查询一次授权是否过期
                    $checkUserMobileEmpowerKey = $userId . '_check_mobile_empower_' . date('Y-m-d');
                    if (!Cache::has($checkUserMobileEmpowerKey)) {
                        $checkMobileEmpower = app('App\EofficeApp\Auth\Services\AuthService')->checkMobileEmpowerAndWapAllow($userId);
                        if (isset($checkMobileEmpower['code'])) {
                            if (isset($checkMobileEmpower['code'][0]) && isset($checkMobileEmpower['code'][1])) {
                                return new JsonResponse(error_response($checkMobileEmpower['code'][0], $checkMobileEmpower['code'][1]), 200);
                            }
                        } else {
                            Cache::add($checkUserMobileEmpowerKey, 1, 1440);
                        }
                    }
                }
            }
        } else {
            if (empty($token)) {
                return new JsonResponse(error_response('0x003001', 'auth'), 200);
            }

            if (!Cache::has($token)) {
                return new JsonResponse(error_response('0x003003', 'auth'), 200);
            }
        }


        //获取路由配置
        $moduleDir = base_path() . '/app/EofficeApp';
        $modules = get_current_module();



        $file = $this->getRoutes($moduleDir, $modules);

        if (is_file($file)) {
            require $file;
        }
        //$routeConfig

        $routeConfig = collect($routeConfig)->mapWithKeys(function($item) {
            return [$item[1] => $item];
        });

        //是否存在路由数组中
        $funcitonName = explode('@', $request->route()[1]['uses'])[1];

        if (isset($routeConfig[$funcitonName])) {
            $allowMenu = [];

            if (isset($routeConfig[$funcitonName][2]) && is_array($routeConfig[$funcitonName][2])) {
                $allowMenu = $routeConfig[$funcitonName][2];
            }

            if ($allowMenu == [] && isset($routeConfig[$funcitonName][3])) {
                $allowMenu = $routeConfig[$funcitonName][3];
            }

            if ($allowMenu) {
                //用户信息
                $userInfo = Cache::get($token);
                $userId = isset($userInfo->user_id) ? $userInfo->user_id : '';
                Lang::setLocale(app('App\EofficeApp\Lang\Services\LangService')->getUserLocale($userId));

                if (!is_array($allowMenu)) {
                    return new JsonResponse(error_response('0x000013', 'common'), 200);
                }

                $userMenus = $userInfo->menus['menu'];

                if (!array_intersect($userMenus, $allowMenu)) {
                    return new JsonResponse(error_response('0x000006', 'common'), 200);
                }
            }
        }

        return $next($request);
    }

    public function getRoutes($moduleDir, $module) 
    {
        if (is_array($module)) {
            $file = $moduleDir . '/' . $module[0] . '/' . $module[1] . '/routes.php';
        } else {
            $file = $moduleDir . '/' . $module . '/routes.php';
        }
        
        return $file;
    }

}
