<?php
namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 电子签署-契约锁服务
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class FlowRunRelationQysContractEntity extends BaseEntity
{
    use SoftDeletes;
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'flow_run_relation_qys_contract';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

}
