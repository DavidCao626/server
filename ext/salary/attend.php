<?php

class Attendance
{
    private $year;
    private $month;
    private $userId;
    //生产合成部
    private $deptId = 2;
    //返回哪种统计
    private $type;
    private $data = [
        //周末
        'weekend' => 0,
        //白班
        'day' => 0,
        //夜班
        'night' => 0
    ];

    private $userRepository;
    private $attendanceRecordsRepository;

    public function __construct()
    {
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->attendanceRecordsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRecordsRepository';

        if (!$request = Utils::bindRequest('year', 'month', 'user_id', 'type')) {
            return $this->response();
        }
        list($this->year, $this->month, $this->userId, $this->type) = $request;
    }

    public function run()
    {
        $userDeptId = $this->getUserDeptId();
        if (!$userDeptId) {
            return $this->response();
        }
        list($startDate, $endDate) = Utils::getMonthStartEnd($this->year, $this->month);
        $parsms = ['sign_date' => [[$startDate, $endDate], 'between'], 'user_id' => [$this->userId]];
        $records = app($this->attendanceRecordsRepository)->getRecords($parsms)->toArray();
        if (!$records) {
            return $this->response();
        }
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
            return $this->response();
        }
        $ruleType = $userDeptId == $this->deptId ? 'dept' : 'common';
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
                        $this->data[$type]++;
                        break;
                    };
                }
            }
        }
        return $this->response();
    }

    public function response()
    {
        echo $this->data[$this->type] ?? 0;
        exit();
    }


    private function getUserDeptId()
    {
        return app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($this->userId)['dept_id'] ?? null;
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

require_once __DIR__ . '/../../bootstrap/app.php';
$attendance = new Attendance();
$attendance->run();
?>
