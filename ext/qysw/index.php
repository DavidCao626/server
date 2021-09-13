<?php
require __DIR__ . '/../../bootstrap/app.php';

use App\EofficeApp\Attendance\Services\AttendanceBaseService;

class Attendance extends AttendanceBaseService
{
    private $attendanceStatService;
    private $orderBy = [
        'department.dept_id' => 'asc',
        'user.user_id' => 'asc',
    ];
    //生产合成部
    private $deptId = 2;
    public function __construct(){
        parent::__construct();
        $this->attendanceStatService = app('App\EofficeApp\Attendance\Services\AttendanceStatService');
    }
    public function boot()
    {
        $action = static::request('action', '');
        list($vacation, $count) = $this->getLeaveVacation();
        if($action === 'stat') {
            $this->stat($vacation);
        } else {
            include_once './view.php';
        }
    }
    public function stat($vacation)
    {
        $year = static::request('year', date('Y'));
        $month = intval(static::request('month', date('m')));
        $statMonth = $this->formatDateMonth($year, $month);
        $startDate = $statMonth . '-01';
        $endDate = $this->getMonthEndDate($year, $month);
        $userParam = [
            'noPage' => true,
            'order_by' => $this->orderBy
        ];
        $users = app($this->userRepository)->getSimpleUserList($userParam);
        
        $userIds = array_column($users, 'user_id');
        $deptMap = $this->arrayMapWithKeys($users, 'user_id', 'dept_id');
        // 获取排班
        $schedulingDates = $this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, $userIds);
        $leaveHours = app($this->attendanceLeaveDiffStatRepository)->getMoreUserAttendLeaveHoursStatByDate($startDate, $endDate, $userIds);
        $overtimeRecords = app($this->attendanceOvertimeStatRepository)->getMoreUserAttendOvertimeRecordsByDate($startDate, $endDate, $userIds);
        $recordsStats = $this->attendanceStatService->recordsStatMapWithUserIds($schedulingDates, $startDate, $endDate, $userIds, false);
        $toVacation = [];
        if(count($overtimeRecords) > 0) {
            foreach ($overtimeRecords as $userId => $items) {
                $weekHours = $normalHours = 0;
                foreach ($items as $item) {
                    if($item->to == 1) {
                        $week = date('w',strtotime($item->date));
                        if(($week==6) || ($week == 0)){
                            $weekHours += $item->overtime_hours * $item->ratio;
                        } else {
                            $normalHours += $item->overtime_hours * $item->ratio;
                        }
                    }
                }
                $toVacation[$userId] = [
                    'week' => $weekHours,
                    'normal' => $normalHours
                ];
            }
        }
        
        $stat = [];
        //list($vacation, $count) = $this->getLeaveVacation();
        $otherStat = $this->statOther($startDate, $endDate, $userIds, $deptMap);
        foreach ($users as $key => $user) {
            $userId = $user['user_id'];
            $oneStat = $recordsStats[$userId] ?? [];
            $oneToVacation = $toVacation[$userId] ?? [];
            $userLeaveHours = $leaveHours[$userId] ?? [];
            $oneOtherStat = $otherStat[$userId] ?? [];
            list($overtimeNormalHours, $overtimeRestHours, $overtimeHolidayHours) = $this->splitOvertime($startDate, $endDate, $userId, $overtimeRecords[$userId] ?? [], array_column($schedulingDates[$userId], 'scheduling_date'));
            $row = [
                ['value' => $key + 1],
                ['value' => $user['dept_name']],
                ['value' => $user['user_name']],
                ['value' => $user['user_position']],
                ['value' => $oneStat['attend_days'] ?? 0],
                ['value' => $oneStat['real_attend_days'] ?? 0],
                ['value' => $oneStat['lag_hours'] ?? 0],
                ['value' => $oneStat['leave_early_hours'] ?? 0],
                ['value' => $oneStat['absenteeism_days'] ?? 0],
                ['value' => $oneStat['no_sign_out'] ?? 0],
                ['value' => $oneToVacation['normal'] ?? 0],
                ['value' => $oneToVacation['week'] ?? 0],
                ['value' => ''], // 假期抵冲
                ['value' => $overtimeNormalHours],
                ['value' => $overtimeRestHours],
                ['value' => $overtimeHolidayHours],
                ['value' => $oneOtherStat['weekend'] ?? 0],
                ['value' => $oneOtherStat['day'] ?? 0],
                ['value' => $oneOtherStat['night'] ?? 0],
            ];
            foreach ($vacation as $key => $name) {
                $row[] = ['value' => round($userLeaveHours[$key] ?? 0,$this->hourPrecision)];
            }
            $stat[] = $row;
        }
        echo json_encode($stat);exit;
    }
    public function getVacation() 
    {
        list($vacation, $count) = $this->getLeaveVacation();
        
        echo json_encode($vacation);exit;
    }
    private function statOther($startDate, $endDate, $userIds, $deptmap)
    {
        $parsms = ['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$userIds]];
        $records = app($this->attendanceRecordsRepository)->getRecords($parsms)->toArray();
        if (count($records) == 0) {
            return [];
        }
        $recordsGroup = $this->arrayGroupWithKeys($records, 'user_id', true);
        $otherStat = [];
        foreach ($recordsGroup as $userId => $records) {
            $otherStat[$userId] = $this->statOtherOne($records, $deptmap[$userId] ?? 0);
        }
        return $otherStat;
    }
    private function statOtherOne($records, $deptId)
    {
        $dateGroup = array();
        foreach ($records as $record) {
            $signDate = Utils::has($record, 'sign_date');
            $signIn = Utils::has($record, 'sign_in_time');
            $signOut = Utils::has($record, 'sign_out_time');
            if ($signDate && $signIn && $signOut) {
                $dateGroup[$signDate] = [$signIn, $signOut];
            }
        }
        if (!$dateGroup) {
            return [];
        }
        $ruleType = $deptId == $this->deptId ? 'dept' : 'common';
        $result = [
            //周末
            'weekend' => 0,
            //白班
            'day' => 0,
            //夜班
            'night' => 0
        ];
        foreach ($dateGroup as $date => $sign) {
            $rules = $this->getRules($date, $ruleType);
            list($signIn, $signOut) = $sign;
            $isWeekEnd = Utils::isWeekEnd($date);
            foreach ($rules as $type => $rule) {
                foreach ($rule as $item) {
                    $needWeekend = $item['weekend'];//是否是周末才有的
                    $hour = $item['hour'];//达到补贴的小时
                    $time = $item['time']; //补贴的时间段
                    $secords = Utils::getIntersetTime($signIn, $signOut, $time[0], $time[1]);
                    if ($secords < $hour * 3600) {
                        continue;
                    }
                    if (!$needWeekend || ($needWeekend && $isWeekEnd)) {
                        $result[$type]++;
                        break;
                    }
                }
            }
        }
        return $result;
    }
    private function getRules($date, $type)
    {
        $nextDate = Utils::getNextDate($date);
        $rules = [
            'common' => [
                'day' => [
                    ['weekend' => false, 'hour' => 3, 'time' => [$date . ' 16:00:00', $nextDate . ' 00:00:00']],
                    ['weekend' => false, 'hour' => 2, 'time' => [$date . ' 17:00:00', $date . ' 19:00:00']]
                ],
                'night' => [
                    ['weekend' => false, 'hour' => 3, 'time' => [$date . ' 00:00:00', $date . ' 08:30:00']]
                ],
                'weekend' => [
                    ['weekend' => true, 'hour' => 3, 'time' => [$date . ' 09:30:00', $date . ' 12:30:00']]
                ]
            ],
            'dept' => [
                'day' => [
                    ['weekend' => false, 'hour' => 12, 'time' => [$date . ' 08:30:00', $date . ' 20:30:00']]
                ],
                'weekend' => [
                    ['weekend' => true, 'hour' => 3, 'time' => [$date . ' 09:30:00', $date . ' 12:30:00']]
                ]
            ]
        ];
        return $rules[$type];
    }
    private function splitOvertime($startDate, $endDate, $userId, $overtimeRecords, $schedulingDates)
    {
        $restDate = array_column($this->schedulingMapWithUserIdsByDateScope($startDate, $endDate, [$userId],'holiday')[$userId], 'scheduling_date');
        if(count($overtimeRecords) === 0) {
            return [0,0,0];
        }
        $normalHours = $restHours = $holidayHours = 0;
        foreach ($overtimeRecords as $item) {
            $date = $this->formatDate($item->year, $item->month, $item->day);

            if(in_array($date, $schedulingDates)) {
                $normalHours += $item->overtime_hours;
            } else if(in_array($date, $restDate)) {
                $holidayHours += $item->overtime_hours;
            } else {
                $restHours += $item->overtime_hours;
            }
        }

        return [$normalHours, $restHours, $holidayHours];
    }
    public static function request($key, $default = '')
    {
        return (isset($_GET[$key]) && !empty($_GET[$key])) ? $_GET[$key] : ((isset($_POST[$key]) && !empty($_GET[$key])) ? $_POST[$key]: $default);
    }
    public function formatDateMonth($year, $month, $split = '-')
    {
        return $year . $split . ($month < 10 ? '0' . $month : $month);
    }
    public function getMonthEndDate($year, $month)
    {
        $statMonth = $this->formatDateMonth($year, $month);

        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        return $statMonth . '-' . $days;
    }
}
class Utils
{
    public static function bindRequest(...$keys)
    {
        $array = array();
        foreach ($keys as $key) {
            if (!$v = static::has($_REQUEST, $key)) {
                return false;
            } else {
                $array[] = $v;
            }
        }
        return $array;
    }

    public static function has($array, $key)
    {
        return (isset($array[$key]) && !empty($array[$key])) ? $array[$key] : false;
    }

    public static function getMonthStartEnd($year, $month)
    {
        $date = $year . '-' . $month . '-01';
        $startDate = date('Y-m-01', strtotime($date));
        $endDate = date('Y-m-t', strtotime($date));
        return [$startDate, $endDate];
    }

    public static function getNextDate($date = false)
    {
        if ($date) {
            return date('Y-m-d', strtotime("+1 day", strtotime($date)));
        }
        return date('Y-m-d', strtotime("+1 day"));
    }

    public static function isWeekEnd($date)
    {
        if ((date('w', strtotime($date)) == 6) || (date('w', strtotime($date)) == 0)) {
            return true;
        } else {
            return false;
        }
    }

    public static function getIntersetTime($start1, $end1, $start2, $end2)
    {
        $intersetTime = 0;
        if ($end2 > $start1 && $start2 < $end1) {
            $beginTime = static::getBigData($start1, $start2);
            $endTime = static::getSmallData($end2, $end1);
            $intersetTime = strtotime($endTime) - strtotime($beginTime);
        }
        return $intersetTime;
    }

    protected static function getSmallData($one, $two)
    {
        return $one < $two ? $one : $two;
    }

    protected static function getBigData($one, $two)
    {
        return $one < $two ? $two : $one;
    }
}
(new Attendance())->boot();