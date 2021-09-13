<?php
namespace App\EofficeApp\Document\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 文档文件夹实体类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentFolderEntity extends BaseEntity
{
	public $primaryKey		= 'folder_id';
	
	public $table 			= 'document_folder';
}
