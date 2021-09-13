<?php

namespace App\EofficeApp\System\Permission\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Permission\Entities\PermissionGroupEntity;
use DB;
use Schema;

/**
 * 权限Repository类:提供权限组及权限成员。
 *
 * @author 牛晓克
 *
 * @since  2019-04-09 创建
 */
class PermissionGroupRepository extends BaseRepository
{
    public function __construct(PermissionGroupEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取权限组列表
     *
     * @param  array $param 查询条件
     *
     * @since  2019-04-09
     */
    public function getPermissionGroups($param = []) {
        $default = [
            'fields'   => ["*"],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => []
        ];

        $param = array_merge($default, array_filter($param));

        return $this->entity->select($param['fields'])
                            ->leftJoin('system_permission_type', 'system_permission_type.type_id', '=', 'system_permission_group.type_id')
                            ->wheres($param['search'])
                            ->parsePage($param['page'], $param['limit'])
                            ->orders($param['order_by'])
                            ->get();
    }

    public function getPermissionGroupsTotal($param) {
        $where = isset($param['search']) ? $param['search'] : [];

        return $this->entity->select(['group_id'])->wheres($where)->count();
    }

    public function insertGetId($data = []) {
        return $this->entity->insertGetId($data);
    }
}
