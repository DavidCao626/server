<?php
namespace app\EofficeApp\Menu\Entities;

use App\EofficeApp\Base\BaseEntity;
//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 系统功能实体
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class MenuEntity extends BaseEntity {
   // use SoftDeletes;
    /** @var string $table 定义实体表 */
    public $table = 'menu';
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'menu_id';
    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    /**
     * 和 flow_module_factory 的对应关系
     *
     * @return object
     */
    public function menuHasOneFlowModuleFactory()
    {
        return $this->hasOne('App\EofficeApp\FlowModeling\Entities\FlowModelingEntity', 'module_id', 'menu_param');
    }
}
