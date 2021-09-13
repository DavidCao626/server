<?php

namespace App\EofficeApp\Task\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Task\Entities\TaskClassEntity;

class TaskClassRepository extends BaseRepository {

    public function __construct(TaskClassEntity $taskClassEntity) {
        parent::__construct($taskClassEntity);
    }

    /**
     * [getTaskClassList 获取任务类别列表]
     *
     * @method 朱从玺
     *
     * @param  [array]            $params    [查询条件]
     * @param  [string]           $countType [获取梳理]
     *
     * @return [object]                      [获取结果]
     */
    public function getTaskClassList($params, $countType = 'all') {
        $defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['id' => 'asc'],
            'search' => []
        ];

        $params = array_merge($defaultParams, $params);

        $query = $this->entity->select($params['fields'])
                ->multiWheres($params['search'])
                ->parsePage($params['page'], $params['limit'])
                ->orders($params['order_by']);

        switch ($countType) {
            case 'first':
                return $query->first();
                break;

            default:
                return $query->get();
                break;
        }
    }

    public function getTaskClassRelation($userId) {
        return $this->entity->select(["class_name", "task_id", "id"])->leftJoin('task_class_relation', function($join) {
                    $join->on("task_class_relation.class_id", '=', 'task_class.id');
                })->where("user_id", $userId)->orderBy("sort_id", "asc")->get()->toArray();
    }
    
    public function getTaskClassByWhere($where, $fields = ['*']) {
        return $this->entity->select($fields)->wheres($where)->get()->toArray();
    }
    
            

}
