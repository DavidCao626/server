<?php

namespace App\EofficeApp\Attendance\Traits;

trait AttendanceSettingTrait
{
    public function getUserIdByUserRoleDeptConfig($config, $allKey = 'all_member', $userKey = 'user_id', $deptKey ='dept_id', $roleKey = 'role_id')
    {
        $isAllMember = $config[$allKey] ?? 0;
        if ($isAllMember) {
            $users = app($this->userRepository)->getSimpleUserList(['noPage' => true]);
            return array_column($users, 'user_id');
        }
        $allUserIds = $config[$userKey] ?? [];
        $deptIds = $config[$deptKey] ?? [];
        $roleIds = $config[$roleKey] ?? [];
        if (!empty($deptIds)) {
            $deptUsers = app($this->userSystemInfoRepository)->getInfoByWhere(['dept_id' => [$deptIds, 'in']]);
            if (count($deptUsers) > 0) {
                $allUserIds = array_merge($allUserIds, array_column($deptUsers, 'user_id'));
            }
        }
        if (!empty($roleIds)) {
            $roleUserIds = app($this->userRoleRepository)->getRoleUsers(['role_id' => [$roleIds, 'in']]);
            $allUserIds = array_merge($allUserIds, $roleUserIds);
        }
        return array_unique($allUserIds);
    }
}
