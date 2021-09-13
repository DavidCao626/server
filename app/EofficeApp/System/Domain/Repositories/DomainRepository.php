<?php

namespace App\EofficeApp\System\Domain\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Domain\Entities\DomainEntity;

/**
 * @域集成资源库类
 *
 * @author niuxiaoke
 */
class DomainRepository extends BaseRepository
{
    public function __construct(DomainEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getDataByWhere($where)
    {
        return $this->entity->wheres($where)->get();
    }

    public function getDomainInfo()
    {
    	return $this->entity->first();
    }
}
