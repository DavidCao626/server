<?php

namespace App\Console\Commands;

use App\EofficeApp\Vendor\Message\Workwechat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
//use Queue;
//use App\Jobs\sendChatJob;
use App\EofficeApp\Vendor\Message\AppPush;
use App\EofficeApp\Vendor\Message\Wechat;
class Push extends Command
{
    /**
     * 控制台命令名称.
     *
     * @var string
     */
    protected $signature = 'eoffice:push';

    /**
     * 控制台命令描述.
     *
     * @var string
     */
    protected $description = 'Eoffice 10.0 push channel in redis';

    protected $wechat;
    protected $workWechat;
    public function __construct(Wechat $wechat ,Workwechat $workWechat)
    {
        parent::__construct();
        $this->wechat = $wechat;
        $this->workWechat = $workWechat;
    }
    /**
     * 执行控制台命令.
     *
     * @return mixed
     */
    public function handle()
    {
        Redis::subscribe(['eoffice-chat-channel'], function ($message) {
            $message = json_decode($message,true);
            /************************@消息特殊处理--补充参数 start********************************/
            $urlParams='';
            if (isset($message['msg']) && !empty($message['msg']) && isset($message['msg']['msg_type']) && $message['msg']['msg_type'] == 'metion') {
                $urlParams=[];
                if (isset($message['msg']['download']['name'])&&!empty($message['msg']['download']['name'])){
                    $name=explode('.',$message['msg']['download']['name']);
                    $urlParams['mention']=$name[0]?$name[0]:'';
                    $urlParams['action']=$name[1]?$name[1]:'';
                }else{
                    $urlParams['mention']='';
                    $urlParams['action']='';
                }
                $urlParams['params']=$message['msg']['download']['params']?$message['msg']['download']['params']:'';  // msg.download.params  解析对象
                $urlParams=json_encode($urlParams);
            }
            /********************************************** end **************************************/
            $sendData = [
                'content'       => $message['message'] . ' - 来自 ' . $message['from'],
                'redirect_url'  => $this->messageUrl($message),
                'toUser'        => explode(',', rtrim($message['to'],','))
            ];

            $pushData = [
                'content'       => $message['message'],
                'toUser'        => $message['to'],
                'remindMenu'    =>'',
                'remindType'    =>'',
                'from'          => $message['from'],
                'chat'          => 'personal',
                'urlParams'=>$urlParams
            ];
            $this->wechat->sendMessage($pushData);
            //即时通讯企业微信消息推送
            $pushData['remindMenu'] = 'im';
            $this->workWechat->sendChatMessage($pushData);
            $appPush = new AppPush();
            
            $appPush->sendMessage($sendData);
        });
    }

    /**
     * 跳转路由处理.
     *
     * @return mixed
     */
    public function messageUrl($data)
    // 'home/message/index',
    {
        $url = '';
        if($data['type'] === 'user'){
            $url = 'home/message/chat/' . $data['id'];
        }else if($data['type'] === 'group'){
            $url = 'home/message/group/' . $data['id'];
        }else{
            $url = 'home/message/index';
        }
        return $url;
    }
}
