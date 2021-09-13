<?php
namespace App\EofficeApp\Document\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 文档文件夹权限实体类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentFolderPurviewEntity extends BaseEntity
{
	public $primaryKey		= 'purview_id';
	
	public $table 			= 'document_folder_purview';
}
