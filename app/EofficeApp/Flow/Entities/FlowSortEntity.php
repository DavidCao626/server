<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程分类表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowSortEntity extends BaseEntity
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
	public $table = 'flow_sort';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'id';

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
     * 每个流程类别下有多个定义好的流程
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowSortHasManyFlowType()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_sort', 'id');
    }

    /**
     * 每个流程类别下有多个定义好的流程，为了计算每个sort下有多少个flow_type
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowSortHasManyFlowTypeCount()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_sort', 'id');
    }
    /**
     * 一个表单类别对应多个管理人员
     */
    public function flowSortHasManyMnamgeUser()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowSortUserEntity', 'type_id', 'id');
    }
    /**
     * 一个表单类别对应多个管理角色
     */
    public function flowSortHasManyMnamgeRole()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowSortRoleEntity', 'type_id', 'id');
    }
    /**
     * 一个表单类别对应多个管理部门
     */
    public function flowSortHasManyMnamgeDeptarment()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowSortDepartmentEntity', 'type_id', 'id');
    }
}
