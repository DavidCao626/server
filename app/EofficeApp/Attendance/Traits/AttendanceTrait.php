<?php

namespace App\EofficeApp\Attendance\Traits;

trait AttendanceTrait
{
    private $orderBy;
    public $mobilePlatform = [2, 3, 4, 5, 6, 11];
    //获取考勤平台，考勤平台（1pc，2app，3微信公众号，4企业号,5企业微信,6移动钉钉,7PC钉钉,8考勤机,11手机政务钉钉,12 PC政务钉钉，14服务端自动打卡）
    protected $mobilePlatformMap = [
        'ding_talk' => 6,
        'ding_talk_pc' => 7,
        'official_account' => 3,
        'enterprise_account' => 4,
        'enterprise_wechat' => 5,
        'mobile_app' => 2,
        'other' => 0,
        'dgwork' => 11,
    ];
    private $weeks;
    private $attendResult;
    protected $outSendTimeKeys = [
        'leave' => ['leave_start_time', 'leave_end_time'],
        'out' => ['out_start_time', 'out_end_time'],
        'trip' => ['trip_start_date', 'trip_end_date'],
        'overtime' => ['overtime_start_time', 'overtime_end_time'],
        'backLeave' => ['back_leave_start_time', 'back_leave_end_time'],
        'repair' => ['repair_date']
    ];
    public function getSignTypes($key = null)
    {
        static $signTypes;
        if (!$signTypes) {
            $signTypes = [
                trans('attendance.report_location'),
                trans('attendance.sign_in'),
                trans('attendance.sign_out'),
                '',
                trans('attendance.punch'),
                trans('attendance.legwork') . trans('attendance.sign_in'),
                trans('attendance.legwork') . trans('attendance.sign_out')
            ];
        }
        if (is_null($key)) {
            return $signTypes;
        }
        return $signTypes[$key] ?? '';
    }
    public function mapToGroups($items, callable $callback)
    {
        $group = [];
        if (!empty($items) && $items->isNotEmpty()) {
            $group = $items->mapToGroups($callback);
        }
        return $group;
    }
    public function isMobilePlatform($platformId)
    {
        return in_array($platformId, $this->mobilePlatform);
    }
    public function getMobilePlatformId($platform)
    {
        if (is_string($platform)) {
            $platform = $this->mobilePlatformMap[$platform];
        }
        return $platform;
    }
    public function isAbsenteeismLag($shift, $lagTime)
    {
        if (!$shift->absenteeism_lag || !$shift->absenteeism_lag_time) {
            return false;
        }
        
        return $lagTime && $lagTime > $shift->absenteeism_lag_time * 60;
    }
    public function isSeriouslyLag($shift, $lagTime)
    {
        if (!$shift->seriously_lag || !$shift->seriously_lag_time){
            return false;
        }
        return $lagTime && $lagTime > $shift->seriously_lag_time * 60;
    }
    public function calcHours($time)
    {
        return $this->roundHours($this->calcRealHours($time));
    }
    public function calcRealHours($time) 
    {
        return intval($time) / 3600;
    }
    public function calcDay($realTime, $attendTime)
    {
        return $this->roundDays(intval($realTime) / $attendTime);
    }
    public function calcDayByHours($realTime, $attendTime)
    {
        return $this->roundDays($realTime / $attendTime);
    }
    public function roundDays($days)
    {
        return round($days, $this->dayPrecision);
    }
    public function roundHours($hours)
    {
        return round($hours, $this->hourPrecision);
    }
    public function sumTotal(&$total, $value = 1)
    {
        $total += is_string($value) ? intval($value) : $value;
    }

    /**
     * 向下取整
     * @author yangxingqiang
     * @param $n
     * @param $base
     * @return float
     */
    public function floor($n, $base)
    {
        return floor($n / $base) * $base;
    }
    public function transPlatform($key = null)
    {
        static $platform;
        if (!$platform) {
            $platform = [
                0 => trans("attendance.Unknown_platform"),
                1 => trans("attendance.computer"),
                2 => trans("attendance.mobile_app"),
                3 => trans("attendance.WeChat_Official_Account"),
                4 => trans("attendance.Enterprise"),
                5 => trans("attendance.Enterprise_WeChat"),
                6 => trans("attendance.mobile_dingtalk"),
                7 => trans("attendance.pc_dingtalk"),
                8 => trans("attendance.attendance_machine"),
                9 => trans("attendance.excel_import"),
                10 => trans("attendance.dingtalk_system"),
                11 => trans("attendance.mobile_dgwork"),
                12 => trans("attendance.pc_dgwork"),
                14 => trans("attendance.server_auto_sign"),
            ];
        }
        if (is_null($key)) {
            return $platform;
        }
        if (key_exists($key, $platform)) {
            return $platform[$key];
        }
        return '';
    }
    public function getAttendResultString($keys)
    {
        if (empty($keys)) {
            return '';
        }
        if (!$this->attendResult) {
            $this->attendResult = [
                '-' => '',
                'ok' => '√',
                'rest' => trans('attendance.rest'),
                'lag' => trans('attendance.late'),
                'seriously_lag' => trans('attendance.seriously_lag'),
                'early' => trans('attendance.early'),
                'no_sign_out' => trans('attendance.nout'),
                'calibration' => trans('attendance.calibration'),
                'abs' => trans('attendance.absent'),
                'overtime' => trans('attendance.overtime'),
                'leave' => trans('attendance.leave'),
                'trip' => trans('attendance.business_trip'),
                'out' => trans('attendance.out'),
                'repair'=>trans('attendance.repair'),
            ];
        }
        $result = '';
        foreach ($keys as $key) {
            if (isset($this->attendResult[$key])) {
                $result .= $this->attendResult[$key] . ' ';
            }
        }
        return rtrim($result);
    }

    /**
     * 转驼峰
     *
     * @param string $str
     * @param string $delimter
     *
     * @return string
     */
    public function toCamelCase($str, $delimter = '-')
    {
        $array = explode($delimter, $str);

        $name = array_reduce($array, function ($carry, $item) {
            return $carry . ucfirst($item);
        });

        return lcfirst($name);
    }

    public function getWeekByDate($date)
    {
        if (!$this->weeks) {
            $this->weeks = [
                trans('diary.sunday'),
                trans('diary.monday'),
                trans('diary.tuesday'),
                trans('diary.wednesday'),
                trans('diary.thursday'),
                trans('diary.friday'),
                trans('diary.saturday'),
            ];
        }
        $weekKey = date("w", strtotime($date));
        return [$weekKey, $this->weeks[$weekKey]];
    }

    

    /**
     * 过滤数组中对应的字段值并赋默认值
     *
     * @param string $key
     * @param array $data
     * @param type $default
     *
     * @return int|array|string...
     */
    public function defaultValue($key, $data, $default = '')
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * 获取统计一个月的结束时间
     *
     * @param string $year
     * @param string $month
     *
     * @return string
     */
    public function getMonthEndDate($year, $month)
    {
        $statMonth = $this->formatDateMonth($year, $month);

        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        return $statMonth . '-' . $days;
    }

    /**
     * 获取两段时间的时间差
     *
     * @param string $start
     * @param string $end
     * @param string $return
     *
     * @return float
     */
    public function timeDiff($start, $end, $return = 'second')
    {
        if (sizeof(explode(':', $start)) == 2) {
            $start = $start . ':00';
        }

        if (sizeof(explode(':', $end)) == 2) {
            $end = $end . ':00';
        }

        $endDate = $end < $start ? '2000-01-02 ' . $end : '2000-01-01 ' . $end;

        $startDate = '2000-01-01 ' . $start;

        $diffSeconds = $this->datetimeDiff($startDate, $endDate);

        if ($return == 'second') {
            return $diffSeconds;
        } else if ($return == 'minute') {
            return floor($diffSeconds / 60);
        } else if ($return == 'hour') {
            return floor($diffSeconds / 3600);
        } else if ($return == 'days') {
            return floor($diffSeconds / 86400);
        }
    }
    public function datetimeDiff($start, $end)
    {
        return strtotime($end) - strtotime($start);
    }
    /**
     * 过滤列表查询参数
     *
     * @param type $param
     *
     * return $param;
     */
    public function filterParam($param)
    {
        $param['fields'] = $this->defaultValue('fields', $param, ['*']);

        $param['limit'] = $this->defaultValue('limit', $param, config('eoffice.pagesize'));

        $param['page'] = $this->defaultValue('page', $param, 0);

        $param['order_by'] = $this->defaultValue('order_by', $param, $this->orderBy);

        return $param;
    }

    public function timeStrToTime($time, $diffSeconds = 0, $toDate = false)
    {
        if (sizeof(explode(':', $time)) == 2) {
            $time = $time . ':00';
        }

        $timeDate = '2000-01-01 ' . $time;

        $resultSeconds = strtotime($timeDate) - $diffSeconds;

        if ($toDate) {
            return date('H:i:s', $resultSeconds);
        }

        return strtotime($timeDate) - $diffSeconds;
    }

    public function combineDatetime($date, $time, $split = ' ')
    {
        return $date . $split . $time;
    }

    public function arrayToStr($array, $glue = ',')
    {
        if (is_string($array)) {
            return false;
        }

        if (empty($array)) {
            return '';
        }

        return implode($glue, $array);
    }

    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    }

    public function getUserRoleIds($userId)
    {
        static $roleId;

        if (isset($roleId[$userId])) {
            return $roleId[$userId];
        }

        $userRoles = app('App\EofficeApp\Role\Repositories\UserRoleRepository')->getUserRole(['user_id' => $userId]);

        if (count($userRoles) > 0) {
            $roleId[$userId] = array_column($userRoles, 'role_id');

            return $roleId[$userId];
        }

        return [];
    }

    public function getUserDeptId($userId)
    {
        static $dept;

        if (isset($dept[$userId])) {
            return $dept[$userId];
        }

        $userInfo = app('App\EofficeApp\User\Repositories\UserSystemInfoRepository')->getOneUserSystemInfo(['user_id' => [$userId]], ['dept_id']);

        if ($userInfo) {
            $dept[$userId] = $userInfo->dept_id;

            return $dept[$userId];
        }

        return 0;
    }

    public function getAllDeptName()
    {
        $depts = app('App\EofficeApp\System\Department\Repositories\DepartmentRepository')->getAllDepartment();

        $map = [];

        foreach ($depts as $dept) {
            $map[$dept->dept_id] = $dept->dept_name;
        }

        return $map;
    }

    public function getAllUserName()
    {
        $users = app('App\EofficeApp\User\Repositories\UserRepository')->getAllSimpleUsers(['user_id', 'user_name']);

        $map = [];

        foreach ($users as $user) {
            $map[$user->user_id] = $user->user_name;
        }

        return $map;
    }

    public function getAllUserDeptMap()
    {
        $users = app('App\EofficeApp\User\Repositories\UserSystemInfoRepository')->getInfoByWhere([], ['user_id', 'dept_id']);

        $map = [];

        foreach ($users as $user) {
            $map[$user->user_id] = $user->dept_id;
        }

        return $map;
    }

    public function randomColor()
    {
        $colors = ['#F44336', '#9C27B0', '#3F51B5', '#2196F3', '#00BCD4', '#009688', '#4CAF50', '#CDDC39', '#FF9800', '#795548', '#607D8B'];

        return $colors[array_rand($colors, 1)];
    }

    /**
     *
     * @param type $date
     * @param type $delimiter
     * @return type
     */
    public function getPrvDate($date = null)
    {
        if ($date) {
            return date('Y-m-d', strtotime("-1 day", strtotime($date)));
        }
        return date('Y-m-d', strtotime("-1 day"));
    }

    public function getNextDate($date = false)
    {
        if ($date) {
            return date('Y-m-d', strtotime("+1 day", strtotime($date)));
        }
        return date('Y-m-d', strtotime("+1 day"));
    }

    public function getMonth($year, $month, $type = 'curr')
    {
        if ($type == 'prv') {
            if ($month == 1) {
                $month = 12;
                $year = $year - 1;
            } else {
                $month = $month - 1;
            }
        } else if ($type == 'next') {
            if ($month == 12) {
                $month = 1;
                $year = $year + 1;
            } else {
                $month = $month + 1;
            }
        }

        return [$year, $month, $year + '-' + ($month < 10 ? '0' + $month : $month)];
    }

    public function getVacationId($vacationName)
    {
        return app('App\EofficeApp\Vacation\Repositories\VacationRepository')->getVacationId($vacationName);
    }

    public function getVacationInfo($vacationId)
    {
        static $array;
        if (isset($array[$vacationId])) {
            return $array[$vacationId];
        }
        $vacationInfo = app('App\EofficeApp\Vacation\Repositories\VacationRepository')->getDetail($vacationId);

        return $array[$vacationId] = $vacationInfo;
    }

    public function formatDateMonth($year, $month, $split = '-')
    {
        return $year . $split . ($month < 10 ? '0' . $month : $month);
    }

    public function getCustomerAttr($customerId, $attrName = 'customer_name')
    {
        static $array;

        if (isset($array[$customerId . $attrName])) {
            return $array[$customerId . $attrName];
        }

        $customer = app('App\EofficeApp\Customer\Repositories\CustomerRepository')->show($customerId);
        if ($customer) {
            return $array[$customerId . $attrName] = $customer[$attrName];
        }

        return $array[$customerId . $attrName] = '';
    }

    public function formatDate($year, $month, $day, $split = '-')
    {
        return $year . $split . ($month < 10 ? '0' . $month : $month) . $split . ($day < 10 ? '0' . $day : $day);
    }

    /**
     * 填充键值对数组，值不存在填充空值
     * @param type $keys
     * @param type $source
     * @return type
     */
    public function fillKeysArray($keys, $source)
    {
        $data = [];

        foreach ($keys as $key) {
            $data[$key] = isset($source[$key]) ? $source[$key] : [];
        }

        return $data;
    }

    public function getFullTime($time, $isDate = true)
    {
        if ($isDate) {
            return strlen($time) == 16 ? $time . ':00' : $time;
        }

        return strlen($time) == 5 ? $time . ':00' : $time;
    }

    public function getOutSendDataLists($param)
    {
        $param = $this->filterParam($param);

        $query = $this->entity->select($param['fields']);

        if (isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }
        if (isset($param['orSearch']) && !empty($param['orSearch'])) {
            $orSearch = $param['orSearch'];
            $query->where(function ($query) use ($orSearch) {
                $i = 0;
                foreach ($orSearch as $key => $item) {
                    if ($i == 0) {
                        $query->whereBetween($key, $item[0]);
                    } else {
                        $query->orWhereBetween($key, $item[0]);
                    }
                    $i++;
                }
            });
        }
        $query->orders($param['order_by']);
        if ($param['page'] == 0) {
            return $query->get();
        }

        return $query->parsePage($param['page'], $param['limit'])->get();
    }

    public function getOutStats($year, $month, $userIds)
    {
        return $this->entity->where('year', $year)->where('month', $month)->whereIn('user_id', $userIds)->get();
    }

    public function getOutStatsByDate($startDate, $endDate, $userIds)
    {
        return $this->entity->SeparateDate($startDate, $endDate)->whereIn('user_id', $userIds)->get();
    }

    public function datesArrayColumn($dates, $columnIndex, $split = '-')
    {
        $columns = [];

        if (!empty($dates)) {
            foreach ($dates as $date) {
                $YMD = explode($split, $date);
                $columns[] = intval($YMD[$columnIndex]);
            }
        }

        return $columns;
    }

    public function insertArrayFromEndBeforeIndex($array, $index, $insert)
    {
        if (!isset($array[$index])) {
            return $array;
        }
        $index = count($array) - 1 - $index;
        $returnArray = [];
        for ($i = 0; $i < count($array); $i++) {
            if ($i == $index) {
                $returnArray[] = $insert;
            }
            $returnArray[] = $array[$i];
        }
        return $returnArray;
    }

    public function insertArrayFromStartAfterIndex($array, $index, $insert)
    {
        if (!isset($array[$index])) {
            return $array;
        }
        $returnArray = [];
        for ($i = 0; $i < count($array); $i++) {
            $returnArray[] = $array[$i];
            if ($i == $index) {
                $returnArray[] = $insert;
            }
        }
        return $returnArray;
    }

    public function multInsertArrayFromStartAfterIndex($array, $index, $insert)
    {
        if (!isset($array[$index])) {
            return $array;
        }
        $returnArray = [];
        for ($i = 0; $i < count($array); $i++) {
            $returnArray[] = $array[$i];
            if ($i == $index) {
                foreach ($insert as $arr) {
                    $returnArray[] = $arr;
                }
            }
        }
        return $returnArray;
    }

    public function getMonthByDate($startDate, $endDate)
    {
        $startDate = date('Y-m', strtotime($startDate)) . '-01';
        $endDate = date('Y-m', strtotime($endDate)) . '-01';
        $end = date('Ym', strtotime($endDate));
        $range = [];
        $i = 0;
        do {
            $yearMonth = date('Ym', strtotime($startDate . ' + ' . $i . ' month'));
            $range[] = [
                'year' => date('Y', strtotime($startDate . ' + ' . $i . ' month')),
                'month' => date('m', strtotime($startDate . ' + ' . $i . ' month'))
            ];
            $i++;
        } while ($yearMonth < $end);
        return $range;
    }

    public function isInDate($date, $startDate, $endDate)
    {
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $date = strtotime($date);
        if ($date >= $startDate && $date <= $endDate) {
            return true;
        }
        return false;
    }

    /**
     * 获取指定日期段内每一天的日期
     * @param  Date $startdate 开始日期
     * @param  Date $enddate 结束日期
     * @return Array
     */
    function getDateFromRange($startDate, $endDate)
    {

        $stimestamp = strtotime($startDate);
        $etimestamp = strtotime($endDate);

        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;

        // 保存每天日期
        $date = array();

        for ($i = 0; $i < $days; $i++) {
            $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
        }

        return $date;
    }

    /**
     * 数组转键索引数组
     * @param array $data
     * @param string $key
     * @param boolean $isArray
     *
     * @return array
     */
    protected function arrayGroupWithKeys($data, $key = 'user_id', $isArray = false)
    {
        $map = [];

        if (count($data) > 0) {
            foreach ($data as $item) {
                if ($isArray) {
                    $map[$item[$key]][] = $item;
                } else {
                    $map[$item->$key][] = $item;
                }
            }
        }

        return $map;
    }

    public function getDateByType($type)
    {
        //自定义开始结束日期
        if (is_array($type)) {
            return $type;
        }
        //上个月
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        //上一年
        $lastYearStart = date('Y-01-01', strtotime('-1 year'));
        $lastYearEnd = date('Y-12-31', strtotime('-1 year'));
        //今天
        $today = date('Y-m-d');
        //本周
        $thisWeekStart = date('Y-m-d', strtotime("this week Monday", time()));
        $thisWeekEnd = date('Y-m-d', strtotime(date('Y-m-d', strtotime("this week Sunday", time()))) + 24 * 3600 - 1);
        //$thisWeekEnd = date('Y-m-d', time());
        //本月
        $thisMonthStart = date('Y-m-01', strtotime(date('Y-m-d')));
        $thisMonthEnd = date('Y-m-t', strtotime(date('Y-m-d')));
        //$thisMonthEnd = date('Y-m-d', time());
        //本季度
        $season = ceil(date('n') / 3); //获取月份的季度
        $thisSeasonStart = date('Y-m-01', mktime(0, 0, 0, ($season - 1) * 3 + 1, 1, date('Y')));
        $thisSeasonEnd = date('Y-m-t', mktime(0, 0, 0, $season * 3, 1, date('Y')));
        //$thisSeasonEnd = date('Y-m-d', time());
        //本年
        $thisYearStart = date('Y-01-01');
        $thisYearEnd = date('Y-12-31');
        //$thisYearEnd = date('Y-m-d', time());
        $typeMap = [
            -2 => [$lastYearStart, $lastYearEnd],//上一年
            -1 => [$lastMonthStart, $lastMonthEnd],//上个月
            1 => [$today, $today],//今天
            2 => [$thisWeekStart, $thisWeekEnd],//本周
            3 => [$thisMonthStart, $thisMonthEnd],//本月
            4 => [$thisSeasonStart, $thisSeasonEnd],//本季度
            5 => [$thisYearStart, $thisYearEnd],//本年
        ];
        if (key_exists($type, $typeMap)) {
            return $typeMap[$type];
        }
        return $typeMap[1];
    }
    /**
     * 判断是否比某个时间更晚一点
     */
    public function isEarlierThanTime($time, $compareTime)
    {
        if (strtotime($time) < strtotime($compareTime)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据某条记录判断出勤时间是否为0
     */
    public function attendTimeIsZero($record)
    {
        $zero = false;
        list($signInNormal, $signOutNormal) = $this->getSignNormalDatetime($record->sign_date, $record->sign_in_normal, $record->sign_out_normal);
        if ($record->sign_out_time && $record->sign_in_time) {
            //如果签退时间是在排班签到时间之前则出勤时间为0
            if ($this->isEarlierThanTime($record->sign_out_time, $signInNormal)) {
                $zero = true;
            }
            //如果签到不是在下班前签到的则出勤时间为0
            if (!$this->isEarlierThanTime($record->sign_in_time, $signOutNormal)) {
                $zero = true;
            }
        }
        return $zero;
    }

    /**
     * 从目标时间中拼接完成的日期时间
     */
    public function makeFullDatetime($datetime, $date = false)
    {
        if (strlen($datetime) > 10) {
            return date('Y-m-d H:i:s', strtotime($datetime));
        }
        if ($datetime && $date) {
            return $this->combineDatetime($date, $datetime);
        }
        return $datetime;
    }

    /**
     * 判断数组中是否存在这些元素且不为空
     * @param $array
     * @param $keys
     * @return bool
     */
    protected function hasAll($array, $keys)
    {
        if (!$array) {
            return false;
        }
        foreach ($keys as $key) {
            if (!isset($array[$key]) || !$array[$key]) {
                return false;
            }
        }
        return true;
    }

    /**
     * 格式化时间，默认格式：Y-m-d H:i:s
     * @param $time
     * @param string $format
     * @return false|string
     */
    protected function format($time, $format = 'Y-m-d H:i:s')
    {
        return date($format, strtotime($time));
    }
    public function getStatMonthStartEnd($year, $month)
    {
        if ($this->isCurrentMonth($year, $month)) {
            $dateM = $this->formatDateMonth($year, $month);
            $startDate = $dateM . '-01';
            $endDate = $dateM . '-' . date('d');
            return [$startDate, $endDate];
        } else {
            return $this->getMonthStartEnd($year, $month);
        }
    }
    public function isCurrentMonth($year, $month)
    {
        $dateM = $this->formatDateMonth($year, $month);
        
        return $dateM == date('Y-m');
    }
    public function getMonthStartEnd($year, $month)
    {
        $date = $year . '-' . $month . '-01';
        $startDate = date('Y-m-01', strtotime($date));
        $endDate = date('Y-m-t', strtotime($date));
        return [$startDate, $endDate];
    }
}
