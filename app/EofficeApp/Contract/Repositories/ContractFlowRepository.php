<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractFlowEntity;
use DB;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractFlowRepository extends BaseRepository
{

    const TABLE_FLOW = 'flow_run';

    public function __construct(ContractFlowEntity $entity)
    {
        parent::__construct($entity);
    }

    public function insertManyData($data, $contract_id)
    {
        if (!$contract_id || empty($data)) {
            return false;
        }
        $run_ids = $result = [];
        foreach ($data as $key => $run_id) {
            $run_ids[] = $run_id;
        }

        $lists     = DB::table(self::TABLE_FLOW)->select(['run_name', 'run_id'])->whereIn('run_id', $run_ids)->get();
        $run_lists = [];
        if (!empty($lists)) {
            foreach ($lists as $key => $item) {
                $run_lists[$item->run_id] = $item->run_name;
            }
        }
        foreach ($data as $key => $run_id) {
            $temp['contract_t_id'] = $contract_id;
            $temp['run_id']        = $run_id;
            $temp['run_name']      = $run_lists[$run_id] ?? '';
            $result[]              = $temp;
        }
        return $this->insertMultipleData($result);
    }

    public function updateContract($contractId, $contractFlow, $flag = true)
    {
        if ($flag) {
            $this->deleteByWhere(['contract_t_id' => [$contractId]]);
        }
        if (!empty($contractFlow)) {
            $this->insertManyData($contractFlow, $contractId);
        }
        return true;
    }

    public static function getFlows(array $runIds)
    {
        $result = [];
        if (empty($runIds)) {
            return $result;
        }
        foreach ($runIds as $runId) {
            $result[$runId] = '';
        }
        $lists = DB::table(self::TABLE_FLOW)->select(['run_id', 'flow_id'])->whereIn('run_id', $runIds)->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $index => $item) {
            $result[$item->run_id] = $item->flow_id;
        }
        return $result;
    }

    public static function getRunIds($contract_id){
        return DB::table('contract_t_flow')->where('contract_t_id', $contract_id)->whereNull('deleted_at')->pluck('run_id')->toArray();
    }
}
