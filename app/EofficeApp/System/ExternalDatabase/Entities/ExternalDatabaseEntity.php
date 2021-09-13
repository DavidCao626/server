<?php

namespace App\EofficeApp\System\ExternalDatabase\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * webhook Entity类:提供webhook 数据表实体
 *
 * @author qishaobo
 *
 * @since  2016-07-11 创建
 */
class ExternalDatabaseEntity extends BaseEntity
{
    /**
     * webhook表
     *
     * @var string
     */
	public $table = 'external_database';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'database_id';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = true;
}
