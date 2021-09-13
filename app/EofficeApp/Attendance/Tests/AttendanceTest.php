<?php
namespace App\EofficeApp\Attendance\Tests;
use App\Tests\UnitTest;
use App\EofficeApp\Attendance\Services\AttendanceService;
/**
 * Description of AttendanceTest
 *
 * @author lizhijun
 */
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
class AttendanceTest extends UnitTest
{
    public $callMethods = [
        //'autoSignInTest',
//        'signInTest',
//        'signOutTest',
        'testHttp',
//        'externalSignInTest',
//        'externalSignOutTest',
//        'sendSignRemindTest',
//        'setSignRemindJobTest',
//        'setAutoSignOutJob'
    ];
    public function __construct(AttendanceService $attendanceService) 
    {
        parent::__construct();
        $this->attendanceService = $attendanceService;
    }
    public function testHttp()
    {
        $httpClient = new GuzzleHttpClient(); // Create HTTP client
        try {
            $response = $httpClient->get('http://www.baidu.com');
        } catch (GuzzleRequestException $e) {
            throw new ErrorException($e->getMessage());
        }
        $body = $response->getBody();
        $contents = $body->getContents();
        var_dump($contents);
    }
    public function setAutoSignOutJob()
    {
        $this->attendanceService->setAutoSignOutJob();
    }
    /**
     * 打卡提醒定时任务测试
     */
    public function setSignRemindJobTest() 
    {
        $result = exec_eoffice_command('php artisan queue:work --queue=import');
        
    
    }
    public function sendSignRemindTest()
    {
        
    }
   
    public function autoSignInTest()
    {
        $own = [
            'user_id' => 'admin',
            'dept_id' => 1,
            'role_id' => [1, 2]
        ];
        $data = [
            'platform' => 1 // 1pc，2app，3微信公众号，4企业号,5企业微信,6移动钉钉,7PC钉钉,8考勤机,11手机政务钉钉,12 PC政务钉钉
        ];
        $result = $this->attendanceService->autoSignIn($own, $data);
        $this->responseJson($result);
    }
    public function signOutTest()
    {
        $own = [
            'user_id' => 'admin',
            'dept_id' => 1,
            'role_id' => [1, 2]
        ];
        $data = [
            'platform' => 1, // 1pc，2app，3微信公众号，4企业号,5企业微信,6移动钉钉,7PC钉钉,8考勤机,11手机政务钉钉,12 PC政务钉钉
            'sign_date' => '2020-11-07',
            'sign_out_time' => '2020-11-07 18:42:31'
        ];
        $result = $this->attendanceService->signOut($data, $own);
        $this->responseJson($result);
    }
    public function externalSignInTest()
    {
        $own = [
            'user_id' => 'admin',
            'dept_id' => 1,
            'role_id' => [1, 2]
        ];
        $data = [
            'platform' => 2, // 1pc，2app，3微信公众号，4企业号,5企业微信,6移动钉钉,7PC钉钉,8考勤机,11手机政务钉钉,12 PC政务钉钉
            'sign_date' => '2020-10-29',
            'sign_in_time' => '2020-10-29 13:37:31',
            'sign_times' => 1,
            'long' => '121.474817',
            'lat' => '31.173005',
            'address' => '上海市浦东新区上钢新村街道耀华支路53号耀华滨江公寓'
        ];
        $result = $this->attendanceService->externalSignIn($data, $own);
        $this->responseJson($result);
    }
    public function externalSignOutTest()
    {
        $own = [
            'user_id' => 'admin',
            'dept_id' => 1,
            'role_id' => [1, 2]
        ];
        $data = [
            'platform' => 2, // 1pc，2app，3微信公众号，4企业号,5企业微信,6移动钉钉,7PC钉钉,8考勤机,11手机政务钉钉,12 PC政务钉钉
            'sign_date' => '2020-10-29',
            'sign_out_time' => '2020-10-29 14:37:32',
            'sign_times' => 1,
            'long' => '121.474817',
            'lat' => '31.173005',
            'address' => '上海市浦东新区上钢新村街道耀华支路53号耀华滨江公寓'
        ];
        $result = $this->attendanceService->externalSignOut($data, $own);
        $this->responseJson($result);
    }
    public function signInTest()
    {
        $own = [
            'user_id' => 'admin',
            'dept_id' => 1,
            'role_id' => [1, 2]
        ];
        $data = [
            'platform' => 1, // 1pc，2app，3微信公众号，4企业号,5企业微信,6移动钉钉,7PC钉钉,8考勤机,11手机政务钉钉,12 PC政务钉钉
            'sign_date' => '2020-11-07',
            'sign_in_time' => '2020-11-07 09:00:00'
        ];
        $result = $this->attendanceService->signIn($data, $own);
        $this->responseJson($result);
    }
    public function batchSignTest()
    {
        $signData =  [
            'WV00000198' => [
                '2020-10-27'  => [
                    [
                        'sign_date' => '2020-10-27',
                        'sign_nubmer' => 1,
                        'sign_in_time' => '2020-10-27 10:00:00',
                        'sign_out_time' => '2020-10-27 17:00:00',
                        'platform' => 8
                    ]
                ],
                '2020-10-28'  => [
                    [
                        'sign_date' => '2020-10-28',
                        'sign_nubmer' => 1,
                        'sign_in_time' => '2020-10-28 10:00:00',
                        'sign_out_time' => '2020-10-28 17:00:00',
                        'platform' => 8
                    ]
                ],
                '2020-10-29'  => [
                    [
                        'sign_date' => '2020-10-29',
                        'sign_nubmer' => 1,
                        'sign_in_time' => '2020-10-29 10:00:00',
                        'sign_out_time' => '2020-10-29 17:00:00',
                        'platform' => 8
                    ]
                ]
            ],
            'WV00000001' => [
                '2020-10-27'  => [
                    [
                        'sign_date' => '2020-10-27',
                        'sign_nubmer' => 1,
                        'sign_in_time' => '2020-10-27 10:00:00',
                        'sign_out_time' => '2020-10-27 17:00:00',
                        'platform' => 8
                    ]
                ],
                '2020-10-28'  => [
                    [
                        'sign_date' => '2020-10-28',
                        'sign_nubmer' => 1,
                        'sign_in_time' => '2020-10-28 10:00:00',
                        'sign_out_time' => '2020-10-28 17:00:00',
                        'platform' => 8
                    ]
                ],
                '2020-10-29'  => [
                    [
                        'sign_date' => '2020-10-29',
                        'sign_nubmer' => 1,
                        'sign_in_time' => '2020-10-29 10:00:00',
                        'sign_out_time' => '2020-10-29 17:00:00',
                        'platform' => 8
                    ]
                ]
            ]
        ];
        $signData =  [
            'admin' => [
                '2020-12-23'  => [
                    [
                        'sign_date' => '2020-12-23',
                        'sign_nubmer' => 1,
                        'sign_in_time' => '2020-12-23 05:00:00',
                        'sign_out_time' => '2020-12-23 18:30:00',
                        'platform' => 8
                    ]
                ]
            ]
        ];
        $result = $this->attendanceService->batchSign($signData, '2020-12-23', '2020-12-23');
        $this->responseJson($result);
    }
}
