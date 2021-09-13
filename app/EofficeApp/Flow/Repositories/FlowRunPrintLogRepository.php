<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowRunPrintLogEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程打印日志
 */
class FlowRunPrintLogRepository extends BaseRepository 
{
    public function __construct(FlowRunPrintLogEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取流程打印日志
     *
     * @param [type] $params
     * @return void
     */
    public function getFlowRunPrintLogList(array $params) {
        $query = $this->entity;

        $search = [];
        if (isset($params['search'])) {
            $search = $params['search'];
            unset($params['search']);
        }
        
        // 流程id，必填
        $params['search']['flow_run_id'] = [$params['run_id']];
        unset($params['run_id']);

        $printTimeRange = [];
        // 查询条件处理
        if (count($search)) {
            // 打印时间
            if (isset($search['print_date']) && $search['print_date']) {
                $printTimeRange['startTime'] = date('Y-m-d 00:00:00', strtotime($search['print_date']['startDate']));
                $printTimeRange['endTime'] = date('Y-m-d 23:59:59', strtotime($search['print_date']['endDate']));
            }
            // 打印人id
            if (isset($search['print_user_id']) && $search['print_user_id']) {
                $params['search']['print_user_id'] = [array_filter(array_values($search['print_user_id'])), 'in'];
            }
            // 打印人名称，模糊搜索
            if (isset($search['print_user_name']) && $search['print_user_name']) {
                $params['print_user_name'] = $search['print_user_name'];
            }
            // 打印类型
            if (isset($search['print_type']) && $search['print_type']) {
                $params['search']['print_type'] = [$search['print_type']];
            }
        }

        // 默认查询条件
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['print_time' => 'desc'],
            'returnType' => 'list',
        ];

        // 查询条件合并
        $params = array_merge($default, array_filter($params));

        // 初始查询
        $query = $query->select($params['fields'])
                       ->wheres($params['search'])
                       ->orders($params['order_by'])
                       ->with(['flowRunPrintLogHasOneUser' => function ($query) { // 打印人信息
                            $query->select('user_id', 'user_name')->withTrashed();
                        }]);
                        
        // 时间范围条件
        if ($printTimeRange) {
            $query->whereBetween('print_time', [$printTimeRange['startTime'], $printTimeRange['endTime']]);
        }

        // 打印人姓名模糊搜索
        if (isset($params['print_user_name'])) {
            $query = $query->whereHas('flowRunPrintLogHasOneUser', function ($query) use($params) {
                $query->Where('user_name', 'like', '%' . $params['print_user_name'][0] . '%')    
                    ->orWhere('user_name_zm', 'like', '%' . $params['print_user_name'][0] . '%')
                    ->orWhere('user_name_py', 'like', '%' . $params['print_user_name'][0] . '%')->withTrashed();
            });
        }

        // 分页
        $query = $query->parsePage($params['page'], $params['limit']);
        // echo $query->toSql();die;
        switch ($params['returnType']) {
            case 'count':
                return $query->count();
            case 'list':
                return $query->get()->toArray();
        }
    }


    public function getFlowRunPrintLogListTotal($param = []) {
        $param["returnType"] = "count";
        $param["page"] = 0;
        return $this->getFlowRunPrintLogList($param);
    }
}