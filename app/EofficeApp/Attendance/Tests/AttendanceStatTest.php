<?php
namespace App\EofficeApp\Attendance\Tests;
use App\Tests\UnitTest;
use App\EofficeApp\Attendance\Services\AttendanceStatService;
/**
 * Description of AttendanceTest
 *
 * @author lizhijun
 */
class AttendanceStatTest extends UnitTest
{
    public $callMethods = [
        'abnormalUserStatTest'
    ];
    public function __construct(AttendanceStatService $attendanceStatService) 
    {
        parent::__construct();
        $this->attendanceStatService = $attendanceStatService;
    }
    /**
     * 分类统计测试
     */
    public function abnormalUserStatTest() 
    {
        $params= ['start_date' => '2020-09-01', 'end_date' => '2020-09-19'];
        $result = $this->attendanceStatService->abnormalUserStat($params);
        $this->responseJson($result, false);
    }
}
