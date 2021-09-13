<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission;

use App\EofficeApp\Project\Entities\FunctionPageApiConfigEntity;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
class FunctionPageApiConfigManager
{

    // 过滤功能权限,返回权限与配置，权限是key，配置是value数组
    public static function filterFunctionPages(Model $model, Collection $functionPages)
    {
        // 获取每个功能的角色数组
        $functionPageRoleIds = [];
        foreach ($functionPages as $item) {
            $idTemp = $item['function_page_id'];
            $functionPageRoleIds[$idTemp] =  $functionPageRoleIds[$idTemp] ?? [];
            $functionPageRoleIds[$idTemp][] = $item['role_id'];
        }
        
        $functionPages = $functionPages->unique('function_page_id');
        $functionPagesData = [];
        foreach ($functionPages as $key => $item) {
            $roleIds = $functionPageRoleIds[$item['function_page_id']];
            $configs = $item->configs;
            $testResult = self::testPermission($model, $roleIds, $configs);
            if ($testResult === false) {
                continue;
            }
            $functionPagesData[$item['function_page_id']] = $testResult;
        }
        return $functionPagesData;
    }

    // 返回false代表权限验证不过关
    private static function testPermission(Model $model, array $roleIds, collection $configs)
    {
        if ($configs->isEmpty()) {
            return []; // 没有配置不验证
        }

        foreach ($configs as $config) {
            $roleConfig = $config->role_ids;
            $filter = $config->filter;
            $testResult = self::testRoleIds($roleConfig, $roleIds) && HelpersManager::testFilter($model, $filter);
            if ($testResult) {
                return $config->toArray();
            }
        }
        return false;
    }

    private static function allowReturnConfig($functionPageConfig)
    {
        $allowFields = []; // 需要返回到前端的配置字段
        return array_extract($functionPageConfig->toArray(), $allowFields);
    }

    // 验证配置得角色
    private static function testRoleIds(array $roleIdConfig, array $allRoleIds)
    {
        if (empty($roleIdConfig)) {
            return true;
        }
        $type = Arr::get($roleIdConfig, 'type');
        $queryParams = Arr::get($roleIdConfig, 'values');
        $roleIds = ProjectRoleRepository::buildQuery($queryParams)->pluck('role_id')->toArray();
        if ($type === 'white') {
            return (bool) array_intersect($roleIds, $allRoleIds);
        } else if ($type === 'black') {
            return array_diff($allRoleIds, $roleIds); // 存在不在黑名单的就代表有权限
        }

        return false;
    }

    // 将配置入数据库
    public static function initFunctionPageApiConfigData()
    {
        $config = HelpersManager::getProjectConfig('api_config');
        $initData = [];
        foreach ($config as $functionPageId => $items) {
            foreach ($items as $item) {
                $initData[] = [
                    'function_page_id' => $functionPageId,
                    'sort' => $item['sort'] ?? 1,
                    'role_ids' => json_encode(Arr::get($item, 'role_ids', [])),
                    'filter' => json_encode(Arr::get($item, 'filter', [])),
                    'config' => json_encode(Arr::get($item, 'config', [])),
                ];
            }
        }
        DatabaseManager::insertBatch(FunctionPageApiConfigEntity::class, $initData, true);
    }

}
