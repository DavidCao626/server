<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkUserEntity;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class DingtalkUserRepository extends BaseRepository
{
    private $userRepository;
    public function __construct(DingtalkUserEntity $entity)
    {
        $this->userRepository = "App\EofficeApp\User\Repositories\UserRepository";
        parent::__construct($entity);
    }

    public function getUser($where = [])
    {
        return $this->entity->wheres($where)->first();
    }

    //清空表
    public function truncateDingtalkUser()
    {
        return $this->entity->truncate();
    }

    public function transferUsers($where)
    {
        return $this->entity->select(["userid", "oa_id"])->wheres($where)->get()->toArray();
    }

    public function getDingTalkUserIdById($user_id)
    {
        return $this->entity->select(["userid"])->where("oa_id", $user_id)->first();
    }

    public function getDingTalkOaIdById($user_id)
    {
        return $this->entity->select(["oa_id"])->where("userid", $user_id)->first();
    }

    public function getDingtalkUserList($param)
    {
        if ($param['type'] == 'unbind') {
            // 返回未绑定的用户
            $query = $this->entity->rightJoin('user', 'oa_id', '=', 'user.user_id')->select('user.user_accounts', 'user.user_name', 'userid');
            $query = $query->whereNull('userid');
        } else if ($param['type'] == 'bind') {
            // 返回绑定的用户
            $query = $this->entity->Join('user', 'oa_id', '=', 'user.user_id')->select('user.user_accounts', 'user.user_name', 'userid', 'user.user_id');
        } else {
            // 默认返回全部all
            $query = $this->entity->rightJoin('user', 'oa_id', '=', 'user.user_id')->select('user.user_accounts', 'user.user_name', 'userid');
        }
        // 离职用户不显示
        $query = $query->where('user.user_accounts', '!=', '');
        if (!empty($param['search']) && (count($param['search'])  > 0)) {
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

    // 解绑函数
    public function deleteByOaId($userId)
    {
        if (empty($userId)) {
            return false;
        }
        return $this->entity->where('oa_id', $userId)->delete();
    }

    // 确认OA用户是否存在函数
    public function checkOaUserId($userId)
    {
        if (empty($userId)) {
            return false;
        }
        return $this->entity->where('oa_id', $userId)->count();
    }

}
