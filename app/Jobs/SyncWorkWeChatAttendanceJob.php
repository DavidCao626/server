<?php
namespace App\Jobs;

use App\EofficeApp\WorkWechat\Services\WorkWechatService;

class SyncWorkWeChatAttendanceJob extends Job
{

    public $allUserIds;
    public $accessToken;
    public $startTime;
    public $endTime;
    public $groupArray;
    public $params;
    public $userId;

    public function __construct($allUserIds,$accessToken,$startTime,$endTime,$groupArray,$params,$userId)
    {
        $this->allUserIds = $allUserIds;
        $this->accessToken = $accessToken;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->groupArray = $groupArray;
        $this->params = $params;
        $this->userId = $userId;

    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function handle()
    {
        /** @var WorkWeChatService $service */
            $service = app("App\EofficeApp\WorkWechat\Services\WorkWechatService");
            $service->syncAttendanceJob($this->allUserIds,$this->accessToken,$this->startTime,$this->endTime,$this->groupArray,$this->params,$this->userId);

    }

}
