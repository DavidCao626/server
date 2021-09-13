<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 定义流程节点表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessEntity extends BaseEntity
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
	public $table = 'flow_process';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'node_id';

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
     * 一条固定流程节点，对应多条经办用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessUserEntity','id','node_id');
    }

    /**
     * 一条固定流程节点，对应多条经办角色权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessRoleEntity','id','node_id');
    }

    /**
     * 一条固定流程节点，对应多条经办部门权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessDepartmentEntity','id','node_id');
    }

    /**
     * 一条固定流程节点，对应多条抄送用户
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyCopyUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessCopyUserEntity','id','node_id')->orderBy('auto_id', 'asc');
    }

    /**
     * 一条固定流程节点，对应多条抄送角色
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyCopyRole()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessCopyRoleEntity','id','node_id')->orderBy('auto_id', 'asc');
    }

    /**
     * 一条固定流程节点，对应多条抄送部门
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyCopyDept()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessCopyDepartmentEntity','id','node_id')->orderBy('auto_id', 'asc');
    }

    /**
     * 一条固定流程节点，对应多条流程默认办理人
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyDefaultUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessDefaultUserEntity','id','node_id');
    }

    /**
     * 一条固定流程节点，默认主办人关联用户
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessDefaultUserHostHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','process_default_manage');
    }

    /**
     * 一条固定流程节点，关联多个出口条件
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyOutCondition()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTermEntity','term_id','node_id');
    }

    /**
     * 一条固定流程节点，关联“控件操作[node_id => control_id]”
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyControlOperation()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessControlOperationEntity','node_id','node_id');
    }

    /**
     * 一条固定流程节点，关联“流程数据外发URL”
     *
     * @return object
     *
     * @since  2016-01-12
     */
    public function flowProcessHasManyOutsend()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowOutsendEntity','node_id','node_id');
    }
    /**
     * 一条固定流程节点，关联“流程数据外发到子流程”
     *
     * @return object
     *
     * @since  2016-01-12
     */
    public function flowProcessHasManySunWorkflow()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowSunWorkflowEntity','node_id','node_id');
    }

    /**
     * 一条固定流程节点，对应一个节点模板，暂时没用到
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyRunTemplate()
    {
        return  $this->HasOne('App\EofficeApp\Flow\Entities\FlowFormTemplateEntity','node_id','node_id');
    }

    /**
     * 一条固定流程节点，对应多条数据验证
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessHasManyDataValidate()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowDataValidateEntity','node_id','node_id');
    }

    /**
     * 一条固定流程节点，对应多条超时处理
     *
     * @return object
     *
     * @since  20190817
     */
    public function flowProcessHasManyOverTimeRemind()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowOverTimeRemindEntity','node_id','node_id');
    }
    /**
     * 一条固定流程节点，对应多条流转节点流程
     *
     * @return object
     *
     * @since  20190817
     */
    public function flowProcessHasManyFlowRunProcess()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','flow_process','node_id');
    }


}
