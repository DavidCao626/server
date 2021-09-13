<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程表单模板，控件明细表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormTemplateControlStructureEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_form_template_control_structure';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'template_control_auto_id';

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'asc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;

}
