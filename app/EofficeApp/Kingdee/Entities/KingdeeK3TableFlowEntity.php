<?php
namespace App\EofficeApp\Kingdee\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * K3集成
 *
 * @author wwf
 *
 * @since  2020-04-22 创建
 */
class KingdeeK3TableFlowEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'kingdee_k3_table_flow';

    public $primaryKey = 'kingdee_table_flow_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    public $timestamps = false;

    public function flow()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'flow_id');
    }

    public function k3table()
    {
        return $this->hasOne('App\EofficeApp\Kingdee\Entities\KingdeeK3TableEntity', 'kingdee_table_id', 'k3table_id');
    }
}
