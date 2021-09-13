<?php
namespace App\EofficeApp\Attendance\Traits;

/**
 * Description of AttendanceParamsTrait
 *
 * @author lizhijun
 */
trait AttendanceTransTrait 
{
   
    private $trans;
    public function trans($key = null)
    {
        if (!$this->trans) {
            $this->trans = [
                'dept' => trans("attendance.department"),
                'user' => trans("attendance.user_name"),
                'must_attend' => trans("attendance.Should_attendance"),
                'real_attend' => trans("attendance.actual_attendance"),
                'no_attend' => trans("attendance.no_attendance"),
                'attend_ratio' => trans("attendance.attendance_ratio"),
                'lag' => trans("attendance.late"),
                'seriously_lag' => trans('attendance.seriously_lag'),
                'leave_early' => trans("attendance.early"),
                'absenteeism' => trans("attendance.absent"),
                'no_sign_out' => trans("attendance.nout"),
                'next' => trans("attendance.next"),
                'leave' => trans("attendance.leave"),
                'trip' => trans("attendance.business_trip"),
                'out' => trans("attendance.out"),
                'overtime' => trans("attendance.overtime"),
                'zhcn_day' => trans("attendance.zhcn_day"),
                'min_hour' => trans("attendance.hour_for_length"),
                'all' => trans("attendance.total"),
                'personage' => trans("attendance.personage"),
                'average' => trans("attendance.average"),
                'working_day' => trans("attendance.working_day"),
                'rest_day' => trans("attendance.rest_day"),
                'holiday' => trans("attendance.holiday"),
                'no_offset_all' => trans('attendance.incomplete_offset'),
                'rest' => trans('attendance.rest'),
                'period' => trans('attendance.period'),
                'normal' => trans('attendance.normal')
            ];
        }
        
        return $key ? ($this->trans[$key] ?? '') : $this->trans;
    }
}
