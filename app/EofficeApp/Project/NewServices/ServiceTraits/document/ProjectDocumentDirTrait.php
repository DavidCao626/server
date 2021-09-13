<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\Document;

use App\EofficeApp\Project\Entities\ProjectDocumentDirEntity;
use App\EofficeApp\Project\NewRepositories\ProjectDocumentDirRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use Illuminate\Support\Arr;
Trait ProjectDocumentDirTrait
{
    public static function documentDirList(DataManager $dataManager)
    {
        $params = [];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);
        $managerId = $dataManager->getManagerId();

        $queryParams = self::handleListParams($params);
        $query = ProjectDocumentDirRepository::buildProjectDirQuery($managerId, $queryParams);
        ProjectDocumentDirRepository::withSonDir($query, $managerId, true);
        $query->withCount('documents'); // 集中查询，用于判断是否可以删除文件

        $data = $query->get();

        $data = ['list' => $data, 'total' => count($data)];
        $dataManager->getApiBin()->formatResult($data); // 存在树结构，这里处理权限等数据
        // 是否是嵌套数据
        if (Arr::get($apiParams, 'is_nest')) {
            $data['list'] = self::toDirTreeNest($data['list']);
        } else {
            $data['list'] = self::toDirTree($data['list']); // 平级的数据
        }

        return $data;
    }

    public static function documentDirAdd(DataManager $dataManager)
    {
        $model = new ProjectDocumentDirEntity();
        $params = $dataManager->getApiParams();
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();
        $functionPageApi = $dataManager->getFunctionPageBin()->getFunctionPageId();
        $params['creator'] = $curUserId;
        $params['dir_project'] = $managerId;

        $relation = $dataManager->getRelations()->first(); // 添加依赖于项目数据，内含配置
        $dataManager->getApiBin()->fillAddData($model, $params, $relation, $functionPageApi);
        $model->save();

        return ['model' => $model];
    }

    public static function documentDirInfo(DataManager $dataManager)
    {
        $dir = $dataManager->getRelations()->first();
        self::setDocumentDirRoles($dir);

        return $dir;
    }

    public static function documentDirEdit(DataManager $dataManager)
    {
        $userId = $dataManager->getCurUserId();
        $functionPageApi = $dataManager->getFunctionPageBin()->getFunctionPageId();
        $managerId = $dataManager->getManagerId();
        $relation = $dataManager->getRelations();
        $editApiBin = $dataManager->getApiBin();
        $data = $dataManager->getApiParams();
        $editApiBin->fillRelationData($relation, $data, $functionPageApi);

        // 更新第一条数据
        $model = $relation->first();
        $model->save(); // 编辑暂无日志

        return ['model' => $model];
    }

    public static function documentDirDelete(DataManager $dataManager)
    {
        $relations = $dataManager->getRelations();

        $destroyIds = $relations->pluck('dir_id')->toArray();
        // 删除附件与数据
        $res = DatabaseManager::deleteByIds(ProjectDocumentDirEntity::class, $destroyIds);

        return [];
    }

    private static function toDirTree($data, $treeData = null, $parentId = 0)
    {
        is_null($treeData) && $treeData = collect();
        $currentData = $data->where('parent_id', $parentId);
        foreach ($currentData as $item) {
            $treeData->push($item);
            $hasChildren = $data->where('parent_id', $item->dir_id)->isNotEmpty();
            if ($hasChildren) {
                self::toDirTree($data, $treeData, $item->dir_id);
            }
        }
        return $treeData;
    }

    private static function toDirTreeNest($data, $treeData = null, $parentId = 0)
    {
        is_null($treeData) && $treeData = collect();
        $currentData = $data->where('parent_id', $parentId);
        foreach ($currentData as $item) {
            $hasChildren = $data->where('parent_id', $item->dir_id)->isNotEmpty();
            if ($hasChildren) {
                $item['children'] = self::toDirTreeNest($data, null, $item->dir_id);
            }
            $treeData->push($item);
        }
        return $treeData;
    }

    private static function setDocumentDirRoles(&$data) {
        self::setModelRoles($data, 'document_dir');
        HelpersManager::toEloquentCollection($data, function ($data) {
            foreach ($data as &$item) {
                $usersInfo = Arr::get($item, 'users_info', []);
                $item['user_name'] = Arr::get($usersInfo, $item['doc_creater'], '');
            }
            return $data;
        });
    }
}
