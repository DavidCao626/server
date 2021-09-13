<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectConfigRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\ProjectService;
use Illuminate\Support\Arr;
Trait ProjectUserReportTrait
{

    /**
     * 获取用户报表
     * @param array $input
     * @return array
     */
    public static function userReport(array $input)
    {
        $userReportCount = self::getCacheUserReportCount();
        // 获取参数
        $input = self::parseFixParam($input);
        $limit = Arr::get($input, 'limit', 10);
        $page = Arr::get($input, 'page', 1);
        $type = Arr::get($input, 'type');
        $orderBy = Arr::get($input, 'order_by');
        $search = Arr::get($input, 'search');
        self::searchUserReportResult($userReportCount, $search);

        $total = count($userReportCount);
        self::formatUserReportResult($userReportCount, $type, $orderBy, $limit, $page);

        return [
            'list' => array_values($userReportCount),
            'total' => $total,
        ];
    }

    /**
     * 查看详情数据
     * @param $input
     * @return array
     */
    public static function userReportDetail($input)
    {
        $type = Arr::get($input, 'type');
        $key = Arr::get($input, 'key');
        $userId = Arr::get($input, 'user_id');
        $detailType = Arr::get($input, 'detail_type');
        $dataIds = self::getDetailDataIds($type, $key, $userId);
        if ($dataIds === false) {
            return ['code' => ['0x036001', 'project']];
        }
        if ($detailType === 'task') {
            return self::getDetailTask($dataIds, $key);
        } else if ($detailType === 'project') {
            return self::getDetailProject($input, $dataIds);
        }
        return [];
    }

    // 获取项目的详情数据
    private static function getDetailProject($input, array $projectIds)
    {
        $input['in_project_id'] = $projectIds;
        $data = self::projectReport($input);
        return $data;
    }

    // 获取任务的详情数据
    private static function getDetailTask(array $taskIds, $key)
    {
        $params = [
            'in_task_id' => $taskIds,
            'with_project_manager' => true,
            'with_person_do' => true,
        ];
        $tasks = ProjectTaskRepository::buildQuery($params)->paginate(\Request::get('limit'));
        $total = $tasks->total();
        $tasks = $tasks->items();
        $today = date('Y-m-d');
        $lastSevenDay = date('Y-m-d', strtotime('+6day'));
        $lastThirtyDay = date('Y-m-d', strtotime('+29day'));
        $tasks = collect($tasks);
        ProjectService::setTaskInfo($tasks);
        foreach ($tasks as $key => $value) {
            $tasks[$key]['manager_name'] = Arr::get($value, 'project_manager.manager_name');
            $tasks[$key]['user_name'] = Arr::get($value, 'person_do.user_name');
            $tasks[$key]['task_7_days'] = self::diffBetweenDays($value->task_begintime, $value->task_endtime, $today, $lastSevenDay);
            $tasks[$key]['task_30_days'] = self::diffBetweenDays($value->task_begintime, $value->task_endtime, $today, $lastThirtyDay);
            $tasks[$key]['user_name'] = Arr::get($value, 'person_do.user_name');
            unset($tasks[$key]['project_manager']);
            unset($tasks[$key]['person_do']);
        }
        if (ProjectConfigRepository::getTaskProgressShowModel() == 1) {
            self::spliceStringForColumns($tasks, ['task_persent']);
        }
        return [
            'total' => $total,
            'list' => $tasks,
        ];
    }

    // 获取不用类型的，不同用户的项目id，如审核的某用户的所有项目
    private static function getDetailDataIds($type, $key, $userId)
    {
        $userReport = self::getCacheUserReportData();
        $userData = Arr::get($userReport, $userId);
        if (!$userData) {
            return false;
        }
        unset($userReport);
        $types = ['doing', 'complete'];
        if ($type !== 'all') {
            if (in_array($type, $types)) {
                $types = [$type];
            } else {
                return false;
            }
        }

        $data = [];
        foreach ($types as $typeTemp) {
            if ($key === 'task_7_days') {
                $data = Arr::get($userData, 'task_in_7day', []);
                break;
            } else if ($key === 'task_30_days') {
                $data = Arr::get($userData, 'task_in_30day', []);
                break;
            }  else if ($key === 'total') {
                $dataTemp = self::getAllProjectId($userData, $typeTemp);
            } else {
                $dataTemp = Arr::get($userData, $key . '_' . $typeTemp, []);
            }
            $data = array_merge($data, $dataTemp);
        }
        return $data;
    }

    private static function getDetailType($key)
    {
        if ($key !== 'task_project' && strpos($key, 'task') === 0) {
            return 'task';
        } else {
            return 'project';
        }
    }

    // 获取或缓存用户报表（项目id数据）
    private static function getCacheUserReportData()
    {
        return CacheManager::getArrayCache(CacheManager::PROJECT_USER_REPORT, function () {
            return self::getUserReportData();
        }, self::REPORT_CACHE_SECOND);
    }

    // 获取或缓存用户报表（数字数据）
    private static function getCacheUserReportCount()
    {
        return CacheManager::getArrayCache(CacheManager::PROJECT_USER_REPORT_COUNT, function () {
            $userReport = self::getCacheUserReportData();
            return self::getUserReportCount($userReport);
        }, self::REPORT_CACHE_SECOND);
    }

    // 搜索用户与部门
    private static function searchUserReportResult(&$userReportCount, $search = [])
    {
        $userName = Arr::get($search, 'user_name');
        $userIds = null;
        if ($userName) {
            $userIds = OtherModuleRepository::buildUserQuery(['user_name' => $userName])
                ->pluck('user_id')->toArray();
        }
        $deptName = Arr::get($search, 'dept_name');
        if ($deptName) {
            $deptIds = OtherModuleRepository::buildDepartmentQuery(['dept_name' => $deptName])
                ->pluck('dept_id')->toArray();
            $userIds = OtherModuleRepository::buildUserSystemInfoQuery(['in_dept_id' => $deptIds])
                ->pluck('user_id')->toArray();
        }
        if (!is_null($userIds)) {
            $userIds = array_flip($userIds);
            $userReportCount = array_intersect_key($userReportCount, $userIds);
        }
    }

    // 格式化结果、排序、分页
    private static function formatUserReportResult(&$userReportCount, $type, $orderBy, $limit = 10, $page = 1)
    {
        // 排序
        if ($orderBy) {
            $keys = array_keys($orderBy);
            $column = array_pop($keys);
            $orderType = array_pop($orderBy);
            if (!self::isSpecialColumn($column)) {
                $column .= '_' . $type;
            }
            $userReportCount = mult_array_sort($userReportCount, $column, $orderType === 'asc' ? 4 : 3);
        }

        // 分页
        if ($page !== 0) {
            $offset = ($page - 1) * $limit;
            $userReportCount = array_slice($userReportCount, $offset, $limit);
        }

        foreach ($userReportCount as &$userData) {
            foreach ($userData as $key => $item) {
                if (self::isSpecialColumn($key)) {
                    if ($type === 'complete') {
                        $userData['task_7_days_percent'] = 0;
                        $userData['task_7_days'] = 0;
                        $userData['task_30_days_percent'] = 0;
                        $userData['task_30_days'] = 0;
                    }
                } else {
                    $newKey = str_replace('_' . $type, '', $key);
                    $userData[$newKey] = $userData[$key];
                    unset($userData[$key]);
                }
            }
        }

        // 获取数据
        $userIds = Arr::pluck($userReportCount, 'user_id');
        $usersInfo = OtherModuleRepository::buildUserQuery([
            'with_dept' => true,
            'in_user_id' => $userIds
        ])->get()->keyBy('user_id');
        foreach ($userReportCount as $key => $item) {
            $userIdTemp = $item['user_id'];
            $user = $usersInfo->get($userIdTemp);
            $userReportCount[$key]['user_name'] = Arr::get($user, 'user_name');
            $userReportCount[$key]['dept_name'] = Arr::get($user, 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment.dept_name');
        }
        self::spliceStringForColumns(
            $userReportCount,
            ['overdue_percent', 'task_overdue_percent', 'task_30_days_percent', 'task_7_days_percent']
        );
    }

    /**
     * 获取报表的数字数据
     * @param $userReportData
     * @return array
     */
    private static function getUserReportCount($userReportData)
    {
        $userReportCount = [];
        foreach ($userReportData as $userId => $data) {
            $countTemp = [];
            foreach ($data as $key => $value) {
                if (strpos($key, '_complete') > 0) {
                    $prefixKey = str_replace('_complete', '', $key);
                    $completeCount = count($data["{$prefixKey}_complete"]);
                    $doingCount = count($data["{$prefixKey}_doing"]);
                    $countTemp["{$prefixKey}_complete"] = $completeCount;
                    $countTemp["{$prefixKey}_doing"] = $doingCount;
                    $countTemp["{$prefixKey}_all"] = $completeCount + $doingCount;
                }
            }
            // 获取用户实际参与项目的数量
            $allCompleteCount = count(self::getAllProjectId($data));
            $allDoingCount = count(self::getAllProjectId($data, 'doing'));
            $countTemp["total_complete"] = $allCompleteCount;
            $countTemp["total_doing"] = $allDoingCount;
            $countTemp["total_all"] = $allCompleteCount + $allDoingCount;
            $countTemp['overdue_percent_complete'] = percent($countTemp['overdue_complete'], $countTemp['total_complete']);
            $countTemp['overdue_percent_doing'] = percent($countTemp['overdue_doing'], $countTemp['total_doing']);
            $countTemp['overdue_percent_all'] = percent($countTemp['overdue_all'], $countTemp['total_all']);
            $countTemp['task_overdue_percent_complete'] = percent($countTemp['task_overdue_complete'], $countTemp['task_complete']);
            $countTemp['task_overdue_percent_doing'] = percent($countTemp['task_overdue_doing'], $countTemp['task_doing']);
            $countTemp['task_overdue_percent_all'] = percent($countTemp['task_overdue_all'], $countTemp['task_all']);

            $countTemp["task_7_days"] = $data['task_7_days'];
            $countTemp["task_30_days"] = $data['task_30_days'];
            $countTemp["task_7_days_percent"] = percent($countTemp['task_7_days'], 7);
            $countTemp["task_30_days_percent"] = percent($countTemp['task_30_days'], 30);
            $countTemp['user_id'] = $userId;
            $userReportCount[$userId] = $countTemp;
        }
        return $userReportCount;
    }

    // 获取报表的实际数据
    private static function getUserReportData()
    {
        $userReport = [];
        $projectIds = ['complete' => [], 'doing' => [], 'overdue' => []];
        $projectInput = ['in_manager_state' => [4, 5]];
        self::getUserReportProjectData($projectInput, $userReport, $projectIds);

        $taskInput = ['in_task_project' => $projectIds['complete'], 'overdue' => $projectIds['overdue']];
        self::getUserReportTaskData($taskInput, $userReport, 'complete');

        $taskInput = ['in_task_project' => $projectIds['doing'], 'overdue' => $projectIds['overdue']];
        self::getUserReportTaskData($taskInput, $userReport, 'doing');

        self::formatUserReportData($userReport);
        return $userReport;
    }

    // 将几个去重的数据转换回来
    private static function formatUserReportData(&$userReport)
    {
        if (isset($userReport[''])) {
            unset($userReport['']);
        }
        foreach ($userReport as $user => &$item) {
            $item['task_project_complete'] = array_keys($item['task_project_complete']);
            $item['task_project_doing'] = array_keys($item['task_project_doing']);
            $item['overdue_complete'] = array_keys($item['overdue_complete']);
            $item['overdue_doing'] = array_keys($item['overdue_doing']);
        }
    }

    // 获取报表的项目相关数据
    private static function getUserReportProjectData($input, &$userReport, &$projectIds)
    {
        $projectQuery = ProjectManagerRepository::buildQuery($input)
            ->select('manager_person', 'manager_examine', 'manager_monitor','manager_state','is_overdue', 'manager_id');
        $projectQuery->chunk(10000, function ($projects) use (&$userReport, &$projectIds) {
            $projects->each(function ($item) use (&$userReport, &$projectIds) {
                $projectId = $item->manager_id;
                $isComplete = $item->manager_state === 5;
                $isComplete ? $projectIds['complete'][] = $projectId : $projectIds['doing'][] = $projectId;
                $item->is_overdue ? $projectIds['overdue'][$projectId] = 1 : '';
                self::insertUserProjectId($userReport, $item->manager_person, 'manage', $projectId, $isComplete, $item->is_overdue);
                self::insertUserProjectId($userReport, $item->manager_examine, 'examine', $projectId, $isComplete, $item->is_overdue);
                self::insertUserProjectId($userReport, $item->manager_monitor, 'monitor', $projectId, $isComplete, $item->is_overdue);
            });
        });
    }

    // 获取报表的任务相关数据
    private static function getUserReportTaskData($input, &$userReport, $type)
    {
        $overdueProjectIds = $input['overdue'];
        unset($input['overdue']);
        $taskQuery = ProjectTaskRepository::buildQuery($input)
            ->select('task_id', 'task_persondo', 'task_begintime', 'task_endtime', 'is_overdue', 'task_persent', 'task_project');
        $today = date('Y-m-d');
        $lastSevenDay = date('Y-m-d', strtotime('+6day'));
        $lastThirtyDay = date('Y-m-d', strtotime('+29day'));
        $taskQuery->chunk(10000, function ($tasks) use (&$userReport, $type, $today, $lastSevenDay, $lastThirtyDay, $overdueProjectIds) {
            $tasks->each(function ($task) use (&$userReport, $type, $today, $lastSevenDay, $lastThirtyDay, $overdueProjectIds) {
                $userId = $task->task_persondo;
                self::initUserReportData($userReport, $userId);
                $taskId = $task->task_id;
                $projectId = $task->task_project;
                $userReport[$userId]['task_project_' . $type][$projectId] = 0;
                $userReport[$userId]['task_' . $type][] = $taskId;
                isset($overdueProjectIds[$projectId]) && $userReport[$userId]['overdue_' . $type][$projectId] = 0;
                if ($task->is_overdue/* || ($type === 'complete' && $task->task_persent < 100)*/) {
                    $userReport[$userId]['task_overdue_' . $type][] = $taskId;
                }
                if ($type === 'doing') {
                    $sevenDays = self::diffBetweenDays($task->task_begintime, $task->task_endtime, $today, $lastSevenDay);
                    $userReport[$userId]['task_7_days'] += $sevenDays;
                    $sevenDays > 0 && $userReport[$userId]['task_in_7day'][] = $taskId;
                    $thirtyDays = self::diffBetweenDays($task->task_begintime, $task->task_endtime, $today, $lastThirtyDay);
                    $userReport[$userId]['task_30_days'] += $thirtyDays;
                    $thirtyDays > 0 && $userReport[$userId]['task_in_30day'][] = $taskId;
                }
            });
        });
    }

    /**
     * 根据项目的各角色成员，存储项目id
     * @param $userReport 
     * @param $users
     * @param $key
     * @param $projectId
     * @param $isComplete
     * @param $isOverdue
     */
    private static function insertUserProjectId(&$userReport, $users, $key, $projectId, $isComplete, $isOverdue)
    {
        $users = is_string($users) ? self::explodeUser($users) : $users;
        foreach ($users as $userId) {
            self::initUserReportData($userReport, $userId);
            if ($isComplete) {
                $userReport[$userId][$key . '_complete'][] = $projectId;
                $isOverdue && $userReport[$userId]['overdue_complete'][$projectId] = 0;
            } else {
                $userReport[$userId][$key . '_doing'][] = $projectId;
                $isOverdue && $userReport[$userId]['overdue_doing'][$projectId] = 0;
            }
        }
    }

    /**
     * 计算两组日期的交集
     * @return int 返回日期交集的天数
     */
    private static function diffBetweenDays($startDate1, $endDate1, $startDate2, $endDate2)
    {
        $dates = [$startDate1, $endDate1, $startDate2, $endDate2];
        foreach ($dates as $key => $date) {
            $dates[$key] = strtotime($date);
            if ($dates[$key] === false) {
                return 0;
            }
        }
        if ($dates[0] > $dates[3] || $dates[1] < $dates[2]) {
            return 0;
        }
        $dates = Arr::sort($dates);
        $dates = array_values($dates);
        $diffDay = ($dates[2] - $dates[1]) / 86400;
        return $diffDay + 1;
    }

    // 基本数据结构
    private static function initUserReportData(&$userReport, $userId)
    {
        if (!isset($userReport[$userId])) {
            $userReport[$userId] = [
                'manage_complete' => [],// 负责的项目
                'manage_doing' => [],
                'examine_complete' => [],// 审核的项目
                'examine_doing' => [],
                'monitor_complete' => [],// 监控的项目
                'monitor_doing' => [],
                'overdue_complete' => [],// 逾期的而项目
                'overdue_doing' => [],
                'task_project_complete' => [],// 执行的项目
                'task_project_doing' => [],
                'task_complete' => [], // 任务
                'task_doing' => [],
                'task_overdue_complete' => [], // 逾期的任务
                'task_overdue_doing' => [],
                'task_7_days' => 0, // 7天内执行的任务天数
                'task_30_days' => 0,
                'task_in_7day' => [], // 7天内执行的任务
                'task_in_30day' => [],
            ];
        }
    }


    // 获取个人参与的全部项目id
    private static function getAllProjectId($userData, $type = 'complete')
    {
        return scalar_array_merge(
            $userData['manage_' . $type],
            $userData['monitor_' . $type],
            $userData['examine_' . $type],
            $userData['task_project_' . $type]
        );
    }

    private static function explodeUser(string $userString)
    {
        return $userString ? explode(',', $userString) : [];
    }

    // 这几个字段不需要拼接
    private static function isSpecialColumn($column) {
        return in_array($column, ['task_7_days_percent', 'task_7_days', 'task_30_days_percent', 'task_30_days', 'user_id']);
    }
}
