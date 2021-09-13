<?php


namespace App\EofficeApp\Salary\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryFieldHistoryEntity;

class SalaryFieldHistoryRepository extends BaseRepository
{
    public function __construct(SalaryFieldHistoryEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getSalaryItems(array $param = [], $isAll = false)
    {
        $default = [
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['field_sort' => 'asc', 'created_at' => 'asc'],
        ];

        if (isset($param['search']) && !is_array($param['search'])) {
            $param['search'] = json_decode($param['search'], true);
        }

        $param = array_merge($default, array_filter($param));
        if (isset($param['order_by']) || isset($param['orders'])) {
            $param['order_by']['created_at'] = 'asc';
        }

        $query = $this->entity
            ->where('report_id', $param['report_id'])
            ->wheres($param['search'])
            ->orders($param['order_by']);

        if (isset($param['delete']) && $param['delete'] == 1) {
            $query->withTrashed();
        }

        if (!isset($param['getAll'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }

        return $query->get()->toArray();
    }

    public function getFieldCode($reportId, $file_name = '')
    {
        if ($file_name == '') {
            return $this->entity
                ->select(['field_code', 'field_default_set', 'field_source', 'field_decimal'])
                ->where('report_id', $reportId)
                ->get()->toArray();
        } else {
            return $this->entity->select('field_code')
                ->where('report_id', $reportId)
                ->where('field_name', $file_name)
                ->get()->toArray();
        }
    }

    public function getSalaryFieldHistoryListByWhere($where)
    {
        return $this->entity->wheres($where)->get();
    }

}
