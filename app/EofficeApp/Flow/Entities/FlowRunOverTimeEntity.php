<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 超时提醒
 *
 * @author 王政
 *
 * @since  
 */
class FlowRunOverTimeEntity extends BaseEntity
{

	const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
	/**
	 * 应该被调整为日期的属性
	 *
	 * @var array
	 */
	// protected $dates = ['created_at'];
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_run_overtime';

	/**
	 * 主键
	 *
	 * @var string
	 */
	public $primaryKey = 'id';

	 /**
     * 一条超时流程，关联flow_run
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function FlowRunOverTimeBelongsToFlowRun()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowRunEntity','run_id','run_id');
    }
     /**
     * 一条超时提醒，关联flow_run_process
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function FlowRunOverTimeHasManyFlowRunProcess()
    {
        return $this->HasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','run_id','run_id');
    }
    /**
     * 一条超时提醒，关联flow__process
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function FlowRunOverTimeHasOneFlowProcess()
    {
        return $this->HasOne('App\EofficeApp\Flow\Entities\FlowProcessEntity','node_id','node_id');
    }
     /**
     * 一条超时提醒，关联flow__process
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function FlowRunOverTimeBelongsToFlowType()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowTypeEntity','flow_id','flow_id');
    }

}
