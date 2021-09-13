<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程数据外发数据表
 *
 * @author 史瑶
 *
 * @since  2016-01-11 创建
 */
class FlowOutsendEntity extends BaseEntity
{
	//use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    //protected $dates = ['deleted_at'];
    protected $hidden = [
        'updated_at','created_at','deleted_at'
    ];
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_outsend';

	/**
	 * 主键
	 *
	 * @var string
	 */
	public $primaryKey = 'id';

    public function outsendHasManyFields()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowOutsendFieldsEntity','flow_outsend_id','id');
    }

    // 依赖字段
    public function outsendHasManyDependentFields()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowOutsendDependentFieldsEntity', 'flow_outsend_id', 'id');
    }

    // 流程运行节点
    public function outsendHasOneFlowRunProcess()
    {
        return  $this->HasOne('App\EofficeApp\Flow\Entities\FlowRunProcessEntity', 'flow_run_process_id', 'log_relation_id_add');
    }
}
