<?php
require __DIR__ . '/../../bootstrap/app.php';
// 默认用法，引入数据库接口
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

    // ini_set("display_errors", "Off");
    // error_reporting(E_ALL^E_NOTICE^E_WARNING);
// 生成二维码
        function getMeetingQRCodeInfo($qrInfo) {
            $base_path = base_path();
            $filePath = base_path('public/meeting');
            if(!is_dir($filePath)){
                mkdir($filePath, 0777);
            }
            $filePath1 = base_path('public/meeting/external');
            if(!is_dir($filePath1)){
                mkdir($filePath1, 0777);
            }
            $qrPath = base_path('public/meeting/external/qrcode.png');
            QrCode::format('png')->size(200)->generate(json_encode($qrInfo), $qrPath);
            if(file_exists($qrPath)) {
                echo  imageToBase64($qrPath);
            }
            return false;
       }
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <title>会议邀请</title>
    <meta charset="utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta content="IE=Edge" http-equiv="X-UA-Compatible" />
    <meta name="format-detection" content="telephone=no" />
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport" />
</head>
<style>
    *{
        box-sizing: border-box;
    }
    body{
        margin: 0;
    }
    .meeting-invitation{
        height: 100vh;
        padding: 5% 30px 30px;
        text-align: center;
        background-repeat: no-repeat;
        background-size: 100% 100%;
        background-image: url(./images/invitation.png);
    }
    .meeting-invitation  .title{
        margin-top: 70px;
    }
    .meeting-invitation  .title p{
        font-size: 24px;
        line-height: 30px;

    }
    .meeting-invitation .target {
        font-size: 20px;
        font-weight: bold;
        margin-top: 30px;
    }
    .meeting-invitation .address {
        margin: 20px 0;
        font-size: 16px;
    }
    .meeting-invitation .invitation-time div {
        font-size: 16px;
        line-height: 25px;
    }
    .meeting-invitation .list-point {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #4e7dff;
        line-height: 25px;
        display: inline-block;
        margin-right: 10px;
        position: relative;
        top: -2px;
    }
    .meeting-invitation .list-point .end span .list-point {
        background-color: #ff8849;
    }
    .meeting-invitation .QRCode {
        position: absolute;
        bottom:20%;
        left: 50%;
        transform: translateX(-50%);
    }
    .meeting-invitation .QRCode img {
        width: 90%;
    }

@media screen and (min-width: 500px) {
    .meeting-invitation {
        background-image: url(./images/invitation_pad.png);
    }
    .meeting-invitation .QRCode {
        bottom: 20%;
    }
}

</style>
    <body class="{{theme}}">
    <?php
        $user_id = isset($_GET['userId']) ? security_filter($_GET['userId']) : '';
        $mApplyId = isset($_GET['applyId']) ? security_filter($_GET['applyId']) : '';
        $data = DB::table('meeting_apply')->where('meeting_apply_id', $mApplyId)->get()->toArray();
        if (empty($data)) {
            return '';
        }
        $user_id = '';
        $meetingName = '';
        if ($data) {
            $user_id = $data[0]->meeting_apply_user;
            $meetingName = $data[0]->meeting_subject;
        }
        $userInfo = DB::table('user')->select(["user_id", 'user_name'])->where('user_id', $user_id)->get()->toArray();
        $userName = '';
        if ($userInfo) {
            $userName = $userInfo[0]->user_name;
        }
        $qrInfo = [
            'user_id' => $user_id,
            'meeting_apply_id' => $mApplyId,
            'qrcodeFrom' => 'externalMeetingSign'
        ];
    ?>
        <center>
            <div class="has-header meeting-invitation">
                <div class="title">
                    <p><?php echo $meetingName?></p>
                </div>
                <div class="target">
                    <?php echo $userName;?>
                </div>
                <div class="address">
                    <span>
                        地点:
                    </span>
                    <span class="room"><?php echo security_filter($_GET['room'])?></span>
                </div>
                <div class="invitation-time">
                    <div class="start">
                        <span class="list-point"></span><span><?php echo isset($data[0]->meeting_begin_time) ? $data[0]->meeting_begin_time : '';?></span>
                    </div>
                    <div class="end">
                        <span class="list-point"  style=" background-color: #ff8849;"></span><span><?php echo isset($data[0]->meeting_end_time) ? $data[0]->meeting_end_time : '';?></span>
                    </div>
                </div>
                <div class="QRCode">
                    <?php
                        $user_id = isset($_GET['userId']) ? security_filter($_GET['userId']) : '';
                        $mApplyId = isset($_GET['applyId']) ? security_filter($_GET['applyId']) : '';
                        $qrInfo = [
                            'mode' => 'function',
                            'body' => [
                                'function_name' => 'externalMeetingSign',
                                'params' => [
                                    'user_id' => $user_id,
                                    'meeting_apply_id' => $mApplyId,
                                ]
                            ],
                            'timestamp' => time(),
                            'ttl' => 0
                        ];
//                        $qrInfo = [
//                            'user_id' => $user_id,
//                            'meeting_apply_id' => $mApplyId,
//                            'qrcodeFrom' => 'externalMeetingSign'
//                        ];
                    ?>
                    <img width="90%" src="<?php echo getMeetingQRCodeInfo($qrInfo);?>" alt="">
                </div>
            </div>
        </center>
    </body>
</html>