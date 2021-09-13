<?php

namespace App\EofficeApp\Project\Controllers;

use App\EofficeApp\Project\Entities\ProjectQuestionEntity;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\PermissionManager;
use App\EofficeApp\Project\NewServices\ProjectService;

Trait ProjectControllerV2Trait {

    /**
     * 获取项目列表数据
     * @apiTitle 获取项目列表
     * @param {int} with_my_project_not_read 可选，默认1；是否包含项目未读数，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_my_task_count  可选，默认1；是否包含项目任务数，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_my_question_count  可选，默认1；是否包含项目问题数，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_my_doc_count  可选，默认1；是否包含项目文档数，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_project_role  可选，默认0；是否包含项目角色数据，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} showFinalPorjectFlag 可选，默认0；是否包含已结束项目，0：包含，1：不包含
     * @param {int} list_type 可选，默认空；为custom时，代表查询单个项目分类，可支持自定义字段查询，还需传入manager_type参数，指定项目类型；不传入则只返回项目的系统字段数据
     * @param {int} manager_type 可选，默认空；项目分类id，当list_type为custom时该参数必填
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} search 字段查询条件，参考概述-自定义查询条件（如果list_type非custom，则不支持某项目分类的自定义字段查询，系统字段支持，且项目分类与其字段对应）
     *
     * @paramExample {string} 参数示例
     * {
     *  "limit": 10,
     *  "list_type": 'custom',
     *  "manager_type": 1,
     *  "page": 2,
     *  "@with_project_role": 1
     *  "search": {}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 43,
     *         "list": [
     *             {
     *                  // 项目系统数据，可参考表单建模项目字段列表的系统字段
     *                  "manager_id": 77 //项目id
     *                  "manager_name": "风格和" // 项目名称
     *                  "manager_number": "" // 项目编号
     *                  "manager_begintime": "2020-08-28" // 项目开始时间
     *                  "manager_endtime": "2020-08-29" // 项目结束时间
     *                  "manager_type": 1  // 项目类型
     *                  "manager_explain": "" // 项目描述
     *                  "manager_fast": "高" // 紧急程度
     *                  "manager_level": "" // 优先级别
     *                  "manager_state": 1 // 项目状态
     *                  "manager_appraisal": "" // 考核
     *                  "manager_appraisal_feedback": "" // 考核备注
     *                  "created_at": "2020-08-28 09:51:18"
     *                  "creator": "admin"
     *                  "project_new_disscuss": 0  // 项目未读数
     *                  "question_count": 0  // 问题未读数
     *                  "doc_count": 0  // 文档未读数
     *                  "task_new_feedback": 0 // 任务未读数
     *                  "task_count": 0  // 任务数
     *                  "complete_task_count": 0  // 完成任务数
     *                  "complete_date": null  // 项目完成时间
     *                  "is_overdue": 0 // 是否过期
     *                  "progress": 0 // 进度
     *                  // 以下为list_type为custom的返回值
     *                  "manager_person": "系统管理员" // 负责人
     *                  "manager_examine": "系统管理员" // 审核人
     *                  "manager_monitor": "系统管理员" // 监控人
     *                  "manager_creater": "系统管理员" // 创建人
     *                  "team_person": "系统管理员" // 团队成员
     *                  "creat_time": "2020-08-28 09:51:04"
     *                  "raw_manager_person": "admin"
     *                  "raw_manager_fast": 1
     *                  "raw_manager_level": 0
     *                  "raw_manager_examine": "admin"
     *                  "raw_manager_monitor": "admin"
     *                  "raw_team_person": ""
     *                  "raw_manager_creater": "admin"
     *                  "field_1": "ss" // 项目类型1的自定义字段field_1的值，不传入list_type与manager_type则无自定义字段值
     *                  "field_5": ["00490e6f1405a9a89af8fcc5c39018fc"]
     *                  "field_7": "辽宁省 锦州市"
     *                   // 以下为@with_project_role为1时的角色数据
     *                  "team_person_name": "系统管理员"
     *                  "manager_type_name": "工程管理"
     *                  "manager_person_name": "系统管理员"
     *                  "manager_examine_name": "系统管理员"
     *                  "manager_monitor_name": "系统管理员"
     *                  "manager_creater_name": "系统管理员"
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function projectListV2() {
        $dataManager = DataManager::getIns();
        $userId = $this->dataManager->getCurUserId();
        ProjectService::setProjectTypeRoleId($dataManager); // 兼容过去的项目类型：我管理的、我监控的等
        $result = ProjectService::projectList($userId, $dataManager->getApiParams(), $dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    /**
     * 获取项目详情数据
     * @apiTitle 获取项目详情
     * @param {int} with_project_role 可选，默认1；是否包含项目角色数据，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_project_info  可选，默认1；是否包含项目字典翻译数据，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_project_custom_info  可选，默认1；是否包含项目自定义字段数据，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_my_project_not_read  可选，默认0；是否包含项目讨论未读数，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_count_info  可选，默认0；是否包含项目任务、文档、问题、团队数量，0：不包含，1：包含；（该参数前需要加@符号）
     *
     * @paramExample {string} 参数示例
     * {
     *  "@with_my_project_not_read": 1
     *  "@with_count_info": 1
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *           // 项目系统字段，可参考项目列表接口或表单建模项目字段列表的系统字段
     *          "manager_id": 106
     *          "manager_name": "士大夫"
     *          "manager_number": ""
     *          "manager_begintime": "2020-09-08"
     *          "manager_endtime": "2020-09-11"
     *          "manager_type": 2
     *          "manager_person": "WV00000007,admin"
     *          "manager_examine": "WV00000007"
     *          "manager_monitor": "WV00000007"
     *          "manager_explain": ""
     *          "manager_team": ""
     *          "manager_template": 0
     *          "manager_fast": 1
     *          "manager_level": 0
     *          "manager_state": 1
     *          "manager_appraisal": ""
     *          "manager_appraisal_feedback": ""
     *          "manager_creater": "WV00000007"
     *          "creat_time": "2020-09-08 17:06:01"
     *          "created_at": "2020-09-08 17:06:05"
     *          "complete_date": null
     *          "is_overdue": 0 // 是否逾期
     *          "progress": 37 // 进度
     *          // 以下为@with_project_role为1时的角色数据
     *          "manager_person_name": "周吴,系统管理员"
     *          "manager_examine_name": "周吴"
     *          "manager_monitor_name": "周吴"
     *          "manager_creater_name": "周吴"
     *          "team_person_name": "周吴"
     *          "team_person": "WV00000007"
     *          // 以下为@with_project_info为1时的角色数据
     *          "manager_fast_name": "高"
     *          "manager_level_name": ""
     *          "type_name": "项目研发"
     *          "manager_state_name": "立项中"
     *          // 以下为@with_my_project_not_read为1时的角色数据
     *          "project_new_disscuss": 0
     *          // 以下为@with_count_info为1时的角色数据
     *          "task_count": 2
     *          "document_count": 0
     *          "question_count": 1
     *          "discuss_count": 0
     *          "team_count": 1
     *     }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function projectInfoV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::projectInfo($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    /**
     * 更新项目
     * 项目字段可参考表单建模项目的字段列表
     * @apiTitle 更新项目
     * @param {string}  manager_name 可选，但不能置空，项目名称
     * @param {string}  manager_begintime 可选，但不能置空，开始时间
     * @param {string}  manager_endtime 可选，但不能置空，结束时间
     * @param {string}  manager_person 可选，但不能置空，项目负责人，多个以逗号间隔
     * @param {string}  manager_type 可选，但不能置空，项目类型，参考项目分类设置
     * @param {string}  manager_number 可选，项目编号
     * @param {string}  manager_fast 可选，紧急程度
     * @param {string}  manager_level 可选，优先级别
     * @param {string}  manager_examine 可选，项目审核人，多个以逗号间隔
     * @param {string}  manager_monitor 可选，项目监控人，多个以逗号间隔
     * @param {string}  team_person 可选，项目团队成员，多个以逗号间隔
     * @param {string}  manager_explain 可选，项目描述
     * @param {string}  manager_state 可选，默认1，项目状态：1立项，2审核，3已退回，4进行中，5已结束
     *
     * @paramExample {string} 参数示例
     * {
     *       "manager_number": null
     *       "manager_name": "士大夫"
     *       "manager_begintime": "2020-09-15"
     *       "manager_endtime": "2020-09-23"
     *       "manager_person": "admin"
     *       "manager_fast": 1
     *       "manager_level": null
     *       "manager_examine": "admin"
     *       "manager_monitor": "admin"
     *       "team_person": "admin"
     *       "manager_explain": ""
     *       "manager_creater": "admin"
     *       "creat_time": "2020-09-15 14:49:21"
     *       "user_id": "admin"
     *       "manager_type": 1
     *       "manager_state": "1"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function projectEditV2() {
        if (!true) {
            $result = $this->projectService->editProjectManager($this->getAllInputAndCurUserId());
            return $this->returnResult($result);
        }
        $dataManager = DataManager::getIns();
        $result = ProjectService::projectEdit($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    /**
     * 删除项目
     * @apiTitle 删除项目
     * @param {string}  manager_id 必填，项目id
     * @paramExample {string} 参数示例
     * {
     *       "manager_id": 1
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function projectDeleteV2() {
        $result = ProjectService::projectDelete($this->dataManager);
        return $this->returnResult($result);
    }

    public function customTabMenusV2() {
        $dataManager = DataManager::getIns();
        $project = $dataManager->getProject();
        $result = ProjectService::customTabMenus($project, $dataManager->getApiParams(), $dataManager->getOwn());
        return $this->returnResult($result);
    }

    public function projectTeamListV2()
    {
        $result = ProjectService::projectTeamList($this->dataManager);
        return $this->returnResult($result);
    }

    ############任务#####################
    /**
     * 获取任务列表数据
     * @apiTitle 获取任务列表
     * @param {int} with_task_read_flag  可选，默认1；是否包含任务未读状态，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} search 字段查询条件，参考概述-自定义查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  "limit": 10,
     *  "page": 2,
     *  "@with_task_role": 1
     *  "search": {}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 43,
     *         "list": [
     *             {
     *                  // 任务字段，可参考表单建模任务字段列表
     *                  "task_id": 81 // 任务id
     *                  "task_project": 106 // 关联项目id
     *                  "task_name": "sdf" // 任务名称
     *                  "task_persondo": "admin" // 任务执行人
     *                  "task_frontid": 0 // 前置任务id
     *                  "task_begintime": "2020-09-14" // 任务开始时间
     *                  "task_endtime": "2020-09-26" // 任务结束时间
     *                  "task_explain": "" // 任务描述
     *                  "task_level": "" // 任务级别
     *                  "task_mark": "否" // 里程碑
     *                  "task_remark": "" // 备注
     *                  "task_creater": "admin" // 创建人
     *                  "creat_time": "2020-09-14 15:03:58" // 创建时间
     *                  "task_persent": "73" // 任务进度
     *                  "sort_id": "0" // 任务排序id
     *                  "parent_task_id": 0 // 父任务id
     *                  "tree_level": 1 // 任务树状层级
     *                  "complete_date": "" // 完成日期
     *                  "is_overdue": "否" // 是否延期
     *                  "is_leaf": 1 // 是否是叶子节点任务
     *                  "working_days": "13" // 工时
     *                  "weights": 1 // 权重
     *                  "created_at": "2020-09-14 15:03:58" // 创建时间
     *                  // 以下为自定义字段数据，自己新建了才会有
     *                  "field_4": "sdf"
     *                  "field_2": "系统管理员"
     *                  "field_5": ""
     *                  "field_6": ""
     *                  "field_7": ""
     *                  "raw_task_persondo": "admin"
     *                  "raw_task_level": 0
     *                  "raw_task_mark": 0
     *                  "raw_is_overdue": 0
     *                  "raw_task_creater": "admin"
     *                   // 以下为@with_task_read_flag为1时的未读状态
     *                  "task_read_flag": 0
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function taskListV2()
    {
        $dataManager = DataManager::getIns();
        $result = ProjectService::taskList($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    /**
     * 新建项目任务
     * 任务字段可参考表单建模任务的字段列表
     * @apiTitle 新建项目任务
     * @param {string}  task_project 必填，项目id
     * @param {string}  task_name 必填，任务名称
     * @param {string}  task_persondo 必填，任务执行人
     * @param {string}  task_begintime 必填，开始日期
     * @param {string}  task_endtime 必填，结束日期
     * @param {string}  sort_id 选填，排序
     * @param {string}  working_days 必填，工时，由开始日期结束日期计算差值
     * @param {string}  task_explain 选填，说明
     * @param {string}  weights 选填，权重
     * @param {string}  task_level 选填，任务级别
     * @param {string}  task_mark 选填，里程碑
     * @param {string}  task_remark 选填，备注
     * @param {string}  task_persent 选填，进度
     * @param {string}  task_creater 选填，创建人，默认当前用户
     * @param {string}  creat_time 选填，创建时间，默认当前时间
     * @param {string}  task_frontid 选填，前置任务
     * @param {string}  parent_task_id 选填，父级任务id
     * @param {string}  attachments 选填
     *
     * @paramExample {string} 参数示例
     * {
     *      "task_project": "106" // 项目id
     *      "task_name": "士大夫"
     *      "sort_id": 4
     *      "task_persondo": "admin"
     *      "task_begintime": "2020-09-15"
     *      "task_endtime": "2020-09-24"
     *      "working_days": 10
     *      "task_explain": "<p>第三方</p>"
     *      "weights": "1"
     *      "task_level": 5
     *      "task_mark": 1
     *      "task_remark": "<p>第三方</p>"
     *      "task_persent": null
     *      "start_date": null
     *      "complete_date": null
     *      "is_overdue": 0
     *      "task_creater": "admin"
     *      "creat_time": "2020-09-15 17:48:51"
     *      "field_4": "士大夫"
     *      "field_2": "系统管理员"
     *      "field_5": null
     *      "field_6": null
     *      "task_frontid": 82
     *      "attachments": ""
     *      "user_id": "admin"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *          "model": {} // 包含新增任务的基本字段数据，不包含自定义字段的数据。字段说明参考任务详情数据接口
     *     }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function taskAddV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::taskAdd($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function sonTaskAddV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::taskAdd($dataManager, $dataManager->getRelations()->first());
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    /**
     * 获取任务详情数据
     * @apiTitle 获取任务详情
     * @param {int} task_project 必填，任务所属项目id
     * @param {int} task_id 必填，任务id
     * @param {int} with_front_task 可选，默认1；是否包含前置任务数据，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} with_task_custom_info  可选，默认1；是否包含任务自定义字段数据，0：不包含，1：包含；（该参数前需要加@符号）
     *
     * @paramExample {string} 参数示例
     * {
     *  "task_project": 106
     *  "task_id": 81
     *  "@with_my_project_not_read": 1
     *  "@with_count_info": 1
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *           // 任务系统字段，可参考任务列表接口或表单建模任务字段列表
     *          "task_id": 81
     *          "task_project": 106
     *          "task_complate": 0
     *          "task_name": "sdf"
     *          "task_persondo": "admin"
     *          "task_frontid": 0
     *          "task_begintime": "2020-09-14"
     *          "task_endtime": "2020-09-26"
     *          "task_explain": ""
     *          "task_level": 0
     *          "task_mark": 0
     *          "task_remark": ""
     *          "task_creater": "admin"
     *          "creat_time": "2020-09-14 15:03:58"
     *          "task_persent": 73
     *          "created_at": "2020-09-14 15:03:58"
     *          "sort_id": 0
     *          "parent_task_id": 0
     *          "tree_level": 1
     *          "complete_date": null
     *          "is_overdue": 0
     *          "is_leaf": 1
     *          "start_date": null
     *          "working_days": 13
     *          "weights": 1
     *          "task_level_name": "" // 任务级别名称
     *          "task_creater_name": "系统管理员" // 任务创建人名称
     *          "task_persondo_name": "系统管理员" // 任务执行人名称
     *          "attachments": []
     *          // 以下为@with_task_custom_info为1时的自定义字段数据
     *          "data_id": 81
     *          "field_4": "sdf"
     *          "field_2": "系统管理员"
     *          "field_5": ""
     *          "field_6": ""
     *          "field_7": ""
     *          // 以下为@with_front_task为1时的前置任务数据
     *          "front_task": {} // 仅包含任务系统字段数据，不含自定义字段数据，需要详细数据请根据前置任务id重新获取
     *     }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function taskInfoV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::taskInfo($this->dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    /**
     * 更新项目任务
     * 任务字段可参考表单建模任务的字段列表
     * @apiTitle 更新项目任务
     * @param {string}  task_project 必填，项目id
     * @param {string}  task_name 可选，但不能置空，任务名称
     * @param {string}  task_persondo 可选，但不能置空，任务执行人
     * @param {string}  task_begintime 可选，但不能置空，开始日期
     * @param {string}  task_endtime 可选，但不能置空，结束日期
     * @param {string}  sort_id 选填，排序
     * @param {string}  working_days 可选，但不能置空，工时，由开始日期结束日期计算差值
     * @param {string}  task_explain 选填，说明
     * @param {string}  weights 选填，权重
     * @param {string}  task_level 选填，任务级别
     * @param {string}  task_mark 选填，里程碑
     * @param {string}  task_remark 选填，备注
     * @param {string}  task_persent 选填，进度
     * @param {string}  task_frontid 选填，前置任务
     * @param {string}  attachments 选填，附件
     *
     * @paramExample {string} 参数示例
     * {
     *      "task_project": "106" // 项目id
     *      "task_name": "士大夫"
     *      "sort_id": 4
     *      "task_persondo": "admin"
     *      "task_begintime": "2020-09-15"
     *      "task_endtime": "2020-09-24"
     *      "working_days": 10
     *      "task_explain": "<p>第三方</p>"
     *      "weights": "1"
     *      "task_level": 5
     *      "task_mark": 1
     *      "task_remark": "<p>第三方</p>"
     *      "task_persent": null
     *      "start_date": null
     *      "complete_date": null
     *      "is_overdue": 0
     *      "task_creater": "admin"
     *      "creat_time": "2020-09-15 17:48:51"
     *      "field_4": "士大夫"
     *      "field_2": "系统管理员"
     *      "field_5": null
     *      "field_6": null
     *      "task_frontid": 82
     *      "attachments": ""
     *      "user_id": "admin"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *
     *     }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function taskEditV2() {
        $result = ProjectService::taskEdit($this->dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function frontTaskListV2()
    {
        $this->dataManager->forgetApiParams('fields'); // 自定义字段列表会识别该条件，破坏数据结构。所以要删除
        $this->dataManager->forgetApiParams('response'); // 自定义模块会识别这个值，破坏返回结构，所以要删除
        $result = ProjectService::taskList($this->dataManager);
        $exceptTaskId = $this->dataManager->getApiParams('except_task_id', 'not_set');

        // 格式化任务名称
        foreach ($result['list'] as $key => &$item) {
            if ($item['task_id'] == $exceptTaskId) {
                unset($result['list'][$key]);
                continue;
            }
            $item['task_name'] = str_pad($item['task_name'], ($item['tree_level'] - 1) * 3 + strlen($item['task_name']), '　', STR_PAD_LEFT);
        }
        $result['list'] = $result['list']->values();

        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    /**
     * 删除任务
     * @apiTitle 删除任务
     * @param {string}  task_project 必填，项目id
     * @param {string}  task_id 必填，任务id
     * @paramExample {string} 参数示例
     * {
     *       "task_project": 106
     *       "task_id": 81
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function taskDeleteV2()
    {
        $dataManager = DataManager::getIns();
        $result = ProjectService::taskDelete($dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function ganttListV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::ganttList($dataManager);
        return $this->returnResult($result);
    }

    ############问题#####################
    /**
     * 获取项目问题列表数据
     * @apiTitle 获取项目问题列表
     * @param {int} manager_id  必填，项目id
     * @param {int} with_user_info  可选，默认0；是否包含问题角色信息，0：不包含，1：包含；（该参数前需要加@符号）
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} search 字段查询条件，参考概述-自定义查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  "limit": 10,
     *  "page": 2,
     *  "@with_user_info": 1
     *  "search": {}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 43,
     *         "list": [
     *             {
     *                 "question_id": 96 // 问题id
     *                 "question_project": 106 // 所属项目id
     *                 "question_task": 0 // 问题关联任务
     *                 "question_name": "fgh " // 问题名称
     *                 "question_level": 1 // 优先级别
     *                 "question_explain": "" // 问题描述
     *                 "question_person": "admin" // 提出人
     *                 "question_doperson": "admin" //处理人c
     *                 "question_endtime": "2020-09-18" // 到期时间
     *                 "question_creater": "admin" // 创建人
     *                 "question_createtime": "2020-09-11" //创建时间
     *                 "question_do": "gfh" // 处理内容
     *                 "question_dotime": "2020-09-11 14:53:17" // 处理时间
     *                 "question_back": "fgh " // 回执内容
     *                 "question_backtime": "2020-09-11 14:53:25" // 回执时间
     *                 "question_state": 4 // 问题状态：0草稿、1已提交、2处理中、3已处理、4未解决、5已解决
     *                 "created_at": "2020-09-11 14:53:13"
     *                 // 以下为@with_user_info为1时的未读状态
     *                 "question_person_name": "系统管理员"
     *                 "question_doperson_name": "系统管理员"
     *                 "question_creater_name": "系统管理员"
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function questionListV2() {
        if (!true) {
            $result = $this->projectService->getProjectQuestionList($this->getAllInputAndCurUserId());
            return $this->returnResult($result);
        }
        $dataManager = DataManager::getIns();
        $result = ProjectService::questionList($dataManager);
        $needFunctionPageIds = ['question_info', 'question_edit', 'question_delete', 'question_solve', 'question_receipt'];
        PermissionManager::setDataFunctionPages($result['list'], $dataManager, $needFunctionPageIds);
        return $this->returnResult($result);
    }

    public function questionInfoV2() {
        if (!true) {
            $result = $this->projectService->getOneProjectQuestion($this->getAllInputAndCurUserId());
            return $this->returnResult($result);
        }
        $dataManager = DataManager::getIns();
        $result = ProjectService::questionInfo($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function questionEditV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::questionEdit($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function questionAddV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::questionAdd($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function questionDeleteV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::questionDelete($dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    ############文档#####################
    /**
     * 获取项目文档列表数据
     * @apiTitle 获取项目文档列表
     * @param {int} manager_id  必填，项目id
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} search 字段查询条件，参考概述-自定义查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  "limit": 10,
     *  "page": 2,
     *  "search": {}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 43,
     *         "list": [
     *             {
     *                 "doc_id": 14 // 文档id
     *                 "doc_project": 106 // 所属项目id
     *                 "doc_name": "sdf" // 文档名称
     *                 "doc_explain": "" // 文档说明
     *                 "doc_creater": "admin" //创建人
     *                 "doc_creattime": "2020-09-15" // 创建时间
     *                 "created_at": "2020-09-15 16:35:30" //
     *                 "doc_creater_name": "系统管理员" // 创建人名称
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function documentListV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::documentList($dataManager);
        $needFunctionPageIds = ['document_info', 'document_edit', 'document_delete'];
        PermissionManager::setDataFunctionPages($result['list'], $dataManager, $needFunctionPageIds);
        return $this->returnResult($result);
    }

    public function documentInfoV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::documentInfo($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function documentEditV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::documentEdit($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function documentAddV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::documentAdd($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function documentDeleteV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::documentDelete($dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function hasAttachmentsV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::hasAttachments($dataManager);
        return $this->returnResult($result);
    }

    public function batchDownloadAttachmentsV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::batchDownloadAttachments($dataManager);
        return $this->returnResult($result);
    }

    public function logListV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::logList($dataManager->getApiParams());
        return $this->returnResult($result);
    }

    public function logSearchV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::logSearch($dataManager->getApiParams());
        return $this->returnResult($result);
    }

    public function projectTemplateListV2()
    {
        $params = $this->dataManager->getApiParams();
        $managerType = $this->dataManager->getProject('manager_type');
        $params['search'] = [
            'template_type' => [$managerType, '=']
        ];
        $result = $this->projectService->getAllTemplate($params);
        return $this->returnResult($result);
    }

    public function importProjectTemplateTaskV2()
    {
        $result = ProjectService::importProjectTemplateTask($this->dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    ############项目讨论start#####################

    public function discussListV2()
    {
        $result = ProjectService::discussList($this->dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function discussAddV2()
    {
        $result = ProjectService::discussAdd($this->dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function discussEditV2()
    {
        $result = ProjectService::discussEdit($this->dataManager);
        return $this->returnResult($result);
    }

    public function discussDeleteV2()
    {
        $result = ProjectService::discussDelete($this->dataManager);
        return $this->returnResult($result);
    }

    ############项目讨论end#####################

    ############项目任务讨论start#####################

    public function taskDiscussListV2()
    {
        $result = ProjectService::taskDiscussList($this->dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function taskDiscussAddV2()
    {
        $result = ProjectService::taskDiscussAdd($this->dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function taskDiscussEditV2()
    {
        $result = ProjectService::taskDiscussEdit($this->dataManager);
        return $this->returnResult($result);
    }

    public function taskDiscussDeleteV2()
    {
        $result = ProjectService::taskDiscussDelete($this->dataManager);
        return $this->returnResult($result);
    }

    ############项目任务讨论end#####################
    ############项目文档文件夹start#####################

    public function documentDirListV2()
    {
        $result = ProjectService::documentDirList($this->dataManager);
        // 存在嵌套的树结构，因此在list中执行formatResult
        return $this->returnResult($result);
    }

    public function documentDirInfoV2() {
        $dataManager = DataManager::getIns();
        $result = ProjectService::documentDirInfo($dataManager);
        $dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function documentDirAddV2()
    {
        $result = ProjectService::documentDirAdd($this->dataManager);
        $this->dataManager->getApiBin()->formatResult($result);
        return $this->returnResult($result);
    }

    public function documentDirEditV2()
    {
        $result = ProjectService::documentDirEdit($this->dataManager);
        return $this->returnResult($result);
    }

    public function documentDirDeleteV2()
    {
        $result = ProjectService::documentDirDelete($this->dataManager);
        return $this->returnResult($result);
    }

    ############项目文档文件夹end#####################
}
