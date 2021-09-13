<?php

namespace App\EofficeApp\XiaoE\Services;


/**
 * 小e数据验证
 * @author shiqi
 */
class CheckService extends BaseService
{
    private $validate = [
        'leaveDays' => ['creator', 'time'],//请假验证依赖的字段
        'businessTrip' => ['creator', 'time'],//出差验证依赖的字段
        'overtime' => ['creator', 'time'],//加班验证依赖的字段
    ];
    private $attendanceService;

    public function __construct()
    {
        $this->attendanceService = 'App\EofficeApp\Attendance\Services\AttendanceService';
        $this->attendanceOutSendService = 'App\EofficeApp\Attendance\Services\AttendanceOutSendService';
    }

    /**
     * 验证请假和获取请假天数
     * @param $data
     * @return mixed
     */
    public function leaveDays($data)
    {
        $data['params'] = json_decode($data['params'], true);
        //错误信息为空时，小e需要返回{}，而不是[]
        $return['paramsErrMsg'] = (object)array();
        if (!$this->checkParams($data['params'], $this->validate[__FUNCTION__])) {
            $return['params'] = $data['params'];
            return $return;
        }
        $userId = $data['params']['creator'];
        list($startTime, $endTime) = explode('_', $data['params']['time']);
        $leaveDays = app($this->attendanceOutSendService)->getLeaveOrOutDays($startTime, $endTime, $userId)['days'] ?? 0;
        $leaveDays = round($leaveDays, 2);
        $data['params']['days'] = $leaveDays;
        $return['params'] = $data['params'];
        return $return;
    }

    /**
     * 验证出差和获取出差天数
     * @param $data
     */
    public function businessTrip($data)
    {
        $data['params'] = json_decode($data['params'], true);
        //错误信息为空时，小e需要返回{}，而不是[]
        $return['paramsErrMsg'] = (object)array();
        if (!$this->checkParams($data['params'], $this->validate[__FUNCTION__])) {
            $return['params'] = $data['params'];
            return $return;
        }
        $userId = $data['params']['creator'];
        list($startTime, $endTime) = explode('_', $data['params']['time']);
        $leaveTime = app($this->attendanceOutSendService)->getLeaveOrOutDays($startTime, $endTime, $userId);
        $data['params']['days'] = round($leaveTime['days'], 2);
        $data['params']['hours'] = round($leaveTime['hours'], 2);
        $return['params'] = $data['params'];
        return $return;
    }

    /**
     * 验证加班和加班天数
     * @param $data
     */
    public function overtime($data)
    {
        $data['params'] = json_decode($data['params'], true);
        //错误信息为空时，小e需要返回{}，而不是[]
        $return['paramsErrMsg'] = (object)array();
        if (!$this->checkParams($data['params'], $this->validate[__FUNCTION__])) {
            $return['params'] = $data['params'];
            return $return;
        }
        $userId = $data['params']['creator'];
        list($startTime, $endTime) = explode('_', $data['params']['time']);
        $leaveTime = app($this->attendanceOutSendService)->getOvertimeDays($startTime, $endTime, $userId);
        $data['params']['days'] = round($leaveTime['days'], 2);
        $data['params']['hours'] = round($leaveTime['hours'], 2);
        $return['params'] = $data['params'];
        return $return;
    }

    /**
     * 判断验证依赖字段是否全部存在
     * @param $param
     * @param $checkField
     * @return bool
     */
    private function checkParams($param, $checkField)
    {
        foreach ($checkField as $field) {
            if (!isset($param[$field])) {
                return false;
            }
        }
        return true;
    }
}
