<?php


namespace App\EofficeApp\Invoice\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Invoice\Entities\InvoiceFlowSettingEntities;

class InvoiceFlowSettingRepositories extends BaseRepository
{
    public function __construct(InvoiceFlowSettingEntities $entity)
    {
        parent::__construct($entity);
    }

    public function getCount($param = [])
    {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getList($param);
    }

    public function getList($param = [])
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['setting_id' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->with(['flow' => function ($query) {
                $query->select(['flow_id', 'flow_name']);
            }])
            ->with(['user' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }])
            ->with(['actions' => function ($query) {
                $query->select(['invoice_flow_node_action_setting.*']);
            }]);
        // 分组参数
        if (isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        // 解析原生 where
        if (isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if (isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        if (isset($param['withTrashed']) && $param['withTrashed']) {
            $query->withTrashed();
        }
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            if (isset($param['groupBy'])) {
                return $query->get()->count();
            } else {
                return $query->count();
            }
        } else if ($param["returntype"] == "object") {
            return $query->get();
        } else if ($param["returntype"] == "first") {
            if (isset($param['groupBy'])) {
                return $query->get()->first();
            } else {
                return $query->first();
            }
        }
    }

    public function getOneSetting($settingId, $where = [])
    {
        $query = $this->entity;
        if ($settingId) {
            $query = $query->where(['setting_id' => $settingId]);
        }
        if (count($where, 1) == count($where)) {
            $query = $query->where($where);
        } else {
            $query = $query->wheres($where);
        }
       return $query->with(['flow' => function ($query) {
                $query->select(['flow_id', 'flow_name']);
            }])
            ->with(['user' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }])
            ->with(['actions' => function ($query) {
                $query->select(['action_setting_id', 'setting_id', 'node_id', 'action', 'trigger_time', 'back', 'workflow_id'])->orderBy('action_setting_id', 'asc');
            }])->first();
    }

    public function getOneSettingByFlowId($settingId, $flowId)
    {
        $query = $this->entity;
        if ($settingId) {
            $query = $query->where('setting_id', '<>', $settingId);
        }
        if ($flowId) {
            $query = $query->where('workflow_id', $flowId);
        }
        return $query->first();
    }

    public function getSettingsOnlyIdName() 
    {
        return $this->entity
            ->select(['flow_id', 'flow_name', 'is_default'])
            ->leftJoin('flow_type', 'flow_id', '=','workflow_id')
            ->where(['enable' => 1])
            ->get()->toArray();
    }
}