<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarShareUserRelationEntity;
/**
 * @日程资源库类
 *
 *
 */
class CalendarShareUserRelationRepository extends BaseRepository
{
    public function __construct(CalendarShareUserRelationEntity $entity) 
    {
        parent::__construct($entity);
    }
    public function removeUserByCalendarId($allCalendarId) 
    {
        return $this->entity->whereIn('calendar_id', $allCalendarId)->delete();
    }
    public function getListById($calendarId)
    {
        return $this->entity->where('calendar_id', $calendarId)->get()->toArray();
    }
    public function getCalendarShareUserRelationByIds($calendarIds)
    {
        return $this->entity->whereIn('calendar_id', $calendarIds)->get()->toArray();
    }
}
