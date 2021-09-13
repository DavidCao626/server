<?php


namespace App\EofficeApp\Salary\Services;

use App\EofficeApp\Salary\Enums\FieldDefaultSet;
use App\EofficeApp\Salary\Exceptions\SalaryError;
use App\EofficeApp\Salary\Repositories\SalaryFieldHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldRepository;
use App\EofficeApp\Salary\Repositories\SalaryPayDetailRepository;
use App\EofficeApp\Salary\Repositories\SalaryReportRepository;
use App\EofficeApp\Salary\Repositories\SalaryRepository;
use App\EofficeApp\Salary\Services\SalaryField\DefaultValueField;
use App\EofficeApp\Salary\Services\SalaryField\SalaryFieldBuilder;
use App\EofficeApp\Salary\Services\SalaryField\SalaryFieldList;
use App\EofficeApp\System\Department\Services\DepartmentService;
use Eoffice;
use Batch;

/**
 * 薪资录入Service
 * Class SalaryEntryService
 * @package App\EofficeApp\Salary\Services
 */
class SalaryEntryService extends SalaryBaseService
{
    private $salaryReportSetService;
    public $salaryRepository;
    private $salaryFieldRepository;
    private $salaryReportRepository;
    private $salaryFieldService;
    private $salaryReportService;
    private $salaryFieldHistoryRepository;
    private $salaryPayDetailRepository;
    public $salaryFieldPersonalDefaultHistoryRepository;
    public $departmentService;

    public function __construct(
        SalaryReportSetService $salaryReportSetService,
        SalaryRepository $salaryRepository,
        SalaryFieldRepository $salaryFieldRepository,
        SalaryReportRepository $salaryReportRepository,
        SalaryFieldService $salaryFieldService,
        SalaryReportService $salaryReportService,
        SalaryFieldHistoryRepository $salaryFieldHistoryRepository,
        SalaryPayDetailRepository $salaryPayDetailRepository,
        SalaryFieldPersonalDefaultHistoryRepository $salaryFieldPersonalDefaultHistoryRepository,
        DepartmentService $departmentService
    )
    {
        parent::__construct();
        $this->salaryReportSetService = $salaryReportSetService;
        $this->salaryRepository = $salaryRepository;
        $this->salaryFieldRepository = $salaryFieldRepository;
        $this->salaryReportRepository = $salaryReportRepository;
        $this->salaryFieldService = $salaryFieldService;
        $this->salaryReportService = $salaryReportService;
        $this->salaryFieldHistoryRepository = $salaryFieldHistoryRepository;
        $this->salaryPayDetailRepository = $salaryPayDetailRepository;
        $this->salaryFieldPersonalDefaultHistoryRepository = $salaryFieldPersonalDefaultHistoryRepository;
        $this->departmentService = $departmentService;
        $this->userService = "App\EofficeApp\User\Services\UserService";
        $this->salaryReportFormService = "App\EofficeApp\Salary\Services\SalaryReportFormService";
        $this->salaryEntity = 'App\EofficeApp\Salary\Entities\SalaryEntity';
        $this->salaryPayDetailEntity = 'App\EofficeApp\Salary\Entities\SalaryPayDetailEntity';

    }

    /**
     * 薪资是否上报
     * @param $userId
     * @param $reportId
     * @param $own
     * @return array|int
     * @throws SalaryError
     */
    public function isSalaryReport($userId, $reportId, $own)
    {
        $where = [
            'report_id' => [$reportId],
            'user_id'   => [$userId],
        ];

        $this->salaryReportSetService->checkReportUserPermission($userId, $own);

        if ($salaryObj = $this->salaryRepository->isSalaryReport($where)) {
            return 2;
        }

        return 0;
    }

    /**
     * 新建薪酬上报流程
     * @param $data
     * @param $own
     * @param bool $flag
     * @return bool|array
     * @throws SalaryError
     */
    public function inputUserSalary($data, $own, $flag = true)
    {
        // 判断是否有权限
        if($flag){
            $this->salaryReportSetService->checkReportUserPermission($data['user_id'], $own);
        }

        //获取当前上报薪资数据
        $reportData = $this->salaryReportRepository->getDetail($data['report_id']);

        if (!$reportData || $reportData->status != 1 || $reportData->start_date > date('Y-m-d') || $reportData->end_date < date('Y-m-d')) {
            return ['code' => ['0x038003', 'salary']];
        }

        $values = $this->getUserSalaryDetailWithInput($data['user_id'], $data['report_id'], $data);

        $where = [
            'report_id' => [$data['report_id']],
            'user_id'   => [$data['user_id']],
        ];

        $salaryObj   = $this->salaryRepository->getDetailByWhere($where);
        // 如果已经上报，则更新数据
        if ($salaryObj) {
            $salaryId = $salaryObj->salary_id;
            $result =  $this->editSalary($values, $salaryId);
        }else{
            $result = $this->createSalary($values, $data['user_id'], $data['report_id']);
        }
        $this->inputSalaryMessage($data['user_id']);

        return $result;
    }

    // 发送上报提醒
    private function inputSalaryMessage($userId) {
        if(is_array($userId)) {
            $userIdArray = $userId;
        } else {
            $userIdArray = explode(',', trim($userId,','));
        }
        // $userId [无账号发薪]模式下，传的是人事档案id，取出里面的用户id，发消息
        $userId = $this->transPersonnelFileIds($userIdArray);
        $sendData = [
            'remindMark'  => 'salary-report',
            'toUser'      => $userId, // 可以接收数组
        ];
        Eoffice::sendMessage($sendData);
        return true;
    }

    /**
     * 薪资录入-部门批量上报
     * @param  [type]  $data [description]
     * @param  [type]  $own  [description]
     * @param  boolean $flag [description]
     * @return [type]        [description]
     */
    public function multiReportedUserSalary($data, $own)
    {
        $multiValues = $data['values'] ?? [];
        $reportId = $data['report_id'] ?? '';
        $users = $data['users'] ?? '';
        if(empty($multiValues) || !$reportId) {
            // 上报数据出错，无法上报
            return ['code' => ['0x038003', 'salary']];
        }
        // 判断权限
        $viewUser = $this->salaryReportSetService->getViewUser($own);
        // 开启[无账号发薪]时，判断权限这里，把查到的$viewUser转换为人事档案id
        $viewUser = $this->salaryReportSetService->transUserIds($viewUser);
        $diff     = array_diff($users, $viewUser);
        if(!empty($diff)){
            // 越权提醒
            return ['code' => ['0x038016', 'salary']];
        }

        //获取当前上报薪资数据
        $reportData = $this->salaryReportRepository->getDetail($reportId);
        if (!$reportData || $reportData->status != 1 || $reportData->start_date > date('Y-m-d') || $reportData->end_date < date('Y-m-d')) {
            return ['code' => ['0x038003', 'salary']];
        }
        $result = [];
        // 批量获取已上报数据，用于判断某用户是否已经上报
        $where = ['report_id' => [$reportId]];
        $salaryObj = $this->salaryRepository->getSalaryListByWhere($where);
        $salaryObj = $salaryObj->toArray();
        $salaryAlready = array_column($salaryObj,'salary_id','user_id');
        // 上报输入时获取薪资值--提取为公共参数
        $fields = $this->getEntryFieldsWithNoChildren($reportId);
        // 收集salary表，待批量更新的数据
        $salaryBatchUpdateData = [];
        // 收集salaryPayDetail表，待批量更新的数据
        $salaryPayDetailBatchInsertData = [];

        // 循环，处理上报过来的数据，并注意处理性能问题
        foreach ($multiValues as $key => $valueItem) {
            $userId = $valueItem['user_id'] ?? '';
            if($userId) {
                // 上报输入时获取薪资值--单个上报写法
                // $values = $this->getUserSalaryDetailWithInput($userId, $reportId, $data);
                // 上报输入时获取薪资值--批量上报
                $fields = $this->handleDefaultValuesInFieldsWithInput($fields, $valueItem['data']);
                $values = $this->getValueWithFieldsDefaultValueHandled($userId, $reportId, $fields);

                // 如果已经上报，则更新数据
                if (isset($salaryAlready[$userId])) {
                    $salaryId = $salaryAlready[$userId];
                    // 单个上报的时候的处理
                    // $result[$userId] =  $this->editSalary($values, $salaryId);
                    // 不再循环update salary，而是收集待更新的数据用 Batch 批量更新
                    $salaryBatchUpdateData[] = [
                        'updated_at' => date('Y-m-d H:i:s',time()),
                        'salary_id' => $salaryId
                    ];
                    // 对 salaryPayDetail 的操作保持原样，因为有updateOrCreate
                    foreach($values as $key => $value){
                        $this->salaryPayDetailRepository->entity->updateOrCreate(
                            ['salary_id' => $salaryId, 'field_id' => $key],
                            ['value' => $value]
                        );
                    }
                }else{
                    // 单个上报的时候的处理
                    // $result[$userId] = $this->createSalary($values, $userId, $reportId);
                    // 插入主表salary获取关联用的salary_id
                    $salaryObj = $this->salaryRepository->insertData([
                        'user_id' => $userId,
                        'report_id' => $reportId
                    ]);
                    $salaryId = $salaryObj->getKey();
                    // 组织批量插入用的数据
                    foreach($values as $key => $value){
                        $salaryPayDetailBatchInsertData[] = [$salaryId, $key, $value];
                    }
                }
            }
        }
        // 批量更新 salary
        if(!empty($salaryBatchUpdateData)) {
            Batch::update(app($this->salaryEntity),$salaryBatchUpdateData,'salary_id');
        }
        // 批量插入 salaryPayDetail
        if(!empty($salaryPayDetailBatchInsertData)) {
            $columns = ['salary_id','field_id','value'];
            Batch::insert(app($this->salaryPayDetailEntity), $columns, $salaryPayDetailBatchInsertData);
        }
        // 传入user数组，进行提醒
        $this->inputSalaryMessage($users);

        return $result;
    }

    /**
     * 修改薪酬
     * @param $subValues
     * @param $salaryId
     * @return bool
     */
    public function editSalary($subValues, $salaryId)
    {
        $this->salaryRepository->updateData(
            ['updated_at' => date('Y-m-d H:i:s',time())],
            ['salary_id' => $salaryId]
        );
        foreach($subValues as $key => $value){
            $this->salaryPayDetailRepository->entity->updateOrCreate(
                ['salary_id' => $salaryId, 'field_id' => $key],
                ['value' => $value]
            );
        }

        return true;
    }

    /**
     * 录入薪酬
     * @param $subValues
     * @param $userId
     * @param $reportId
     * @return object
     */
    public function createSalary($subValues, $userId, $reportId)
    {
        $salaryObj = $this->salaryRepository->insertData([
            'user_id' => $userId,
            'report_id' => $reportId
        ]);
        $salaryId = $salaryObj->getKey();

        $insertData = [];
        foreach($subValues as $key => $value){
            $insertData[] = [
                'salary_id' => $salaryId,
                'field_id' => $key,
                'value' => $value
            ];
        }

        return $this->salaryPayDetailRepository->entity->insert($insertData);
    }

    /**
     * 上报没有修改数据时
     * 1. 尚未上报获取默认薪资值
     * 2. 已上报过获取已上报值
     * @param $userId
     * @param $reportId
     * @return array
     * @throws SalaryError
     */
    public function getUserSalaryDetailWithNoInput($userId, $reportId)
    {
        $salary = $this->salaryRepository->getSalaryByReportIdAndUserId($reportId, $userId);
        if($salary) {
            $data = $this->salaryPayDetailRepository->getPayDetailsBySalaryId($salary->salary_id);
            $reported = 1;
        }else {
            $data = $this->getFieldsWithDefault($userId, $reportId);
            $reported = 0;
        }

        return compact('data', 'reported');
    }

    /**
     * 尚未上报时获取各项薪资值
     * @param $userId
     * @param $reportId
     * @return array
     * @throws SalaryError
     */
    public function getFieldsWithDefault($userId, $reportId)
    {
        $fields = $this->getEntryFieldsWithNoChildren($reportId);

        $fields = $this->handleDefaultValuesInFieldsWithNoInput($fields, $userId);

        return $this->getValueWithFieldsDefaultValueHandled($userId, $reportId, $fields);
    }

    /**
     * 获取上报字段
     * @param $reportId
     * @return mixed
     */
    public function getEntryFieldsWithNoChildren($reportId)
    {
        return $fields = $this->salaryFieldHistoryRepository->entity
            ->where('report_id', $reportId)
            ->where('has_children', 0)
            ->get()->toArray();
    }

    /**
     * 对已处理过默认值的薪资列表进行处理获取最终值
     * @param $userId
     *        $userId 字段，SalaryField 的 getValue() 里面，已经不再进行转换处理了，把转换提到外面来，可以提升性能
     * @param $reportId
     * @param $fields
     * @return array
     * @throws SalaryError
     */
    public function getValueWithFieldsDefaultValueHandled($userId, $reportId, $fields)
    {
        // [无账号发薪]预设值-系统数据 时，把传入的$userId(人事id)转换成用户id，才能取到绩效、考勤的数据
        $transUserArray = $this->transPersonnelFileIds([$userId]);
        $transformUserId = $transUserArray[0] ?? '';

        $fields = (new SalaryFieldList($fields))->sortListForCalculate();
        $result = [];
        foreach($fields as $key => $field) {
            try{
                $fieldBuilderObject = (new SalaryFieldBuilder($field))->build($fields);
                // 20201231-dp-传入一个数组，user_id 是代码运行的原始id，是传入的； transform_user_id是转换后的用户id，用于取绩效、考勤
                $userInfo = ['user_id' => $userId, 'transform_user_id' => $transformUserId];
                $value = $fieldBuilderObject->getFormatValue($userInfo, $reportId);
                $fields[$key]['result'] = $value;

                $result[$field['field_id']] = $value;
            }catch (\Throwable $e){
                $err = new SalaryError('0x038020');
                $err->setDynamic(trans('salary.0x038020', ['field' => $field['field_name']]));
                throw $err;
            }
        }

        return $result;
    }

    /**
     * 获取默认值类型的值附到field的result上
     * @param $fields
     * @param $userId
     * @return mixed
     */
    public function handleDefaultValuesInFieldsWithNoInput($fields, $userId)
    {
        foreach($fields as &$field) {
            if($field['field_default_set'] == FieldDefaultSet::DEFAULT_VALUE){
                $field['result'] = $this->salaryFieldPersonalDefaultHistoryRepository->getUserPersonalDefault(
                    $field, $userId
                );
            }
        }

        return $fields;
    }

    /**
     * 上报输入时获取薪资值
     * @param $userId
     * @param $reportId
     * @param $input
     * @throws SalaryError
     */
    public function getUserSalaryDetailWithInput($userId, $reportId, $input)
    {
        $fields = $this->getEntryFieldsWithNoChildren($reportId);

        $fields = $this->handleDefaultValuesInFieldsWithInput($fields, $input);

        return $this->getValueWithFieldsDefaultValueHandled($userId, $reportId, $fields);
    }

    /**
     * @param $fields
     * @param $input
     * @return mixed
     */
    public function handleDefaultValuesInFieldsWithInput($fields, $input)
    {
        foreach($fields as $key => $field){
            $fieldManager = (new SalaryFieldBuilder($field))->getFieldManager();
            if($fieldManager == DefaultValueField::class){
                $fields[$key]['result'] = isset($input[$field['field_id']])?$input[$field['field_id']]:'';
            }
        }

        return $fields;
    }

    /**
     * 获取上报时需要的薪资项(不含父级项)
     * @param $reportId
     * @return mixed
     */
    public function getEntryFieldLists($reportId)
    {
        $param['report_id'] = $reportId;
        $param['search'] = [
            'has_children' => [0]
        ];

        return $this->salaryFieldHistoryRepository->getSalaryItems($param);
    }

    /**
     * 按部门上报，初始化，获取：部门下人员(递归)；每个人的薪资值+已填报的值；
     * @param  [type] $reportId [description]
     * @param  [type] $userId   [description]
     * @return [type]           [description]
     */
    public function getDeptEntryInitValues($reportId, $deptId, $own)
    {
        $entryResult = ['entryValues' => [], 'entryFields' => []];
        // 获取当前用户管理权限
        // 获取有权限查看的用户id/人事id
        $viewUser = $this->salaryReportSetService->getViewUser($own);
        if (empty($viewUser)) {
            return ['fields' => [], 'data' => [], 'total' => 0];
        }

        // 开了[无账号发薪]
        if ($this->payWithoutAccountConfig == '1') {
            $userIds = [];
            // 去人事档案模块查数据（经过和测试确认，这里只上报某个部门，不穿透到子部门）
            $params = ['fields' => ['id', 'user_id', 'user_name'], 'search' => []];
            $params['search']['id'] = [$viewUser, 'in'];
            $params['search']['personnel_files.dept_id'] = [[$deptId], 'in'];
            $params['order_by'] = ['no' => 'asc','id'=>'asc'];
            $searchResult = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
        } else {
            // 原始状态，通过部门id获取所有本部门包括子部门的人员列表
            // 混合管理权限，根据部门id，取所有用户
            $param['search']['user_id'] = [$viewUser, 'in'];
            // $param['order_by'] = ['list_number' => 'asc', 'user_name' => 'asc'];
            // 穿透子部门（20210106-注释掉原来写法，不再穿透）
            // $searchResult = app($this->userService)->getUserListByDeptId($deptId, $param, $own);
            // 不穿透子部门写法
            $param["search"]["dept_id"] = ["0" => $deptId];
            $searchResult = app($this->userService)->userSystemList($param, $own);
            $searchResult = $searchResult['list'] ?? [];
        }
        if(!empty($searchResult)) {
            // 解析出 user_id 的数组，用于批量解析
            if ($this->payWithoutAccountConfig == '1') {
                $usersInfo = array_column($searchResult, 'id');
            } else {
                $usersInfo = array_column($searchResult, 'user_id');
            }
            // 批量获取已上报数据，用于判断某用户是否已经上报
            $where = ['report_id' => [$reportId]];
            $salaryObj = $this->salaryRepository->getSalaryListByWhere($where);
            $salaryObj = $salaryObj->toArray();
            $salaryAlready = array_column($salaryObj,'salary_id','user_id');
            // 上报输入时获取薪资值--提取为公共参数
            $fields = $this->getEntryFieldsWithNoChildren($reportId);
            $fields = (new SalaryFieldList($fields))->sortListForCalculate();
            // 批量获取字段默认值，返回 user_id . field_id => value
            $multiUserPersonalDefault = $this->getMultiUserPersonalDefault($fields, $usersInfo);
            // [无账号发薪]预设值-系统数据 时，转换人事id为人事id=>用户id，在下面 $fieldBuilderObject->getFormatValue() 的时候，传入用户id，以获取到绩效、考勤的数据
            $transUserArray = $this->transPersonnelFileIds($usersInfo, 'id', 'relate_array');

            foreach ($searchResult as $key => $userItem) {
                $entryItem = [];
                $userId = '';
                // 用于获取考勤数据的用户id
                $attendanceUserId = '';
                if ($this->payWithoutAccountConfig == '1') {
                    $userId = $userItem['id'] ?? '';
                    $attendanceUserId = $transUserArray[$userId] ?? '';
                } else {
                    $userId = $userItem['user_id'] ?? '';
                    $attendanceUserId = $userId;
                }
                $userName = $userItem['user_name'] ?? '';
                // 用户信息
                $entryItem['user_id'] = $userId;
                $entryItem['user_name'] = $userName;
                // 初始化 -- 原始处理
                // $entryValues = $this->getUserSalaryDetailWithNoInput($userId, $reportId);
                // 拆出来做优化
                if(isset($salaryAlready[$userId])) {
                    $data = $this->salaryPayDetailRepository->getPayDetailsBySalaryId($salaryAlready[$userId]);
                    $reported = 1;
                }else {
                    // 原来的处理
                    // $data = $this->getFieldsWithDefault($userId, $reportId);
                    // new处理
                    // $fields = $this->handleDefaultValuesInFieldsWithNoInput($fields, $userId);
                    foreach($fields as $key => $fieldItem) {
                        if($fieldItem['field_default_set'] == FieldDefaultSet::DEFAULT_VALUE){
                            $fieldId = $fieldItem['id'];
                            $fields[$key]['result'] = $multiUserPersonalDefault[$userId."_".$fieldId] ?? '';
                            // 以前的处理方法，有性能问题，已经改造为上面的写法200用户 20字段，7s-4s
                            // $field['result'] = $this->salaryFieldPersonalDefaultHistoryRepository->getUserPersonalDefault($field, $userId);
                        }
                    }
                    // $data = $this->getValueWithFieldsDefaultValueHandled($userId, $reportId, $fields);
                    $result = [];
                    foreach($fields as $key => $field) {
                        try{
                            $fieldBuilderObject = (new SalaryFieldBuilder($field))->build($fields);
                            // 20201231-dp-传入一个数组，user_id 是代码运行的原始id，是传入的； transform_user_id是转换后的用户id，用于取绩效、考勤
                            $userInfo = ['user_id' => $userId, 'transform_user_id' => $attendanceUserId];
                            $value = $fieldBuilderObject->getFormatValue($userInfo, $reportId);
                            $fields[$key]['result'] = $value;
                            $result[$field['field_id']] = $value;
                        }catch (\Throwable $e){
                            $err = new SalaryError('0x038020');
                            $err->setDynamic(trans('salary.0x038020', ['field' => $field['field_name']]));
                            throw $err;
                        }
                    }
                    $data = $result;
                    $reported = 0;
                }

                $entryItem['data'] = $data;
                $entryItem['reported'] = $reported;
                $entryResult['entryValues'][] = $entryItem;
            }
        }
        $tempFields = app($this->salaryReportFormService)->getShownHistoryFieldList($reportId);
        // 处理字段
        $fields = app($this->salaryReportFormService)->handleFieldsForReportForm($tempFields);
        $entryResult['entryFields'] = $fields;

        return $entryResult;
    }

    // 批量获取字段默认值，返回 user_id . field_id => value
    function getMultiUserPersonalDefault($fields, $usersInfo) {
        $result = [];
        // 传入 $fields 的 id数组 从 salaryFieldHistoryRepository 查 field_default 备用，只查一次
        $fieldIds = array_column($fields, 'id');
        $salaryFieldHistoryList = $this->salaryFieldHistoryRepository->getSalaryFieldHistoryListByWhere(['id' => [$fieldIds, 'in']])->toArray();
        $salaryFieldHistoryFieldDefaults = array_column($salaryFieldHistoryList, 'field_default', 'id');

        // 传入 $fields 的 id数组 从 SalaryFieldPersonalDefaultHistoryRepository 查 default_value 列表
        // 用于替换掉原来的循环获取，且，这里面有排序
        $defaultValueList = $this->salaryFieldPersonalDefaultHistoryRepository->getFieldHistoryDefaultList($fieldIds, $usersInfo);
        $defaultValueList = $defaultValueList->toArray();
        // 循环，重组数据，得到默认值数组，且，排除可能存在的多行问题
        $defaultValueInfo = [];
        if(!empty($defaultValueList)) {
            foreach ($defaultValueList as $key => $item) {
                $userId = $item['user_id'] ?? '';
                $fieldHistoryId = $item['field_history_id'] ?? '';
                $defaultValue = $item['default_value'] ?? '';
                if(!isset($defaultValueInfo[$userId."_".$fieldHistoryId])) {
                    $defaultValueInfo[$userId."_".$fieldHistoryId] = $defaultValue;
                }
            }
        }
        // 循环 字段和用户的矩阵 ，根据SalaryFieldPersonalDefaultHistoryRepository - getUserPersonalDefault函数的逻辑，生成 user_id => value 的对应关系
        if(!empty($fieldIds)) {
            foreach ($fieldIds as $key => $fieldId) {
                if(!empty($usersInfo)) {
                    foreach ($usersInfo as $key => $userId) {
                        if(isset($defaultValueInfo[$userId."_".$fieldId])) {
                            $result[$userId."_".$fieldId] = $defaultValueInfo[$userId."_".$fieldId];
                        } else {
                            $result[$userId."_".$fieldId] = $salaryFieldHistoryFieldDefaults[$fieldId] ?? '';
                        }
                    }
                }
            }
        }
        return $result;
    }

}
