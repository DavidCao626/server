<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarSetEntity;
/**
 * @日程资源库类
 *
 *
 */
class CalendarSetRepository extends BaseRepository
{
    public function __construct(CalendarSetEntity $entity) {
        parent::__construct($entity);
    }

    public function getCalendarSetInfo($key = null) {
        $query = $this->entity->select(['*']);
        if ($key) {
            return $query->where('calendar_set_key', $key)->get();
        }
        return $query->get();
    }

    public function updateSetData($data, $where) {
        return $this->entity
                        ->wheres($where)
                        ->update($data);
    }

    public function getCalendarSettingInfo($param = []) {
        return $this->entity
                        ->select(['calendar_set_key', 'calendar_set_value'])
                        ->wheres($param)
                        ->first();
    }

}
