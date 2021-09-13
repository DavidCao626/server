<?php 
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议室实体
 * 
 * @author 李志军
 */
class MeetingSignTypeEntity extends BaseEntity
{
    public $primaryKey 		= 'sign_id';
	
    public $table 			= 'meeting_sign';
	
}
