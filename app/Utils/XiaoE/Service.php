<?php

namespace App\Utils\XiaoE;

use Request;

/**
 * 小e调用其他模块，需要对其他模块service作一些处理
 * Class Service
 * @package App\Utils\XiaoE
 */
class Service
{
    /**
     * 当前登录的用户信息
     * @var
     */
    public static $own;
    /**
     * @var 需要实例化的class
     */
    public $class;
    /**
     * 自定义的验证类
     * @var
     */
    public $permission;
    /**
     * 是否需要验证权限
     * @var
     */
    public $validate;
    /**
     * 是否拦截错误不继续执行错误
     * @var bool
     */
    public $intercept;
    /**
     * 单例
     * @var
     */
    static public $instance;

    /**
     * 私有化构造方法
     * Service constructor.
     */
    private function __construct()
    {
        $this->permission = 'App\EofficeApp\XiaoE\Permissions\XiaoEPermission';
    }

    /**
     * 获取本类的单例
     * @return Service
     */
    static public function getInstance()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    /**
     * 这里拦截一下service返回的结果
     * @param $method
     * @param $arguments
     * @return mixed|void
     */
    public function __call($method, $arguments)
    {
        if ($this->validate && !$this->validatePermission($this->class, $method, $arguments)) {
            return $this->intercept(['code' => ['0x011001', 'xiaoe']]);
        }
        $result = call_user_func_array(array(app($this->class), $method), $arguments);
        return $this->intercept($result);
    }

    /**
     * 具体拦截数据处理
     * @param $result
     */
    private function intercept($result)
    {
        $error = $this->returnResult($result);
        if ($error === false || !$this->intercept) {
            return $result;
        }
        return $this->response($error);
    }

    /**
     * 直接响应json数据
     * @param $result
     */
    private function response($result)
    {
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($result));
    }

    /**
     * 处理下service返回的错误信息
     */
    protected function returnResult($result)
    {
        if (is_array($result)) {
            if (isset($result['code'])) {
                $dynamic = isset($result['dynamic']) ? $result['dynamic'] : '';
                return error_response($result['code'][0], $result['code'][1], $dynamic);
            }
            if (isset($result['error'])) {
                $dynamic = isset($result['dynamic']) ? $result['dynamic'] : '';
                return error_response($result['error'][0], $result['error'][1], $dynamic);
            }
            if (isset($result['warning'])) {
                $dynamic = isset($result['dynamic']) ? $result['dynamic'] : '';
                return warning_response($result['warning'][0], $result['warning'][1], $dynamic);
            }
        }
        return false;
    }

    /**
     * 调用其他模块的service转换成controller来验证权限
     * @param $service
     * @param $method
     * @return bool|\Laravel\Lumen\Application|mixed
     */
    private function validatePermission($service, $method, $arguments)
    {
        if (strpos($service, 'Service') === false) {
            return true;
        }
        $permissionPath = str_replace('Services\\', 'Controllers\\', substr($service, 0, strrpos($service, 'Service'))) . 'Controller';
        $own = self::$own;
        $requestParam = [];
        $extraParam = [];
        //验证配置文件
        $permission = app($this->permission);
        if (isset($permission->config)) {
            $config = $permission->config;
            if (isset($config[$service])) {
                //替换控制器
                if (isset($config[$service]['controller']) && $config[$service]['controller']) {
                    $permissionPath = $config[$service]['controller'];
                }
                //替换参数
                if (isset($config[$service]['params']) && isset($config[$service]['params'][$method]) && $config[$service]['params'][$method]) {
                    list($requestParam, $extraParam) = $permission->{$config[$service]['params'][$method]}(Request::all(), $arguments, $own);
                }
                //验证
                if (isset($config[$service]['validate']) && isset($config[$service]['validate'][$method]) && $config[$service]['validate'][$method]) {
                    $check = $permission->{$config[$service]['validate'][$method]}(Request::all(), $arguments, $own);
                    if (!$check) {
                        return false;
                    }
                }
                //替换方法放最后
                if (isset($config[$service]['methods']) && isset($config[$service]['methods'][$method]) && $config[$service]['methods'][$method]) {
                    $method = $config[$service]['methods'][$method];
                }
            }
        }
        //非控制器里面的方法不验证
        if (!class_exists($permissionPath) || !method_exists($permissionPath, $method)) {
            return true;
        }
        //验证菜单权限
        if (!$this->validateMenuPermission($permissionPath, $method)) {
            return false;
        }
        //验证数据权限
        return $this->validateDataPermission($permissionPath, $method, $own, $requestParam, $extraParam);
    }

    /**
     * 验证菜单权限
     * @param $permissionPath
     * @param $method
     * @return bool
     */
    private function validateMenuPermission($permissionPath, $method)
    {
        $menuIds = Module::getMenuIds($permissionPath, $method);
        if ($menuIds !== true) {
            $hasPermission = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission($menuIds);
            if ($hasPermission == 'false' || !$hasPermission) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证模块对应api的数据权限的验证引擎
     * @param type $controllerPath
     * @param type $method
     * @param type $extraParam
     * @param type $responseError
     */
    private function validateDataPermission($controllerPath, $method, $own, $requestParam, $extraParam)
    {

        $hasPermission = true;
        $permissionPath = str_replace('Controllers\\', 'Permissions\\', substr($controllerPath, 0, strrpos($controllerPath, 'Controller'))) . 'Permission';
        if (class_exists($permissionPath)) {
            $permission = app($permissionPath);
            // 获取可以调用的方法
            $canCallMethod = '';
            if (method_exists($permissionPath, $method)) {
                $canCallMethod = $method;
            } else {
                $rules = $permission->rules ?? [];
                if (isset($rules[$method]) && $rules[$method]) {
                    $canCallMethod = $rules[$method];
                }
            }
            if ($canCallMethod) {
                $result = $permission->{$canCallMethod}($own, $requestParam, $extraParam);
                if (!$result) {
                    $hasPermission = false;
                }
            }
        }
        return $hasPermission;
    }
}