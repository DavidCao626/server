<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarOuterEntity;
/**
 * @日程资源库类
 *
 *
 */
class CalendarOuterRepository extends BaseRepository
{
    public function __construct(CalendarOuterEntity $entity) 
    {
        parent::__construct($entity);
    }
    public function removeOuterByCalendarId($allCalendarId) 
    {
        return $this->entity->whereIn('calendar_id', $allCalendarId)->delete();
    }
    public function getOuterBySourceId($sourceId, $sourceFrom) 
    {
        return $this->entity->where('source_id', $sourceId)->where('source_from', $sourceFrom)->get()->toArray();
    }
}
