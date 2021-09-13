<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\Authority;

use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\NewRepositories\ProjectFunctionPageRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use Illuminate\Support\Facades\Lang;

Trait ProjectFunctionPageTrait
{
    private static function functionPageListData() {
        $params = [
            'order_by' => ['sort' => 'asc']
        ];
        $data = ProjectFunctionPageRepository::buildQuery($params)->get();
        return $data;
    }

    public static function functionPageList() {
        return CacheManager::getArrayCache('functionPageListCacheKey', function () {
            $data = self::functionPageListData();
            $managerStates = ProjectManagerEntity::getAllManagerStates();

            $list = [];
            foreach ($managerStates as $managerState) {
                $list[$managerState] = self::filterStateFunctionPage($data, $managerState)->toArray();
            }
            return $list;
        });
    }

    // 功能树
    public static function functionPageTreeList(): array
    {
        $langType = Lang::getLocale();
        $cacheKey = 'functionPageTreeListCacheKey_' . $langType;
        return CacheManager::getArrayCache($cacheKey, function () {
            $data = self::functionPageListData();
            $managerStates = ProjectManagerEntity::getAllManagerStates();

            $treeList = [];
            foreach ($managerStates as $managerState) {
                $dataTemp = self::filterStateFunctionPage($data, $managerState);
                $treeList[$managerState] = self::toFunctionPageTreeNest($dataTemp);
            }
            return $treeList;
        });
    }

    // 嵌套的权限
    private static function toFunctionPageTreeNest($data, $treeData = null, $parentId = '')
    {
        $idKey = 'function_page_id';
        $parentIdKey = 'parent_id';
        $childrenKey = 'children';
        is_null($treeData) && $treeData = [];
        $currentData = $data->where($parentIdKey, $parentId);
        foreach ($currentData as $item) {
            $hasChildren = $data->where($parentIdKey, $item->$idKey)->isNotEmpty();
            if ($hasChildren) {
                $item[$childrenKey] = self::toFunctionPageTreeNest($data, null, $item->$idKey);
            }
            $treeData[]  = $item->toArray();
        }
        return $treeData;
    }

    // 不同项目状态下，过滤某些功能不展示
    private static function filterStateFunctionPage($data, $managerState)
    {
        $dataTemp = clone $data;
        $dataTemp = $dataTemp->keyBy('function_page_id');
        switch ($managerState) {
            case 1:
                $dataTemp->forget(['pro_approve', 'pro_refuse', 'pro_over', 'pro_restart']);
                break;
            case 2:
                $dataTemp->forget(['pro_examine', 'pro_over', 'pro_restart']);
                break;
            case 3:
                $dataTemp->forget(['pro_approve', 'pro_refuse', 'pro_over', 'pro_restart']);
                break;
            case 4:
                $dataTemp->forget(['pro_examine', 'pro_approve', 'pro_refuse', 'pro_restart']);
                break;
            case 5:
                $dataTemp->forget(['pro_examine', 'pro_approve', 'pro_refuse', 'pro_over']);
                break;
        }
        return $dataTemp;
    }
}
