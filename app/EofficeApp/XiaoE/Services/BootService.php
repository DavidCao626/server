<?php

namespace App\EofficeApp\XiaoE\Services;

use App\Utils\XiaoE\Route;
use App\EofficeApp\XiaoE\Traits\AttendanceTrait;
use App\EofficeApp\XiaoE\Traits\FlowTrait;
use App\EofficeApp\XiaoE\Traits\XiaoETrait;

/**
 * 小e助手完成意图后，触发的引导函数的服务类
 *
 * @author lizhijun
 */
class BootService extends BaseService
{

    use XiaoETrait, FlowTrait, AttendanceTrait;

    private $limit = 50;
    private $iconPath = './main/img/icon';
    private $flowServicel;
    private $userRepository;
    private $attendanceService;
    private $vacationService;
    private $flowRunService;
    private $userService;
    private $meetingService;
    private $calendarService;
    private $calendarRecordService;
    private $diaryService;
    private $documentService;
    private $flowTypeRepository;
    private $systemService;
    private $salaryReportFormService;
    private $customerService;
    private $projectService;
    private $emailService;
    private $newsService;
    private $contractService;
    private $formModelingService;

    public function __construct()
    {
        $this->flowService = 'App\EofficeApp\Flow\Services\FlowService';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->attendanceService = 'App\EofficeApp\Attendance\Services\AttendanceService';
        $this->attendanceStatService = 'App\EofficeApp\Attendance\Services\AttendanceStatService';
        $this->attendanceSettingService = 'App\EofficeApp\Attendance\Services\AttendanceSettingService';
        $this->vacationService = 'App\EofficeApp\Vacation\Services\VacationService';
        $this->flowRunService = 'App\EofficeApp\Flow\Services\FlowRunService';
        $this->meetingService = 'App\EofficeApp\Meeting\Services\MeetingService';
        $this->calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';
        $this->calendarRecordService = 'App\EofficeApp\Calendar\Services\CalendarRecordService';
        $this->diaryService = 'App\EofficeApp\Diary\Services\DiaryService';
        $this->documentService = 'App\EofficeApp\Document\Services\DocumentService';
        $this->flowTypeRepository = 'App\EofficeApp\Flow\Repositories\FlowTypeRepository';
        $this->systemService = 'App\EofficeApp\XiaoE\Services\SystemService';
        $this->salaryReportFormService = 'App\EofficeApp\Salary\Services\SalaryReportFormService';
        $this->customerService = 'App\EofficeApp\Customer\Services\CustomerService';
        $this->projectService = 'App\EofficeApp\Project\Services\ProjectService';
        $this->emailService = 'App\EofficeApp\Email\Services\EmailService';
        $this->newsService = 'App\EofficeApp\News\Services\NewsService';
        $this->contractService = 'App\EofficeApp\Contract\Services\ContractService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    }

    /**
     * 发起请假流程&&提交
     * @param $response
     */
    public function leave($response, $own)
    {
        list($startTime, $endTime) = explode('_', $response['time']);
        $userId = $response['creator'];
        $response['start_time'] = $startTime;//开始时间
        $response['end_time'] = $endTime;//结束时间
        $response['creator'] = $userId;//申请人
        $data = $this->handleFlowFormData($response, 'leave.create', $own, function ($formData, $formField, $xiaoeField, $type) use ($response, $userId) {
            if ($xiaoeField == 'vacationType' && $type == 'select') {
                $formData[$formField . '_TEXT'] = make($this->vacationService, false)->getVacationData($response['vacationType'])['vacation_name'];
            }
            return $formData;
        });
        if (isset($data['code'])) {
            return $data;
        }
        if ($run = make($this->flowService)->newPageSaveFlowInfo($data)) {
            return $this->turnFlow($own, $data['flow_id'], $run);
        }
    }

    /**
     * 出差
     * @param $response
     * @param $own
     */
    public function businessTrip($response, $own)
    {
        list($startTime, $endTime) = explode('_', $response['time']);
        $userId = $response['creator'];
        $response['start_time'] = $startTime;//开始时间
        $response['end_time'] = $endTime;//结束时间
        $response['creator'] = $userId;//申请人
        $data = $this->handleFlowFormData($response, 'business_trip.create', $own);
        if (isset($data['code'])) {
            return $data;
        }
        if ($run = make($this->flowService)->newPageSaveFlowInfo($data)) {
            return $this->turnFlow($own, $data['flow_id'], $run);
        }
    }

    /**
     * 加班
     * @param $response
     * @param $own
     */
    public function overtime($response, $own)
    {
        list($startTime, $endTime) = explode('_', $response['time']);
        $userId = $response['creator'];
        $response['start_time'] = $startTime;//开始时间
        $response['end_time'] = $endTime;//结束时间
        $response['creator'] = $userId;//申请人
        //获取下调休假的id
        if (!$vacationId = app('App\EofficeApp\Vacation\Repositories\VacationRepository')->getVacationId('调休假')) {
            return ['code' => ['0x052002', 'vacation']];
        }
        $response['vacationType'] = $vacationId;
        $data = $this->handleFlowFormData($response, '_regex.overtime', $own, function ($formData, $formField, $xiaoeField, $type) use ($response, $userId) {
            if ($xiaoeField == 'vacationType' && $type == 'select') {
                $formData[$formField . '_TEXT'] = '调休假';
            }
            return $formData;
        });
        if (isset($data['code'])) {
            return $data;
        }
        if ($run = make($this->flowService)->newPageSaveFlowInfo($data)) {
            return $this->turnFlow($own, $data['flow_id'], $run);
        }
    }

    /**
     * 我的假期信息
     * @param $response
     * @param $own
     */
    public function getVacationDays($response, $own)
    {
        $return['list'] = array();
        $userId = $response['creator'];
        // 动态单位
        $setData = app('App\EofficeApp\Vacation\Repositories\VacationRepository')->getVacationSet();
        $isTrans = $setData['is_transform'] ?? 0;
        $unit = $isTrans ? '小时数' : '天数';

        $vacations = make($this->vacationService)->getMyVacationData($userId);
        if ($vacations) {
            foreach ($vacations as $vacation) {
                $item['name'] = $vacation['vacation_name'];
                $item['detail'] = array();
                if (isset($vacation['his'])) {
                    $item['detail'][] = [
                        'name' => '上一年剩余'.$unit,
                        'value' => round($vacation['his'], 2)
                    ];
                    $item['detail'][] = [
                        'name' => '今年剩余'.$unit,
                        'value' => round($vacation['cur'], 2)
                    ];
                    $item['detail'][] = [
                        'name' => '当前可请'.$unit,
                        'value' => round(($vacation['cur'] + $vacation['his']), 2)
                    ];
                } else {
                    $item['detail'][] = [
                        'name' => '当前可请'.$unit,
                        'value' => round($vacation['cur'], 2)
                    ];
                }
                $return['list'][] = $item;
            }
        }
        return $return;
    }

    /**
     * 我的代办流程
     * @param $response
     */
    public function workflowPending($response, $own)
    {
        $params = [
            'limit' => $this->limit,
            'page' => 1,
            'platform' => 'mobile',
            'search' => [],
            'user_id' => $response['creator'],
            'order_by' => ['receive_time' => 'desc']
        ];
        $data = make($this->flowService)->getTeedToDoList($params, $own)['list'];
        $relations = [
            'simpleTitle' => ['flow_run_process_belongs_to_flow_run.run_name'],
            'simpleDesc' => function ($flow) {
                $creator = $flow['flow_run_process_belongs_to_flow_run']['flow_run_has_one_user']['user_name'] ?? '';
                $category = $flow['flow_run_process_belongs_to_flow_run']['flow_run_has_one_flow_type']['flow_type_belongs_to_flow_sort']['title'] ?? '';
                return "创建人:$creator/流程分类:$category";
            },
            'url' => function ($flow) {
                $flowId = $flow['flow_id'];
                $runId = $flow['run_id'];
                $flowRunProcessId = $flow['flow_run_process_id'];
                return Route::navigate('/flow/handle', ['run_id' => $runId, 'flow_id' => $flowId, 'flow_run_process_id' => $flowRunProcessId]);
            },
            'isnew' => 1,     // 先声明 后面覆盖
            'image' => $this->iconPath . '/daiban.png',
        ];
        $formatData =  $this->windowViewApproval($data, $relations);

        return $this->processFlowDataUnreadStatus($formatData, $data);
    }

    /**
     * 我的已办流程
     * @param $response
     */
    public function workflowAlready($response, $own)
    {
        $params = [
            'limit' => $this->limit,
            'page' => 1,
            'platform' => 'mobile',
            'search' => [],
            'user_id' => $response['creator'],
            'user_info' => $own,
            'order_by' => [
                'transact_time' => 'desc'
            ]
        ];
        $data = make($this->flowService)->getAlreadyDoList($params)['list'];
        $relations = [
            'simpleTitle' => ['flow_run_process_belongs_to_flow_run.run_name'],
            'simpleDesc' => function ($flow) {
                $creator = $flow['flow_run_process_belongs_to_flow_run']['flow_run_has_one_user']['user_name'] ?? '';
                $category = $flow['flow_run_process_belongs_to_flow_run']['flow_run_has_one_flow_type']['flow_type_belongs_to_flow_sort']['title'] ?? '';
                return "创建人:$creator/流程分类:$category";
            },
            'url' => function ($flow) {
                $flowId = $flow['flow_id'];
                $runId = $flow['run_id'];
                return Route::navigate('/flow/view', ['run_id' => $runId, 'flow_id' => $flowId]);
            },
            'isnew' => 1,
            'image' => $this->iconPath . '/yiban.png',
        ];
        $formatData =  $this->windowViewApproval($data, $relations);

        return $this->processFlowDataUnreadStatus($formatData, $data);
    }

    /**
     * 创建流程
     * @param $response
     */
    public function workflowCreate($response)
    {
        if (!isset($response['flowType'])) {
            return '';
        }
        $flowId = $response['flowType'];
        $url = Route::navigate('/flow/create', ['flow_id' => $flowId]);
        return $this->windowOpen($url);
    }

    /**
     * 查询我发起的流程
     * @param $response
     * @return mixed
     */
    public function workflowQuery($response, $own)
    {
        $params = [
            'limit' => $this->limit,
            'page' => 1,
            'platform' => 'mobile',
            'search' => [],
            'user_id' => $response['creator'],
            'user_info' => $own,
            'order_by' => [
                'transact_time' => 'desc'
            ]
        ];
        $data = make($this->flowService)->getMyRequestList($params)['list'];
        $relations = [
            'simpleTitle' => ['run_name'],
            'simpleDesc' => function ($flow) {
                return '最新步骤' . ($flow['latest_steps'] ?? '');
            },
            'url' => function ($flow) {
                return Route::navigate('/flow/view', ['run_id' => $flow['run_id'], 'flow_id' => $flow['flow_id']]);
            },
            'image' => $this->iconPath . '/flow.png',
            'isnew' => 1,
        ];
        $formatData =  $this->windowViewApproval($data, $relations);

        return $this->processFlowDataUnreadStatus($formatData, $data);
    }

    /**
     * 创建会议
     */
    public function createMeeting()
    {
        $url = Route::navigate('/meeting/add');
        return $this->windowOpen($url);
    }

    /**
     * 查询我的会议
     * @param $response
     */
    public function queryMeeting($response, $own)
    {
        $params = [
            'limit' => $this->limit,
            'page' => 1,
            'platform' => 'mobile',
            'search' => [],
            'meeting_apply_user' => $response['creator'],
            'order_by' => [
                'meeting_apply_id' => 'desc'
            ]
        ];
        //是否指定时间
        if (isset($response['time']) && !empty($response['time'])) {
            list($startTime, $endTime) = explode('_', $response['time']);
            $params['search']['meeting_begin_time'] = [[$startTime, $endTime], 'between'];
        }
        //是否指定会议状态
        if (isset($response['status']) && !empty($response['status'])) {
            $params['search']['meeting_status'] = [$response['status']];
        }
        if ($params['search']) {
            $params['search'] = json_encode($params['search']);
        }
        $status = [0 => '待审批', 1 => '审批中', 2 => '已批准', 3 => '已拒绝', 4 => '已开始', 5 => '已结束'];
        $data = make($this->meetingService)->listOwnMeeting($params, $own)['list'];
        $relations = [
            'simpleTitle' => function ($meet) use ($status) {
                $meetStaus = $status[$meet['meeting_status']] ?? '未知状态';
                return $meet['meeting_subject'] . '(' . $meetStaus . ')';
            },
            'simpleDesc' => function ($meet) {
                return $meet['meeting_begin_time'] . '至' . $meet['meeting_end_time'];
            },
            'url' => function ($meet) {
                return Route::navigate('/meet/detail', ['meeting_apply_id' => $meet['meeting_apply_id']]);
            },
            'image' => $this->iconPath . '/meeting.png',
            'isnew' => 0        // 会议无未读消息
        ];
        return $this->windowViewApproval($data, $relations);
    }

    /**
     * 查询我的日程
     * @param $response
     */
    public function querySchedule($response, $own)
    {
        $params = [
            'type' => 'my',
            'order_by' => [
                'calendar_begin' => 'desc'
            ]
        ];
        $answer = false;
        if (isset($response['time']) && !empty($response['time'])) {
            list($startTime, $endTime) = explode('_', $response['time']);
            $params['calendar_begin'] = $startTime;
            $params['calendar_end'] = $endTime;
        } else {
            //默认查询本月的日程
            $beginDate = date('Y-m-01', strtotime(date("Y-m-d")));
            $endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
            $params['calendar_begin'] = $beginDate . ' 00:00:00';
            $params['calendar_end'] = $endDate . ' 23:59:59';
            $answer = date('Y') . '年' . date('m') . '月的日程';
        }
        $data = make($this->calendarRecordService)->getinitList($params, $response['creator'])['list'];
        if (!$answer) {
            $beginDate = date('Y-m-d', strtotime($params['calendar_begin']));
            $endDate = date('Y-m-d', strtotime($params['calendar_end']));
            if ($beginDate == $endDate) {
                $answer = $beginDate . '的日程';
            } else {
                $answer = $beginDate . '至' . $endDate . '的日程';
            }
        }
        $relations = [
            'simpleTitle' => ['calendar_content'],
            'simpleDesc' => function ($calendar) {
                return $calendar['calendar_begin'] . '至' . $calendar['calendar_end'];
            },
            'url' => function ($calendar) {
                return Route::navigate('/calendar/detail', ['calendar_id' => $calendar['calendar_id']]);
            },
            'isnew' => 0,   // 日程未读消息不针对具体待办  针对关注的人
            'image' => $this->iconPath . '/schedule.png',
        ];
        return $this->windowViewApproval($data, $relations, $answer);
    }


    public function createSchedule($response)
    {
        if (!isset($response['creator']) || !$response['creator']) {
            return true;
        }
        $userId = $response['creator'];
        list($startTime, $endTime) = explode('_', $response['time']);
        $data = [
            'calendar_address' => $response['address'] ?? '',    //位置
            'calendar_content' => $response['content'] ?? '',    //内容
            'calendar_begin' => $startTime,                      //开始时间
            'calendar_end' => $endTime,                          //结束时间
            'calendar_level' => $response['level'],              //重要程度
            'calendar_remark' => $response['remark'] ?? '',      //备注
            'reminder_timing_h' => $response['hours'] ?? 0,      //提醒小时
            'reminder_timing_m' => $response['minute'] ?? 5,     //提箱分钟
            'repeat' => 0,                                       //是否重复
            'creator' => $userId,
            'handle_user' => [$userId],
            'allow_remind' => 1,
        ];
        $reminderTiming = intval($data['reminder_timing_h']) * 60 + intval($data['reminder_timing_m']);
        $reminderTime = date('Y-m-d H:i:s', strtotime($startTime) - $reminderTiming * 60);
        $data['reminder_time'] = $reminderTime;
        $data['reminder_timing'] = $reminderTiming;
        return make($this->calendarService)->addCalendar($data, $userId);
    }

    /**
     * 查询人员信息
     * @param $response
     */
    public function person($response)
    {
        $list['list'] = array();
        if (isset($response['searchTo']) && $response['searchTo']) {
            $userId = $response['searchTo'];
            $user = make($this->userService)->getUserAllData($userId)->toArray();
            if ($user) {
                $info = array();
                //用户名
                $info['user_name'] = $user['user_name'];
                //性别
                $info['sex'] = $user['user_has_one_info']['sex'] ?? '';
                //邮箱
                $info['email'] = $user['user_has_one_info']['email'] ?? '';
                //电话号码
                $info['phone'] = $user['user_has_one_info']['phone_number'] ?? '';
                //职位名
                $info['user_position_name'] = $user['user_position_name'] ?? '';
                //部门
                $info['dept'] = $user['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] ?? '';
                //状态
                $info['status'] = $user['user_has_one_system_info']['user_system_info_belongs_to_user_status']['status_name'] ?? '';
                //角色
                $info['role'] = $this->getUserHas($user, 'user_has_many_role', 'has_one_role', 'role_name');
                //上级
                $info['superior'] = $this->getUserHas($user, 'user_has_many_superior', 'superior_has_one_user', 'user_name');
                //下级
                $info['subordinate'] = $this->getUserHas($user, 'user_has_many_subordinate', 'subordinate_has_one_user', 'user_name');
                //小e需要用到的信息
                $item['other'] = [
                    'URL' => '',
                    'JOBTITLENAME' => $info['user_position_name'],//职位
                    'SUBCOMP' => $info['phone'],//这里先存放电话
                    'DEPT' => $info['dept'],//部门
                ];
                $item['simpleTitle'] = $info['user_name'];
                $item['id'] = Route::navigate('/chat', ['user_id' => $userId, 'content' => '']);
                $item['other']['URL'] = $this->getUserAvatar($userId, $response['api_token']);
                $list['list'][] = $item;
            }
        }
        return $list;
    }

    /**
     * 查询我的考勤记录
     * @param $response
     */
    public function getAttendanceRecord($response)
    {
        $return['list'] = array();
        if (!isset($response['creator']) || empty($response['creator'])) {
            return $return;
        }
        $userId = $response['creator'];
        $time = time();
        if (isset($response['time']) && !empty($response['time'])) {
            $time = strtotime($response['time']);
        }
        list($year, $month) = explode('-', date('Y-m', $time));
        $params = [
            'year' => intval($year),
            'month' => intval($month)
        ];
        $record = make($this->attendanceStatService)->oneAttendStat($params, $userId);
        //标题
        $item['name'] = $year . '年' . $month . '月' . '考勤记录';
        //考勤记录
        $detail = [
            ['name' => '出勤(次)', 'value' => $record['real_attend_days'],],
            ['name' => '迟到(次)', 'value' => $record['lag_count'],],
            ['name' => '早退(次)', 'value' => $record['leave_early_count'],],
            ['name' => '漏签(次)', 'value' => $record['no_sign_out'],],
            ['name' => '旷工(天)', 'value' => $record['absenteeism_days'],],
            ['name' => '请假(天)', 'value' => $record['leave_days'],],
            ['name' => '出差(天)', 'value' => $record['trip_days'],],
            ['name' => '外出(天)', 'value' => $record['out_days'],],
            ['name' => '加班(天)', 'value' => $record['overtime_days'],],
            ['name' => '外勤(次)', 'value' => $record['out_attend_total'],],
        ];
        $item['detail'] = $detail;
        $return['list'][] = $item;
        return $return;
    }

    /**
     * 考勤统计
     * @param $response
     * @param $own
     */
    public function moreAttendStat($response, $own)
    {
        $time = time();
        if (isset($response['time']) && !empty($response['time'])) {
            $time = strtotime($response['time']);
        }
        list($year, $month) = explode('-', date('Y-m', $time));
        $params = [
            'noPage' => true,
            'year' => $year,
            'month' => $month,
            'order_by' => [
                "department.dept_id" => "asc",
                "user.user_id" => "asc"
            ]
        ];
        $answer = $year . '年' . $month . '月的考勤统计';
        //查询某一个用户的考勤统计
        if (isset($response['userId'])) {
            $params['type'] = 'user';
            $params['user_id'] = json_encode($response['userId']);
            //dd($params);
            $record = make($this->attendanceStatService)->moreAttendStat($params, $own,false,false)['list'][0] ?? [];
            if (!$record) {
                return $this->windowAnswer('未找到相关人员的考勤统计');
            }
            $title = [
                '应出勤（天/小时）:' . $record['attend_days'] . '/' . $record['attend_hours'],
                '实际出勤（天/小时）:' . $record['real_attend_days'] . '/' . $record['real_attend_hours'],
                //'异常（天）:' . $record['abnomal_days'],
                '迟到（次）:' . $record['lag_count'],
                '早退（次）:' . $record['leave_early_count'],
                '漏签（次）:' . $record['no_sign_out'],
                '旷工（天）:' . $record['absenteeism_days'],
//                '请假（天）:' . $record['leave_days'],
//                '请假带薪（天）:' . $record['leave_money_days'],
//                '加班（天）:' . $record['overtime_days'],
//                '外出（天）:' . $record['out_days'],
//                '出差（天）:' . $record['trip_days'],
//                '校准（次）:' . $record['calibration_total'],
            ];
            $userName = $record['user_name'];
            $answer = $userName . $answer;
            return $this->windowViewTable($title, false, false, $answer);
        } else {
            if (isset($response['deptId'])) {
                $params['type'] = 'dept';
                $params['dept_id'] = json_encode($response['deptId']);
            }
            $records = make($this->attendanceStatService)->moreAttendStat($params, $own,false,false)['list'];
            //dd($records);
            //获取下部门名称从查询的结果中
            if (isset($response['deptId']) && isset($records[0]['dept_name'])) {
                $answer = $records[0]['dept_name'] . $answer;
            }
            $head = [
                'user_name' => '用户名',
                'dept_name' => '部门',
                'attend_days' => '应出勤',
                'real_attend_days' => '实际出勤'
            ];
            return $this->windowViewTable(false, $head, $records, $answer);
        }
    }

    /**
     * 获取我的排班
     * @param $response
     */
    public function scheduling($response, $own)
    {
        $list = array();
        $userId = $own['user_id'];
        $user = make($this->userService)->getUserAllData($userId)->toArray();
        $userInfo = [
            'imageUrl' => '',
            'username' => $user['user_name'],
            'subName' => $this->getUserHas($user, 'user_has_many_role', 'has_one_role', 'role_name'),//角色
            'jobtitle' => $user['user_position_name'] ?? '',//职位
            'deptName' => $user['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] ?? ''//部门
        ];
        $userInfo['imageUrl'] = $this->getUserAvatar($userId, $response['api_token']);
        if (isset($response['time']) && $response['time']) {
            list($starTime, $endTime) = explode('_', $response['time']);
            $year = date('Y', strtotime($starTime));
            $month = date('m', strtotime($starTime));
        } else {
            $year = date('Y');
            $month = date('m');
        }
        $scheduling = make($this->attendanceService)->getUserSchedulingDate($year, $month, $own['user_id']);
        $shiftIds = array_unique(array_column($scheduling, '0'));
        $shifts = [];
        foreach ($shiftIds as $shiftId) {
            if($shiftId==0){
                continue;
            }
            $shifts[$shiftId] = make($this->attendanceSettingService, false)->shiftDetail($shiftId)->toArray();
        }
        $lastDay = $this->getMonthLastDay($year, $month, true);
        for ($i = 1; $i <= $lastDay; $i++) {
            $day = $i < 10 ? '0' . $i : $i;
            $date = $year . '-' . $month . '-' . $day;
            $content = $scheduling[$date][1] ?? '<span style="color: #ccc!important;">休息</span>';
            //班次的详细信息
            if (isset($scheduling[$date][0]) && isset($shifts[$scheduling[$date][0]])) {
                $currenShift = $shifts[$scheduling[$date][0]];
                $signTimes = $currenShift['sign_time']->toArray();
                if ($signTimes) {
                    $time = '考勤时间段：';
                    foreach ($signTimes as $signTime) {
                        $time .= $signTime['sign_in_time'] . '~' . $signTime['sign_out_time'] . ' ';
                    }
                    $content .= '<p style="font-size: 12px!important;">' . $time . '</p>';
                }
                if (isset($currenShift['advance_sign_time'])) {
                    $content .= '<p style="font-size: 12px!important;">允许提前签到：' . $currenShift['advance_sign_time'] . '分钟</p>';
                }
            }
            $list[] = [
                'content' => $content,
                'workdate' => $date
            ];
        }
        return [
            'answer' => '以下是您' . $year . '年' . $month . '月' . '的排班',
            'data' => ['userInfo' => $userInfo, 'list' => $list]
        ];
    }

    /**
     * @param $response
     */
    public function queryBlog($response, $own)
    {
        $userId = $own['user_id'];
        if (isset($response['userId']) && !empty($response['userId'])) {
            $userId = $response['userId'];
        }
        $params = [
            'page' => 1,
            'byDate' => 1,
            'getDateData' => 'all',
            'search' => ['user_id' => $userId, 'firstLoad' => 1, 'plan_status' => ['', '!=']],
        ];
        if (!isset($response['time']) || empty($response['time'])) {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-01', strtotime($endDate));
        } else {
            list($starTime, $endTime) = explode('_', $response['time']);
            $startDate = date('Y-m-d', strtotime($starTime));
            $endDate = date('Y-m-d', strtotime($endTime));
            if (strtotime($endDate) > strtotime(date('Y-m-d'))) {
                $endDate = date('Y-m-d');
            }
        }
        $params['search']['diary_date'] = [[$startDate, $endDate], 'between'];
        $params['search'] = json_encode($params['search']);
        $user = make($this->userService)->getUserAllData($userId)->toArray();
        $userInfo = [
            'imageUrl' => '',
            'username' => $user['user_name'],
            'subName' => $this->getUserHas($user, 'user_has_many_role', 'has_one_role', 'role_name'),//角色
            'jobtitle' => $user['user_position_name'] ?? '',//职位
            'deptName' => $user['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] ?? ''//部门
        ];
        $userInfo['imageUrl'] = $this->getUserAvatar($userId, $response['api_token']);
        $list = array();
        $blogs = make($this->diaryService)->getDiaryList($params, $own);
        if (isset($blogs['result_error']) && $blogs['result_error'] == 'notFollow') {
            return ['code' => ['0x011001', 'xiaoe']];
        }
        if ($blogs) {
            foreach ($blogs as $blog) {
                $list[] = [
                    'content' => $this->parseBlogContent($blog),
                    'workdate' => $blog['date']
                ];
            }
        }
        return ['userInfo' => $userInfo, 'list' => $list];
    }

    /**
     * 发布微博
     * @param $response
     */
    public function createBlog($response, $own)
    {
        if (!isset($response['creator']) || empty($response['creator'])) {
            return [];
        }
        $content = '<p>' . $response['content'] . '</p>';
        $data = [
            'address' => $response['address'] ?? '',
            'diary_content' => $content,
            'diary_content_html' => $content,
            'plan_content' => $content,
            'date' => date('Y-m-d'),
            'diary_date' => date('Y-m-d'),
            'diary_label_date' => date('m-d'),
            'kind_id' => 1,
            'plan_scope_date_end' => date('Y-m-d'),
            'plan_scope_date_start' => date('Y-m-d'),
            'template_number' => 1,
            'submitType' => 'publish',
            'main_add' => 1
        ];
        return make($this->diaryService)->saveUserDiaryPlan($data, $own);
    }

    /**
     * 查询文档
     * @param $response
     * @param $own
     * @return mixed
     */
    public function document($response, $own)
    {
        $params = [
            'page' => 1,
            'limit' => 50,
            'order_by' => [
                'updated_at' => 'desc'
            ]
        ];
        if (isset($response['keyword']) && $response['keyword']) {
            $params['search']['subject'] = [$response['keyword'], 'like'];
            $params['search']['content'] = [$response['keyword'], 'like'];
            $params['search'] = json_encode($params['search']);
        }
        $documents = make($this->documentService)->listDocument($params, $own)['list'];
        $relations = [
            'simpleTitle' => ['subject'],
            'simpleDesc' => ['create_name'],
            'date' => function ($document) {
                return $this->getSimpleDate($document->created_at);
            },
            'url' => function ($document) {
                return Route::navigate('/document/detail', [
                    'document_id' => $document->document_id,
                ]);
            },
            'isnew' => 1,   // 先声明 后面覆盖
            'image' => $this->iconPath . '/document.png'
        ];
        $formatData =  $this->windowViewApproval($documents, $relations);

        return $this->processDocumentDataUnreadStatus($formatData, $documents);
    }

    /**
     * 打开应用
     * @param $response
     */
    public function openApp($response, $own)
    {
        return $this->windowAnswer('暂不支持此功能');
    }

    /**
     * 签到
     * @param $response
     * @param $own
     * @return array|bool|string
     */
    public function signIn($response, $own)
    {
        //平台，目前小e只支持app
        $platfrom = ['app' => 2];
        //考勤方式，1定位，2wifi
        $attendType = $response['attendType'] ?? 1;
        $userId = $own['user_id'];
        $before = $this->beforeSign($response, $userId);
        if (isset($before['code'])) {
            return $before;
        }
        $data = [
            'address' => $response['address'] ?? '',
            'lat' => $response['lat'] ?? '',
            'long' => $response['long'] ?? '',
            'attend_wifi_name' => $response['attend_wifi_name'] ?? '',
            'attend_wifi_mac' => $response['attend_wifi_mac'] ?? '',
            'sign_times' => 1,
            'sign_date' => date('Y-m-d'),
            'sign_in_time' => date('Y-m-d H:i:s'),
            'platform' => $platfrom[$response['platfrom']] ?? 2,
        ];
        $result = make($this->attendanceService, true, false)->signIn($data, $own);
        if ($result) {
            //考勤service返回错误码，处理下向用户返回更详细的错误提示
            if (isset($result['code'])) {
                $answer = trans($result['code'][1] . '.' . $result['code'][0]);
            } else {
                $answer = '您已签到成功，签到时间：';
                $answer .= '<p>' . date('Y-m-d H:i:s', strtotime($data['sign_in_time'])) . '</p>';
            }
            if ($attendType == 1) {
                $address = $data['address'];
            } elseif ($attendType == 2) {
                $address = 'wifi:' . $data['attend_wifi_name'];
            }
            return ['answer' => $answer, 'address' => $address];
        } else {
            return ['answer' => '签到失败，请重试！'];
        }
    }

    /**
     * 外勤签到
     * @param $response
     * @param $own
     * @return array|bool|string
     */
    public function outSignIn($response, $own)
    {
        $data = [
            'address' => $response['address'] ?? '',
            'lat' => $response['lat'] ?? '',
            'long' => $response['long'] ?? '',
            'sign_date' => date('Y-m-d'),
            'sign_time' => date('Y-m-d H:i:s'),
            'platform' => 'mobile_app',
        ];
        $result = make($this->attendanceService)->externalAttend($data, $own['user_id']);
        if ($result) {
            $answer = '外勤打卡成功，打卡时间：';
            $answer .= '<p>' . date('Y-m-d H:i:s', strtotime($data['sign_time'])) . '</p>';
            return ['answer' => $answer, 'address' => $data['address']];
        } else {
            return ['answer' => '外勤打卡失败，请重试！'];
        }
    }

    /**
     * 签退
     * @param $response
     * @param $own
     * @return array|bool|string
     */
    public function signOut($response, $own)
    {
        //平台，目前小e只支持app
        $platfrom = ['app' => 2];
        //考勤方式，1定位，2wifi
        $attendType = $response['attendType'] ?? 1;
        $userId = $own['user_id'];
        $before = $this->beforeSign($response, $userId);
        if (isset($before['code'])) {
            return $before;
        }
        $data = [
            'address' => $response['address'] ?? '',
            'lat' => $response['lat'] ?? '',
            'long' => $response['long'] ?? '',
            'attend_wifi_name' => $response['attend_wifi_name'] ?? '',
            'attend_wifi_mac' => $response['attend_wifi_mac'] ?? '',
            'sign_times' => 1,
            'sign_date' => date('Y-m-d'),
            'sign_out_time' => date('Y-m-d H:i:s'),
            'platform' => $platfrom[$response['platfrom']] ?? 2,
        ];
        $result = make($this->attendanceService, true, false)->signOut($data, $own);
        if ($result) {
            //考勤service返回错误码，处理下向用户返回更详细的错误提示
            if (isset($result['code'])) {
                $answer = trans($result['code'][1] . '.' . $result['code'][0]);
            } else {
                $answer = '您已签退成功，签退时间：';
                $answer .= '<p>' . date('Y-m-d H:i:s', strtotime($data['sign_out_time'])) . '</p>';
            }
            if ($attendType == 1) {
                $address = $data['address'];
            } elseif ($attendType == 2) {
                $address = 'wifi:' . $data['attend_wifi_name'];
            }
            return ['answer' => $answer, 'address' => $address];
        } else {
            return ['answer' => '签退失败，请重试！'];
        }
    }

    /**
     * 签到签退等操作之前的验证和处理
     * @param string $judge
     * @param $userId
     */
    private function beforeSign($response, $userId, $scene = 'in')
    {
        //考勤方式，1定位，2wifi
        $attendType = $response['attendType'] ?? 1;
        if ($attendType == 1) {
            //无定位信息
            if (!$this->hasAll($response, ['address', 'lat', 'long'])) {
                return ['code' => ['0x044025', 'attendance']];
            }
        }
        if ($attendType == 2) {
            //无wifi信息
            if (!$this->hasAll($response, ['attend_wifi_name', 'attend_wifi_mac'])) {
                return ['code' => ['0x044037', 'attendance']];
            }
        }
        if (!$this->directSign($userId)) {
            return ['code' => ['0x044050', 'attendance']];
        }
        return true;
    }

    /**
     * 给某人发消息
     * @param $response
     * @return array
     */
    public function sendMessage($response)
    {
        $sendTo = $response['sendTo'];
        $content = $response['content'] ?? '';
        return $this->windowOpen(Route::navigate('/chat', [
            'user_id' => $sendTo,
            'content' => $content
        ]));
    }

    /**
     * 给某人打电话
     * @param $response
     * @return array
     */
    public function phone($response)
    {
        $userId = $response['to'];
        $user = make($this->userService)->getUserAllData($userId)->toArray();
        if (isset($user['user_has_one_info']['phone_number']) && $user['user_has_one_info']['phone_number']) {
            $phone = $user['user_has_one_info']['phone_number'];
            return $this->windowCall($phone);
        } else {
            return $this->windowAnswer('未找到相关电话');
        }
    }

    /**
     * 同步数据字典
     * @return mixed
     */
    public function syncDict()
    {
        make('App\EofficeApp\XiaoE\Services\SystemService')->syncDictData();
        return $this->windowAnswer('同步成功');
    }

    /**
     * 查询我的工资
     * @param $response
     * @param $own
     */
    public function salary($response, $own)
    {
        $data = make($this->salaryReportFormService)->getMySalaryListByMobile($own['user_id'], [])['list'] ?? [];
        $relations = [
            'simpleTitle' => ['salary_to_salary_report.title'],
            'simpleDesc' => '',
            'url' => function ($salary) {
                return Route::navigate('/salary/detail', ['report_id' => $salary['report_id']]);
            },
            'isnew' => 0,
            'image' => $this->iconPath . '/salary.png',
        ];
        return $this->windowViewApproval($data->toArray(), $relations);
    }

    /**
     * 查找客户
     */
    public function customer($response, $own)
    {
        $params = [
            'limit' => $this->limit,
            'page' => 1,
            'search' => [],
        ];
        $customers = make($this->customerService)->lists($params, $own)['list'] ?? [];
        $relations = [
            'simpleTitle' => ['customer_name'],
            'simpleDesc' => function ($customer) {
                $description = [
                    '客户经理：' . $this->defaultValue($customer->customer_manager),
                    '客户来源：' . $this->defaultValue($customer->customer_from),
                    '客户状态：' . $this->defaultValue($customer->customer_status),
                    '客户行业：' . $this->defaultValue($customer->customer_industry),
                    '客户电话：' . $this->defaultValue($customer->phone_number),
                    '上次联系时间：' . $this->defaultValue(date('Y-m-d', $customer->last_distribute_time)),
                    '创建时间：' . $this->defaultValue(date('Y-m-d', strtotime($customer->created_at))),
                    '创建人：' . $this->defaultValue($customer->customer_creator),
                ];
                return implode(' ', $description);
            },
            'url' => function ($customer) {
                return Route::navigate('/customer/detail', ['customer_id' => $customer->customer_id]);
            },
            'isnew' => 0,
            'image' => $this->iconPath . '/customer.png',
        ];
        return $this->windowViewApproval($customers, $relations);
    }

    /**
     * 创建客户,跳转到客户创建页面
     * @param $response
     * @param $own
     */
    public function createCustomer($response, $own)
    {
        return $this->windowOpen(Route::navigate('/customer/add'));
    }

    /**
     * 查询项目
     * @param $response
     * @param $own
     */
    public function project($response, $own)
    {
        $data = [
            'platform' => 'mobile',
            'noPage' => true
        ];
        //状态
        if (isset($response['status'])) {
            $status = $response['status'];
            //未结束
            if ($status == 6) {
                $data['showFinalPorjectFlag'] = 'unFinal';
            } else {
                $data['search']['manager_state'] = [$status];
            }
        }
        //时间
        if (isset($response['time']) && !empty($response['time'])) {
            list($startTime, $endTime) = explode('_', $response['time']);
            $data['search']['creat_time'] = [[$startTime, $endTime], 'between'];
        }
        if (isset($data['search']) && $data['search']) {
            $data['search'] = json_encode($data['search']);
        }
        $projects = make($this->projectService)->mobileProjectIndex($own['user_id'], $data)['list'];
        $relations = [
            'simpleTitle' => function ($project) {
                return $project['manager_name'] . '[' . $project['manager_type_name'] . ']' . '[' . $project['plan'] . '%]';
            },
            'simpleDesc' => function ($project) {
                $description = [
                    '[' . $this->getPorjectStatus($project['manager_state']) . ']',
                    '任务：' . $project['task_count'],
                    '问题：' . $project['question_count'],
                    '文档：' . $project['doc_count'],
                ];
                return implode(' ', $description);
            },
            'url' => function ($project) {
                return Route::navigate('/project/detail', ['manager_id' => $project['manager_id']]);
            },
            'isnew' => 0,
            'image' => $this->iconPath . '/project.png',
            'date' => function ($item) {
                return $this->getSimpleDate($item['creat_time']);
            },
        ];
        return $this->windowViewApproval($projects, $relations);
    }

    /**
     * 获取项目状态
     * @param $status
     */
    private function getPorjectStatus($statusId)
    {
        switch ($statusId) {
            case 1:
                $status = '立项中';
                break;
            case 2:
                $status = '审批中';
                break;
            case 3:
                $status = '已退回';
                break;
            case 4:
                $status = '进行中';
                break;
            case 5:
                $status = '已结束';
                break;
            default:
                $status = '未知状态';
        }
        return $status;
    }

    /**
     * 创建项目
     */
    public function createProject()
    {
        return $this->windowOpen(Route::navigate('/project/add'));
    }

    /**
     * 发送内部邮件
     */
    public function sendInMail($response, $own)
    {
        $data = [
            'to_id' => $response['sendTo'],
            'subject' => $response['subject'],
            'content' => $response['content'],
            'send_flag' => 1
        ];
        return make($this->emailService)->newEmail($data, $own);
    }

    /**
     * 播报新闻
     * @param $response
     * @param $own
     */
    public function news($response, $own)
    {
        $params = [
            'platform' => 'mobile',
            'page' => 1,
            'limit' => $this->limit
        ];
        $news = make($this->newsService)->getList($params, $own['user_id'])['list'] ?? [];

        $relations = [
            'simpleTitle' => ['title'],
            'simpleDesc' => ['news_desc'],
            'url' => function ($item) {
                return Route::navigate('/news', ['news_id' => $item['news_id']]);
            },
            'isnew' => '1',
            'image' => $this->iconPath . '/news.png',
            'date' => function ($item) {
                return $this->getSimpleDate($item['publish_time']);
            },
        ];
        $formData =  $this->windowViewApproval($news, $relations);

        return $this->processNewsDataUnreadStatus($formData, $news);
    }

    /**
     * 我的合同
     * @param $response
     * @param $own
     */
    public function contract($response, $own)
    {
        $search = [
            'status' => [[0, 1], 'in'],
            'user_id' => [$own['user_id']],
            'recycle_status' => [0],
            'type' => 'mine'
        ];
        $params = [
            'platform' => 'mobile',
            'page' => 1,
            'limit' => $this->limit,
            'search' => json_encode($search)
        ];
        $tableKey = 'contract_t';
        $data = make($this->formModelingService)->getCustomDataLists($params, $tableKey)['list'] ?? [];
        $relations = [
            'simpleTitle' => function ($contract) {
                return $contract->title . '[' . $contract->number . ']';
            },
            'simpleDesc' => function ($contract) {
                $desc = [
                    '合同对象：' . $contract->target_name,
                    '合同金额：' . $contract->money
                ];
                return implode(' ', $desc);
            },
            'url' => function ($contract) {
                return Route::navigate('/contract', ['id' => $contract->id]);
            },
            'isnew' => 0,
            'image' => $this->iconPath . '/document.png',
            'date' => function ($contract) {
                return $this->getSimpleDate($contract->created_at);
            },
        ];
        return $this->windowViewApproval($data, $relations);
    }
}
