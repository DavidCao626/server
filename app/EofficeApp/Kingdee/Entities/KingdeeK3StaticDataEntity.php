<?php
namespace App\EofficeApp\Kingdee\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * K3集成
 *
 * @author wwf
 *
 * @since  2020-05-12 创建
 */
class KingdeeK3StaticDataEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'kingdee_k3_static_data';

    public $primaryKey = 'kingdee_static_data_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    // public function table()
    // {
    //     return $this->hasOne('App\EofficeApp\Kingdee\Entities\KingdeeK3TableEntity', 'kingdee_table_id', 'table_id');
    // }
}
