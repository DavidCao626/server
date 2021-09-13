<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 流程抄送表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowCopyEntity extends BaseEntity
{
    use SoftDeletes;
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_copy';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'copy_id';

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
     * 抄送流程的run_id关联流程运行表[flow_run]
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowCopyHasOneFlowRun()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowRunEntity', 'run_id', 'run_id');
    }

    /**
     * 抄送流程的抄送人 copy_user 关联 用户表
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowCopyHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','copy_user');
    }

    /**
     * 抄送流程的被抄送人 by_user_id 关联 用户表
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowCopyHasOneByCopyUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','by_user_id');
    }

    /**
     * 一条抄送， flow_id 对应一个定义流程
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowCopyBelongsToFlowType()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowTypeEntity','flow_id','flow_id');
    }

    /**
     * 一条抄送流程，通过flow_id，关联多个节点
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowCopyHasManyFlowProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowProcessEntity','flow_id','flow_id');
    }

}
