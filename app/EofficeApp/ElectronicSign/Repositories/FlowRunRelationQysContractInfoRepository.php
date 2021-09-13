<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\FlowRunRelationQysContractInfoEntity;

class FlowRunRelationQysContractInfoRepository extends BaseRepository
{
    public function __construct(FlowRunRelationQysContractInfoEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getByRunId($runId)
    {
        return $this->entity->where('runId', $runId)->first();
    }

    /**
     * 获取合同总个数
     *
     * @param array $param
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getCount(array $param)
    {
        $query = $this->entity;
        if ($param) {
            $query = $query->leftjoin('flow_run', 'flow_run.run_id', '=', 'flow_run_relation_qys_contract_info.runId')
                ->leftjoin('flow_type', 'flow_type.flow_id', '=', 'flow_run.flow_id');
        }
        $query = $this->getParseWhere($query, $param);
        return $query->count();
    }

    /**
     * 获取合同列表
     *
     * @param array $param
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getList(array $param)
    {
        $default = [
            'page' => 0,
            'order_by' => ['runId' => 'desc'],
            'limit' => 10,
            'fields' => ['flow_run_relation_qys_contract_info.*'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        // 获取关联数据  搜索条件按流程查询
        $query = $query->leftjoin('flow_run', 'flow_run.run_id', '=', 'flow_run_relation_qys_contract_info.runId')
            ->leftjoin('flow_type', 'flow_type.flow_id', '=', 'flow_run.flow_id')
            ->addSelect('flow_type.flow_id', 'flow_type.flow_name', 'flow_run.run_name');
        $query = $this->getParseWhere($query, $param);
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }

        return $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 查询条件解析
     *
     * @param [type] $query
     * @param [type] $param
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getParseWhere($query, $param)
    {
        if (isset($param['search'])) {
            $search = $param['search'];
            // 按定义流程id查询
            if (isset($search['flow_id']) && !empty($search['flow_id'])) {
                $query = $query->where('flow_run.flow_id', $search['flow_id']);
            }
            // 按运行流程名查询
            if (isset($search['run_name']) && !empty($search['run_name'])) {
                $query = $query->where('flow_run.run_name', $search['run_name']);
            }
        }
        return $query;
    }
}
