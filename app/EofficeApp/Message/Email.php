<?php

namespace App\EofficeApp\Message;

use App\EofficeApp\Email\Services\EmailService;

class Email implements MessageInterface
{
	protected $emailService;

	public function __construct(EmailService $emailService)
	{
		$this->emailService = $emailService;
	}

	public function sendMessage($fromId, $toId, $subject, $content, $tableName, $dataId)
	{
		if($fromId == '' || $toId == '' || $subject == '') {
			return false;
		}

		$sendData = [
			'user_id' => $fromId,
			'to_id' => $toId,
			'subject' => $subject,
			'content' => $content
		];

		return $this->emailService->newEmail($sendData);
	}
}