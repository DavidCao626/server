<?php
namespace App\EofficeApp\Contract\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractProjectEntity extends BaseEntity
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
    protected $table = 'contract_t_project';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var [array]
     */
//    protected $fillable = ['contract_id', 'type','money','pay_way','pay_account','pay_time','run_id','remarks','contract_t_id','invoice_time','pay_type'];
}
