<?php

namespace App\EofficeApp\Salary\Services;

use App\EofficeApp\Salary\Repositories\SalaryFieldHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldRepository;
use App\EofficeApp\Salary\Repositories\SalaryPayDetailRepository;
use App\EofficeApp\Salary\Repositories\SalaryRepository;
use App\EofficeApp\Salary\Services\SalaryField\SalaryFieldBuilder;
use App\EofficeApp\User\Repositories\UserRepository;

class SalaryReportFormService extends SalaryBaseService
{
    private $salaryReportSetService;

    private $userRepository;

    private $salaryFieldService;

    public $salaryRepository;

    private $salaryFieldHistoryRepository;

    private $salaryFieldRepository;

    private $salaryPayDetailRepository;

    public function __construct(
        SalaryReportSetService $salaryReportSetService,
        UserRepository $userRepository,
        SalaryFieldService $salaryFieldService,
        SalaryRepository $salaryRepository,
        SalaryFieldHistoryRepository $salaryFieldHistoryRepository,
        SalaryFieldRepository $salaryFieldRepository,
        SalaryPayDetailRepository $salaryPayDetailRepository
    ) {
        parent::__construct();
        $this->salaryReportSetService = $salaryReportSetService;
        $this->userRepository = $userRepository;
        $this->userService = "App\EofficeApp\User\Services\UserService";
        $this->userMenuService = "App\EofficeApp\Menu\Services\UserMenuService";
        $this->salaryFieldService = $salaryFieldService;
        $this->salaryRepository = $salaryRepository;
        $this->salaryFieldHistoryRepository = $salaryFieldHistoryRepository;
        $this->salaryFieldRepository = $salaryFieldRepository;
        $this->salaryPayDetailRepository = $salaryPayDetailRepository;
        $this->personnelFilesRepository = "App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository";
    }

    /**
     * 查询某次上报记录员工薪酬报表
     * @param $reportId
     * @param $own
     * @param $param
     * @param bool $type
     * @return array
     */
    public function getEmployeeSalaryList($reportId, $own, $param, $type = true)
    {
        $param = $this->parseParams($param);
        $page = isset($param['page']) ? $param['page'] : 1;
        $limit = isset($param['limit']) ? $param['limit'] : 10;
        $begin = $limit * ($page - 1);
        $flag = (isset($param['search']['user_id']) or isset($param['search']['dept_id']) or isset($param['search']['role_id']));
        $handleParam = $this->parseSalaryParams($param, false);

        // 获取有权限查看的用户
        $viewUser = $this->salaryReportSetService->getViewUser($own);
        if (empty($viewUser)) {
            return ['fields' => [], 'data' => [], 'total' => 0];
        }
        if (isset($handleParam['search']['user_id'][0]) && $flag) {
            $handleParam['search']['user_id'][0] = array_intersect($viewUser, $handleParam['search']['user_id'][0]);
        } else {
            $handleParam['search']['user_id'] = [$viewUser, 'in'];
        }
        // 开了[无账号发薪]，混合条件，得到$userIds作为范围
        // 可能传入部门、用户，没有角色
        if ($this->payWithoutAccountConfig == '1') {
            $userIds = [];
            // 去人事档案模块查数据
            $handleParam['search']['id'] = $handleParam['search']['user_id'];
            unset($handleParam['search']['user_id']);
            $params = ['fields' => ['id', 'user_id', 'user_name'], 'search' => $handleParam['search']];
            $personnelInfo = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
            if (!empty($personnelInfo)) {
                $userIds = array_column($personnelInfo, 'id');
            }
        } else {
            // 原始状态，去查用户，得到人员范围，这个search，包括部门角色用户的查询
            $userIds = $this->userRepository->entity->wheres($handleParam['search'])->pluck('user_id');
            $userIds = $userIds->toArray();
        }

        $salaries = $this->salaryRepository->getReportSalaryListWithPayDetailsAndUserInfo($reportId, $userIds);

        // 处理字段
        $tempFields = $this->getShownHistoryFieldList($reportId);

        $fields = $this->handleFieldsForReportForm($tempFields);

        // 处理头部
        $head = $this->getFieldsWithNoChildren($fields);
        $headIds = array_keys($head);

        $total = array_map(function () {
            return 0;
        }, array_flip($headIds));

        $data = [];
        foreach ($salaries as $salary) {
            $salary = $salary->toArray();
            $salary['user_name'] = $salary['user']['user_name'] ?? '';
            $salary['role_name'] = $salary['user']['user_role'][0]['role_name'] ?? '';
            $salary['dept_name'] = $salary['user']['user_to_dept'][0]['dept_name'] ?? '';
            unset($salary['user']);
            // 开了[无账号发薪]，处理人事档案的 user_name role_name dept_name
            if ($this->payWithoutAccountConfig == '1') {
                if (isset($salary['has_personnel']) && isset($salary['has_personnel']['user_name'])) {
                    $salary['user_name'] = $salary['has_personnel']['user_name'];
                }
                // $salary['role_name']
                if (isset($salary['has_personnel']) && isset($salary['has_personnel']['department'])) {
                    $salary['dept_name'] = $salary['has_personnel']['department']['dept_name'] ?? '';
                }
                unset($salary['has_personnel']);
            }
            $payDetails = $salary['pay_details'];
            $payMap = [];
            foreach ($payDetails as $detail) {
                $payMap[$detail['field_id']] = $detail;
            }
            $payDetail = [];
            foreach ($headIds as $fieldId) {
                $value = $payMap[$fieldId]['value'] ?? 0;
                $payDetail[] = $value;
                if (is_numeric($value)) {
                    $total[$fieldId] += $value;
                }
            }
            $salary['pay_details'] = $payDetail;

            $data[] = $salary;
        }
        $count = count($data);
        // 格式化total
        $returnTotal = [];
        foreach ($total as $key => $value) {
            $returnTotal[] = (new SalaryFieldBuilder($head[$key]))->simpleBuild()->formatValue($value);
        }

        return [
            'fields' => $fields,
            'data' => $page == 0 ? $data : array_slice($data, $begin, $limit),
            'total' => $returnTotal,
            'count' => $count,
        ];
    }

    /**
     * 薪资报表
     * @param $param
     * @param $type
     * @param $own
     * @return array
     */
    public function getSalaryReport($param, $own)
    {
        $param = $this->parseParams($param);
        $page = isset($param['page']) ? $param['page'] : 0;
        $limit = isset($param['limit']) ? $param['limit'] : 10;
        $begin = $limit * ($page - 1);
        $param["search"] = isset($param["search"]) ? $param["search"] : [];
        $flag = (isset($param['search']['user_id']) or isset($param['search']['dept_id']));
        $searchDept = isset($param['search']['dept_id']) ? $param['search']['dept_id'][0] : [];
        $searchUser = isset($param['search']['user_id']) ? $param['search']['user_id'][0] : [];
        $param = $this->parseSalaryParams($param, false);

        // 报表查看范围,查出用户部门、角色、本人可查看的用户id集合,取交集
        $viewUser = $this->salaryReportSetService->getViewUser($own, ['fields' => ['user_id']]);
        if (empty($viewUser)) {
            return ['fields' => [], 'data' => [], 'total' => 0];
        }
        if (isset($param['search']['user_id'][0]) && $flag) {
            $param['search']['user_id'][0] = array_intersect($param['search']['user_id'][0], $viewUser);
        } else {
            $param['search']['user_id'] = [$viewUser, 'in'];
        }
        //获取用户对应部门名称
        $allUsers = app($this->userService)->getUserListsWithDept();
        $userDepts = [];
        $deptNameById = [];
        $userNameById = [];
        foreach ($allUsers as $userInfo) {
            $userDepts[$userInfo['user_id']] = ['dept_id' => $userInfo['dept_id'], 'dept_name' => $userInfo['dept_name']];
            $deptNameById[$userInfo['dept_id']] = $userInfo['dept_name'];
            $userNameById[$userInfo['user_id']] = $userInfo['user_name'];
        }

        $userSearch = [];
        if (isset($param['search']['user_id'])) {
            $userSearch = ['user_id' => $param['search']['user_id']];
        }
        $userIds = $this->userRepository->entity
            ->when(!empty($userSearch), function ($query) use ($userSearch) {
                $query->wheres($userSearch);
            })
            ->pluck('user_id');
        $userIds = $userIds->toArray();
        // 开了[无账号发薪]，处理其余id
        if ($this->payWithoutAccountConfig == '1') {
            $different = array_diff($param['search']['user_id'][0], $userIds);
            if (!empty($different)) {
                // 去人事档案模块查数据
                $params = ['fields' => ['id', 'user_id', 'user_name'], 'search' => ['id' => [$different, 'in']]];
                $personnelInfo = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
                if (!empty($personnelInfo)) {
                    foreach ($personnelInfo as $key => $personnelItem) {
                        // 拼接到 $userIds 上面去
                        $userIds[] = $personnelItem['id'] ?? '';
                    }
                }
            }
            // $param['search']['user_name']处理
            if (isset($param['search']) && isset($param['search']['user_name'])) {
                $param['search']['personnel_files.user_name'] = $param['search']['user_name'];
                unset($param['search']['user_name']);
            }
        } else {
            // $param['search']['user_name']处理
            if (isset($param['search']) && isset($param['search']['user_name'])) {
                $param['search']['user.user_name'] = $param['search']['user_name'];
                unset($param['search']['user_name']);
            }
        }
        if (isset($param['search']['user_id'])) {
            unset($param['search']['user_id']);
        }
        $salaries = $this->salaryRepository->getAllSalaryReportPayDetails($userIds, $param);
        // 处理字段
        $tempFields = $this->getShownAllFieldList();

        $fields = $this->handleFieldsForReportForm($tempFields);
        // 处理头部
        $head = $this->getFieldsWithNoChildren($fields);
        $headIds = array_keys($head);

        $total = array_map(function () {
            return 0;
        }, array_flip($headIds));
        $deptTotal = [];
        $userTotal = [];
        // 开始处理salaries

        // 用来放pay_details
        $fieldIdMap = $total;

        $data = [];
        $tempSalaryId = 0;
        foreach ($salaries as $salary) {
            if ($salary->salary_id != $tempSalaryId) {
                if ($tempSalaryId != 0) {
                    $data[$tempSalaryId]['pay_details'] = array_values($data[$tempSalaryId]['pay_details']);
                }
                $tempSalaryId = $salary->salary_id;
                $salaryUserId = $salary->user_id ?? '';
                $salaryUserName = $salary->user_name ?? '';
                $data[$salary->salary_id] = [
                    'user_name' => $salaryUserName,
                    'user_id' => $salaryUserId,
                    'title' => $salary->title,
                ];
                // 处理年月
                $year = $salary->year ?: '';
                $month = $salary->month ?: '';
                if ($year && $month) {
                    $month = $month < 10 ? '0' . $month : $month;
                    $data[$salary->salary_id]['date'] = $year . '-' . $month;
                } else {
                    $data[$salary->salary_id]['date'] = $year . $month;
                }
                $data[$salary->salary_id]['pay_details'] = $fieldIdMap;
            }
            $deptName = isset($userDepts[$salaryUserId]['dept_name']) ? $userDepts[$salaryUserId]['dept_name'] : '';
            $deptId = isset($userDepts[$salaryUserId]['dept_id']) ? $userDepts[$salaryUserId]['dept_id'] : '';
            // 开了[无账号发薪]，处理人事档案的 $deptName $deptId
            if ($this->payWithoutAccountConfig == '1') {
                if ($deptName == '' && isset($salary->personnel_dept_id)) {
                    $deptId = $salary->personnel_dept_id;
                    $deptName = $deptNameById[$deptId] ?? '';
                }
                if ($salaryUserId == '' && $salaryUserName == '' && isset($salary->personnel_id) && isset($salary->personnel_user_name)) {
                    $salaryUserId = $salary->personnel_id;
                    $data[$salary->salary_id]['user_id'] = $salaryUserId;
                    $data[$salary->salary_id]['user_name'] = $salary->personnel_user_name;
                }
                $deptNameById[$deptId] = $deptName;
                $userNameById[$salaryUserId] = $salary->personnel_user_name;
            }
            $data[$salary->salary_id]['dept_name'] = $deptName;
            if (in_array($salary->field_id, $headIds)) {
                $data[$salary->salary_id]['pay_details'][$salary->field_id] = $salary->value;
                if (is_numeric($salary->value)) {
                    $total[$salary->field_id] += $salary->value;
                }
                if (!empty($searchDept)) {
                    $deptTotal[$deptId][$salary->field_id][] = $salary->value;
                }
                if (!empty($searchUser)) {
                    $userTotal[$salaryUserId][$salary->field_id][] = $salary->value;
                }

            }
        }
        // 最后一个salary的pay_details还未处理，补处理
        if ($tempSalaryId != 0) {
            $data[$tempSalaryId]['pay_details'] = array_values($data[$tempSalaryId]['pay_details']);
        }
        $data = array_values($data);
        // salaries处理结束
        $count = count($data);
        // 格式化total
        $returnTotal = [];
        foreach ($total as $key => $value) {
            $returnTotal[] = (new SalaryFieldBuilder($head[$key]))->simpleBuild()->formatValue($value);
        }
        $returnDept = [];
        $returnUser = [];
        if ((!empty($searchDept) || !empty($searchUser)) && !empty($deptTotal)) {
            foreach ($deptTotal as $deptId => $fieldArray) {
                $res = array_map(function () {
                    return 0;
                }, array_flip($headIds));
                $result = [];
                foreach ($fieldArray as $k => $v) {
                    $res[$k] = array_sum($v);
                }
                foreach ($res as $key => $value) {
                    $result[] = (new SalaryFieldBuilder($head[$key]))->simpleBuild()->formatValue($value);
                }
                $returnDept[] = ['res' => $result, 'dept_name' => $deptNameById[$deptId]];
            }
        }
        if ((!empty($searchUser) || !empty($searchUser)) && !empty($userTotal)) {
            foreach ($userTotal as $userId => $fieldArray) {
                $res = array_map(function () {
                    return 0;
                }, array_flip($headIds));
                $result = [];
                foreach ($fieldArray as $k => $v) {
                    $res[$k] = array_sum($v);
                }
                foreach ($res as $key => $value) {
                    $result[] = (new SalaryFieldBuilder($head[$key]))->simpleBuild()->formatValue($value);
                }
                $returnUser[] = ['res' => $result, 'user_name' => $userNameById[$userId]];
            }
        }
        return [
            'fields' => $fields,
            'data' => $page == 0 ? $data : array_slice($data, $begin, $limit),
            'total' => $returnTotal,
            'count' => $count,
            'deptTotal' => $returnDept,
            'userTotal' => $returnUser,
        ];

    }

    /**
     * 查询我的薪酬列表
     * @param $userId
     * @param $param
     * @return array
     */
    public function getMySalaryList($userId, $param)
    {
        $param = $this->parseParams($param);
        $param['page'] = $param['page'] ?? 0;
        $param['limit'] = $param['limit'] ?? 10;
        $begin = $param['limit'] * ($param['page'] - 1);

        // [无账号发薪]时，把$userId翻译成人事档案id，因为上报那里，储存的是人事档案id
        if ($this->payWithoutAccountConfig == "1") {
            // 用户id转换为档案的id
            $personnelId = $this->getUserPersonnelId($userId);
            if ($personnelId) {
                $userId = $personnelId;
            }
        }
        $param['search']['user_id'] = [$userId];
        $salaries = $this->salaryRepository->getAllSalaryListWithPayDetailsAndUserInfo([$userId], $param);

        $count = $this->salaryRepository->getSalaryTotal($param);
        // 处理字段
        $tempFields = $this->getShownAllFieldList();

        $fields = $this->handleFieldsForReportForm($tempFields);

        // 处理头部
        $head = $this->getFieldsWithNoChildren($fields);
        $headIds = array_keys($head);

        $data = [];
        $total = [];
        foreach ($salaries as $salary) {
            $salary = $salary->toArray();
            $report = $salary['salary_to_salary_report'];
            $salary['title'] = $report['title'] ?? '';
            $year = $report['year'] ?: '';
            $month = $report['month'] ?: '';
            if ($year && $month) {
                $month = $month < 10 ? '0' . $month : $month;
                $salary['date'] = $year . '-' . $month;
            } else {
                $salary['date'] = $year . $month;
            }
            unset($salary['salary_to_salary_report']);
            $payDetails = $salary['pay_details'];
            $payMap = [];
            foreach ($payDetails as $detail) {
                $payMap[$detail['field_id']] = $detail;
            }
            $payDetail = [];
            foreach ($headIds as $fieldId) {
                $value = $payMap[$fieldId]['value'] ?? 0;
                $payDetail[] = $value;
                $total[$fieldId][] = $value;
            }
            $salary['pay_details'] = $payDetail;
            $data[] = $salary;
        }
        $returnTotal = [];
        foreach ($total as $key => $value) {
            $sum = array_sum($value);
            $returnTotal[] = (new SalaryFieldBuilder($head[$key]))->simpleBuild()->formatValue($sum);
        }
        return [
            'fields' => $fields,
            'data' => $param['page'] == 0 ? $data : array_slice($data, $begin, $param['limit']),
            'count' => $count,
            'total' => $returnTotal,
        ];

    }

    /**
     * 获取人事卡片页面上的薪资报表
     * @param $userId
     * @param $own
     * @param $param
     * @return array
     */
    public function getSalaryForm($userId, $own, $param)
    {
        $allows = $viewUser = $this->salaryReportSetService->getViewUser($own);
        if ($userId != $own['user_id'] && !in_array($userId, $allows)) {
            return ['code' => ['0x000006', 'common']];
        }
        // 判断319的菜单in_pc属性==1，才可以显示数据-DT201908150006
        $menuInfo = app($this->userMenuService)->getMenuDetail('319');
        if($menuInfo && isset($menuInfo['in_pc']) && $menuInfo['in_pc'] == '1') {
            return $this->getMySalaryList($userId, $param);
        } else {
            return ['code' => ['0x038026', 'salary']];
        }
    }

    public function getShownHistoryFieldList($reportId)
    {
        $params = [
            'report_id' => $reportId,
            'search' => [
                'field_show' => [1],
            ],
        ];
        return $this->salaryFieldHistoryRepository->getSalaryItems($params);
    }

    public function getShownAllFieldList()
    {
        $params = [
            'with_trashed' => 1,
            'search' => [
                'field_show' => [1],
            ],
        ];
        return $this->salaryFieldRepository->getSalaryItems($params, true);
    }

    /**
     * 处理薪酬项将子项放到父级的children中
     * @param $fields
     * @return array
     */
    public function handleFieldsForReportForm($fields)
    {
        $parentArray = array_filter(array_column($fields, 'field_parent'));
        $fieldMap = [];
        foreach($fields as $key => $field){
            if(isset($field['has_children']) && $field['has_children'] == '1') {
                if(array_search($field['field_id'], $parentArray) !== false) {
                    $fieldMap[$field['field_id']] = $field;
                }
            } else {
                $fieldMap[$field['field_id']] = $field;
            }
        }
        foreach($fieldMap as $key => $field){
            if($field['field_parent'] != 0) {
                if(isset($fieldMap[$field['field_parent']])){
                    $fieldMap[$field['field_parent']]['children'][] = $field;
                }
                unset($fieldMap[$key]);
            }
        }
        return array_values($fieldMap);
    }

    public function getFieldsWithNoChildren($fields)
    {
        $result = [];
        foreach($fields as $field){
            if(isset($field['children'])){
                if(!empty($field['children'])) {
                    foreach($field['children'] as $child){
                        $result[$child['field_id']] = $child;
                    }
                }
                continue;
            }
            $result[$field['field_id']] = $field;
        }

        return $result;
    }

    /**
     * 解析参数
     * @param $param
     * @param bool $flag
     * @return mixed
     */
    public function parseSalaryParams($param, $flag = true)
    {
        $users = [];
        if (isset($param['search']['user_id'][0])) {
            $users = $param['search']['user_id'][0];
            unset($param['search']['user_id']);
        }

        if (isset($param['search']['dept_id'][0]) && !empty($param['search']['dept_id'][0])) {
            // 开了[无账号发薪]，去人事档案根据部门查人事档案id
            if ($this->payWithoutAccountConfig == '1') {
                $params = ['fields' => ['id', 'user_id', 'user_name'], 'search' => ['personnel_files.dept_id' => [$param['search']['dept_id'][0], 'in']]];
                $user1 = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
                if (!empty($user1)) {
                    $user1 = array_column($user1, 'user_id');
                    // 用户id转换为档案的id
                    $user1 = $this->getUserPersonnelId($user1);
                }
            } else {
                // 原始状态，先根据传入的部门id，查部门下用户，传出，进行筛选
                $user1 = $this->userRepository->getUserByAllDepartment($param['search']['dept_id'][0])->toArray();
                if (!empty($user1)) {
                    $user1 = array_column($user1, 'user_id');
                }
            }

            if (isset($param['search']['role_id'][0]) && !empty($param['search']['role_id'][0])) {
                $user2 = $this->userRepository->getUserListByRoleId($param['search']['role_id'][0])->toArray();
                if (!empty($user2)) {
                    $user2 = array_column($user2, 'user_id');
                }

                $users = empty($users) ? array_intersect($user1, $user2) : array_intersect(array_intersect($users, $user1), $user2);

                unset($param['search']['role_id']);
            } else {
                $users = empty($users) ? $user1 : array_intersect($users, $user1);
            }

            unset($param['search']['dept_id']);
        } else {
            if (isset($param['search']['role_id'][0]) && !empty($param['search']['role_id'][0])) {
                $user = $this->userRepository->getUserListByRoleId($param['search']['role_id'][0])->toArray();
                if (!empty($user)) {
                    $user = array_column($user, 'user_id');
                }

                $users = empty($users) ? $user : array_intersect($users, $user);
                unset($param['search']['role_id']);
            }
        }

        if (!isset($param['search'])) {
            $param['search'] = [];
        }

        if ($flag) {
            $param['search']['salary.user_id'] = [$users, 'in'];
        } else {
            $param['search']['user_id'] = [$users, 'in'];
        }

        return $param;
    }

    /**
     * 移动端我的薪资-薪资详情
     * @param $userId
     * @param $reportId
     * @return array
     */
    public function getUserSalaryDetailByMobile($userId, $reportId)
    {
        // [无账号发薪]时，把$userId翻译成人事档案id，因为上报那里，储存的是人事档案id
        if ($this->payWithoutAccountConfig == "1") {
            // 用户id转换为档案的id
            $personnelId = $this->getUserPersonnelId($userId);
            if ($personnelId) {
                $userId = $personnelId;
            }
        }
        // 处理字段
        $salary = $this->salaryRepository->entity
            ->where('report_id', $reportId)
            ->where('user_id', $userId)
            ->with('payDetails')
            ->first();
        if (!$salary) {
            return [];
        }

        $payDetails = $salary->payDetails;
        if (!$payDetails) {
            return [];
        }

        $detailMap = [];
        foreach ($payDetails as $detail) {
            $detailMap[$detail->field_id] = $detail->value;
        }
        $tempFields = $this->getShownHistoryFieldList($reportId);
        foreach ($tempFields as $key => $field) {
            $tempFields[$key]['value'] = $detailMap[$field['field_id']] ?? 0;
        }

        return $this->handleFieldsForReportForm($tempFields);
    }

    /**
     * 手机版我的薪资列表
     * @param $userId
     * @return array|mixed
     */
    public function getMySalaryListByMobile($userId, $param)
    {
        $param = $this->parseParams($param);
        // [无账号发薪]时，把$userId翻译成人事档案id，因为上报那里，储存的是人事档案id
        if ($this->payWithoutAccountConfig == "1") {
            // 用户id转换为档案的id
            $personnelId = $this->getUserPersonnelId($userId);
            if ($personnelId) {
                $userId = $personnelId;
            }
        }
        $param['page'] = $param['page'] ?? 0;
        $param['limit'] = $param['limit'] ?? 10;
        $param['search'] = [
            'user_id' => [$userId],
        ];
        $list = $this->salaryRepository->getListWithReport($param);

        $total = $this->salaryRepository->getSalaryTotal($param);

        return compact('list', 'total');
    }

}
