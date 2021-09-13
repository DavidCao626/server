<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


use App\EofficeApp\Attendance\Services\AttendanceStatService;
use App\EofficeApp\Performance\Services\PerformanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Cache;

class CalculateField extends SalaryField
{
    private $object;

    private $method;
    // 动态展示假期类型时，储存假期类型的id/type
    private $fieldSourceVacationId;
    private $fieldSourceVacationType;

    private $performanceService = PerformanceService::class;

    private $attendanceStatService = AttendanceStatService::class;

    /**
     * [getValue description]
     * @param  [type] $userInfo [传入的$userInfo是个数组：user_id 是代码运行的原始id，是传入的；transform_user_id是转换后的用户id，用于取绩效、考勤]
     * @param  [type] $reportId [description]
     * @return [type]           [description]
     */
    public function getValue($userInfo, $reportId)
    {
        if (Cache::has('salary_report_info_'.$reportId)) {
            $report = Cache::get('salary_report_info_'.$reportId);
        } else {
            $report = DB::table('salary_report')->where('report_id', $reportId)->first();
            // 把 $report 缓存 1 分钟
            Cache::put('salary_report_info_'.$reportId, $report, 1*60);
        }

        $params = [
            'year' => $report && $report->year ? $report->year : Carbon::now()->subMonthNoOverflow()->year,
            'month' => $report && $report->month ? $report->month : Carbon::now()->subMonthNoOverflow()->month,
        ];
        // 此类中，使用转换后的用户id，取考勤、绩效
        $userId = $userInfo['transform_user_id'] ?? '';
        if($userId) {
            return $this->{$this->method}($userId, $params);
        } else {
            return 0;
        }
    }

    public function getDependenceIds()
    {
        return '';
    }

    protected function parseConfig()
    {
        $fieldSource = $this->config['field_source'];
        // $fieldSource 是 1_vacation_days 时
        // 特殊处理，拼接上考勤模块的动态假期类型，进而“支持请假明细项”(DT202010140003)
        $fieldSourceExplode = explode("_", $fieldSource);
        if(count($fieldSourceExplode) == 3 && $fieldSourceExplode[1] == 'vacation') {
            $this->object = "App\EofficeApp\Salary\Services\SalaryField\CalculateField";
            $this->method = "getVacationMethod";
            $this->fieldSourceVacationId = $fieldSourceExplode[0];
            $this->fieldSourceVacationType = $fieldSourceExplode[2];
        } else {
            if (Cache::has('salary_calculate_data_'.$fieldSource)) {
                $calculateInfo = Cache::get('salary_calculate_data_'.$fieldSource);
                $calculateInfo = json_decode($calculateInfo);
            } else {
                $calculateInfo = DB::table('calculate_data')->find($fieldSource);
                // 把 $calculateInfo 缓存 1 分钟
                Cache::put('salary_calculate_data_'.$fieldSource, json_encode($calculateInfo), 1*60);
            }
            $this->object = $calculateInfo->type_object;
            $this->method = $calculateInfo->type_method;
        }
    }

    public function getLastMonthPerformData($userId, $params)
    {
        return app($this->performanceService)->getLastMonthPerformData($userId, $params);
    }

    /**
     * 获取考勤统计内容
     * @param $userId
     * @param $params
     * @param $key
     * @return int|mixed
     */
    private function getOneAttendStat($userId, $params, $key)
    {
        $cacheKey = "salary:attend:" . $params['year'] . ':'. $params['month'] . ':' . $userId;
        $cache = Redis::hget($cacheKey, $key);
        if($cache !== null){
            return $cache;
        }

        /** @var AttendanceStatService $attendanceStatService */
        $attendanceStatService = app($this->attendanceStatService);
        $all = $attendanceStatService->oneAttendStatOnlySalaryOvertime($params, $userId);
        // 返回的请假信息，在个数组里面，要处理一下才能哈希储存
        $all['leave_items'] = isset($all['leave_items']) ? json_encode($all['leave_items']) : json_encode([]);

        Redis::hMset($cacheKey, $all);
        Redis::expire($cacheKey, 30);
        return $all[$key] ?? 0;
    }

    /**
     * 应出勤天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getShouldAttendDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'attend_days');
    }

    /**
     * 应出勤小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getShouldAttendHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'attend_hours');
    }

    /**
     * 实际出勤天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getActualAttendDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'real_attend_days');
    }

    /**
     * 实际出勤小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getActualAttendHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'real_attend_hours');
    }

    /**
     * 校准次数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getAdjustNum($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'calibration_count');
    }

    /**
     * 校准天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getAdjustDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'calibration_days');
    }

    /**
     * 校准小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getAdjustHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'calibration_hours');
    }

    /**
     * 迟到次数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getLateNum($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'lag_count');
    }

    /**
     * 迟到天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getLateDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'lag_days');
    }

    /**
     * 迟到小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getLateHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'lag_hours');
    }

    /**
     * 早退次数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getEarlyNum($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'leave_early_count');
    }

    /**
     * 早退天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getEarlyDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'leave_early_days');
    }

    /**
     * 早退小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getEarlyHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'leave_early_hours');
    }

    /**
     * 旷工天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getAbsenteeismDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'absenteeism_days');
    }

    /**
     * 旷工小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getAbsenteeismHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'absenteeism_hours');
    }

    /**
     * 漏签次数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getMissNum($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'no_sign_out');
    }

    /**
     * 外勤次数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getFieldNum($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'out_attend_total');
    }

    /**
     * 外出天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getOutDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'out_days');
    }

    /**
     * 外出小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getOutHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'out_hours');
    }

    /**
     * 请假天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getLeaveDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'leave_days');
    }

    /**
     * 请假小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getLeaveHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'leave_hours');
    }

    /**
     * 加班天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getOvertimeDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'overtime_days');
    }

    /**
     * 加班小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getOvertimeHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'overtime_hours');
    }

    /**
     * 出差天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getTravelDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'trip_days');
    }

    /**
     * 出差小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getTravelHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'trip_hours');
    }

    /**
     * 工作日加班天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getNormalOvertimeDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'normal_overtime_days');
    }

    /**
     * 工作日加班小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getNormalOvertimeHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'normal_overtime_hours');
    }

    /**
     * 周末加班天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getRestOvertimeDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'rest_overtime_days');
    }

    /**
     * 周末加班小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getRestOvertimeHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'rest_overtime_hours');
    }

    /**
     * 节假日加班天数
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getHolidayOvertimeDays($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'holiday_overtime_days');
    }

    /**
     * 节假日加班小时
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getHolidayOvertimeHours($userId, $params)
    {
        return $this->getOneAttendStat($userId, $params, 'holiday_overtime_hours');
    }

    /**
     * 所有假期类型下，所有天数/小时，解析入口
     * @param $userId
     * @param $params
     * @return int|mixed
     */
    public function getVacationMethod($userId, $params)
    {
        /*
        返回的请假信息 leave_items ，在个数组里面，被转成json之后，哈希缓存的
        原型:
        [leave_items] => Array (
            [0] => Array
                (
                    [vacation_id] => 1
                    [vacation_name] => 年假
                    [days] => 0
                    [hours] => 0
                )
            [1] => Array
                (
                    [vacation_id] => 2
                    [vacation_name] => 带薪事假
                    [days] => 0
                    [hours] => 0
                )
        )*/
        // 动态展示假期类型时，储存假期类型的id $fieldSourceVacationId
        // 动态展示假期类型时，储存假期类型的type $fieldSourceVacationType
        $data = $this->getOneAttendStat($userId, $params, 'leave_items');
        $data = json_decode($data, true);
        foreach ($data as $key => $value) {
            if($value['vacation_id'] == $this->fieldSourceVacationId) {
                return $value[$this->fieldSourceVacationType] ?? 0;
            }
        }
        return 0;
    }


}
