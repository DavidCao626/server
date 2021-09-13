<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceLeaveEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceLeaveRepository extends BaseRepository
{
	use AttendanceTrait;
    private $defaultParams;
    public function __construct(AttendanceLeaveEntity $entity)
    {
            parent::__construct($entity);
    $this->orderBy = ['leave_start_time' => 'desc'];
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
        return $this->entity->select(['user_id'])->distinct('user_id')->where('leave_start_time','<=',$endDate)->where('leave_end_time','>=',$startDate)->get()->toArray();
    }

    public function getAttendLeave($startDate, $endDate, $userId)
    {
        return $this->entity->where('leave_start_time','<=',$endDate)->where('leave_end_time','>=',$startDate)->where('user_id', $userId)->first();
    }
    public function getAttendLeaves($startDate, $endDate, $userId)
    {
        return $this->entity->where('leave_start_time','<=',$endDate)->where('leave_end_time','>=',$startDate)->where('user_id', $userId)->get();
    }
    public function getLeaveRecordsByDateScopeAndUserIds($startDate, $endDate, $userIds)
    {
        return $this->entity->where('leave_start_time','<=',$endDate)->where('leave_end_time','>=',$startDate)->whereIn('user_id', $userIds)->get();
    }
    /**
     * 获取所有的请假类型
     */
    public function getAttendAllLeaveIds()
    {
        return $this->entity->select('vacation_id')->groupBy('vacation_id')->get()->toArray();
    }

    public function getList($params, $relation = false)
    {
        $params = array_merge($this->defaultParams, $params);
        $query = $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit']);
        if ($relation) {
            $query->with('hasOneBackLeaveRecord');
            $query->with(['hasOneLeaveRecord' => function ($query) {
                $query->withTrashed();
            }]);
        }
        return $query->get();
    }
}