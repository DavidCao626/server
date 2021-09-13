<?php

namespace App\EofficeApp\Message;

class MessageService
{
	protected $remindMode;

	public function __construct(MessageInterface $remindMode)
	{
		$this->remindMode = $remindMode;
	}

	public function sendMessage($fromId, $toId, $subject, $content, $tableName, $dataId)
	{
		$this->remindMode->sendMessage($fromId, $toId, $subject, $content, $tableName, $dataId);
	}
}