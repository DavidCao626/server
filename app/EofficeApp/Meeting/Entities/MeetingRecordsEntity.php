<?php 
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议记录实体
 * 
 * @author 李志军
 */
class MeetingRecordsEntity extends BaseEntity
{
    public $primaryKey 		= 'record_id';
	
    public $table 			= 'meeting_records';
}
