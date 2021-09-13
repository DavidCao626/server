<?php
namespace App\EofficeApp\Contract\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractProjectProjectEntity extends BaseEntity
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
    protected $table = 'contract_t_project_project';

}
