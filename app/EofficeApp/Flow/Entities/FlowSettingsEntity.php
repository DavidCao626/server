<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程设置实体
 *
 * @author 缪晨晨
 *
 * @since  2019-10-14 创建
 */
class FlowSettingsEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table        = 'flow_settings';
    public $timestamps   = false;
    public $primaryKey   = 'param_key';
    public $incrementing = false;
}
