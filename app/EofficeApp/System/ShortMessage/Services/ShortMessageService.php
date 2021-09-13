<?php

namespace App\EofficeApp\System\ShortMessage\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use App\Utils\Utils;
/**
 * 手机短信Service类:提供手机短信相关服务
 *
 * @author qishaobo
 *
 * @since  2017-03-06 创建
 */
class ShortMessageService extends BaseService
{
    /**
     * 手机短信资源
     *
     * @var object
     */
    private $shortMessageRepository;
    private $empowerService;

    /**
     * 手机短信信息
     *
     * @var array
     */
    private $sms;

    /**
     * webservice所有的参数类型
     * @var
     */
    protected $functionParams;

    public function __construct()
    {
        $this->client                    = 'GuzzleHttp\Client';
        $this->userRepository            = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userRoleRepository        = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->shortMessageRepository    = 'App\EofficeApp\System\ShortMessage\Repositories\ShortMessageRepository';
        $this->shortMessageSetRepository = 'App\EofficeApp\System\ShortMessage\Repositories\ShortMessageSetRepository';
        $this->shortMessageTemplateRepository = 'App\EofficeApp\System\ShortMessage\Repositories\ShortMessageTemplateRepository';
        $this->empowerService            = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->apiService                = 'App\EofficeApp\Api\Services\ApiService';
    }

    /**
     * 获取手机短信详情数据
     *
     * @param  array $param 查询条件
     *
     * @return array 手机短信详情
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function getSMSSetList($param)
    {
        $param = $this->parseParams($param);
        $data  = [];
        // 获取手机短信配置
        // $data = app($this->shortMessageSetRepository)->getSMSSetList($param);
        $id      = isset($param['search']['sms_id']) ? $param['search']['sms_id'] : '';
        $details = $this->getSMSSetDetail($id);
        // 获取关联的短信发送类别【type表】
        $typeId   = isset($param['search']['relation_type_id']) ? $param['search']['relation_type_id'] : '';
        $typeName = DB::table('short_message_type')->select('*')->where('sms_type_id', $typeId)->pluck('type_name')->first();
        $typeName = isset($typeName) ? $typeName : '';
        if (empty($details)) {
            return [];
        }
        $data[0] = $details;
        // 查询系统配置关联表config,关联手机配置数据
        $configs           = DB::table('short_message_config')->select('*')->first();
        $configs           = json_decode(json_encode($configs), true);
        $system_send_allow = isset($configs['system_send_allow']) ? $configs['system_send_allow'] : '0';
        $out_send_allow    = isset($configs['out_send_allow']) ? $configs['out_send_allow'] : '0';
        $message_prefix    = isset($configs['message_prefix']) ? $configs['message_prefix'] : '0';
        foreach ($data as $k => $v) {
            $data[$k]['type_name'] = $typeName;
            // if (!empty($v['sms_password'])) {
            //     $data[$k]['sms_password'] = decrypt($v['sms_password']);
            // }
            $data[$k]['system_send_allow'] = $system_send_allow;
            $data[$k]['out_send_allow']    = $out_send_allow;
            $data[$k]['message_prefix']    = $message_prefix;

        }
        return $data;
    }

    /**
     * 获取手机短信
     *
     * @param  int $id 手机短信id
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function getSMSSetDetail($id = 0)
    {
        if ($id > 0) {
            $search = ['sms_id' => $id];
        } else {
            $search = ['is_use' => 1];
        }

        if ($dataObj = app($this->shortMessageSetRepository)->getSMSSetDetail($search)) {
            $data = $dataObj->toArray();

            $data = isset($data[0]) ? $data[0] : [];

            // if (isset($data['sms_password']) && !empty($data['sms_password'])) {
            //     $data['sms_password'] = decrypt($data['sms_password']);
            // }
            $result                    = DB::table('short_message_config')->select('*')->first();
            $data['system_send_allow'] = isset($result->system_send_allow) ? $result->system_send_allow : 0;
            $data['out_send_allow']    = isset($result->out_send_allow) ? $result->out_send_allow : 0;
            $data['message_prefix']    = isset($result->message_prefix) ? $result->message_prefix : '';
            $data['prefix_location']   = isset($result->prefix_location) ? $result->prefix_location : 0;
            return $data;
        }

        return [];
    }

    /**
     * 是否有短信配置
     * @return bool
     */
    public function hasSMSSet()
    {
        $setData = $this->getSMSSetDetail();
        if(count($setData) > 3 && isset($setData['is_use']) && $setData['is_use'] == 1){
            return true;
        }
        return false;
    }

    /**
     * 新建手机短信配置
     *
     * @param  array $data 新建数据
     *
     * @return int|array 新添加的手机短信id|错误码
     *
     *
     */
    public function addSms($data)
    {
        if (!isset($data) || empty($data)) {
            return ['code' => ['0x015025', 'system']];
        }
        if (!empty($data['sms_password'])) {
            $data['sms_password'] = encrypt($data['sms_password']);
        }

        if($data['relation_type_id']==1){
        	if(!check_white_list($data['sms_url'])){
        		return ['code' => ['0x000025','common']];
        	}
        }

        // $data['is_use'] = 1;

        $result = app($this->shortMessageSetRepository)->insertData($data);
        $smsId  = isset($result->sms_id) ? $result->sms_id : '';
        if ($smsId) {
            // 更新其他数据为不使用
            // DB::table('short_message_set')->where('sms_id', '<>', $smsId)->update(['is_use' => '0']);
            return true;
        }
        return ['code' => ['0x015026', 'system']];
    }

    /**
     * 编辑手机短信数据
     *
     * @param  array $data 编辑数据
     *
     * @return int|array 新添加的手机短信id|错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function editSMSSet($smsId, $data)
    {
        $where = ['sms_id' => $smsId];

        if (!empty($data['sms_password'])) {
            $data['sms_password'] = encrypt($data['sms_password']);
        }

		if($data['relation_type_id']==1){
			if(!check_white_list($data['sms_url'])){
				return ['code' => ['0x000025','common']];
			}
		}

        // $data['is_use'] = 1;

        if (app($this->shortMessageSetRepository)->updateData($data, $where)) {
            // 更新其他数据为不使用
            if (isset($data['is_use']) && $data['is_use'] == 1){
                DB::table('short_message_set')->where('sms_id', '<>', $smsId)->update(['is_use' => '0']);
            }
        }
        // return ['code' => ['0x015009', 'system']];
        return true;
    }

    /**
     * 发送短信
     *
     * @param  array $param |string mobile_to,string user_to,string message 发送短信
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function sendSMS($param = [], $roleId = [])
    {
        if ($param['message'] === '') {
            return ['code' => ['0x015012', 'system']];
        }

        $sms = $this->getSMSSetDetail(0);

        if (empty($sms) || !isset($sms['relation_type_id']) || empty($sms['relation_type_id'])) {
            return ['code' => ['0x015038', 'system']];
        }

        // 不允许手机短信外发
        if (!$sms['out_send_allow']) {
        	if (isset($param['mobile_to']) && $param['mobile_to'] !== '') {
        		return ['code' => ['0x015036', 'system']];
        	}
        }

        if (!$sms['system_send_allow']) {
        	if (!empty($param['sms_list'])) {
        		return ['code' => ['0x015037', 'system']];
        	}
        }

        $toUser = [];

        if (isset($param['mobile_to']) && $param['mobile_to'] !== '') {
            $toUser = is_array($param['mobile_to']) ? $param['mobile_to'] : explode(',', $param['mobile_to']);
            foreach ($toUser as $v) {
                if (!$v) { //  !is_numeric($v) || strlen($v) != 11
                    return ['code' => ['0x003043', 'auth']];
                }
            }
        }

        if (!empty($param['user_to'])) {
        	if(empty($param['sms_list'])){
        		$param['sms_list'] = [];
        	}
        	$roleUsers = [];
        	foreach($param['sms_list'] as $v){
        		if($v['type']==1){
        			$id = str_replace("user_","",$v['id']);
        			$roleUsers[$id] = $id;
        		}
        	}
            $userIds         = is_array($param['user_to']) ? $param['user_to'] : explode(',', $param['user_to']);
            $roleCommunicate = $this->getCommunicateRoles($roleId);
            // $userIds = ['0'=>'admin'];
			if(empty($param['sms_list'])){
				foreach($userIds as $v){
					$roleUsers[$v] = $v;
				}
			}

            $whereRole = [
                'user_id' => [$roleUsers, 'in'],
                'role_id' => [$roleCommunicate, 'not_in'],
            ];

            $toUserIds = app($this->userRoleRepository)->getRoleUsers($whereRole);


            if (!empty($roleUsers) && empty($toUserIds)) {
                return ['code' => ['communicate_empty_sms', 'sms']];
            }

            $userInfos = app($this->userRepository)->getUserWithInfoByIds($toUserIds, ['user_id'], ['user_id', 'phone_number']);

            foreach ($userInfos as $v) {
                $userInfo = $v['user_has_one_info'];
                if (empty($userInfo['phone_number'])) {
                    continue;
                }
            	$toUser[$userInfo['user_id']] = $userInfo['phone_number'];
            }

            foreach($param['sms_list'] as $v){
            	if(isset($v['method'])){
            		$temp = [];
            		if(strpos($v['method'],',') !== false){
            			$temp = explode(',', $v['method']);
            		}else{
            			$temp = explode('，', $v['method']);
            		}
            		foreach($temp as $value){
            			if (!$value) { // is_numeric($value) || strlen($value) != 11
            				return ['code' => ['0x003043', 'auth']];
            			}
            		}
            	}else{
            		if (!$v['name']) { // is_numeric($v['name']) || strlen($v['name']) != 11
            			return ['code' => ['0x003043', 'auth']];
            		}
            	}

            	if($v['type']==1){
            		$id = str_replace("user_","",$v['id']);
            		$toUser[$id] = $v['method'];
            	}else{
            		if(isset($v['method'])){
            			$toUser[] = $v['method'];
            		}else{
            			$toUser[] = $v['name'];
            		}
            	}
            }
            if (empty($toUser)) {
                return ['code' => ['phone_empty', 'sms']];
            }
        }

        if (empty($toUser)) {
            return ['code' => ['0x015011', 'system']];
        }

        $to = array_unique(array_values($toUser));

        $typeFunction = '';

        //获取种类表中的type_function,用来拼接方法名,小驼峰命名，首字母一定要大写
        if (isset($sms['relation_type_id']) && !empty($sms['relation_type_id'])) {
            $typeFunction = DB::table('short_message_type')->select('*')->where('sms_type_id', $sms['relation_type_id'])->pluck('type_function')->first();
            $typeFunction = ucfirst($typeFunction);
            $typeFunction = isset($typeFunction) ? $typeFunction : '';
        }

        // if (empty($sms['type']) || (!method_exists($this, "sendSMSBy" . $sms['type']))) {
        //     return ['code' => ['0x015010', 'system']];
        // }
        if (empty($typeFunction) || (!method_exists($this, "sendSMSBy" . $typeFunction))) {
            return ['code' => ['0x015010', 'system']];
        }
        // 判断是否允许系统发送短信
        $configs = DB::table('short_message_config')->select('*')->first();
        $configs = json_decode(json_encode($configs), true);
        //system_send_allow  out_send_allow 均不选 表示不允许发送短信
        $can_send_sms =isset($configs['system_send_allow']) && isset($configs['out_send_allow']) && $configs['system_send_allow'] != 1 && $configs['out_send_allow'] != 1;
        if ($can_send_sms) {
            return ['code' => ['0x015013', 'system']];
        }
        // if ($sms['system_send_allow'] != 1) {
        //     return ['code' => ['0x015013', 'system']];
        // }

        $this->sms = $sms;

        $dataSend = [
            'to'      => $to,
            'message' => $param['message'],
        ];
        $smsStart = date('Y-m-d H:i:s');

        if (!empty($typeFunction)) {
            $response = $this->{"sendSMSBy" . $typeFunction}($dataSend);
        }

        // $response = $this->{"sendSMSBy" . $sms['type']}($dataSend);

        return $this->saveSMS($toUser, $param, $response, $smsStart);
    }

    /**
     * 保存短信
     *
     * @param  array $param |string mobile_to,string user_to,string message 发送短信
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function saveSMS($toUser, $param, $response, $smsStart)
    {
        $dataSMS = [
            'mobile_from' => empty($param['mobile_from']) ? '' : $param['mobile_from'],
            'user_from'   => empty($param['user_from']) ? '' : $param['user_from'],
            'message'     => isset($response['message']) ? $response['message'] : $param['message'],
            'ip'          => getClientIp(),
            'sms_start'   => $smsStart,
            'sms_finish'  => date('Y-m-d H:i:s'),
            'sms_status'  => isset($response['error']) && $response['error'] == 1 ? '0' : 1,
        ];

        foreach ($toUser as $K => $v) {
            if (is_string($K)) {
                $dataSMS['user_to'] = $K;
            }else{
            	$dataSMS['user_to'] = "";
            }
            if (empty($v)) {
                $dataSMS['sms_status'] = '0';
            }

            $dataSMS['mobile_to'] = $v;
            app($this->shortMessageRepository)->insertData($dataSMS);
        }
        if (isset($response['error']) && $response['error'] == 1) {
            // return ['code' => [$this->sms['type'] . "." . $response['error_code'], 'sms']];
            return ['code' => ["YiMei" . "." . $response['error_code'], 'sms']];
        }

        return true;
    }

    /**
     * 获取限制角色
     *
     * @param array $param 查询条件
     *
     * @return rray 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getCommunicateRoles($roleId = [])
    {
        $roleCommunicate = [];

        if (!empty($roleId)) {

            $roles = app('App\EofficeApp\Role\Repositories\RoleCommunicateRepository')->communicateRoles($roleId, [5]);

            if (!empty($roles)) {
                foreach ($roles as $role) {
                    $roleCommunicate = array_merge($roleCommunicate, array_filter(explode(',', $role['role_to'])));
                }

                $roleCommunicate = array_unique($roleCommunicate);
            }
        }

        return $roleCommunicate;
    }

    /**
     * 获取不限制用户
     *
     * @param array $param 查询条件
     *
     * @return rray 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getCommunicateUsers($userInfo)
    {
        $roleCommunicate = $this->getCommunicateRoles($userInfo['role_id']);
        $where           = ['role_id' => [$roleCommunicate, 'not_in']];
        $users           = app($this->userRoleRepository)->getRoleUsers($where);
        $users[]         = $userInfo['user_id'];

        return $users;
    }

    /**
     * 发送短信
     *
     * @param  array $param 发送短信
     *
     * @return array|error, error_code
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function sendSMSByAddress($data)
    {
        if (!empty($this->sms['message_prefix'])) {
            $message = "【" . $this->sms['message_prefix'] . "】" . $data['message'];
            if(isset($this->sms['prefix_location'])&&$this->sms['prefix_location']==1){
                $message = $data['message']."【" . $this->sms['message_prefix'] . "】";
            }
        } else {
            $message = $data['message'];
        }
        $formParams = [];
        // 取出sms_params参数，解析成数组
        $dataParams = $this->parseSMSParams($this->sms);
        $where = ['is_use' => 1];
        $smsData = app($this->shortMessageSetRepository)->getSMSSetDetail($where)->first()->toArray();
        // phone#phone#prefixValue=tel:#phone#  ， 拼接的参数分别表示：#phone,手机号；
        if (isset($dataParams) && !empty($dataParams)) {
            foreach ($dataParams as $k => $v) {
                //系统数据，目前有三种类型，phone和message，value
                if (strstr($k, '#phone')) {
                    unset($dataParams[$k]);
                    $v = isset($data['to']) ? implode(',', array_unique(array_filter($data['to']))) : '';
                    // 是否设置前后缀，如果设置了，则拼接，比如：tel:18012345678:end
                    if (strstr($k, '#formatValue=')) {
                        $formdata                               = substr(strstr($k, '='), '1');
                        $prefix                                 = strstr($formdata, '#phone#', true);
                        $suffix                                 = substr(strstr($formdata, '#phone#'), 7);
                        $formParams[strstr($k, '#phone', true)] = $prefix . $v . $suffix;
                    } else {
                        $formParams[strstr($k, '#phone', true)] = $v;
                        if (isset($smsData['phone_separate_type'])&&$smsData['phone_separate_type']==1) {
                        	$formParams[strstr($k, '#phone', true)] = explode(',',$v);
                        }
                    }
                }
                if (strstr($k, '#message')) {
                    unset($dataParams[$k]);
                    $v = isset($message) ? $message : '';
                    // 是否设置前后缀，如果设置了，则拼接，比如：tel:18012345678:end
                    if (strstr($k, '#formatValue=')) {
                        $formdata                                 = substr(strstr($k, '='), '1');
                        $prefix                                   = strstr($formdata, '#message#', true);
                        $suffix                                   = substr(strstr($formdata, '#message#'), 9);
                        $formParams[strstr($k, '#message', true)] = $prefix . $v . $suffix;
                    } else {
                        $formParams[strstr($k, '#message', true)] = $v;
                    }
                }
                // 固定值，去掉前缀#value,保留值
                if (strstr($k, '#value')) {
                    unset($dataParams[$k]);
                    $formParams[strstr($k, '#value', true)] = $v;
                }
                // 来自文件
                if (strstr($k, '#file')) {
                    unset($dataParams[$k]);
                    $apiParams = array_merge($formParams, [
                        'url'    => $v,
                        'handle' => 'data',
                        'method' => 'GET'
                    ]);
                    $responseContent = app($this->apiService)->guzzleHttp($apiParams);
                    $formParams[strstr($k, '#file', true)] = $responseContent['content'] ?? '';
                }
            }
        } else {
            return [
                'error'      => 1,
                'error_code' => '-117',
                'message'    => $message,
            ];
        }
        // $dataParams = [
        //     'cdkey' => trim($this->sms['sms_account']),
        //     'password' => trim($this->sms['sms_password']),
        //     'phone' => implode(',', array_unique(array_filter($data['to']))),
        //     'message' => $message
        // ];
        /*
        if(Utils::checkWhiteList($this->sms['sms_url'])){
        	return [
        			'error'      => 1,
        			'error_code' => '',
        			'message'    => trans("systemlog.white_list_check_failed", ['url'=>$this->sms['sms_url']]),
        	];
        }
        */
		$content = "";
        try {
            // 发送短信请求
        	$postData = ['form_params' => $formParams];
        	if (isset($this->sms['post_type'])&&$this->sms['post_type']=='json') {
        		if (isset($formParams['mac'])) {
        			$formParams = $formParams['mac'];
        		}
        		$postData = ['json'=> $formParams];
            }
            $guzzleResponse = app($this->client)->request('POST', $this->sms['sms_url'], $postData);
            $status         = $guzzleResponse->getStatusCode();
            if (!empty($guzzleResponse)) {
            	$content = $guzzleResponse->getBody()->getContents();
            }
        } catch (\Exception $e) {
            $status = 0;
            $content = $e->getMessage();
        }

        if ($status != '200') {
            return [
                'error'      => 1,
                'error_code' => '-2000',
                'message'    => $message,
            	'status' => $status,
            	'content' => $content
            ];
        }

        $response = $guzzleResponse->getBody();;
        // 错误代码返回值调试, 这里是转换成xml格式，有些平台返回值可能是其他类型，转换方式不同
        // dd((string) $response);
        // if (strpos($response, '<error>0</error>') !== false) {
        return [
            'error'      => 0,
            'error_code' => '',
            'message'    => $message,
        ];
        // }

        // preg_match("/<error>(.*)</iU", $response, $matchs);

        // if (empty($matchs)) {
        //     return [
        //         'error' => 1,
        //         'error_code' => '-2001',
        //         'message' => $message
        //     ];
        // }

        // return [
        //     'error' => 1,
        //     'error_code' => $matchs[1],
        //     'message' => $message
        // ];
    }

    /**
     * 发送短信
     *
     * @param  array $param 发送短信
     *
     * @return array|error, error_code
     */
    public function sendSMSByWebService($data)
    {
        $where = [
            'is_use' => 1,
        ];
        $smsData      = app($this->shortMessageSetRepository)->getSMSSetDetail($where)->first()->toArray();
        $functionName = isset($smsData['function_name']) ? $smsData['function_name'] : '';
        if (empty($functionName)) {
            return [
                'error'      => 1,
                'error_code' => '-2003',
                'message'    => $message,
            ];
        }
        if (!isset($smsData)) {
            return [];
        }
        // $serverUrl = 'http://211.103.103.189:8091/MAS5/services/cmcc_mas_wbs?wsdl';
        // 获取短信息签名
        $data['message'] = isset($data['message']) ? $data['message'] : '';
        if (isset($this->sms['message_prefix']) && !empty($this->sms['message_prefix'])) {
            $message = "【" . $this->sms['message_prefix'] . "】" . $data['message'];
            if(isset($this->sms['prefix_location'])&&$this->sms['prefix_location']==1){
            	$message = $data['message']."【" . $this->sms['message_prefix'] . "】";
            }
        } else {
            $message = $data['message'];
        }

        // wsdl文件地址
        $serverUrlWsdl = isset($smsData['sms_url_wsdl']) ? $smsData['sms_url_wsdl'] : '';

        // 取出参数，解析成数组
        $formParams = [];
        $dataParams = $this->parseSMSParams($smsData);
        if (isset($dataParams) && !empty($dataParams)) {
            foreach ($dataParams as $k => &$v) {
                //系统数据，目前有三种类型，phone和message,value
                if (strstr($k, '#phone')) {
                    unset($dataParams[$k]);
                    // 判断选择哪种符号分隔, 目前两种 逗号, 和 分号 ;
                    // $v = isset($data['to']) ? implode(',', array_unique(array_filter($data['to']))) : '';
                    $v = isset($data['to']) ? array_unique(array_filter($data['to'])) : '';
                    // 手机号码集合
                    $tels = [];
                    $allPrefix = '';
                    foreach ($v as $j => &$tel) {
                        // 是否设置前后缀，如果设置了，则拼接，比如：tel:18012345678:end
                        if (strstr($k, '#formatValue=')) {
                            $formdata = substr(strstr($k, '='), '1');
                            $prefix   = strstr($formdata, '#phone#', true);
                            // 判断是否设置了号码集合的前缀#prefix，如果设置了，则截取总体前缀，并截取单个号码的前缀
                            if (strstr($prefix, '#prefix#')) {
                                //单个号码前缀
                                $singlePrefix = strstr($prefix, '#prefix#');
                                $singlePrefix = substr($singlePrefix, '8');
                                //号码集合前缀
                                $allPrefix = strstr($prefix, '#prefix#', true);
                            } else {
                                $singlePrefix = $prefix; // 单个号码前缀
                                $allPrefix    = ''; // 号码集合前缀
                            }
                            $suffix = substr(strstr($formdata, '#phone#'), 7);
                            // 拼接到单个号码上
                            $tels[] = $singlePrefix . $tel . $suffix;
                            // $tels[] = $tel.$suffix;
                            // $formParams[strstr($k,'#phone',true)] = $prefix.$tel.$suffix;
                        } else {
                            // $formParams[strstr($k,'#phone',true)] = $tel;
                            $tels[] = $tel;
                        }
                    }
                    $formParams[strstr($k, '#phone', true)] = $allPrefix . implode('', $tels);
                    if (isset($smsData['phone_separate_type'])&&$smsData['phone_separate_type']==1) {
                    	$formParams[strstr($k, '#phone', true)] = $tels;
                    }
                }
                if (strstr($k, '#message')) {
                    unset($dataParams[$k]);
                    $v = isset($message) ? $message : '';
                    // 否设置前后缀，如果设置了，则拼接，比如：tel:#phone#:end
                    if (strstr($k, '#formatValue=')) {
                        $formdata                                 = substr(strstr($k, '='), '1');
                        $prefix                                   = strstr($formdata, '#message#', true);
                        $suffix                                   = substr(strstr($formdata, '#message#'), 9);
                        $formParams[strstr($k, '#message', true)] = $prefix . $v . $suffix;
                    } else {
                        $formParams[strstr($k, '#message', true)] = $v;
                    }
                }
                // 固定值，去掉前缀#value,保留值
                if (strstr($k, '#value')) {
                    unset($dataParams[$k]);
                    $formParams[strstr($k, '#value', true)] = $v;
                }
            }
        }
        // // 短信发送地址
        // $serverUrl = isset($smsData['sms_url']) ? $smsData['sms_url'] : '';
        // // 企业ID
        // $smsAccount = isset($smsData['sms_account']) ? $smsData['sms_account'] : '';
        // 调用webservice里面的方法
        try {
            $soap = new \SoapClient($serverUrlWsdl);
        } catch (\Exception $e) {
            $status = 0;
            return [
                'error'      => 1,
                'error_code' => '-117',
                'message'    => $message,
            ];
        }
        // $data = [
        //     'ApplicationID'        => $smsAccount,
        //     'DestinationAddresses' => $serverUrl,
        //     'Message'              => $message,
        //     'MessageFormat'        => 'ASCII',
        //     'SendMethod'           => 'Normal',
        //     'DeliveryResultRequest'=> true,
        // ];
        // $formParams['DestinationAddresses'] = "tel:18261299959;15951556176;";
        try {
            $status = $soap->$functionName($formParams);
            // $paramReturn = [
            //     'ApplicationID' => 'P000000000000074',
            //     'RequestIdentifier' => $status->RequestIdentifier,
            // ];
            // $result = $soap->GetSmsDeliveryStatus($paramReturn);
        } catch (\Exception $e) {
            $status = 0;
        }
        if ($status === 0) {
            return [
                'error'      => 1,
                'error_code' => '-117',
                'message'    => $message,
            ];
        } else {
            return [
                'error'      => 0,
                'error_code' => '',
                'message'    => $message,
            ];
        }
    }

    /**
     * 获取短信列表
     *
     * @param array $param 查询条件
     *
     * @return rray 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getSMSs($param, $userId = '')
    {
        $param = $this->parseParams($param);

        if (!empty($userId)) {
            $param['withUser'] = $userId;
        }

        return $this->response(app($this->shortMessageRepository), 'getNum', 'getSMSs', $param);
    }

    /**
     * 删除手机短信
     *
     * @param string $smsId 短信id
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-07 创建
     */
    public function deleteSMS($smsId, $userInfo)
    {
        // 判断权限，只有拥有256-短信管理菜单的权限，才可以删除流程
        $userMenu = [];
        if (isset($userInfo["menus"]) && $userInfo["menus"] && isset($userInfo["menus"]["menu"])) {
            $userMenu = $userInfo["menus"]["menu"];
        }
        // 没有"短信管理"权限
        if(!$this->verifyUserMenuPermission($userMenu,"256")) {
            return ['code' => ['0x000006', 'common']];
        }

        $smsIds = array_filter(explode(',', $smsId));
        return app($this->shortMessageRepository)->deleteById($smsIds);
    }

    public function verifyUserMenuPermission($userMenu, $targetMenu)
    {
        $targetMenu = explode(",", $targetMenu);
        if (count($targetMenu)) {
            foreach ($targetMenu as $key => $value) {
                if (in_array($value, $userMenu)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取手机短信详情，带权限判断
     *
     * @param string $smsId 短信id
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-07 创建
     */
    public function getSMS($smsId, $userInfo)
    {
        $result = app($this->shortMessageRepository)->getSMSDetail($smsId);
        if($result){
            $data = $result->toArray();
            $user_id = $userInfo['user_id'];

            $userMenu = [];
            if (isset($userInfo["menus"]) && $userInfo["menus"] && isset($userInfo["menus"]["menu"])) {
                $userMenu = $userInfo["menus"]["menu"];
            }
            // 没有短信管理权限
            if(!$this->verifyUserMenuPermission($userMenu,"256")) {
                if($data['user_from']  != $user_id  && $data['user_to'] != $user_id){
                    return ['code' => ['0x000006','common']];
                }
            }
        }
        return $result;
    }

    public function showShortMessage()
    {
        $message   = app($this->shortMessageSetRepository)->getSMSSet();
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        // 判断是否允许系统发送短信
        $configs                      = DB::table('short_message_config')->select('*')->first();
        $configs                      = json_decode(json_encode($configs), true);
        $message['system_send_allow'] = isset($configs['system_send_allow']) ? isset($configs['system_send_allow']) : '';
        if (isset($auhtMenus["code"]) || !in_array(43, $auhtMenus) || empty($message) || $message['system_send_allow'] == 0) {
            return "false";
        } else {
            return "true";
        }

    }

    /**
     * 获取短信发送种类
     * @return array 种类集合
     */
    public function getSMSType()
    {
        $types = DB::table('short_message_type')->select('*')->get();
        $types = json_decode(json_encode($types), true);
        if ($types) {
            return $types;
        }
        return [];
    }

    /**
     * 获取短信列表
     * @param  array $param 分页条件
     * @return array 短信列表
     */
    public function getSMSList($param)
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->shortMessageSetRepository), 'getSMSSetNum', 'getSMSSets', $param);
    }

    /**
     * 获取webservice所有方法
     * @return array
     */
    public function getWebServiceFunction($param)
    {
        $serverUrlWsdl = isset($param['sms_url_wsdl']) ? $param['sms_url_wsdl'] : '';
        // 添加http和https验证过滤。
        if(!(strpos($serverUrlWsdl, 'http://') === 0 || strpos($serverUrlWsdl, 'https://') === 0)) {
            return ['code' => ['0x000018','common']];
        }
        
        if(!check_white_list($serverUrlWsdl)){
            return ['code' => ['0x000025','common']];
        }
        // $serverUrlWsdl = 'http://211.103.103.189:8091/MAS5/services/cmcc_mas_wbs?wsdl';
        $result  = [];
        
        $content = @file_get_contents($serverUrlWsdl);
        if (empty($content)) {
            return ['code' => ["YiMei" . "." . '-2002', 'sms']];
        }
        try {
            $soap = new \SoapClient($serverUrlWsdl);
        } catch (\Exception $e) {
            $status = 0;
            return ['code' => ["YiMei" . "." . '-2002', 'sms']];
        }
        $result = $soap->__getFunctions();
        if (isset($result) && !empty($result)) {
            foreach ($result as $k => &$v) {
                $v = strstr($v, '(', true);
                $v = explode(' ', $v);
                $v = isset($v[1]) ? $v[1] : '';
            }
        }
        return $result;
    }

    /**
     * 解析手机短信配置表的sms_params字段数据
     * @param  json $param json数据
     * @return array 发送手机短信需要的参数
     */
    public function parseSMSParams($smsData)
    {
        // 取出参数，解析成数组
        $data                  = [];
        $smsData['sms_params'] = isset($smsData['sms_params']) && !empty($smsData['sms_params']) ? $smsData['sms_params'] : '';
        $params                = [];
        if (!empty($smsData['sms_params'])) {
            $params = json_decode($smsData['sms_params'], true);
        }
        if (!empty($params)) {
            foreach ($params as $k => $v) {
            	if(!isset($v['key'])){
            		$v['key'] = "";
            	}
                if (isset($v['paramsType'])) {
                    if (isset($v['formatValue']) && !empty($v['formatValue'])) {
                        $data[$v['key'] . $v['paramsType'] . '#' . 'formatValue' . '=' . $v['formatValue']] = isset($v['value']) ? $v['value'] : '';
                        continue;
                    } else {
                        $data[$v['key'] . $v['paramsType']] = isset($v['value']) ? $v['value'] : '';
                        continue;
                    }
                }

                $data[$v['key']] = $v['value'];
            }
        }
        return $data;
    }

    /**
     * 删除手机短信配置
     * @param  string $smsId id字符串
     * @return bool
     */
    public function smsSetDelete($smsId)
    {
        $smsIds = isset($smsId['sms_id']) ? $smsId['sms_id'] : '';
        $smsIds = array_filter(explode(',', $smsIds));
        return app($this->shortMessageSetRepository)->deleteById($smsIds);
    }

    /**
     * 编辑手机短信系统配置
     * @param  array $data 编辑配置
     * @return string
     */
    public function editSmsConfig($data)
    {
        if (!isset($data) || empty($data)) {
            return ['code' => ["YiMei" . "." . '-2004', 'sms']];
        }
        $result = DB::table('short_message_config')->where('config_id', 1)->update($data);
        return $result;
    }

    /**
     * 获取手机短信系统配置
     * @param  array $data 获取配置
     * @return string
     */
    public function getSmsConfig($data)
    {
        if (!isset($data) && empty($data)) {
            return false;
        }
        $result = DB::table('short_message_config')->select('*')->first();
        $result = json_decode(json_encode($result), true);
        return $result;
    }

    /**
     * 获取每个方法对应的参数名称
     * @param  array $param wsdl地址和方法名称
     * @return array 参数名集合
     */
    public function getSMSFunctionParams($param)
    {
        $functionName  = isset($param['function_name']) ? $param['function_name'] : '';
        $serverUrlWsdl = isset($param['sms_url_wsdl']) ? $param['sms_url_wsdl'] : '';
        // $serverUrlWsdl = 'http://211.103.103.189:8091/MAS5/services/cmcc_mas_wbs?wsdl';
        $result = [];
        try {
            $soap = new \SoapClient($serverUrlWsdl);
        } catch (\SoapFault $e) {
            $status = 0;
            return ['code' => ["YiMei" . "." . '-2002', 'sms']];
        }
        $types     = $soap->__getTypes();
        $functions = $soap->__getFunctions();
        // 获取所有方法名称，并截取请求的参数名称
        if (isset($functions) && !empty($functions)) {
            $functionNameAndParams = [];
            foreach ($functions as $k => $v) {
                $v                            = strstr($v, '(', true);
                $v                            = explode(' ', $v);
                $v                            = isset($v[1]) ? $v[1] : '';
                $functionNameAndParams[$k][0] = $v;
                $functionNameAndParams[$k][1] = explode(' ', str_replace('(', ' ', strstr($functions[$k], '(')))[1];
            }
        }
        // 匹配方法对应的参数名称
        foreach ($functionNameAndParams as $k => $v) {
            if ($v['0'] === $functionName) {
                $functionNameAndParams = $v[1];
                break;
            }
        }
        // 匹配返回的结构
        foreach ($types as $k => $v) {
            if (strpos($v, $functionNameAndParams)) {
                $params                = [];
                $functionNameAndParams = explode(';', $v);
                foreach ($functionNameAndParams as $j => $i) {
                    $params[] = trim(substr($i, strrpos($i, ' ')));
                    if ($params[$j] === '}') {
                        unset($params[$j]);
                    }
                }
                break;
            }
        }
        return $params;

    }

    public function getSMSTemplate()
    {
        return app($this->shortMessageTemplateRepository)->getFieldInfo([]);
    }

}
