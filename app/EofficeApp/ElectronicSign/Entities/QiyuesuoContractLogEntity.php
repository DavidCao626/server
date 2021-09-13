<?php

namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * Class WfQysSealApplyAuthLogEntity
 * @package App\EofficeApp\ElectronicSign\Entities
 */
class QiyuesuoContractLogEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_contract_log';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'logId';

    public function hasOneFlowType()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'flowId');
    }

    public function hasOneRunFlowType()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowRunEntity', 'run_id', 'runId');
    }

    public function hasOneQiyuesuoServer()
    {
        return $this->hasOne('App\EofficeApp\ElectronicSign\Entities\QiyuesuoServerEntity', 'serverId', 'serverId');
    }

}
