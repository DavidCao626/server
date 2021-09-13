<?php
 
namespace App\EofficeApp\Sms\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Sms\Requests\SmsRequest;
use App\EofficeApp\Sms\Services\SmsService;

/**
 * 内部消息控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class SmsController extends Controller {

    public function __construct(
    Request $request, SmsService $smsService, SmsRequest $smsRequest
    ) {
        parent::__construct();
        $this->smsService = $smsService;
        $this->smsRequest = $request;
        $this->formFilter($request, $smsRequest);
    }

    /**
     * 获取内部消息的列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function sentSms() {
        $result = $this->smsService->sentSms($this->smsRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 增加内部消息
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addSms() {
        $result = $this->smsService->addSms($this->smsRequest->all());
        return $this->returnResult($result);
    }

    public function getTalkList($user1, $user2, $type) {
        $result = $this->smsService->getTalkList($user1, $user2, $type,$this->smsRequest->all());
        return $this->returnResult($result);
    }
    
    public function getUnreadCountByReceive($to_id) {
        $result = $this->smsService->getUnreadCountByReceive($to_id);
        return $this->returnResult($result);
    }

    public function getSmsGroup($user_id){
        $result = $this->smsService->getSmsGroup($user_id);
        return $this->returnResult($result);
    }
    /**
     * 删除内部消息
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteSms($id) {
        $result = $this->smsService->deleteSms($this->smsRequest->all(), $id);
        return $this->returnResult($result);
    }

    public function readSms($id) {
        $result = $this->smsService->readSms($id);
        return $this->returnResult($result);
    }

  

}
