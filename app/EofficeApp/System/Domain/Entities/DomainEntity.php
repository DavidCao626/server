<?php
namespace App\EofficeApp\System\Domain\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * @域集成实体类
 *
 * @author niuxiaoke
 */
class DomainEntity extends BaseEntity
{
    protected $table = 'domain';

    public $primaryKey = 'id';

    public $timestamps = false;
}
