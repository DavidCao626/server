<?php
namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 电子签章-契约锁集成物理相关资源同步日志表
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class QiyuesuoSyncRelatedResourceLogEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_sync_related_resource_log';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'log_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
}
