<?php
namespace App\EofficeApp\PersonalSet\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 常用短语实体
 * 
 * @author  李志军
 * 
 * @since 2015-10-30
 */
class CommonPhraseEntity extends BaseEntity
{
	public $primaryKey		= 'phrase_id';
	
	public $table 			= 'common_phrase';

}
