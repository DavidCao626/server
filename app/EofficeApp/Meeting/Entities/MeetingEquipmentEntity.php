<?php 
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议室设备实体
 * 
 * @author 李志军
 */
class MeetingEquipmentEntity extends BaseEntity
{
    public $primaryKey 		= 'equipment_id';
	
    public $table 			= 'meeting_equipment';
	
}
