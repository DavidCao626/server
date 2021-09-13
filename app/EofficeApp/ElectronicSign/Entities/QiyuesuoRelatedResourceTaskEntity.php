<?php
namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 电子签章-契约锁集成物理相关资源任务表
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class QiyuesuoRelatedResourceTaskEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_related_resource_task';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'task_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
}
