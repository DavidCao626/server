<?php

namespace App\Utils\XiaoE;
/**
 * 小e与其他模块的集成的使用方法
 * Class Module
 * @package App\Utils\XiaoE
 */
class Module
{
    /**
     * 引入的模块路由
     * @var array
     */
    public static $routeFile = [];

    public static function getMenuIds($permissionPath, $funcitonName)
    {
        $moduleDir = base_path() . '/app/EofficeApp';
        $modules = explode('\\', $permissionPath)[2];
        $file = self::getRoutes($moduleDir, $modules);
        if (isset(self::$routeFile[$file])) {
            $routeConfig = self::$routeFile[$file];
        } elseif (is_file($file)) {
            require_once $file;
            //这里针对小e模块的权限控制，请求当前模块的路由，路由文件在框架里面引入过了，会有问题，处理下
            if (!isset($routeConfig)) {
                require $file;
            }
            self::$routeFile[$file] = $routeConfig;
        } else {
            return true;
        }
        $routeConfig = collect($routeConfig)->mapWithKeys(function ($item) {
            return [$item[1] => $item];
        });
        //是否存在路由数组中
        if (isset($routeConfig[$funcitonName])) {
            $allowMenu = [];

            if (isset($routeConfig[$funcitonName][2]) && is_array($routeConfig[$funcitonName][2])) {
                $allowMenu = $routeConfig[$funcitonName][2];
            }

            if ($allowMenu == [] && isset($routeConfig[$funcitonName][3])) {
                $allowMenu = $routeConfig[$funcitonName][3];
            }
            if ($allowMenu) {
                return $allowMenu;
            }
        }
        return true;
    }

    public static function getRoutes($moduleDir, $module)
    {
        if (is_array($module)) {
            $file = $moduleDir . '/' . $module[0] . '/' . $module[1] . '/routes.php';
        } else {
            $file = $moduleDir . '/' . $module . '/routes.php';
        }
        return $file;
    }
}