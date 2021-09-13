<?php
namespace app\EofficeApp\Charge\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 费用权限实体
 *
 */
class ChargePermissionEntity extends BaseEntity
{
    /** @var string $table 定义实体表 */
    public $table = 'charge_permission';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'id';

    /** @var bool 表明模型是否应该被打上时间戳 */
    public $timestamps = false;
}
