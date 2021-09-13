<?php

namespace App\EofficeApp\PersonnelFiles\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesPermissionEntity;
use App\EofficeApp\PersonnelFiles\Enums\Permission\ManagerType;
use App\EofficeApp\PersonnelFiles\Enums\Permission\Ranges;
use App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesPermissionRepository;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
use App\Exceptions\ErrorMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Redis;

class PersonnelFilesPermission extends BaseService
{
    const REDIS_QUERY_DEPTS_KEY = 'personnel_files_query_depts';
    const REDIS_MANAGE_DEPTS_KEY = 'personnel_files_manage_depts';

    private $personnelFilesPermissionRepository;

    public function __construct(
        PersonnelFilesPermissionRepository $personnelFilesPermissionRepository
    ) {
        parent::__construct();
        $this->personnelFilesPermissionRepository = $personnelFilesPermissionRepository;
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
    }

    /**
     * @param $params
     * @return bool|\string[][]
     */
    public function setPermission($params)
    {
        if (!isset($params['range']) || $this->isApplyRangeEmpty($params['range'])) {
            return ['code' => ['0x022008', 'personnelFiles']];
        }
        if (isset($params['all_purview']) && !$params['all_purview'] && empty($params['dept_id'])) {
            return ['code' => ['0x022009', 'personnelFiles']];
        }
        $managers = $this->appendManagerTypeToManager($params['manager_id']);
        if(isset($params['id']) &&  $params['id']){
            $this->personnelFilesPermissionRepository->deleteById($params['id']);
        }
        foreach ($managers as $manager) {
            PersonnelFilesPermissionEntity::updateOrCreate(
                $manager,
                [
                    'all_purview' => $params['all_purview'] ?? 0,
                    'dept_id' => $params['dept_id'] ?? [],
                    'include_children' => $params['include_children'] ?? 1,
                    'range' => $params['range'],
                ]
            );
        }

        $this->clearPermissionCache();

        return true;
    }

    /**
     * @param $range
     * @return bool
     */
    private function isApplyRangeEmpty($range)
    {
        if (isset($range['query']) && $range['query']) {
            return false;
        }
        if (isset($range['manage']) && $range['manage']) {
            return false;
        }

        return true;
    }

    /**
     * @param $params
     * @return array
     */
    public function getPermissionList($params)
    {
        $params = $this->parseParams($params);

        /** @var Collection $permissions */
        $list = $this->personnelFilesPermissionRepository->getPermissionList($params);
        $list->load('manager');
        foreach ($list as $permission) {
            $permission->manager_name = $this->getManagerName(
                $permission->manager, $permission->manager_type
            );
            unset($permission->manager);
        }

        $total = $this->personnelFilesPermissionRepository->getPermissionCount($params);

        return compact('list', 'total');
    }

    /**
     * @param $manager
     * @param $type
     * @return string
     */
    public function getManagerName($manager, $type)
    {
        if (!$manager) {
            return '';
        }

        switch ($type) {
            case 'user':
                return $manager->user_name;
            case 'dept':
                return $manager->dept_name;
            case 'role':
                return $manager->role_name;
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getPermission($id)
    {
        $permission = PersonnelFilesPermissionEntity::find($id);
        $managerArray = [
            'dept_id' => [],
            'role_id' => [],
            'user_id' => [],
        ];
        switch ($permission->manager_type) {
            case ManagerType::DEPT:
                $managerArray['dept_id'] = [$permission->manager_id];
                break;
            case ManagerType::USER:
                $managerArray['user_id'] = [$permission->manager_id];
                break;
            case ManagerType::ROLE:
                $managerArray['role_id'] = [$permission->manager_id];
        }
        $permission->manager_id = $managerArray;

        return $permission;
    }

    /**
     * @param $managers
     * @return array
     */
    public function appendManagerTypeToManager($managers)
    {
        $result = [];
        $depts = $managers['dept_id'] ?? [];
        $roles = $managers['role_id'] ?? [];
        $users = $managers['user_id'] ?? [];
        foreach ($depts as $deptId) {
            $result[] = [
                'manager_id' => $deptId,
                'manager_type' => ManagerType::DEPT,
            ];
        }
        foreach ($roles as $roleId) {
            $result[] = [
                'manager_id' => $roleId,
                'manager_type' => ManagerType::ROLE,
            ];
        }
        foreach ($users as $userId) {
            $result[] = [
                'manager_id' => $userId,
                'manager_type' => ManagerType::USER,
            ];
        }

        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    public function deletePermission($id)
    {
        $id = explode(',', $id);
        PersonnelFilesPermissionEntity::destroy($id);

        $this->clearPermissionCache();

        return true;
    }

    /**
     * @param $own
     * @return array|string
     */
    public function getQueryPermittedDepts($own)
    {
        if (!isset($own['user_id'])) {
            return [];
        }

        if ($value = Redis::hget(self::REDIS_QUERY_DEPTS_KEY, $own['user_id'])) {
            return json_decode($value, true);
        }

        $range = [Ranges::QUERY, Ranges::QUERY + Ranges::MANAGE];

        $depts = $this->getPermittedDepts($own, $range);

        Redis::hset(self::REDIS_QUERY_DEPTS_KEY, $own['user_id'], json_encode($depts));

        return $depts;
    }

    /**
     * @param $own
     * @return array|string
     */
    public function getManagePermittedDepts($own)
    {
        if (!isset($own['user_id'])) {
            return [];
        }

        if ($value = Redis::hget(self::REDIS_MANAGE_DEPTS_KEY, $own['user_id'])) {
            return json_decode($value, true);
        }

        $range = [Ranges::MANAGE, Ranges::QUERY + Ranges::MANAGE];

        $depts = $this->getPermittedDepts($own, $range);

        Redis::hset(self::REDIS_MANAGE_DEPTS_KEY, $own['user_id'], json_encode($depts));

        return $depts;
    }

    /**
     * @param $own
     * @param $range
     * @return array|string
     */
    public function getPermittedDepts($own, $range)
    {
        $permissions = $this->getOwnPermissions($own, $range);
        if (empty($permissions)) {
            return [];
        }
        $parentDepts = [];
        $onlySelfDepts = [];
        foreach ($permissions as $permission) {
            if ($permission['all_purview']) {
                return 'all';
            }
            if ($permission['include_children']) {
                $parentDepts = array_merge($parentDepts, $permission['dept_id']);
            } else {
                $onlySelfDepts = array_merge($onlySelfDepts, $permission['dept_id']);
            }
        }

        $allDepts = DepartmentEntity::select(['dept_id', 'arr_parent_id'])->get()->toArray();
        $depts = [];
        foreach ($allDepts as $dept) {
            if ($this->isPermittedDept($dept, $onlySelfDepts, $parentDepts)) {
                $depts[] = $dept['dept_id'];
            }
        }

        return $depts;
    }

    /**
     * @param $department
     * @param $onlySelfDepts
     * @param $parentDepts
     * @return bool
     */
    private function isPermittedDept($department, $onlySelfDepts, $parentDepts)
    {
        $deptId = $department['dept_id'];
        $parentIds = explode(',', $department['arr_parent_id']);
        if (in_array($deptId, $onlySelfDepts) || in_array($deptId, $parentDepts)) {
            return true;
        }
        if (!empty(array_intersect($parentIds, $parentDepts))) {
            return true;
        }
        return false;
    }
    /**
     * @param $own
     * @param $range
     * @return array
     */
    public function getOwnPermissions($own, $range)
    {
        $search = [];
        $search['user'] = isset($own['user_id']) ? [$own['user_id']] : [];
        $search['dept'] = isset($own['dept_id']) ? [$own['dept_id']] : [];
        $search['role'] = $own['role_id'] ?? [];
        if (empty($search['user']) && $search['dept'] && $search['role']) {
            return [];
        }
        $search['range'] = [$range, 'in'];
        $params = [
            'fields' => ['id', 'all_purview', 'dept_id', 'include_children'],
            'search' => $search,
        ];

        return $this->personnelFilesPermissionRepository->getPermissionList($params)->toArray();
    }

    /**
     * 清空缓存
     */
    private function clearPermissionCache()
    {
        Redis::del(self::REDIS_QUERY_DEPTS_KEY);
        Redis::del(self::REDIS_MANAGE_DEPTS_KEY);
    }

    /**
     * @param $deptId
     * @param $permittedDept array|string
     * @throws ErrorMessage
     */
    public function checkDeptPermissionByPermittedDept($deptId, $permittedDept)
    {
        if ($permittedDept == 'all' || in_array($deptId, $permittedDept)) {
            return;
        }
        throw new ErrorMessage('personnelFiles.0x022010');
    }

    /**
     * @param $deptId
     * @param $own
     * @param bool $manage
     * @throws ErrorMessage
     */
    public function checkDeptPermissionByOwn($deptId, $own, $manage = false)
    {
        $permittedDept = $manage ? $this->getManagePermittedDepts($own)
        : $this->getQueryPermittedDepts($own);

        $this->checkDeptPermissionByPermittedDept($deptId, $permittedDept);
    }

    public function getManger($deptId)
    {
        $search = [];
        $search['range'] = [[2,3], 'in'];
        $params = [];
        $params['search'] = $search;
        $havePermission = [];
        $permissions = $this->personnelFilesPermissionRepository->getPermissionList($params)->toArray();
        foreach ($permissions as $permission) {
            if ($permission['all_purview'] == 1) {
                $havePermission[] = $permission;
                continue;
            } else {
                $dept = $permission['dept_id'];
                if (!empty($dept)) {
                    if ($permission['include_children'] == 1) {
                        $currentDept = DepartmentEntity::select(['dept_id', 'arr_parent_id'])->where('dept_id', $deptId)->first();
                        $arrParent = explode(',', $currentDept->arr_parent_id);
                        if (in_array($deptId, $dept) || !empty(array_intersect($arrParent, $dept))) {
                            $havePermission[] = $permission;
                            continue;
                        }
                    } else {
                        if (in_array($deptId, $dept)) {
                            $havePermission[] = $permission;
                            continue;
                        }
                    }
                }
            }

        }
        $personnelPower = $this->permissionTransferUser($havePermission);
        $menuPower =  app($this->userMenuService)->getMenuRoleUserbyMenuId(417);
        return  array_merge(array_intersect($personnelPower,$menuPower));
    }

    public function permissionTransferUser($permissions)
    {
        $users = [];
        foreach ($permissions as $permission) {
            $type = $permission['manager_type'];
            $user = [];
            switch ($type) {
                case 'role':
                    $param['search'] = ["role_id" => [$permission['manager_id'], "in"]];
                    $res = app($this->userService)->getAllUserIdString($param);
                    $user = explode(",", $res);
                    break;
                case 'user':
                    $user = $permission['manager_id'];
                    $user = explode(",", $user);
                    break;
                case 'dept':
                    $param['search'] = ["dept_id" => [$permission['manager_id'], "in"]];
                    $res = app($this->userService)->getAllUserIdString($param);
                    $user = explode(",", $res);
                    break;

            }
            $users = array_merge($user,$users);
        }
        return array_unique($users);
    }

}
