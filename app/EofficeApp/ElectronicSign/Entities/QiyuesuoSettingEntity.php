<?php
namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 电子签章-契约锁集成
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class QiyuesuoSettingEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_setting';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'settingId';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * [settinghasManySign 契约锁集成设置和签署的一对多关系]
     *
     * @return [object]          [关联关系]
     */
    public function settinghasManySignInfo()
    {
        return $this->hasMany('App\EofficeApp\ElectronicSign\Entities\QiyuesuoSettingSignInfoEntity', 'settingId', 'settingId');
    }

    /**
     * [settinghasManySign 契约锁集成设置和操作权限的一对多关系]
     *
     * @return [object]          [关联关系]
     */
    public function settinghasManyOperationInfo()
    {
        return $this->hasMany('App\EofficeApp\ElectronicSign\Entities\QiyuesuoSettingOperationInfoEntity', 'settingId', 'settingId');
    }

    /**
     * [settinghasOneFlowName 契约锁集成设置和定义流程表实体的一对一关系]
     *
     * @return void
     */
    public function settinghasOneFlowName()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'workflowId');
    }

    /**
     * [settinghasOneServer 契约锁集成设置和契约锁服务表实体的一对一关系]
     *
     * @return void
     */
    public function settinghasOneServer()
    {
        return $this->hasOne('App\EofficeApp\ElectronicSign\Entities\QiyuesuoServerEntity', 'serverId', 'serverId');
    }

    /**
     * 契约锁集成设置和运行流程表实体的一对多关系
     *
     * @return [type]                     [description]
     */
    public function settingHasManyFlowRun()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunEntity', 'flow_id', 'workflowId');
    }

    /**
     * [settinghasManySign 契约锁集成设置和操作权限的一对多关系]
     *
     * @return [object]          [关联关系]
     */
    public function settinghasManyOutsendInfo()
    {
        return $this->hasMany('App\EofficeApp\ElectronicSign\Entities\QiyuesuoSealApplySettingOutsendInfoEntity', 'settingId', 'settingId');
    }
}
