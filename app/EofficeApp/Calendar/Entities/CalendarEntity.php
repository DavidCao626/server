<?php 
namespace App\EofficeApp\Calendar\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @日程记录实体
 * 
 *  
 */
class CalendarEntity extends BaseEntity
{
    public $primaryKey = 'calendar_id';
    public $table = 'calendar';

    /**
	 * calendar有多个办理人
	 * @return object
	 */
	public function calendarHasManyHandle() {
		return $this->hasMany('App\EofficeApp\Calendar\Entities\CalendarHandleUserRelationEntity','calendar_id','calendar_id');
	}
	/**
	 * calendar有多个共享人
	 * @return object
	 */
	public function calendarHasManyShare() {
		return $this->hasMany('App\EofficeApp\Calendar\Entities\CalendarShareUserRelationEntity','calendar_id','calendar_id');
	}

}
