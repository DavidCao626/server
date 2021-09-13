<?php

namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\PermissionGroupRoleEntity;

class PermissionGroupRoleRepository extends BaseRepository
{
    public function __construct(PermissionGroupRoleEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getListByRoleId($roleId)
    {
        return $this->entity->where('role_id', $roleId)->first();
    }

    public function getRolePermissionByGroupId($group_id)
    {
        return $this->entity->where('group_id', $group_id)->first();
    }

    public function getListsByRoleIds($roleIds)
    {
        return $this->entity->whereIn('role_id', $roleIds)->where('group_id', '>', 0)->get();
    }
}
