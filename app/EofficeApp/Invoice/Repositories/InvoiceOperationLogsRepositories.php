<?php


namespace App\EofficeApp\Invoice\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Invoice\Entities\InvoiceOperationLogsEntities;

class InvoiceOperationLogsRepositories extends BaseRepository
{
    public $invoiceLogRelatedInvoiceIdRepositories;
    public function __construct(InvoiceOperationLogsEntities $entity)
    {
        parent::__construct($entity);
        $this->invoiceLogRelatedInvoiceIdRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceLogRelatedInvoiceIdRepositories';
    }

    public function getCount($param = [])
    {
//        $query = $this->entity;
        $param['response'] = 'count';
//        $query = $this->getParseWhere($query, $param);
        return $this->getList($param);
    }

    public function getList($param = [])
    {
        $response = $param['response'] ?? 'array;';
        $default = [
            'page' => 0,
            'order_by' => ['invoice_id' => 'desc'],
            'limit' => 10,
            'fields' => ['invoice_operation_logs.*'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
//        $query = $query->with(['user' => function ($query) {
//            $query->select(['user_id', 'user_name']);
//        }])->with(['flow' => function ($query) {
//            $query->select(['run_id', 'run_name', 'flow_run.flow_id', 'flow_name'])->leftJoin('flow_type', 'flow_type.flow_id', '=', 'flow_run.flow_id');
//        }]);
        $query = $query->addSelect(['user.user_id', 'user_name', 'flow_run.run_id', 'run_name', 'flow_run.flow_id', 'flow_name'])
            ->leftJoin('user', 'user.user_id', '=', 'invoice_operation_logs.creator')
            ->leftJoin('flow_run', 'flow_run.run_id', '=', 'invoice_operation_logs.run_id')
            ->leftJoin('flow_type', 'flow_type.flow_id', '=', 'flow_run.flow_id');
        $query = $this->getParseWhere($query, $param);
        // 发票号码及代码关键字搜索处理 无相关数据是直接返回
        if (!$query) {
            return [];
        }
        if (isset($param['order_by'])) {
            if (isset($param['order_by']['created_at'])) {
                $param['order_by']['invoice_operation_logs.created_at'] = $param['order_by']['created_at'];
                unset($param['order_by']['created_at']);
            }
            $query = $query->orders($param['order_by']);
        }
        if ($response == 'count') {
            return $query->count();
        }
        return  $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 查询条件解析 where条件解析
     *
     * @param array $param 查询条件
     *
     * @return mixed
     *
     * @author [dosy]
     */
    public function getParseWhere($query, $param)
    {
        $search = $param['search'] ?? [];
        if ($search) {
//             高级查询处理
            $createdAt = $search['created_at'] ?? [];
            if ($createdAt) {
                $search['invoice_operation_logs.created_at'] = $search['created_at'];
                unset($search['created_at']);
            }
            $createdAtAdvance = $search['created_at_advance'] ?? [];
            if ($createdAtAdvance) {
                unset($search['created_at_advance']);
                $startDate = $createdAtAdvance['startDate'] . '00:00:00';
                $endDate = $createdAtAdvance['endDate'] . '23:59:59';
                $query = $query->where('invoice_operation_logs.created_at', '>=', date('Y-m-d H:i:s', strtotime($startDate)))->where('invoice_operation_logs.created_at', '<=', date('Y-m-d H:i:s', strtotime($endDate)));
            }
            $code = $search['code'] ?? '';
            if (isset($search['creator'])) {
                $search['invoice_operation_logs.creator'] = $search['creator'];
                unset($search['creator']);
            }
            // 通过发票号码或代码查询
            if ($code) {
                unset($search['code']);
                $invoiceIds = app($this->invoiceLogRelatedInvoiceIdRepositories)->getInvoiceIds($code);
                if ($invoiceIds) {
                    $search['invoice_id'] = [$invoiceIds, 'in'];
                } else {
                    return false;
                }
            }
            $query = $query->wheres($search);
        }
        return $query;
    }

    public function AddInvocieLogs($data)
    {
        if (!$data) {
            return false;
        }
        return $this->entity->insert($data);
    }

    public function getLog($logId)
    {
        return $this->entity
            ->with(['user' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->with(['flow' => function ($query) {
            $query->select(['run_id', 'run_name', 'flow_run.flow_id', 'flow_name'])->leftJoin('flow_type', 'flow_type.flow_id', '=', 'flow_run.flow_id');
        }])->where('log_id', $logId)->first();
    }
}