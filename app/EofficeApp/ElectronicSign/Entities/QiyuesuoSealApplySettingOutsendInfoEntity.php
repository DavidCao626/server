<?php
namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 电子签章-契约锁集成签署信息
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class QiyuesuoSealApplySettingOutsendInfoEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_seal_apply_setting_outsend_info';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'outsendInfoId';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * 一条签署信息，关联一条集成设置
     *
     * @return [object]               [关联关系]
     */
    public function signInfoBelongsToSetting()
    {
        return $this->belongsTo('App\EofficeApp\ElectronicSign\Entities\QiyuesuoSealApplySettingEntity', 'settingId', 'settingId');
    }

}
