<?php

namespace App\EofficeApp\HighMeterIntegration\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 契约锁业务分类同步数据模型
 *
 * @author yuanmenglin
 * @since 
 */
class HighMeterBaseUrlSettingEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'high_meter_base_url_setting';

    /**
     * 主键
     *
     * @var string
     */
     public $primaryKey = 'id';

    /**
     * 执行模型是否自动维护时间戳.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
}
