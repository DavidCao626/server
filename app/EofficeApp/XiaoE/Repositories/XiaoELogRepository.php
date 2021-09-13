<?php

namespace App\EofficeApp\XiaoE\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\XiaoE\Entities\XiaoELogEntity;

class XiaoELogRepository extends BaseRepository
{

    public function __construct(XiaoELogEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 监控数据列表
     * @param $params
     * @return mixed
     */
    public function getMonitoringList($params)
    {
        $defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['id' => 'desc'],
            'search' => []
        ];

        $params = array_merge($defaultParams, $params);

        return $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get();
    }

    /**
     * 监控记录数量
     * @param $params
     * @return mixed
     */
    public function getMonitoringCount($params)
    {
        $search = isset($params['search']) ? $params['search'] : [];

        return $this->entity->wheres($search)->count();
    }

    /**
     * 统计使用意图按排名统计
     */
    public function countIntenttionUsed($params)
    {
        $search = isset($params['search']) ? $params['search'] : [];
        return $this->entity
            ->selectRaw('count(intention_name) as count,intention_name')
            ->wheres($search)
            ->groupBy('intention_name')
            ->orderBy('count', 'asc')
            ->get()->toArray();
    }

    /**
     * 统计使用意图按排名统计
     */
    public function countFromPlatfrom($params)
    {
        $search = isset($params['search']) ? $params['search'] : [];
        return $this->entity
            ->selectRaw('count(platform) as count,platform')
            ->wheres($search)
            ->groupBy('platform')
            ->orderBy('count', 'desc')
            ->get()->toArray();
    }

    /**
     * 统计使用按天分类
     */
    public function countUsedByDay($params)
    {
        $search = isset($params['search']) ? $params['search'] : [];
        return $this->entity
            ->selectRaw('count(intention_name) as count,date,intention_name')
            ->wheres($search)
            ->groupBy('intention_name')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->orderBy('count', 'desc')
            ->get()->toArray();
    }
}
