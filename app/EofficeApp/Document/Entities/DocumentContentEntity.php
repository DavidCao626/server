<?php
namespace App\EofficeApp\Document\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 文档内容实体类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentContentEntity extends BaseEntity
{
	use SoftDeletes;
	
	public $primaryKey		= 'document_id';
	
	public $table 			= 'document_content';

	public $dates 		    = ['deleted_at'];
}
