<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectRoleEntity;
use Illuminate\Support\Arr;

/**
 * 项目角色 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectRoleRepository extends BaseRepository {

    public function __construct(ProjectRoleEntity $entity) {
        parent::__construct($entity);
    }

    public static function buildQuery($params = [], $query = null)
    {
        return ProjectRoleEntity::buildQuery($params, $query);
    }

    // 获取默认角色的查询对象
    public static function buildDefaultQuery($params = [], $query = null)
    {
        return ProjectRoleEntity::buildQuery($params, $query)->withoutGlobalScope('not_default')->where('is_default', 1);
    }

    // 构建数据权限角色的查询对象
    public static function buildDataRoleQuery($params = [], $query = null)
    {
        return self::buildQuery($params, $query)->where('manager_type', '>', 0);
    }

    // 构建监控权限角色的查询对象
    public static function buildMonitorRoleQuery($params = [], $query = null)
    {
        // 提前获取，否则会被后续解析到sql中
        $managerType = Arr::get($params, 'manager_type');
        unset($params['manager_type']);

        $query = self::buildQuery($params, $query)->where('manager_type', 0);

        // 处理分类查询
        if ($managerType) {
            $roleIds = ProjectRoleManagerTypeRepository::buildQuery(['manager_type' => $managerType])
                ->orWhere('manager_type', 'all')
                ->pluck('role_id')->unique()->toArray();
            $query->whereIn('role_id', $roleIds);
        }

        return $query;
    }

    public static function getRoles()
    {
        return self::buildDataRoleQuery()->select('role_id', 'role_field_key', 'type', 'manager_type')->get();
    }

}
