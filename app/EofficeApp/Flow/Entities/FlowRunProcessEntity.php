<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程运行步骤表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunProcessEntity extends BaseEntity
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
	public $table = 'flow_run_process';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'flow_run_process_id';

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
    function flowRunProcessHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
    /**
     * 一条流程运行步骤，流程办理人关联user表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunProcessHasOneSendBackUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','send_back_user');
    }

    /**
     * 一条流程运行步骤，流程办理人关联user_system_info表
     *
     * @author 缪晨晨
     *
     * @return [type]                     [description]
     */
    function flowRunProcessHasOneUserSystemInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity','user_id','user_id');
    }

    /**
     * 一条流程运行步骤，流程委托人关联user表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunProcessHasOneAgentUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','by_agent_id');
    }

    /**
     * 一条流程运行步骤，流程转发人关联user表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunProcessHasOneForwardUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','forward_user_id');
    }

    /**
     * 一条流程运行步骤，流程监控提交人关联user表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunProcessHasOneMonitorSubmitUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','monitor_submit');
    }

    /**
     * 一条流程运行步骤，关联flow_run
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunProcessBelongsToFlowRun()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowRunEntity','run_id','run_id');
    }

    /**
     * 一条运行流程，流程节点对应定义流程--节点表的节点，在使用的时候，需要增加上flow_id的条件
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function FlowRunHasManyDefineProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowProcessEntity','process_id','flow_process');
    }

    /**
     * 一条运行流程，有多条委托记录
     *
     * @return [type]                     [description]
     */
    function flowRunProcessHasManyAgencyDetail()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessAgencyDetailEntity','flow_run_process_id','flow_run_process_id');
    }




    /**
     * 一条流程运行步骤， flow_id 对应一个定义流程
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunProcessBelongsToFlowType()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowTypeEntity','flow_id','flow_id');
    }

    /**
     * 一条流程运行步骤，通过flow_id，关联多个节点
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunProcessHasManyFlowProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowProcessEntity','flow_id','flow_id');
    }

    /**
     * 流程步骤的flow_process关联流程节点表node_id
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowRunProcessHasOneFlowProcess()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowProcessEntity', 'node_id', 'flow_process');
    }
    /**
     * 流程步骤的flow_process关联流程节点表node_id
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowRunProcessHasOneFlowProcessFree()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowProcessFreeEntity', 'node_id', 'flow_process');
    }

     /**
     * 流程步骤的flow_process关联流程节点表node_id
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowRunProcessHasManyFlowRunProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity', 'run_id', 'run_id');
    }

    /**
     * 一条运行节点关联多个外发日志
     *
     * @author 张译心
     *
     * @since  20200807 创建
     *
     * @return [object]               [关联关系]
     */
    // public function flowRunProcessHasManyFlowOutsendLog()
    // {
    //     return $this->hasMany('App\EofficeApp\System\Log\Entities\SystemFlowLogEntity', 'log_relation_id_add', 'flow_run_process_id');
    // }

    
}
