<?php

namespace App\EofficeApp\Task\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Task\Entities\TaskRemindsEntity;

class TaskRemindsRepository extends BaseRepository {

    private $currentdate;

    public function __construct(TaskRemindsEntity $taskRemindsEntity) {
        parent::__construct($taskRemindsEntity);
    }

    public function getTasksByWhere($where) {
        return $this->entity->select(["*"])->wheres($where)->first();
    }

    public function getTaskRemindByDate($date) {
        $this->currentdate = $date;
        return $this->entity->select(["task_manage.*"])
                        ->leftJoin('task_manage', function($join) {
                            $join->on("task_reminds.task_id", '=', 'task_manage.id');
                        })->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where("remind_type", 2)
                                ->where('remind_time', $this->currentdate);
                    })->orWhere(function ($query) {
                        $query->where('remind_type', 3)
                                ->where('end_date', $this->currentdate);
                    })->orWhere(function ($query) {
                        $query->where('remind_type', 4)
                                ->where('start_date', $this->currentdate);
                    });
                })->where("task_status", "0")->get();
    }

    public function getInfo($where) {
         return $this->entity->select(["*"])->wheres($where)->get();
    }

}
