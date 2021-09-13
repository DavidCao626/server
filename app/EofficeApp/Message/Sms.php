<?php

namespace App\EofficeApp\Message;

use App\EofficeApp\Sms\Services\SmsService;

class Sms implements MessageInterface
{
	protected $smsService;

	public function __construct(SmsService $smsService)
	{
		$this->smsService = $smsService;
	}

	public function sendMessage($fromId, $toId, $subject, $content, $tableName, $dataId)
	{
		if($fromId == '' || $toId == '' || $content == '') {
			return false;
		}

		$sendData = [
			'from_id' => $fromId,
			'recipients' => $toId,
			'content' => $content,
			'table_name' => $tableName,
			'table_id' => $dataId,
			'send_time' => date('Y-m-d H:i:s')
		];

		return $this->smsService->addSms($sendData);
	}
}