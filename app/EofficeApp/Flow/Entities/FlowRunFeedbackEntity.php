<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程签办反馈表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunFeedbackEntity extends BaseEntity
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
	public $table = 'flow_run_feedback';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'feedback_id';

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
     * 一条签办反馈，签办人关联user表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunFeedbackHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }

    /**
     * 一条签办反馈，flow_process关联节点表
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowRunFeedbackHasOneNode()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowProcessEntity', 'node_id', 'flow_process');
    }

}
