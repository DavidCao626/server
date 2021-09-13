<?php
namespace App\EofficeApp\Kingdee\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * K3集成
 *
 * @author wwf
 *
 * @since  2020-04-16 创建
 */
class KingdeeK3AccountConfigEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'kingdee_k3_account_config';
    public $primaryKey = 'kingdee_account_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    public function tables()
    {
        return $this->hasMany('App\EofficeApp\Kingdee\Entities\KingdeeK3TableEntity', 'account_id', 'kingdee_account_id');
    }
}
