<?php

namespace App\EofficeApp\System\Permission\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Permission\Repositories\PermissionGroupRepository;
use DB;

class PermissionService extends BaseService
{

    public function __construct()
    {
        parent::__construct();
        $this->permissionGroupRepository = 'App\EofficeApp\System\Permission\Repositories\PermissionGroupRepository';
        $this->permissionUserRepository = 'App\EofficeApp\System\Permission\Repositories\PermissionUserRepository';
        $this->permissionDeptRepository = 'App\EofficeApp\System\Permission\Repositories\PermissionDeptRepository';
        $this->permissionRoleRepository = 'App\EofficeApp\System\Permission\Repositories\PermissionRoleRepository';
        $this->permissionTypeRepository = 'App\EofficeApp\System\Permission\Repositories\PermissionTypeRepository';
    }

    public function getPermissionGroups($param) {
        $param = $this->parseParams($param);
        return $this->response(app($this->permissionGroupRepository), 'getPermissionGroupsTotal', 'getPermissionGroups', $param);
    }

    public function addPermissionGroup($data)
    {
        $insertData = [
            'group_name'     => $data['group_name'],
            'purview_type'   => $data['purview_type'],
            'all_department' => $data['all_department'] ?? 1,
            'all_superior'   => $data['all_superior'] ?? 1,
            'all_sub_dept'   => $data['all_sub_dept'] ?? 1,
            'all_sub'        => $data['all_sub'] ?? 1,
            'all_staff'      => $data['all_staff'] ?? 1,
            'remark'         => $data['remark'] ?? '',
            'type_id'        => $data['type_id'] ?? 1
        ];

        return $this->handleGroupData($insertData, $data, '', false);
    }

    public function editPermissionGroup($groupId, $data) {
        $updateData = [
            'group_name'     => $data['group_name'],
            'purview_type'   => $data['purview_type'],
            'all_department' => $data['all_department'] ?? 1,
            'all_superior'   => $data['all_superior'] ?? 1,
            'all_sub_dept'   => $data['all_sub_dept'] ?? 1,
            'all_sub'        => $data['all_sub'] ?? 1,
            'all_staff'      => $data['all_staff'] ?? 1,
            'remark'         => $data['remark'] ?? '',
            'type_id'        => $data['type_id'] ?? 1
        ];

        $updateData = $this->handleGroupData($updateData, $data, $groupId);
        
        return app($this->permissionGroupRepository)->updateData($updateData, ['group_id' => $groupId]);
    }

    public function handleGroupData($groupData, $data, $groupId = "", $type = true)
    {
        $data['all_department'] = $data['all_department'] ?? 1;
        $data['all_superior']   = $data['all_superior'] ?? 1;

        if ($data['purview_type'] == 1 && $data['all_department'] != 1) {
            $groupData['dept_ids'] = is_string($data['dept_ids']) ? $data['dept_ids'] : implode(',', $data['dept_ids']);
        }
        if ($data['purview_type'] == 2 && $data['all_superior'] != 1) {
            $groupData['superiors'] = is_string($data['superiors']) ? $data['superiors'] : implode(',', $data['superiors']);
        }

        if ($type) {
            $where = ['group_id' => [$groupId]];
        } else {
            $groupId = app($this->permissionGroupRepository)->insertGetId($groupData);
        }

        if (!is_array($data['manager'])) {
            $data['manager'] = explode(',', $data['manager']);
        }

        if ($data['purview_type'] == 3) {
            $userData = $deptData = $roleData = [];
            if ($type) {
                app($this->permissionUserRepository)->deleteBywhere($where);
                app($this->permissionDeptRepository)->deleteBywhere($where);
                app($this->permissionRoleRepository)->deleteBywhere($where);
            }

            foreach ($data['manager'] as $value) {
                if ($data['all_staff'] != 1) {
                    if (!empty($data['user_id'])) {
                        $userIds    = $data['user_id'] == 'all' ? 'all' : implode(',', $data['user_id']);
                        $userData[] = ['group_id' => $groupId, 'manager' => $value, 'user_id' => $userIds];
                    }
                    if (!empty($data['dept_id'])) {
                        $deptIds    = $data['dept_id'] == 'all' ? 'all' : implode(',', $data['dept_id']);
                        $deptData[] = ['group_id' => $groupId, 'manager' => $value, 'dept_id' => $deptIds];
                    }
                    if (!empty($data['role_id'])) {
                        $roleIds    = $data['role_id'] == 'all' ? 'all' : implode(',', $data['role_id']);
                        $roleData[] = ['group_id' => $groupId, 'manager' => $value, 'role_id' => $roleIds];
                    }
                } else {
                    $userData[] = ['group_id' => $groupId, 'manager' => $value, 'user_id' => 'all'];
                    $deptData[] = ['group_id' => $groupId, 'manager' => $value, 'dept_id' => 'all'];
                    $roleData[] = ['group_id' => $groupId, 'manager' => $value, 'role_id' => 'all'];
                }
            }

            if (!empty($userData)) {
                app($this->permissionUserRepository)->insertMultipleData($userData);
            }
            if (!empty($deptData)) {
                app($this->permissionDeptRepository)->insertMultipleData($deptData);
            }
            if (!empty($roleData)) {
                app($this->permissionRoleRepository)->insertMultipleData($roleData);
            }
        }

        return $groupData;
    }
    // 删除权限组
    public function deletePermissionGroup($groupIds) {
        $groupIds = explode(',', $groupIds);
        $idSize   = sizeof($groupIds);
        if($idSize == 0){
            return ['code' => ['0x015033', 'system']];
        }

        app($this->permissionUserRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);
        app($this->permissionDeptRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);
        app($this->permissionRoleRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);
        app($this->permissionGroupRepository)->deleteBywhere(['group_id' => [$groupIds, 'in']]);

        return true;
    }
    // 权限组详情
    public function getGroupDetail($groupId)
    {
        $data = app($this->permissionGroupRepository)->getDetail($groupId)->toArray();

        if (isset($data['purview_type'])) {
            $data['dept_ids'] = empty($data['dept_ids']) ? [] : explode(',', $data['dept_ids']);
            $data['superiors'] = empty($data['superiors']) ? [] : explode(',', $data['superiors']);

            $where = ['group_id' => [$groupId]];
            if ($data['purview_type'] == 3) {
                $userObj = app($this->permissionUserRepository);
                $deptObj = app($this->permissionDeptRepository);
                $roleObj = app($this->permissionRoleRepository);

                $users           = $userObj->getPurviewByWhere($where)->pluck('manager')->toArray();
                $depts           = $deptObj->getPurviewByWhere($where)->pluck('manager')->toArray();
                $roles           = $roleObj->getPurviewByWhere($where)->pluck('manager')->toArray();
                $data['manager'] = array_values(array_unique(array_merge($users, $depts, $roles)));

                if ($data['all_staff'] != 1) {
                    $tempUser        = $userObj->getOnePurview($where);
                    $tempDept        = $deptObj->getOnePurview($where);
                    $tempRole        = $roleObj->getOnePurview($where);
                    $data['user_id'] = isset($tempUser->user_id) ? ($tempUser->user_id == 'all' ? 'all' : explode(',', $tempUser->user_id)) : [];
                    $data['dept_id'] = isset($tempDept->dept_id) ? ($tempDept->dept_id == 'all' ? 'all' : explode(',', $tempDept->dept_id)) : [];
                    $data['role_id'] = isset($tempRole->role_id) ? ($tempRole->role_id == 'all' ? 'all' : explode(',', $tempRole->role_id)) : [];
                } else {
                    $data['user_id'] = $data['dept_id'] = $data['role_id'] = 'all';
                }
            }
        }

        return $data;
    }
    // 权限分类
    public function getPermissionType($param) {
        $param = $this->parseParams($param);
        return $this->response(app($this->permissionTypeRepository), 'getPermissionTypeTotal', 'getPermissionTypes', $param);
    }
    // 新建权限分类
    public function addPermissionType($data) {
        if (!isset($data['type_name'])) {
            return ['code' => ['0x015028', 'system']];
        }

        if (empty($data['type_order'])) {
            $data['type_order'] = 0;
        }

        return app($this->permissionTypeRepository)->insertData($data);
    }

    public function editPermissionType($typeId, $data) {
        if (!isset($data['type_name'])) {
            return ['code' => ['0x015028', 'system']];
        }

        return app($this->permissionTypeRepository)->updateData(['type_name' => $data['type_name'], 'type_order' => $data['type_order']], ['type_id' => [$typeId]]);
    }

    public function deletePermissionType($typeId) {
        return app($this->permissionTypeRepository)->deleteById($typeId);
    }

    public function getPermissionTypeDetail($typeId) {
        return app($this->permissionTypeRepository)->getDetail($typeId);
    }
}
