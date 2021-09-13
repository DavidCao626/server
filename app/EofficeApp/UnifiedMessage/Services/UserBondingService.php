<?php

namespace App\EofficeApp\UnifiedMessage\Services;

use App\EofficeApp\Base\BaseService;

/**
 * 统一消息 -- 用户关联服务
 * Class UserAssociatedService
 * @package App\EofficeApp\UnifiedMessage\Services
 */
class UserBondingService extends BaseService
{
    private $heterogeneousSystemUserBondingRepository;
    private $heterogeneousSystemRepository;
    private $userRepository;

    public function __construct()
    {
        $this->heterogeneousSystemUserBondingRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemUserBondingRepository';
        $this->heterogeneousSystemRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
    }

    /**
     * 添加用户关联
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function addUserBonding($params)
    {
        if (isset($params['id']) && !empty($params['id'])) {
            return $this->editUserBonding($params['id'], $params);
        }
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = trim($value);
            }
        }
        if (isset($params['oa_user_id'])) {
            $userInfo = app($this->userRepository)->getUserName($params['oa_user_id']);
            if (!$userInfo) {
                return ['code' => ['0x000001', 'unifiedMessage']];
            }
        } else {
            return ['code' => ['0x000003', 'unifiedMessage']];
        }
        //检查当前异构系统用户绑定是否重复
        $insertDataOne = [
            'heterogeneous_system_code' => $params['heterogeneous_system_code'],
            'heterogeneous_system_user_id' => $params['heterogeneous_system_user_id'],
            'oa_user_id' => $params['oa_user_id'],
        ];
        $checkUser = app($this->heterogeneousSystemUserBondingRepository)->getData($insertDataOne);
        if ($checkUser) {
            return ['code' => ['0x000004', 'unifiedMessage']];
        }
        $data = array_intersect_key($params, array_flip(app($this->heterogeneousSystemUserBondingRepository)->getTableColumns()));
        $result = app($this->heterogeneousSystemUserBondingRepository)->insertData($data);
        return $result;
    }

    /**
     * 删除用户关联ById
     * @param $id
     * @return mixed
     * @author [dosy]
     */
    public function deleteUserBondingById($id)
    {
        $result = app($this->heterogeneousSystemUserBondingRepository)->deleteById($id);
        return $result;
    }

    /**
     * 批量删除用户关联
     * @param $param
     * @return int
     * @author [dosy]
     */
    public function batchDeleteUserBinding($param)
    {
        $userBindingId = [];
        foreach ($param as $value) {
            if (isset($value['id']) && !empty($value)) {
                $userBindingId[] = $value['id'];
            } else {
                continue;
            }
        }
        $wheres = [
            'id' => [$userBindingId, 'in']
        ];
        $result = app($this->heterogeneousSystemUserBondingRepository)->deleteByWhere($wheres);
        return $result;
    }

    /**
     * 删除所有用户关联
     * @return mixed
     * @author [dosy]
     */
    public function deleteAllUserBonding()
    {
        $result = app($this->heterogeneousSystemUserBondingRepository)->truncateTable();
        return $result;
    }

    /**
     *  编辑用户关联
     * @param $id
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function editUserBonding($id, $params)
    {
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = trim($value);
            }
        }

        if (isset($params['oa_user_id'])) {
            $userInfo = app($this->userRepository)->getUserName($params['oa_user_id']);
            if (!$userInfo) {
                return ['code' => ['0x000001', 'unifiedMessage']];
            }
        } else {
            return ['code' => ['0x000003', 'unifiedMessage']];
        }
        //检查当前异构系统用户绑定是否重复
        $insertDataOne = [
            'heterogeneous_system_code' => $params['heterogeneous_system_code'],
            'heterogeneous_system_user_id' => $params['heterogeneous_system_user_id'],
            'oa_user_id' => $params['oa_user_id'],
        ];
        $checkUser = app($this->heterogeneousSystemUserBondingRepository)->getData($insertDataOne);
        if ($checkUser) {
            // 检查是不是未修改编辑
            if($checkUser['id'] == $id){
                return false;
            }
            return ['code' => ['0x000004', 'unifiedMessage']];  
        }
        $data = array_intersect_key($insertDataOne, array_flip(app($this->heterogeneousSystemUserBondingRepository)->getTableColumns()));
        $result = app($this->heterogeneousSystemUserBondingRepository)->updateData($data, ['id' => $id], ['id']);
        return $result;
    }

    /**
     * 查看用户关联
     * @param $id
     * @return mixed
     * @author [dosy]
     */
    public function getUserBondingById($id)
    {
        $where = ['id' => $id];
        $result = app($this->heterogeneousSystemUserBondingRepository)->getData($where);
        return $result;
    }

    /**
     * 用户关联列表
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function getUserBondingList($params)
    {
        if (isset($params['search']) && !empty($params['search'])) {
            $search = json_decode($params['search'],true);
            //处理oa用户名模糊查询
            if (isset($search['user_accounts']) && !empty($search['user_accounts'])) {
                $userParams=[
                    'search'=>['user_accounts'=>$search['user_accounts']]
                ];
               $users= app($this->userRepository)->getUserList($userParams);
               $searchUserId=[];
               foreach ($users as $user){
                   $searchUserId[]=$user['user_id'];
               }
                $search['oa_user_id']=[$searchUserId,'in'];
               unset($search['user_accounts']);
                $params['search']=json_encode($search);
            }
            if (isset($search['heterogeneous_system_code'][0]) && !empty($search['heterogeneous_system_code'][0])) {
                $systemInfo = app($this->heterogeneousSystemRepository)->getOneFieldInfo(['system_code' => $search['heterogeneous_system_code'][0]]);
                if (!empty($systemInfo)) {
                    $users = $this->response(app($this->heterogeneousSystemUserBondingRepository), 'getHeterogeneousSystemUserBondingTotal',
                        'getHeterogeneousSystemUserBondingList',
                        $this->parseParams($params));
                    foreach ($users['list'] as $key => $user) {
                        //$systemInfo = app($this->heterogeneousSystemRepository)->getOneFieldInfo(['system_code' => $user['heterogeneous_system_code']]);
                        $users['list'][$key]['system_name'] = $systemInfo['system_name'];
                        $userInfo = app($this->userRepository)->getOneFieldInfo(['user_id' => $user['oa_user_id']]);
                        $users['list'][$key]['user_accounts'] = $userInfo['user_accounts'];
                        $users['list'][$key]['user_name'] = $userInfo['user_name'];
                    }
                } else {
                    return ['code' => ['0x000002', 'unifiedMessage']];
                }
            }else {
                return ['code' => ['0x000002', 'unifiedMessage']];
            }
        } else {
            return ['code' => ['0x000002', 'unifiedMessage']];
        }
        return $users;
    }

    /**
     * 导出用户关联模板api
     * @author [dosy]
     */
    public function exportUserBonding()
    {
        $data = [
            'header' => [
                // 'heterogeneous_system_user_id' => '外部账号',
                'heterogeneous_system_user_id' => trans('unifiedMessage.external_account'),
                // 'user_accounts' => 'OA用户（OA用户账号）',
                'user_accounts' =>  trans('unifiedMessage.user_account'),
            ],
            'data' => [                                    //可选填充模板数据
                [
                    'heterogeneous_system_user_id' => 'admin',
                    //'user_accounts' => '张三',
                    'user_accounts' => trans('unifiedMessage.zhang_san'),
                ]
            ]
        ];
        return $data;
    }

    /**
     * 批量导入用户关联
     * 目前仅新增[后续导入相同的不导入]
     * 注：允许多个异构系统账号绑定一个oa用户，也允许一个异构系统账号绑定多个oa用户
     * @param $data
     * @param $param
     * @return array
     * @author [dosy]
     */
    public function importUserBonding($data, $param)
    {
        //检查异构系统标识
        if (!isset($param['params']['system_code'])) {
            foreach ($data as $key => $value) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('unifiedMessage.0x000002'));
            }
            return compact('data');
        }
        if (isset($data)) {
            foreach ($data as $key => $value) {
                $userId = '';
                //检查用户oa账号
                if (!isset($value['user_accounts']) || empty($value['user_accounts'])) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('unifiedMessage.0x000001'));
                    continue;
                }
                //检查用户异构系统账号
                if (!isset($value['heterogeneous_system_user_id']) || empty($value['heterogeneous_system_user_id'])) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('unifiedMessage.0x000003'));
                    continue;
                }
                $value['user_accounts'] = trim($value['user_accounts']);
                $value['heterogeneous_system_user_id'] = trim($value['heterogeneous_system_user_id']);
                $userInfo = app($this->userRepository)->getUserByAccount($value['user_accounts']);
                if (!empty($userInfo)) {
                    $userId = $userInfo['user_id'] ?? '';
                    /*//检查当前异构系统用户是否已绑定oa用户
                    $checkUser = [
                        'heterogeneous_system_code'=> $param['params']['system_code'],
                        'heterogeneous_system_user_id'=> $value['heterogeneous_system_user_id'],
                    ];
                    $result = app($this->heterogeneousSystemUserBondingRepository)->getDate($checkUser);
                    if ($result){
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans('unifiedMessage.0x000003'));
                        continue;
                    }*/
                    //检查当前异构系统用户绑定是否重复
                    $insertDataOne = [
                        'heterogeneous_system_code' => $param['params']['system_code'],
                        'heterogeneous_system_user_id' => $value['heterogeneous_system_user_id'],
                        'oa_user_id' => $userId,
                    ];
                    $checkUser = app($this->heterogeneousSystemUserBondingRepository)->getData($insertDataOne);
                    if ($checkUser) {
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans('unifiedMessage.0x000004'));
                        continue;
                    }
                    $result = app($this->heterogeneousSystemUserBondingRepository)->insertData($insertDataOne);
                    if ($result) {
                        $data[$key]['importResult'] = importDataSuccess();
                    }
                } else {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('unifiedMessage.0x000001'));
                    continue;
                }
            }
        }
        return compact('data');
    }

    public function importUserBondingFilter($data, $param)
    {
        foreach ($data as $key => $value) {
            if (!isset($value['user_name'])) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('unifiedMessage.lack_of_OA_user'));
            }
        }
        return $data;
    }

}




