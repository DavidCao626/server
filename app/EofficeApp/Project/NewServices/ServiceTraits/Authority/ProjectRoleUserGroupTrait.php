<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\Authority;

use App\EofficeApp\Project\Entities\ProjectRoleUserGroupEntity;
use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserGroupRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\User\Services\UserService;
use Illuminate\Support\Arr;

Trait ProjectRoleUserGroupTrait
{
    /**
     * 新生成角色后，生成对应的类型关联
     * @param $roleId
     * @param array $userGroup 已选择的用户信息
     */
    public static function createRoleUserGroupByRoleId($roleId, $userGroup) {
        $insertData = [];
        foreach ($userGroup as $type => $items) {
            foreach ($items as $item) {
                $insertData[] = [
                    'role_id' => $roleId,
                    'type' => $type,
                    'type_value' => $item,
                ];
            }
        }

        DatabaseManager::insertBatch(ProjectRoleUserGroupEntity::class, $insertData, true);
    }

    public static function updateRoleUserGroupByRoleId($roleId, $managerTypes)
    {
        self::deleteRoleUserGroupByRoleId($roleId);
        self::createRoleUserGroupByRoleId($roleId, $managerTypes);
    }

    public static function deleteRoleUserGroupByRoleId($roleId)
    {
        if ($roleId) {
            ProjectRoleUserGroupEntity::buildQuery(['role_id' => $roleId])->delete();
        }
    }

    /**
     * 新生成角色后，生成对应的数据穿透信息
     * @param $roleId
     * @param array $relations 已选择的数据穿透
     */
    public static function createDataRoleRelationByRoleId($roleId, $relations) {
        $insertData = [];
        foreach ($relations as $value) {
            $insertData[] = [
                'role_id' => $roleId,
                'type' => ProjectRoleUserGroupEntity::relationPenetrateKey,
                'type_value' => $value,
            ];
        }
        DatabaseManager::insertBatch(ProjectRoleUserGroupEntity::class, $insertData, true);
    }

    public static function updateDataRoleRelationByRoleId($roleId, $managerTypes)
    {
        self::deleteDataRoleRelationByRoleId($roleId);
        self::createDataRoleRelationByRoleId($roleId, $managerTypes);
    }

    public static function deleteDataRoleRelationByRoleId($roleId)
    {
        if ($roleId) {
            ProjectRoleUserGroupEntity::buildQuery(['role_id' => $roleId, 'type' => ProjectRoleUserGroupEntity::relationPenetrateKey])->delete();
        }
    }

    /**
     * 获取每个角色的用户穿透数据
     * @param $roleIds
     * @param $userId
     * @return array eg:[role_id => [user_id...]...]
     */
    public static function getRoleRelationPenetrateUsers($roleIds, $userId)
    {
        $data = ProjectRoleUserGroupRepository::buildMyRelationPenetrateQuery($roleIds)->get()->groupBy('role_id');
        $allValue = []; // 存在的穿透类型
        foreach ($data as $roleId => $item) {
            $data[$roleId] = $item->pluck('type_value')->toArray();
            $allValue = array_unique(array_merge($allValue, $data[$roleId]));
        }
        $data = $data->toArray();
        $allRelationPenetrateUsers = self::getAllRelationPenetrateUsers($userId, $allValue);

        foreach ($data as $roleId => $typeValue) {
            $tempUsers = array_extract($allRelationPenetrateUsers, $typeValue);
            $data[$roleId] = collect($tempUsers)->collapse()->unique()->toArray();
        }

        return $data;
    }


    /**
     * 获取用户的数据穿透用户数据
     * @param $userId
     * @param null $relationPenetrateData 默认获取全部，可传入部分获取部分数据
     * @return array [1: [user_id1...], 2: []...]
     */
    public static function getAllRelationPenetrateUsers($userId, $relationPenetrateData = null)
    {
        $relationPenetrateData = is_array($relationPenetrateData) ? $relationPenetrateData : ProjectRoleUserGroupEntity::getAllRelationPenetrateValue();
        $userService = self::getUserService();
        $relationPenetrateUsers = [];
        if(in_array(1, $relationPenetrateData)){
            $relationPenetrateUsers[1] = [$userId];
        }
        if(in_array(2, $relationPenetrateData)){
            $relationPenetrateUsers[2] = [];
            $result = $userService->getSubordinateArrayByUserId($userId,['include_leave' => true]);
            if($result['id']){
                $relationPenetrateUsers[2] = $result['id'];
            }
        }
        if(in_array(3, $relationPenetrateData)){
            $relationPenetrateUsers[3] = [];
            $ancestor = $userService->getSubordinateArrayByUserId($userId, ['all_subordinate' => 1, 'include_leave' => true]);
            if($ancestor['id']){
                $relationPenetrateUsers[3] = $ancestor['id'];
            }
        }
        if(in_array(4, $relationPenetrateData)){
            $relationPenetrateUsers[4] = [];
            $director = self::getDepartmentDirectorRepository()->getManageDeptByUser($userId)->pluck('dept_id')->toArray();
            if (!empty($director)) {
                // 如果是部门负责人，则获取当前部门下的人员id
                $belongsDept = self::getUserRepository()->getUserByAllDepartment($director)->pluck('user_id')->toArray();
                $relationPenetrateUsers[4] = $belongsDept;
            }
        }
        if(in_array(5, $relationPenetrateData)){
            $relationPenetrateUsers[5] = [];
            $deptId = OtherModuleRepository::buildUserSystemInfoQuery()->find($userId);
            $deptId = $deptId ? $deptId->dept_id : 0;
            if($result = UserService::getUserIdsByDeptIds([$deptId])){
                $relationPenetrateUsers[5] = $result;
            };
        }
        if(in_array(6, $relationPenetrateData)){
            $relationPenetrateUsers[6] = [];
            $userRoleIds = self::getRoleService()->getUserRole($userId);
            $userRoleIds = Arr::pluck($userRoleIds, 'role_id');
            if($result = UserService::getUserIdsByRoleIds($userRoleIds)){
                $relationPenetrateUsers[6] = $result;
            };
        }

        return $relationPenetrateUsers;
    }
}
