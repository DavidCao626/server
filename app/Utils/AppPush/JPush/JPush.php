<?php
namespace App\Utils\AppPush\JPush;

use App\Utils\AppPush\Base\BasePush;
use App\Utils\AppPush\Base\PushInterface;
use JPush\Client as JPushClient;

class Jpush extends BasePush implements PushInterface
{
    private $appKey = 'cccdfefb5e2a575239abf266';
                
    private $masterSecret = '53104157b7ee8fbb4ab978ec';
    
    private $jPushClient;
    
    private $timeToLive = 3600;
    
    public function __construct() 
    {
        parent::__construct();
        if (!is_dir($this->loggerPath . 'jpush')) {
            mkdir($this->loggerPath . 'jpush', 0777);
        }
        $this->jPushClient = new JPushClient( $this->appKey, $this->masterSecret, $this->loggerPath . 'jpush/' .date('Y-m-d') . '.log');
    }
    
    public function sendMessage($message, $registerId, $type) 
    {   
        try {
            $push = $this->jPushClient->push();//返回推送构建器
            
            $push->setPlatform(['ios', 'android']);//设置推送平台

            switch($type) {
                case 'normal':
                    return $this->sendNotification($message, $registerId, $push);
                default:
                    return $this->sendNotification($message, $registerId, $push);
            }
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            print $e;
        } catch (\JPush\Exceptions\APIRequestException $e) {
            print $e;
        }
    }
    
    private function sendNotification($message, $registerId, $push)
    {
        
        $alert = $message['content'];

        $extras = [
            'redirect_url' => isset($message['redirect_url']) ? $message['redirect_url'] : '',
            'menu'          => isset($message['menu']) ? $message['menu'] : '',
            'type'          =>  isset($message['type']) ? $message['type'] : '',
            'stateParam'    =>  isset($message['stateParam']) && $message['stateParam'] ? json_decode($message['stateParam'],true) : ''
        ];
         
        $iosNotification = [
            'content-available' => true,
            "sound" => "default",
            'extras'            => $extras
        ];
        
        $androidNotification = [
            'title'     => 'e-office10',
            'extras'    => $extras
        ];
        
        $options = ['time_to_live' => $this->timeToLive,'apns_production' => true];
       
        $result = $push->addRegistrationId($registerId)
            ->iosNotification($alert, $iosNotification)
            ->androidNotification($alert, $androidNotification)
          //  ->setMessage($alert, 'e-office10', '系統消息', $extras)
            ->options($options)
            ->send();
        
        return $result;
    }
       
    public function validate()
    {
        
    }
}
