<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarDefaultValueEntity;

class CalendarDefaultValueRepository extends BaseRepository
{
    public function __construct(CalendarDefaultValueEntity $entity) {
        parent::__construct($entity);
    }
    public function getDefalutValueByTypeId($typeId)
    {
        return $this->entity->where('type_id', $typeId)->first();
    }
}
