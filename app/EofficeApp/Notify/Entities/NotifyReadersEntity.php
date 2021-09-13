<?php
namespace App\EofficeApp\Notify\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 公告查看人实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-23
 */
class NotifyReadersEntity extends BaseEntity
{
	public $primaryKey		= 'reader_id';
	
	public $table 			= 'notify_readers';
	
}
