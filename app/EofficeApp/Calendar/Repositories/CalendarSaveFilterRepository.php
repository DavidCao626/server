<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarSaveFilterEntity;

class CalendarSaveFilterRepository extends BaseRepository
{
    public function __construct(CalendarSaveFilterEntity $entity) {
        parent::__construct($entity);
    }
    
    public function getData($userId) {
        return $this->entity->where('user_id', $userId)->first();
    }
    
}
