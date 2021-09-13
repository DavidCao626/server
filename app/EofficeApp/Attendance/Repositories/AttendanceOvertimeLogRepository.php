<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOvertimeLogEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;

class AttendanceOvertimeLogRepository extends BaseRepository
{
    use AttendanceTrait;

    private $defaultParams;


    public function __construct(AttendanceOvertimeLogEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['log_id' => 'desc'];
        $this->defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => $this->orderBy,
            'search' => []
        ];
    }
    public function getUserIdByDateRange($startDate, $endDate)
    {
        return $this->entity->select(['user_id'])->distinct('user_id')->where('overtime_start_time','<=',$endDate)->where('overtime_end_time','>=',$startDate)->get()->toArray();
    }
    /**
     * 主要用于我的记录时间戳和日历上的数据展示
     * @param $startDate
     * @param $endDate
     * @param $userId
     * @return mixed
     */
    public function getAttendOvertimes($startDate, $endDate, $userId)
    {
        $records = $this->entity
            ->where('overtime_start_time', '<=', $endDate)
            ->where('overtime_end_time', '>=', $startDate)
            ->where('days', '>', 0)
            ->orderBy('days', 'asc')//时间轴上加班审批+打卡重叠，优先展示有天数的记录
            ->where('user_id', $userId)
            ->get();
        foreach ($records as $record) {
            $record->overtime_days = $record->days;
            $record->overtime_hours = $record->hours;
            if ($record->overtime_flow) {
                $extra = json_decode($record->overtime_flow, true);
                $record->overtime_extra = $record->overtime_flow;
                $record->overtime_reason = $extra['overtime_reason'];
            }
        }
        return $records;
    }

    public function getList($params, $relation = false)
    {
        ini_set('serialize_precision', 16);//精度丢失
        $params = array_merge($this->defaultParams, $params);
        $query = $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit']);
        if ($relation) {
            $query->with(['hasManyTime' => function ($query) {
                $query->orderBy('date', 'desc');
            }]);
        }
        return $query->get();
    }
}