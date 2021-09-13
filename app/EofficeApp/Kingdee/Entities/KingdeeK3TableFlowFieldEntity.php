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
class KingdeeK3TableFlowFieldEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'kingdee_k3_table_flow_field';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
}
