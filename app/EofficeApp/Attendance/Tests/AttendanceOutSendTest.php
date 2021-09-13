<?php
namespace App\EofficeApp\Attendance\Tests;
use App\Tests\UnitTest;
use App\EofficeApp\Attendance\Services\AttendanceOutSendService;
/**
 * Description of AttendanceOutSendTest
 *
 * @author lizhijun
 */
class AttendanceOutSendTest extends UnitTest 
{
    public $callMethods = [
        'leaveTest',
//        'overtimeTest',
//        'getOvertimeDays',
//        'getLeaveOrOutDays'
    ];
    public function __construct(AttendanceOutSendService $attendanceOutSendService) 
    {
        parent::__construct();
        $this->attendanceOutSendService = $attendanceOutSendService;
    }
    /**
     * 请假外发测试
     */
    public function leaveTest()
    {
        $data = [
            'user_id' => 'admin',
            'vacation_id' => 2,
            'leave_start_time' => '2021-06-07 23:00:00',
            'leave_end_time' => '2021-06-07 22:00:00',
            'run_id' => 1,
            'flow_id' => 2,
            'run_name' => '测试'
        ];
        $result = $this->attendanceOutSendService->leave($data);
        $this->responseJson($result);
    }
    public function overtimeTest()
    {
        $data = [
            'user_id' => 'admin',
            'overtime_start_time' => '2020-12-06 09:00:00',
            'overtime_end_time' => '2020-12-06 18:00:00',
            'run_id' => 1,
            'flow_id' => 2,
            'run_name' => '测试'
        ];
        $result = $this->attendanceOutSendService->overtime($data, true);
        $this->responseJson($result);
    }
    /**
     * 获取加班天数
     */
    public function getOvertimeDays()
    {
        $result = $this->attendanceOutSendService->getOvertimeDays('2020-08-17', '2020-08-18', 'admin');
        $this->responseJson($result);
    }
    /**
     * 获取请假或者外出天数
     */
    public function getLeaveOrOutDays()
    {
        $result = $this->attendanceOutSendService->getLeaveOrOutDays('2020-08-17', '2020-08-18', 'admin', 1); 
        $this->responseJson($result);
    }
}
