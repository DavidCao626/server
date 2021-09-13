<?php

namespace App\EofficeApp\Task\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Task\Entities\TaskManageEntity;
use Illuminate\Support\Arr;
class TaskManageRepository extends BaseRepository
{

    private $temp      = null;
    private $sch_start = null;
    private $sch_end   = null;

    public function __construct(TaskManageEntity $taskManageEntity)
    {
        parent::__construct($taskManageEntity);
    }

    /**
     * [taskInsertBasic 基本插入]
     * @param  [array] $insertData [要插入的数据]
     * @return [null]
     */
    public function taskInsertBasic($insertData)
    {
        $this->entity->task_name       = $insertData['task_name'];
        $this->entity->parent_id       = $insertData['parent_id'];
        $this->entity->create_user     = $insertData['create_user'];
        $this->entity->manage_user     = $insertData['manage_user'];
        $this->entity->start_date      = $insertData['start_date'];
        $this->entity->end_date        = $insertData['end_date'];
        $this->entity->important_level = $insertData['important_level'];
        $this->entity->lock            = $insertData['lock'];
        $this->entity->task_description            = Arr::get($insertData, 'task_description', '');

        $this->entity->save();
        return $this->entity;
    }

    /**
     * 获取任务数据
     * @param $taskId
     * @param bool $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     */
    public function getTaskInfo($taskId, $withTrashed = false)
    {
        $query = $this->entity->newQuery();
        $withTrashed && $query->withTrashed();
        return $query->where('id', $taskId)
            ->with(['taskUser' => function ($query) use ($taskId) {
                $query->select('task_id', 'user_id', 'task_relation');
            }, 'taskHasManySonTask' => function ($query) use ($withTrashed) {
                $withTrashed && $query->withTrashed();
                $query->select('parent_id', 'id', 'task_name', 'manage_user', 'progress', 'task_status')
                    ->with(['taskHasOneManager' => function ($query) {
                        $query->select('user_id', 'user_name');
                    }]);
            }, 'taskHasManyCompleteSon' => function ($query) use ($withTrashed) {
                $withTrashed && $query->withTrashed();
                $query->select('parent_id', 'id', 'task_name', 'manage_user', 'progress', 'task_status')
                    ->with(['taskHasOneManager' => function ($query) {
                        $query->select('user_id', 'user_name');
                    }])
                    ->where('task_status', 0); //查找未完成子任务
            }, 'taskHasOneManager' => function ($query) {
                $query->select('user_id', 'user_name');
            }, 'taskBelongsToParent' => function ($query) use ($withTrashed) {
                $query->select('id', 'task_name');
                $query->withTrashed(); // 即时父任务被删除了，也查询出来给予显示名称
            }])
            ->first();
    }

    /**
     * [getUserTaskCount 查询结果的条数]
     *
     * @author 朱从玺
     *
     * @param  [array]           $param [查询条件]
     *
     * @return [int]                    [查询结果]
     */
    public function getUserTaskCount($param)
    {
        $query = $this->getUserTaskWhere($param);

        if (isset($param['search']['joiner'][0]) && $param['search']['joiner'][0]) {
            $query = $query->whereHas('taskUser', function ($query) use ($param) {
                $query->whereIn('user_id', $param['search']['joiner'][0])
                    ->where('task_relation', 'join');
            });
        }

        if (isset($param['search']['shared'][0]) && $param['search']['shared'][0]) {
            $query = $query->whereHas('taskUser', function ($query) use ($param) {
                $query->whereIn('user_id', $param['search']['shared'][0])
                    ->where('task_relation', 'shared');
            });
        }
        unset($param['search']['joiner']);
        unset($param['search']['shared']);

        $search = isset($param['search']) ? $param['search'] : [];

        return $query->wheres($search)->count();
    }

    /**
     * [getUserTaskList 获取某个人的任务列表]
     *
     * @author 朱从玺
     *
     * @param  [array]           $param [查询条件]
     *
     * @return [object]                 [查询结果]
     */
    public function getUserTaskList($param)
    {
        $query = $this->getUserTaskWhere($param);

        if (isset($param['search']['joiner'][0]) && $param['search']['joiner'][0]) {
            $query = $query->whereHas('taskUser', function ($query) use ($param) {
                $query->whereIn('user_id', $param['search']['joiner'][0])
                    ->where('task_relation', 'join');
            });
        }

        if (isset($param['search']['shared'][0]) && $param['search']['shared'][0]) {
            $query = $query->whereHas('taskUser', function ($query) use ($param) {
                $query->whereIn('user_id', $param['search']['shared'][0])
                    ->where('task_relation', 'shared');
            });
        }
        unset($param['search']['joiner']);
        unset($param['search']['shared']);
        return $query->wheres($param['search'])
            ->with(['taskUser' => function ($query) use ($param) {
                $query->select('task_id', 'user_id')
                    ->where('user_id', $param['user_id'])
                    ->where('task_relation', 'follow');
            }, 'taskHasOneManager' => function ($query) {
                $query->select('user_id', 'user_name');
            }, 'taskHasManySonTask' => function ($query) {
                $query->select('parent_id')
                    ->selectRaw("count(*) as sonTotal")
                    ->groupBy('parent_id');
            }, 'taskHasManyCompleteSon' => function ($query) {
                $query->select('parent_id')
                    ->selectRaw("count(*) as completeSon")
                    ->where('task_status', 1)
                    ->groupBy('parent_id');
            }])
            ->parsePage($param['page'], $param['limit'])
            ->orders($param['order_by'])
            ->get();
    }

    /**
     * [getUserTaskWhere 组装查询条件]
     *
     * @author 朱从玺
     *
     * @param  [array]            $param [查询条件]
     *
     * @return [object]                  [组装后的查询实体]
     */
    public function getUserTaskWhere($param)
    {
        $query = $this->entity;
        if (isset($param['task_class'])) {
            switch ($param['task_class']) {
                case 'mine': //我的任务
                    $query = $query->where(function ($query) use ($param) {
                        $query->where('manage_user', $param['user_id'])
                            ->orWhereHas('taskUser', function ($query) use ($param) {
                                $query->where('user_id', $param['user_id'])
                                    ->where('task_relation', 'join');
                            })
                            ->orWhereHas('taskUser', function ($query) use ($param) {
                                $query->where('user_id', $param['user_id'])
                                    ->where('task_relation', 'shared');
                            })
                            ;
                    });
                    break;
                case 'join': //参与的任务
                    $query = $query->whereHas('taskUser', function ($query) use ($param) {
                        $query->where('user_id', $param['user_id'])
                            ->where('task_relation', 'join');
                    });
                    break;
                case 'manage': //负责的任务
                    $query = $query->where('manage_user', $param['user_id']);
                    break;
                case 'follow': //关注的任务
                    $query = $query->whereHas('taskUser', function ($query) use ($param) {
                        $query->where('user_id', $param['user_id'])
                            ->where('task_relation', 'follow');
                    });
                    break;
                case 'shared': //共享给我的
                    $query = $query->whereHas('taskUser', function ($query) use ($param) {
                        $query->where('user_id', $param['user_id'])
                            ->where('task_relation', 'shared');
                    });
                    break;
                case 'create': //我创建的
                    $query = $query->where('create_user', $param['user_id']);
                    break;
                case 'all': //全部
                    $query = $query->where(function ($query) use ($param) {
                        $query->where('manage_user', $param['user_id'])
                        //->orWhere('create_user', $param['user_id'])
                            ->orWhereHas('taskUser', function ($query) use ($param) {
                                $query->where('user_id', $param['user_id'])
                                    ->where(function ($query) {
                                        $query->where('task_relation', 'join')
                                            ->orWhere('task_relation', 'shared');
                                    })
                                    ->whereIn('task_relation', ['join', 'shared']);
                            });
                    });
                    break;
                case 'complete': //完成
                    $query = $query->where('task_status', 1)
                        ->where(function ($query) use ($param) {
                            $query->where('manage_user', $param['user_id'])
                                ->orWhere('create_user', $param['user_id'])
                                ->orWhereHas('taskUser', function ($query) use ($param) {
                                    $query->where('user_id', $param['user_id'])
                                        ->where(function ($query) {
                                            $query->where('task_relation', 'join')
                                                ->orWhere('task_relation', 'shared');
                                        });
                                });
                        });
                    break;
                case 'deleted': //有删除记录的
                    $query = $query->withTrashed()
                        ->whereHas('taskLog', function ($query) use ($param) {
                            $query->whereIn('log_type', ['delete', 'restore'])
                                ->where('user_id', $param['user_id']);
                        });
                    break;

                default:
                    $query = $query->where(function ($query) use ($param) {
                        $query->where('manage_user', $param['user_id'])
                            ->orWhereHas('taskUser', function ($query) use ($param) {
                                $query->where('user_id', $param['user_id'])
                                    ->whereIn('task_relation', ['join', 'shared']);
                            });
                    });
                    break;
            }
        }

        if (!isset($param['search']['end_date']) || !$param['search']['end_date']) {
            $today    = date('Y-m-d');
            $tomorrow = date('Y-m') . '-' . (date('d') + 1);

            if (isset($param['end_status'])) {
                switch ($param['end_status']) {
                    case 'delay':
                        $query = $query->where('end_date', '<', $today)
                            ->where('end_date', '!=', '0000-00-00')
                            ->where('task_status', 0);
                        break;
                    case 'today':
                        $query = $query->where('end_date', $today);
                        break;
                    case 'tomorrow':
                        $query = $query->where('end_date', $tomorrow);
                        break;
                    case 'will':
                        $query = $query->where('end_date', '>', $tomorrow);
                        break;
                    case 'soon':
                        $query = $query->where('end_date', '>=', $today)
                            ->where('task_status', 0);
                        break;
                    case 'noTime':
                        $query = $query->where('end_date', '0000-00-00');
                        break;
                    default:
                        $query = $query;
                        break;
                }
            }
        }

        return $query;
    }

    /**
     * [getDeletedTaskCount 获取回收站任务数量]
     *
     * @method 朱从玺
     *
     * @param  [array]              $param [查询条件]
     *
     * @return [int]                       [查询结果]
     */
    public function getDeletedTaskCount($param)
    {
        $search = isset($param['search']) ? $param['search'] : [];

        return $this->entity->onlyTrashed()->where('force_delete', 0)->wheres($search)->count();
    }

    /**
     * [getDeletedTaskList 获取回收站任务列表]
     *
     * @method 朱从玺
     *
     * @param  [array]              $param [查询条件]
     *
     * @return [object]                    [查询结果]
     */
    public function getDeletedTaskList($param)
    {
        $defaultParam = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['deleted_at' => 'desc'],
            'search'   => [],
        ];

        $param = array_merge($defaultParam, $param);
        $query = $this->entity->newQuery()->where('force_delete', 0);
        if (!isset($param['noPage']) || $param['noPage'] != 'noPage') {
            $query = $query->parsePage($param['page'], $param['limit']);
        }
        return $query->select($param['fields'])
            ->with(['taskHasOneManager' => function ($query) {
                $query->select('user_id', 'user_name');
            }, 'taskBelongsToParent' => function ($query) {
                $query->withTrashed();
            }])->withCount(['taskHasManySonTask as sonTaskCount' => function ($query) {
                $query->withTrashed();
            }])->wheres($param['search'])
            ->orders($param['order_by'])
            ->onlyTrashed()
            ->get();
    }

    /**
     * [restoreTask 还原任务]
     *
     * @method 朱从玺
     *
     * @param  [array]      $search [还原条件]
     *
     * @return [bool]               [还原结果]
     */
    public function restoreTask($search)
    {
        return $this->entity->wheres($search)->restore();
    }

    /**
     * [getTaskListCount 获取任务数量]
     * @param  [array] $param [查询条件]
     * @return [int]           [查询结果]
     */
    public function getTaskListCount($param)
    {
        $query = $this->entity->where(function ($query) use ($param) {
            $query->where('id', 'in', $param['id'])
                ->orWhere('manage_user', $param['user_id']);
        });

        if (isset($param['left_date'])) {
            $query = $query->where('end_date', '>=', $param['left_date']);
        }

        if (isset($param['right_date'])) {
            $query = $query->where('end_date', '<', $param['right_date']);
        }

        //任务类型 delay,已超期/execute,进行中/complete,已完成
        if (isset($param['task_class'])) {
            $today = date('Y-m-d');

            switch ($param['task_class']) {
                case 'delay':
                    $query = $query->where('end_date', '<', $today)
                        ->where('end_date', '!=', '0000-00-00')
                        ->where('task_status', 0);
                    break;
                case 'execute':
                    $query = $query->where('task_status', 0);
                    break;
                case 'complete':
                    $query = $query->where('task_status', 1);
                    break;
            }
        }

        return $query->count();
    }

    /**
     * [getChildTask 获取子任务列表]
     *
     * @method 朱从玺
     *
     * @param  [int]          $taskId [任务ID]
     *
     * @return [object]               [查询结果]
     */
    public function getChildTask($taskId)
    {
        return $this->entity->select('*')
            ->where('parent_id', $taskId)
            ->get();
    }

    /**
     * [getTaskList 获取任务列表]
     *
     * @method 朱从玺
     *
     * @param  [type]      $param [查询条件]
     *
     * @return [type]             [查询结果]
     */
    public function getTaskList($param)
    {
        $defaultParam = [
            'fields' => ['*'],
            // 'page' => 0,
            // 'limit' => config('eoffice.pagesize'),
            // 'order_by' => ['id' => 'asc'],
            'search' => [],
        ];

        $param = array_merge($defaultParam, $param);

        return $this->entity->select($param['fields'])
            ->wheres($param['search'])
        // ->parsePage($param['page'], $param['limit'])
        // ->orders($param['order_by'])
            ->get();
    }

    /**
     * [getManagerPowerTask 获取负责人以上权限的任务]
     *
     * @method getManagerPowerTask
     *
     * @param  [type]              $userId [description]
     * @param  array               $fields [description]
     *
     * @return [type]                      [description]
     */
    public function getManagerPowerTask($userId, $fields = ['*'])
    {
        return $this->entity->select($fields)
            ->where('manage_user', $userId)
            ->get();
    }
    public function getTaskCount($where, $userId, $taskId, $taskType = 'all', $taskSearch = [])
    {
        $query = $this->entity->multiwheres($where);
        if (!empty($taskSearch)) {
            $query = $query->wheres($taskSearch);
        }
        if ($taskType == 'execute') {
            $query->where('task_status', 0);
        } else if ($taskType == 'complete') {
            $query->where('task_status', 1);
        } else if ($taskType == 'delay') {
            $this->buildDelayQuery($query);
        }
        return $query->where(function ($query) use ($userId, $taskId) {
            $this->buildRelationTaskQuery($userId, $taskId, $query);
        })->count();

    }

    // 获取用户得完成任务数以及平均得分
    public function getUserCompleteTaskAgeGrade($userId, $taskId = null, $where = []) {
        $query = $this->entity->newQuery();
        $query->multiwheres($where);
        $query->where('task_status', 1);
        $query->where(function ($query) use ($userId, $taskId) {
            $this->buildRelationTaskQuery($userId, $taskId, $query);
        });
        $data = $query->selectRaw('count(*) as count, avg(task_grade) avg_task_grade')->first();
        return [
            'count' => $data['count'],
            'avg_task_grade' => $data['avg_task_grade']
        ];
    }

    public function isTaskManager($userId, $taskId)
    {
        return $this->entity->where('manage_user', $userId)->where('id', $taskId)->count();
    }
    public function getTaskCountByDate($userId, $taskIds, $date, $taskType = 'execute')
    {
        if ($taskType == 'execute') {
            $query = $this->entity->where(function ($query) use ($date) {
                $query->where('task_status', 0)
                    ->orWhere(function ($query) use ($date) {
                        $query->where('complete_date', '>', $date . ' 23:59:59')
                            ->where('task_status', 1);
                    });
            })->where('start_date', '<=', $date);
        } else if ($taskType == 'complete') {
            $query = $this->entity->where('task_status', 1)->where('complete_date', 'like', $date . '%');
        } else if ($taskType == 'new') {
            $query = $this->entity->where('created_at', 'like', $date . '%');
        }

        return $query->where(function ($query) use ($userId, $taskIds) {
            if ($userId && empty($taskIds)) {
                $query->where('manage_user', $userId);
            } else if (!$userId && $taskIds) {
                $query->whereIn('id', $taskIds);
            } else if ($userId && $taskIds) {
                $query->where('manage_user', $userId)
                    ->orWhereIn('id', $taskIds);
            }
        })->count();
    }
    public function getOneUserSimpleTask($param, $userId, $taskId, $taskType = 'all')
    {
        $query = $this->entity->wheres($param['search'])->wheres($param['taskSearch']);

        if ($taskType == 'execute') {
            $query->where('task_status', 0);
        } else if ($taskType == 'complete') {
            $query->where('task_status', 1);
        } else if ($taskType == 'delay') {
            $this->buildDelayQuery($query);
        }
        return $query->where(function ($query) use ($userId, $taskId) {
            $this->buildRelationTaskQuery($userId, $taskId, $query);
        })->orders($param['order_by'])
            ->ParsePage($param['page'], $param['limit'])
            ->get();
    }

    /**
     * [getTaskListByUser 获取某些用户的任务列表]
     *
     * @method 朱从玺
     *
     * @param  [array]             $param [查询条件]
     *
     * @return [object]                   [查询结果]
     */
    public function getTaskListByUser($param)
    {

        $default = array(
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'order_by'   => ['end_date' => 'asc'],
            'search'     => [],
            'taskSearch' => [],
        );
        $param = array_merge($default, $param);

        $query = $this->entity->select($param['fields'])
            ->wheres($param['taskSearch'])
            ->with(['taskUser' => function ($query) {
                $query->select('task_id', 'user_id', 'task_relation')
                    ->whereIn('task_relation', ['join', 'shared']);
            }, 'taskHasOneManager' => function ($query) {
                $query->select('user_id', 'user_name');
            }, 'taskHasManySonTask' => function ($query) {
                $query->select('parent_id')
                    ->selectRaw("count(*) as sonTotal")
                    ->groupBy('parent_id');
            }, 'taskHasManyCompleteSon' => function ($query) {
                $query->select('parent_id')
                    ->selectRaw("count(*) as completeSon")
                    ->where('task_status', 1)
                    ->groupBy('parent_id');
            }])
            ->orders($param['order_by'])
            ->ParsePage($param['page'], $param['limit']);

        if (isset($param['reportUser']) && $param['reportUser']) {
            $query = $query->where(function ($query) use ($param) {
                if (in_array('manager', $param['reportUser']) && isset($param['userIds'])) {
                    $query->whereIn('manage_user', $param['userIds']);
                }

                if (in_array('joiner', $param['reportUser']) && isset($param['userIds'])) {
                    $query->orWhereHas('taskUser', function ($query) use ($param) {
                        $query->whereIn('user_id', $param['userIds'])
                            ->where('task_relation', 'join');
                    });
                }

                if (in_array('shared', $param['reportUser']) && isset($param['userIds'])) {
                    $query->orWhereHas('taskUser', function ($query) use ($param) {
                        $query->whereIn('user_id', $param['userIds'])
                            ->where('task_relation', 'shared');
                    });
                }
            });
        } else {
            if (isset($param['userIds'])) {
                $query = $query->whereIn('manage_user', $param['userIds']);
            }
        }

        if (isset($param['fieldSearchManageUser'])) {
            $query->whereIn('manage_user', $param['fieldSearchManageUser']);
        }

        if (isset($param['my_attention']) && $param['my_attention']) {

            $query = $query->where(function ($query) use ($param) {
                if (in_array('follow', $param['my_attention'])) {
                    $query->whereIn('id', $param['my_attention_ids']);
                } else {
                    if (in_array('not_follow', $param['my_attention'])) {
                        $query->whereNotIn('id', $param['my_attention_ids']);
                    }
                }
            });
        }

        //一个时间段内新增和完成的任务
        if (isset($param['dateRange']) && $param['dateRange'] != []) {
            $dateRange = [$param['dateRange'][0], array_pop($param['dateRange']) . ' 23:59:59'];

            $query = $query->where(function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange)
                    ->orWhereBetween('complete_date', $dateRange);
            });
        }

        if (isset($param['taskType'])) {
            switch ($param['taskType']) {
                case 'all': //全部
                    $query = $query;
                    break;
                case 'delay': //已超期
                    $this->buildDelayQuery($query);
                    break;
                case 'execute': //进行中
                    $query = $query->where('task_status', 0);
                    break;
                case 'complete': //已完成
                    $query = $query->where('task_status', 1);
                    break;
            }
        }

        return $query->get();
    }

    /**
     * [getTaskCountByUser 获取某些用户的任务条数]
     *
     * @method 朱从玺
     *
     * @param  [array]             $param [查询条件]
     *
     * @return [int]                      [查询结果]
     */
    public function getTaskCountByUser($param)
    {
        $default = array(
            'order_by' => [],
            'search'   => [],
        );
        $params     = array_merge($default, $param);
        $taskSearch = isset($param['taskSearch']) ? $param['taskSearch'] : [];

        $query = $this->entity->wheres($taskSearch);

        if (isset($param['my_attention']) && $param['my_attention']) {

            $query = $query->where(function ($query) use ($param) {
                if (in_array('follow', $param['my_attention'])) {
                    $query->whereIn('id', $param['my_attention_ids']);
                } else {
                    if (in_array('not_follow', $param['my_attention'])) {
                        $query->whereNotIn('id', $param['my_attention_ids']);
                    }
                }
            });
        }

        if (isset($param['reportUser']) && $param['reportUser']) {
            $query = $query->where(function ($query) use ($param) {
                if (in_array('manager', $param['reportUser']) && isset($param['userIds'])) {
                    $query->whereIn('manage_user', $param['userIds']);
                }

                if (in_array('joiner', $param['reportUser']) && isset($param['userIds'])) {
                    $query->orWhereHas('taskUser', function ($query) use ($param) {
                        $query->whereIn('user_id', $param['userIds'])
                            ->where('task_relation', 'join');
                    });
                }

                if (in_array('shared', $param['reportUser']) && isset($param['userIds'])) {
                    $query->orWhereHas('taskUser', function ($query) use ($param) {
                        $query->whereIn('user_id', $param['userIds'])
                            ->where('task_relation', 'shared');
                    });
                }
            });
        } else {
            if (isset($param['userIds'])) {
                $query = $query->whereIn('manage_user', $param['userIds']);
            }
        }

        if (isset($param['fieldSearchManageUser'])) {
            $query->whereIn('manage_user', $param['fieldSearchManageUser']);
        }

        if (isset($param['taskType'])) {
            switch ($param['taskType']) {
                case 'all': //全部
                    $query = $query;
                    break;
                case 'delay': //已超期
                    $this->buildDelayQuery($query);
                    break;
                case 'execute': //进行中
                    $query = $query->where('task_status', 0);
                    break;
                case 'complete': //已完成
                    $query = $query->where('task_status', 1);
                    break;
            }
        }

        //某天剩余未完成的任务
        if (isset($param['dateRemaind']) && $param['dateRemaind'] != '') {
            $query = $query->where('created_at', '<', $param['dateRemaind'] . ' 23:59:59')
                ->where(function ($query) use ($param) {
                    $query->where('complete_date', '>', $param['dateRemaind'] . ' 23:59:59')
                        ->orWhere('task_status', 0);
                });
        }

        //一个时间段内新增和完成的任务
        if (isset($param['dateRange']) && $param['dateRange'] != []) {
            $dateRange = [$param['dateRange'][0], array_pop($param['dateRange']) . ' 23:59:59'];

            $query = $query->where(function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange)
                    ->orWhereBetween('complete_date', $dateRange);
            });
        }

        return $query->count();
    }

    /**
     * 新版任务API
     */

    /**
     * [getMyTaskList 获取我的任务列表]
     *
     * @method 朱从玺
     *
     * @param  [array]         $params [查询条件]
     *
     * @return [object]                [查询结果]
     */
    public function getMyTaskList($params, $withRelation = true)
    {
        $defaultParams = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['end_date' => 'asc'],
            'search'   => [],
        ];

        $params = array_merge($defaultParams, $params);

        $query = $this->entity->select($params['fields']);

        if ($withRelation) {
            $query->with(['taskHasOneManager' => function ($query) {
                $query->select('user_id', 'user_name');
            }, 'taskHasManySonTask' => function ($query) {
                $query->select('parent_id')
                    ->selectRaw("count(*) as sonTotal")
                    ->groupBy('parent_id');
            }, 'taskHasManyCompleteSon' => function ($query) {
                $query->select('parent_id')
                    ->selectRaw("count(*) as completeSon")
                    ->where('task_status', 1)
                    ->groupBy('parent_id');
            }, 'taskUser' => function ($query) {
                $query->select('task_id', 'user_id')
                    ->where('task_relation', 'follow');
            }]);
        }

        if (isset($params["task_ids"])) {
            $query = $query->whereIn("id", $params["task_ids"]);
        }

        return $query->multiWheres($params['search'])
            ->parsePage($params['page'], $params['limit'])
            ->orderBy('task_status', 'asc')
            ->orders($params['order_by'])
            ->get();
    }

    public function getMyTaskListCount($params)
    {
        $query = $this->entity->newQuery();
        if (isset($params["task_ids"])) {
            $query = $query->whereIn("id", $params["task_ids"]);
        }

        return $query->multiWheres($params['search'])->count();
    }

    /**
     * [getUserTask 查询用户任务]
     *
     * @method getUserTask
     *
     * @param  [type]      $params [description]
     *
     * @return [type]              [description]
     */
    public function getUserTask($params)
    {
        $defaultParams = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['end_date' => 'asc'],
            'search'   => [],
        ];

        $params = array_merge($defaultParams, $params);

        $query = $this->entity->select($params['fields']);

        return $query->multiWheres($params['taskSearch'])
            ->parsePage($params['page'], $params['limit'])
            ->with('taskUser')
            ->orderBy('task_status', 'asc')
            ->orders($params['order_by'])
            ->get();
    }
    public function getManageTaskId($userId)
    {
        return $this->entity->select(['id'])->where('manage_user', $userId)->get();
    }
    public function taskCountSumPersent($taskId)
    {
        return $this->entity->where("parent_id", $taskId)->sum("progress");
    }

    public function taskCountNums($taskId)
    {
        return $this->entity->where("parent_id", $taskId)->count();
    }

    public function taskScheduleList($where, $calendar_begin, $calendar_end, $calendar_day)
    {

        $querey = $this->entity->select(["*"])
            ->wheres($where);

        if ($calendar_begin && $calendar_end) {
            $this->sch_start = date("Y-m-d", strtotime($calendar_begin));
            $this->sch_end   = date("Y-m-d", strtotime($calendar_end));
            $querey          = $querey->where(function ($query) {
                $query->where("end_date", "=", "0000-00-00")->where("start_date", "<=", $this->sch_end)
                    ->orWhere(function ($query) {
                        $query->where("end_date", ">=", $this->sch_end)->where("start_date", "<=", $this->sch_end);
                    })->orWhere(function ($query) {
                    $query->where("end_date", "<=", $this->sch_end)->where("end_date", ">=", $this->sch_start);
                });
            });
        }
        if ($calendar_day) {
            $this->temp = date("Y-m-d", strtotime($calendar_day));

            $querey = $querey->where(function ($query) {
                $query->where("end_date", "=", "0000-00-00")->where("start_date", "<=", $this->temp)
                    ->orWhere(function ($query) {
                        $query->where("end_date", ">=", $this->temp)->where("start_date", "<=", $this->temp);
                    });
            });
        }
        $querey->where("task_status", "!=", 1);
        return $querey->orderBy('id', 'desc')
            ->get()->toArray();
    }

    public function getTasksByWhere($where)
    {
        return $this->entity->select(["*"])
            ->wheres($where)
            ->get()->toArray();
    }

    public function getTaskScheduleByDate($start, $end, $where)
    {
        $this->sch_end   = $end;
        $this->sch_start = $start;

        return $this->entity->select(["start_date", "end_date"])
            ->wheres($where)
            ->where(function ($query) {
                $query->where("end_date", "=", "0000-00-00")->where("start_date", "<=", $this->sch_end)
                    ->orWhere(function ($query) {
                        $query->where("end_date", ">=", $this->sch_end)->where("start_date", "<=", $this->sch_end);
                    })->orWhere(function ($query) {
                    $query->where("end_date", "<=", $this->sch_end)->where("end_date", ">=", $this->sch_start);
                });
            })->orderBy('id', 'desc')->get()->toArray();
    }

    public function getTaskPortalTotal($param)
    {
        $param['search_id'] = isset($param['search_id']) ? $param['search_id'] : [];
        $default            = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity;

        return $query->wheres($param['search']) //前端搜索条件
            ->wheres($param['search_id'])->count();
    }

    public function getTaskPortallist($param)
    {
        $param['search_id'] = isset($param['search_id']) ? $param['search_id'] : [];
        $default            = [
            'fields'   => ['task_manage.*', 'user_name'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        $query = $query->select($param['fields']);
        $query = $query->leftJoin('user', function ($join) {
            $join->on("user.user_id", '=', 'task_manage.manage_user');
        });
        $query = $query
            ->wheres($param['search']) //前端搜索条件
            ->wheres($param['search_id']);
        $result = $query->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get()->toArray();

        return $result;
    }

    /**
     * 构建延期的查询对象
     * @param $query
     */
    private function buildDelayQuery($query)
    {
        $query->where(function ($query) {
            $query->where(function ($query) {
                $query->where('end_date', '<', date('Y-m-d'))
                    ->where('end_date', '!=', "0000-00-00")
                    ->where('task_status', 0);
            })->orWhere(function ($query) {
                $query->whereRaw('end_date < date_format(complete_date, "%y-%m-%d")')
                    ->where('task_status', 1);
            });
        });
    }

    /**
     * 构建负责人、参与人、共享人的查询对象
     * @param $userId
     * @param null|array $taskId
     * @param $query
     */
    private function buildRelationTaskQuery($userId, $taskId, $query)
    {
        $hasTaskId = is_null($taskId) ? false : true; // null时才不需要查询
        if ($userId && !$hasTaskId) {
            $query->where('manage_user', $userId);
        } else if (!$userId && $hasTaskId) {
            $query->whereIn('id', $taskId);
        } else if ($userId && $hasTaskId) {
            $query->where('manage_user', $userId)
                ->orWhereIn('id', $taskId);
        }
    }
}
