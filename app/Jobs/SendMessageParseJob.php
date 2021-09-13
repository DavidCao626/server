<?php
namespace App\Jobs;

use Eoffice;
use App\Jobs\SendMessageJob;
use Illuminate\Support\Facades\Redis;
use Queue;
class SendMessageParseJob extends Job {

    public $sendData;

    protected $remindService;

    /**
     * 自动消息队列方式
     *
     * @return void
     */
    public function __construct($sendData) {
        $this->remindService = 'App\EofficeApp\System\Remind\Services\SystemRemindService';
        $this->smsService = 'App\EofficeApp\SystemSms\Services\SystemSmsService';
        $this->sendData = $sendData;

    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $sendData = $this->sendData;
        //系统提醒 放进队列
        if(!isset($sendData['toUser']) || !isset($sendData['remindMark'])) {
            return false;
        }

        $toUser = is_array($sendData['toUser']) ? array_filter($sendData['toUser']) : array_filter(explode(',', rtrim($sendData['toUser'], ',')));
        if(empty($toUser)){
            return false;
        }

        $remindMark = $sendData['remindMark'];
        $moduleType = '';
        if (isset($sendData['module_type'])) {
            $moduleType = $sendData['module_type'];
        }
        $remindMarkArr = explode('-', $remindMark);

        $remindsInfo = app($this->remindService)->getRemindByMark($remindMark);

        if(!$remindsInfo) {
            return false;
        }
        
        if ($remindMark == 'birthday-start') {
            if (isset($remindsInfo['email'])) {
                unset($remindsInfo['email']);
            }
        }
        $remind_name = mulit_trans_dynamic('system_reminds.remind_name.'.$remindsInfo['remind_menu']. '_' . $remindsInfo['remind_type'] . '_'. $remindsInfo['id']);
        
        $remindContentKey = $remindsInfo['remind_content'] ?? '';
        //解析消息提醒发送方式
        $sendMethods = $this->getRemindMethods($remindsInfo, $sendData);
        if(empty($sendMethods)) {
            return false;
        }
        $localeGroupUserIds = get_userid_group_by_locale($toUser);
        foreach ($localeGroupUserIds as $locale => $userIds){
            $sendDataItem = [
                'contents' => $remindContentKey,
                'sendMethod' => $sendMethods,
                'remindMenu' => $remindMarkArr[0] ?? '',
                'remindType' => $remindMarkArr[1] ?? '',
                'fromUser' => 'systemAdmin',
                'toUser' => $userIds,
                "remindName" => $remind_name,
                "module_type" => $moduleType,
                'remind_name' => $remindsInfo['remind_name'] ?? '',
                'remind_id' => $remindsInfo['id'] ?? '',
                'redirect_url' => $sendData['redirect_url'] ?? '',
                'locale'        => $locale
            ];
            //解析提醒内容
            $transContent = trans_dynamic('system_reminds.remind_content.' . $remindContentKey,[],$locale);
            if(isset($sendData['contentParam']) && $sendData['contentParam']) {
                $patten = "/\{(\S+?)\}/";
                preg_match_all($patten, $transContent, $variableName);

                //替换发送内容中的变量
                foreach ($variableName[1] as $key => $value) {
                    $transContent = str_replace($variableName[0][$key], $sendData['contentParam'][$value], $transContent);
                }
            } else {
                $transContent = $sendData['content'] ?? $transContent;
            }
            $sendDataItem['content'] = $transContent;
            //解析subject
            if(!isset($sendData['subject']) || !$sendData['subject']) {
                $sendDataItem['subject'] = $transContent;
            } else {
                $sendDataItem['subject'] = $sendData['subject'];
            }
            //解析stateParams
            if(isset($sendData['stateParams']) && $sendData['stateParams'] != '') {
                $sendDataItem['stateParams'] = json_encode($sendData['stateParams']);
            }
            $sendDataItem['contentParam'] = isset($sendData['meetingResponse']) ? $sendData['meetingResponse'] : '';
            // 多案例模式发送redis数据
            $isCasePlatform = envOverload('CASE_PLATFORM', false);
            if ($isCasePlatform) {
                $sendDataItem['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
            }
            Queue::push(new SendMessageJob($sendDataItem), null, 'eoffice_message_queue');
        }
    }
    private function getRemindMethods($remindsInfo, $sendData)
    {
        if (!isset($sendData['sendMethod'])) {
            $sendMethods = [];
            foreach ($remindsInfo as $key => $value) {
                if (is_array($value) && isset($value['selected']) && $value['selected'] == 1) {
                    $sendMethods[] = $key;
                }
            }
            return $sendMethods;
        } else {
            return $sendData['sendMethod'];
        }
    }
}
