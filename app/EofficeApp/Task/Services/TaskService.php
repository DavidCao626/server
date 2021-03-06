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
     * [createTask ????????????]
     *
     * @author ?????????
     *
     * @param  [array]     $createData [????????????]
     * @param  [string]    $userId     [??????ID]
     * @param  [bool]    $forcePermission     [????????????????????????????????????????????????]
     *
     * @return [object]                 [????????????]
     */
    public function createTask($createData, $userId, $forcePermission = false)
    {
        //?????????????????????????????????
        if (isset($createData['parent_id']) && $createData['parent_id'] != 0) {
            $parentTask = app($this->taskManageRepository)->getDetail($createData['parent_id']);

            if (!$parentTask || $parentTask->parent_id != 0) {
                return array('code' => array('0x046007', 'task'));
            }

            // ??????????????????????????????????????????????????????????????????
            if (!$forcePermission) {
                //?????????????????????????????????????????????ID??????
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

        //???????????????????????????
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
        //??????????????????
        $newTask = app($this->taskManageRepository)->taskInsertBasic($createData);
//        $sortData = [
        //            'task_id' => $newTask->id,
        //            'user_id' => $userId,
        //            'sort_id' => 0,
        //            'parent_id' => $createData['parent_id']
        //        ];
        //
        //???????????????????????????????????????

        $relationData = [
            'task_id'  => $newTask->id,
            'class_id' => isset($createData['class_id']) ? $createData['class_id'] : 0,
        ];

        app($this->taskClassRelationRepository)->insertData($relationData);
        //??????????????????
        $this->createTaskLog($newTask->id, $userId, trans('task.0x046009'));

        //?????????????????????
        if ($createData['parent_id'] != 0) {
            //????????????
            $logContent = trans('task.0x046008') . ' [' . $createData['task_name'] . ']';
            $this->createTaskLog($createData['parent_id'], $userId, $logContent);

            //?????????????????????
            $this->modifyParentTaskProgress($createData['parent_id']);
        }

        // ????????????
        $attachments = Arr::get($createData, 'attachment_ids');
        if ($attachments) {
            if (is_string($attachments)) {
                $attachments = explode(',', $attachments);
            }
            app($this->attachmentService)->attachmentRelation("task_manage", $newTask->id, $attachments);
        }

        //???????????????????????????????????????????????????
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

        //?????????????????????????????????????????????
        if ($taskUser != []) {
            app($this->taskUserRepository)->insertMultipleData($taskUser);
            app($this->taskLogRepository)->insertMultipleData($taskUserLog);
        }


        //????????????
        $createData['joiner'] = isset($createData['joiner']) && is_array($createData['joiner']) ? $createData['joiner'] : [];
        $createData['shared'] = isset($createData['shared']) && is_array($createData['shared']) ? $createData['shared'] : [];
        $managerUser          = $createData['manage_user'];
        $toUser               = array_unique(array_merge([$managerUser], $createData['joiner'], $createData['shared']));
        $this->newTaskRemind($newTask, $toUser);
        $calendarUser = array_unique(array_merge([$managerUser], isset($createData['joiner']) ? $createData['joiner'] : $createData['join_user']));

        // ????????????????????? --??????--
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
        // ????????????????????? --??????--
        return $newTask;
    }

    /**
     * [modifyTask ????????????,???????????????Entity???fillable????????????]
     *
     * @author ?????????
     *
     * @param  [int]        $taskId      [??????ID]
     * @param  [array]      $newTaskData [????????????]
     * @param  [string]     $userId      [??????ID]
     *
     * @return [object]                  [????????????]
     */
    public function modifyTask($taskId, $newTaskData, $userId)
    {
        //?????????????????????????????????
        $oldTaskData = app($this->taskManageRepository)->getDetail($taskId);

        if (!$oldTaskData) {
            return array('code' => array('0x046006', 'task'));
        }

        //?????????????????? ??????????????????/??????????????????
        $taskArray = $this->getPowerTaskArray($userId, 'joiner');

        if (!in_array($taskId, $taskArray)) {
            return array('code' => array('0x000017', 'common'));
        }

        //????????????????????????
        if ($oldTaskData->lock == 1 && $userId != $oldTaskData->manage_user) {
            return array('code' => array('0x046027', 'task'));
        }

        //???????????????????????????
        if ($oldTaskData->parent_id != 0) {
            $parentTask = app($this->taskManageRepository)->getDetail($oldTaskData->parent_id);

            if ($parentTask && $parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                return array('code' => array('0x046041', 'task'));
            }

            //????????????????????????100
            if ($newTaskData['progress'] > 100) {
                return array('code' => array('0x046031', 'task'));
            }

            if ($oldTaskData->task_status == 1 && $newTaskData['progress'] < 100) {
                return array('code' => array('0x046044', 'task'));
            }
        } else {
            //???????????????
            //??????????????????
            if (isset($newTaskData["task_has_many_son_task"]) && count($newTaskData["task_has_many_son_task"]) > 0) {
                unset($newTaskData['progress']);
            }
        }

        //???????????????????????????????????????
        if ($newTaskData['start_date'] && $newTaskData['end_date'] && $newTaskData['end_date'] != "0000-00-00") {
            if ($newTaskData['start_date'] > $newTaskData['end_date']) {
                return array('code' => array('0x046011', 'task'));
            }
        }

        //??????????????????
        $where        = ['id' => $taskId];
        $updateResult = app($this->taskManageRepository)->updateDataBatch($newTaskData, $where);

        //????????????
        $oldAttachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $taskId]);
        if (isset($newTaskData['attachments'])) {
            app($this->attachmentService)->attachmentRelation("task_manage", $taskId, $newTaskData['attachments']);

            if (isset($newTaskData['files'])) {
                //??????????????????
                $fileName   = implode(',', array_column($newTaskData['files'], 'name'));
                $logContent = trans('task.0x046017') . ' ' . $fileName;

                $this->createTaskLog($taskId, $userId, $logContent);
            }

            $deleteAttachments = array_diff($oldAttachments, $newTaskData['attachments']);

            if ($deleteAttachments) {
                $deleteData = app($this->attachmentService)->getAttachments(['attach_ids' => $deleteAttachments]);

                //??????????????????
                $fileName   = implode(',', array_column($deleteData, 'attachment_name'));
                $logContent = trans('task.0x046037') . ' ' . $fileName;

                $this->createTaskLog($taskId, $userId, $logContent);
            }
        }

        //??????????????????
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

                //?????????????????????
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
     * [modifyTaskManager ?????????????????????]
     *
     * @author ?????????
     *
     * @param  [int]               $taskId  [??????ID]
     * @param  [string]            $manager [?????????????????????]
     * @param  [string]            $userId  [?????????ID]
     *
     * @return [bool]                       [????????????]
     */
    public function modifyTaskManager($taskId, $manager, $userId)
    {
        $taskData = app($this->taskManageRepository)->getDetail($taskId);
        if ($userId != $taskData->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskData) {
            //??????????????????
            if ($taskData->lock == 1 && $userId != $taskData->manage_user) {
                return array('code' => array('0x000017', 'common'));
            }

            //???????????????????????????
            if ($taskData->parent_id != 0) {
                $parentTask = app($this->taskManageRepository)->getDetail($taskData->parent_id);

                if ($parentTask && $parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                    return array('code' => array('0x000017', 'common'));
                }
            }

            $where = array('id' => $taskId);
            $data  = array('manage_user' => $manager['user_id']);
            $updateResult = app($this->taskManageRepository)->updateData($data, $where);

            //??????????????????
            $this->newTaskRemind($taskData, [$manager['user_id']]);

            //??????????????????
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
        // ?????????????????????
        $joiner = app($this->taskUserRepository)->getTaskJoinUserById($sourceId);
        $share = app($this->taskUserRepository)->getTaskSharedUserById($sourceId);
        $joinerIds = array_column($joiner, 'user_id');
        $shareIds = array_column($share, 'user_id');
        $calendarUser = array_unique(array_merge([$taskData->manage_user], $joinerIds));
        $attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $sourceId]);
        // ????????????????????? --??????--
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
     * [createJoiner ???????????????]
     *
     * @author ?????????
     *
     * @param  [array]          $createData [????????????]
     * @param  [string]         $userId       [?????????ID]
     *
     * @return [object]                       [????????????]
     */
    public function createJoiner($createData, $userId)
    {
        $taskInfo = $this->getTaskInfo($createData['task_id'], $userId);
        if ($userId != $taskInfo->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskInfo) {
            //??????????????????
            if ($taskInfo->lock == 1 && $userId != $taskInfo->manage_user) {
                return array('code' => array('0x046027', 'task'));
            }

            //???????????????????????????
            if ($taskInfo->parent_id != 0) {
                $parentTask = app($this->taskManageRepository)->getDetail($taskInfo->parent_id);

                if ($parentTask && $parentTask->lock == 1 && $userId != $parentTask->manage_user) {
                    return array('code' => array('0x046041', 'task'));
                }
            }

            //??????????????????  ??????????????????
            if ($taskInfo->manage_user != $userId) {
                return array('code' => array('0x000017', 'common'));
            }

            $deletedUser = array_diff($taskInfo->join_user, $createData['user_ids']);
            $createdUser = array_diff($createData['user_ids'], $taskInfo->join_user);

            //???????????????
            if ($deletedUser) {
                $where = [
                    'task_id'       => [$createData['task_id']],
                    'task_relation' => ['join'],
                    'user_id'       => [$deletedUser, 'in'],
                ];
                $result = app($this->taskUserRepository)->deleteByWhere($where);

                //??????????????????
                $this->createPersonTaskLog($createData['task_id'], $userId, $deletedUser, trans('task.0x046033'));
            }

            //???????????????
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
                //??????????????????
                $this->newTaskRemind($taskInfo, $createdUser);
            }
            $this->emitCalendarUpdate($createData['task_id'], $createData, $userId, 'update');
            return true;
        }

        return array('code' => array('0x046006', 'task'));
    }

    /**
     * [createShared ???????????????]
     *
     * @author ?????????
     *
     * @param  [array]        $createData [????????????]
     * @param  [string]       $userId       [?????????ID]
     *
     * @return [object]                     [????????????]
     */
    public function createShared($createData, $userId)
    {
        $taskInfo = $this->getTaskInfo($createData['task_id'], $userId);
        if ($userId != $taskInfo->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskInfo) {
            //??????????????????
            if ($taskInfo->lock == 1 && $userId != $taskInfo->manage_user) {
                return array('code' => array('0x000017', 'common'));
            }

            //???????????????????????????
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

                //??????????????????
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
                //??????????????????
                $this->newTaskRemind($taskInfo, $createdUser);
            }

            return true;
        }

        return array('code' => array('0x046006', 'task'));
    }

    /**
     * [completeTask ????????????/?????????]
     *
     * @author ?????????
     *
     * @param  [string]       $userId       [??????ID]
     * @param  [array]        $taskId         [?????????????????????]
     *
     * @return [bool]                       [????????????]
     */
    public function completeTask($taskId, $userId)
    {
        $taskInfo = app($this->taskManageRepository)->getTaskInfo($taskId);
        if ($userId != $taskInfo->manage_user) {
            return array('code' => array('0x000017', 'common'));
        }
        if ($taskInfo) {
            //??????????????????
            if ($taskInfo->lock == 1 && $taskInfo->manage_user != $userId) {
                return array('code' => array('0x000017', 'common'));
            }

            //???????????????????????????
            if ($taskInfo->parent_id != 0) {
                $parentTask = app($this->taskManageRepository)->getDetail($taskInfo->parent_id);

                if (!$parentTask) {
                    return array('code' => array('0x046038', 'task'));
                }

                if ($parentTask->lock == 1 && $taskInfo->manage_user != $userId) {
                    return array('code' => array('0x000017', 'common'));
                }
            }

            //????????????
            if ($taskInfo->task_status == 0) {
                //?????????,???????????????????????????,?????????????????????
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

                //????????????
                $this->createTaskLog($taskId, $userId, trans('task.0x046004'));

                //??????????????????
                $this->completeTaskRemind([$taskInfo]);

                //?????????????????????
                $this->modifyParentTaskProgress($taskInfo->parent_id);
                $this->emitCalendarComplete($taskId, $userId, 'complete');
                return 'complete';
                //???????????????
            } else {
                //?????????,??????????????????????????????,????????????????????????
                if ($taskInfo->parent_id != 0) {
                    if ($parentTask->task_status == 1) {
                        return array('code' => array('0x046042', 'task'));
                    }
                }

                $taskInfo->task_status   = 0;
                $taskInfo->complete_date = '';
                $taskInfo->save();

                //????????????
                $this->createTaskLog($taskId, $userId, trans('task.0x046005'));

                //??????????????????
                $this->restartTaskRemind($taskInfo);
                $this->emitCalendarComplete($taskId, $userId, 'delete');
                // ??????????????????
                $this->emitCalendarUpdate($taskId, $taskInfo, $userId, 'add');
                return 'execute';
            }
        }

        return array('code' => array('0x046006', 'task'));
    }

    /**
     * [lockTask ??????/????????????]
     *
     * @author ?????????
     *
     * @param  [int]      $lockId [??????ID]
     * @param  [string]   $userId [??????ID]
     *
     * @return [string]           [????????????]
     */
    public function lockTask($lockId, $userId)
    {
        //?????????????????????????????????????????????ID??????
        $taskArray = $this->getPowerTaskArray($userId, 'manager');

        //????????????
        if (is_array($lockId)) {
            $lockTasks = array_intersect($lockId, $taskArray);

            foreach ($lockTasks as $taskId) {
                $taskInfo = app($this->taskManageRepository)->getDetail($taskId);

                if ($taskInfo && $taskInfo->lock == 0) {
                    $taskInfo->lock = 1;
                    $taskInfo->save();

                    //????????????
                    $this->createTaskLog($taskId, $userId, trans('task.0x046018'));
                }
            }

            return true;
        }

        //??????????????????/??????
        if (!in_array($lockId, $taskArray)) {
            return array('code' => array('0x046001', 'task'));
        }

        $taskInfo = app($this->taskManageRepository)->getDetail($lockId);
        $childIds = [];
        if ($taskInfo) {
            //??????
            if ($taskInfo->parent_id) {
                $taskParentInfo = app($this->taskManageRepository)->getDetail($taskInfo->parent_id);
                if ($taskParentInfo->lock == 1) {
                    //??????????????????
                    return array('code' => array('0x046047', 'task'));
                }
            }
            if ($taskInfo->lock == 0) {
                $taskInfo->lock = 1;
                $taskInfo->save();
                if ($taskInfo->parent_id == 0) {
                    $childIds = $this->batchDoChildTasks("lock", $taskInfo->id);
                }

                //????????????
                $this->createTaskLog($lockId, $userId, trans('task.0x046018'));

                $type = 'lock';
                //??????
            } else {
                $taskInfo->lock = 0;
                $taskInfo->save();
                if ($taskInfo->parent_id == 0) {
                    $childIds = $this->batchDoChildTasks("unlock", $taskInfo->id);
                }
                //????????????
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

    //???????????????????????????
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

    //???????????????
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
     * [followTask ??????/????????????]
     *
     * @author ?????????
     *
     * @param  [integer]     $followData [??????ID,?????????ID??????]
     * @param  [string]      $userId     [??????ID]
     *
     * @return [string]                  [????????????]
     */
    public function followTask($followData, $userId)
    {
        //??????????????????  ??????????????????/?????????/?????????
        //??????????????????????????????????????????ID??????
        //  $taskArray = $this->getPowerTaskArray($userId, 'shared');
        //????????????
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

                    //????????????
                    $this->createTaskLog($taskId, $userId, trans('task.0x046003'));
                }
            }

            return true;
        }

//        //????????????????????????????????????
        //        if (!in_array($followData, $taskArray)) {
        //            return array('code' => array('0x046001', 'task'));
        //        }

        $where = [
            'user_id'       => [$userId],
            'task_id'       => [$followData],
            'task_relation' => ['follow'],
        ];
        $count = app($this->taskUserRepository)->getTaskUserCount($where);

        //????????????
        if ($count) {
            $where = [
                'user_id'       => [$userId],
                'task_id'       => [$followData],
                'task_relation' => ['follow'],
            ];

            $result = app($this->taskUserRepository)->deleteByWhere($where);

            //????????????
            $this->createTaskLog($followData, $userId, trans('task.0x046002'));
            return 'cancel';
        }

        //??????
        $insertData = ['user_id' => $userId, 'task_id' => $followData, 'task_relation' => 'follow'];
        $result     = app($this->taskUserRepository)->insertData($insertData);

        //????????????
        $this->createTaskLog($followData, $userId, trans('task.0x046003'));
        return 'follow';
    }

    /**
     * [getTaskInfo ??????????????????]
     *
     * @author ?????????
     *
     * @param  [int]         $taskId [??????ID]
     * @param  [string]      $userId [?????????ID]
     *
     * @return [object]              [????????????]
     */
    public function getTaskInfo($taskId, $userId, $data = [])
    {
        $withTrashed = false;
        if (isset($data['deletedMode']) && $data['deletedMode']) {
            $withTrashed = true;
        }
        $taskInfo = app($this->taskManageRepository)->getTaskInfo($taskId, $withTrashed);
        if ($taskInfo) {
            //????????????????????????
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

            //????????????
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

            //????????????ID
            $attachments           = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $taskId]);
            $taskInfo->attachments = $attachments;
        } else {
            return ['code' => ['0x000006', 'common']];
        }

        return $taskInfo;
    }

    /**
     * ???????????? ??????????????? ??????????????????
     */
    public function taskAuth($taskId, $userId, $data = [])
    {
        //??????????????????????????????????????????ID??????
        $taskArray = $this->getPowerTaskArray($userId, 'shared');

        //???????????????????????????
        $sonTaskArray = $this->getSonTask($taskArray);

        //???????????????
        $subordinateTask = $this->getSubordinateTask($userId);

        //????????????????????????
        $subordinateSonTask = $this->getSonTask($subordinateTask);

        if (!in_array($taskId, array_merge($taskArray, $sonTaskArray, $subordinateTask, $subordinateSonTask))) {
            return -1; //????????????
        }

        return 1;
    }

    /**
     * ???????????????????????????classID
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
     * [completeTaskRemind ??????????????????]
     *
     * @author ?????????
     *
     * @param  [array]             $taskInfoArray [?????????????????????]
     *
     * @return [bool]                             [????????????]
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
     * [restartTaskRemind ??????????????????]
     *
     * @author ?????????
     *
     * @param  [object]            $taskInfo [?????????????????????]
     *
     * @return [bool]                        [????????????]
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
     * [newTaskRemind ???????????????]
     *
     * @author ?????????
     *
     * @param  [object]        $taskInfo [????????????]
     * @param  [array]         $toUser   [????????????]
     *
     * @return [bool]                    [????????????]
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
     * [pressTask ????????????]
     *
     * @author ?????????
     *
     * @param  [array]     $param    [????????????]
     * @param  [string]    $userId   [??????ID]
     * @param  [string]    $userName [?????????]
     *
     * @return [bool]                [????????????]
     */
    public function pressTask($param, $userId, $userName)
    {
        //??????????????????????????????????????????ID??????
        $taskArray = $this->getPowerTaskArray($userId, 'shared');

        //???????????????????????????
        $sonTaskArray = $this->getSonTask($taskArray);

        //???????????????
        $subordinateTask = $this->getSubordinateTask($userId);

        //????????????????????????
        $subordinateSonTask = $this->getSonTask($subordinateTask);

        //????????????
        //????????????
        if (!$param['pressContent'] || $param['pressContent'] == '') {
            return array('code' => array('0x046022', 'task'));
        }

        //????????????
        if (!isset($param['sendMethod']) || $param['sendMethod'] == []) {
            return array('code' => array('0x046021', 'task'));
        }

        //?????????
        if ((!isset($param['manager']) || $param['manager'] == 0) && (!isset($param['joiner']) || $param['joiner'] == 0) && (!isset($param['otherUser']) || $param['otherUser'] == [])) {
            return array('code' => array('0x046023', 'task'));
        }

        //????????????????????????
        $sendData['remindState'] = 'task.edit';
        $sendData['remindMark']  = 'task-press';
        $sendData['sendMethod']  = $param['sendMethod'];
        $sendData['isHand']      = true;

        //??????????????????,??????????????????
        foreach ($param['taskArray'] as $value) {
            //????????????  ??????????????????/?????????/?????????/????????????
            if (!in_array($value['id'], $taskArray) && !in_array($value['id'], $sonTaskArray) && !in_array($value['id'], $subordinateTask) && !in_array($value['id'], $subordinateSonTask)) {
                continue;
            }

            //????????????
            $sendData['content']      = $param['pressContent'];
            $sendData['contentParam'] = ['task_name' => $value['task_name'], 'userName' => $userName];
            //?????????
            $toUser = [];
            if ($param['otherUser']) {
                if ($param['otherUser'] == 'all') {
                    //???????????????????????????
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

            //??????????????????
            $sendData['stateParams'] = ['taskId' => $value['id']];

            Eoffice::sendMessage($sendData);
        }

        return true;
    }

    /**
     * [deleteTask ????????????]
     *
     * @author ?????????
     *
     * @param  [int]        $taskId [??????ID]
     * @param  [string]     $userId [?????????ID]
     *
     * @return [bool]               [????????????]
     */
    public function deleteTask($taskId, $userId)
    {
        $taskInfo = app($this->taskManageRepository)->getDetail($taskId);

        //?????????????????????????????????true
        if (!$taskInfo) {
            return ['code' => ['no_data', 'common']];
        }

        //????????????  ??????????????????
        if ($taskInfo->manage_user != $userId) {
            return array('code' => array('0x000017', 'common'));
        }

        $deleteResult = app($this->taskManageRepository)->deleteById($taskId);

        //??????????????????
        $deleteTaskIds = [$taskId];
        if ($deleteResult) {
            $this->emitCalendarComplete($taskId, $userId, 'delete');
            if ($taskInfo->parent_id == 0) {
                //?????????????????????
                $subTaskIds = app($this->taskManageRepository)->entity
                    ->where('parent_id', $taskId)->get()->pluck('id')->toArray();
                if ($subTaskIds) {
                    app($this->taskManageRepository)->entity->whereIn('id', $subTaskIds)->delete();
                    $deleteTaskIds = array_merge($deleteTaskIds, $subTaskIds);
                }
            } else {
                //?????????????????????
                $this->modifyParentTaskProgress($taskInfo->parent_id);
            }

            //??????????????????
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
     * [mobileCreateTask ?????????????????????]
     *
     * @method ?????????
     *
     * @param  [array]            $taskInfo [????????????]
     * @param  [string]           $userId   [??????ID]
     *
     * @return [bool]                       [????????????]
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
     * [mobileEditTask ?????????????????????]
     *
     * @method ?????????
     *
     * @param  [integer]        $taskId   [????????????]
     * @param  [array]          $taskInfo [????????????]
     * @param  [string]         $userId   [??????ID]
     *
     * @return [bool]                     [????????????]
     */
    public function mobileEditTask($taskId, $taskInfo, $userId)
    {
        //???????????????
        $taskArray = $this->getPowerTaskArray($userId, 'joiner');

        //???????????????????????????,?????????????????????
        if (!in_array($taskId, $taskArray)) {
            return array('code' => array('0x000017', 'common'));
        }

        $this->modifyTask($taskId, $taskInfo, $userId);

        //???????????????
        $taskArray = $this->getPowerTaskArray($userId, 'manager');

        if (in_array($taskId, $taskArray)) {
            //???????????????
            if ($taskInfo['join_user']) {
                $createJoinerData = [
                    'task_id'  => $taskId,
                    'user_ids' => $taskInfo['join_user'],
                ];
                $this->createJoiner($createJoinerData, $userId);
            }

            //???????????????
            if ($taskInfo['shared_user']) {
                $createSharedData = [
                    'task_id'  => $taskId,
                    'user_ids' => $taskInfo['shared_user'],
                ];
                $this->createShared($createSharedData, $userId);
            }

            //???????????????
            if ($taskInfo['manage_user'] != $userId) {
                $userName = app($this->userRepository)->getUserName($taskInfo['manage_user']);
                $this->modifyTaskManager($taskId, ['user_id' => $taskInfo['manage_user'], 'user_name' => $userName], $userId);
            }
        }

        return true;
    }

    /**
     * [modifyParentTaskProgress ?????????????????????]
     *
     * @method ?????????
     *
     * @param  [int]                    $taskId    [?????????ID]
     *
     * @return [bool]                              [????????????]
     */
    public function modifyParentTaskProgress($taskId)
    {
        //???????????????
        if ($taskId <= 0) {
            return false;
        }
        //????????????????????????????????????
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
     * [getSubordinateTaskList ??????????????????????????????]
     *
     * @author ?????????
     *
     * @param  [array]                  $param  [????????????]
     * @param  [string]                 $userId [??????ID]
     *
     * @return [array]                          [????????????]
     */
    public function getSubordinateTaskList($param, $userId)
    {
        //????????????
        $param = $this->parseParams($param);
        // ??????????????????????????????
        $userSubordinate = app($this->userService)->getSubordinateArrayByUserId($userId, ['all_subordinate' => 1]);

        if (!$userSubordinate) {
            return ['total' => 0, 'list' => ''];
        }
        // ???????????? ???????????? ??????
        $reportUser = [];
        if (isset($param['reportUser'])) {
            $reportUser = json_decode($param['reportUser'], true);
        }
        if (count($reportUser)) {
            $param['reportUser'] = $reportUser;
        } else {
            $param['reportUser'] = ['manager', 'joiner', 'shared'];
        }
        // $param ????????? user_id ??? $param search user_name ????????????????????????????????????????????? ifelse ???--20171023??????bug24069?????????
        // $param['user_id'] ????????????????????????????????????????????????????????????????????????????????????
        if (isset($param['user_id']) && $param['user_id']) {
            // ??????????????????user_id?????????????????????????????????????????????????????????????????? $param['userIds'] ??????
            if (in_array($param['user_id'], $userSubordinate['id'])) {
                $param['userIds'] = [$param['user_id']];
            } else {
                return array('code' => array('0x000006', 'common'));
            }
        } else {
            // ?????? user_id???????????????????????????????????????????????????????????????????????????????????????????????? ????????? ????????????????????????
            // ??????user_id???????????????????????????????????????????????????
            $param['userIds'] = $userSubordinate['id'];
        }
        // $param['user_name'] ?????????????????????????????????????????????grid?????????????????????????????????????????????????????????
        if (isset($param['search']['user_name'])) {
            $userParam = [
                'search' => [
                    'user_name' => $param['search']['user_name'],
                ],
            ];
            //????????????
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
        //????????????????????????ID
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
     * ????????????????????????ID
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
     * [getUserSubordinate ??????????????????,???????????????]
     *
     * @author ?????????
     *
     * @param  [string]             $userId [??????ID]
     *
     * @return [array]                      [????????????]
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
                // ???????????? user_name ??????????????? ???????????????
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
     * [getTaskRelationUser ????????????????????????]
     *
     * @author ?????????
     *
     * @param  [int]                 $taskId       [??????ID]
     * @param  [string]              $relationType [????????????,join??????follow??????shared??????]
     * @param  [string]               $userId       [?????????ID]
     *
     * @return [object]                            [????????????]
     */
    public function getTaskRelationUser($taskId, $relationType, $userId)
    {
        //??????????????????  ??????????????????/?????????/?????????/????????????
        //??????????????????????????????????????????ID??????
        $taskArray = $this->getPowerTaskArray($userId, 'shared');

        //???????????????????????????
        $sonTaskArray = $this->getSonTask($taskArray);

        //???????????????
        $subordinateTask = $this->getSubordinateTask($userId);

        //????????????????????????
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
     * [createTaskFeedback ??????????????????]
     *
     * @author ?????????
     *
     * @param  [array]             $feedbackData [????????????]
     * @param  [string]             $userId       [??????ID]
     *
     * @return [object]                           [????????????]
     */
    public function createTaskFeedback($feedbackData, $userId)
    {
        $feedbackData['user_id'] = $userId;

        if ($result = app($this->taskFeedBackRepository)->insertData($feedbackData)) {
            // ????????????
            if (isset($feedbackData['attachments'])) {
                app($this->attachmentService)->attachmentRelation("task_feedback", $result["id"], $feedbackData['attachments']);
            }
            $this->sendCommentReminder(Arr::get($feedbackData, 'task_id'), $userId, Arr::get($feedbackData, 'parent_id'));
            return $result;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * [modifyTaskFeedback ????????????]
     *
     * @method ?????????
     *
     * @param  [int]                $feedbackId   [??????ID]
     * @param  [array]              $feedbackData [????????????]
     * @param  [string]             $userId       [??????ID]
     *
     * @return [bool]                             [????????????]
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
     * [deleteTaskFeedback ????????????]
     *
     * @method ?????????
     *
     * @param  [int]              $feedbackId [??????ID]
     *
     * @return [bool]                         [????????????]
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
     * [getTaskFeedback ??????????????????]
     *
     * @author ?????????
     *
     * @param  [int]             $taskId [??????ID]
     * @param  [string]          $userId [??????ID]
     *
     * @return [object]                  [????????????]
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
                // ??????????????????
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
     * [getFeedbackInfo ??????????????????????????????]
     *
     * @author ?????????
     *
     * @param  [int]             $feedbackId [??????ID]
     *
     * @return [object]                      [????????????]
     */
    public function getFeedbackInfo($feedbackId)
    {
        return app($this->taskFeedBackRepository)->getFeedbackInfo($feedbackId);
    }

    /**
     * ???????????????
     * [getTaskList ?????????????????????????????????]
     *
     * @author ?????????
     *
     * @param  [string]      $userId [??????ID]
     * @param  [array]       $param  [????????????]
     *
     * @return [object]              [????????????]
     */
    public function getTaskList($param, $userId)
    {
        $param = $this->parseParams($param);

        /**
         * task_class???????????????
         *         mine    ????????????
         *         join     ???????????????
         *         manage     ???????????????
         *         follow     ???????????????
         *         shared     ???????????????
         *         create     ?????????
         *         all     ??????
         *         complete??????
         *         deleted    ??????????????????
         *
         * end_status?????????????????????
         *         delay     ????????????
         *         today     ????????????
         *         tomorrow????????????
         *         will     ????????????
         *         noTime     ????????????
         *         all     ??????
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

        //??????????????????
        if (isset($param['search']['task_status']) && $param['search']['task_status'] == 'all') {
            unset($param['search']['task_status']);
        }

        //?????????????????????????????????
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
                //??????????????????
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
        // ??????????????????id
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
     * ????????????????????????ID
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
     * [getUserRelationTask ????????????????????????]
     *
     * @author ?????????
     *
     * @param  [string]              $userId       [??????ID]
     * @param  [string]              $relationType [????????????]
     *
     * @return [object]                            [????????????]
     */
    public function getUserRelationTask($userId, $relationType)
    {
        switch ($relationType) {
            case 'create': //?????????
                $param = [
                    'fields' => ['id'],
                    'search' => [
                        'create_user' => [$userId],
                    ],
                ];

                $taskIds = app($this->taskManageRepository)->getTaskList($param);
                break;
            case 'manage': //?????????
                $param = [
                    'fields' => ['id'],
                    'search' => [
                        'manage_user' => [$userId],
                    ],
                ];

                $taskIds = app($this->taskManageRepository)->getTaskList($param);
                break;
            case 'join': //?????????
                $param = [
                    'fields' => ['task_id'],
                    'search' => [
                        'user_id'       => [$userId],
                        'task_relation' => ['join'],
                    ],
                ];

                $taskIds = app($this->taskUserRepository)->getTaskUserList($param);
                break;
            case 'shared': //????????????
                $param = [
                    'fields' => ['task_id'],
                    'search' => [
                        'user_id'       => [$userId],
                        'task_relation' => ['shared'],
                    ],
                ];

                $taskIds = app($this->taskUserRepository)->getTaskUserList($param);
                break;
            case 'follow': //?????????
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
     * [getTaskLog ??????????????????]
     *
     * @author ?????????
     *
     * @param  [int]        $taskId [??????ID]
     * @param  [array]      $param [????????????]
     * @param  [string]     $userId [??????ID]
     *
     * @return [object]             [????????????]
     */
    public function getTaskLog($taskId, $param, $userId)
    {
        $param = $this->parseParams($param);

        $param['search'] = isset($param['search']) ? $param['search'] : [];

        $param['search']['task_id'] = [$taskId];

        return app($this->taskLogRepository)->getLogList($param);
    }

    /**
     * [getTaskReport ????????????????????????]
     *
     * @author ?????????
     *
     * @param  [array]        $params [????????????]
     *
     * @return [array]               [????????????]
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
        // ????????????--20180308-dp
        $params["include_leave"] = "1";
        Arr::set($params['order_by'], 'list_number', 'asc');//???????????????????????????
        Arr::set($params['order_by'], 'user_name', 'asc');//???????????????????????????
        //????????????
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
            // null???????????????????????????????????????????????????????????????
            $userTaskId    = empty($taskRelation) ? null : array_column(app($this->taskUserRepository)->getTasksByWhere(['user_id' => [$user['user_id']], 'task_relation' => [$taskRelation, 'in']], 'task_id'), 'task_id');
            // ?????????
            $user['all'] = app($this->taskManageRepository)->getTaskCount($taskSearch, $managerUserId, $userTaskId);
            // ?????????
//            $user['execute'] = app($this->taskManageRepository)->getTaskCount($executeSearch, $managerUserId, $userTaskId);
            $completeInfo = app($this->taskManageRepository)->getUserCompleteTaskAgeGrade($managerUserId, $userTaskId, $executeSearch);
            // ?????????
            $user['delay'] = app($this->taskManageRepository)->getTaskCount([], $managerUserId, $userTaskId, 'delay', $taskSearch);
            // ?????????
            $user['complete'] = $completeInfo['count'];
            $user['avg_task_grade'] = round($completeInfo['avg_task_grade'], 2);
            $user['execute'] = $user['all'] - $user['complete'];
            // ?????????
            $user['completeRate'] = $user['all'] == 0 ? '100%' : sprintf('%.3f', $user['complete'] / $user['all']) * 100 . '%';
            $lists[]              = $user;
        }

        unset($params['order_by']);

        $total = app($this->userRepository)->getUserListTotal($params);

        return ['total' => $total, 'list' => $lists];
    }
    /**
     * [getUserTaskReport ????????????????????????]
     *
     * @author ?????????
     *
     * @param  [array]            $params [????????????]
     *
     * @return [array]                    [????????????]
     */
    public function getUserTaskReport($params)
    {
        //?????????????????????ID??????????????????
        $userTask = [];
        foreach ($params['userIds'] as $userId) {
            $userTask[$userId] = [
                'all'      => [],
                'delay'    => [],
                'execute'  => [],
                'complete' => [],
            ];
        }

        //????????????
        $params['page'] = 0;
        $today          = date('Y-m-d');

        //????????????
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

                //????????????
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
                            //?????????????????????????????????????????????????????????,???????????????????????????,?????????????????????
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
                            //?????????????????????????????????????????????????????????,???????????????????????????,?????????????????????
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
     * [getOneUserTask ??????????????????????????????????????????]
     *
     * @author ?????????
     *
     * @param  [array]         $param [????????????]
     *
     * @return [array]                [????????????]
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
     * [getUserTaskTrend ????????????????????????????????????]
     *
     * @author ?????????
     *
     * @param  [array]            $param  [????????????]
     * @param  [string]           $userId [??????ID]
     *
     * @return [array]                   [????????????]
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
     * [getDates ???????????????????????????????????????]
     *
     * @author ?????????
     *
     * @param  [date]    $startdate [????????????]
     * @param  [date]    $endDate   [????????????]
     *
     * @since  2015-11-19 ??????
     *
     * @return [array]              [????????????]
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
     * [exportTaskReport ??????????????????]
     *
     * @author ?????????
     *
     * @param  [array]           $param [????????????]
     *
     * @return [array]                  [????????????]
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
     * [exportTaskReport ??????????????????]
     *
     * @author ?????????
     *
     * @param  [array]           $param [????????????]
     *
     * @return [array]                  [????????????]
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
     * ????????????API
     *
     * ????????????:
     *         ???????????????????????????????????????;
     *         ?????????????????????????????????????????????,????????????,?????????;
     *
     * ??????API???????????????,????????????,?????????????????????
     */

    /**
     * [createTaskClass ??????????????????]
     *
     * @method ?????????
     *
     * @param  [array]           $classData [????????????]
     * @param  [string]          $userId    [??????ID]
     *
     * @return [bool]                       [????????????]
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
     * [deleteTaskClass ??????????????????]
     *
     * @method ?????????
     *
     * @param  [int]             $classId [??????ID]
     * @param  [string]          $userId  [??????ID]
     *
     * @return [bool]                     [????????????]
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

        // ?????????????????????????????????
        $deleteWhere = [
            'class_id' => [$classId],
        ];

        app($this->taskClassRelationRepository)->deleteByWhere($deleteWhere);

        return true;
    }

    /**
     * [modifyTaskClass ??????????????????]
     *
     * @method ?????????
     *
     * @param  [int]            $classId   [??????ID]
     * @param  [array]          $classData [????????????]
     *
     * @return [bool]                      [????????????]
     */
    public function modifyTaskClass($classId, $classData)
    {
        $where = [
            'id' => [$classId],
        ];

        return app($this->taskClassRepository)->updateDataBatch($classData, $where);
    }

    /**
     * [getMyTaskList ????????????????????????]
     *
     * @method ?????????
     *
     * @param  [array]         $params [????????????]
     * @param  [string]        $userId [??????ID]
     *
     * @return [array]                 [????????????]
     */
    public function getMyTaskList($params, $userId)
    {

        //????????????????????????????????????
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

        //?????????????????????????????????
        $relationWhere = ['class_id' => [$classIdArray, 'in']];

        $relationData = app($this->taskClassRelationRepository)->getRelationData($relationWhere)->toArray();

        $taskRelationClass = [];
        foreach ($relationData as $value) {
            $taskRelationClass[$value['task_id']] = $value['class_id'];
        }

        //????????????????????????
        $params = $this->parseParams($params);

        //?????????????????????????????????
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

        //???????????????????????????
        $hasAttachmentTask = app($this->attachmentService)->getEntityIdsFromAttachRelTable('task_manage');

        // $hasAttachmentTask = [];
        // foreach ($allAttachment as $value) {
        //     $hasAttachmentTask[] = $value->entity_id;
        // }

        //??????????????????
        $myClass     = [];
        $noClassTask = [];
        foreach ($classList as $class) {
            $taskArray = [];
            foreach ($taskListData as $key => $task) {
                if (isset($taskRelationClass[$task['id']])) {
                    if ($taskRelationClass[$task['id']] == $class['id']) {
                        //????????????
                        $taskListData[$key]['completeDate'] = $task['complete_date'] == '0000-00-00 00:00:00' ? '' : date('Y-m-d', strtotime($task['complete_date']));

                        //???????????????
                        if (!empty($hasAttachmentTask) && in_array($task['id'], $hasAttachmentTask)) {
                            $taskListData[$key]['hasAttachment'] = true;
                        } else {
                            $taskListData[$key]['hasAttachment'] = false;
                        }

                        //????????????
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
                    //????????????
                    $taskListData[$key]['completeDate'] = $task['complete_date'] == '0000-00-00 00:00:00' ? '' : date('Y-m-d', strtotime($task['complete_date']));

                    //???????????????
                    if ($hasAttachmentTask != [] && in_array($task['id'], $hasAttachmentTask)) {
                        $taskListData[$key]['hasAttachment'] = true;
                    } else {
                        $taskListData[$key]['hasAttachment'] = false;
                    }

                    //????????????
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
     * [modifyClassSort ??????????????????]
     *
     * @method ?????????
     *
     * @param  [array]           $taskList [????????????????????????]
     * @param  [string]          $userId   [??????????????????]
     *
     * @return [bool]                      [????????????]
     */
    public function modifyClassSort($taskList, $userId)
    {
        //????????????????????????????????????
        $classParams = [
            'search'   => ['user_id' => [$userId]],
            'order_by' => ['sort_id' => 'asc'],
        ];
        $classList = app($this->taskClassRepository)->getTaskClassList($classParams)->toArray();
        $classIdArray = array_column($classList, 'id');

        //?????????????????????
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
     * [modifyTaskClassRelation ?????????????????????????????????]
     *
     * @method ?????????
     *
     * @param  [array]                   $taskList [????????????????????????]
     * @param  [string]                  $userId   [??????????????????]
     *
     * @return [bool]                              [????????????]
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
     * [getPowerTaskArray ??????????????????]
     *
     * @author ?????????
     *
     * @param  [type]      $userId [??????ID]
     * @param  [type]      $power  [????????????,manager?????????joiner?????????shared?????????]
     *
     * @return [type]              [?????????????????????ID??????]
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
     * [getSonTask ?????????????????????]
     *
     * @author ?????????
     *
     * @param  [int]        $taskId [??????ID]
     *
     * @return [object]             [????????????]
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
     * [getSubordinateTask ?????????????????????]
     *
     * @author ?????????
     *
     * @param  [type]             $userId [??????ID]
     *
     * @return [type]                     [????????????]
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
     * [createTaskLog ????????????????????????]
     *
     * @author ?????????
     *
     * @param  [int]           $taskId     [??????ID]
     * @param  [string]        $userId     [??????ID]
     * @param  [string]        $logContent [????????????]
     *
     * @return [object]                    [????????????]
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
     * ?????????????????????????????????
     * @param int $taskId
     * @param int $operateUserId ????????????id
     * @param array $userIds
     * @param string $prefixLogContent ??????????????????
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

        //??????
        $task = app($this->taskManageRepository)->getDetail($taskId);
        if (!$task) {
            return ['code' => ['0x046006', 'task']];
        }
        if ($task['lock'] == 1 && $task['manage_user'] != $userId) {
            return ['code' => ['0x046027', 'task']];
        }

        //????????????????????????????????????
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
                //?????????????????????
                $this->modifyParentTaskProgress($pid);
            }
        }

        //????????????
        // if ($attachments) {
        if ($type == "task_attachments") {
            $oldAttachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'task_manage', 'entity_id' => $taskId]);
            app($this->attachmentService)->attachmentRelation("task_manage", $taskId, $attachments);

            if ($files) {
                //??????????????????
                $fileName   = implode(',', array_column($files, 'name'));
                $logContent = trans('task.0x046017') . ' ' . $fileName;
                $this->createTaskLog($taskId, $userId, $logContent);
            }

            $deleteAttachments = array_diff($oldAttachments, $attachments);

            if (!empty($deleteAttachments)) {
                $deleteData = app($this->attachmentService)->getAttachments(['attach_ids' => $deleteAttachments]);

                //??????????????????
                $fileName   = implode(',', array_column($deleteData, 'attachment_name'));
                $logContent = trans('task.0x046037') . ' ' . $fileName;

                $this->createTaskLog($taskId, $userId, $logContent);
            }
        }

        //??????????????????
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

    //??????????????? ????????????
    public function taskScheduleList($user_id, $data = [])
    {
        //????????????????????? ????????? ??????ID
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
            $t2["user_id"]          = $v["manage_user"]; //?????????
            $t2["calendar_begin"]   = ($v["start_date"] && $v["start_date"] != '0000-00-00') ? $v["start_date"] : $v["created_at"];
            $t2["calendar_end"]     = $v["end_date"] == "0000-00-00" ? '' : $v["end_date"];
            $t2["share_user"]       = $this->getTaskUserById($v["id"], "shared"); //?????????
            $t2["join_user"]        = $this->getTaskUserById($v["id"], "join");
            $t2["type"]             = "task";
            array_push($result, $t2);
        }
        return $result;
    }

    //???????????????  ?????????????????????????????????
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

    //????????????????????? ????????????
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
        $t2["user_id"]          = $temp['manage_user']; //?????????
        $t2["calendar_begin"]   = ($temp["start_date"] && $temp["start_date"] != '0000-00-00') ? $temp['start_date']: $temp['created_at'];
        $t2["calendar_end"]     = $temp['end_date'] == "0000-00-00" ? '' : $temp['end_date'];
        $t2["share_user"]       = $this->getTaskUserById($temp['id'], "shared"); //?????????
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

    //????????????
    //????????????   //    remind_type  1 ????????? 2 ??????????????? 3 ???????????? 4 ???????????? 5 ???????????? ??????X???X??? 6 ???????????? ??????X?????????X???
    public function getTaskRemindByDate()
    {
        //?????? ??????????????????????????? ?????? remind_type = 2 3 4
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
        //?????? ??????????????????????????? ?????? remind_type = 5
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
        //?????? ??????????????????????????? ?????? remind_type = 6
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

    //???????????? ??????
    private function checkRepeatRemindByPlan($day, $limit, $endDate)
    {
        //30??? ??????5???
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
                //??????
                if ($currentDay % $limit == 0) {
                    //??????
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

        // ??????????????????
        $filterClassId = Arr::get($params, 'search.class_id.0');
        if ($filterClassId) {
            $filterClassId = is_array($filterClassId) ? $filterClassId : [$filterClassId];
            $result = array_intersect_key($result, array_flip($filterClassId));
        }
        // ??????????????????
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
        //?????????????????????????????????
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

        //????????????????????????
        $params = $this->parseParams($params);

        //?????????????????????????????????
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

        //???????????????????????????
        $hasAttachmentTask = app($this->attachmentService)->getEntityIdsFromAttachRelTable('task_manage');
        // $hasAttachmentTask = [];
        // if (count($allAttachment) > 0) {
        //     $hasAttachmentTask = array_column($allAttachment->toArray(), 'entity_id');
        // }
        $taskArray = [];

        foreach ($taskListData as $key => $task) {
            //????????????
            $task['completeDate'] = $task['complete_date'] == '0000-00-00 00:00:00' ? '' : date('Y-m-d', strtotime($task['complete_date']));

            //???????????????
            $task['hasAttachment'] = (!empty($hasAttachmentTask) && in_array($task['id'], $hasAttachmentTask)) ? true : false;
            //????????????
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

    // ????????????????????????????????????????????????
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

    // ????????????????????????????????????????????????????????????????????????
    public function dailyLogByUserId($userId, $day) {
        $logs = app($this->taskLogRepository)->dailyLogByUserId($userId, $day);
        $taskLog = $logs->groupBy('task_id')->toArray();
        $logTypeKeysOri = [
            '0x046004' => '???????????????????????????',
            '0x046005' => '???????????????????????????',
            '0x046039' => '????????????????????????',
            '0x046016' => '??????????????????',
            '0x046010' => '??????????????????',
            '0x046008' => '??????????????????',
            '0x046009' => '???????????????',
        ];
        $langKeys = ['en', 'zh-CN']; // ?????????????????????
        // ????????????id???????????????????????????????????????????????????
        foreach ($taskLog as $taskId => &$logs) {
            $logTypeKeys = $logTypeKeysOri;
            foreach ($logs as $logKey => $log) {
                $logContent = Arr::get($log, 'log_content');
                $flag = false; // ??????????????????????????????????????????????????????????????????????????????
                foreach ($logTypeKeys as $typeKey => $logTypeKey) {
                    foreach ($langKeys as $key => $langKey) {
                        $flag = strpos($logContent, trans('task.' . $typeKey, [], $langKey)); // ????????????
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

    // ????????????????????????????????????????????????
    public function recovery($input) {
        $ids = Arr::get($input, 'ids');
        $ids = is_array($ids) ? $ids : [$ids];
        $parentIds = app($this->taskManageRepository)->entity->whereIn('id', $ids)->withTrashed()
            ->pluck('parent_id')->unique()->filter()->toArray();
        $recoverIds = array_merge($ids, $parentIds);
        $res = app($this->taskManageRepository)->restoreSoftDelete(['id' => [$recoverIds, 'in']]);
        // ????????????
        $this->MultiRecoveryTaskToCalendar($recoverIds, own('user_id'));
        //??????????????????
        $logContent = trans('task.restore_task');
        $this->createTaskLog($recoverIds, own('user_id'), $logContent);

        // ????????????????????????
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
        // ?????????????????????
        $dynamic = '';
        if (Arr::has($data, 'additional')) {
            $additional = $data['additional'];
            // ?????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
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
        // ???????????????????????????????????????
        $taskName = Arr::get($data, 'task_name', true);
        $startDate = Arr::get($data, 'start_date', true);
        $manageUser = Arr::get($data, 'manage_user', true);
        $test = $this->buildFlowOutTaskMsg($taskName, true, $startDate, $manageUser);
        if ($test !== true) {
            return ['code' => $test];
        }

        // ??????????????????????????????key???????????????????????????????????????;?????????????????????
        if (array_key_exists('attachment_ids', $data)) {
            $data['attachments'] = is_array($data['attachment_ids']) ? $data['attachment_ids'] : explode(',', $data['attachment_ids']);
            unset($data['attachment_ids']);
        }

        $res = $this->modifyFlowOutTask($id, $data, $userId);
        $res === true && $res = $id; // ?????????????????????id

        return $this->handFlowOutSendResult($res, TaskManageEntity::class);
    }

    public function flowOutSendToDeleteTask($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'data.current_user_id');

        $res = $this->deleteTask($id, $userId);
        $res === true && $res = $id; // ?????????????????????id

        return $this->handFlowOutSendResult($res, TaskManageEntity::class);
    }

    public function getCustomTaskClass($id, $own)
    {
        $taskClasses = $this->taskMineClass($own['user_id']);
        $data = empty($id) ? $taskClasses : collect($taskClasses)->whereIn('class_id', $id)->toArray();
        return $data;
    }

    // ??????true?????????msg
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

    // ?????????????????????????????????????????????????????????
    private function buildFlowOutTaskMsg($taskName, $taskCreateUser, $startDate, $manageUser = true) {
        $msg = trans('common.0x000001');
        $empty = [];
        if (!$taskName) { $empty[] = trans('outsend.task.fileds.task_name.field_name'); }
        if (!$taskCreateUser) { $empty[] = trans('outsend.task.fileds.create_user.field_name'); }
        if (!$startDate) { $empty[] = trans('outsend.task.fileds.start_date.field_name'); }
        if (!$manageUser) { $empty[] = trans('outsend.task.fileds.manage_user.field_name'); }
        if ($empty) {
            return $msg . ': ' . implode('???', $empty);
        } else {
            return true;
        }
    }

    // ?????????????????????
    public static function taskFields($hasParentId = true, $isRequired = 1) {
        $fields = [
            'task_name' => [
                'field_name' => '????????????',
                'required' => $isRequired,
            ],
            'create_user' => [
                'field_name' => '?????????',
                'describe' => '?????????id',
                'required' => $isRequired,
            ],
            'manage_user' => [
                'field_name' => '?????????',
                'describe' => '?????????id?????????????????????'
            ],
            'joiner' => [
                'field_name' => '?????????',
                'describe' => '?????????id??????'
            ],
            'shared' => [
                'field_name' => '?????????',
                'describe' => '?????????id??????'
            ],
            'task_description' => [
                'field_name' => '????????????',
                'describe' => '?????????id?????????????????????'
            ],
            'start_date' => [
                'field_name' => '????????????',
                'required' => $isRequired,
            ],
            'end_date' => [
                'field_name' => '?????????',
            ],
            'important_level' => [
                'field_name' => '????????????',
                'describe' => '??????0????????????????????????1????????????????????????2???????????????'
            ],
            'attachment_ids' => [
                'field_name' => '??????',
            ]
        ];
        if ($hasParentId) {
            $fields['parent_id'] = [
                //?????????????????????????????????????????????
                'field_name' => '?????????',
                //?????????????????????????????????????????????
                'describe' => '???????????????????????????id??????????????????'
            ];
        }
        return $fields;
    }

    // ?????????????????????????????????????????????
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
        //???????????????
        $taskArray = $this->getPowerTaskArray($userId, 'joiner');

        //???????????????????????????,?????????????????????
        if (!in_array($taskId, $taskArray)) {
            return array('code' => array('0x000017', 'common'));
        }

        $updateInfo = array_merge($oldTaskData->toArray(), $taskInfo); // ???????????????????????????
        $updateRes = $this->modifyTask($taskId, $updateInfo, $userId);

        if ($updateRes === true && $hasPersonModify) {
            //???????????????
            if (Arr::has($taskInfo, 'joiner')) {
                $join = explode(',', $taskInfo['joiner']);
                $createJoinerData = [
                    'task_id'  => $taskId,
                    'user_ids' => $join,
                ];
                $this->createJoiner($createJoinerData, $userId);
            }

            //???????????????
            if (Arr::has($taskInfo, 'shared')) {
                $shared = explode(',', $taskInfo['shared']);
                $createSharedData = [
                    'task_id'  => $taskId,
                    'user_ids' => $shared,
                ];
                $this->createShared($createSharedData, $userId);
            }

            //???????????????
            if (Arr::has($taskInfo, 'manage_user') && $taskInfo['manage_user'] != $oldTaskData['manage_user']) {
                $userName = app($this->userRepository)->getUserName($taskInfo['manage_user']);
                $this->modifyTaskManager($taskId, ['user_id' => $taskInfo['manage_user'], 'user_name' => $userName], $userId);
            }
        }
        return $updateRes;
    }

    // ???????????????????????????
    private function hasManagePermission($curUserId, $task)
    {
        $manageUser = $task['manage_user'] ?? '';
        return $manageUser === $curUserId;
    }

    // ????????????????????????
    private function sendCommentReminder($taskId, $curUserId, $parentFeedbackId = null)
    {
        // ????????????????????????????????????????????????
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
