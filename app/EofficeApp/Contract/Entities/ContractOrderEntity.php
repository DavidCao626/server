<?php
namespace App\EofficeApp\Contract\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractOrderEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * [$table 数据表名]
     *
     * @var [string]
     */
    protected $table = 'contract_t_order';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var [array]
     */
    protected $fillable = ['contract_id', 'product_id','shipping_date','number','run_id','remarks'];

    public function product()
    {
        return  $this->HasOne('App\EofficeApp\Product\Entities\ProductEntity', 'product_id', 'product_id');
    }
}
