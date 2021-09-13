<?php

namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoSealApplySettingEntity;

class QiyuesuoSealApplySettingRepository extends BaseRepository
{
    public function __construct(QiyuesuoSealApplySettingEntity $entity)
    {
        parent::__construct($entity);
    }

    //根据条件获取内容
    public function getDataById($id, $field = ["*"])
    {
        $result = $this->entity->select($field)->find($id);
        return $result;
    }

    public function getCount($param = [])
    {
        $param['return_type'] = 'count';
        return $this->getList($param);
    }

    public function getList($param = [])
    {
        $default = [
            'page' => 0,
            'order_by' => ['settingId' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields'])
                           ->with(["hasOneFlowType"=>function($query){
                               $query->select("flow_id","flow_name");
                             }])
                            ->with(["hasOneQiyuesuoServer"=>function($query){
                                $query->select("serverId","serverName","serverType");
                            }])
                            ->with(["hasManyOutsendInfo" => function($query) {
                                $query->select("settingId","nodeId","action","flowOutsendTimingArrival", "flowOutsendTimingSubmit")->where('type', 'physical_seal')->orderBy('outsendInfoId', 'asc');
                            }]);
        }
        $query = $this->getParseWhere($query, $param);
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }

        if (isset($param['return_type']) && $param['return_type'] == 'count') {
            return $query->count();
        } else {
            return $query
                ->parsePage($param['page'], $param['limit'])
                ->get()
                ->toArray();
        }
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
            if (isset($search['flow_name'])) {
                $flowName = $search['flow_name'];
                unset($search['flow_name']);
                $query = $query->leftJoin('flow_type', function($join) use ($flowName) {
                    $join->on('flow_type.flow_id', '=', 'qiyuesuo_seal_apply_setting.workflowId');
                })
                    ->where('flow_type.flow_name', 'like', '%'. $flowName.'%');
            }
            $query = $query->wheres($search);
        }
        return $query;
    }

    public function getSettingByFlowId($flowId)
    {
        $config = $this->entity->where('workflowId', $flowId)->with(['hasManyOutsendInfo' => function($query) {
            $query->select('settingId', 'flowId', 'nodeId', 'action', 'flowOutsendTimingArrival', 'flowOutsendTimingSubmit', 'back')->where('type', 'physical_seal')->orderBy('outsendInfoId', 'asc');
        }])->with(["hasOneQiyuesuoServer"=>function($query){
            $query->select("serverId","serverName","serverType");
        }])->first();
        return $config;
    }
}
