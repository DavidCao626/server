<?php

namespace App\EofficeApp\Message;

/**
 * 消息提醒发送接口
 */
interface MessageInterface
{
	/**
	 * [sendMessage 消息发送]
	 *
	 * @method sendMessage
	 *
	 * @param  [string]      $fromId    [发送人ID]
	 * @param  [string]      $toId      [发送对象ID,多人则以逗号分隔]
	 * @param  [string]      $subject   [发送标题]
	 * @param  [string]      $content   [发送内容]
	 * @param  [string]      $tableName [对应功能表名]
	 * @param  [string]      $dataId    [对应记录的ID]
	 *
	 * @return [bool]                   [发送结果]
	 */
	public function sendMessage($fromId, $toId, $subject, $content, $tableName, $dataId);
}