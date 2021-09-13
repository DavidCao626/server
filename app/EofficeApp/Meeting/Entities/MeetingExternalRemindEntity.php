<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议室实体
 *
 * @author 李旭
 */
class MeetingExternalRemindEntity extends BaseEntity
{
    public $primaryKey 		= 'external_remind_id';

    public $table 			= 'meeting_external_remind';

}
