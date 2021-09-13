<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\Entities\ProjectQuestionEntity;
use App\EofficeApp\Project\NewRepositories\ProjectQuestionRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use Illuminate\Support\Arr;
Trait ProjectQuestionTrait
{

    public static function questionList(DataManager $dataManager) {
        $params = [
            '@get_project_question' => 0, // 获取项目已提交的问题，不含草稿的
            '@with_user_info' => 0,
            'order_by' => ['question_id' => 'desc']
        ];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);
        $userId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();

        $queryParams = self::handleListParams($params);
        $query = ProjectQuestionRepository::buildQuery($queryParams);
        if ($params['@get_project_question']) {
            ProjectQuestionRepository::buildProjectSubmittedQuestion($managerId, $query);
        } else {
            ProjectQuestionRepository::buildMyProjectQuestion($managerId, $userId, $query);
        }

        $data = HelpersManager::paginate($query, $dataManager);

        self::setQuestionInfo($data['list']);
        $params['@with_user_info'] && self::setQuestionRoles($data['list']);

        return $data;
    }

    public static function questionInfo(DataManager $dataManager)
    {
        $params = [
            '@with_task' => 0
        ];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);

        $question = $dataManager->getRelations()->first();
        self::setQuestionInfo($question);
        self::setQuestionRoles($question);
        self::setAttachments($question);

        if (Arr::get($params, '@with_task')) {
            $question->task;
        }

        return $question;
    }

    public static function questionEdit(DataManager $dataManager) {
        $userId = $dataManager->getCurUserId();
        $functionPageApi = $dataManager->getFunctionPageBin()->getFunctionPageId();
        $managerId = $dataManager->getManagerId();
        $managerType = $dataManager->getProject('manager_type');
        $relation = $dataManager->getRelations();
        $editApiBin = $dataManager->getApiBin();
        $data = $dataManager->getApiParams();
        $editApiBin->fillRelationData($relation, $data, $functionPageApi);

        // 更新第一条数据
        $model = $relation->first();
        // 是否是从保存变为提交状态
        $isSubmit = $model->isDirty('question_state') && $model['question_state'] == 1 && $model->getOriginal('question_state') == 0;
        $needEditLog = !$isSubmit && $model['question_state'] != 0;
        $model->mineSave($userId,  $managerId, 'modify', $needEditLog); // 编辑暂无日志
        self::syncAttachments($model, $data, 'attachments');

        if ($isSubmit) {
            ProjectLogManager::getIns($userId, $managerId)
                ->questionAddLog($model['question_name'], $model->getKey());
            MessageManager::sendQuestionSubmitReminder($model);
            self::setQuestionProjectRoleUserDisabled($model->getKey(), $managerId, $managerType, 0);
        } else {
            self::setQuestionProjectRoleUserDisabled($model->getKey(), $managerId, $managerType, 1);
        }

        return ['model' => $model];
    }

    public static function questionAdd(DataManager $dataManager)
    {
        $model = new ProjectQuestionEntity();
        $params = $dataManager->getApiParams();
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();
        $managerType = $dataManager->getProject('manager_type');
        $functionPageApi = $dataManager->getFunctionPageBin()->getFunctionPageId();

        $relation = $dataManager->getRelations()->first(); // 添加依赖于项目数据，内含配置
        $dataManager->getApiBin()->fillAddData($model, $params, $relation, $functionPageApi);
        $model->question_createtime = date("Y-m-d H:i:s", time());
        $model->question_creater = $curUserId;
        $model->question_project = $managerId;
        $model->mineSave($curUserId, $managerId, 'add', false);

        if ($model['question_state'] == 1) {
            ProjectLogManager::getIns($curUserId, $managerId)
                ->questionAddLog($model['question_name'], $model->getKey());
            MessageManager::sendQuestionSubmitReminder($model);
        } else {
            self::setQuestionProjectRoleUserDisabled($model->getKey(), $managerId, $managerType, 1);
        }
        self::syncAttachments($model, $params, 'attachments', 'add');

        return ['model' => $model];
    }

    public static function questionDelete(DataManager $dataManager)
    {
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();
        $relations = $dataManager->getRelations();
        $idName = $relations->where('question_state', '>', 0)->pluck('question_name', 'question_id')->toArray();
        $destroyIds = $relations->pluck('question_id')->toArray();
        // 删除附件与数据
        self::deleteAttachments($relations);
        $res = DatabaseManager::deleteByIds(ProjectQuestionEntity::class, $destroyIds);
        self::deleteProjectTypeRoleUser('question', $destroyIds, $managerId);
        // 记录日志
        $logManager = self::initDeleteLog('question', $curUserId, $idName, $managerId);
        $res && $logManager && $logManager->storageFillData();

        return [];
    }

    private static function setQuestionInfo(&$data) {
        $priorities = self::getAllProjectPriorities();
        $questionType = self::getAllQuestionTypes();
        $questionState = ProjectQuestionEntity::getSelectFieldKeyValue('question_state', 'project');
        HelpersManager::toEloquentCollection($data, function ($data) use ($priorities, $questionState, $questionType) {
            foreach ($data as $item) {
                $item['question_level_name'] = Arr::get($priorities, $item['question_level'], '');
                $item['question_type_name'] = Arr::get($questionType, $item['question_type'], '');
                $item['question_state_name'] = Arr::get($questionState, $item['question_state'], '');
            }
            return $data;
        });
    }

    private static function setQuestionRoles(&$data) {
        self::setModelRoles($data, 'question');
        HelpersManager::toEloquentCollection($data, function ($data) {
            foreach ($data as &$item) {
                $usersInfo = Arr::get($item, 'users_info', []);
                $item['question_person_name'] = Arr::get($usersInfo, $item['question_person'], '');
                $item['question_doperson_name'] = Arr::get($usersInfo, $item['question_doperson'], '');
                $item['question_creater_name'] = Arr::get($usersInfo, $item['question_creater'], '');
            }
            return $data;
        });
    }

    // 对是否草稿状态的权限数据设置禁用状态，除问题创建人的其它所有角色
    private static function setQuestionProjectRoleUserDisabled($questionId, $managerId, $managerType, $isDisabled)
    {
        $allQuestionRoleIds = RoleManager::getRoleIdsByType('question', $managerType);
        $createrRoleId = RoleManager::getRoleId('question_creater', $managerType);
        $roleIds = array_diff($allQuestionRoleIds, $createrRoleId);
        ProjectRoleUserRepository::buildQuery([
            'role_id' => $roleIds,
            'relation_type' => 'question',
            'relation_id' => $questionId,
            'manager_id' => $managerId,
        ])->update(['is_disabled' => $isDisabled ? 1 : 0]);
    }
}
