<?php

namespace App\EofficeApp\Sso\Services;

use App\EofficeApp\Base\BaseService;
use Cache;
use Illuminate\Support\Facades\DB;

/**
 * 单点登录服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class SsoService extends BaseService
{

    /** @var object 外部系统配置：用户设置资源库变量 */
    private $ssoRepository;

    /** @var object 系统配置：单点登录设置资源库变量 */
    private $ssoLoginRepository;
    private $userRepository;
    private $userService;
    private $heterogeneousSystemService;

    public function __construct()
    {
        $this->ssoRepository = 'App\EofficeApp\Sso\Repositories\SsoRepository';
        $this->ssoLoginRepository = 'App\EofficeApp\Sso\Repositories\SsoLoginRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserServices';
        $this->heterogeneousSystemService = 'App\EofficeApp\UnifiedMessage\Services\HeterogeneousSystemService';
    }

    /**
     * 访问单点登录配置列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getSsoList($data)
    {
        return $this->response(app($this->ssoRepository), 'getTotal', 'getSsoList', $this->parseParams($data));
    }

    /**
     * 增加单点登录配置
     *
     * @param array $data
     *
     * @return  int
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addSso($data)
    {
        //处理多个参数--start
        $ssoSysParam = isset($data['sso_sys_param']) ? $data['sso_sys_param'] : array();
        if (empty($ssoSysParam)) {
            $data['sso_sys_param'] = array();
        } else {
            foreach ($ssoSysParam as $key => $value) {
                if (empty($value['key'])) {
                    unset($ssoSysParam[$key]);
                }
            }
        }
        $ssoSysParam = array_values($ssoSysParam);
        $data['sso_sys_param'] = json_encode($ssoSysParam, JSON_UNESCAPED_UNICODE);
        //--end

        $where['search'] = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v['user_id'])) {
                Cache::forget('custom_menus_' . $v['user_id']);
            }
        }
        $ssoData = array_intersect_key($data, array_flip(app($this->ssoRepository)->getTableColumns()));
        $result = app($this->ssoRepository)->insertData($ssoData);
        return $result->sso_id;
        /*dd($ssoSysParam);
        $temt = json_decode($data['sso_sys_param'], true);
        $sso_sys_param = "";
        foreach ($temt as $v) {
            if ($v['key'] && $v['value']) {
                $sso_sys_param.= $v['key'] . "=" . $v['value'] . "&";
            }
        }
        $where['search']   = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v['user_id'])) {
                Cache::forget('custom_menus_' . $v['user_id']);
            }
        }
        $data['sso_sys_param'] = trim($sso_sys_param, "&");
        $ssoData = array_intersect_key($data, array_flip(app($this->ssoRepository)->getTableColumns()));
        $result = app($this->ssoRepository)->insertData($ssoData);
        return $result->sso_id;*/
    }

    /**
     * 编辑单点登录配置
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editSso($data)
    {
        $ssoInfo = app($this->ssoRepository)->infoSso($data['sso_id']);
        if (count($ssoInfo) == 0) {
            return ['code' => ['0x031002', 'sso']];
        }
        $where['search'] = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v['user_id'])) {
                Cache::forget('custom_menus_' . $v['user_id']);
            }
        }
        //处理多个参数--start
        $ssoSysParam = isset($data['sso_sys_param']) ? $data['sso_sys_param'] : [];
        if (empty($ssoSysParam)) {
            $data['sso_sys_param'] = [];
        } else {
            foreach ($ssoSysParam as $key => $value) {
                if (empty($value['key'])) {
                    unset($ssoSysParam[$key]);
                }
            }
        }
        $ssoSysParam = array_values($ssoSysParam);
        $data['sso_sys_param'] = json_encode($ssoSysParam, JSON_UNESCAPED_UNICODE);
        // --end
        $ssoData = array_intersect_key($data, array_flip(app($this->ssoRepository)->getTableColumns()));
        return app($this->ssoRepository)->updateData($ssoData, ['sso_id' => $data['sso_id']]);
        /*$ssoInfo = app($this->ssoRepository)->infoSso($data['sso_id']);
        if (count($ssoInfo) == 0) {
            return ['code' => ['0x031002', 'sso']];
        }
        $where['search']   = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v['user_id'])) {
                Cache::forget('custom_menus_' . $v['user_id']);
            }
        }

        $temt = json_decode($data['sso_sys_param'], true);
        $sso_sys_param = "";
        foreach ($temt as $v) {
            if (isset($v['key']) && $v['key'] && isset($v['value']) && $v['value']) {
                $sso_sys_param.= $v['key'] . "=" . $v['value'] . "&";
            }
        }

        $data['sso_sys_param'] = trim($sso_sys_param, "&");

        $ssoData = array_intersect_key($data, array_flip(app($this->ssoRepository)->getTableColumns()));
        return app($this->ssoRepository)->updateData($ssoData, ['sso_id' => $data['sso_id']]);*/
    }

    /**
     * 删除单点登录配置
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function deleteSso($data)
    {
        $destroyIds = explode(",", $data['sso_id']);
        $where['search'] = [
            "user_accounts" => ["", "!="]
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v['user_id'])) {
                Cache::forget('custom_menus_' . $v['user_id']);
            }
        }
        $where = [
            'sso_id' => [$destroyIds, 'in']
        ];
        $status = app($this->ssoRepository)->deleteByWhere($where);
        if ($status) {
            app($this->ssoLoginRepository)->deleteByWhere($where);
        }
        return $status;
    }

    /**
     * 获取当前单点登录的明细
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getOneSso($data)
    {
        $result = app($this->ssoRepository)->infoSso($data['sso_id']);
        if (!isset($result[0])) {
            return [];
        }
        return $result[0];
        /*$result = app($this->ssoRepository)->infoSso($data['sso_id']);
        //把
        if (!isset($result[0])) {
            return [];
        }
        $sso_sys_param = $result[0]['sso_sys_param'];
        if ($sso_sys_param) {
            $array = explode("&", $sso_sys_param);
            $params = [];
            foreach ($array as $v) {
                $title = substr($v, 0, strpos($v, "="));
                $value = substr($v, strpos($v, "=") + 1);
                $dataParam["key"] = $title;
                $dataParam["value"] = $value;

                array_push($params, $dataParam);
            }

            $result[0]['sso_sys_param'] = json_encode($params);
        }
        dd($result);
        return $result[0];*/
    }

    /**
     * 获取当前单点登录的明细
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getSsoLogin($data, $user_id = null)
    {
        if (isset($data['sso_id']) && empty($data['sso_id'])) {
            return [];
        }
        $result = app($this->ssoRepository)->getSsoLogin($data['sso_id'], $user_id);
        if (!isset($result[0])) {
            $result = app($this->ssoRepository)->getSsoLeftJoinLogin($data['sso_id'], $user_id);
        }
        $sso_sys_param = isset($result[0]['sso_sys_param']) ? $result[0]['sso_sys_param'] : '';
        if ($sso_sys_param) {
            $ssoSysParam = json_decode($sso_sys_param);
            $params = [];
            foreach ($ssoSysParam as $key => $value) {
                $dataParam = [];
                if (isset($value->type) && $value->type == 'fixed') {
                    if (isset($value->encrypt) && $value->encrypt == 'md5') {
                        $dataParam["key"] = $value->key;
                        $dataParam["value"] = md5($value->value);
                    } else {
                        $dataParam["key"] = $value->key;
                        $dataParam["value"] = $value->value;
                    }
                } elseif (isset($value->type) && $value->type == 'file') {
                    if (!empty($value->value)) {
                        $api = $value->value;
                        $fileParam = getHttps($api);
                        $info = json_decode($fileParam, true);
                        if (is_string($info)) {
                            $dataParam["key"] = $value->key;
                            if (isset($value->encrypt) && $value->encrypt == 'md5') {
                                $dataParam["value"] = md5($info);
                            } else {
                                $dataParam["value"] = $info;
                            }
                        } else {
                            $dataParam["key"] = $value->key;
                            $dataParam["value"] = '';
                        }
                    }
                } elseif (isset($value->type) && $value->type == 'expression') {
                    if (isset($value->encrypt) && $value->encrypt == 'md5') {
                        $dataParam["key"] = $value->key;
                        $dataParam["value"] = md5($value->value);
                    } else {
                        $dataParam["key"] = $value->key;
                        $dataParam["value"] = $value->value;
                    }
                } elseif (isset($value->type) && $value->type == 'system') {
                    if (!empty($user_id)) {
                        if (isset($value->encrypt) && $value->encrypt == 'md5') {
                            $systemParam = $this->systemParam($value->value, $user_id);
                            $dataParam["key"] = $value->key;
                            $dataParam["value"] = md5($systemParam);
                        } else {
                            $systemParam = $this->systemParam($value->value, $user_id);
                            $dataParam["key"] = $value->key;
                            $dataParam["value"] = $systemParam;
                        }
                    }
                }
                if (empty($dataParam) && isset($value->key) && isset($value->value)) {
                    $dataParam["key"] = $value->key;
                    $dataParam["value"] = $value->value;
                }
                array_push($params, $dataParam);
            }
            $result[0]['sso_sys_param'] = json_encode($params);
        }
        /* $sso_sys_param = isset($result[0]['sso_sys_param']) ? $result[0]['sso_sys_param'] : '';
         if ($sso_sys_param) {
             $array = explode("&", $sso_sys_param);
             $params = [];
             foreach ($array as $v) {
                 $title = substr($v, 0, strpos($v, "="));
                 $value = substr($v, strpos($v, "=") + 1);
                 $dataParam["key"] = $title;
                 $dataParam["value"] = $value;

                 array_push($params, $dataParam);
             }

             $result[0]['sso_sys_param'] = json_encode($params);
         }*/

        return $result[0];
    }

    /**
     * 编辑外部系统账户
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-27
     */
    public function editSsoLogin($data, $sso_login_id)
    {
        $ssoInfo = app($this->ssoLoginRepository)->infoSsoLogin($sso_login_id);
        if (count($ssoInfo) == 0) {
            return ['code' => ['0x031002', 'sso']];
        }
        $where['search'] = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v['user_id'])) {
                Cache::forget('custom_menus_' . $v['user_id']);
            }
        }
//        $data['sso_login_user_id'] = $user_id;
        $ssoData = array_intersect_key($data, array_flip(app($this->ssoLoginRepository)->getTableColumns()));
        $result = app($this->ssoLoginRepository)->updateData($ssoData, ['sso_login_id' => $sso_login_id]);
        if ($result) {
            app($this->heterogeneousSystemService)->updateUserBinding($ssoInfo, $ssoData);
        }
        return $result;
    }

    /**
     * 外部系统列表
     *
     * @param array $data
     *
     * @return type
     */
    public function getSsoLoginList($data, $user_id)
    {
//        $param['search'] = [
//            "sso_login_user_id" => [$user_id, '='],
//        ];
        $data['sso_login_user_id'] = $user_id;
        $ssoLoginData = app($this->ssoRepository)->insertSsoLogin($user_id);
        $this->initUserSso($user_id);
//        foreach ($ssoLoginData as $k => $v) {
//            if (!$v["sso_login_id"]) {
//                $dataLogin = [
//                    "sso_id" => $v["sso_id"],
//                    "sso_login_user_id" => $user_id
//                ];
//                app($this->ssoLoginRepository)->insertData($dataLogin);
//            }
//        }
        return $this->response(app($this->ssoRepository), 'getSsoLoginTotal', 'getSsoLoginList',
            $this->parseParams($data));
    }

    public function getSsoTree($data)
    {
        $where = [];
        //选择器时需要
        if (isset($data["search"]) && $data["search"]) {
            $temp = json_decode($data["search"], true);
            $p = [];
            foreach ($temp as $k => $v) {
                if ($k == "sso_id") {
                    $k = "sso.sso_id";
                }
                $p[$k] = $v;
            }

            $where = $p;
        }


        return app($this->ssoRepository)->getSsoTree($where);
//        $result = [];
//        $dataParam = [];
//        foreach ($temp as $val) {
//            $sso_sys_param = $val["sso_sys_param"];
//            $params = [];
//            if ($sso_sys_param) {
//                $array = explode("&", $sso_sys_param);
//
//                foreach ($array as $v) {
//                    $title = substr($v, 0, strpos($v, "="));
//                    $value = substr($v, strpos($v, "=") + 1);
//                    $dataParam[$title] = $value;
//
//                }
//            }
//            $val["sso_params"] = $dataParam;
//            $result[] = $val;
//        }
//        return $result;
    }

    /**
     * 点了“登录集成--单点登录”的菜单后，前端调用接口获取外部系统配置详情，实现单点登录
     * 1、此函数是董思尧改“登录集成-外部系统支持系统参数&表达式”的时候新增的，之前的处理方式废弃了
     * 2、此函数bug：“没有解析表达式”；此bug修复：“1、把此函数内的md5去掉，在返回数据后，由前端的统一处理函数md5；2、在前端返回后，由前端已有的函数处理‘表达式解析’”(20210513-丁鹏)
     * @param  [type] $ssoId  [description]
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function getMySsoLoginDetail($ssoId, $userId)
    {
        $result = app($this->ssoLoginRepository)->getMySsoLoginDetail($ssoId, $userId);
        if (empty($result)) {
            $this->initUserSso($userId);
            $result = app($this->ssoLoginRepository)->getMySsoLoginDetail($ssoId, $userId);
            if (empty($result)) {
                return $result;
            }
        }
        $result = $result->toArray();
        $params = [];
        $ssoSysParam = isset($result['sso_system']['sso_sys_param']) ? $result['sso_system']['sso_sys_param'] : '';
        if ($ssoSysParam) {
            $ssoSysParam = json_decode($ssoSysParam);
            $params = [];

            foreach ($ssoSysParam as $key => $value) {
                $dataParam = [];

                if (isset($value->type) && $value->type == 'fixed') {
                    $dataParam["key"] = $value->key;
                    $dataParam["value"] = $value->value;
                } elseif (isset($value->type) && $value->type == 'file') {
                    if (!empty($value->value)) {
                        $api = $value->value;
                        $fileParam = getHttps($api); //来自文件的返回值必须是json字符串
                        $info = json_decode($fileParam, true);
                        $dataParam["key"] = $value->key;
                        $dataParam["value"] = is_string($info) ? $info : '';
                    }
                } elseif (isset($value->type) && $value->type == 'expression') {
                    $dataParam["key"] = $value->key;
                    $dataParam["value"] = $value->value;
                } elseif (isset($value->type) && $value->type == 'system') {
                    if (!empty($userId)) {
                        $systemParam = $this->systemParam($value->value, $userId);
                        $dataParam["key"] = $value->key;
                        $dataParam["value"] = $systemParam;
                    }
                }
                if (empty($dataParam) && isset($value->key) && isset($value->value)) {
                    $dataParam["key"] = $value->key;
                    $dataParam["value"] = $value->value;
                }
                $dataParam['type'] = $value->type ?? '';
                $dataParam['encrypt'] = $value->encrypt ?? '';
                array_push($params, $dataParam);
            }
        }

        $result['sso_system']['sso_sys_param'] = json_encode($params);
        return $result;
    }

    public function systemParam($data, $userId)
    {
        $userAllData = app($this->userRepository)->getUserAllData($userId)->toArray();
        $result = '';
        switch ($data) {
            case 'date':
                $result = date("Y-m-d",time());
                break;
            case 'time':
                $result = date("H:i:s",time());
                break;
            case 'userRoleLeavel':
                $userRole = [];
                $userAllrole = isset($userAllData['user_has_many_role']) ? $userAllData['user_has_many_role'] : '';
                if (!empty($userAllrole)) {
                    foreach ($userAllrole as $key => $role) {
                        $userRole[] = isset($role['has_one_role']['role_no']) ? $role['has_one_role']['role_no'] : '';
                    }
                }
                $result = implode(',', $userRole);
                break;
            case 'timestamp':
                $result = time();
                break;
            case 'userId':
                $result = $userId;
                break;
            case 'userName':
                $result = isset($userAllData['user_name']) ? $userAllData['user_name'] : '';
                break;
            case 'userAccount':
                $result = isset($userAllData['user_accounts']) ? $userAllData['user_accounts'] : '';
                break;
            case 'userRole':
                $userRole = [];
                $userAllrole = isset($userAllData['user_has_many_role']) ? $userAllData['user_has_many_role'] : '';
                if (!empty($userAllrole)) {
                    foreach ($userAllrole as $key => $role) {
                        $userRole[] = isset($role['has_one_role']['role_name']) ? $role['has_one_role']['role_name'] : '';
                    }
                }
                $result = implode(',', $userRole);
                break;
            case 'userDeptName':
                $userDeptName = '';
                $userDeptName = isset($userAllData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name']) ? $userAllData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] : '';
                $result = $userDeptName;
                break;
            case 'userDeptId':
                $userDeptId = '';
                $userDeptId = isset($userAllData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id']) ? $userAllData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id'] : '';
                $result = $userDeptId;
                break;
            case 'userJobNumber':
                $userJobNumber = '';
                $userJobNumber = isset($userAllData['user_job_number']) ? $userAllData['user_job_number'] : '';
                $result = $userJobNumber;
                break;
            default:
                $result = '';
        }
        return $result;
    }

    // 初始化用户的外部系统配置列表
    private function initUserSso($userId)
    {
        $ssoLoginData = app($this->ssoRepository)->insertSsoLogin($userId);
        foreach ($ssoLoginData as $k => $v) {
            if (!$v["sso_login_id"]) {
                $dataLogin = [
                    "sso_id" => $v["sso_id"],
                    "sso_login_user_id" => $userId
                ];
                app($this->ssoLoginRepository)->insertData($dataLogin);
            }
        }
        return true;
    }
}
