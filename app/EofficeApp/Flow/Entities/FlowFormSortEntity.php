<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程表单分类表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormSortEntity extends BaseEntity
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
    public $table = 'flow_form_sort';

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
     * 一个表单类别对应多个表单
     */
    public function flowFormSortHasManyFlowForm()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowFormTypeEntity', 'form_sort', 'id');
    }
    /**
     * 一个表单类别对应多个管理人员
     */
    public function flowFormSortHasManyMnamgeUser()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowFormSortUserEntity', 'type_id', 'id');
    }
    /**
     * 一个表单类别对应多个管理角色
     */
    public function flowFormSortHasManyMnamgeRole()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowFormSortRoleEntity', 'type_id', 'id');
    }
    /**
     * 一个表单类别对应多个管理部门
     */
    public function flowFormSortHasManyMnamgeDeptarment()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowFormSortDepartmentEntity', 'type_id', 'id');
    }
}
