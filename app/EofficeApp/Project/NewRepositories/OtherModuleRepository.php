<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Role\Entities\RoleEntity;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\User\Entities\UserSystemInfoEntity;
use Illuminate\Support\Arr;
/**
 * 项目管理 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class OtherModuleRepository extends ProjectBaseRepository {

    public static function buildUserQuery($params = [], $query = null)
    {
        $query = is_null($query) ? UserEntity::query() : $query;

        $userName = Arr::get($params, 'user_name');
        self::buildLikeQuery($query, 'user_name', $userName);

        $inUserId = Arr::get($params, 'in_user_id');
        if ($inUserId) {
            $query->whereIn('user_id', $inUserId);
        }

        // 获取部门名称，部门无软删除
        if (Arr::get($params, 'with_dept')) {
            $query->withTrashed();
            $query->with(['userHasOneSystemInfo' => function ($query) {
                $query->withTrashed();
                $query->with('userSystemInfoBelongsToDepartment');
            }]);
        }
        return $query;
    }

    public static function buildDepartmentQuery($params = [], $query = null)
    {
        $query = is_null($query) ? DepartmentEntity::query() : $query;
        
        $inDeptId = Arr::get($params, 'in_dept_id');
        if ($inDeptId) {
            $query->whereIn('dept_id', $inDeptId);
        }

        self::buildLikeQuery($query, 'dept_name', Arr::get($params, 'dept_name'));

        return $query;
    }

    public static function buildRoleQuery($params = [], $query = null)
    {
        $query = is_null($query) ? RoleEntity::query() : $query;


        $inRoleId = Arr::get($params, 'in_role_id');
        if ($inRoleId) {
            $query->whereIn('role_id', $inRoleId);
        }

        return $query;
    }

    public static function buildUserSystemInfoQuery($params = [], $query = null)
    {
        $query = is_null($query) ? UserSystemInfoEntity::query() : $query;

        $inDeptId = Arr::get($params,'in_dept_id');
        if (is_array($inDeptId)) {
            $query->whereIn('dept_id', $inDeptId);
        }

        return $query;
    }
}
