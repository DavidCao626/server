<?php
namespace App\EofficeApp\System\Domain\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * @域同步日志实体类
 *
 * @author niuxiaoke
 */
class DomainSyncLogEntity extends BaseEntity
{
    protected $table = 'ad_sync_logs';

    public $primaryKey = 'log_id';

    public $timestamps = false;
}
