<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程表单内的控件序号表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormControlSortEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_form_control_sort';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'sort_id';

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
     * belongs_group字段关联flow_form_control_group表group_id字段，一对一，belongsTo
     * 每个控件，属于一个分组
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowFormControlBelongsToGroup()
    {
        return  $this->belongsTo('App\EofficeApp\Flow\Entities\FlowFormControlGroupEntity','belongs_group','group_id');
    }
}
