<?php

namespace App\EofficeApp\XiaoE\Services;
/**
 * 小e助手获取字典的服务类
 *
 * @author lizhijun
 */
class DictService extends BaseService
{
    // 字典数据源过滤字段
    public $filterFields = [
        'get-vacation' => ['vacation_id', 'vacation_name'],
        'get-user' => ['user_id', 'user_name', 'dept_name'],
        'get-flow-type' => ['flow_id', 'flow_name'],
        'get-project-search-status' => ['id', 'name'],
        'get-all-dept' => ['dept_id', 'dept_name']
    ];

    private $flowService;
    private $deepartmentRepository;

    public function __construct()
    {
        $this->vacationService = 'App\EofficeApp\Vacation\Services\VacationService';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->flowService = 'App\EofficeApp\Flow\Services\FlowService';
        $this->deepartmentRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
    }

    public function getVacation($params)
    {
        $params['response'] = 'data';

        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $params['search'] = ['vacation_name' => [$params['keyword'], 'like']];
        }

        $response = app($this->vacationService)->getVacationList($params);

        return $response['list'];
    }

    public function getUser($params)
    {
        $queryParams['noPage'] = true;
        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $queryParams['user_name'] = $params['keyword'];
        }
        return app($this->userRepository)->getSimpleUserList($queryParams, true);
    }

    /**
     * 获取所有部门
     */
    public function getAllDept()
    {
        $depts = app($this->deepartmentRepository)->getAllDepartment()->toArray();
        return $depts;
    }

    /**
     * 获取用户能创建的流程类型，需userId
     */
    public function getFlowType($params)
    {
        //没有用户id，字典需要的数据，查询所有流程
        if (!isset($params['userId'])) {
            return app($this->flowService)->getFlowDefineListService(['request_from' => true], [])['list'];
        }
        //有用户id，说明是创建流程时用户选择或者搜索，只显示有权限的流程
        if (!$params['userId']) {
            return [];
        }
        $data = [
            'user_id' => $params['userId'],
            'platform' => 'mobile',
            'getType' => 'table',
            'noPage' => true,
        ];
        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $data['search'] = json_encode(['fieldSearchVal' => [$params['keyword'], 'like']]);
        }
        $flow = app($this->flowService)->flowNewIndexCreateList($data)['list']->toArray();
        return $flow;
    }

    /**
     * 根据项目状态查询，提供状态词典
     */
    public function getProjectSearchStatus()
    {
        $status = [
            ['id' => 1, 'name' => '立项中'],
            ['id' => 2, 'name' => '审批中'],
            ['id' => 3, 'name' => '已退回'],
            ['id' => 4, 'name' => '进行中'],
            ['id' => 5, 'name' => '已结束'],
            ['id' => 4, 'name' => '进行中'],
            ['id' => 6, 'name' => '未结束'],//这个bootServie要单独处理下
        ];
        return $status;
    }
}
