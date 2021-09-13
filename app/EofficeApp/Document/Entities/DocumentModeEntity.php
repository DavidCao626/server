<?php
namespace App\EofficeApp\Document\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 文档样式实体类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentModeEntity extends BaseEntity
{
	public $primaryKey		= 'mode_id';
	
	public $table 			= 'document_mode';
}
