<?php
namespace App\Jobs;

use App\EofficeApp\WorkWechat\Services\WorkWechatService;

class syncWorkWeChatJob extends Job
{

    public $params;
    public $loginUserInfo;
    public $creatorData;
    public $client;
    public $accessToken;
    public $syncType;
    public $allParam;
    public $logId;

    public function __construct($params, $loginUserInfo,$creatorData,$accessToken,$syncType,$allParam,$logId = 1)
    {
        $this->params = $params;
        $this->loginUserInfo = $loginUserInfo;
        $this->creatorData = $creatorData;
        $this->accessToken = $accessToken;
        $this->syncType = $syncType;
        $this->allParam = $allParam;
        $this->logId = $logId;

    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function handle()
    {
        if ( $this->syncType == 'work_wechat_to_oa'){
            /** @var WorkWeChatService $service */
            $service = app("App\EofficeApp\WorkWechat\Services\WorkWechatService");
            $syncAddAndUpdateUserWorkWeChatToOa = $service->incrSyncToOA($this->params, $this->loginUserInfo, $this->creatorData,$this->accessToken, $this->logId);
            $service->abnormalDataLog($syncAddAndUpdateUserWorkWeChatToOa);
        }

        if ( $this->syncType == 'oa_to_work_wechat'){

            $service = app("App\EofficeApp\WorkWechat\Services\WorkWechatService");
            $syncAutoOaToWorkWeChat = $service->autoSyncToWorkWeChat($this->allParam['controller'],$this->allParam['response'],$this->allParam);
            if (isset($syncAutoOaToWorkWeChat['code'])){
                $service->abnormalDataLog($syncAutoOaToWorkWeChat);
            }
        }
    }
}
