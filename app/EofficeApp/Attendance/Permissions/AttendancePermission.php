<?php
namespace App\EofficeApp\Attendance\Permissions;
class AttendancePermission
{
    private $attendanceRecordsRepository;
    private $attendanceRestSchemeRepository;
    public $rules = [
        'returnedCalibration' => 'handleCalibrationPermission', // 退回校准
        'approveCalibration' => 'handleCalibrationPermission' // 批准校准
    ];
    public function __construct()
    {
        $this->attendanceRecordsRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRecordsRepository';
        $this->attendanceRestSchemeRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceRestSchemeRepository';
    }
    /**
     * 申请校准
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return boolean
     */
    public function applyCalibration($own, $data, $urlData)
    {
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord(['record_id' => [$urlData['recordId']]]);
        if($record && $record->user_id == $own['user_id']) {
            return true;
        }
        return false;
    }
    /**
     * 公共的校准权限验证
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return boolean
     */
    public function handleCalibrationPermission($own, $data, $urlData)
    {
        $record = app($this->attendanceRecordsRepository)->getOneAttendRecord(['record_id' => [$urlData['recordId']]]);
        if($record && $record->calibration_status == 1) {
            return true;
        }
        return false;
    }

    /**
     * 排班设置权限验证
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return boolean
     */
    public function setSchedulingDate($own, $data, $urlData)
    {
        $restIds = array();
        foreach ($data as $v) {
            if ($v['type'] == 'rest' && $v['rest_id'] != 0 && !in_array($v['rest_id'], $restIds)) {
                $restIds[] = $v['rest_id'];
            }
        }
        if (!$restIds) {
            return true;
        }
        $scheme = app($this->attendanceRestSchemeRepository)->getOneScheme(['status' => ['1']], true)->toArray();
        $usedRestIds = isset($scheme['rest']) ? array_column($scheme['rest'], 'rest_id') : [];
        $check = array_diff($restIds, $usedRestIds) ? false : true;
        return $check;
    }
}
