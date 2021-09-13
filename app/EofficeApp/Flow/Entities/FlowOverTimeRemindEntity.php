<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 超时提醒
 *
 * @author 王政
 *
 * @since  
 */
class FlowOverTimeRemindEntity extends BaseEntity
{ 
	/**
	 * 应该被调整为日期的属性
	 *
	 * @var array
	 */
	// protected $dates = ['created_at'];
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_overtime_remind';

	/**
	 * 主键
	 *
	 * @var string
	 */
	public $primaryKey = 'id';
}
