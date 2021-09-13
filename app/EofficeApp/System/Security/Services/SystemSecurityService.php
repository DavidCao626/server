<?php

namespace App\EofficeApp\System\Security\Services;

use App\EofficeApp\Base\BaseService;
use Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 系统性能安全设置service
 *
 * @author  朱从玺
 *
 * @since  2015-10-28
 */
class SystemSecurityService extends BaseService
{
    /**
     * [$uploadRepository system_upload表资源库]
     *
     * @var [object]
     */
    protected $uploadRepository;

    /**
     * [$paramsRepository system_params表资源库]
     *
     * @var [object]
     */
    protected $paramsRepository;

    public function __construct(
    ) {
        parent::__construct();
		$this->userRepository 	= 'App\EofficeApp\User\Repositories\UserRepository';
        $this->langService      = 'App\EofficeApp\Lang\Services\LangService';
		$this->uploadRepository = 'App\EofficeApp\System\Security\Repositories\SystemUploadRepository';
		$this->paramsRepository = 'App\EofficeApp\System\Security\Repositories\SystemParamsRepository';
		$this->customizeSelectorRepository = 'App\EofficeApp\System\Security\Repositories\CustomizeSelectorRepository';
		$this->whiteAddressRepository = 'App\EofficeApp\System\Security\Repositories\SystemSecurityWhiteAddressRepository';
    }

    /**
     * [getModuleUploadList 获取上传设置列表]
     *
     * @method 朱从玺
     *
     * @param  [array]               $param [查询条件]
     *
     * @since  2015-10-28 创建
     *
     * @return [array]                      [查询结果]
     */
    public function getModuleUploadList($param = [])
    {
        $default = array(
            'page'  => 0,
            'limit' => config('eoffice.pagesize'),
        );

        $param = array_merge($default, $param);

		$uploadSetList = $this->response(app($this->uploadRepository), 'getTotal', 'getAllModule', $param);

        foreach ($uploadSetList['list'] as $key => $value) {
            $uploadSetList['list'][$key]['function_name'] = trans('system.' . $value['function_abbreviation']);
        }

        return $uploadSetList;
    }

    /**
     * [getModuleUpload 获取某个模块的上传设置]
     *
     * @method 朱从玺
     *
     * @param  [int]          $functionAbbreviation [模块ID]
     *
     * @since  2015-10-28 创建
     *
     * @return [array]                              [查询结果]
     */
    public function getModuleUpload($functionAbbreviation)
    {
        $defaultSuffix = config('eoffice.uploadDeniedExtensions');
        $iniFileMax    = substr(ini_get('upload_max_filesize'), 0, -1);

        if ($functionAbbreviation == -1) {
            $moduleSetting['default_suffix'] = $defaultSuffix;
        } else {
			$moduleSetting = app($this->uploadRepository)->getOneModule($functionAbbreviation);
            $moduleSetting['default_suffix'] = $defaultSuffix;
        }
        $moduleSetting['upload_max_filesize'] = $iniFileMax;
        return $moduleSetting;
    }

    /**
     * [modifyModuleUpload 编辑上传设置]
     *
     * @method 朱从玺
     *
     * @param  [int]               $id      [主键ID]
     * @param  [array]             $newData [编辑数据]
     *
     * @since  2015-10-28 创建
     *
     * @return [bool]                       [编辑结果]
     */
    public function modifyModuleUpload($id, $newData)
    {
        //php_ini上传配置
        $iniFileMax  = substr(ini_get('upload_max_filesize'), 0, -1);
        $iniTotalMax = substr(ini_get('post_max_size'), 0, -1);
        if(isset($newData['upload_max_num']) && ($newData['upload_max_num'] <= 0)){
            return ['code' => ['0x015043', 'system']];
        }
        if(isset($newData['upload_single_max_size']) && ($newData['upload_single_max_size'] <= 0)){
            return ['code' => ['0x015044', 'system']];
        }
        //用户设置上传配置
        $totalMax = (int)trim($newData['upload_max_num']) * (int)trim($newData['upload_single_max_size']);

        if ((int)trim($newData['upload_single_max_size']) / 1024 > $iniFileMax) {
            return array('code' => array('0x015005', 'system'), 'dynamic' => trans('system.0x015005') . $iniFileMax . 'M!');
        }

        if ($totalMax / 1024 > $iniTotalMax) {
            return array('code' => array('0x015006', 'system'), 'dynamic' => trans('system.0x015006') . $iniTotalMax . 'M!');
        }

        $newData['upload_total_max_size'] = $totalMax;
        $newData['suffix'] = trim($newData['suffix']);
        unset($newData['file_name_rules_html']);
        unset($newData['file_name_rules']);

        // 此段代码进行路径匹配,写在这里用意何在?
        // if($newData['upload_full_src']) {
        //     $src = '#\b[a-z]:[\\/](?:[^\\/:*?"<>|\r\n]+[\\/])*#i';

        //     if(!preg_match($src, $newData['upload_full_src'])) {
        //         return array('code' => array('0x015002', 'system'));
        //     }
        // }

        $where = array('id' => array($id));

		$result = app($this->uploadRepository)->updateDataBatch($newData, $where);

        return $result;

    }

    /**
     * 编辑附件名称规则
     * @author yangxingqiang
     * @param $id
     * @param $newData
     * @return mixed
     */
    public function modifyUploadFileRule($id, $newData)
    {
        $data = [];
        $data['id'] = $id;
        $data['file_name_rules_html'] = (isset($newData['file_name_rules']) && !empty($newData['file_name_rules']) && isset($newData['file_name_rules_html'])) ? $newData['file_name_rules_html'] : '';
        $data['file_name_rules'] = (isset($newData['file_name_rules']) && !empty($newData['file_name_rules'])) ? json_encode($newData['file_name_rules']) : '';
        $where = array('id' => array($id));
        $result = app($this->uploadRepository)->updateDataBatch($data, $where);
        return $result;

    }

    public function getUploadFileRule($id)
    {
        $result = app($this->uploadRepository)->getDetail($id);
        return isset($result) ? $result->toArray() : [];
    }

    /**
     * [getSecurityOption 获取系统安全选项]
     *
     * @method 朱从玺
     *
     * @param  [string]   $params [查询选项类型, login登录安全选项,capability性能安全选项,contract合同提醒选项,log日志修改选项]
     *
     * @since  2015-10-29 创建
     *
     * @return [array]            [查询结果]
     */
    public function getSecurityOption($params)
    {
        $securityOption = $this->getOptionArray($params);
        $where = array('param_key' => array($securityOption, 'in'));

		$result = app($this->paramsRepository)->getParamsByWhere($where);

        $securityData = [];
        foreach ($result as $key => $value) {
            $securityData[$value['param_key']] = $value['param_value'];
        }

        //如果获取日志修改设置时间,则转一下格式
        if ($params == 'log') {
            $showDay      = floor($securityData['log_update_time'] / 24);
            $showHour     = $securityData['log_update_time'] - ($showDay * 24);
            $securityData = array(
                'show_day'                  => $showDay,
                'show_hour'                 => $showHour,
                'diary_supplement'          => (int) $securityData['diary_supplement'],
                'dimission'                 => (int) $securityData['dimission'],
                'show_work_time'            => isset($securityData['show_work_time']) ? $securityData['show_work_time'] : 1,
                'display_holiday_microblog' => isset($securityData['display_holiday_microblog']) ? (int) $securityData['display_holiday_microblog'] : 1,
            );
        } elseif ($params == 'capability') {
            $securityData = [
                'sms_refresh_frequency' => $securityData['sms_refresh_frequency'] ?? 0,
                'form_refresh_frequency' => $securityData['form_refresh_frequency'] ?? 0,
                'encrypt_organization' => $securityData['encrypt_organization'] ?? 0,
                'exclude_encrypt_dept' => isset($securityData['exclude_encrypt_dept']) && !empty('exclude_encrypt_dept') ? explode(',', $securityData['exclude_encrypt_dept']) : [],
                'exclude_encrypt_role' => isset($securityData['exclude_encrypt_role']) && !empty('exclude_encrypt_role') ? explode(',', $securityData['exclude_encrypt_role']) : [],
                'exclude_encrypt_user' => isset($securityData['exclude_encrypt_user']) && !empty('exclude_encrypt_user') ? explode(',', $securityData['exclude_encrypt_user']) : []
            ];
        }

        return $securityData;
    }

    /**
     * [getSecurityLevel 获取系统登录安全等级]
     * @return array
     */
    public function getSecurityLevel()
    {
        $securityOption = $this->getOptionArray('login');
        $where = array('param_key' => array($securityOption, 'in'));
		$result = app($this->paramsRepository)->getParamsByWhere($where);
        $securityData = [];
        foreach ($result as $key => $value) {
            $securityData[$value['param_key']] = $value['param_value'];
        }
        $count = 0;
        //图形验证码、手机短信验证、密码定时过期、启用密码强度限制、启用密码防暴力破解
        $securitySet = ['security_image_code','sms_verify','security_password_overdue','login_password_security_switch','wrong_password_lock'];
        foreach ($securitySet as $item){
            if($securityData[$item]){
                $count++;
            }
        }
        if($count < 2){
            $level = $count + 1;
        }else{
            $level = 3;
        }
        return ['level' => $level];
    }

    /**
     * [modifySecurityOption 编辑系统安全选项]
     *
     * @method 朱从玺
     *
     * @param  [string]               $params [编辑选项类型]
     * @param  [array]                $data   [编辑数据]
     *
     * @since  2015-10-29 创建
     *
     * @return [bool]                         [编辑结果]
     */
    public function modifySecurityOption($params, $data)
    {
        if ($params == 'log') {
            if (isset($data['show_day']) || isset($data['show_hour'])) {
                $hour = $data['show_day'] * 24 + $data['show_hour'];
                unset($data);
                $data['log_update_time'] = $hour;
            }
        }

        $oldSecuritySwitch = get_system_param('login_password_security_switch');
        $oldForceChange = get_system_param('force_change_password');

        if(isset($data['login_password_security_switch'])){
        if(isset($data['login_password_security_switch']) && $data['login_password_security_switch'] == 0){
        	app($this->userRepository)->updateFields(['change_pwd' => 0]);
            }elseif($data['login_password_security_switch'] == 1){
                if(isset($data['force_change_password']) && $data['force_change_password'] == 1) {
                    if($oldSecuritySwitch != $data['login_password_security_switch']){
			    app($this->userRepository)->updateFields(['change_pwd' => 1]);
                    }
                    if($oldForceChange == 0){
                        app($this->userRepository)->updateFields(['change_pwd' => 1]);
                    }
                }else{
			app($this->userRepository)->updateFields(['change_pwd' => 0]);
                }
            }
        }

		if(isset($data['wrong_password_lock']) && $data['wrong_password_lock'] == 0){
            // 清空锁定用户redis缓存
            ecache('Auth:WrongPwdTimes')->delAll();
        }

        $securityOption = $this->getOptionArray($params);
        $modifyResult   = true;
        $result         = false;
        foreach ($data as $key => $value) {
            if (!in_array($key, $securityOption)) {
                continue;
            }
            if($params == 'login') {
                if($key === 'password_length' && $value < 6) {
                    return ['code' => ['0x015039', 'system']];
                }
            }
            if(count(app($this->paramsRepository)->paramKeyExits(['param_key' => [$key]])) > 0) {
                $where = array('param_key' => array($key));

                $data = array('param_key' => $key, 'param_value' => $value);

                $result = app($this->paramsRepository)->updateDataBatch($data, $where);
                if (!$result) {
                    $modifyResult = false;
                }
            } else {
                set_system_param($key, $value);
            }
            
        }

        if (!$modifyResult) {
            return array('warning' => array('0x000004', 'common'));
        }
        // 清除所有下级人员信息缓存
        $cacheParams = array(
            'search' => [
            ]
        );
        $userList = app($this->userRepository)->getAllUsers($cacheParams);
        if (!empty($userList)) {
            foreach ($userList as $key => $value) {
                if ($value->user_id) {
                    Cache::forget('default_attention_list_' . $value->user_id);
                    if(isset($data['wrong_password_lock']) && $data['wrong_password_lock'] == 0){
                        ecache('Auth:WrongPwdTimes')->clear($value->user_id);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * [getOptionArray 获取当前类型有操作权限的选项数组]
     *
     * @method 朱从玺
     *
     * @param  [string]   $params [查询选项类型, login登录安全选项,capability性能安全选项,contract合同提醒选项,log日志修改选项]
     *
     * @since  2015-10-29 创建
     *
     * @return [array]            [选项数组]
     */
    public function getOptionArray($params)
    {
        $loginOption = array('security_password_overdue', 'password_length', 'security_password_effective_time', 'security_image_code', 'limit_window', 'dynamic_password_type', 'dynamic_password_is_useed', 'usbkey_code_type', 'dynami_addr', 'allow_mobile_login', 'login_by_qrcode', 'login_auth_type', 'sms_verify', 'sms_verify_type', 'login_password_security', 'login_password_security_switch', 'force_change_password', 'wrong_password_lock', 'login_by_normal', 'auto_unlock_account', 'auto_unlock_time', 'account_login_control');

        $capabilityOption = array('sms_refresh_frequency', 'form_refresh_frequency', 'encrypt_organization', 'exclude_encrypt_dept', 'exclude_encrypt_role', 'exclude_encrypt_user');

        $contractOption = array('commerce_contract_period', 'labour_contract_period');

        $logOption = array('log_update_time', 'diary_supplement', 'dimission', 'show_work_time', 'display_holiday_microblog');
        $messageOption = array('message_read');

		$whiteAddressOption = array('system_security_white_address');

        $securityOption = $params . 'Option';

        return $$securityOption;
    }

    /**
     * [resetCapabilityOption 系统性能安全恢复初始设置]
     *
     * @method 朱从玺
     *
     * @param  [string]              $paramKey [恢复选项,sms_refresh_frequency内部消息刷新频率,form_refresh_frequency表单保存频率]
     *
     * @since  2015-10-29 创建
     *
     * @return [bool]                          [恢复结果]
     */
    public function resetCapabilityOption($paramKey)
    {
        $result = true;

        if ($paramKey == 'sms_refresh_frequency') {
            $paramvalue = $this->getParamsData($paramKey)->toArray();
            if ($paramvalue[0]['param_value'] == '300') {
                return true;
            }
            $where = array('param_key' => array($paramKey));

            $data = array('param_value' => 300);

			$result = app($this->paramsRepository)->updateData($data, $where);
        } elseif ($paramKey == 'form_refresh_frequency') {
            $paramvalue = $this->getParamsData($paramKey)->toArray();
            if ($paramvalue[0]['param_value'] == '3000') {
                return true;
            }
            $where = array('param_key' => array($paramKey));

            $data = array('param_value' => 3000);

			$result = app($this->paramsRepository)->updateData($data, $where);
        } elseif ($paramKey == 'encrypt') {
            $result = app($this->paramsRepository)->updateData(['param_value' => 0], ['param_key' => 'encrypt_organization']);
            $result = app($this->paramsRepository)->updateData(['param_value' => ''], ['param_key' => [['exclude_encrypt_dept', 'exclude_encrypt_role', 'exclude_encrypt_user'], 'in']]);
        }
        return $result;
    }

    /**
     * [getParamsData 获取配置的数据]
     *
     * @method 朱从玺
     *
     * @param  [string]        $paramsKey [要查询的数据名]
     *
     * @return [object]                   [查询结果]
     */
    public function getParamsData($paramsKey)
    {
        $where = array('param_key' => [$paramsKey]);
		return app($this->paramsRepository)->getParamsByWhere($where);
	}

    /**
     * [modifyParamsData 获取配置的数据]
     *
     * @method lixx
     *
     * @param  [string]        $paramsKey [要编辑的数据名]
     *
     * @return [object]                   [查询结果]
     */
    public function modifyParamsData($param,$paramsKey)
    {
        $param = $this->parseParams($param);
        if(!isset($param['param_value']))
            return ['code' => ['0x000003', 'common']];
        $where = array('param_key' => $paramsKey);
        $data = array('param_value' => $param['param_value']);
        return app($this->paramsRepository)->modifySystemParams($where,$data);
    }

    /**
     * 获取系统标题
     *
     * @author 丁鹏
     *
     * @return [type]                        [description]
     */
    public function getSystemTitleSetting($param)
    {
		return app($this->paramsRepository)->getParamsByWhere(["param_key" => ["system_title"]]);
    }

    /**
     * 设置系统标题
     *
     * @author 丁鹏
     *
     * @return [type]                           [description]
     */
    public function modifySystemTitleSetting($param)
    {
        $systemTitle = $param["system_title"];
		return app($this->paramsRepository)->updateDataBatch(["param_value" => $systemTitle], ["param_key" => ["system_title"]]);
    }
    public function setSecurityEditor($param)
    {
        if (!isset($param['set_editor']) || empty($param['set_editor'])) {
            return ['code' => ['0x000003', 'common']];
        }
        $insertData = [
            'param_key'   => 'set_editor',
            'param_value' => $param['set_editor'],
        ];
        $chekParam = ['param_key' => 'set_editor'];
        $temp      = $this->paramKeyExits($chekParam);
        if ($temp) {
			return app($this->paramsRepository)->updateDataBatch(["param_value" => $param['set_editor']], ["param_key" => ["set_editor"]]);
        } else {
            if ($result = $this->systemParamsRepository->insertData($insertData)) {
                return ['param_key' => $result->param_key, 'param_value' => $result->param_value];
            }
        }
        return ['code' => ['0x000003', 'common']];
    }
    private function paramKeyExits($param)
    {
        if (!isset($param['param_key']) || empty($param['param_key'])) {
            return ['code' => ['0x000003', 'common']];
        }
        $temp = $this->systemParamsRepository->paramKeyExits($param);
        if (empty($temp)) {
            return false;
        }
        return true;
    }
    /**
     * 获取白名单
     * @param $param
     */
    public function getWhiteAddress($param)
    {
        $param = $this->parseParams($param);
        return $data = $this->response(app($this->whiteAddressRepository), 'getWhiteAddressTotal', 'getWhiteAddressList', $param);
    }

    /**
     * 新增白名单
     * @param $param
     */
    public function addWhiteAddress($param,$userId)
    {
        $param = $this->parseParams($param);
        $matchRule = '/(http|https):\/\/([\w.]+\/?)\S*/';
        // 验证地址http/https
        if (!preg_match($matchRule, $param['white_address_url'], $match)) {
            return ['code' => ['0x015042', 'system']];//已存在
        }
        if(is_string($param['white_address_url'])){
            if(app($this->whiteAddressRepository)->whiteAddressExists($param['white_address_url']))
                return ['code' => ['0x000020', 'common']];//已存在

            $param['user_id'] = $userId;
            $param['created_at'] = Date("Y-m-d H:i:s");
            unset($param['http_type']);
            return $data = app($this->whiteAddressRepository)->addWhiteAddress($param);
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑一条白名单地址
     * @param $param
     * @param $whiteAddressId
     * @param $userId
     */
    public function modifyAWhiteAddress($param,$whiteAddressId,$userId)
    {
        $param = $this->parseParams($param);
        $matchRule = '/(http|https):\/\/([\w.]+\/?)\S*/';
        // 验证地址http/https
        if (!preg_match($matchRule, $param['white_address_url'], $match)) {
            return ['code' => ['0x015042', 'system']];//已存在
        }
        if(is_numeric($whiteAddressId) && is_string($param['white_address_url'])){
            if(app($this->whiteAddressRepository)->whiteAddressExists($param['white_address_url'])){
                return ['code' => ['0x015029', 'system']];//更新内容已存在
            }
            if(!app($this->whiteAddressRepository)->whiteAddressIdExists($whiteAddressId)){
                return ['code' => ['0x000021', 'common']];//数据不存在，无法更新
            }
            $data = [
                'white_address_url' => $param['white_address_url'],
                'user_id'   => $userId,
                'created_at'=> Date("Y-m-d H:i:s"),
            ];
            $where = [
                'white_address_id' => $whiteAddressId,
            ];
            return app($this->whiteAddressRepository)->updateWhiteAddress($data,$where);
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除一条白名单
     * @param $whiteAddressId
     */
    function deleteAWhiteAddress($whiteAddressId)
    {
        if(is_numeric($whiteAddressId)){
            return $data = app($this->whiteAddressRepository)->deleteAWhiteAddress($whiteAddressId);
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 【水印】获取水印设置数据
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function getWatermarkSettingInfo($param,$type,$userInfo)
    {
        $paramKey = 'watermark_set';
        $result = app($this->paramsRepository)->getParamsByWhere(["param_key" => [$paramKey]]);
        if($result && $result->count() && $result->first()) {
            $first = $result->first();
            $info = isset($first->param_value) ? $first->param_value : '';
            $result = json_decode($info,true);
            // 如果传了解析标识 parse ，解析content
            if(isset($param['parse']) && $param['parse'] != '') {
                $user_id = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
                $userInfos = app($this->userRepository)->getUserWithInfoByIds([$user_id], ['user_id'], ['user_id', 'email']);
                $email = '';
                if(!empty($userInfos)) {
                    $userInfoItems = $userInfos[0];
                    $userOneInfo = isset($userInfoItems['user_has_one_info']) ? $userInfoItems['user_has_one_info'] : [];
                    $email = isset($userOneInfo['email']) ? $userOneInfo['email'] : '';
                }
                $content = isset($result['content']) ? $result['content'] : '';
                $userName = isset($userInfo['user_name']) ? $userInfo['user_name'] : '';
                $account = isset($userInfo['user_accounts']) ? $userInfo['user_accounts'] : '';
                $department = isset($userInfo['dept_name']) ? $userInfo['dept_name'] : '';
                $roleName = isset($userInfo['role_name']) ? $userInfo['role_name'] : [];
                $role = implode(',',$roleName);
                $phone_number = isset($userInfo['phone_number']) ? $userInfo['phone_number'] : '';
                // $date = date("Y-m-d");
                // $time = date("H:i:s");
                $content = str_replace("[NAME]", "{$userName}", $content); // 当前操作者真实姓名
                $content = str_replace("[ACCOUNT]", "{$account}", $content); // 当前操作者登录账号
                $content = str_replace("[DEPARTMENT]", "{$department}", $content); // 当前操作者部门
                $content = str_replace("[ROLE]", "{$role}", $content); // 当前操作者角色
                $content = str_replace("[PHONE_NUMBER]", "{$phone_number}", $content); // 当前操作者手机号码
                $content = str_replace("[EMAIL]", "{$email}", $content); // 当前操作者邮箱
                $content = str_replace("[USER_ID]", "{$user_id}", $content); // 当前操作者用户ID
                // $content = str_replace("[DATE]", "{$date}", $content); // 当前日期
                // $content = str_replace("[TIME]", "{$time}", $content); // 当前时间
                $content = strip_tags(html_entity_decode($content));
                $result['content_parse'] = $content;
            }
            // 如果 $type 不等于all，说明是实际使用的时候，取配置，那么需要判断是否开启了对应的水印  --- 20191219 调整为前端缓存配置处理
            if($type && $type != 'all') {
                $scope = isset($result['scope']) ? $result['scope'] : [];
                return $result;
                // if(isset($scope[$type]) && $scope[$type] == 1) {
                //     return $result;
                // } else {
                //     return [];
                // }
            } else {
                return $result;
            }
        } else {
            return [];
        }
    }

    /**
     * 【水印】获取水印预览页面html
     * @return [type] [description]
     */
    public function getWatermarkPriviewHtml($param)
    {
        $time = date('Y-m-d H:i:s');
        $html = <<<EOF
        <h1 style="text-align: center;">报表功能使用指南</h1>
<p>&nbsp;<small>发布人</small>：<small>管理员</small>&nbsp;&nbsp;&nbsp;&nbsp;<small>$time</small></p>
<hr style="background-color: #adadad; border: 0px; height: 1px;" />
    <p style="text-indent: 0em;"><h1 style="font-size: 2.5rem; font-weight: 500; line-height: 1.1; text-align: center;"></h1><p></p><p></p><p>报表是OA系统内对数据进行分析的有效手段，本次更新实现一张报表纵向分析多条流程或公告，同时新增三种报表数据源类型，报表功能更精细。</p><p></p><p>报表系统内部数据源增加公告的报表统计，用户可对公告的阅读信息进行统计，分析不同部门的阅读情况，比较已读未读信息等。</p><p></p><p>通过一张报表统计不同类的流程或公告，实现数据的纵向比较。管理者可以同时对多条流程或公告进行数据分析，从而深入了解数据间的内在联系以及变动情况，提升管理的全局观。</p><br><br><br><br><br><br></p>

EOF;
        return $html;
    }

    /**
     * 【水印】保存水印设置
     * @return [type] [description]
     */
    public function saveWatermarkSetting($param,$userInfo)
    {
        // 只存一份，不再传type
        // $type = (isset($param['type']) && $param['type']) ? $param['type'] : '';

        // content: "<p>[NAME]</p>"
        // font: "3"
        // height: "2"
        // opaqueness: "4"
        // rotate: "5"
        // toggle: 1
        // type: "chat"
        // width: "1"
        // scope:
        $paramKey = 'watermark_set';
        $paramValue = $param;
        $paramValue = json_encode($paramValue);
        $searchResult = app($this->paramsRepository)->getParamsByWhere(["param_key" => [$paramKey]]);
        if($searchResult && $searchResult->count()) {
            $data = ["param_value" => $paramValue];
            $where = ['param_key' => [$paramKey]];
            $result = app($this->paramsRepository)->updateData($data, $where);
            return $result;
        } else {
            $insertData = [
                'param_key'   => $paramKey,
                'param_value' => $paramValue
            ];
            $result = app($this->paramsRepository)->insertData($insertData);
            return $result;
        }
    }
    public function getSystemParams($keys) {
        $params = [];

        foreach (get_system_param() as $value) {
            if (in_array($value->param_key, $keys)) {
                $params[$value->param_key] = $value->param_value;
            }
        }

        return $params;
    }
    public function setSystemParams($data) {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $tempData = ['param_value' => $value];
                $where = ['param_key' => $key];
                app($this->paramsRepository)->modifySystemParams($where, $tempData);
            }
        }
        return true;
    }
}
