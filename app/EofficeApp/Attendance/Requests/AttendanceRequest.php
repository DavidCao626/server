<?php
namespace App\EofficeApp\Attendance\Requests;

use App\EofficeApp\Base\Request;
/**
 * 考勤模块请求验证
 *
 * @author  李志军
 *
 * @since  2015-11-10 创建
 */
class AttendanceRequest extends Request
{
    protected $rules = [
		'addShift' => [
                'shift_type' => 'required|integer',
                'sign_time' => 'required',
                'shift_name' => 'required'
            ],
            'editShift' => [
                'shift_type' => 'required|integer',
                'sign_time' => 'required',
                'shift_name' => 'required'
            ],
            'addScheduling' => [
                'scheduling_name' =>  'required'
            ],
            'editScheduling' => [
                'scheduling_name' =>  'required'
            ],
            'addMobilePoints' => [
                'point_name' => 'required',
                'point_address' => 'required',
                'point_latitude' => 'required',
                'point_longitude' => 'required'
            ],
            'editMobilePoints' => [
                'point_name' => 'required',
                'point_address' => 'required',
                'point_latitude' => 'required',
                'point_longitude' => 'required'
            ],
            'externalAttend' => [
                'long' => 'required',
                'lat' => 'required',
                'address' => 'required',
                'platform' => 'required'
            ],
            'applyCalibration' => [
                'calibration_reason' => 'required'
            ],
            'returnedCalibration' => [
                'calibration_reason' => 'required'
            ],
            'approveCalibration' => [
                'calibration_reason' => 'required'
            ],
            'getLeaveOrOutDays' => [
                'start_time' => 'required',
                'end_time' => 'required',
            ],
            'attachmentReplace' => [
                'source_attachment_id' => 'required',
                'dest_attachment_id' => 'required',
            ],
            'savePurview' => [
                'purview_type' => 'required',
                'levels_purview_mark' => 'required',
                'department_purview_mark' => 'required',
                'group_id' => 'required',
            ]
	];

    public function rules($request)
    {
        $function = explode("@", $request->route()[1]['uses'])[1];

        return $this->getRouteValidateRule($this->rules, $function);
    }
}
