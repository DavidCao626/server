<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSimpleRecordsEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceSimpleRecordsRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendanceSimpleRecordsEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['user_id' => 'asc', 'sign_date' => 'desc', 'sign_time' => 'desc'];
    }
    
    public function getMoreRecords($year, $month, array $userIds, array $fields = [], array $orderBy = [])
    {
        if (empty($userIds)) {
            return [];
        }
        $query = $this->entity;
        if (!empty($fields)) {
            $query = $query->select($fields);
        }
        $query = $query->where('year', $year);
        if ($month) {
            $query = $query->where('month', $month);
        }
        $orderBy = empty($orderBy) ? $this->orderBy : $orderBy;
        return $query->whereIn('user_id', $userIds)->orders($orderBy)->get();
    }

    public function getMoreRecordsByDate($startDate, $endDate, array $userIds, array $fields = [], array $orderBy = [])
    {
        if (empty($userIds)) {
            return [];
        }
        $query = $this->entity;
        if (!empty($fields)) {
            $query = $query->select($fields);
        }
        $orderBy = empty($orderBy) ? $this->orderBy : $orderBy;
        return $query->whereIn('user_id', $userIds)->whereBetween('sign_date', [$startDate, $endDate])->orders($orderBy)->get();
    }

    public function getOneUserOneDaySignRecords(string $signDate, string $userId)
    {
        return $this->entity->where('sign_date', $signDate)->where('sign_type','!=', 0)->where('user_id', $userId)->get();
    }

    public function getRecordsList($params)
    {
        $defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => $params['order_by'] ?? $this->orderBy,
            'search' => []
        ];

        $params = array_merge($defaultParams, $params);

        return $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get();
    }

    public function getRecordsTotal($params)
    {
        if (isset($params['search'])) {
            return $this->entity->wheres($params['search'])->count();
        } else {
            return $this->entity->count();
        }
    }
}