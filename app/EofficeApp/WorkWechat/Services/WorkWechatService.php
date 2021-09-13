<?php

namespace App\EofficeApp\WorkWechat\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\syncWorkWeChatJob;
use App\Jobs\SyncWorkWeChatAttendanceJob;
use Queue;
use App\Utils\Utils;
use GuzzleHttp\Client;
use Schema;
use Log;
use Illuminate\Support\Facades\Redis;

/**
 * 企业微信
 *
 * @author: 白锦
 *
 * @since：2017-06-01
 */
class WorkWechatService extends BaseService
{
    private $WorkWechatRepository;
    private $WorkWechatAppRepository;
    private $WechatTicketRepository;
    private $WorkWechatUserRepository;
    private $workWechat;
    private $userRepository;
    private $userService;
    private $authService;
    private $remindsRepository;
    private $systemRemindsRepository;
    public  $attachmentService;
    private $departmentRepository;
    private $departmentService;
    private $userMenuService;
    private $empowerService;
    private $WorkWechatSyncLogRepository;
    private $companyRepository;
    private $unifiedMessageService;
    private $attendanceSettingService;
    private $roleService;
    private $workWechatTimingSyncAttendanceRepository;
    private $attendanceService;
    private $attendanceMachineService;
    private $workWechatSyncAttendanceLogRepository;
    private $workWechatAppTicketRepository;
    private $workWechatWithCustomerRepository;
    private $workWechatGroupWithCustomerRepository;
    private $linkmanRepository;
    private $customerRepository;
    private $linkmanService;
    private $customerService;

    public function __construct()
    {
        $this->WorkWechatRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatRepository';
        $this->WorkWechatAppRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatAppRepository';
        $this->WorkWechatUserRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatUserRepository';
        $this->workWechatTimingSyncAttendanceRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatTimingSyncAttendanceRepository';
        $this->WorkWechatSyncLogRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatSyncLogRepository';
        $this->workWechatSyncAttendanceLogRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatSyncAttendanceLogRepository';
        $this->workWechat = 'App\Utils\WorkWechat';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->authService = 'App\EofficeApp\Auth\Services\AuthService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->remindsRepository = 'App\EofficeApp\System\Remind\Repositories\RemindsRepository';
        $this->systemRemindsRepository = 'App\EofficeApp\System\Remind\Repositories\SystemRemindsRepository';
        $this->WechatTicketRepository = 'App\EofficeApp\WorkWechat\Repositories\WechatTicketRepository';
        $this->departmentRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->departmentService = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->companyRepository = 'App\EofficeApp\System\Company\Repositories\CompanyRepository';
        $this->empowerService = 'App\EofficeApp\Empower\Services\EmpowerService';
        //$this->heterogeneousSystemService = 'App\EofficeApp\UnifiedMessage\Services\HeterogeneousSystemService';
        $this->unifiedMessageService = 'App\EofficeApp\UnifiedMessage\Services\UnifiedMessageService';
        $this->attendanceSettingService = 'App\EofficeApp\Attendance\Services\AttendanceSettingService';
        $this->roleService = 'App\EofficeApp\Role\Services\RoleService';
        $this->attendanceService = "App\EofficeApp\Attendance\Services\AttendanceService";
        $this->attendanceMachineService = "App\EofficeApp\Attendance\Services\AttendanceMachineService";
        $this->workWechatAppTicketRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatAppTicketRepository';
        $this->workWechatWithCustomerRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatWithCustomerRepository';
        $this->workWechatGroupWithCustomerRepository = 'App\EofficeApp\WorkWechat\Repositories\WorkWechatGroupWithCustomerRepository';
        $this->linkmanRepository = 'App\EofficeApp\Customer\Repositories\LinkmanRepository';
        $this->customerRepository = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
        $this->linkmanService = 'App\EofficeApp\Customer\Services\LinkmanService';
        $this->customerService = 'App\EofficeApp\Customer\Services\CustomerService';
    }

    public function saveWorkWeChatSync($param)
    {

        if (isset($param['sync_direction'])) {
            if ($param['sync_direction'] == '2') {
                if (isset($param['sync_ini_role'])) {
                    $param['sync_ini_role'] = implode(',', $param['sync_ini_role']);
                }
                if (!is_numeric($param['sync_ini_dept_id']) || $param['sync_ini_dept_id'] == 1 || $param['sync_ini_dept_id'] == 0) {
                    return ['code' => ['sync_ini_dept_id_error', 'workwechat']];
                }
                if (isset($param['sync_leave_dept_id']) && !empty($param['sync_leave_dept_id'])) {
                    if (!is_numeric($param['sync_leave_dept_id']) || $param['sync_leave_dept_id'] == 1) {
                        return ['code' => ['sync_leave_dept_id_error', 'workwechat']];
                    }
                }
//                if (isset($param['auto_sync']) && $param['auto_sync'] == 1) {
//                    if (empty($param['sync_must_token'])||empty($param['sync_must_encoding_aes_key'])) {
//                        return ['Token和EncodingAESKey必填']];
//                    }
//                }
            } else {
                if (isset($param['sync_type']) && $param['sync_type'] === 0) {
                    $param = [
                        'sync_direction' => $param['sync_direction'],
                        'sync_type' => $param['sync_type'],
                        'sync_leave' => $param['sync_leave'],
                        'auto_sync' => $param['auto_sync'],
                    ];
                } else {
                    $param = [
                        'sync_direction' => $param['sync_direction'],
                        'sync_type' => $param['sync_type'],
                        'sync_leave' => $param['sync_leave'],
                    ];
                }
            }
            $data = array_intersect_key($param, array_flip(app($this->WorkWechatRepository)->getTableColumns()));
            $weixinData = app($this->WorkWechatRepository)->getWorkWechat();
            //dd($data);
            if ($weixinData) {
                $res = app($this->WorkWechatRepository)->updateData($data, ['id' => 1]);
            } else {
                $res = app($this->WorkWechatRepository)->insertData($data);
            }
            if (!$res) {
                return ['code' => ['save_false', 'workwechat']];
            } else {
                return $res;
            }
        } else {
            return ['code' => ['params_error', 'workwechat']];
        }

    }

    //自动更新access_token
    public function getAccessToken($agentid = 0)
    {
        $wechat = $this->getWorkWechat();
        if (!isset($wechat->corpid) || empty($wechat->corpid)) {
            return false;
        }
        if (empty($agentid)) {
            $agentid = $_COOKIE["agentid"];
        }
        if (empty($agentid)) {
            return false;
        }
        $corpid = $wechat->corpid;
        $domain = $wechat->domain;
        $app = app($this->WorkWechatAppRepository)->getAppById($agentid);
        if (empty($app)) {
            $secret = $wechat->secret;
            $sms_agent = $wechat->sms_agent;
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}";
            $wechatConnect = getHttps($url);
            $connectList = json_decode($wechatConnect, true);
            if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
                $code = $connectList['errcode'];
                return ['code' => ["$code", 'workwechat']];
            }
            $access_token = $connectList['access_token'];
            $agent_url = "https://qyapi.weixin.qq.com/cgi-bin/agent/get?access_token={$access_token}&agentid={$sms_agent}";
            $agent = getHttps($agent_url);
            $agentList = json_decode($agent, true);
            $module = trans('workwechat.moa');
            if (!empty($agentList) && $agentList['errcode'] == 0) {
                $module = $agentList['name'];
            }
            $appData = [
                "agentid" => $wechat->sms_agent,
                "module" => $module,
                "secret" => $wechat->secret,
            ];
            $this->wechatAppSave($appData);
            $app = app($this->WorkWechatAppRepository)->getAppById($agentid);
        }
        if (empty($app)) {
            return false;
        }
        $secret = $app->secret;
        $access_token = $app->access_token;
        $create_token = $app->create_token;
        if (abs(time() - $create_token) > 7200) {
            //GET请求的地址
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}";
            $wechatConnect = getHttps($url);
            $connectList = json_decode($wechatConnect, true);
            if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
                $code = $connectList['errcode'];
                return ['code' => ["$code", 'workwechat']];
            }
            $data['access_token'] = $connectList['access_token'];
            $data['create_token'] = time();
            app($this->WorkWechatAppRepository)->updateData($data, ['agentid' => $agentid]);
            return $data['access_token'];
        }
        return $access_token;
    }

    public function showWorkWechat($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        //$power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($userId);
        $wechat = $this->getWorkWechat();
        if (isset($auhtMenus["code"]) || !in_array(913, $auhtMenus) || empty($wechat) || $wechat['is_push'] == 0) {
            return "false";
        } else {
            return "true";
        }
    }

    public function showAppPush($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $power = app($this->empowerService)->getMobileEmpower($userId);
        if (empty($power)) {
            return "false";
        } else {
            return "true";
        }
    }

    public function showEmail()
    {
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (isset($auhtMenus["code"]) || !in_array(11, $auhtMenus)) {
            return "false";
        } else {
            return "true";
        }
    }

    /**
     * 保存企业微信配置
     * @param $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function saveWorkWechat($data)
    {
        $corpid = trim($data['corpid']);
        $domain = trim($data["domain"]);
        $domain = trim($domain, "/");
        $sms_agent = trim($data['sms_agent']);
        $secret = trim($data['secret']);
        $sync_type = isset($data['sync_type']) ? $data['sync_type'] : 0;
        $sync_leave = isset($data['sync_leave']) ? $data['sync_leave'] : 0;
        $sync_direction = isset($data['sync_direction']) ? $data['sync_direction'] : 1;
        $im_is_push = isset($data['im_is_push']) ? $data['im_is_push'] : 0;
        $sync_secret = isset($data['sync_secret']) ? $data['sync_secret'] : '';
        $creator_id = isset($data['creator_id']) ? $data['creator_id'] : '';
        //同步组织架构
        $syncData = [];
        if (isset($data['auto_sync'])) {
            $syncData['auto_sync'] = trim($data['auto_sync']);
        }
        if (isset($data['is_autohrms'])) {
            $syncData['is_autohrms'] = trim($data['is_autohrms']);
        }
        if (isset($data['is_open_sync_role'])) {
            $syncData['is_open_sync_role'] = trim($data['is_open_sync_role']);
        }
        if (isset($data['sync_ini_dept_id'])) {
            $syncData['sync_ini_dept_id'] = trim($data['sync_ini_dept_id']);
        }
        if (isset($data['sync_ini_role'])) {
            $syncData['sync_ini_role'] = trim($data['sync_ini_role']);
        }
        if (isset($data['sync_ini_user_password'])) {
            $syncData['sync_ini_user_password'] = trim($data['sync_ini_user_password']);
        }
        if (isset($data['sync_ini_user_status'])) {
            $syncData['sync_ini_user_status'] = trim($data['sync_ini_user_status']);
        }
        if (isset($data['sync_ini_wap_allow'])) {
            $syncData['sync_ini_wap_allow'] = trim($data['sync_ini_wap_allow']);
        }
        if (isset($data['sync_leave_dept_id'])) {
            $syncData['sync_leave_dept_id'] = trim($data['sync_leave_dept_id']);
        }
        if (isset($data['sync_must_encoding_aes_key'])) {
            $syncData['sync_must_encoding_aes_key'] = trim($data['sync_must_encoding_aes_key']);
        }
        if (isset($data['sync_must_token'])) {
            $syncData['sync_must_token'] = trim($data['sync_must_token']);
        }
        //同步组织架构
        $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$corpid&corpsecret=$secret";
        $wechatConnect = getHttps($url);
        $connectList = json_decode($wechatConnect, true);
        if (empty($connectList)) {
            return ['code' => ["0x040001", 'workwechat']];
        } else {
            if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
                $code = $connectList['errcode'];
                return ['code' => ["$code", 'workwechat']];
            }
        }
        $weixinData = [
            "domain" => $domain,
            "corpid" => $corpid,
            "sms_agent" => $sms_agent,
            "secret" => $secret,
            "sync_secret" => $sync_secret,
            "wechat_code" => isset($data["wechat_code"]) ? $data["wechat_code"] : "",
            "is_push" => isset($data["is_push"]) ? $data["is_push"] : 0,
            "im_is_push" => empty($im_is_push) ? 0 : $im_is_push,
            "sync_type" => empty($sync_type) ? 0 : $sync_type,
            "sync_leave" => empty($sync_leave) ? 0 : $sync_leave,
            "creator_id" => $creator_id,
            "sync_direction" => empty($sync_direction) ? 1 : $sync_direction,
        ];
        $weixinData = array_merge($weixinData, $syncData);
        app($this->WorkWechatRepository)->truncateWechat();
        $qyData = app($this->WorkWechatRepository)->insertData($weixinData);
        $app = app($this->WorkWechatAppRepository)->getAppById($sms_agent);
        if (empty($app)) {
            $access_token = $connectList['access_token'];
            $agent_url = "https://qyapi.weixin.qq.com/cgi-bin/agent/get?access_token={$access_token}&agentid={$sms_agent}";
            $agent = getHttps($agent_url);
            $agentList = json_decode($agent, true);
            $module = trans("workwechat.moa");
            if (!empty($agentList) && $agentList['errcode'] == 0) {
                $module = $agentList['name'];
            }
            $appData = [
                "agentid" => $sms_agent,
                "module" => $module,
                "secret" => $secret,
            ];
            $this->wechatAppSave($appData);
        }
        return $data;
    }

    /**
     * 匹配用户
     * @param $tempUserId
     * @return array|bool|null
     * @author [dosy]
     */
    public function matchUser($tempUserId)
    {
        $agentid = $_COOKIE["agentid"];
        $access_token = $this->getAccessToken($agentid);

        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&userid={$tempUserId}";
        $postJson = getHttps($url);
        if (!$postJson) {
            return false;
        }
        $postObj = json_decode($postJson, true);
        if (isset($postObj['errcode']) && $postObj['errcode'] != 0) {
            $code = $postObj['errcode'];
            return ['code' => ["$code", 'workwechat']];
        }
        $weixinid = isset($postObj["weixinid"]) ? $postObj["weixinid"] : "";
        $mobile = isset($postObj["mobile"]) ? $postObj["mobile"] : "";
        $email = isset($postObj["email"]) ? $postObj["email"] : "";

        if ($weixinid || $mobile || $email) {
            if ($mobile) {
                $where = ["phone_number" => [$mobile]];
            } else {
                if ($weixinid) {
                    $where = ["weixin" => [$weixinid]];
                } else {
                    if ($email) {
                        $where = ["email" => [$email]];
                    }
                }
            }
            $user = app($this->userRepository)->checkUserByWhere($where);
            return $user;
        } else {
            return null;
        }
    }

    /**
     * 获得企业微信配置
     * @return mixed
     * @author [dosy]
     */
    public function getWorkWechat()
    {
        $workWechatSet = app($this->WorkWechatRepository)->getWorkWechat();
        if (isset($workWechatSet->wechat_code)) {
            $checkAttachment = app($this->attachmentService)->getOneAttachmentById($workWechatSet->wechat_code);
            if (isset($checkAttachment['code'])) {
                return $checkAttachment;
            }
            if (isset($checkAttachment['temp_src_file']) && file_exists($checkAttachment['temp_src_file'])) {
                return $workWechatSet;
            } else {
                $workWechatSet->wechat_code = '';
            }
        }
        return $workWechatSet;
    }

    /**
     * 企业微信消息推送到指定用应用菜单保存
     * @param $param
     * @return array
     * @creatTime 2020/11/12 17:09
     * @author [dosy]
     */
    public function saveRemindMenu($param)
    {
        if (isset($param['id']) && !empty($param['id'])) {
            $remindMenu = isset($param['remind_menu']) ? json_encode($param['remind_menu']) : '';
            return app($this->WorkWechatAppRepository)->updateData(['remind_menu' => $remindMenu], ['id' => $param['id']]);
        } else {
            return ['code' => ['params_error', 'workwechat']];
        }
    }

    /**
     * 企业微信消息推送开关设置
     * @param $param
     * @return array
     * @creatTime 2020/11/12 17:08
     * @author [dosy]
     */
    public function saveWechatAppPush($param)
    {
        if (isset($param['is_push']) && isset($param['sms_agent'])) {
            return app($this->WorkWechatRepository)->updateData(['is_push' => $param['is_push']], ['sms_agent' => $param['sms_agent']]);
        } else {
            return ['code' => ['params_error', 'workwechat']];
        }
    }

    /**
     * 应用保存
     * @param $data
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function wechatAppSave($data)
    {
        // dd($data);
        $workwechat = app($this->WorkWechatRepository)->getWorkWechat();
        if (!isset($workwechat->corpid)) {
            return ['code' => ["0x040007", 'workwechat']];
        }
        $corpid = $workwechat->corpid;
        if (empty($data['agentid'])) {
            return false;
        }
        $agentid = trim($data['agentid']);
        $secret = trim($data['secret']);
        $module = isset($data['module']) ? $data['module'] : '';
        $agentType = isset($data['agent_type']) ? $data['agent_type'] : '1';
        $isStart = isset($data['is_start']) ? $data['is_start'] : 0;
        $syncTime = isset($data['sync_time']) ? $data['sync_time'] : [];
        $remindMenu = isset($data['remind_menu']) ? json_encode($data['remind_menu']) : '';
        // $app = app($this->WorkWechatAppRepository)->getAppById($agentid);
        if (isset($data['id']) && !empty($data['id'])) { //更新
            $app = app($this->WorkWechatAppRepository)->getOneFieldInfo(['id' => $data['id']]);
            if (empty($app)) {
                return ['code' => ['params_error', 'workwechat']];
            }
            if ($app->agentid != $agentid) {
                $agentApp = app($this->WorkWechatAppRepository)->getAppById($agentid);
                if ($agentApp) {
                    return ['code' => ['agent_id_exist', 'workwechat']];
                }
                return $this->saveAgentCheck($app->secret, $secret, $corpid, $module, $agentid, $remindMenu, $data['id'], $isStart, $syncTime, $agentType);
            } else {
                return $this->saveAgentCheck($app->secret, $secret, $corpid, $module, $agentid, $remindMenu, $data['id'], $isStart, $syncTime, $agentType);
            }
        } else { //新增
            $agentApp = app($this->WorkWechatAppRepository)->getAppById($agentid);
            if ($agentApp) {
                return ['code' => ['agent_id_exist', 'workwechat']];
            }
            $secretCheck = app($this->WorkWechatAppRepository)->getOneFieldInfo(['secret' => $secret]);
            if ($secretCheck) {
                return ['code' => ['secret_exist', 'workwechat']];
            }
            $connectList = $this->getWorkWeChatToken($corpid, $secret);
            if (isset($connectList['code'])) {
                return $connectList;
            }
            //打卡应用
            if ($agentType == 2) {
                $this->attendanceTimingSet($isStart, $syncTime);
            }
            $appData = [
                "module" => $module,
                "agentid" => $agentid,
                "secret" => $secret,
                "agent_type" => $agentType,
                "access_token" => $connectList['access_token'],
                "remind_menu" => $remindMenu,
                "create_token" => time(),
            ];
            // $result = app($this->WorkWechatAppRepository)->updateData($updateAppData,['id'=>$data['id']]);
            $result = app($this->WorkWechatAppRepository)->insertData($appData);
            return $result;
        }
    }

    /**
     * 应用检查保存
     * @param $oldSecret
     * @param $secret
     * @param $corpid
     * @param $module
     * @param $agentid
     * @param $remindMenu
     * @param $id
     * @param $agentType
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function saveAgentCheck($oldSecret, $secret, $corpid, $module, $agentid, $remindMenu, $id, $isStart, $syncTime, $agentType = 1)
    {
        if ($oldSecret != $secret) {
            $secretCheck = app($this->WorkWechatAppRepository)->getOneFieldInfo(['secret' => $secret]);
            if ($secretCheck) {
                return ['code' => ['secret_exist', 'workwechat']];
            }
            $connectList = $this->getWorkWeChatToken($corpid, $secret);
            if (isset($connectList['code'])) {
                return $connectList;
            }
            //打卡应用
            if ($agentType == 2) {
                $this->attendanceTimingSet($isStart, $syncTime);
            }
            $updateAppData = [
                "module" => $module,
                "agentid" => $agentid,
                "secret" => $secret,
                "access_token" => $connectList['access_token'],
                "remind_menu" => $remindMenu,
                "create_token" => time(),
            ];
            $result = app($this->WorkWechatAppRepository)->updateData($updateAppData, ['id' => $id]);
            //$qyData = app($this->WorkWechatAppRepository)->insertData($appData);
            return $result;
        } else {
            //打卡应用
            if ($agentType == 2) {
                $this->attendanceTimingSet($isStart, $syncTime);
            }
            $updateAppData = [
                "module" => $module,
                "agentid" => $agentid,
                "remind_menu" => $remindMenu,
            ];
            $result = app($this->WorkWechatAppRepository)->updateData($updateAppData, ['id' => $id]);
            //$qyData = app($this->WorkWechatAppRepository)->insertData($appData);
            return $result;
        }
    }

    public function attendanceTimingSet($isStart, $syncTime)
    {
        //打卡应用
        app($this->workWechatTimingSyncAttendanceRepository)->truncateWechat();
        $timingSet = [];
        if ($isStart == 1) {
            foreach ($syncTime as $value) {
                $timingSet[] = ['is_start' => $isStart, 'sync_time' => $value];
            }
            app($this->workWechatTimingSyncAttendanceRepository)->insertMultipleData($timingSet);
        }
    }

    /**
     * 获取同步考勤设置
     * @return mixed
     * @creatTime 2020/12/8 10:12
     * @author [dosy]
     */
    public function getAttendanceSyncSet()
    {
        $agentData = app($this->WorkWechatAppRepository)->getWorkWechatApp(['agent_type' => 2])->toArray();
        if ($agentData) {
            $data['id'] = $agentData[0]['id'];
            $data['agentid'] = $agentData[0]['agentid'];
            $data['secret'] = $agentData[0]['secret'];
        }
        $timingData = app($this->workWechatTimingSyncAttendanceRepository)->getFieldInfo(['is_start' => 1]);
        if (empty($timingData)) {
            $data['is_start'] = 0;
        } else {
            $data['is_start'] = 1;
            foreach ($timingData as $v) {
                $data['sync_time'][] = $v['sync_time'];
            }
        }
        return $data;
    }

    /**
     * 请求应用access_token
     * @param $corpid
     * @param $secret
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatToken($corpid, $secret)
    {
        $api = config('weChat.workWeChatApi.getToken');
        $fullApi = $api . "?corpid=" . $corpid . "&corpsecret=" . $secret;
        return $this->workWeChatRequest($fullApi, 'GET');
    }

    /**
     * 获取系统内标准的提醒菜单类型值（部分不在，统一消息、自定义字段消息不在内）
     * @return array
     * @author [dosy]
     */
    public function getRemindMenu()
    {
        $list = app($this->systemRemindsRepository)->getRemindsParent(['fields' => ['remind_menu']])->map(function ($value) {
            return $value['remind_menu'];
        })->toArray();
        $locale = \Lang::getLocale();
        $systemLangs = trans('system', [], $locale);
        $workWeChatRemindAgentBind = config('weChat.workWeChatRemindAgentBind');
        $menuName = [];
        foreach ($list as $menu) {
            if (isset($systemLangs[$menu])) {
                foreach ($workWeChatRemindAgentBind as $key => $value) {
                    if (in_array($menu, $value)) {
                        $menuName[] = ['remind_menu' => $menu, 'name' => $systemLangs[$menu], 'type' => $key];
                        continue;
                    }
                }
            }
        }
        return $menuName;
    }

    /**
     * 通过code获取用户信息
     * @param $code
     * @return array|mixed
     * @author [dosy]
     */
    public function workwechatCode($code)
    {
        if (!$code) {
            return ['code' => ['0x040002', 'workwechat']];
        }
        $workwechat = $this->getWorkWechat();
        $agentid = isset($_COOKIE["agentid"]) ? $_COOKIE["agentid"] : $workwechat->sms_agent;
        $access_token = $this->getAccessToken($agentid);
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";

        $postJson = getHttps($url);
        $postObj = json_decode($postJson, true);

        if (isset($postObj['errcode']) && $postObj['errcode'] != 0) {
            $code = $postObj['errcode'];
            return ['code' => ["$code", 'workwechat']];
        }
        if (!isset($postObj["UserId"])) {
            return ['code' => ["0x040003", 'workwechat']];
        }
        $userId = $postObj["UserId"];
        //获取当前的userInfo、 使用手机账号 weixin 邮箱 去匹配
        $user = $this->matchUser($userId);
        if (!$user) {
            return ['code' => ["0x040008", 'workwechat']];
        }
        if (isset($user['code'])) {
            return $user;
        }
        $user_id = $user["user_id"];
        return $user_id;
    }

    /**
     * 链接测试
     * @param $corpid
     * @param $secret
     * @return array
     * @author [dosy]
     */
    public function connectToken($corpid, $secret)
    {
        $time = time();
        $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}";
        $weixinConnect = getHttps($url);
        if (!$weixinConnect) {
            return ['code' => ['0x040004', 'workwechat']]; //企业微信响应异常
        }
        $connectList = json_decode($weixinConnect, true);
        if ($connectList['errcode'] != 0) {
            $code = $connectList['errcode'];
            return ['code' => ["$code", 'workwechat']];
        }
        return $connectList['access_token'];
    }

    /**
     * 判断是手机访问pc访问
     * @return bool
     * @author [dosy]
     */
    public function check_wap()
    {
        if (isset($_SERVER['HTTP_VIA'])) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_NOKIA_CONNECTION_MODE'])) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])) {
            return true;
        }
        if (strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML") > 0) {
            $br = "WML";
        } else {
            $browser = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
            if (empty($browser)) {
                return true;
            }
            $mobile_os_list = array(
                'Google Wireless Transcoder',
                'Windows CE',
                'WindowsCE',
                'Symbian',
                'Android',
                'armv6l',
                'armv5',
                'Mobile',
                'CentOS',
                'mowser',
                'AvantGo',
                'Opera Mobi',
                'J2ME/MIDP',
                'Smartphone',
                'Go.Web',
                'Palm',
                'iPAQ'
            );

            $mobile_token_list = array(
                'Profile/MIDP',
                'Configuration/CLDC-',
                '160×160',
                '176×220',
                '240×240',
                '240×320',
                '320×240',
                'UP.Browser',
                'UP.Link',
                'SymbianOS',
                'PalmOS',
                'PocketPC',
                'SonyEricsson',
                'Nokia',
                'BlackBerry',
                'Vodafone',
                'BenQ',
                'Novarra-Vision',
                'Iris',
                'NetFront',
                'HTC_',
                'Xda_',
                'SAMSUNG-SGH',
                'Wapaka',
                'DoCoMo',
                'iPhone',
                'iPod'
            );

            $found_mobile = $this->checkSubstrs($mobile_os_list, $browser) || $this->checkSubstrs($mobile_token_list, $browser);
            if ($found_mobile) {
                $br = "WML";
            } else {
                $br = "WWW";
            }
        }
        if ($br == "WML") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断手机访问， pc访问
     * @param $list
     * @param $str
     * @return bool
     * @author [dosy]
     */
    public function checkSubstrs($list, $str)
    {
        $flag = false;
        for ($i = 0; $i < count($list); $i++) {
            if (strpos($str, $list[$i]) > 0) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    /**
     * 企业微信跳转
     * @return array
     * @author [dosy]
     */
    public function wechatAuth()
    {
        if (isset($_GET["code"]) && !empty($_GET["code"])) {
            $user_id = $this->workwechatCode($_GET["code"]);
            if (isset($user_id["code"]) && is_array($user_id["code"])) {
                $code = $user_id['code'][0];
                $message = urlencode(trans("workwechat.$code"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $power = app($this->empowerService)->checkMobileEmpowerAndWapAllow($user_id);
            if (!empty($power["code"]) && !empty($power)) {
                $message = trans("workwechat.phone_allowed_access");
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $user = app($this->userService)->getLoginUserInfo($user_id);
            if (!$user) {
                return ['code' => ['0x040002', 'workwechat']];
            }
            app($this->authService)->systemParams = app($this->authService)->getSystemParam();
            $registerUserInfo = app($this->authService)->generateInitInfo($user, "zh-CN", '', false);
            if (empty($registerUserInfo) || empty($registerUserInfo['token'])) {
                return ['code' => ['0x040002', 'workwechat']];
            }
            $target_module = "";
            $state_url = "";
            $type = isset($_GET["state"]) ? $_GET["state"] : "";
            $wechat = $this->getWorkWechat();
            if (!$wechat) {
                $message = urlencode(trans("workwechat.0x040003"));
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
            $domain = $wechat->domain;
            $target = "";
            if (!$type) {
                $state_url = "";
            } else {
                if (is_numeric($type)) {
                    $target = "";
                    if ($this->check_wap()) {
                        //mobile
                        $wechat = config('eoffice.wechat');
                        $state_url = isset($wechat[$type]) ? $wechat[$type] : "";
                        if (!$state_url) {
                            $state_url = "";
                        } else {
                            $state_url = "target_url=" . $state_url;
                        }
                        //跳转到默认的模块
                        $applistAuthIds = config('eoffice.applistAuthId');
                        $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                        if ($auth_id) {
                            $state_url = "";
                            $target_module = $auth_id;
                            $target = "&module_id=" . $target_module;
                        }
                    } else {
                        //pc
                        $applistAuthIds = config('eoffice.applistAuthId');
                        $menuIds = config('eoffice.workwechat');
                        $module_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                        $menu_id = isset($menuIds[$type]) ? $menuIds[$type] : "";
                        if (!empty($module_id) && !empty($menu_id)) {
                            $state_url = "&module_id=" . $module_id . "&menu_id=" . $menu_id;
                        } else {
                            $state_url = "";
                        }
                    }
                } else {
                    $json = json_decode($type, true);
                    if ($json) {
                        //异构系统特殊处理
                        app($this->unifiedMessageService)->unifiedMessageLocation($json, $user_id);
//                        if (isset($json['module']) && is_string($json['module'])) {
//                            $heterogeneous = strpos($json['module'], 'heterogeneous_');
//                            if ($heterogeneous === 0) {
//                                $params = isset($json['params']) ? $json['params'] : '';
//                                $param['system_id'] = $params['heterogeneous_system_id'] ? $params['heterogeneous_system_id'] : '';
//                                $param['message_id'] = $params['message_id'] ? $params['message_id'] : '';
//                                $heterogeneousSystemInfo = app($this->heterogeneousSystemService)->getDomainReadMessage($param, $user_id);
//                                if (isset($heterogeneousSystemInfo['code'])) {
//                                    $message = urlencode(trans("unifiedMessage.0x000022"));
//                                    $errorUrl = integratedErrorUrl($message);
//                                    header("Location: $errorUrl");
//                                    exit;
//                                }
//                                $domain = $heterogeneousSystemInfo['app_domain'];
//                                $address = isset($heterogeneousSystemInfo['message_data']['app_address']) ? $heterogeneousSystemInfo['message_data']['app_address'] : '';
//                                $url = $domain . $address;
//                                header("Location: $url");
//                                exit();
//                            }
//                        }
                        if (isset($json['mention'])) {
                            $module = isset($json['mention']) ? $json['mention'] : '';
                            $action = isset($json['action']) ? $json['action'] : '';
                            $params = isset($json['params']) ? json_encode($json['params']) : '';
                            $state_url = "mention=" . $module . "&action=" . $action . "&params=" . $params;
                        } else {
                            readSmsByClient($user_id, $json);
                            $module = isset($json['module']) ? $json['module'] : '';
                            $action = isset($json['action']) ? $json['action'] : '';
                            $params = isset($json['params']) ? json_encode($json['params']) : '';
                            $state_url = "module=" . $module . "&action=" . $action . "&params=" . $params;
                        }
                    }
                }
            }
            if ($this->check_wap()) {
                if ($state_url) {
                    $application_url = $domain . "/eoffice10/client/mobile/#" . $state_url . "&platform=enterpriseWechat" . $target . "&token=" . $registerUserInfo['token'];
                } else {
                    $application_url = $domain . "/eoffice10/client/mobile/#" . "platform=enterpriseWechat" . $target . "&token=" . $registerUserInfo['token'];
                }
                if ($type) {
                    $application_url = $application_url . "&reminds=" . $type;
                }
            } else {
                if (!$state_url && !$target) {
                    $application_url = $domain . "/eoffice10/client/web" . "#token=" . $registerUserInfo['token'] . "&platform=enterpriseWechat";
                } else {
                    $application_url = $domain . "/eoffice10/client/web#" . $state_url . $target . "&token=" . $registerUserInfo['token'] . "&platform=enterpriseWechat";
                }

            }
            // 企业微信-客户卡片||客户群 特殊处理 类型33
            $agentid = $_GET['agentid'] ?? "";
            if (($type == 33) && !empty($agentid)) {
                $application_url = $domain . "/eoffice10/client/mobile/#&target_url=/customer/workwechat/customer-workwechat&platform=enterpriseWechat&agentid=$agentid&type=$type&token=" . $registerUserInfo['token'];
            }
            header("Location: $application_url");
            exit;
        }

    }

    /**
     * 企业微信接入
     * @param $data
     * @return bool
     * @author [dosy]
     */
    public function workwechatAccess($data)
    {
        //微信模块ID 499 企业微信107  钉钉应用 120
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (isset($auhtMenus["code"])) {
            $code = $auhtMenus["code"];
            $message = urlencode(trans("register.$code"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        } else {
            //能获取到授权信息
            if (!in_array(913, $auhtMenus)) {
                $message = trans("workwechat.Authorization_expires");
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
        }
        $type = isset($data["type"]) ? $data["type"] : "-1";
        $agentid = isset($data["agentid"]) ? $data["agentid"] : "";
        setcookie("agentid", $agentid, time() + 3600, "/");
        $reminds = isset($data["reminds"]) ? $data["reminds"] : "";
        if ($type === '0') {
            $state_url = $reminds;
        } else {
            if ($type == -1) {
                $state_url = "/home/application";
            } else {
                $state_url = $type;
                // 0 - 4 不在考虑范围
                $applistAuthIds = config('eoffice.applistAuthId');
                $auth_id = isset($applistAuthIds[$type]) ? $applistAuthIds[$type] : "";
                if ($auth_id) {
                    if (!in_array($auth_id, $auhtMenus)) {
                        $message = trans('workwechat.module_authorization_expires');
                        $errorUrl = integratedErrorUrl($message);
                        header("Location: $errorUrl");
                        exit;
                    }
                }
            }
        }

        $app = app($this->WorkWechatAppRepository)->getAppById($agentid);
        if (empty($app)) {
            return false;
        }
        $wechat = $this->getWorkWechat();
        if (!$wechat) {
            $message = urlencode(trans("workwechat.0x040003"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }
        $corpid = $wechat->corpid;
        $domain = $wechat->domain;
        $secret = $app->secret;

        if (!($corpid && $domain && $secret)) {
            $message = urlencode(trans("workwechat.0x040003"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        }

        $tempUrl = $domain . "/eoffice10/server/public/api/workwechat-auth?agentid=$agentid";
        $redirect_uri = urlencode($tempUrl);

        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $corpid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_base&state=" . $state_url . "#wechat_redirect";
        //dd($url);
        header("Location: $url");
        exit;
    }

    /**
     * 获取企业微信JsApiTicket
     * @return array
     * @author [dosy]
     */
    public function getJsApiTicket()
    {
        $data = app($this->WechatTicketRepository)->getTickt();
        if (!$data) {
            $expire_time = 0;
        } else {
            $expire_time = $data->expire_time;
        }
        if (isset($_COOKIE["agentid"])) {
            $agentid = $_COOKIE["agentid"];
        } else {
            $agentid = app($this->WorkWechatRepository)->getWorkWechat()->sms_agent;
        }
        if (abs(time() - $expire_time) > 7000) {
            $accessToken = $this->getAccessToken($agentid);
            // 如果是企业号用以下 URL 获取 ticket
            $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $wechatTemp = getHttps($url);
            $wechatData = json_decode($wechatTemp, true);
            if (isset($wechatData['errcode']) && $wechatData['errcode'] != 0) {
                $code = $wechatData['errcode'];
                return ['code' => ["$code", 'workwechat']];
            }
            $ticket = $wechatData["ticket"];
            if ($ticket) {
                $wechat["jsapi_ticket"] = $ticket;
                $wechat["expire_time"] = time();
                app($this->WechatTicketRepository)->truncateWechat();
                app($this->WechatTicketRepository)->insertData($wechat);
            } else {
                return ['code' => ["-1", 'workwechat']];
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }
        return $ticket;
    }

    /**
     * 获取SignPackage
     * @param null $data
     * @return array
     * @author [dosy]
     */
    public function workwechatSignPackage($data = null)
    {
        $url = isset($data["url"]) && $data["url"] ? $data["url"] : "";

        if (!$url) {
            return ['code' => ["0x040005", 'workwechat']];
        }

        $url = urldecode($url);

        $jsapiTicket = $this->getJsApiTicket();

        if (isset($jsapiTicket['code']) && $jsapiTicket['code'] != 0) {
            return $jsapiTicket;
        }
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);
        $wechat = $this->getWorkWechat();
        $signPackage = array(
            "appId" => $wechat->corpid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string,
        );
        return $signPackage;
    }

    /**
     * 企业微信应用权限注入获取票据
     * @param null $data
     * @return array
     * @author [dosy]
     */
    public function getSignatureAndConfig($data = null)
    {
        $url = isset($data["url"]) && $data["url"] ? $data["url"] : "";
        $agentid = isset($data["agentid"]) && $data["agentid"] ? $data["agentid"] : "";
        if (!$url || !$agentid) {
            return ['code' => ["param_error", 'workwechat']];
        }
        // $url = urldecode($url);
        $data = app($this->workWechatAppTicketRepository)->getTickt($agentid);
        if (!$data) {
            $acquire_time = 0;
        } else {
            $acquire_time = $data->acquire_time;
        }
        if (abs(time() - $acquire_time) > 7000) {
            $accessToken = $this->getAccessToken($agentid);
            //获取 ticket
            $api = "https://qyapi.weixin.qq.com/cgi-bin/ticket/get?access_token=$accessToken&type=agent_config";
            $wechatTemp = getHttps($api);
            $wechatData = json_decode($wechatTemp, true);
            if (isset($wechatData['errcode']) && $wechatData['errcode'] != 0) {
                $code = $wechatData['errcode'];
                return ['code' => ["$code", 'workwechat']];
            }
            $ticket = $wechatData["ticket"];
            if ($ticket) {
                $wechat["ticket"] = $ticket;
                $wechat["acquire_time"] = time();
                if ($data) {
                    app($this->workWechatAppTicketRepository)->updateData($wechat, ['agentid' => $agentid]);
                } else {
                    $wechat["agentid"] = $agentid;
                    app($this->workWechatAppTicketRepository)->insertData($wechat);
                }
            } else {
                return ['code' => ["-1", 'workwechat']];
            }
        } else {
            $ticket = $data->ticket;
        }
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $wechat = $this->getWorkWechat();
        $signPackage = array(
            "corpid" => $wechat->corpid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string,
        );
        return $signPackage;

    }

    /**
     * 获取随机字符串
     * @param int $length
     * @return string
     * @author [dosy]
     */
    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取企业微信应用
     * @param $param
     * @return array
     * @author [dosy]
     */
    public function wechatAppGet($param)
    {
        $param['agent_type'] = 1;
        $app = app($this->WorkWechatAppRepository)->wechatAppGet($param);
        $total = app($this->WorkWechatAppRepository)->wechatAppCount($param);
        if (!empty($app) && isset($param['main_app']) && !empty($param['main_app'])) {
            $app = $app->toArray();
            foreach ($app as $key => $v) {
                if ($v['agentid'] == $param['main_app']) {
                    unset($app[$key]);
                    $app = array_merge($app);
                    $total -= 1;
                }
            }
        }
        return ['total' => $total, 'list' => $app];

    }

    /**
     * 企业微信应用删除
     * @param $id
     * @return mixed
     * @author [dosy]
     */
    public function wechatAppDelete($id)
    {
        return app($this->WorkWechatAppRepository)->wechatAppDelete($id);

    }

    /**
     * 推送消息
     * @param $option
     * @return bool
     * @author [dosy]
     */
    public function pushMessage($option)
    {
        $wechat = $this->getWorkWechat();
        if (empty($wechat)) {
            return false;
        }
        if ($wechat->is_push != 1) {
            return false;
        }
        if (count($option["toids"]) > 100) {
            $userIds = array_chunk($option["toids"], 100);
            foreach ($userIds as $user_id) {
                $option['toids'] = $user_id;
                $this->connectMessage($option, $wechat);
            }
        } else {
            $this->connectMessage($option, $wechat);
        }
    }

    /**
     * 企业微信-发送应用消息
     * @param $option
     * @return \App\Utils\type|bool
     * @author [dosy]
     */
    public function pushChatMessage($option)
    {
        $wechat = $this->getWorkWechat();
        $imIsPush = isset($wechat->im_is_push) ? $wechat->im_is_push : 0;
        if ($imIsPush == 1) {
            if (!isset($wechat->sms_agent) || empty($wechat->sms_agent) || !isset($wechat->domain) || empty($wechat->domain)) {
                return false;
            }
            $agentid = $wechat->sms_agent;
            $domain = $wechat->domain;
            if (isset($option) && !empty($option['toUser'])) {
                $access_token = $this->getAccessToken($agentid);
                if (!is_string($access_token)) {
                    return false;
                }
                $userIds = explode(',', $option['toUser']);
                $newToUserIds = $this->checkWorkWechatUser($userIds, $access_token);
                if (!$newToUserIds) {
                    return false;
                }
                /*                $toUserIds = app($this->WorkWechatUserRepository)->getWorkWechatUserIdByIds($userIds);
                                //检查
                                if (empty($toUserIds)) {
                                    return false;
                                }
                                //获取企业微信后台真实部门成员
                                $api = "https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token=$access_token&department_id=1&fetch_child=1";
                                $resData = getHttps($api);
                                $resData = json_decode($resData, true);
                                if (!isset($resData['errcode']) || $resData['errcode'] != 0 || !isset($resData['userlist']) || empty($resData['userlist'])) {
                                    return false;
                                }
                                $workWeChatUserList = [];
                                foreach ($resData['userlist'] as $key => $user) {
                                    $workWeChatUserList[] = $user['userid'];
                                }
                                $newToUserIds = array_intersect($toUserIds, $workWeChatUserList);
                                if (empty($newToUserIds)) {
                                    return false;
                                }*/
                $newToUser = implode('|', $newToUserIds);
                //发送应用消息
                //http://bpdwxeoffice.weaver.cn/eoffice10/server/public/api/workwechat-access?agentid=1000002&type=31
                // $url    = $domain . "/eoffice10/server/public/api/workwechat-access?agentid=$agentid&type=18";
                if (isset($option['urlParams']) && !empty($option['urlParams'])) {
                    //@消息特殊处理
                    $api = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=$access_token";
                    $urlParam = $option['urlParams'];
                    $url = $domain . "/eoffice10/server/public/api/workwechat-access?agentid=$agentid&type=$urlParam";
                    //$url    = "http://www.baidu.com";
                    $msgTemp = array(
                        'touser' => $newToUser,
                        'toparty' => "",
                        'totag' => "",
                        'msgtype' => 'textcard',
                        'agentid' => $agentid,
                        'textcard' => array(
                            'title' => '待处理通知',
                            'description' => "通知内容：" . html_entity_decode($option['content']) . "\n通知人：" . $option['from'] . "\n通知时间：" . date('Y-m-d H:i', time()), //内容
                            'url' => $url,
                            'btntxt' => '查看详情'
                        ),
                        'enable_id_trans' => 0
                    );
                    $msg = urldecode(json_encode($msgTemp));
                    return getHttps($api, $msg);
                } else {
                    $api = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=$access_token";
                    $msgTemp = array(
                        'touser' => $newToUser,
                        'toparty' => "",
                        'totag' => "",
                        'msgtype' => 'text',
                        'agentid' => $agentid,
                        'text' => array(
                            'content' => "来自OA系统即时通讯\n通知内容：" . html_entity_decode($option['content']) . "\n通知人：" . $option['from'] . "\n通知时间：" . date('Y-m-d H:i',
                                    time()) . "\n请登录OA系统查看详情", //内容
                        ),
                        'enable_id_trans' => 0
                    );
                    $msg = urldecode(json_encode($msgTemp));
                    return getHttps($api, $msg);
                }

            }
        }
    }

    /**
     * 检查企业微信后台存在的用户
     * @param $userIds
     * @param $access_token
     * @return array|bool
     * @author [dosy]
     */
    public function checkWorkWechatUser($userIds, $access_token)
    {
        $toUserIds = app($this->WorkWechatUserRepository)->getWorkWechatUserIdByIds($userIds);
        //检查
        if (empty($toUserIds)) {
            return false;
        }
        //获取主应用的TOKEN,获取用户信息
//        $wechat = $this->getWorkWechat();
//        $agentid = $wechat->sms_agent;
//        $access_token = $this->getAccessToken($agentid);
        $access_token = $this->getToken();
        if (!is_string($access_token)) {
            return false;
        }
        //获取企业微信后台真实部门成员
        $api = "https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token=$access_token&department_id=1&fetch_child=1";
        $resData = getHttps($api);
        $resData = json_decode($resData, true);
        if (!isset($resData['errcode']) || $resData['errcode'] != 0 || !isset($resData['userlist']) || empty($resData['userlist'])) {
            return false;
        }
        $workWeChatUserList = [];
        foreach ($resData['userlist'] as $key => $user) {
            $workWeChatUserList[] = $user['userid'];
        }
        $newToUserIds = array_intersect($toUserIds, $workWeChatUserList);
        if (empty($newToUserIds)) {
            return false;
        }
        return $newToUserIds;
    }

    public function connectMessage($option, $wechat)
    {
        if (empty($option["title"])) {
            $option["title"] = "消息提醒";
        }
        $showContent = "<div class='gray'>" . date("Y-m-d", time()) . "</div><div class='normal'>" . $option["content"] . "</div>";
        $agentid = $wechat->sms_agent;
        $where = ['agent_type' => 1];
        $app = app($this->WorkWechatAppRepository)->getWorkWechatApp($where);
        if (isset($option['remindMenu']) && !empty($option['remindMenu'])) {
            $issetCheck = false;
            foreach ($app as $agent) {
                if (isset($agent->remind_menu) && in_array($option['remindMenu'], $agent->remind_menu)) {
                    $sendCheck = $this->sendAgentMessage($agent->agentid, $option, $showContent);
                    if ($sendCheck) {
                        $issetCheck = true;
                    }
                }
            }
            //消息类型没有指定发送应用，发到主应用
            if (!$issetCheck) {
                $this->sendAgentMessage($agentid, $option, $showContent);
            }
        } else {
            $this->sendAgentMessage($agentid, $option, $showContent);
        }
    }

    public function sendAgentMessage($agentid, $option, $showContent)
    {
        $access_token = $this->getAccessToken($agentid);
        if (!is_string($access_token)) {
            $error = [
                'access_token_error',
                $agentid,
                $access_token
            ];
            \Log::info($error);
            return false;
        }
        //获取企业微信后台存在的用户
        if (!is_array($option["toids"])) {
            $userIds = explode(",", $option["toids"]);
        } else {
            $userIds = $option["toids"];
        }
        $newToUserIds = $this->checkWorkWechatUser($userIds, $access_token);
        if (!$newToUserIds) {
            return false;
        }
        $pushUser = implode('|', $newToUserIds);
        $redirectUrl = $option["url"];

        //$api = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=$access_token";
        $msgTemp = array(
            'touser' => $pushUser,
            'toparty' => "",
            'totag' => "",
            'msgtype' => 'textcard',
            'agentid' => $agentid, //其他 ----- 后面根据配置重新选择 todo
            'textcard' => array(
                'title' => html_entity_decode($option["title"]), // 内容
                'description' => html_entity_decode($showContent),
                'url' => $redirectUrl, // 链接
                'btntxt' => $option['btntxt'],
            ),
        );
        $json = ['json' => $msgTemp];
        $res = $this->sendWorkWeChatAgentMessage($access_token, $json);
        if (isset($res['code'])) {
            \Log::info($res);
            return false;
        }
        return true;
        /*  try {
              $client = new Client();
              $json = ['json' => $msgTemp];
              $guzzleResponse = $client->request('POST', $api, $json);
              $status = $guzzleResponse->getStatusCode();
          } catch (\Exception $e) {
              $status = $e->getMessage();
              \Log::info($status);
          }*/

    }

    /**发送应用消息
     * @param $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function sendWorkWeChatAgentMessage($accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.sendAgentMessage');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 废弃
     */
    public function connectMessageOld($option, $wechat)
    {
        $wechat = $this->getWorkWechat();
        $content = $option["content"];
        $title = $option["title"];
        if (empty($title)) {
            $title = "消息提醒";
        }
        $btntxt = $option['btntxt'];
        //$btntxt = trans("system." . $btntxt);
        $showContent = "<div class='gray'>" . date("Y-m-d", time()) . "</div><div class='normal'>" . $option["content"] . "</div>";
        $agentid = $wechat->sms_agent;
        if (isset($option['remindMenu']) && !empty($option['remindMenu'])) {
            $type = '';
            $workWeChatRemindAgentBind = config('weChat.workWeChatRemindAgentBind');
            foreach ($workWeChatRemindAgentBind as $key => $value) {
                if (in_array($option['remindMenu'], $value)) {
                    $type = $key;
                    continue;
                }
            }
            if ($type) {
                $app = app($this->WorkWechatAppRepository)->getOneFieldInfo(['type' => $type]);
                if ($app) {
                    $agentid = $app->agentid;
                }
            }
        }
        $access_token = $this->getAccessToken($agentid);
        if (!is_string($access_token)) {
            return false;
        }
        //获取企业微信后台存在的用户
        if (!is_array($option["toids"])) {
            $userIds = explode(",", $option["toids"]);
        } else {
            $userIds = $option["toids"];
        }
        $newToUserIds = $this->checkWorkWechatUser($userIds, $access_token);
        if (!$newToUserIds) {
            return false;
        }
        $pushUser = implode('|', $newToUserIds);
        /*        $secret       = $wechat->secret;
                $corpid       = $wechat->corpid;
                $toUsers      = $option["toids"];
                $pushUser     = $this->tranferWorkWechatUser($option["toids"]);*/
        $redirectUrl = $option["url"];

        $api = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=$access_token";
        $msgTemp = array(
            'touser' => $pushUser,
            'toparty' => "",
            'totag' => "",
            'msgtype' => 'textcard',
            'agentid' => $agentid, //其他 ----- 后面根据配置重新选择 todo
            'textcard' => array(
                'title' => html_entity_decode($title), // 内容
                'description' => html_entity_decode($showContent),
                'url' => $redirectUrl, // 链接
                'btntxt' => $btntxt,
            ),
        );
        try {
            $client = new Client();
            $json = ['json' => $msgTemp];
            $guzzleResponse = $client->request('POST', $api, $json);
            $status = $guzzleResponse->getStatusCode();
        } catch (\Exception $e) {
            $status = $e->getMessage();
        }
        return $status;
//        $msg = urldecode(json_encode($msgTemp));
//        return getHttps($api, $msg);
    }

    /**
     * 拼接用户
     * @param $users
     * @return string
     * @author [dosy]
     */
    public function tranferWorkWechatUser($users)
    {
        if (!is_array($users)) {
            $users = explode(",", $users);
        }
        $str = "";
        foreach ($users as $useId) {
            $row = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($useId);
            if ($row && $row["userid"]) {
                $str .= $row["userid"] . "|";
            } else {
                $str .= $useId . "|";
            }
        }

        return trim($str, "|");
    }

    public function tranferUser($data)
    {
        $userId = isset($data['userId']) ? $data['userId'] : '';
        $row = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($userId);
        if (!$row || !isset($row["userid"])) {
            return ['code' => ['not_find_work_wechat_user', 'workwechat']];
        }
        return $row["userid"] ?? '';

    }

    /**
     * 处理图片
     * @param $data
     * @return array
     * @author [dosy]
     */
    public function workwechatMove($data)
    {
        $accessToken = $this->getAccessToken($_COOKIE["agentid"]);
        if (is_array($data["serverId"])) {
            $mediaIds = $data["serverId"];
        } else {
            $mediaIds = explode(",", trim($data["serverId"], ","));
        }

        $fileName = [];
        $fileIds = [];
        $thumbWidth = config('eoffice.thumbWidth', 100);
        $thumbHight = config('eoffice.thumbHight', 40);
        $thumbPrefix = config('eoffice.thumbPrefix', "thumb_");
        $attachmentFile = 1; //图片格式
        $attachment_base_path = getAttachmentDir();

        $temp = [];
        foreach ($mediaIds as $mediaId) {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$mediaId";

            ob_start(); //打开输出
            readfile($url); //输出图片文件
            $img = ob_get_contents(); //得到浏览器输出
            ob_end_clean(); //清除输出并关闭
            //生成附件ID
            $attachmentId = md5(time() . $mediaId . rand(1000000, 9999999));
            $newPath = app($this->attachmentService)->createCustomDir($attachmentId);
            $mediaIdName = $mediaId . ".jpg";
            $originName = $newPath . $mediaIdName;
            $size = strlen($img); //得到图片大小
            $fp2 = @fopen($originName, "a");
            fwrite($fp2, $img); //向当前目录写入图片文件，并重新命名
            fclose($fp2);

            $thumbAttachmentName = scaleImage($originName, $thumbWidth, $thumbHight, $thumbPrefix);
            //       组装数据 存入附件表
            $attachment_path = str_replace($attachment_base_path, '', $newPath);
            // $tableData       = [
            //     "attachment_id"          => $attachmentId,
            //     "attachment_name"        => $mediaId . ".jpg",
            //     "affect_attachment_name" => $mediaId . ".jpg",
            //     "thumb_attachment_name"  => $thumbAttachmentName,
            //     "attachment_size"        => $size,
            //     "attachment_type"        => "jpg",
            //     "attachment_create_user" => "",
            //     "attachment_base_path"   => $attachment_base_path,
            //     "attachment_path"        => $attachment_path,
            //     "attachment_file"        => 1,
            //     "attachment_time"        => date("Y-m-d H:i:s", time()),
            // ];
            // app($this->attachmentService)->addAttachment($tableData);
            $newFullFileName = $newPath . DIRECTORY_SEPARATOR . $mediaId . ".jpg";
            $attachmentInfo = [
                "attachment_id" => $attachmentId,
                "attachment_name" => $mediaId . ".jpg",
                "affect_attachment_name" => $mediaId . ".jpg",
                'new_full_file_name' => $newFullFileName,
                "thumb_attachment_name" => $thumbAttachmentName,
                "attachment_size" => $size,
                "attachment_type" => 'jpg',
                "attachment_create_user" => '',
                "attachment_base_path" => $attachment_base_path,
                "attachment_path" => $attachment_path,
                "attachment_mark" => 1,
                "relation_table" => '',
                "rel_table_code" => "",
            ];

            app($this->attachmentService)->handleAttachmentDataTerminal($attachmentInfo);
            //生成64code
            $path = $attachment_base_path . $attachment_path . DIRECTORY_SEPARATOR . $thumbAttachmentName;
            $temp[] = [
                "attachmentId" => $attachmentId,
                "attachmentName" => $mediaIdName,
                "attachmentThumb" => imageToBase64($path),
                // 为了兼容统一的附件接口增加attachmentType属性
                "attachmentSize" => $size,
                "attachmentType" => 'jpg',
                "attachmentMark" => 1,
            ];
        }

        return $temp;
    }

    /**
     * 配置判断
     * @return int
     * @author [dosy]
     */
    public function workwechatCheck()
    {
        $wechat = $this->getWorkWechat();

        if (!$wechat) {
            return 0;
        }

        $data["corpid"] = $wechat->corpid;
        $data["domain"] = $wechat->domain;
        $data["wechat_code"] = $wechat->wechat_code;

        if (empty($data["wechat_code"])) {
            return 0;
        }

        return 1;
    }


    /*//同步组织架构
    public function syncOrganization()
    {

        $dept = $this->syncOrganizationDept();
        if ($dept == "ok") {
            $user = $this->syncOrganizationUser();
            if ($user !== "ok") {
                return $user;
            }
        } else {
            return $dept;
        }
        $sync = $this->userListWechat();
        if ($sync == "true") {
            return "ok";
        }

    }*/


    /**
     * 获取部门用户
     * @param string $accessToken
     * @param int $departmentId
     * @param int $fetchChild 1/0：是否递归获取子部门下面的成员
     * @return array
     */
    public function getDeptUserInfo($accessToken, $departmentId, $fetchChild = 1)
    {
        $getUserListUrl = "https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token=$accessToken&department_id=$departmentId&fetch_child=$fetchChild";
        $tempData = getHttps($getUserListUrl);
        $getData = json_decode($tempData, true);
        if ($getData['errcode'] != 0) {
            $code = $getData['errcode'];
            $result = ['code' => ["$code", 'workwechat']];
            return $result;
        } else {
            return $getData['userlist'];
        }
    }

    /**
     * 获取用户微信信息
     * @param $accessToken
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function getUserInfo($accessToken, $userId)
    {
        $getUserInfoUrl = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=$accessToken&userid=$userId";
        $tempData = getHttps($getUserInfoUrl);
        $getData = json_decode($tempData, true);
        if ($getData['errcode'] != 0) {
            $code = $getData['errcode'];
            $result = ['code' => ["$code", 'workwechat']];
            return $result;
        } else {
            return $getData['userid'];
        }
    }

    /**
     * 删除用户
     * @param string $accessToken
     * @param array $userIds
     * @return array|bool
     */
    public function delUserIds($accessToken, $userIds)
    {
        $delUserIdUrl = "https://qyapi.weixin.qq.com/cgi-bin/user/batchdelete?access_token=$accessToken";
        $userIdsFields = array('useridlist' => $userIds);
        $userIdsMsg = urldecode(json_encode($userIdsFields));
        $delTempData = getHttps($delUserIdUrl, $userIdsMsg);
        $getData = json_decode($delTempData, true);
        if ($getData['errcode'] != 0) {
            $code = $getData['errcode'];
            $result = ['code' => ["$code", 'workwechat']];
            return $result;
        } else {
            return true;
        }
    }


    /**
     * 上传附件
     * @param $file
     * @param $type
     * @return array|bool|false|string
     * @author [dosy]
     */
    public function uploadTempFile($file, $type)
    {
        $wechat = $this->getWorkWechat();
        if (!isset($wechat->sms_agent)) {
            return ['code' => ["0x040007", 'workwechat']];
        }
        $access_token = $this->getAccessToken($wechat->sms_agent);
        $fields = array('media' => new \CURLFile($file));
        $url = "https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token=$access_token&type=$type";

        try {
            if (function_exists('curl_init')) {

                $ch = curl_init();
                if (version_compare(phpversion(), '5.6') >= 0 && version_compare(phpversion(), '7.0') < 0) {
                    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                }
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSLVERSION, 1);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);

                if ($output === false) {
                    $res = array(
                        "errcode" => "0x033003",
                        "errmsg" => curl_error($ch),
                    );
                    $output = json_encode($res);
                }

                curl_close($ch);
            } else {
                $res = array(
                    "errcode" => "0x033003",
                    "errmsg" => trans('workwechat.extension_unopened'),
                );
                $output = json_encode($res);
            }
        } catch (Exception $exc) {

            $res = array(
                "errcode" => "0x033003",
                "errmsg" => $exc->getTraceAsString(),
            );

            $output = json_encode($res);
        }
        return $output;
    }

    /**
     * 获取通讯录token
     * @return array|bool
     * @author [dosy]
     */
    public function getToken()
    {
        $workWeChatTokenDuration = config('weChat.workWeChatTokenDuration');
        $wechat = $this->getWorkWechat();
        if (!isset($wechat->corpid) || !isset($wechat->secret)) {
            return false;
        }
        $corpid = $wechat->corpid;
        $secret = $wechat->sync_secret;
        if (empty($corpid) || empty($secret)) {
            return false;
        }
        $accessTokenTime = $wechat->sync_token_time;
        $syncAccessToken = $wechat->sync_access_token;
        if (abs(time() - $accessTokenTime) > $workWeChatTokenDuration) {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}";
            $wechatConnect = getHttps($url);
            $connectList = json_decode($wechatConnect, true);
            if (isset($connectList['errcode']) && $connectList['errcode'] != 0) {
                $code = $connectList['errcode'];
                return ['code' => ["$code", 'workwechat']];
            }
            $data['sync_access_token'] = $connectList['access_token'];
            $data['sync_token_time'] = time();
            app($this->WorkWechatRepository)->updateData($data, ['corpid' => $corpid]);
            return $data['sync_access_token'];
        }

        return $syncAccessToken;
    }

    /**
     * 清空企业微信
     * @return bool
     * @author [dosy]
     */
    public function workwechatTruncate()
    {
        $qyData = app($this->WorkWechatRepository)->truncateWechat();
        $param = [
            'sync_direction' => 1,
            'sync_type' => 1,
            'sync_leave' => 0,
            'sync_ini_user_status' => 1,
        ];
        app($this->WorkWechatRepository)->insertData($param);
        return true;
    }

    /**
     * 高德地图
     * @param $data
     * @return mixed
     * @author [dosy]
     */
    public function geocodeAttendance($data)
    {
        return app($this->workWechat)->geocodeAttendance($data);
    }

    /**
     * 企业微信用户列表
     * @return array|bool|string
     * @author [dosy]
     */
    public function userListWechat()
    {
        $access_token = $this->getToken();
        if (!$access_token) {
            return ['code' => ["0x040007", 'workwechat']];
        }
        if (isset($access_token['code'])) {
            return $access_token;
        }
        $api = "https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token=$access_token&department_id=1&fetch_child=1&status=0";
        $userJson = getHttps($api);
        $userObj = json_decode($userJson, true);
        if ($userObj['errcode'] != 0) {
            return false;
        }
        app($this->WorkWechatUserRepository)->truncateWechat();
        if (!empty($userObj['userlist'])) {
            foreach ($userObj['userlist'] as $k => $v) {
                $phone = $v['mobile'];
                $user = $v['userid'];
                $data = array();
                // if (strpos($v['userid'], "WV") === false && $v['userid'] != "admin") {
                if ($v['userid'] != "admin") {
                    $where = ["user_info.phone_number" => [$phone]];
                    $userInfo = app($this->userRepository)->checkUserByWhere($where);
                    if (!empty($userInfo)) {
                        $user_id = $userInfo['user_id'];
                        $data = [
                            "oa_id" => $user_id,
                            "userid" => $v['userid'],
                            "mobile" => $v['mobile'],
                        ];
                    }
                } else {
                    $data = [
                        "oa_id" => $v['userid'],
                        "userid" => $v['userid'],
                        "mobile" => $v['mobile'],
                    ];
                }
                if (!empty($data)) {
                    $Data = app($this->WorkWechatUserRepository)->insertData($data);
                }

            }
        }
        return "true";
    }

    /**
     * 按新的执行顺序同步通讯录
     * @param $loginUser
     * @return array|bool|false|mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function syncOrganization($loginUser)
    {
        $loginUserInfo = $loginUser;
        $user = $loginUser['user_id'];
        //获取企业微信配置信息
        $wechat = $this->getWorkWechat();
        //同步方向
        $syncDirection = 1;
        if (isset($wechat->sync_direction)) {
            $syncDirection = $wechat->sync_direction;
        }
        //预设增量同步
        $sync_type = 1;
        if (isset($wechat->sync_type)) {
            $sync_type = $wechat->sync_type;
        }
        //是否同步离职人员，预设不同步
        $sync_leave = 0;
        if (isset($wechat->sync_leave)) {
            $sync_leave = $wechat->sync_leave;
        }
        //同步结果预设参数
        $params['sync_start_time'] = date("Y-m-d H:i:s", time());
        $params['operator'] = $user;
        $params['sync_leave'] = $sync_leave;
        if ($syncDirection == 1) {  //OA同步到企业微信
            //同步方式
            if ($sync_type == 1) {
                $incrSyncRes = $this->incrSync($params);
                if (isset($incrSyncRes['code'])) {
                    return $incrSyncRes;
                }
            } else {
                $accessToken = $this->getToken();
                if (!$accessToken) {
                    return ['code' => ["0x040007", 'workwechat']];
                }
                if (isset($accessToken['code'])) {
                    return $accessToken;
                }
                //验证企业微信创建人是否存在将要被删除用户列表，存在剔除
                if (!isset($wechat->creator_id)) {
                    return ['code' => ["0x040007", 'workwechat']];
                }
                $coverSyncRes = $this->coverSync($params, $accessToken, $wechat->creator_id);
                if (isset($coverSyncRes['code'])) {
                    return $coverSyncRes;
                }
            }
            //获取新的关联关系
            $sync = $this->userListWechat();
            if ($sync == "true") {
                return "ok";
            }
        } elseif ($syncDirection == 2) { //企业微信同步到OA
            //第一步 验证，必须是admin 用户操作
            $specialUser = 'admin';
            if ($loginUserInfo['user_id'] != $specialUser) {
                return ['code' => ['sync_only_admin', 'workwechat']];
                //return ['仅仅admin用户能执行该操作'];
            }
            //第二步 获取表单默认值
            //预设企业微信未定义性别的默认性别
            $syncIniUserSex = 0;
            if (isset($wechat->sync_ini_user_sex)) {
                $syncIniUserSex = $wechat->sync_ini_user_sex;
            }
            $params['sync_ini_user_sex'] = $syncIniUserSex;
            //预设默认密码
            $syncIniUserPassword = '';
            if (isset($wechat->sync_ini_user_password)) {
                $syncIniUserPassword = $wechat->sync_ini_user_password;
            }
            $params['sync_ini_user_password'] = $syncIniUserPassword;
            //预设企业微信用户状态
            $syncIniUserStatus = 1; //允许在职
            if (isset($wechat->sync_ini_user_status) && !empty($wechat->sync_ini_user_status)) {
                $syncIniUserStatus = $wechat->sync_ini_user_status;
            }
            $params['sync_ini_user_status'] = $syncIniUserStatus;
            //预设企同步人事档案
            $syncIniIsAutohrms = null; //默认不同步人事档案
            if (isset($wechat->is_autohrms)) {
                $syncIniIsAutohrms = $wechat->is_autohrms;
            }
            $params['is_autohrms'] = $syncIniIsAutohrms;
            //预设企业微信考勤排班
            $syncIniAttendanceScheduling = '';
//            if (isset($wechat->sync_ini_attendance_scheduling)) {
//                $syncIniAttendanceScheduling = $wechat->sync_ini_attendance_scheduling;
//            }
            $params['sync_ini_attendance_scheduling'] = $syncIniAttendanceScheduling;
            //预设手机访问
            $syncIniWapAllow = 1;
            if (isset($wechat->sync_ini_wap_allow)) {
                $syncIniWapAllow = $wechat->sync_ini_wap_allow;
            }
            $params['sync_ini_wap_allow'] = $syncIniWapAllow;
            //预设开启角色同步
            $isOpenSyncRole = 1;
            if (isset($wechat->is_open_sync_role)) {
                $isOpenSyncRole = $wechat->is_open_sync_role;
            }
            $params['is_open_sync_role'] = $isOpenSyncRole;
//            //预设默认角色   新增用户多角色是role_id_init: "85,62,86"这种   预设无职务人员默认角色
            if (isset($wechat->sync_ini_role) && !empty($wechat->sync_ini_role)) {
                $defaultRole = $wechat->sync_ini_role;
            } else {
                return ['code' => ['sync_ini_role_error', 'workwechat']];
            }
            $params['sync_ini_role'] = $defaultRole;
            //离职部门id
            $syncLeaveDeptId = '';
            if (isset($wechat->sync_leave_dept_id) && $wechat->sync_leave_dept_id != 0 && $wechat->sync_ini_dept_id != 1) {
                $syncLeaveDeptId = $wechat->sync_leave_dept_id;
            }
            // dd($syncLeaveDeptId);
            $params['sync_leave_dept_id'] = $syncLeaveDeptId;
            //默认部门 -- 主要处理2块，其一是企业微信公司下的用户部门，其二是企业微信离职人员的默认部门
            $syncIniDeptId = 2;
            if (isset($wechat->sync_ini_dept_id) && $wechat->sync_ini_dept_id != 0 && $wechat->sync_ini_dept_id != 1) {
                $syncIniDeptId = $wechat->sync_ini_dept_id;
            }
            $params['sync_ini_dept_id'] = $syncIniDeptId;
            if ($params['sync_ini_dept_id'] == $params['sync_leave_dept_id']) {
                return ['code' => ['sync_leave_dept_and_ini_dept_error', 'workwechat']];
            }
            //企业微信创建人
            if (isset($wechat->creator_id) && !empty($wechat->creator_id)) {
                $params['creator_id'] = $wechat->creator_id;
            } else {
                return ['code' => ['sync_work_wechat_creator_error', 'workwechat']];
            }
            //获取token
            $accessToken = $this->getToken();
            if (!$accessToken) {
                return ['code' => ["0x040007", 'workwechat']];
            }
            if (isset($accessToken['code'])) {
                return $accessToken;
            }
            // $client = new Client();
            //获取企业默认部门信息
            $defaultDept = $this->workWeChatDepartmentList($accessToken, $params['sync_ini_dept_id']);
            if (isset($defaultDept['code'])) {
                return ['code' => ['sync_ini_dept_error', 'workwechat']];
            }
            //获取企业微信创建人信息
            $creatorData = $this->workWeChatUserInfo($accessToken, $params['creator_id']);
            if (isset($creatorData['code'])) {
                return ['code' => ['sync_work_wechat_creator_error', 'workwechat']];
            }
            //企业微信穿建人不在离职部门中
            if ($creatorData['department'][0] == $params['sync_leave_dept_id']) {
                return ['code' => ['sync_work_wechat_creator_in_dept_error', 'workwechat']];
            }
            $params['sync_result'] = 2; //同步中
            $params['sync_type'] = 2;   // 企业微信同步到OA
            $params['error_content'] = ['code' => ['sync_log_error_empty', 'workwechat']];
            $result = $this->syncLog($params, $syncDirection);
            if (isset($result['id'])) {
                //准备同步
                Queue::push(new syncWorkWeChatJob($params, $loginUserInfo, $creatorData, $accessToken, 'work_wechat_to_oa', [], $result['id']));
            } else {
                return ['code' => ['sync_work_wechat_log_error', 'workwechat']];
            }

            // $incrSyncRes = $this->incrSyncToOA($params, $loginUserInfo, $specialUser,$accessToken,$result['id']);
            return "start";
        }
    }

    /***************************企业微信同步到OA***********************************************/

    /**
     * 用户、部门数据备份
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function syncDataBackup($userId)
    {
        if ($userId == 'admin') {
            app($this->departmentService)->syncDeptDataBackup();
            app($this->userService)->userBackupForWorkWechat();
            $time = date("Y-m-d H:i:s");
            $data = [
                'sync_date_backup_time' => $time
            ];
            $result = app($this->WorkWechatRepository)->updateData($data, ['id' => 1]);
            return $time;
        } else {
            return ['code' => ['sync_only_admin', 'workwechat']];
        }

    }

    /**
     * 用户、部门数据还原
     * @param $userId
     * @return array|bool
     * @author [dosy]
     */
    public function syncDataReduction($userId)
    {
        if ($userId == 'admin') {
            app($this->departmentService)->syncDeptDataReduction();
            app($this->userService)->userSyncFailForWorkWechat();
            return true;
        } else {
            return ['code' => ['sync_only_admin', 'workwechat']];
        }
        //数据还原，会关闭自动同步
//        $data = [
//            'auto_sync'=> 0
//        ];
//        $result = app($this->WorkWechatRepository)->updateData($data,['id'=>1]);
//        return $result;
    }

    /**
     * 企业微信同步到OA
     * @param $params
     * @param array $loginUserInfo
     * @param $creatorData
     * @param $accessToken
     * @param $logId
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function incrSyncToOA($params, array $loginUserInfo, $creatorData, $accessToken, $logId)
    {
        //  $this->syncDataBackup();
        //$client = new Client();
        /*//第三步 检查admin用户是否在企业微信中获得用户信息，后面判断且仅属于更新用户，所在部门不是被删除部门
        $workWeChatAdmin = $this->workWeChatUserInfo($accessToken, $specialUser);
        if (isset($workWeChatAdmin['code'])) {
            if ($workWeChatAdmin['code'][0] == 60111) { //60111 查不到该账号，微信错误60111
                return ['code' => ['must_add_admin_in_work_wechat', 'workwechat']];
            } else {
                return $workWeChatAdmin;
            }
        }

        //第四步 检查admin 用户的用户部门不在离职部门中
        if ($workWeChatAdmin['department'][0] == $params['sync_leave_dept_id']) {
            return ['code' => ['admin_in_work_wechat_leave', 'workwechat']];
        }*/
        //获取OA数据
        //OA存在的部门id
        $oaDepartmentIds = array_column(app($this->departmentService)->getAllDeptId(), 'dept_id');
        //OA存在的部门列表
        //$oaDepartment = app($this->departmentService)->listDept([]);
        //OA存在的用户
        $oaAllUserData = app($this->userService)->userSystemList();
        if (isset($oaAllUserData['code'])) {
            $params['sync_result'] = 0;
            $params['sync_type'] = 2;
            $params['error_content'] = ['code' => ['get_oa_user_list_error', 'workwechat']];
            //$params['error_content'] = $departmentData['code'];
            $params['error_code'] = $oaAllUserData['code'];
            $params['log_id'] = $logId;
            $this->syncLog($params);
            return $oaAllUserData;
        }
        //获取企业微信部门列表
        $departmentData = $this->workWeChatDepartmentList($accessToken, 1);
        if (isset($departmentData['code'])) {
            $params['sync_result'] = 0;
            $params['sync_type'] = 2;
            $params['error_content'] = ['code' => ['get_work_wechat_department_list_error', 'workwechat']];
            //$params['error_content'] = $departmentData['code'];
            $params['error_code'] = $departmentData['code'];
            $params['log_id'] = $logId;
            $this->syncLog($params);
            return $params['error_content'];
        }
        //获取企业微信成员列表
        $userData = $this->workWeChatUserList($accessToken);
        if (isset($userData['code'])) {
            $params['sync_result'] = 0;
            $params['sync_type'] = 2;
            $params['error_content'] = ['code' => ['get_work_wechat_user_list_error', 'workwechat']];
            $params['error_code'] = $userData;
            $params['log_id'] = $logId;
            $this->syncLog($params);
            return $params['error_content'];
        }
        //预处理企业微信的部门列表数据
        // 对比2个部门列表返回需要新增，更新，删除的部门列表
        $departmentNewData = $this->preProcessingDepartmentData($departmentData['department'], $oaDepartmentIds, $params['sync_leave_dept_id']);
        //预处理企业微信的用户列表数据
        $userNewData = $this->preProcessingUserData($userData['userlist'], $params, $oaAllUserData, $departmentNewData['leave_dept_children_ids']);
        //同步新增、更新OA部门
        // $syncAddAndUpdateDeptWorkWeChatToOa = $this->syncAddAndUpdateDeptWorkWeChatToOa($departmentNewData, $loginUserInfo['user_id'], $oaDepartmentIds, $oaDepartment['list']);
        //部门同步
        $syncDeptWorkWeChatToOa = app($this->departmentService)->addDepartmentForWorkWechat($departmentNewData['dept_id_key_info'], 'admin');
        Redis::del('user:user_accounts');
        if (isset($syncDeptWorkWeChatToOa['code'])) {
            $params['sync_result'] = 0;
            $params['sync_type'] = 2;
            $params['error_content'] = ['code' => ['sync_dept_error', 'workwechat']];
            $params['error_code'] = $syncDeptWorkWeChatToOa;
            $params['log_id'] = $logId;
            $this->syncLog($params);
            return $syncDeptWorkWeChatToOa;
        }
        //处理admin用户
        $adminInfo = app($this->userService)->getUserAllData($loginUserInfo['user_id'], ['user_id' => $loginUserInfo['user_id']], [])->toArray();
        $this->adminUpdate($loginUserInfo, $adminInfo, $creatorData, $params);
        //同步用户
        //$syncUserWorkWeChatToOa = app($this->userService)->addUserForWorkWechat($userNewData['all_user_info_data'], 'admin');
        //同步部门删除 delete_dept_ids
        //$syncDeleteDeptWorkWeChatToOa = $this->syncDeleteDeptWorkWeChatToOa($departmentNewData['delete_dept_ids'], $loginUserInfo['user_id']);

        //同步删除、更新、新增OA用户
        //Queue::push(new syncWorkWeChatJob($userNewData, $params, $loginUserInfo, $params['sync_ini_dept_id']));
        $syncAddAndUpdateUserWorkWeChatToOa = $this->syncAddAndUpdateUserWorkWeChatToOa($userNewData, $adminInfo['user_accounts'], $params, $loginUserInfo);
        //部门负责人更新
        $syncUpdateDeptLeader = $this->syncUpdateDeptLeader($userNewData['leader'], $params['creator_id'], $params['sync_leave_dept_id'], $departmentNewData['dept_id_key_info'],
            $loginUserInfo['user_id']);
        //关联用户
        $this->workWeChatWithOa($syncAddAndUpdateUserWorkWeChatToOa);
        //删除部门copy的数据
        app($this->departmentService)->addDepartmentSuccessForWorkWechat();
        //日志
        $params['sync_result'] = 1;
        $params['sync_type'] = 2;
        $params['log_id'] = $logId;
        $params['error_content'] = ['code' => ['sync_log_error_empty', 'workwechat']];
        $this->syncLog($params);
        Redis::del('user:user_accounts');
        return true;
    }

    /**
     * 管理员特殊处理
     * @param $loginUserInfo
     * @param $adminInfo
     * @param $creatorData
     * @param $params
     * @return mixed
     * @author [dosy]
     */
    public function adminUpdate($loginUserInfo, $adminInfo, $creatorData, $params)
    {
        $tempUserArray['user_name'] = $creatorData['name'];
        // 用户性别* ---企业微信性别0表示未定义，1表示男性，2表示女性；  OA 0女1男对应的未定义表示为男  ['userInfo']
        if ($params['sync_ini_user_sex'] === 1) {
            $tempUserArray['sex'] = ($creatorData['gender'] === 0 || $creatorData['gender'] === 1) ? '1' : '0';
        } else {
            $tempUserArray['sex'] = ($creatorData['gender'] === 0 || $creatorData['gender'] === 2) ? '0' : '1';
        }
        // 部门* ---企业微信是多部门--有主部门概念，OA是单部门 企业微信非主部门不再OA内显示 ['userSystemInfo']
        if (isset($creatorData['main_department'])) {
            $tempUserArray['dept_id'] = $creatorData['main_department'] == 1 ? $params['sync_ini_dept_id'] : $creatorData['main_department'];
        } else {
            $tempUserArray['dept_id'] = $params['sync_ini_dept_id'];
        }
        //用户手机号 ---可以不填，但是填了必须唯一 ['userInfo']
        $tempUserArray['phone_number'] = $creatorData['mobile'] ?? '';
        // email ['userInfo']
        $tempUserArray ['email'] = $creatorData['email'] ?? "";
        //对应企业微信座机 ['userInfo']
        $tempUserArray['dept_phone_number'] = $creatorData['telephone'] ?? '';
        //地址 ['userInfo']
        $tempUserArray['home_address'] = $creatorData['address'] ?? '';

        $tempUserArray['user_id'] = $loginUserInfo['user_id'];

        $roleIniId = [];
        if (isset($adminInfo['user_has_many_role'])) {
            foreach ($adminInfo['user_has_many_role'] as $roleInfo) {
                $roleIniId[] = $roleInfo['role_id'];
            }
        }
        $roleIniIdStr = implode(',', $roleIniId);
        $userSuperior = [];
        if (isset($adminInfo['user_has_many_superior'])) {
            foreach ($adminInfo['user_has_many_superior'] as $superior) {
                $userSuperior[] = $superior['user_id'];
            }
        }
        $userSuperiorStr = implode(',', $userSuperior);
        $userSubordinate = [];
        if (isset($adminInfo['user_has_many_subordinate'])) {
            foreach ($adminInfo['user_has_many_subordinate'] as $subordinate) {
                $userSubordinate[] = $subordinate['user_id'];
            }
        }
        $userSubordinateStr = implode(',', $userSubordinate);
        $update = [
            'birthday' => $adminInfo['user_has_one_info']['birthday'],
            'dept_id' => $adminInfo['user_has_one_system_info']['dept_id'],
            'dept_phone_number' => $adminInfo['user_has_one_info']['dept_phone_number'],
            'duty_type' => $adminInfo['user_has_one_system_info']['duty_type'],
            'email' => $adminInfo['user_has_one_info']['email'],
            'faxes' => $adminInfo['user_has_one_info']['faxes'],
            'home_address' => $adminInfo['user_has_one_info']['home_address'],
            'home_phone_number' => $adminInfo['user_has_one_info']['home_phone_number'],
            'home_zip_code' => $adminInfo['user_has_one_info']['home_zip_code'],
            'is_autohrms' => $adminInfo['user_has_one_system_info']['is_autohrms'],
            'last_login_time' => $adminInfo['user_has_one_system_info']['last_login_time'],
            'last_pass_time' => $adminInfo['user_has_one_system_info']['last_pass_time'],
            'list_number' => $adminInfo['list_number'],
            'login_usbkey' => $adminInfo['user_has_one_system_info']['login_usbkey'],
            'max_role_no' => $adminInfo['user_has_one_system_info']['max_role_no'],
            'menu_hide' => $adminInfo['user_has_one_info']['menu_hide'],
            'msn' => $adminInfo['user_has_one_info']['msn'],
            'notes' => $adminInfo['user_has_one_info']['notes'],
            'oicq_no' => $adminInfo['user_has_one_info']['msn'],
            'phone_number' => $adminInfo['user_has_one_info']['phone_number'],
            'post_dept' => $adminInfo['user_has_one_system_info']['post_dept'],
            'post_priv' => $adminInfo['user_has_one_system_info']['post_priv'],
            'role_id_init' => $roleIniIdStr,
            'sex' => $adminInfo['user_has_one_info']['sex'],
            'shortcut' => $adminInfo['user_has_one_system_info']['shortcut'],
            'show_page_after_login' => $adminInfo['user_has_one_info']['show_page_after_login'],
            'signature_picture' => $adminInfo['user_has_one_info']['signature_picture'],
            'sms_login' => $adminInfo['user_has_one_system_info']['sms_login'],
            'sms_on' => $adminInfo['user_has_one_info']['sms_on'],
            'theme' => $adminInfo['user_has_one_info']['theme'],
            'usbkey_pin' => $adminInfo['user_has_one_system_info']['usbkey_pin'],
            'user_accounts' => $adminInfo['user_accounts'],
            'user_area' => $adminInfo['user_area'],
            'user_city' => $adminInfo['user_city'],
            'user_id' => $adminInfo['user_id'],
            'user_job_category' => $adminInfo['user_job_category'],
            'user_job_number' => $adminInfo['user_job_number'],
            'user_name' => $adminInfo['user_name'],
            'user_position' => $adminInfo['user_position'],
            'user_status' => $adminInfo['user_has_one_system_info']['user_status'],
            'user_subordinate_id' => $userSubordinateStr,
            'user_superior_id' => $userSuperiorStr,
            'user_workplace' => $adminInfo['user_workplace'],
            'wap_allow' => $adminInfo['user_has_one_system_info']['wap_allow'],
            'weixin' => $adminInfo['user_has_one_info']['weixin'],
        ];
        $updateData = array_merge($update, $tempUserArray);
        $res1 = app($this->userService)->userSystemEdit($updateData, ['user_id' => $loginUserInfo['user_id']]);
        return $res1;
    }

    /**
     * 企业微信反向同步建立关联关系
     * @param $syncAddAndUpdateUserWorkWeChatToOa
     * @author [dosy]
     */
    public function workWeChatWithOa($syncAddAndUpdateUserWorkWeChatToOa)
    {
        app($this->WorkWechatUserRepository)->truncateWechat();
        // oa账号 =>[oa_user_id,微信userid,oa账号,手机号]
        foreach ($syncAddAndUpdateUserWorkWeChatToOa as $oaAccount => $value) {
            $data[] = [
                "oa_id" => $value[0],
                "oa_user_account" => $oaAccount,
                "userid" => $value[1],
                "mobile" => $value[3],
            ];
        }
        if (!empty($data)) {
            app($this->WorkWechatUserRepository)->insertMultipleData($data);
        }
    }

    /**
     * 部门负责人更新
     * @param $leader
     * @param $creatorId
     * @param $leaveDept
     * @param $deptListInfo
     * @param $userId
     * @author [dosy]
     */
    public function syncUpdateDeptLeader($leader, $creatorId, $leaveDept, $deptListInfo, $userId)
    {
        //$leader[$user['department'][$keys]][] = [$user['userid'],$tempUserArray['user_accounts']] ;   //部门id =>[[企业微信userid,指向OA账号]]
        foreach ($leader as $deptId => $accounts) {
            if ($deptId == $leaveDept || $deptId == 1) { //离职部门跳过、公司跳过
                continue;
            }
            $temUserIds = [];
            foreach ($accounts as $key => $account) {
                if ($account[0] == $creatorId) {
                    $userOaUser = $userId;
                } else {
                    $userOaUser = app($this->userService)->getUserToAccount(urldecode($account[1]));
                }
                if ($userOaUser) {
                    $temUserIds[] = $userOaUser['user_id'];
                }
            }
            $deptListInfo[$deptId]['director'] = $temUserIds;
            $res = app($this->departmentService)->updateDepartment($deptListInfo[$deptId], $deptId, $userId);
            if (isset($res['code'])) {
                $errorData = [
                    'deptId' => $deptId,
                    'leader' => $leader,
                ];
                $error = ['error' => $res, 'error_data' => $errorData];
                $this->abnormalDataLog($error);
            }
        }
    }

    /**
     * 同步部门删除
     * @param $deleteDeptData
     * @param $loginUserId
     * @return array|bool
     * @author [dosy]
     */
    public function syncDeleteDeptWorkWeChatToOa($deleteDeptData, $loginUserId)
    {
        $tempDeleteDeptData = $deleteDeptData;
        /************删除********************/  //部门删除要等人员同步结束后再去执行,被删除的部门下可能有人员
        foreach ($deleteDeptData as $key => $deptId) {
            $hasSonDept = app($this->departmentService)->checkSonDept($deptId);
            //有子部门直接跳过
            if ($hasSonDept) {
                continue;
            }
            $hasDeptUser = app($this->departmentService)->checkDeptUsers($deptId);
            if ($hasDeptUser) {
                return ['code' => ['sync_delete_dept_have_user_error', 'workwechat']];
            }
            $res = app($this->departmentService)->delete($deptId, $loginUserId);
            if (isset($res['code'])) {
                $this->abnormalDataLog($res);
                return $res;
            } else {
                unset($deleteDeptData[array_search($deptId, $deleteDeptData)]);
            }

        }

        if (!empty($deleteDeptData)) {
            $diff = strcmp(serialize($tempDeleteDeptData), serialize($deleteDeptData));
            //$diff = array_diff($tempDeleteDeptData,$deleteDeptData);
            if ($diff === 0) {
                $this->abnormalDataLog($tempDeleteDeptData);
                //有错
                return ['code' => ['sync_delete_dept_data_error', 'workwechat']];
            } else {
                return $this->syncDeleteDeptWorkWeChatToOa($deleteDeptData, $loginUserId);
            }
        } else {
            return true;
        }
    }

    /**
     * 同步删除、更新、新增OA用户
     * @param $userNewData
     * @param $adminAccount
     * @param $params
     * @param $loginUserInfo
     * @return array
     * @author [dosy]
     */
    public function syncAddAndUpdateUserWorkWeChatToOa($userNewData, $adminAccount, $params, $loginUserInfo)
    {
        $oaWithWorkWechat = [];
        //判断是否同步离职用户
        //$params['sync_leave'] =1;
        if ($params['sync_leave'] == 0) { //不同步
            //delete_user_account_arr 删除
            foreach ($userNewData['delete_user_account_arr'] as $key => $deleteUser) {
                app($this->userService)->userSystemDelete($deleteUser['user_id'], $loginUserInfo['user_id']);
            }
            //leave_user_in_oa 删除
            foreach ($userNewData['leave_user_in_oa'] as $userAccount => $leaveUser) {
                app($this->userService)->userSystemDelete($userNewData['oa_user_list'][$userAccount]['user_id'], $loginUserInfo['user_id']);
            }

            // update_user_account_arr 更新
            foreach ($userNewData['update_user_account_arr'] as $updateUserAccount => $updateUser) {
                //企业微信创建者不被更新
                if ($updateUser[0] == $params['creator_id']) {
                    // oa账号 =>[oa_user_id,微信userid,oa账号,手机号]
                    $oaWithWorkWechat[$adminAccount] = [
                        $loginUserInfo['user_id'],
                        $updateUser[0],
                        $adminAccount,
                        $updateUser[1]['phone_number']
                    ];
                    continue;
                } else {
                    //不允许非企业微信创建者用户姓名和企业微信创建者相同
                    if ($updateUserAccount == $params['creator_id']) {
                        $errorData = [
                            'update_user' => $updateUser,
                        ];
                        $error = ['error' => 'sync_work_wechat_creator_name_error', 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                        continue;
                    }
                }
                $updateUserOaUser = app($this->userService)->getUserToAccount(urldecode($updateUserAccount));
                if ($updateUserOaUser) {
                    if ($updateUserOaUser['user_id'] == $loginUserInfo['user_id']) {
                        continue;
                    }
                    $updateUser[1]['user_id'] = $updateUserOaUser['user_id'];
                    $res1 = app($this->userService)->userSystemEdit($updateUser[1], $loginUserInfo);
                    if (isset($res1['code'])) {
                        $errorData = [
                            'update_user' => $updateUser,
                            'update_user_oa_user' => $updateUserOaUser,
                        ];
                        $error = ['error' => $res1, 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                    } else {
                        // oa账号 =>[oa_user_id,微信userid,oa账号,手机号]
                        $oaWithWorkWechat[$updateUserAccount] = [
                            $updateUserOaUser['user_id'],
                            $updateUser[0],
                            $updateUserAccount,
                            $updateUser[1]['phone_number']
                        ];
                    }
                }
            }
            //add_user_account_arr 添加
            foreach ($userNewData['add_user_account_arr'] as $addUserAccount => $addUser) {
                //创建人对应admin 不添加
                if ($addUser[0] == $params['creator_id']) {
                    // oa账号 =>[oa_user_id,微信userid,oa账号,手机号]
                    $oaWithWorkWechat[$adminAccount] = [
                        $loginUserInfo['user_id'],
                        $addUser[0],
                        $adminAccount,
                        $addUser[1]['phone_number']
                    ];
                    continue;
                } else {
                    //不允许非企业微信创建者用户姓名和企业微信创建者相同
                    if ($addUser == $params['creator_id']) {
                        $errorData = [
                            'update_user' => $addUser,
                        ];
                        $error = ['error' => 'sync_work_wechat_creator_name_error', 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                        continue;
                    }
                }
                $oaUserInfo = app($this->userService)->userSystemCreate($addUser[1], $loginUserInfo);
                if (isset($oaUserInfo['code'])) {
                    $errorData = [
                        'add_user_info' => $addUser,
                    ];
                    $error = ['error' => $oaUserInfo, 'error_data' => $errorData];
                    $this->abnormalDataLog($error);
                    continue;
                } else {
                    $oaWithWorkWechat[$addUserAccount] = [
                        $oaUserInfo['user_id'],
                        $addUser[0],
                        $addUserAccount,
                        $addUser[1]['phone_number']
                    ];  // [$updateUserOaUser['user_id'],$updateUser[0],$updateUserAccount] ;  // oa账号 =>[oa_user_id,微信userid,oa账号]
                }
            }
        } elseif ($params['sync_leave'] == 1) { //同步
            //dd($userNewData['leave_user_not_oa']);
            //获取离职用户列表
            $allLeaveUser = app($this->userService)->getLeaveOfficeUser([], $loginUserInfo)->toArray();
            //删除OA现有的所有离职用户
            foreach ($allLeaveUser as $leaveUser) {
                if ($leaveUser['user_id'] == $loginUserInfo['user_id']) {
                    continue;
                }
                app($this->userService)->userSystemDelete($leaveUser['user_id'], $loginUserInfo['user_id']);
            }
            //leave_user_in_oa 转离职
            foreach ($userNewData['leave_user_in_oa'] as $userAccount => $newLeaveUser) {
                $leaveUserOaUser = app($this->userService)->getUserToAccount(urldecode($userAccount));
                if ($leaveUserOaUser['user_id'] == $loginUserInfo['user_id']) {
                    continue;
                }
                if ($leaveUserOaUser) {
                    $newLeaveUser['user_id'] = $leaveUserOaUser['user_id'];
                    $newLeaveUser['user_status'] = 2;
                    $newLeaveUser['phone_number'] = '';
                    $res3 = app($this->userService)->userSystemEdit($newLeaveUser);
                    if (isset($res3['code'])) {
                        $errorData = [
                            'new_leave_user' => $newLeaveUser,
                            'leave_user_oa_user' => $leaveUserOaUser,
                        ];
                        $error = ['error' => $res3, 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                    }
                }
            }
            //delete_user_account_arr 删除
            foreach ($userNewData['delete_user_account_arr'] as $deleteUser) {
                if ($deleteUser['user_id'] == $loginUserInfo['user_id']) {
                    continue;
                }
                app($this->userService)->userSystemDelete($deleteUser['user_id'], $loginUserInfo['user_id']);
            }
            //        $data = [
//            'userList' => $userArray,
//            'all_user_info_data' => $allUserInfoData,
//            'oa_user_list' => $oaUserList, // ['账号' => [从OA内拿出的用户数据，包含user_id]
//            'leader' => $leader,
//            'add_user_account_arr' => $addUser, // ['账号' => ['userid',用户数据]]
//            'update_user_account_arr' => $updateUser, // ['账号' => ['userid',用户数据]]
//            'oa_update_user_account' => $oaUpdateAccounts, // ['账号']
//            'delete_user_account_arr' => $oaUser, // ['账号' => [从OA内拿出的用户数据，包含user_id]
//            'leave_user_in_oa' => $leaveUserInOa, // ['账号' => [用户数据]
//            'leave_user_not_oa' => $leaveUserNotOa, // ['账号' => [用户数据]
//        ];
            // update_user_account_arr 更新
            // $oaWithWorkWechat = $this->cycleUpdateUser($userNewData['update_user_account_arr'],$userNewData['oa_update_user_account'],$loginUserInfo,[]);
            foreach ($userNewData['update_user_account_arr'] as $updateUserAccount => $updateUser) {
                //企业微信创建者不被更新
                if ($updateUser[0] == $params['creator_id']) {
                    // oa账号 =>[oa_user_id,微信userid,oa账号,手机号]
                    $oaWithWorkWechat[$adminAccount] = [
                        $loginUserInfo['user_id'],
                        $updateUser[0],
                        $adminAccount,
                        $updateUser[1]['phone_number']
                    ];
                    continue;
                } else {
                    //不允许非企业微信创建者用户姓名和企业微信创建者相同
                    if ($updateUserAccount == $params['creator_id']) {
                        $errorData = [
                            'update_user' => $updateUser,
                        ];
                        $error = ['error' => 'sync_work_wechat_creator_name_error', 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                        continue;
                    }
                }
                $updateUserOaUser = app($this->userService)->getUserToAccount(urldecode($updateUserAccount));
                if ($updateUserOaUser) {
                    if ($updateUserOaUser['user_id'] == $loginUserInfo['user_id']) {
                        continue;
                    }

                    $updateUser[1]['user_id'] = $updateUserOaUser['user_id'];
                    $res4 = app($this->userService)->userSystemEdit($updateUser[1], $loginUserInfo);
                    if (isset($res4['code'])) {
                        $errorData = [
                            'update_user' => $updateUser,
                            'update_user_oa_user' => $updateUserOaUser,
                        ];
                        $error = ['error' => $res4, 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                    } else {
                        // oa账号 =>[oa_user_id,微信userid,oa账号,手机号]
                        $oaWithWorkWechat[$updateUserAccount] = [
                            $updateUserOaUser['user_id'],
                            $updateUser[0],
                            $updateUserAccount,
                            $updateUser[1]['phone_number']
                        ];
                    }
                }
            }
            //leave_user_not_oa 添加转离职
            foreach ($userNewData['leave_user_not_oa'] as $toLeaveUser) {
                //重新调整部门
                $toLeaveUser['dept_id'] = $params['sync_ini_dept_id'];
                $toLeaveUser['user_accounts'] = $toLeaveUser['user_accounts'] . '_tempUser';
                $addLeaveUser = app($this->userService)->userSystemCreate($toLeaveUser, $loginUserInfo);
                if ($addLeaveUser) {
                    if ($addLeaveUser->user_id == $loginUserInfo['user_id']) {
                        continue;
                    }
                    $addLeaveUserInfo = app($this->userService)->getUserAllData($addLeaveUser->user_id, $loginUserInfo, ['editMode' => true])->toArray();
                    $addLeaveUserInfo['user_status'] = 2;
                    $addLeaveUserInfo['phone_number'] = '';
                    $res5 = app($this->userService)->userSystemEdit($addLeaveUserInfo);
                    if (isset($res5['code'])) {
                        $errorData = [
                            'to_leave_user_info' => $addLeaveUserInfo,
                            'leave_user_not_oa' => $toLeaveUser,
                        ];
                        $error = ['error' => $res5, 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                    }
                }
            }
            //add_user_account_arr 添加0x005026
            foreach ($userNewData['add_user_account_arr'] as $addUserAccount => $addUser) {
                //创建人对应admin 不添加
                if ($addUser[0] == $params['creator_id']) {
                    // oa账号 =>[oa_user_id,微信userid,oa账号,手机号]
                    $oaWithWorkWechat[$adminAccount] = [
                        $loginUserInfo['user_id'],
                        $addUser[0],
                        $adminAccount,
                        $addUser[1]['phone_number']
                    ];
                    continue;
                } else {
                    //不允许非企业微信创建者用户姓名和企业微信创建者相同
                    if ($addUser == $params['creator_id']) {
                        $errorData = [
                            'update_user' => $addUser,
                        ];
                        $error = ['error' => 'sync_work_wechat_creator_name_error', 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                        continue;
                    }
                }
                $oaUserInfo = app($this->userService)->userSystemCreate($addUser[1], $loginUserInfo);
                if (isset($oaUserInfo['code'])) {
                    $errorData = [
                        'add_user_info' => $addUser,
                    ];
                    $error = ['error' => $oaUserInfo, 'error_data' => $errorData];
                    $this->abnormalDataLog($error);
                    continue;
                } else {
                    $oaWithWorkWechat[$addUserAccount] = [
                        $oaUserInfo['user_id'],
                        $addUser[0],
                        $addUserAccount,
                        $addUser[1]['phone_number']
                    ];  // [$updateUserOaUser['user_id'],$updateUser[0],$updateUserAccount] ;  // oa账号 =>[oa_user_id,微信userid,oa账号]
                }
            }
        }
        return $oaWithWorkWechat;
    }


    public function cycleUpdateUser($userData, $oaUserAccounts, $loginUserInfo, $oaWithWorkWechat)
    {
        $tem = $oaUserAccounts;
        foreach ($userData as $updateUserAccount => $updateUser) {
            $updateUserOaUser = app($this->userService)->getUserToAccount(urldecode($updateUserAccount));
            if ($updateUserOaUser['user_id'] == 'admin') {
                unset($userData[$updateUserAccount]);
                continue;
            }
            if ($updateUserOaUser) {
                $updateUser[1]['user_id'] = $updateUserOaUser['user_id'];
                $res4 = app($this->userService)->userSystemEdit($updateUser[1], $loginUserInfo);
                if (isset($res4['code'])) {
                    $errorData = [
                        'update_user' => $updateUser,
                        'update_user_oa_user' => $updateUserOaUser,
                    ];
                    $error = ['error' => $res4, 'error_data' => $errorData];
                    $this->abnormalDataLog($error);
                } else {
                    $oaUserAccounts[] = $updateUser[1]['user_accounts'];
                    $oaWithWorkWechat[$updateUserAccount] = $updateUser[0];
                }
            } else {
                $errorData = [
                    'update_user_oa_account' => $updateUserAccount,
                    'update_user' => $updateUser,
                ];
                $error = ['error' => '该账号在OA内不存在', 'error_data' => $errorData];
                $this->abnormalDataLog($error);
            }
            unset($userData[$updateUserAccount]);
            unset($oaUserAccounts[array_search($updateUserAccount, $oaUserAccounts)]);
        }
        $diff = array_diff($tem, $oaUserAccounts);
        if (empty($oaUserAccounts) || empty($diff)) {
            return $oaWithWorkWechat;
        } else {
            return $this->cycleUpdateUser($userData, $oaUserAccounts, $loginUserInfo, $oaWithWorkWechat);
        }
    }


    /**
     * 同步新增、更新OA部门
     * @param $departmentNewData
     * @param $userId
     * @param $oaDepartmentIds
     * @return array
     * @author [dosy]
     */
    public function syncAddAndUpdateDeptWorkWeChatToOa($departmentNewData, $userId, $oaDepartmentIds, $oaDepartment)
    {
        /***********同步部门名称锁定******/
        //获取OA所有部门
//        foreach (){
//
//        }
        /***********添加部门*********************/
        //$isError = false;
        //必须先添加完部门后才能更新部门，之后才能删除部门可能存在父级不一样
        foreach ($departmentNewData['dept_id_key_info'] as $deptId => $department) {
            //部门ID在OA内，属于要更新部门
            if (in_array($deptId, $oaDepartmentIds)) {
                continue;
            }
            //获取其所有的父级id 注：数据特点一定是 按键值越大层级越深
            $arrParentIdStr = $department['arr_parent_id'];
            $arrParentIdArr = explode(',', $arrParentIdStr);
            //优先循环添加其父级
            foreach ($arrParentIdArr as $key => $relativeDeptId) {
                if ($relativeDeptId == 0 || in_array($relativeDeptId, $oaDepartmentIds)) { //针对第二个循环体，部门id是0 ，说明是公司，直接跳过||部门id属于OA部门，直接跳过
                    continue;
                } else {
                    $parentAddInfo = app($this->departmentService)->addDepartment($departmentNewData['dept_id_key_info'][$relativeDeptId], $userId);
                    if (isset($parentAddInfo['code'])) {
                        $errorData = [
                            'dept_id' => $relativeDeptId,
                            'arr_parent_id_arr' => $arrParentIdArr,
                            'all_dept_data' => $departmentNewData,
                            'oa_department_ids' => $oaDepartmentIds,
                        ];
                        $error = ['error' => $parentAddInfo, 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                        $return = $parentAddInfo . '部门id:' . $relativeDeptId;
                        return ['code' => ['sync_oa_add_dept_error', 'workwechat'], 'dynamic' => $return];
//                        $isError = true;
//                        break;
                    }
                    $oaDepartmentIds[] = $parentAddInfo['dept_id'];
                }
            }
//            if ($isError) {
//                $isError = true;
//                break;
//            }
            $addInfo = app($this->departmentService)->addDepartment($department, $userId);
            if (isset($addInfo['code'])) {
                $errorData = [
                    'dept_id' => $deptId,
                    'arr_parent_id_arr' => $arrParentIdArr,
                    'all_dept_data' => $departmentNewData,
                    'oa_department_ids' => $oaDepartmentIds,
                ];
                $error = ['error' => $addInfo, 'error_data' => $errorData];
                $this->abnormalDataLog($error);
                $errorCode = error_response('sync_oa_add_dept_error', 'workwechat', ''); ////return ['code' => ['sync_oa_add_dept_error', 'workwechat']];
                $errorResponse = error_response($addInfo['code'][0], $addInfo['code'][1], '');
                $return = $errorCode['errors'][0]['message'] . '：' . $errorResponse['errors'][0]['message'] . '，受错误影响的企业微信部门id:' . $deptId;
                return ['code' => ['sync_oa_add_dept_error', 'workwechat'], 'dynamic' => $return];

//                $isError = true;
//                break;
            }
        }
//        if ($isError) {
//
//        }
        /************更新******************/
        foreach ($departmentNewData['dept_id_key_info'] as $deptId => $department) {
            if (in_array($deptId, $departmentNewData['update_part_ids'])) {
                $updateRes = app($this->departmentService)->updateDepartment($department, $department['dept_id'], $userId);
                if (isset($updateRes['code'])) {
                    $errorData = [
                        'dept_id' => $deptId,
                        'update_part_ids' => $departmentNewData['update_part_ids'],
                        'department' => $department,
                    ];
                    $error = ['error' => $updateRes, 'error_data' => $errorData];
                    $this->abnormalDataLog($error);
                    continue;
                }
            }
        }
        /************删除********************/  //部门删除要等人员同步结束后再去执行,被删除的部门下可能有人员

    }

    /**
     * 预处理用户数据
     * @param $userData 企业微信用户列表
     * @param $params
     * @param $oaAllUserData  OA系统的用户列表
     * @param $leaveDeptChildrenIds
     * @return array
     * @author [dosy]
     */
    public function preProcessingUserData($userData, $params, $oaAllUserData, $leaveDeptChildrenIds)
    {
        //注：同步过程中企业微信账号和OA用户名匹配 这是基本匹配规则，账号不符合规则的情况下，取用用户姓名
        $addUser = [];
        $updateUser = [];
        $oaUser = [];
        $oaUserList = [];
        $oaUserAccounts = [];
        $leaveUserInOa = [];
        $leaveUserNotOa = [];
        //最后整理的用户数据
        $allUserInfoData = [];
        //所有属于更新的账号
        $oaUpdateAccounts = [];
        foreach ($oaAllUserData['list'] as $user) {
            $oaUser[$user['user_accounts']] = $user;
            $oaUserList[$user['user_accounts']] = $user;
            $oaUserAccounts[] = $user['user_accounts'];
        }
        //考勤排班类型
        //$oaSystemAttendanceScheduling = app($this->attendanceSettingService)->getAllShiftMap('shift_name');
        //Oa系统用户角色
        $roleList = [];
        //获取新增用户工号规则
        $rules = app($this->userService)->getUserJobNumberRule();
        //oa系统角色列表
        if ($params['is_open_sync_role']) {  // 因为后续是职务对接角色，防止成员自定义修改导致OA内越权
            $oaSystemRoleList = app($this->roleService)->getRoleList();
            if (isset($oaSystemRoleList['list'])) {
                foreach ($oaSystemRoleList['list'] as $value) {
                    $roleList[$value['role_id']] = $value['role_name'];
                }
            }
        }
        //部门领导集合
        $leader = [];
        //扩展字段
        $workWeChatUserField = config('weChat.workWeChatUserField');
        //预处理用户数据
        $userArray = [];
        foreach ($userData as $key => $user) {
            //预处理用户扩展信息
            $extAttrUserInfo = [];
            if (isset($user['extattr'])) {
                $extAttrUserInfo = $this->extAttrUserInfo($user['extattr']);
            }
            $tempUserArray = [];
            /****oa必填****/
            //用户真实姓名*  ['userBaseData']
            if (isset($user['name'])) {
                $tempUserArray['user_name'] = $user['name'];
            } else {
                $error = ['error' => 'get_work_wechat_user_name_error', 'data' => $user];
                $this->abnormalDataLog($error);
                continue;
            }
            //用户账号*  ['userBaseData']18667936251
            if (isset($user['userid'])) {
                $validation = app($this->userService)->validateUserAccountsValidation($user['userid']);
                // 不合法
                if (!$validation) {
                    if ($user['name'] == $params['creator_id']) {
                        $error = ['error' => 'sync_work_wechat_creator_name_error', 'data' => $user];
                        $this->abnormalDataLog($error);
                        continue;
                    }
                    $validation = app($this->userService)->validateUserAccountsValidation($user['name']);
                    if ($validation) {
                        $tempUserArray['user_accounts'] = $user['name'];
                    } else {
                        $error = ['error' => 'sync_work_wechat_creator_name_not_oa_account_error', 'data' => $user];
                        $this->abnormalDataLog($error);
                        continue;
                    }
                } else {
                    $tempUserArray['user_accounts'] = $user['userid'];
                }
            } else {
                $error = ['error' => 'get_work_wechat_user_account_error', 'data' => $user];
                $this->abnormalDataLog($error);
                continue;
            }
            // 用户性别* ---企业微信性别0表示未定义，1表示男性，2表示女性；  OA 0女1男对应的未定义表示为男  ['userInfo']
            if ($params['sync_ini_user_sex'] === 1) {
                $tempUserArray['sex'] = ($user['gender'] == 0 || $user['gender'] == 1) ? '1' : '0';
            } else {
                $tempUserArray['sex'] = ($user['gender'] == 0 || $user['gender'] == 2) ? '0' : '1';
            }
            // 手机访问* --- 可通过扩展属性来改变 ['userSystemInfo']
            $tempUserArray['wap_allow'] = $params['sync_ini_wap_allow'];
            $wapAllowField = $workWeChatUserField['wapAllow'];
            if (isset($extAttrUserInfo[$wapAllowField])) {
                if ($extAttrUserInfo[$wapAllowField] == $workWeChatUserField['wapAllowYes']) {
                    $tempUserArray['wap_allow'] = 1;
                } elseif ($extAttrUserInfo[$wapAllowField] == $workWeChatUserField['wapAllowNo']) {
                    $tempUserArray['wap_allow'] = 0;
                }
            }
            // 用户状态* --- 可通过扩展属性来改变----这里要写具体的状态id值 ['userSystemInfo']---不可通过扩展属性来改变
            $tempUserArray['user_status'] = $params['sync_ini_user_status'];
//            if (isset($extAttrUserInfo[$workWeChatUserField['userStatus']])) {
//                //判断设置的用户状态ID是否在用户状态列表范围内
//                $oaSystemUserStatus = app($this->userService)->judgeUserStatusIdInStatusList($extAttrUserInfo[$workWeChatUserField['userStatus']]);
//                if ($oaSystemUserStatus === 1) {
//                    $tempUserArray['user_status'] = $extAttrUserInfo[$workWeChatUserField['userStatus']];
//                }
//            }
            // 部门* ---企业微信是多部门--有主部门概念，OA是单部门 企业微信非主部门不再OA内显示 ['userSystemInfo']
            if (isset($user['main_department'])) {
                $tempUserArray['dept_id'] = $user['main_department'] == 1 ? $params['sync_ini_dept_id'] : $user['main_department'];
            } else {
                $error = ['error' => 'get_work_wechat_user_main_department_error', 'data' => $user];
                $this->abnormalDataLog($error);
                continue;
            }
            // 考勤排班类型* --- 可通过扩展属性来改变---这里要写具体的考勤排班的id值 ['userBaseData'] --- 不可通过扩展状态来改变
            $tempUserArray['attendance_scheduling'] = $params['sync_ini_attendance_scheduling'];

            // 角色* --- 对应企业微信的职务 --- 直接写角色名称 --- 要特殊处理1.未定义职务 2.定义了要去匹配 ['userSystemInfo']
            $paramRoleIdStr = $params['sync_ini_role'];
            if ($params['is_open_sync_role']) {
                if (isset($user['position'])) {
                    $position = explode(',', $user['position']);
                    $paramRoleId = [];
                    foreach ($position as $positionV) {
                        if (in_array($positionV, $roleList)) {
                            $paramRoleId[] = array_search($positionV, $roleList); //如果有重复的value输出第一个相匹配的role_id
                        }
                    }
                    if (!empty($paramRoleId)) {
                        $paramRoleId = array_unique($paramRoleId);
                        $paramRoleIdStr = implode(',', $paramRoleId);
                    }
                }
            }
            $tempUserArray['role_id_init'] = $paramRoleIdStr;
            /****oa非必填****/
            //用户手机号 ---可以不填，但是填了必须唯一 ['userInfo']
            $tempUserArray['phone_number'] = $user['mobile'] ?? '';
            // 序号 ['userBaseData']
            if (isset($user['order']) && isset($user['order'][0])) {
                $tempUserArray['list_number'] = $user['order'][0];
            } else {
                $tempUserArray['list_number'] = '';
            }
            // email ['userInfo']
            $tempUserArray ['email'] = $user['email'] ?? "";
            //对应企业微信座机 ['userInfo']
            $tempUserArray['dept_phone_number'] = $user['telephone'] ?? '';
            //地址 ['userInfo']
            $tempUserArray['home_address'] = $user['address'] ?? '';
            /****扩展属性extattr****/
            //区域  --下拉框选项值 - ['userBaseData']
            $userArea = $this->extAttrToOa('userArea', $extAttrUserInfo, $workWeChatUserField);
            if ($userArea) {
                $tempUserArray['user_area'] = $userArea;
            } else {
                $tempUserArray['user_area'] = '';
            }
            // 职位 --下拉框选项值 涉及自定义没办法很好的取得选择框内的值，暂统一为默认空  ['userBaseData']
            $tempUserArray['user_position'] = 0;
            //城市 --下拉框选项值 ['userBaseData']
            $tempUserArray['user_city'] = 0;
            //职场 --下拉框选项值  ['userBaseData']
            $tempUserArray['user_workplace'] = 0;
            //岗位类别 --下拉框选项值  ['userBaseData']
            $tempUserArray['user_job_category'] = 0;
            //生日 ['userInfo']
            if ($birthday = $this->extAttrToOa('birthday', $extAttrUserInfo, $workWeChatUserField)) {
                $tempUserArray['birthday'] = date(strtotime($birthday));
            }
            //QQ ['userInfo']
            $tempUserArray['oicq_no'] = $this->extAttrToOa('oicqNo', $extAttrUserInfo, $workWeChatUserField);
            //微信号 ['userInfo']
            $tempUserArray['weixin'] = $this->extAttrToOa('weixin', $extAttrUserInfo, $workWeChatUserField);
            //单位传真 ['userInfo']
            $tempUserArray['faxes'] = $this->extAttrToOa('faxes', $extAttrUserInfo, $workWeChatUserField);
            //家庭邮编 ['userInfo']
            $tempUserArray['home_zip_code'] = $this->extAttrToOa('homeZipCode', $extAttrUserInfo, $workWeChatUserField);
            //家庭电话 ['userInfo']
            $tempUserArray['home_phone_number'] = $this->extAttrToOa('homePhoneNumber', $extAttrUserInfo, $workWeChatUserField);
            //备注['userInfo']
            $tempUserArray['notes'] = $this->extAttrToOa('notes', $extAttrUserInfo, $workWeChatUserField);
            if (!$rules) {
                //工号 ['userBaseData']
                $tempUserArray['user_job_number'] = $this->extAttrToOa('userJobNumber', $extAttrUserInfo, $workWeChatUserField);
            }
            //密码 --- 设置同步初始化密码 ['userBaseData']
            $tempUserArray['user_password'] = $params['sync_ini_user_password'] ?? '';
            //同步人事档案 -- 设置同步人事档案默认值 ['userSystemInfo']
            $tempUserArray['is_autohrms'] = $params['is_autohrms'] ?? null;
            // 上级 ['userSuperiorInfo'] --- 因为在同步过程中，用户可能还没有，没办法利用账号直接同步过去，后期如果需要，可以在补充方法，当用户同步完成后，根据用户账号获取用户user_id来更新用户上下级，暂时先不写
            $tempUserArray['user_superior_id'] = '';
            // 下级 ['userSubordinateInfo']  ---同上级
            $tempUserArray['user_subordinate_id'] = '';
            // 管理范围 ['userSystemInfo']  --- 默认0 本部门
            $tempUserArray['post_priv'] = '0';
            //部门负责人
            if (isset($user['is_leader_in_dept'])) {
                foreach ($user['is_leader_in_dept'] as $keys => $value) {
                    if ($value == 1) {
                        // $leader[$user['department'][$keys]][] = $user['userid'];
                        $leader[$user['department'][$keys]][] = [$user['userid'], $tempUserArray['user_accounts']];   //部门id =>[[企业微信userid,指向OA账号]]
                    }
                }
            }
            //企业微信特殊的字段 is_leader_in_dept、avatar、thumb_avatar、alias、status、external_profile、external_position、hide_mobile、english_name、open_userid、main_department
            //所有的企业微信人员整理后的数据
            $userArray[$user['userid']] = $tempUserArray;
            $allUserInfoData[$user['userid']] = $tempUserArray;
            // 离职部门人员
            if ($tempUserArray['dept_id'] == $params['sync_leave_dept_id'] || in_array($tempUserArray['dept_id'], $leaveDeptChildrenIds)) {
                $tempUserArray['dept_id'] = $params['sync_ini_dept_id'];
                $allUserInfoData[$user['userid']]['dept_id'] = $params['sync_ini_dept_id'];
                //判断离职部门的人员在不在OA未离职员工内
                if (in_array($tempUserArray['user_accounts'], $oaUserAccounts)) {
                    $leaveUserInOa[$tempUserArray['user_accounts']] = $tempUserArray;
                    unset($oaUser[$tempUserArray['user_accounts']]);
                } else {
                    $leaveUserNotOa[$tempUserArray['user_accounts']] = $tempUserArray;
                }
            } else {
                if (in_array($tempUserArray['user_accounts'], $oaUserAccounts)) {
                    $updateUser[$tempUserArray['user_accounts']] = [$user['userid'], $tempUserArray]; //账号在OA 内的人员
                    $oaUpdateAccounts[] = $tempUserArray['user_accounts'];
                    unset($oaUser[$tempUserArray['user_accounts']]);
                } else {
                    $addUser[$tempUserArray['user_accounts']] = [$user['userid'], $tempUserArray]; //账号不在OA 内的人员
                }
            }
        }
        $data = [
            'userList' => $userArray,
            'all_user_info_data' => $allUserInfoData,
            'oa_user_list' => $oaUserList, // ['账号' => [从OA内拿出的用户数据，包含user_id]
            'leader' => $leader,
            'add_user_account_arr' => $addUser, // ['账号' => ['userid',用户数据]]
            'update_user_account_arr' => $updateUser, // ['账号' => ['userid',用户数据]]
            'oa_update_user_account' => $oaUpdateAccounts, // ['账号']
            'delete_user_account_arr' => $oaUser, // ['账号' => [从OA内拿出的用户数据，包含user_id]
            'leave_user_in_oa' => $leaveUserInOa, // ['账号' => [用户数据]
            'leave_user_not_oa' => $leaveUserNotOa, // ['账号' => [用户数据]
        ];
        return $data;
    }

    /**
     * 非正常数据及企业微信报错记录
     * @param $data
     * @author [dosy]
     */
    public function abnormalDataLog($data)
    {
        $requestData = date('Y-m-d H:i:s') . '***:' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\r\n";
        $fileName = storage_path() . '/logs/sync_wechat_to_oa.log';
        file_put_contents($fileName, $requestData, FILE_APPEND);
    }

    /**
     * 获取企业微信用户的扩展信息
     * @param $field
     * @param $extAttrUserInfo
     * @param $workWeChatUserField
     * @return string
     * @author [dosy]
     */
    public function extAttrToOa($field, $extAttrUserInfo, $workWeChatUserField)
    {
        if (isset($workWeChatUserField[$field])) {
            $fieldName = $workWeChatUserField[$field];
            if (isset($extAttrUserInfo[$fieldName])) {
                return $extAttrUserInfo[$fieldName];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * 调整企业微信用户的扩展信息
     * @param $extAttr
     * @return array
     * @author [dosy]
     */
    public function extAttrUserInfo($extAttr)
    {
        $extArray = [];
        if (isset($extAttr['attrs'])) {
            foreach ($extAttr['attrs'] as $key => $value) {
                if ($value['type'] === 0) {
                    $extArray[$value['name']] = $value['text']['value'];
                }
            }
        }
        return $extArray;
    }

    /**
     * 预处理企业微信获取的部门信息
     * @param $departmentData
     * @param $oaDepartmentIds
     * @param $leaveDeptId
     * @return array
     * @author [dosy]
     */
    public function preProcessingDepartmentData($departmentData, $oaDepartmentIds, $leaveDeptId)
    {
        $workWeChatPartIds = [];
        $partArray = [];
        $leaveDept = [];
        $departmentArray = [];
        $leaveDeptChildrenIds = []; //离职部门的子部门
        $partMap = []; //部门ID=>父级
        //$workWeChatLeaveDept = config('weChat.workWeChatLeaveDept');
        foreach ($departmentData as $part) {
            $part['id'] = (int)$part['id'];
            if ($part['id'] == 1) {
                continue;
            }
            $tempArray = [];
            $tempArray['dept_id'] = $part['id'];
            $tempArray['dept_name'] = $part['name'];
            // 特殊 处理原因是在企业微信中公司是部门1，在OA中公司是部门0
            $tempArray['parent_id'] = $part['parentid'] == 1 ? 0 : $part['parentid'];
            $tempArray['dept_sort'] = $part['order'];
            //部门传真
            $tempArray['tel_no'] = '';
            //部门电话
            $tempArray['fax_no'] = '';
            $deptNamePinyin = Utils::convertPy($part['name']);
            $tempArray['dept_name_py'] = $deptNamePinyin[0];
            $tempArray['dept_name_zm'] = $deptNamePinyin[1];
            $tempArray['has_children'] = 0;
            $partMap[$part['id']] = $tempArray['parent_id'];
            if ($leaveDeptId) {
                //离职部门特殊处理，跳过
                if ($part['id'] == $leaveDeptId) {
                    $leaveDept = $tempArray;
                    continue;
                }
                //如果最高级父级部门是离职部门--该部门不做任何操作，在处理用户部门成员统一归为离职部门下
                if ($tempArray['parent_id'] == $leaveDeptId || in_array($tempArray['parent_id'], $leaveDeptChildrenIds)) {
                    $leaveDeptChildrenIds[] = $part['id'];
                    continue;
                }
            }
            $partArray[] = $tempArray;
            $workWeChatPartIds[] = $part['id'];
        }

        foreach ($partArray as $key => $department) {
            $arrParentIds = [];
            $strParentIds = $this->getPartTree($department['dept_id'], $partMap, $arrParentIds);
            $partArray[$key]['arr_parent_id'] = implode(',', array_reverse($strParentIds));
            $partArray[$key]['has_children'] = in_array($department['dept_id'], $partMap) ? 1 : 0;
            $departmentArray[$department['dept_id']] = $partArray[$key];
        }
        $addPartIds = array_diff($workWeChatPartIds, $oaDepartmentIds); // 查出在企业微信部门列表中，但不在目前OA部门列表中的部门id
        $deletePartIds = array_diff($oaDepartmentIds, $workWeChatPartIds); // 查出在OA部门列表中的部门，不在企业微信部门列表中。
        $updatePartIds = array_intersect($workWeChatPartIds, $oaDepartmentIds); // 返回2个部门列表的交集
        $partInfoArray = [
            'dept_id_key_info' => $departmentArray,
            'add_dept_ids' => $addPartIds,
            'update_dept_ids' => $updatePartIds,
            'delete_dept_ids' => $deletePartIds,
            'leave_dept_info' => $leaveDept,
            'leave_dept_children_ids' => $leaveDeptChildrenIds
        ];
        return $partInfoArray;
    }

    /**
     * 递归找到所有父级树
     * @param $departmentId
     * @param $partMap
     * @param $arrParentIds
     * @return array
     * @author [dosy]
     */
    public function getPartTree($departmentId, $partMap, $arrParentIds)
    {
        if ($partMap[$departmentId] === 0) {
            $arrParentIds[] = 0;
            return $arrParentIds;
        }
        $arrParentIds[] = $partMap[$departmentId];
        return $this->getPartTree($partMap[$departmentId], $partMap, $arrParentIds);
    }

    /**
     * 请求企业微信获取用户详情
     * @param string $accessToken
     * @param $userId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatUserInfo(string $accessToken, $userId)
    {
        $api = config('weChat.workWeChatApi.userInfo');
        $fullApi = $api . "?access_token=" . $accessToken . "&userid=" . $userId;
        return $this->workWeChatRequest($fullApi, 'GET');
    }

    /**
     * 请求企业微信获取部门列表
     * @param string $accessToken
     * @param int $ID
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatDepartmentList(string $accessToken, $ID = 1)
    {
        $api = config('weChat.workWeChatApi.departmentList');
        $fullApi = $api . "?access_token=" . $accessToken . "&id=" . $ID;
        return $this->workWeChatRequest($fullApi, 'GET');
    }

    /**
     * 请求企业微信获取用户列表
     * @param string $accessToken
     * @param int $departmentId
     * @param int $fetchChild
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatUserList(string $accessToken, $departmentId = 1, $fetchChild = 1)
    {
        $api = config('weChat.workWeChatApi.userList');
        $fullApi = $api . "?access_token=" . $accessToken . "&department_id=" . $departmentId . "&fetch_child=" . $fetchChild;
        return $this->workWeChatRequest($fullApi, 'GET');
    }

    /**
     * 添加部门
     * @param string $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatAddDept(string $accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.addDept');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 更新部门
     * @param string $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatUpdateDept(string $accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.updateDept');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 删除部门
     * @param string $accessToken
     * @param $departmentId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatDeleteDept(string $accessToken, $departmentId)
    {
        $api = config('weChat.workWeChatApi.deleteDept');
        $fullApi = $api . "?access_token=" . $accessToken . "&id=" . $departmentId;
        return $this->workWeChatRequest($fullApi, 'GET');
    }

    /**
     * 添加用户
     * @param string $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatAddUser(string $accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.addUser');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 更新用户
     * @param string $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatUpdateUser(string $accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.updateUser');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 删除用户
     * @param string $accessToken
     * @param $userId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatDeleteUser(string $accessToken, $userId)
    {
        $api = config('weChat.workWeChatApi.deleteUser');
        $fullApi = $api . "?access_token=" . $accessToken . "&userid=" . $userId;
        return $this->workWeChatRequest($fullApi, 'GET');
    }

    /**
     * 获取企业微信打卡数据
     * @param string $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @creatTime 2020/11/18 10:17
     * @author [dosy]
     */
    public function workWeChatGetCheckInData(string $accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.getCheckInData');
        //$api = 'https://qyapi.weixin.qq.com/cgi-bin/checkin/getcorpcheckinoption';
        //$requestData = ['useridlist'=>['DongSiYao'],'datetime'=>'1607529600'];
        //$requestData = [];
        //$postParam =   $jsonData = ['body' => json_encode($requestData)];
        //\Log::info($postParam);
        $fullApi = $api . "?access_token=" . $accessToken;
        //\Log::info($fullApi);
        //$a = $this->workWeChatRequest($fullApi, 'POST', $postParam);
        //\Log::info($a);die;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**获取企业所有打卡规则
     * @param string $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @creatTime 2020/12/24 10:21
     * @author [dosy]
     */
    public function workWeChatGetCorpCheckinOption(string $accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.getCorpCheckinOption');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 获取企业微信外部联系人列表详情
     * @param $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatExternalContact($accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.externalContact');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 获取客户详情
     * @param $accessToken
     * @param $externalUserId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatExternalContactDetail($accessToken, $externalUserId)
    {
        $api = config('weChat.workWeChatApi.externalContactDetail');
        $fullApi = $api . "?access_token=" . $accessToken . "&external_userid=" . $externalUserId;
        return $this->workWeChatRequest($fullApi, 'GET');
    }

    /**
     * 获取企业微信获取客户群列表
     * @param $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatGroupChatList($accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.groupChatList');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 获取企业微信获取客户群详情
     * @param $accessToken
     * @param $postParam
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatGroupChatDetail($accessToken, $postParam)
    {
        $api = config('weChat.workWeChatApi.groupChatDetail');
        $fullApi = $api . "?access_token=" . $accessToken;
        return $this->workWeChatRequest($fullApi, 'POST', $postParam);
    }

    /**
     * 请求企业微信返回值处理
     * @param $url
     * @param $methods
     * @param array $params
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function workWeChatRequest($url, $methods, $params = [])
    {
        try {
            $client = new Client();
            if ($methods == 'GET') {
                $guzzleResponse = $client->request($methods, $url);
            } else {
                $guzzleResponse = $client->request($methods, $url, $params);
            }
            $getStatusCode = $guzzleResponse->getStatusCode();
            if ($getStatusCode == 200) {
                $responseData = $guzzleResponse->getBody()->getContents();
                $data = json_decode($responseData, true);
                if (isset($data['errcode']) && $data['errcode'] != 0) {
                    $code = $data['errcode'];
                    $error = ['error' => ['request_work_wechat_api_error'], 'data' => ['url' => $url, 'return_data' => $data]];
                    $this->abnormalDataLog($error);
                    return ['code' => ["$code", 'workwechat']];
                } else {
                    return $data;
                }
            } else {
                return ['code' => ["request_error", 'workwechat']];
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            \Log::error($error);
        }

    }
    /***********************************企业微信同步到OA自动同步*** 修改完配置后，自动同步会关闭，必须先手动同步成功才会开启 ***************************/

    /**
     * 自动同步
     * @param $param
     * @param $changeType
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSync($param, $changeType)
    {
        $wechat = $this->getWorkWechat();
        $autoSync = $wechat->auto_sync;
        $toOa = $wechat->sync_direction;
        if ($autoSync && $toOa == 2) {
            //获取通讯录token
            $accessToken = $this->getToken();
            if (!$accessToken) {
                return ['code' => ["0x040007", 'workwechat']];
            }
            if (isset($accessToken['code'])) {
                return $accessToken;
            }
            $loginInfo = (array)app($this->userService)->getLoginUserInfo('admin', []);
            foreach ($loginInfo['roles'] as $role) {
                $loginInfo['role_id'][] = $role['role_id'];
            }
            switch ($changeType) {
                case 'create_party':
                    $syncLeaveDeptId = $wechat->sync_leave_dept_id; //离职部门ID
                    return $this->autoSyncCreateDept($param, $accessToken, $syncLeaveDeptId, $loginInfo);
                    break;
                case 'update_party':
                    $syncLeaveDeptId = $wechat->sync_leave_dept_id; //离职部门ID
                    return $this->autoSyncUpdateDept($param, $accessToken, $syncLeaveDeptId, $loginInfo);
                    break;
                case 'delete_party':
                    $syncLeaveDeptId = $wechat->sync_leave_dept_id; //离职部门ID
                    return $this->autoSyncDeleteDept($param, $accessToken, $syncLeaveDeptId, $loginInfo);
                    break;
                case 'create_user':
                    $syncLeaveDeptId = $wechat->sync_leave_dept_id; //离职部门ID
                    $iniParam = $wechat->toArray();
                    return $this->autoSyncCreateUser($param, $iniParam, $syncLeaveDeptId, $accessToken, $loginInfo);
                    break;
                case 'update_user':
                    $syncLeaveDeptId = $wechat->sync_leave_dept_id; //离职部门ID
                    $iniParam = $wechat->toArray();
                    return $this->autoSyncUpdateUser($param, $iniParam, $syncLeaveDeptId, $accessToken, $loginInfo);
                    break;
                case 'delete_user':
                    return $this->autoSyncDeleteUser($param, $loginInfo);
                    break;
            }
        }
    }

    /**
     * 删除用户
     * @param $param
     * @param $loginInfo
     * @return bool
     * @author [dosy]
     */
    public function autoSyncDeleteUser($param, $loginInfo)
    {
        //获取用户信息
        //$updateUserOaUser = app($this->userService)->getUserToAccount(urldecode($param['user_accounts']));
        $withUser = app($this->WorkWechatUserRepository)->getOneFieldInfo(['userid' => $param['user_accounts']]);
        if ($withUser) {
            $userId = $withUser->oa_id;
            app($this->userService)->userSystemDelete($userId, $loginInfo['user_id']);
            app($this->WorkWechatUserRepository)->deleteByWhere(['oa_id' => [$userId, '=']]);
            return true;
        }
    }

    /**
     * 企业微信同步更新用户
     * @param $param
     * @param $iniParam
     * @param $syncLeaveDeptId
     * @param $accessToken
     * @param $loginInfo
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncUpdateUser($param, $iniParam, $syncLeaveDeptId, $accessToken, $loginInfo)
    {
        //$client = new Client();
        if (isset($param['gender'])) {
            if ($iniParam['sync_ini_user_sex'] === 1) {
                $param['sex'] = ($param['gender'] == 0 || $param['gender'] == 1) ? '1' : '0';
            } else {
                $param['sex'] = ($param['gender'] == 0 || $param['gender'] == 2) ? '0' : '1';
            }
            unset($param['gender']);
        }
        $allLeaveDept = [];
        $department = [];
        if (isset($param['department'])) {
            $param['dept_id'] = $param['department'][0] == 1 ? $iniParam['sync_ini_dept_id'] : $param['department'][0];
            if ($syncLeaveDeptId) {
                $allLeaveDept = $this->workWeChatDepartmentList($accessToken, $syncLeaveDeptId);
                if (isset($allLeaveDept['code'])) {
                    return $allLeaveDept;
                }
                foreach ($allLeaveDept['department'] as $dept) {
                    if ($param['dept_id'] == $dept['id']) {
                        return ['code' => ['sync_update_leave_dept', 'workwechat']]; //离职部门不处理
                    }
                }
            }
            $department = $param['department'];
            unset($param['department']);
        }

        //获取用户信息
        //$updateUserOaUser = app($this->userService)->getUserToAccount(urldecode($param['user_accounts']));
        $withUser = app($this->WorkWechatUserRepository)->getOneFieldInfo(['userid' => $param['userid']]);
        if ($withUser) {
            $param['user_id'] = $withUser->oa_id;
            $updateUserOaUserInfo = app($this->userService)->getUserAllData($withUser->oa_id, ['user_id' => 'admin'], [])->toArray();
            $roleIniId = [];
            if (isset($updateUserOaUserInfo['user_has_many_role'])) {
                foreach ($updateUserOaUserInfo['user_has_many_role'] as $roleInfo) {
                    $roleIniId[] = $roleInfo['role_id'];
                }
            }
            $roleIniIdStr = implode(',', $roleIniId);
            $userSuperior = [];
            if (isset($updateUserOaUserInfo['user_has_many_superior'])) {
                foreach ($updateUserOaUserInfo['user_has_many_superior'] as $superior) {
                    $userSuperior[] = $superior['user_id'];
                }
            }
            $userSuperiorStr = implode(',', $userSuperior);
            $userSubordinate = [];
            if (isset($updateUserOaUserInfo['user_has_many_subordinate'])) {
                foreach ($updateUserOaUserInfo['user_has_many_subordinate'] as $subordinate) {
                    $userSubordinate[] = $subordinate['user_id'];
                }
            }
            $userSubordinateStr = implode(',', $userSubordinate);
            $update = [
                'birthday' => $updateUserOaUserInfo['user_has_one_info']['birthday'],
                'dept_id' => $updateUserOaUserInfo['user_has_one_system_info']['dept_id'],
                'dept_phone_number' => $updateUserOaUserInfo['user_has_one_info']['dept_phone_number'],
                'duty_type' => $updateUserOaUserInfo['user_has_one_system_info']['duty_type'],
                'email' => $updateUserOaUserInfo['user_has_one_info']['email'],
                'faxes' => $updateUserOaUserInfo['user_has_one_info']['faxes'],
                'home_address' => $updateUserOaUserInfo['user_has_one_info']['home_address'],
                'home_phone_number' => $updateUserOaUserInfo['user_has_one_info']['home_phone_number'],
                'home_zip_code' => $updateUserOaUserInfo['user_has_one_info']['home_zip_code'],
                'is_autohrms' => $updateUserOaUserInfo['user_has_one_system_info']['is_autohrms'],
                'last_login_time' => $updateUserOaUserInfo['user_has_one_system_info']['last_login_time'],
                'last_pass_time' => $updateUserOaUserInfo['user_has_one_system_info']['last_pass_time'],
                'list_number' => $updateUserOaUserInfo['list_number'],
                'login_usbkey' => $updateUserOaUserInfo['user_has_one_system_info']['login_usbkey'],
                'max_role_no' => $updateUserOaUserInfo['user_has_one_system_info']['max_role_no'],
                'menu_hide' => $updateUserOaUserInfo['user_has_one_info']['menu_hide'],
                'msn' => $updateUserOaUserInfo['user_has_one_info']['msn'],
                'notes' => $updateUserOaUserInfo['user_has_one_info']['notes'],
                'oicq_no' => $updateUserOaUserInfo['user_has_one_info']['oicq_no'],
                'phone_number' => $updateUserOaUserInfo['user_has_one_info']['phone_number'],
                'post_dept' => $updateUserOaUserInfo['user_has_one_system_info']['post_dept'],
                'post_priv' => $updateUserOaUserInfo['user_has_one_system_info']['post_priv'],
                'role_id_init' => $roleIniIdStr,
                'sex' => $updateUserOaUserInfo['user_has_one_info']['sex'],
                'shortcut' => $updateUserOaUserInfo['user_has_one_system_info']['shortcut'],
                'show_page_after_login' => $updateUserOaUserInfo['user_has_one_info']['show_page_after_login'],
                'signature_picture' => $updateUserOaUserInfo['user_has_one_info']['signature_picture'],
                'sms_login' => $updateUserOaUserInfo['user_has_one_system_info']['sms_login'],
                'sms_on' => $updateUserOaUserInfo['user_has_one_info']['sms_on'],
                'theme' => $updateUserOaUserInfo['user_has_one_info']['theme'],
                'usbkey_pin' => $updateUserOaUserInfo['user_has_one_system_info']['usbkey_pin'],
                'user_accounts' => $updateUserOaUserInfo['user_accounts'],
                'user_area' => $updateUserOaUserInfo['user_area'],
                'user_city' => $updateUserOaUserInfo['user_city'],
                'user_id' => $updateUserOaUserInfo['user_id'],
                'user_job_category' => $updateUserOaUserInfo['user_job_category'],
                'user_job_number' => $updateUserOaUserInfo['user_job_number'],
                'user_name' => $updateUserOaUserInfo['user_name'],
                'user_position' => $updateUserOaUserInfo['user_position'],
                'user_status' => $updateUserOaUserInfo['user_has_one_system_info']['user_status'],
                'user_subordinate_id' => $userSubordinateStr,
                'user_superior_id' => $userSuperiorStr,
                'user_workplace' => $updateUserOaUserInfo['user_workplace'],
                'wap_allow' => $updateUserOaUserInfo['user_has_one_system_info']['wap_allow'],
                'weixin' => $updateUserOaUserInfo['user_has_one_info']['weixin'],
            ];
            $leaderDept = [];
            if (isset($param['user_is_leader_in_dept'])) {
                $leaderDept = $param['user_is_leader_in_dept'];
                unset($param['user_is_leader_in_dept']);
            }
            //地址
            if (isset($param['address'])) {
                $param['home_address'] = $param['address'];
                unset($param['address']);
            }
            //职位对应角色
// 角色* --- 对应企业微信的职务 --- 直接写角色名称 --- 要特殊处理1.未定义职务 2.定义了要去匹配 ['userSystemInfo']
            if ($iniParam['is_open_sync_role']) {
                if (isset($param['position'])) {
                    $position = explode(',', $param['position']);
                    //oa系统角色列表
                    // 因为后续是职务对接角色，防止成员自定义修改导致OA内越权
                    $oaSystemRoleList = app($this->roleService)->getRoleList();
                    $paramRoleIdStr = $iniParam['sync_ini_role'];
                    if (isset($oaSystemRoleList['list'])) {
                        $roleList = [];
                        foreach ($oaSystemRoleList['list'] as $value) {
                            $roleList[$value['role_id']] = $value['role_name'];
                        }
                        $paramRoleId = [];
                        foreach ($position as $positionV) {
                            if (in_array($positionV, $roleList)) {
                                $paramRoleId[] = array_search($positionV, $roleList); //如果有重复的value输出第一个相匹配的role_id
                            }
                        }
                        if (!empty($paramRoleId)) {
                            $paramRoleId = array_unique($paramRoleId);
                            $paramRoleIdStr = implode(',', $paramRoleId);
                        }
                    }
                    $update['role_id_init'] = $paramRoleIdStr;
                }
            }
            $userData = [];
            if (isset($param['new_user_id'])) {
                $validation = app($this->userService)->validateUserAccountsValidation($param['new_user_id']);
                $workWeChatUserInfo = $this->workWeChatUserInfo($accessToken, $param['new_user_id']);
                if (isset($workWeChatUserInfo['code'])) {
                    return $workWeChatUserInfo;
                }
                $param['home_address'] = $workWeChatUserInfo['address'];
                if (!$validation) {
                    if (!isset($param['user_name'])) {
                        /* $workWeChatUserInfo = $this->workWeChatUserInfo($accessToken, $param['new_user_id']);
                         if (isset($workWeChatUserInfo['code'])) {
                             return $workWeChatUserInfo;
                         }*/
                        $param['user_name'] = $workWeChatUserInfo['name'];
                    }
                    if ($param['user_name'] == $iniParam['creator_id']) {
                        return ['code' => 'sync_work_wechat_creator_name_error', 'workwechat'];
                    }
                    $validation = app($this->userService)->validateUserAccountsValidation($param['user_name']);
                    if ($validation) {
                        $param['user_accounts'] = $param['user_name'];
                    } else {
                        return ['code' => 'sync_work_wechat_creator_name_not_oa_account_error', 'workwechat'];
                    }
                } else {
                    $param['user_accounts'] = $param['new_user_id'];
                }
                $userData['userid'] = $param['new_user_id'];
                $userData['oa_user_account'] = $param['user_accounts'];
            } else {
                $userIdValidation = app($this->userService)->validateUserAccountsValidation($param['userid']);
                $workWeChatUserInfo = $this->workWeChatUserInfo($accessToken, $param['userid']);
                if (isset($workWeChatUserInfo['code'])) {
                    return $workWeChatUserInfo;
                }
                $param['home_address'] = $workWeChatUserInfo['address'];
                if (!$userIdValidation) {
                    if (!isset($param['user_name'])) {
                        $param['user_name'] = $workWeChatUserInfo['name'];
                    } else {
                        $userData['oa_user_account'] = $param['user_name'];
                    }
                    if ($param['user_name'] == $iniParam['creator_id']) {
                        return ['code' => 'sync_work_wechat_creator_name_error', 'workwechat'];
                    }
                    $validation = app($this->userService)->validateUserAccountsValidation($param['user_name']);
                    if ($validation) {
                        $param['user_accounts'] = $param['user_name'];
                    } else {
                        return ['code' => 'sync_work_wechat_creator_name_not_oa_account_error', 'workwechat'];
                    }
                } else {
                    $param['user_accounts'] = $param['userid'];
                }

            }
            $updateData = array_merge($update, $param);
            //检查部门是否存在，不存在，自动添加
            $deptInfo = app($this->departmentService)->getDeptDetail($updateData['dept_id']);
            if (isset($deptInfo['code'])) {
                return $deptInfo;
            }
            if (empty($deptInfo)) {
                //获取并检查父级部门
                $deptList = $this->workWeChatDepartmentList($accessToken, $updateData['dept_id']);
                if (isset($deptList['code'])) {
                    return $deptList;
                }
                foreach ($deptList['department'] as $key => $dept) {
                    if ($dept['id'] == $updateData['dept_id']) {
                        $deptContent = $dept;
                    }
                }
                if (isset($deptContent)) {
                    if ($deptContent['parentid'] != 1) {
                        $parentDeptRes = $this->parentDeptCheckAndAdd($deptContent['parentid'], $accessToken, $loginInfo);
                        if (isset($parentDeptRes['code'])) {
                            return $parentDeptRes;
                        }
                    }
                    //添加当前部门
                    $deptAdd = $this->basicAddDeptToOa($deptContent, $loginInfo);
                    if (isset($deptAdd['code'])) {
                        return $deptAdd;
                    }
                }
            }

            $res1 = app($this->userService)->userSystemEdit($updateData, $loginInfo);
            if (isset($res1['code'])) {
                $errorData = [
                    'update_data' => $updateData,
                    'update_user_oa_user_info' => $withUser,
                ];
                $error = ['error' => $res1, 'error_data' => $errorData];
                $this->abnormalDataLog($error);
            }
            //手机号如果被更新,要修改关联关系
            if (isset($param['phone_number'])) {
                $userData['mobile'] = $param['phone_number'];
            }
//            if (isset($param['user_name'])){
//                $userData['user'] = $param['user_name'];
//            }
            if (!empty($userData)) {
                //更新关联
                $where = ['oa_id' => $updateData['user_id']];
                app($this->WorkWechatUserRepository)->updateData($userData, $where);
            }
            //更新部门负责人
            $leader = [];
            foreach ($leaderDept as $key => $value) {
                if ($value == 1) {
                    if ($syncLeaveDeptId) {
                        foreach ($allLeaveDept['department'] as $dept) {
                            if ($department[$key] == $dept['id']) {
                                continue; //离职部门不处理
                            }
                        }
                    }
                    if ($department[$key] == $syncLeaveDeptId || $department[$key] == 1) { //离职部门跳过、公司跳过
                        continue;
                    }
                    //获取部门信息
                    $deptInfo = app($this->departmentService)->getDeptDetail($department[$key]);
                    if ($deptInfo) {
                        $deptInfo['director'] = [$updateData['user_id']];
                    }
                    //替换部门负责人
                    $res = app($this->departmentService)->updateDepartment($deptInfo, $department[$key], $loginInfo['user_id']);
                    if (isset($res['code'])) {
                        $errorData = [
                            'deptId' => $param['department'][$key],
                            'leader' => $leader,
                        ];
                        $error = ['error' => $res, 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                    }
                }
            }
        } else {
            $userInfo = $this->workWeChatUserInfo($accessToken, $param['userid']);
            if (isset($userInfo['code'])) {
                return $userInfo;
            }
            $addUser = [
                'user_account' => $userInfo['userid'],
                'user_name' => $userInfo['name'],
                'department' => $userInfo['department'],
                'user_is_leader_in_dept' => $userInfo['is_leader_in_dept'],
                'mobile' => $userInfo['mobile'],
                'position' => $userInfo['position'],
                'gender' => $userInfo['gender'],
                'email' => $userInfo['email'],
                //'user_status'=>$userStatus,
                // 'user_avatar'=>$userAvatar,
                // 'user_alias'=>$userAlias,
                'telephone' => $userInfo['telephone'],
                'address' => $userInfo['address'],
            ];
            return $this->autoSyncCreateUser($addUser, $iniParam, $syncLeaveDeptId, $accessToken, $loginInfo);
            //return ['code' => ['sync_work_user_not_in_oa', 'workwechat']];
        }
    }

    /**
     * 自动添加用户
     * @param $param
     * @param $iniParam
     * @param $syncLeaveDeptId
     * @param $accessToken
     * @param $loginInfo
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncCreateUser($param, $iniParam, $syncLeaveDeptId, $accessToken, $loginInfo)
    {
        //$client = new Client();
        //用户账号*  ['userBaseData']
        $validation = app($this->userService)->validateUserAccountsValidation($param['user_account']);
        if (!$validation) {
            if ($param['user_name'] == $iniParam['creator_id']) {
                return ['code' => 'sync_work_wechat_creator_name_error', 'workwechat'];
            }
            $validation = app($this->userService)->validateUserAccountsValidation($param['user_name']);
            if ($validation) {
                $tempUserArray['user_accounts'] = $param['user_name'];
            } else {
                return ['code' => 'sync_work_wechat_creator_name_not_oa_account_error', 'workwechat'];
            }
        } else {
            $tempUserArray['user_accounts'] = $param['user_account'];
        }
        //用户真实姓名*  ['userBaseData']
        $tempUserArray['user_name'] = $param['user_name'];
        // 用户性别* ---企业微信性别0表示未定义，1表示男性，2表示女性；  OA 0女1男对应的未定义表示为男  ['userInfo']
        if ($iniParam['sync_ini_user_sex'] === 1) {
            $tempUserArray['sex'] = ($param['gender'] == 0 || $param['gender'] == 1) ? '1' : '0';
        } else {
            $tempUserArray['sex'] = ($param['gender'] == 0 || $param['gender'] == 2) ? '0' : '1';
        }
        // 手机访问* --- 可通过扩展属性来改变 ['userSystemInfo']
        $tempUserArray['wap_allow'] = $iniParam['sync_ini_wap_allow'];
        // 用户状态* --- 可通过扩展属性来改变----这里要写具体的状态id值 ['userSystemInfo']
        $tempUserArray['user_status'] = $iniParam['sync_ini_user_status'];
        // 部门* ---企业微信是多部门--有主部门概念，OA是单部门 企业微信非主部门不再OA内显示 ['userSystemInfo']
        $tempUserArray['dept_id'] = $param['department'][0] == 1 ? $iniParam['sync_ini_dept_id'] : $param['department'][0];
        $allLeaveDept = [];
        if ($syncLeaveDeptId) {
            $allLeaveDept = $this->workWeChatDepartmentList($accessToken, $syncLeaveDeptId);
            if (isset($allLeaveDept['code'])) {
                return $allLeaveDept;
            }
            foreach ($allLeaveDept['department'] as $dept) {
                if ($tempUserArray['dept_id'] == $dept['id']) {
                    return ['code' => ['sync_add_user_in_leave_dept_error', 'workwechat']]; //离职部门不处理
                }
            }
        }
        // 考勤排班类型* --- 可通过扩展属性来改变---这里要写具体的考勤排班的id值 ['userBaseData']
        // $tempUserArray['attendance_scheduling'] = $iniParam['sync_ini_attendance_scheduling'];
        $tempUserArray['attendance_scheduling'] = '';
        // 角色* --- 对应企业微信的职务 --- 直接写角色名称 --- 要特殊处理1.未定义职务 2.定义了要去匹配 ['userSystemInfo']
        $paramRoleIdStr = $iniParam['sync_ini_role'];
        if ($iniParam['is_open_sync_role']) {
            if (isset($param['position'])) {
                $position = explode(',', $param['position']);
                //oa系统角色列表
                // 因为后续是职务对接角色，防止成员自定义修改导致OA内越权
                $oaSystemRoleList = app($this->roleService)->getRoleList();
                if (isset($oaSystemRoleList['list'])) {
                    $roleList = [];
                    foreach ($oaSystemRoleList['list'] as $value) {
                        $roleList[$value['role_id']] = $value['role_name'];
                    }
                    $paramRoleId = [];
                    foreach ($position as $positionV) {
                        if (in_array($positionV, $roleList)) {
                            $paramRoleId[] = array_search($positionV, $roleList); //如果有重复的value输出第一个相匹配的role_id
                        }
                    }
                    if (!empty($paramRoleId)) {
                        $paramRoleId = array_unique($paramRoleId);
                        $paramRoleIdStr = implode(',', $paramRoleId);
                    }
                }
            }
        }
        $tempUserArray['role_id_init'] = $paramRoleIdStr;
        //用户手机号 ---可以不填，但是填了必须唯一 ['userInfo']
        $tempUserArray['phone_number'] = $param['mobile'] ?? '';
        // 序号 ['userBaseData']
        $tempUserArray['list_number'] = '';
        // email ['userInfo']
        $tempUserArray ['email'] = $param['email'] ?? "";
        //对应企业微信座机 ['userInfo']
        $tempUserArray['dept_phone_number'] = $param['telephone'] ?? '';
        //地址 ['userInfo']
        $tempUserArray['home_address'] = $param['address'] ?? '';
        //区域  --下拉框选项值 - ['userBaseData']
        $tempUserArray['user_area'] = '';
        // 职位 --下拉框选项值 涉及自定义没办法很好的取得选择框内的值，暂统一为默认空  ['userBaseData']
        $tempUserArray['user_position'] = 0;
        //城市 --下拉框选项值 ['userBaseData']
        $tempUserArray['user_city'] = 0;
        //职场 --下拉框选项值  ['userBaseData']
        $tempUserArray['user_workplace'] = 0;
        //岗位类别 --下拉框选项值  ['userBaseData']
        $tempUserArray['user_job_category'] = 0;
        //密码 --- 设置同步初始化密码 ['userBaseData']
        $tempUserArray['user_password'] = $iniParam['sync_ini_user_password'] ?? '';
        //同步人事档案 -- 设置同步人事档案默认值 ['userSystemInfo']
        $tempUserArray['is_autohrms'] = $iniParam['is_autohrms'] ?? null;
        // 上级 ['userSuperiorInfo']
        $tempUserArray['user_superior_id'] = '';
        // 下级 ['userSubordinateInfo']
        $tempUserArray['user_subordinate_id'] = '';
        // 管理范围 ['userSystemInfo']  --- 默认0 本部门
        $tempUserArray['post_priv'] = '0';
        //检查部门是否存在，不存在，自动添加
        $deptInfo = app($this->departmentService)->getDeptDetail($tempUserArray['dept_id']);
        if (isset($deptInfo['code'])) {
            return $deptInfo;
        }
        if (empty($deptInfo)) {
            //获取并检查父级部门
            $deptList = $this->workWeChatDepartmentList($accessToken, $tempUserArray['dept_id']);
            if (isset($deptList['code'])) {
                return $deptList;
            }
            foreach ($deptList['department'] as $key => $dept) {
                if ($dept['id'] == $tempUserArray['dept_id']) {
                    $deptContent = $dept;
                }
            }
            if (isset($deptContent)) {
                if ($deptContent['parentid'] != 1) {
                    $parentDeptRes = $this->parentDeptCheckAndAdd($deptContent['parentid'], $accessToken, $loginInfo);
                    if (isset($parentDeptRes['code'])) {
                        return $parentDeptRes;
                    }
                }
                //添加当前部门
                $deptAdd = $this->basicAddDeptToOa($deptContent, $loginInfo);
                if (isset($deptAdd['code'])) {
                    return $deptAdd;
                }
            }
        }
        $userAddInfo = app($this->userService)->userSystemCreate($tempUserArray, $loginInfo);
        //更新部门负责人
        if (isset($userAddInfo['user_id'])) {
            //添加关联
            $userData = [
                'oa_id' => $userAddInfo['user_id'],
                'userid' => $param['user_account'],
                'mobile' => $tempUserArray['phone_number'],
                'oa_user_account' => $tempUserArray['user_accounts'],
            ];
            app($this->WorkWechatUserRepository)->insertData($userData);
            //部门负责人
            $leader = [];
            foreach ($param['user_is_leader_in_dept'] as $key => $value) {
                if ($value == 1) {
                    if ($syncLeaveDeptId) {
                        foreach ($allLeaveDept['department'] as $dept) {
                            if ($param['department'][$key] == $dept['id']) {
                                continue; //离职部门不处理
                            }
                        }
                    }
                    if ($param['department'][$key] == $syncLeaveDeptId || $param['department'][$key] == 1) { //离职部门跳过、公司跳过
                        continue;
                    }
                    //获取部门信息
                    $deptInfo = app($this->departmentService)->getDeptDetail($param['department'][$key]);
                    if ($deptInfo) {
                        $deptInfo['director'] = [$userAddInfo['user_id']];
                    }
                    //替换部门负责人
                    $res = app($this->departmentService)->updateDepartment($deptInfo, $param['department'][$key], $loginInfo['user_id']);
                    if (isset($res['code'])) {
                        $errorData = [
                            'deptId' => $param['department'][$key],
                            'leader' => $leader,
                        ];
                        $error = ['error' => $res, 'error_data' => $errorData];
                        $this->abnormalDataLog($error);
                    }
                }
            }
        }
        if (isset($userAddInfo['code'])) {
            return $userAddInfo;
        } else {
            return true;
        }
    }

    /**
     * 企业微信自动同步删除部门
     * @param $param
     * @param $accessToken
     * @param $syncLeaveDeptId
     * @param ,$loginInfo
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncDeleteDept($param, $accessToken, $syncLeaveDeptId, $loginInfo)
    {
        //获取离职部门及离职部门的子部门
        $client = new Client();
        if ($syncLeaveDeptId) {
            $allLeaveDept = $this->workWeChatDepartmentList($accessToken, $syncLeaveDeptId);
            if (isset($allLeaveDept['code'])) {
                return $allLeaveDept;
            }
            foreach ($allLeaveDept['department'] as $dept) {
                if ($param['dept_id'] == $dept['id']) {
                    return ['code' => ['sync_delete_leave_dept', 'workwechat']]; //离职部门不处理
                }
            }
        }
        $deptDeleteInfo = app($this->departmentService)->delete($param['dept_id'], $loginInfo['user_id']);
        if (isset($deptDeleteInfo['code'])) {
            return $deptDeleteInfo;
        } else {
            return true;
        }
    }

    /**
     * 企业微信自动同步更新部门
     * @param $param
     * @param $accessToken
     * @param $syncLeaveDeptId
     * @param $loginInfo
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncUpdateDept($param, $accessToken, $syncLeaveDeptId, $loginInfo)
    {
        if ($syncLeaveDeptId) {
            //获取离职部门及离职部门的子部门
            $allLeaveDept = $this->workWeChatDepartmentList($accessToken, $syncLeaveDeptId);
            if (isset($allLeaveDept['code'])) {
                return $allLeaveDept;
            }
            // $allLeaveDeptIds = [];
            foreach ($allLeaveDept['department'] as $dept) {
                if ($param['dept_id'] == $dept['id']) { //离职部门不处理
                    return ['code' => ['sync_update_leave_dept', 'workwechat']];
                }
            }
        }
        //获取部门信息
        $deptList = $this->workWeChatDepartmentList($accessToken, $param['dept_id']);
        if (isset($deptList['code'])) {
            return $deptList;
        }
        $deptParentId = 0;
        foreach ($deptList['department'] as $key => $dept) {
            if ($dept['id'] == $param['dept_id']) {
                $deptInfo = $dept;
            }
        }
        //检查当前部门及父部门是否存在OA，不存在则添加，存在则更新
        if (isset($deptInfo)) {
            $deptParentId = $deptInfo['parentid'] == 1 ? 0 : $deptInfo['parentid'];
            $updateData = [
                'parent_id' => $deptParentId,
                'dept_name' => $deptInfo['name'],
                'dept_id' => (int)$param['dept_id'],
            ];
            return $this->checkAndUpdateDeptToOa($deptInfo, $updateData, $accessToken, $loginInfo);
        } else {
            return ['code' => ['sync_dept_id_not_exist', 'workwechat']];
        }
    }

    /**
     * 检查部门及父级部门并添加一条线父级部门--更新
     * @param $deptData
     * @param $updateData
     * @param $accessToken
     * @param $loginInfo
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function checkAndUpdateDeptToOa($deptData, $updateData, $accessToken, $loginInfo)
    {
        //检查部门
        $deptInfo = app($this->departmentService)->getDeptDetail($deptData['id']);
        if (isset($deptInfo['code'])) {
            return $deptInfo;
        }
        if ($deptInfo) {
            //更新部门
            $deptUpdateInfo = app($this->departmentService)->updateDepartment($updateData, (int)$updateData['dept_id'], $loginInfo['user_id']);
            if (isset($deptUpdateInfo['code'])) {
                return $deptUpdateInfo;
            } else {
                return true;
            }
        }
        //检查父级部门
        if ($deptData['parentid'] == 1) {
            $add = $this->basicAddDeptToOa($deptData, $loginInfo);
            if (isset($add['code'])) {
                return $add;
            }
        }
        //添加父级部门非一级部门的部门
        $addParent = $this->parentDeptCheckAndAdd($deptData['parentid'], $accessToken, $loginInfo);
        if (isset($addParent['code'])) {
            return $addParent;
        }
        //添加部门
        return $this->basicAddDeptToOa($deptData, $loginInfo);

    }

    /**
     * 企业微信自动同步添加部门
     * @param $param
     * @param $accessToken
     * @param $syncLeaveDeptId
     * @param $loginInfo
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncCreateDept($param, $accessToken, $syncLeaveDeptId, $loginInfo)
    {
        if ($syncLeaveDeptId) {
            //获取离职部门及离职部门的子部门
            // $client = new Client();
            $allLeaveDept = $this->workWeChatDepartmentList($accessToken, $syncLeaveDeptId);
            if (isset($allLeaveDept['code'])) {
                return $allLeaveDept;
            }
            foreach ($allLeaveDept['department'] as $dept) {
                if ($param['id'] == $dept['id']) {
                    return ['code' => ['sync_add_leave_dept', 'workwechat']]; //离职部门不处理
                }
            }
        }
        // 检查部门及父级部门并添加一条线父级部门
        $check = $this->checkDeptAndAddDept($param['id'], $param['parentid'], $accessToken, $loginInfo);
        if (isset($check['code'])) {
            return $check;
        }
        return $this->basicAddDeptToOa($param, $loginInfo);
        /*$tempArray = [];
        $tempArray['dept_id'] = (int)$param['id'];
        $tempArray['dept_name'] = $param['name'];
        // 特殊 处理原因是在企业微信中公司是部门1，在OA中公司是部门0
        $tempArray['parent_id'] = $param['parentid'] == 1 ? 0 : $param['parentid'];
        $tempArray['dept_sort'] = $param['order'];
        //部门传真
        $tempArray['tel_no'] = '';
        //部门电话
        $tempArray['fax_no'] = '';
        $deptNamePinyin = Utils::convertPy($param['name']);
        $tempArray['dept_name_py'] = $deptNamePinyin[0];
        $tempArray['dept_name_zm'] = $deptNamePinyin[1];
        $tempArray['has_children'] = 0;
        //添加部门
        $deptAddInfo = app($this->departmentService)->addDepartment($tempArray, 'admin');
        if (isset($deptAddInfo['code'])) {
            return $deptAddInfo;
        } else {
            return true;
        }*/
    }

    /**
     * 检查部门及父级部门并添加一条线父级部门--添加
     * @param $deptId
     * @param $parentDeptId
     * @param $accessToken
     * @param $loginInfo
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function checkDeptAndAddDept($deptId, $parentDeptId, $accessToken, $loginInfo)
    {
        //检查部门
        $deptInfo = app($this->departmentService)->getDeptDetail($deptId);
        if (isset($deptInfo['code'])) {
            return $deptInfo;
        }
        if ($deptInfo) {
            return ['code' => ['sync_dept_exist_yet', 'workwechat']];
        }
        //检查父级部门
        if ($parentDeptId == 1) {
            return true;
        }
        return $this->parentDeptCheckAndAdd($parentDeptId, $accessToken, $loginInfo);
    }

    /**
     * 检查并添加非顶级父级部门
     * @param $parentDeptId
     * @param $accessToken
     * @param $loginInfo
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function parentDeptCheckAndAdd($parentDeptId, $accessToken, $loginInfo)
    {
        $parentDeptInfo = app($this->departmentService)->getDeptDetail($parentDeptId);
        if (isset($parentDeptInfo['code'])) {
            return $parentDeptInfo;
        }
        if (!empty($parentDeptInfo)) {
            return true;
        }
        //获取企业微信所有部门列表的一条线
        $allDept = $this->workWeChatDepartmentList($accessToken, 1);
        if (isset($allDept['code'])) {
            return $allDept;
        }
        $allDeptIds = [];
        $allDeptList = [];
        foreach ($allDept['department'] as $dept) {  //dept=>parentDept  2=>1 3=>2 4=>3
            $allDeptIds[$dept['id']] = $dept['parentid'];
            $allDeptList[$dept['id']] = $dept;
        }
        $deptLine = [];
        $deptLine[] = $parentDeptId;
        $tempDeptId = $allDeptIds[$parentDeptId];
        while ($tempDeptId != 1) {
            $tempDeptInfo = app($this->departmentService)->getDeptDetail($tempDeptId);
            if (isset($tempDeptInfo['code'])) {
                return $tempDeptInfo;
            }
            if (!empty($tempDeptInfo)) {
                break;
            }
            $deptLine[] = $tempDeptId;
            $tempDeptId = $allDeptIds[$tempDeptId];
        }
        $count = count($deptLine);
        for ($i = 0; $i < $count; $i++) {
            $addDeptId = array_pop($deptLine);
            $add = $this->basicAddDeptToOa($allDeptList[$addDeptId], $loginInfo);
            if (isset($add['code'])) {
                return $add;
            }
        }
    }

    /**
     * 基础添加部门到OA
     * @param $deptInfo
     * @param $loginInfo
     * @return bool
     * @author [dosy]
     */
    public function basicAddDeptToOa($deptInfo, $loginInfo)
    {
        $tempArray = [];
        $tempArray['dept_id'] = (int)$deptInfo['id'];
        $tempArray['dept_name'] = $deptInfo['name'];
        // 特殊 处理原因是在企业微信中公司是部门1，在OA中公司是部门0
        $tempArray['parent_id'] = $deptInfo['parentid'] == 1 ? 0 : $deptInfo['parentid'];
        $tempArray['dept_sort'] = $deptInfo['order'];
        //部门传真
        $tempArray['tel_no'] = '';
        //部门电话
        $tempArray['fax_no'] = '';
        $deptNamePinyin = Utils::convertPy($deptInfo['name']);
        $tempArray['dept_name_py'] = $deptNamePinyin[0];
        $tempArray['dept_name_zm'] = $deptNamePinyin[1];
        $tempArray['has_children'] = 0;
        //添加部门
        $deptAddInfo = app($this->departmentService)->addDepartment($tempArray, $loginInfo['user_id']);
        if (isset($deptAddInfo['code'])) {
            return $deptAddInfo;
        } else {
            return true;
        }
    }

    /*******************************************OA同步到企业微信******************************/
    /**
     * 增量同步更新
     * @param $params
     * @return array|bool|string
     * @author [dosy]
     */
    public function incrSync($params)
    {
        $params['sync_type'] = 1;
        //创建部门csv文件-上传部门csv文件
        $dept_media_id = $this->creatAndUploadDeptCSV($params['sync_leave']);
        if (isset($dept_media_id['code']) || !is_string($dept_media_id)) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '创建部门csv文件-上传部门csv文件过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_dept_csv', 'workwechat']];
            $params['error_code'] = $dept_media_id;
            $this->syncLog($params);
            return $dept_media_id;
        }
        //创建用户csv文件-上传用户csv文件
        $user_media_id = $this->creatAndUploadUserCSV($params['sync_leave'], $params['sync_type']);
        if (isset($user_media_id['code']) || !is_string($user_media_id)) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '创建用户csv文件-上传用户csv文件过程失败，具体错误:';
            $params['error_content'] = ['code' => ['sync_log_error_user_csv', 'workwechat']];
            $params['error_code'] = $user_media_id;
            $this->syncLog($params);
            return $user_media_id;
        }

        //同步部门组织架构【全量覆盖部门】-获取异步信息
        $dept = $this->syncDept($dept_media_id);
        if (isset($dept['code'])) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '同步部门【全量覆盖部门】-获取异步信息过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_dept_sync', 'workwechat']];
            $params['error_code'] = $dept;
            $this->syncLog($dept);
            return $dept;
        }

        //同步用户【增量添加成员】-获取异步信息
        $user = $this->syncIncrUser($user_media_id);
        if (isset($user['code'])) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '同步用户【增量添加成员】-获取异步信息过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_user_sync', 'workwechat']];
            $params['error_code'] = $dept;
            $this->syncLog($user);
            return $user;
        }
        $params['sync_result'] = 1;
        //$params['error_content'] = '无';
        $params['error_content'] = ['code' => ['sync_log_error_empty', 'workwechat']];
        $this->syncLog($params);
    }

    /**
     * 同步更新企业微信通讯录
     * @param $params
     * @param $accessToken
     * @param $creator
     * @return array|bool|false|mixed|string
     * @author [dosy]
     */
    public function coverSync($params, $accessToken, $creator)
    {
        $params['sync_type'] = 0;
        //用户预处理；获取符合同步条件的用户和要删除企业微信的用户
        $allUser = $this->userProcessing($params['sync_leave'], $accessToken, $creator);
        if (isset($allUser['code'])) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '用户预处理过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_user_pretreatment', 'workwechat']];
            $params['error_code'] = $allUser;
            $this->syncLog($params);
            return $allUser;
        }
        //创建部门csv文件-上传部门csv文件
        $dept_media_id = $this->creatAndUploadDeptCSV($params['sync_leave']);
        if (isset($dept_media_id['code']) || !is_string($dept_media_id)) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '创建部门csv文件-上传部门csv文件过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_dept_csv', 'workwechat']];
            $params['error_code'] = $dept_media_id;
            $this->syncLog($params);
            return $dept_media_id;
        }
        //创建用户csv文件-上传用户csv文件
        $user_media_id = $this->creatAndUploadUserCSV($params['sync_leave'], $params['sync_type'], $allUser['sync_update_user']);
        if (isset($user_media_id['code']) || !is_string($user_media_id)) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '创建用户csv文件-上传用户csv文件过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_user_csv', 'workwechat']];
            $params['error_code'] = $user_media_id;
            $this->syncLog($params);
            return $user_media_id;
        }
        //同步部门组织架构【全量覆盖部门】-获取异步信息
        $dept = $this->syncDept($dept_media_id);
        if (isset($dept['code'])) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '同步部门【全量覆盖部门】-获取异步信息过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_dept_sync', 'workwechat']];
            $params['error_code'] = $dept;
            $this->syncLog($dept);
            return $dept;
        }
        //同步用户【增量添加成员】-获取异步信息
        $user = $this->syncIncrUser($user_media_id);
        if (isset($user['code'])) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '同步用户【增量添加成员】-获取异步信息过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_user_sync', 'workwechat']];
            $params['error_code'] = $user;
            $this->syncLog($user);
            return $user;
        }
        //删除oa不存在的用户
        $deleteUsers = $allUser['sync_delete_user'];
        if (empty($deleteUsers)) {
            $params['sync_result'] = 1;
            //$params['error_content'] = '无';
            $params['error_content'] = ['code' => ['sync_log_error_empty', 'workwechat']];
            $this->syncLog($params);
            return $user;
        }
        $deleteResult = $this->syncDeleteWorkWechatUser($deleteUsers);
        if (isset($deleteResult['code'])) {
            $params['sync_result'] = 0;
            //$params['error_content'] = '同步用户-删除oa不存在的用户过程失败，具体错误：';
            $params['error_content'] = ['code' => ['sync_log_error_user_sync_delete', 'workwechat']];
            $params['error_code'] = $deleteResult;
            $this->syncLog($deleteResult);
            return $deleteResult;
        }
        $params['sync_result'] = 1;
        // $params['error_content'] = '无';
        $params['error_content'] = ['code' => ['sync_log_error_empty', 'workwechat']];
        $this->syncLog($params);

    }

    /**
     * 删除oa不存在的用户
     * @param $deleteUsers
     * @return array|bool
     * @author [dosy]
     */
    public function syncDeleteWorkWechatUser($deleteUsers)
    {
        $length = 200;
        $offset = 0;
        $count = count($deleteUsers);
        $floor = floor($count / $length);
        $module = $count % $length;
        $accessToken = $this->getToken();
        if (!$accessToken) {
            return ['code' => ["0x040007", 'workwechat']];
        }
        if (isset($accessToken['code'])) {
            return $accessToken;
        }
        for ($i = 0; $i < $floor; $i++) {
            $deleteUser = array_slice($deleteUsers, $offset, $length);
            $res = $this->delUserIds($accessToken, $deleteUser);
            if (isset($res['code'])) {
                return $res;
            }
            $offset = $offset + 200;
        }
        if ($module !== 0) {
            $deleteUser = array_slice($deleteUsers, -$module, $module);
            $res = $this->delUserIds($accessToken, $deleteUser);
            if (isset($res['code'])) {
                return $res;
            }
        }
    }

    /**
     * 用户处理；获取符合同步条件的用户和要删除企业微信的用户
     * @param $sync_leave
     * @param $accessToken
     * @param $creator
     * @return array|bool|mixed
     * @author [dosy]
     */
    public function userProcessing($sync_leave, $accessToken, $creator)
    {
        //获取企业微信存在的用户
        $departmentId = 1;  //全部门用户
        $fetchChild = 1;
        //获取企业微信已存在成员信息  获取用户userlist
        $workWechatUserList = $this->getDeptUserInfo($accessToken, $departmentId, $fetchChild);
        if (isset($workWechatUserList['code'])) {
            return $workWechatUserList;
        }
        //获取oa用户
        if ($sync_leave == 1) {
            $data = ['include_leave' => 1, 'response' => 'data'];
        } else {
            $data = ['response' => 'data'];
        }
        $oaUsers = app($this->userService)->userSystemList($data);
        if (isset($oaUsers['code'])) {
            return $oaUsers;
        }
        $oaUserList = [];
        //处理用户
        $oaUserAccountList = [];
        $exitPhoneNumbers = [];
        foreach ($oaUsers['list'] as $k => $oaUser) {
            $phoneNumber = $oaUser['user_has_one_info']['phone_number'] ? $oaUser['user_has_one_info']['phone_number'] : null;
            $email = $oaUser['user_has_one_info']['email'] ? $oaUser['user_has_one_info']['email'] : null;
            if ($phoneNumber || $email) {
                $oaUserAccountList[] = $oaUser['user_id'];
                $oaUserList[] = $oaUser;
            }
            if ($phoneNumber) {
                //oa所有存在的手机号
                $exitPhoneNumbers[] = $phoneNumber;
            }
        }
        //获取企业微信存在，oa不存在的用户
        $delWorkWechatUser = [];
        foreach ($workWechatUserList as $k => $user) {
            //企业微信用户user_id和oa用户user_id检查
            if (in_array($user['userid'], $oaUserAccountList)) {
                continue;
            } else {
                //企业微信用户mobile和oa用户mobile检查
                if (in_array($user['mobile'], $exitPhoneNumbers)) {
                    continue;
                }
                $delWorkWechatUser[] = $user['userid'];
            }
        }
        //验证企业微信创建人是否存在
        $res = $this->getUserInfo($accessToken, $creator);
        if (isset($res['code'])) {
            return $res;
        }
        //企业创建者存在剔除
        $deleteUsers = array_diff($delWorkWechatUser, [$creator]);
        $user = [
            'sync_update_user' => $oaUserList,
            'sync_delete_user' => $deleteUsers,
        ];
        return $user;
    }

    /**
     * 创建部门csv文件-上传部门csv文件
     * @param $sync_leave
     * @return array|bool|false|string
     * @author [dosy]
     */
    public function creatAndUploadDeptCSV($sync_leave)
    {
        $newPath = createCustomDir("workwechat");
        $deptFile = $newPath . "enterprise_organization.csv"; //企业号
        $depts = app($this->departmentRepository)->getDepartmentBySort();
        $fp1 = fopen($deptFile, "w+"); //打开csv文件，如果不存在则创建
        $depts_arr1 = array("部门名称", "部门ID", "父部门ID", "排序");
        $depts_str1 = implode(",", $depts_arr1); //用 ' 分割成字符串
        $tempDept = app($this->companyRepository)->getCompanyDetail();
        $set_name = $tempDept->company_name; //"所有部门";
        if (!$set_name) {
            $set_name = trans('workwechat.department_manage');
        }
        $first_arr1 = array($set_name, "1", "0", "1"); //第一行数据
        $first_arr1 = implode(",", $first_arr1); //用 ' 分割成字符串
        if ($sync_leave == 1) {
            $leave_arr1 = array("离职人员", "8888", "1", "1"); //第一行数据
            $leave_arr1 = implode(",", $leave_arr1); //用 ' 分割成字符串
            $dept_str = $depts_str1 . "\n" . $first_arr1 . "\n" . $leave_arr1 . "\n";
        } else {
            $dept_str = $depts_str1 . "\n" . $first_arr1 . "\n";
        }


        foreach ($depts as $k => $v) {
            if ($v['dept_id'] == 1) {
                $v['dept_id'] = 9999;
            }
            if ($v['parent_id'] == 1) {
                $v['parent_id'] = 9999;
            }
            $v['sort'] = $k;
            $dept_name = $v['dept_name'];
            $dept_id = $v['dept_id'];
            $parent_id = $v['parent_id'] == 0 ? 1 : $v['parent_id'];
            $dept_sort = $v['sort'];

            $depts_arr2 = array(
                $dept_name,
                $dept_id,
                $parent_id,
                $dept_sort,
            ); //第二行数据

            $depts_arr2 = implode(",", $depts_arr2);
            $dept_str .= $depts_arr2 . "\n"; //加入换行符
        }
        fwrite($fp1, $dept_str); //写入数据
        fclose($fp1); //关闭文件句柄

        //上传附件
        $wechatDept = $this->uploadTempFile($deptFile, 'file');
        if (isset($wechatDept['code'])) {
            return $wechatDept;
        }
        $wechatDeptData = json_decode($wechatDept, true);
        if (isset($wechatDeptData['errcode']) && $wechatDeptData['errcode']) {
            $code = $wechatDeptData['errcode'];
            return ['code' => ["$code", 'workwechat']];
        }
        //部门文件的id
        $dept_media_id = $wechatDeptData["media_id"];
        return $dept_media_id;
    }

    /**
     * 创建用户csv文件-上传用户csv文件
     * @param $sync_leave
     * @param $sync_type
     * @param $userList
     * @return array|bool|false|string
     * @author [dosy]
     */
    public function creatAndUploadUserCSV($sync_leave, $sync_type, $userList = [])
    {
        $newPath = createCustomDir("workwechat");
        $file = $newPath . "enterprise_contacts.csv"; //企业号
        if ($sync_leave == 1) {
            $data = ['include_leave' => 1, 'response' => 'data'];
        } else {
            $data = ['response' => 'data'];
        }
        if ($sync_type == 1) {
            $userList = app($this->userService)->userSystemList($data);
            $userList = $userList['list'];
        }
        if (empty($userList)) {
            return ['code' => ["empty_user", 'workwechat']];
        }
        $fp = fopen($file, "w+"); //打开csv文件，如果不存在则创建
        $data_arr1 = array("姓名", "账号", "手机号", "邮箱", "所在部门", "职位", "性别", "是否领导", "排序", "英文名", "座机", "禁用"); //第一行数据
        $data_str1 = implode(",", $data_arr1); //用 ' 分割成字符串
        $data_str = $data_str1 . "\n";
        //生成csv
        //调试注册500用户
        foreach ($userList as $k => $v) {
            $phoneNumber = $v['user_has_one_info']['phone_number'] ? $v['user_has_one_info']['phone_number'] : null;
            $email = $v['user_has_one_info']['email'] ? $v['user_has_one_info']['email'] : null;
            if ($phoneNumber || $email) {
                $userId = $v['user_id'];
                $userName = $v['user_name'];
                $sex = $v['user_has_one_info']['sex'] == 1 ? "男" : "女";
                $enable = 0;
                $dept = $v['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id'] ?? 0;
                if($dept == 0){
                    continue;
                }
                if ($v['user_has_one_system_info']['user_status'] == 2) {
                    $dept = "8888";
                    $enable = 1;
                }
                $role_name = $v['user_has_many_role'];
                $position = "";
                if (!empty($role_name)) {
                    $user_position = [];
                    foreach ($role_name as $key => $value) {
                        if (isset($value['has_one_role']['role_name'])) {
                            $user_position[] = $value['has_one_role']['role_name'];
                        }

                    }
                    $position = implode(";", $user_position);
                }
                $data_arr2 = array(
                    $userName,
                    $userId,
                    $phoneNumber,
                    $email,
                    $dept,
                    $position,
                    $sex,
                    0,
                    0,
                    '',
                    '',
                    $enable,

                ); //第二行数据
                $data_str2 = implode(",", $data_arr2);
                $data_str .= $data_str2 . "\n"; //加入换行符

            } else {
                continue;
            }
        }
        fwrite($fp, $data_str); //写入数据
        fclose($fp); //关闭文件句柄
        //上传素材文件
        $wechatTemp = $this->uploadTempFile($file, 'file');
        if (isset($wechatTemp['code'])) {
            return $wechatTemp;
        }
        $wechatData = json_decode($wechatTemp, true);
        if (isset($wechatData['errcode']) && $wechatData['errcode']) {
            $code = $wechatData['errcode'];
            return ['code' => ["$code", 'workwechat']];
        }
        $user_media_id = $wechatData["media_id"];
        return $user_media_id;
    }

    /**
     * 同步部门组织架构【全量覆盖部门】-获取异步信息
     * @param $dept_media_id
     * @return array|bool
     * @author [dosy]
     */
    public function syncDept($dept_media_id)
    {
        $access_token = $this->getToken();  //强制token 的原因是后面由一个异步，为止时间（假定2小时有结果）；
        if (!$access_token) {
            return ['code' => ["0x040007", 'workwechat']];
        }
        if (isset($access_token['code'])) {
            return $access_token;
        }
        //同步通讯录
        $deptUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/replaceparty?access_token=$access_token";
        $deptFields = array('media_id' => $dept_media_id);
        $deptMsg = urldecode(json_encode($deptFields));
        $vl = getHttps($deptUrl, $deptMsg);
        $vlData = json_decode($vl, true);

        if (isset($vlData['errcode']) && $vlData['errcode'] != 0) {
            $code = $vlData['errcode'];
            return ['code' => ["$code", 'workwechat']];
        } else {
            $jobid = $vlData["jobid"];
            $successUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/getresult?access_token=$access_token&jobid=$jobid";
            $tempData = getHttps($successUrl);
            $v2Data = json_decode($tempData, true);
            if (isset($v2Data['errcode']) && $v2Data['errcode'] != 0) {
                $this->abnormalDataLog($tempData);
                $code = $v2Data['errcode'];
                return ['code' => ["$code", 'workwechat']];
            }
        }
        return true;
    }

    /**
     * 同步用户【增量添加成员】-获取异步信息
     * @param $user_media_id
     * @return array|bool
     * @author [dosy]
     */
    public function syncIncrUser($user_media_id)
    {
        $access_token = $this->getToken();
        if (!$access_token) {
            return ['code' => ["0x040007", 'workwechat']];
        }
        if (isset($access_token['code'])) {
            return $access_token;
        }
        //更新
        $userUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/syncuser?access_token=$access_token";
        $fields = array('media_id' => $user_media_id);
        $userMsg = urldecode(json_encode($fields));
        $vl = getHttps($userUrl, $userMsg);
        $vlData = json_decode($vl, true);

        if (isset($vlData['errcode']) && $vlData['errcode']) {
            $code = $vlData['errcode'];
            return ['code' => ["$code", 'workwechat']];
        } else {
            $jobid = $vlData["jobid"];
            $successUrl = "https://qyapi.weixin.qq.com/cgi-bin/batch/getresult?access_token=$access_token&jobid=$jobid";
            $tempData = getHttps($successUrl);
            $v2Data = json_decode($tempData, true);
            if (isset($v2Data['errcode']) && $v2Data['errcode']) { //45026 触发删除用户保护
                $this->abnormalDataLog($tempData);
                $code = $v2Data['errcode'];
                $result = ['code' => ["$code", 'workwechat']];
            } else {
                $result = true;
            }
            return $result;
        }
    }


    /**
     * 企业微信同步通讯录-同步日志
     * @param $params
     * @param $syncDirection
     * @return mixed
     * @author [dosy]
     */
    public function syncLog($params, $syncDirection = 1)
    {
        if (isset($params['sync_type']) && isset($params['sync_leave']) && isset($params['sync_result']) && isset($params['operator'])) {
            if (isset($params['error_code'])) {
                $error_code = error_response($params['error_code']['code'][0], $params['error_code']['code'][1], '');
                $error_content = error_response($params['error_content']['code'][0], $params['error_content']['code'][1], '');
                $error_message = $error_content['errors'][0]['message'] . $error_code['errors'][0]['message'];
            } else {
                $error_message = error_response($params['error_content']['code'][0], $params['error_content']['code'][1], '');
                $error_message = $error_message['errors'][0]['message'];
            }
            if (isset($params['log_id'])) {
                $data = [
                    'sync_end_time' => date("Y-m-d H:i:s", time()),
                    'sync_result' => $params['sync_result'],
                    'operator' => $params['operator'],
                    'sync_error' => $error_message
                ];
                $result = app($this->WorkWechatSyncLogRepository)->updateData($data, ['id' => $params['log_id']]);
                return $result;
            } else {
                $time = date("Y-m-d H:i:s", time());
                if ($syncDirection == 2) {
                    $time = trans("workwechat.sync_not_yet");
                }
                $data = [
                    'sync_start_time' => $params['sync_start_time'],
                    'sync_end_time' => $time,
                    'sync_type' => $params['sync_type'],
                    'sync_leave' => $params['sync_leave'],
                    'sync_result' => $params['sync_result'],
                    'operator' => $params['operator'],
                    'sync_error' => $error_message
                ];
                $result = app($this->WorkWechatSyncLogRepository)->insertData($data);
                return $result;
            }

        }
    }

    /**
     * 获取日志
     * @param $param
     * @return mixed
     * @author [dosy]
     */
    public function getSyncLogList($param)
    {
        $data = $this->response(app($this->WorkWechatSyncLogRepository), 'getCount', 'getList', $this->parseParams($param));
        return $data;
    }

    /** 组装拉取微信电子发票列表所需参数
     * @return array|bool|mixed|string
     */
    public function getInvoiceParam()
    {
        // 获取agentid
        $agentId = $_COOKIE['agentid'] ?? '';
        if (!$agentId) {
            return ['code' => ['', '无agentid']];
        }
        // 获取access_token
        $accessToken = $this->getAccessToken($agentId);
        if (!$accessToken || isset($accessToken['code'])) {
            return $accessToken;
        }
        // 获取api_ticket
        $apiTicket = $this->getTicket($accessToken);
        if (!$apiTicket || isset($apiTicket['code'])) {
            return $apiTicket;
        }
        // cardSign 参数处理
        $wechat = $this->getWorkWechat();
        $corpid = $wechat->corpid;
        $cardType = 'INVOICE';
        $timestamp = time();
        $nonceStr = md5(uniqid(microtime(true), true));
        $signType = 'SHA1';
        $cardSign = sha1($apiTicket . $corpid . $timestamp . $nonceStr . $cardType);
        return compact('timestamp', 'nonceStr', 'signType', 'cardSign');
    }

    /** 获取电子发票ticket
     * @param $access_token 调用接口凭证
     * @param string $type
     * @return array|mixed|string
     */
    public function getTicket($access_token, $type = 'wx_card')
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/ticket/get?access_token=$access_token&type=$type";
        $vl = getHttps($url);
        $result = json_decode($vl, true);
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            $code = $result['errcode'];
            return ['code' => ["$code", 'workwechat']];
        } else {
            $apiTicket = $result['ticket'] ?? '';
            return $apiTicket;
        }
    }

    public function getWxInvoices($params)
    {
        // 获取agentid
        $agentId = $_COOKIE['agentid'] ?? '';
        if (!$agentId) {
            return ['code' => ['', '无agentid']];
        }
        // 获取access_token
        $accessToken = $this->getAccessToken($agentId);
        if (!$accessToken || isset($accessToken['code'])) {
            return $accessToken;
        }
        $userList = $this->getDeptUserInfo($accessToken, 1);
        if (isset($userList['code'])) {
            return $userList;
        }
        $userCount = count($userList);
        $info = $params['info'] ?? [];
        if (!$info) {
            return ['code' => ['', '微信返回发票列表信息错误']];
        }
        $invoices = [];
        $errors = [];
        //https://qyapi.weixin.qq.com/cgi-bin/card/invoice/reimburse/getinvoiceinfo?access_token=ACCESS_TOKEN
        if ($userCount > 200) {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/card/invoice/reimburse/getinvoiceinfo?access_token=$accessToken";
            $fields = $info;
            $userMsg = urldecode(json_encode($fields));
            $vl = getHttps($url, $userMsg);
            $vlData = json_decode($vl, true);
            if (isset($vlData['errcode']) && $vlData['errcode'] != 0) {
                $code = $vlData['errcode'];
                $errors = ['code' => ["$code", 'workwechat']];
            } else {
                $invoices[] = $vlData;
            }
        } else {
            if (count($info) == count($info, 1)) {
                $info = [$info];
            }
            foreach ($info as $param) {
                $url = "https://qyapi.weixin.qq.com/cgi-bin/card/invoice/reimburse/getinvoiceinfo?access_token=$accessToken";
                $fields = $param;
                $userMsg = urldecode(json_encode($fields));
                $vl = getHttps($url, $userMsg);
                $vlData = json_decode($vl, true);
                if (isset($vlData['errcode']) && $vlData['errcode'] != 0) {
                    $code = $vlData['errcode'];
                    $errors = ['code' => ["$code", 'workwechat']];
                    if ($code == '48001') {
                        break;
                    }
                } else {
                    $invoices[] = $vlData;
                }
            }
        }
        if ($errors) {
            return $errors;
        }
        return $invoices;
    }

    /**
     * 企业微信事件回调
     * @param $param
     * @author [dosy]
     */
    public function syncCallback($param)
    {
        //微信模块ID 499 企业微信107  钉钉应用 120
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (isset($auhtMenus["code"])) {
            $code = $auhtMenus["code"];
            $message = urlencode(trans("register.$code"));
            $errorUrl = integratedErrorUrl($message);
            header("Location: $errorUrl");
            exit;
        } else {
            //能获取到授权信息
            if (!in_array(913, $auhtMenus)) {
                $message = trans("workwechat.Authorization_expires");
                $errorUrl = integratedErrorUrl($message);
                header("Location: $errorUrl");
                exit;
            }
        }
        if (isset($param['echostr'])) {
            app($this->workWechat)->valid();
        } else {
            app($this->workWechat)->responseMsg($param);
        }
    }

    /********************************************** 正向同步 自动同步************************************/

    /**
     * 自动更新 -- 分配
     * @param $controller
     * @param $response
     * @param $allParam
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChat($controller, $response, $allParam)
    {
        $wechat = $this->getWorkWechat();
        if (!$wechat) {
            return ['code' => ["params_error", 'workwechat']];
        }
        $autoSync = $wechat->auto_sync ?? '';
        $toWorkWeChat = $wechat->sync_direction ?? '';
        $syncType = $wechat->sync_type ?? ''; //通讯录同步方式
        $syncLeave = $wechat->sync_leave ?? '';
        $syncLeaveDeptId = $wechat->sync_leave_dept_id ?? '';
        if ($autoSync && $toWorkWeChat == 1 && $syncType === 0) {
            //获取通讯录token
            $accessToken = $this->getToken();
            if (!$accessToken) {
                return ['code' => ["0x040007", 'workwechat']];
            }
            if (isset($accessToken['code'])) {
                return $accessToken;
            }
            switch ($controller) {
                case 'DepartmentController@addDepartment':
                    $deptId = $response->original['data']['dept_id'];
                    return $this->autoSyncToWorkWeChatAddDept($deptId, $accessToken);
                    break;
                case 'DepartmentController@editDepartment':
                    $deptId = $allParam['dept_id'];
                    return $this->autoSyncToWorkWeChatUpdateDept($accessToken, $deptId);
                    break;
                case 'DepartmentController@deleteDepartment':
                    $deptId = $allParam['dept_id'];
                    return $this->autoSyncToWorkWeChatDeleteDept($accessToken, $deptId);
                    break;
                case 'UserController@userSystemCreate':
                    $userData = $response->original['data'];
                    $userId = $userData->user_id;
                    return $this->autoSyncToWorkWeChatAddUser($userId, $accessToken);
                    break;
                case 'UserController@userSystemEdit':
                    $userId = $allParam['user_id'];
                    return $this->autoSyncToWorkWeChatUpdateUser($accessToken, $userId, $syncLeave, $syncLeaveDeptId);
                    break;
                case 'UserController@userSystemDelete':
                    $userId = $allParam['user_id'];
                    return $this->autoSyncToWorkWeChatDeleteUser($accessToken, $userId);
                    break;
            }
        }
    }


    /**
     * 导入用户、审批用户、批量审核用户集中处理
     * @param $param
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function importUserToWorkWeChatUser($param)
    {
        $wechat = $this->getWorkWechat();
        if($wechat->isEmpty()){
            return true;
        }
        $autoSync = $wechat->auto_sync;
        $toWorkWeChat = $wechat->sync_direction;
        $syncType = $wechat->sync_type; //通讯录同步方式
        $syncLeave = $wechat->sync_leave;
        $syncLeaveDeptId = $wechat->sync_leave_dept_id;
        if ($autoSync && $toWorkWeChat == 1 && $syncType === 0) {
            //获取通讯录token
            $accessToken = $this->getToken();
            if (!$accessToken) {
                return ['code' => ["0x040007", 'workwechat']];
            }
            if (isset($accessToken['code'])) {
                return $accessToken;
            }
            //仅新增
            if (isset($param['type']) && $param['type'] == 'add' && isset($param['user_id']) && !empty($param['user_id'])) {
                $res = $this->autoSyncToWorkWeChatAddUser($param['user_id'], $accessToken);
                $this->abnormalDataLog($res);
            }
            //仅更新数据
            if (isset($param['type']) && $param['type'] == 'update' && isset($param['user_id']) && !empty($param['user_id'])) {
                $res = $this->autoSyncToWorkWeChatUpdateUser($accessToken, $param['user_id'], $syncLeave, $syncLeaveDeptId);
                $this->abnormalDataLog($res);
            }
        }
    }

    /**
     * 清楚企业微信全部用户
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChatDeleteAllUser()
    {
        $wechat = $this->getWorkWechat();
        if($wechat->isEmpty()){
            return true;
        }
        $autoSync = $wechat->auto_sync;
        $toWorkWeChat = $wechat->sync_direction;
        $syncType = $wechat->sync_type; //通讯录同步方式
        $syncLeave = $wechat->sync_leave;
        $syncLeaveDeptId = $wechat->sync_leave_dept_id;
        if ($autoSync && $toWorkWeChat == 1 && $syncType === 0) {
            //获取通讯录token
            $accessToken = $this->getToken();
            if (!$accessToken) {
                return ['code' => ["0x040007", 'workwechat']];
            }
            if (isset($accessToken['code'])) {
                return $accessToken;
            }
            $userList = $this->workWeChatUserList($accessToken, 1, 1);
            if (isset($userList)) {
                foreach ($userList['userlist'] as $value) {
                    $userIds[] = $value['userid'];
                }
                if (!empty($userIds)) {
                    $deleteResult = $this->syncDeleteWorkWechatUser($userIds);
                    $this->abnormalDataLog($deleteResult);
                }
            }
        }
    }

    /**
     * 自动更新--删除用户
     * @param $accessToken
     * @param $userId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChatDeleteUser($accessToken, $userId)
    {
        if ($userId) {
            $workWechatUser = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($userId);
            if (!isset($workWechatUser->userid)||empty($workWechatUser->userid)){
                return false;
            }else{
                $res = $this->workWeChatDeleteUser($accessToken, $workWechatUser->userid);
                app($this->WorkWechatUserRepository)->deleteByWhere(['oa_id' => [$userId]]);
                if (isset($res['errcode']) && $res['errcode'] === 0) {
                    return true;
                } else {
                    return $res;
                }
            }
        } else {
            return ['code' => ['sync_user_id_not_exist', 'workwechat']];
        }
    }

    /**
     * 自动更新--更新用户
     * @param $accessToken
     * @param $userId
     * @param $syncLeave
     * @param $syncLeaveDeptId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChatUpdateUser($accessToken, $userId, $syncLeave, $syncLeaveDeptId)
    {
        if ($userId) {
            $workWechatUser = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($userId);
            if (!isset($workWechatUser->userid)||empty($workWechatUser->userid)){
                return $this->autoSyncToWorkWeChatAddUser($userId, $accessToken);
            }
            //先检查用户是否存在，如果不存在企业微信，则添加
            $check = $this->workWeChatUserInfo($accessToken, $workWechatUser->userid);
            if (isset($check['code']) && $check['code'][0] == 60111) {
                return $this->autoSyncToWorkWeChatAddUser($userId, $accessToken);
            }
            $updateUserOaUserInfo = app($this->userService)->getUserAllData($userId, ['user_id' => 'admin'], [])->toArray();
            $mobile = $updateUserOaUserInfo['user_has_one_info']['phone_number'];
            if (!$mobile) {
                return false;
            }
            $userStatus = $updateUserOaUserInfo['user_has_one_system_info']['user_status'];
            $mainDepartment = $updateUserOaUserInfo['user_has_one_system_info']['dept_id'];
            if ($userStatus == 2) {
                if ($syncLeave) {
                    $mainDepartment = $syncLeaveDeptId;
                } else {
                    return $this->autoSyncToWorkWeChatDeleteUser($accessToken, $userId);
                }
            }
            $userAccount = $updateUserOaUserInfo['user_accounts'];
            $userName = $updateUserOaUserInfo['user_name'];
            $order = $updateUserOaUserInfo['list_number'];
            $mobile = $updateUserOaUserInfo['user_has_one_info']['phone_number'];
            $gender = $updateUserOaUserInfo['user_has_one_info']['sex'] == 1 ? $updateUserOaUserInfo['user_has_one_info']['sex'] : 2;
            $email = $updateUserOaUserInfo['user_has_one_info']['email'];
            $telephone = $updateUserOaUserInfo['user_has_one_info']['dept_phone_number'];
            $address = $updateUserOaUserInfo['user_has_one_info']['home_address'];

            $roleIniName = [];
            if (isset($updateUserOaUserInfo['user_has_many_role'])) {
                foreach ($updateUserOaUserInfo['user_has_many_role'] as $roleInfo) {
                    $roleIniName[] = $roleInfo['has_one_role']['role_name'];
                }
            }
            $roleIniNameStr = implode(',', $roleIniName);
            // 部门负责人由于OA和企业微信规则不符合，所以不同步，OA内的部门负责人，可以是任意人员，企业则必须是部门内成员 --- OA内一个人员只能在一个部门内，企业微信一个人员可以多部门存在
            $user = [
                'userid' => $workWechatUser->userid,
                'name' => $userName,
                'mobile' => $mobile,
                'department' => [$mainDepartment],
                'order' => [$order],
                'position' => $roleIniNameStr,
                'gender' => $gender,
                'email' => $email,
                'telephone' => $telephone,
                //'is_leader_in_dept'=> $a,
                //'avatar_mediaid'=> $a,
                'address' => $address,
                'main_department' => $mainDepartment,
            ];

            $res = $this->workWeChatUpdateUser($accessToken, ['body' => json_encode($user, JSON_UNESCAPED_UNICODE)]);
            if (isset($res['errcode']) && $res['errcode'] === 0) {
                $workWeChatUserInfo = $this->workWeChatUserInfo($accessToken, $workWechatUser->userid);
                if (isset($workWeChatUserInfo['code'])) {
                    return $workWeChatUserInfo;
                }
                if ($mobile != $workWeChatUserInfo['mobile']) {
                    return app($this->WorkWechatUserRepository)->deleteByWhere(['oa_id' => [$userId]]);
                } else {
                    $withUser = [
                        'oa_id' => $userId,
                        'userid' => $workWechatUser->userid,
                        'mobile' => $mobile,
                        'oa_user_account' => $userAccount,
                    ];
                    $where = ['oa_id' => $userId];
                    return app($this->WorkWechatUserRepository)->updateData($withUser, $where);
                }
            } else {
                return $res;
            }
        }
    }

    /**
     * 自动更新--添加用户
     * @param $userId
     * @param $accessToken
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChatAddUser($userId, $accessToken)
    {
//        $userData = $response->original['data'];
//        $userId = $userData->user_id;
        $updateUserOaUserInfo = app($this->userService)->getUserAllData($userId, ['user_id' => 'admin'], [])->toArray();
        $userAccount = $updateUserOaUserInfo['user_accounts'];
        $userName = $updateUserOaUserInfo['user_name'];
        $mobile = $updateUserOaUserInfo['user_has_one_info']['phone_number'];
        if (!$mobile) {
            return false;
        }
        $order = $updateUserOaUserInfo['list_number'];
        $mobile = $updateUserOaUserInfo['user_has_one_info']['phone_number'];
        $gender = $updateUserOaUserInfo['user_has_one_info']['sex'] == 1 ? $updateUserOaUserInfo['user_has_one_info']['sex'] : 2;
        $email = $updateUserOaUserInfo['user_has_one_info']['email'];
        $telephone = $updateUserOaUserInfo['user_has_one_info']['dept_phone_number'];
        $address = $updateUserOaUserInfo['user_has_one_info']['home_address'];
        $mainDepartment = $updateUserOaUserInfo['user_has_one_system_info']['dept_id'];
        //检查部门ID是否存在，不存在追踪上级部门，全部优先添加部门
        $check = $this->checkAndAddDept($mainDepartment, $accessToken);
        if (isset($check['code'])) {
            return $check;
        }
        $roleIniName = [];
        if (isset($updateUserOaUserInfo['user_has_many_role'])) {
            foreach ($updateUserOaUserInfo['user_has_many_role'] as $roleInfo) {
                $roleIniName[] = $roleInfo['has_one_role']['role_name'];
            }
        }
        $roleIniNameStr = implode(',', $roleIniName);
        // 部门负责人由于OA和企业微信规则不符合，所以不同步，OA内的部门负责人，可以是任意人员，企业则必须是部门内成员 --- OA内一个人员只能在一个部门内，企业微信一个人员可以多部门存在
        $user = [
            'userid' => $userId,
            'name' => $userName,
            'mobile' => $mobile,
            'department' => [$mainDepartment],
            'order' => [$order],
            'position' => $roleIniNameStr,
            'gender' => $gender,
            'email' => $email,
            'telephone' => $telephone,
            //'is_leader_in_dept'=> $a,
            //'avatar_mediaid'=> $a,
            'address' => $address,
            'main_department' => $mainDepartment,
        ];
        $res = $this->workWeChatAddUser($accessToken, ['body' => json_encode($user, JSON_UNESCAPED_UNICODE)]);
        if (isset($res['errcode']) && $res['errcode'] === 0) {
            app($this->WorkWechatUserRepository)->deleteByWhere(['oa_id'=>[$userId]]);
            $withUser = [
                'oa_id' => $userId,
                'userid' => $userId,
                'mobile' => $mobile,
                'oa_user_account' => $userAccount,
            ];
            return app($this->WorkWechatUserRepository)->insertData($withUser);
        } else {
            return $res;
        }

    }

    /**
     * 添加用户检查部门不存在则创建
     * @param $deptId
     * @param $accessToken
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function checkAndAddDept($deptId, $accessToken)
    {
        $deptInfo = $this->workWeChatDepartmentList($accessToken, $deptId);
        if (isset($deptInfo['errcode']) && $deptInfo['errcode'] === 0) {
            return true;
        } elseif (isset($deptInfo['code']) && $deptInfo['code'][0] == 60123) {
            return $this->addDeptArr($deptId, $accessToken);
        } else {
            return $deptInfo;
        }
    }

    /**
     * 自动更新--部门添加--检查部门及上级部门是否存在，不存在自动创建部门
     * @param $accessToken
     * @param $deptId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChatAddDept($deptId, $accessToken)
    {
        $deptInfo = $this->workWeChatDepartmentList($accessToken, $deptId);
        if (isset($deptInfo['errcode']) && $deptInfo['errcode'] === 0) {
            return ['code' => ["sync_dept_exist_yet", 'workwechat']];
        } elseif (isset($deptInfo['code']) && $deptInfo['code'][0] == 60123) {
            return $this->addDeptArr($deptId, $accessToken);
        } else {
            return $deptInfo;
        }
    }

    /**
     * 检查并添加当前部门及上级部门
     * @param $deptId
     * @param $accessToken
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function addDeptArr($deptId, $accessToken)
    {
        $deptInfo = app($this->departmentService)->getDeptDetail($deptId);
        if (isset($deptInfo->arr_parent_id)) {
            $arrParentId = explode(',', $deptInfo->arr_parent_id);
            foreach ($arrParentId as $parentId) {
                if ($parentId == 0) {
                    $parentId = 1;
                }
                $parentDeptInfo = $this->workWeChatDepartmentList($accessToken, $parentId);
                if (isset($parentDeptInfo['errcode']) && $parentDeptInfo['errcode'] === 0) {
                    continue;
                } elseif (isset($parentDeptInfo['code']) && $parentDeptInfo['code'][0] == 60123) {
                    $addDeptInfo = app($this->departmentService)->getDeptDetail($parentId);
                    if (isset($addDeptInfo['code'])) {
                        return $addDeptInfo;
                    }
                    $addRes = $this->basicAddDeptToWorkWeChat($addDeptInfo, $accessToken);
                    if (isset($addRes['code'])) {
                        return $addRes;
                    }
                } else {
                    return $parentDeptInfo;
                }
            }
            $res = $this->basicAddDeptToWorkWeChat($deptInfo, $accessToken);
            return $res;
        } else {
            return $deptInfo;
        }
    }

    /**
     * 自动更新--部门更新
     * @param $accessToken
     * @param $deptId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChatUpdateDept(string $accessToken, $deptId)
    {
        if ($deptId) {
            $deptInfo = $this->workWeChatDepartmentList($accessToken, $deptId);
            if (isset($deptInfo['errcode']) && $deptInfo['errcode'] === 0) {
                $deptInfo = app($this->departmentService)->getDeptDetail($deptId);
                if ($deptInfo->parent_id == 0) {
                    $parentId = 1;
                } else {
                    $parentId = $deptInfo->parent_id;
                }
                $data = [
                    'name' => $deptInfo->dept_name,
                    'parentid' => $parentId,
                    'order' => $deptInfo->dept_sort,
                    'id' => $deptInfo->dept_id,
                ];
                $jsonData = ['body' => json_encode($data)];
                //更新部门
                return $this->workWeChatUpdateDept($accessToken, $jsonData);
            } elseif (isset($deptInfo['code']) && $deptInfo['code'][0] == 60123) {
                $this->addDeptArr($deptId, $accessToken);
            } else {
                return $deptInfo;
            }
        } else {
            return ['code' => ['sync_dept_id_not_exist', 'workwechat']];
        }
    }

    /**
     * 自动更新--部门添加--基础
     * @param $deptInfo
     * @param $accessToken
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function basicAddDeptToWorkWeChat($deptInfo, $accessToken)
    {
        if ($deptInfo->parent_id == 0) {
            $parentId = 1;
        } else {
            $parentId = $deptInfo->parent_id;
        }
        $data = [
            'name' => $deptInfo->dept_name,
            'parentid' => $parentId,
            'order' => $deptInfo->dept_sort,
            'id' => $deptInfo->dept_id,
        ];
        $jsonData = ['body' => json_encode($data)];
        //创建部门
        return $this->workWeChatAddDept($accessToken, $jsonData);
    }

    /**
     * 自动更新--部门删除
     * @param $accessToken
     * @param $deptId
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function autoSyncToWorkWeChatDeleteDept($accessToken, $deptId)
    {
        if ($deptId) {
            return $this->workWeChatDeleteDept($accessToken, $deptId);
        } else {
            return ['code' => ['sync_dept_id_not_exist', 'workwechat']];
        }
    }

    /**
     * 获取日志列表
     * @param $param
     * @return array
     * @creatTime 2020/12/28 11:59
     * @author [dosy]
     */
    public function getSyncAttendanceLog($param)
    {
        $data = $this->response(app($this->workWechatSyncAttendanceLogRepository), 'getCount', 'getList', $this->parseParams($param));
        return $data;
    }

    /**下载当天日志
     * @param $param
     * @return array|\Symfony\Component\HttpFoundation\BinaryFileResponse
     * @creatTime 2020/12/30 16:20
     * @author [dosy]
     */
    public function downSyncAttendanceLog($param)
    {
        $dir = isset($param['time']) ? date('Ym', strtotime($param['time'])) : '';
        $fileName = isset($param['time']) ? date('Ymd', strtotime($param['time'])) : '';
        $fileName = base_path() . '/storage/logs/WorkWechatSyncAttendance/' . $dir . DIRECTORY_SEPARATOR . $fileName . '.log';
        return response()->download($fileName);
    }

    /**
     * @param $param
     * @return array|bool
     * @creatTime 2020/12/30 16:48
     * @author [dosy]
     */
    public function checkSyncAttendanceLog($param)
    {
        $dir = isset($param['time']) ? date('Ym', strtotime($param['time'])) : '';
        $fileName = isset($param['time']) ? date('Ymd', strtotime($param['time'])) : '';
        $fileName = base_path() . '/storage/logs/WorkWechatSyncAttendance/' . $dir . DIRECTORY_SEPARATOR . $fileName . '.log';
        if ((is_file($fileName))) {
            return true;
        } else {
            return ['code' => ['log_not_find', 'workwechat']];
        }
    }

    /**
     * 定时同步入口
     * @param $params
     * @param $own
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @creatTime 2020/12/28 11:00
     * @author [dosy]
     */
    public function timingSync($params, $own)
    {
        $log = [
            'sync_start_time' => date('Y-m-d  H:i:s'),
            'sync_type' => 'day',
            'sync_way' => 'timing',
            'sync_user' => '',
        ];
        $result = $this->syncAttendance($params, $own);
        if ($result) {
            $log['sync_result'] = trans('workwechat.sync_end');
        } else {
            if (isset($result['code'])) {
                $log['sync_result'] = trans($result['code'][1] . '.' . $result['code'][0]);
            } else {
                $log['sync_result'] = trans('workwechat.timing_fail');
            }
        }
        $log['sync_end_time'] = date('Y-m-d H:i:s');
        app($this->workWechatSyncAttendanceLogRepository)->insertData($log);
    }

    /**
     * 同步考勤
     * @param $params
     * @param $own
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @creatTime 2020/12/25 17:22
     * @author [dosy]
     */
    public function syncAttendance($params, $own)
    {
        // $this->getTimingData();
        $data = app($this->WorkWechatAppRepository)->getWorkWechatApp(['agent_type' => 2])->toArray();
        if (empty($data)) {
            return ['code' => ['0x040007', 'workwechat']];
        }
        $agentId = $data[0]['agentid'];
        //获取打卡应用token
        $accessToken = $this->getAccessToken($agentId);
        if (isset($accessToken['code'])) {
            return $accessToken;
        }
        if (!is_string($accessToken)) {
            return false;
        }
        if (isset($params['sync_type']) && $params['sync_type'] == 'day') {
            $startTime = strtotime(date('Y-m-d'));
            $endTime = strtotime(date('Y-m-d', strtotime('+1 day')));
        } elseif (isset($params['sync_type']) && $params['sync_type'] == 'month') {
            $time = $this->getThisMonth();
            $startTime = $time['start_month'];
            $endTime = $time['end_month'];
        } else {
            return ['code' => ['params_error', 'workwechat']];
        }
        //获取所有的用户
        $allUsers = app($this->WorkWechatUserRepository)->getAllInfo();
        $allUserIds = [];
        foreach ($allUsers as $userLink) {
            if (!empty($userLink['oa_id']) && !empty($userLink['userid']))
                $allUserIds[] = $userLink['userid'];
        }
        if (empty($allUserIds)) {
            return ['code' => ['not_find_sync_user_link', 'workwechat']];
        }
        //获取打卡规则
        $corpCheckinOption = $this->workWeChatGetCorpCheckinOption($accessToken, ['body' => '']);
        //dd($accessToken);
        if (isset($corpCheckinOption['code'])) {
            return $corpCheckinOption;
        }
        if (!isset($corpCheckinOption['group']) || empty($corpCheckinOption['group'])) {
            return ['code' => ['not_find_work_wechat_attendance_rule', 'workwechat']];
        }
        $groupArray = [];
        foreach ($corpCheckinOption['group'] as $option) {
            $groupArray[$option['groupid']] = $option['grouptype'];
        }
        Queue::push(new SyncWorkWeChatAttendanceJob($allUserIds, $accessToken, $startTime, $endTime, $groupArray, $params, $own['user_id']));
        // $this->syncAttendanceJob($allUserIds, $accessToken, $startTime, $endTime, $groupArray, $params, $own['user_id']);
        return true;
    }

    /**
     * 考勤同步主体
     * @param $allUserIds
     * @param $accessToken
     * @param $startTime
     * @param $endTime
     * @param $groupArray
     * @param $params
     * @param $userId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @creatTime 2020/12/29 19:17
     * @author [dosy]
     */
    public function syncAttendanceJob($allUserIds, $accessToken, $startTime, $endTime, $groupArray, $params, $userId)
    {
        $fileName = '';
        if (isset($params['sync_way']) && $params['sync_way'] == 'manual') {
            $log = [
                'sync_start_time' => date('Y-m-d  H:i:s'),
                'sync_type' => $params['sync_type'],
                'sync_way' => 'manual',
                'sync_user' => $userId,
            ];
        }
        log_write_to_file('******同步开始******获取企业微信考勤数据开始******', ['dirName' => 'WorkWechatSyncAttendance', 'fileName' => $fileName]);
        $workWechatAttendanceData = [];
        if (count($allUserIds) > 100) {
            $userIds = array_chunk($allUserIds, 20);
            foreach ($userIds as $user_id) {
                $tempData = $this->getAttendanceData($accessToken, $startTime, $endTime, $user_id);
                $workWechatAttendanceData = array_merge($workWechatAttendanceData, $tempData);
            }
        } else {
            $workWechatAttendanceData = $this->getAttendanceData($accessToken, $startTime, $endTime, $allUserIds);
        }
        if (empty($workWechatAttendanceData)) {
            log_write_to_file('******未查询到企业微信打卡数据******同步结束******', ['dirName' => 'WorkWechatSyncAttendance', 'fileName' => $fileName]);
            if (isset($params['sync_way']) && $params['sync_way'] == 'manual') {
                $log['sync_end_time'] = date('Y-m-d H:i:s');
                $log['sync_result'] = trans('workwechat.not_find_work_wechat_attendance_data');
                app($this->workWechatSyncAttendanceLogRepository)->insertData($log);
            }
            return ['code' => ['not_find_work_wechat_attendance_data', 'workwechat']];
        }
        log_write_to_file('******获取企业微信考勤数据结束******导入考勤数据到OA开始', ['dirName' => 'WorkWechatSyncAttendance', 'fileName' => $fileName]);
        //数据进入内部处理
        $this->dealAttendanceData($workWechatAttendanceData, $groupArray, [$startTime, $endTime], $params['sync_type']);
        if (isset($params['sync_way']) && $params['sync_way'] == 'manual') {
            $log['sync_end_time'] = date('Y-m-d H:i:s');
            $log['sync_result'] = trans('workwechat.sync_end');
            app($this->workWechatSyncAttendanceLogRepository)->insertData($log);
        }
        log_write_to_file('******导入考勤数据到OA结束******同步结束******', ['dirName' => 'WorkWechatSyncAttendance', 'fileName' => $fileName]);
    }

    /**
     * 获取考勤数据
     * @param $accessToken
     * @param $startTime
     * @param $endTime
     * @param $userIds
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @creatTime 2020/12/9 11:56
     * @author [dosy]
     */
    public function getAttendanceData($accessToken, $startTime, $endTime, $userIds)
    {
        $requestData = [
            'opencheckindatatype' => 1, //打卡类型。1：上下班打卡；2：外出打卡；3：全部打卡
            'starttime' => $startTime,
            'endtime' => $endTime,
            'useridlist' => $userIds,
            //'useridlist' => ['DongSiYao'],
        ];
        $jsonData = ['body' => json_encode($requestData)];
        $LogInfo = [
            'work_wechat_api_request_data' => $requestData,
            'work_wechat_api_request_time' => date('Y-m-d H:i:s'),
        ];
        $resultData = $this->workWeChatGetCheckInData($accessToken, $jsonData);
        $LogInfo['work_wechat_api_data'] = $resultData;
        $LogInfo['work_wechat_api_result_time'] = $resultData;
        $fileName = '';
        log_write_to_file($LogInfo, ['dirName' => 'WorkWechatSyncAttendance', 'fileName' => $fileName]);
        if (isset($resultData['checkindata'])) {
            return $resultData['checkindata'];
        }
    }

    /**
     * 处理考勤数据
     * @param $attendanceData
     * @param $groupArray
     * @param $startEndTime
     * @param $syncType
     * @return bool
     * @creatTime 2020/12/29 19:18
     * @author [dosy]
     */
    public function dealAttendanceData($attendanceData, $groupArray, $startEndTime, $syncType)
    {
        $attendance = [];
        $attendanceRecord = [];
        foreach ($attendanceData as $value) {
            $userLink = app($this->WorkWechatUserRepository)->getOneFieldInfo(['userid' => $value['userid']]);
            $oa_id = $userLink->oa_id;
            $date = date("Y-m-d", $value['checkin_time']);
            //固定班
            if (isset($groupArray[$value['groupid']]) && $groupArray[$value['groupid']] == 1) {
                $signNumber = 1;
                if ($value['checkin_type'] == '上班打卡') {
                    if (isset($attendance[$oa_id][$date])) {
                        $attendance[$oa_id][$date][0]['sign_in_time'] = date("Y-m-d H:i:s", $value['checkin_time']);
                        $attendance[$oa_id][$date][0]['platform'] = 5;
                        $attendance[$oa_id][$date][0]['in_address'] = $value['location_detail'];
                    } else {
                        $attendance[$oa_id][$date][] = ['sign_date' => $date, 'sign_nubmer' => $signNumber, 'sign_in_time' => date("Y-m-d H:i:s", $value['checkin_time']), 'sign_out_time' => '', 'platform' => 5, 'in_address' => $value['location_detail'], 'out_address' => ''];
                    }
                    $attendanceRecord[$oa_id][] = ['sign_type' => 'sign_in_data', 'type' => 1, 'checktime' => date("Y-m-d H:i:s", $value['checkin_time']), 'sign_date' => date("Y-m-d", $value['checkin_time']), 'address' => $value['location_detail']];
                }
                if ($value['checkin_type'] == '下班打卡') {
                    if (isset($attendance[$oa_id][$date])) {
                        $attendance[$oa_id][$date][0]['sign_out_time'] = date("Y-m-d H:i:s", $value['checkin_time']);
                        $attendance[$oa_id][$date][0]['platform'] = 5;
                        $attendance[$oa_id][$date][0]['out_address'] = $value['location_detail'];
                    } else {
                        $attendance[$oa_id][$date][] = ['sign_date' => $date, 'sign_times' => $signNumber, 'sign_in_time' => '', 'sign_out_time' => date("Y-m-d H:i:s", $value['checkin_time']), 'platform' => 5, 'in_address' => '', 'out_address' => $value['location_detail']];
                    }
                    $attendanceRecord[$oa_id][] = ['sign_type' => 'sign_out_data', 'type' => 2, 'checktime' => date("Y-m-d H:i:s", $value['checkin_time']), 'sign_date' => date("Y-m-d", $value['checkin_time']), 'address' => $value['location_detail']];

                }
            }
        }
//        if ($syncType == 'month') {
//        } else {
//            $startEndTime[0] = date("Y-m-d", $startEndTime[0]);
//            $startEndTime[1] = date("Y-m-d", $startEndTime[1]);
//        }
        $startEndTime[0] = date("Y-m-d", $startEndTime[0]);
        $startEndTime[1] = date("Y-m-d", $startEndTime[1]);
//       $attendance['WV00000009'] = $attendance['admin'];
//        $batchSignData = app($this->attendanceMachineService)->parseRecordExist($attendance);
        //数据进入OA考勤
        $res = app($this->attendanceService)->batchSign($attendance, $startEndTime[0], $startEndTime[1]);
        foreach ($attendanceRecord as $user => $record) {
            foreach ($record as $value) {
                $recordLog = [
                    'checktime' => $value['checktime'],
                    'user_id' => $user,
                    //'user_id' => 'WV00000009',
                    'sign_date' => $value['sign_date'] ?? '',
                    'type' => $value['type'],
                    'platform' => 5,
                    'address' => $value['address'] ?? '',
                ];
                app($this->attendanceMachineService)->checkAndInsertSimpleRecord($recordLog, 'workWechat');
            }
        }
        return true;
    }

    /**
     * 获取当前月
     * @return array
     * @creatTime 2020/12/29 19:18
     * @author [dosy]
     */
    public function getThisMonth()
    {
        $y = date("Y", time()); //年
        $m = date("m", time()); //月
        $t0 = date('t'); // 本月一共有几天
        $r = array();
        $r['start_month'] = mktime(0, 0, 0, $m, 1, $y); // 创建本月开始时间
        $r['end_month'] = mktime(23, 59, 59, $m, $t0, $y); // 创建本月结束时间
        return $r;
    }

    /**
     * 获取当前时间
     * @return array
     * @creatTime 2020/12/29 19:18
     * @author [dosy]
     */
    public function getTimingData()
    {
        $data = app($this->workWechatTimingSyncAttendanceRepository)->getFieldInfo([]);
        $result = ['is_start' => 0, 'time' => []];
        if ($data) {
            foreach ($data as $value) {
                $result['is_start'] = $value['is_start'];
                $result['time'][] = $value['sync_time'];
            }
        }
        return $result;
    }

    /**
     * 检查表是否被创建
     */
    public function checkTable()
    {
       if (Schema::hasTable('work_wechat_timing_sync_attendance')){
            return true;
       }else {
            return false;
       }
    }

    /********************企业微信 外部联系人**********************/

    /**
     * 通过企业微信外部联系人userid获取客户联系人
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function getCustomerLinkman($params)
    {
        if (isset($params['external_contact_user_id']) && !empty($params['external_contact_user_id'])) {
            $info = app($this->workWechatWithCustomerRepository)->getCustomerLinkman($params['external_contact_user_id']);
            if (isset($info->customer_linkman_id)) {
                if ($result = app($this->linkmanRepository)->getDetail($info->customer_linkman_id)) {
                    $info->customer_id = $result->customer_id;
                    return $info;
                }
                return ['code' => ['not_customer_linkmen', 'workwechat']];
            }
        } else {
            return ['code' => ['params_error', 'workwechat']];
        }

    }

    /**
     * 保存企业微信外部联系人和客户联系人绑定关系
     * @param $params
     * @param $user
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function saveWorkWechatWithCustomer($params, $user)
    {
        if (isset($params['external_contact_user_id']) && !empty($params['external_contact_user_id']) && isset($params['customer_linkman_id']) && !empty($params['customer_linkman_id'])) {
            //检查客户联系人是否已经被绑定
            $check = app($this->workWechatWithCustomerRepository)->getDataByCustomerLinkmanId($params['customer_linkman_id']);
            if ($check) {
                if (isset($check->external_contact_user_id) && !empty($check->external_contact_user_id)) {
                    $detail = $this->getWorkWeChatExternalContactDetail($check->external_contact_user_id);
                    if (isset($detail['code'])) {
                        //客户被删除了
                        if ($detail['code'][0] == '84061') {
                            //取消绑定
                            $this->deleteWorkWechatWithCustomer($params, $user);
                            return $this->saveWorkWechatWithCustomer($params, $user);
                        } else {
                            return $detail;
                        }
                    }
                    $userName = $detail['follow_user']['remark'] ?? $detail['external_contact']['name'];
                    return ['code' => ['contact_is_bound', 'workwechat'], 'dynamic' => trans('workwechat.contact_is_bound') . $userName];
                } else {
                    return ['code' => ['data_error', 'workwechat']];
                }
            }
            $info = app($this->workWechatWithCustomerRepository)->getCustomerLinkman($params['external_contact_user_id']);
            $data = [
                'external_contact_user_id' => $params['external_contact_user_id'],
                'customer_linkman_id' => $params['customer_linkman_id'],
            ];

            if ($info) {
                $where = [
                    'external_contact_user_id' => $params['external_contact_user_id']
                ];
                $res = app($this->workWechatWithCustomerRepository)->updateData($data, $where);
            } else {
                $res = app($this->workWechatWithCustomerRepository)->insertData($data);
            }
            if ($res) {
                $result = app($this->linkmanService)->bindingLinkman($data, $data['customer_linkman_id'], $user);
                if (isset($result['code'])) {
                    return $result;
                }
            }
            return $res;
        }
    }

    /**
     * 删除企业微信外部联系人和客户联系人绑定关系
     * @param $params
     * @param $user
     * @return mixed
     * @author [dosy]
     */
    public function deleteWorkWechatWithCustomer($params, $user)
    {
        if (isset($params['customer_linkman_id']) && !empty($params['customer_linkman_id'])) {
            $where['customer_linkman_id'] = [$params['customer_linkman_id']];
            $res = app($this->workWechatWithCustomerRepository)->deleteByWhere($where);
            app($this->linkmanService)->cancelBinding($params['customer_linkman_id'], $user);
            return $res;
        }
    }

    /**
     * 获取当前登录用户企业微信外部联系人列表
     * @param $params
     * @param $user
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatExternalContactList($params, $user)
    {
        $wechat = $this->getWorkWechat();
        if (!isset($wechat->sms_agent) || empty($wechat->sms_agent)) {
            return ['code' => ['not_setting_workwechat', 'workwechat']];
        }
        //获取user_id对应的企业微信user_id
        $userLink = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($user['user_id']);
        if (!$userLink) {
            return ['code' => ['not_find_workwechat_user', 'workwechat']];
        }
        // 获取access_token
        $accessToken = $this->getAccessToken($wechat->sms_agent);
        if (!is_string($accessToken)) {
            return $accessToken;
        }
        $allList = $this->recursiveWorkWeChatExternalContact($userLink['userid'], $accessToken, '', []);
        return $allList;
//       $postParam=[
//          // 'userid'=>$user['user_id'],
//           'userid'=>'DongSiYao',
//           'cursor'=>'6eRXsfAJSUUBABYCWDQEVtMoa_BVuKRNMXXAM_LYSlE',
//           'limit'=>1,
//       ];
//       $jsonData = ['body' => json_encode($postParam)];
//       $limitList = $this->workWeChatExternalContact($accessToken,$jsonData);
//        dd($limitList);
//       return $limitList;
    }

    /**
     * 递归获取当前user的企业微信所有外部联系人
     * @param $userId
     * @param $accessToken
     * @param string $cursor
     * @param array $allList
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function recursiveWorkWeChatExternalContact($userId, $accessToken, $cursor = '', $allList = [])
    {
        $postParam = [
            'userid' => $userId,
            // 'userid' => 'DongSiYao',
            'cursor' => $cursor,
            'limit' => 100,
        ];
        $jsonData = ['body' => json_encode($postParam)];
        $limitList = $this->workWeChatExternalContact($accessToken, $jsonData);
        if (isset($limitList['code'])) {
            return $limitList;
        }
        if (isset($limitList['external_contact_list']) && empty($limitList['external_contact_list'])) {
            return $allList;
        }
        $allList = array_merge($allList, $limitList['external_contact_list']);
        if (isset($limitList['external_contact_list']) && isset($limitList['next_cursor']) && !empty($limitList['next_cursor'])) {
            // $allList[] = $limitList['external_contact_list'];
            return $this->recursiveWorkWeChatExternalContact($userId, $accessToken, $limitList['next_cursor'], $allList);
        } else {
            return $allList;
        }
    }

    /**
     * 获取企业微信客户详情
     * @param $externalUserId
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatExternalContactDetail($externalUserId)
    {
        $wechat = $this->getWorkWechat();
        if (!isset($wechat->sms_agent) || empty($wechat->sms_agent)) {
            return ['code' => ['not_setting_workwechat', 'workwechat']];
        }
        // 获取access_token
        $accessToken = $this->getAccessToken($wechat->sms_agent);
        if (!is_string($accessToken)) {
            return $accessToken;
        }
        $detail = $this->workWeChatExternalContactDetail($accessToken, $externalUserId);
        return $detail;
    }

    /**
     * 获取企业微信当前用户群主所有客户群详细
     * @param $user
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatGroupChatListDetail($user)
    {
        $wechat = $this->getWorkWechat();
        if (!isset($wechat->sms_agent) || empty($wechat->sms_agent)) {
            return ['code' => ['not_setting_workwechat', 'workwechat']];
        }
        //获取user_id对应的企业微信user_id
        $userLink = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($user['user_id']);
        if (!$userLink) {
            return ['code' => ['not_find_workwechat_user', 'workwechat']];
        }
        // 获取access_token
        $accessToken = $this->getAccessToken($wechat->sms_agent);
        if (!is_string($accessToken)) {
            return $accessToken;
        }
        $allList = $this->getWorkWeChatGroupChatList($userLink, $accessToken);
        if (isset($allList['code'])) {
            return $allList;
        }
        $allListDetail = [];
        foreach ($allList['group_chat_list'] as $chat) {
            $detail = $this->getWorkWeChatGroupChatDetail($chat['chat_id'], $accessToken);
            if (isset($detail['code'])) {
                return $detail;
            } else {
                $allListDetail[] = $detail['group_chat'];
            }
        }
        return $allListDetail;
    }

    /**
     * 获取当前用户企业微信客户群列表
     * @param $userLink
     * @param $accessToken
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatGroupChatList($userLink, $accessToken)
    {
        //注：此处只获取了1000条群数据，超过未获取
        $postParam = [
            'status_filter' => 0,
            'owner_filter' => [
                "userid_list" => [$userLink['userid']],
            ],
            'limit' => 1000
        ];
        $jsonData = ['body' => json_encode($postParam)];
        $allList = $this->workWeChatGroupChatList($accessToken, $jsonData);
        return $allList;
    }

    /**
     * 获取企业微信客户群详情
     * @param $chatId
     * @param $accessToken
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function getWorkWeChatGroupChatDetail($chatId, $accessToken)
    {
        $postParam = [
            'chat_id' => $chatId
        ];
        $jsonData = ['body' => json_encode($postParam)];
        $detail = $this->workWeChatGroupChatDetail($accessToken, $jsonData);
        return $detail;
    }

    /**
     * 保存企业微信客户群和客户关系
     * @param $params
     * @param $user
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function saveWorkWeChatGroupWithCustomer($params, $user)
    {
        if (isset($params['group_chat_id']) && !empty($params['group_chat_id']) && isset($params['customer_id']) && !empty($params['customer_id'])) {
            //检查绑定操作是否为群主，非群主不可执行绑定
            $wechat = $this->getWorkWechat();
            if (!isset($wechat->sms_agent) || empty($wechat->sms_agent)) {
                return ['code' => ['not_setting_workwechat', 'workwechat']];
            }
            // 获取access_token
            $accessToken = $this->getAccessToken($wechat->sms_agent);
            if (!is_string($accessToken)) {
                return $accessToken;
            }
            //获取user_id对应的企业微信user_id
            $userLink = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($user['user_id']);
            if (!$userLink) {
                return ['code' => ['not_find_user_link', 'workwechat']];
            }
            $detail = $this->getWorkWeChatGroupChatDetail($params['group_chat_id'], $accessToken);
            if (isset($detail['code'])) {
                return $detail;
            }
            if ($detail['group_chat']['owner'] != $userLink->userid) {
                return ['code' => ['only_support_owner', 'workwechat']];
            }

            $info = app($this->workWechatGroupWithCustomerRepository)->getCustomer($params['group_chat_id']);
            $data = [
                'group_chat_id' => $params['group_chat_id'],
                'customer_id' => $params['customer_id'],
            ];
            if ($info) {
                $where = [
                    'group_chat_id' => $params['group_chat_id']
                ];
                $res = app($this->workWechatGroupWithCustomerRepository)->updateData($data, $where);
            } else {
                $res = app($this->workWechatGroupWithCustomerRepository)->insertData($data);
            }
            if ($res) {
                $result = app($this->customerService)->chatBinding($data, $data['customer_id'], $user);
                if (isset($result['code'])) {
                    return $result;
                }
            }
            return $res;
        }
    }

    /**
     * 删除企业微信客户群绑定
     * @param $params
     * @param $user
     * @return array|bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function deleteWorkWechatGroupWithCustomer($params, $user)
    {
        if (isset($params['customer_id']) && !empty($params['customer_id']) && isset($params['group_chat_id']) && !empty($params['group_chat_id'])) {
            //检查绑定操作是否为群主，非群主不可执行解除绑定
            $wechat = $this->getWorkWechat();
            if (!isset($wechat->sms_agent) || empty($wechat->sms_agent)) {
                return ['code' => ['not_setting_workwechat', 'workwechat']];
            }
            // 获取access_token
            $accessToken = $this->getAccessToken($wechat->sms_agent);
            if (!is_string($accessToken)) {
                return $accessToken;
            }
            //获取user_id对应的企业微信user_id
            $userLink = app($this->WorkWechatUserRepository)->getWorkWechatUserIdById($user['user_id']);
            if (!$userLink) {
                return ['code' => ['not_find_user_link', 'workwechat']];
            }
            $detail = $this->getWorkWeChatGroupChatDetail($params['group_chat_id'], $accessToken);
            if (isset($detail['code'])) {
                return $detail;
            }
            if ($detail['group_chat']['owner'] != $userLink->userid) {
                return ['code' => ['only_support_owner', 'workwechat']];
            }
            $where = [
                'customer_id' => [$params['customer_id']],
                'group_chat_id' => [$params['group_chat_id']],
            ];
            $res = app($this->workWechatGroupWithCustomerRepository)->deleteByWhere($where);
            app($this->customerService)->cancelChatBinding($where, $user);
            return $res;
        }
    }

    /**
     * 通过企业微信客户群chatId获取客户联系人
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function getCustomer($params)
    {
        if (isset($params['group_chat_id']) && !empty($params['group_chat_id'])) {
            $info = app($this->workWechatGroupWithCustomerRepository)->getCustomer($params['group_chat_id']);
            if (isset($info->customer_id)) {
                if ($result = app($this->customerRepository)->getCustomerName($info->customer_id)) {
                    return $info;
                }
                return ['code' => ['not_customer', 'workwechat']];
            }
        } else {
            return ['code' => ['params_error', 'workwechat']];
        }

    }
}
