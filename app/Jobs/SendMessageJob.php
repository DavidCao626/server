<?php
namespace App\Jobs;

use Eoffice;
use Illuminate\Support\Facades\Redis;
class sendMessageJob extends Job {

    public $sendData;

    const SEND_TYPE = ['dingtalk', 'shortMessage', 'workwechat'];
    /**
     * 数据导入导出
     *
     * @return void
     */
    public function __construct($sendData) {
        $this->sendData = $sendData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        foreach ($this->sendData['sendMethod'] as $value) {
            if (empty($value)) {
                return;
            }
            if (in_array($value, self::SEND_TYPE)) {
                $this->sendData['content'] = preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($this->sendData['content'])));
            } else if ($value == 'appPush') {
                $this->sendData['content'] = preg_replace('/\[emoji.*?\]/', '', str_replace(array("\r\n", "\r", "\n",'&nbsp;'),'',strip_tags($this->sendData['content'])));
            }
            //传入的消息处理方式是小写，需要把首字母转成大写，否则linux系统报错，文件名首字母是大写的
            $remindClassName = 'App\EofficeApp\Vendor\Message\\' . ucwords($value);
            $message = app($remindClassName);
            $message->sendMessage($this->sendData);
        }
    }
}
