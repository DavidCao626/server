<?php

namespace App\EofficeApp\UnifiedMessage\Services;

use App\EofficeApp\Base\BaseService;
use Eoffice;


/**
 * 统一消息 -- 消息数据服务
 * Class MessageDataService
 * @package App\EofficeApp\UnifiedMessage\Services
 */
class MessageDataService extends BaseService
{
    private $heterogeneousSystemMessageRepository;
    private $heterogeneousSystemUserBondingRepository;
    private $heterogeneousSystemService;
    private $unifiedMessageService;
    private $heterogeneousSystemMessageTypeRepository;
    private $userRepository;
    private $roleService;
    private $heterogeneousSystemIntegrationLogRepository;

    public function __construct()
    {
        $this->heterogeneousSystemMessageRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemMessageRepository';
        $this->heterogeneousSystemUserBondingRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemUserBondingRepository';
        $this->heterogeneousSystemMessageTypeRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemMessageTypeRepository';
        $this->heterogeneousSystemService = 'App\EofficeApp\UnifiedMessage\Services\HeterogeneousSystemService';
        $this->unifiedMessageService = 'App\EofficeApp\UnifiedMessage\Services\UnifiedMessageService';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->roleService = 'App\EofficeApp\Role\Services\RoleService';
        $this->heterogeneousSystemIntegrationLogRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemIntegrationLogRepository';
    }
    /***************************************************************************** 对外 start *********************************************/


    /**
     * 接收第三方消息数据-添加消息
     *  为了保证消息ID和第三方系统唯一，只支持一条数据一条数据来接受
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function acceptMessageData($params)
    {
        foreach ($params as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $params[$key] = trim($value);
            } else {
                return ['code' => ['0x000007', 'unifiedMessage']];
            }
        }
        //检查【是否接受消息、ip是否安全、获取系统id和名字】
        $check = app($this->unifiedMessageService)->acceptSystemCheck($params);

        if (isset($check['code'])) {
            return $check;
        } else {
            $params['heterogeneous_system_id'] = $check['id'];
            $params['system_name'] = $check['system_name'];
        }
        if (!is_numeric($params['message_create_time'])) {
            return ['code' => ['0x000023', 'unifiedMessage']];
        }
        //检查消息类型
        if (empty($params['message_type'])) {
            return ['code' => ['0x000005', 'unifiedMessage']];
        }
        $checkResult = app($this->heterogeneousSystemService)->acceptMessageTypeCheck($params);
        if (!$checkResult) {
            return ['code' => ['0x000009', 'unifiedMessage']];
        }

        //当前系统消息类型下要求消息类型唯一
        $messageIdData = [
            'heterogeneous_system_id' => $params['heterogeneous_system_id'],
            'message_type_id' => $checkResult['id'],
            'message_id' => $params['message_id'],
        ];
        $messageIdCheck = app($this->heterogeneousSystemMessageRepository)->getData($messageIdData);
        if ($messageIdCheck) {
            return ['code' => ['0x000013', 'unifiedMessage']];
        }
        //$recipient=app($this->heterogeneousSystemUserBondingRepository)->getHeterogeneousSystemUserBondingList();

        $data = [
            'heterogeneous_system_id' => $params['heterogeneous_system_id'],
            'message_type_id' => $checkResult['id'],
            'message_id' => $params['message_id'], //要求唯一
            'message_title' => $params['message_title'],
            'dispose_state' => 0,
            'read_state' => 0,
            'sender' => $params['sender'],
            'message_create_time' => date("Y-m-d H:i:s", $params['message_create_time']),
            'recipient' => $params['recipient'],
            'recipient_time' => date("Y-m-d H:i:s"),
            'pc_address' => $params['pc_address'],
            'app_address' => $params['app_address'],
        ];
        $data = array_intersect_key($data, array_flip(app($this->heterogeneousSystemMessageRepository)->getTableColumns()));
        $result = app($this->heterogeneousSystemMessageRepository)->insertData($data);
        $log = [
            'heterogeneous_system_id' => $params['heterogeneous_system_id'],
            'heterogeneous_system_name' => $params['system_name'],
            'message_type' => $params['message_type'],
            'message_id' => $params['message_id'],
            'operation_type' => 'add',
            'operator' => $params['operator'],
            'operation_time' => date("Y-m-d H:i:s"),
            'link_data' => $params['message_title'],
        ];
        //注，这块还有要补充一个消息提醒、日志
        if ($result) {
            //消息提醒
            $this->sendMessageRemind($params['message_title'], $params['heterogeneous_system_id'], $checkResult['id'], $result['id'], $params['recipient'], $params['pc_address'],
                $params['app_address'], $params['system_code'], $params['system_name'],$check['app_domain']);
            //日志
            $log['operation_result'] = trans('unifiedMessage.success');
            $log['operation_explain'] = trans('unifiedMessage.api_add_one_message');
            app($this->heterogeneousSystemIntegrationLogRepository)->insertData($log);
            unset($result['heterogeneous_system_id']);
            unset($result['id']);
            unset($result['updated_at']);
//            $result['system_code'] = $params['system_code'];
//            $result['system_secret'] = $params['system_secret'];
            return $result;
        } else {
            $log['operation_result'] = trans('unifiedMessage.fail');
            $log['operation_explain'] = trans('unifiedMessage.api_add_one_message');
            app($this->heterogeneousSystemIntegrationLogRepository)->insertData($log);
            return ['code' => ['0x000007', 'unifiedMessage']];
        }
    }

    /**
     * 删除消息(对外API）
     * @param $param
     * @return array
     * @author [dosy]
     */
    public function deleteMessage($param)
    {
        //检查【是否接受消息、ip是否安全、获取系统id和名字】
        $check = app($this->unifiedMessageService)->acceptSystemCheck($param);
        if (isset($check['code'])) {
            return $check;
        } else {
            $param['heterogeneous_system_id'] = $check['id'];
            $param['system_name'] = $check['system_name'];
        }
        $messageId = $param['message_id'];
        if (!is_array($messageId)) {
            return ['code' => ['0x000010', 'unifiedMessage']];
        }
        //检查消息类型
        if (!is_string($param['message_type']) || empty($param['message_type'])) {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }
        $checkResult = app($this->heterogeneousSystemService)->acceptMessageTypeCheck($param);
        if (!$checkResult) {
            return ['code' => ['0x000009', 'unifiedMessage']];
        }
        $delResult = [];
        foreach ($messageId as $key => $value) {
            $log = [
                'heterogeneous_system_id' => $param['heterogeneous_system_id'],
                'heterogeneous_system_name' => $param['system_name'],
                'message_type' => $param['message_type'],
                'message_id' => $value,
                'operation_type' => 'delete',
                'operator' => $param['operator'],
                'operation_time' => date("Y-m-d H:i:s"),
            ];
            if (is_string($value) || is_numeric($value)) {
                $messageData = app($this->heterogeneousSystemMessageRepository)->getData([
                    'message_id' => $value,
                    'message_type_id' => $checkResult['id'],
                    'heterogeneous_system_id' => $param['heterogeneous_system_id']
                ]);
                if (isset($messageData['message_title'])) {
                    $log['link_data'] = $messageData['message_title'];
                    $result = app($this->heterogeneousSystemMessageRepository)->deleteMessageByWhere([
                        'message_id' => $value,
                        'message_type_id' => $checkResult['id'],
                        'heterogeneous_system_id' => $param['heterogeneous_system_id']
                    ]);
                    if ($result) {
                        $log['operation_result'] = trans('unifiedMessage.success');
                        $log['operation_explain'] = trans('unifiedMessage.api_delete_message_by_id');
                        $delResult[$value] = true;
                    } else {
                        $log['operation_result'] = trans('unifiedMessage.fail');
                        $log['operation_explain'] = trans('unifiedMessage.api_delete_message_by_id');
                        $delResult[$value] = false;
                    }
                } else {
                    $log['operation_result'] = trans('unifiedMessage.fail');
                    $log['operation_explain'] = trans('unifiedMessage.api_delete_message_by_id');
                    $delResult[$value] = false;
                }
            } else {
                $log['operation_result'] = trans('unifiedMessage.fail');
                $log['operation_explain'] = trans('unifiedMessage.api_delete_message_by_id');
                $delResult[$value] = false;
            }
            app($this->heterogeneousSystemIntegrationLogRepository)->insertData($log);
        }
        return $delResult;
    }

    /**
     * 删除指定人消息
     * @author [dosy]
     */
    public function deleteDesignatedPersonMessage($param)
    {
        //检查【是否接受消息、ip是否安全、获取系统id和名字】
        $check = app($this->unifiedMessageService)->acceptSystemCheck($param);
        if (isset($check['code'])) {
            return $check;
        } else {
            $param['heterogeneous_system_id'] = $check['id'];
            $param['system_name'] = $check['system_name'];
        }

        $recipients = $param['recipients'];
        if (!is_array($recipients)) {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }
        $delResult = [];
        foreach ($recipients as $key => $value) {
            $log = [
                'heterogeneous_system_id' => $param['heterogeneous_system_id'],
                'heterogeneous_system_name' => $param['system_name'],
                'message_type' => '',
                'operation_type' => 'delete',
                'external_account' => $value,
                'operator' => $param['operator'],
                'operation_time' => date("Y-m-d H:i:s"),
                'link_data' => $value . '-' . trans('unifiedMessage.all_message'),
            ];
            if (is_string($value) || is_numeric($value)) {
                $result = app($this->heterogeneousSystemMessageRepository)->deleteMessageByWhere([
                    'recipient' => $value,
                    'heterogeneous_system_id' => $param['heterogeneous_system_id']
                ]);
                if ($result) {
                    $log['operation_result'] = trans('unifiedMessage.success');
                    $log['operation_explain'] = trans('unifiedMessage.api_delete_message_by_accounts');
                    $delResult[$value] = true;
                } else {
                    $log['operation_result'] = trans('unifiedMessage.fail');
                    $log['operation_explain'] = trans('unifiedMessage.api_delete_message_by_accounts');
                    $delResult[$value] = false;
                }
            } else {
                $log['operation_result'] = trans('unifiedMessage.fail');
                $log['operation_explain'] = trans('unifiedMessage.api_delete_message_by_accounts');
                $delResult[$value] = false;
            }
            app($this->heterogeneousSystemIntegrationLogRepository)->insertData($log);
        }
        return $delResult;
    }

    /**
     * 修改消息状态（已处理,已读）
     * @author [dosy]
     */
    public function editMessageState($param)
    {
        //检查【是否接受消息、ip是否安全、获取系统id和名字】
        $check = app($this->unifiedMessageService)->acceptSystemCheck($param);
        if (isset($check['code'])) {
            return $check;
        } else {
            $param['heterogeneous_system_id'] = $check['id'];
            $param['system_name'] = $check['system_name'];
        }

        if (is_string($param['message_id']) || is_numeric($param['message_id'])) {
            $messageId = trim($param['message_id']);
        } else {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }
        //检查消息类型
        if (!is_string($param['message_type']) || empty($param['message_type'])) {
            return ['code' => ['0x000007', 'unifiedMessage']];
        }
        $checkResult = app($this->heterogeneousSystemService)->acceptMessageTypeCheck($param);
        if (!$checkResult) {
            return ['code' => ['0x000009', 'unifiedMessage']];
        }
        $messageData = app($this->heterogeneousSystemMessageRepository)->getData([
            'message_id' => $messageId,
            'message_type_id' => $checkResult['id'],
            'heterogeneous_system_id' => $param['heterogeneous_system_id']
        ]);
        if (isset($messageData['message_title'])) {
            $result = app($this->heterogeneousSystemMessageRepository)->updateData(['dispose_state' => 1, 'read_state' => 1],
                ['message_id' => $messageId, 'message_type_id' => $checkResult['id'], 'heterogeneous_system_id' => $param['heterogeneous_system_id']]);
            if ($result) {
                $log = [
                    'heterogeneous_system_id' => $param['heterogeneous_system_id'],
                    'heterogeneous_system_name' => $param['system_name'],
                    'message_type' => $param['message_type'],
                    'message_id' => $messageId,
                    'operation_type' => 'edit',
                    'operation_result' => trans('unifiedMessage.success'),
                    'operation_explain' => trans('unifiedMessage.api_edit_one_message_status'),
                    'operator' => $param['operator'],
                    'operation_time' => date("Y-m-d H:i:s"),
                    'link_data' => $messageData['message_title'],
                ];
                app($this->heterogeneousSystemIntegrationLogRepository)->insertData($log);
                $res = [$messageId => true];
            } else {
                $res = ['code' => ['0x000012', 'unifiedMessage']];
            }
        } else {
            $res = ['code' => ['0x000012', 'unifiedMessage']];
        }
        return $res;
    }
    /**************************************************************** end ************************************************************************************/
    /**
     * 删除消息ByWhere
     * @author [dosy]
     */
    public function deleteMessageByWhere($id, $userId)
    {
        $where = ['search' => ['id' => [$id, '=']]];
        $messageData = app($this->heterogeneousSystemMessageRepository)->getHeterogeneousSystemMessageList($where);
        $result = app($this->heterogeneousSystemMessageRepository)->deleteMessageByWhere(['id' => $id]);
        if ($messageData && $result) {
            $log = [
                'heterogeneous_system_id' => $messageData[0]['system_has_one']['id'] ?? '',
                'heterogeneous_system_name' => $messageData[0]['system_has_one']['system_name'] ?? '',
                'message_type' => $messageData[0]['message_type_has_one']['message_type'] ?? '',
                'operation_type' => 'delete',
                'operation_result' => trans('unifiedMessage.success'),
                'operation_explain' => trans('unifiedMessage.delete_message_by_oa_id'),
                'operator' => $userId,
                'message_id' => $messageData[0]['message_id'],
                'operation_time' => date("Y-m-d H:i:s"),
                'link_data' => $messageData[0]['message_title'],
            ];
            app($this->heterogeneousSystemIntegrationLogRepository)->insertData($log);
        }
        return $result;
    }

    /**
     * 批量删除消息
     * @param $param
     * @param $userId
     * @return int
     * @author [dosy]
     */
    public function batchDeleteMessage($param, $userId)
    {
        $messageId = [];
        foreach ($param as $value) {
            if (isset($value['id']) && !empty($value)) {
                $messageId[] = $value['id'];
            } else {
                continue;
            }
        }
        $where = [
            'id' => [$messageId, 'in']
        ];
        $wheres = ['search' => ['id' => [$messageId, 'in']]];
        $data = app($this->heterogeneousSystemMessageRepository)->getHeterogeneousSystemMessageList($wheres);
        if (!empty($data)) {
            foreach ($data as $key => $message) {
                $logs[$key] = [
                    'heterogeneous_system_id' => $message['heterogeneous_system_id'] ?? '',
                    'heterogeneous_system_name' => $message['system_has_one']['system_name'] ?? '',
                    'message_type' => $message['message_type_has_one']['message_type'] ?? '',
                    'operation_type' => 'batch_delete',
                    'message_id' => $message['message_id'],
                    'operation_result' => trans('unifiedMessage.success'),
                    'operation_explain' => trans('unifiedMessage.delete_message_by_batch_delete'),
                    'operator' => $userId,
                    'operation_time' => date("Y-m-d H:i:s"),
                    'link_data' => $message['message_title'],
                ];
            }
            app($this->heterogeneousSystemIntegrationLogRepository)->insertMultipleData($logs);
        }
        $result = app($this->heterogeneousSystemMessageRepository)->deleteByWhere($where);
        return $result;
    }

    /**
     * 修改消息状态（已读）
     * @param $id
     * @param $userId
     * @return mixed
     * @author [dosy]
     */
    public function readMessage($id, $userId)
    {
        $where = ['search' => ['id' => [$id, '=']]];
        $messageData = app($this->heterogeneousSystemMessageRepository)->getHeterogeneousSystemMessageList($where);
        $result = app($this->heterogeneousSystemMessageRepository)->updateData(['read_state' => 1], ['id' => $id]);
        // if ($messageData && $result) {
        //     $log = [
        //         'heterogeneous_system_id' => $messageData[0]['system_has_one']['id'] ?? '',
        //         'heterogeneous_system_name' => $messageData[0]['system_has_one']['system_name'] ?? '',
        //         'message_type' => $messageData[0]['message_type_has_one']['message_type'] ?? '',
        //         'operation_type' => 'edit',
        //         'operation_result' => trans('unifiedMessage.success'),
        //         'operation_explain' => trans('unifiedMessage.read_one_message_status'),
        //         'operator' => $userId,
        //         'message_id' => $messageData[0]['message_id'],
        //         'operation_time' => date("Y-m-d H:i:s"),
        //         'link_data' => $messageData[0]['message_title'],
        //     ];
        //     app($this->heterogeneousSystemIntegrationLogRepository)->insertData($log);
        // }
        return $messageData;
    }

    /**
     * 删除所有消息
     * @author [dosy]
     */
    public function deleteAllMessage()
    {
        return '';
    }

    /**
     * 获取消息ById
     * @author [dosy]
     */
    public function getMessageById($id)
    {
        $where = ['id' => $id];
        $result = app($this->heterogeneousSystemMessageRepository)->getData($where);
        return $result;
    }

    /**
     * 获取消息列表
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function getMessageList($params)
    {
        if (isset($params['search']) && !empty($params['search'])) {
            $search = json_decode($params['search'], true);
            if (isset($search['search_info']) && !empty($search['search_info'])) { //来自高级搜索
                foreach ($search['search_info'] as $key => $value) {
                    if ($key === 'message_title') {
                        $search[$key] = [$value, 'like'];
                    } elseif ($key === 'oa_user_id') {
                        //$recipient = app($this->heterogeneousSystemUserBondingRepository)->getData(['oa_user_id' => $value, 'heterogeneous_system_code'=>$search['heterogeneous_system_code'][0]]);
                        $userSelect[ 'search']['oa_user_id'] =[[$value],'='];
                        $userSelect[ 'search']['heterogeneous_system_code'] =[[$search['heterogeneous_system_code'][0]],'='];
                        $recipient = app($this->heterogeneousSystemUserBondingRepository)->getHeterogeneousSystemUserBondingList($userSelect);
                        $heterogeneousSystemUserIds = [];
                        foreach ($recipient as $user){
                            $heterogeneousSystemUserIds[] = $user['heterogeneous_system_user_id'];
                        }
                        if ($heterogeneousSystemUserIds) {
                            $search['recipient'] = [$heterogeneousSystemUserIds,'in'];
                        }else {
                            return ['total'=>0,'list'=>[]];
                        }
                    } elseif ($key === 'dispose_state') {
                        if ($value === 'dispose_true') {
                            $search[$key] = [1];
                        } else {
                            $search[$key] = [0];
                        }
                    } elseif ($key === 'read_state') {
                        if ($value === 'read_true') {
                            $search[$key] = [1];
                        } else {
                            $search[$key] = [0];
                        }
                    } elseif ($key === 'message_type') {
                        $messageType = app($this->heterogeneousSystemMessageTypeRepository)->getData(['message_type' => $value,'heterogeneous_system_id'=>$search['heterogeneous_system_id'][0]]);
                        if ($messageType) {
                            $search['message_type_id'] = [$messageType['id']];
                        } else {
                            return ['total'=>0,'list'=>[]];
                        }
                    } else {
                        $search[$key] = [$value];
                    }
                }
                unset($search['search_info']);
                unset($search['heterogeneous_system_code']);
                $params['search'] = json_encode($search);
            }
        }
        $data = $this->response(app($this->heterogeneousSystemMessageRepository), 'getHeterogeneousSystemMessageTotal', 'getHeterogeneousSystemMessageList',
            $this->parseParams($params));
        if (isset($data['list'])) {
            foreach ($data['list'] as $key => $message) {
                if (isset($message['system_has_one']['system_code']) && !empty($message['system_has_one']['system_code']) && isset($message['recipient']) && !empty($message['recipient'])) {
                    $where = ['heterogeneous_system_code' => $message['system_has_one']['system_code'], 'heterogeneous_system_user_id' => $message['recipient']];
                   // $bindingData = app($this->heterogeneousSystemUserBondingRepository)->getData($where);
                    $bindingData = app($this->heterogeneousSystemUserBondingRepository)->getList($where);
                    if (!empty($bindingData)){
                        $usersName = [];
                        foreach ($bindingData as $binding){
                            $userName = app($this->userRepository)->getUserName($binding['oa_user_id']);
                            if (!empty($userName)) {
                                $usersName[] = $userName;
                            }
                        }
                        if (!empty($usersName)){
                            $usersNameStr = implode('，',$usersName);
                            $data['list'][$key]['user_name'] = $usersNameStr;
                        }else{
                            $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                        }
                    }else{
                        $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                    }
                    //dd($bindingData);
                    /*if (isset($bindingData['oa_user_id']) && !empty($bindingData['oa_user_id'])) {
                        $userName = app($this->userRepository)->getUserName($bindingData['oa_user_id']);
                        if (!empty($userName)) {
                            $data['list'][$key]['user_name'] = $userName;
                        } else {
                            $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                        }
                    } else {
                        $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                    }*/
                } else {
                    $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                }
                if (isset($message['system_has_one']['pc_domain']) && !empty($message['system_has_one']['pc_domain'])) {
                    $data['list'][$key]['pc_address'] = $message['system_has_one']['pc_domain'] . $message['pc_address'];
                }
                if (isset($message['system_has_one']['app_domain']) && !empty($message['system_has_one']['app_domain'])) {
                    $data['list'][$key]['app_address'] = $message['system_has_one']['app_domain'] . $message['app_address'];
                }
            }
        }
        return $data;
    }

    /**
     * 返回门户消息数据
     * @param $params
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function portalData($params, $userId)
    {
        $allExtAccounts = app($this->heterogeneousSystemUserBondingRepository)->getHeterogeneousSystemUserBondingList(['search' => ['oa_user_id' => [$userId, '=']]]);
        $accountsInfo = [];
        foreach ($allExtAccounts as $value) {
            //用户可能是新增导入导致的没用用户--导入用户后未保存异构系统
            if (empty($value['heterogeneous_system_has_one'])){
                continue;
            }
            $accountsInfo[] = ['system_id'=>$value['heterogeneous_system_has_one']['id'],'user'=>$value['heterogeneous_system_user_id']];
        }

        //如果用户关联不存在，则不返回任何消息
        if (empty($accountsInfo)){
            return ['total'=>0,'list'=>[]];
        }
        if (isset($params['search'])) {
            $params['search'] = json_decode($params['search'], true);
            if (isset($params['search']['message_title'])) {
                $params['search']['message_title'] = [$params['search']['message_title'], 'like'];
            }
            if (isset($params['search']['message_id'])) {
                $params['search']['message_id'] = [[$params['search']['message_id']], '='];
            }
            if (isset($params['search']['sender'])) {
                $params['search']['sender'] = [[$params['search']['sender']], '='];
            }
        }

        if (isset($params['read_state'])) {
            //高级查询和标签类型一致，值不一致时，直接返回空
            if(isset($params['search']['read_state'])&&$params['search']['read_state'][0] != $params['read_state']){
                return ['total'=>0,'list'=>[]];
            }
            $params['search']['read_state'] = [[$params['read_state']], '='];
        }
        if (isset($params['dispose_state'])) {
            //高级查询和标签类型一致，值不一致时，直接返回空
            if(isset($params['search']['dispose_state'])&&$params['search']['dispose_state'][0] != $params['dispose_state']){
                return ['total'=>0,'list'=>[]];
            }
            $params['search']['dispose_state'] = [[$params['dispose_state']], '='];

        }
        $total = app($this->heterogeneousSystemMessageRepository)->getHeterogeneousSystemMessageTotal($this->parseParams($params),$accountsInfo);
        $list = app($this->heterogeneousSystemMessageRepository)->getHeterogeneousSystemMessageList($this->parseParams($params),$accountsInfo);
        $data =  ['total'=>$total,'list'=>$list];
       /* if (!empty($accountsInfo)) {
            if (isset($params['search'])) {
                $params['search'] = json_decode($params['search'], true);
                if (isset($params['search']['message_title'])) {
                    $params['search']['message_title'] = [$params['search']['message_title'], 'like'];
                }
                if (isset($params['search']['message_id'])) {
                    $params['search']['message_id'] = [[$params['search']['message_id']], '='];
                }
                if (isset($params['search']['sender'])) {
                    $params['search']['sender'] = [[$params['search']['sender']], '='];
                }
            }
            if(!empty($accountsInfo)){
                $params['search']['recipient'] = [$accountsInfo, 'in'];
            }
        }*/

        /*if (isset($params['read_state'])) {
            //高级查询和标签类型一致，值不一致时，直接返回空
            if(isset($params['search']['read_state'])&&$params['search']['read_state'][0] != $params['read_state']){
                return ['total'=>0,'list'=>[]];
            }
            $params['search']['read_state'] = [[$params['read_state']], '='];
        }
        if (isset($params['dispose_state'])) {
            //高级查询和标签类型一致，值不一致时，直接返回空
            if(isset($params['search']['dispose_state'])&&$params['search']['dispose_state'][0] != $params['dispose_state']){
                return ['total'=>0,'list'=>[]];
            }
            $params['search']['dispose_state'] = [[$params['dispose_state']], '='];

        }*/
//        $data = $this->response(app($this->heterogeneousSystemMessageRepository), 'getHeterogeneousSystemMessageTotal', 'getHeterogeneousSystemMessageList',
//            $this->parseParams($params));
//        dd($data);
        if (isset($data['list'])) {
            foreach ($data['list'] as $key => $message) {
                if (isset($message['system_has_one']['system_code']) && !empty($message['system_has_one']['system_code']) && isset($message['recipient']) && !empty($message['recipient'])) {
                    $where = ['heterogeneous_system_code' => $message['system_has_one']['system_code'], 'heterogeneous_system_user_id' => $message['recipient']];
                    $bindingData = app($this->heterogeneousSystemUserBondingRepository)->getData($where);
                    if (isset($bindingData['oa_user_id']) && !empty($bindingData['oa_user_id'])) {
                        $userName = app($this->userRepository)->getUserName($bindingData['oa_user_id']);
                        if (!empty($userName)) {
                            $data['list'][$key]['user_name'] = $userName;
                        } else {
                            $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                        }
                    } else {
                        $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                    }
                } else {
                    $data['list'][$key]['user_name'] = trans('unifiedMessage.not_binding');
                }
                if (isset($message['system_has_one']['pc_domain']) && !empty($message['system_has_one']['pc_domain']) && isset($message['pc_address'])) {
                    $data['list'][$key]['pc_address'] = $message['system_has_one']['pc_domain'] . $message['pc_address'];
                }
                if (isset($message['system_has_one']['app_domain']) && !empty($message['system_has_one']['app_domain']) && isset($message['app_address'])) {
                    $data['list'][$key]['app_address'] = $message['system_has_one']['app_domain'] . $message['app_address'];
                }
            }
        }
        return $data;
    }

    // 提醒设置方法
    public function getUndefinedMessageData()
    {
        $data = app($this->heterogeneousSystemMessageTypeRepository)->getAllMessagedata();
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['remind_link_src']] = trans('system.' . $value['remind_link_src']);
            $result[$value['remind_menu']] = $value['system_name'];
        }
        return $result;
    }

    /**
     * 消息提醒发送
     * @param $title
     * @param $heterogeneousSystemId
     * @param $remainType
     * @param $messageId
     * @param $recipient
     * @param $pcAddress
     * @param $appAddress
     * @param $systemCode
     * @param string $systemName
     * @author [dosy]
     */
    public function sendMessageRemind($title, $heterogeneousSystemId, $remainType, $messageId, $recipient, $pcAddress, $appAddress, $systemCode, $systemName = "异构系统",$appDomain)
    {
        $remindMark = 'heterogeneous_' . $heterogeneousSystemId . '-' . $remainType;
        $allExtAccounts = app($this->heterogeneousSystemUserBondingRepository)->getHeterogeneousSystemUserBondingList([
            'search' => [
                'heterogeneous_system_user_id' => [
                    $recipient,
                    '='
                ],
                'heterogeneous_system_code' => [
                    $systemCode,
                    '='
                ],
            ]
        ]);
        $redirectUrl = $appDomain.$appAddress;
        $oaUsers = [];
        foreach ($allExtAccounts as $value) {
            $oaUsers[] = $value['oa_user_id'];
        }
        $oaUsers = array_filter($oaUsers);
        $oaUsers = array_unique($oaUsers);

        if (!empty($oaUsers)) {
            $sendData = [
                "toUser" => $oaUsers,
                "remindMark" => $remindMark, // remind_menu-remind_type
                //'redirect_url' => $redirectUrl,
                'redirect_url' => 'unifiedMessage',
                "stateParams" => [    //写入params的参数
                    'heterogeneous_system_id' => (string)$heterogeneousSystemId,
/*                    'pc_address' => $pcAddress,
                    'app_address' => $appAddress,*/
                    'message_id' => (string)$messageId,
                ],
                'contentParam' => [
                    'systemName' => $systemName,
                    'messageContent' => $title   //占位符参数
                ]
            ];

            Eoffice::sendMessage($sendData);
        }
    }
}




