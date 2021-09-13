<?php
namespace App\EofficeApp\Document\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 文档共享实体类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentShareEntity extends BaseEntity
{
	public $primaryKey		= 'document_id';
	
	public $table 			= 'document_share';
}
