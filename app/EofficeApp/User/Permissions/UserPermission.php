<?php
namespace App\EofficeApp\User\Permissions;
use Illuminate\Support\Arr;
class UserPermission
{
    private $userRoleRepository;
    private $roleRepository;
    private $userSystemInfoEntity;
    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。
    public $rules = [
        'userSystemEmptyPassword' => 'adminPermission',
        'userSystemCreate' => 'userModifyRole',
        'userSystemEdit' => 'userModifyRole',
    ];
    public function __construct() 
    {
        $this->userRoleRepository = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->userSystemInfoEntity = 'App\EofficeApp\User\Entities\UserSystemInfoEntity';
        $this->roleRepository = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
    }

    //admin用户的特有权限
    public function adminPermission($own, $data, $urlData)
    {
        return $own['user_id'] === 'admin';
    }

    //删除用户限制不能删除admin，且只能删除权限比自己小的用户
    public function userSystemDelete($own, $data, $urlData)
    {
        if ($own['user_id'] === 'admin') {
            return true;
        }
        $deleteUserId = Arr::get($urlData, 'userId');
        if ($deleteUserId == 'admin') {
            return false;
        }
        // 获取可管理部门id
        $deptIds = app($this->userService)->getUserCanManageDepartmentId($own);
        if ($deptIds != 'all') {
            $deleteUserDeptId = app($this->userService)->getDeptInfoByUserId($deleteUserId);
            if (isset($deleteUserDeptId['dept_id']) && !in_array($deleteUserDeptId['dept_id'], $deptIds)) {
                return false;
            }
        }
        return $this->compareUserRole($own['user_id'], $deleteUserId);
    }

    //添加编辑用户时，用户角色级别不能高于当前登录用户的最高用户级别
    public function userModifyRole($own, $data, $urlData)
    {
        if ($own['user_id'] === 'admin') {
            return true;
        }

        //编辑时需要判断用户权限
        if (isset($data['user_id'])) {
            $status = $this->compareUserRole($own['user_id'], $data['user_id']);
            if (!$status) {
                return false;
            }
        }
        $roleIdString = Arr::get($data, 'role_id_init');
        if (!$roleIdString) {
            return false;
        }
        $roleIds = explode(',', $roleIdString);
        $minRoleNo = app($this->userRoleRepository)->buildUserRolesQuery($own['user_id'])->min('role_no');
        $testMinRoleNo = app($this->roleRepository)->entity->whereIn('role_id', $roleIds)->min('role_no');

        return $testMinRoleNo >= $minRoleNo;
    }

    /**
     * 比较两用户的权限大小，不含权限相等
     * @param string $maxUserId  权限大的用户id
     * @param string $minUserId  权限小的用户id
     * @return bool 符合预期返回true，否则false
     */
    private function compareUserRole($maxUserId, $minUserId)
    {
        $userMaxRoleNos = app($this->userSystemInfoEntity)
            ->whereIn('user_id', [$maxUserId, $minUserId])
            ->pluck('max_role_no', 'user_id');
        if (count($userMaxRoleNos) !== 2) {
            return false;
        }
        return $userMaxRoleNos[$maxUserId] < $userMaxRoleNos[$minUserId];
    }

}
