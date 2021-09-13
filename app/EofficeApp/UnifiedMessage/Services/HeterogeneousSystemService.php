<?php

namespace App\EofficeApp\UnifiedMessage\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 统一消息 -- 异构系统服务
 * Class HeterogeneousSystemService
 * @package App\EofficeApp\UnifiedMessage\Services
 */
class HeterogeneousSystemService extends BaseService
{
    public $iniParam;
    private $heterogeneousSystemRepository;
    private $heterogeneousSystemMessageTypeRepository;
    private $heterogeneousSystemUserBondingRepository;
    private $heterogeneousSystemIntegrationLogRepository;
    private $ssoLoginRepository;
    private $systemRemindsRepository;
    private $heterogeneousSystemMessageRepository;
    private $messageDataService;
    private $langRepository;

    public function __construct()
    {
        $this->heterogeneousSystemRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemRepository';
        $this->heterogeneousSystemMessageTypeRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemMessageTypeRepository';
        $this->heterogeneousSystemUserBondingRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemUserBondingRepository';
        $this->heterogeneousSystemMessageRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemMessageRepository';
        $this->heterogeneousSystemIntegrationLogRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemMessageRepository';
        $this->ssoLoginRepository = 'App\EofficeApp\Sso\Repositories\SsoLoginRepository';
        $this->systemRemindsRepository = 'App\EofficeApp\System\Remind\Repositories\SystemRemindsRepository';
        $this->messageDataService = 'App\EofficeApp\UnifiedMessage\Services\MessageDataService';
        $this->langRepository = 'App\EofficeApp\Lang\Repositories\LangRepository';
    }

    public function ini()
    {
        $this->iniParam = [
            'system_code' => 'biaoshi',
            'for_short' => 'biaoshi',
            'full_title' => 'biaoshi',
            'security_ip' => 'biaoshi',
            'pc_address_prefix' => 'biaoshi',
            'pc_transfer_page' => 'biaoshi',
            'app_address_prefix' => 'biaoshi',
            'app_transfer_page' => 'biaoshi',
            'is_receive_data' => 'biaoshi',
            'associated_user_way' => 'biaoshi',
            'attachment_id' => 'biaoshi',
            'associated_external_system_id' => 'biaoshi',
        ];
    }

    /**
     * 注册异构系统标识
     * @param $userId
     * @return string
     * @author [dosy]
     */
    public function registerHeterogeneousSystemCode($userId)
    {
        $len = 32;
        $systemCode = getRandomStr($len);
        // Redis::set($systemCode,$userId,'ex','3600','nx');
        return $systemCode;
    }

    /**
     * 刷新异构系统标识
     * @param $param
     * @param $userId
     * @return string
     * @author [dosy]
     */
    public function refreshHeterogeneousSystemCode($param, $userId)
    {
        if (isset($param['system_code']) && !empty($param['system_code'])) { //M9C4d90QL5obrrZkfDNYMNgQpCiwRj30
            $systemCode = $this->registerHeterogeneousSystemCode($userId);
            $heterogeneousSystem = app($this->heterogeneousSystemRepository)->getData(['system_code' => $param['system_code']]);
            $heterogeneousSystemUserBonding = app($this->heterogeneousSystemUserBondingRepository)->getData(['heterogeneous_system_code' => $param['system_code']]);
            if (!empty($heterogeneousSystem)) {
                //更新异构系统表的system_code
                $res = app($this->heterogeneousSystemRepository)->updateData(['system_code' => $systemCode], ['system_code' => $param['system_code']]);
            }
            if (!empty($heterogeneousSystemUserBonding)) {
                $res = app($this->heterogeneousSystemUserBondingRepository)->updateData(['heterogeneous_system_code' => $systemCode],
                    ['heterogeneous_system_code' => $param['system_code']]);
            }
            return $systemCode;
        } else {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }

    }

    /**
     * 注册异构系统
     * @param $params
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function registerHeterogeneousSystem($params, $userId)
    {
        //存在id 是编辑
        if (isset($params['id'])) {
            return $this->editHeterogeneousSystem($params['id'], $params);
        }
        //去空格
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = trim($value);
            } else {
                continue;
            }
        }
        if (!isset($params['system_code']) && empty($params['system_code']) && !is_string($params['system_code'])) {
            return ['code' => ['0x000002', 'unifiedMessage']];
        }
        //检查消息类型是否重复
        $uniqueMessageType = [];
        if (isset($params['message_type']) && !empty($params['message_type'])) {
            foreach ($params['message_type'] as $key => $value) {
                if (in_array($value['message_type'], $uniqueMessageType)) {
                    return ['code' => ['0x000017', 'unifiedMessage']];
                } else {
                    $uniqueMessageType[] = $value['message_type'];
                }
            }
        } else {
            return ['code' => ['0x000005', 'unifiedMessage']];
        }
        $params['associated_external_system_id'] = $params['sso_id'] ?? '';
        $data = array_intersect_key($params, array_flip(app($this->heterogeneousSystemRepository)->getTableColumns()));
        $result = app($this->heterogeneousSystemRepository)->insertData($data);
        if (isset($result['id'])) {
            $result['message_type'] = $params['message_type'];
            //检查是否存在消息类型，不存在就删除提前新增的异构系统配置
            /*
             * 消息提醒规则
             * remind_menu = 'heterogeneous_'.异构系统ID号
             * reming_type = 消息类型ID号
             */
            $prefix = 'heterogeneous_';
            if (isset($params['message_type']) && !empty($params['message_type'])) {
                foreach ($params['message_type'] as $key => $value) {
                    if (isset($value['message_type']) && !empty($value['message_type'])) {
                        $remindMenu = $prefix . $result['id'];
                        $params['message_type'][$key] = [
                            'heterogeneous_system_id' => $result['id'],
                            'message_type' => trim($value['message_type']),
                            //用于消息提醒
                            'remind_menu' => $remindMenu,
                            // 'remind_type' => $res['id'], =>这个不要了，类型直接取用id值
                            'remind_link_src' => 'heterogeneous_system_src',
                        ];
                        $res = app($this->heterogeneousSystemMessageTypeRepository)->insertData($params['message_type'][$key]);
                        $langRes = $this->addMessageRemind($remindMenu, $res['id'], trim($value['message_type']), $params['system_name'], trim($value['remind_setting']));
                        if (isset($langRes['code'])) {
                            return $langRes;
                        }
                        $params['message_type'][$key]['id'] = $res['id'];
                    }
                }
                $result['message_type'] = $params['message_type'];
            } else {
                app($this->heterogeneousSystemRepository)->deleteById($result['id']);
                return ['code' => ['0x000005', 'unifiedMessage']];
            }
            //绑定账号 -- 外部系统
            if (isset($params['associated_user_way']) && $params['associated_user_way'] == 2) {
                if (isset($params['sso_id']) && !empty($params['sso_id'])) {
                    $where = ['search' => ['sso_id' => [$params['sso_id'], '=']]];
                    $userData = app($this->ssoLoginRepository)->getSsoLoginList($where);
                    $bindUser = [];
                    foreach ($userData as $key => $user) {
                        if (isset($user['sso_login_name']) && !empty($user['sso_login_name']) && isset($user['sso_login_user_id']) && !empty($user['sso_login_user_id'])) {
                            $bindUser = [
                                'heterogeneous_system_code' => $params['system_code'],
                                'heterogeneous_system_user_id' => $user['sso_login_name'],
                                'oa_user_id' => $user['sso_login_user_id'],
                            ];
                            app($this->heterogeneousSystemUserBondingRepository)->insertData($bindUser);
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * sso单点登录编辑新增用户绑定【外部账号为空不添加】
     * @param $userInfo 原来的数据
     * @param $userData 最新的数据
     * @author [dosy]
     */
    public function updateUserBinding($userInfo, $userData)
    {
        if (!isset($userInfo[0]['sso_id']) || !isset($userInfo[0]['sso_login_name']) || !isset($userInfo[0]['sso_login_user_id']) || !isset($userData['sso_login_name'])) {
            return;
        }
        //原来的外部账号是空，最新保存也是空，直接返回不做任何操作
        if(empty($userData['sso_login_name'])&&empty($userInfo[0]['sso_login_name'])){
            return;
        }
        $where = [
            'search' => [
                'associated_user_way' => [[2], '='],
                'associated_external_system_id' => [[$userInfo[0]['sso_id']], '=']
            ]
        ];
        $heterogeneousSystem = app($this->heterogeneousSystemRepository)->getHeterogeneousSystemList($where);
        if (!empty($heterogeneousSystem)) {
            $systemCode = [];
            foreach ($heterogeneousSystem as $value) {
                $systemCode[] = $value['system_code'];
                $userBindingWhere['search'] = [
                    'heterogeneous_system_code' => [[$value['system_code']], '='],
                    'heterogeneous_system_user_id' => [[$userInfo[0]['sso_login_name']], '='],
                    'oa_user_id' => [[$userInfo[0]['sso_login_user_id']], '=']
                ];
                $heterogeneousSystemUserBonding = app($this->heterogeneousSystemUserBondingRepository)->getHeterogeneousSystemUserBondingList($userBindingWhere);
                //dd($heterogeneousSystemUserBonding);
                if (!empty($heterogeneousSystemUserBonding)) {
                    //原来的外部账号是非空，最新保存是空---删除操作
                    if(empty($userData['sso_login_name'])&&!empty($userInfo[0]['sso_login_name'])){
                        $deleteByWhere = [
                            'heterogeneous_system_code' => [$value['system_code']],
                            'heterogeneous_system_user_id' => [$userInfo[0]['sso_login_name']],
                            'oa_user_id' => [$userInfo[0]['sso_login_user_id']]
                        ];
                        app($this->heterogeneousSystemUserBondingRepository)->deleteByWhere($deleteByWhere);
                    }else{
                        //原来的外部账号是非空，最新保存是非空---更新操作
                        $userBindingUpdateParam = [
                            'heterogeneous_system_user_id' => $userData['sso_login_name']
                        ];
                        app($this->heterogeneousSystemUserBondingRepository)->updateData($userBindingUpdateParam, ['id' => $heterogeneousSystemUserBonding[0]['id']]);
                    }

                } else {
                    //原来的外部账号是空，最新保存是非空---新增操作
                    $userBindingInsertParam = [
                        'heterogeneous_system_code' => $value['system_code'],
                        'heterogeneous_system_user_id' => $userData['sso_login_name'],
                        'oa_user_id' => $userInfo[0]['sso_login_user_id'],
                    ];
                    app($this->heterogeneousSystemUserBondingRepository)->insertData($userBindingInsertParam);
                }
            }

        }
    }
    /**
     * 编辑异构系统
     * @param $id
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function editHeterogeneousSystem($id, $params)
    {
        //去空格
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = trim($value);
            } else {
                continue;
            }
        }
        //检查至少存在一个消息类型
        $oldMessageType = [];
        $uniqueMessageType = [];
        $checkMessageType = false;
        if (isset($params['message_type']) && !empty($params['message_type'])) {
            foreach ($params['message_type'] as $key => $value) {
                if (isset($value['message_type']) && !empty($value['message_type'])) {
                    $uniqueMessageType[] = $value['message_type'];
                    $checkMessageType = true;
                    if (isset($value['id'])) {
                        $oldMessageType[] = $value['id'];
                    }
                }
            }
        }
        if (!$checkMessageType) {
            return ['code' => ['0x000005', 'unifiedMessage']];
        }
        //检查消息类型不重复
        if (count($uniqueMessageType) != count(array_unique($uniqueMessageType))) {
            return ['code' => ['0x000017', 'unifiedMessage']];
        }
        $params['associated_external_system_id'] = $params['sso_id'] ?? null;
        //获取异构系统原有设置
        //$heterogeneousSystem = app($this->heterogeneousSystemRepository)->getData(['id' => $id]);
        //更新异构系统
        $data = array_intersect_key($params, array_flip(app($this->heterogeneousSystemRepository)->getTableColumns()));
        $result = app($this->heterogeneousSystemRepository)->updateData($data, ['id' => $id], ['id', 'system_code']);
        if ($result) {
            //获取当前异构系统所有消息类型
            $default = [
                'fields' => ['id'],
                'search' => ['heterogeneous_system_id' => [$id, '=']],
            ];
            $allMessageType = [];
            $messageTypeList = app($this->heterogeneousSystemMessageTypeRepository)->getHeterogeneousSystemMessageTypeList($default);
            foreach ($messageTypeList as $value) {
                $allMessageType[] = $value['id'];
            }
            //比较获取新编辑要被删除的消息类型（没有动的消息类型不能删除，里面绑定这消息类型）
            $delMessageTypeIds = array_diff($allMessageType, $oldMessageType);
            if (!empty($delMessageTypeIds)) {
                //删除当前异构系统旧的消息类型
                app($this->heterogeneousSystemMessageTypeRepository)->deleteByWhere(['id' => [$delMessageTypeIds, 'in']]);
                //删除改消息类型的消息提醒类 --删除system_reminds,lang_en和lang_zh_cn这里面的数据
                $this->delSystemRemind($id, $delMessageTypeIds);
            }
            //重新更新添加异构系统消息类型
            foreach ($params['message_type'] as $key => $value) {
                if (isset($value['id'])) {
                    //更新消息类型的名字，该名称不参与消息提醒，无需更新
                    app($this->heterogeneousSystemMessageTypeRepository)->updateData($value, ['id' => $value['id']], ['id', 'heterogeneous_system_id']);
                    //更新消息提醒设置
                    $remindWhere = [
                        'remind_menu' => $value['remind_menu'],
                        'remind_type' => $value['id']
                    ];
                    $newRemindSelect['reminds_select'] = $value['remind_setting'];
                    app($this->systemRemindsRepository)->updateData($newRemindSelect, $remindWhere);
                    //获取到id,去更新多语言表
                    $langId = app($this->systemRemindsRepository)->getColumns(['id'], $remindWhere);
                    //提醒名称更新 --- 提醒名称 -》 消息类型值
                    $langReceiveKey = $value['remind_menu'] . "_" . $value['id'] . "_" . $langId[0]; // 'heterogeneous_16_24_230'
                    $newLangValue = [
                        'lang_value' => trim($value['message_type'])
                    ];
                    $LangWhere = [
                        'lang_key' => [$langReceiveKey]
                    ];
                    app($this->langRepository)->updateData($newLangValue, $LangWhere, 'lang_zh_cn');
                    app($this->langRepository)->updateData($newLangValue, $LangWhere, 'lang_en');
//                    //提醒内容更新 请查看来自{异构系统名}的消息！消息标题：{消息标题}
//                    $langContentKey = $value['remind_menu'] . "_content_" . $langId[0];// heterogeneous_16_content_230
//                    $newLangZhCnContentValue = [
//                        'lang_value' => '请查看来自' .$params['system_name'].'的消息！消息标题：{messageContent}' // '请查看来自{systemName}的消息！消息标题：{messageContent}',
//                    ];
//                    $newLangEnContentValue = [
//                        'lang_value' => 'See the message from' .$params['system_name'].' ! Message title: {messageContent}' //  'See the message from {systemName}! Message title: {messageContent}',
//                    ];
//                    $LangWhere = [
//                        'lang_key' => [$langContentKey]
//                    ];
//                    app($this->langRepository)->updateData($newLangZhCnContentValue, $LangWhere, 'lang_zh_cn');
//                    app($this->langRepository)->updateData($newLangEnContentValue, $LangWhere, 'lang_en');
                } else {
                    if (isset($value['message_type']) && !empty($value['message_type'])) {
                        $prefix = 'heterogeneous_';
                        $remindMenu = $prefix . $id;
                        $params['message_type'][$key] = [
                            'heterogeneous_system_id' => $id,
                            'message_type' => trim($value['message_type']),
                            //用于消息提醒
                            'remind_menu' => $remindMenu,
                            // 'remind_type' => $res['id'], =>这个不要了，类型直接取用id值
                            'remind_link_src' => 'heterogeneous_system_src',
                        ];
                        $res = app($this->heterogeneousSystemMessageTypeRepository)->insertData($params['message_type'][$key]);
                        $langRes = $this->addMessageRemind($remindMenu, $res['id'], trim($value['message_type']), $params['system_name'], trim($value['remind_setting']));
                        if (isset($langRes['code'])) {
                            return $langRes;
                        }
                        $params['message_type'][$key]['id'] = $res['id'];
                    }
                }
            }
            //绑定账号 -- 外部系统
            if (isset($params['associated_user_way']) && $params['associated_user_way'] == 2) {
                if (isset($params['sso_id']) && !empty($params['sso_id'])) {
                    //外部系统关联的原来的，会被删除
                    /*if ($heterogeneousSystem['associated_user_way']==2 && $heterogeneousSystem['associated_user_way']==$params['sso_id']){
                    }*/
                    app($this->heterogeneousSystemUserBondingRepository)->deleteByWhere(['heterogeneous_system_code' => [$params['system_code']]]);
                    //获取外部系统数据
                    $where = ['search' => ['sso_id' => [$params['sso_id'], '=']]];
                    $userData = app($this->ssoLoginRepository)->getSsoLoginList($where);
                    $bindUser = [];
                    foreach ($userData as $key => $user) {
                        if (isset($user['sso_login_name']) && !empty($user['sso_login_name']) && isset($user['sso_login_user_id']) && !empty($user['sso_login_user_id'])) {
                            $bindUser = [
                                'heterogeneous_system_code' => $params['system_code'],
                                'heterogeneous_system_user_id' => $user['sso_login_name'],
                                'oa_user_id' => $user['sso_login_user_id'],
                            ];
                            //检查是否存在，存在更新
                            $check = app($this->heterogeneousSystemUserBondingRepository)->getData($bindUser);
                            if (!$check) {
                                app($this->heterogeneousSystemUserBondingRepository)->insertData($bindUser);
                            }
                        }
                    }
                }
            }
        }
        Redis::del('mulitlang_zh-CN');
        Redis::del('mulitlang_en');
        return $params;
    }

    /**
     * 编辑接收消息开关
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function editMessageSwitch($params)
    {
        if (isset($params['id']) && !empty($params['id'])) {
            if (isset($params['is_receive_data']) && $params['is_receive_data'] == 1) {
                $data = [
                    'is_receive_data' => 1
                ];
            } else {
                $data = [
                    'is_receive_data' => 0
                ];
            }
            $result = app($this->heterogeneousSystemRepository)->updateData($data, ['id' => $params['id']]);
            return $result;
        } else {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }

    }
    /**
     * 删除异构系统
     * @author [dosy]
     */
    /**
     * @param $id
     * @param bool $checkDelete
     * @return array|void
     * @author [dosy]
     */
    public function deleteHeterogeneousSystem($id, $checkDelete = true)
    {
        //获取异构系统数据
        $systemInfo = app($this->heterogeneousSystemRepository)->getData(['id' => $id]);
        //获取当前异构系统消息类型Ids
        $messageTypeIds = [];
        $messageTypeData = app($this->heterogeneousSystemMessageTypeRepository)->getHeterogeneousSystemMessageTypeList(['search' => ['heterogeneous_system_id' => [$id, '=']]]);
        foreach ($messageTypeData as $key => $value) {
            $messageTypeIds[] = $value['id'];
        }
        if ($checkDelete) {
            //检查是否存在绑定用户
            $userBindingCheck = app($this->heterogeneousSystemUserBondingRepository)->getData(['heterogeneous_system_code' => $systemInfo['system_code']]);
            if ($userBindingCheck) {
                return ['code' => ['0x000018', 'unifiedMessage']];
            }
            //检查是否有消息
            $messageCheck = app($this->heterogeneousSystemMessageRepository)->getData(['heterogeneous_system_id' => $systemInfo['system_code']]);
            if ($messageCheck) {
                return ['code' => ['0x000019', 'unifiedMessage']];
            }
        }
        //删除异构系统
        app($this->heterogeneousSystemRepository)->deleteById($id);
        //删除异构系统日志
        app($this->heterogeneousSystemIntegrationLogRepository)->deleteByWhere(['heterogeneous_system_id' => [$id, '=']]);
        //删除用户绑定
        app($this->heterogeneousSystemUserBondingRepository)->deleteByWhere(['heterogeneous_system_code' => [$systemInfo['system_code'], '=']]);
        //删除该系统下所有消息
        app($this->heterogeneousSystemMessageRepository)->deleteMessageByWhere(['heterogeneous_system_id' => $id]);
        //删除消息类型
        $result = app($this->heterogeneousSystemMessageTypeRepository)->deleteByWhere(['heterogeneous_system_id' => [$id, '=']]);
        //删除消息提醒
        if (!empty($messageTypeIds)) {
            $result = $this->delSystemRemind($id, $messageTypeIds);
        }
        return $result;
    }

    /**
     * 查询异构系统
     * @author [dosy]
     */
    public function getHeterogeneousSystem($id)
    {
        $where = ['id' => $id];
        $result = app($this->heterogeneousSystemRepository)->getData($where);
        $result['message_type'] = app($this->heterogeneousSystemMessageTypeRepository)->getFieldInfo(['heterogeneous_system_id' => [$id, '=']]);
        $temp = $result['message_type'];
        foreach ($temp as $key => $messageType) {
            $remindInfo = app($this->systemRemindsRepository)->getFieldInfo(['remind_menu' => $messageType['remind_menu'], 'remind_type' => $messageType['id']]);
            $temp[$key]['remind_setting'] = $remindInfo[0]['reminds_select'];
        }
        $result['message_type'] = $temp;
        return $result;
    }

    /**
     * 获取消息域名，同时阅读消息
     * @param $params
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function getDomainReadMessage($params, $userId)
    {
        if (isset($params['system_id']) && !empty($params['system_id'])) {
            $systemId = $params['system_id'];
        } else {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }
        if (isset($params['message_id']) && !empty($params['message_id'])) {
            $messageId = $params['message_id'];
        } else {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }
        $messageData = app($this->messageDataService)->readMessage($messageId, $userId);
        $systemData = $this->getHeterogeneousSystem($systemId);
        if ($systemData) {
            $result = [
                'pc_domain' => $systemData['pc_domain'] ?? '',
                'app_domain' => $systemData['app_domain'] ?? '',
            ];
            //$result=$systemData;
        } else {
            return ['code' => ['0x000020', 'unifiedMessage']];
        }
        if ($messageData) {
            $result['message_data'] = [
                'pc_address' => $messageData[0]['pc_address'] ?? '',
                'app_address' => $messageData[0]['app_address'] ?? '',
            ];
        } else {
            return ['code' => ['0x000021', 'unifiedMessage']];
        }
        return $result;
    }

    /**
     * 查询异构系统列表
     * @author [dosy]
     */
    public function getHeterogeneousSystemList($params)
    {
        $data = $this->response(app($this->heterogeneousSystemRepository), 'getHeterogeneousSystemTotal', 'getHeterogeneousSystemList', $this->parseParams($params));
        return $data;
    }

    /**
     * 检查消息类型
     * @param $params
     * @return mixed
     * @author [dosy]
     */
    public function acceptMessageTypeCheck($params)
    {
        $where = ['heterogeneous_system_id' => $params['heterogeneous_system_id'], 'message_type' => $params['message_type']];
        $result = app($this->heterogeneousSystemMessageTypeRepository)->getData($where);
        return $result;
    }

    /**
     * 获取异构系统消息类型
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function getHeterogeneousSystemMessageTypeList($params)
    {
        $data = $this->response(app($this->heterogeneousSystemMessageTypeRepository), 'getHeterogeneousSystemMessageTypeTotal', 'getHeterogeneousSystemMessageTypeList',
            $this->parseParams($params));
        return $data;
    }

    /**
     * 添加消息提醒
     * @param $remindMenu
     * @param $remindType
     * @param $title
     * @param $systemName
     * @param $remindSet
     * @return array
     * @author [dosy]
     */
    public function addMessageRemind($remindMenu, $remindType, $title, $systemName = '异构系统', $remindSet = '')
    {
        if (empty($remindMenu)) {
            return ['code' => ['full_title', 'unifiedMessage']];
        }
        if (empty($remindType)) {
            return ['code' => ['0x000005', 'unifiedMessage']];
        }
        $data = array(
            'remind_name' => $remindMenu . '_' . $remindType,
            'remind_menu' => $remindMenu,
            'remind_type' => $remindType,
            'remind_time' => $remindMenu . '_time',
            'receive_range' => $remindMenu . '_range',
            'remind_content' => $remindMenu . '_content',
            'remind_state' => '',
            'reminds_select' => 1,
            'is_hand' => 0,
            'reminds_select' => $remindSet
        );
        $list = app($this->systemRemindsRepository)->getFieldInfo(['remind_menu' => $remindMenu, 'remind_type' => $remindType]);
        //$list = DB::table('system_reminds')->where("remind_menu", $remindMenu)->where('remind_type', $remindType)->first(); dd($list);
        if (!empty($list)) {
            return ['code' => ['0x000006', 'unifiedMessage']];
        }
//        $resultData = app($this->systemRemindsRepository)->insertData($data); //插入字段做了限制用DB类了
//        $resultId = $resultData['id'];
        $resultId = DB::table("system_reminds")->insertGetId($data);
        if ($resultId) {
            $updateData = [
                'remind_name' => $remindMenu . '_' . $remindType . '_' . $resultId,
                'remind_time' => $remindMenu . '_time_' . $resultId,
                'receive_range' => $remindMenu . '_range_' . $resultId,
                'remind_content' => $remindMenu . '_content_' . $resultId,
            ];

            $returnData = DB::table("system_reminds")->where('id', $resultId)->update($updateData);
            if ($returnData) {
                //插入英文数据到lang_zh_cn表
                $receiveData = [
                    'lang_key' => $remindMenu . '_' . $remindType . '_' . $resultId,
                    'lang_value' => $title,
                    'table' => 'system_reminds',
                    'column' => 'remind_name',
                    'option' => 'remind_name',
                ];
                $contentData = [
                    'lang_key' => $remindMenu . '_content_' . $resultId,
                    //'lang_value' => '请查看来自' . $systemName . '的消息！消息标题：{messageContent}',
                    'lang_value' => '请查看来自{systemName}的消息！消息标题：{messageContent}',
                    'table' => 'system_reminds',
                    'column' => 'remind_content',
                    'option' => 'remind_content',
                ];
                $timeData = [
                    'lang_key' => $remindMenu . '_time_' . $resultId,
                    'lang_value' => '外部系统消息推送到OA时',
                    'table' => 'system_reminds',
                    'column' => 'remind_time',
                    'option' => 'remind_time',
                ];
                $rangeData = [
                    'lang_key' => $remindMenu . '_range_' . $resultId,
                    'lang_value' => '消息接收人',
                    'table' => 'system_reminds',
                    'column' => 'receive_range',
                    'option' => 'receive_range',
                ];
                $insertData = [$receiveData, $contentData, $timeData, $rangeData];
                DB::table("lang_zh_cn")->insert($insertData);

                //插入英文数据到lang_en表
                $receiveDataEn = [
                    'lang_key' => $remindMenu . '_' . $remindType . '_' . $resultId,
                    'lang_value' => $title,
                    'table' => 'system_reminds',
                    'column' => 'remind_name',
                    'option' => 'remind_name',
                ];
                $contentDataEn = [
                    'lang_key' => $remindMenu . '_content_' . $resultId,
                    'lang_value' => 'See the message from {systemName}! Message title: {messageContent}',
                    'table' => 'system_reminds',
                    'column' => 'remind_content',
                    'option' => 'remind_content',
                ];
                $timeDataEn = [
                    'lang_key' => $remindMenu . '_time_' . $resultId,
                    'lang_value' => 'Messages from external systems are pushed to OA',
                    'table' => 'system_reminds',
                    'column' => 'remind_time',
                    'option' => 'remind_time',
                ];
                $rangeDataEn = [
                    'lang_key' => $remindMenu . '_range_' . $resultId,
                    'lang_value' => 'Message receiver',
                    'table' => 'system_reminds',
                    'column' => 'receive_range',
                    'option' => 'receive_range',
                ];
                $insertDataEn = [$receiveDataEn, $contentDataEn, $timeDataEn, $rangeDataEn];
                DB::table("lang_en")->insert($insertDataEn);
            }
        }
    }

    /**
     * 消息提醒删除
     * @param $systemId
     * @param $delMessageType
     * @author [dosy]
     */
    public function delSystemRemind($systemId, $delMessageType)
    {
        $prefix = 'heterogeneous_';
        $remindMenu = $prefix . $systemId;
        $remindSystemData = DB::table('system_reminds')->where('remind_menu', $remindMenu)->whereIn('remind_type', $delMessageType)->get()->toArray();
        $remindIds = [];
        $remindNames = [];
        foreach ($remindSystemData as $key => $value) {
            $remindIds[] = $value->id;
            $remindNames[] = $value->remind_name;
        }
        $langKeys = [];
        foreach ($remindIds as $id) {
            $langKeys[] = $remindMenu . "_content_" . $id;
            $langKeys[] = $remindMenu . "_time_" . $id;
            $langKeys[] = $remindMenu . "_range_" . $id;

        }
        $delLangKeys = array_merge($remindNames, $langKeys);
        //删除system_reminds 里面数据
        $where = [
            'remind_menu' => [$remindMenu, '='],
            'remind_type' => [$delMessageType, 'in']
        ];
        app($this->systemRemindsRepository)->deleteByWhere($where);
        //删除lang_zh_cn、lang_en里面数据
        DB::table('lang_zh_cn')->whereIn('lang_key', $delLangKeys)->delete();
        DB::table('lang_en')->whereIn('lang_key', $delLangKeys)->delete();
    }

}




