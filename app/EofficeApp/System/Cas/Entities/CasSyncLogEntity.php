<?php

namespace App\EofficeApp\System\Cas\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * cas_sync_log Entity类:提供cas_sync_log 数据表实体
 *
 * @author 缪晨晨
 *
 * @since  2018-01-29 创建
 */
class CasSyncLogEntity extends BaseEntity
{
    use SoftDeletes;
    /**
     * cas_sync_log表
     *
     * @var string
     */
	public $table = 'cas_sync_log';
}
