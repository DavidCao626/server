<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\Authority;

use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\Entities\RoleFunctionPageEntity;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use App\EofficeApp\Project\NewRepositories\RoleFunctionPageRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

Trait ProjectRoleFunctionPageTrait
{
    // 根据查询条件，获取多组权限数据
    public static function roleFunctionPageTreeList($params)
    {
        $roleId = Arr::get($params, 'role_id', 0);
        $managerState = Arr::get($params, 'manager_state');
        $queryParams = [
            'role_id' => $roleId,
            'manager_state' => $managerState
        ];
        $data = RoleFunctionPageRepository::buildQuery($queryParams)->get();
        $tree = [];
        $tree = self::getFunctionPageTreeList($data);

        return $tree;
    }

    /**
     * 新生成角色后，生成对应的功能权限配置
     * @param $roleId
     * @param array $roleFunctionPageCheckedData 已勾选的功能id，根据项目状态分类 eg:[1:[],2:[]]
     */
    public static function createRoleFunctionPageByRoleId($roleId, $roleFunctionPageCheckedData) {
        $functionPages = self::functionPageList();
        $insertData = [];
        foreach (ProjectManagerEntity::getAllManagerStates() as $managerState) {
            $functionPagesTemp = $functionPages[$managerState];
            $checkedData = Arr::get($roleFunctionPageCheckedData, $managerState);
            foreach ($functionPagesTemp as $functionPage) {
                $functionPageId = $functionPage['function_page_id'];
                $insertData[] = [
                    'role_id' => $roleId,
                    'function_page_id' => $functionPageId,
                    'manager_state' => $managerState,
                    'is_checked' => in_array($functionPageId, $checkedData) ? 1 : 0,
                    'is_fold' => 0,
                    'is_default' => 0,
                    'examine_config' => '',
                ];
            }
        }

        DatabaseManager::insertBatch(RoleFunctionPageEntity::class, $insertData, true);
    }

    public static function updateRoleFunctionPageByRoleId($roleId, $roleFunctionPageCheckedData)
    {
        self::deleteRoleFunctionPageByRoleId($roleId);
        self::createRoleFunctionPageByRoleId($roleId, $roleFunctionPageCheckedData);
    }

    public static function deleteRoleFunctionPageByRoleId($roleId)
    {
        if ($roleId) {
            RoleFunctionPageRepository::buildQuery(['role_id' => $roleId])->delete();
        }
    }

    public static function createDefaultRoleFunctionPages($managerType)
    {
        // 组装新旧id对照数据
        $roles = ProjectRoleRepository::buildQuery()
            ->withoutGlobalScope('not_default')
            ->whereIn('manager_type', [0, $managerType])
            ->select('role_id', 'type', 'role_field_key', 'manager_type')
            ->get()
            ->each(function ($item) {
                $item['type'] = $item['type'] . '_' . $item['role_field_key'];
            })->groupBy('manager_type');
        $roleMap = [];
        $defaultRoles = $roles[0]->pluck('role_id', 'type');
        $newRoles = $roles[$managerType]->pluck('role_id', 'type');
        foreach ($newRoles as $type => $roleId) {
            $roleMap[$defaultRoles[$type]] = $roleId;
        }

        $defaultData = RoleFunctionPageRepository::buildDefaultDataQuery()->get()->toArray();
        foreach ($defaultData as &$items) {
            $items = (array) $items;
            $items['role_id'] = $roleMap[$items['role_id']];
            $items['is_default'] = 0;
            unset($items['id']);
        }

        DatabaseManager::insertBatch(RoleFunctionPageEntity::class, $defaultData, true);
    }

    private static function getFunctionPageTreeList(Collection $data)
    {
        $functionPageTree = self::functionPageTreeList();
        $data = $data->groupBy('manager_state');
        $tree = [];
        foreach ($data as $managerState => $item) {
            $data[$managerState] = $item->pluck('is_checked', 'function_page_id')->toArray();
        }
        $data = $data->toArray();
        $managerStates = [1, 2, 3, 4, 5];
        foreach ($managerStates as $managerState) {
            $authoritiesTemp = Arr::get($data, $managerState);
            $key = $managerState;
            $tree[$key] = $functionPageTree[$managerState];
            self::setAuthorityToTree($tree[$key], $authoritiesTemp);
        }
        return $tree;
    }

    /**
     * 给功能树插入权限
     * @param $functionPageTree
     * @param $authorities
     */
    private static function setAuthorityToTree(&$functionPageTree, $authorities)
    {
        foreach ($functionPageTree as &$item) {
            $item['is_checked'] = Arr::get($authorities, $item['function_page_id'], 0);
            if (Arr::has($item, 'children')) {
                self::setAuthorityToTree($item['children'], $authorities);
            }
        }
    }

}
