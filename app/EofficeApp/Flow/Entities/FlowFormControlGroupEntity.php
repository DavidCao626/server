<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程控件的“控件分组”表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormControlGroupEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_form_control_group';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'group_id';

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
     * 一个分组下有多个控件
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowFormControlGroupHasManyFormControl()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowFormControlSortEntity','belongs_group','group_id');
    }
}
