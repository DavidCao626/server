<?php
namespace App\Utils\AppPush;
use DB;
class AppPush
{
    public static function send($message, $userId, $type = 'normal')
    {
        $registerId = self::getRegsterId($userId);//获取设备注册Id

        $servers = ['JPush', 'HWPush', 'MiPush'];//极光推送,华为推送,小米推送
        foreach ($servers as $server) {
           if(!empty($registerId[$server])) {
                $jPushServer = \App\Utils\AppPush\AppPushFactory::getPushServer($server);//获取推送服务
                $jPushServer->sendMessage($message, $registerId[$server], $type);
            }
        }
    }
    public static function validate()
    {
        $pushServerName = 'JPush';

        $pushServer = \App\Utils\AppPush\AppPushFactory::getPushServer($pushServerName);

        $pushServer->validate();
    }

    public static function getRegsterId($userId)
    {
        $query = DB::table('mobile_bind_user')->select(['register_id','user_id','push_type']);

        $query = is_array($userId) ? $query->whereIn('user_id',$userId) : $query->where('user_id',$userId);

        $registers = $query->get();

        $registerId = [
            'JPush'     => [],
            'HWPush'    => [],
            'MiPush'    => []
        ];
        if(count($registers) > 0){
            foreach($registers as $register) {
                if($register->push_type == 1) {
                    $registerId['HWPush'][] = $register->register_id;
                } else if($register->push_type == 2){
                    $registerId['MiPush'][] = $register->register_id;
                } else{
                    $registerId['JPush'][] = $register->register_id;
                }
            }
        }

        return $registerId;
    }
    /**
     * app设备与当前登录用户绑定
     *
     * @param type $data
     *
     * @return boolean
     */
    public static function bindMobile($data)
    {
        if(!isset($data['register_id']) || !$data['register_id'] || !isset($data['user_id']) || !$data['user_id']) {
            return false;
        }

        $registerId = $data['register_id'];

        $userId = $data['user_id'];

        $deviceType = $data['device_type'] ? $data['device_type'] : 'iphone';

        $pushType = self::getPushType($deviceType);

        $bind = DB::table('mobile_bind_user')->where([
            'user_id' => $userId,
            'register_id' => $registerId
        ])->first();

        if($bind) {
            return DB::table('mobile_bind_user')->where([
                'user_id' => $userId,
                'register_id' => $registerId
            ])->update(['device_type' => $deviceType, 'push_type' => $pushType]);
        }
        return DB::table('mobile_bind_user')->insert(['register_id' => $registerId,'user_id' => $userId, 'device_type' => $deviceType,'push_type' => $pushType]);
    }
    /**
     * 获取推送类别
     *
     * @param type $deviceType
     *
     * @return int
     */
    private static function getPushType($deviceType)
    {
        $pushType = 0;

        if($deviceType == 'huawei' || $deviceType == 'HUAWEI' || $deviceType == 'HONOR'){
            $pushType = 1;
        } else if($deviceType == 'xiaomi' || $deviceType == 'Xiaomi') {
            $pushType = 2;
        }

        return $pushType;
    }
    /**
     * app设备与当前登录用户解除绑定
     *
     * @param type $data
     *
     * @return boolean
     */
    public static function unbindMobile($data)
    {
        if(!isset($data['register_id']) || !$data['register_id'] || !isset($data['user_id']) || !$data['user_id']) {
            return false;
        }

        return DB::table('mobile_bind_user')->where('register_id', $data['register_id'])->where('user_id', $data['user_id'])->delete();
    }
}
