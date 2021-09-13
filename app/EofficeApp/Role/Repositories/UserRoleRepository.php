<?php

namespace App\EofficeApp\Role\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Role\Entities\RoleEntity;
use App\EofficeApp\Role\Entities\UserRoleEntity;

/**
 * 人员角色Repository类:提供人员角色表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class UserRoleRepository extends BaseRepository
{
    public function __construct(UserRoleEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取人员角色
     *
     * @param  array $where 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-11-18 创建
     */
    public function getUserRole($where, $from = 0)
    {
        if ($from == 1) {
            return $this->entity->select(['user_id', 'role_id'])->wheres($where)->get()->toArray();
        }

        return $this->entity->select(['user_id', 'role_id'])->where($where)->get()->toArray();
    }

    /**
     * 获取人员权限
     *
     * @param  string $userId 用户id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-30 创建
     */
    public function getUserPermissions($userId)
    {
        return $this->entity
            ->select(['role_id', 'user_id'])
            ->with(['hasManyPermission' => function ($query) {
                $query->select(['role_id', 'function_id']);
            }])
            ->where('user_id', $userId)
            ->get()
            ->toArray();
    }

    /**
     *
     * @param type $where
     * 获取角色信息
     */
    public function getUserByWhere($where)
    {
        return $this->entity->wheres($where)->get()->toArray();
    }

    /**
     * 获取权限人员
     *
     * @param  string $userId 用户id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2017-04-01 创建
     */
    public function getRoleUsers($where)
    {
        return $this->entity->distinct()->wheres($where)->pluck('user_id')->toArray();
    }

    //构建用户角色的查询query对象
    public function buildUserRolesQuery($userId, $query = null)
    {
        $roleName = (new RoleEntity())->getTable();
        $userRoleName = $this->entity->getTable();

        $query = $query ? $query : $this->entity->newQuery();
        $query->where('user_id', $userId)
            ->leftJoin($roleName, "{$roleName}.role_id", "{$userRoleName}.role_id");

        return $query;
    }
}
