<?php
namespace App\EofficeApp\Notify\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 公告实体类
 *
 * @author 李志军
 *
 * @since 2015-10-17
 */
class NotifyEntity extends BaseEntity
{
	use SoftDeletes;

	public $primaryKey		= 'notify_id';

	public $table 			= 'notify';

	/**
	* 获取公共对象的类别
	*/
   public function notifyType(){
	   return $this->belongsTo('App\EofficeApp\Notify\Entities\NotifyTypeEntity', 'notify_type_id','notify_type_id');
   }

   public function user()
   {
	   return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'from_id', 'user_id');
   }

   public function reader()
   {
       return $this->hasMany('App\EofficeApp\Notify\Entities\NotifyReadersEntity','notify_id','notify_id');
   }
}
