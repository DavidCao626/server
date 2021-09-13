<?php
namespace App\EofficeApp\YonyouVoucher\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * U8集成
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class VoucherIntergrationU8MainConfigEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'voucher_intergration_u8_main_config';
    public $primaryKey = 'voucher_config_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    public function hasOneFlowType()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'bind_flow_id');
    }

}
