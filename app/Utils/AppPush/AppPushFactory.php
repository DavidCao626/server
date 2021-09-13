<?php
namespace App\Utils\AppPush;

class AppPushFactory 
{  
    public static function getPushServer($serverName)
    {
        switch ($serverName){
            case 'HWPush':
                return new \App\Utils\AppPush\HWPush\HWPush();
            case 'MiPush':
                return new \App\Utils\AppPush\MiPush\MiPush();
            default:
                return new \App\Utils\AppPush\JPush\JPush();
        }
    }
}
