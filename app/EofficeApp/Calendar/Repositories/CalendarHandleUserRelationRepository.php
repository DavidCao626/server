<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarHandleUserRelationEntity;
/**
 * @日程资源库类
 *
 *
 */
class CalendarHandleUserRelationRepository extends BaseRepository
{
    public function __construct(CalendarHandleUserRelationEntity $entity) 
    {
        parent::__construct($entity);
    }
    public function checkCalendarConflict($calendarIds, $userIds) 
    {
       return $this->entity->whereIn('user_id', $userIds)->whereIn('calendar_id', $calendarIds)->where('calendar_status', 0)->get()->toArray();
    }
    public function deleteByCalendarId($calendarId)
    {
        return $this->entity->where('calendar_id', $calendarId)->delete();
    }
    public function isCalendarHandleUser($userId, $calendarId)
    {
        return $this->entity->where('calendar_id', $calendarId)->where('user_id', $userId)->count();
    }
    public function completeCalendar($param, $userId, $data) 
    {
        return $this->entity->where('calendar_id', $param['calendar_id'])->where('user_id', $userId)->update($data);
    }
    public function removeUserByCalendarId($allCalendarId) 
    {
        return $this->entity->whereIn('calendar_id', $allCalendarId)->delete();
    }
    public function getCalendarHanderUserRelationByIds($calendarIds)
    {
        return $this->entity->whereIn('calendar_id', $calendarIds)->get()->toArray();
    }
    public function getListById($calendarId)
    {
        return $this->entity->where('calendar_id', $calendarId)->get()->toArray();
    }
    public function multipleCompleteCalendar($param, $userId, $data) 
    {
        return $this->entity->where('calendar_id', $param['calendar_id'])->whereIn('user_id', $userId)->update($data);
    }
    public function getHandleDetail($calendarId, $userId)
    {
        return $this->entity->where('calendar_id', $calendarId)->where('user_id', $userId)->first();
    }
}
