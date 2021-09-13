<?php

namespace App\EofficeApp\Base;

use Lang;
use Request;
use Illuminate\Validation\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;
use Cache;
use Illuminate\Support\Arr;
class Controller extends BaseController
{
    protected $own;

    /** @var string 表单验证错误码*/
    private $formValidationCode;
    public $apiToken;
    private $authControllerPath = 'App\EofficeApp\Auth\Controllers\AuthController';
    protected $phone_number;
    public function __construct()
    {
        $this->boot();
    }
    /**
     * 进入系统初始化引导函数
     */
    private function boot()
    {
        $route = Request::route();

        list($controllerPath, $method) = explode('@', $route[1]['uses']);

        $this->register($controllerPath, $method, function() {
            $this->registerOwnInfo();
            if (envOverload('APP_DEBUG', false)) {
                //$this->middleware("routeVisitsRecord");
            }
        })->validatePermission($controllerPath, $method, $route[2], function($code) {
            if (isset($code['dynamic'])){
                echo json_encode(error_response($code[0], $code[1],$code['dynamic'])); exit;
            }else{
                echo json_encode(error_response($code[0], $code[1])); exit;
            }

        });
    }
    /**
     * 系统初始化信息注册
     */
    private function register($controllerPath, $method, $terminal)
    {
        $this->setLocale();

        if($controllerPath != $this->authControllerPath){
            $terminal();
        } else {
            require dirname(__DIR__) . '/Auth/routes.php';

            $methods = array_column($routeConfig, 1);

            if(in_array($method, $methods)) {
                $terminal();
            }
        }

        return $this;
    }
    /**
     * 验证模块对应api的数据权限的验证引擎
     * @param type $controllerPath
     * @param type $method
     * @param type $extraParam
     * @param \Closure $responseError
     */
    private function validatePermission($controllerPath, $method, $extraParam, $responseError)
    {

        $permissionPath = str_replace('Controllers\\', 'Permissions\\', substr($controllerPath, 0, strrpos($controllerPath, 'Controller'))). 'Permission';
        if(class_exists($permissionPath)) {
            $permission = app($permissionPath);
            // 获取可以调用的方法
            $canCallMethod = '';
            if(method_exists($permissionPath, $method)) {
                $canCallMethod = $method;
            } else {
                $rules = $permission->rules ?? [];
                if(isset($rules[$method]) && $rules[$method]) {
                    $canCallMethod = $rules[$method];
                }
            }
            if($canCallMethod) {
                $result = $permission->{$canCallMethod}($this->own, Request::all(), $extraParam);
                if(!$result) {
                    $responseError(['0x000006', 'common']);
                } else if (isset($result['code'])) {
                    if (isset($result['dynamic'])){
                        $responseError([$result['code'][0],$result['code'][1],'dynamic'=>$result['dynamic']]);
                    }else{
                        $responseError($result['code']);
                    }

                }
            }
        }
    }
    /**
     * 注册个人信息
     */
    private function registerOwnInfo()
    {
        if($authInfo = $this->getAuthInfo()) {
            $this->own = [
                'user_id'                => $authInfo->user_id,
                'user_name'              => $authInfo->user_name,
                'user_accounts'          => $authInfo->user_accounts,
                'user_job_number'        => $authInfo->user_job_number ?? '',
                'dept_id'                => $authInfo->userHasOneSystemInfo->dept_id ?? ($authInfo->dept_id ?? null),
                'dept_name'              => $authInfo->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name ?? ($authInfo->dept_name ?? null),
                'roles'                  => $authInfo->roles,
                'role_id'                => isset($authInfo->roles) ? array_column($authInfo->roles,'role_id') : $this->parseRoles($authInfo['userHasManyRole'],'role_id'),
                'role_name'                => isset($authInfo->roles) ? array_column($authInfo->roles,'role_name') : $this->parseRoles($authInfo['userHasManyRole'],'role_name'),
                'menus'                  =>  $authInfo->menus ?? [],
                'post_priv'              => $authInfo->userHasOneSystemInfo->post_priv ?? ($authInfo->post_priv ?? 0),
                'max_role_no'            => $authInfo->userHasOneSystemInfo->max_role_no ?? ($authInfo->max_role_no ?? 0),
                'user_position_name'     => $authInfo->user_position_name ?? '',
                'user_area_name'         => $authInfo->user_area_name ?? '',
                'user_city_name'         => $authInfo->user_city_name ?? '',
                'user_workplace_name'    => $authInfo->user_workplace_name ?? '',
                'user_job_category_name' => $authInfo->user_job_category_name ?? '',
                'post_dept'              => $authInfo->userHasOneSystemInfo->post_dept ?? ($authInfo->post_dept ?? ''),
                'phone_number'           => $authInfo->userHasOneInfo->phone_number ?? ($authInfo->phone_number ?? ''),
            ];
            $this->phone_number = $authInfo->phone_number ?? '';
        }
    }
    private function parseRoles($roles,$type)
    {
        if(empty($roles)) {
            return [];
        }
        static $parseRole = array();

        if(!empty($parseRole)){
            return $parseRole[$type];
        }
        $roleId = $roleName = $roleArray = [];

        foreach ($roles as $role) {
            $roleId[]   = $role['role_id'];
            $roleName[] = $roleArray[$role['role_id']] = $role['hasOneRole']['role_name'];
        }

        $parseRole = ['role_id' => $roleId,'role_name' => $roleName,'role_array' => $roleArray];

        return $parseRole[$type];
    }


    private function parseSuperiorSubordinate($dataObj, $type)
    {
        $data = $dataObj->toArray();

        if (empty($data)) {
            return [];
        }

        if ($type == 'superior') {
            $k = 'superior_user_id';
        } else if ($type == 'subordinate') {
            $k = 'user_id';
        }

        return array_column($data, $k);
    }

    /**
     * 获取权限信息
     * @return boolean
     */
    private function getAuthInfo()
    {
        $token = $this->getApiToken();
        $this->apiToken = $token;
        if($token) {
                return Cache::get($token);
        }

        return false;
    }
    private function getApiToken()
    {
        static $apiToken = null;

        if($apiToken != null){
            return $apiToken;
        } else {
            $token = Request::input('api_token');
            if (empty($token)) {
                $token = Request::bearerToken();
            }

            if (empty($token)) {
                $token = Request::getPassword();
            }
            if($token) {
                return $apiToken = $token;
            }

            return $apiToken = false;
        }
    }
    /**
     * 批量处理返回值
     *
     * @param array|int $result 返回结果
     *
     * @return array json格式返回值
     *
     * @author qishaobo
     *
     * @since  2016-02-17 创建
     */
    protected function returnResult($result){
        if(is_array($result)) {
            if (isset($result['code']) && isset($result['code'][0]) && isset($result['code'][1])) {
                $dynamic = isset($result['dynamic']) ? $result['dynamic'] : '';

                return error_response($result['code'][0], $result['code'][1], $dynamic);
            }

            if (isset($result['error']) && isset($result['error'][0]) && isset($result['error'][1])) {
                $dynamic = isset($result['dynamic']) ? $result['dynamic'] : '';

                return error_response($result['error'][0], $result['error'][1], $dynamic);
            }

            if (isset($result['warning']) && isset($result['warning'][0]) && isset($result['warning'][1])) {
                $dynamic = isset($result['dynamic']) ? $result['dynamic'] : '';

                return warning_response($result['warning'][0], $result['warning'][1], $dynamic);
            }
        }
        return success_response($result === true ? false : $result);
    }

    /**
     * 设置本地化语言
     *
     * @return
     *
     * @author qishaobo
     *
     * @modify lizhijun
     *
     * @since  2016-02-17 创建
     */
    private function setLocale(){
        $token = $this->getApiToken();

        if($token) {
            if (ecache('Lang:Local')->get($token)) {
                Lang::setLocale(ecache('Lang:Local')->get($token));
            }
        } else {
            if (Request::has('local')) {
                Lang::setLocale(Request::input('local'));
            }
        }

        return true;
    }

    /**
     * 表单验证
     *
     * @param \Illuminate\Http\Request $request 请求
     * @param \App\EofficeApp\Base\Request $formRequest 验证规则类
     *
     * @return
     *
     * @author qishaobo
     *
     * @since  2016-02-17 创建
     */
    protected function formFilter($request, $formRequest) {
        $method = $request->method();
        if ($method == 'POST' || $method == 'PUT') {
            $this->formValidationCode = $formRequest->errorCode ? : '0x000111';
            $this->rules = $formRequest->rules($request);
            $this->validate($request, $this->rules);
        }
    }

    /**
     * 表单验证失败返回
     *
     * @return json 表单验证失败返回结果
     *
     * @author qishaobo
     *
     * @since  2016-02-17 创建
     */
    protected function formatValidationErrors(Validator $validator)
    {
        $error = $validator->errors()->all();
        $rules = array_keys($this->rules);
        $search = $replace = [];
        $class = Request::route()[1]['uses'];
        $classNames = explode('\\', explode('@', $class)[0]);
        $className = str_replace('controller', '', strtolower(array_pop($classNames)));

        foreach ($rules as $field) {
            $search[] = str_replace('_', ' ', $field);
            $replace[] = trans($className.'.'.$field);
        }

        foreach ($error as $k => $v) {
            $error[$k] = str_replace($search, $replace, $v);
        }

        echo json_encode(error_response(array_fill(0, count($error), $this->formValidationCode), '', $error));
        exit;
        /*
        $errors = [
            array_fill(0, count($error), $this->formValidationCode),
            $error
        ];
        return $this->returnResult(['error' => $errors]);
        */
    }

    /**
     * 获取当前所有的请求参数，且将指定key替换成当前登录用户id
     * @param string $key 如：user_id、search.user_id
     * @return mixed
     */
    protected function getAllInputAndCurUserId($key = 'user_id')
    {
        $input = Request::all();
        if (Arr::has($input, $key)) {
            Arr::set($input, $key, $this->own['user_id']);
        }

        return $input;
    }
}
