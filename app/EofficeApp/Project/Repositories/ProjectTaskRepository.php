<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectTaskEntity;
use DB;
use Illuminate\Support\Arr;
/**
 * 项目任务 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectTaskRepository extends BaseRepository
{

    private $tempId;

    public function __construct(ProjectTaskEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取项目任务列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getProjectTaskList($param)
    {
        $query = $this->buildProjectTaskList($param);

        $page = Arr::get($param, 'page', 0);
        $limit = Arr::get($param, 'limit', config('eoffice.pagesize'));
        return $query->parsePage($page, $limit)->get()->toArray();
    }

    //构建获取任务列表的query对象
    private function buildProjectTaskList($param, $query = null)
    {
        $default = [
            'fields'   => ['project_task.*', 'project_task.creat_time as ctime', 'project_manager.*', "user_name"],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['sort_id' => 'asc', "project_task.creat_time" => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        if (is_null($query)) {
            $query = $this->entity->newQuery();
        }
        $query->select($param['fields'])->leftJoin('project_manager', function ($join) {
            $join->on("project_task.task_project", '=', 'project_manager.manager_id');
        })->leftJoin('user', function ($join) {
            $join->on("project_task.task_persondo", '=', 'user.user_id');
        });
        if (isset($param['task_frontid']) && $param['task_frontid'] == "exist") {
            $query = $query->where("task_frontid", ">", "0");
        } else if (isset($param['task_frontid']) && $param['task_frontid'] == "un-exist") {
            $query = $query->where("task_frontid", "=", "0");
        }

        if (isset($param['task_mark']) && $param['task_mark'] == "un-sign") {
            $query = $query->where("task_mark", "=", "0");
        } else if (isset($param['task_mark']) && $param['task_mark'] == "sign") {
            $query = $query->where("task_mark", "=", "1");
        }
        if (isset($param['task_level']) && $param['task_level'] > 0) {
            $query = $query->where("task_level", "=", $param['task_level']);
        }
        // 关联获取子任务与执行人信息
        if (Arr::get($param, 'with_son_tasks')) {
            $query->with(['son_tasks' => function ($query) {
                $this->buildProjectTaskList([], $query);
            }]);
        }

        if (Arr::get($param, 'with_son_task_count')) {
            $query->withCount('son_tasks as son_task_count');
        }

//        if(isset($param['task_begintime'])&& !empty($param['task_begintime'])){
        //            $query = $query->where("task_begintime",">=",$param['task_begintime']);
        //        }
        //         if(isset($param['task_endtime'])&& !empty($param['task_endtime'])){
        //            $query = $query->where("task_endtime","<=",$param['task_endtime']);
        //        }

        if (isset($param['task_begintime']) && !empty($param['task_begintime'])) {
            $dateTime = json_decode($param['task_begintime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('task_begintime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }

        if (isset($param['task_endtime']) && !empty($param['task_endtime'])) {
            $dateTime = json_decode($param['task_endtime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('task_endtime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }
        if (isset($param['order_by']['sort_id']) && !empty($param['order_by']['sort_id'])) {
            $param['order_by'] = ['sort_id' => 'asc', "project_task.creat_time" => 'DESC'];
        }
        $query->wheres($param['search'])->orders($param['order_by']);

        return $query;
    }

    public function ProjectTaskList($id)
    {
        return $this->entity->where("task_project", $id)->get()->toArray();

    }

    public function getTemplateTaskList($param)
    {
        return $this->entity->wheres($param['search'])->get()->toArray();
    }

    public function getTemplateTaskListTotal($param)
    {
        return $this->entity->wheres($param['search'])->count();
    }

    public function getProjectTaskTotal($param)
    {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));

        $query = $this->entity;
        if (isset($param['task_frontid']) && $param['task_frontid'] == "exist") {
            $query = $query->where("task_frontid", ">", "0");
        } else if (isset($param['task_frontid']) && $param['task_frontid'] == "un-exist") {
            $query = $query->where("task_frontid", "=", "0");
        }

        if (isset($param['task_begintime']) && !empty($param['task_begintime'])) {
            $dateTime = json_decode($param['task_begintime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('task_begintime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }

        if (isset($param['task_endtime']) && !empty($param['task_endtime'])) {
            $dateTime = json_decode($param['task_endtime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('task_endtime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }

        if (isset($param['task_mark']) && $param['task_mark'] == "un-sign") {
            $query = $query->where("task_mark", "=", "0");
        } else if (isset($param['task_mark']) && $param['task_mark'] == "sign") {
            $query = $query->where("task_mark", "=", "1");
        }
        if (isset($param['task_level']) && $param['task_level'] > 0) {
            $query = $query->where("task_level", "=", $param['task_level']);
        }

        return $query->wheres($param['search'])
            ->count();
    }

    public function getProjectTaskFontList($param)
    {
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['task_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        return $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()->toArray();
    }

    /**
     * 获取项目任务详细
     *
     * @param array
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoProjectTaskbyWhere($where,$param=[])
    {
        $default = [
            'order_by' => ['task_frontid' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        //left join biao
        $query = $this->entity->select(['project_task.*', 'project_manager.manager_name', 'project_manager.manager_state', 'project_manager.manager_creater', 'project_manager.manager_person', 'project_manager.manager_examine', 'project_manager.manager_monitor'])->leftJoin('project_manager', function ($join) {
            $join->on("project_task.task_project", '=', 'project_manager.manager_id');
        })
        ->orders($param['order_by'])
        // ->orderBy("task_frontid", "desc")
        // ->orders(["task_frontid"=>"desc","creat_time"=>"DESC"])
        // ->orders(["creat_time"=>"DESC"])
        // ->orders(["task_frontid"=>"desc"])
        ->wheres($where);

        if (Arr::get($param, 'with_son_task_count')) {
            $query->withCount('son_tasks as son_task_count');
        }
        if (Arr::get($param, 'with_front_task')) {
            $query->with('front_task');
        }
        $result = $query->get()->toArray();
        return $result;
    }

    //模板任务 倒序（关联任务倒序）
    public function getTemplateTasks($where)
    {
        $result = $this->entity->select(['*'])->orderBy("task_frontid", "desc")->wheres($where)->get()->toArray();
        return $result;
    }

    public function getCurrentProjectTaskStatusByMangerId($user_id, $manager_id)
    {
        $this->tempId = $manager_id;
        return $this->entity->select(['project_status.relation_id'])->leftJoin('project_status', function ($join) {
            $join->on("project_task.task_id", '=', 'project_status.relation_id')->where("project_task.task_project", $this->tempId);
        })->where("project_status.participant", "$user_id")->where("project_status.type", "task")->where("project_status.remind_flag", "0")->count();
    }

    //获取任务个数
    public function projectCountSumPersent($manager_id)
    {
        return $this->entity->where("task_project", $manager_id)->where('tree_level', 1)->count();
    }

    // 获取任务个数，按项目id分组
    public function getTaskCountGroupByProject(array $managerIds = null, $wheres = [])
    {
        $query = $this->entity;
        $query = $query->select([
            'task_project',
            DB::raw('COUNT(task_id) as task_count'),
            DB::raw("sum(case when task_persent=100 then 1 else 0 end ) as complete_task_count"),
            DB::raw('SUM(task_persent) AS task_persent')
        ])->groupBy('task_project');
        $query->wheres($wheres);

        if (!is_null($managerIds)) {
            $query->whereIn('task_project', $managerIds);
        }

        return $query->get()->toArray();
    }

    // 获取全部任务的项目状态，按项目id分组
    public function getTaskStatusGroupByProject($user_id, array $managerIds = null)
    {
        // $this->tempId = $manager_id;
        // return $this->entity->select(['project_status.relation_id'])
        // ->leftJoin('project_status', function ($join) {
        //     $join->on("project_task.task_id", '=', 'project_status.relation_id')->where("project_task.task_project", $this->tempId);
        // })->where("project_status.participant", "$user_id")->where("project_status.type", "task")->where("project_status.remind_flag", "0")->count();

        $query = $this->entity;
        $query = $query->select(['task_project',DB::raw('COUNT(task_id) as count')])
        ->leftJoin('project_status', function ($join) {
            $join->on("project_task.task_id", '=', 'project_status.relation_id');
        })
        ->where("project_status.participant", "$user_id")
        ->where("project_status.type", "task")
        ->where("project_status.remind_flag", "0")
        ->groupBy('task_project');

        if (!is_null($managerIds)) {
            $query->whereIn('task_project', $managerIds);
        }

        return $query->get()->toArray();
    }

    //获取项目当前的进度
    public function projectPersent($manager_id)
    {

        return $this->entity->where("task_project", $manager_id)->where('tree_level', 1)->sum("task_persent");
    }

    /**
     * 获取父任务id
     * @param $taskIds int|array
     * @return array
     */
    public function getParentTaskIds($taskIds)
    {
        $taskIds = is_array($taskIds) ? $taskIds : [$taskIds];
        return $this->entity
            ->whereIn('task_id', $taskIds)
            ->pluck('parent_task_id')
            ->unique()
            ->filter()
            ->toArray();
    }
    
    public function getTemplateTask($taskId)
    {
        return $this->entity->where('task_complate', '>', 0)->find($taskId);
    }

}
