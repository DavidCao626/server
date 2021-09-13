<?php

namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoContractLogEntity;

class QiyuesuoContractLogRepository extends BaseRepository
{
    public function __construct(QiyuesuoContractLogEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增日志
     */
    public function addLog($param)
    {
        $resAdd = $this->entity->create($param);
        if ($resAdd) {
            return true;
        }
        return false;
    }

    public function getCount($param = [])
    {
        $query = $this->entity;
        $query = $this->getParseWhere($query, $param);
        return $query->count();
    }

    public function getList($param = [])
    {
        $default = [
            'page' => 0,
            'order_by' => ['id' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity;
        $param['fields'] = ['qiyuesuo_contract_log.*'];
        if (isset($param['fields'])) {
            $query = $query->select($param['fields'])
            ->with(["hasOneFlowType"=>function($query){
                $query->select("flow_id","flow_name");
             }])
            ->with(['hasOneRunFlowType' => function($query){
                $query->select("run_id","run_name");
             }])
            ->with(["hasOneQiyuesuoServer"=>function($query){
                $query->select("serverId","serverName","serverType");
            }]);
        }
        $query->AddSelect(['user.user_name as creator_name'])->leftJoin('user', 'user.user_id', '=', 'qiyuesuo_contract_log.userid');
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
            if (isset($search['run_name'])) {
                $runName = $search['run_name'];
                unset($search['run_name']);
                $query = $query->leftJoin('flow_run', function($join) use ($runName) {
                    $join->on('flow_run.run_id', '=', 'qiyuesuo_contract_log.runId');
                })
                    ->where('flow_run.run_name', 'like', '%'. $runName.'%');
            }
            $query = $query->wheres($search);
        }
        return $query;
    }
}
