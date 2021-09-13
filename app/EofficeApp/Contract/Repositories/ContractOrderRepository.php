<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractOrderEntity;
use DB;
use Illuminate\Support\Arr;
/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractOrderRepository extends BaseRepository
{

    public function __construct(ContractOrderEntity $entity)
    {
        parent::__construct($entity);
    }

    public function insertManyData($data, $contract_id)
    {
        if (!$contract_id || empty($data)) {
            return false;
        }
        $tableFields = $this->getTableColumns();
        $insertData = [];
        foreach ($data as $key => $value) {
            $validate = self::validateInput($value);
            if ($validate !== true) {
                continue;
            }
            foreach ($value as $filed => $field_value) {
                if (!in_array($filed, $tableFields)) {
                    unset($value[$filed]);
                } else {
                    $value[$filed] = $value[$filed] ?? '';
                }
            }
            $runids = '';
            if (isset($value['run_id'])) {
                $runids = is_array($value['run_id']) ? implode(',', $value['run_id']) : $value['run_id'];
            }
            $value['run_id'] = $runids;
            $value['contract_t_id'] = $contract_id;
            $insertData[] = $value;
        }
        return $this->insertMultipleData($insertData);
    }

    public function updateContract($contractId, $contractOrder, $deleteOrder)
    {
        if (!empty($deleteOrder)) {
            $this->deleteByWhere(['contract_t_id' => [$contractId], 'id' => [$deleteOrder, 'in']]);
        }
        if (!empty($contractOrder)) {
            $insertData = $updateData = [];
            foreach ($contractOrder as $key => $value) {
                if (!isset($value['id'])) {
                    $insertData[] = $value;
                } else {
                    $updateData[] = $value;
                }
            }
            if (!empty($insertData)) {
                $this->insertManyData($insertData, $contractId);
            }
            if (!empty($updateData)) {
                // 获取表结构
                $orders = $this->getTableColumns();
                $orders = array_diff($orders, ['created_at', 'deleted_at', 'updated_at']);
                foreach ($updateData as $key => $value) {
                    foreach ($orders as $table_key) {
                        $value[$table_key] = isset($value[$table_key]) ? $value[$table_key] : '';
                    }
                    $unique_key          = array_diff(array_keys($value), $orders);
                    $temp           = Arr::except($value, $unique_key);
                    $temp['run_id'] = (isset($temp['run_id']) && $temp['run_id']) ? implode(',', $temp['run_id']) : '';
                    unset($temp['id']);
                    $this->updateData($temp, ['id' => $value['id']]);
                }
            }
        }
        return true;
    }

    public function parseResults(&$orders)
    {
        if (empty($orders)) {
            return true;
        }
        $runIds = [];
        foreach ($orders as $key => $value) {
            $temp = explode(',', $value['run_id']);
            if (!empty($temp)) {
                $orders[$key]['run_id'] = $temp;
                $runIds                 = array_merge($runIds, $temp);
            }
        }
        if (empty($runIds)) {
            return true;
        }
        $runIds   = array_unique($runIds);
        $runLists = $this->getSimpleFlowRun($runIds);
        foreach ($orders as $key => $value) {
            if (empty($value['run_id'])) {
                continue;
            }
            $temp_lists = [];
            foreach ($value['run_id'] as $temp_run_id) {
                if (isset($runLists[$temp_run_id])) {
                    $temp_lists[] = $runLists[$temp_run_id];
                }
            }
            $orders[$key]['run_lists'] = $temp_lists;
        }
        return true;
    }

    public function getSimpleFlowRun($runId)
    {
        if (empty($runId)) {
            return false;
        }
        $query = DB::table('flow_run')->select(['run_name', 'run_id', 'flow_id']);
        if (is_array($runId)) {
            $list = $query->whereIn('run_id', $runId)->get();
            if (count($list) > 0) {
                $runName = [];
                foreach ($list as $item) {
                    $runName[$item->run_id] = $item;
                }
                return $runName;
            }
        } else {
            return $query->where('run_id', $runId)->first();
        }

        return false;
    }

    public static function validateInput(array $data)
    {
        if (!isset($data['product_id']) || !$data['product_id']) {
            return ['code' => ['0x066013', 'contract']];
        }
        if (!isset($data['number']) || !$data['number']) {
            return ['code' => ['is_number_err', 'contract']];
        }
        if(isset($data['number']) && $data['number']){
            if(!is_numeric($data['number'])){
                return ['code' => ['is_number_err', 'contract']];
            }
            if($data['number'] <= 0){
                return ['code' => ['is_number_err', 'contract']];
            }
        }
        if (!isset($data['shipping_date']) || !$data['shipping_date']) {
            return ['code' => ['0x066014', 'contract']];
        }
        return true;
    }
    public static function validateImportInput(array $data)
    {
        if (!isset($data['product_id']) || !$data['product_id']) {
            return trans('contract.0x066013');
        }
        if (!isset($data['number']) || !$data['number']) {
            return trans('contract.is_number_err');
        }
        if(isset($data['number']) && $data['number']){
            if(!is_numeric($data['number'])){
                return trans('contract.is_number_err');
            }
            if($data['number'] <= 0){
                return trans('contract.is_number_err');
            }
        }
        if (!isset($data['shipping_date']) || !$data['shipping_date']) {
            return trans('contract.0x066014');
        }
    }
}
