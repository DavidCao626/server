<?php
require __DIR__ . '/../../bootstrap/app.php';

use App\EofficeApp\Attendance\Services\AttendanceBaseService;

class Attendance extends AttendanceBaseService
{
    public function __construct(){
        parent::__construct();
    }
    public function boot()
    {
        // 用于以后拓展
        $action = static::request('action', '');
        if($action === '') {
            $this->statVacation();
        }
    }
    public function statVacation()
    {
        $year = static::request('year', date('Y'));
        $month = intval(static::request('month', date('m')));
        $userId = static::request('user_id', '');
        $type = static::request('type', 'normal');
        if(!$userId) {
            return 0;
        }
        $startDate = $this->formatDateMonth($year, $month) . '-01';
        $endDate = $this->getMonthEndDate($year, $month);
        
        // 获取排班
        $overtimeRecords = app($this->attendanceOvertimeStatRepository)->getMoreUserAttendOvertimeRecordsByDate($startDate, $endDate, [$userId]);
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
                    'weekend' => $weekHours,
                    'normal' => $normalHours
                ];
            }
        }
        $oneVacation = $toVacation[$userId] ?? [];
        
        echo $oneVacation[$type] ?? 0;
        exit;
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
(new Attendance())->boot();