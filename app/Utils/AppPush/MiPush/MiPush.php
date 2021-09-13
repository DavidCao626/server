<?php
namespace App\Utils\AppPush\MiPush;

use App\Utils\AppPush\Base\BasePush;
use App\Utils\AppPush\Base\PushInterface;
use xmpush\Builder;
use xmpush\Sender;
use xmpush\Constants;

include_once(dirname(__FILE__) . '/autoload.php');

class MiPush extends BasePush implements PushInterface
{
    private $package = 'com.weaver.eoffice';

    private $appSecret = 'P0EBmW/hxbiidGYwVKPS5w==';

    private $title = 'e-office';

    public function __construct()
    {
        parent::__construct();

        Constants::setPackage($this->package);

        Constants::setSecret($this->appSecret);
    }

    public function sendMessage($message, $registerId, $type)
    {
        $sender = new Sender();

        switch($type) {
                case 'normal':
                    return $this->sendNormalMessage($message, $registerId, $sender);
                default:
                    return $this->sendNormalMessage($message, $registerId, $sender);
            }

    }
    private function sendNormalMessage($message, $registerId, $sender)
    {
        $payload = 'e-office';
        $redirectUrl = isset($message['redirect_url']) ? $message['redirect_url'] : '';
        $menu = isset($message['menu']) ? $message['menu'] : '';
        $type = isset($message['type']) ? $message['type'] : '';
        $stateParam = isset($message['stateParam']) ? json_decode($message['stateParam'],true) : '';
        $extras = [
            'redirect_url' => $redirectUrl,
            'menu'         => $menu,
            'type'         => $type,
            'stateParam'   => $stateParam
        ];

        $msgBuilder = new Builder();
        $msgBuilder->title($this->title);// 通知栏的title
        $msgBuilder->description($message['content']); // 通知栏的descption
        $msgBuilder->notifyType(1);
        $msgBuilder->passThrough(0);// 这是一条通知栏消息，如果需要透传，把这个参数设置成1,同时去掉title和descption两个参数
        $msgBuilder->payload($payload);// 携带的数据，点击后将会通过客户端的receiver中的onReceiveMessage方法传入。
        $msgBuilder->extra(Builder::notifyEffect, 1);// 应用在前台是否展示通知，如果不希望应用在前台时候弹出通知，则设置这个参数为0，打开app是1，自定义行为是2
        $msgBuilder->extra(Builder::notifyForeground, 1);
        // $msgBuilder->extra(Builder::intentUri, "intent:#Intent;component=com.weaver.eoffice/.MainActivity;extra=1;end");
        // $msgBuilder->extra('extras', json_encode($extras));
        $uid = rand();
        $msgBuilder->notifyId($uid);// 通知类型
        $msgBuilder->build();
        $res = $sender->sendToIds($msgBuilder, $registerId)->getRaw();
        return $res;
        // return $sender->broadcastAll($msgBuilder)->getRaw();
    }

    public function validate()
    {

    }
}
