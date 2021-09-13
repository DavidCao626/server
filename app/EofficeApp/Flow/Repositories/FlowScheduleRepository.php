<?php

namespace App\EofficeApp\Flow\Repositories;

use Schema;
use App\EofficeApp\Flow\Entities\FlowScheduleEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 流程定时触发设置
 *
 * @author zyx
 *
 * @since  20200408
 */
class FlowScheduleRepository extends BaseRepository
{
    public function __construct(FlowScheduleEntity $entity) {
        parent::__construct($entity);
    }

    // 获取指定流程的定时触发配置
    public function getFlowSchedulesByWhere($where) {
        return $this->entity->where($where)->orderBy('id', 'ASC')->get();
    }

    /**
     * 获取所有流程的定时触发配置
     *
     * @author zyx
     * @since 20200416
     * @return void
     */
    public function getAllFlowScheduleInfo($where) {
        $query = $this->entity
                ->where($where)
                ->with(['flowScheduleHasOneType' => function ($query) {
                    $query->select('flow_id', 'flow_type', 'flow_sort', 'is_using');
                }])
                ->get();
        return $query->toArray();
    }
}
