<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarJoinModuleConfigEntity;

class CalendarJoinModuleConfigRepository extends BaseRepository
{
    public function __construct(CalendarJoinModuleConfigEntity $entity) {
        parent::__construct($entity);
    }
    public function getAllModuleConfig()
    {
        return $this->entity->get();
    }
    public function getModuleConfigByFrom($from)
    {
        return $this->entity->where('from', $from)->first();
    }
}
