<?php

namespace App\EofficeApp\Task\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Task\Entities\TaskUserEntity;

class TaskUserRepository extends BaseRepository {

    public function __construct(TaskUserEntity $taskUserEntity) {
        parent::__construct($taskUserEntity);
    }

    /**
     * [getTaskUserCount 获取任务关联用户数据数量,用于判断数据是否已存在]
     *
     * @method 朱从玺
     *
     * @param  [array]           $search [查询条件]
     *
     * @return [object]                  [查询结果]
     */
    public function getTaskUserCount($search) {
        return $this->entity->wheres($search)->count();
    }

    /**
     * [getTaskUserList 获取任务关联用户数据]
     *
     * @method 朱从玺
     *
     * @param  [type]          $param [查询条件]
     *
     * @return [type]                 [查询结果]
     */
    public function getTaskUserList($param) {
        $defaultParam = [
            'fields' => ['*'],
            'search' => []
        ];

        $param = array_merge($defaultParam, $param);

        return $this->entity->select($param['fields'])
                        ->wheres($param['search'])
                        ->get();
    }
    public function getTaskIds($userId)
    {
        return $this->entity->select(['task_id'])->where('user_id',$userId)->get();
    }
    public function isJoinerOrSharer($userId, $taskId)
    {
        return $this->entity->where('user_id',$userId)->where('task_id',$taskId)->whereIn('task_relation',['join','shared'])->count();
    }
    /**
     * [getTaskRelationUser 获取任务相关用户]
     *
     * @method 朱从玺
     *
     * @param  [int]                 $taskId [任务ID]
     *
     * @return [object]                      [查询结果]
     */
    public function getTaskRelationUser($taskId) {
        return $this->entity->select('*')
                        ->where('task_id', $taskId)
                        ->whereIn('task_relation', ['join', 'shared'])
                        ->get();
    }

    /**
     * [getsharedPower 获取分享权限以上的任务]
     *
     * @method getsharedPower
     *
     * @param  [type]         $userId [description]
     * @param  array          $fields [description]
     *
     * @return [type]                 [description]
     */
    public function getsharedPower($userId, $fields = ['*']) {
        return $this->entity->select($fields)
                        ->where('user_id', $userId)
                        ->where(function($query) {
                            $query->where('task_relation', 'join')
                            ->orWhere('task_relation', 'shared');
                        })
                        ->get();
    }

    public function getTasksByWhere($where,$fields = ['*']) {
        return $this->entity->select($fields)->wheres($where)
                        ->get()->toArray();
    }

    public function getTaskJoinUserById($taskId) {
        return $this->entity->select(['user_id'])->where('task_relation', 'join')->where('task_id', $taskId)
                        ->get()->toArray();
    }
    public function getTaskSharedUserById($taskId) {
        return $this->entity->select(['user_id'])->where('task_relation', 'shared')->where('task_id', $taskId)
                        ->get()->toArray();
    }
}
