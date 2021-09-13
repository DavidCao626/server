<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程数据外发子流程数据表
 *
 * @author 史瑶
 *
 * @since  2016-01-11 创建
 */
class FlowSunWorkflowEntity extends BaseEntity
{
	//use SoftDeletes;

	/**
	 * 应该被调整为日期的属性
	 *
	 * @var array
	 */
	//protected $dates = ['deleted_at'];
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_sun_workflow';

	/**
	 * 主键
	 *
	 * @var string
	 */
	public $primaryKey = 'id';
}
