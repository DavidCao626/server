<?php
namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 电子签署-契约锁服务
 *
 * @author yml
 *
 * @since  2019-05-23 创建
 */
class FlowRunRelationQysContractInfoEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'flow_run_relation_qys_contract_info';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

     /**
     * 合同信息和运行流程表实体的一对一关系
     *
     * @return [type]                     [description]
     */
    public function hasOneFlowRun()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowRunEntity', 'run_id', 'runId');
    }

}
