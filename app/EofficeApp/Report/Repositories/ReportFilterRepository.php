<?php

namespace App\EofficeApp\Report\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Report\Entities\ReportFilterEntity;

class ReportFilterRepository extends BaseRepository {

    public function __construct(ReportFilterEntity $entity) {
        parent::__construct($entity);
    }

    public function getChartFilter($params)
    {
        $query = $this->entity;
        if (isset($params['datasource_id']) || !empty($params['datasource_id'])) {
            $query = $query->leftJoin('report_chart', 'report_chart.chart_id', '=', 'report_filter.chart_id')->where('datasource_id', $params['datasource_id']);
        }
        if (isset($params['chart_id']) || !empty($params['chart_id'])) {
            $query = $query->where('chart_id', $params['chart_id']);
        }
        return $query->get();
    }

}
