<?php

namespace App\EofficeApp\Task\Services;

use App\EofficeApp\Base\BaseService;
// use App\EofficeApp\Task\Repositories\TaskManageRepository;
// use App\EofficeApp\Task\Repositories\TaskUserRepository;
// use App\EofficeApp\Task\Repositories\TaskFeedbackRepository;
// use App\EofficeApp\Task\Repositories\TaskLogRepository;
// use App\EofficeApp\Task\Repositories\TaskClassRepository;
// use App\EofficeApp\Task\Repositories\TaskClassRelationRepository;
// use App\EofficeApp\User\Repositories\UserRepository;
// use App\EofficeApp\User\Services\UserService;
// use App\EofficeApp\Attachment\Services\AttachmentService;
// use App\EofficeApp\Task\Repositories\TaskRemindsRepository;
use App\EofficeApp\Task\Entities\TaskManageEntity;
use Eoffice;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
class TaskService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->taskManageRepository        = 'App\EofficeApp\Task\Repositories\TaskManageRepository';
        $this->taskUserRepository          = 'App\EofficeApp\Task\Repositories\TaskUserRepository';
        $this->taskFeedBackRepository      = 'App\EofficeApp\Task\Repositories\TaskFeedbackRepository';
        $this->taskLogRepository           = 'App\EofficeApp\Task\Repositories\TaskLogRepository';
        $this->taskClassRepository         = 'App\EofficeApp\Task\Repositories\TaskClassRepository';
        $this->taskClassRelationRepository = 'App\EofficeApp\Task\Repositories\TaskClassRelationRepository';
        $this->userRepository              = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userService                 = 'App\EofficeApp\User\Services\UserService';
        $this->attachmentService           = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->calendarService             = 'App\EofficeApp\Calendar\Services\CalendarService';
        $this->taskRemindsRepository       = 'App\EofficeApp\Task\Repositories\TaskRemindsRepository';
    }

    /**
     * [createTask 创建任务]
     *
     * @author 朱从玺
     *
     * @param  [array]     $createData [创建数据]
     * @param  [string]    $userId     [用户ID]
     * @param  [bool]    $forcePermission     [强制创建权限，跳过父任务权限判断]
     *
     * @return [object]                 [创建结果]
     */
    public function createTask($createData, $userId, $forcePermission = false)
    {
        //判断任务是否有父级任务
        if (isset($createData['parent_id']) && $createData['parent_id'] != 0) {
            $parentTask = app($this->taskManageRepository)->getDetail($createData['parent_id']);

            if (!$parentTask || $parentTask->parent_id != 0) {
                return array('code' => array('0x046007', 'task'));
            }

            // 验证创建子任务的权限，通过流程外发的强制创建
            if (!$forcePermission) {
                //获取有参与人权限以上的所有任务ID数组
                $taskArray = $this->getPowerTaskArray($userId, 'joiner');

                if (!in_array($createData['parent_id'], $taskArray)) {
                    return array('code' => array('no_parent_task_permission', 'task'));
                }
            }

            if ($parentTask->task_status == 1) {
                return array('code' => array('0x046043', 'task'));
            }

            if ($parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                return array('code' => array('0x046027', 'task'));
            } else {
                $createData['lock'] = $parentTask->lock;
            }
        } else {
            $createData['parent_id'] = 0;
        }

        //指定创建人和负责人
        $manageUser = Arr::get($createData, 'manage_user', '');
        if (strpos($manageUser, ',') !== false) {
            return ['code' => ['just_choose_single_manage_user', 'task']];
        }
        $createData['create_user']     = $userId;
        $createData['manage_user']     = $manageUser ? $manageUser : $createData['create_user'];
        $createData['end_date']        = isset($createData['end_date']) ? $createData['end_date'] : date('Y-m-d');
        $createData['start_date']      = isset($createData['start_date']) ? $createData['start_date'] : date('Y-m-d');
        $createData['important_level'] = isset($createData['important_level']) ? $createData['important_level'] : 0;
        $createData['lock']            = isset($createData['lock']) ? $createData['lock'] : 0;
        $createData['task_description'] = isset($createData['task_description']) ? $createData['task_description'] : '';
        if ($createData['end_date'] && $createData['start_date'] > $createData['end_date']) {
            return array('code' => array('0x046011', 'task'));
        }
        //插入任务数据
        $newTask = app($this->taskManageRepository)->taskInsertBasic($createData);
//        $sortData = [
        //            'task_id' => $newTask->id,
        //            'user_id' => $userId,
        //            'sort_id' => 0,
        //            'parent_id' => $createData['parent_id']
        //        ];
        //
        //创建任务与任务类别关联关系

        $relationData = [
            'task_id'  => $newTask->id,
            'class_id' => isset($createData['class_id']) ? $createData['class_id'] : 0,
        ];

        app($this->taskClassRelationRepository)->insertData($relationData);
        //任务创建日志
        $this->createTaskLog($newTask->id, $userId, trans('task.0x046009'));

        //主任务相关更新
        if ($createData['parent_id'] != 0) {
            //记录日志
            $logContent = trans('task.0x046008') . ' [' . $createData['task_name'] . ']';
            $this->createTaskLog($createData['parent_id'], $userId, $logContent);

            //更新主任务进度
            $this->modifyParentTaskProgress($createData['parent_id']);
        }

        // 关联附件
        $attachments = Arr::get($createData, 'attachment_ids');
        if ($attachments) {
            if (is_string($attachments)) {
                $attachments = explode(',', $attachments);
            }
            app($this->attachmentService)->attachmentRelation("task_manage", $newTask->id, $attachments);
        }

        //判断创建时有没有带入参与人或共享人
        $taskUser    = [];
        $taskUserLog = [];
        if (isset($createData['joiner']) && is_array($createData['joiner'])) {
            $userNames = $this->getUserNames($createData['joiner']);
            foreach ($createData['joiner'] as $joiner) {
                $taskUser[] = [
                    'task_id'       => $newTask->id,
                    'user_id'       => $joiner,
                    'task_relation' => 'join',
                ];

                $userName   = $userNames->get($joiner);
                $logContent = trans('task.0x046032') . ' ' . $userName;

                $taskUserLog[] = [
                    'task_id'     => $newTask->id,
                    'user_id'     => $userId,
                    'log_content' => $logContent,
                ];
            }
        }

        if (isset($createData['shared']) && is_array($createData['shared'])) {
            $userNames = $this->getUserNames($createData['shared']);
            foreach ($createData['shared'] as $shared) {
                $taskUser[] = [
                    'task_id'       => $newTask->id,
                    'user_id'       => $shared,
                    'task_relation' => 'shared',
                ];

                $userName   = $userNames->get($shared);
                $logContent = trans('task.0x046034') . ' ' . $userName;

                $taskUserLog[] = [
                    'task_id'     => $newTask->id,
                    'user_id'     => $userId,
                    'log_content' => $logContent,
                ];
            }
        }

        //插入任务用户关联数据并创建日志
        if ($taskUser != []) {
            app($this->taskUserRepository)->insertMultipleData($taskUser);
            app($this->taskLogRepository)->insertMultipleData($taskUserLog);
        }


        //消息提醒
        $createData['joiner'] = isset($createData['joiner']) && is_array($createData['joiner']) ? $createData['joiner'] : [];
        $createData['shared'] = isset($createData['shared']) && is_array($createData['shared']) ? $createData['shared'] : [];
        $managerUser          = $createData['manage_user'];
        $toUser               = array_unique(array_merge([$managerUser], $createData['joiner'], $createData['shared']));
        $this->newTaskRemind($newTask, $toUser);
        $calendarUser = array_unique(array_merge([$managerUser], isset($createData['joiner']) ? $createData['joiner'] : $createData['join_user']));

        // 外发到日程模块 --开始--
        $calendarData = [
            'calendar_content' => $createData['task_name'],
            'handle_user'      => $calendarUser,
            'calendar_begin'   => $createData['start_date'],
            'calendar_end'     => $createData['end_date'] == '' ? '0000-00-00' : $createData['end_date'],
            'calendar_level'   => $createData['important_level'],
            'share_user'       => isset($createData['shared']) ? $createData['shared'] : $createData['shared_user'],
            'attachment_id'    => $attachments,
            'calendar_remark'  => $createData['task_description'] ?? ''
        ];
        $relationData = [
            'source_id'     => $newTask->id,
            'source_from'   => 'task-new',
            'source_title'  => $createData['task_name'],
            'source_params' => ['task_id' => $newTask->id]
        ];
        app($this->calendarService)->emit($calendarData, $relationData, $userId);
        // 外发到日程模块 --结束--
        return $newTask;
    }

    /**
     * [modifyTask 编辑任务,仅允许更新Entity中fillable数组字段]
     *
     * @author 朱从玺
     *
     * @param  [int]        $taskId      [任务ID]
     * @param  [array]      $newTaskData [编辑数据]
     * @param  [string]     $userId      [用户ID]
     *
     * @return [object]                  [编辑结果]
     */
    public function modifyTask($taskId, $newTaskData, $userId)
    {
        //获取编辑之前的任务数据
        $oldTaskData = app($this->taskManageRepository)->getDetail($taskId);

        if (!$oldTaskData) {
            return array('code' => array('0x046006', 'task'));
        }

        //判断用户权限 必须是负责人/参与人的一种
        $taskArray = $this->getPowerTaskArray($userId, 'joiner');

        if (!in_array($taskId, $taskArray)) {
            return array('code' => array('0x000017', 'common'));
        }

        //判断任务是否锁定
        if ($oldTaskData->lock == 1 && $userId != $oldTaskData->manage_user) {
            return array('code' => array('0x046027', 'task'));
        }

        //判断主任务是否锁定
        if ($oldTaskData->parent_id != 0) {
            $parentTask = app($this->taskManageRepository)->getDetail($oldTaskData->parent_id);

            if ($parentTask && $parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                return array('code' => array('0x046041', 'task'));
            }

            //任务进度不能大于100
            if ($newTaskData['progress'] > 100) {
                return array('code' => array('0x046031', 'task'));
            }

            if ($oldTaskData->task_status == 1 && $newTaskData['progress'] < 100) {
                return array('code' => array('0x046044', 'task'));
            }
        } else {
            //任务主任务
            //如果有子任务
            if (isset($newTaskData["task_has_many_son_task"]) && count($newTaskData["task_has_many_son_task"]) > 0) {
                unset($newTaskData['progress']);
            }
        }

        //判断起始日期是否大于到期日
        if ($newTaskData['start_date'] && $newTaskData['end_date'] && $newTaskData['end_date'] != "0000-00-00") {
            if ($newTaskData['start_date'] > $newTaskData['end_date']) {
                return array('code' => array('0x046011', 'task'));
            }
        }

        //执行编辑操作
        $where        = ['id' => $taskId];
        $updateResult = app($this->taskManageRepository)->updateDataBatch($newTaskData, $where);

        //附件上传
        $oldAttachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $taskId]);
        if (isset($newTaskData['attachments'])) {
            app($this->attachmentService)->attachmentRelation("task_manage", $taskId, $newTaskData['attachments']);

            if (isset($newTaskData['files'])) {
                //记录操作日志
                $fileName   = implode(',', array_column($newTaskData['files'], 'name'));
                $logContent = trans('task.0x046017') . ' ' . $fileName;

                $this->createTaskLog($taskId, $userId, $logContent);
            }

            $deleteAttachments = array_diff($oldAttachments, $newTaskData['attachments']);

            if ($deleteAttachments) {
                $deleteData = app($this->attachmentService)->getAttachments(['attach_ids' => $deleteAttachments]);

                //记录操作日志
                $fileName   = implode(',', array_column($deleteData, 'attachment_name'));
                $logContent = trans('task.0x046037') . ' ' . $fileName;

                $this->createTaskLog($taskId, $userId, $logContent);
            }
        }

        //记录操作日志
        if ($updateResult) {
            $logContentArray = [];
            if ($newTaskData['task_name'] != $oldTaskData['task_name']) {
                $logContentArray[] = trans('task.0x046012') . ' ' . $newTaskData['task_name'];
            }
            if ($newTaskData['task_description'] != $oldTaskData['task_description']) {
                $logContentArray[] = trans('task.0x046013');
            }
            if ($newTaskData['important_level'] != $oldTaskData['important_level']) {
                if ($newTaskData['important_level'] == 0) {
                    $logContentArray[] = trans('task.0x046014') . ' ' . trans('task.general');
                } elseif ($newTaskData['important_level'] == 1) {
                    $logContentArray[] = trans('task.0x046014') . ' ' . trans('task.import');
                } else {
                    $logContentArray[] = trans('task.0x046014') . ' ' . trans('task.instancy');
                }
            }
            if ($newTaskData['start_date'] != $oldTaskData['start_date']) {
                $logContentArray[] = trans('task.0x046015') . ' ' . $newTaskData['start_date'];
            }
            if ($newTaskData['end_date'] != $oldTaskData['end_date']) {
                $logContentArray[] = trans('task.0x046016') . ' ' . $newTaskData['end_date'];
            }
            if (isset($newTaskData['progress']) && $newTaskData['progress'] != $oldTaskData['progress']) {
                $logContentArray[] = trans('task.0x046039') . ' ' . $newTaskData['progress'] . '%';

                //更新主任务进度
                $this->modifyParentTaskProgress($oldTaskData->parent_id);
            }

            if ($logContentArray) {
                foreach ($logContentArray as $logContent) {
                    $this->createTaskLog($taskId, $userId, $logContent);
                }
            }
        }

        return $updateResult;
    }

    /**
     * [modifyTaskManager 编辑任务负责人]
     *
     * @author 朱从玺
     *
     * @param  [int]               $taskId  [任务ID]
     * @param  [string]            $manager [任务负责人数据]
     * @param  [string]            $userId  [编辑人ID]
     *
     * @return [bool]                       [编辑结果]
     */
    public function modifyTaskManager($taskId, $manager, $userId)
    {
        $taskData = app($this->taskManageRepository)->getDetail($taskId);
        if ($userId != $taskData->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskData) {
            //判断是否锁定
            if ($taskData->lock == 1 && $userId != $taskData->manage_user) {
                return array('code' => array('0x000017', 'common'));
            }

            //判断主任务是否锁定
            if ($taskData->parent_id != 0) {
                $parentTask = app($this->taskManageRepository)->getDetail($taskData->parent_id);

                if ($parentTask && $parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                    return array('code' => array('0x000017', 'common'));
                }
            }

            $where = array('id' => $taskId);
            $data  = array('manage_user' => $manager['user_id']);
            $updateResult = app($this->taskManageRepository)->updateData($data, $where);

            //发送消息提醒
            $this->newTaskRemind($taskData, [$manager['user_id']]);

            //记录操作日志
            if ($updateResult) {
                $this->emitCalendarUpdate($taskId, $data, $userId, 'update');
                $logContent = trans('task.0x046010') . ' ' . $manager['user_name'];

                $this->createTaskLog($taskId, $userId, $logContent);
            }

            return $updateResult;
        }

        return array('code' => array('0x046006', 'task'));
    }
    private function MultiRecoveryTaskToCalendar($sourceId, $userId)
    {
        if (is_array($sourceId) && !empty($sourceId)) {
            foreach ($sourceId as $key => $value) {
                $this->emitCalendarUpdate($value, [], $userId, 'add');
            }
        }
    }

    private function emitCalendarUpdate ($sourceId, $data, $userId, $type) 
    {
        $taskData = app($this->taskManageRepository)->getDetail($sourceId);
        // 获取任务参与人
        $joiner = app($this->taskUserRepository)->getTaskJoinUserById($sourceId);
        $share = app($this->taskUserRepository)->getTaskSharedUserById($sourceId);
        $joinerIds = array_column($joiner, 'user_id');
        $shareIds = array_column($share, 'user_id');
        $calendarUser = array_unique(array_merge([$taskData->manage_user], $joinerIds));
        $attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $sourceId]);
        // 外发到日程模块 --开始--
        $calendarData = [
            'calendar_content' => $taskData->task_name,
            'handle_user'      => $calendarUser,
            'calendar_begin'   => $taskData->start_date,
            'calendar_end'     => ($taskData->end_date == '' || $taskData->end_date == '0000-00-00') ? '0000-00-00' : $taskData->end_date,
            'calendar_level'   => $taskData->important_level,
            'share_user'       => $shareIds,
            'attachment_id'    => $attachments,
            'calendar_remark'  => $taskData->task_description ?? ''
        ];
        $relationData = [
            'source_id'     => $sourceId,
            'source_from'   => 'task-new',
            'source_title'  => $taskData->task_name,
            'source_params' => ['task_id' => $sourceId]
        ];
        if ($type == 'update') {
            return app($this->calendarService)->emitUpdate($calendarData, $relationData, $userId);
        } else {
            return app($this->calendarService)->emit($calendarData, $relationData, $userId);
        }
        
    }

    /**
     * [createJoiner 添加参与人]
     *
     * @author 朱从玺
     *
     * @param  [array]          $createData [添加数据]
     * @param  [string]         $userId       [操作人ID]
     *
     * @return [object]                       [添加结果]
     */
    public function createJoiner($createData, $userId)
    {
        $taskInfo = $this->getTaskInfo($createData['task_id'], $userId);
        if ($userId != $taskInfo->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskInfo) {
            //判断是否锁定
            if ($taskInfo->lock == 1 && $userId != $taskInfo->manage_user) {
                return array('code' => array('0x046027', 'task'));
            }

            //判断主任务是否锁定
            if ($taskInfo->parent_id != 0) {
                $parentTask = app($this->taskManageRepository)->getDetail($taskInfo->parent_id);

                if ($parentTask && $parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                    return array('code' => array('0x046041', 'task'));
                }
            }

            //判断用户权限  必须是负责人
            if ($taskInfo->manage_user != $userId) {
                return array('code' => array('0x000017', 'common'));
            }

            $deletedUser = array_diff($taskInfo->join_user, $createData['user_ids']);
            $createdUser = array_diff($createData['user_ids'], $taskInfo->join_user);

            //删除参与人
            if ($deletedUser) {
                $where = [
                    'task_id'       => [$createData['task_id']],
                    'task_relation' => ['join'],
                    'user_id'       => [$deletedUser, 'in'],
                ];
                $result = app($this->taskUserRepository)->deleteByWhere($where);

                //记录操作日志
                $this->createPersonTaskLog($createData['task_id'], $userId, $deletedUser, trans('task.0x046033'));
            }

            //添加参与人
            if ($createdUser) {
                $insertData = [];
                foreach ($createdUser as $user) {
                    $insertData[] = [
                        'task_id'       => $createData['task_id'],
                        'user_id'       => $user,
                        'task_relation' => 'join',
                    ];
                }
                app($this->taskUserRepository)->insertMultipleData($insertData);
                $this->createPersonTaskLog($createData['task_id'], $userId, $createdUser, trans('task.0x046032'));
                //发送消息提醒
                $this->newTaskRemind($taskInfo, $createdUser);
            }
            $this->emitCalendarUpdate($createData['task_id'], $createData, $userId, 'update');
            return true;
        }

        return array('code' => array('0x046006', 'task'));
    }

    /**
     * [createShared 添加共享人]
     *
     * @author 朱从玺
     *
     * @param  [array]        $createData [添加数据]
     * @param  [string]       $userId       [操作人ID]
     *
     * @return [object]                     [添加结果]
     */
    public function createShared($createData, $userId)
    {
        $taskInfo = $this->getTaskInfo($createData['task_id'], $userId);
        if ($userId != $taskInfo->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskInfo) {
            //判断是否锁定
            if ($taskInfo->lock == 1 && $userId != $taskInfo->manage_user) {
                return array('code' => array('0x000017', 'common'));
            }

            //判断主任务是否锁定
            if ($taskInfo->parent_id != 0) {
                $parentTask = app($this->taskManageRepository)->getDetail($taskInfo->parent_id);

                if ($parentTask && $parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                    return array('code' => array('0x046041', 'task'));
                }
            }

            if (isset($createData['onlyAdd']) && $createData['onlyAdd']) {
                $deletedUser = [];

                foreach ($createData['user_ids'] as $key => $value) {
                    if (in_array($value, $taskInfo->shared_user)) {
                        unset($createData['user_ids'][$key]);
                    }
                }

                $createdUser = $createData['user_ids'];
            } else {
                $deletedUser = array_diff($taskInfo->shared_user, $createData['user_ids']);
                $createdUser = array_diff($createData['user_ids'], $taskInfo->shared_user);
            }

            if ($deletedUser) {
                $where = [
                    'task_id'       => [$createData['task_id']],
                    'task_relation' => ['shared'],
                    'user_id'       => [$deletedUser, 'in'],
                ];
                $result = app($this->taskUserRepository)->deleteByWhere($where);

                //记录操作日志
                $this->createPersonTaskLog($createData['task_id'], $userId, $deletedUser, trans('task.0x046035'));
            }

            if ($createdUser) {
                $insertData = [];
                foreach ($createdUser as $user) {
                    $insertData[] = [
                        'task_id'       => $createData['task_id'],
                        'user_id'       => $user,
                        'task_relation' => 'shared',
                    ];
                }
                app($this->taskUserRepository)->insertMultipleData($insertData);
                $this->createPersonTaskLog($createData['task_id'], $userId, $createdUser, trans('task.0x046034'));
                //发送消息提醒
                $this->newTaskRemind($taskInfo, $createdUser);
            }

            return true;
        }

        return array('code' => array('0x046006', 'task'));
    }

    /**
     * [completeTask 标记完成/未完成]
     *
     * @author 朱从玺
     *
     * @param  [string]       $userId       [用户ID]
     * @param  [array]        $taskId         [标记完成的数据]
     *
     * @return [bool]                       [标记结果]
     */
    public function completeTask($taskId, $userId)
    {
        $taskInfo = app($this->taskManageRepository)->getTaskInfo($taskId);
        if ($userId != $taskInfo->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskInfo) {
            //判断是否锁定
            if ($taskInfo->lock == 1 && $taskInfo->manage_user != $userId) {
                return array('code' => array('0x000017', 'common'));
            }

            //判断主任务是否锁定
            if ($taskInfo->parent_id != 0) {
                $parentTask = app($this->taskManageRepository)->getDetail($taskInfo->parent_id);

                if (!$parentTask) {
                    return array('code' => array('0x046038', 'task'));
                }

                if ($parentTask->lock == 1 && $taskInfo->manage_user != $userId) {
                    return array('code' => array('0x000017', 'common'));
                }
            }

            //标记完成
            if ($taskInfo->task_status == 0) {
                //主任务,如果有子任务未完成,不允许标记完成
                if ($taskInfo->parent_id == 0) {
                    if ($taskInfo->taskHasManySonTask->count() > 0 && $taskInfo->taskHasManyCompleteSon->count() > 0) {
                        return array('code' => array('0x046040', 'task'));
                    }

                    if ($taskInfo->taskHasManySonTask->count() == 0) {
                        $taskInfo->progress = 100;
                    }
                } else {
                    $taskInfo->progress = 100;
                }

                $taskInfo->task_status   = 1;
                $taskInfo->complete_date = date('Y-m-d H:i:s');
                $taskInfo->save();

                //记录日志
                $this->createTaskLog($taskId, $userId, trans('task.0x046004'));

                //任务完成提醒
                $this->completeTaskRemind([$taskInfo]);

                //更新主任务进度
                $this->modifyParentTaskProgress($taskInfo->parent_id);
                $this->emitCalendarComplete($taskId, $userId, 'complete');
                return 'complete';
                //标记未完成
            } else {
                //子任务,如果主任务已标记完成,不允许标记未完成
                if ($taskInfo->parent_id != 0) {
                    if ($parentTask->task_status == 1) {
                        return array('code' => array('0x046042', 'task'));
                    }
                }

                $taskInfo->task_status   = 0;
                $taskInfo->complete_date = '';
                $taskInfo->save();

                //记录日志
                $this->createTaskLog($taskId, $userId, trans('task.0x046005'));

                //任务重启提醒
                $this->restartTaskRemind($taskInfo);
                $this->emitCalendarComplete($taskId, $userId, 'delete');
                // 新增同步过去
                $this->emitCalendarUpdate($taskId, $taskInfo, $userId, 'add');
                return 'execute';
            }
        }

        return array('code' => array('0x046006', 'task'));
    }

    /**
     * [lockTask 锁定/解锁任务]
     *
     * @author 朱从玺
     *
     * @param  [int]      $lockId [任务ID]
     * @param  [string]   $userId [用户ID]
     *
     * @return [string]           [操作结果]
     */
    public function lockTask($lockId, $userId)
    {
        //获取有负责人权限以上的所有任务ID数组
        $taskArray = $this->getPowerTaskArray($userId, 'manager');

        //批量锁定
        if (is_array($lockId)) {
            $lockTasks = array_intersect($lockId, $taskArray);

            foreach ($lockTasks as $taskId) {
                $taskInfo = app($this->taskManageRepository)->getDetail($taskId);

                if ($taskInfo && $taskInfo->lock == 0) {
                    $taskInfo->lock = 1;
                    $taskInfo->save();

                    //记录日志
                    $this->createTaskLog($taskId, $userId, trans('task.0x046018'));
                }
            }

            return true;
        }

        //一个任务锁定/解锁
        if (!in_array($lockId, $taskArray)) {
            return array('code' => array('0x046001', 'task'));
        }

        $taskInfo = app($this->taskManageRepository)->getDetail($lockId);
        $childIds = [];
        if ($taskInfo) {
            //锁定
            if ($taskInfo->parent_id) {
                $taskParentInfo = app($this->taskManageRepository)->getDetail($taskInfo->parent_id);
                if ($taskParentInfo->lock == 1) {
                    //主任务已锁定
                    return array('code' => array('0x046047', 'task'));
                }
            }
            if ($taskInfo->lock == 0) {
                $taskInfo->lock = 1;
                $taskInfo->save();
                if ($taskInfo->parent_id == 0) {
                    $childIds = $this->batchDoChildTasks("lock", $taskInfo->id);
                }

                //记录日志
                $this->createTaskLog($lockId, $userId, trans('task.0x046018'));

                $type = 'lock';
                //解锁
            } else {
                $taskInfo->lock = 0;
                $taskInfo->save();
                if ($taskInfo->parent_id == 0) {
                    $childIds = $this->batchDoChildTasks("unlock", $taskInfo->id);
                }
                //记录日志
                $this->createTaskLog($lockId, $userId, trans('task.0x046019'));

                $type = 'cancelLock';
            }

            return [
                "type" => $type,
                "ids"  => $childIds,
            ];
        }

        return array('code' => array('0x046006', 'task'));
    }

    //批量加锁解锁子任务
    public function batchDoChildTasks($type, $pid)
    {
        $childs      = $this->getChildTasks($pid);
        $updateWhere = [
            'id' => [$childs, "in"],
        ];

        switch ($type) {
            case "unlock":
                $updateData = [
                    'lock' => 0,
                ];

                break;
            case "lock":{
                    $updateData = [
                        'lock' => 1,
                    ];

                    break;
                }
        }
        app($this->taskManageRepository)->updateData($updateData, $updateWhere);
        return $childs;
    }

    //获取子任务
    public function getChildTasks($parent_id)
    {
        $where = [
            "parent_id" => [$parent_id],
        ];
        $temp = app($this->taskManageRepository)->getTasksByWhere($where);
        $ids  = [];
        foreach ($temp as $t) {
            $ids[] = $t["id"];
        }

        return $ids;
    }

    /**
     * [followTask 关注/取消关注]
     *
     * @author 朱从玺
     *
     * @param  [integer]     $followData [任务ID,或任务ID数组]
     * @param  [string]      $userId     [用户ID]
     *
     * @return [string]                  [操作结果]
     */
    public function followTask($followData, $userId)
    {
        //判断用户权限  必须是负责人/参与人/共享人
        //获取有分享权限以上的所有任务ID数组
        //  $taskArray = $this->getPowerTaskArray($userId, 'shared');
        //批量关注
        if (is_array($followData)) {
            $followTasks = $followData; //array_intersect($followData, $taskArray);

            foreach ($followTasks as $taskId) {
                $where = [
                    'user_id'       => [$userId],
                    'task_id'       => [$taskId],
                    'task_relation' => ['follow'],
                ];
                $count = app($this->taskUserRepository)->getTaskUserCount($where);

                if (!$count) {
                    $insertData = ['user_id' => $userId, 'task_id' => $taskId, 'task_relation' => 'follow'];
                    $result     = app($this->taskUserRepository)->insertData($insertData);

                    //记录日志
                    $this->createTaskLog($taskId, $userId, trans('task.0x046003'));
                }
            }

            return true;
        }

//        //一个任务的关注或取消关注
        //        if (!in_array($followData, $taskArray)) {
        //            return array('code' => array('0x046001', 'task'));
        //        }

        $where = [
            'user_id'       => [$userId],
            'task_id'       => [$followData],
            'task_relation' => ['follow'],
        ];
        $count = app($this->taskUserRepository)->getTaskUserCount($where);

        //取消关注
        if ($count) {
            $where = [
                'user_id'       => [$userId],
                'task_id'       => [$followData],
                'task_relation' => ['follow'],
            ];

            $result = app($this->taskUserRepository)->deleteByWhere($where);

            //记录日志
            $this->createTaskLog($followData, $userId, trans('task.0x046002'));
            return 'cancel';
        }

        //关注
        $insertData = ['user_id' => $userId, 'task_id' => $followData, 'task_relation' => 'follow'];
        $result     = app($this->taskUserRepository)->insertData($insertData);

        //记录日志
        $this->createTaskLog($followData, $userId, trans('task.0x046003'));
        return 'follow';
    }

    /**
     * [getTaskInfo 获取任务数据]
     *
     * @author 朱从玺
     *
     * @param  [int]         $taskId [任务ID]
     * @param  [string]      $userId [查询人ID]
     *
     * @return [object]              [查询结果]
     */
    public function getTaskInfo($taskId, $userId, $data = [])
    {
        $withTrashed = false;
        if (isset($data['deletedMode']) && $data['deletedMode']) {
            $withTrashed = true;
        }
        $taskInfo = app($this->taskManageRepository)->getTaskInfo($taskId, $withTrashed);
        if ($taskInfo) {
            //统计任务关联人员
            $joinUser   = [];
            $sharedUser = [];
            $followUser = [];
            foreach ($taskInfo->taskUser as $relation) {
                switch ($relation->task_relation) {
                    case 'join':
                        $joinUser[] = $relation->user_id;
                        break;
                    case 'shared':
                        $sharedUser[] = $relation->user_id;
                        break;
                    case 'follow':
                        $followUser[] = $relation->user_id;
                        break;
                }
            }

            $taskInfo->join_user   = $joinUser;
            $taskInfo->shared_user = $sharedUser;
            $taskInfo->follow_user = $followUser;

            if (in_array($userId, $followUser)) {
                $taskInfo->isFollow = true;
            } else {
                $taskInfo->isFollow = false;
            }

            //判断权限
            $taskInfo->managerPower = false;
            $taskInfo->joinerPower  = false;
            if ($userId == $taskInfo->manage_user) {
                $taskInfo->managerPower = true;
            }

            if ($taskInfo->managerPower) {
                $taskInfo->joinerPower = true;
            } else {
                if (in_array($userId, $taskInfo->join_user)) {
                    $taskInfo->joinerPower = true;
                }
            }

            $taskInfo->class_id = $this->getClassIdByTaskId($taskId);

            //获取附件ID
            $attachments           = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $taskId]);
            $taskInfo->attachments = $attachments;
        } else {
            return ['code' => ['0x000006', 'common']];
        }

        return $taskInfo;
    }

    /**
     * 验证权限 点击主任务 次任务切换时
     */
    public function taskAuth($taskId, $userId, $data = [])
    {
        //获取有分享权限以上的所有任务ID数组
        $taskArray = $this->getPowerTaskArray($userId, 'shared');

        //有权限任务的子任务
        $sonTaskArray = $this->getSonTask($taskArray);

        //下属的任务
        $subordinateTask = $this->getSubordinateTask($userId);

        //下属任务的子任务
        $subordinateSonTask = $this->getSonTask($subordinateTask);

        if (!in_array($taskId, array_merge($taskArray, $sonTaskArray, $subordinateTask, $subordinateSonTask))) {
            return -1; //没有权限
        }

        return 1;
    }

    /**
     * 获取当前任务所在的classID
     */
    //getClassId
    public function getClassIdByTaskId($taskId)
    {

        $row = app($this->taskClassRelationRepository)->getClassIdByTaskId($taskId);
        if ($row) {
            return $row["class_id"];
        } else {
            return 0;
        }
    }

    /**
     * [completeTaskRemind 任务完成提醒]
     *
     * @author 朱从玺
     *
     * @param  [array]             $taskInfoArray [完成的任务数组]
     *
     * @return [bool]                             [提醒结果]
     */
    public function completeTaskRemind($taskInfoArray)
    {
        foreach ($taskInfoArray as $taskInfo) {
            $toUser   = array_column(app($this->taskUserRepository)->getTaskRelationUser($taskInfo->id)->toArray(), 'user_id');
            $toUser[] = $taskInfo->manage_user;

            $message = [
                'remindMark'   => 'task-complete',
                'toUser'       => array_unique($toUser),
                'contentParam' => ['taskName' => $taskInfo->task_name],
                'stateParams'  => ['taskId' => $taskInfo->id],
            ];

            Eoffice::sendMessage($message);
        }

        return true;
    }

    /**
     * [restartTaskRemind 重启任务提醒]
     *
     * @author 朱从玺
     *
     * @param  [object]            $taskInfo [重启的任务数据]
     *
     * @return [bool]                        [提醒结果]
     */
    public function restartTaskRemind($taskInfo)
    {
        $toUser   = array_column(app($this->taskUserRepository)->getTaskRelationUser($taskInfo->id)->toArray(), 'user_id');
        $toUser[] = $taskInfo->manage_user;
        $message  = [
            'remindMark'   => 'task-restart',
            'toUser'       => array_unique($toUser),
            'contentParam' => ['taskName' => $taskInfo->task_name],
            'stateParams'  => ['taskId' => $taskInfo->id],
        ];

        return Eoffice::sendMessage($message);
    }

    /**
     * [newTaskRemind 新任务提醒]
     *
     * @author 朱从玺
     *
     * @param  [object]        $taskInfo [任务数据]
     * @param  [array]         $toUser   [发送对象]
     *
     * @return [bool]                    [发送结果]
     */
    public function newTaskRemind($taskInfo, $toUser)
    {
        $message = [
            'remindMark'   => 'task-new',
            'toUser'       => $toUser,
            'contentParam' => ['taskName' => $taskInfo->task_name],
            'stateParams'  => ['taskId' => $taskInfo->id],
        ];

        return Eoffice::sendMessage($message);
    }

    /**
     * [pressTask 催办提醒]
     *
     * @author 朱从玺
     *
     * @param  [array]     $param    [催办参数]
     * @param  [string]    $userId   [用户ID]
     * @param  [string]    $userName [用户名]
     *
     * @return [bool]                [催办结果]
     */
    public function pressTask($param, $userId, $userName)
    {
        //获取有分享权限以上的所有任务ID数组
        $taskArray = $this->getPowerTaskArray($userId, 'shared');

        //有权限任务的子任务
        $sonTaskArray = $this->getSonTask($taskArray);

        //下属的任务
        $subordinateTask = $this->getSubordinateTask($userId);

        //下属任务的子任务
        $subordinateSonTask = $this->getSonTask($subordinateTask);

        //判断数据
        //催办内容
        if (!$param['pressContent'] || $param['pressContent'] == '') {
            return array('code' => array('0x046022', 'task'));
        }

        //发送方式
        if (!isset($param['sendMethod']) || $param['sendMethod'] == []) {
            return array('code' => array('0x046021', 'task'));
        }

        //接收人
        if ((!isset($param['manager']) || $param['manager'] == 0) && (!isset($param['joiner']) || $param['joiner'] == 0) && (!isset($param['otherUser']) || $param['otherUser'] == [])) {
            return array('code' => array('0x046023', 'task'));
        }

        //发送消息提醒参数
        $sendData['remindState'] = 'task.edit';
        $sendData['remindMark']  = 'task-press';
        $sendData['sendMethod']  = $param['sendMethod'];
        $sendData['isHand']      = true;

        //遍历任务数组,发送消息提醒
        foreach ($param['taskArray'] as $value) {
            //判断权限  必须是创建人/负责人/参与者/被分享人
            if (!in_array($value['id'], $taskArray) && !in_array($value['id'], $sonTaskArray) && !in_array($value['id'], $subordinateTask) && !in_array($value['id'], $subordinateSonTask)) {
                continue;
            }

            //发送内容
            $sendData['content']      = $param['pressContent'];
            $sendData['contentParam'] = ['task_name' => $value['task_name'], 'userName' => $userName];
            //接收人
            $toUser = [];
            if ($param['otherUser']) {
                if ($param['otherUser'] == 'all') {
                    //任务催办不需要全部
                } else {
                    $toUser = $param['otherUser'];
                }
            }

            if ($param['manager'] == 1) {
                $toUser[] = $value['manage_user'];
            }

            if ($param['joiner'] == 1) {
                $joinParam = [
                    'search' => [
                        'task_id'       => [$value['id']],
                        'task_relation' => ['join'],
                    ],
                ];

                $joiner = array_column(app($this->taskUserRepository)->getTaskUserList($joinParam)->toArray(), 'user_id');
                $toUser = array_merge($toUser, $joiner);
            }

            $sendData['toUser'] = array_unique($toUser);

            //跳转页面参数
            $sendData['stateParams'] = ['taskId' => $value['id']];

            Eoffice::sendMessage($sendData);
        }

        return true;
    }

    /**
     * [deleteTask 删除任务]
     *
     * @author 朱从玺
     *
     * @param  [int]        $taskId [任务ID]
     * @param  [string]     $userId [操作人ID]
     *
     * @return [bool]               [删除结果]
     */
    public function deleteTask($taskId, $userId)
    {
        $taskInfo = app($this->taskManageRepository)->getDetail($taskId);

        //如果任务不存在直接返回true
        if (!$taskInfo) {
            return ['code' => ['no_data', 'common']];
        }

        //权限验证  必须是负责人
        if ($taskInfo->manage_user != $userId) {
            return array('code' => array('0x000017', 'common'));
        }

        $deleteResult = app($this->taskManageRepository)->deleteById($taskId);

        //如果删除成功
        $deleteTaskIds = [$taskId];
        if ($deleteResult) {
            $this->emitCalendarComplete($taskId, $userId, 'delete');
            if ($taskInfo->parent_id == 0) {
                //同步删除子任务
                $subTaskIds = app($this->taskManageRepository)->entity
                    ->where('parent_id', $taskId)->get()->pluck('id')->toArray();
                if ($subTaskIds) {
                    app($this->taskManageRepository)->entity->whereIn('id', $subTaskIds)->delete();
                    $deleteTaskIds = array_merge($deleteTaskIds, $subTaskIds);
                }
            } else {
                //更新主任务进度
                $this->modifyParentTaskProgress($taskInfo->parent_id);
            }

            //记录操作日志
            $logContent = trans('task.0x046020');
            $logType    = 'delete';

            $this->createTaskLog($deleteTaskIds, $userId, $logContent, $logType);
        }

        return $deleteResult;
    }

    private function emitCalendarComplete($sourceId, $userId, $type = 'complete') 
    {
        $relationData = [
            'source_id' => $sourceId,
            'source_from' => 'task-new'
        ];
        if ($type == 'complete') {
            $result = app($this->calendarService)->emitComplete($relationData);
        } else {
            $result = app($this->calendarService)->emitDelete($relationData, $userId);
        }
        return $result;
    }

    /**
     * [mobileCreateTask 手机版创建任务]
     *
     * @method 朱从玺
     *
     * @param  [array]            $taskInfo [任务数据]
     * @param  [string]           $userId   [用户ID]
     *
     * @return [bool]                       [创建结果]
     */
    public function mobileCreateTask($taskInfo, $userId)
    {
        $taskInfo['joiner'] = isset($taskInfo['join_user']) ? $taskInfo['join_user'] : [];
        $taskInfo['shared'] = isset($taskInfo['shared_user']) ? $taskInfo['shared_user'] : [];
        $managerUser             = isset($taskInfo['manage_user']) ? $taskInfo['manage_user'] : $userId;
        $taskInfo['manage_user'] = $userId;
        $newTask                 = $this->createTask($taskInfo, $userId);

        if (is_array($newTask)) {
            return $newTask;
        }

        $taskInfo['start_date']       = isset($taskInfo['start_date']) ? $taskInfo['start_date'] : '';
        $taskInfo['end_date']         = isset($taskInfo['end_date']) ? $taskInfo['end_date'] : date('Y-m-d');
        $taskInfo['progress']         = isset($taskInfo['progress']) ? $taskInfo['progress'] : 0;
        $taskInfo['task_description'] = isset($taskInfo['task_description']) ? $taskInfo['task_description'] : '';
        $taskInfo['important_level']  = isset($taskInfo['important_level']) ? $taskInfo['important_level'] : 0;
        $taskInfo['manage_user']      = $managerUser;
        $taskInfo['join_user']        = isset($taskInfo['join_user']) ? $taskInfo['join_user'] : [];
        $taskInfo['shared_user']      = isset($taskInfo['shared_user']) ? $taskInfo['shared_user'] : [];

        return $this->mobileEditTask($newTask->id, $taskInfo, $userId);

        return true;
    }

    /**
     * [mobileEditTask 手机版编辑任务]
     *
     * @method 朱从玺
     *
     * @param  [integer]        $taskId   [任务数据]
     * @param  [array]          $taskInfo [任务数据]
     * @param  [string]         $userId   [用户ID]
     *
     * @return [bool]                     [编辑结果]
     */
    public function mobileEditTask($taskId, $taskInfo, $userId)
    {
        //参与人权限
        $taskArray = $this->getPowerTaskArray($userId, 'joiner');

        //如果没有参与人权限,则没有编辑权限
        if (!in_array($taskId, $taskArray)) {
            return array('code' => array('0x000017', 'common'));
        }

        $this->modifyTask($taskId, $taskInfo, $userId);

        //负责人权限
        $taskArray = $this->getPowerTaskArray($userId, 'manager');

        if (in_array($taskId, $taskArray)) {
            //编辑参与人
            if ($taskInfo['join_user']) {
                $createJoinerData = [
                    'task_id'  => $taskId,
                    'user_ids' => $taskInfo['join_user'],
                ];
                $this->createJoiner($createJoinerData, $userId);
            }

            //编辑共享人
            if ($taskInfo['shared_user']) {
                $createSharedData = [
                    'task_id'  => $taskId,
                    'user_ids' => $taskInfo['shared_user'],
                ];
                $this->createShared($createSharedData, $userId);
            }

            //编辑负责人
            if ($taskInfo['manage_user'] != $userId) {
                $userName = app($this->userRepository)->getUserName($taskInfo['manage_user']);
                $this->modifyTaskManager($taskId, ['user_id' => $taskInfo['manage_user'], 'user_name' => $userName], $userId);
            }
        }

        return true;
    }

    /**
     * [modifyParentTaskProgress 更新主任务进度]
     *
     * @method 朱从玺
     *
     * @param  [int]                    $taskId    [子任务ID]
     *
     * @return [bool]                              [更新结果]
     */
    public function modifyParentTaskProgress($taskId)
    {
        //主任务数据
        if ($taskId <= 0) {
            return false;
        }
        //获取父任务下的任务值统计
        $diaryCount = app($this->taskManageRepository)->taskCountSumPersent($taskId);
        $taskCount  = app($this->taskManageRepository)->taskCountNums($taskId);

        if ($taskCount == "0" || $diaryCount == "0") {
            $parentTaskProgress = "0"; //111
        } else {
            $persent            = $diaryCount / $taskCount;
            $parentTaskProgress = round($persent);
        }

        $updateData = [
            'progress' => $parentTaskProgress,
        ];
        $updateWhere = [
            'id' => [$taskId],
        ];

        return app($this->taskManageRepository)->updateData($updateData, $updateWhere);
    }

    /**
     * [getSubordinateTaskList 获取用户下属任务列表]
     *
     * @author 朱从玺
     *
     * @param  [array]                  $param  [查询条件]
     * @param  [string]                 $userId [用户ID]
     *
     * @return [array]                          [查询结果]
     */
    public function getSubordinateTaskList($param, $userId)
    {
        //查询条件
        $param = $this->parseParams($param);
        // 获取当前用户所有下级
        $userSubordinate = app($this->userService)->getSubordinateArrayByUserId($userId, ['all_subordinate' => 1]);

        if (!$userSubordinate) {
            return ['total' => 0, 'list' => ''];
        }
        // 高级查询 任务关联 参数
        $reportUser = [];
        if (isset($param['reportUser'])) {
            $reportUser = json_decode($param['reportUser'], true);
        }
        if (count($reportUser)) {
            $param['reportUser'] = $reportUser;
        } else {
            $param['reportUser'] = ['manager', 'joiner', 'shared'];
        }
        // $param 里面的 user_id 和 $param search user_name 条件，不是互斥的，不能写在一个 ifelse 里--20171023修复bug24069的备注
        // $param['user_id'] ，这参数，是我的下属菜单页面，选中中间列某个下属才会传的
        if (isset($param['user_id']) && $param['user_id']) {
            // 验证传过来的user_id，是否是当前人员的下属，不是，报错。是，存在 $param['userIds'] 里面
            if (in_array($param['user_id'], $userSubordinate['id'])) {
                $param['userIds'] = [$param['user_id']];
            } else {
                return array('code' => array('0x000006', 'common'));
            }
        } else {
            // 传了 user_id，说明中间列选人了，那么，就按照中间列的人，关联三种关系（负责人 参与人 共享人）进行查询
            // 没传user_id，用下属的人，关联三种关系进行查询
            $param['userIds'] = $userSubordinate['id'];
        }
        // $param['user_name'] ，这参数，是我的下属菜单页面，grid，快速查询，查询任务负责人才会传的参数
        if (isset($param['search']['user_name'])) {
            $userParam = [
                'search' => [
                    'user_name' => $param['search']['user_name'],
                ],
            ];
            //查询用户
            $userList      = app($this->userRepository)->getUserDeptName($userParam)->toArray();
            $searchUserIds = array_column($userList, 'user_id');
            if (count($searchUserIds)) {
                $param["fieldSearchManageUser"] = $searchUserIds;
            } else {
                $param["fieldSearchManageUser"] = [];
            }
            unset($param['search']['user_name']);
        }

        if (isset($param['search'])) {
            $param['taskSearch'] = $param['search'];
        }

        $param['taskType'] = 'all';

        $followTaskIds = $this->getTaskByTypes(["follow"], $userId);
        //获取我关注的任务ID
        $myAttention = [];
        if (isset($param['attention'])) {
            $myAttention = json_decode($param['attention'], true);
            if (count($myAttention) == 2) {
                $myAttention = [];
            }
        }

        $param["my_attention"] = [];
        if ($myAttention) {
            $param["my_attention"]     = $myAttention;
            $param["my_attention_ids"] = $followTaskIds;
        }

        $temp = $this->response(app($this->taskManageRepository), 'getTaskCountByUser', 'getTaskListByUser', $param);

        $lists      = $temp["list"];
        $total      = $temp["total"];
        $resultList = [];
        foreach ($lists as $list) {

            $list["is_follow"] = 0;
            if (in_array($list["id"], $followTaskIds)) {
                $list["is_follow"] = 1;
            }
            $resultList[] = $list;
        }

        return [
            "total" => $total,
            "list"  => $resultList,
        ];
    }

    /**
     * 获取我关注的任务ID
     */
    public function getTaskByTypes($type, $userId)
    {

        $where = [
            "task_relation" => [$type, "in"],
            "user_id"       => [$userId],
        ];

        return array_column(app($this->taskUserRepository)->getTasksByWhere($where, ['task_id']), 'task_id');
    }

    /**
     * [getUserSubordinate 获取用户下级,用于中间列]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @return [array]                      [查询结果]
     */
    public function getUserSubordinate($userId, $param)
    {
        $param              = $this->parseParams($param);
        $getSubordinateType = ["returntype" => "list", 'include_leave' => true];
        $searchUserId       = "";
        if (isset($param["search"])) {
            $getSubordinateType["search"] = $param["search"];
            if (isset($param["search"]["user_id"])) {
                $searchUserId = $param["search"]["user_id"];
                if (isset($searchUserId[0])) {
                    $searchUserId = $searchUserId[0];
                }
            }
            if (isset($param["search"]["user_name"])) {
                // 这里要查 user_name 的同时，查 拼音、字母
                $searchUserName                              = $param["search"]["user_name"];
                $getSubordinateType["search"]["multiSearch"] = [
                    'user_name'    => $searchUserName,
                    'user_name_py' => $searchUserName,
                    'user_name_zm' => $searchUserName,
                    '__relation__' => 'or',
                ];
                unset($getSubordinateType["search"]["user_name"]);
            }
        }
        $getSubordinateType['all_subordinate'] = 1;
        $userSubordinate = app($this->userService)->getSubordinateArrayByUserId($userId, $getSubordinateType);
        if (!$userSubordinate) {
            return '';
        }

        $subordinateArray = [];
        if (count($userSubordinate)) {
            foreach ($userSubordinate as $key => $value) {
                if (isset($value["user_id"]) && $value["user_id"] && isset($value["user_name"]) && $value["user_name"]) {
                    if ($searchUserId != "") {
                        if ($value["user_id"] == $searchUserId) {
                            array_push($subordinateArray, [
                                "user_id"   => $value["user_id"],
                                "user_name" => $value["user_name"],
                            ]);
                        }
                    } else {
                        $subordinateArray[$key]['user_id']   = $value["user_id"];
                        $subordinateArray[$key]['user_name'] = $value["user_name"];
                        $subordinateArray[$key]['user_status'] = Arr::get($value, 'user_has_one_system_info.user_status');
                    }
                }
            }
        }

        return $subordinateArray;
    }

    /**
     * [getTaskRelationUser 获取任务关联用户]
     *
     * @author 朱从玺
     *
     * @param  [int]                 $taskId       [任务ID]
     * @param  [string]              $relationType [关联类型,join参与follow关注shared分享]
     * @param  [string]               $userId       [查询人ID]
     *
     * @return [object]                            [查询结果]
     */
    public function getTaskRelationUser($taskId, $relationType, $userId)
    {
        //判断用户权限  必须是创建人/负责人/参与者/被分享人
        //获取有分享权限以上的所有任务ID数组
        $taskArray = $this->getPowerTaskArray($userId, 'shared');

        //有权限任务的子任务
        $sonTaskArray = $this->getSonTask($taskArray);

        //下属的任务
        $subordinateTask = $this->getSubordinateTask($userId);

        //下属任务的子任务
        $subordinateSonTask = $this->getSonTask($subordinateTask);

        if (!in_array($taskId, array_merge($taskArray, $sonTaskArray, $subordinateTask, $subordinateSonTask))) {
            return array('code' => array('0x000006', 'common'));
        }

        $param = [
            'fields' => ['user_id'],
            'search' => [
                'task_id'       => [$taskId],
                'task_relation' => [$relationType],
            ],
        ];

        return app($this->taskUserRepository)->getTaskUserList($param);
    }

    /**
     * [createTaskFeedback 添加任务反馈]
     *
     * @author 朱从玺
     *
     * @param  [array]             $feedbackData [反馈数据]
     * @param  [string]             $userId       [用户ID]
     *
     * @return [object]                           [添加结果]
     */
    public function createTaskFeedback($feedbackData, $userId)
    {
        $feedbackData['user_id'] = $userId;

        if ($result = app($this->taskFeedBackRepository)->insertData($feedbackData)) {
            // 附件处理
            if (isset($feedbackData['attachments'])) {
                app($this->attachmentService)->attachmentRelation("task_feedback", $result["id"], $feedbackData['attachments']);
            }
            $this->sendCommentReminder(Arr::get($feedbackData, 'task_id'), $userId, Arr::get($feedbackData, 'parent_id'));
            return $result;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * [modifyTaskFeedback 编辑反馈]
     *
     * @method 朱从玺
     *
     * @param  [int]                $feedbackId   [反馈ID]
     * @param  [array]              $feedbackData [修改数据]
     * @param  [string]             $userId       [用户ID]
     *
     * @return [bool]                             [编辑结果]
     */
    public function modifyTaskFeedback($feedbackId, $feedbackData, $userId)
    {
        $oldFeedbackData = app($this->taskFeedBackRepository)->getDetail($feedbackId);

        if (!$oldFeedbackData) {
            return array('code' => array('0x046036', 'task'));
        }

        if ($oldFeedbackData->user_id != $userId) {
            return array('code' => array('0x000017', 'common'));
        }

        if (!$this->testTime($oldFeedbackData->created_at)) {
            return array('code' => array('0x000017', 'common'));
        }

        if ($oldFeedbackData->feedback_content == $feedbackData['feedback_content']) {
            // return true;
        }

        $updateData = [
            'feedback_content' => $feedbackData['feedback_content'],
        ];

        $updateWhere = [
            'id' => [$feedbackId],
        ];
        if ($result = app($this->taskFeedBackRepository)->updateData($updateData, $updateWhere)) {
            if (isset($feedbackData['attachments'])) {
                app($this->attachmentService)->attachmentRelation("task_feedback", $feedbackId, $feedbackData['attachments']);
            }

            return $result;
        }

        return true;
    }

    /**
     * [deleteTaskFeedback 删除反馈]
     *
     * @method 朱从玺
     *
     * @param  [int]              $feedbackId [反馈ID]
     *
     * @return [bool]                         [删除结果]
     */
    public function deleteTaskFeedback($feedbackId, $userId)
    {
        $oldFeedbackData = app($this->taskFeedBackRepository)->getDetail($feedbackId);

        if (!$oldFeedbackData) {
            return true;
        }

        if ($oldFeedbackData->user_id != $userId) {
            return array('code' => array('0x000017', 'common'));
        }

        if (!$this->testTime($oldFeedbackData->created_at)) {
            return array('code' => array('0x000017', 'common'));
        }

        return app($this->taskFeedBackRepository)->deleteById($feedbackId);
    }

    /**
     * [getTaskFeedback 获取任务反馈]
     *
     * @author 朱从玺
     *
     * @param  [int]             $taskId [任务ID]
     * @param  [string]          $userId [用户ID]
     *
     * @return [object]                  [查询结果]
     */
    public function getTaskFeedback($taskId, $params, $userId)
    {

        $params['taskId'] = $taskId;
        $count            = app($this->taskFeedBackRepository)->getTaskFeedbackCount($params);
        $list             = [];
        if ($count > 0) {
            $_list = app($this->taskFeedBackRepository)->getTaskFeedback($params);
            foreach ($_list as $item) {
                $item->attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_feedback', 'entity_id' => $item->id]);
                // $returnFeedbackData = [];
                // 取回复的附件
                if ($item->feedbackHasManySon && $item->feedbackHasManySon->count()) {
                    foreach ($item->feedbackHasManySon as $key => $value) {
                        $value->attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_feedback', 'entity_id' => $value->id]);
                        // $returnFeedbackData[$key] = $value;
                    }
                }
                // $item->feedback_has_many_son = $returnFeedbackData;
                $list[] = $item;
            }
        }

        return ['total' => $count, 'list' => $list];
        // return $this->response(app($this->taskFeedBackRepository), 'getTaskFeedbackCount', 'getTaskFeedback', $params);
    }

    /**
     * [getFeedbackInfo 获取某条任务反馈数据]
     *
     * @author 朱从玺
     *
     * @param  [int]             $feedbackId [反馈ID]
     *
     * @return [object]                      [查询结果]
     */
    public function getFeedbackInfo($feedbackId)
    {
        return app($this->taskFeedBackRepository)->getFeedbackInfo($feedbackId);
    }

    /**
     * 手机版在用
     * [getTaskList 获取某个用户的任务列表]
     *
     * @author 朱从玺
     *
     * @param  [string]      $userId [用户ID]
     * @param  [array]       $param  [查询条件]
     *
     * @return [object]              [查询结果]
     */
    public function getTaskList($param, $userId)
    {
        $param = $this->parseParams($param);

        /**
         * task_class为查询类别
         *         mine    我的任务
         *         join     参与的任务
         *         manage     负责的任务
         *         follow     关注的任务
         *         shared     共享给我的
         *         create     创建的
         *         all     全部
         *         complete完成
         *         deleted    有删除记录的
         *
         * end_status为结束时间类型
         *         delay     已经延期
         *         today     今天到期
         *         tomorrow明天到期
         *         will     即将到期
         *         noTime     无到期日
         *         all     全部
         */
        $defaultParam = array(
            'task_class' => 'mine',
            'end_status' => 'all',
            'page'       => 0,
            'limit'      => 5,
            'order_by'   => ['id' => 'asc'],
            'user_id'    => $userId,
            'search'     => []
        );

        $param = array_merge($defaultParam, $param);

        //查询全部任务
        if (isset($param['search']['task_status']) && $param['search']['task_status'] == 'all') {
            unset($param['search']['task_status']);
        }

        //字段查询中的负责人查询
        if (isset($param['search']['manager']) && $param['search']['manager']) {
            $userParams = [
                'fields' => ['user_id'],
                'search' => [
                    'user_name' => $param['search']['manager'],
                ],
            ];
            unset($param['search']['manager']);

            $userIds = array_column(app($this->userRepository)->getUserList($userParams), 'user_id');

            $param['search']['manage_user'] = [$userIds, 'in'];
        }

        $taskData = $this->response(app($this->taskManageRepository), 'getUserTaskCount', 'getUserTaskList', $param);

        if (isset($taskData['list'])) {
            $followTaskArray = $this->getUserRelationTask($userId, 'follow');

            foreach ($taskData['list'] as $key => $value) {
                //判断是否关注
                if (in_array($value['id'], $followTaskArray)) {
                    $taskData['list'][$key]['follow_status'] = 1;
                } else {
                    $taskData['list'][$key]['follow_status'] = 0;
                }
            }
        }

        return $taskData;
    }

    public function taskPortal($param, $userId)
    {
        $param = $this->parseParams($param);
        $classIds = Arr::get($param, 'search.class_id.0', []);
        $classIds = is_array($classIds) ? $classIds : [$classIds];
        // 获取所有任务id
        $ids  = $this->getTaskByTypes(["join", "shared"], $userId);
        $mids = $this->getManagerUserByUserId($userId);
        $allIds = array_unique(array_merge($mids, $ids));

        if ($classIds) {
            $hasUnassigned = in_array(0, $classIds);
            if ($hasUnassigned) {
                $allClassIds = array_column(app($this->taskClassRepository)->getTaskClassByWhere(['user_id' => [$userId]], ['id']), 'id');
                $otherClassIds = array_diff($allClassIds, $classIds);
                $otherIds = app($this->taskClassRelationRepository)->getTaskIdsByClassId($otherClassIds);
                $otherIds = Arr::pluck($otherIds, 'task_id');
                $allIds = array_diff($allIds, $otherIds);
            } else {
                $classTaskIds = app($this->taskClassRelationRepository)->getTaskIdsByClassId($classIds);
                $classTaskIds = Arr::pluck($classTaskIds, 'task_id');
                $allIds = array_intersect($allIds, $classTaskIds);
            }
            unset($param['search']['class_id']);
        }

        $param["search_id"] = ["id" => [$allIds, "in"]];

        $taskData = $this->response(app($this->taskManageRepository), 'getTaskPortalTotal', 'getTaskPortallist', $param);

        return $taskData;
    }

    /**
     * 获取我负责的项目ID
     */
    private function getManagerUserByUserId($userId)
    {
        $taskIds = app($this->taskManageRepository)->getManagerPowerTask($userId, 'id');
        $result  = [];
        foreach ($taskIds as $t) {
            $result[] = $t["id"];
        }

        return $result;
    }

    /**
     * [getUserRelationTask 获取用户关联任务]
     *
     * @author 朱从玺
     *
     * @param  [string]              $userId       [用户ID]
     * @param  [string]              $relationType [关联类型]
     *
     * @return [object]                            [查询结果]
     */
    public function getUserRelationTask($userId, $relationType)
    {
        switch ($relationType) {
            case 'create': //创建的
                $param = [
                    'fields' => ['id'],
                    'search' => [
                        'create_user' => [$userId],
                    ],
                ];

                $taskIds = app($this->taskManageRepository)->getTaskList($param);
                break;
            case 'manage': //负责的
                $param = [
                    'fields' => ['id'],
                    'search' => [
                        'manage_user' => [$userId],
                    ],
                ];

                $taskIds = app($this->taskManageRepository)->getTaskList($param);
                break;
            case 'join': //参与的
                $param = [
                    'fields' => ['task_id'],
                    'search' => [
                        'user_id'       => [$userId],
                        'task_relation' => ['join'],
                    ],
                ];

                $taskIds = app($this->taskUserRepository)->getTaskUserList($param);
                break;
            case 'shared': //被分享的
                $param = [
                    'fields' => ['task_id'],
                    'search' => [
                        'user_id'       => [$userId],
                        'task_relation' => ['shared'],
                    ],
                ];

                $taskIds = app($this->taskUserRepository)->getTaskUserList($param);
                break;
            case 'follow': //关注的
                $param = [
                    'fields' => ['task_id'],
                    'search' => [
                        'user_id'       => [$userId],
                        'task_relation' => ['follow'],
                    ],
                ];

                $taskIds = app($this->taskUserRepository)->getTaskUserList($param);
                break;

            default:
                return '';
                break;
        }

        $taskArray = [];

        foreach ($taskIds as $value) {
            if (in_array($relationType, ['create', 'manage'])) {
                $taskArray[] = $value->id;
            } else {
                $taskArray[] = $value->task_id;
            }
        }

        return $taskArray;
    }

    /**
     * [getTaskLog 获取任务日志]
     *
     * @author 朱从玺
     *
     * @param  [int]        $taskId [任务ID]
     * @param  [array]      $param [查询条件]
     * @param  [string]     $userId [用户ID]
     *
     * @return [object]             [查询结果]
     */
    public function getTaskLog($taskId, $param, $userId)
    {
        $param = $this->parseParams($param);

        $param['search'] = isset($param['search']) ? $param['search'] : [];

        $param['search']['task_id'] = [$taskId];

        return app($this->taskLogRepository)->getLogList($param);
    }

    /**
     * [getTaskReport 获取任务分析报告]
     *
     * @author 朱从玺
     *
     * @param  [array]        $params [查询条件]
     *
     * @return [array]               [查询结果]
     */
    public function getTaskReport($params)
    {

        $params = $this->parseParams($params);

        $taskSearch = $reportUser = [];

        if (isset($params['taskSearch'])) {
            $taskSearch = json_decode($params['taskSearch'], true);
        }

        if (isset($params['reportUser'])) {
            $reportUser = json_decode($params['reportUser'], true);
        }

        if (empty($reportUser)) {
            $reportUser = ['manager', 'joiner', 'shared'];
        }

        $default = array(
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'order_by'   => ['dept_id' => 'asc'],
            'search'     => [],
            'taskSearch' => [],
        );

        $params = array_merge($default, $params);
        // 包含离职--20180308-dp
        $params["include_leave"] = "1";
        Arr::set($params['order_by'], 'list_number', 'asc');//增加按用户序号排序
        Arr::set($params['order_by'], 'user_name', 'asc');//增加按用户序号排序
        //查询用户
        $userList = app($this->userRepository)->getUserDeptName($params)->toArray();

        $taskRelation = [];

        if (in_array('joiner', $reportUser)) {
            array_push($taskRelation, 'join');
        }

        if (in_array('shared', $reportUser)) {
            array_push($taskRelation, 'shared');
        }

        $allSearch = $executeSearch = $delaySearch1 = $delaySearch2 = $taskSearch;

        $delaySearch2['end_date']    = ['complete_date', '<'];
        $delaySearch2['task_status'] = [1, '='];
        $lists = [];

        foreach ($userList as $user) {
            $managerUserId = in_array('manager', $reportUser) ? $user['user_id'] : false;
            // null代表不查询，空数组代表无数据，两者不能混淆
            $userTaskId    = empty($taskRelation) ? null : array_column(app($this->taskUserRepository)->getTasksByWhere(['user_id' => [$user['user_id']], 'task_relation' => [$taskRelation, 'in']], 'task_id'), 'task_id');
            // 任务数
            $user['all'] = app($this->taskManageRepository)->getTaskCount($taskSearch, $managerUserId, $userTaskId);
            // 进行中
//            $user['execute'] = app($this->taskManageRepository)->getTaskCount($executeSearch, $managerUserId, $userTaskId);
            $completeInfo = app($this->taskManageRepository)->getUserCompleteTaskAgeGrade($managerUserId, $userTaskId, $executeSearch);
            // 已延期
            $user['delay'] = app($this->taskManageRepository)->getTaskCount([], $managerUserId, $userTaskId, 'delay', $taskSearch);
            // 已完成
            $user['complete'] = $completeInfo['count'];
            $user['avg_task_grade'] = round($completeInfo['avg_task_grade'], 2);
            $user['execute'] = $user['all'] - $user['complete'];
            // 完成率
            $user['completeRate'] = $user['all'] == 0 ? '100%' : sprintf('%.3f', $user['complete'] / $user['all']) * 100 . '%';
            $lists[]              = $user;
        }

        unset($params['order_by']);

        $total = app($this->userRepository)->getUserListTotal($params);

        return ['total' => $total, 'list' => $lists];
    }
    /**
     * [getUserTaskReport 获取部分用户任务]
     *
     * @author 朱从玺
     *
     * @param  [array]            $params [查询条件]
     *
     * @return [array]                    [查询结果]
     */
    public function getUserTaskReport($params)
    {
        //声明一个以用户ID为键值的数组
        $userTask = [];
        foreach ($params['userIds'] as $userId) {
            $userTask[$userId] = [
                'all'      => [],
                'delay'    => [],
                'execute'  => [],
                'complete' => [],
            ];
        }

        //取消分页
        $params['page'] = 0;
        $today          = date('Y-m-d');

        //获取任务
        $allTask = app($this->taskManageRepository)->getUserTask($params);
        foreach ($allTask as $value) {
            if (isset($params['reportUser']) && $params['reportUser'] != []) {
                $joiner = [];
                $shared = [];

                foreach ($value['taskUser'] as $taskUser) {
                    if ($taskUser['task_relation'] == 'join') {
                        $joiner[] = $taskUser['user_id'];
                    } elseif ($taskUser['task_relation'] == 'shared') {
                        $shared[] = $taskUser['user_id'];
                    }
                }

                //分配任务
                if (in_array('manager', $params['reportUser'])) {
                    foreach ($userTask as $userId => $taskArray) {
                        if ($userId == $value['manage_user']) {
                            $userTask[$userId]['all'][] = $value;

                            if ($value['task_status'] == 0) {
                                $userTask[$userId]['execute'][] = $value;

                                if ($value['end_date'] < $today) {
                                    $userTask[$userId]['delay'][] = $value;
                                }
                            } else {
                                $userTask[$userId]['complete'][] = $value;
                            }
                        }
                    }
                }

                if (in_array('joiner', $params['reportUser'])) {
                    foreach ($userTask as $userId => $taskArray) {
                        if (in_array($userId, $joiner)) {
                            //如果是任务的负责人且查询对象包含负责人,则前面已经放入数组,在这里不再放入
                            if (in_array('manager', $params['reportUser']) && $userId == $value['manage_user']) {
                                continue;
                            }

                            $userTask[$userId]['all'][] = $value;

                            if ($value['task_status'] == 0) {
                                $userTask[$userId]['execute'][] = $value;

                                if ($value['end_date'] < $today) {
                                    $userTask[$userId]['delay'][] = $value;
                                }
                            } else {
                                $userTask[$userId]['complete'][] = $value;
                            }
                        }
                    }
                }

                if (in_array('shared', $params['reportUser'])) {
                    foreach ($userTask as $userId => $taskArray) {
                        if (in_array($userId, $shared)) {
                            //如果是任务的负责人且查询对象包含负责人,则前面已经放入数组,在这里不再放入
                            if (in_array('manager', $params['reportUser']) && $userId == $value['manage_user']) {
                                continue;
                            }

                            if (in_array('joiner', $params['reportUser']) && in_array($userId, $joiner)) {
                                continue;
                            }

                            $userTask[$userId]['all'][] = $value;

                            if ($value['task_status'] == 0) {
                                $userTask[$userId]['execute'][] = $value;

                                if ($value['end_date'] < $today) {
                                    $userTask[$userId]['delay'][] = $value;
                                }
                            } else {
                                $userTask[$userId]['complete'][] = $value;
                            }
                        }
                    }
                }
            } else {
                $userTask[$value['manage_user']]['all'][] = $value;

                if ($value['task_status'] == 0) {
                    $userTask[$value['manage_user']]['execute'][] = $value;

                    if ($value['end_date'] < $today) {
                        $userTask[$value['manage_user']]['delay'][] = $value;
                    }
                } else {
                    $userTask[$value['manage_user']]['complete'][] = $value;
                }
            }
        }

        return $userTask;
    }

    /**
     * [getOneUserTask 获取任务分析列表相关任务详情]
     *
     * @author 朱从玺
     *
     * @param  [array]         $param [查询条件]
     *
     * @return [array]                [查询结果]
     */
    public function getOneUserTask($param)
    {
        $param = $this->parseParams($param);
        if (isset($param['taskSearch']) && $param['taskSearch']) {
            $param['taskSearch'] = json_decode($param['taskSearch'], true);
        }
        if (isset($param['reportUser']) && $param['reportUser']) {
            $param['reportUser'] = json_decode($param['reportUser'], true);
        } else {
            $param['reportUser'] = ['manager', 'joiner', 'shared'];
        }
        if (isset($param['userIds']) && $param['userIds']) {
            $param['userIds'] = json_decode($param['userIds'], true);
        }

        $default = array(
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'order_by'   => [],
            'search'     => [],
            'taskSearch' => [],
        );

        $param = array_merge($default, $param);

        return $this->response(app($this->taskManageRepository), 'getTaskCountByUser', 'getTaskListByUser', $param);
    }
    public function getSimpleOneUserTask($param)
    {
        $param   = $this->parseParams($param);
        $default = array(
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'order_by'   => [],
            'search'     => [],
            'taskSearch' => [],
        );

        $reportUser = isset($param['reportUser']) ? json_decode($param['reportUser']) : [];

        $manageUserId = '';
        if (empty($reportUser)) {
            $manageUserId = $param['user_id'];
            $taskRelation = ['join', 'shared'];
        } else {
            if (in_array('manager', $reportUser)) {
                $manageUserId = $param['user_id'];
            }
            $taskRelation = [];
            if (in_array('joiner', $reportUser)) {
                $taskRelation[] = 'join';
            }
            if (in_array('shared', $reportUser)) {
                $taskRelation[] = 'shared';
            }
        }
        $param      = array_merge($default, $param);
        $userTaskId = null;

        if (!empty($taskRelation)) {
            $userTaskId = array_column(app($this->taskUserRepository)->getTasksByWhere(['user_id' => [$param['user_id']], 'task_relation' => [$taskRelation, 'in']], 'task_id'), 'task_id');
        }
        if (isset($param['taskSearch']) && is_string($param['taskSearch'])) {
            $param['taskSearch'] = json_decode($param['taskSearch'], true);
        }
        $count = app($this->taskManageRepository)->getTaskCount($param['search'], $manageUserId, $userTaskId, $param['task_type'], $param['taskSearch']);

        $returnList = [];

        if ($lists = app($this->taskManageRepository)->getOneUserSimpleTask($param, $manageUserId, $userTaskId, $param['task_type'])) {
            foreach ($lists as $list) {
                $list->manage_user_name = get_user_simple_attr($list->manage_user);
                $returnList[]           = $list;
            }
        }
        return ['total' => $count, 'list' => $returnList];
    }
    /**
     * [getUserTaskTrend 获取用户任务趋势图表数据]
     *
     * @author 朱从玺
     *
     * @param  [array]            $param  [查询条件]
     * @param  [string]           $userId [用户ID]
     *
     * @return [array]                   [查询结果]
     */
    public function getUserTaskTrend($param, $userId)
    {
        $dateRange = (isset($param['dateRange']) && $param['dateRange']) ? json_decode($param['dateRange'], true) : $this->getDates(date('Y-m-d', strtotime("-6 day")), date('Y-m-d'));

        $userTaskId = array_column(app($this->taskUserRepository)->getTasksByWhere(['user_id' => [$userId], 'task_relation' => [['join', 'shared'], 'in']], 'task_id'), 'task_id');

        $dateArray = [];

        foreach ($dateRange as $date) {
            $dateArray[$date]['executeCount']  = app($this->taskManageRepository)->getTaskCountByDate($userId, $userTaskId, $date, 'execute');
            $dateArray[$date]['newCount']      = app($this->taskManageRepository)->getTaskCountByDate($userId, $userTaskId, $date, 'new');
            $dateArray[$date]['completeCount'] = app($this->taskManageRepository)->getTaskCountByDate($userId, $userTaskId, $date, 'complete');
        }

        return $dateArray;
    }

    /**
     * [getDates 获取两个日期之间的具体日期]
     *
     * @author 朱从玺
     *
     * @param  [date]    $startdate [开始日期]
     * @param  [date]    $endDate   [结束日期]
     *
     * @since  2015-11-19 创建
     *
     * @return [array]              [查询结果]
     */
    public function getDates($startDate, $endDate)
    {
        $startDate = strtotime(date('Y-m-d', strtotime($startDate)));
        $endDate   = strtotime(date('Y-m-d', strtotime($endDate)));

        $dates = [];
        while ($startDate <= $endDate) {
            $dates[] = date('Y-m-d', $startDate);
            $startDate += 86400;
        }

        return $dates;
    }

    /**
     * [exportTaskReport 任务分析导出]
     *
     * @author 朱从玺
     *
     * @param  [array]           $param [导出条件]
     *
     * @return [array]                  [导出数据]
     */
    public function exportTaskReport($param)
    {
        $header = [
            'user_name'                                                                 => trans('task.user_name'),
            'user_has_one_system_info.user_system_info_belongs_to_department.dept_name' => trans('task.belongs_department'),
            'all'                                                                       => trans('task.task_number'),
            'execute'                                                                   => trans('task.doing'),
            'delay'                                                                     => trans('task.has_been_postponed'),
            'complete'                                                                  => trans('task.has_been_done'),
            'completeRate'                                                              => trans('task.percentage_complete'),
            'avg_task_grade'                                                            => trans('task.avg_task_grade'),
        ];

        if (isset($param['search'])) {
            $param['search'] = json_encode($param['search']);
        }

        $reportData = $this->getTaskReport($param)['list'];
        $data       = [];
        foreach ($reportData as $value) {
            $data[] = Arr::dot($value);
        }

        return compact('header', 'data');
    }

    /**
     * [exportTaskReport 任务分析导出]
     *
     * @author 朱从玺
     *
     * @param  [array]           $param [导出条件]
     *
     * @return [array]                  [导出数据]
     */
    public function exportTaskSubordinate($param)
    {

        $header = [
            'task_name'   => trans("task.0x046024"),
            'user_name'   => trans("task.person_in_charge"),
            'task_grade'  => trans("task.score"),
            'subtask'     => trans("task.subtask"),
            'start_date'  => trans("task.start_date"),
            'end_date'    => trans("task.end_date"),
            'progress'    => trans("task.task_schedule"),
            'task_status' => trans("task.is_it_done"),
        ];

        if (isset($param['search'])) {
            $param['search'] = json_encode($param['search']);
        }

        $userId     = $param["current_user"];
        $reportData = $this->getSubordinateTaskList($param, $userId)['list'];

        $data = [];

        foreach ($reportData as $value) {
            $t                = [];
            $item             = $value->toArray();

            $sonTotal = 0;
            $completeSon = 0;
            if (isset($item["task_has_many_son_task"]) && $item["task_has_many_son_task"] && $item["task_has_many_son_task"] != '') {
                if ($item["task_has_many_son_task"][0] && $item["task_has_many_son_task"][0]["sonTotal"]) {
                    $sonTotal = $item["task_has_many_son_task"][0]["sonTotal"];
                } else {
                    $sonTotal = $item["task_has_many_son_task"]["length"];
                }
            }
            if (isset($item["task_has_many_complete_son"]) && $item["task_has_many_complete_son"] && $item["task_has_many_complete_son"] != '') {
                if ($item["task_has_many_complete_son"][0] && $item["task_has_many_complete_son"][0]["completeSon"]) {
                    $completeSon = $item["task_has_many_complete_son"][0]["completeSon"];
                } else {
                    $completeSon = $item["task_has_many_son_task"]["length"] - $item["task_has_many_complete_son"]["length"];
                }
            }
            $subtask = $completeSon . '/' . $sonTotal;
            $t["task_name"]   = $item["task_name"];
            $t["user_name"]   = $item['task_has_one_manager']["user_name"];
            $t["task_grade"]  = $item["task_grade"];
            $t["subtask"]     = $subtask;
            $t["start_date"]  = $item["start_date"];
            $t["end_date"]    = $item["end_date"];
            $t["progress"]    = $item["progress"];
            $t["task_status"] = $item["task_status"] == 1 ? trans("task.complete") : trans("task.hang_in_the_air");
            $data[]           = Arr::dot($t);
        }

        return compact('header', 'data');
    }

    /**
     * 新版任务API
     *
     * 主要功能:
     *         任务类别新建编辑删除等操作;
     *         在存在任务类别情况下的任务新建,列表获取,排序等;
     *
     * 老版API手机版在用,暂时保留,以后看情况整合
     */

    /**
     * [createTaskClass 创建任务类别]
     *
     * @method 朱从玺
     *
     * @param  [array]           $classData [类别数据]
     * @param  [string]          $userId    [用户ID]
     *
     * @return [bool]                       [创建结果]
     */
    public function createTaskClass($classData, $userId)
    {
        $params = [
            'search'   => [
                'user_id' => [$userId],
            ],
            'order_by' => ['sort_id' => 'desc'],
        ];

        $taskClassList = app($this->taskClassRepository)->getTaskClassList($params, 'first');

        if ($taskClassList) {
            $sortId = $taskClassList->sort_id + 1;
        } else {
            $sortId = 1;
        }

        $createData = [
            'class_name' => $classData['class_name'],
            'user_id'    => $userId,
            'sort_id'    => $sortId,
        ];

        return app($this->taskClassRepository)->insertData($createData);
    }

    /**
     * [deleteTaskClass 删除任务类别]
     *
     * @method 朱从玺
     *
     * @param  [int]             $classId [类别ID]
     * @param  [string]          $userId  [用户ID]
     *
     * @return [bool]                     [删除结果]
     */
    public function deleteTaskClass($classId, $userId)
    {
        $classData = app($this->taskClassRepository)->getDetail($classId);

        if (!$classData) {
            return true;
        }

        if ($classData['user_id'] != $userId) {
            return array('code' => array('0x000017', 'common'));
        }

        app($this->taskClassRepository)->deleteById($classId);

        // 删除任务类别关联表数据
        $deleteWhere = [
            'class_id' => [$classId],
        ];

        app($this->taskClassRelationRepository)->deleteByWhere($deleteWhere);

        return true;
    }

    /**
     * [modifyTaskClass 编辑任务类别]
     *
     * @method 朱从玺
     *
     * @param  [int]            $classId   [类别ID]
     * @param  [array]          $classData [类别数据]
     *
     * @return [bool]                      [编辑结果]
     */
    public function modifyTaskClass($classId, $classData)
    {
        $where = [
            'id' => [$classId],
        ];

        return app($this->taskClassRepository)->updateDataBatch($classData, $where);
    }

    /**
     * [getMyTaskList 获取我的任务列表]
     *
     * @method 朱从玺
     *
     * @param  [array]         $params [查询条件]
     * @param  [string]        $userId [用户ID]
     *
     * @return [array]                 [查询结果]
     */
    public function getMyTaskList($params, $userId)
    {

        //查找当前用户所有任务类别
        $classParams = [
            'search'   => ['user_id' => [$userId]],
            'order_by' => ['sort_id' => 'asc'],
        ];
        $classList = app($this->taskClassRepository)->getTaskClassList($classParams)->toArray();
        if (!$classList) {
            $classList[] = [
                'id'         => 0,
                'class_name' => trans("task.unassigned"),
            ];
        }
        $classIdArray = array_column($classList, 'id');

        //查找任务分类对应的任务
        $relationWhere = ['class_id' => [$classIdArray, 'in']];

        $relationData = app($this->taskClassRelationRepository)->getRelationData($relationWhere)->toArray();

        $taskRelationClass = [];
        foreach ($relationData as $value) {
            $taskRelationClass[$value['task_id']] = $value['class_id'];
        }

        //获取用户所有任务
        $params = $this->parseParams($params);

        //字段查询中的负责人查询
        if (isset($params['search']['manager']) && $params['search']['manager']) {
            $userParams = [
                'fields' => ['user_id'],
                'search' => [
                    'user_name' => $params['search']['manager'],
                ],
            ];
            unset($params['search']['manager']);

            $userIds = array_column(app($this->userRepository)->getUserList($userParams), 'user_id');

            $params['search']['manage_user'] = [$userIds, 'in'];
        }

        $taskListData = app($this->taskManageRepository)->getMyTaskList($params);

        //判断任务是否有附件
        $hasAttachmentTask = app($this->attachmentService)->getEntityIdsFromAttachRelTable('task_manage');

        // $hasAttachmentTask = [];
        // foreach ($allAttachment as $value) {
        //     $hasAttachmentTask[] = $value->entity_id;
        // }

        //整理返回格式
        $myClass     = [];
        $noClassTask = [];
        foreach ($classList as $class) {
            $taskArray = [];
            foreach ($taskListData as $key => $task) {
                if (isset($taskRelationClass[$task['id']])) {
                    if ($taskRelationClass[$task['id']] == $class['id']) {
                        //完成日期
                        $taskListData[$key]['completeDate'] = $task['complete_date'] == '0000-00-00 00:00:00' ? '' : date('Y-m-d', strtotime($task['complete_date']));

                        //是否有附件
                        if (!empty($hasAttachmentTask) && in_array($task['id'], $hasAttachmentTask)) {
                            $taskListData[$key]['hasAttachment'] = true;
                        } else {
                            $taskListData[$key]['hasAttachment'] = false;
                        }

                        //是否关注
                        $taskListData[$key]['isFollow'] = false;
                        if ($task['taskUser'] != []) {
                            $followUser = array_column($task['taskUser']->toArray(), 'user_id');

                            if (in_array($userId, $followUser)) {
                                $taskListData[$key]['isFollow'] = true;
                            }
                        }

                        $taskArray[] = $taskListData[$key];
                        unset($taskListData[$key]);
                    }
                } else {
                    //完成日期
                    $taskListData[$key]['completeDate'] = $task['complete_date'] == '0000-00-00 00:00:00' ? '' : date('Y-m-d', strtotime($task['complete_date']));

                    //是否有附件
                    if ($hasAttachmentTask != [] && in_array($task['id'], $hasAttachmentTask)) {
                        $taskListData[$key]['hasAttachment'] = true;
                    } else {
                        $taskListData[$key]['hasAttachment'] = false;
                    }

                    //是否关注
                    $taskListData[$key]['isFollow'] = false;
                    if ($task['taskUser'] != []) {
                        $followUser = array_column($task['taskUser']->toArray(), 'user_id');

                        if (in_array($userId, $followUser)) {
                            $taskListData[$key]['isFollow'] = true;
                        }
                    }

                    $noClassTask[] = $taskListData[$key];
                    unset($taskListData[$key]);
                }
            }
            if ($taskArray || $class['id'] > 0) {
                $myClass[] = [
                    'class_id'         => $class['id'],
                    'class_name'       => $class['class_name'],
                    'isUnappropriated' => false,
                    'list_count'       => count($taskArray),
                    'taskArray'        => $taskArray,
                ];
            }
        }

        if ($noClassTask != []) {
            $noClass = [
                'class_id'         => 0,
                'class_name'       => trans("task.unassigned"),
                'isUnappropriated' => true,
                'list_count'       => count($noClassTask),
                'taskArray'        => $noClassTask,
            ];

            array_unshift($myClass, $noClass);
        }

        return $myClass;
    }

    /**
     * [modifyClassSort 任务分类排序]
     *
     * @method 朱从玺
     *
     * @param  [array]           $taskList [新传入的任务列表]
     * @param  [string]          $userId   [当前登录人员]
     *
     * @return [bool]                      [排序结果]
     */
    public function modifyClassSort($taskList, $userId)
    {
        //查找当前用户所有任务类别
        $classParams = [
            'search'   => ['user_id' => [$userId]],
            'order_by' => ['sort_id' => 'asc'],
        ];
        $classList = app($this->taskClassRepository)->getTaskClassList($classParams)->toArray();
        $classIdArray = array_column($classList, 'id');

        //新传入任务类别
        if ($taskList[0]['class_id'] == 0) {
            unset($taskList[0]);
        } else {
            return ['code' => ['not_support_allocate', 'task']];
        }
        $newClassIds = array_column($taskList, 'class_id');

        if (array_diff($classIdArray, $newClassIds) || array_diff($newClassIds, $classIdArray)) {
            return array('code' => array('0x046038', 'task'));
        }

        foreach ($newClassIds as $key => $value) {
            if ($value != $classIdArray[$key]) {
                $updateWhere = [
                    'id' => $value,
                ];
                $updateData = [
                    'sort_id' => $key + 1,
                ];

                app($this->taskClassRepository)->updateData($updateData, $updateWhere);
            }
        }

        return true;
    }

    /**
     * [modifyTaskClassRelation 更新任务与任务分类关联]
     *
     * @method 朱从玺
     *
     * @param  [array]                   $taskList [新传入的任务列表]
     * @param  [string]                  $userId   [当前登录人员]
     *
     * @return [bool]                              [更新结果]
     */
    public function modifyTaskClassRelation($data, $userId)
    {
        $classParams = [
            'search'   => ['user_id' => [$userId]],
        ];
        $classList = app($this->taskClassRepository)->getTaskClassList($classParams)->toArray();
        $classIds = array_column($classList, 'id');
        array_push($classIds, 0);

        $sourceClassId = isset($data['source_class_id']) ? $data['source_class_id'] : 0;
        $targetClassId = isset($data['target_class_id']) ? $data['target_class_id'] : 0;

        if (!in_array($sourceClassId, $classIds) || !in_array($targetClassId, $classIds)) {
            return array('code' => array('0x046038', 'task'));
        }

        $taskId        = $data['task_id'];
        if ($sourceClassId == 0) {
            if (!app($this->taskClassRelationRepository)->taskRelationExists($taskId, $targetClassId)) {
                app($this->taskClassRelationRepository)->insertData(['class_id' => $targetClassId, 'task_id' => $taskId]);
            }
        } else {
            app($this->taskClassRelationRepository)->updateData(['class_id' => $targetClassId], ['class_id' => $sourceClassId, 'task_id' => $taskId]);
        }
        return true;
    }

    /**
     * [getPowerTaskArray 验证用户权限]
     *
     * @author 朱从玺
     *
     * @param  [type]      $userId [用户ID]
     * @param  [type]      $power  [权限类型,manager负责人joiner参与人shared被分享]
     *
     * @return [type]              [相应权限的任务ID数组]
     */
    public function getPowerTaskArray($userId, $power)
    {
        switch ($power) {
            case 'manager':
                $params = [
                    'fields' => ['id', 'task_name'],
                    'search' => [
                        'manage_user' => [$userId],
                    ],
                ];
                break;
            case 'joiner':
                $params = [
                    'fields' => ['id', 'task_name'],
                    'search' => [
                        'manage_user'  => [$userId],
                        'multiSearch'  => [
                            'relationType' => ['join'],
                            'relationUser' => [$userId],
                        ],
                        '__relation__' => 'or',
                    ],
                ];
                break;
            case 'shared':
                $params = [
                    'fields' => ['id', 'task_name'],
                    'search' => [
                        'manage_user'  => [$userId],
                        'multiSearch'  => [
                            'relationType' => [['join', 'shared'], 'in'],
                            'relationUser' => [$userId],
                        ],
                        '__relation__' => 'or',
                    ],
                ];
                break;

            default:
                return [];
                break;
        }

        $taskList = app($this->taskManageRepository)->getMyTaskList($params, false)->toArray();

        return array_column($taskList, 'id');
    }

    /**
     * [getSonTask 获取任务子任务]
     *
     * @author 朱从玺
     *
     * @param  [int]        $taskId [任务ID]
     *
     * @return [object]             [查询结果]
     */
    public function getSonTask($taskId)
    {
        if (is_array($taskId)) {
            $params = [
                'fields' => ['id'],
                'search' => [
                    'parent_id' => [$taskId, 'in'],
                ],
            ];
        } else {
            $params = [
                'fields' => ['id'],
                'search' => [
                    'parent_id' => [$taskId],
                ],
            ];
        }

        $sonTask = app($this->taskManageRepository)->getMyTaskList($params, false)->toArray();

        return array_column($sonTask, 'id');
    }

    /**
     * [getSubordinateTask 获取下级的任务]
     *
     * @author 朱从玺
     *
     * @param  [type]             $userId [用户ID]
     *
     * @return [type]                     [查询结果]
     */
    public function getSubordinateTask($userId)
    {
        $userSubordinate = app($this->userService)->getSubordinateArrayByUserId($userId, ['all_subordinate' => 1]);

        if ($userSubordinate['id'] == []) {
            return [];
        }

        $params = [
            'fields' => ['id'],
            'search' => [
                'manage_user'  => [$userSubordinate['id'], 'in'],
                'multiSearch'  => [
                    'relationType' => [['join', 'shared'], 'in'],
                    'relationUser' => [$userSubordinate['id'], 'in'],
                ],
                '__relation__' => 'or',
            ],
        ];

        $subordinateTask = app($this->taskManageRepository)->getMyTaskList($params, false)->toArray();

        return array_column($subordinateTask, 'id');
    }

    /**
     * [createTaskLog 创建任务操作日志]
     *
     * @author 朱从玺
     *
     * @param  [int]           $taskId     [任务ID]
     * @param  [string]        $userId     [用户ID]
     * @param  [string]        $logContent [日志内容]
     *
     * @return [object]                    [创建结果]
     */
    public function createTaskLog($taskId, $userId, $logContent, $logType = '')
    {
        if (is_array($taskId)) {
            $logData = [];
            foreach ($taskId as $id) {
                $logData[] = [
                    'task_id'     => $id,
                    'user_id'     => $userId,
                    'log_content' => $logContent,
                    'log_type'    => $logType,
                ];
            }

            return app($this->taskLogRepository)->insertMultipleData($logData);
        }

        $logData = [
            'task_id'     => $taskId,
            'user_id'     => $userId,
            'log_content' => $logContent,
            'log_type'    => $logType,
        ];

        return app($this->taskLogRepository)->insertData($logData);
    }

    /**
     * 批量生产人员变动的日志
     * @param int $taskId
     * @param int $operateUserId 操作用户id
     * @param array $userIds
     * @param string $prefixLogContent 日志内容前缀
     * @return mixed
     */
    public function createPersonTaskLog($taskId, $operateUserId, array $userIds, $prefixLogContent, $logType = '')
    {
        $userNames = $this->getUserNames($userIds);
        foreach ($userIds as $userId) {
            $logData[] = [
                'task_id'     => $taskId,
                'user_id'     => $operateUserId,
                'log_content' => $prefixLogContent . ' ' . $userNames->get($userId),
                'log_type'    => $logType,
            ];
        }
        return app($this->taskLogRepository)->insertMultipleData($logData);
    }

    /**
     * @param $userIds
     * @return Collection
     */
    private function getUserNames($userIds)
    {
        return app($this->userRepository)->getUserNames($userIds)->pluck('user_name', 'user_id');
    }

    public function quickModifyTaskDetail($taskId, $data, $userId)
    {
        $taskArray = $this->getPowerTaskArray($userId, 'joiner');

        if (!in_array($taskId, $taskArray)) {
            return array('code' => array('0x000017', 'common'));
        }
        $where = ['id' => $taskId];
        $type  = isset($data["type"]) ? $data["type"] : "";
        $value = isset($data["value"]) ? $data["value"] : "";
        $pid   = isset($data["parent_id"]) ? $data["parent_id"] : "";
        $files = isset($data["files"]) ? $data["files"] : "";

        //验证
        $task = app($this->taskManageRepository)->getDetail($taskId);
        if (!$task) {
            return ['code' => ['0x046006', 'task']];
        }
        if ($task['lock'] == 1 && $task['manage_user'] != $userId) {
            return ['code' => ['0x046027', 'task']];
        }

        //存在子任务不允许修改进度
        if ($type == 'process') {
            $sonTasks = app($this->taskManageRepository)->getChildTask($taskId);
            if ($sonTasks->isNotEmpty()) {
                return ['code' => ['0x046048', 'task']];
            }
        }

        $newTaskData     = [];
        $attachments     = [];
        $logContentArray = [];
        $updateResult    = false;
        switch ($type) {
            case "task_name":

                $newTaskData       = ["task_name" => $value];
                $logContentArray[] = trans('task.0x046012') . ' ' . $value;
                break;
            case "process":
                if ($task['task_status'] == 1) {
                    return ['code' => ['0x046044', 'task']];
                }
                $newTaskData       = ["progress" => $value];
                $logContentArray[] = trans('task.0x046039') . ' ' . $value . '%';

                break;
            case "begin_day":
                $logContentArray[] = trans('task.0x046015') . ' ' . $value;
                $newTaskData       = ["start_date" => $value];
                break;
            case "end_day":
                $logContentArray[] = trans('task.0x046016') . ' ' . $value;
                $newTaskData       = ["end_date" => $value];
                break;
            case "task_detail":
                $logContentArray[] = trans('task.0x046013');
                $newTaskData       = ["task_description" => $value];
                break;
            case "task_attachments":
                // $newTaskData = ["attachments" => $value];
                $attachments = $value;
                break;
            case "task_level":
                if ($value == 0) {
                    $logContentArray[] = trans('task.0x046014') . ' ' . trans('task.general');
                } elseif ($value == 1) {
                    $logContentArray[] = trans('task.0x046014') . ' ' . trans('task.import');
                } else {
                    $logContentArray[] = trans('task.0x046014') . ' ' . trans('task.instancy');
                }
                $newTaskData = ["important_level" => $value];

                break;
            default:

                break;
        }

        if ($newTaskData) {
            $updateResult = app($this->taskManageRepository)->updateDataBatch($newTaskData, $where);

            if ($type == "process") {
                //更新主任务进度
                $this->modifyParentTaskProgress($pid);
            }
        }

        //附件上传
        // if ($attachments) {
        if ($type == "task_attachments") {
            $oldAttachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $taskId]);
            app($this->attachmentService)->attachmentRelation("task_manage", $taskId, $attachments);

            if ($files) {
                //记录操作日志
                $fileName   = implode(',', array_column($files, 'name'));
                $logContent = trans('task.0x046017') . ' ' . $fileName;
                $this->createTaskLog($taskId, $userId, $logContent);
            }

            $deleteAttachments = array_diff($oldAttachments, $attachments);

            if (!empty($deleteAttachments)) {
                $deleteData = app($this->attachmentService)->getAttachments(['attach_ids' => $deleteAttachments]);

                //记录操作日志
                $fileName   = implode(',', array_column($deleteData, 'attachment_name'));
                $logContent = trans('task.0x046037') . ' ' . $fileName;

                $this->createTaskLog($taskId, $userId, $logContent);
            }
        }

        //记录操作日志
        if ($updateResult) {
            if ($logContentArray) {
                foreach ($logContentArray as $logContent) {
                    $this->createTaskLog($taskId, $userId, $logContent);
                }
            }
        }
        $this->emitCalendarUpdate($taskId, $data, $userId, 'update');
        return $updateResult;
    }

    public function setTaskGrade($task_id, $user_id, $data)
    {
        $opUserId  = isset($data["user_id"]) ? $data["user_id"] : "";
        $taskGrade = isset($data["task_grade"]) ? $data["task_grade"] : "0";

        if ($taskGrade > 100) {
            $taskGrade = 100;
        }
        $taskGrade < 0 && $taskGrade = 0;

        if ($opUserId != $user_id) {
            return array('code' => array('0x046045', 'task'));
        }

        $where = array('id' => $task_id);
        $data  = array('task_grade' => $taskGrade);

        $updateResult = app($this->taskManageRepository)->updateData($data, $where);
        return $updateResult;
    }

    //任务转日程 数据翻转
    public function taskScheduleList($user_id, $data = [])
    {
        //获取任务是参与 共享的 用户ID
        $ids  = $this->getTaskByTypes(["join", "shared"], $user_id);
        $mids = $this->getManagerUserByUserId($user_id);
        $arr  = array_unique(array_merge($mids, $ids));
        if (!$arr) {
            return [];
        }
        $where          = ["id" => [$arr, "in"]];
        $calendar_begin = isset($data["calendar_begin"]) && $data["calendar_begin"] ? $data["calendar_begin"] : "";
        $calendar_end   = isset($data["calendar_end"]) && $data["calendar_end"] ? $data["calendar_end"] : "";

        $calendar_day = isset($data["calendar_day"]) && $data["calendar_day"] ? $data["calendar_day"] : "";
        $temp         = app($this->taskManageRepository)->taskScheduleList($where, $calendar_begin, $calendar_end, $calendar_day);
        $result       = [];
        $t2           = [];

        foreach ($temp as $v) {
            $t2["calendar_id"]      = $v["id"];
            $t2["calendar_content"] = $v["task_name"];
            $t2["calendar_level"]   = $v["important_level"];
            $t2["create_id"]        = $v["create_user"];
            $t2["user_id"]          = $v["manage_user"]; //负责人
            $t2["calendar_begin"]   = ($v["start_date"] && $v["start_date"] != '0000-00-00') ? $v["start_date"] : $v["created_at"];
            $t2["calendar_end"]     = $v["end_date"] == "0000-00-00" ? '' : $v["end_date"];
            $t2["share_user"]       = $this->getTaskUserById($v["id"], "shared"); //共享人
            $t2["join_user"]        = $this->getTaskUserById($v["id"], "join");
            $t2["type"]             = "task";
            array_push($result, $t2);
        }
        return $result;
    }

    //任务转日程  获取某个月有日程的日期
    public function getTaskScheduleByDate($date, $user_id)
    {
        $ids  = $this->getTaskByTypes(["join", "shared"], $user_id);
        $mids = $this->getManagerUserByUserId($user_id);
        $arr  = array_unique(array_merge($mids, $ids));
        if (!$arr) {
            return [];
        }
        $where = ["id" => [$arr, "in"]];
        $start = date('Y-m-01', strtotime($date));
        $end   = date('Y-m-d', strtotime("$start +1 month -1 day"));

        $temp = app($this->taskManageRepository)->getTaskScheduleByDate($start, $end, $where);

        $res = [];
        foreach ($temp as $t) {
            $task_end   = $t["end_date"] == "0000-00-00" || $t["end_date"] >= $end ? $end : $t["end_date"];
            $task_start = $t["start_date"] <= $start ? $start : $t["start_date"];
            $days       = $this->diffBetweenTwoDays($task_end, $task_start);

            for ($i = 0; $i <= $days; $i++) {
                $time  = strtotime($task_start) + $i * 86400;
                $res[] = date("Y-m-d", $time);
            }
        }

        return array_unique($res);
    }

    public function getTaskUserById($taskId, $type)
    {
        $where = [
            "task_id"       => [$taskId],
            "task_relation" => [$type],
        ];

        $temp   = app($this->taskUserRepository)->getTasksByWhere($where);
        $result = "";
        foreach ($temp as $t) {
            $result .= $t["user_id"] . ",";
        }

        return trim($result, ",");
    }

    //日程点击任务是 数据展示
    public function getTaskSchedule($taskId)
    {
        // $ids = $this->getTaskByTypes(["join", "shared"], $user_id);
        // $mids = $this->getManagerUserByUserId($user_id);
        // $arr = array_unique(array_merge($mids, $ids));
        // if (!in_array($taskId, $arr)) {
        //     return array('code' => array('0x046001', 'task'));
        // }
        //return $taskId;
        $t2   = [];
        $temp = app($this->taskManageRepository)->getDetail($taskId);
        if ($temp) {
            $temp = $temp->toArray();
        }
        $t2["calendar_id"]      = $temp['id'];
        $t2["calendar_content"] = $temp['task_name'];
        $t2["calendar_level"]   = $temp['important_level'];
        $t2["create_id"]        = $temp['create_user'];
        $t2["user_id"]          = $temp['manage_user']; //负责人
        $t2["calendar_begin"]   = ($temp["start_date"] && $temp["start_date"] != '0000-00-00') ? $temp['start_date']: $temp['created_at'];
        $t2["calendar_end"]     = $temp['end_date'] == "0000-00-00" ? '' : $temp['end_date'];
        $t2["share_user"]       = $this->getTaskUserById($temp['id'], "shared"); //共享人
        $t2["join_user"]        = $this->getTaskUserById($temp['id'], "join");
        $t2["type"]             = "task";
        $t2['attachment_id']    = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $temp['id']]);
        $t2['calendar_remark']           = $temp['task_description'];

        $username = [];

        $username['create_name'] = app($this->userRepository)->getUserName($t2['create_id']);
        $username['user_name']   = "";
        foreach (explode(',', $t2['user_id']) as $namestr) {
            if (!empty($namestr)) {
                $username['user_name'] .= app($this->userRepository)->getUserName($namestr) . ",";
            }
        }
        $username['share_name'] = "";
        foreach (explode(',', $t2["share_user"]) as $namestr) {
            if (!empty($namestr)) {
                $username['share_name'] .= app($this->userRepository)->getUserName($namestr) . ",";
            }
        }
        if (isset($t2['join_user'])) {
            $username['join_name'] = "";
            foreach (explode(',', $t2["join_user"]) as $namestr) {
                if (!empty($namestr)) {
                    $username['join_name'] .= app($this->userRepository)->getUserName($namestr) . ",";
                }
            }
        }
        $t2['username'] = $username;
        return $t2;
    }

    public function getTaskReminds($taskId)
    {
        if (!$taskId) {
            return ["code" => ['0x046046', 'task']];
        };

        $dataInfo = app($this->taskManageRepository)->getDetail($taskId);
        if (!$dataInfo) {
            return ["code" => ['0x046038', 'task']];
        }
        $remindByStart     = "";
        $remindStartFlag   = false;
        $remindByEnd       = "";
        $remindEndFlag     = false;
        $remindCustomByEnd = "";
        $remindCustomFlag  = false;
        if (!$dataInfo["start_date"] || $dataInfo["start_date"] == "0000-00-00") {
            $remindByStart   = " ( " . trans("task.not_set") . " ) ";
            $remindStartFlag = true;
        }

        if (!$dataInfo["end_date"] || $dataInfo["end_date"] == "0000-00-00") {
            $remindByEnd       = " ( " . trans("task.not_set") . " ) ";
            $remindEndFlag     = true;
            $remindCustomByEnd = " ( " . trans("task.please_set_up_the_task_deadline_first") . " ) ";
            $remindCustomFlag  = true;
        }

        $result = [
            ["remind_id" => 1, "remind_name" => trans("task.not_reminding"), "remind_flag" => false],
            ["remind_id" => 2, "remind_name" => trans("task.custom_reminding"), "remind_flag" => false],
            ["remind_id" => 3, "remind_name" => trans("task.task_deadline_reminding") . $remindByEnd, "remind_flag" => $remindEndFlag],
            ["remind_id" => 4, "remind_name" => trans("task.reminding_at_the_beginning_of_the_task") . $remindByStart, "remind_flag" => $remindStartFlag],
            ["remind_id" => 5, "remind_name" => trans("task.early_warning") . $remindCustomByEnd, "remind_flag" => $remindCustomFlag],
            ["remind_id" => 6, "remind_name" => trans("task.reminding") . $remindCustomByEnd, "remind_flag" => $remindCustomFlag],
        ];

        return [
            "total" => 6,
            "list"  => $result,
        ];
    }

    public function remindSet($taskId, $data = [])
    {
        $where = [
            "task_id" => [$taskId],
        ];
        $row      = app($this->taskRemindsRepository)->getTasksByWhere($where);
        $tempData = array_intersect_key($data, array_flip(app($this->taskRemindsRepository)->getTableColumns()));

        if ($row) {
            //update
            $result = app($this->taskRemindsRepository)->updateData($tempData, ["task_id" => $taskId]);
        } else {
            //insert
            $result = app($this->taskRemindsRepository)->insertData($tempData);
        }

        return $result;
    }

    public function getRemindSet($taskId, $data = [])
    {

        $where = [
            "task_id" => [$taskId],
        ];
        return app($this->taskRemindsRepository)->getTasksByWhere($where);
    }

    //获取任务
    //任务提醒   //    remind_type  1 不提醒 2 自定义提醒 3 截止提醒 4 开始提醒 5 一次提醒 提前X天X时 6 重复提醒 提前X天每隔X天
    public function getTaskRemindByDate()
    {
        //查询 设置任务提醒的任务 获取 remind_type = 2 3 4
        $date    = date("Y-m-d", time());
        $temp    = app($this->taskRemindsRepository)->getTaskRemindByDate($date);
        $message = [];
        foreach ($temp as $task) {
            $toUser   = array_column(app($this->taskUserRepository)->getTaskRelationUser($task->id)->toArray(), 'user_id');
            $toUser[] = $task->manage_user;
            if ($task->end_date && $task->end_date != "0000-00-00") {
                $endDate = $task->end_date;
            } else {
                $endDate = trans("task.not_set");
            }
            $message[] = [
                'remindMark'   => 'task-handle',
                'toUser'       => $toUser,
                'contentParam' => ['taskName' => $task->task_name, 'taskEndDate' => $endDate],
                'stateParams'  => ['taskId' => $task->id],
            ];
        }

        return $message;
    }

    public function getTaskRemindByPlan()
    {
        $taskReminds = app($this->taskRemindsRepository)->getInfo(["remind_type" => [5]]);
        //查询 设置任务提醒的任务 获取 remind_type = 5
        $currentDate = date("Y-m-d", time());
        $ids         = [];
        $days        = [];
        foreach ($taskReminds as $task) {
            $id        = $task->task_id;
            $ids[]     = $id;
            $days[$id] = $task["remind_day"];
        }

        $where = [
            "id"          => [$ids, "in"],
            "task_status" => [0],
        ];
        $temp    = app($this->taskManageRepository)->getTasksByWhere($where);
        $message = [];
        foreach ($temp as $task) {
            $id      = $task["id"];
            $name    = $task["task_name"];
            $endDate = $task["end_date"];

            $diff = $this->diffBetweenTwoDays($endDate, $currentDate);

            if (isset($days[$id]) && $diff == $days[$id]) {
                $toUser   = array_column(app($this->taskUserRepository)->getTaskRelationUser($id)->toArray(), 'user_id');
                $toUser[] = $task["manage_user"];
                if ($task["end_date"] && $task["end_date"] != "0000-00-00") {
                    $endDate = $task["end_date"];
                } else {
                    $endDate = trans("task.not_set");
                }
                $message[] = [
                    'remindMark'   => 'task-handle',
                    'toUser'       => $toUser,
                    'contentParam' => ['taskName' => $name, 'taskEndDate' => $endDate],
                    'stateParams'  => ['taskId' => $id],
                ];
            }
        }
        return $message;
    }

    public function getTaskRepeatRemindByPlan()
    {
        $taskReminds = app($this->taskRemindsRepository)->getInfo(["remind_type" => [6]]);
        //查询 设置任务提醒的任务 获取 remind_type = 6
        $ids     = [];
        $days    = [];
        $isolate = [];
        foreach ($taskReminds as $task) {
            $id           = $task->task_id;
            $ids[]        = $id;
            $days[$id]    = $task["remind_day"];
            $isolate[$id] = $task["remind_hour"];
        }

        $where = [
            "id"          => [$ids, "in"],
            "task_status" => [0],
        ];
        $temp    = app($this->taskManageRepository)->getTasksByWhere($where);
        $message = [];
        foreach ($temp as $task) {
            $id      = $task["id"];
            $name    = $task["task_name"];
            $endDate = $task["end_date"];

            if (isset($days[$id]) && $days[$id]) {

                $limit = isset($isolate[$id]) && $isolate[$id] ? $isolate[$id] : 0;
                $check = $this->checkRepeatRemindByPlan($days[$id], $limit, $endDate);
                if ($check == 1) {
                    $toUser   = array_column(app($this->taskUserRepository)->getTaskRelationUser($id)->toArray(), 'user_id');
                    $toUser[] = $task["manage_user"];
                    if ($task["end_date"] && $task["end_date"] != "0000-00-00") {
                        $endDate = $task["end_date"];
                    } else {
                        $endDate = trans("task.not_set");
                    }
                    $message[] = [
                        'remindMark'   => 'task-handle',
                        'toUser'       => $toUser,
                        'contentParam' => ['taskName' => $name, 'taskEndDate' => $endDate],
                        'stateParams'  => ['taskId' => $id],
                    ];
                }
            } else {
                continue;
            }
        }
        return $message;
    }

    private function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);
        return ($second1 - $second2) / 86400;
    }

    //重复发送 验证
    private function checkRepeatRemindByPlan($day, $limit, $endDate)
    {
        //30天 每隔5天
        if ($limit == 0) {
            return 1;
        }
        $currentDate = date("Y-m-d", time());
        $temp        = $this->diffBetweenTwoDays($endDate, $currentDate); //

        if ($temp == $day) {
            return 1;
        } else if (($temp < $day)) {
            $currentDay = date("d", strtotime($currentDate));
            $daylimit   = $day % $limit;
            if ($daylimit == 0) {
                //推送
                if ($currentDay % $limit == 0) {
                    //推送
                    return 1;
                }
            } else {
                if ($currentDay % $limit == 0) {
                    return 1;
                } else {
                    $end = date("d", strtotime($endDate));
                    $max = $end - $daylimit;
                    if ($currentDay > $max && $currentDay == $end) {
                        return 1;
                    }
                }
            }
        }

        return 0;
    }

    public function taskMineClass($userId, $params = [])
    {
        $classParams = [
            'search'   => ['user_id' => [$userId]],
            'order_by' => ['sort_id' => 'asc'],
            'fields'   => ["id as class_id", "class_name"],
        ];
        $params = $this->parseParams($params);
        $classList = app($this->taskClassRepository)->getTaskClassList($classParams)->toArray();
        $result[0]  = [
            "class_id"         => 0,
            "class_name"       => trans("task.unassigned"),
            "isUnappropriated" => true,
            "list_count"       => 0,
            "taskArray"        => [],
        ];
        foreach ($classList as $class) {
            $class["isUnappropriated"] = false;
            $class["list_count"]       = 0;
            $class["taskArray"]        = [];
            $result[$class['class_id']]                  = $class;
        }

        // 筛选过滤分类
        $filterClassId = Arr::get($params, 'search.class_id.0');
        if ($filterClassId) {
            $filterClassId = is_array($filterClassId) ? $filterClassId : [$filterClassId];
            $result = array_intersect_key($result, array_flip($filterClassId));
        }
        // 分类名称过滤
        $filterClassName = Arr::get($params, 'search.class_name', []);
        if (Arr::get($filterClassName, 1) === 'like') {
            foreach ($result as $key => $class) {
                if (strpos($class['class_name'], $filterClassName[0]) === false) {
                    unset($result[$key]);
                }
            }
        }

        return array_values($result);
    }

    public function taskMineClassTaskList($userId, $classId, $params)
    {
        //查找任务类别下的任务组
        if ($classId == 0) {
            $allTaskIds = array_column(app($this->taskUserRepository)->getTaskIds($userId)->toArray(), 'task_id');

            $classIds = array_column(app($this->taskClassRepository)->getTaskClassByWhere(['user_id' => [$userId]], ['id']), 'id');

            $otherTasks = app($this->taskClassRelationRepository)->getTaskIdsByClassId($classIds);

            $othertaskIds = array_column($otherTasks->toArray(), 'task_id');

            $taskIds  = array_diff($allTaskIds, $othertaskIds);
            $manageId = array_column(app($this->taskManageRepository)->getManageTaskId($userId)->toArray(), 'id');
            $manageId = array_diff($manageId, $othertaskIds);
            $taskIds  = array_merge($taskIds, $manageId);
        } else {
            $tempTasks = app($this->taskClassRelationRepository)->getTaskIdsByClassId($classId);
            $taskIds   = array_column($tempTasks->toArray(), 'task_id');
        }

        $params['task_ids'] = $taskIds;

        //获取用户所有任务
        $params = $this->parseParams($params);

        //字段查询中的负责人查询
        if (isset($params['search']['manager']) && $params['search']['manager']) {
            $userParams = [
                'fields' => ['user_id'],
                'search' => [
                    'user_name' => $params['search']['manager'],
                ],
            ];
            unset($params['search']['manager']);

            $userIds = array_column(app($this->userRepository)->getUserList($userParams), 'user_id');

            $params['search']['manage_user'] = [$userIds, 'in'];
        }

        $taskListData = app($this->taskManageRepository)->getMyTaskList($params);

        //判断任务是否有附件
        $hasAttachmentTask = app($this->attachmentService)->getEntityIdsFromAttachRelTable('task_manage');
        // $hasAttachmentTask = [];
        // if (count($allAttachment) > 0) {
        //     $hasAttachmentTask = array_column($allAttachment->toArray(), 'entity_id');
        // }
        $taskArray = [];

        foreach ($taskListData as $key => $task) {
            //完成日期
            $task['completeDate'] = $task['complete_date'] == '0000-00-00 00:00:00' ? '' : date('Y-m-d', strtotime($task['complete_date']));

            //是否有附件
            $task['hasAttachment'] = (!empty($hasAttachmentTask) && in_array($task['id'], $hasAttachmentTask)) ? true : false;
            //是否关注
            $task['isFollow'] = false;
            if (!empty($task['taskUser'])) {
                if (in_array($userId, array_column($task['taskUser']->toArray(), 'user_id'))) {
                    $taskListData[$key]['isFollow'] = true;
                }
            }

            $taskArray[] = $task;
        }

        return $taskArray;
    }

    // 系统数据选择器接口，获取我的任务
    public function getMyTask($input)
    {
        $search = Arr::get($input, 'search', '');
        $search = json_decode($search, true);
        $type = Arr::get($search, 'type');
        unset($search['type']);
        $input['search'] = $search;
        $params = $input;

        $type = is_array($type) ? array_shift($type) : $type;
        $userId = own('user_id');
        $multiSearchUser = [];
        if ($type == 'manage') {
            $multiSearchUser['manage_user'] = [$userId];
        } else if (in_array($type, ['join', 'shared', 'follow'])) {
            $multiSearchUser = [
                'relationUser' => [$userId],
                'relationType' => [$type]
            ];
        } else {
            $multiSearchUser = [
                'manage_user' => [$userId],
                'multiSearch' => [
                    'relationUser' => [$userId],
                    'relationType' => [['join', 'shared', 'follow'], 'in']],
                    '__relation__' => 'or'
                ];
        }
        $params['search']['multiSearch'] = $multiSearchUser;
        $params['fields'] = ['id', 'task_name'];
        $taskListData = app($this->taskManageRepository)->getMyTaskList($params, false);
        return [
            'total' => app($this->taskManageRepository)->getMyTaskListCount($params),
            'list' => $taskListData
        ];
    }

    // 根据用户获取存在动态的任务列表（为微博写的接口）
    public function dailyLogByUserId($userId, $day) {
        $logs = app($this->taskLogRepository)->dailyLogByUserId($userId, $day);
        $taskLog = $logs->groupBy('task_id')->toArray();
        $logTypeKeysOri = [
            '0x046004' => '将任务标记为已完成',
            '0x046005' => '将任务标记为未完成',
            '0x046039' => '将任务进度更新为',
            '0x046016' => '修改到期日为',
            '0x046010' => '更改负责人为',
            '0x046008' => '创建了子任务',
            '0x046009' => '创建了任务',
        ];
        $langKeys = ['en', 'zh-CN']; // 支持搜锁的语言
        // 根据任务id分组后，遍历日志，筛选出需要的日志
        foreach ($taskLog as $taskId => &$logs) {
            $logTypeKeys = $logTypeKeysOri;
            foreach ($logs as $logKey => $log) {
                $logContent = Arr::get($log, 'log_content');
                $flag = false; // 标记，有没有在多种类型、语言中搜索到，没搜索到就删除
                foreach ($logTypeKeys as $typeKey => $logTypeKey) {
                    foreach ($langKeys as $key => $langKey) {
                        $flag = strpos($logContent, trans('task.' . $typeKey, [], $langKey)); // 查询结果
                        if ( $flag !== false) {
                            break;
                        }
                    }
                    if ($flag !== false) {
                        break;
                    }
                }
                if ($flag === false) {
                    unset($logs[$logKey]);
                } else {
                    $logs[$logKey] = [
                        'log_content' => $log['log_content'],
                        'created_at' => $log['created_at'],
                    ];
                }
            }
            if (!$logs) {
                unset($taskLog[$taskId]);
            }
        }
        if ($taskLog) {
            $taskIds = array_keys($taskLog);
            $tasks = app($this->taskManageRepository)->entity
                ->whereIn('id', $taskIds)->withTrashed()->get()->keyBy('id')->toArray();
            foreach ($taskLog as $taskId => $item) {
                if (isset($tasks[$taskId])) {
                    $res = $tasks[$taskId];
                    $res['logs'] = $item;
                    $taskLog[$taskId] = $res;
                } else {
                    unset($taskLog[$taskId]);
                }
            }
        }

        return ['list' => array_values($taskLog), 'total' => count($taskLog)];
    }

    public function getDeletedTask($input)
    {
        $input = $this->parseParams($input);
        $count = app($this->taskManageRepository)->getDeletedTaskCount($input);
        $taskList = app($this->taskManageRepository)->getDeletedTaskList($input);
        return ['list' => $taskList, 'total' => $count];
    }

    // 获取任务，若是子任务会恢复父任务
    public function recovery($input) {
        $ids = Arr::get($input, 'ids');
        $ids = is_array($ids) ? $ids : [$ids];
        $parentIds = app($this->taskManageRepository)->entity->whereIn('id', $ids)->withTrashed()
            ->pluck('parent_id')->unique()->filter()->toArray();
        $recoverIds = array_merge($ids, $parentIds);
        $res = app($this->taskManageRepository)->restoreSoftDelete(['id' => [$recoverIds, 'in']]);
        // 同步日程
        $this->MultiRecoveryTaskToCalendar($recoverIds, own('user_id'));
        //记录操作日志
        $logContent = trans('task.restore_task');
        $this->createTaskLog($recoverIds, own('user_id'), $logContent);

        // 更新父任务的进度
        foreach ($parentIds as $parentId) {
            $this->modifyParentTaskProgress($parentId);
        }

        return [
            'data' => $res,
            'message' => trans('common.restore_success')
        ];
    }

    public function forceDelete($input)
    {
        $ids = Arr::get($input, 'ids');
        $ids = is_array($ids) ? $ids : [$ids];
        $res = app($this->taskManageRepository)->entity
            ->onlyTrashed()
            ->where(function ($query) use ($ids) {
                $query->whereIn('id', $ids)
                    ->orWhereIn('parent_id', $ids);
            })
            ->update(['force_delete' => 1]);
        return [
            'data' => $res,
            'message' => trans('common.delete_success')
        ];
    }

    public function flowOutSendToTask($data)
    {
        $formatRes = $this->formatFlowOutSendData($data);
        if ($formatRes !== true) {
            return ['code' => $formatRes];
        }
        $newTask = $this->createTask($data, $data['create_user']);
        if (isset($newTask['code'])) {
            return $newTask;
        }
        $newTaskId = $newTask->getKey();
        // 处理子任务外发
        $dynamic = '';
        if (Arr::has($data, 'additional')) {
            $additional = $data['additional'];
            // 如果只有一个明细，且都无数据，就默认用户不外发明细内容跳过（流程那边，至少会外发一个空的过来）
            if (count($additional) == 1 && !array_filter(Arr::first($additional))) {
                return $this->handFlowOutSendResult($newTaskId, TaskManageEntity::class, $dynamic);
            }

            if ($newTask->parent_id) {
                $dynamic = trans('task.had_parent_task');
            } else {
                $errorInfo = [];
                foreach ($additional as $key => $subTaskData) {
                    $subTaskFormatRes = $this->formatFlowOutSendData($subTaskData);
                    if ($subTaskFormatRes !== true) {
                        $errorInfo[$key + 1] = $subTaskFormatRes;
                    } else {
                        $subTaskData['parent_id'] = $newTask->id;
                        $tempRes = $this->createTask($subTaskData, $subTaskData['create_user'], true);
                        if (isset($tempRes['code'])) {
                            $tempLang = trans(trans(implode('.', array_reverse($tempRes['code']))));
                            $errorInfo[$key + 1] = $tempLang;
                        }
                    }
                }
                if ($errorInfo) {
                    $dynamic = flow_out_extra_msg('task.subtask', $errorInfo);
                }
            }
        }

        return $this->handFlowOutSendResult($newTaskId, TaskManageEntity::class, $dynamic);
    }

    public function flowOutSendToUpdateTask($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'data.current_user_id');

        $data = Arr::get($data, 'data');
        // 必填验证，不存在的值不验证
        $taskName = Arr::get($data, 'task_name', true);
        $startDate = Arr::get($data, 'start_date', true);
        $manageUser = Arr::get($data, 'manage_user', true);
        $test = $this->buildFlowOutTaskMsg($taskName, true, $startDate, $manageUser);
        if ($test !== true) {
            return ['code' => $test];
        }

        // 更新函数中，用的附件key与添加不一致，因此替换一下;且需要数组形式
        if (array_key_exists('attachment_ids', $data)) {
            $data['attachments'] = is_array($data['attachment_ids']) ? $data['attachment_ids'] : explode(',', $data['attachment_ids']);
            unset($data['attachment_ids']);
        }

        $res = $this->modifyFlowOutTask($id, $data, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, TaskManageEntity::class);
    }

    public function flowOutSendToDeleteTask($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'data.current_user_id');

        $res = $this->deleteTask($id, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, TaskManageEntity::class);
    }

    public function getCustomTaskClass($id, $own)
    {
        $taskClasses = $this->taskMineClass($own['user_id']);
        $data = empty($id) ? $taskClasses : collect($taskClasses)->whereIn('class_id', $id)->toArray();
        return $data;
    }

    // 返回true或错误msg
    private function formatFlowOutSendData(&$data)
    {
        $taskName = Arr::get($data, 'task_name');
        $taskCreateUser = Arr::get($data, 'create_user');
        $startDate = Arr::get($data, 'start_date');
        $test = $this->buildFlowOutTaskMsg($taskName, $taskCreateUser, $startDate);
        if ($test !== true) {
            return $test;
        }
        if (Arr::get($data, 'joiner')) {
            $joiner = explode(',', $data['joiner']);
            $data['joiner'] = $joiner;
        }
        if (Arr::get($data, 'shared')) {
            $joiner = explode(',', $data['shared']);
            $data['shared'] = $joiner;
        }
        return true;
    }

    // 外发项目，明细外发任务时，组装失败原因
    private function buildFlowOutTaskMsg($taskName, $taskCreateUser, $startDate, $manageUser = true) {
        $msg = trans('common.0x000001');
        $empty = [];
        if (!$taskName) { $empty[] = trans('outsend.task.fileds.task_name.field_name'); }
        if (!$taskCreateUser) { $empty[] = trans('outsend.task.fileds.create_user.field_name'); }
        if (!$startDate) { $empty[] = trans('outsend.task.fileds.start_date.field_name'); }
        if (!$manageUser) { $empty[] = trans('outsend.task.fileds.manage_user.field_name'); }
        if ($empty) {
            return $msg . ': ' . implode('、', $empty);
        } else {
            return true;
        }
    }

    // 获取任务的配置
    public static function taskFields($hasParentId = true, $isRequired = 1) {
        $fields = [
            'task_name' => [
                'field_name' => '任务名称',
                'required' => $isRequired,
            ],
            'create_user' => [
                'field_name' => '创建人',
                'describe' => '创建人id',
                'required' => $isRequired,
            ],
            'manage_user' => [
                'field_name' => '负责人',
                'describe' => '负责人id，默认为创建人'
            ],
            'joiner' => [
                'field_name' => '参与人',
                'describe' => '参与人id数组'
            ],
            'shared' => [
                'field_name' => '共享人',
                'describe' => '共享人id数组'
            ],
            'task_description' => [
                'field_name' => '任务详情',
                'describe' => '负责人id，默认为创建人'
            ],
            'start_date' => [
                'field_name' => '开始日期',
                'required' => $isRequired,
            ],
            'end_date' => [
                'field_name' => '到期日',
            ],
            'important_level' => [
                'field_name' => '重要程度',
                'describe' => '值为0为草稿状态，值为1为发布状态，值为2为审核状态'
            ],
            'attachment_ids' => [
                'field_name' => '附件',
            ]
        ];
        if ($hasParentId) {
            $fields['parent_id'] = [
                //流程外发处的可选字段的字段名称
                'field_name' => '主任务',
                //流程外发处的可选字段的取值描述
                'describe' => '系统中存在的主任务id，必须为整数'
            ];
        }
        return $fields;
    }

    // 更新外发的任务，其他地方不适用
    private function modifyFlowOutTask($taskId, $taskInfo, $userId)
    {
        $oldTaskData = app($this->taskManageRepository)->getDetail($taskId);
        if (!$oldTaskData) {
            return array('code' => array('0x046006', 'task'));
        }
        $hasPersonModify = false;
        if (Arr::has($taskInfo, 'manage_user') || Arr::has($taskInfo, 'shared') || Arr::has($taskInfo, 'joiner')) {
            $hasPersonModify = true;
            if (!$this->hasManagePermission($userId, $oldTaskData)) {
                return ['code' => ['can_not_modify_persons', 'task']];
            }
        }
        //参与人权限
        $taskArray = $this->getPowerTaskArray($userId, 'joiner');

        //如果没有参与人权限,则没有编辑权限
        if (!in_array($taskId, $taskArray)) {
            return array('code' => array('0x000017', 'common'));
        }

        $updateInfo = array_merge($oldTaskData->toArray(), $taskInfo); // 保证历史数据完整性
        $updateRes = $this->modifyTask($taskId, $updateInfo, $userId);

        if ($updateRes === true && $hasPersonModify) {
            //编辑参与人
            if (Arr::has($taskInfo, 'joiner')) {
                $join = explode(',', $taskInfo['joiner']);
                $createJoinerData = [
                    'task_id'  => $taskId,
                    'user_ids' => $join,
                ];
                $this->createJoiner($createJoinerData, $userId);
            }

            //编辑共享人
            if (Arr::has($taskInfo, 'shared')) {
                $shared = explode(',', $taskInfo['shared']);
                $createSharedData = [
                    'task_id'  => $taskId,
                    'user_ids' => $shared,
                ];
                $this->createShared($createSharedData, $userId);
            }

            //编辑负责人
            if (Arr::has($taskInfo, 'manage_user') && $taskInfo['manage_user'] != $oldTaskData['manage_user']) {
                $userName = app($this->userRepository)->getUserName($taskInfo['manage_user']);
                $this->modifyTaskManager($taskId, ['user_id' => $taskInfo['manage_user'], 'user_name' => $userName], $userId);
            }
        }
        return $updateRes;
    }

    // 是否拥有负责人权限
    private function hasManagePermission($curUserId, $task)
    {
        $manageUser = $task['manage_user'] ?? '';
        return $manageUser === $curUserId;
    }

    // 发送反馈评论提醒
    private function sendCommentReminder($taskId, $curUserId, $parentFeedbackId = null)
    {
        // 获取任务负责人、参阅人；被回复人
        $task = app($this->taskManageRepository)->getDetail($taskId);
        if (!$task) {
            return;
        }
        $manageUser = $task['manage_user'];
        $joiner = app($this->taskUserRepository)->getTaskJoinUserById($taskId);
        $joiner = array_column($joiner, 'user_id');
        $toUser = array_merge([$manageUser], $joiner);
        if ($parentFeedbackId) {
            $feedback = app($this->taskFeedBackRepository)->getDetail($parentFeedbackId);
            $feedback && $toUser[] = $feedback['user_id'];
        }
        $toUser = array_unique($toUser);
        $toUser = array_diff($toUser, [$curUserId]);
        if ($toUser) {
            $message = [
                'remindMark'   => 'task-comment',
                'toUser'       => $toUser,
                'contentParam' => ['taskName' => $task->task_name],
                'stateParams'  => ['taskId' => $taskId],
            ];

            Eoffice::sendMessage($message);
        }
    }
}
