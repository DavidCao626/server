<?php
namespace App\EofficeApp\Notify\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 公告类别实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-23
 */
class NotifyTypeEntity extends BaseEntity
{
	public $primaryKey		= 'notify_type_id';
	
	public $table 			= 'notify_type';
	/**
	 * [typeHasManyBook 公告类型与公告的一对多关系]
	 *
	 */
	public function typeHasManyNotify()
	{
		return $this->hasMany('App\EofficeApp\Notify\Entities\NotifyEntity','notify_type_id','notify_type_id');
	}
}
