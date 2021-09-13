<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程表单表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormTypeEntity extends BaseEntity
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
	public $table = 'flow_form_type';

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

    /**
     * 一条流程表单，关联多个定义流程
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowFormHasManyFlowType()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeEntity','form_id','form_id');
    }
    /**
     * 一条流程表单，关联多个定义流程
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowFormHasManyChildForm()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowChildFormTypeEntity','parent_id','form_id');
    }
    /**
     * 一条流程表单，关联一个表单分类
     */
    public function flowFormHasOneFlowFormSort()
    {
        return  $this->HasOne('App\EofficeApp\Flow\Entities\FlowFormSortEntity','id','form_sort');
    }
}
