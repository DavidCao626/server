<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 定义流程自由节点表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessFreeEntity extends BaseEntity
{

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
    public $table = 'flow_process_free';

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
     * 一条固定流程节点，对应多条经办用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessFreeHasManyPreset()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessFreePresetEntity','node_id','node_id');
    }
    /**
     * 一条固定流程节点，对应多条经办用户权限
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowProcessFreeHasManyRequired()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowProcessFreeRequiredEntity','node_id','node_id');
    }
}
