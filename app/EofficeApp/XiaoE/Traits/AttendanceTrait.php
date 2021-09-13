<?php

namespace App\EofficeApp\XiaoE\Traits;

trait AttendanceTrait
{
    /**
     * 判断是否可以直接打卡
     */
    private function directSign($userId)
    {
        $record = $this->getLastTwoDaysShift($userId);
        //昨天已经不能打卡&&今天是正常班&&不跨天
        if (!$record['yesterday'] && isset($record['today']['data']) && count($record['today']['data']) == 1) {
            $signInNormal = $record['today']['data'][0]['sign_in']['normal'];
            $signOutNormal = $record['today']['data'][0]['sign_out']['normal'];
            if ($signInNormal < $signOutNormal) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取用户最近两天的班次情况，包括打卡信息
     * @param $userId
     * @return array
     */
    public function getLastTwoDaysShift($userId)
    {
        $twoDaysShift = make($this->attendanceService)->getLastTwoDaysShift($userId);
        $yesterday = $this->getOneDaySignInfo($twoDaysShift, 'yesterday');
        $today = $this->getOneDaySignInfo($twoDaysShift, 'today');
        return ['yesterday' => $yesterday, 'today' => $today];
    }

    private function getOneDaySignInfo($twoDaysShift, $day)
    {
        if ($twoDaysShift[$day]) {
            $daysName = ['yesterday' => '昨天', 'today' => '今天'];
            $oneDay = $twoDaysShift[$day];
            if (!isset($oneDay['sign_time'])) {
                return false;
            }
            $oneDaySignTime = $oneDay['sign_time']->toArray();
            $oneDayShift = $oneDay['shift']->toArray();
            $dayName = $daysName[$day];
            $sign = [];
            //昨天的数据是这种结构
            if ($day == 'yesterday') {
                $sign[] = [
                    'sign_in' => [
                        'normal' => $oneDaySignTime['sign_in_time'],//班次时间
                        'real' => $oneDaySignTime['result']['sign_in_time'] ?? false,//实际时间
                    ],
                    'sign_out' => [
                        'normal' => $oneDaySignTime['sign_out_time'],//班次时间
                        'real' => $oneDaySignTime['result']['sign_out_time'] ?? false,//实际时间
                    ]
                ];
            } elseif (count($oneDaySignTime) > 0) {
                foreach ($oneDaySignTime as $oneSign) {
                    $sign[] = [
                        'sign_in' => [
                            'normal' => $oneSign['sign_in_time'],//班次时间
                            'real' => $oneSign['result']['sign_in_time'] ?? false,//实际时间
                        ],
                        'sign_out' => [
                            'normal' => $oneSign['sign_out_time'],//班次时间
                            'real' => $oneSign['result']['sign_out_time'] ?? false,//实际时间
                        ]
                    ];
                }
            }
            return ['title' => $oneDayShift['shift_name'] . '(' . $dayName . ')', 'data' => $sign];
        }
        return false;
    }
}
