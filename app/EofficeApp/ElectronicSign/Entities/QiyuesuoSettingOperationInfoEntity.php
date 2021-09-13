<?php
namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 电子签章-契约锁集成操作权限
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class QiyuesuoSettingOperationInfoEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_setting_operation_info';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'operationId';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * 一条操作权限，关联一条集成设置
     *
     * @return [object]               [关联关系]
     */
    public function operationInfoBelongsToSetting()
    {
        return $this->belongsTo('App\EofficeApp\ElectronicSign\Entities\QiyuesuoSettingEntity', 'settingId', 'settingId');
    }

}
