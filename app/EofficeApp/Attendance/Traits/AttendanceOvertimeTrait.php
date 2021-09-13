<?php
namespace App\EofficeApp\Attendance\Traits;

/**
 * Description of AttendanceParamsTrait
 *
 * @author lizhijun
 */
trait AttendanceOvertimeTrait 
{
   /**
     * 添加加班日志
     * @param $log
     * @return bool
     */
    public function addOvertimeLog($log)
    {
        $detail = $log['detail'];
        unset($log['detail']);
        $res = app($this->attendanceOvertimeLogRepository)->insertData($log);
        if ($res && $detail) {
            $logId = $res->log_id;
            $detail = array_map(function ($row) use ($logId) {
                $row['times'] = json_encode($row['times']);
                $row['log_id'] = $logId;
                $row['created_at'] = date('Y-m-d H:i:s');
                $row['updated_at'] = date('Y-m-d H:i:s');
                return $row;
            }, $detail);
            return app($this->attendanceOvertimeTimeLogRepository)->insertMultipleData($detail);
        }
        return true;
    }
    public function getStaticOvertimeConfig($userId, $key = null)
    {
        static $configs = [];
        if (!isset($configs[$userId])) {
            $config = app('App\EofficeApp\Attendance\Services\AttendanceSettingService')->getOvertimeConfig($userId);
            $configs[$userId] = $config;
        } else {
            $config = $configs[$userId];
        }
        if ($key) {
            return $config->{$key};
        }
        return $config;
    }
    public function getOvertimeBeginTime($fullSignOutNormal, $config)
    {
        //计算加班的开始时间
        return date('Y-m-d H:i:s', strtotime($fullSignOutNormal) + $config->work_after_time * 60);
    }
    public function getOvertimeEffectTimes($date, $startTime, $endTime, $config, $dateType)
    {
        if ($dateType === 'holiday') {
            $isDiff = $config->holiday_diff;
            $diffTime = $config->holiday_diff_time;
        } else {
            $isDiff = $config->rest_diff;
            $diffTime = $config->rest_diff_time;
        }
        $restTimes = [];
        if ($isDiff) {
            $diffTimes = $diffTime ? json_decode($diffTime, true) : [];
            if (!empty($diffTimes)) {
                foreach ($diffTimes as $item) {
                    $begin = $this->combineDatetime($date, $this->getFullTime($item['begin'], false));
                    $end = $this->combineDatetime($date, $this->getFullTime($item['end'], false));
                    $restTimes[] = [$begin , $end];
                }
            }
        }
        return $this->getSortDatetimeDiff($restTimes, $startTime, $endTime);
    }
}
