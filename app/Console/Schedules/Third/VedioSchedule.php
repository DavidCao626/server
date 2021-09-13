<?php

namespace App\Console\Schedules\Third;

use App\Console\Schedules\Schedule;
use DB;
/**
 * 定时任务类
 *
 * 1、必须以Schedule为后缀命名类名和文件名。
 * 2、必须实现Schedule接口。
 * 3、可以参考本示例代码。
 *
 * @author lizhijun
 */
  class VedioSchedule implements Schedule
{
    public function call($schedule)
    {
        $username = "pktech";
        $password = "pk@tech";
        // 生成authcode
        $authcode = md5($username.$password.time()).'.'.time();
        $data = [];
        $data['audioes'] = 'pktech0219.wav';
        // $data['calleeE164s'] = '15601931532';
        $data['accessE164'] = 'pktech';
        $data['authCode'] = $authcode;
        // $data['statusCallback'] = '';

        // echo $authcode;
        // 封装curl 请求
        /**
         * curl post 对接  传输数据流
         * */
        function curlPost($Url, $data){
            if (is_array($data)) {
                $data = json_encode($data);
            }
            $ch = curl_init($Url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//$data JSON类型字符串
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
            $result = curl_exec($ch);
            curl_close ($ch);

            return $result;
        }
        // 播报语音文件
        function readVedio($data) {
            $url = 'http://111.231.107.47:8000/external/server/PlayAudio';
            $result = curlPost($url, $data);
            return $result;
        }
        // 通过流水号获取通话记录
        function getPhoneRecord($trancecode, $authcode) {
            // $a = readVedio($data);
            // $return = json_decode($a, true);
            $datas = [];
            $datas['UserId'] = 'pktech';
            $datas['TransCode'] = $trancecode;
            $datas['authCode'] = $authcode;
            $url = 'http://111.231.107.47:8000/external/server/GetCdrByTransCode';
            $result = curlPost($url, $datas);
            return $result;
        }
        function getMeetingAttendance() {
            $time = date("Y-m-d H:i:s", time());
            $result = DB::table('meeting_apply')
            ->where(function($query) use($time){
                $query->where('meeting_end_time', '>', $time)
                        // ->where('')
                        ->whereIn('meeting_status', [2, 4]);
            })->get()->toArray();
            return $result;

        }
            $result = getMeetingAttendance();
            if (!empty($result)) {
                foreach($result as $key => $value) {
                    $time = $value->meeting_begin_time;
                    $times = date("H:i",strtotime($time)-1800);
                    $schedule->call(function () use ($data, $authcode, $value, $times) {
                        $users = DB::table('meeting_attendance')->where('meeting_apply_id', '=', $value->meeting_apply_id)->get()->toArray();
                        if (empty($users)) {
                            $userarr = explode(',', $value->meeting_join_member);
                            foreach($userarr as $k => $v) {
                                $phonenumber = DB::table('user_info')->select(['phone_number'])->where('user_id', $v)->get()->toArray();
                                if ($phonenumber) {
                                    $phone = isset($phonenumber[0]) ? $phonenumber[0] : '';
                                    $callPhone = isset($phone->phone_number) ? $phone->phone_number : '';
                                    $data['calleeE164s'] = $callPhone;
                                    $a = readVedio($data);
                                    $res = json_decode($a, true);
                                    $trancecode = isset($res['transcode']) ? $res['transcode'] : '';
                                    $return = getPhoneRecord($trancecode, $authcode);
                                    $returns = json_decode($return, true);
                                    file_put_contents(base_path('storage/logs/video.txt'), var_export($returns, true) . "\r\n", FILE_APPEND);
                                }
                            }
                        }else{
                            $joinUser = [];
                            foreach ($users as $k => $v) {
                                $joinUser[] = $v->meeting_attence_user;
                            }
                            $userarr = array_unique(explode(',', $value->meeting_join_member));
                            $allUser = array_diff($userarr, $joinUser);
                            foreach($allUser as $k => $v) {
                                $phonenumber = DB::table('user_info')->select(['phone_number'])->where('user_id', $v)->get()->toArray();
                                if ($phonenumber) {
                                    $phone = isset($phonenumber[0]) ? $phonenumber[0] : '';
                                    $callPhone = isset($phone->phone_number) ? $phone->phone_number : '';
                                    $data['calleeE164s'] = $callPhone;
                                    $a = readVedio($data);
                                    $res = json_decode($a, true);
                                    $trancecode = isset($res['transcode']) ? $res['transcode'] : '';
                                    $return = getPhoneRecord($trancecode, $authcode);
                                    $returns = json_decode($return, true);
                                    file_put_contents(base_path('storage/logs/video.txt'), var_export($returns, true) . "\r\n", FILE_APPEND);
                                }
                            }


                        }
                    })->dailyAt($times);
                }
            }
        }
}

