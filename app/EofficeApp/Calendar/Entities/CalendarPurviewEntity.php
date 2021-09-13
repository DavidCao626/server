<?php 
namespace App\EofficeApp\Calendar\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @日程记录实体
 * 
 *  
 */
class CalendarPurviewEntity extends BaseEntity
{
    public $table = 'calendar_purview';

    public $primaryKey = 'id';

    /**
	 * 公共组有多个用户
	 * @return object
	 */
	public function calendarPurviewHasManyUser() {
		return $this->hasMany('App\EofficeApp\Calendar\Entities\CalendarPurviewUserEntity','group_id','id');
	}
	/**
	 * 公共组有多个部门
	 * @return object
	 */
	public function calendarPurviewHasManyDept() {
		return $this->hasMany('App\EofficeApp\Calendar\Entities\CalendarPurviewDeptEntity','group_id','id');
	}
	/**
	 * 公共组有多个角色
	 * @return object
	 */
	public function calendarPurviewHasManyRole() {
		return $this->hasMany('App\EofficeApp\Calendar\Entities\CalendarPurviewRoleEntity','group_id','id');
	}
	/**
	 * 公共组有多个共享用户
	 * @return object
	 */
	public function calendarPurviewHasManyManageUser() {
		return $this->hasMany('App\EofficeApp\Calendar\Entities\CalendarPurviewManageUserEntity','group_id','id');
	}
}
