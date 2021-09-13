<?php

namespace App\EofficeApp\Task\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Task\Entities\TaskClassRelationEntity;

class TaskClassRelationRepository extends BaseRepository {

    public function __construct(TaskClassRelationEntity $taskClassRelationEntity) {
        parent::__construct($taskClassRelationEntity);
    }

    public function getRelationData($where) {
        return $this->entity->multiWheres($where)->get();
    }

    public function getClassIdByTaskId($task_id) {
        return $this->entity->select(["class_id"])->where("task_id", $task_id)->orderBy('created_at', 'desc')->first();
    }
    
    public function getTaskIdsByClassId($class_id){
        if(is_array($class_id)){
            return $this->entity->select(["task_id"])->whereIn("class_id", $class_id)->get();
        } else {
            return $this->entity->select(["task_id"])->where("class_id", $class_id)->get();
        }
    }
    
    public function taskRelationExists($taskId,$classId)
    {
        return $this->entity->where('task_id', $taskId)->where('class_id', $classId)->count();
    }
}
