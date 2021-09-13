<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractProjectEntity;
use DB;
use Illuminate\Support\Arr;
/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractProjectRepository extends BaseRepository
{

    const PAY_WAY_SELECT_FIELD  = 1002;
    const PAY_TYPE_SELECT_FIELD = 1101;

    public function __construct(ContractProjectEntity $entity)
    {
        parent::__construct($entity);
    }

    public function insertManyData($data, $contract_id)
    {
        if (!$contract_id || empty($data)) {
            return false;
        }
        $table_fields = $this->getTableColumns();
        $insertData   = $result = [];
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
            $runids = '';
            if (isset($value['run_id'])) {
                $runids = is_array($value['run_id']) ? implode(',', $value['run_id']) : $value['run_id'];
            }
            $value['pay_type']      = $value['pay_type'] ?? 0;
            $value['run_id']        = $runids;
            $value['contract_t_id'] = $contract_id;
            $insertData[]           = $value;
        }
        if($insertData){
            foreach ($insertData as $vo){
                $result[] = $this->insertData($vo);
            }
        }
        return $result;
//        return $this->insertMultipleData($insertData);
    }

    public function lists($contractIds)
    {
        return $this->entity->select(['contract_t_id', 'type', 'money', 'pay_way', 'pay_account', 'pay_time', 'run_id', 'remarks', 'pay_type', 'invoice_time'])->whereIn('contract_t_id', $contractIds)->get();
    }

    public function updateContract($contractId, $contractProject, $deleteProject)
    {
        if (!empty($deleteProject)) {
            $this->deleteByWhere(['contract_t_id' => [$contractId], 'id' => [$deleteProject, 'in']]);
        }
        if (!empty($contractProject)) {
            $insertData = $updateData = [];
            foreach ($contractProject as $key => $value) {
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
                $projects = $this->getTableColumns();
                $projects = array_diff($projects, ['created_at', 'deleted_at', 'updated_at']);
                foreach ($updateData as $key => $value) {
                    foreach ($projects as $tableKey) {
                        $value[$tableKey] = isset($value[$tableKey]) ? $value[$tableKey] : '';
                    }
                    $unique_key     = array_diff(array_keys($value), $projects);
                    $temp           = Arr::except($value, $unique_key);
                    $temp['run_id'] = (isset($temp['run_id']) && $temp['run_id']) ? implode(',', $temp['run_id']) : '';
                    unset($temp['id']);
                    $this->updateData($temp, ['id' => $value['id']]);
                }
            }
        }
        return true;
    }

    public function parseResults($systemComboboxService, &$projects)
    {
        if (empty($projects)) {
            return true;
        }
        $runIds = [];
        foreach ($projects as $key => $value) {
            $projects[$key]->pay_way_name  = $systemComboboxService->parseCombobox(self::PAY_WAY_SELECT_FIELD, $value->pay_way);
            $projects[$key]->pay_type_name = $systemComboboxService->parseCombobox(self::PAY_TYPE_SELECT_FIELD, $value->pay_type);
//            $temp                            = explode(',', $value['project_run_id']);
//            if (!empty($temp)) {
//                $projects[$key]->project_run_id = $temp;
//            }
//            $runIds = array_merge($runIds, $temp);
        }
        if (empty($runIds)) {
            return true;
        }
        $runIds   = array_unique($runIds);
        $runLists = $this->getSimpleFlowRun($runIds);
        foreach ($projects as $key => $value) {
            if (empty($value->run_id)) {
                continue;
            }
            $temp_lists = [];
            foreach ($value['run_id'] as $temp_run_id) {
                if (isset($runLists[$temp_run_id])) {
                    $temp_lists[] = $runLists[$temp_run_id];
                }
            }
            $projects[$key]['run_lists'] = $temp_lists;
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
        if (!isset($data['type'])) {
            return ['code' => ['0x066016', 'contract']];
        }
        if (!isset($data['money'])) {
            return ['code' => ['0x066017', 'contract']];
        }
        return true;
    }


    public function getProjects($id){
        return $this->entity->select('*')->where('id',$id)->get()->toArray();
    }

    public function getIds($id){
        return $this->entity->select(['project_id','contract_t_id as id'])->whereIn('contract_t_id',$id)->get()->toArray();
    }

    public static function childLists($id){
        return DB::table('contract_t_project')->whereIn('contract_t_id',$id)->get()->toArray();
    }

    public function getContractProject(){
        return $this->entity->select('*')->get()->toArray();
    }

    public function getProject($params){
        return $this->entity->select('*')->wheres($params['search'])->get()->toArray();
    }

    public function getProjectPayLists($input,$pay_type){
        $query = $this->entity->select('*')->where($pay_type);
        if($input && isset($input['search'])){
            $query = $query->wheres($input['search']);
        }
        return $query->sum('money');
    }

    public function getProjectLists($input){
        $query = $this->entity->select('*');
        if($input && isset($input['search'])){
            $query = $query->wheres($input['search']);
        }
        return $query->get()->toArray();
    }

    public function getProjectData($params){
        $query = $this->entity->select('type',DB::raw('sum(money) as value'))->groupBy('type');
        if($params && isset($params['search'])){
            $query = $query->wheres($params['search']);
        }
        return $query->get()->toArray();
    }

}
