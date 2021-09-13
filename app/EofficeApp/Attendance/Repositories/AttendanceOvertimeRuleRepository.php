<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOvertimeRuleEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;

class AttendanceOvertimeRuleRepository extends BaseRepository
{
    use AttendanceTrait;
    private $defaultParams;

    public function __construct(AttendanceOvertimeRuleEntity $entity)
    {
        parent::__construct($entity);
        $this->defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['id' => 'desc'],
            'search' => []
        ];
    }

    public function getRules($params, $withScheduling = false)
    {
        $params = array_merge($this->defaultParams, $params);
        $query = $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit']);
        if ($withScheduling) {
            $query = $query->with('scheduling');
        }
        return $query->get();
    }

    /**
     * 获取方案总数
     */
    public function getRulesTotal($params)
    {
        $search = isset($params['search']) ? $params['search'] : [];

        return $this->entity->wheres($search)->count();
    }
}