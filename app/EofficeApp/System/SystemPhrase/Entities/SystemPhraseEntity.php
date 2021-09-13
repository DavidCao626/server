<?php
namespace App\EofficeApp\System\SystemPhrase\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 *  系统常用短语实体
 * 
 */
class SystemPhraseEntity extends BaseEntity
{
	public $primaryKey		= 'phrase_id';
	
	public $table 			= 'system_phrase';

}
