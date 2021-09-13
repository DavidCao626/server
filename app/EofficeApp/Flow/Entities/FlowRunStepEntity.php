<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程办理步骤表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunStepEntity extends BaseEntity
{
    use SoftDeletes;

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
	public $table = 'flow_run_step';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'flow_step_id';

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
     * 一条流程运行步骤，流程办理人关联user表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunStepHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }

    /**
     * 流程步骤的run_id关联流程运行表[flow_run]
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowRunStepHasOneFlowRun()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowRunEntity', 'run_id', 'run_id');
    }

    /**
     * 一条流程运行步骤， flow_id 对应一个定义流程
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunStepBelongsToFlowType()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowTypeEntity','flow_id','flow_id');
    }

    /**
     * 一条流程运行步骤，通过flow_id，关联多个节点
     *
     * 只有一个地方用。可以考虑给那里改掉。已改，删除这里。
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    // function flowRunStepHasManyFlowProcess()
    // {
    //     return $this->hasMany('App\EofficeApp\Flow\Entities\FlowProcessEntity','flow_id','flow_id');
    // }

    /**
     * 流程步骤的flow_process关联流程节点表node_id
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowRunStepHasOneFlowProcess()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowProcessEntity', 'node_id', 'flow_process');
    }
}
