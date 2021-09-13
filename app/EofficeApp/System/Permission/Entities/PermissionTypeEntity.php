<?php

namespace App\EofficeApp\System\Permission\Entities;

use App\EofficeApp\Base\BaseEntity;

class PermissionTypeEntity extends BaseEntity
{
    /** @var string 权限组表 */
    public $table = 'system_permission_type';

    /** @var string 主键 */
    public $primaryKey = 'type_id';

    public $timestamps = false;
}
