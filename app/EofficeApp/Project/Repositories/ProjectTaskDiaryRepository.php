<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectTaskDiaryEntity;

/**
 * 项目任务处理 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectTaskDiaryRepository extends BaseRepository {

    public function __construct(ProjectTaskDiaryEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取项目任务处理列表
     * 
     * @param array $param
     * 
     * @author 喻威
     * 
     * @since 2015-10-19
     */
    public function getProjectTaskDiaryList($param) {
        $default = [
            'fields' => ['project_task_diary.*', 'user.user_name as user_name', 'avatar_thumb'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['taskdiary_curtime' => 'desc']
        ];

        $param = array_merge($default, array_filter($param));
        return $this->entity->select($param['fields'])->leftJoin('user', function($join) {
                            $join->on("project_task_diary.taskdiary_creater", '=', 'user.user_id');
                        })->leftJoin('user_info', function($join) {
                            $join->on("user_info.user_id", '=', 'user.user_id');
                        })->wheres($param['search'])
                        ->where("taskdiary_task", $param['taskdiary_task'])
                        ->where("task_diary_replyid", "0")
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    public function getProjectTaskDiaryTotal($param) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->where("taskdiary_task", $param['taskdiary_task'])->where("task_diary_replyid", "0")->wheres($param['search'])->count();
    }

    public function getTaskDiaryDetail($tid) {
        return $this->entity->select(['project_task_diary.*', 'task_name', 'user_name', 'manager_name'])
                        ->leftJoin('user', function($join) {
                            $join->on("user.user_id", '=', 'project_task_diary.taskdiary_creater');
                        })
                        ->leftJoin('project_manager', function($join) {
                            $join->on("project_manager.manager_id", '=', 'project_task_diary.taskdiary_project');
                        })
                        ->leftJoin('project_task', function($join) {
                            $join->on("project_task.task_id", '=', 'project_task_diary.taskdiary_task');
                        })
                        ->where("taskdiary_id", $tid)
                        ->get()->toArray();
    }

    /**
     * 获取
     * 
     * @param  
     * 
     * @return array
     * 
     * @author 喻威
     * 
     * @since 2015-10-21
     */
    public function infoProjectTaskDiarybyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }

    /**
     * 
     * 获取当前任务的百分比（整数）
     * 
     * @param type $taskId
     * @return int
     */
    public function getTaskDiaryPersent($taskId) {

        return $this->entity->where('taskdiary_task', $taskId)->sum('taskdiary_persent');
    }

    //获取项目当前的进度
    public function projectCountSumPersent($manager_id) {

        return $this->entity->where("taskdiary_project", $manager_id)->sum("taskdiary_persent");
    }

    public function getTaskDiarybyWhere($where) {

        return $this->entity->select(['project_task_diary.*', 'user.user_name as user_name'])->leftJoin('user', function($join) {
                    $join->on("project_task_diary.taskdiary_creater", '=', 'user.user_id');
                })->wheres($where)->get()->toArray();
    }

}
