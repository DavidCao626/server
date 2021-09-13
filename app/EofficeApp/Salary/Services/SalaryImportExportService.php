<?php


namespace App\EofficeApp\Salary\Services;


use App\EofficeApp\Salary\Enums\FieldDefaultSet;
use App\EofficeApp\Salary\Enums\FieldTypes;
use App\EofficeApp\Salary\Repositories\SalaryFieldHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldRepository;
use App\EofficeApp\Salary\Repositories\SalaryRepository;
use App\EofficeApp\Salary\Services\SalaryField\DefaultValueField;
use App\EofficeApp\User\Repositories\UserRepository;
use App\Exceptions\ErrorMessage;
use Illuminate\Database\Eloquent\Collection;

class SalaryImportExportService extends SalaryBaseService
{
    private $salaryReportSetService;

    private $salaryFieldService;


    private $userRepository;

    public $salaryRepository;

    private $salaryEntryService;

    private $salaryReportFormService;

    private $salaryFieldHistoryRepository;

    private $salaryFieldRepository;

    public $salaryFieldPersonalDefaultRepository;

    public $salaryFieldPersonalDefaultHistoryRepository;

    public function __construct(
        SalaryReportSetService $salaryReportSetService,
        SalaryFieldService $salaryFieldService,
        UserRepository $userRepository,
        SalaryRepository $salaryRepository,
        SalaryEntryService $salaryEntryService,
        SalaryReportFormService $salaryReportFormService,
        SalaryFieldHistoryRepository $salaryFieldHistoryRepository,
        SalaryFieldRepository $salaryFieldRepository,
        SalaryFieldPersonalDefaultRepository $salaryFieldPersonalDefaultRepository,
        SalaryFieldPersonalDefaultHistoryRepository $salaryFieldPersonalDefaultHistoryRepository
    )
    {
        parent::__construct();
        $this->salaryReportSetService = $salaryReportSetService;
        $this->salaryFieldService = $salaryFieldService;
        $this->userRepository = $userRepository;
        $this->salaryRepository = $salaryRepository;
        $this->salaryEntryService = $salaryEntryService;
        $this->salaryReportFormService = $salaryReportFormService;
        $this->salaryFieldHistoryRepository = $salaryFieldHistoryRepository;
        $this->salaryFieldRepository = $salaryFieldRepository;
        $this->salaryFieldPersonalDefaultRepository = $salaryFieldPersonalDefaultRepository;
        $this->salaryFieldPersonalDefaultHistoryRepository = $salaryFieldPersonalDefaultHistoryRepository;
        $this->userService = "App\EofficeApp\User\Services\UserService";
        $this->personnelFilesRepository = "App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository";
    }

    /**
     * 查询薪酬上报模板数据
     * @param $userInfo
     * @param $param
     * @return array 模板数据
     */
    public function getSalaryTemplateData($userInfo, $param)
    {
        $own            = [];
        $own['user_id'] = $userInfo['user_id'];
        $own['dept_id'] = $userInfo['dept_id'];
        $own['role_id'] = $userInfo['role_id'];
        $reportId = $param['report_id'];

        // 管理范围(如果开了无账号发薪，返回档案id的数组)
        $viewUser = $this->salaryReportSetService->getViewUser($own);

        // 用户ID
        $headerUserIdLang = trans('salary.user').'ID';
        // 姓名
        $headerUserNameLang = trans('salary.user_name');
        if($this->payWithoutAccountConfig == '1') {
            // 人事档案ID
            $headerUserIdLang = trans('common.personnelfiles')."ID";
            // 人事档案姓名
            $headerUserNameLang = trans('common.personnelfiles').trans('salary.user_name');
        }

        $header = [
            'user_id'       => ['data' => $headerUserIdLang, '_RANGE_' => [1, 1, 1, 2]],
            'user_accounts' => ['data' => trans('salary.account'), '_RANGE_' => [2, 1, 2, 2]],
            'user_name'     => ['data' => $headerUserNameLang, '_RANGE_' => [3, 1, 3, 2]],
        ];

        // $fieldIdDefaultMap->field_id
        $data = $fieldsWithNoChildren = $fieldIdDefaultMap = [];

        $tempFields = $this->salaryFieldHistoryRepository->getSalaryItems(['report_id' => $reportId]);
        // 处理薪酬项将子项放到父级的children中
        $fields = $this->salaryReportFormService->handleFieldsForReportForm($tempFields);

        if(!empty($fields)){
            $num = 4;
            foreach ($fields as $key => $value) {
                if(isset($value['children']) && !empty($value['children'])){
                    $count = count($value['children']);
                    // 合并的薪资项
                    $header[$value['field_id']] = ['data' => $value['field_name'], '_RANGE_' => [$num, 1, $num + $count -1, 1], '_MERGE_' => 1];
                    // 子薪资项
                    for ($i=0; $i < $count; $i++) {
                        $temp = $value['children'][$i];
                        $header[$temp['field_id']] = ['data' => $temp['field_name'], '_RANGE_' => [$num+$i, 2, $num+$i, 2]];
                        $fieldsWithNoChildren[$temp['id']] = $temp;
                        $fieldIdDefaultMap[$temp['field_id']] = $temp['field_default'];
                    }
                    $num += $count;
                }else{
                    $header[$value['field_id']] = ['data' => $value['field_name'], '_RANGE_' => [$num, 1, $num, 2]];
                    $fieldsWithNoChildren[$value['id']] = $value;
                    $fieldIdDefaultMap[$value['field_id']] = $value['field_default'];
                    $num ++;
                }
            }
        }
        $historyIds = array_keys($fieldsWithNoChildren);

        // 开了[无账号发薪]，$viewUser内是personnel--id数组
        // 去人事档案里面查$viewUser范围内的信息，用withuser带出user_accounts
        // 拼装并收集数据[user_id==id;user_accounts==空;user_name==user_name]
        if($this->payWithoutAccountConfig == '1') {
            // 取管理范围内的人，作为excel的行
            $result = [];
            $userIds = [];
            // 去人事档案模块查数据
            $params = [
                'fields' => ['id', 'user_id', 'user_name'],
                'search' => [
                    'id' => [$viewUser, 'in']
                ],
                'order_by' => ['no' => 'asc','id'=>'asc'],
                'with_user_table' => '1', // 关联user表获取user_accounts
            ];
            $personnelInfo = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
            if(!empty($personnelInfo)) {
                foreach ($personnelInfo as $key => $personnelItem) {
                    $userAccounts = '';
                    if(isset($personnelItem['personnel_files_to_user']) && !empty($personnelItem['personnel_files_to_user'])) {
                        $userAccounts = $personnelItem['personnel_files_to_user']['user_accounts'] ?? '';
                    }
                    // 拼接到 $result $userIds 上面去
                    $result[] = [
                        'user_id' => $personnelItem['id'] ?? '',
                        'user_accounts' => $userAccounts, // $userIdRelateAccounts[$personnelItem['user_id']] ?? '',
                        'user_name' => $personnelItem['user_name'] ?? '',
                    ];
                    $userIds[] = $personnelItem['id'] ?? '';
                }
            }
        } else {
            // 取管理范围内的人员，作为excel的行
            $result = $this->userRepository->getUserList(['fields' => ['user_id', 'user_accounts', 'user_name'], 'search' => ['user_id' => [$viewUser, 'in']]]);
            $userIds = array_column($result, 'user_id');
        }

        // 获取用户个人默认值
        /** @var Collection $personalDefaults */
        $personalDefaults = $this->salaryFieldPersonalDefaultHistoryRepository->entity
            ->select(['field_history_id', 'user_id', 'default_value'])
            ->whereIn('user_id', $userIds)
            ->whereIn('field_history_id', $historyIds)
            ->get()->toArray();

        // $historyIdPersonalDefaultMap->field_history_id->user_id
        $historyIdPersonalDefaultMap = [];
        foreach($personalDefaults as $default){
            $historyIdPersonalDefaultMap[$default['field_history_id']][$default['user_id']] = $default['default_value'];
        }

        // 获取已上报值
        $payDetails = $this->salaryRepository->entity
            ->select(['user_id', 'field_id', 'value'])
            ->leftJoin('salary_pay_detail', 'salary.salary_id', '=', 'salary_pay_detail.salary_id')
            ->where('salary.report_id', $reportId)
            ->get();

        //$fieldIdPayMap->user_id->field_id
        $fieldIdPayMap = [];
        foreach($payDetails as $detail){
            $fieldIdPayMap[$detail['user_id']][$detail['field_id']] = $detail['value'];
        }

        foreach ($result as $k => $user) {
            $data[$k] = [
                'user_id'       => $user['user_id'],
                'user_accounts' => $user['user_accounts'],
                'user_name'     => $user['user_name'],
            ];
            foreach($fieldsWithNoChildren as $historyId => $field){
                $tempValue = $field['field_default'];
                if($field['field_default_set'] == FieldDefaultSet::DEFAULT_VALUE && $field['field_type'] != FieldTypes::FROM_FILE){

                    $tempValue = isset($historyIdPersonalDefaultMap[$historyId][$user['user_id']])
                        ? (new DefaultValueField($field))->formatValue($historyIdPersonalDefaultMap[$historyId][$user['user_id']])
                        : $tempValue;

                    $tempValue = $fieldIdPayMap[$user['user_id']][$field['field_id']] ?? $tempValue;
                }
                $data[$k][$field['field_id']] = $tempValue;
            }
        }

        return ['header' => $header, 'data' => $data];
    }

    /**
     * 薪资录入导入
     * @param $data
     * @param $param
     * @return array|string
     */
    public function importSalary($data, $param)
    {
        // 当前用户信息，用于判断薪酬权限
        $own = $param['user_info'];
        $viewUser = $this->salaryReportSetService->getViewUser($own);
        if (count($data) == 0) {
            return '';
        }

        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];

        $where = [
            'report_id' => [$param['params']['report_id']],
        ];

        $oldData      = $this->salaryRepository->getSalaryListByWhere($where)->toArray();
        // 开了[无账号发薪]之后，这里面是档案id(可以直接进库，不用处理)
        $oldDataUsers = array_column($oldData, 'user_id');

        foreach ($data as $key => $value) {
            $value['report_id'] = $param['params']['report_id'];

            // 开了[无账号发薪]之后， $value['user_id'] 是档案id
            if (!isset($value['user_id']) || !$value['user_id']) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("salary.0x038004"));
                continue;
            }
            try{
                switch ($param['type']) {
                    case 1:
                        // 判断是否有权限
                        if(empty($viewUser) || !in_array($value['user_id'], $viewUser)){
                            $info['error']++;
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail(trans("salary.0x038016"));
                        } else {
                            if (!in_array($value['user_id'], $oldDataUsers)) {
                                if ($value['user_id'] == '') {
                                    continue 2;
                                }
                                foreach ($value as $k => $v) {
                                    $value[$k] = trim($v);
                                }
                                $result = $this->salaryEntryService->inputUserSalary($value, $own, false);
                                if (isset($result['code'])) {
                                    $data[$key]['importResult'] = importDataFail();
                                    $data[$key]['importReason'] = importDataFail(trans($result['code'][1].'.'.$result['code'][0]));
                                    continue 2;
                                }
                                $info['success']++;
                                $data[$key]['importResult'] = importDataSuccess();
                            } else {
                                $info['error']++;
                                $data[$key]['importResult'] = importDataFail();
                                $data[$key]['importReason'] = importDataFail(trans("common.0x000020"));
                            }
                        }
                        break;
                    case 2: //仅更新数据
                        if(empty($viewUser) || !in_array($value['user_id'], $viewUser)){
                            $info['error']++;
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail(trans("salary.0x038016"));
                        } else {
                            if (in_array($value['user_id'], $oldDataUsers)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = trim($v);
                                }
                                $result = $this->salaryEntryService->inputUserSalary($value, $own, false);
                                if (isset($result['code'])) {
                                    $data[$key]['importResult'] = importDataFail();
                                    $data[$key]['importReason'] = importDataFail(trans($result['code'][1].'.'.$result['code'][0]));
                                    continue 2;
                                }
                                $info['success']++;
                                $data[$key]['importResult'] = importDataSuccess();
                            } else {
                                $info['error']++;
                                $data[$key]['importResult'] = importDataFail();
                                $data[$key]['importReason'] = importDataFail(trans("common.0x000021"));
                            }
                        }
                        break;
                    case 4:
                        if(empty($viewUser) || !in_array($value['user_id'], $viewUser)){
                            $info['error']++;
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail(trans("salary.0x038016"));
                        } else {
                            foreach ($value as $k => $v) {
                                $value[$k] = trim($v);
                            }
                            $result = $this->salaryEntryService->inputUserSalary($value, $own, false);
                            if (isset($result['code'])) {
                                $data[$key]['importResult'] = importDataFail();
                                $data[$key]['importReason'] = importDataFail(trans($result['code'][1].'.'.$result['code'][0]));
                                continue 2;
                            }
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                        }
                        break;
                }
            }catch (\Throwable $e){
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                if($e instanceof ErrorMessage){
                    $message = $e->getErrorMessage();
                }else{
                    $message = $e->getMessage();
                }
                $data[$key]['importReason'] = importDataFail($message);
            }

        }

        return compact('data', 'info');
    }

    /**
     * 薪酬上报导入筛选!!!
     * @param $data
     * @return mixed
     */
    public function importSalaryFilter($data)
    {
        foreach ($data as $key => $value) {
            if (!isset($value['user_id']) || !$value['user_id']) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("salary.0x038004"));
            }
        }

        return $data;
    }

    public function importSalaryPersonalDefaultFilter($data, $params)
    {
        $own = $params['user_info'] ?? [];
        $viewUsers = $this->salaryReportSetService->getViewUser($own);
        $fieldId = $params['params']['field_id'];
        $fieldConfig = $this->salaryFieldRepository->getDetail($fieldId);

        foreach ($data as $key => &$value) {
            // 用户id为空
            if (!isset($value['user_id']) || !$value['user_id']) {
                $value['importResult'] = importDataFail();
                $value['importReason'] = importDataFail(trans("salary.0x038004"));
            }
            // 没有权限
            if (! in_array($value['user_id'], $viewUsers)){
                $value['importResult'] = importDataFail();
                $value['importReason'] = importDataFail(trans("salary.0x038016"));
            }

            try{
                $value['default_value'] = $this->salaryFieldService->formatPersonalDefault(
                    $value['default_value'], $fieldConfig
                );
            }catch (\Throwable $e){
                $value['importResult'] = importDataFail();
                $value['importReason'] = importDataFail(trans("salary.incorrect_format"));
            }
        }

        return $data;
    }

    /**
     * 薪资报表导出
     * @param $params
     * @return array
     */
    public function exportSalaryReport($params)
    {
        $data   = '<table border="1px" style="border-color:#2319DC;">';
        $params['page'] = 0;
        $myData = $this->salaryReportFormService->getSalaryReport($params, $params['user_info']['user_info']);
        if(!empty($myData['fields'])){
            $tr1 = '<tr style="height:30px;">'
                    .'<td style="background-color: #BCD6FC;width: 100px" rowspan="2">'.trans("salary.department").'</td>'
                    .'<td style="background-color: #BCD6FC;width: 100px" rowspan="2">'.trans("salary.user_name").'</td>'
                    .'<td style="background-color: #BCD6FC;width: 100px" rowspan="2">'.trans("salary.pay_year_and_month").'</td>'
                    .'<td style="background-color: #BCD6FC" rowspan="2">'.trans("salary.instructions").'</td>';
            $tr2 = '<tr style="height:30px;">';
            foreach($myData['fields'] as $key => $value){
                if(isset($value['children']) && !empty($value['children'])){
                    $tr1 .= '<td style="text-align: center;background-color: #BCD6FC;width: 100px" colspan="'.count($value['children']).'">'.$value['field_name'].'</td>';
                    foreach ($value['children'] as $k => $v) {
                        $tr2 .= '<td style="text-align: center;background-color: #BCD6FC;width: 100px">'.$v['field_name'].'</td>';
                    }
                }else{
                    $tr1 .= '<td style="text-align: center;background-color: #BCD6FC;width: 100px" rowspan="2">'.$value['field_name'].'</td>';
                }
            }
            $tr1 .= '</tr>';
            $tr2 .= '</tr>';
            $data .= $tr1 . $tr2;
        }
        // 20201030-dp-[无账号发薪]修改，返回的报表数据里有部门名称 dept_name ，不需要再次查询
        // $allUsers = app($this->userService)->getUserListsWithDept();
        // $userDepts = [];
        // foreach ($allUsers as $userInfo) {
        //    $userDepts[$userInfo['user_id']] = $userInfo['dept_name'];
        // }
        if(isset($myData['data']) && !empty($myData['data'])){
            foreach ($myData['data'] as $key => $value) {
                $userName    = $value['user_name'] ?? '';
                // $deptName = $userDepts[$value['user_id']] ?? '';
                $deptName    = $value['dept_name'] ?? '';
                // $date     = !empty($value['date']) ? str_replace('-', '.', $value['date']) : '';
                $date        = $value['date'] ?? '';
                $title       = $value['title'] ?? '';
                $tr          = '<tr style="height:30px;"><td>'.$deptName.'</td><td>'.$userName.'</td><td style=\'vnd.ms-excel.numberformat:@\'>'.$date.'</td><td>'.$title.'</td>';
                foreach($value['pay_details'] as $k => $v){
                    $tempValue = $v;
                    $tr .= '<td>'.$tempValue.'</td>';
                }
                $tr .= '</tr>';
                $data .= $tr;
            }
        }

        if (!empty($myData['total'])) {
            $data .= '<tr style="height:30px;"><td colspan="4">'.trans("salary.total").'</td>';
            foreach ($myData['total'] as $key => $value) {
                $data .= '<td>'.$value.'</td>';
            }
            $data .= '</tr>';
        }
        $data .= '</table>';
        return ['export_title' => trans("salary.salary_reports"), 'export_data' => $data];
    }

    /**
     * 导出我的薪资
     * @param $param
     * @return array
     */
    public function mySalaryReport($param)
    {
        $data = '<table border="1px" style="border-color:#2319DC;">';
        $userId = $param['user_info']['user_id'];
        $param['page'] = 0;
        $myData = $this->salaryReportFormService->getMySalaryList($userId, $param);
        if(!empty($myData['fields'])){
            $tr1 = '<tr style="height:30px">'
                    .'<td style="text-align: center;background-color: #BCD6FC" rowspan="2">'.trans('salary.pay_year_and_month').'</td>'
                    .'<td style="text-align: center;background-color: #BCD6FC" rowspan="2">'.trans('salary.instructions').'</td>';
            $tr2 = '<tr style="height:30px;">';
            foreach($myData['fields'] as $key => $value){
                if(isset($value['children']) && !empty($value['children'])){
                    $tr1 .= '<td style="text-align: center;background-color: #BCD6FC" colspan="'.count($value['children']).'">'.$value['field_name'].'</td>';
                    foreach ($value['children'] as $k => $v) {
                        $tr2 .= '<td style="text-align: center;width: 100px;background-color: #BCD6FC">'.$v['field_name'].'</td>';
                    }
                }else{
                    $tr1 .= '<td style="text-align: center;width: 100px;background-color: #BCD6FC" rowspan="2">'.$value['field_name'].'</td>';
                }
            }
            $tr1 .= '</tr>';
            $tr2 .= '</tr>';
            $data .= $tr1.$tr2;
        }
        if(isset($myData['data']) && !empty($myData['data'])){
            foreach ($myData['data'] as $key => $value) {
                if(isset($value['pay_details']) && !empty($value['pay_details'])){
                    // $date = !empty($value['date']) ? str_replace('-', '.', $value['date']) : '';
                    $date = $value['date'] ?? '';
                    $title = $value['title'] ?? '';
                    $tr = '<tr style="height:30px;"><td style=\'vnd.ms-excel.numberformat:@\'>'.$date.'</td><td>'.$title.'</td>';
                    foreach($value['pay_details'] as $k => $v){
                        $tempValue = $v ?? '0';
                        $tr .= '<td>'.$tempValue.'</td>';
                    }
                    $tr .= '</tr>';
                    $data .= $tr;
                }

            }
        }
        $data .= '</table>';
        return ['export_title' => trans("salary.my_salary"), 'export_data' => $data];
    }

    // 20201124-看起来是一个废弃了的函数
    public function getSalaryReportsExport($params)
    {
        $params['page'] = 0;
        $data   = '<table border="1px" style="border-color:#2319DC;">';
        $myData = $this->salaryReportFormService->getEmployeeSalaryList($params['report_id'], $params['user_info'], $params, false);
        if(!empty($myData['fields'])){
            $tr1 = '<tr style="height:30px;">
                    <td style="background-color: #BCD6FC;width: 100px" rowspan="2">'.trans("salary.department").'</td>
                    <td style="background-color: #BCD6FC;width: 100px" rowspan="2">'.trans("salary.user_name").'</td>
                    <td style="background-color: #BCD6FC;width: 100px" rowspan="2">'.trans("salary.post").'</td>';
            $tr2 = '<tr style="height:30px;">';
            foreach($myData['fields'] as $key => $value){
                if(isset($value['children']) && !empty($value['children'])){
                    $tr1 .= '<td style="text-align: center;background-color: #BCD6FC;width: 100px" colspan="'.count($value['children']).'">'.$value['field_name'].'</td>';
                    foreach ($value['children'] as $k => $v) {
                        $tr2 .= '<td style="text-align: center;background-color: #BCD6FC;width: 100px">'.$v['field_name'].'</td>';
                    }
                }else{
                    $tr1 .= '<td style="text-align: center;background-color: #BCD6FC;width: 100px" rowspan="2">'.$value['field_name'].'</td>';
                }
            }
            $tr1 .= '</tr>';
            $tr2 .= '</tr>';
            $data .= $tr1.$tr2;
        }
        if(isset($myData['data']) && !empty($myData['data'])){
            foreach ($myData['data'] as $key => $value) {
                $deptName = $value['dept_name'] ?? '';
                $userName = $value['user_name'] ?? '';
                $position = $value['role_name'] ?? '';
                $tr       = '<tr style="height:30px;">
                                <td>'.$deptName.'</td>
                                <td>'.$userName.'</td>
                                <td>'.$position.'</td>';
                foreach($value['pay_details'] as $k => $v){
                    $tempValue = $v ?? '0';
                    $tr .= '<td>'.$tempValue.'</td>';
                }
                $tr .= '</tr>';
                $data .= $tr;
            }
        }

        if (!empty($myData['total'])) {
            $data .= '<tr style="height:30px;"><td colspan="3">'.trans("salary.total").'</td>';
            foreach ($myData['total'] as $key => $value) {
                $data .= '<td>'.$value.'</td>';
            }
            $data .= '</tr>';
        }
        $data .= '</table>';
        return ['export_title' => trans("salary.salary_reports"), 'export_data' => $data];
    }

    /**
     * 个人默认值导入模板
     * @param $userInfo
     * @param $param
     * @return array
     */
    public function getSalaryPersonalDefaultTemplateData($userInfo, $param)
    {
        $fieldId = $param['field_id'];
        $own = $userInfo;
        $viewUsers = $this->salaryReportSetService->getViewUser($own);

        $field = $this->salaryFieldRepository->entity->where('field_id', $fieldId)->first();
        if(!$field){
            return ['code' => ['0x038022', 'salary']];
        }
        $header = [
            'user_id' => trans('salary.user') . 'ID',
            'user_name' => trans('salary.user_name'),
            'default_value' => trans('salary.default_value')
        ];
        $default = $field->field_default;
        $fieldObj = new DefaultValueField($field);

        $param = [];
        $param['payWithoutAccountConfig'] = $this->payWithoutAccountConfig;
        if($this->payWithoutAccountConfig == '1') {
            // 查人事档案
            $param['search'] = ['id' => [$viewUsers, 'in']];
            $param['order_by'] = ['no' => 'asc','id'=>'asc'];
            // head处理为“人事ID”
            $header['user_id'] = trans('common.personnelfiles') . 'ID';
        } else {
            $param['search'] = ['user_id' => [$viewUsers, 'in']];
            $param['order_by'] = ['list_number' => 'asc', 'user_name' => 'asc'];
        }

        $personalDefaultList = $this->salaryFieldPersonalDefaultRepository->getPersonalDefaultList($fieldId, $param);

        $data = $personalDefaultList->map(function ($value) use ($fieldObj, $default) {
                if($this->payWithoutAccountConfig == '1') {
                    $userId = $value->id;
                } else {
                    $userId = $value->user_id;
                }
                return [
                    'user_id' => $userId,
                    'user_name' => $value->user_name,
                    'default_value' => $value->salaryPersonalDefault->isEmpty()
                        ? $fieldObj->formatValue($default)
                        : $fieldObj->formatValue(($value->salaryPersonalDefault)[0]['default_value'])
                ];
            })->all();
        // $records = $this->userRepository->entity
        //     ->select(['user.user_id', 'user.user_name', 'salary_field_personal_default.default_value'])
        //     ->whereIn('user.user_id', $viewUsers)
        //     ->where('user.user_accounts', '!=', '')
        //     ->leftJoin('salary_field_personal_default', function ($join) use ($fieldId) {
        //         $join->on('user.user_id', '=', 'salary_field_personal_default.user_id')
        //             ->where('salary_field_personal_default.field_id', $fieldId);
        //     })
        //     ->get();
        // $data = [];
        // foreach ($records as $record){
        //     $one = [
        //         'user_id' => $record->user_id,
        //         'user_name' => $record->user_name,
        //         'default_value' => is_null($record->default_value) ?
        //             $fieldObj->formatValue($default) :
        //             $fieldObj->formatValue($record->default_value)
        //     ];
        //     $data[] = $one;
        // }

        return compact('header', 'data');
    }

    /**
     * 个人默认值导入
     * @param $data
     * @param $param
     * @return array
     */
    public function importSalaryPersonalDefault($data, $param)
    {
        $fieldId = $param['params']['field_id'];
        $viewUser = $this->salaryReportSetService->getViewUser($param['user_info']);
        $insertData = [];
        foreach($data as $key => $value){
            if(isset($value['importReason'])){
                continue;
            }
            $data[$key]['default_value'] = trim($data[$key]['default_value']);
            $insertData[] = $data[$key];
            $data[$key]['importResult'] = importDataSuccess();
        }

        $this->salaryFieldPersonalDefaultRepository->updatePersonalDefaultList($fieldId, $insertData);

        return ['data' => $data];
    }


}
