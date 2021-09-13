<?php
namespace App\EofficeApp\Attendance\Services;
use App\EofficeApp\Attendance\Traits\{AttendanceTransTrait, AttendanceOrgTrait, AttendanceExportTrait};
use App\EofficeApp\ImportExport\Facades\Export;
class AttendanceImportExportService extends AttendanceBaseService
{
    use AttendanceTransTrait;
    use AttendanceOrgTrait;
    use AttendanceExportTrait;
    private $attendanceStatService;
    private $attendanceRecordsService;
    private $br = "\r\n";
    public function __construct()
    {
        parent::__construct();
        $this->attendanceStatService = 'App\EofficeApp\Attendance\Services\AttendanceStatService';
        $this->attendanceRecordsService = 'App\EofficeApp\Attendance\Services\AttendanceRecordsService';
    }

    /**
     * 考勤导出
     *
     * @param array $param
     *
     * @return string
     */
    public function export($builder, $param)
    {
        $userParam = ['noPage' => true];
        $filterType = $param['filter_type'] ?? 1;
        switch ($filterType) {
            case 1: //按部门筛选
                $filterDeptId = $param['dept_id'] ?? [];
                if (empty($filterDeptId)) {
                    return ['code' => ['0x044031', 'attendance']];
                }
                $filterDeptId = is_array($filterDeptId) ? $filterDeptId : [$filterDeptId];
                $departmentRepository = app($this->departmentRepository);
                $depts = $departmentRepository->getDepartmentByIds($filterDeptId);
                $subTitle = trans('attendance.export') . implode(',', array_column($depts->toArray(), 'dept_name'));
                if ($param['comprise_son'] == 1) {
                    $userParam['dept_id'] = $this->getFamilyDetpId($departmentRepository, $filterDeptId);
                    $subTitle .= trans('attendance.all_child_department');
                } else {
                    $userParam['dept_id'] = $filterDeptId;
                }
                break;
            case 2: //按用户筛选
                $filterUserId = $param['user_id'] ?? [];
                if (empty($filterUserId)) {
                    return ['code' => ['0x044032', 'attendance']];
                }
                $userParam['user_id'] = $filterUserId;
                break;
            case 3: //按角色筛选
                $filterRoleId = $param['role_id'] ?? [];
                if (empty($filterRoleId)) {
                    return ['code' => ['0x044033', 'attendance']];
                }
                $subTitle = trans('attendance.export_role') . implode(',', $this->getRoleNamesById($filterRoleId));
                $userParam['user_id'] = array_column(app($this->userRoleRepository)->getUserRole(['role_id' => [$filterRoleId, 'in']], 1), 'user_id');
                break;
        }
        /**
         * 获取导出用户
         */
        $userParam['order_by'] = ['department.dept_id' => 'asc', 'user.user_id' => 'asc']; //按部门排序,按用户ID排序
        $includeLeave = (isset($param['include_leave']) && $param['include_leave'] == 1) ? true : false; // 判断是否获取离职人员
        $viewUser  = app($this->attendanceSettingService)->getPurviewUser($param['user_info'], $param['menu_id']); // 获取有权限的用户
        $users = $this->getExportUsers($viewUser, $userParam, $includeLeave);
        
        if ($filterType == 2) {
            $subTitle = trans('attendance.export_user') . implode(',', array_column($users, 'user_name')); //获取所有需要导出的用户名称
        }
        /**
         * 获取导出的日期范围
         */
        $dateType = $param['date_type'] == 0 ? [$param['customDate']['startDate'], $param['customDate']['endDate']] : $param['date_type'];
        list($startDate, $endDate) = $this->getDateByType($dateType);
        return $this->{$param['export_content'] . 'Export'}($builder, $startDate, $endDate, $users, $subTitle);
    }
    /**
     * 导出考勤汇总表
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $users
     * @param string $subTitle
     *
     * @return array
     */
    private function statExport($builder, $startDate, $endDate, $users, $subTitle)
    {
        $title = trans("attendance.Attendance_summary_table");
        $description = $startDate . trans("attendance.to") . $endDate . $this->br;
        $description .= trans('attendance.0x044102') . $this->br;
        $description .= "\t" . trans('attendance.0x044103') . $this->br;
        $description .= "\t" . trans('attendance.0x044104') . $this->br;
        $description .= "\t" . trans('attendance.0x044105') . $this->br;
        $description .= "\t" . trans('attendance.0x044106') . $this->br;
        $description .= "\t" . trans('attendance.0x044107') . $this->br;
        $description .= "\t" . trans('attendance.0x044108') . $this->br;
        $description .= "\t" . trans('attendance.0x044109');
        $header = app($this->attendanceStatService)->getStatTableHeader($startDate, $endDate, true);
        $data = app($this->attendanceStatService)->getStatTableBody($startDate, $endDate, $users, true);
        
        return $builder->setMode(2)->setFontSize(12)->setTitle($title)->setDescription($description, 180)
                        ->setHeader($header)->setData($data)->generate();
    }
    /**
     * 导出所有考勤记录
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $users
     * @param string $subTitle
     *
     * @return array
     */
    private function allRecordsExport($builder, $startDate, $endDate, $users, $subTitle)
    {
        $title = trans('attendance.all_attend_records_table');
        $description = $startDate . trans("attendance.to") . $endDate . $subTitle;
        $header = [
            'user_name' => ['data' => trans("attendance.user_name"), 'style' => ['width' => '100px']],
            'dept_name' => ['data' => trans("attendance.department"), 'style' => ['width' => '150px']],
            'sign_date' => ['data' => trans("attendance.date_of_attendance"), 'style' => ['width' => '160px']],
            'sign_time' => ['data' => trans("attendance.time_of_attendance"), 'style' => ['width' => '160px']],
            'sign_type' => ['data' => trans("attendance.type_of_attendance"), 'style' => ['width' => '80px']],
            'platform' => ['data' => trans("attendance.platform_of_attendance"), 'style' => ['width' => '80px']],
            'ip' => ['data' => trans("attendance.ip_of_attendance"), 'style' => ['width' => '160px']],
            'address' => ['data' => trans("attendance.address_of_attendance"), 'style' => ['width' => '360px']],
            'remark' => ['data' => trans("attendance.remark"), 'style' => ['width' => '360px']],
        ];
        $data = $this->getAllRecordsExportBody($startDate, $endDate, $users);
        return $builder->setMode(2)->setTitle($title)->setHeader($header)->setDescription($description)->setData($data)->generate();
    }
    /**
     * 导出考勤记录的表格体
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $users
     */
    private function getAllRecordsExportBody($startDate, $endDate, $users)
    {
        $userIds = array_column($users, 'user_id');
        $fields = ['user_id', 'sign_date', 'sign_time', 'sign_type', 'platform', 'ip', 'address', 'remark'];
        $records = app($this->attendanceSimpleRecordsRepository)->getMoreRecordsByDate($startDate, $endDate, $userIds, $fields);
        $recordGroups = $this->mapToGroups($records, function ($item, $key) {
            return [$item['user_id'] => $item];
        });
        $this->clearStaticDepartment();
        if (empty($userIds) || empty($recordGroups)) {
            return [];
        }
        $data = [];
        foreach ($users as $user) {
            $oneRecords = $recordGroups[$user['user_id']] ?? [];
            if (empty($oneRecords)) {
                continue;
            }
            foreach ($oneRecords as $record) {
                $data[] = [
                    ['data' => $user['user_name']],
                    ['data' => $this->getFullDepartmentName($user['dept_id'])],
                    ['data' => $record['sign_date'], 'dataType' => 'string'],
                    ['data' => $record['sign_time'], 'dataType' => 'string'],
                    ['data' => $this->getSignTypes($record['sign_type'])],
                    ['data' => $this->transPlatform($record['platform'])],
                    ['data' => $record['ip']],
                    ['data' => $record['address']],
                    ['data' => $record['remark']],
                ];
            }
        }
        return $data;
    }
    /**
     * 导出外发记录
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $users
     * @param string $subTitle
     *
     * @return array
     */
    private function outSendRecordExport($builder, $startDate, $endDate, $users, $subTitle = '')
    {
        $sheetNames = [
            'leave' => trans('attendance.leave'),
            'out' => trans('attendance.out'),
            'trip' => trans('attendance.business_trip'),
            'overtime' => trans('attendance.overtime'),
        ];
        $headers = [
            'leave' => [
                'user_name' => ['data' => trans('attendance.applicant'), 'style' => ['width' => '100px']],
                'dept_name' => ['data' => trans('attendance.department'), 'style' => ['width' => '100px']],
                'vacation_name' => ['data' => trans('attendance.holiday_types'), 'style' => ['width' => '100px']],
                'leave_start_time' => ['data' => trans('attendance.start_time'), 'style' => ['width' => '150px']],
                'leave_end_time' => ['data' => trans('attendance.end_time'), 'style' => ['width' => '150px']],
                'leave_days' => ['data' => trans('attendance.days'), 'style' => ['width' => '60px']],
                'run_name' => ['data' => trans('attendance.associated_process'), 'style' => ['width' => '150px']],
                'leave_reason' => ['data' => trans('attendance.reason'), 'style' => ['width' => '260px']],
            ],
            'out' => [
                'user_name' => ['data' => trans('attendance.applicant'), 'style' => ['width' => '100px']],
                'dept_name' => ['data' => trans('attendance.department'), 'style' => ['width' => '100px']],
                'out_start_time' => ['data' => trans('attendance.start_time'), 'style' => ['width' => '150px']],
                'out_end_time' => ['data' => trans('attendance.end_time'), 'style' => ['width' => '150px']],
                'out_days' => ['data' => trans('attendance.days'), 'style' => ['width' => '60px']],
                'run_name' => ['data' => trans('attendance.associated_process'), 'style' => ['width' => '150px']],
                'out_reason' => ['data' => trans('attendance.reason'), 'style' => ['width' => '260px']],
            ],
            'trip' => [
                'user_name' => ['data' => trans('attendance.applicant'), 'style' => ['width' => '100px']],
                'dept_name' => ['data' => trans('attendance.department'), 'style' => ['width' => '100px']],
                'trip_start_date' => ['data' => trans('attendance.start_time'), 'style' => ['width' => '150px']],
                'trip_end_date' => ['data' => trans('attendance.end_time'), 'style' => ['width' => '150px']],
                'trip_days' => ['data' => trans('attendance.days'), 'style' => ['width' => '60px']],
                'trip_area' => ['data' => trans('attendance.business_area'), 'style' => ['width' => '150px']],
                'run_name' => ['data' => trans('attendance.associated_process'), 'style' => ['width' => '150px']],
                'trip_reason' => ['data' => trans('attendance.reason'), 'style' => ['width' => '260px']],
            ],
            'overtime' => [
                'user_name' => ['data' => trans('attendance.applicant'), 'style' => ['width' => '100px']],
                'dept_name' => ['data' => trans('attendance.department'), 'style' => ['width' => '100px']],
                'overtime_start_time' => ['data' => trans('attendance.start_time'), 'style' => ['width' => '150px']],
                'overtime_end_time' => ['data' => trans('attendance.end_time'), 'style' => ['width' => '150px']],
                'overtime_days' => ['data' => trans('attendance.days'), 'style' => ['width' => '60px']],
                'run_name' => ['data' => trans('attendance.associated_process'), 'style' => ['width' => '150px']],
                'overtime_reason' => ['data' => trans('attendance.reason'), 'style' => ['width' => '260px']],
            ],
        ];

        $builder->setTitle(trans('attendance.External_record_table'));
        $sheetIndex = 0;
        foreach ($headers as $key => $header) {
            $sheetName = $sheetNames[$key] . trans('attendance.log_sheet');
            $data = $this->getOutsendData($startDate, $endDate, $users, $key);
            $builder->setActiveSheet($sheetIndex)->setSheetName($sheetName)->setHeader($header)->setData($data);
            $sheetIndex ++;
        }
        return $builder->generate();
    }
    /**
     * 根据起始日期获取请假，外出，出差，加班一个sheet的数据
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $users
     * @param string $type
     *
     * @return array
     */
    private function getOutsendData($startDate, $endDate, $users, $type)
    {
        /**
         * 获取外发数据
         */
        if ($type === 'trip') {
            $startDate = $startDate . ' 00:00:00';
            $endDate = $endDate . ' 23:59:59';
        }
        $startField = $this->outSendTimeKeys[$type][0];
        $endField = $this->outSendTimeKeys[$type][1];
        $param = [
            'page' => 0,
            'search' => ['user_id' => [array_column($users, 'user_id'), 'in']],
            'orSearch' => [$startField => [[$startDate, $endDate], 'between'], $endField => [[$startDate, $endDate], 'between']],
            'order_by' => ['user_id' => 'asc', $startField => 'asc']
        ];
        $records = app($this->{'attendance' . ucfirst($type) . 'Repository'})->getOutSendDataLists($param)->toArray();
        $data = $this->arrayGroupWithKeys($records, 'user_id', true);
        /**
         * 组装导出excel结构的外发数据
         */
        $items = [];
        $this->clearStaticDepartment();
        foreach ($users as $user) {
            $currentData = $data[$user['user_id']] ?? [];
            if (empty($currentData)) {
                continue;
            }
            foreach ($currentData as $item) {
                $item['user_name'] = $user['user_name'];
                $item['dept_name'] = $this->getFullDepartmentName($user['dept_id']);
                if ($type == 'leave') {
                    $vacation = $this->getVacationInfo($item['vacation_id']);
                    $item['vacation_name'] = $vacation->vacation_name;
                }
                $extra = json_decode($item[$type . '_extra'], true);
                $item['run_name'] = $extra['run_name'];
                $items[] = $item;
            }
        }
        return $items;
    }
    /**
     * 导出考勤记录表
     *
     * @param type $year
     * @param type $month
     * @param type $users
     * @param type $subTitle
     *
     * @return string
     */
    private function recordExport($builder, $startDate, $endDate, $users, $subTitle)
    {
        ini_set('memory_limit', '4096M');
        $title = trans("attendance.TAttendanceRecords");
        $description = $startDate . trans("attendance.to") . $endDate . $subTitle;
        $header = [
            'user_name' => ['data' => trans("attendance.user_name"), 'style' => ['width' => '80px']],
            'dept_name' => ['data' => trans("attendance.department"), 'style' => ['width' => '140px']],
            'sign_date' => ['data' => trans("attendance.date_of_attendance"), 'style' => ['width' => '100px']],
            'scheduling' => ['data' => trans("attendance.scheduling"), 'style' => ['width' => '80px']],
            'attend_hours' => ['data' => trans("attendance.Should_attendance") . "\r\n" . trans("attendance.labor"), 'style' => ['width' => '80px']],
            'real_attend_hours' => ['data' => trans("attendance.actual_attendance") . "\r\n" . trans("attendance.labor"), 'style' => ['width' => '80px']],
            'sign_in' => [
                'data' => trans("attendance.Sign_in_work"),
                'children' => [
                    'time' => ['data' => trans("attendance.time"), 'style' => ['width' => '100px']],
                    'ip' => ['data' => 'IP', 'style' => ['width' => '140px']],
                    'address' => ['data' => trans("attendance.address"), 'style' => ['width' => '180px']],
                    'platform' => ['data' => trans("attendance.platform"), 'style' => ['width' => '60px']]
                ]
            ],
            'sign_out' => [
                'data' => trans("attendance.Sign_back_work"),
                'children' => [
                    'time' => ['data' => trans("attendance.time"), 'style' => ['width' => '100px']],
                    'ip' => ['data' => 'IP', 'style' => ['width' => '140px']],
                    'address' => ['data' => trans("attendance.address"), 'style' => ['width' => '180px']],
                    'platform' => ['data' => trans("attendance.platform"), 'style' => ['width' => '60px']]
                ]
            ],
            'leave' => [
                'data' => trans("attendance.leave"),
                'children' => [
                    'day' => ['data' => trans("attendance.day"), 'style' => ['width' => '60px']],
                    'hours' => ['data' => trans("attendance.duration"), 'style' => ['width' => '60px']],
                    'salary_day' => ['data' => trans("attendance.day_salary"), 'style' => ['width' => '60px']],
                    'salary_hours' => ['data' => trans("attendance.duration_salary"), 'style' => ['width' => '60px']]
                ]
            ],
            'trip' => [
                'data' => trans("attendance.business_trip"),
                'children' => [
                    'day' => ['data' => trans("attendance.day"), 'style' => ['width' => '60px']],
                    'hours' => ['data' => trans("attendance.duration"), 'style' => ['width' => '60px']]
                ]
            ],
            'out' => [
                'data' => trans("attendance.out"),
                'children' => [
                    'day' => ['data' => trans("attendance.day"), 'style' => ['width' => '60px']],
                    'hours' => ['data' => trans("attendance.duration"), 'style' => ['width' => '60px']]
                ]
            ],
            'overtime' => [
                'data' => trans("attendance.overtime"),
                'children' => [
                    'day' => ['data' => trans("attendance.day"), 'style' => ['width' => '60px']],
                    'hours' => ['data' => trans("attendance.duration"), 'style' => ['width' => '60px']]
                ]
            ],
            'result' => ['data' => trans("attendance.the_result_attendance"), 'style' => ['width' => '140px']]
        ];
        if(count($users) > 100) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $interval = \DateInterval::createFromDateString('1 month');
            $period = new \DatePeriod($start, $interval, $end);
            $months = [];
            $count = 0;
            foreach ($period as $item) {
                ++$count;
                $months[] = $item->format("Y-m");
            }
            if ($count >= 3) {
                $files = [];
                foreach ($months as $key => $month) {
                    $firstDay = date('Y-m-01', strtotime($month));
                    $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
                    if ($key === 0) {
                        $_startDate = $startDate;
                        $_endDate = $lastDay;
                    } else if ($key === $count - 1) {
                        $_startDate = $firstDay;
                        $_endDate = $endDate;
                    } else {
                        $_startDate = $firstDay;
                        $_endDate = $lastDay;
                    }
                    $data = $this->getRecordExportData($_startDate, $_endDate, $users);
                    list($fileName, $filePath) = $builder->setTitle($_startDate . '_' . $_endDate)
                            ->setHeader($header)
                            ->setDescription($_startDate . trans("attendance.to") . $_endDate . $subTitle)
                            ->setData($data)->setMode(2)->generate();
                    $files[] = ['./' . $_startDate . '~' . $_endDate . '_' . $title . '.' . $builder->getSuffix() => $filePath];
                }
                return Export::saveAsZip($files, $title);
            }
        }
        $data = $this->getRecordExportData($startDate, $endDate, $users);
        return $builder->setTitle($title)->setDescription($description)->setHeader($header)->setData($data)->setMode(2)->generate();
    }
    /**
     * 获取考勤记录表数据部分
     *
     * @param type $startDate
     * @param type $endDate
     * @param type $users
     *
     * @return string
     */
    private function getRecordExportData($startDate, $endDate, $users)
    {
        $this->clearStaticDepartment();
        $userIds = array_column($users, 'user_id');
        $allDate = $this->getDateFromRange($startDate, $endDate);
        // 获取排班
        $schedulingDates = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, $userIds);

        $records = app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userIds, 'in']]);
        $recordsGroup = $this->arrayGroupWithKeys($records);
        $outStats = app($this->attendanceOutStatRepository)->getMoreUserAttendOutRecordsByDate($startDate, $endDate, $userIds);
        $leaveStats = app($this->attendanceLeaveDiffStatRepository)->getMoreUserAttendLeaveRecordsByDate($startDate, $endDate, $userIds);
        $overtimeStats = app($this->attendanceOvertimeStatRepository)->getMoreUserAttendOvertimeRecordsByDate($startDate, $endDate, $userIds);
        $tripStats = app($this->attendanceTripStatRepository)->getMoreUserAttendTripRecordsByDate($startDate, $endDate, $userIds);
        // 兼容跨天班，结束日期往后推一天
        $nextEndDate = $this->getNextDate($endDate);
        // 获取按用户分组的请假记录,按日期拆分请假记录
        $leavesGroup = $this->arrayGroupWithKeys(app($this->attendanceLeaveRepository)->getLeaveRecordsByDateScopeAndUserIds($startDate, $nextEndDate, $userIds));
        $leavesGroup = $this->splitDatetimeByDateFromLeaveOrOutGroup($leavesGroup, 'leave_start_time', 'leave_end_time');
        // 获取按用户分组的外出记录,按日期拆分外出记录
        $outsGroup = $this->arrayGroupWithKeys(app($this->attendanceOutRepository)->getOutRecordsByDateScopeAndUserIds($startDate, $nextEndDate, $userIds));
        $outsGroup = $this->splitDatetimeByDateFromLeaveOrOutGroup($outsGroup, 'out_start_time', 'out_end_time');
        // 获取按用户分组的外出记录,按日期拆分出差记录
        $tripsGroup = $this->arrayGroupWithKeys(app($this->attendanceTripRepository)->getTripRecordsByDateScopeAndUserIds($startDate, $nextEndDate, $userIds));
        $tripsGroup = $this->splitDatetimeByDateFromLeaveOrOutGroup($tripsGroup, 'trip_start_date', 'trip_end_date');
        // 合并请假，外出时间，主要用于考勤抵消
        $offsetDatetimeGroup = $this->combileLeaveOutTripByDateMapWithUserIds($leavesGroup, $outsGroup, $tripsGroup, $userIds);
        $recordService = app($this->attendanceRecordsService);
        $allowNoSignOutUserIdsMap = app($this->attendanceSettingService)->getAllowNoSignOutUserIdsMap($userIds);
        $items = [];
        foreach ($users as $user) {
            $userId = $user['user_id'];
            $currRecords = $this->arrayGroupWithKeys($this->defaultValue($userId, $recordsGroup, []), 'sign_date');
            $currOutStats = $this->arrayMapWithKeys($this->defaultValue($userId, $outStats, []), 'date');
            $currLeaveStats = $this->arrayMapWithKeys($this->defaultValue($userId, $leaveStats, []), 'date');
            $currOvertimeStats = $this->arrayMapWithKeys($this->defaultValue($userId, $overtimeStats, []), 'date');
            $currTripStats = $this->arrayMapWithKeys($this->defaultValue($userId, $tripStats, []), 'date');
            $currSchedulingDates = $this->arrayMapWithKeys($this->defaultValue($userId, $schedulingDates, []), 'scheduling_date');
            $offsetDatetimes = $offsetDatetimeGroup[$userId] ?? [];
            $allowNoSignOut = $allowNoSignOutUserIdsMap[$userId] ?? false;
            foreach ($allDate as $date) {
                $shift = null;
                $attendTime = 0;
                if (isset($currSchedulingDates[$date]) && $currSchedulingDates[$date]) {
                    $shiftId = $currSchedulingDates[$date]['shift_id'];
                    $shift = $this->getShiftById($shiftId);
                    $attendTime = $shift->attend_time;
                }
                $oneDateRecords = $currRecords[$date] ?? [];
                $outSendData = [
                    'out' => $currOutStats[$date] ?? null,
                    'leave' => $currLeaveStats[$date] ?? null,
                    'trip' => $currTripStats[$date] ?? null,
                    'overtime' => $currOvertimeStats[$date] ?? null
                ];
                $result = $recordService->handleOneRecordForMore($user, $date, $oneDateRecords, $shift, $outSendData, $offsetDatetimes, $allowNoSignOut);
                $shiftTimeTd = $this->getShiftTimeTd($result);
                $signInTimeTd = $signInIpTd = $signInAddrTd = $signInPlatformTd = '';
                $signOutTimeTd = $signOutIpTd = $signOutAddrTd = $signOutPlatformTd = '';
                $resultTd = '';
                $resultStyle = [];
                if ($shift && $shift->shift_type == 2) {
                    if (!empty($result['sign_in'])) {
                        foreach ($result['sign_in'] as $key => $item) {
                            $signInTimeTd .= $this->periodTitle($key) . $item['sign_time'] . $this->br;
                            $signInIpTd .= $this->periodTitle($key) . $item['ip'] . $this->br;
                            if ($item['address']) {
                                $signInAddrTd .= $this->periodTitle($key) . $item['address'] . $this->br;
                            }
                            $signInPlatformTd .= $this->periodTitle($key) . $item['platform'] . $this->br;
                        }
                    }
                    if (!empty($result['sign_out'])) {
                        foreach ($result['sign_out'] as $key => $item) {
                            $signOutTimeTd .= $this->periodTitle($key) . $item['sign_time'] . $this->br;
                            $signOutIpTd .= $this->periodTitle($key) . $item['ip'] . $this->br;
                            if ($item['address']) {
                                $signOutAddrTd .= $this->periodTitle($key) . $item['address'] . $this->br;
                            }
                            $signOutPlatformTd .= $this->periodTitle($key) . $item['platform'] . $this->br;
                        }
                    }

                    foreach ($result['shift']['shift_times'] as $key => $item) {
                        $number = $key + 1;
                        $resultTd .= $this->periodTitle($number);
                        if (!empty($result['result']) && !empty($result['result'][$number])) {
                            if (is_array($result['result'][$number])) {
                                foreach ($result['result'][$number] as $item) {
                                    if ($this->trans($item)) {
                                        $resultTd .= $this->trans($item) . ',';
                                        $this->setResultTdStyle($resultStyle, $item);
                                    }
                                }
                            } else {
                                $resultTd .= $result['result'] === 'empty' ? '-' : $this->trans('rest');
                            }
                        } else {
                            $resultTd .= $this->trans('normal');
                        }
                        $resultTd = rtrim($resultTd, ',');
                        $resultTd .= $this->br;
                    }
                } else {
                    if (!empty($result['sign_in'])) {
                        $signInItem = $result['sign_in'][1];
                        $signInTimeTd .= $signInItem['sign_time'];
                        $signInIpTd .= $signInItem['ip'];
                        if ($signInItem['address']) {
                            $signInAddrTd .= $signInItem['address'];
                        }
                        $signInPlatformTd .= $signInItem['platform'];
                    }
                    if (!empty($result['sign_out'])) {
                        $signOutItem = $result['sign_out'][1];
                        $signOutTimeTd .= $signOutItem['sign_time'];
                        $signOutIpTd .= $signOutItem['ip'];
                        if ($signOutItem['address']) {
                            $signOutAddrTd .= $signOutItem['address'];
                        }
                        $signOutPlatformTd .= $signOutItem['platform'];
                    }
                    if ($result['result'] == 'empty') {
                        $resultTd .= '-';
                    } else if ($result['result'] == 'rest') {
                        $resultTd .= $this->trans('rest');
                    } else {
                        if (!empty($result['result']) && !empty($result['result'][1])) {
                            foreach ($result['result'][1] as $item) {
                                if ($this->trans($item)) {
                                    $resultTd .= $this->trans($item) . ',';
                                    $this->setResultTdStyle($resultStyle, $item);
                                }
                            }
                            $resultTd = rtrim($resultTd, ',');
                        } else {
                            $resultTd .= $this->trans('normal');
                        }
                    }
                }
                
                $items[] = [
                    ['data' => $user['user_name']],
                    ['data' => $this->getFullDepartmentName($user['dept_id'])],
                    ['data' => $date, 'type' => 'string'],
                    ['data' => $shiftTimeTd],
                    ['data' => $result['attend_hours']],
                    ['data' => $result['real_attend_hours']],
                    ['data' => $signInTimeTd, 'dataType' => 'string'],
                    ['data' => $signInIpTd],
                    ['data' => $signInAddrTd],
                    ['data' => $signInPlatformTd],
                    ['data' => $signOutTimeTd, 'dataType' => 'string'],
                    ['data' => $signOutIpTd],
                    ['data' => $signOutAddrTd],
                    ['data' => $signOutPlatformTd],
                    ['data' => $result['leave']['day'] ?? 0],
                    ['data' => $result['leave']['hours'] ?? 0],
                    ['data' => 0],
                    ['data' => 0],
                    ['data' => $result['trip']['day'] ?? 0],
                    ['data' => $result['trip']['hours'] ?? 0],
                    ['data' => $result['out']['day'] ?? 0],
                    ['data' => $result['out']['hours'] ?? 0],
                    ['data' => $result['overtime']['day'] ?? 0],
                    ['data' => $result['overtime']['hours'] ?? 0],
                    ['data' => $resultTd, 'style' => $resultStyle],
                ];
            }
        }
        return $items;
    }
    private function getShiftTimeTd($result)
    {
        if ($result['shift']) {
            $shiftTimeTd = '';
            foreach ($result['shift']['shift_times'] as $item) {
                $shiftTimeTd .= $item->sign_in_time . ' ~ ' . $item->sign_out_time . ',';
            }
            return rtrim($shiftTimeTd, ',');
        } 
        return $this->trans('rest');
    }
    private function setResultTdStyle(&$style, $item)
    {
        if ($item == 'absenteeism') {
            $style = ['background' => 'f04134', 'color' => 'ffffff'];
        } else if (in_array($item, ['lag', 'leave_early', 'no_sign_out', 'no_offset_all'])) {
            if (!isset($style['background']) || $style['background'] != 'f04134') {
                $style = ['background' => 'f4aa16', 'color' => 'ffffff'];
            }
        }
    }
    private function periodTitle($number = 1)
    {
        return $this->trans('period') . $number . ':';
    }
     /**
     * 导出考勤校准记录表
     *
     * @param type $year
     * @param type $month
     * @param type $users
     * @param type $subTitle
     *
     * @return string
     */
    private function calibrationRecordExport($builder, $startDate, $endDate, $users, $subTitle)
    {
        $title = trans("attendance.Calibration_record");
        $description = $startDate . trans("attendance.to") . $endDate . $subTitle;
        $header = [
            ['data' => trans("attendance.user_name"), 'style' => ['width' => '80px']],
            ['data' => trans("attendance.department"), 'style' => ['width' => '140px']],
            ['data' => trans("attendance.date_of_attendance"), 'style' => ['width' => '100px']],
            ['data' => trans("attendance.scheduling"), 'style' => ['width' => '100px']],
            ['data' => trans("attendance.Work_attendance"), 'style' => ['width' => '60px']],
            ['data' => trans("attendance.Sign_in_work"), 'style' => ['width' => '140px']],
            ['data' => trans("attendance.Sign_back_work"), 'style' => ['width' => '140px']],
            ['data' => trans("attendance.calibration_time"), 'style' => ['width' => '140px']],
            ['data' => trans("attendance.calibration_length"), 'style' => ['width' => '140px']],
            ['data' => trans("attendance.calibration_reason"), 'style' => ['width' => '300px']],
            ['data' => trans("attendance.calibration_status"), 'style' => ['width' => '100px']]
        ];
        $data = $this->getCalibrationRecordExportBody($startDate, $endDate, $users);
        
        return $builder->setTitle($title)->setDescription($description)->setHeader($header)->setData($data)->generate();
    }
    /**
     * 获取考勤校准表数据
     *
     * @param type $startDate
     * @param type $endDate
     * @param type $users
     *
     * @return string
     */
    private function getCalibrationRecordExportBody($startDate, $endDate, $users)
    {
        $userIds = array_column($users, 'user_id');
        $fields = ['user_id', 'sign_date', 'sign_in_time', 'sign_in_normal', 'sign_out_time', 'sign_out_normal', 'lag_time', 'leave_early_time', 'is_lag', 'is_leave_early', 'calibration_status', 'calibration_reason', 'calibration_aprove_time', 'calibration_time', 'attend_type'];
        $records = app($this->attendanceRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userIds, 'in'], 'calibration_status' => [[1, 2, 3], 'in']], $fields);
        $recordMap = $this->arrayGroupWithKeys($records, 'user_id');
        $this->clearStaticDepartment();
        $items = [];
        foreach ($users as $user) {
            if (isset($recordMap[$user['user_id']])) {
                $currRecords = $recordMap[$user['user_id']];
                foreach ($currRecords as $record) {
                    $signIn = $record->sign_in_time;
                    $signInStyle = '';
                    if ($record->is_lag) {
                        $signIn .= '[' . trans("attendance.late") . ']';
                        $signInStyle = ['background' => '#dc3545', 'color' => '#ffffff'];
                    }

                    $signOut = $signOutStyle = '';
                    if ($record->sign_out_time) {
                        $signOut .= $record->sign_out_time;
                        if ($record->is_leave_early) {
                            $signOut .= '[' . trans("attendance.early") . ']';
                            $signOutStyle = ['background' => '##dc3545', 'color' => '#ffffff'];
                        }
                    } else {
                        $signOut = trans("attendance.nout");
                        $signOutStyle = ['background' => '##dc3545', 'color' => '#ffffff'];
                    }

                    $calibrationStatusStyle = '';
                    if ($record->calibration_status == 1) {
                        $calibrationStatus = trans("attendance.for_calibration");
                        $calibrationStatusStyle = ['background' => '#FF9800', 'color' => '#ffffff'];
                    } else if ($record->calibration_status == 2) {
                        $calibrationStatus = trans("attendance.has_calibration");
                        $calibrationStatusStyle = ['background' => '#008000', 'color' => '#ffffff'];
                    } else if ($record->calibration_status == 0) {
                        $calibrationStatus = trans("attendance.unapply");
                        $calibrationStatusStyle = ['color' => '#999999'];
                    } else {
                        $calibrationStatus = trans("attendance.has_returned");
                        $calibrationStatusStyle = ['background' => '#FC004A', 'color' => '#ffffff'];
                    }
                    $items[] = [
                        ['data' => $user['user_name']],
                        ['data' => $this->getFullDepartmentName($user['dept_id'])],
                        ['data' => $record->sign_date],
                        ['data' => $record->sign_in_normal . ' - ' . $record->sign_out_normal],
                        ['data' => $record->attend_type == 1 ? trans("attendance.yes") : trans("attendance.no")],
                        ['data' => $signIn, 'style' => $signInStyle],
                        ['data' => $signOut, 'style' => $signOutStyle],
                        ['data' => $record->calibration_aprove_time],
                        ['data' => $this->formatTimeToDetail($record->calibration_time)],
                        ['data' => $record->calibration_reason],
                        ['data' => $calibrationStatus, 'style' => $calibrationStatusStyle],
                    ];
                }
            }
        }
        return $items;
    }
    private function formatTimeToDetail($time)
    {
        if ($time) {
            $secondTime = intval($time);
            $minuteTime = 0;
            $hourTime = 0;
            if ($secondTime > 60) {
                $minuteTime = intval($secondTime / 60);
                $secondTime = intval($secondTime % 60);
                if ($minuteTime > 60) {
                    $hourTime = intval($minuteTime / 60);
                    $minuteTime = intval($minuteTime % 60);
                }
            }
            $result = '' . intval($secondTime) . trans("attendance.second_for_length");
            if ($minuteTime > 0 || $hourTime > 0) {
                $result = '' . intval($minuteTime) . trans("attendance.minutes_for_length") . $result;
            }
            if ($hourTime > 0) {
                $result = '' . intval($hourTime) . trans("attendance.hour_for_length") . $result;
            }
            return $result;
        }
        return $time;
    }
    /**
     * 移动考勤记录导出
     *
     * @param object $builder
     * @param type $startDate
     * @param type $endDate
     * @param type $users
     * @param type $subTitle
     *
     * @return string
     */
    private function mobileRecordExport($builder, $startDate, $endDate, $users, $subTitle)
    {
        $title = trans("attendance.Move_the_attendance_record");
        $description = $startDate . trans("attendance.to") . $endDate . $subTitle;
        $header = [
            ['data' => trans("attendance.user_name"), 'style' => ['width' => '80px']],
            ['data' => trans("attendance.department"), 'style' => ['width' => '140px']],
            ['data' => trans("attendance.date_of_attendance"), 'style' => ['width' => '100px']],
            ['data' => trans("attendance.time_of_attendance"), 'style' => ['width' => '160px']],
            ['data' => trans("attendance.ip_of_attendance"), 'style' => ['width' => '120px']],
            ['data' => trans("attendance.address_of_attendance"), 'style' => ['width' => '300px']],
            ['data' => 'WiFi' . trans("attendance.name"), 'style' => ['width' => '200px']],
            ['data' => 'WiFi-MAC', 'style' => ['width' => '200px']],
            ['data' => trans("attendance.sign_way"), 'style' => ['width' => '80px']],
            ['data' => trans("attendance.platform_of_attendance"), 'style' => ['width' => '100px']],
            ['data' => trans("attendance.type_of_attendance"), 'style' => ['width' => '80px']],
            ['data' => trans("attendance.client_meeting"), 'style' => ['width' => '200px']],
            ['data' => trans("attendance.remark"), 'style' => ['width' => '250px']]
        ];
        $data = $this->getMobileRecordExportBody($startDate, $endDate, $users);
        
        return $builder->setTitle($title)->setDescription($description)->setHeader($header)->setData($data)->generate();
    }
    /**
     * 获取移动考勤记录表数据
     *
     * @param type $year
     * @param type $month
     * @param type $users
     *
     * @return string
     */
    private function getMobileRecordExportBody($startDate, $endDate, $users)
    {
        $userIds = array_column($users, 'user_id');
        $fields = ['user_id', 'sign_date', 'sign_time', 'ip', 'address', 'customer_id', 'platform', 'sign_type', 'sign_status', 'remark', 'wifi_name', 'wifi_mac', 'sign_category'];
        $records = app($this->attendanceMobileRecordsRepository)->getRecords(['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userIds, 'in']], $fields);
        $recordMap = $this->arrayGroupWithKeys($records, 'user_id');
        $this->clearStaticDepartment();
        $items = [];
        foreach ($users as $user) {
            if (isset($recordMap[$user['user_id']])) {
                foreach ($recordMap[$user['user_id']] as $record) {
                    $signType = '';
                    $signTypeStyle = [];
                    if ($record->sign_status == 1) {
                        if($record->sign_type == 2) {
                            $signType = trans("attendance.legwork").trans("attendance.sign_in");
                        } else {
                            $signType = trans("attendance.sign_in");
                        }
                        $signTypeStyle = ['background' => '#2196F3', 'color' => '#ffffff'];
                    } else if ($record->sign_status == 2) {
                        if($record->sign_type == 2) {
                            $signType = trans("attendance.legwork") . trans("attendance.sign_out");
                        } else {
                            $signType = trans("attendance.sign_out");
                        }
                        $signTypeStyle = ['background' => '#F6BF26', 'color' => '#ffffff'];
                    } else if ($record->sign_status == 4) {
                        $signType = trans("attendance.punch");
                        $signTypeStyle = ['background' => '#2196F3', 'color' => '#ffffff'];
                    } else {
                        $signType = trans("attendance.report_location");
                        $signTypeStyle = ['background' => '#34CC09', 'color' => '#ffffff'];
                    }

                    $items[] = [
                        ['data' => $user['user_name']],
                        ['data' => $this->getFullDepartmentName($user['dept_id'])],
                        ['data' => $record->sign_date, 'style' => ['vnd.ms-excel.numberformat'  => '@']],
                        ['data' => $record->sign_time, 'style' => ['vnd.ms-excel.numberformat'  => '@']],
                        ['data' => $record->ip],
                        ['data' => $record->address],
                        ['data' => $record->wifi_name],
                        ['data' => $record->wifi_mac],
                        ['data' => $record->sign_category == 1 ? trans("attendance.location") : 'WI-FI'],
                        ['data' => $this->transPlatform($record->platform)],
                        ['data' => $signType, 'style' => $signTypeStyle],
                        ['data' => $record->customer_id ? $this->getCustomerAttr($record->customer_id) : ''],
                        ['data' => $record->remark],
                    ];
                }
            }
        }
        return $items;
    }
    /**
     * 获取角色名称
     *
     * @param array $roleId
     *
     * @return array
     */
    private function getRoleNamesById($roleId)
    {
        $roles = app($this->roleRepository)->getAllRoles(['search' => ['role_id' => [$roleId, 'in']], 'fields' => ['role_name']])->toArray();
        return array_column($roles, 'role_name');
    }
    /**
     * 获取所有部门ID，包含所有子部门
     *
     * @param Object $departmentRepository
     * @param array $deptIds
     *
     * @return array
     */
    private function getFamilyDetpId($departmentRepository, $deptIds)
    {
        $allDeptId = $deptIds;

        foreach ($deptIds as $deptId) {
            $allDeptId = array_merge($allDeptId, array_column($departmentRepository->getALLChlidrenByDeptId($deptId), 'dept_id'));
        }

        return $allDeptId;
    }
    /**
     * 获取所有导出的用户
     *
     * @param array $viewUser
     * @param array $userParam
     * @param boolean $includeLeave
     *
     * @return array
     */
    private function getExportUsers($viewUser, $userParam, $includeLeave)
    {
        $users = [];
        if (!empty($viewUser)) {
            if ($viewUser != 'all') {
                $userParam['user_id'] = isset($userParam['user_id']) ? array_intersect($userParam['user_id'], $viewUser) : $viewUser;
            }
            $users = app($this->userRepository)->getSimpleUserList($userParam, $includeLeave); //获取需要所有导出的用户
        }
        return $users;
    }
}