<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\Document;

use App\EofficeApp\Project\Entities\ProjectDocumentEntity;
use App\EofficeApp\Project\NewRepositories\ProjectDocumentDirRepository;
use App\EofficeApp\Project\NewRepositories\ProjectDocumentRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermissionManager;
use Illuminate\Support\Arr;
Trait ProjectDocumentTrait
{
    use ProjectDocumentDirTrait;

    public static function documentList(DataManager $dataManager)
    {
        $params = [
            'with_dir' => 1
        ];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);
        $managerId = $dataManager->getManagerId();

        $queryParams = self::handleListParams($params);
        if (array_key_exists('dir_id', $queryParams)) {
            $allSubordinateDirIds = ProjectDocumentDirRepository::getSubordinateDirIds($queryParams['dir_id'], $managerId);
            $queryParams['dir_id'] = $allSubordinateDirIds;
        }
        $query = ProjectDocumentRepository::buildQuery($queryParams);
        ProjectDocumentRepository::buildProjectDocument($managerId, $query);

        $data = HelpersManager::paginate($query, $dataManager);
        self::setDocumentRoles($data['list']);

        return $data;
    }

    public static function documentAdd(DataManager $dataManager)
    {
        $model = new ProjectDocumentEntity();
        $params = $dataManager->getApiParams();
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();
        $functionPageApi = $dataManager->getFunctionPageBin()->getFunctionPageId();
        $params['doc_creattime'] = date("Y-m-d H:i:s", time());
        $params['doc_creater'] = $curUserId;
        $params['doc_project'] = $managerId;

        $relation = $dataManager->getRelations()->first(); // 添加依赖于项目数据，内含配置
        $dataManager->getApiBin()->fillAddData($model, $params, $relation, $functionPageApi);
        $model->mineSave($curUserId, $managerId, 'add');

        self::syncAttachments($model, $params, 'attachments', 'add');

        return ['model' => $model];
    }

    public static function documentInfo(DataManager $dataManager)
    {
        $doc = $dataManager->getRelations()->first();
        $params = $dataManager->getApiParams();
        if (Arr::get($params, 'with_dir')) {
            $doc->dir;
        }
        self::setAttachments($doc);
        self::setDocumentRoles($doc);
        $doc['manager_name'] = $dataManager->getProject('manager_name');

        return $doc;
    }

    public static function documentEdit(DataManager $dataManager)
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
        // 是否是从保存变为提交状态
        $model->mineSave($userId,  $managerId, 'modify'); // 编辑暂无日志
        self::syncAttachments($model, $data, 'attachments');

        return ['model' => $model];
    }

    public static function documentDelete(DataManager $dataManager)
    {
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();
        $relations = $dataManager->getRelations();
        $idName = $relations->pluck('doc_name', 'doc_id')->toArray();
        $destroyIds = $relations->pluck('doc_id')->toArray();
        // 删除附件与数据
        self::deleteAttachments($relations);
        $res = DatabaseManager::deleteByIds(ProjectDocumentEntity::class, $destroyIds);
        self::deleteProjectTypeRoleUser('document', $destroyIds, $managerId);
        // 记录日志
        $logManager = self::initDeleteLog('document', $curUserId, $idName, $managerId);
        $res && $logManager && $logManager->storageFillData();

        return [];
    }

    //批量下载文档中的附件
    public static function batchDownloadAttachments(DataManager $dataManager)
    {
        $docs = self::getDocumentList($dataManager, ['page' => 0, 'list_type' => 'list', 'manager_id' => $dataManager->getManagerId()])['list'];
        //下载
        $attachmentService = self::getAttachmentService();
        $existDocNames = $docs->pluck('doc_name', 'doc_id')->toArray();
        $existDocIds = array_keys($existDocNames);
        $attachmentIds = $attachmentService->getAttachmentsByEntityIds('project_document', $existDocIds);
        $downloadAttachmentIds = [];
        foreach ($attachmentIds as $docId => $ids) {
            $docName = Arr::get($existDocNames, $docId, '无名称文档附件');
            if (isset($downloadAttachmentIds[$docName])) {
                $downloadAttachmentIds[$docName] = array_merge($downloadAttachmentIds[$docName], $ids);
            } else {
                $downloadAttachmentIds[$docName] = $ids;
            }
        }

        $attachmentService->downZipByFolder($downloadAttachmentIds);

        return true;
    }

    //项目是否拥有文档附件
    public static function hasAttachments(DataManager $dataManager)
    {
        $docs = self::getDocumentList($dataManager, ['page' => 0, 'list_type' => 'list', 'manager_id' => $dataManager->getManagerId()])['list'];
        $attachmentIds = [];
        if ($docs->isNotEmpty()) {
            $existDocIds = $docs->pluck('doc_id')->toArray();
            $attachmentIds = self::getAttachmentService()->getAttachmentsByEntityIds('project_document', $existDocIds);
        }

        return ['has_attachments' => !empty($attachmentIds)];
    }

    // 获取档案列表
    public static function getDocumentList(DataManager $dataManager, $params, $action = 'documentList', $functionPageId = 'document_list')
    {
        return RolePermissionManager::toggleDataManager($dataManager, $action, $functionPageId, $params, function ($newDataManager) {
           return self::documentList($newDataManager);
        });
    }

    private static function setDocumentRoles(&$data) {
        self::setModelRoles($data, 'document');
        HelpersManager::toEloquentCollection($data, function ($data) {
            foreach ($data as &$item) {
                $usersInfo = Arr::get($item, 'users_info', []);
                $item['user_name'] = Arr::get($usersInfo, $item['doc_creater'], '');
            }
            return $data;
        });
    }
}
