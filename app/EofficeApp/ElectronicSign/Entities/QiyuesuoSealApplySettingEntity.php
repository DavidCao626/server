<?php

namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * Class WfQysSealApplyAuthLogEntity
 * @package App\EofficeApp\ElectronicSign\Entities
 */
class QiyuesuoSealApplySettingEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_seal_apply_setting';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'settingId';

    public function hasOneFlowType()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'workflowId');
    }
    public function hasOneQiyuesuoServer()
    {
        return $this->hasOne('App\EofficeApp\ElectronicSign\Entities\QiyuesuoServerEntity', 'serverId', 'serverId');
    }

    /**
     * [settinghasManySign 契约锁物理用印集成设置和外发节点设置的一对多关系]
     *
     * @return [object]          [关联关系]
     */
    public function hasManyOutsendInfo()
    {
        return $this->hasMany('App\EofficeApp\ElectronicSign\Entities\QiyuesuoSealApplySettingOutsendInfoEntity', 'settingId', 'settingId');
    }

}
