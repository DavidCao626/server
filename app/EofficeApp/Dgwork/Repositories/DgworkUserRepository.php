<?php

namespace App\EofficeApp\Dgwork\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dgwork\Entities\DgworkUserEntity;


class DgworkUserRepository extends BaseRepository
{
    private $userRepository;
    public function __construct(DgworkUserEntity $entity)
    {
        $this->userRepository = "App\EofficeApp\User\Repositories\UserRepository";
        parent::__construct($entity);
    }

    // public function getUser($where = [])
    // {
    //     return $this->entity->wheres($where)->first();
    // }

    //清空表
    public function truncateDgworkUser()
    {
        return $this->entity->truncate();
    }

    // public function transferUsers($where)
    // {
    //     return $this->entity->select(["userid", "oa_id"])->wheres($where)->get()->toArray();
    // }

    public function getDgworkEmployeeCodeByUserId($user_id)
    {
        $res = $this->entity->select(["employee_code"])->where("user_id", $user_id)->first();
        if(empty($res)){
            return '';
        }
        return $res->toArray();
    }

    public function getDgworkAcccountIdByUserId($user_id)
    {
        $res = $this->entity->select(["account_id"])->where("user_id", $user_id)->first();
        if(empty($res)){
            return '';
        }
        return $res->toArray();
    }

    public function getUserIdByEmployeeCode($employeeCode)
    {
        $res = $this->entity->select(["user_id"])->where("employee_code", $employeeCode)->first();
        if(empty($res)){
            return '';
        }
        return $res->toArray();
    }

    public function getDgworkUserList($param)
    {
        if ($param['type'] == 'unbind') {
            // 返回未绑定的用户
            $query = $this->entity->rightJoin('user', 'dgwork_user.user_id', '=', 'user.user_id')->select('user.user_accounts', 'user.user_name', 'dgwork_user.user_id','user.user_id');
            $query = $query->whereNull('dgwork_user.user_id');
        } else if ($param['type'] == 'bind') {
            // 返回绑定的用户
            $query = $this->entity->Join('user', 'dgwork_user.user_id', '=', 'user.user_id')->select('user.user_accounts', 'user.user_name', 'user.user_id','dgwork_user.user_id','dgwork_user.employee_code');
        } else {
            // 默认返回全部all
            $query = $this->entity->rightJoin('user', 'dgwork_user.user_id', '=', 'user.user_id')->select('user.user_accounts', 'user.user_name', 'dgwork_user.user_id','user.user_id','dgwork_user.employee_code');
        }
        // 离职用户不显示
        $query = $query->where('user.user_accounts', '!=', '');
        if (!empty($param['search']) && count($param['search'] > 0)) {
            // foreach ($param['search'] as $filedKey => $content) {
            //     $query = $query->where($filedKey, $content[1], $content[0]);
            // }
            $query = $this->entity->scopeWheres($query, $param['search']);
        }

        if (isset($param['page']) && isset($param['limit'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }
        if (isset($param["returntype"]) && $param["returntype"] == "count") {
            return $query->count();
        } else {
            return $query->get()->toArray();
        }
    }

    // 解绑函数使用user_id
    public function deleteDgworkUserBind($userId)
    {
        if (empty($userId)) {
            return false;
        }
        return $this->entity->where('user_id', $userId)->delete();
    }

    // 确认是否存在绑定关系函数
    public function checkBindExist($data)
    {
        if(empty($data['employee_code']) || empty($data['user_id'])){
            return false;
        }
        return $this->entity->where('employee_code', $data['employee_code'])->orWhere('user_id', $data['user_id'])->count();
    }

    public function addDgworkUserBind($data)
    {
        if(empty($data['employee_code']) || empty($data['user_id'])){
            return false;
        }
        $data = [
            'employee_code' => $data['employee_code'],
            'user_id' => $data['user_id'],
        ];
        return $this->entity->create($data);
    }

    // 批量插入关联用户
    public function insertDgworkUserBind($data)
    {
        if(is_array($data) && count($data)>0){
            $this->entity->insert($data);
        }
        return false;
    }

}
