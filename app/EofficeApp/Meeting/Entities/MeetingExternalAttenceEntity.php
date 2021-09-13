<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议记录实体
 *
 * @author 李志军
 */
class MeetingExternalAttenceEntity extends BaseEntity
{
    public $primaryKey 		= 'meeting_external_attence_id';

    public $table 			= 'meeting_external_attendance';
}
