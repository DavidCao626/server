<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程委托表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowAgencyDetailEntity extends BaseEntity
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
	public $table = 'flow_agency_detail';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'agency_detail_id';

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'desc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;

    /**
     * 一条委托详细，关联一条委托
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowAgencyDetailBelongsToFlowAgency()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowAgencyEntity', 'flow_agency_id', 'flow_agency_id');
    }

    /**
     * 一条委托详细，流程关联一条定义流程
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowAgencyDetailHasOneFlowType()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'flow_id');
    }
}
