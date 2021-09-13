<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectQuestionEntity;
use DB;
/**
 * 项目问题  资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectQuestionRepository extends BaseRepository {

    private $user_id;

    public function __construct(ProjectQuestionEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取项目问题 列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getProjectQuestionList($param) {


        if (!isset($param["user_id"])) {
            return [];
        }
        $this->user_id = $param["user_id"];

        $default = [
            'fields' => ['project_question.*', 'user.user_name as user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['created_at' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity;
        if (isset($param['question_createtime']) && !empty($param['question_createtime'])) {
            $dateTime = json_decode($param['question_createtime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('question_createtime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }

        if (isset($param['question_endtime']) && !empty($param['question_endtime'])) {
            $dateTime = json_decode($param['question_endtime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('question_endtime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }

        //未提交的问题 创建者 和提出者才能看到

        $query = $query->where(function ($query) {
            $query->where('question_state', ">", "0")
                    ->orWhere(function ($query) {
                        $query->where("question_state", 0)
                        ->where(function ($query) {
                            $query->where("question_person", $this->user_id)
                            ->orWhere(function ($query) {
                                $query->where("question_creater", $this->user_id);
                            });
                        });
                    });
        });

        return $query->select($param['fields'])->leftJoin('user', function($join) {
                            $join->on("user.user_id", '=', 'project_question.question_doperson');
                        })->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    public function getProjectQuestionTotal($param) {


        if (!isset($param["user_id"])) {
            return 0;
        }
        $this->user_id = $param["user_id"];

        $default = [

            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity;

        if (isset($param['question_createtime']) && !empty($param['question_createtime'])) {
            $dateTime = json_decode($param['question_createtime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('question_createtime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }

        if (isset($param['question_endtime']) && !empty($param['question_endtime'])) {
            $dateTime = json_decode($param['question_endtime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('question_endtime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }

        //未提交的问题 创建者 和提出者才能看到

        $query = $query->where(function ($query) {
            $query->where('question_state', ">", "0")
                    ->orWhere(function ($query) {
                        $query->where("question_state", 0)
                        ->where(function ($query) {
                            $query->where("question_person", $this->user_id)
                            ->orWhere(function ($query) {
                                $query->where("question_creater", $this->user_id);
                            });
                        });
                    });
        });

        return $query->leftJoin('user', function($join) {
                            $join->on("user.user_id", '=', 'project_question.question_doperson');
                        })->wheres($param['search'])
                        ->count();
    }

    /**
     * 获取
     *
     * @param array where
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoProjectQuestionbyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }

    //删除对应条件的问题
    public function deleteProjectQuestion($data) {
        //创建人 + 状态立项0+提出者+ IDs
        // 状态结束5 + 提速者+IDs
        return $this->entity->whereRaw("question_id in  ('" . $data['question_id'] . "') and ((question_state = 0 and (question_creater = '" . $data['user_id'] . "' or question_person = '" . $data['user_id'] . "')) or (question_state =5 and question_person = '" . $data['user_id'] . "'))")->delete();
    }

    public function getOneProjectQuestion($where) {

        $result = $this->entity->select(['project_question.*', 'project_manager.manager_name as manager_name', 'project_task.task_name as task_name'])
                        ->leftJoin('project_manager', function($join) {
                            $join->on("project_manager.manager_id", '=', 'project_question.question_project');
                        })
                        ->leftJoin('project_task', function($join) {
                            $join->on("project_task.task_id", '=', 'project_question.question_task');
                        })->wheres($where)->get()->toArray();

        return $result;
    }

    public function projectQuestionCount($manager_id, $user_id = null) {
        $query = $this->entity->where("question_project", $manager_id);
        if ($user_id) {
            $this->user_id = $user_id;
            $query = $query->where(function ($query) {
                $query->where('question_state', ">", "0")
                        ->orWhere(function ($query) {
                            $query->where("question_state", 0)
                            ->where(function ($query) {
                                $query->where("question_person", $this->user_id)
                                ->orWhere(function ($query) {
                                    $query->where("question_creater", $this->user_id);
                                });
                            });
                        });
            });
        }
        return $query->count();
    }

    /**
     * 根据当前用户，获取所有有权限的项目的下属任务的各自的分组后数量
     * @param  [type] $user_id [description]
     * @param  $managerIds [项目id]
     * @return [type]          [description]
     */
    public function getQuestionCountGroupByProject($user_id, array $managerIds = null) {
        $query = $this->entity;
        if ($user_id) {
            $this->user_id = $user_id;
            $query = $query->where(function ($query) {
                $query->where('question_state', ">", "0")
                        ->orWhere(function ($query) {
                            $query->where("question_state", 0)
                            ->where(function ($query) {
                                $query->where("question_person", $this->user_id)
                                ->orWhere(function ($query) {
                                    $query->where("question_creater", $this->user_id);
                                });
                            });
                        });
            });
        }
        $query = $query->select(['question_project',DB::raw('COUNT(question_id) as question_count')])
        ->groupBy('question_project');

        if (!is_null($managerIds)) {
            $query->whereIn('question_project', $managerIds);
        }

        return $query->get()->toArray();
    }

}
