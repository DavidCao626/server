<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程运行表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunEntity extends BaseEntity
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
	public $table = 'flow_run';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'run_id';

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
     * 允许批量更新的字段
     *
     * @var array
     */
    protected $fillable = ["run_name","run_name_html","run_seq","attachment_id","attachment_name","run_seq","link_doc","view_user","current_step","transact_time","instancy_type","max_process_id","is_effect","flow_id","create_time","creator","run_seq_strip_tags","parent_id", "max_flow_serial"];

    /**
     * 一条运行流程，流程创建人关联user表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','creator');
    }

    /**
     * 一条运行流程，流程创建人关联userSystemInfo表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasOneUserSystemInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity','user_id','creator');
    }

    /**
     * 一条运行流程，run_id关联FlowRunFeedback表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowRunFeedback()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunFeedbackEntity','run_id','run_id');
    }

    /**
     * 一条运行流程，run_id关联FlowRunProcess表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowRunProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','run_id','run_id');
    }

    /**
     * 一条运行流程，run_id关联FlowRunStep表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowRunStep()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunStepEntity','run_id','run_id');
    }

    /**
     * 一条运行流程，run_id关联FlowRunStep表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowRunStepWithUser()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunStepEntity','run_id','run_id');
    }

    /**
     * 一条运行流程，run_id关联FlowCopy表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowCopy()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowCopyEntity','run_id','run_id');
    }

    /**
     * 一条运行流程，run_id关联Htmlsignature表;暂时没有。
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    // function flowRunHasManyHtmlsignature()
    // {
    //     return $this->hasMany('App\EofficeApp\System\Signature\Entities\HtmlsignatureEntity','documentid','run_id');
    // }

    /**
     * 一条运行流程，关联FlowRunProcess表，再传入当前用户id，获取：相对当前流程来说，当前用户的所在步骤
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowRunProcessRelateCurrentUser()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','run_id','run_id');
    }

    /**
     * 一条运行流程，对应一个定义流程
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function FlowRunHasOneFlowType()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowTypeEntity','flow_id','flow_id');
    }

    /**
     * 一条运行流程，通过flow_id，关联多个节点
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowProcessEntity','flow_id','flow_id');
    }

      /**
     * 一条运行流程，run_id关联FlowRunProcess表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunHasManyFlowRunProcessAlias()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','run_id','run_id');
    }
}
