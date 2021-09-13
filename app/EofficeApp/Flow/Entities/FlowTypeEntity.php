<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 定义流程表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowTypeEntity extends BaseEntity
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
	public $table = 'flow_type';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'flow_id';

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
     * 一条定义流程，有多个节点
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyFlowProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowProcessEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，有一个其他设置
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasOneFlowOthers()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowOthersEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，有一个流程表单
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasOneFlowFormType()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowFormTypeEntity','form_id','form_id');
    }

    /**
     * 一条定义流程，属于一个流程类别
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeBelongsToFlowSort()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowSortEntity','flow_sort','id');
    }

    /**
     * 一条定义流程，对应多个流程抄送
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyFlowCopy()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowCopyEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，对应多个运行流程[flow_run]
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyFlowRun()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，对应多个流程step
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyFlowRunStep()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunStepEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，对应多个流程step，查unread
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyUnReadFlowRunStep()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunStepEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，对应多个流程process，查unread
     *
     * @author 缪晨晨
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyUnReadFlowRunProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，对应多个流程process
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyFlowRunProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程，对应多个出口条件
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowTypeHasManyFlowTerm()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowTermEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条自由流程可创建用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowTypeHasManyCreateUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeCreateUserEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条自由流程可创建角色权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowTypeHasManyCreateRole()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeCreateRoleEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条自由流程可创建部门权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowTypeHasManyCreateDept()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeCreateDepartmentEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条监控规则
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowTypeHasManyManageRule()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageRuleEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条监控人员指定人员
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowTypeHasManyManageUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageUserEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条监控人员指定角色
     *
     * @return object
     *
     * @since  2018-04-17
     */
    public function flowTypeHasManyManageRole()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageRoleEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条监控范围指定部门
     *
     * @return object
     *
     * @since  2018-04-17
     */
    public function flowTypeHasManyManageScopeDept()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageScopeDeptEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多条监控范围指定人员
     *
     * @return object
     *
     * @since  2018-04-17
     */
    public function flowTypeHasManyManageScopeUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageScopeUserEntity','flow_id','flow_id');
    }

    /**
     * 一条定义流程对应多个流程收藏
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowTypeHasManyFlowFavorite()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowFavoriteEntity','flow_id','flow_id');
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
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowOverTimeRemindEntity','flow_id','flow_id');
    }

    /**
     * 一个流程有一个定时任务，可能有
     *
     * @since zyx 20200408
     */
    public function flowTypeHasOneSchedule() {
        return $this->HasOne('App\EofficeApp\Flow\Entities\FlowScheduleEntity', 'flow_id', 'flow_id');
    }
}
