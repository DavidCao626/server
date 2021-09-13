<?php

namespace App\EofficeApp\System\Permission\Entities;

use App\EofficeApp\Base\BaseEntity;

class PermissionGroupEntity extends BaseEntity
{
    /** @var string 权限组表 */
    public $table = 'system_permission_group';

    /** @var string 主键 */
    public $primaryKey = 'group_id';
}
