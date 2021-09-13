<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\Entities\ProjectTaskDiaryEntity;
use App\EofficeApp\Project\NewRepositories\ProjectStatusRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskDiscussRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\Utils\ResponseService;
use Illuminate\Support\Arr;
Trait ProjectTaskDiscussTrait
{
    public static function taskDiscussList(DataManager $dataManager)
    {
        $params = [
            'order_by' => ['taskdiary_curtime' => 'desc']
        ];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);
        $managerId = $dataManager->getManagerId();
        $taskId = self::getProjectTaskDiscussTaskId($dataManager);

        $queryParams = self::handleListParams($params);
        $query = ProjectTaskDiscussRepository::buildProjectTaskDiscussListQuery($managerId, $taskId, $queryParams);
        $data = HelpersManager::paginate($query, $dataManager);
        self::handleTaskDiscussInfo($data['list']);

        return $data;
    }

    public static function taskDiscussAdd(DataManager $dataManager)
    {
        $model = new ProjectTaskDiaryEntity();
        $params = $dataManager->getApiParams();
        $taskId = self::getProjectTaskDiscussTaskId($dataManager);
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();

        $params['taskdiary_curtime'] = date("Y-m-d H:i:s", time());
        $params['taskdiary_creater'] = $curUserId;
        $params['taskdiary_project'] = $managerId;
        $model->fill($params);
        $model->save();

        self::syncAttachments($model, $params, 'attachments', 'add');
        ProjectStatusRepository::update($managerId, 'task', $taskId, $curUserId);

        MessageManager::sendNewTaskDiscussReminder($dataManager->getProject(), $dataManager->getRelations()->first());

        return $model; // 固定格式
    }

    public static function taskDiscussEdit(DataManager $dataManager)
    {
        $params = $dataManager->getApiParams();
        $managerId = $dataManager->getManagerId();
        $curUserId = $dataManager->getCurUserId();
        $discussId = $dataManager->getApiParams('taskdiary_id');
        $model = ProjectTaskDiscussRepository::buildQuery()->find($discussId);
        !$model && ResponseService::throwException('0x036001', 'project'); // 数据不存在
        !HelpersManager::testTime($model['taskdiary_curtime']) && ResponseService::throwException('0x000017', 'common');
        $model->fill($params)->save();

        self::syncAttachments($model, $params, 'attachments');
        ProjectStatusRepository::update($managerId, 'project', $managerId, $curUserId);

        return ['model' => $model];
    }

    public static function taskDiscussDelete(DataManager $dataManager)
    {
//        $task = $dataManager->getRelations()->first(); // 目前只支持删除一个任务的讨论
        $discussId = $dataManager->getApiParams('taskdiary_id');
        $model = ProjectTaskDiscussRepository::buildQuery()->find($discussId);
        !$model && ResponseService::throwException('0x036001', 'project'); // 数据不存在

        // 暂时没有开通负责人删除功能
        if (/*!$task->testRoleKey('manager_user') && */!HelpersManager::testTime($model['taskdiary_curtime'])) {
            ResponseService::throwException('0x000017', 'common');
        }
        // 有回复不能删除
        $replyIds = ProjectTaskDiscussRepository::buildQuery(['task_diary_replyid' => $discussId])->pluck('taskdiary_id')->toArray();
        $replyIds && ResponseService::throwException('0x000017', 'common');

        return DatabaseManager::deleteByIds(ProjectTaskDiaryEntity::class, $discussId);
    }


    // 处理讨论的信息，回复、引用、附件数据
    private static function handleTaskDiscussInfo(&$data)
    {
        HelpersManager::toEloquentCollection($data, function (&$data) {
            // 获取附件
            $quoteIds = $data->pluck('task_diary_quoteid')->toArray();
            $discussIds = $data->pluck('taskdiary_id')->toArray();
            $replies = $data->pluck('reply');
            $replyIds = $replies ? $replies->collapse()->pluck('taskdiary_id')->toArray() : [];
            $allDiscussIds = array_unique(array_merge($quoteIds, $discussIds, $replyIds));
            $allAttachments = self::getAttachments('project_task_diary', $allDiscussIds, true);
            unset($quoteIds, $discussIds, $replyIds);

            foreach ($data as &$item) {
                self::handleTaskDiscussUserNameAndAttachmentIds($item, $allAttachments);
                if ($item->quote) {
                    self::handleTaskDiscussUserNameAndAttachmentIds($item['quote'], $allAttachments);
                }
                if ($item->reply) {
                    foreach ($item['reply'] as &$reply) {
                        self::handleTaskDiscussUserNameAndAttachmentIds($reply, $allAttachments);
                    }
                }
            }
            return $data;
        });
    }

    private static function handleTaskDiscussUserNameAndAttachmentIds(&$item, $allAttachmentIds)
    {
        $item['user_name'] = object_get($item, 'user.user_name', '');
        $item->unsetRelation('user');
        $discussId = $item['taskdiary_id'];
        $item['attachments'] = Arr::get($allAttachmentIds, $discussId, []);
    }

    private static function getProjectTaskDiscussTaskId($dataManager)
    {
        return $dataManager->getRelations()->first()['task_id'];
    }
}
