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
class KingdeeK3TableEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'kingdee_k3_table';

    public $primaryKey = 'kingdee_table_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
    public function account()
    {
        return $this->hasOne('App\EofficeApp\Kingdee\Entities\KingdeeK3AccountConfigEntity', 'kingdee_account_id', 'account_id');
    }
}
