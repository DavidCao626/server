<?php


namespace App\EofficeApp\Salary\Services;


use App\EofficeApp\Salary\Entities\SalaryFieldsEntity;
use App\EofficeApp\Salary\Enums\FieldDefaultSet;
use App\EofficeApp\Salary\Enums\FieldTypes;
use App\EofficeApp\Salary\Exceptions\SalaryError;
use App\EofficeApp\Salary\Helpers\SalaryHelpers;
use App\EofficeApp\Salary\Repositories\SalaryCalculateRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldRepository;
use App\EofficeApp\Salary\Repositories\SalaryRepository;
use App\EofficeApp\Salary\Services\SalaryField\DefaultValueField;
use App\EofficeApp\Salary\Services\SalaryField\SalaryFieldBuilder;
use App\Exceptions\ErrorMessage;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalaryFieldService extends SalaryBaseService
{
    private $salaryFieldRepository;

    private $salaryCalculateRepository;

    public $salaryRepository;

    public $salaryFieldPersonalDefaultRepository;

    private $salaryFieldHistoryRepository;

    public $salaryFieldPersonalDefaultHistoryRepository;

    private $salaryReportSetService;

    public function __construct(
        SalaryFieldRepository $salaryFieldRepository,
        SalaryCalculateRepository $salaryCalculateRepository,
        SalaryRepository $salaryRepository,
        SalaryFieldPersonalDefaultRepository $salaryFieldPersonalDefaultRepository,
        SalaryFieldHistoryRepository $salaryFieldHistoryRepository,
        SalaryFieldPersonalDefaultHistoryRepository $salaryFieldPersonalDefaultHistoryRepository,
        SalaryReportSetService $salaryReportSetService
    )
    {
        parent::__construct();
        $this->salaryFieldRepository = $salaryFieldRepository;
        $this->salaryCalculateRepository = $salaryCalculateRepository;
        $this->salaryRepository = $salaryRepository;
        $this->salaryFieldPersonalDefaultRepository = $salaryFieldPersonalDefaultRepository;
        $this->salaryFieldHistoryRepository = $salaryFieldHistoryRepository;
        $this->salaryFieldPersonalDefaultHistoryRepository = $salaryFieldPersonalDefaultHistoryRepository;
        $this->salaryReportSetService = $salaryReportSetService;
    }

    /**
     * ??????????????????
     * @param  array $param ????????????
     * @return array ????????????
     */
    public function getSalaryItemsList($param)
    {
        $param = $this->parseParams($param);

        if (isset($param['getAll']) && $param['getAll'] != 0) {
            if (isset($param['user_id']) && $param['user_id'] != '') {
                $userId    = $param['user_id'];
                $items = $this->salaryFieldRepository->getSalaryItems($param, true);
                foreach ($items as $key => $value) {
                    if ($value['field_default_set'] == 3) {
                        $items[$key]['field_default'] = $this->getSystemData($value['field_source'], $userId);
                    }
                }
                return $items;
            }
            return $this->salaryFieldRepository->getSalaryItems($param, true);
        } else {
            return $this->response($this->salaryFieldRepository, 'getTotal', 'getSalaryItems', $param);
        }
    }

    /**
     * ??????????????????
     * @param int $id
     * @param string $userId
     * @return int | string | array
     **/
    public function getSystemData($id, $userId)
    {
        $record = $this->salaryCalculateRepository->getDetail($id);

        if(!empty($record)){
            try {
                $result = app($record->type_object)->{$record->type_method}($userId);
            } catch (\Exception $e) {
                return ['code' => $e->getMessage()];
            }

            return $result;
        }

        return '';
    }

    /**
     * ????????????????????????
     * @param $param
     * @return array
     */
    public function getNumericSalaryItems($param)
    {
        $param = $this->parseParams($param);
        if (!isset($param['search'])) {
            $param['search'] = [];
        }
        $param['search']['field_type']        = [1];
        $param['search']['field_default_set'] = [1];
        $param['search']['has_children']      = [0];
        $param['getAll']                      = $param['getAll'] ?? 1;

        return $this->getSalaryItemsList($param);
    }

    /**
     * ??????????????????????????????????????????
     * @param $param
     * @return array
     */
    public function getAllSalaryItems($param)
    {
        $param = $this->parseParams($param);
        $param['fields'] = ['*'];

        return $this->getSalaryItemsList($param);
    }

    /**
     * ????????????????????????????????????
     * @return int
     */
    public function getMaxSort()
    {
        $record = $this->salaryFieldRepository->getMaxSort();
        return isset($record['field_sort']) ? $record['field_sort'] + 1 : 1;
    }

    /**
     * ?????????????????????
     * @param $param
     * @return array
     */
    public function getDeletedSalaryItems($param)
    {
        $param           = $this->parseParams($param);
        $param['delete'] = isset($param['delete']) ? $param['delete'] : 1;

        return $this->response($this->salaryFieldRepository, 'getDeletedTotal', 'getSalaryItems', $param);
    }

    /**
     * ??????????????????
     * @param $data
     * @return array|bool
     */
    public function addSalary($data)
    {
        $lastField = $this->salaryFieldRepository->getLastRecord();

        if (!empty($lastField) && isset($lastField->field_code)) {
            $num        = substr($lastField->field_code, 6) + 1;
            $field_code = 'field_' . $num;
        } else {
            $field_code = "field_1";
        }

        $data['field_code'] = $field_code;

        if ($data['field_type'] == 1 && $data['field_default_set'] != 3) {
            if ($data['field_default'] == '') {
                $data['field_default'] = 0;
            }
            if (!$data['field_decimal']) {
                $data['field_decimal'] = 0;
            }
            $data['over_zero'] = isset($data['over_zero'])?$data['over_zero']:0;
            $data['field_default'] = SalaryHelpers::valueFormat(
                $data['field_default'],
                $data['field_format'],
                $data['field_decimal'],
                $data['over_zero']
            );
        }
        // ??????????????????????????????????????????????????????
        if ($data['field_default_set'] == 3) {
            $sysData = $this->getCalculateDetail(['search' => ['id' => [$data['field_source']]]]);
            if (isset($sysData[0]) && !empty($sysData[0])) {
                $data['field_default'] = $sysData[0]['type_name'];
            }
        } elseif ($data['field_default_set'] == 2) {
            $data['field_default'] = trans("salary.calculated_value");
        }

        // ????????????
        if ($data['field_type'] == FieldTypes::FROM_FILE) {
            $data['field_source'] = json_encode($data['field_source']);
            $data['field_default'] = trans("salary.from_file");
        }
        // ????????????
        if ($data['field_default_set'] == FieldDefaultSet::TAX) {
            $data['field_source'] = json_encode($data['field_source']);
            $data['field_default'] = trans("salary.tax_type");
        }
        // ????????????
        if ($data['field_default_set'] == FieldDefaultSet::LAST_REPORT_DATA) {
            $data['field_source'] = json_encode($data['field_source']);
            $data['field_default'] = trans("salary.last_report_data");
        }

        // ????????????id
        $data['dependence_ids'] = $this->getDependenceIdsByInputConfig($data);

        $result = $this->salaryFieldRepository->entity->create($data);

        if(isset($data['field_parent']) && $data['field_parent'] != 0){
            // ????????????????????????????????????????????????20201228-dp-?????????enabled????????????????????????enabled???true???
            $this->salaryFieldRepository->updateData(['has_children' => 1, 'field_default' => 0, 'enabled' => 1], ['field_id' => $data['field_parent']]);
        }

        return $result;
    }

    /**
     * @param $config
     * @return mixed
     */
    public function getDependenceIdsByInputConfig($config)
    {
        $fields = $this->salaryFieldRepository->getAllField();

        return (new SalaryFieldBuilder($config))->build($fields)->getDependenceIds();
    }

    /**
     * ????????????????????????
     * @param $param
     * @return array
     */
    public function getCalculateDetail($param)
    {
        $data = [];
        if (isset($param['search'])) {
            if (!is_array($param['search'])) {
                $search = $param['search'];
                $searchArray = json_decode($search, true);
                // ???????????????????????????????????? search: {"id":["1_vacation_days","="]}
                if(isset($searchArray['id']) && isset($searchArray['id'][0])) {
                    $searchArrayId = $searchArray['id'][0];
                    $searchArrayIdExplode = explode('_',$searchArrayId);
                    if(count($searchArrayIdExplode) == 3 && $searchArrayIdExplode[1] == 'vacation') {
                        // ???????????????
                        $vacationId = $searchArrayIdExplode[0];
                        $vacationType = $searchArrayIdExplode[2];
                    } else {
                        // ????????????
                    }
                } else if(isset($searchArray['trans']) && isset($searchArray['trans'][0])) {
                    // ?????????????????????????????????????????????????????????????????????
                    // search: {"trans":["wyl","like"]}
                }
                $param['search'] = json_decode($param['search'], true);
                // ??????search?????????parent_id(?????????????????????????????????????????????????????????)
                $searchParentData = $searchArray['parent_id'] ?? [];
                $searchParentId = $searchParentData[0] ?? 0;
            } else {
                // ???????????????????????????????????????????????????????????????: id=>[[0] => 2_vacation_hours]
                $searchArray = $param['search'];
                if(isset($searchArray['id']) && isset($searchArray['id'][0])) {
                    $searchArrayId = $searchArray['id'][0];
                    $searchArrayIdExplode = explode('_',$searchArrayId);
                    if(count($searchArrayIdExplode) == 3 && $searchArrayIdExplode[1] == 'vacation') {
                        // ???????????????
                        $vacationId = $searchArrayIdExplode[0];
                        $vacationType = $searchArrayIdExplode[2];
                    }
                }
            }
            if(isset($vacationId)) {
                // ??????????????????
                $vacationService = app("App\EofficeApp\Vacation\Services\VacationService");
                $vacationData = $vacationService->getVacationList(['search' => ['vacation_id' => [$vacationId]]]);
                if(isset($vacationData['total']) && $vacationData['total']) {
                    $vacationListData = $vacationData['list'] ?? [];
                    $vacationListData = $vacationListData[0] ?? [];
                    $vacationName = $vacationListData['vacation_name'] ?? '';
                    if($vacationType == 'days') {
                        // ??????-???
                        $vacationItemDays = [
                            "id" => $vacationId."_vacation_days",
                            "type_code" => $vacationId."_vacation_days",
                            "type_name" => $vacationName."(".trans("salary.number_of_days").")",
                            // "parent_id" => $searchParentId,
                            "trans" => $vacationName."(".trans("salary.number_of_days").")",
                        ];
                        $data[] = $vacationItemDays;
                    } else if($vacationType == 'hours'){
                        // ??????-??????
                        $vacationItemHours = [
                            "id" => $vacationId."_vacation_hours",
                            "type_code" => $vacationId."_vacation_hours",
                            "type_name" => $vacationName."(".trans("salary.number_of_hours").")",
                            // "parent_id" => $searchParentId,
                            "trans" => $vacationName."(".trans("salary.number_of_hours").")",
                        ];
                        $data[] = $vacationItemHours;
                    }
                }
            } else {
                $data = $this->salaryCalculateRepository->getParentData($param['search']);

                if ($data && !empty($data->toArray())) {
                    foreach ($data as $key => $value) {
                        $data[$key]['type_name'] = mulit_trans_dynamic("calculate_data.type_name." .$value->type_name);
                    }
                } else {
                    // ???????????????????????????????????????????????????????????????????????????????????????????????????
                }
            }
            // ?????????????????????????????????????????????????????????????????????????????????????????????(DT202010140003)
            $record = DB::table('calculate_data')
                ->where('type_code', 'attendance_data')
                ->first();
            if($record && $record->id) {
                if(isset($searchArray['trans']) && isset($searchArray['trans'][0]) && isset($searchArray['trans'][1]) && $searchArray['trans'][1] == 'like') {
                    $attendanceDataSearch = $searchArray['trans'][0];
                    $searchParentId = $record->id;
                }
                if(isset($searchParentId) && $searchParentId == $record->id) {
                    $vacationSearch = [];
                    if(isset($attendanceDataSearch)) {
                        $vacationSearch = ['search' => ['vacation_name' => [$attendanceDataSearch, 'like']]];
                    }
                    // ??????????????????
                    $vacationService = app("App\EofficeApp\Vacation\Services\VacationService");
                    $vacationData = $vacationService->getVacationList($vacationSearch);
                    $vacationList = $vacationData['list'] ?? [];
                    if(!empty($vacationList)) {
                        $vacationList = $vacationList->toArray();
                        foreach ($vacationList as $key => $value) {
                            // ??????-???
                            $vacationItemDays = [
                                "id" => $value['vacation_id']."_vacation_days",
                                "type_code" => $value['vacation_id']."_vacation_days",
                                "type_name" => $value['vacation_name']."(".trans("salary.number_of_days").")",
                                "parent_id" => $searchParentId,
                                // "type_object" => "App\\EofficeApp\\Salary\\Services\\SalaryField\\CalculateField",
                                // "type_method" => "getVacationMethod",
                                "trans" => $value['vacation_name']."(".trans("salary.number_of_days").")",
                            ];
                            $data[] = $vacationItemDays;
                            // ??????-??????
                            $vacationItemHours = [
                                "id" => $value['vacation_id']."_vacation_hours",
                                "type_code" => $value['vacation_id']."_vacation_hours",
                                "type_name" => $value['vacation_name']."(".trans("salary.number_of_hours").")",
                                "parent_id" => $searchParentId,
                                // "type_object" => "App\\EofficeApp\\Salary\\Services\\SalaryField\\CalculateField",
                                // "type_method" => "getVacationMethod",
                                "trans" => $value['vacation_name']."(".trans("salary.number_of_hours").")",
                            ];
                            $data[] = $vacationItemHours;
                        }
                    }
                }
            }
            return $data;
        } else {
            return ['code' => ['0x000003', 'common']];
        }
    }

    /**
     * ??????????????????
     * @param $data
     * @param $field_id
     * @return bool
     * @throws SalaryError
     */
    public function editSalaryInfo($data, $field_id)
    {
        if(! $data['enabled']){
            $this->checkIsOthersDependence($field_id);
        }
        unset($data['field_id']);
        if ($data['field_type'] == FieldTypes::NUMBER) {
            if ($data['field_default'] == '') {
                $data['field_default'] = 0;
            }
            if(!$data['field_decimal']){
                $data['field_decimal'] = 0;
            }
            $data['over_zero'] = isset($data['over_zero'])?$data['over_zero']:0;
            $data['field_default'] = SalaryHelpers::valueFormat(
                $data['field_default'],
                $data['field_format'],
                $data['field_decimal'],
                $data['over_zero']
            );
        }
        if ($data['field_default_set'] == FieldDefaultSet::SYSTEM_DATA) {
            $sysData = $this->getCalculateDetail(['search' => ['id' => [$data['field_source']]]]);
            if (isset($sysData[0]) && !empty($sysData[0])) {
                $data['field_default'] = $sysData[0]['type_name'];
            }
        } elseif ($data['field_default_set'] == FieldDefaultSet::FORMULA) {
            $data['field_default'] = trans("salary.calculated_value");
        }

        if ($data['field_type'] == FieldTypes::FROM_FILE) {
            $data['field_source'] = json_encode($data['field_source']);
            $data['field_default'] = trans("salary.from_file");
        }
        if ($data['field_default_set'] == FieldDefaultSet::TAX) {
            $data['field_source'] = json_encode($data['field_source']);
            $data['field_default'] = trans("salary.tax_type");
        }
        // ????????????
        if ($data['field_default_set'] == FieldDefaultSet::LAST_REPORT_DATA) {
            $data['field_source'] = json_encode($data['field_source']);
            $data['field_default'] = trans("salary.last_report_data");
        }

        // ???????????????????????????????????????????????????????????????
        if($data['has_children'] == 1){
            if($data['field_show'] == 0){
                $this->salaryFieldRepository->updateData(['field_show' => 0], ['field_parent' => $field_id]);
            }
            if($data['enabled'] == 0){
                $this->salaryFieldRepository->updateData(['enabled' => 0], ['field_parent' => $field_id]);
            }
        }

        $data['dependence_ids'] = $this->getDependenceIdsByInputConfig($data);

        // ?????????????????????????????????????????????
        $old = $this->salaryFieldRepository->getDetail($field_id);
        if($old && ($old->field_type != $data['field_type'] || $old->field_default_set != $data['field_default_set'])){
            $this->salaryFieldPersonalDefaultRepository->entity->where('field_id', $field_id)->delete();
        }

        $where = ['field_id' => $field_id];

        return $this->salaryFieldRepository->updateData($data, $where);
    }


    /**
     * ??????????????????
     * @param $field_id
     * @return array|bool
     * @throws SalaryError
     */
    public function deleteSalary($field_id)
    {
        $field_ids = explode(',', trim($field_id, ","));
        $param     = [
            "fields" => ["field_id", "is_count", "field_parent", "has_children"],
            "search" => ["field_id" => [$field_ids, "in"]]
        ];

        $salaryInfo = $this->salaryFieldRepository->getSalaryItems($param);
        if(!empty($salaryInfo)){
            foreach ($salaryInfo as $value) {
                // ????????????????????????????????????
                $this->checkIsOthersDependence($value['field_id']);

                if ($value['has_children'] == 1) {
                    return ['code' => ['0x038009', 'salary']];
                }
                // ??????
                $where = ['field_id' => [$value['field_id'], '=']];
                $this->salaryFieldRepository->deleteByWhere($where);

                $childCount = $this->salaryFieldRepository->getChildrensCount($value['field_parent']);
                if ($childCount == 0) {
                    // ??????has_children????????????
                    $this->salaryFieldRepository->updateData(['has_children' => 0], ['field_id' => $value['field_parent']]);
                }
            }
        }

        $this->salaryFieldRepository->deleteById($field_ids);

        return true;
    }

    /**
     * ??????????????????????????????
     * @param $field_id
     * @return bool
     */
    public function forceDeleteSalary($field_id)
    {
        $field_ids = explode(',', trim($field_id, ","));
        /** @var \Illuminate\Database\Eloquent\Collection $fields */
        $fields = $this->salaryFieldRepository->entity->onlyTrashed()->find($field_ids);

        $fields->each(function (SalaryFieldsEntity $field) {
            $field->personalDefaults()->forceDelete();
            $field->forceDelete();
        });

        return true;
    }

    /**
     * ????????????????????????
     * @param $field_id
     * @return array
     */
    public function getIndexSalaryItemsByFieldId($field_id)
    {
        return $this->salaryFieldRepository->getSalaryItemsByFieldId($field_id);
    }

    /**
     * ?????????????????????
     * @return array
     */
    public function getCalculateData()
    {
        $where = ['parent_id' => [0]];
        $data  = $this->salaryCalculateRepository->getParentData($where);

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $data[$key]['type_name'] = mulit_trans_dynamic("calculate_data.type_name." .$value->type_name);
            }
        }

        return $data;
    }

    /**
     * ??????????????????????????????
     * @param array $param
     * @return array|mixed
     */
    public function getSalaryFieldsManageList($param=[])
    {
        $data = $this->salaryFieldRepository->getSalaryItems($param, true);
        $temp = [];
        $res = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $temp[$value['field_parent']][] = $value;
            }
        }
        if (!empty($temp)){
            if(isset($temp[0])){
                foreach ($temp[0] as $key => $value) {
                    if (isset($temp[$value['field_id']])) {
                        $temp[0][$key]['childrens'] = $temp[$value['field_id']];
                    }
                }
                return $temp[0];
            }else if(is_array($temp)){
                foreach ($temp as $parent => $datas) {
                    foreach ($datas as $k => $v) {
                        $res[] = $v;
                    }
                }
                return $res;
            }
        }else{
            return [];
        }
    }

    /**
     * ??????????????????????????????
     * @param $param
     * @return array
     */
    public function getSalaryFieldsParent($param)
    {
        $param = $this->parseParams($param);

        if(isset($param['search']['multiSearch']['field_name'])){
            $param['search']['field_name'] = $param['search']['multiSearch']['field_name'];
            unset($param['search']['multiSearch']);
        }

        return $this->response($this->salaryFieldRepository, 'getTotal', 'getSalaryItems', $param);
    }

    /**
     * ???????????????
     * @param $data
     * @return array|bool
     */
    public function salaryFieldMove($data)
    {
        if (!isset($data['field_id']) || !isset($data['field_parent'])) {
            return ['code' => ['0x038010', 'salary']];
        }
        if ($data['field_id'] == $data['field_parent']) {
            return true;
        }
        $fieldInfo = $this->salaryFieldRepository->getSalaryItemsByFieldId($data['field_id']);
        if ($fieldInfo['has_children'] == 1) {
            return ['code' => ['0x038010', 'salary']];
        }
        $where = ['field_id' => $data['field_id']];
        $update = ['field_parent' => $data['field_parent']];
        $this->salaryFieldRepository->updateData($update, $where);
        // ?????????????????????????????????
        if (isset($fieldInfo['field_parent'])) {
            if ($fieldInfo['field_parent'] != 0) {
                $childrenCount = $this->salaryFieldRepository->getChildrensCount($fieldInfo['field_parent']);
                if ($childrenCount == 0) {
                    $this->salaryFieldRepository->updateData(['has_children' => 0], ['field_id' => $fieldInfo['field_parent']]);
                }
            }
        }
        if ($data['field_parent'] != 0) {
            // ???????????????has_children??????
            $newParentInfo = $this->salaryFieldRepository->getSalaryItemsByFieldId($data['field_parent']);
            if(isset($newParentInfo['has_children']) && $newParentInfo['has_children'] == 0){
                return $this->salaryFieldRepository->updateData(['has_children' => 1], ['field_id' => $data['field_parent']]);
            }else{
                return true;
            }
        }else{
            return true;
        }
    }

    /**
     * ????????????????????????????????????
     * @param $param
     * @return array
     */
    public function salaryFieldCanAdjust($param)
    {
        if(empty($param)){
            $param = [];
        }
        $param['fields'] = ['field_name', 'field_type', 'field_default_set', 'field_id', 'has_children'];
        $param['getAll'] = 1;
        $param['delete'] = 0;
        $fields          = $this->getSalaryItemsList($param);
        $data            = [];
        if(!empty($fields)){
            foreach ($fields as $key => $value) {
                if ($value['field_type'] == 1 && $value['field_default_set'] == 1 && $value['field_id'] != '' && $value['has_children'] == 0) {
                    $data[] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * ?????????????????????????????????????????????????????????????????????
     * @param $params
     * @return array
     */
    public function getSalaryFieldsInCount($params)
    {
        $param           = [];
        $param['getAll'] = 1;
        $param['search'] = [
            'enabled' => [1]
        ];
        $except = $params['except'] ?? 0;
        $fields          = $this->salaryFieldRepository->getSalaryItems($param, true);

        $data = [];
        if(!empty($fields)){
            foreach ($fields as $key => $value) {
                if (($value['field_type'] != FieldTypes::NUMBER && $value['field_type'] != FieldTypes::FROM_FILE)
                    || $value['has_children'] == 1
                ) {
                    continue;
                }
                if($except && $except == $value['field_id']){
                    continue;
                }
                if ($value['field_parent'] != 0) {
                    $parent = $this->salaryFieldRepository->getSalaryItemsByFieldId($value['field_parent']);
                    if(isset($parent['enabled']) && $parent['enabled'] == 0){
                        continue;
                    }
                }
                $data[] = $value;
            }
        }
        return $data;
    }

    /**
     * ?????????????????????
     * @param array $param
     * @param bool $flag
     * @return array|mixed
     */
    public function getSalaryFieldsList($param=[], $flag=true)
    {
        $data = $this->salaryFieldRepository->getSalaryItems($param, true);
        $temp = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if($value['field_show'] != 1 && $flag){
                    continue;
                }
                $temp[$value['field_parent']][] = $value;
            }
        }
        if (!empty($temp) && isset($temp[0])){
            foreach ($temp[0] as $key => $value) {
                if (isset($temp[$value['field_id']])) {
                    $temp[0][$key]['childrens'] = $temp[$value['field_id']];
                }
            }
            return $temp[0];
        }else{
            return [];
        }
    }

    /**
     * @param $fieldId
     * @param $param
     * @param $own
     * @return array
     * @throws SalaryError
     */
    public function getSalaryPersonalDefaultList($fieldId, $param, $own)
    {
        $data = [];
        $total = 0;
        $viewUser = $this->salaryReportSetService->getViewUser($own);
        if(empty($viewUser)){
            return compact('data', 'total');
        }

        $fieldConfig = $this->salaryFieldRepository->getDetail($fieldId);
        if(!$fieldConfig){
            throw new SalaryError('0x038022');
        }
        $field = new DefaultValueField($fieldConfig->toArray());

        $param['payWithoutAccountConfig'] = $this->payWithoutAccountConfig;
        if($this->payWithoutAccountConfig == '1') {
            // ???????????????
            $param['search'] = ['id' => [$viewUser, 'in']];
            $param['order_by'] = ['no' => 'asc','id'=>'asc'];
        } else {
            $param['search'] = ['user_id' => [$viewUser, 'in']];
            $param['order_by'] = ['list_number' => 'asc', 'user_name' => 'asc'];
        }
        $personalDefaultList = $this->salaryFieldPersonalDefaultRepository->getPersonalDefaultList($fieldId, $param);
        $data = $personalDefaultList->map(function ($value) use ($field, $fieldConfig) {
                if($this->payWithoutAccountConfig == '1') {
                    $userId = $value->id;
                } else {
                    $userId = $value->user_id;
                }
                return [
                    'user_id' => $userId,
                    'user_name' => $value->user_name,
                    'default_value' => $value->salaryPersonalDefault->isEmpty()
                        ? $field->formatValue($fieldConfig->field_default)
                        : $field->formatValue(($value->salaryPersonalDefault)[0]['default_value'])
                ];
            })->all();
        $total = $this->salaryFieldPersonalDefaultRepository->getPersonalDefaultTotal($param);

        return compact('data', 'total');
    }

    /**
     * @param $fieldId
     * @param $data
     * @param $own
     * @return array
     * @throws SalaryError
     */
    public function setSalaryPersonalDefault($fieldId, $data, $own)
    {
        $field = $this->salaryFieldRepository->entity->find($fieldId);
        if(!$field){
            throw new SalaryError('0x038022');
        }
        if($field->has_children != 0){
            throw new SalaryError('0x038023');
        }
        $viewUser = $this->salaryReportSetService->getViewUser($own);
        foreach($data as &$record) {
            if(! in_array($record['user_id'], $viewUser)) {
                return ['code' => ['0x000006', 'common']];
            }
            $record['default_value'] = $this->formatPersonalDefault($record['default_value'], $field);
        }

        return $this->salaryFieldPersonalDefaultRepository->updatePersonalDefaultList($fieldId, $data);
    }

    /**
     * @param $fieldId
     * @param $userId
     * @return string
     */
    public function getUserPersonalDefault($fieldId, $userId)
    {
        return $this->salaryFieldPersonalDefaultRepository->getUserPersonalDefault($fieldId, $userId);
    }

    /**
     * ????????????????????????????????????
     * @param $reportId
     */
    public function createHistoryData($reportId)
    {
        $this->createFieldHistoryData($reportId);
        $this->createPersonalDefaultHistoryData($reportId);
    }

    /**
     * ???????????????????????????????????????
     * @param $reportId
     */
    public function createFieldHistoryData($reportId)
    {
        /** @var Collection $allFields */
        $allFields = $this->salaryFieldRepository->entity
            ->where('enabled', 1)
            ->get();
        $insertData = $allFields->map(function ($field) use ($reportId) {
            $field->report_id = $reportId;
            $time = [
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time())
            ];
            return collect($field)->except(['created_at', 'updated_at', 'deleted_at'])
                ->merge($time)->toArray();
        })->toArray();

        $this->salaryFieldHistoryRepository->entity->insert($insertData);
    }

    /**
     * @param $reportId
     */
    public function createPersonalDefaultHistoryData($reportId)
    {
        /** @var Collection $fieldHistory */
        $fieldHistoryList = $this->salaryFieldHistoryRepository->entity
                        ->select(['id', 'field_id'])
                        ->where('report_id', $reportId)
                        ->get();
        $map = [];
        foreach ($fieldHistoryList as $fieldHistory) {
            $map[$fieldHistory->field_id] = $fieldHistory->id;
        }
        /** @var Collection $personalDefaultList */
        $personalDefaultList = $this->salaryFieldPersonalDefaultRepository->entity->get();
        $insertData = [];
        foreach($personalDefaultList as $personalDefault){
            if(!isset($map[$personalDefault->field_id])){
                continue;
            }
            $insertData[] = [
                'field_history_id' => $map[$personalDefault->field_id],
                'user_id' => $personalDefault->user_id,
                'default_value' => $personalDefault->default_value,
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time())
            ];
        }

        $this->salaryFieldPersonalDefaultHistoryRepository->entity->insert($insertData);
    }

    /**
     * ?????????????????????????????????
     * @param $fieldId
     * @return bool
     */
    public function isOthersDependence($fieldId)
    {
        $list = $this->salaryFieldRepository->getFieldsHasDependenceId($fieldId);

        return ! $list->isEmpty();
    }

    /**
     * @param $fieldId
     * @throws SalaryError
     */
    public function checkIsOthersDependence($fieldId)
    {
        if($this->isOthersDependence($fieldId)){
            throw new SalaryError('0x038008');
        }
    }

    /**
     * ????????????????????????
     * @param $value
     * @param $config
     * @return false|float|int|string
     * @throws SalaryError
     */
    public function formatPersonalDefault($value, $config)
    {
        $value = trim($value);
        if(!$value){
            if($config['field_type'] == FieldTypes::NUMBER){
                $value = 0;
            }else{
                return '';
            }
        }
        try {
            switch ($config['field_type']) {
                case FieldTypes::NUMBER:
                    if(! is_numeric($value)){
                        throw new SalaryError('incorrect_format');
                    }
                    return SalaryHelpers::valueFormat(
                        $value,
                        $config['field_format'],
                        $config['field_decimal'],
                        $config['over_zero']
                    );
                case FieldTypes::DATE:
                    return (new Carbon($value))->format('Y-m-d');
                case FieldTypes::TIME:
                    return (new Carbon($value))->format('Y-m-d H:i:s');
                default:
                    return $value;
            }
        }catch (\Throwable $e){
            throw new SalaryError('incorrect_format');
        }
    }
}
