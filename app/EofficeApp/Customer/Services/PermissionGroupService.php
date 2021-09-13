<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\PermissionGroupRepository;

class PermissionGroupService extends BaseService
{

    public function __construct()
    {
        $this->contactRecordRepository       = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
        $this->repository                    = 'App\EofficeApp\Customer\Repositories\PermissionGroupRepository';
        $this->permissionGroupRoleRepository = 'App\EofficeApp\Customer\Repositories\PermissionGroupRoleRepository';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    }

    public function lists($param)
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->repository), 'getTotal', 'lists', $param);
    }

    // 选择器，所有的权限组
    public function allLists($param)
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->repository), 'allTotal', 'allLists', $param);
    }

    // 新增权限组
    public function store(array $data)
    {
        if (!$validate = PermissionGroupRepository::validateInput($data)) {
            return ['code' => ['0x024002', 'customer']];
        }
        return app($this->repository)->insertData($data);
    }

    // 编辑权限组
    public function update($groupId, $data)
    {
        $list = app($this->repository)->getDetail($groupId);
        if (empty($list)) {
            return ['code' => ['0x024011', 'customer']];
        }
        if (!$validate = PermissionGroupRepository::validateInput($data)) {
            return ['code' => ['0x024002', 'customer']];
        }
        $where = [
            'id' => $groupId,
        ];
        return app($this->repository)->updateData($data, $where);
    }

    // 获取权限组详情
    public function show($groupId)
    {
        return app($this->repository)->show($groupId);
    }

    // 删除权限组
    public function delete($groupId)
    {
        // 权限组已被使用，则不能删除
        $list = app($this->permissionGroupRoleRepository)->getRolePermissionByGroupId($groupId);
        if (!empty($list)) {
            return ['code' => ['0x024012', 'customer']];
        }
        $where['id'] = [$groupId];
        return app($this->repository)->deleteByWhere($where);
    }

    // 获取权限组列表
    public function roleLists($param)
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->permissionGroupRoleRepository), 'getTotal', 'getCustomerPermissionGroupRoleList', $param);
    }

    // 获取角色权限组详情
    public function showRole($roleId)
    {
        return app($this->permissionGroupRoleRepository)->getListByRoleId($roleId);
    }

    // 修改角色的权限组
    public function updateRole($roleId, $input)
    {
        $data = [
            'group_id' => $input['group_id'] ?? 0,
            'role_id'  => $roleId,
        ];
        $list = app($this->permissionGroupRoleRepository)->getListByRoleId($roleId);
        if (empty($list)) {
            app($this->permissionGroupRoleRepository)->insertData($data);
        } else {
            app($this->permissionGroupRoleRepository)->updateData($data, ['role_id' => $roleId]);
        }
        PermissionGroupRepository::refreshPermissionGroup();
        return true;
    }
}
