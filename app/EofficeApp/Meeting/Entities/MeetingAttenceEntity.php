<?php 
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议记录实体
 * 
 * @author 李志军
 */
class MeetingAttenceEntity extends BaseEntity
{
    public $primaryKey 		= 'meeting_attence_id';
	
    public $table 			= 'meeting_attendance';
}
