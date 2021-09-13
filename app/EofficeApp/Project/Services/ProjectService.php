<?php

namespace App\EofficeApp\Project\Services;
use App\EofficeApp\FormModeling\Repositories\FormModelingRepository;
use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\NewRepositories\ProjectRoleUserRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTeamRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;
use App\EofficeApp\Project\NewServices\ProjectService as NewProjectService;
use DB;
use Eoffice;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\Base\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Utils\Utils;
use Schema;
use Illuminate\Support\Arr;
/**
 * 项目管理服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class ProjectService extends BaseService {
    public function __construct(
    ) {
        $this->projectDiscussRepository      = 'App\EofficeApp\Project\Repositories\ProjectDiscussRepository';
        $this->projectDocumentRepository     = 'App\EofficeApp\Project\Repositories\ProjectDocumentRepository';
        $this->projectExamineRepository      = 'App\EofficeApp\Project\Repositories\ProjectExamineRepository';
        $this->projectManagerRepository      = 'App\EofficeApp\Project\Repositories\ProjectManagerRepository';
        $this->projectMonitorRepository      = 'App\EofficeApp\Project\Repositories\ProjectMonitorRepository';
        $this->projectQuestionRepository     = 'App\EofficeApp\Project\Repositories\ProjectQuestionRepository';
        $this->projectRoleRepository         = 'App\EofficeApp\Project\Repositories\ProjectRoleRepository';
        $this->projectTaskDiaryRepository    = 'App\EofficeApp\Project\Repositories\ProjectTaskDiaryRepository';
        $this->projectTaskRepository         = 'App\EofficeApp\Project\Repositories\ProjectTaskRepository';
        $this->projectTeamRepository         = 'App\EofficeApp\Project\Repositories\ProjectTeamRepository';
        $this->projectTemplateRepository     = 'App\EofficeApp\Project\Repositories\ProjectTemplateRepository';
        $this->projectStatusRepository       = 'App\EofficeApp\Project\Repositories\ProjectStatusRepository';
        $this->projectSupplementService       = 'App\EofficeApp\Project\Services\ProjectSupplementService';
        $this->userRepository                = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->attachmentService             = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->systemComboboxService         = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->formModelingRepository = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
        $this->calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';
    }

    /**
     * 设置监控用户
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function setProjectMonitor($data) {
        $data['monitor_creater'] = $data['user_id'];
        $data['creat_time'] = date("Y-m-d H:i:s", time());
        $projectData = array_intersect_key($data, array_flip(app($this->projectMonitorRepository)->getTableColumns()));
        $projectInfo = app($this->projectMonitorRepository)->infoProjectMonitorbyWhere(["monitor_id" => ["1", ">="]]);
        if (count($projectInfo) == 0) {
            $resultData = app($this->projectMonitorRepository)->insertData($projectData);
            $result = $resultData->monitor_id;
        } else {
            $result = app($this->projectMonitorRepository)->updateData($projectData, ['monitor_id' => $projectInfo[0]['monitor_id']]);
        }

        return $result;
    }

    //** 获取甘特图信息

    public function getProjectGantt($data) {

        //验证权限
        //验证权限
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];
        //验证权限
//        $status = $this->getProjectCheckPrivate($param);
//        if ($status !== true) {
//            return ['code' => ['0x000006', 'common']];
//        }

        // 获取当前项目下的任务，一级一级的取，从没有前置任务的任务开始，解决前置任务排序不对的问题
        // 结果数组
        $sourceResult = [];

        // 获取没有前置任务的任务
        $where = [
            'task_project' => [[$data['manager_id']], '='],
            "task_frontid" => [0],
            'tree_level' => [1, '=']
        ];
        // 按照 task_frontid 从小到大，每一级里面 creat_time 从小到大的方式push，最后再倒序，就是正确的结果了
        $param = [
            "order_by" => ['creat_time'=>'asc'],
        ];
        $sourceResultItem = app($this->projectTaskRepository)->infoProjectTaskbyWhere($where,$param);
        if(count($sourceResultItem)) {
            foreach ($sourceResultItem as $key => $value) {
                $sourceResult[] = $value;
                $taskId = isset($value["task_id"]) ? $value["task_id"] : "";
                $taskItemInfo = $this->getProjectGanttListInSteps($data['manager_id'],[$taskId]);
                if(count($taskItemInfo)) {
                    foreach ($taskItemInfo as $itemKey => $itemValue) {
                        $sourceResult[] = $itemValue;
                    }
                }
            }
        }
        $sourceResult = array_reverse($sourceResult);

        // $sourceResult = $this->getProjectGanttListInSteps($data['manager_id'],[0]);
        // $sourceResult = array_reverse($sourceResult);



        //拆解成固有格式
        $temp = [];
        $values = [];
        $destResult = [];
        $manager_name = "";
        foreach ($sourceResult as $result) {
            $manager_name = $result["manager_name"];
            $temp['name'] = $result["task_name"];
//            $temp['desc'] = "  ";
            $values["id"] = $result["task_id"];
            $values["from"] = $result["task_begintime"];
            $values["to"] = $result["task_endtime"];
            $values["customClass"] = "ganttRed";
            $values["label"] = $result["task_name"];
            $values["desc"] = $result["task_name"];
            $temp['values'][0] = $values;
            array_push($destResult, $temp);
        }
        return [
            "manager_name" => $manager_name,
            "task_gantt" => $destResult
        ];
    }


    /**
     * 获取当前项目下的任务，一级一级的取，从没有前置任务的任务开始，解决前置任务排序不对的问题
     * @param  [type] $managerId [description]
     * @param  [type] $frontId   [description]
     * @param  array  &$result   [description]
     * @return [type]            [description]
     */
    function getProjectGanttListInSteps($managerId,$frontId,&$result=[]) {
        $where = [
            'task_project' => [[$managerId], '='],
            "task_frontid" => [$frontId,'in']
        ];
        // 按照 task_frontid 从小到大，每一级里面 creat_time 从小到大的方式push，最后再倒序，就是正确的结果了
        $param = [
            "order_by" => ['creat_time'=>'asc'],
        ];
        $sourceResultItem = app($this->projectTaskRepository)->infoProjectTaskbyWhere($where,$param);
        $frontId = [];
        if(count($sourceResultItem)) {
            foreach ($sourceResultItem as $key => $value) {
                $result[] = $value;
                if(isset($value["task_id"])) {
                    $frontId[] = $value["task_id"];
                }
            }
            if(count($frontId)) {
                $this->getProjectGanttListInSteps($managerId,$frontId,$result);
            }
        }
        return $result;
    }

    /**
     * 获取监控用户
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectMonitor() {
        $result = app($this->projectMonitorRepository)->infoProjectMonitorbyWhere(["monitor_id" => ["1", ">="]]);

        $tempData = "";
        foreach ($result as $res) {
            $tempData = $res['monitor_person'];
        };

        $arr = explode(",", $tempData);

        $finalObj = [
            "total" => 0,
            "list" => []
        ];

        if (count($arr) == 0) {

            return $finalObj;
        }

        $arrName = app($this->userRepository)->getUsersNameByIds($arr);

        $temp = [];
        $resultObj = [];
        foreach ($arr as $k => $user) {
            $temp['user_id'] = $user;
            $temp['user_name'] = $arrName[$k];
            array_push($resultObj, $temp);
        }

        $finalObj = [
            "total" => count($arr),
            "list" => $resultObj
        ];

        return $finalObj;
    }

    /**
     * 设置用户审批权限
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function setProjectExamine($data) {
        $data['exam_creater'] = $data['user_id'];
        $data['creat_time'] = date("Y-m-d H:i:s", time());
        $where = [
            'exam_person' => [$data['exam_person'], '=']
        ];
        $projectInfo = app($this->projectExamineRepository)->infoProjectExaminebyWhere($where);
        $projectData = array_intersect_key($data, array_flip(app($this->projectExamineRepository)->getTableColumns()));
        if (count($projectInfo) == 0) {
            $resultData = app($this->projectExamineRepository)->insertData($projectData);
            $result = $resultData->exam_id;
        } else {
            $result = app($this->projectExamineRepository)->updateData($projectData, ['exam_person' => $projectInfo[0]['exam_person']]);
        }
        return $result;
    }

    /**
     * 获取某个用户审批权限
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectExamineByUserId($data) {

        $where = [
            'exam_person' => [[$data['user_id']], '=']
        ];

        return app($this->projectExamineRepository)->infoProjectExaminebyWhere($where);
    }

    /**
     * 获取有项目审核的用户
     *
     * @todo 通过用户user_id获取部门ID
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectExamineUsers($data) {
        //这里获取部门ID
        $userInfo = app($this->userRepository)->getUserAllData($data['user_id']);
        $deptId = $userInfo['userHasOneSystemInfo']['dept_id'];

        $resultData = app($this->projectExamineRepository)->getProjectExamineUsers($deptId);
        $users = [];
        foreach ($resultData as $v) {
            $users[] = $v['exam_person'];
        }

        $finalObj = [
            "total" => 0,
            "list" => []
        ];

        if (count($users) == 0) {

            return $finalObj;
        }

        $arrName = app($this->userRepository)->getUsersNameByIds($users);

        $temp = [];
        $resultObj = [];
        foreach ($users as $k => $user) {
            $temp['user_id'] = $user;
            $temp['user_name'] = $arrName[$k];
            array_push($resultObj, $temp);
        }

        $finalObj = [
            "total" => count($users),
            "list" => $resultObj
        ];

        return $finalObj;
    }

    /**
     * 增加项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectRole($data) {
        $data['role_creater'] = $data['user_id'];
        $data['creat_time'] = date("Y-m-d H:i:s", time());

        $projectData = array_intersect_key($data, array_flip(app($this->projectRoleRepository)->getTableColumns()));
        $result = app($this->projectRoleRepository)->insertData($projectData);
        return $result->role_id;
    }

    //获取最大的role_order

    public function getMaxRoleOrder() {
        $result = app($this->projectRoleRepository)->getMaxOrder();
        $maxOrder = 0;
        if (count($result)) {
            $maxOrder = $result[0]['role_order'];
        }

        return max($maxOrder, 0);
    }

    public function getMaxOrderByType($type) {
        switch ($type) {
            case "role":
                $content = $this->getMaxRoleOrder();
                break;
            case "discuss":
                $content = $this->getMaxOrder();
                break;
            case "type":
                $content = $this->getMaxTypeOrder();
                break;
            default:
                $content = 1;
                break;
        }

        return $content;
    }

    /**
     * 编辑项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectRole($data, $role_id) {

        $where = [
            'role_id' => [[$role_id], '=']
        ];
        $projectInfo = app($this->projectRoleRepository)->infoProjectRolebyWhere($where);
        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $projectData = array_intersect_key($data, array_flip(app($this->projectRoleRepository)->getTableColumns()));
        $result = app($this->projectRoleRepository)->updateData($projectData, ['role_id' => $role_id]);
        return $result;
    }

    /**
     * 删除项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectRole($data, $role_id) {
        //项目时，则不能删除项目角色。
        //删除之前判断是否存在
        $projectInfo = app($this->projectRoleRepository)->infoProjectRolebyWhere(['role_id' => [[$role_id], '=']]);
        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        //判断是否被项目占用
        $projectManagerInfo = app($this->projectTeamRepository)->infoProjectTeambyWhere(['team_role' => [[$role_id], '=']]);
        if (count($projectManagerInfo) > 0) {
            return ['code' => ['0x036004', 'project']]; //项目角色被占用 不可以删除
        }

        return app($this->projectRoleRepository)->deleteById($role_id);
    }

    /**
     * 获取全部项目角色
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getAllProjectRole($data) {

        return $this->response(app($this->projectRoleRepository), 'getTotal', 'getAllProjectRole', $this->parseParams($data));
    }

    // 获取某一个角色
    public function getOneProjectRole($role_id) {
        $where = [
            'role_id' => [[$role_id], '=']
        ];
        $return = app($this->projectRoleRepository)->infoProjectRolebyWhere($where);
        return $return[0];
    }

    /**
     * 增加项目模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectTemplate($data) {
        $data['template_creater'] = $data['user_id'];
        $data['creat_time'] = date("Y-m-d H:i:s", time());
        $projectData = array_intersect_key($data, array_flip(app($this->projectTemplateRepository)->getTableColumns()));
        $result = app($this->projectTemplateRepository)->insertData($projectData);
        return $result->template_id;
    }

    /**
     * 编辑项目模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectTemplate($data, $template_id) {
        $where = [
            'template_id' => [[$template_id], '=']
        ];
        $projectInfo = app($this->projectTemplateRepository)->infoProjectTemplatebyWhere($where);
        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $projectData = array_intersect_key($data, array_flip(app($this->projectTemplateRepository)->getTableColumns()));
        $result = app($this->projectTemplateRepository)->updateData($projectData, ['template_id' => $template_id]);
        return $result;
    }

    /**
     * 删除项目模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectTemplate($template_id) { //manager_template
        //删除之前判断是否存在
        $projectInfo = app($this->projectTemplateRepository)->infoProjectTemplatebyWhere(['template_id' => [[$template_id], '=']]);
        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        //不能删除占用项目模板。
        $projectManagerInfo = app($this->projectManagerRepository)->infoProjectManagerbyWhere(['manager_template' => [[$template_id], '=']]);
        if (count($projectManagerInfo) > 0) {
            return ['code' => ['0x036004', 'project']]; //项目角色被占用 不可以删除
        }
        // 删除对应的任务
        app($this->projectTaskRepository)->deleteByWhere(['task_complate' => [[$template_id], '=']]);
        // 删除相关的模板
        return app($this->projectTemplateRepository)->deleteById($template_id);
    }

    /**
     * 获取全部项目类型+模板
     *
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getAllProjectTemplate($data) {
        //获取所有的类型
        $resultTypes = app($this->systemComboboxService)->getProjectTypeAll();

        //获取所有的模板
        $resultTemplates = app($this->projectTemplateRepository)->getAll($this->parseParams($data));
        // dd($resultTypes);
        //数据重组
        $resultList = [];
        foreach ($resultTypes as $vType) {
            $typeId = $vType['field_value'];
            $resultList[$typeId] = $vType;
            $resultList[$typeId]["template_list"] = [];
            foreach ($resultTemplates as $vTemplate) {
                if ($vTemplate['template_type'] == $typeId) {
                    array_push($resultList[$typeId]["template_list"], $vTemplate);
                }
            }
            $resultList[$typeId]["count"] = count($resultList[$typeId]["template_list"]);
        }

        return array_merge([], $resultList);
    }

    public function getAllTemplate($data) {
        return $this->response(app($this->projectTemplateRepository), 'getTotal', 'getAllProjectTemplate', $this->parseParams($data));
    }

    //获取一个模板内容
    public function getOneProjectTemplate($template_id) {
        $where = [
            'template_id' => [[$template_id], '=']
        ];
        $return = app($this->projectTemplateRepository)->infoProjectTemplatebyWhere($where);
        return $return ? $return[0] : [];
    }

    /**
     * 增加项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectTask($data) {
        $this->filterNull($data);
        // 项目中添加任务 需要
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['task_project']
        ];
        //验证权限
        $status = $this->getProjectTaskPrivate($param);

        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";
        $data['task_creater'] = $data['user_id'];
        $data['creat_time'] = isset($data['flow_creat_time']) ? $data['flow_creat_time'] : date("Y-m-d H:i:s", time());
        $data['task_persent'] = 0;
        $data['task_complate'] = 0;
        $data['sort_id'] = isset($data['sort_id']) ? $data['sort_id'] : 0;
        $data['task_frontid'] = isset($data['task_frontid']) ? $data['task_frontid'] : 0;
        $data['task_level'] = isset($data['task_level']) ? $data['task_level'] : 1;
        $projectData = array_intersect_key($data, array_flip(app($this->projectTaskRepository)->getTableColumns()));
        $result = app($this->projectTaskRepository)->insertData($projectData);
//        MessageManager::sendProjectNewTaskReminder($result);
        $task_id = $result->task_id;
        ProjectLogManager::getIns($data['user_id'], $data['task_project'])->taskAddLog($result->task_name, $task_id);
        $attachments = isset($data['attachments']) ? $data['attachments'] : "";
        if ($attachments) {
            app($this->attachmentService)->attachmentRelation("project_task", $task_id, $attachments);
        }
          $projectStatusData = [
            "type" => "task",
            "relation_id" => $task_id,
            "manager_id" => $result->task_project,
            "user_id" => $data['user_id'],
            ];
        $this->insertProjectStatus($projectStatusData);
        $this->syncParentTaskProgress($task_id);//更新父任务进度
        return $result;
    }

    /**
     * 编辑项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectTask($data, $task_id) {
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";

        $where = [
            'task_id' => [[$task_id], '=']
        ];
        $projectInfo = app($this->projectTaskRepository)->infoProjectTaskbyWhere($where);
        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $task = app($this->projectTaskRepository)->getDetail($task_id);
        if (!$task) {
            return ['code' => ['0x036024', 'project']];
        }
        $data['task_project'] = Arr::get($projectInfo, '0.task_project', 0);//覆盖前端传入的项目id

        // 项目中添加任务 需要
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['task_project']
        ];
        //验证权限
        $status = $this->getProjectTaskPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $data['task_complate'] = 0;
        $projectData = array_intersect_key($data, array_flip(app($this->projectTaskRepository)->getTableColumns()));

        $attachments = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_task", $task_id, $attachments);

           $projectStatusData = [
            "type" => "task",
            "relation_id" => $task_id,
            "manager_id" => $data['task_project'],
            "user_id" => $data['user_id'],
            ];
        $this->insertProjectStatus($projectStatusData);
        $task->fill($projectData);
        $result = $this->saveWithLog($task, 'task', $data['user_id'], $data['task_project']);
        return $result;
    }

    public function addProjectTemplateTask($data) {

        // 模板添加任务 不需要执行人
        $data['task_persondo'] = isset($data['task_persondo']) && !empty($data['task_persondo']) ? $data['task_persondo'] : "";
        // 项目中添加任务 需要
        $data['task_project'] = 0;
        $data['task_creater'] = $data['user_id'];
        $data['creat_time'] = date("Y-m-d H:i:s", time());
        $data['task_persent'] = 0;
        $data['sort_id'] =isset($data['sort_id'])?$data['sort_id']:0;
        $projectData = array_intersect_key($data, array_flip(app($this->projectTaskRepository)->getTableColumns()));
        $result = app($this->projectTaskRepository)->insertData($projectData);
        return $result->task_id;
    }

    /**
     * 编辑项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectTemplateTask($data, $task_id) {
        $projectInfo = app($this->projectTaskRepository)->getTemplateTask($task_id);
        if (!$projectInfo) {
            return ['code' => ['0x000006', 'common']];
        }
        // 模板添加任务 不需要执行人
        $data['task_persondo'] = isset($data['task_persondo']) && !empty($data['task_persondo']) ? $data['task_persondo'] : "";
        // 项目中添加任务 需要
        $data['task_project'] = 0;
        $projectData = array_intersect_key($data, array_flip(app($this->projectTaskRepository)->getTableColumns()));
        return app($this->projectTaskRepository)->updateData($projectData, ['task_id' => $task_id]);
    }

    /**
     * 删除模板任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectTemplateTask($data) {
        //不允许删除非模板任务的任务
        if (!$data['task_complate']) {
            return ['code' => ['0x000006', 'common']];
        }
        $destroyIds = explode(",", $data['task_id']);
        $where = [
            'task_id' => [$destroyIds, 'in'],
            'task_complate' => [[$data['task_complate']], '=']
        ];
        return app($this->projectTaskRepository)->deleteByWhere($where);
    }

    /**
     * 删除项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectTask($data) {
        $taskId = Arr::get($data, 'task_id');
        $projectInfo = app($this->projectTaskRepository)->getDetail($taskId);
        if (!$projectInfo) {
            return ['code' => ['0x000006', 'common']];
        }
        $data['task_project'] = $projectInfo->task_project;//覆盖前端传入的项目id

        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['task_project']
        ];
        //验证权限
        $status = $this->getProjectTaskPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $destroyIds = explode(",", $data['task_id']);

        //存在子任务不准删除
        $hasSonTask = app($this->projectTaskRepository)->entity->whereIn('parent_task_id', $destroyIds)->exists();
        if ($hasSonTask) {
            return ['code' => ['0x036001', 'project']];
        }

        foreach ($destroyIds as $delId) {
            $delData = [
                "entity_table" => "project_task",
                "entity_id" => $delId
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }

        //删除任务应该删除所有的任务处理记录
        $projectTaskDiaryDels = [
            "taskdiary_task" => [$destroyIds, 'in'],
            "taskdiary_project" => [$data['task_project']]
        ];

        $tempRow = app($this->projectTaskDiaryRepository)->infoProjectTaskDiarybyWhere($projectTaskDiaryDels);
        $taskDirays = [];
        foreach ($tempRow as $temp) {

            $delData = [
                "entity_table" => "project_task_diary",
                "entity_id" => $temp["taskdiary_id"],
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }

        app($this->projectTaskDiaryRepository)->deleteByWhere($projectTaskDiaryDels);

        // 删掉一个任务的时候，判断有没有子，把他的子任务的前置信息删掉
        $where = [
            'task_project' => [[$data['task_project']], '='],
            "task_frontid" => [$destroyIds,'in']
        ];
        $param = [
            "order_by" => ['creat_time'=>'asc'],
        ];
        $sourceResultItem = app($this->projectTaskRepository)->infoProjectTaskbyWhere($where,$param);
        if(count($sourceResultItem)) {
            foreach ($sourceResultItem as $key => $value) {
                if(isset($value['task_project']) && $value['task_project'] && isset($value['task_id']) && $value['task_id']) {
                    $where = [
                        "task_project" => [$value['task_project']],
                        "task_id" => [$value['task_id']]
                    ];
                    app($this->projectTaskRepository)->updateData(["task_frontid" => "0"], $where);
                }
            }
        }

        $where = [
            'task_id' => [$destroyIds, 'in'],
            'task_project' => [[$data['task_project']], "="]
        ];
        //获取父级id，用于更新进度
        $parentTaskIds = app($this->projectTaskRepository)->getParentTaskIds($destroyIds);
        $logManager = $this->initDeleteLog('task', $data['user_id'], $destroyIds, $data['task_project']);
        $res = app($this->projectTaskRepository)->deleteByWhere($where);
        $res && $logManager && $logManager->storageFillData();
        $this->syncTaskProgress($parentTaskIds);
        CacheManager::cleanProjectReportCache();
        return $res;
    }

    /**
     * 获取任务详细(by task_id)
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectTemplateTask($data) {
        $where = [
            'task_id' => [[$data['task_id']], '='],
            'task_complate' => [[$data['task_complate']], '=']
        ];
        $return = app($this->projectTaskRepository)->infoProjectTaskbyWhere($where);
        $result = [];
        if (isset($return[0])) {
            $return[0]["task_creater_name"] = app($this->userRepository)->getUserName($return[0]['task_creater']); //222
            $result = $return[0];
        }
        return $result;
    }

    /**
     * 获取任务详细(by task_id)
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectTask($data) {
        $where = [
            'task_id' => [[$data['task_id']], '=']
        ];
        $return = app($this->projectTaskRepository)->infoProjectTaskbyWhere($where);
        if (count($return) < 1) {
            return ['code' => ['0x000006', 'common']];
        }
        $data['task_project'] = Arr::get($return, '0.task_project');

        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['task_project']
        ];
        //验证权限
        $status = $this->getProjectTaskSelects($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $where = [
            'task_id' => [[$data['task_id']], '=']
        ];
        $return = app($this->projectTaskRepository)->infoProjectTaskbyWhere($where, [
            'with_son_task_count' => true,
            'with_front_task' => true,
        ]);

        $temp = [];
        foreach ($return as $ret) {
            $ret['task_persondo_name'] = app($this->userRepository)->getUserName($ret['task_persondo']);
            $ret['task_creater_name'] = app($this->userRepository)->getUserName($ret['task_creater']);
            // 废弃，和前端moment计算的不一样，改成在前端计算--20180326-dp
            // $ret['diffTime'] = (strtotime($ret['task_endtime']) - strtotime($ret['task_begintime'])) / 86400;

            $temp1 = app($this->systemComboboxFieldRepository)->getNameByValue($ret['task_level'], "PROJECT_PRIORITY");
            $ret['task_level_name'] = $temp1["field_name"];

            $ret['task_frontid_name'] = "";
            if ($ret['task_frontid']) {
                $ret['task_frontid_name'] = Arr::get($ret, 'front_task.task_name', '');
            }

            $temp[] = $ret;
        }

        if (isset($temp[0])) {
            $temp[0]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'project_task', 'entity_id' => $data['task_id']]);
            return $temp[0];
        }

        return $temp;
    }

    //任务级别
    public function taskLevelToName($taskLevel) {
        $return = "";
        switch ($taskLevel) {
            case 1:
                $return = trans("project.secondary");
                break;
            case 2:
                $return = trans("project.commonly");
                break;
            case 3:
                $return = trans("project.important");
                break;
            case 4:
                $return = trans("project.very_important");
                break;
            default:
                break;
        }

        return $return;
    }

    //任务优先级
    public function taskPriorityToName($taskLevel) {
        $return = "";
        switch ($taskLevel) {
            case 1:
                $return = trans("project.very_low");
                break;
            case 2:
                $return = trans("project.low");
                break;
            case 3:
                $return = trans("project.in");
                break;
            case 4:
                $return = trans("project.high");
                break;
            case 5:
                $return = trans("project.higher");
                break;
            default:
                break;
        }

        return $return;
    }

    /**
     * 获取模板的任务列表 by temp_id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectTaskListbyTemplateId($data, $id) {
        $params = $this->parseParams($data);
        //禁止传入task_complate=0获取所有的任务列表
        $taskComplate = Arr::get($params, 'search.task_complate.0');
        if ($taskComplate < 1) {
            return ['code' => ['0x000006', 'common']];
        }
        return $this->response(app($this->projectTaskRepository), 'getProjectTaskTotal', 'getProjectTaskList', $params);
    }

    public function getAllTaskByTemplateId($template_id) {

        $param = [
            "search" => [
                'task_complate' => [[$template_id], '=']
            ]
        ];

        return $this->response(app($this->projectTaskRepository), 'getTemplateTaskListTotal', 'getTemplateTaskList', $param);
    }

    public function getProjectFontTask($type, $id, $data) {
        if ($id < 1) {
            return ['code' => ['0x000006', 'common']];
        }
        $data = $this->parseParams($data);
        $task = isset($data['task_id']) && $data['task_id'] > 0 ? $data['task_id'] : 0;
        $searchTaskId = (isset($data["search"]) && isset($data["search"]["task_id"])) ? $data["search"]["task_id"][0]:false;
        $param = [];
        if(isset($data["search"])) {
            $param["search"] = $data["search"];
        } else {
            $param["search"] = [];
        }
        switch ($type) {
            case "manager":
                // $param = [
                //     "search" => [
                //         'task_project' => [[$id], '='],
                //         'task_id' => [[$task], '!='],
                //     ]
                // ];
                $param["search"]["task_project"] = [[$id], '='];
                if($searchTaskId === false) {
                    $param["search"]["task_id"] = [[$task], '!='];
                }

                break;
            case "template":
                // $param = [
                //     "search" => [
                //         'task_complate' => [[$id], '='],
                //         'task_id' => [[$task], '!='],
                //     ]
                // ];
                $param["search"]["task_complate"] = [[$id], '='];
                $param["search"]["task_id"] = [[$task], '!='];
                break;
            default:
                return ['code' => ['0x000006', 'common']];
        }

        $param["page"] = isset($data["page"]) && $data["page"] ? $data["page"] : "";
        $param["limit"] = isset($data["limit"]) && $data["limit"] ? $data["limit"] : "";

        $data = $this->response(app($this->projectTaskRepository), 'getTotal', 'getProjectTaskFontList', $param);
        foreach ($data['list'] as $key => $item) {
            $data['list'][$key]['task_name'] = str_pad($item['task_name'], ($item['tree_level'] - 1) * 3 + strlen($item['task_name']), '　', STR_PAD_LEFT);
        }
        return $data;
    }

    //项目模板新建任务处 展示任务执行人
    public function getPersonDo($user_id) {
        $result = [];
        $temp = [];

        $arrName = app($this->userRepository)->getUsersNameByIds([$user_id]);
        foreach ($arrName as $k => $v) {
            $temp['user_name'] = $v;
            $temp['user_id'] = $user_id;
            array_push($result, $temp);
        }

        $finalData = [
            'list' => $result,
            'total' => count($result)
        ];

        return $finalData;
    }

    /**
     * 获取任务列表  by project_id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectTaskListbyProjectId($data, $manager_id) {
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $manager_id
        ];
        //验证权限
        $status = $this->getProjectTaskSelects($param);

        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }
        $params = $this->parseParams($data);
        $params['search']['tree_level'] = [1, '='];
        $temp = $this->response(app($this->projectTaskRepository), 'getProjectTaskTotal', 'getProjectTaskList', $params);
        $temp['list'] = $this->handleSonTaskList($temp['list']);
        $list = [];
        //获取这个人
        $taskStatusList = $this->getCurrentListStatusByUserId($data['user_id'], "task");

        foreach ($temp["list"] as $info) {
            $info["task_read_flag"] = 0;
            if (in_array($info["task_id"], $taskStatusList)) {
                $info["task_read_flag"] = 1;
            }
            $list[] = $info;
        }


        return [
            "total" => $temp["total"],
            "list" => $list
        ];
    }

    //将子任务处理到同层
    private function handleSonTaskList($tasks)
    {
        $newTask = [];
        foreach ($tasks as $key => $task) {
            if (Arr::has($task, 'son_tasks')) {
                $task['son_task_count'] = 0;
                $sonTasks = $task['son_tasks'];
                $task['son_task_count'] = count($sonTasks);
                unset($task['son_tasks']);
                array_push($newTask, $task);
                foreach ($sonTasks as $sonTask) {
                    array_push($newTask, $sonTask);
                }
            } else {
                array_push($newTask, $task);
            }
            unset($tasks[$key]);
        }
        return $newTask;
    }

    // 任务处理 --
    /**
     * 增加项目任务处理
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectTaskDiary($data) {//11112
        $data['taskdiary_curtime'] = date("Y-m-d H:i:s", time());
        $data['discuss_person'] = $data['user_id'];

        $projectData = array_intersect_key($data, array_flip(app($this->projectTaskDiaryRepository)->getTableColumns()));
        $result = app($this->projectTaskDiaryRepository)->insertData($projectData);


        $taskId = $result->taskdiary_id;
        $attachmentObj = isset($data['attachments']) ? $data['attachments'] : "";
        if ($attachmentObj) {
            app($this->attachmentService)->attachmentRelation("project_task_diary", $taskId, $attachmentObj);
        }

        $projectStatusData = [
            "type" => "task",
            "relation_id" => $data["taskdiary_task"],
            "manager_id" => $data["taskdiary_project"],
            "user_id" => $data['user_id'],
        ];
        $this->insertProjectStatus($projectStatusData);
        return $taskId;
    }

    public function modifyProjectTaskDiaryProcess($taskId, $data) {
        //修改当前任务的
        $task_persent = isset($data['task_persent']) && !empty($data['task_persent']) ? $data['task_persent'] : "0";
        //变更的任务书
        $task_modify_task_persent = isset($data['task_modify_task_persent']) && !empty($data['task_modify_task_persent']) ? $data['task_modify_task_persent'] : "0";
        $hasSonTask = app($this->projectTaskRepository)->entity->where('parent_task_id', $taskId)->exists();
        if ($hasSonTask) {
            return ['code' => ['0x036001', 'project']];
        }
        if ($task_persent >= 0 && $task_persent <= 100) {
            $projectData = [
                'task_persent' => $task_persent
            ];
            $task = app($this->projectTaskRepository)->getDetail($taskId);
            if (!$task) {
                return ['code' => ['0x000006', 'common']];
            }
            $task->fill($projectData);
            $this->saveWithLog($task, 'task', $data['user_id'], $task['task_project']);
            $logData = [
                'log_content' => trans("project.modify_task_id_schedule",["task_id" => $taskId,"task_persent" => $task_modify_task_persent]),
                'log_type' => 1,
                'log_creator' => $data['user_id'],
                'log_time' => date('Y-m-d H:i:s'),
                'log_ip' => getClientIp(),
                'log_relation_table' => 'project_task',
            ];

            add_system_log($logData);
            $projectStatusData = [
                "type" => "task",
                "relation_id" => $data["taskdiary_task"],
                "manager_id" => $data["taskdiary_project"],
                "user_id" => $data['user_id'],
            ];
            $this->insertProjectStatus($projectStatusData);
            $parentTasks = $this->syncParentTaskProgress($taskId, true);
            return [
                'task_id' => $taskId,
                'parent_task' => Arr::get($parentTasks, '0', [])
            ];
        }
        //记录本次修改的日志


        return $taskId;
    }

    /**
     * 更新父任务的进度
     * @param $taskIds int|array
     * @param bool $withParentTaskInfo
     * @return array
     */
    public function syncParentTaskProgress($taskIds, $withParentTaskInfo = false)
    {
        $parentTaskIds = app($this->projectTaskRepository)->getParentTaskIds($taskIds);
        return $this->syncTaskProgress($parentTaskIds, $withParentTaskInfo);
    }

    /**
     * 更新任务的进度
     * @param $taskIds int|array
     * @param bool $withParentTaskInfo
     * @return array
     */
    public function syncTaskProgress($taskIds, $withParentTaskInfo = false)
    {
        $taskIds = is_array($taskIds) ? $taskIds : [$taskIds];
        if ($taskIds) {
            $progresses = app($this->projectTaskRepository)->entity
                ->whereIn('parent_task_id', $taskIds)
                ->selectRaw(\DB::raw('avg(`task_persent`) as task_persent,parent_task_id'))
                ->groupBy('parent_task_id')
                ->pluck('task_persent', 'parent_task_id');

            $tasks = app($this->projectTaskRepository)->entity->whereIn('task_id', $taskIds)->get();
            foreach ($tasks as $task) {
                $taskId = $task->task_id;
                $progress = $progresses->get($taskId);
                $progress = $progress ? floor($progress) : 0;
                $task->fill([
                    'task_persent' => $progress
                ])->save();
            }

            if ($withParentTaskInfo) {
                return app($this->projectTaskRepository)->entity->
                    whereIn('task_id', $taskIds)
                    ->select('task_persent', 'task_id', 'task_name')
                    ->get()
                    ->toArray();
            }
        }

        return [];
    }

    public function editProjectTaskDiary($taskdiary_id, $data) {
        $where = [
            'taskdiary_id' => [[$taskdiary_id], '='],
            'taskdiary_creater' => [[$data['user_id']], '=']
        ];
        $projectInfo = app($this->projectTaskDiaryRepository)->infoProjectTaskDiarybyWhere($where);
        $oldTaskDiary = Arr::get($projectInfo, '0');
        if (!$oldTaskDiary || !$this->testTime($oldTaskDiary['taskdiary_curtime'])) {
            return ['code' => ['0x000017', 'common']];
        }

        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        }

        $attachmentObj = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_task_diary", $taskdiary_id, $attachmentObj);

        $projectStatusData = [
            "type" => "task",
            "relation_id" => $data["taskdiary_task"],
            "manager_id" => $data["taskdiary_project"],
            "user_id" => $data['user_id'],
        ];
        $this->insertProjectStatus($projectStatusData);



        $projectData = array_intersect_key($data, array_flip(app($this->projectTaskDiaryRepository)->getTableColumns()));
        return app($this->projectTaskDiaryRepository)->updateData($projectData, ['taskdiary_id' => $taskdiary_id]);
    }

    public function getOneProjectTaskDiary($taskdiary_id) {
        $where = [
            'taskdiary_id' => [$taskdiary_id, "="]
        ];
        $resultData = app($this->projectTaskDiaryRepository)->getTaskDiarybyWhere($where);
        if ($resultData[0]["task_diary_replyid"] > 0) {
            $where2 = [
                'taskdiary_id' => [$resultData[0]["task_diary_replyid"], "="]
            ];
            $resultData2 = app($this->projectTaskDiaryRepository)->getTaskDiarybyWhere($where2);
            $resultData[0]["reply"] = (Object) $resultData2[0];
        }

        return $resultData[0];
    }

    // 新建任务讨论
    public function replyProjectTaskDiary($data) {

        if (!isset($data['task_diary_replyid'])) {
            $data['task_diary_replyid'] = 0;
        }
        $data['taskdiary_curtime'] = date("Y-m-d H:i:s", time());
        $projectData = array_intersect_key($data, array_flip(app($this->projectTaskDiaryRepository)->getTableColumns()));
        $result = app($this->projectTaskDiaryRepository)->insertData($projectData);
        $taskdiary_id = $result->taskdiary_id;
        $attachmentObj = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_task_diary", $taskdiary_id, $attachmentObj);
        $projectStatusData = [
            "type" => "task",
            "relation_id" => $data["taskdiary_task"],
            "manager_id" => $data["taskdiary_project"],
            "user_id" => $data['taskdiary_creater'],
        ];
        $this->insertProjectStatus($projectStatusData);

        //发送消息
        $projectId = $data["taskdiary_project"];
        $projectInfo = $this->getOneProject($projectId, $data['taskdiary_creater']);
        $projectName = $projectInfo["manager_name"];
        // 提醒对象：项目团队成员、项目负责人、监控人
        $smsToUsers = $this->remindUsers(
            $projectInfo['manager_state'],
            $projectInfo['team_persons'],
            $projectInfo['manager_persons'],
            $projectInfo['manager_monitors']
        );

        $smsToUsers = array_unique($smsToUsers);
        $sendData['remindMark']   = 'project-taskComment';
        $sendData['toUser']       = $smsToUsers;
        $sendData['contentParam'] = ['projectName' => $projectName];
        $sendData['stateParams']  = ['task_id' => $data["taskdiary_task"],'manager_id' => $projectId];
        // $sendData['stateParams']  = ['manager_id' => $projectId];
        Eoffice::sendMessage($sendData);

        return $result;
    }

    // 项目团队成员,项目负责人,监控人 任务反馈提醒的权限处理
    private function remindUsers($managerState, $teamPersons, $mangerPersons, $monitors) {
        $remindUsers = $mangerPersons;
        if (in_array($managerState, [4, 5])) {
            $remindUsers = array_merge($remindUsers, $teamPersons);
        }

        if (in_array($managerState, [4, 5])) {
            $remindUsers = array_merge($remindUsers, $monitors);
        }
        return $remindUsers;
    }

    /**
     * 删除项目任务
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectTaskDiary($taskdiary_id, $data = null) { //discuss_readtime为空
        $where = [
            'taskdiary_id' => [$taskdiary_id],
            'taskdiary_creater' => [[$data['user_id']], '=']
        ];
        $projectInfo = app($this->projectTaskDiaryRepository)->infoProjectTaskDiarybyWhere($where);
        $oldTaskDiary = Arr::get($projectInfo, '0');
        if (!$oldTaskDiary || !$this->testTime($oldTaskDiary['taskdiary_curtime'])) {
            return ['code' => ['0x000017', 'common']];
        }

        $delData = [
            "entity_table" => "project_task_diary",
            "entity_id" => $taskdiary_id
        ];
        app($this->attachmentService)->deleteAttachmentByEntityId($delData);


        return app($this->projectTaskDiaryRepository)->deleteById($taskdiary_id);
    }

    /**
     * 获取任务处理 详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
//    public function getProjectTaskDiaryList($data) {
//        return $this->response(app($this->projectTaskDiaryRepository), 'getProjectTaskDiaryTotal', 'getProjectTaskDiaryList', $this->parseParams($data));
//    }

    public function getProjectTaskDiaryList($data, $task_id, $user_id) {

        $data['taskdiary_task'] = $task_id;
        $resultData = $this->response(app($this->projectTaskDiaryRepository), 'getProjectTaskDiaryTotal', 'getProjectTaskDiaryList', $this->parseParams($data));

        foreach ($resultData['list'] as $k => $v) {
            $tempD1 = [
                "entity_table" => "project_task_diary",
                "entity_id" => $v["taskdiary_id"]
            ];
            $resultData['list'][$k]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId($tempD1);
            $resultData2 = [];
            $where = [
                'task_diary_replyid' => [$v["taskdiary_id"], "="]
            ];
            $resultData2 = app($this->projectTaskDiaryRepository)->getTaskDiarybyWhere($where);
            if (isset($resultData2[0]["taskdiary_id"])) {
                $tempD2 = [
                    "entity_table" => "project_task_diary",
                    "entity_id" => $resultData2[0]["taskdiary_id"]
                ];
                $resultData2[0]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId($tempD2);
            }


            $resultData['list'][$k]["reply"] = $resultData2;

            if ($v['task_diary_quoteid'] > 0) {
                $where = [
                    'taskdiary_id' => [$v["task_diary_quoteid"], "="]
                ];
                $resultData3 = app($this->projectTaskDiaryRepository)->getTaskDiarybyWhere($where);

                if (isset($resultData3[0]["taskdiary_id"])) {

                    $tempD3 = [
                        "entity_table" => "project_task_diary",
                        "entity_id" => $resultData3[0]["taskdiary_id"]
                    ];
                    $resultData3[0]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId($tempD3);

                    $resultData['list'][$k]["quote"] = (Object) $resultData3[0];
                } else {
                    $resultData['list'][$k]["quote"] = null;
                }
            } else {
                $resultData['list'][$k]["quote"] = null;
            }
        }
        return $resultData;
    }

    /**
     * 新建项目
     * 外发生成项目也是走这里
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectManager($data) {
        $this->filterNull($data);
        $outSendDataFlag = false;
        $additional = [];
        $currentUserId = isset($data["current_user_id"]) ? $data["current_user_id"] : "";
        // 判断可能是外发过来的情况
        if(isset($data["data"]) && !empty($data["data"]) && isset($data["current_user_id"])) {
            $outSendDataFlag = true;
            $data = $data["data"];
            $data['user_id'] = $currentUserId;// 外发无user_id，补充
            $additional = isset($data["additional"]) ? $data["additional"] : [];
            // project_type 是项目类型的名称，根据名称取id，传给 manager_type
            $projectType = isset($data["project_type"]) ? $data["project_type"] : "";
            if($projectType) {
                $emptyMsg = $this->buildFlowOutProjectMsg($data, $projectType);
                if ($emptyMsg) {
                    return ['code' => $emptyMsg];
                }
                // project_state 是项目状态的名称，获取到id，传给 manager_state
                $projectState = isset($data["project_state"]) ? $data["project_state"] : "";
                // 2018-05-21-外发修改，项目状态，传的id，不需要转换了
                if($projectState) {
                    $data["manager_state"] = $projectState;
                }
                $data["manager_type"] = $projectType;
                $data["manager_fast"] = isset($data["manager_fast"]) ? $data["manager_fast"] : "";
                $data["manager_level"] = isset($data["manager_level"]) ? $data["manager_level"] : "";
            } else {
                return ['code' => trans('common.0x000001') . ': ' . trans('project.project_type')];
            }
        }
        //验证项目数据
        $resTemp = $this->filterSaveData($data);
        if (isset($resTemp['code'])) {
            return $resTemp;
        }

        if (isset($data["manager_examine"]) && $data["manager_examine"] == "all") {
            $data["manager_examine"] = Utils::getUserIds();
        }
        if (isset($data["manager_person"]) && $data["manager_person"] == "all") {
            $data["manager_person"] = Utils::getUserIds();
        }
        if (isset($data["manager_monitor"]) && $data["manager_monitor"] == "all") {
            $data["manager_monitor"] = Utils::getUserIds();
        }

        //添加项目时 值只能是1（立项中）2（已提交）
        $data['manager_state'] = isset($data['manager_state']) ?$data['manager_state'] :"1";
        // $data['manager_state'] = isset($data['manager_state']) && $data['manager_state'] == 2 ? 2 : "1";
        if(!isset($data['manager_creater'])) {
            $data['manager_creater'] = isset($data['user_id']) ? $data['user_id'] : "";
        }
        $data['creat_time']    = isset($data['creat_time']) ?$data['creat_time'] :date("Y-m-d H:i:s", time());
        $data['manager_fast']  = isset($data['manager_fast'])?$data['manager_fast']:"";
        $data['manager_level'] = isset($data['manager_level'])?$data['manager_level']:"";
        $data['sort_id']       = isset($data['sort_id'])?$data['sort_id']:0;
        $projectData           = array_intersect_key($data, array_flip(app($this->projectManagerRepository)->getTableColumns()));

        $customTableKey = "project_value_" . $data["manager_type"] ?? 0;
        $checkResultParam['search']['is_system'] = [1];
        $checkResult = app($this->formModelingService)->authDataValid($customTableKey, $data,$checkResultParam);
        if ($checkResult !== true) {
            return $checkResult;
        }
        $result                = app($this->projectManagerRepository)->insertData($projectData);
        $projectId             = $result->manager_id;
        ProjectLogManager::getIns($data['user_id'], $projectId)->projectAddLog($result->manager_name);

        // 保存自定义字段的值，若自定义字段必填验证失败，手动回滚数据
        if(isset($data["manager_type"])) {
            $customTableFields = app($this->formModelingService)->listFields([], $customTableKey);
            $customData = array_intersect_key($data, $customTableFields);
            $customData['data_id'] = $projectId;

            $addCustomDataRes = app($this->formModelingService)->addCustomData($customData, $customTableKey);
            if (!$addCustomDataRes) {
                app($this->projectManagerRepository)->deleteById($projectId);//删除项目
                return ['code' => ['0x000003', 'common']];
            } elseif (isset($addCustomDataRes['code'])) {
                app($this->projectManagerRepository)->deleteById($projectId);//删除项目
                return $addCustomDataRes;
            }
        }

        //插入到项目团队中 --- todo
        $temp = [];
        if (isset($data["team_person"]) && $data["team_person"] == "all") {
            $temp["team_person"] = Utils::getUserIds();
        } else {
            $temp["team_person"] = Arr::get($data, 'team_person', '');
        }
        $temp["team_project"] = $projectId;

        $teamData = array_intersect_key($temp, array_flip(app($this->projectTeamRepository)->getTableColumns()));
        app($this->projectTeamRepository)->insertData($teamData);

        // 判断并外发项目任务，注意附件
        $saveTaskResult = [];
        if($projectId && $outSendDataFlag === true && !empty($additional)) {
            /*
            "task_project"   => [所属项目的项目id],[单行文本框，string],[eg : "6"],
            "task_name"      => [任务名称],[单行文本框，string],[eg : "任务名称"],
            "sort_id"        => [排序级别（值越小越靠前）],[单行文本框，int],[eg : "0"],
            "task_persondo"  => [任务执行人],[用户选择器，单选，string],[eg : "admin"],
            "task_frontid"   => [前置任务],[任务选择器，单选，int],[eg : 32],
            "task_begintime" => [计划周期-开始时间],[年月日选择器，string],[eg : "2018-06-20"],
            "task_endtime"   => [计划周期-结束时间],[年月日选择器，string],[eg : "2018-06-27"],
            "task_level"     => [任务级别],[任务级别选择器，来自下拉框配置，int],[eg : 5],
            "task_mark"      => [是否标记为里程碑，是1，否0],[checkbox，int],[eg : 1],
            "task_explain"   => [任务描述],[编辑器，string],[eg : "<p>任务描述</p>"],
            "task_remark"    => [备注],[编辑器，string],[eg : "<p>备注</p>"],
            "attachments"    => [附件],[...],[eg : ""],
            "user_id"        => [任务创建人],[系统数据，当前用户id，string],[eg : "WV00000003"],
            "creat_time"     => [任务创建时间],[单行文本框，当前时间，年月日时分秒，string],[eg : "2018-06-20 16:37:48"],
            */
            foreach ($additional as $key => $value) {
                // task_name task_persondo 必填
                $taskInfo = [];
                $taskInfo["task_project"]  = $projectId;
                $taskInfo["task_name"]     = isset($value["task_name"]) ? $value["task_name"] : "";
                $taskInfo["task_persondo"] = isset($value["task_persondo"]) ? $value["task_persondo"] : "";
                $taskInfo["user_id"] = isset($value["task_creater"]) ? $value["task_creater"] : "";
                $taskInfo["task_begintime"] = isset($value["task_begintime"]) ? $value["task_begintime"] : '';
                $taskInfo["task_endtime"]   = isset($value["task_endtime"]) ? $value["task_endtime"] : '';
                if($taskInfo["task_name"] && $taskInfo["task_persondo"] && $taskInfo["user_id"] && $taskInfo["task_begintime"] && $taskInfo["task_endtime"]) {
                    $taskInfo["sort_id"]        = isset($value["sort_id"]) ? $value["sort_id"] : 0;
                    $taskInfo["task_frontid"]   = isset($value["task_frontid"]) ? $value["task_frontid"] : 0;
                    $taskInfo["task_level"]     = isset($value["task_level"]) ? $value["task_level"] : 1;
                    $taskInfo["task_mark"]      = isset($value["task_mark"]) ? $value["task_mark"] : 0;
                    $taskInfo["task_explain"]   = isset($value["task_explain"]) ? $value["task_explain"] : "";
                    $taskInfo["task_remark"]    = isset($value["task_remark"]) ? $value["task_remark"] : "";
                    $taskInfo["attachments"]    = isset($value["attachments"]) ? $value["attachments"] : "";
                    $taskInfo["flow_creat_time"]     = isset($value["creat_time"]) ? $value["creat_time"] : date("Y-m-d H:i:s", time());
                    $saveTaskResult[$key + 1] = $this->addProjectTask($taskInfo);
                } else {
                    $saveTaskResult[$key + 1] = $this->buildFlowOutTaskMsg(
                        $taskInfo["task_name"], $taskInfo["task_persondo"], $taskInfo["user_id"], $taskInfo["task_begintime"], $taskInfo["task_endtime"]);
                }
            }
        }
        MessageManager::sendProjectCreatedReminder($result);
	// 外发到日程模块 --开始--
        $calendarData = [
            'calendar_content' => $data['manager_name'],
            'handle_user'      => explode(',', $data['manager_person']),
            'calendar_begin'   => $data['manager_begintime'],
            'calendar_end'     => $data['manager_endtime'],
            'calendar_remark'  => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags(Arr::get($data, 'manager_explain', ''))))
        ];
        $relationData = [
            'source_id'     => $result->manager_id,
            'source_from'   => 'project-detail',
            'source_title'  => $data['manager_name'],
            'source_params' => ['manager_id' => $result->manager_id]
        ];
        app($this->calendarService)->emit($calendarData, $relationData, $data['manager_creater']);
        return ['status' => boolval($result), 'dynamic' => $this->buildFlowOutTasksMsg($saveTaskResult), 'project_id' => $projectId];
    }

    /**
     * 编辑项目
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectManager($data) {
        $projectId = $data['manager_id'];
        //更新之前看下权限
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $projectId
        ];
        //验证权限
        $status = $this->getProjectEditPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        if (isset($data["manager_examine"]) && $data["manager_examine"] == "all") {
            $data["manager_examine"] = Utils::getUserIds();
        }
        if (isset($data["manager_person"]) && $data["manager_person"] == "all") {
            $data["manager_person"] = Utils::getUserIds();
        }
        if (isset($data["manager_monitor"]) && $data["manager_monitor"] == "all") {
            $data["manager_monitor"] = Utils::getUserIds();
        }

        //验证项目数据
        $resTemp = $this->filterSaveData($data);
        if (isset($resTemp['code'])) {
            return $resTemp;
        }
        // 先检测主表唯一性，然后保存自定义字段时检测自定义唯一性，之后再save主表数据，这样避免数据回滚
        $checkResult = app($this->projectSupplementService)->checkProjectManagerUnique($data, $projectId);
        if ($checkResult !== true) {
            return $checkResult;
        }

        // 保存自定义字段的值
        if(isset($data["manager_type"])) {
            $customTableKey = "project_value_" . $data["manager_type"];
            $customTableFields = app($this->formModelingService)->listFields([], $customTableKey);
            $customData = array_intersect_key($data, $customTableFields);
            $customData['data_id'] = $projectId;

            // 编辑之前，先判断一下，自定义字段值的表里面，是否有值了，没值，要用插入
            $customProjectInfo = app($this->formModelingService)->getCustomDataDetail("project_value_".$data["manager_type"],$projectId);
            if(count($customProjectInfo)) {
                // 有值，用保存
                $checkResult = app($this->formModelingService)->editCustomData($customData,"project_value_".$data["manager_type"],$projectId);
            } else {
                // 没值，用插入
                $checkResult = app($this->formModelingService)->addCustomData($customData,"project_value_".$data["manager_type"]);
            }
            if (isset($checkResult['code'])) {
                return $checkResult;
            }
        }
//        $projectData = array_intersect_key($data, array_flip(app($this->projectManagerRepository)->getTableColumns()));
//        $result = app($this->projectManagerRepository)->updateData($projectData, ['manager_id' => $projectId]);
        $project = app($this->projectManagerRepository)->getDetail($projectId);
        $project->fill($data);
        $result = $this->saveWithLog($project, 'project', $data['user_id'], $projectId);

        //插入到项目团队中 --- todo
        $temp = [];
        if (isset($data["team_person"]) && $data["team_person"] == "all") {
            $temp["team_person"] = Utils::getUserIds();
        } else {
            $temp["team_person"] = Arr::get($data, 'team_person', '');
        }

        //判断是否存在项目团队
        $whereTeam = [
            'team_project' => [[$projectId], '=']
        ];
        $detailTeam = app($this->projectTeamRepository)->getOneProjectTeam($whereTeam);

        if ($detailTeam && isset($detailTeam[0])) {
            $teamData = array_intersect_key($temp, array_flip(app($this->projectTeamRepository)->getTableColumns()));
            app($this->projectTeamRepository)->updateData($teamData, ['team_project' => $projectId]);
        } else {
            $temp["team_project"] = $projectId;
            $teamData = array_intersect_key($temp, array_flip(app($this->projectTeamRepository)->getTableColumns()));
            app($this->projectTeamRepository)->insertData($teamData);
        }
        $this->emitCalendarUpdate($project, $projectId);
        return $result;
    }

    private function emitCalendarUpdate($data, $managerId)
    {
        $calendarData = [
            'calendar_content' => $data['manager_name'],
            'handle_user'      => explode(',', $data['manager_person']),
            'calendar_begin'   => $data['manager_begintime'],
            'calendar_end'     => $data['manager_endtime'],
            'calendar_remark'  => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($data['manager_explain'])))
        ];
        $relationData = [
            'source_id'     => $managerId,
            'source_from'   => 'project-detail',
            'source_title'  => $data['manager_name'],
            'source_params' => ['manager_id' => $managerId]
        ];
        return app($this->calendarService)->emitUpdate($calendarData, $relationData, $data['manager_creater']);
    }

    /**
     * 项目-获取项目类型的list，用在流程表单控件-下拉框-数据源-系统数据-项目管理-项目状态
     *
     * @return array
     *
     * @author dp
     *
     * @since 2018-01-24
     */
    public function getPorjectManagerStateList($data=[]) {
        $data = $this->parseParams($data);
        // return array("1"=>"立项中","2"=>"审批中","3"=>"已退回","4"=>"进行中","5"=>"已结束");
        $resultData = [
            ["state_id" => "1","state_name" => trans("project.in_the_project")],
            ["state_id" => "2","state_name" => trans("project.examination_and_approval")],
            ["state_id" => "3","state_name" => trans("project.retreated")],
            ["state_id" => "4","state_name" => trans("project.have_in_hand")],
            ["state_id" => "5","state_name" => trans("project.finished")],
        ];
        // 传search，要查询
        if(isset($data["search"])) {
            $stateIdArray = isset($data["search"]["state_id"]) ? $data["search"]["state_id"] : [];
            $stateNameArray = isset($data["search"]["state_name"]) ? $data["search"]["state_name"] : [];
            $searchResultData = [];
            if(count($stateIdArray)) {
                if (isset($stateIdArray[1])) {
                    if ($stateIdArray[1] == "in") {
                        $stateIdArray = $stateIdArray[0] ?? [];
                    } else if ($stateIdArray[1] == "=") {
                        $stateIdArray = [$stateIdArray[0]];
                    }
                }
                foreach ($stateIdArray as $idKey => $idValue) {
                    foreach ($resultData as $key => $value) {
                        if($value["state_id"] == $idValue) {
                            array_push($searchResultData, $value);
                        }
                    }
                }
            } else if(count($stateNameArray) == 2) {
                // 查询字段值
                $nameSearchString = $stateNameArray[0];
                // 查询标识
                $nameSearchSign = $stateNameArray[1];
                if($nameSearchSign == 'like') {
                    foreach ($resultData as $key => $value) {
                        $stateName = $value["state_name"];
                        if(stripos($stateName, $nameSearchString) !== false) {
                            array_push($searchResultData, $value);
                        }
                    }
                }
            }
            return $searchResultData;
        } else {
            return $resultData;
        }
    }

    /**
     * 删除项目
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectManager($data) {


        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];

        $status = $this->getProjectdeletePrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $project = $this->getOneProject($data['manager_id'], $data['user_id']);

        //任务处理 taskdiary_project 附件
        $projectTaskDiaryDels = [
            "taskdiary_project" => [$data['manager_id']]
        ];
        $tempRow = app($this->projectTaskDiaryRepository)->infoProjectTaskDiarybyWhere($projectTaskDiaryDels);

        foreach ($tempRow as $temp) {
            $delData = [
                "entity_table" => "project_task_diary",
                "entity_id" => $temp["taskdiary_id"],
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }
        app($this->projectTaskDiaryRepository)->deleteByWhere($projectTaskDiaryDels);

        //项目任务 task_project 附件
        $projectTaskDels = [
            "task_project" => [$data['manager_id']]
        ];
        $tempRow = app($this->projectTaskRepository)->infoProjectTaskbyWhere($projectTaskDels);

        foreach ($tempRow as $temp) {
            $delData = [
                "entity_table" => "project_task",
                "entity_id" => $temp["task_id"],
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }
        app($this->projectTaskRepository)->deleteByWhere($projectTaskDels);

        //项目团队 team_project
        app($this->projectTeamRepository)->deleteByWhere(['team_project' => [[$data['manager_id']], '=']]);
        //项目文档 doc_project 附件
        $projectDocDels = [
            "doc_project" => [$data['manager_id']]
        ];
        $tempRow = app($this->projectDocumentRepository)->getInfobyWhere($projectDocDels);

        foreach ($tempRow as $temp) {
            $delData = [
                "entity_table" => "project_document",
                "entity_id" => $temp["doc_id"],
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }
        app($this->projectDocumentRepository)->deleteByWhere($projectDocDels);
        //项目问题 question_project 附件
        $projectQuestionDels = [
            "question_project" => [$data['manager_id']]
        ];
        $tempRow = app($this->projectQuestionRepository)->infoProjectQuestionbyWhere($projectQuestionDels);

        foreach ($tempRow as $temp) {
            $delData = [
                "entity_table" => "project_question",
                "entity_id" => $temp["question_id"],
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }
        app($this->projectQuestionRepository)->deleteByWhere($projectQuestionDels);
        //项目讨论 discuss_project 附件
        $projectDiscussDels = [
            "discuss_project" => [$data['manager_id']]
        ];
        $tempRow = app($this->projectDiscussRepository)->getInfobyWhere($projectDiscussDels);

        foreach ($tempRow as $temp) {
            $delData = [
                "entity_table" => "project_discuss",
                "entity_id" => $temp["discuss_id"],
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }
        app($this->projectDiscussRepository)->deleteByWhere($projectDiscussDels);

        //删除对应的项目 //--
        $this->deleteProjectCustomData($project);
        CacheManager::cleanProjectReportCache();
        $res = app($this->projectManagerRepository)->deleteById($data['manager_id']);
        $res && ProjectLogManager::getIns($data['user_id'], $data['manager_id'])->projectDeleteLog($project['manager_name']);
        $this->emitCalendarDelete($data['manager_id'], $data['user_id'], 'delete');
        return $res;
    }

    /**
     * 获取项目的参与人
     *
     * @return string
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectTeams($data) {

        $result = app($this->projectTeamRepository)->infoProjectTeambyWhere(['team_project' => [[$data['manager_id']], '=']]);
        $teams = "";
        foreach ($result as $v) {
            $teams .= $v['team_person'] . ",";
        }

        $teams = trim($teams, ",");
        //去重复
        return implode(',', array_unique(explode(',', $teams)));
    }

    public function getProjectTeamsDrow($data) {
        $data = $this->parseParams($data);
        $teams = $this->getProjectTeams($data);
        $teamsArray = explode(",", $teams);

        $where["page"] = isset($data["page"]) && $data["page"] ? $data["page"] : "";
        $where["limit"] = isset($data["limit"]) && $data["limit"] ? $data["limit"] : "";

        $where['search'] = [
            "user_id" => [$teamsArray, "in"]
        ];
        if (isset($data["search"]) && $data["search"]) {
            $whereSearch = array_merge($where["search"],array_filter($data["search"]));
            // $where = array_merge($where, array_filter($data));
            $where["search"] = $whereSearch;
        }

        $data = app($this->userRepository)->getAllUsers($where);
        $result = [
            "total" => count($teamsArray),
            "list" => $data
        ];

        return $result;
    }

    public function getUserName($data) {

        if (!$data) {
            return '';
        }

        if (isset($data['user_id'])) {
            $user = explode(",", $data['user_id']);
        } else {
            $user = explode(",", $data);
        }

        $where['search'] = [
            "user_id" => [$user, "in"]
        ];
        return app($this->userRepository)->getAllUsers($where);
    }

    // 导入模板
    /**
     * 导入模板
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function importProjectTemplates($data) {
        //获取当前项目的团队

        try {
            $tempWhere = [
                "task_complate" => [$data['template_id']]
            ];
            $result = app($this->projectTaskRepository)->getTemplateTasks($tempWhere);
            $now = date('Y-m-d H:i:s');
            if (count($result) > 0) {
                $dat = [];
                $params = [];
                $temp = [];
                foreach ($result as $v) {
                    $dat['task_project'] = $data['manager_id'];
                    //获取某个项目的团队
                    $detailTeam = $this->getOneProjectTeam($data);

                    $dat['task_persondo'] = in_array($v['task_persondo'], $detailTeam) ? $v['task_persondo'] : "";

                    $dat['creat_time'] = $v['creat_time']; //date("Y-m-d H:i:s", time());
                    $dat['task_creater'] = $data['user_id'];
                    $dat['task_complate'] = 0; //导入的模板 这个地方是0
                    $dat['task_name'] = $v['task_name'];
                    $dat['sort_id'] = $v['sort_id'];
                    $dat['task_frontid'] = $v['task_frontid'];
                    $dat['task_begintime'] = $v['task_begintime'];
                    $dat['task_endtime'] = $v['task_endtime'];
                    $dat['task_explain'] = $v['task_explain'];
                    $dat['task_level'] = $v['task_level'];
                    $dat['task_mark'] = $v['task_mark'];
                    $dat['task_remark'] = $v['task_remark'];
                    $dat['created_at'] = $now;
                    $dat['updated_at'] = $now;

                    array_push($params, $dat);
                    if ($v['task_frontid'] > 0) {
                        $temp[] = $dat;
                    }
                }

                app($this->projectTaskRepository)->insertMultipleData($params);
                //更新temp数组中的前置任务
                foreach ($temp as $t) {
                    //找到数据库下插入的ID
                    $res = app($this->projectTaskRepository)->getDetail($t['task_frontid']);

                    //插入当前下 项目+任务名称+创建时间+处理人+任务类别+开始时间结束时间
                    $updateWhere = [
                        "task_project" => [$t['task_project']],
                        "task_name" => [$res['task_name']],
                        "creat_time" => [$res['creat_time']],
                        "task_level" => [$res['task_level']],
                        "task_begintime" => [$res['task_begintime']],
                        "task_endtime" => [$res['task_endtime']]
                    ];
                    $updateInserID = app($this->projectTaskRepository)->getTemplateTasks($updateWhere);
                    if (isset($updateInserID[0]["task_id"])) {
                        $insertId = $updateInserID[0]["task_id"];
                    } else {
                        $insertId = 0;
                    }



                    $where = [
                        "task_project" => [$t['task_project']],
                        "task_frontid" => [$t['task_frontid']]
                    ];
                    app($this->projectTaskRepository)->updateData(["task_frontid" => $insertId], $where);
                }
                // 增加任务的日志
                if (count($params)) {
                    $tasks = app($this->projectTaskRepository)->entity->where('created_at', $now)
                        ->where('task_project', $data['manager_id'])
                        ->where('task_creater', $data['user_id'])
                        ->get();
                    if ($tasks->isNotEmpty()) {
                        $projectLogManager = ProjectLogManager::getIns($data['user_id'], $data['manager_id'])->beginFillDataModule();
                        foreach ($tasks as $task) {
                            $projectLogManager->taskAddLog($task['task_name'], $task['task_id']);
                        }
                        $projectLogManager->storageFillData();
                    }
                }
            }


            //更新带有task_frontid的项目


            return true;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        //return  app($this->projectManagerRepository)->updateData(['manager_template' => $data['template_id']], ['manager_id' => $data['manager_id']]);
    }

    /**
     * 项目列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectManagerList($data, $own = []) {

        //修改
        //当前用户 user_id
        $projects = $this->getProjectsByUser($data['user_id']);
        $projectsObject = collect($projects);
        $teams          = $projectsObject->pluck("team_project")->toArray();
        $teamstr = implode(",", $teams);
        $data['user_team_project'] = $teamstr;

        //获取所有的类型
        $resultTypes = app($this->systemComboboxService)->getProjectTypeAll();

        $getProjectParams = $this->parseParams($data);
        // 默认，获取未结束的项目
        // 默认选中，值为1，表示隐藏已经结束的项目；取消选中，值为0，表示展示所有的项目
        if(isset($data["showFinalPorjectFlag"])) {
            $this->setFinalProjectParams($getProjectParams, $data["showFinalPorjectFlag"]);
        }
        //获取所有的项目
        $res = app($this->projectManagerRepository)->getProjectLists($getProjectParams, $this->getOrderByfield());
        list('total' => $total, 'list' => $resultProjects) = $res;
        $managerIds = array_column($resultProjects, 'manager_id');

        // 项目关联费用：费用清单-项目中间列展示每个项目的总费用
        if(!empty($resultProjects) && isset($data['withCharge'])){
            $charge     = app("App\EofficeApp\Charge\Services\ChargeService")->getStatisticByProject($managerIds, $own);
        }
        $taskStatusList = $this->getCurrentListStatusByUserId($data['user_id'], "project");
        $projectStatusList = $taskStatusList;
//        $projectStatusList = $this->getCurrentListStatusByUserId($data['user_id'], "project");


        // 计算项目下属任务数量
        $taskCountAll = app($this->projectTaskRepository)->getTaskCountGroupByProject($managerIds, ['tree_level' => [1, '=']]);
        $taskCountAllInfo = [];
        if(!empty($taskCountAll)) {
            foreach ($taskCountAll as $key => $value) {
                if(isset($value["task_project"]) && $value["task_project"] > 0) {
                    $taskCountAllInfo[$value["task_project"]] = [
                        "task_count"   => isset($value["task_count"]) ? $value["task_count"] : 0,
                        "task_persent" => isset($value["task_persent"]) ? $value["task_persent"] : 0
                    ];
                }
            }
        }
        // 获取全部任务的项目状态，按项目id分组
        $taskStatusCountAll = app($this->projectTaskRepository)->getTaskStatusGroupByProject($data['user_id'], $managerIds);
        $taskStatusCountAllInfo = [];
        if(!empty($taskStatusCountAll)) {
            foreach ($taskStatusCountAll as $key => $value) {
                if(isset($value["task_project"]) && $value["task_project"] > 0) {
                    $taskStatusCountAllInfo[$value["task_project"]] = isset($value["count"]) ? $value["count"] : 0;
                }
            }
        }

        //数据重组
        $resultList = [];
        foreach ($resultTypes as $k => $vType) {

            $resultList[$k] = $vType;
            $cost           = 0;
            $resultList[$k]["project_list"] = [];
            foreach ($resultProjects as $vProject) {

                if ($vProject['manager_type'] == $vType['field_value']) {


                    $taskCountPersentItem = isset($taskCountAllInfo[$vProject["manager_id"]]) ? $taskCountAllInfo[$vProject["manager_id"]] : [];
                    $taskCount = isset($taskCountPersentItem["task_count"]) ? $taskCountPersentItem["task_count"] : 0;
                    $diaryCount = isset($taskCountPersentItem["task_persent"]) ? $taskCountPersentItem["task_persent"] : 0;

                    //获取当前项目的任务进度
                    // $taskCount = app($this->projectTaskRepository)->projectCountSumPersent($vProject["manager_id"]);

                    // $diaryCount = app($this->projectTaskRepository)->projectPersent($vProject["manager_id"]);

                    if ($taskCount == "0" || $diaryCount == "0") {
                        $vProject['plan'] = "0"; //111
                    } else {
                        $persent = $diaryCount / $taskCount;
                        $vProject['plan'] = round($persent);
                    }
                    $vProject['user_name'] = app($this->userRepository)->getUserName($vProject['manager_creater']);
                    $vProject["task_read_flag"] = 0;
                    if (in_array($vProject['manager_id'], $taskStatusList)) {
                        $vProject["task_read_flag"] = 1;
                    }

                    $vProject["project_new_disscuss"] = 0;
                    if (in_array($vProject["manager_id"], $projectStatusList)) {
                        $vProject["project_new_disscuss"] = 1;
                    }

                    // $temp = app($this->projectTaskRepository)->ProjectTaskList($vProject['manager_id']);
                    // foreach ($temp as $info) {
                    //     if (in_array($info["task_id"], $taskStatusList)) {
                    //         $vProject["task_read_flag"] = 1;
                    //     }
                    // }


                    $taskStatusCountItem = isset($taskStatusCountAllInfo[$vProject["manager_id"]]) ? $taskStatusCountAllInfo[$vProject["manager_id"]] : 0;
                    $vProject["task_read_flag"] = $taskStatusCountItem > 0 ? 1 : 0;
                    // if ($this->getCurrentProjectTaskStatusByMangerId($data['user_id'], $vProject["manager_id"]) > 0) {
                    //     $vProject["task_read_flag"] = 1;
                    // }
                    // 项目费用统计
                    if(isset($charge) && isset( $charge[$vProject['manager_id']] ) ){
                        $vProject["cost"] = $charge[$vProject['manager_id']];
                        $cost             += (float)$charge[$vProject['manager_id']];
                    }
                    $resultList[$k]["project_list"][] = $vProject;
                }
            }
            //是否存在项目列表
            if ($resultList[$k]["project_list"]) {
                if(isset($data['withCharge'])){
                    $resultList[$k]["field"] = $resultList[$k]["field_name"]."( ".$cost." )";
                }else{
                    $resultList[$k]["count"] = count($resultList[$k]["project_list"]);
                    $resultList[$k]["field"] = $resultList[$k]["field_name"]."(".$resultList[$k]["count"].")";
                }
            } else {
                unset($resultList[$k]);
            }

        }

        return [
            'total' => $total,
            'list' => array_values($resultList)
        ];
    }

    /**
     * 根据manager_type获取project_manager表中的数据
     *
     */
     public function getProjectListAll($data) {
        $result = app($this->projectManagerRepository)->getProjectsByFieldValue($data['manager_type']);
        return $result;


    }

    /**
     * 获取排序字段 order by field
     *
     */
    public function getOrderByfield() {
        return []; // 去除按分类排序的功能
        //获取所有的类型
        $resultTypes = app($this->systemComboboxService)->getProjectTypeAll();
        $temp = [];
        foreach ($resultTypes as $val) {
            $temp[] = $val["field_value"];
        }
        return $temp;
    }

    /**
     * 项目border
     */
    public function getProjectListIndex($user_id, $data) {
        $data['user_id'] = $user_id;
        // 当前用户 user_id ，获取用户参与的所有项目
        $projects       = $this->getProjectsByUser($data['user_id']);
        $projectsObject = collect($projects);
        $teams          = $projectsObject->pluck("team_project")->toArray();
        $teamstr        = implode(",", $teams);
        $data['user_team_project'] = $teamstr;
        //  $taskStatusList = $this->getCurrentListStatusByUserId($user_id, "task");
        $projectStatusList = $this->getCurrentListStatusByUserId($user_id, "project");
        $getProjectParams = $this->parseParams($data);
        // 默认，获取未结束的项目
        // 默认选中，值为1，表示隐藏已经结束的项目；取消选中，值为0，表示展示所有的项目
        if(isset($data["showFinalPorjectFlag"])) {
            $this->setFinalProjectParams($getProjectParams, $data["showFinalPorjectFlag"]);
        }
        $res = app($this->projectManagerRepository)->getProjectLists($getProjectParams, $this->getOrderByfield());
        list('total' => $total, 'list' => $resultProjects) = $res;
        $managerIds = Arr::pluck($resultProjects, 'manager_id');

        $taskCountAllInfo = [];
        $taskStatusCountAllInfo = [];
        $questionCountAllInfo = [];
        $docCountAllInfo = [];
        if ($managerIds) {
            // 计算项目下属任务数量
            $taskCountAll = app($this->projectTaskRepository)->getTaskCountGroupByProject($managerIds, ['tree_level' => [1, '=']]);
            if(!empty($taskCountAll)) {
                foreach ($taskCountAll as $key => $value) {
                    if(isset($value["task_project"]) && $value["task_project"] > 0) {
                        $taskCountAllInfo[$value["task_project"]] = [
                            "task_count"   => isset($value["task_count"]) ? $value["task_count"] : 0,
                            "complete_task_count"   => isset($value["complete_task_count"]) ? $value["complete_task_count"] : 0,
                            "task_persent" => isset($value["task_persent"]) ? $value["task_persent"] : 0
                        ];
                    }
                }
            }
            // 获取全部任务的项目状态，按项目id分组
            $taskStatusCountAllInfo = $this->checkReadStatus($user_id, $managerIds);
            // 计算项目下属问题数量
            $questionCountAll = app($this->projectQuestionRepository)->getQuestionCountGroupByProject($user_id, $managerIds);
            if(!empty($questionCountAll)) {
                foreach ($questionCountAll as $key => $value) {
                    if(isset($value["question_project"]) && $value["question_project"] > 0) {
                        $questionCountAllInfo[$value["question_project"]] = isset($value["question_count"]) ? $value["question_count"] : 0;
                    }
                }
            }
            // 计算项目下属文档数量
            $docCountAll = app($this->projectDocumentRepository)->getDocumentCountGroupByProject($managerIds);
            if(!empty($docCountAll)) {
                foreach ($docCountAll as $key => $value) {
                    if(isset($value["doc_project"]) && $value["doc_project"] > 0) {
                        $docCountAllInfo[$value["doc_project"]] = isset($value["doc_count"]) ? $value["doc_count"] : 0;
                    }
                }
            }
        }

        // 取自定义字段那里配置的项目类型的名称
        $typesAllInfo = app($this->systemComboboxFieldRepository)->getSystemComboboxFieldNameAll("PROJECT_TYPE");
        $resultProject = [];
        foreach ($resultProjects as $v) {
            //获取项目类型
            if(isset($v["manager_type"])) {
                $typesItem = isset($typesAllInfo[$v["manager_type"]]) ? $typesAllInfo[$v["manager_type"]] : [];
                $v["manager_type_name"] = isset($typesItem["field_name"]) ? $typesItem["field_name"] : "";
            }
            $v["project_new_disscuss"] = 0;
            if (in_array($v["manager_id"], $projectStatusList)) {
                $v["project_new_disscuss"] = 1;
            }

            // $v["task_new_feedback"] = $this->getCurrentProjectTaskStatusByMangerId($user_id, $v["manager_id"]) > 0 ? 1 : 0;
            $v["task_new_feedback"] = Arr::get($taskStatusCountAllInfo, $v['manager_id'], 0);

            // $taskCount = app($this->projectTaskRepository)->projectCountSumPersent($v["manager_id"]);
            $taskCountPersentItem = isset($taskCountAllInfo[$v["manager_id"]]) ? $taskCountAllInfo[$v["manager_id"]] : [];
            $taskCount = isset($taskCountPersentItem["task_count"]) ? $taskCountPersentItem["task_count"] : 0;
            $v['complete_task_count'] = isset($taskCountPersentItem["complete_task_count"]) ? $taskCountPersentItem["complete_task_count"] : 0;
            $v['task_count'] = $taskCount;
            // $questionCount = app($this->projectQuestionRepository)->projectQuestionCount($v["manager_id"], $user_id);
            $questionCount = isset($questionCountAllInfo[$v["manager_id"]]) ? $questionCountAllInfo[$v["manager_id"]] : 0;
            $v['question_count'] = $questionCount;
            // $docCount = app($this->projectDocumentRepository)->projectDocumentCount($v["manager_id"]);
            $docCount = isset($docCountAllInfo[$v["manager_id"]]) ? $docCountAllInfo[$v["manager_id"]] : 0;
            $v['doc_count'] = $docCount;
            // $diaryCount = app($this->projectTaskRepository)->projectPersent($v["manager_id"]);
            $diaryCount = isset($taskCountPersentItem["task_persent"]) ? $taskCountPersentItem["task_persent"] : 0;

            if ($taskCount == "0" || $diaryCount == "0") {
                $v['plan'] = "0"; //333
            } else {
                $persent = $diaryCount / $taskCount;
                $v['plan'] = round($persent);
            }

            array_push($resultProject, $v);
        }

        return ['total' => $total, 'list' => $resultProject];
    }

    public function getProjectSystemData($user_id, $data) {

        $data["user_id"] = $user_id;
        $projects = $this->getProjectsByUser($data['user_id']);
        $projectsObject = collect($projects);
        $teams          = $projectsObject->pluck("team_project")->toArray();
        $teamstr = implode(",", $teams);
        $data['user_team_project'] = $teamstr;

        $data["order_by_field"] = $this->getOrderByfield();

        $getProjectParams = $this->parseParams($data);
        if(isset($getProjectParams["search"]) && isset($getProjectParams["search"]["manager_state"])) {
        } else {
            // 默认，获取未结束的项目
            $getProjectParams["search"]["manager_state"] = ["5","!="];
        }
        // // 默认选中，值为1，表示隐藏已经结束的项目；取消选中，值为0，表示展示所有的项目
        // if(isset($data["showFinalPorjectFlag"]) && $data["showFinalPorjectFlag"] == "1") {
        // }

        $resultProjects = $this->response(app($this->projectManagerRepository), 'getProjectSystemDataTotal', 'getProjectSystemData', $getProjectParams);
        $managerIds = Arr::pluck($resultProjects['list'], 'manager_id');
        $resultProject = [];
        $taskCountAll = app($this->projectTaskRepository)->getTaskCountGroupByProject($managerIds, ['tree_level' => [1, '=']]);
        $tasksCount = Arr::pluck($taskCountAll, 'task_count', 'task_project');
        $completeTasksCount = Arr::pluck($taskCountAll, 'complete_task_count', 'task_project');
        $tasksPersent = Arr::pluck($taskCountAll, 'task_persent', 'task_project');

        foreach ($resultProjects["list"] as $v) {
            $managerIdTemp = $v["manager_id"];
            $types = app($this->systemComboboxFieldRepository)->getNameByValue($v["manager_type"], "PROJECT_TYPE");

            $v["manager_type_name"] = $types["field_name"];

//            $taskCount = app($this->projectTaskRepository)->projectCountSumPersent($v["manager_id"]);
            $taskCount = Arr::get($tasksCount, $managerIdTemp, 0);
            $v['task_count'] = $taskCount;
            $v['complete_task_count'] = Arr::get($completeTasksCount, $managerIdTemp, 0);

            $questionCount = app($this->projectQuestionRepository)->projectQuestionCount($v["manager_id"], $user_id);
            $v['question_count'] = $questionCount;
            $docCount = app($this->projectDocumentRepository)->projectDocumentCount($v["manager_id"]);
            $v['doc_count'] = $docCount;

//            $diaryCount = app($this->projectTaskRepository)->projectPersent($v["manager_id"]);
            $diaryCount = Arr::get($tasksPersent, $managerIdTemp, '0');
            if ($taskCount == "0" || $diaryCount == "0") {
                $v['plan'] = "0"; //333
            } else {
                $persent = $diaryCount / $taskCount;
                $v['plan'] = round($persent);
            }

            array_push($resultProject, $v);
        }

        return [
            "list" => $resultProject,
            "total" => $resultProjects["total"]
        ];
    }

    /**
     * 获取用户参与的所有项目
     *
     * return id
     */
    public function getProjectsByUser($user_id) {
        //当前用户 user_id ->project_team
        return app($this->projectTeamRepository)->getProjectsByUser($user_id);
    }

    /**
     * 处理项目
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function dealProjectManager($data) {
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];

        $project = app($this->projectManagerRepository)->getDetail($data['manager_id']);
        if (!$project) {
            return ['code' => ['0x036018', 'project']];
        }
        $projectInfo = $project->toArray();

        switch ($data['operate']) {
            //in:proExamine,proApprove,proRefuse,proEnd,proRestart //提交 批准 退回  结束 重新启动
            case "proExamine"://提交
                $status = $this->getProjectApprovalPrivate($param);
                if ($status !== true) {
                    return $status;
                }
                // 判断是否存在审批人，如果当前项目没有审批人，不能进行[提交审核]
                if(!empty($projectInfo) && isset($projectInfo["manager_examine"])) {
                    if($projectInfo["manager_examine"] == "") {
                        return ['code' => ['no_auditor_can_not_submission_of_audit', 'project']];
                    }
                }

                //1->2  //3->2
                if ($projectInfo['manager_state'] != 1 && $projectInfo['manager_state'] != 3) {
                    return ['code' => ['0x036011', 'project']];
                }
                $projectData = [
                    "manager_state" => 2
                ];
                MessageManager::sendProjectExamineReminder($projectInfo);
                break;
            case "proApprove"://批准
                //2-4
                $status = $this->getProjectExaminePrivate($param);
                if ($status !== true) {
                    return $status;
                }
                if ($projectInfo['manager_state'] != 2) {
                    return ['code' => ['0x036011', 'project']];
                }
                $projectData = [
                    "manager_state" => 4
                ];
                MessageManager::sendProjectBeginReminder($projectInfo);
                break;
            case "proRefuse"://退回
                //2-3
                $status = $this->getProjectExaminePrivate($param);
                if ($status !== true) {
                    return $status;
                }
                if ($projectInfo['manager_state'] != 2) {
                    return ['code' => ['0x036011', 'project']];
                }
                $projectData = [
                    "manager_state" => 3
                ];
                MessageManager::sendProjectReturnReminder($projectInfo);
                break;
            case "proEnd"://结束
                //4->5
                $status = $this->getProjectEndPrivate($param);

                if ($status !== true) {
                    return $status;
                }
                if ($projectInfo['manager_state'] != 4) {
                    return ['code' => ['0x036011', 'project']];
                }
                $projectData = [
                    "manager_state" => 5
                ];
                $this->emitCalendarDelete($data['manager_id'], $data['user_id'], 'complete');
                break;
            case "proRestart"://重新启动
                //5->4
                $status = $this->getProjectStartPrivate($param);

                if ($status !== true) {
                    return $status;
                }
                if ($projectInfo['manager_state'] != 5) {
                    return ['code' => ['0x036011', 'project']];
                }
                $projectData = [
                    "manager_state" => 4
                ];
                $this->emitCalendarDelete($data['manager_id'], $data['user_id'], 'delete');
                $this->emitCalendarAdd($projectInfo, $data['user_id']);
                break;
        }

        $project->fill($projectData);
        $res = $this->saveWithLog($project, 'project', $data['user_id'], $data['manager_id'], $data['operate']);
        return $res;
    }

    private function emitCalendarDelete($sourceId, $userId, $type) 
    {
        $relationData = [
            'source_id' => $sourceId,
            'source_from' => 'project-detail'
        ];
        if ($type == 'delete') {
            return app($this->calendarService)->emitDelete($relationData, $userId);
        } else if ($type =  'complete') {
            return app($this->calendarService)->emitComplete($relationData);
        }
        
    }

    private function emitCalendarAdd($data, $userId) 
    {
        $calendarData = [
            'calendar_content' => $data['manager_name'],
            'handle_user'      => explode(',', $data['manager_person']),
            'calendar_begin'   => $data['manager_begintime'],
            'calendar_end'     => $data['manager_endtime'],
            'calendar_remark'  => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($data['manager_explain'])))
        ];
        $relationData = [
            'source_id'     => $data['manager_id'],
            'source_from'   => 'project-detail',
            'source_title'  => $data['manager_name'],
            'source_params' => ['manager_id' => $data['manager_id']]
        ];
        return app($this->calendarService)->emit($calendarData, $relationData, $data['manager_creater']);
    }

    /**
     * 设置团队成员
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function setProjectTeams($data) {

        if (isset($data["team_person"]) && $data["team_person"] == "all") {
            $data["team_person"] = Utils::getUserIds();
        }

        $where = [
            'team_project' => [[$data['team_project']], '=']
        ];
        $projectInfo = app($this->projectTeamRepository)->infoProjectTeambyWhere($where);
        $projectData = array_intersect_key($data, array_flip(app($this->projectTeamRepository)->getTableColumns()));
        if (!isset($projectInfo[0])) {
            $resultData = app($this->projectTeamRepository)->insertData($projectData);
            $result = $resultData->team_id;
        } else {

            $oldTeamPerson = $this->getProjectTeams(["manager_id" => $data["team_project"]]);

            $result = app($this->projectTeamRepository)->updateData($projectData, ['team_project' => $data["team_project"]]);

            $newTeamPerson = $this->getProjectTeams(["manager_id" => $data["team_project"]]);

            //查看是否更新了人员
            $newTeamPerson = explode(",", $newTeamPerson);
            $oldTeamPerson = explode(",", $oldTeamPerson);
            $temp1 = array_diff($oldTeamPerson, $newTeamPerson);
            if ($temp1) {
                $taskWhere = [
                    "task_project" => [$data["team_project"]],
                    "task_persondo" => [$temp1, "in"]
                ];
                app($this->projectTaskRepository)->updateData(["task_persondo" => ""], $taskWhere); //制空处理人
            }

        }
        return $result;
    }

    /**
     * 获取某个团队
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectTeam($data) {
        $where = [
            'team_project' => [[$data['manager_id']], '=']
        ];
        $temp = app($this->projectTeamRepository)->getOneProjectTeam($where);
        $result = [];
        foreach ($temp as $v) {
            $v["team_person"] = explode(",", $v["team_person"]);
            $result[] = $v;
        }
        if ($result) {
            return $result[0];
        }
        return $result;
    }

    /**
     * 项目讨论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    //获取项目中最大的discuss_order
    public function getMaxOrder() {
        $result = app($this->projectDiscussRepository)->getMaxOrder();
        $maxOrder = 0;
        if (count($result)) {
            $maxOrder = $result[0]['discuss_order'];
        }
        return max($maxOrder, 0);
    }

    /**
     * 项目讨论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectDiscuss($discuss_id, $data) { //discuss_readtime为空
        $where = [
            'discuss_id' => [[$discuss_id], '='],
            'discuss_person' => [[$data['user_id']], '=']
        ];
        $projectInfo = app($this->projectDiscussRepository)->infoProjectDiscussbyWhere($where);

        $oldDiscuss = Arr::get($projectInfo, '0');
        if (!$oldDiscuss || !$this->testTime($oldDiscuss['discuss_time'])) {
            return ['code' => ['0x000017', 'common']];
        }

        $attachmentObj = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_discuss", $discuss_id, $attachmentObj);

        $projectData = array_intersect_key($data, array_flip(app($this->projectDiscussRepository)->getTableColumns()));
        $status = app($this->projectDiscussRepository)->updateData($projectData, ['discuss_id' => $discuss_id]);

        $projectStatusData = [
            "type" => "project",
            "relation_id" => $data["discuss_project"],
            "manager_id" => $data["discuss_project"],
            "user_id" => $data['user_id'],
        ];
        $this->insertProjectStatus($projectStatusData);

        return $status;
    }

    /**
     * 项目讨论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectDiscuss($discuss_id, $data) { //discuss_readtime为空
        $managerId = Arr::get($data, 'discuss_project');
        if (!self::isManager($managerId, $data['user_id'])) {
            //若不是项目负责人，判断权限与时间限制
            $where = [
             'discuss_id' => [[$discuss_id], '='],
             'discuss_person' => [[$data['user_id']], '=']
            ];
            $projectInfo = app($this->projectDiscussRepository)->infoProjectDiscussbyWhere($where);
            $oldDiscuss = Arr::get($projectInfo, '0');
            if (!$oldDiscuss || !$this->testTime($oldDiscuss['discuss_time'])) {
                return ['code' => ['0x000017', 'common']];
            }
        }

        $delData = [
            "entity_table" => "project_discuss",
            "entity_id" => $discuss_id
        ];
        app($this->attachmentService)->deleteAttachmentByEntityId($delData);


        return app($this->projectDiscussRepository)->deleteById($discuss_id);
    }

    /**
     * 回复讨论 新建评论
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectDiscuss($data) {

        $data['discuss_time'] = date("Y-m-d H:i:s", time());
        $data['discuss_person'] = $data['user_id'];
//        $data['discuss_readtime'] = " "; //设置为空
        $projectData = array_intersect_key($data, array_flip(app($this->projectDiscussRepository)->getTableColumns()));
        $result = app($this->projectDiscussRepository)->insertData($projectData);


        $discuss_id = $result->discuss_id;
        $attachmentObj = isset($data['attachments']) ? $data['attachments'] : "";
        if ($attachmentObj) {
            app($this->attachmentService)->attachmentRelation("project_discuss", $discuss_id, $attachmentObj);
        }

        $projectStatusData = [
            "type" => "project",
            "relation_id" => $data["discuss_project"],
            "manager_id" => $data["discuss_project"],
            "user_id" => $data['user_id'],
        ];
        $this->insertProjectStatus($projectStatusData);


        return $discuss_id;
    }

    public function getOneProjectDiscuss($id) {
        $where = [
            'discuss_id' => [$id, "="]
        ];
        $resultData = app($this->projectDiscussRepository)->infoProjectDiscussbyWhere($where);
        if ($resultData[0]["discuss_replyid"] > 0) {
            $where2 = [
                'discuss_id' => [$resultData[0]["discuss_replyid"], "="]
            ];
            $resultData2 = app($this->projectDiscussRepository)->infoProjectDiscussbyWhere($where2);
            $resultData[0]["reply"] = (Object) $resultData2[0];
        }

        return $resultData[0];
    }

    public function replyProjectDiscuss($data) {

        if (!isset($data['discuss_replyid'])) {
            $data['discuss_replyid'] = 0;
        }
        $data['discuss_time'] = date("Y-m-d H:i:s", time());
        $projectData = array_intersect_key($data, array_flip(app($this->projectDiscussRepository)->getTableColumns()));
        $result = app($this->projectDiscussRepository)->insertData($projectData);
        $discuss_id = $result->discuss_id;
        $attachmentObj = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_discuss", $discuss_id, $attachmentObj);

        $projectStatusData = [
            "type" => "project",
            "relation_id" => $data["discuss_project"],
            "manager_id" => $data["discuss_project"],
            "user_id" => $data['discuss_person'],
        ];
        $this->insertProjectStatus($projectStatusData);


        return $result;
    }

    /**
     * 讨论列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectDiscussList($data, $manager_id, $user_id) {

        //看看归不归属这个项目
        $param = [
            'user_id' => $user_id,
            'manager_id' => $manager_id
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $data['discuss_project'] = $manager_id;
        $resultData = $this->response(app($this->projectDiscussRepository), 'getProjectDiscussTotal', 'getProjectDiscussList', $this->parseParams($data));
        foreach ($resultData['list'] as $k => $v) {
            $tempD1 = [
                "entity_table" => "project_discuss",
                "entity_id" => $v["discuss_id"]
            ];
            $resultData['list'][$k]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId($tempD1);
            $resultData2 = [];
            $where = [
                'discuss_replyid' => [$v["discuss_id"], "="]
            ];
            $resultData2 = app($this->projectDiscussRepository)->infoProjectDiscussbyWhere($where);
            if (isset($resultData2[0]["discuss_id"])) {
                $tempD2 = [
                    "entity_table" => "project_discuss",
                    "entity_id" => $resultData2[0]["discuss_id"]
                ];
                $resultData2[0]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId($tempD2);
            }


            $resultData['list'][$k]["reply"] = $resultData2;

            if ($v['discuss_quoteid'] > 0) {
                $where = [
                    'discuss_id' => [$v["discuss_quoteid"], "="]
                ];
                $resultData3 = app($this->projectDiscussRepository)->infoProjectDiscussbyWhere($where);

                if (isset($resultData3[0]["discuss_id"])) {

                    $tempD3 = [
                        "entity_table" => "project_discuss",
                        "entity_id" => $resultData3[0]["discuss_id"]
                    ];
                    $resultData3[0]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId($tempD3);

                    $resultData['list'][$k]["quote"] = (Object) $resultData3[0];
                } else {
                    $resultData['list'][$k]["quote"] = null;
                }
            } else {
                $resultData['list'][$k]["quote"] = null;
            }
        }
        return $resultData;
    }

    /**
     * 项目问题
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectQuestion($data) {
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";
        $data['question_cerattime'] = date("Y-m-d H:i:s", time());
        $data['question_creater'] = $data['user_id'];
        $data['question_state'] = isset($data['question_state']) && $data['question_state'] == 1 ? 1 : 0;
        $projectData = array_intersect_key($data, array_flip(app($this->projectQuestionRepository)->getTableColumns()));
        $result = app($this->projectQuestionRepository)->insertData($projectData);

        $questionId = $result->question_id;

        $attachments = isset($data['attachments']) ? $data['attachments'] : "";
        if ($attachments) {
            app($this->attachmentService)->attachmentRelation("project_question", $questionId, $attachments);
        }
        if ($data['question_state'] == 1) {
            ProjectLogManager::getIns($data['user_id'], $data['question_project'])->questionAddLog($result->question_name, $questionId);
            $this->sendAddQuestionMessage($data, $questionId);
        }

        return $result;
    }

    /**
     * 编辑项目问题
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectQuestion($data) {
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";
        $where = [
            'question_id' => [[$data['question_id']], '=']
        ];
        $projectInfo = app($this->projectQuestionRepository)->infoProjectQuestionbyWhere($where);

        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        } else if ($projectInfo[0]['question_state'] != 0) {
            return ['code' => ['0x036015', 'project']];
        } else if (!in_array($data['user_id'], [$projectInfo[0]['question_creater'], $projectInfo[0]['question_person']])) {
            return ['code' => ['0x036018', 'project']];
        }
        $data['question_state'] = isset($data['question_state']) && $data['question_state'] == 1 ? 1 : 0;
        $projectData = array_intersect_key($data, array_flip(app($this->projectQuestionRepository)->getTableColumns()));
        $resultStatus = app($this->projectQuestionRepository)->updateData($projectData, ['question_id' => $data['question_id']]);

        $attachments = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_question", $data['question_id'], $attachments);
        // 保存状态变更为提交状态时发送消息提醒
        if ($projectInfo[0]['question_state'] == 0 && $data['question_state'] == 1) {
            ProjectLogManager::getIns($data['user_id'], $data['question_project'])->questionAddLog($projectData['question_name'], $data['question_id']);
            $this->sendAddQuestionMessage($data, $data['question_id']);
        }
        return $resultStatus;
    }

    // 发送新建任务提醒
    private function sendAddQuestionMessage($data, $questionId) {
        $userName = app($this->userRepository)->getUserName($data['question_person']);
        $smsToUsers = trim($data["question_doperson"], ",");
        $sendData['remindMark'] = 'project-submit';
        $sendData['toUser'] = $smsToUsers;
        $sendData['contentParam'] = ['userName' => $userName]; //当前登录
        $sendData['stateParams'] = ['question_id' => $questionId, 'manager_id' => $data['question_project']];
        Eoffice::sendMessage($sendData);
    }

    /**
     * 项目列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectQuestion($data) { //批量时 只删除符合条件的
        $destroyIds = explode(",", $data['question_id']);
        foreach ($destroyIds as $delId) {
            $delData = [
                "entity_table" => "project_question",
                "entity_id" => $delId
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }

        $where = [
            'question_id' => [$destroyIds, 'in'],
                //  'task_project' => [[$data['task_project']], "="]
        ];

        $logManager = $this->initDeleteLog('question', $data['user_id'], $destroyIds);

        $res = app($this->projectQuestionRepository)->deleteByWhere($where);

        $res && $logManager && $logManager->storageFillData();

        return $res;
        //return app($this->projectQuestionRepository)->deleteProjectQuestion($data);
    }

    /**
     * 处理项目问题
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function dealProjectQuestion($data, $userId) {
        $type = Arr::get($data, 'type');
        $questionId = Arr::get($data, 'question_id');
        if (!$this->questionDealPermission($questionId, $userId, $type)){
            return ['code' => ['0x000006', 'common']];
        }

        $data['question_do'] = isset($data['question_do']) && !empty($data['question_do']) ? $data['question_do'] : "";
        $data['question_back'] = isset($data['question_back']) && !empty($data['question_back']) ? $data['question_back'] : "";
        $data['question_dotime'] = date("Y-m-d H:i:s", time());
        $data['question_backtime'] = date("Y-m-d H:i:s", time());
        switch ($type) {
            case "issueProcessing": //处理中
                $projectData = [
                    "question_state" => 2,
                    'question_do' => $data['question_do'],
                    'question_dotime' => $data['question_dotime']
                ];
                break;
            case "issueProcessed"://已处理
                $projectData = [
                    "question_state" => 3,
                    'question_do' => $data['question_do'],
                    'question_dotime' => $data['question_dotime']
                ];
                break;
            case "issueUnsolved"://未解决
                $projectData = [
                    "question_state" => 4,
                    'question_back' => $data['question_back'],
                    'question_backtime' => $data['question_backtime']
                ];
                break;
            case "issueResolved":
                $projectData = [
                    "question_state" => 5,
                    'question_back' => $data['question_back'],
                    'question_backtime' => $data['question_backtime']
                ];
                break;
        }

        return app($this->projectQuestionRepository)->updateData($projectData, ['question_id' => $data['question_id']]);
    }

    /**
     * 项目问题列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectQuestionList($data) {

        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        unset($data['manager_id']);

        return $this->response(app($this->projectQuestionRepository), 'getProjectQuestionTotal', 'getProjectQuestionList', $this->parseParams($data));
    }

    /**
     * 项目问题详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectQuestion($data) {

        //这里需要验证权限
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        //user_id
        $where = [
            'question_id' => [[$data['question_id']], '=']
        ];
        $return = app($this->projectQuestionRepository)->getOneProjectQuestion($where);

        $temp = [];
        foreach ($return as $res) {

            $res['question_persons'] = [$res['question_person']];
            $res['question_dopersons'] = [$res['question_doperson']];


            $res['question_person_name'] = app($this->userRepository)->getUserName($res['question_person']);
            $res['question_doperson_name'] = app($this->userRepository)->getUserName($res['question_doperson']);
            $res['question_creater_name'] = app($this->userRepository)->getUserName($res['question_creater']);
            $res['question_state_name'] = $this->questionTypeToName($res['question_state']);
            $temp_question_level = app($this->systemComboboxFieldRepository)->getNameByValue($res['question_level'], "PROJECT_PRIORITY");
            $res['question_level_name'] = $temp_question_level["field_name"];
            $temp[] = $res;
        }

        if (isset($temp[0])) {
            $temp[0]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'project_question', 'entity_id' => $data['question_id']]);
            return $temp[0];
        }

        return $temp;
    }

    function questionTypeToName($status_id) {
        $state_name = "";
        switch ($status_id) {
            case 0:
                $state_name = trans("project.unsubmitted");
                break;
            case 1:
                $state_name = trans("project.submission");
                break;
            case 2:
                $state_name = trans("project.in_the_process_of_processing");
                break;
            case 3:
                $state_name = trans("project.already_processed");
                break;
            case 4:
                $state_name = trans("project.unsolved");
                break;
            case 5:
                $state_name = trans("project.resolved");
                break;
        }

        return $state_name;
    }

    public function managerTypeToName($status_id) {
        $state_name = "";
        switch ($status_id) {

            case 1:
                $state_name = trans("project.in_the_project");
                break;
            case 2:
                $state_name = trans("project.examination_and_approval");
                break;
            case 3:
                $state_name = trans("project.retreated");
                break;
            case 4:
                $state_name = trans("project.have_in_hand");
                break;
            case 5:
                $state_name = trans("project.finished");
                break;
        }

        return $state_name;
    }

    /**
     * 项目文档
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addProjectDocment($data) {
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['doc_project']
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $data['doc_creattime'] = date("Y-m-d H:i:s", time());
        $data['doc_creater'] = $data['user_id'];

        $projectData = array_intersect_key($data, array_flip(app($this->projectDocumentRepository)->getTableColumns()));
        $this->filterNull($projectData);
        $result = app($this->projectDocumentRepository)->insertData($projectData);

        $docId = $result->doc_id;
        ProjectLogManager::getIns($data['user_id'], $data['doc_project'])->documentAddLog($result->doc_name, $docId);

        $attachments = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_document", $docId, $attachments);


        return $result;
    }

    /**
     * 项目文档
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editProjectDocment($data) {
        $data['attachments'] = isset($data['attachments']) ? $data['attachments'] : "";
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['doc_project']
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $where = [
            'doc_id' => [[$data['doc_id']], '='],
            'doc_creater' => [[$data['user_id']], '=']
        ];
        $projectInfo = app($this->projectDocumentRepository)->infoProjectDocumentbyWhere($where);

        if (count($projectInfo) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $projectData = array_intersect_key($data, array_flip(app($this->projectDocumentRepository)->getTableColumns()));
        $resultStatus = app($this->projectDocumentRepository)->updateData($projectData, ['doc_id' => $data['doc_id']]);

        $attachments = isset($data['attachments']) ? $data['attachments'] : "";

        app($this->attachmentService)->attachmentRelation("project_document", $data['doc_id'], $attachments);

        return $resultStatus;
    }

    /**
     * 项目文档删除
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteProjectDocment($data) {

        $destroyIds = explode(",", $data['doc_id']);
        if (!$destroyIds) {
            return true;
        }
        foreach ($destroyIds as $delId) {
            $delData = [
                "entity_table" => "project_document",
                "entity_id" => $delId
            ];
            app($this->attachmentService)->deleteAttachmentByEntityId($delData);
        }
        $where = [
            'doc_id' => [$destroyIds, 'in'],
            'doc_creater' => [[$data['user_id']], '=']
        ];

        $logManager = $this->initDeleteLog('doc', $data['user_id'], $destroyIds);

        $res = app($this->projectDocumentRepository)->deleteByWhere($where);
        $res && $logManager && $logManager->storageFillData();

        return $res;
    }

    //批量下载文档中的附件
    public function batchDownloadAttachments($data, $own)
    {
        $managerId = Arr::get($data, 'manager_id', 0);

        $param = [
            'user_id' => $own['user_id'],
            'manager_id' => $managerId
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $where = [
            'doc_project' => [$managerId, '=']
        ];
        $docs = app($this->projectDocumentRepository)->getInfobyWhere($where);
        //下载
        $existDocIds = Arr::pluck($docs, 'doc_id');
        $existDocNames = Arr::pluck($docs, 'doc_name', 'doc_id');
        $attachmentIds = app($this->attachmentService)->getAttachmentsByEntityIds('project_document', $existDocIds);
        $downloadAttachmentIds = [];
        foreach ($attachmentIds as $docId => $ids) {
            $docName = Arr::get($existDocNames, $docId, '无名称文档附件');
            if (isset($downloadAttachmentIds[$docName])) {
                $downloadAttachmentIds[$docName] = array_merge($downloadAttachmentIds[$docName], $ids);
            } else {
                $downloadAttachmentIds[$docName] = $ids;
            }
        }

        app($this->attachmentService)->downZipByFolder($downloadAttachmentIds);

        return true;
    }

    //项目是否拥有文档附件
    public function hasAttachments($managerId, $own)
    {
        $param = [
            'user_id' => $own['user_id'],
            'manager_id' => $managerId
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $where = [
            'doc_project' => [$managerId, '=']
        ];
        $docs = app($this->projectDocumentRepository)->getInfobyWhere($where);
        if (empty($docs)) {
            return ['has_attachments' => false];
        }
        $existDocIds = Arr::pluck($docs, 'doc_id');
        $attachmentIds = app($this->attachmentService)->getAttachmentsByEntityIds('project_document', $existDocIds);

        return ['has_attachments' => !empty($attachmentIds)];
    }

    /**
     * 项目文档列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getProjectDocmentList($data) {

        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        unset($data['user_id']);
        unset($data['manager_id']);

        return $this->response(app($this->projectDocumentRepository), 'getProjectDocmentTotal', 'getProjectDocmentList', $this->parseParams($data));
    }

    /**
     * 项目文档详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getOneProjectDocment($data) {

        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $where = [
            'doc_id' => [[$data['doc_id']], '=']
        ];
        $return = app($this->projectDocumentRepository)->infoProjectDocumentbyWhere($where);

        if (isset($return[0])) {
            $return[0]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'project_document', 'entity_id' => $data['doc_id']]);
            return $return[0];
        } else {
            return [];
        }
    }

    //判断一个人在项目任务中的权限[增加|编辑|删除]
    public function getProjectTaskPrivate($data) {

        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $status = true;
        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        $mps = explode(",", $projects[0]['manager_person']);
        $mms = explode(",", $projects[0]['manager_monitor']);
        $mes = explode(",", $projects[0]['manager_examine']);

        switch ($manager) {
            case 1 :
                $mps[] = $projects[0]['manager_creater'];
                if (!in_array($data['user_id'], $mps)) {
                    $status = ['code' => ['0x036018', 'project']];
                }
                break;
            case 2:
                if (!in_array($data['user_id'], $mps)) {
                    $status = ['code' => ['0x036018', 'project']];
                }
                break;
            case 3:
                $mps[] = $projects[0]['manager_creater'];
                if (!in_array($data['user_id'], $mps)) {
                    $status = ['code' => ['0x036018', 'project']];
                }
                break;
            case 4:
                $mpmm = array_merge($mps, $mms);
                if (!in_array($data['user_id'], $mpmm)) {
                    $status = ['code' => ['0x036018', 'project']];
                }
                break;
            case 5:
                $status = ['code' => ['0x036019', 'project']];
                break;
            default:
                $status = ['code' => ['0x036002', 'project']];
                break;
        }



        return $status;
    }

    //判断一个人在项目任务中的查看权限
    public function getProjectTaskSelects($data) {
        if ($this->hasReportMenu()) {
            return true;
        }
        //manager_id
        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $mps = explode(",", $projects[0]['manager_person']);
        $mms = explode(",", $projects[0]['manager_monitor']);
        $mes = explode(",", $projects[0]['manager_examine']);

        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;
        $status = true;
        switch ($manager) {
            case 1 :

                $mps[] = $projects[0]['manager_creater'];

                if (!in_array($data['user_id'], $mps)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 2:

                $mpmm = array_merge($mps, $mes);
                if (!in_array($data['user_id'], $mpmm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 3:

                $mps[] = $projects[0]['manager_creater'];
                $mpmm = array_merge($mps, $mes);
                if (!in_array($data['user_id'], $mpmm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 4:
            case 5:

                $dataTemp = $this->getProjectTeams($data);
                $dataTeams = explode(",", $dataTemp);

                $cmpmm = array_merge($mps, $mms, $mes, $dataTeams);
                //$cmpmm = array_merge($mpmm,$dataTeams);
                if (!in_array($data['user_id'], $cmpmm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;

            default:
                return ['code' => ['0x036002', 'project']];
                break;
        }



        return $status;
    }

    //判断一个人在项目任务中的处理权限
    public function getProjectTaskDeal($data) {


        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere(['manager_id' => [[$data['manager_id']], '=']]);

        $tasks = app($this->projectTaskRepository)->infoProjectTaskbyWhere([
            'task_id' => [[$data['task_id']], '='],
            'task_project' => [[$data['manager_id']], '=']
        ]);

        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $mps = explode(",", $projects[0]['manager_person']);
        $mms = explode(",", $projects[0]['manager_monitor']);



        $status = true;
        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        switch ($manager) {
            case 1 :
            case 3 :
                $mps[] = $projects[0]['manager_creater'];
                if (!in_array($data['user_id'], $mps)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 2:
                if (!in_array($data['user_id'], $mps)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;

            case 4:
                $mps[] = $tasks[0]['task_persondo'];
                $mpmm = array_merge($mps, $mms);
                if (!in_array($data['user_id'], $mpmm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            default:
                return ['code' => ['0x036002', 'project']];
                break;
        }



        return $status;
    }

    //判断用户在项目中编辑权限
    public function getProjectEditPrivate($data) {
        // manager_id
        // user_id
        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        $status = true;


        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $mps = explode(",", $projects[0]['manager_person']);
        $mms = explode(",", $projects[0]['manager_monitor']);


        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        switch ($manager) {
            case 1 :
            case 3 :
                $mps[] = $projects[0]['manager_creater'];
                if (!in_array($data['user_id'], $mps)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 2:

                return ['code' => ['0x036020', 'project']];

                break;

            case 4:
                if (!in_array($data['user_id'], $mms)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;

            default:
                return ['code' => ['0x036020', 'project']];
                break;
        }



        return $status;
    }

    //判断用户在项目中删除权限
    public function getProjectdeletePrivate($data) {
        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        $status = true;


        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $mps = explode(",", $projects[0]['manager_person']);
        $mms = explode(",", $projects[0]['manager_monitor']);


        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        switch ($manager) {
            case 1 :
            case 3 :
                $mps[] = $projects[0]['manager_creater'];
                if (!in_array($data['user_id'], $mps)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 2:
                return ['code' => ['0x036020', 'project']];
                break;

            case 4:
                if (!in_array($data['user_id'], $mms)) {
                    $status = ['code' => ['0x036018', 'project']];
                }
                break;
            case 5:
                $mpmm = array_merge($mps, $mms);
                if (!in_array($data['user_id'], $mpmm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            default:
                return ['code' => ['0x036002', 'project']];
                break;
        }
        return $status;
    }

    //判断用户在项目中提交
    public function getProjectApprovalPrivate($data) {
        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        $status = true;

        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $mps = explode(",", $projects[0]['manager_person']);


        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        switch ($manager) {
            case 1 :
            case 3 :
                $mps[] = $projects[0]['manager_creater'];
                if (!in_array($data['user_id'], $mps)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;

            default:
                return ['code' => ['0x036022', 'project']];
                break;
        }


        return $status;
    }

    //判断用户在项目中查看审核
    public function getProjectCheckPrivate($data) {
        if ($this->hasReportMenu()) {
            return true;
        }
        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        $status = true;

        if (!isset($projects[0])) {
            return ['code' => ['0x036002', 'project']];
        }
        $mps = explode(",", $projects[0]['manager_person']);
        $mms = explode(",", $projects[0]['manager_monitor']);
        $mes = explode(",", $projects[0]['manager_examine']);

        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        switch ($manager) {
            case 1 :
                $mps[] = $projects[0]['manager_creater'];
                if (!in_array($data['user_id'], $mps)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 2:

                $mpme = array_merge($mps, $mes);

                if (!in_array($data['user_id'], $mpme)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 3:

                $mps[] = $projects[0]['manager_creater'];
                $mpme = array_merge($mps, $mes);

                if (!in_array($data['user_id'], $mpme)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            case 4:
            case 5:

                $dataTemp = $this->getProjectTeams($data);
                $dataTeams = explode(",", $dataTemp);
                $mpmemm = array_merge($mps, $mes, $mms, $dataTeams);

                if (!in_array($data['user_id'], $mpmemm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;

            default:
                return ['code' => ['0x036002', 'project']];
                break;
        }



        return $status;
    }

    //判断用户在项目中批准|退回审核
    public function getProjectExaminePrivate($data) {
        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);

        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }

        $mes = explode(",", $projects[0]['manager_examine']);

        $status = true;
        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        switch ($manager) {

            case 2:
                if (!in_array($data['user_id'], $mes)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;

            default:
                return ['code' => ['0x036022', 'project']];
                break;
        }

        return $status;
    }

    //判断用户在项目中启动权限
    public function getProjectStartPrivate($data) {
        $where = [
            'manager_id' => [[$data['manager_id']], '=']
        ];
        $projects = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);

        if (count($projects) == 0) {
            return ['code' => ['0x036002', 'project']];
        }
        $mps = explode(",", $projects[0]['manager_person']);
        $mms = explode(",", $projects[0]['manager_monitor']);

        $status = true;
        $manager = isset($projects[0]['manager_state']) && !empty($projects[0]['manager_state']) ? $projects[0]['manager_state'] : 0;

        switch ($manager) {

            case 5:

                $mpmm = array_merge($mps, $mms);

                if (!in_array($data['user_id'], $mpmm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;

            default:
                return ['code' => ['0x036022', 'project']];
                break;
        }

        return $status;
    }

    //判断用户在项目中结束权限
    public function getProjectEndPrivate($data) {

        $userId = isset($data["user_id"]) ? $data["user_id"] : "";
        $manager_id = isset($data["manager_id"]) ? $data["manager_id"] : "";
        if (!$manager_id) {
            return ['code' => ['0x036001', 'project']];
        }
        $projectInfo = app($this->projectManagerRepository)->getDetail($manager_id);

        $status = true;

        if (empty($projectInfo)) {
            return ['code' => ['0x036024', 'project']];
        }
        $mps = explode(",", $projectInfo['manager_person']);
        $mms = explode(",", $projectInfo['manager_monitor']);

        $manager = isset($projectInfo['manager_state']) && !empty($projectInfo['manager_state']) ? $projectInfo['manager_state'] : 0;

        switch ($manager) {
            case 4:

                $mpmm = array_merge($mps, $mms);

                if (!in_array($userId, $mpmm)) {
                    return ['code' => ['0x036018', 'project']];
                }
                break;
            default:
                return ['code' => ['0x036022', 'project']];
                break;
        }

        return $status;
    }

    public function getOneProject($manager_id, $user_id) {
        //判断权限
        $param = [
            'user_id' => $user_id,
            'manager_id' => $manager_id
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {

            return "error_403";
            //return ['code' => ['0x000006', 'common']];
        }
        //获取项目权限，手机端项目详情页控制添加任务的按钮显示状态
        $managerTaskStatus = $this->getProjectTaskPrivate($param);
        $managerTaskStatus = $managerTaskStatus === true ? true : false;

        $where = [
            'manager_id' => [$manager_id, '=']
        ];
        $projectInfo = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        $temp = [];

        foreach ($projectInfo as $project) {

            $projectExamines = $project['manager_examine'] ? explode(",", trim($project['manager_examine'],",")) : [];
            $projectPersons = $project['manager_person'] ? explode(",", trim($project['manager_person'],",")) : [];
            $projectMonitors = $project['manager_monitor'] ? explode(",", trim($project['manager_monitor'],",")) : [];
            $teamPersons = $project['team_person'] ? explode(",", trim($project['team_person'],",")) : [];
            $tempExamines = array();
            $tempPersons = array();
            $tempMonitors = array();
            $tempTeamPersons = array();

            foreach ($projectExamines as $key => $v) {
                $name = app($this->userRepository)->getUsersNameByIds([$v]);
                if ($name) {

                    $tempExamines[] = [
                        "user_id" => $v,
                        "user_name" => $name[0],
                    ];
                }
            }

            foreach ($projectPersons as $key => $v) {
                $name = app($this->userRepository)->getUsersNameByIds([$v]);
                if ($name) {

                    $tempPersons[] = [
                        "user_id" => $v,
                        "user_name" => $name[0],
                    ];
                }
            }

            foreach ($projectMonitors as $key => $v) {
                $name = app($this->userRepository)->getUsersNameByIds([$v]);
                if ($name) {

                    $tempMonitors[] = [
                        "user_id" => $v,
                        "user_name" => $name[0],
                    ];
                }
            }

            foreach ($teamPersons as $key => $v) {
                $name = app($this->userRepository)->getUsersNameByIds([$v]);
                if ($name) {

                    $tempTeamPersons[] = [
                        "user_id" => $v,
                        "user_name" => $name[0],
                    ];
                }
            }

            $project['persons'] = $tempPersons;
            $project['examines'] = $tempExamines;
            $project['monitors'] = $tempMonitors;
            $project['teams'] = $tempTeamPersons;

            $project['manager_examines'] = $projectExamines;
            $project['manager_persons'] = $projectPersons;
            $project['manager_monitors'] = $projectMonitors;
            $project['team_persons'] = $teamPersons;

            $project['manager_creater_name'] = app($this->userRepository)->getUserName($project['manager_creater']);

            $temp1 = app($this->systemComboboxFieldRepository)->getNameByValue($project['manager_fast'], "PROJECT_DEGREE");
            $project['manager_fast_name'] = $temp1["field_name"];
            $temp2 = app($this->systemComboboxFieldRepository)->getNameByValue($project['manager_level'], "PROJECT_PRIORITY");
            $project['manager_level_name'] = $temp2["field_name"];

            $project['manager_state_name'] = $this->managerTypeToName($project['manager_state']);


            //获取项目类型
            $temp1 = app($this->systemComboboxFieldRepository)->getNameByValue($project["manager_type"], "PROJECT_TYPE");
            $project["type_name"] = $temp1["field_name"];

            // 取自定义字段的值
            $customProjectInfo = app($this->formModelingService)->getCustomDataDetail("project_value_".$project["manager_type"],$manager_id);
            if(count($customProjectInfo)) {
                foreach ($customProjectInfo as $key => $value) {
                    $project[$key] = $value;
                }
            }

            $temp[] = $project;
        }
        if (count($temp)) {
            $temp = $temp[0];
        }
        $temp['manager_task_status'] = $managerTaskStatus;//管理任务的权限
        $temp['is_manager'] = self::isManager($temp, $user_id);
        $temp['is_monitor'] = self::isMonitor($temp, $user_id);
        $temp['is_examine'] = self::isExamine($temp, $user_id);

        return $temp;
    }

    //获取当前任务处理的详细
    public function getOneProjectTaskHandle($taskdiary_id, $data) {

        //验证权限
        $param = [
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id']
        ];
        //验证权限
        $status = $this->getProjectCheckPrivate($param);
        if ($status !== true) {
            return ['code' => ['0x000006', 'common']];
        }

        $result = app($this->projectTaskDiaryRepository)->getTaskDiaryDetail($taskdiary_id);

        if (isset($result[0])) {
            $result[0]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'project_task_diary', 'entity_id' => $taskdiary_id]);
            return $result[0];
        }
        return [];
    }

    //获取项目所有相关用户 条件：项目ID
//    public function getProjectUsers($manager_id, $data) {//111
//        $data["manager_id"] = $manager_id;
//        $teams = $this->getProjectTeams($data);
//        $projectData = app($this->projectManagerRepository)->getDetail($manager_id);
//        $projectTeam = app($this->projectTeamRepository)->infoProjectTeambyWhere(['team_project' => [$manager_id]]);
//
//        $tempUser = "";
//        $tempUser = $projectData->manager_person . ',' . $projectData->manager_examine;
//        if ($projectData->manager_monitor) {
//            $tempUser.="," . $projectData->manager_monitor;
//        }
//        $teams = "";
//        foreach ($projectTeam as $v) {
//            $teams .= $v['team_person'] . ",";
//        }
//
//        if ($teams) {
//            $teams = trim($teams, ",");
//            $tempUser .="," . $teams;
//        }
//
//        $where["page"] = isset($data["page"]) && $data["page"] ? $data["page"] : "";
//        $where["limit"] = isset($data["limit"]) && $data["limit"] ? $data["limit"] : "";
//
//        $teamsArray = array_unique(explode(',', $tempUser));
//        $where['search'] = [
//            "user_id" => [$teamsArray, "in"]
//        ];
//
//
//        if (isset($data["search"]) && $data["search"]) {
//            $data = $this->parseParams($data);
//            $where = array_merge($where, array_filter($data));
//        }
//
//        $data = app($this->userRepository)->getAllUsers($where);
//        $result = [
//            "total" => count($teamsArray),
//            "list" => $data
//        ];
//
//        return $result;
//    }

    public function mobileProjectIndex($user_id, $data) {
        $data['user_id'] = $user_id;
        //当前用户 user_id
        $projects = $this->getProjectsByUser($data['user_id']);
        $projectsObject = collect($projects);
        $teams          = $projectsObject->pluck("team_project")->toArray();
        $teamstr = implode(",", $teams);
        $data['user_team_project'] = $teamstr;

        $data["order_by_field"] = $this->getOrderByfield();

        $getProjectParams = $this->parseParams($data);
        // 手机版，走的是筛选的路子！！
        // 默认选中[未结束]，值为 unFinal ，表示隐藏已经结束的项目；
        if(isset($data["showFinalPorjectFlag"]) && $data["showFinalPorjectFlag"] == "unFinal") {
            $getProjectParams["search"]["manager_state"] = ["5","!="];
        }
        // 选中筛选项[结束]，值为 final  表示展示已经结束的项目
        if(isset($data["showFinalPorjectFlag"]) && $data["showFinalPorjectFlag"] == "final") {
            $getProjectParams["search"]["manager_state"] = ["5"];
        }
        //获取所有的项目
        $resultProjects = $this->response(app($this->projectManagerRepository), "getProjectAllTotal", "getProjectAllList", $getProjectParams);


        $result = [];

        $projectStatusList = $this->getCurrentListStatusByUserId($user_id, "project");


        if(isset($resultProjects['list']) && count($resultProjects['list'])) {


            // 计算项目下属任务数量
            $taskCountAll = app($this->projectTaskRepository)->getTaskCountGroupByProject(null, ['tree_level' => [1, '=']]);
            $taskCountAllInfo = [];
            if(!empty($taskCountAll)) {
                foreach ($taskCountAll as $key => $value) {
                    if(isset($value["task_project"]) && $value["task_project"] > 0) {
                        $taskCountAllInfo[$value["task_project"]] = [
                            "task_count"   => isset($value["task_count"]) ? $value["task_count"] : 0,
                            "task_persent" => isset($value["task_persent"]) ? $value["task_persent"] : 0
                        ];
                    }
                }
            }
            // 获取全部任务的项目状态，按项目id分组
            $taskStatusCountAll = app($this->projectTaskRepository)->getTaskStatusGroupByProject($user_id);
            $taskStatusCountAllInfo = [];
            if(!empty($taskStatusCountAll)) {
                foreach ($taskStatusCountAll as $key => $value) {
                    if(isset($value["task_project"]) && $value["task_project"] > 0) {
                        $taskStatusCountAllInfo[$value["task_project"]] = isset($value["count"]) ? $value["count"] : 0;
                    }
                }
            }
            // 计算项目下属问题数量
            $questionCountAll = app($this->projectQuestionRepository)->getQuestionCountGroupByProject($user_id);
            $questionCountAllInfo = [];
            if(!empty($questionCountAll)) {
                foreach ($questionCountAll as $key => $value) {
                    if(isset($value["question_project"]) && $value["question_project"] > 0) {
                        $questionCountAllInfo[$value["question_project"]] = isset($value["question_count"]) ? $value["question_count"] : 0;
                    }
                }
            }
            // 计算项目下属文档数量
            $docCountAll = app($this->projectDocumentRepository)->getDocumentCountGroupByProject();
            $docCountAllInfo = [];
            if(!empty($docCountAll)) {
                foreach ($docCountAll as $key => $value) {
                    if(isset($value["doc_project"]) && $value["doc_project"] > 0) {
                        $docCountAllInfo[$value["doc_project"]] = isset($value["doc_count"]) ? $value["doc_count"] : 0;
                    }
                }
            }
            // 取自定义字段那里配置的项目类型的名称
            $typesAllInfo = app($this->systemComboboxFieldRepository)->getSystemComboboxFieldNameAll("PROJECT_TYPE");

            foreach ($resultProjects['list'] as $v) {

                //获取项目类型
                if(isset($v["manager_type"])) {
                    $typesItem = isset($typesAllInfo[$v["manager_type"]]) ? $typesAllInfo[$v["manager_type"]] : [];
                    $v["manager_type_name"] = isset($typesItem["field_name"]) ? $typesItem["field_name"] : "";
                }
                // $taskCount = app($this->projectTaskRepository)->projectCountSumPersent($v["manager_id"]);
                $taskCountPersentItem = isset($taskCountAllInfo[$v["manager_id"]]) ? $taskCountAllInfo[$v["manager_id"]] : [];
                $taskCount = isset($taskCountPersentItem["task_count"]) ? $taskCountPersentItem["task_count"] : 0;
                $v['task_count'] = $taskCount;
                // $questionCount = app($this->projectQuestionRepository)->projectQuestionCount($v["manager_id"], $user_id);
                $questionCount = isset($questionCountAllInfo[$v["manager_id"]]) ? $questionCountAllInfo[$v["manager_id"]] : 0;
                $v['question_count'] = $questionCount;
                // $docCount = app($this->projectDocumentRepository)->projectDocumentCount($v["manager_id"]);
                $docCount = isset($docCountAllInfo[$v["manager_id"]]) ? $docCountAllInfo[$v["manager_id"]] : 0;
                $v['doc_count'] = $docCount;
                // $diaryCount = app($this->projectTaskRepository)->projectPersent($v["manager_id"]);
                $diaryCount = isset($taskCountPersentItem["task_persent"]) ? $taskCountPersentItem["task_persent"] : 0;

                $taskStatusCountItem = isset($taskStatusCountAllInfo[$v["manager_id"]]) ? $taskStatusCountAllInfo[$v["manager_id"]] : 0;
                // $v["task_new_feedback"] = $this->getCurrentProjectTaskStatusByMangerId($user_id, $v["manager_id"]) > 0 ? 1 : 0;
                $v["task_new_feedback"] = $taskStatusCountItem > 0 ? 1 : 0;

                // $diaryCount = app($this->projectTaskRepository)->projectPersent($v["manager_id"]);

                if ($taskCount == "0" || $diaryCount == "0") {
                    $v['plan'] = "0"; //222
                } else {
                    $persent = $diaryCount / $taskCount;
                    $v['plan'] = round($persent);
                }

                $v["project_new_disscuss"] = 0;
                if (in_array($v["manager_id"], $projectStatusList)) {
                    $v["project_new_disscuss"] = 1;
                }

                array_push($result, $v);
            }
        }
        $finalData = [
            "total" => isset($resultProjects['total']) ? $resultProjects['total'] : 0,
            "list" => $result,
        ];
        return $finalData;
    }

    public function getAppProject($manager_id, $user_id) {

        $dataInfo = app($this->projectManagerRepository)->getDetail($manager_id);
        if (!$dataInfo) {
            return [];
        }

        $projectStatusList = $this->getCurrentListStatusByUserId($user_id, "project");
        $dataInfo->project_new_disscuss = 0;
        if (in_array($manager_id, $projectStatusList)) {
            $dataInfo->project_new_disscuss = 1;
        }

        //获取任务信息
        //获取项目的进度 任务书 团队用户等

        $types = app($this->systemComboboxFieldRepository)->getNameByValue($dataInfo->manager_type, "PROJECT_TYPE");

        $dataInfo->manager_type_name = $types["field_name"];
        $taskCount = app($this->projectTaskRepository)->projectCountSumPersent($manager_id);
        $dataInfo->task_count = $taskCount;
        $questionCount = app($this->projectQuestionRepository)->projectQuestionCount($manager_id, $user_id);
        $dataInfo->question_count = $questionCount;
        $docCount = app($this->projectDocumentRepository)->projectDocumentCount($manager_id);
        $dataInfo->doc_count = $docCount;
        //团队用户个数
        $result = app($this->projectTeamRepository)->infoProjectTeambyWhere(['team_project' => [[$manager_id], '=']]);
        $teams = "";
        foreach ($result as $v) {
            $teams .= $v['team_person'] . ",";
        }
        $teams = trim($teams, ",");
        //去重复
        if ($teams) {
            $dataInfo->team_count = count(array_unique(explode(',', $teams)));
        } else {
            $dataInfo->team_count = 0;
        }


        //讨论个数
        $dissCount = app($this->projectDiscussRepository)->projectDisscussCount($manager_id);
        $dataInfo->diss_count = $dissCount;

        //任务级别

        $temp1 = app($this->systemComboboxFieldRepository)->getNameByValue($dataInfo->manager_level, "PROJECT_PRIORITY");
        $dataInfo->managerLevel = $temp1["field_name"];
        $temp2 = app($this->systemComboboxFieldRepository)->getNameByValue($dataInfo->manager_fast, "PROJECT_DEGREE");
        $dataInfo->managerFast = $temp2["field_name"];

        $dataInfo->managerState = $this->managerTypeToName($dataInfo->manager_state);


        $diaryCount = app($this->projectTaskRepository)->projectPersent($manager_id);

        if ($taskCount == "0" || $diaryCount == "0") {
            $dataInfo->plan = "0%"; //222
        } else {
            $persent = $diaryCount / $taskCount;

            $dataInfo->plan = round($persent) . "%";
        }

        $dataInfo->manager_creater_name = app($this->userRepository)->getUserName($dataInfo->manager_creater);

        return $dataInfo;
    }

    public function getTeamsAppList($data) {
        $where = [
            'team_project' => [[$data['manager_id']], '=']
        ];
        $temp = app($this->projectTeamRepository)->getOneProjectTeam($where);
        if (!isset($temp[0])) {
            return [];
        }

        $users = explode(",", $temp[0]["team_person"]);

        $param["search"] = [
            "user_id" => [$users, "in"]
        ];

        $param = array_merge($param, $data);
        $param = $this->parseParams($param);

        return $this->response(app($this->userRepository), 'getUserListTotal', 'getUserList', $param);
    }

    // 流程数据外发配置了项目模块的时候，项目这接收数据并生产项目的地方--20171208废弃
    public function flowOutSendToProject($data) {

        if (!isset($data["manager_name"]) || empty($data["manager_name"])) {
            return ['code' => ['0x036001', 'project']];
        }

        if (!isset($data["manager_begintime"]) || empty($data["manager_begintime"])) {
            return ['code' => ['0x036001', 'project']];
        }

        if (!isset($data["manager_endtime"]) || empty($data["manager_endtime"])) {
            return ['code' => ['0x036001', 'project']];
        }

        if (!isset($data["manager_type"]) || empty($data["manager_type"])) {
            return ['code' => ['0x036001', 'project']];
        }else{
            $field_name = $data["manager_type"];
            $data["manager_type"] = app($this->systemComboboxFieldRepository)->getValueByComboboxIdentify("PROJECT_TYPE", "$field_name");
        }

        if (isset($data["manager_fast"]) && !empty($data["manager_fast"])) {
            $field_name = $data["manager_fast"];
            $data["manager_fast"] = app($this->systemComboboxFieldRepository)->getValueByComboboxIdentify("PROJECT_DEGREE", "$field_name");
        }

        if (isset($data["manager_level"]) && !empty($data["manager_level"])) {
            $field_name = $data["manager_level"];
            $data["manager_level"] = app($this->systemComboboxFieldRepository)->getValueByComboboxIdentify("PROJECT_PRIORITY", "$field_name");
        }

        if (!isset($data["manager_creater"]) || empty($data["manager_creater"])) {
            return ['code' => ['0x036001', 'project']];
        }

        if (!isset($data["manager_person"]) || empty($data["manager_person"])) {
            $data["manager_person"] = $data["manager_creater"];
        }
        if (!isset($data["manager_examine"]) || empty($data["manager_examine"])) {
            $data["manager_examine"] = $data["manager_creater"];
        }
        if (!isset($data["team_person"]) || empty($data["team_person"])) {
             $data["team_person"] = $data["manager_creater"];
        }
        $data['user_id'] = $data["manager_creater"];
        $data["manager_state"] = "4";
        return $this->addProjectManager($data);
    }

    public function managerModifyStatus($data, $userId) {
        $manager_id = isset($data["manager_id"]) ? $data["manager_id"] : "";
        if (!$manager_id) {
            return ['code' => ['0x036001', 'project']];
        }
        $dataInfo = app($this->projectManagerRepository)->getDetail($manager_id);
        if (!$dataInfo) {
            return ['code' => ['0x036002', 'project']];
        }

        $manager_monitor = explode(",", $dataInfo["manager_monitor"]);
        $manager_person = explode(",", $dataInfo["manager_person"]);

        $managerStauts = isset($data["manager_state"]) ? $data["manager_state"] : "1";
        // 4变5，结束，判断项目负责人权限以及项目监控人权限
        if($managerStauts == "5") {
            $purviewInfo = array_merge($manager_person, $manager_monitor);
        } else {
            // 其他项目状态变更，判断项目负责人权限
            $purviewInfo = $manager_person;
        }
        if (!in_array($userId, $purviewInfo)) {
            return ['code' => ['0x036023', 'project']];
        }

        //更新
        $dataInfo->fill(["manager_state" => $managerStauts]);
        $status = $this->saveWithLog($dataInfo, 'project', $userId, $manager_id);
        $logData = [
            'log_content' => trans('project.modify_project_id_state',["task_id" => $data["manager_id"],"task_status" => $managerStauts]),
            'log_type' => 1,
            'log_creator' => $userId,
            'log_time' => date('Y-m-d H:i:s'),
            'log_ip' => getClientIp(),
            'log_relation_table' => 'project_manager',
        ];

        add_system_log($logData);
        if ($managerStauts == 5) {
            $this->emitCalendarDelete($manager_id, $userId, 'complete');
        } else if ($managerStauts == 4) {
            $data = $dataInfo->toArray();
            $this->emitCalendarDelete($manager_id, $userId, 'delete');
            $this->emitCalendarAdd($data, $userId);
        }
        return $status;
    }

    public function projectAppraisal($data, $userId) {
        $manager_id = isset($data["manager_id"]) ? $data["manager_id"] : "";
        if (!$manager_id) {
            return ['code' => ['0x036001', 'project']];
        }
        $dataInfo = app($this->projectManagerRepository)->getDetail($manager_id);
        if (!$dataInfo) {
            return ['code' => ['0x036002', 'project']];
        }

        $managerPerson = explode(",", $dataInfo["manager_person"]);
        if (!in_array($userId, $managerPerson)) {
            return ['code' => ['0x036023', 'project']];
        }

        // 考核分数
        $managerAppraisal = isset($data["manager_appraisal"]) ? $data["manager_appraisal"] : "";
        // 考核备注
        $managerAppraisalFeedback = isset($data["manager_appraisal_feedback"]) ? $data["manager_appraisal_feedback"] : "";
        return app($this->projectManagerRepository)->updateData(["manager_appraisal" => $managerAppraisal, "manager_appraisal_feedback" => $managerAppraisalFeedback], ["manager_id" => [$manager_id]]);
    }

    //项目所有的参与人：负责人 审核人
    public function getProjectParticipants($manager_id) {
        $where = [
            "manager_id" => [$manager_id]
        ];

        $projectInfo = app($this->projectManagerRepository)->infoProjectManagerbyWhere($where);
        $temp = [];
        foreach ($projectInfo as $project) {
            $projectExamines = explode(",", $project['manager_examine']);
            $projectPersons = explode(",", $project['manager_person']);
            $projectMonitors = explode(",", $project['manager_monitor']);
            $teamPersons = explode(",", $project['team_person']);


            foreach ($projectExamines as $key => $v) {
                $temp[] = $v;
            }
            foreach ($projectPersons as $key => $v) {

                if (!in_array($v, $temp)) {
                    $temp[] = $v;
                }
            }

            foreach ($projectMonitors as $key => $v) {
                if (!in_array($v, $temp)) {
                    $temp[] = $v;
                }
            }

            foreach ($teamPersons as $key => $v) {
                if (!in_array($v, $temp)) {
                    $temp[] = $v;
                }
            }
        }

        return $temp;
    }

    public function insertProjectStatus($data) {
        $data['user_id'] = isset($data["user_id"]) && $data["user_id"] ? $data["user_id"] : "";
//        $data : type  relation_id  participant remind_flag
        $relationId = isset($data["relation_id"]) && $data["relation_id"] ? $data["relation_id"] : "";
        $type = isset($data["type"]) ? $data["type"] : "project";
        $managerId = isset($data["manager_id"]) && $data["manager_id"] ? $data["manager_id"] : "";
        if (!$relationId || !$managerId) {
            return false;
        }
        //删除之前的数据
        app($this->projectStatusRepository)->deleteByWhere(["relation_id" => [$relationId], "type" => [$type]]);

        $participants = $this->getProjectParticipants($managerId);
        foreach ($participants as $key => $value) {
           if ($value == $data['user_id']) {
               unset($participants[$key]);
           }
        }
        $insertData = [];
        foreach ($participants as $participant) {
            if ($participant) {
                $projectData['participant'] = $participant;
                $projectData['type'] = $type;
                $projectData['remind_flag'] = 0;
                $projectData['relation_id'] = $relationId;
                $insertData[] = $projectData;
            }
        }
        app($this->projectStatusRepository)->insert($insertData);
    }

    //项目列表 任务列表 是否已读
    //条件： 用户ID type  返回未读的项目ID集合
    public function getCurrentListStatusByUserId($userId, $type = "project", array $relationIds = null) {

        $where = [
            "participant" => [$userId],
            "remind_flag" => [0],
            "type" => [$type]
        ];

        if (!is_null($relationIds)) {
            $where['relation_id'] = [$relationIds, 'in'];
        }

        $temp = app($this->projectStatusRepository)->getDataByWhere($where);
        $result = [];
        foreach ($temp as $v) {
            $result[] = $v["relation_id"];
        }

        return $result;
    }

    public function updateProjectStatus($data) {

        $userId = isset($data["user_id"]) ? $data["user_id"] : "";
        $relation_id = isset($data["relation_id"]) && $data["relation_id"] ? $data["relation_id"] : "";
        $type = isset($data["type"]) ? $data["type"] : "project";

        if (!$relation_id) {
            return false;
        }

        $where = [
            "participant" => [$userId],
            "relation_id" => [$relation_id],
            "type" => [$type]
        ];

        return app($this->projectStatusRepository)->updateDataBatch(["remind_flag" => 1], $where);
    }

    public function getCurrentProjectTaskStatusByMangerId($user_id, $manager_id) {
        $temp = app($this->projectTaskRepository)->getCurrentProjectTaskStatusByMangerId($user_id, $manager_id);
        return $temp;
    }

    /**
     * 项目模块，返回报表需要的数据
     * @param  [type] $datasource_group_by      [description]
     * @param  [type] $datasource_data_analysis [description]
     * @param  [type] $chart_search             [description]
     * @return [type]                           [description]
     */
     public function getProjectReportData($datasource_group_by,$datasource_data_analysis,$chart_search)
    {
        $field = [];
        switch ($datasource_group_by)
        {
            case 'manager_creater'://项目创建人
                $db_query = DB::select('select   manager_creater,user_name  from  project_manager,user  where  manager_creater  =  user_id   group by manager_creater;');
                foreach($db_query as $v){
                    $reportData[$v->manager_creater] = ['name'=>$v->user_name,'y'=>0];
                }
                $group_by_field = "manager_creater";
                break;
            case 'manager_person'://项目负责人
                // $db_query = DB::select('select   manager_person,user_name  from  project_manager,user  where  manager_person  =  user_id   group by manager_person;');
                // foreach($db_query as $v){
                //     $reportData[$v->manager_person] = ['name'=>$v->user_name,'y'=>0];
                // }
                $getListParams = [];
                $getListParams["page"] = "0";
                $getListParams["search"]["manager_person"] = ["","!="];
                $getListParams["fields"] = ["manager_person"];
                $getListParams["distinct_flag"] = "1";
                $projectList = app($this->projectManagerRepository)->getProjectManagerList($getListParams);
                $personUserData = [];
                if(count($projectList)) {
                    foreach ($projectList as $key => $value) {
                        if($value["manager_person"]) {
                            $managerPersonString = $value["manager_person"];
                            $managerPersonString = trim($managerPersonString,",");
                            $managerPersonArray = explode(",", $managerPersonString);
                            if(count($managerPersonArray)) {
                                foreach ($managerPersonArray as $managerPersonKey => $managerPersonValue) {
                                    if(!in_array($managerPersonValue, $personUserData)) {
                                        array_push($personUserData, $managerPersonValue);
                                    }
                                }
                            }
                        }
                    }
                }
                $personUserName = app($this->userRepository)->getUserNames($personUserData);
                $personUserName = $personUserName->toArray();
                $reportData = [];
                if(count($personUserName)) {
                    foreach ($personUserName as $personUserNameKey => $personUserNameValue) {
                        $reportData[$personUserNameValue["user_id"]] = [
                            "name" => $personUserNameValue["user_name"],
                            "y" => 0,
                        ];
                    }
                }
                $group_by_field = "manager_person";
                break;
            case 'manager_type'://项目类型
                $filter_industry = !empty($chart_search['manager_type'])?explode(',',$chart_search['manager_type']):null;
                $field = app($this->systemComboboxService)->getComboboxFieldByName(trans('project.project_type'));
                if(!is_null($filter_industry)){
                    foreach($field as $k =>$v){
                        if(!in_array($k,$filter_industry)) unset($field[$k]);
                    }
                }
                $group_by_field = "manager_type";
                break;
            case 'manager_fast'://紧急程度
                $filter_industry = !empty($chart_search['manager_fast'])?explode(',',$chart_search['manager_fast']):null;
                $field = app($this->systemComboboxService)->getComboboxFieldByName(trans('project.emergency_degree'));
                $field[0] = trans('project.other');
                if(!is_null($filter_industry)){
                    foreach($field as $k =>$v){
                        if(!in_array($k,$filter_industry)) unset($field[$k]);
                    }
                }
                $group_by_field = "manager_fast";
                break;
            case 'manager_level'://优先级别
                $filter_industry = !empty($chart_search['manager_level'])?explode(',',$chart_search['manager_level']):null;
                $field = app($this->systemComboboxService)->getComboboxFieldByName(trans('porject.priority'));
                $field[0] = trans('project.other');
                if(!is_null($filter_industry)){
                    foreach($field as $k =>$v){
                        if(!in_array($k,$filter_industry)) unset($field[$k]);
                    }
                }
                $group_by_field = "manager_level";
                break;
            case 'manager_state'://项目状态
                $field = array("1"=>trans("project.in_the_project"),"2"=>trans("project.examination_and_approval"),"3"=>trans("project.retreated"),"4"=>trans("project.have_in_hand"),"5"=>trans("project.finished"));
                $group_by_field = "manager_state";
                break;
        }

        foreach($field as $k =>$v){
            $reportData[$k] = ['name'=>$v,'y'=>0];
        }

        $analysis = [];
        $find = " count(*) as count ,";
        $analysis_origin  = ['count'=>'count','progress'=>'progress'];

        //多字段，一次查询，一次完成分析
        foreach($datasource_data_analysis as $k =>$v){
            $index = isset($analysis_origin[$k])?$analysis_origin[$k]:$k;
            $analysis[$index] = $reportData;
            // 分析完成度，不用sum的方式
            if($index == "progress") {
                // $find .= "manager_id,";
            } else {
                // $find .= ($k=='count')?" count(*) as count ,":" sum({$index}) as {$index} ,";
                // $find .= " count(*) as count ,";
            }
        }

        // 项目负责人，特殊处理
        if($datasource_group_by == "manager_person") {
            $db_obj = app($this->projectManagerRepository)->entity->select("manager_person","manager_id");
        } else {
            $db_obj = app($this->projectManagerRepository)->entity->select(DB::raw($find."".$group_by_field." as group_by,SUM(task_progress) AS task_persent"));
        }

        if(!empty($chart_search['manager_type'])) $db_obj->whereIn('manager_type',explode(',',$chart_search['manager_type']));
        if(!empty($chart_search['manager_fast'])) $db_obj->whereIn('manager_fast',explode(',',$chart_search['manager_fast']));
        if(!empty($chart_search['manager_level'])) $db_obj->whereIn('manager_level',explode(',',$chart_search['manager_level']));
        if(!empty($chart_search['manager_time'])){
            $manager_time = explode(',',$chart_search['manager_time']);
            if (isset($manager_time[0]) && !empty($manager_time[0])) {
                $db_obj->whereRaw("manager_begintime >= '".$manager_time[0]."'");
            }
            if (isset($manager_time[1]) && !empty($manager_time[1])) {
                $db_obj->whereRaw("manager_endtime <= '".$manager_time[1]."'");
            }
        }
        // 项目负责人，特殊处理
        if($datasource_group_by == "manager_person") {
            $searchResultObject =  $db_obj->where("manager_person","!=","")->get();
            $db_res = $searchResultObject->pluck("manager_person")->toArray();
            $searchResultArray = $searchResultObject->toArray();

            $analyzeResult = [];
            if(count($searchResultArray)) {
                foreach ($searchResultArray as $searchResultKey => $searchResultValue) {
                    $manager_person = isset($searchResultValue["manager_person"]) ? $searchResultValue["manager_person"] : "";
                    $manager_id     = isset($searchResultValue["manager_id"]) ? $searchResultValue["manager_id"] : "";

                    // 获取单个任务进度
                    $taskCount  = app($this->projectTaskRepository)->projectCountSumPersent($manager_id);
                    $diaryCount = app($this->projectTaskRepository)->projectPersent($manager_id);
                    if ($taskCount == "0" || $diaryCount == "0") {
                        $projectPlan = "0";
                    } else {
                        $persent = $diaryCount / $taskCount;
                        $projectPlan = round($persent);
                    }

                    // $manager_person 可能是多个拼接的
                    $personArray = explode(",", trim($manager_person,","));
                    if(count($personArray)) {
                        foreach ($personArray as $person_key => $person_value) {
                            if(isset($analyzeResult[$person_value])) {
                                $analyzeResult[$person_value]["count"]++;
                                $analyzeResult[$person_value]["progress"] = $analyzeResult[$person_value]["progress"]+$projectPlan;
                            } else {
                                $analyzeResult[$person_value] = [
                                    "count" => 1,
                                    "group_by" => $person_value,
                                    "progress" => $projectPlan,
                                ];
                            }
                        }
                    }
                }
            }
            $db_res = [];
            if(count($analyzeResult)) {
                foreach ($analyzeResult as $analyzeResultKey => $analyzeResultValue) {
                    // 计算任务进度
                    $analyzeResultValue["progress"] = round($analyzeResultValue["progress"] / $analyzeResultValue["count"]);
                    array_push($db_res, $analyzeResultValue);
                }
            }
        } else {
            $db_obj->leftJoin(DB::raw("(SELECT ROUND(SUM(task_persent) / COUNT(task_id)) as task_progress,task_project FROM project_task GROUP BY task_project)  as peoject_progress"), function ($join) {
                $join->on('peoject_progress.task_project', '=', 'project_manager.manager_id');
            });
            $searchResultArray = $db_obj->groupBy('group_by')->get()->toArray();
            $db_res = [];
            if(count($searchResultArray)) {
                $searchResultItem = [];
                foreach ($searchResultArray as $searchResultKey => $searchResultValue) {
                    $itemProgress = (isset($searchResultValue["task_persent"]) && isset($searchResultValue["count"])) ? round($searchResultValue["task_persent"] / $searchResultValue["count"]) : "0";
                    $searchResultItem = [
                        "count" => isset($searchResultValue["count"]) ? $searchResultValue["count"] : "",
                        "group_by" => isset($searchResultValue["group_by"]) ? $searchResultValue["group_by"] : "",
                        "progress" => $itemProgress,
                    ];
                    array_push($db_res, $searchResultItem);
                }
            }
        }
        parseDbRes($db_res,$analysis);
        $name = ['count'=>trans("project.number"),'progress'=>trans("project.project_completed_progress_percent")];
        $group_name = ['manager_creater'=>trans("project.project_creator"),'manager_person'=>trans("project.project_leader"),'manager_type'=>trans("project.project_type"),'manager_fast'=>trans("project.emergency_degree"),'manager_level'=>trans("project.priority_level"),'manager_state'=>trans("project.project_status")];
        //返回结果
        foreach($analysis as $k =>$v){
            $group_by_name = isset($group_name[$datasource_group_by])?$group_name[$datasource_group_by]:"";
            $row = ['data'=>$v,'name'=>$name[$k],'group_by'=>$group_by_name];
            $result[] = $row;
        }
        return $result;
    }

    public function getProjectDatasourceFilter($value='')
    {
        $manager_type = app($this->systemComboboxService)->getComboboxFieldByName(trans('project.project_type'));
        $manager_fast = app($this->systemComboboxService)->getComboboxFieldByName(trans('project.emergency_degree'));
        $manager_level = app($this->systemComboboxService)->getComboboxFieldByName(trans('project.priority'));
        $datasource_filter = [
            [
                //项目周期
                'filter_type' => 'date',
                'itemValue'   => 'manager_time',
                'itemName'    => trans("project.project_cycle")
            ], [
                //项目类型
                'filter_type' => 'singleton',
                'itemValue'   => 'manager_type',
                'itemName'    => trans("project.project_type"),
                'source'      => $manager_type,
            ], [
                //紧急程序
                'filter_type' => 'singleton',
                'itemValue'   => 'manager_fast',
                'itemName'    => trans("project.emergency_degree"),
                'source'      => $manager_fast,
            ], [
                //优先级别
                'filter_type' => 'singleton',
                'itemValue'   => 'manager_level',
                'itemName'    => trans("project.priority_level"),
                'source'      => $manager_level,
            ],
        ];
        return $datasource_filter;
    }

    /**
     * 获取项目自定义表单中，外键的自定义标签信息
     * @param int $projectId
     * @param array $own
     * @return array
     */
    public function customTabMenus(int $projectId, $params, $own)
    {
        $isMobile = Arr::get($params, 'is_mobile', 0);//手机端固定的tab不在电脑端生成
        $project = app($this->projectManagerRepository)->getDetail($projectId);
        if (empty($project)) {
            return [];
        }
        $type = $project->manager_type;
        $menuLists = FormModelingRepository::getCustomTabMenus('project_value_' . $type, function () use ($own, $isMobile, $projectId, $project) {
            if (!$isMobile) {
                return self::getProjectTabMenus($own, $project);
            } else {
                return [];
            }
        }, $projectId);

        //自定义合同标签
        if (envOverload('PROJECT_CUSTOMER_TAB_WITH_CONTRACT', false)) {
            foreach ($menuLists as $key => $menuList) {
                if ($menuList['key'] == 'customer') {
                    $customerId = Arr::get($menuList, 'id', 0);
                    $menuLists[] = [
                        'isShow' => true,
                        'fixed' => true,
                        'id' => $customerId,
                        'key' => 'customer_contract',
                        'menu_code' => 'customer_contract',
                        'foreign_key' => 'customer_id',
                        'title' => '合同',
                        'view' => [
                            "custom/list",
                            ['menu_code'=>'customer_contract','primary_key'=>'contract_id','foreign_key'=>'customer_id','id'=>$customerId]
                        ]
                    ];
                }
            }
        }

        //数据格式化
        foreach ($menuLists as $key => $menuList) {
            //手机端获取数量
            if ($isMobile) {
                $stateUrl = Arr::get($menuList, 'view.0');
                $id = Arr::get($menuList, 'view.1.id');
                if ($stateUrl == 'custom/detail') {
                    $menuLists[$key]['count'] =  $id? 1 : 0;
                } elseif ($stateUrl == 'custom/list') {
                    $menuParams = [
                        'response' => 'count',
                    ];
                    // 合同与其它自定义模块
                    if (isset($menuList['foreign_key'])) {
                        $menuParams['search'] = [
                            $menuList['foreign_key'] => [$id, '=']
                        ];
                    } else {
                        $menuParams['foreign_key'] = $projectId;
                    }
                    $list = app($this->formModelingService)->getCustomDataLists($menuParams, $menuList['key'], $own);

                    $menuLists[$key]['count'] = !is_array($list) ? $list : 0;
                }
                Arr::set($menuLists[$key]['view'], 0, '/project/mine/tasks/' . $stateUrl);
                Arr::set($menuLists[$key]['view'], '1.menu_name', $menuList['title']);
            }
            // 插入项目id
            Arr::set($menuLists[$key]['view'][1], 'manager_id', $projectId);
            // 差异化自定义字段的链接，否则前端active点击会无法正整使用,放在最后
            if (in_array($menuList['view'][0], ['custom/detail', 'custom/list'])) {
                $menuLists[$key]['view'][0] .= '/' . $menuList['key'];
            }
        }

        $data = [
            'menus' => $menuLists,
            'manager_type' => $type
        ];
        if ($isMobile) {
            $data['tab_show_status'] = self::getMobileTabStatus($type, $projectId, $own);
        }

        return $data;
    }

    //获取固定菜单的显示状态
    private static function getMobileTabStatus($type, $projectId, $own)
    {
        $menuLists = FormModelingRepository::getCustomTabMenus('project_value_' . $type, function () use ($own, $projectId) {
            return self::getProjectTabMenus($own);
        }, $projectId);
        $fixedMenuLists = self::getProjectTabMenus($own);
        $menuListsIsShow = Arr::pluck($menuLists, 'isShow', 'key');
        $fixedMenuListKeys = Arr::pluck($fixedMenuLists, 'key', 'key');

        return array_intersect_key($menuListsIsShow, $fixedMenuListKeys);
    }

    public static function getProjectTabMenus($own, $project = null)
    {
        $result[] = [
            'key'    => 'tasks',
            'isShow' => true,
            'fixed'  => true,
            'view'   => ['tasks'],
            'title'  => trans('project.task'),
        ];
        $result[] = [
            'key'    => 'teams',
            'isShow' => true,
            'fixed'  => true,
            'view'   => ['teams'],
            'title'  => trans('project.team'),
        ];
        $result[] = [
            'key'    => 'discuss',
            'isShow' => true,
            'fixed'  => false,
            'view'   => ['discuss'],
            'title'  => trans('project.discuss'),
        ];
        $result[] = [
            'key'    => 'questions',
            'isShow' => true,
            'fixed'  => false,
            'view'   => ['questions'],
            'title'  => trans('project.problem'),
        ];
        $result[] = [
            'key'    => 'documents',
            'isShow' => true,
            'fixed'  => false,
            'view'   => ['documents'],
            'title'  => trans('project.file'),
        ];
        $result[] = [
            'key'    => 'gantt',
            'isShow' => true,
            'fixed'  => false,
            'view'   => ['gantt'],
            'title'  => trans('project.gantt_chart'),
        ];
        $result[] = [
            "key"    => "detail",
            "isShow" => true,
            'fixed'  => true,
            "view"   => ['detail'],
            'title'  => trans('project.detail'),
        ];
        $result[] = [
            "key"    => "appraisal",
            "isShow" => true,
            'fixed'  => false,
            "view"   => ['appraisal'],
            'title'  => trans('project.assessment'),
        ];
        if ($project) {
            $isManager = self::isManager($project, $own['user_id']);
            $isMonitor = self::isMonitor($project, $own['user_id']);
            $isExamine = self::isExamine($project, $own['user_id']);
            if ($isMonitor || $isManager || $isExamine) {
                $result[] = [
                    "key"    => "log",
                    "isShow" => true,
                    'fixed'  => true,
                    "view"   => ['log'],
                    'title'  => trans('project.log.name'),
                ];
            }
        }

        //是否拥有费用模块权限
        $menuIds = Arr::get($own, 'menus.menu', []);
        if (in_array(38, $menuIds) || in_array(90, $menuIds)) {
            $result[] = [
                "key"    => "cost",
                "isShow" => true,
                'fixed'  => false,
                "view"   => ['cost/list'],
                'title'  => trans('charge.charge'),
            ];
        }

        return $result;
    }

    //验证项目入库数据
    private function filterSaveData($data)
    {
        $beginTime = Arr::get($data, 'manager_begintime');
        $endTime = Arr::get($data, 'manager_endtime');
        if ($beginTime && $endTime && $endTime < $beginTime) {
            return ['code' => ['0x036028', 'project']];
        }

        return true;
    }

    /**
     * 根据是否隐藏的条件设置manager_state的查询条件
     * @param array $params 已存在的查询条件
     * @param int $showFinalProjectFlag 是否隐藏结束的项目，1：隐藏
     */
    private function setFinalProjectParams(&$params, $showFinalProjectFlag)
    {
        if ($showFinalProjectFlag == 1) {
            $key = 'search.manager_state';
            //若函数本身已含有该查询条件，与5互斥时则设置-1让其查询不到数据
            if (Arr::has($params, $key)) {
                if (Arr::get($params, $key . '.0') == 5) {
                    Arr::set($params, $key, [-1, '=']);
                }
            } else {
                Arr::set($params, $key, [5, '!=']);
            }
        }
    }

    /**
     * 删除项目自定义表数据
     * @param $project
     * @return void
     */
    private function deleteProjectCustomData($project)
    {
        try {
            $type = Arr::get($project, 'manager_type');
            $id = Arr::get($project, 'manager_id');
            $table = 'custom_data_project_value_' . $type;
            DB::table($table)->where('data_id', $id)->delete();
        } catch (\Exception $e) {
        }
    }

    //验证是否是项目负责人
    public static function isManager($project, $userId)
    {
        return self::projectPersonPermission($project, $userId);
    }

    public static function isMonitor($project, $userId)
    {
        return self::projectPersonPermission($project, $userId, 'manager_monitor');
    }
    public static function isExamine($project, $userId)
    {
        return self::projectPersonPermission($project, $userId, 'manager_examine');
    }

    private static function projectPersonPermission($project, $userId, $personField = 'manager_person')
    {
        if (is_object($project)) {
            $field = object_get($project, $personField);
        } else if (is_array($project)) {
            $field = Arr::get($project, $personField);
        } else {
            $project = app('App\EofficeApp\Project\Repositories\ProjectManagerRepository')->getDetail($project);
            $field = object_get($project, $personField);
        }
        if ($field) {
            $userIds = explode(',', $field);
            if (is_array($userIds) && in_array($userId, $userIds)) {
                return true;
            }
        }
        return false;
    }

    //参照web端按钮权限，处理api的权限
    private function questionDealPermission($questionId, $userId, $type)
    {
        $question = app($this->projectQuestionRepository)->entity->find($questionId);
        if (!$question) {
            return false;
        }
        $doStatus = $question->question_state == 3 ? false : true;//已处理的状态下回执,其他情况都是待解决

        $feedback = $question->question_state == 3 && $userId == $question->question_person;
        $doBack = $userId == $question->question_doperson && ($question->question_state == 1 || $question->question_state == 2 || $question->question_state == 4);
        if ($feedback || $doBack) {
            $handleStaus = true;
        } else {
            $handleStaus = false;
        }

        if ($type == 'issueProcessed' || $type == 'issueProcessing') {
            return $doStatus && $handleStaus;
        } else if ($type == 'issueResolved' || $type == 'issueUnsolved') {
            return !$doStatus && $handleStaus;
        }
        return false;
    }

    //根据用户，获取所有项目信息
    public function getProjectAllByUserId($userId) {
        $data['user_id'] = $userId;
        $projects       = $this->getProjectsByUser($data['user_id']);
        $projectsObject = collect($projects);
        $teams          = $projectsObject->pluck("team_project")->toArray();
        $teamstr        = implode(",", $teams);
        $data['user_team_project'] = $teamstr;
        $getProjectParams = $this->parseParams($data);

        return app($this->projectManagerRepository)->getProjectAll($getProjectParams);

    }

    /**
     * 获取某用户项目的未读信息，0已读，1未读
     * @param $userId
     * @param int|array $managerIds 传入类型决定返回类型
     * @return int|array
     */
    public function checkReadStatus($userId, $managerIds) {
        $isArray = is_array($managerIds);
        $managerIds = $isArray ? $managerIds : [$managerIds];
        $taskStatusCountAll = app($this->projectTaskRepository)->getTaskStatusGroupByProject($userId, $managerIds);
        $readCount = Arr::pluck($taskStatusCountAll, 'count', 'task_project');
        if (!$isArray) {
            $managerId = array_pop($managerIds);
            return Arr::get($readCount, $managerId) ? 1 : 0;
        } else {
            foreach ($managerIds as $managerId) {
                $readCount[$managerId] = Arr::get($readCount, $managerId) ? 1 : 0;
            }
            return $readCount;
        }

    }

    public function getCustomProjectType($id)
    {
        $resultTypes = app($this->systemComboboxService)->getProjectTypeAll();
        $data = empty($id) ? $resultTypes : collect($resultTypes)->whereIn('field_value', $id)->toArray();
        return $data;
    }

    // 过滤null值未空字符串，因为project_manager表不支持null值
    private function filterNull(&$data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                if (is_null(($item))) {
                    $data[$key] = '';
                }
            }
        }
    }

    // 外发项目，明细外发任务时，组装失败原因
    private function buildFlowOutTasksMsg($saveTaskResult)
    {
        $errorInfo = [];
        foreach ($saveTaskResult as $key => $result) {
            if (is_string($result)) {
                $errorInfo[$key] = $result;
            } else if (Arr::has($result, 'code')) {
                // 暂时只有任务创建人没有权限会导致失败
                $errorInfo[$key] = trans('outsend.from_type_160.additional.fields.task_creater.field_name') . trans('common.0x000006');
            } else if (!$result) {
                $errorInfo[$key] = trans('flow.0x030148');
            }
        }
        if ($errorInfo) {
            return flow_out_extra_msg('project.task', $errorInfo);
        }
        return '';
    }

    // 外发项目，明细外发任务时，组装失败原因
    private function buildFlowOutTaskMsg($taskName, $taskPersonDo, $taskCreator, $taskBeginTime, $taskEndTime) {
        $msg = trans('common.0x000001');
        $empty = [];
        if (!$taskName) { $empty[] = trans('outsend.from_type_160.additional.fields.task_name.field_name'); }
        if (!$taskPersonDo) { $empty[] = trans('outsend.from_type_160.additional.fields.task_persondo.field_name'); }
        if (!$taskCreator) { $empty[] = trans('outsend.from_type_160.additional.fields.task_creater.field_name'); }
        if (!$taskBeginTime) { $empty[] = trans('outsend.from_type_160.additional.fields.task_begintime.field_name'); }
        if (!$taskEndTime) { $empty[] = trans('outsend.from_type_160.additional.fields.task_endtime.field_name'); }
        if ($empty) {
            return $msg . ': ' . implode(',', $empty);
        } else {
            return true;
        }
    }

    // 外发项目，项目必填验证
    private function buildFlowOutProjectMsg($data, $projectType): string
    {
        $msg = trans('common.0x000001');
        $empty = [];
        $testFieldNames = $this->getProjectRequiredField($projectType);
        foreach ($testFieldNames as $testFieldName) {
            if (!Arr::get($data, $testFieldName)) {
                $empty[] = mulit_trans_dynamic("custom_fields_table.field_name.project_value_" . '' . $projectType . '_' . $testFieldName);
            }
        }
        if ($empty) {
            return $msg . ': ' . implode(', ', $empty);
        } else {
            return '';
        }
    }

    // 获取必填的字段，如：[manager_person...]
    private function getProjectRequiredField($projectType): array
    {
        $key = 'project_value_' . $projectType;
        $tempKey = 'project_value_field_is_required_' . $key;
        if (!Redis::exists($tempKey)) {
            $fields = app($this->formModelingRepository)->entity->where('field_table_key', $key)->get();
            $isRequiredField = [];
            foreach ($fields as $field) {
                $option = json_decode($field->field_options, 1);
                if (Arr::get($option, 'validate.required')) {
                    $isRequiredField[] = $field->field_code;
                }
            }
            Redis::set($tempKey, json_encode($isRequiredField));
        } else {
            $isRequiredField = json_decode(Redis::get($tempKey), 1);
        }
        return $isRequiredField;
    }

    public function exportProjectMembersReport($params)
    {
        return $this->exportReport($params, function ($apiParams) {
            return  Arr::get(NewProjectService::userReport($apiParams), 'list');
        });
    }

    public function exportProjectReport($params)
    {
        return $this->exportReport($params, function ($apiParams) {
            return  Arr::get(NewProjectService::projectReport($apiParams), 'list');
        });
    }

    // Todo 被福建森阳网络二开调用，不能大改动
    public function exportProject($params)
    {
//        return $this->exportReport($params, function ($apiParams, $header, $userInfo) {
//            return $this->getExportProjectData($apiParams, $header, $userInfo);
//        });
        return NewProjectService::exportProject($params);
    }

    public function exportProjectTask($builder, $params)
    {
        return NewProjectService::exportProjectTask($builder, $params);
    }

    public function exportReport($params, callable $func)
    {
        $apiParams = Arr::get($params, 'api_params');
        $apiParams['page'] = 0;
        $userInfo = $params['user_info'];
        $includeColumns = Arr::get($params, 'export_params.include_columns');;

        $header = NewProjectService::formatExportHeader($includeColumns);
        $data = $func($apiParams, $header, $userInfo);

        return compact('data', 'header');
    }

    public function getExportProjectData($params, $header, $userInfo)
    {
        // 特殊处理，grid会把数组变成字符串，导出不会处理，所以传过来的是数组
        if (isset($params['project_type']) && is_array($params['project_type'])) {
            $params['project_type'] = array_pop($params['project_type']);
        }
        $currentUserId = Arr::get($userInfo, 'user_id');
        $data = $this->getProjectListIndex($currentUserId, $params);
        $data = Arr::get($data, 'list', []);
        $data = collect($data)->keyBy('manager_id')->toArray();
        $managerType = Arr::get($params, 'search.manager_type.0');
        if (!is_null($managerType)) {
            // 先从附属表获取数据，拼接，然后通过自定义字段的方法将字段值翻译后导出
            $tableName = 'custom_data_project_value_' . $managerType;
            $managerIds = Arr::pluck($data, 'manager_id');
            try {
                // 团队人员
                if (isset($header['team_person'])) {
                    $teamPerson = ProjectTeamRepository::buildQuery(['in_team_project' => $managerIds])->pluck('team_person', 'team_project');
                    foreach ($data as $key => $item) {
                        $data[$key]['team_person'] = $teamPerson->get($key, '');
                        $teamPerson->forget($key);
                    }
                }
                // 自定义字段
                $fields = Schema::getColumnListing($tableName);
                $emptyData = array_fill_keys($fields, null);
                $customData = \DB::table($tableName)->whereIn('data_id', $managerIds)->get()->keyBy('data_id');
                foreach ($data as $managerId => $item) {
                    $data[$managerId]['manager_explain'] = strip_tags(htmlspecialchars_decode($data[$managerId]['manager_explain']));
                    if (isset($customData[$managerId])) {
                        $data[$managerId] = array_merge($data[$managerId], (array) $customData[$managerId]);
                    } else {
                        $data[$managerId] = array_merge($data[$managerId], $emptyData);
                    }
                    $data[$managerId] = (Object) $data[$managerId];
                }
            } catch (\Exception $e) {
            }
            $data = app($this->formModelingService)->parseCustomListData('project_value_' . $managerType, $data);
            foreach ($data as $key => $item) {
                $data[$key] = (array) $item;
                $data[$key]['manager_id'] = $key;
            }
        }
        foreach ($data as $key => $item) {
            $data[$key]['plan'] .= '%';
        }
        return $data;
    }

    private function hasReportMenu()
    {
        return NewProjectService::hasReportPermission();
    }

    /**
     * 生成删除日志
     * @param string $type doc|task|question
     * @param $userId
     * @param array $destroyIds
     * @param null $managerId
     * @return ProjectLogManager|null
     */
    private function initDeleteLog($type, $userId, array $destroyIds, $managerId = null)
    {
        // 日志准备工作
        $logManager = null;
        switch ($type) {
            case 'task':
                $entity = app($this->projectTaskRepository)->entity;
                break;
            case 'doc':
                $entity = app($this->projectDocumentRepository)->entity;
                break;
            case 'question':
                $entity = app($this->projectQuestionRepository)->entity;
                $entity->where('question_state', '>', 0);
                break;
            default:
                return $logManager;
        }
        $idField = $type . '_id';
        $nameField = $type . '_name';
        $projectField = $type . '_project';
        $deleteFunctionName = $type . 'DeleteLog';
        $needDeleteData = $entity->whereIn($idField, $destroyIds)->get();
        if ($needDeleteData->isNotEmpty()) {
            if (is_null($managerId)) {
                $managerId = $needDeleteData->first()->$projectField;
            }
            $idNames = $needDeleteData->pluck($nameField, $idField);
            $logManager = ProjectLogManager::getIns($userId, $managerId);
            $logManager->beginFillDataModule();
            foreach ($idNames as $id => $name) {
                $logManager->useRelation()->$deleteFunctionName($id, $name);
            }
        }
        return $logManager;
    }

    // 保存并生成日志 $type: project|task|question|doc
    private function saveWithLog($model, $type, $userId, $managerId, $action = 'modify')
    {
        $dirtyData = $model->getDirty();
        $logManager = null;
        if ($dirtyData) {
            $idField = $type . '_id';
            $editFunctionName = $type . 'EditLog';
            $logManager = ProjectLogManager::getIns($userId, $managerId)->beginFillDataModule();
            foreach ($dirtyData as $key => $newValue) {
                if ($logManager->needEditLog($key)) {
                    $oldValue = $model->getOriginal($key);
                    $logManager->$editFunctionName($key, $oldValue, $newValue, object_get($model, $idField), $action);
                }
            }
        }

        $res = $model->save();
        $logManager && $logManager->storageFillData();
        return $res;
    }

    #################项目外发###############
    public function flowOutSendToCreateProject($data, $own = null)
    {
        $res = NewProjectService::flowOutProjectAdd($data);
        $dynamic = ( is_array($res) && isset($res['dynamic']) ) ? $res['dynamic'] : null;
        if (isset($res['project_id'])) {
            $res = $res['project_id'];
        }
        return $this->handFlowOutSendResult($res, ProjectManagerEntity::class, $dynamic);
    }

    public function flowOutSendToUpdateProject($data)
    {
        $res = NewProjectService::flowOutProjectEdit($data);

        return $this->handFlowOutSendResult($res, ProjectManagerEntity::class);
    }

    public function flowOutSendToDeleteProject($data)
    {
        $res = NewProjectService::flowOutProjectDelete($data);
        return $this->handFlowOutSendResult($res, ProjectManagerEntity::class);
    }

    // 给自定义字段的项目详情插入团队数据
    public function handleCustomProjectDetail($data, $id)
    {
        $params = [
            'manager_id' => $id,
            'relation_type' => 'project',
            'role_id' => 5
        ];
        $teamUser = ProjectRoleUserRepository::buildQuery($params)->pluck('user_id')->implode(',');
        $data['team_person'] = $teamUser;
        return $data;
    }
}
