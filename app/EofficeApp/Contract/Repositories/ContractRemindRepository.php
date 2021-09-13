<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractRemindEntity;
use DB;
use Illuminate\Support\Arr;
/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractRemindRepository extends BaseRepository
{

    public function __construct(ContractRemindEntity $entity)
    {
        parent::__construct($entity);
    }

    public function insertManyData($data, $contract_id)
    {
        if (!$contract_id || empty($data)) {
            return false;
        }
        $table_fields = $this->getTableColumns();
        $insertData   = [];
        foreach ($data as $key => $value) {
            $validate = self::validateInput($value);
            if ($validate !== true) {
                continue;
            }
            foreach ($value as $filed => $field_value) {
                if (!in_array($filed, $table_fields)) {
                    unset($value[$filed]);
                } else {
                    $value[$filed] = $value[$filed] ?? '';
                }
            }
            $value['contract_t_id'] = $contract_id;
            $insertData[]           = $value;
        }
        return $this->insertMultipleData($insertData);
    }

    public function getContractRemindLists($param)
    {
        $default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['id' => 'asc'],
            'search'   => [],
        );

        $param = array_merge($default, $param);
        $query = $this->entity->wheres($param['search'])
            ->select($param['fields'])
            ->with('user')->with('contract')
            ->forPage($param['page'], $param['limit'])
            ->orders($param['order_by']);

        return $query->get();
    }

    public function updateContract($contractId, $contractRemind, $deleteRemind)
    {
        if (!empty($deleteRemind)) {
            $this->deleteByWhere(['contract_t_id' => [$contractId], 'id' => [$deleteRemind, 'in']]);
        }
        if (!empty($contractRemind)) {
            $insertData = $updateData = [];
            foreach ($contractRemind as $key => $value) {
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
                $contract_remind = $this->getTableColumns();
                $contract_remind = array_diff($contract_remind, ['created_at', 'deleted_at', 'updated_at']);
                foreach ($updateData as $key => $value) {
                    foreach ($contract_remind as $tableKey) {
                        $value[$tableKey] = isset($value[$tableKey]) ? $value[$tableKey] : '';
                    }
                    $uKey = array_diff(array_keys($value), $contract_remind);
                    $temp = Arr::except($value, $uKey);
                    if (!empty($temp['run_id'])) {
                        $temp['run_id'] = implode(',', $temp['run_id']);
                    }
                    unset($temp['id']);
                    $this->updateData($temp, ['id' => $value['id']]);
                }
            }
        }
        return true;
    }

    public static function validateInput(array &$data)
    {
        if (!isset($data['user_id']) || !$data['user_id']) {
            return ['code' => ['0x066011', 'contract']];
        }
        if (!isset($data['content']) || !$data['content']) {
            return ['code' => ['0x066012', 'contract']];
        }
        if (!isset($data['remind_date']) || !$data['remind_date']) {
            return ['code' => ['0x066010', 'contract']];
        }
        $data['remarks'] = $data['remarks'] ?? '';
        return true;
    }

    public static function validateImportInput(array &$data)
    {
        if (!isset($data['user_id']) || !$data['user_id']) {
            return trans('contract.0x066011');
        }
        if (!isset($data['content']) || !$data['content']) {
            return trans('contract.0x066012');
        }
        if (!isset($data['remind_date']) || !$data['remind_date']) {
            return trans('contract.0x066010');
        }
        $data['remarks'] = $data['remarks'] ?? '';
    }


    public static function getRimindList($search){
        $query = DB::table('contract_t_remind_remind as a')
            ->select('a.*','b.remind_id','b.contract_t_id','c.title')->where($search);;
        $query = $query->join('contract_t_remind as b', 'b.remind_id','=', 'a.remind_id');
        $query = $query->join('contract_t as c', 'c.id','=', 'b.contract_t_id');
        return $query->get()->toArray();
    }
}
