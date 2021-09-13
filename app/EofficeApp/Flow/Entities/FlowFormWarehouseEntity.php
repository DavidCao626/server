<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程表单模板表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormWarehouseEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_form_warehouse';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'form_id';

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

}
