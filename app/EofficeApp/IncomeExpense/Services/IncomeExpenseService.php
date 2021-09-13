<?php
namespace App\EofficeApp\IncomeExpense\Services;

use App\EofficeApp\Base\BaseService;
use Cache;
use Illuminate\Support\Arr;
use App\EofficeApp\LogCenter\Facades\LogCenter;
/**
 * 收支模块服务类，用于处理手机模块数据逻辑
 *
 * @author 李志军
 *
 * @since 2015-10-17
 */
class IncomeExpenseService extends BaseService
{
    /** @var object 收支方案资源库对象 */
    private $incomeExpensePlanRepository;

    /** @var object 收支方案类别资源库对象 */
    private $incomeExpensePlanTypeRepository;

    /** @var object 收支方案记录资源库对象 */
    private $incomeExpenseRecordsRepository;

    /** @var object 收支方案统计资源库对象 */
    private $incomeExpenseStatRepository;

    private $userRepository;
    /**
     * 注册收支方案相关资源库对象
     *
     * @param \App\EofficeApp\IncomeExpense\Repositories\IncomeExpensePlanRepository $incomeExpensePlanRepository
     * @param \App\EofficeApp\IncomeExpense\Repositories\IncomeExpensePlanTypeRepository $incomeExpensePlanTypeRepository
     * @param \App\EofficeApp\IncomeExpense\Repositories\IncomeExpenseRecordsRepository $incomeExpenseRecordsRepository
     * @param \App\EofficeApp\IncomeExpense\Repositories\IncomeExpenseStatRepository $incomeExpenseStatRepository
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function __construct()
    {
        parent::__construct();

        $this->incomeExpensePlanRepository = 'App\EofficeApp\IncomeExpense\Repositories\IncomeExpensePlanRepository';

        $this->incomeExpensePlanTypeRepository = 'App\EofficeApp\IncomeExpense\Repositories\IncomeExpensePlanTypeRepository';

        $this->incomeExpenseRecordsRepository = 'App\EofficeApp\IncomeExpense\Repositories\IncomeExpenseRecordsRepository';

        $this->incomeExpenseStatRepository = 'App\EofficeApp\IncomeExpense\Repositories\IncomeExpenseStatRepository';

        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';

        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';

        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';

        $this->userService = 'App\EofficeApp\User\Services\UserService';
    }
    /**
     * 获取方案类别列表
     *
     * @param array $param 查询条件参数
     *
     * @return array 方案类别列表
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function listPlanType($param)
    {
        $param = $this->parseParams($param);
        if (!isset($param['type'])) {
            $search['search'] = [
                'plan_status' => [1],
                'plan_type_id' => [0],
            ];
            $record = $this->listPlan($search, own());
            if ($record['total'] > 0) {
                $data['total'] = app($this->incomeExpensePlanTypeRepository)->getPlanTypeCount($param);
                $data['total'] = $data['total'] + 1;
                $data['list'] = app($this->incomeExpensePlanTypeRepository)->listPlanType($param);
                $list['plan_type_id'] = 0;
                $list['plan_type_name'] = trans("incomeexpense.Unfiled");
                $data['list'][] = $list;
                return $data;
            } else {
                return $this->response(app($this->incomeExpensePlanTypeRepository), 'getPlanTypeCount', 'listPlanType', $this->parseParams($param));
            }

        } else {
            return $this->response(app($this->incomeExpensePlanTypeRepository), 'getPlanTypeCount', 'listPlanType', $this->parseParams($param));
        }

    }
    /**
     * 新建方案类别
     *
     * @param array $data 方案类别数据
     *
     * @return array 方案类别id
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function addPlanType($data)
    {
        $planTypeData = [
            'plan_type_name' => $data['plan_type_name'],
            'plan_type_description' => $this->defaultValue('plan_type_description', $data, ''),
        ];

        if (!$planType = app($this->incomeExpensePlanTypeRepository)->addPlanType($planTypeData)) {
            return ['code' => ['0x000003', 'common']];
        }

        return ['plan_type_id' => $planType->plan_type_id];
    }
    /**
     * 编辑方案类别
     *
     * @param array $data 方案类别数据
     * @param int $planTypeId 方案类别id
     *
     * @return int | array 编辑结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function editPlanType($data, $planTypeId)
    {
        if ($planTypeId == 0) {
            return ['code' => ['0x018002', 'incomeexpense']];
        }

        $planTypeData = [
            'plan_type_name' => $data['plan_type_name'],
            'plan_type_description' => $this->defaultValue('plan_type_description', $data, ''),
        ];

        if (!app($this->incomeExpensePlanTypeRepository)->editPlanType($planTypeData, $planTypeId)) {
            // return ['code' => ['0x000003', 'common']];
        }

        return true;
    }
    /**
     * 获取方案类别详情
     *
     * @param int $planTypeId 方案类别id
     *
     * @return object 方案类别详情
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function showPlanType($planTypeId)
    {
        if ($planTypeId == 0) {
            return ['code' => ['0x018002', 'incomeexpense']];
        }

        return app($this->incomeExpensePlanTypeRepository)->showPlanType($planTypeId);
    }
    /**
     * 删除方案类别
     *
     * @param int $planTypeId 方案类别id
     *
     * @return int | array 删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function deletePlanType($planTypeId, $own)
    {
        if ($planTypeId == 0) {
            return ['code' => ['0x018002', 'incomeexpense']];
        }

        $planTypeIds = explode(',', $planTypeId);

        foreach ($planTypeIds as $planTypeId) {
            if (app($this->incomeExpensePlanRepository)->getPlanCount(['search' => ['plan_type_id' => [$planTypeId]], 'own' => $own])) {
                return ['code' => ['0x018012', 'incomeexpense']];
            }
        }

        if (!app($this->incomeExpensePlanTypeRepository)->deletePlanType($planTypeIds)) {
            return ['code' => ['0x000003', 'common']];
        }

        return true;
    }
    public function getProceedListPlan($param, $own)
    {
        $param = $this->parseParams($param);
        $param['search']['plan_status'] = [1];
        return $this->listPlan($param, $own);
    }
    /**
     * 获取收支方案列表
     *
     * @param array $param 查询条件参数
     *
     * @return array 收支方案列表
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function listPlan($param, $own)
    {
        $param = $this->parseParams($param);
        $param['own'] = $own;

        $recordCount = false;
        if (isset($param['search']['type'])) {
            $recordCount = true;
            unset($param['search']['type']);
        }
        $data = $this->response(app($this->incomeExpensePlanRepository), 'getPlanCount', 'listPlan', $param);

        $list = [];

        if ($data['list']) {
            $planTypeMap = app($this->incomeExpensePlanTypeRepository)->getAllPlanTypeMap();
            foreach ($data['list'] as $v) {
                $v->plan_type_name = $v->plan_type_id != 0
                ? (isset($planTypeMap[$v->plan_type_id]) ? $planTypeMap[$v->plan_type_id] : null)
                : null;
                if ($recordCount) {
                    $v->record_count = app($this->incomeExpenseRecordsRepository)->getRecordCountByPlanId($v->plan_id);
                }

                $list[] = $v;
            }
        }
        $data['list'] = $list;
        return $data;
    }
    public function listAllPlan($param, $own)
    {
        $param = $this->parseParams($param);

        $recordCount = false;
        if (isset($param['search']['type'])) {
            $recordCount = true;
            unset($param['search']['type']);
        }
        $tableKey = 'income_expense_plan';
        $data = app($this->formModelingService)->getCustomDataLists($param, $tableKey, $own);
        $list = [];
        if ($data['list']) {
            $planTypeMap = app($this->incomeExpensePlanTypeRepository)->getAllPlanTypeMap();
            foreach ($data['list'] as $k => $v) {
                $v->plan_type_name = $v->plan_type_id != 0
                ? (isset($planTypeMap[$v->plan_type_id]) ? $planTypeMap[$v->plan_type_id] : null)
                : null;

                if ($recordCount) {
                    $v->record_count = app($this->incomeExpenseRecordsRepository)->getRecordCountByPlanId($v->plan_id);
                }
                $dealCycle = (array) $v;
                $dealCycle['plan_status'] = $dealCycle['raw_plan_status'];
                $v->real_cycle = $this->getPlanRealCycle($dealCycle);
                if ($v->plan_type_id == '') {
                    $v->plan_type_id = trans('incomeexpense.Unfiled');
                }
                if ($v->raw_plan_status == 0) {
                    $v->plan_status = trans('incomeexpense.not_starting');
                } else if ($v->raw_plan_status == 1) {
                    $v->plan_status = trans('incomeexpense.have_in_hand');
                } else if ($v->raw_plan_status == 2) {
                    $v->plan_status = trans('incomeexpense.finished');
                }
                $data['list'][$k] = $v;
            }
        }

        return $data;
    }

    /**
     * 新建方案外发
     * @author yangxingqiang
     * @param $_data
     * @return array
     */
    public function addOutSendPlan($_data)
    {
        $_data = $_data['data'];
        if (!isset($_data['plan_code']) || empty($_data['plan_code'])) {
            return ['code' => ['0x018004', 'incomeexpense']];
        }
        if (!isset($_data['plan_name']) || empty($_data['plan_name'])) {
            return ['code' => ['0x018003', 'incomeexpense']];
        }
        if (isset($_data['plan_cycle_unit'])) {
            if (strpos($_data['plan_cycle_unit'], '天') !== false) {
                $_data['plan_cycle_unit'] = 1;
            } elseif (strpos($_data['plan_cycle_unit'], '天') !== false) {
                $_data['plan_cycle_unit'] = 2;
            }
        }
        if (!empty($_data['plan_code'])) {
            $param['search'] = ['plan_code' => [$_data['plan_code']]];
            $count = app($this->incomeExpensePlanRepository)->getAllPlanCount($param);
            if ($count > 0) {
                return ['code' => ['0x018013', 'incomeexpense']];
            }
        }
        if (isset($_data['all_user'])) {
            $_data['all_user'] = $_data['all_user'] == 1 ? 0 : 1;
        }
        // $data = [
        //     'plan_code' => $_data['plan_code'],
        //     'plan_name' => $_data['plan_name'],
        //     'plan_type_id' => $this->defaultValue('plan_type_id', $_data, 0),
        //     'expense_budget_alert' => $this->defaultValue('expense_budget_alert', $_data, 1),
        //     'plan_cycle' => $this->defaultValue('plan_cycle', $_data, 1),
        //     'plan_cycle_unit' => $this->defaultValue('plan_cycle_unit', $_data, 1),
        //     'all_user' => $this->defaultValue('all_user', $_data, 1),
        //     'is_start' => $this->defaultValue('is_start', $_data, 1),
        //     'creator' => $_data['creator'],
        //     'expense_budget' => $this->defaultValue('expense_budget', $_data, 0.00),
        //     'income_budget' => $this->defaultValue('income_budget', $_data, 0.00),
        //     'plan_description' => $this->defaultValue('plan_description', $_data, ''),
        // ];

        $_data['plan_type_id'] = $this->defaultValue('plan_type_id', $_data, 0);
        $_data['expense_budget_alert'] = $this->defaultValue('expense_budget_alert', $_data, 1);
        $_data['plan_cycle'] = $this->defaultValue('plan_cycle', $_data, 1);
        $_data['plan_cycle_unit'] = $this->defaultValue('plan_cycle_unit', $_data, 1);
        $_data['all_user'] = $this->defaultValue('all_user', $_data, 2);
        $_data['is_start'] = $this->defaultValue('is_start', $_data, 0);
        $_data['expense_budget'] = $this->defaultValue('expense_budget', $_data, 0.00);
        $_data['income_budget'] = $this->defaultValue('income_budget', $_data, 0.00);
        $_data['dept_id'] = $this->defaultValue('dept_id', $_data, '') ? $_data['dept_id'] : '';
        $_data['role_id'] = $this->defaultValue('role_id', $_data, '') ? $_data['role_id'] : '';
        $_data['user_id'] = $this->defaultValue('user_id', $_data, '') ? $_data['user_id'] : '';
        $_data['created_at'] = date('Y-m-d H:i:s');
//        $_data['creator'] = isset($_data['creator'])&&!empty($_data['creator'])?$_data['creator'] : own('user_id');
        $_data['creator'] = isset($_data['creator']) && !empty($_data['creator']) ? $_data['creator'] : ($_data['current_user_id'] ?? '');
        if ($_data['is_start'] == 1) {
            $_data['plan_status'] = 1;
            $_data['begin_time'] = date('Y-m-d H:i:s');
        } else {
            $_data['plan_status'] = 0;
        }
        $planId = app($this->formModelingService)->addCustomData($_data, 'income_expense_plan');
        if (isset($planId['code'])) {
            return $planId;
        }
        $update['dept_id'] = $_data['dept_id']?$_data['dept_id']:'';
        $update['role_id'] = $_data['role_id']?$_data['role_id']:'';
        $update['user_id'] = $_data['user_id']?$_data['user_id']:'';
        app($this->incomeExpensePlanRepository)->updateData($update, ['plan_id' => $planId]);
        // if (isset($_data['attachment_id'])) {
        //     app($this->attachmentService)->attachmentRelation("incomeexpense_plan", $planId, $_data['attachment_id']);
        // }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'income_expense_plan',
                    'field_to' => 'plan_id',
                    'id_to' => $planId,
                ],

            ],
        ];
    }

    /**
     * 更新方案外发
     * @author yangxingqiang
     * @param $data
     * @return array
     */
    public function updateOutSendPlan($data)
    {
        if (isset($data['unique_id']) && $data['unique_id']) {
            $unique_id = $data['unique_id'];
            $_data = $data['data'];
            if (isset($_data['plan_code']) && !empty($_data['plan_code'])) {
                $param['search'] = ['plan_code' => [$_data['plan_code']]];
                $param['search']['plan_id'] = [$unique_id, '<>'];
                $count = app($this->incomeExpensePlanRepository)->getAllPlanCount($param);
                if ($count > 0) {
                    return ['code' => ['0x018013', 'incomeexpense']];
                }
            }
            if (isset($_data['all_user'])) {
                $_data['all_user'] = $_data['all_user'] == 1 ? 0 : 1;
            }
            if (isset($_data['is_start'])) {
                if ($_data['is_start'] == 1) {
                    $_data['plan_status'] = 1;
                    $_data['begin_time'] = date('Y-m-d H:i:s');
                } else {
                    $_data['plan_status'] = 0;
                }
            }
            $_data['updated_at'] = date('Y-m-d H:i:s');
            $res = app($this->formModelingService)->editCustomData($_data, 'income_expense_plan', $unique_id);
            if (isset($res['code']) || !$res) {
                if (isset($res['code'])) {
                    return $res;
                } else {
                    return ['code' => ['0x016033', 'fields']];
                }
            } else {
                return [
                    'status' => 1,
                    'dataForLog' => [
                        [
                            'table_to' => 'income_expense_plan',
                            'field_to' => 'plan_id',
                            'id_to' => $unique_id,
                        ],

                    ],
                ];
            }
        } else {
            return ['code' => ['0x016007', 'fields']];
        }
    }

    /**
     * 删除方案外发
     * @author yangxingqiang
     * @param $data
     * @return array|int
     */
    public function deleteOutSendPlan($data)
    {
        if (isset($data['unique_id']) && $data['unique_id']) {
            $unique_id = $data['unique_id'];
            $res = $this->deletePlan($unique_id);
            if ($res) {
                if(isset($res['code'])){
                    return $res;
                }else{
                    return [
                        'status' => 1,
                        'dataForLog' => [
                            [
                                'table_to' => 'income_expense_plan',
                                'field_to' => 'plan_id',
                                'id_to' => $unique_id,
                            ],
                        ],
                    ];
                }
            } else {
                return $res;
            }
        } else {
            return ['code' => ['0x016007', 'fields']];
        }
    }
    /**
     * 新建收支方案
     *
     * @param array $data 收支方案数据
     *
     * @return array 方案id
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function addPlan($_data, $currentUserId)
    {
        // $data = [
        //     'plan_code' => $_data['plan_code'],
        //     'plan_name' => $_data['plan_name'],
        //     'plan_type_id' => $this->defaultValue('plan_type_id', $_data, 0),
        //     'expense_budget_alert' => $this->defaultValue('expense_budget_alert', $_data, 1),
        //     'plan_cycle' => $this->defaultValue('plan_cycle', $_data, 1),
        //     'plan_cycle_unit' => $this->defaultValue('plan_cycle_unit', $_data, 1),
        //     'all_user' => $this->defaultValue('all_user', $_data, 2),
        //     'is_start' => $this->defaultValue('is_start', $_data, 0),
        //     'creator' => $currentUserId,
        //     'expense_budget' => $this->defaultValue('expense_budget', $_data, 0.00),
        //     'income_budget' => $this->defaultValue('income_budget', $_data, 0.00),
        //     'dept_id' => implode(',', $this->defaultValue('dept_id', $_data, [])),
        //     'role_id' => implode(',', $this->defaultValue('role_id', $_data, [])),
        //     'user_id' => implode(',', $this->defaultValue('user_id', $_data, [])),
        //     'plan_description' => $this->defaultValue('plan_description', $_data, ''),
        // ];
        $_data['plan_type_id'] = $this->defaultValue('plan_type_id', $_data, 0);
        $_data['expense_budget_alert'] = $this->defaultValue('expense_budget_alert', $_data, 1);
        $_data['plan_cycle'] = $this->defaultValue('plan_cycle', $_data, '');
        $_data['plan_cycle_unit'] = $this->defaultValue('plan_cycle_unit', $_data, 1);
        $_data['all_user'] = $this->defaultValue('all_user', $_data, 2);
        $_data['is_start'] = $this->defaultValue('is_start', $_data, 0);
        $_data['expense_budget'] = $this->defaultValue('expense_budget', $_data, 0.00);
        $_data['income_budget'] = $this->defaultValue('income_budget', $_data, 0.00);
        $_data['created_at'] = date('Y-m-d H:i:s');
        $_data['dept_id'] = implode(',', $this->defaultValue('dept_id', $_data, []) ? $_data['dept_id'] : []);
        $_data['role_id'] = implode(',', $this->defaultValue('role_id', $_data, []) ? $_data['role_id'] : []);
        $_data['user_id'] = implode(',', $this->defaultValue('user_id', $_data, []) ? $_data['user_id'] : []);
        if ($_data['all_user'] == 1) {
            $_data['dept_id'] = '';
            $_data['role_id'] = '';
            $_data['user_id'] = '';
        }
        if ($_data['is_start'] == 1) {
            $_data['plan_status'] = 1;
            $_data['begin_time'] = date('Y-m-d H:i:s');
        } else {
            $_data['plan_status'] = 0;
        }
        $planId = app($this->formModelingService)->addCustomData($_data, 'income_expense_plan');
        if (isset($planId['code'])) {
            return $planId;
        }
        // if (isset($_data['attachment_id'])) {
        //     app($this->attachmentService)->attachmentRelation("incomeexpense_plan", $planId, $_data['attachment_id']);
        // }

        return ['plan_id' => $planId];
    }
    /**
     * 编辑收支方案
     *
     * @param array $data 收支方案数据
     *
     * @param int $planId 方案id
     *
     * @return int | array  编辑结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function editPlan($_data, $planId)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }
        $_data['plan_type_id'] = $this->defaultValue('plan_type_id', $_data, 0);
        $_data['expense_budget_alert'] = $this->defaultValue('expense_budget_alert', $_data, 1);
        $_data['plan_cycle'] = $this->defaultValue('plan_cycle', $_data, 1);
        $_data['plan_cycle_unit'] = $this->defaultValue('plan_cycle_unit', $_data, 1);
        $_data['all_user'] = $this->defaultValue('all_user', $_data, 2);
        $_data['is_start'] = $this->defaultValue('is_start', $_data, 0);
        $_data['expense_budget'] = $this->defaultValue('expense_budget', $_data, 0.00);
        $_data['income_budget'] = $this->defaultValue('income_budget', $_data, 0.00);
        if ($_data['all_user'] == 1) {
            $_data['dept_id'] = '';
            $_data['role_id'] = '';
            $_data['user_id'] = '';
        }else{
            $_data['dept_id'] = implode(',', $this->defaultValue('dept_id', $_data, []) ? $_data['dept_id'] : []);
            $_data['role_id'] = implode(',', $this->defaultValue('role_id', $_data, []) ? $_data['role_id'] : []);
            $_data['user_id'] = implode(',', $this->defaultValue('user_id', $_data, []) ? $_data['user_id'] : []);
        }
        if ($_data['plan_status'] == 0) {
        } else if ($_data['plan_status'] == 1) {
            $_data['begin_time'] = date('Y-m-d H:i:s');
            $_data['is_start'] = 1;
            $_data['end_time'] = '';
        } else {
            $_data['is_start'] = 0;
            $_data['end_time'] = date('Y-m-d H:i:s');
        }
        $planId = app($this->formModelingService)->editCustomData($_data, 'income_expense_plan', $planId);
        if (isset($planId['code'])) {
            return $planId;
        }

        // if (!app($this->incomeExpensePlanRepository)->editPlan($data, $planId)) {
        //     return ['code' => ['0x000003', 'common']];
        // }

        // if (isset($_data['attachment_id'])) {
        //     app($this->attachmentService)->attachmentRelation("incomeexpense_plan", $planId, $_data['attachment_id']);
        // }

        return true;
    }
    public function showEditPlan($planId)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }
        $plan = app($this->formModelingService)->getCustomDataDetail('income_expense_plan', $planId);
        // $plan->plan_type_id = $plan->plan_type_id == 0 ? '' : $plan->plan_type_id;
        // $plan->user_id = explode(',', $plan->user_id);
        // $plan->dept_id = $this->stringArrayInteger(explode(',', $plan->dept_id));
        // $plan->role_id = $this->stringArrayInteger(explode(',', $plan->role_id));
        // $plan->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId([
        //     'entity_table' => 'incomeexpense_plan', 'entity_id' => $planId]);
        return $plan;
    }
    /**
     * 获取收支方案详情
     *
     * @param int $planId 收支方案id
     *
     * @return object 方案详情
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function showPlan($planId, $own)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }
        if (!app($this->incomeExpensePlanRepository)->hasShowPurview($planId, $own)) {
            return ['code' => ['0x000006', 'common']];
        }
        $plan = app($this->formModelingService)->getCustomDataDetail('income_expense_plan', $planId, $own);
        $plan['real_cycle'] = $this->getPlanRealCycle($plan);
        $plan['real_income_expense'] = $plan['real_income'] - $plan['real_expense'];
        $plan['budget_income_expense'] = $plan['income_budget'] - $plan['expense_budget'];
        $plan['creator_name'] = app($this->userService)->getUserName($plan['creator']);
        if (empty($plan['plan_type_id'])) {
            $plan['plan_type_name'] = trans('incomeexpense.Unfiled');
        } else {
            $plan['plan_type_name'] = $this->showPlanType($plan['plan_type_id'])->plan_type_name;
        }
        $moneyMembers = ['real_income', 'real_expense', 'income_budget', 'expense_budget', 'real_income_expense', 'budget_income_expense'];
        foreach ($moneyMembers as $money) {
            $plan[$money . '2'] = $this->money2($plan[$money]);
        }
        // unset($plan->user_id, $plan->dept_id, $plan->role_id);
        return $plan;
    }
    public function hasShowPurview($id)
    {
        $own = own('');
        if (!app($this->incomeExpensePlanRepository)->hasShowPurview($id, $own)) {
            return ['code' => ['0x000006', 'common']];
        }
        return true;
    }
    private function getPlanRealCycle($plan)
    {
        $beginTime = strtotime($plan['begin_time']);

        $endTime = strtotime($plan['plan_status'] == 1 ? date('Y-m-d H:i:s') : $plan['end_time']);
        return $plan['plan_status'] == 0 ? '' : round(($endTime - $beginTime) / 86400, 2);
    }
    public function dealIncomeExpenseField($plans, $param)
    {
        if (isset($param['field']) && $param['field'] == 'real_cycle') {
            foreach ($plans as $key => $plan) {
                $beginTime = strtotime($plan->begin_time);
                $endTime = strtotime($plan->plan_status == 1 ? date('Y-m-d H:i:s') : $plan->end_time);
                return $plan->plan_status == 0 ? '' : round(($endTime - $beginTime) / 86400, 2);
            }
        }
        return false;
    }
    private function stringArrayInteger($data)
    {
        for ($i = 0; $i < count($data); $i++) {
            $data[$i] = intval($data[$i]);
        }

        return $data;
    }
    public function getPlanCode()
    {
        $code = app($this->incomeExpensePlanRepository)->getPlanCode();

        $length = ($length = strlen($code)) > 5 ? $length : 5;

        return 'FA' . str_pad($code, $length, "0", STR_PAD_LEFT);
    }
    /**
     * 删除收支方案
     *
     * @param int $planId 收支方案id
     *
     * @return int | array 删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function deletePlan($planId)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }

        $planIds = explode(',', $planId);

        foreach ($planIds as $planId) {
            $plan = app($this->incomeExpensePlanRepository)->getDetail($planId);
            if(empty($plan)){
                return ['code' => ['0x016035', 'fields']];
            }
            if ($plan->plan_status == 1) {
                return ['code' => ['0x018014', 'incomeexpense']];
            }

            if (app($this->incomeExpenseRecordsRepository)->getRecordCountByPlanId($planId)) {
                return ['code' => ['0x018017', 'incomeexpense']];
            }
        }

        if (!app($this->incomeExpensePlanRepository)->deletePlan($planIds)) {
            return ['code' => ['0x000003', 'common']];
        }

        return true;
    }
    /**
     * 获取假删除收支方案列表
     *
     * @param array $param 查询参数
     *
     * @return array 假删除收支方案列表
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function listTrashedPlan($param)
    {
        return $this->response(app($this->incomeExpensePlanRepository), 'getTrashedPlanCount', 'listTrashedPlan', $this->parseParams($param));
    }
    /**
     * 恢复软删除的方案
     *
     * @param string $planId
     *
     * @return array｜int  删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function recoverTrashedPlan($planId)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }

        if (app($this->incomeExpensePlanRepository)->recoverTrashedPlan(explode(',', $planId))) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 彻底销毁软删除的方案
     *
     * @param string $planId
     *
     * @return array|int 销毁结果
     *
     * @author 李志军
     *
     *  @since 2015-10-27
     */
    public function destroyTrashedPlan($planId)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }

        if (app($this->incomeExpensePlanRepository)->destroyTrashedPlan(explode(',', $planId))) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 获取收支记录列表
     *
     * @param array $param 查询参数
     *
     * @return array  收支记录列表
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function listRecord($param, $own)
    {
        $param = $this->parseParams($param);

        if (isset($param['fields'])) {
            $param['fields'] = $this->handleRecordFields($param['fields']);
        }

        $param['search'] = $this->handleRecordSearch($param);

        $response = $this->defaultValue('response', $param, 'both');

        if ($response == 'both' || $response == 'count') {
            $count = app($this->incomeExpenseRecordsRepository)->getRecordCount($param['search'], $own);
        }

        $list = [];

        if (($response == 'both' && $count > 0) || $response == 'data') {
            $moneyMembers = ['expense', 'income', 'expense_budget', 'income_budget', 'real_expense', 'real_income'];

            foreach (app($this->incomeExpenseRecordsRepository)->listRecord($param, $own) as $record) {
                foreach ($moneyMembers as $money) {
                    $record->{$money . '2'} = $record->{$money} ? $this->money2($record->{$money}) : '';
                    $record->{$money . '3'} = $record->{$money};
                }

                $record->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'incomeexpense_record', 'entity_id' => $record->record_id]);
                $record->income_detail = $record->income_detail ? json_decode($record->income_detail, true) : '';
                $record->expense_detail = $record->expense_detail ? json_decode($record->expense_detail, true) : '';
                $record->record_extra = $record->record_extra ? json_decode($record->record_extra, true) : '';
                $record->record_desc2 = $record->record_desc;
                $record->record_time2 = $record->record_time;
                $list[] = $record;
            }
        }

        return $response == 'both'
        ? ['total' => $count, 'list' => $list]
        : ($response == 'data' ? $list : $count);
    }
    private function handleRecordSearch($param)
    {
        if (!isset($param['search'])) {
            return [];
        }

        $search = [];

        foreach ($param['search'] as $k => $v) {
            $search['income_expense_records.' . $k] = $v;
        }

        return $search;
    }
    /**
     * 新建外发收支记录
     *
     * @param array $data 外发数据
     *
     * @return
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function addOutSendRecord($_data)
    {
        if (!isset($_data['plan_id'])) {
            return ['code' => ['0x018003', 'incomeexpense']];
        } else {
            if (is_array($_data['plan_id'])) {
                foreach ($_data['plan_id'] as $key => $value) {
                    if (empty($value)) {
                        return ['code' => ['0x018003', 'incomeexpense']];
                    }
                }
            }
        }
        if (!isset($_data['creator'])) {
            return ['code' => ['0x018019', 'incomeexpense']];
        }
        if (!isset($_data['record_time'])) {
            return ['code' => ['0x018022', 'incomeexpense']];
        }
        if (!isset($_data['run_id'])) {
            return ['code' => ['0x018023', 'incomeexpense']];
        }
        if (!isset($_data['flow_id'])) {
            return ['code' => ['0x018025', 'incomeexpense']];
        }
        if (!isset($_data['run_name'])) {
            return ['code' => ['0x018024', 'incomeexpense']];
        }
        if (!isset($_data['income']) || !isset($_data['expense'])) {
            return ['code' => ['0x018006', 'incomeexpense']];
        }
//        if (is_array($_data['creator'])) {
//            $_data['creator'] = $_data['creator'][0];
//        }
        if (is_array($_data['plan_id']) && count($_data['plan_id']) > 1) {
            $res = $_data;
            foreach ($_data['plan_id'] as $key => $value) {
                if(empty($_data['creator'][$key])){
                    return ['code' => ['0x018019', 'incomeexpense']];
                }
                $res['income_reason'] = isset($_data['income_reason'][$key]) ? $_data['income_reason'][$key] : '';
                $res['income'] = isset($_data['income'][$key]) ? [$_data['income'][$key]] : [];
                $res['expense'] = isset($_data['expense'][$key]) ? [$_data['expense'][$key]] : [];
                $res['expense_reason'] = isset($_data['expense_reason'][$key]) ? [$_data['expense_reason'][$key]] : [];
                $res['income_reason'] = isset($_data['income_reason'][$key]) ? [$_data['income_reason'][$key]] : [];
                $res['creator'] = is_array($_data['creator']) ? (isset($_data['creator'][$key]) ? $_data['creator'][$key] : '') : $_data['creator'];
                $res['plan_id'] = isset($_data['plan_id'][$key]) ? $_data['plan_id'][$key] : '';
                $res['attachment_id'] = isset($_data['attachment_id'][$key]) ? $_data['attachment_id'][$key] : '';
                $res['record_time'] = isset($_data['record_time'][$key]) ? $_data['record_time'][$key] : '';
                $res['record_desc'] = isset($_data['record_desc'][$key]) ? $_data['record_desc'][$key]:'';
                //获取方案信息
                $plan = app($this->incomeExpensePlanRepository)->getDetail($res['plan_id']);
                if(isset($plan) && !$plan['all_user']){
                    //获取用户信息
                    $userInfo = app($this->userRepository)->getUserAllData($res['creator'])->toArray();
                    if($userInfo){
                        $own = [
                            'user_id' => $res['creator'],
                            'dept_id' => strval($userInfo['user_has_one_system_info']['dept_id']) ?? ''
                        ];
                        if(($plan['creator'] != $own['user_id']) && (empty($plan['user_id']) || strpos($plan['user_id'], $own['user_id']) === false) && (empty($plan['dept_id']) || strpos($plan['dept_id'], $own['dept_id']) === false)){
                            $i = 0;
                            foreach ($userInfo['user_has_many_role'] as $k => $vo) {
                                if(!empty($plan['role_id']) && strpos($plan['role_id'], strval($vo['role_id'])) !== false){
                                    $i = 1;
                                }
                            }
                            if(!$i){
                                return ['code' => ['0x018028', 'incomeexpense']];
                            }
                        }
                    }
                }
                $over = $this->isOverBudget($res['plan_id'], $res['expense'][0]);
                if(isset($over['code'])){
                    return $over;
                }
                if (!$over) {
                    return ['code' => ['0x018015', 'incomeexpense']];
                }
                $result = $this->insertRecord($res);
                if (isset($result['code'])) {
                    return $result;
                }
            }
        } else {
            if (is_array($_data['plan_id'])) {
                $_data['plan_id'] = $_data['plan_id'][0];
            }
            $_data['attachment_id'] = isset($_data['attachment_id'][0]) ? $_data['attachment_id'][0] : '';
            $_data['record_desc'] = isset($_data['record_desc'][0]) ? $_data['record_desc'][0] : '';
            $_data['record_time'] = isset($_data['record_time'][0]) ? $_data['record_time'][0] : '';
            $_data['creator'] = is_array($_data['creator']) ? (isset($_data['creator'][0]) ? $_data['creator'][0] : '') : $_data['creator'];
            if(empty($_data['creator'])){
                return ['code' => ['0x018019', 'incomeexpense']];
            }
            //获取方案信息
            $plan = app($this->incomeExpensePlanRepository)->getDetail($_data['plan_id']);
            if(isset($plan) && !$plan['all_user']){
                //获取用户信息
                $userInfo = app($this->userRepository)->getUserAllData($_data['creator'])->toArray();
                if($userInfo){
                    $own = [
                        'user_id' => $_data['creator'],
                        'dept_id' => strval($userInfo['user_has_one_system_info']['dept_id']) ?? ''
                    ];
                    if(($plan['creator'] != $own['user_id']) && (empty($plan['user_id']) || strpos($plan['user_id'], $own['user_id']) === false) && (empty($plan['dept_id']) || strpos($plan['dept_id'], $own['dept_id']) === false)){
                        $i = 0;
                        foreach ($userInfo['user_has_many_role'] as $k => $vo) {
                            if(!empty($plan['role_id']) && strpos($plan['role_id'], strval($vo['role_id'])) !== false){
                                $i = 1;
                            }
                        }
                        if(!$i){
                            return ['code' => ['0x018028', 'incomeexpense']];
                        }
                    }
                }
            }
            $over = $this->isOverBudget($_data['plan_id'], $_data['expense'][0]);
            if(isset($over['code'])){
                return $over;
            }
            if (!$over) {
                return ['code' => ['0x018015', 'incomeexpense']];
            }
            $result = $this->insertRecord($_data);
            if (isset($result['code'])) {
                return $result;
            }
        }
    }
    public function insertRecord($_data)
    {
        $data = [
            'plan_id' => $_data['plan_id'],
            'is_flow_record' => 1,
            'creator' => $_data['creator'],
            'record_time' => $_data['record_time'],
        ];

        $extra = [
            'run_id' => $_data['run_id'],
            'flow_id' => $_data['flow_id'],
            'run_name' => $_data['run_name'],
        ];

        $subData = [
            'record_desc' => isset($_data['record_desc']) ? $_data['record_desc'] : '',
            'record_extra' => json_encode($extra),
        ];
        foreach (['income', 'expense'] as $field) {
            $money = $_data[$field];
            if (!empty($money)) {
                if (is_array($money)) {
                    foreach ($money as $in) {
                        if (!is_numeric($in) && $in !== '') {
                            return ['code' => ['0x018027', 'incomeexpense']];
                        }
                        // if (floatval($in) < 0) {
                        //     return ['code' => ['0x018026', 'incomeexpense']];
                        // }
                    }
                } else {
                    if (!is_numeric($money) && $in !== '') {
                        return ['code' => ['0x018027', 'incomeexpense']];
                    }
                    // if (floatval($money) < 0) {
                    //     return ['code' => ['0x018026', 'incomeexpense']];
                    // }

                }
            }
            $fieldData = $this->dealDetailFieldData($_data, $field);
            $data[$field] = $fieldData[0];

            $subData[$field . '_detail'] = $fieldData[1];
        }
        $timestamp = strtotime($data['record_time']);
        $data['year'] = intval(date('Y', $timestamp));
        $data['month'] = intval(date('m', $timestamp));
        $data['day'] = intval(date('d', $timestamp));
        $data['quarter'] = $this->getQuearter($data['month']);
        $data['sub_data'] = $subData;
        $data['income'] = empty($data['income']) ? 0 : $data['income'];
        $data['expense'] = empty($data['expense']) ? 0 : $data['expense'];
        if ($data['income'] == 0 && $data['expense'] == 0) {
            return ['code' => ['0x018006', 'incomeexpense']];
        }
        if (!$recordId = app($this->incomeExpenseRecordsRepository)->addRecord($data)) {
            return ['code' => ['0x000003', 'common']];
        }

        if (isset($_data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("incomeexpense_record", $recordId, $_data['attachment_id']);
        }

        $statData = [
            'year' => $data['year'],
            'month' => $data['month'],
            'quarter' => $data['day'],
            'expense' => empty($data['expense']) ? 0 : $data['expense'],
            'income' => empty($data['income']) ? 0 : $data['income'],
            'times' => 1,
        ];
        app($this->incomeExpenseStatRepository)->addStat($statData);

        app($this->incomeExpensePlanRepository)->editPlan($this->planData($data['plan_id']), $data['plan_id']);

        return ['record_id' => $recordId];
    }
    private function dealDetailFieldData($data, $type = 'income')
    {
        $money = $data[$type];

        if (is_array($money) && !empty($money)) {
            $moneyReason = $data[$type . '_reason'];

            $moneyDetail = [];

            $total = 0;
            foreach ($money as $key => $val) {
                if (!$val) {
                    $val = 0;
                }
                $moneyDetail[] = [$moneyReason[$key], $val];

                $total += floatval($val);
            }

            return [$total, json_encode($moneyDetail)];
        }

        return [$money, ''];
    }
    /**
     * 新建收支记录
     *
     * @param array $data 记录数据
     * @param source $uploadFile 附件
     *
     * @return array 记录id
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function addRecord($_data, $currentUserId)
    {
        $expense = $this->defaultValue('expense', $_data, 0);
        $expense = empty($expense) ? 0 : $expense;
        $income = $this->defaultValue('income', $_data, 0);
        $income = empty($income) ? 0 : $income;
        $planId = $_data['plan_id'];
        if ($expense == 0 && $income == 0) {
            return ['code' => ['0x018006', 'incomeexpense']];
        }
        // if ($expense < 0 || $income < 0) {
        //     return ['code' => ['0x018008', 'incomeexpense']];
        // }

        if (!$this->isOverBudget($planId, $expense)) {
            return ['code' => ['0x018015', 'incomeexpense']];
        }

        $recordTime = $this->defaultValue('record_time', $_data, date('Y-m-d H:i:s'));

        $timestamp = strtotime($recordTime);
        $year = intval(date('Y', $timestamp));
        $month = intval(date('m', $timestamp));
        $day = intval(date('d', $timestamp));
        $quarter = $this->getQuearter($month);

        $data = [
            'plan_id' => $planId,
            'is_flow_record' => 0,
            'expense' => $expense,
            'income' => $income,
            'creator' => $currentUserId,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'day' => $day,
            'record_time' => $recordTime,
            'sub_data' => [
                'record_desc' => $this->defaultValue('record_desc', $_data, ''),
            ],
        ];
        if (!$recordId = app($this->incomeExpenseRecordsRepository)->addRecord($data)) {
            return ['code' => ['0x000003', 'common']];
        }

        if (isset($_data['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("incomeexpense_record", $recordId, $_data['attachment_id']);
        }

        $statData = [
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'expense' => $expense,
            'income' => $income,
            'times' => 1,
        ];

        app($this->incomeExpenseStatRepository)->addStat($statData);

        app($this->incomeExpensePlanRepository)->editPlan($this->planData($planId), $planId);

        return ['record_id' => $recordId];
    }
    /**
     * 编辑收支记录
     *
     * @param array $data 收支记录数据
     * @param source $uploadFile 附件
     * @param int $recordId 记录id
     *
     * @return int | array 编辑结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function editRecord($_data, $uploadFile, $recordId, $currentUserId)
    {
        if ($recordId == 0) {
            return ['code' => ['0x018007', 'incomeexpense']];
        }

        $expense = $this->defaultValue('expense', $_data, 0);

        $income = $this->defaultValue('income', $_data, 0);

        if ($expense == 0 && $income == 0) {
            return ['code' => ['0x018006', 'incomeexpense']];
        }

        // if ($expense < 0 || $income < 0) {
        //     return ['code' => ['0x018008', 'incomeexpense']];
        // }

        if (!$this->isOverBudget($_data['plan_id'], $expense, $recordId)) {
            return ['code' => ['0x018015', 'incomeexpense']];
        }

        $record = app($this->incomeExpenseRecordsRepository)->getSimpleRecordInfo($recordId);
        if ($record->creator != $currentUserId) {
            return ['code' => ['0x018012', 'incomeexpense']];
        }

        $recordTime = $this->defaultValue('record_time', $_data, date('Y-m-d H:i:s'));
        $timestamp = strtotime($recordTime);
        $year = intval(date('Y', $timestamp));
        $month = intval(date('m', $timestamp));
        $day = intval(date('d', $timestamp));
        $quarter = $this->getQuearter($month);

        //编辑收支记录
        $data = [
            'expense' => $expense,
            'income' => $income,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'day' => $day,
            'record_time' => $recordTime,
            'sub_data' => [
                'record_desc' => $this->defaultValue('record_desc', $_data, ''),
            ],
        ];
        if (!app($this->incomeExpenseRecordsRepository)->editRecord($data, $recordId)) {
            return ['code' => ['0x000003', 'common']];
        }

        //编辑收支记录附件

        $attachmentId = $this->defaultValue('attachment_id', $_data, []);

        app($this->attachmentService)->attachmentRelation("incomeexpense_record", $recordId, $attachmentId);

        //编辑收支方案
        $plan = app($this->incomeExpensePlanRepository)->showPlan($record->plan_id);
        $planData = [
            'real_expense' => $plan->real_expense + $expense - $record->expense,
            'real_income' => $plan->real_income + $income - $record->income,
        ];
        $planData['real_income_expense'] = $planData['real_income'] - $planData['real_expense'];
        app($this->incomeExpensePlanRepository)->editPlan($planData, $record->plan_id);
        //编辑收支统计
        $this->editStat('edit', $expense, $income, $year, $month, $quarter, $record->year, $record->month, $record->expense, $record->income);
        //获取返回的记录信息
        return $this->showRecord($recordId);
    }
    /**
     * 获取记录详情
     *
     * @param int $recordId 记录id
     *
     * @return object 记录详情
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function showRecord($recordId)
    {
        if ($recordId == 0) {
            return ['code' => ['0x018007', 'incomeexpense']];
        }

        $record = app($this->incomeExpenseRecordsRepository)->showRecord($recordId);

        $moneyMembers = ['expense', 'income', 'expense_budget', 'income_budget', 'real_expense', 'real_income'];

        foreach ($moneyMembers as $money) {
            $record->{$money . '2'} = $record->{$money} ? $this->money2($record->{$money}) : '';
            $record->{$money . '3'} = $record->{$money};
        }

        return $record;
    }
    /**
     * 删除收支记录
     *
     * @param int $recordId 记录id
     *
     * @return int | array 删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function deleteRecord($recordId, $currentUserId)
    {
        if ($recordId == 0) {
            return ['code' => ['0x018007', 'incomeexpense']];
        }
        //获取记录信息、判断是否有删除权限
        $record = app($this->incomeExpenseRecordsRepository)->getSimpleRecordInfo($recordId);
        $recordDetail = app($this->incomeExpenseRecordsRepository)->showRecord($recordId);
        // if($record->creator != $currentUserId) {
        //     return ['code' => ['0x018011', 'incomeexpense']];
        // }

//        $logData = [
//            'log_content' => "<strong>" . trans('incomeexpense.operator') . ": </strong>" . own('user_name') . "， " .
//            "<strong>" . trans('incomeexpense.Package_name') . "：</strong>" . $recordDetail->plan_name . "， " .
//            "<strong>" . trans('incomeexpense.Spending_amount') . "：</strong>" . $recordDetail->expense . "， " .
//            "<strong>" . trans('incomeexpense.Income_amount') . "：</strong>" . $recordDetail->income . "， " .
//            "<strong>" . trans('incomeexpense.Break_even_time') . "：</strong>" . $recordDetail->record_time,
//            'log_type' => 'incomeexpense',
//            'log_creator' => $currentUserId,
//            'log_time' => date('Y-m-d H:i:s'),
//            'log_ip' => getClientIp(),
//            'log_relation_table' => 'income_expense_records',
//            'log_relation_id' => $recordId,
//        ];
//        add_system_log($logData);

        $logData = [
            'log_content' =>  trans('incomeexpense.operator') . ":" . own('user_name') . "， " .
                  trans('incomeexpense.Package_name') . "：" . $recordDetail->plan_name . "， " .
                trans('incomeexpense.Spending_amount') . "：" . $recordDetail->expense . "， " .
                trans('incomeexpense.Income_amount') . "：" . $recordDetail->income . "， " .
                trans('incomeexpense.Break_even_time') . "：" . $recordDetail->record_time,
            'log_type' => 'incomeexpense',
            'log_creator' => $currentUserId,
            'log_time' => date('Y-m-d H:i:s'),
            'log_ip' => getClientIp(),
            'log_relation_table' => 'income_expense_records',
            'log_relation_id' => $recordId,
        ];
        $identifier  = "incomeexpense.incomeexpense.delete";
        $logParams = $this->handleLogParams($currentUserId, $logData['log_content'], $recordId, 'income_expense_records', $recordDetail->plan_name);
        logCenter::info($identifier , $logParams);
        //删除收支记录
        if (!app($this->incomeExpenseRecordsRepository)->deleteRecord($recordId)) {
            return ['code' => ['0x000003', 'common']];
        }
        //编辑方案表
        $plan = app($this->incomeExpensePlanRepository)->showPlan($record->plan_id);

        $planData = [
            'real_expense' => $plan->real_expense - $record->expense,
            'real_income' => $plan->real_income - $record->income,
        ];
        $planData['real_income_expense'] = $planData['real_income'] - $planData['real_expense'];

        app($this->incomeExpensePlanRepository)->editPlan($planData, $record->plan_id);
        //编辑统计表、删除对应的统计信息
        $this->editStat('delete', $record->year, $record->month, $record->expense, $record->income);

        return true;
    }
    /**
     * 收支统计
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function planStat($param, $userId)
    {
        $type = isset($param['type']) ? $param['type'] : 'all_month';

        $param['year'] = isset($param['year']) ? $param['year'] : date('Y');
        switch ($type) {
            case 'all_quarter':
                return array_merge($this->allQuarterStat($param, $userId));
            case 'all_month':
                return array_merge($this->allMonthStat($param, $userId));
            case "one_month":
                if (!isset($param['month'])) {
                    return ['code' => ['0x018009', 'incomeexpense']];
                }

                return array_merge($this->oneMonthStat($param, $userId));
            case "one_day":
                if (!isset($param['month'])) {
                    return ['code' => ['0x018009', 'incomeexpense']];
                }

                if (!isset($param['day'])) {
                    return ['code' => ['0x018010', 'incomeexpense']];
                }

                return array_merge($this->oneDayStat($param, $userId));
        }
    }
    /**
     * 按收支方案分别统计
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    public function planDiffStat($param, $userId)
    {
        if (!isset($param['plan_id']) || $param['plan_id'] == '') {
            return ['code' => ['0x018005', 'incomeexpense']];
        }

        $type = isset($param['type']) ? $param['type'] : 'all_month';

        $param['year'] = isset($param['year']) ? $param['year'] : date('Y');
        switch ($type) {
            case 'all_quarter':
                $res = $this->allDiffQuarterStat($param, $userId);
                break;
            case 'all_month':
                $res = $this->allDiffMonthStat($param, $userId);
                break;
            case "one_month":
                if (!isset($param['month'])) {
                    return ['code' => ['0x018009', 'incomeexpense']];
                }
                $res = $this->oneDiffMonthStat($param, $userId);
                break;
        }
        $result = [];
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $result[$key] = array_merge($value);
            }
        }
        return $result;
    }
    /**
     * 启动方案
     *
     * @param type $planId
     *
     * @return 启动结果
     *
     * @author 李志军
     *
     * @since 2015-10-16
     */
    public function beginPlan($planId)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }

        $data = [
            'plan_status' => 1,
            'is_start' => 1,
            'begin_time' => date('Y-m-d H:i:s'),
            'end_time' => '',
        ];
        foreach (explode(',', $planId) as $v) {
            if (!app($this->incomeExpensePlanRepository)->editPlan($data, $v)) {
                return ['code' => ['0x000003', 'common']];
            }
        }

        return true;
    }
    /**
     * 结束方案
     *
     * @param type $planId
     *
     * @return 结束结果
     *
     * @author 李志军
     *
     * @since 2015-10-16
     */
    public function endPlan($planId)
    {
        if ($planId == 0) {
            return ['code' => ['0x018005', 'incomeexpense']];
        }

        $data = [
            'plan_status' => 2,
            'end_time' => date('Y-m-d H:i:s'),
        ];
        $plan = app($this->incomeExpensePlanRepository)->getDetail($planId);
        $data['begin_time'] = $plan->begin_time;
        $data['is_start'] = 0;
        foreach (explode(',', $planId) as $v) {
            if (!app($this->incomeExpensePlanRepository)->editPlan($data, $v)) {
                return ['code' => ['0x000003', 'common']];
            }
        }

        return true;
    }
    /**
     * 导出统计数据到excel
     *
     * @param type $param
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function exportPlanStat($param)
    {
        $type = isset($param['type']) ? $param['type'] : 'all_month';
        $year = isset($param['year']) ? $param['year'] : date('Y');
        $userId = $param['user_id'];
        switch ($type) {
            case 'all_quarter':
                $data = $this->getExportData('all_quarter_data_' . $userId, 'quarter');
                $header = ['quarter' => ['data' => trans('incomeexpense.quarter'), 'style' => ['width' => '10']]];
                $title = isset($param['plan_id'])
                ? $param['plan_id'] . $year . trans('incomeexpense.Annual_quarterly_income_expenditure_statistics')
                : $year . trans('incomeexpense.Annual_quarterly_income_expenditure_statistics');
                break;
            case 'all_month':
                $data = $this->getExportData('all_month_data_' . $userId, 'month');
                $header = ['month' => ['data' => trans('incomeexpense.month'), 'style' => ['width' => '10']]];
                $title = isset($param['plan_id'])
                ? $param['plan_id'] . $year . trans('incomeexpense.Annual_income_expenditure_statistics')
                : $year . trans('incomeexpense.Annual_income_expenditure_statistics');
                break;
            case "one_month":
                if (!isset($param['month'])) {
                    return ['code' => ['0x018009', 'incomeexpense']];
                }
                $data = $this->getExportData('one_month_data_' . $userId, 'days');
                $header = ['days' => ['data' => trans('incomeexpense.day'), 'style' => ['width' => '10']]];
                $title = isset($param['plan_id'])
                ? $param['plan_id'] . $year . trans('incomeexpense.year') . $param['month'] . trans('incomeexpense.Monthly_income_expenditure_statistics')
                : $year . trans('incomeexpense.year') . $param['month'] . trans('incomeexpense.Monthly_income_expenditure_statistics');
                break;
            case "one_day":
                if (!isset($param['month'])) {
                    return ['code' => ['0x018009', 'incomeexpense']];
                }

                if (!isset($param['day'])) {
                    return ['code' => ['0x018010', 'incomeexpense']];
                }
                $data = $this->getExportData('one_day_stat_data_' . $userId, 'plan_name');
                $header = ['plan_name' => ['data' => trans('incomeexpense.Package_name'), 'style' => ['width' => '25']]];
                $title = $year . trans('incomeexpense.year') . $param['month'] . trans('incomeexpense.month') . $param['day'] . trans('incomeexpense.Balance_payments_statistics');
                break;
        }
        $header['expense'] = ['data' => trans('incomeexpense.Spending_amount'), 'style' => ['width' => '15']];
        $header['income'] = ['data' => trans('incomeexpense.Income_amount'), 'style' => ['width' => '15']];
        $header['income_expense'] = ['data' => trans('incomeexpense.Balance_payments'), 'style' => ['width' => '15']];
        $header['times'] = ['data' => trans('incomeexpense.Revenue_expenditure_records'), 'style' => ['width' => '10']];

        return ['header' => $header, 'data' => $data, 'title' => $title];
    }
    public function exportPlanDiffStat($param)
    {
        $type = isset($param['type']) ? $param['type'] : 'all_month';

        $userId = $param['user_id'];

        switch ($type) {
            case 'all_quarter':
                $header = ['quarter' => ['data' => trans('incomeexpense.quarter'), 'style' => ['width' => '15']]];
                $data = $this->getDiffExportData('all_diff_quarter_data_' . $userId, 'quarter', $header);
                break;
            case 'all_month':
                $header = ['month' => ['data' => trans('incomeexpense.month'), 'style' => ['width' => '15']]];
                $data = $this->getDiffExportData('all_diff_month_data_' . $userId, 'month', $header);
                break;
            case "one_month":
                if (!isset($param['month'])) {
                    return ['code' => ['0x018009', 'incomeexpense']];
                }
                $header = ['days' => ['data' => trans('incomeexpense.day'), 'style' => ['width' => '15']]];
                $data = $this->getDiffExportData('one_diff_month_data_' . $userId, 'days', $header);
                break;
        }

        return $data;
    }
    private function getDiffExportData($cacheKey, $name, $header)
    {
        $header['expense'] = ['data' => trans('incomeexpense.Spending_amount'), 'style' => ['width' => '15']];
        $header['income'] = ['data' => trans('incomeexpense.Income_amount'), 'style' => ['width' => '15']];
        $header['income_expense'] = ['data' => trans('incomeexpense.Balance_payments'), 'style' => ['width' => '15']];
        $header['times'] = ['data' => trans('incomeexpense.Revenue_expenditure_records'), 'style' => ['width' => '15']];

        $sheets = [];
        $datas = Cache::get($cacheKey);

        if ($datas && sizeof($datas) > 0) {
            foreach ($datas as $key => $data) {
                $planInfo = app($this->incomeExpensePlanRepository)->showPlan($key);

                $sheet = [
                    'sheetName' => $planInfo->plan_name,
                    'header' => $header,
                    'data' => $name == 'plan_name'
                    ? $this->getPlanStat($data, $name)
                    : $this->getDateStat($data, $name),
                ];

                $sheets[] = $sheet;
            }
        }

        return $sheets;
    }

    private function getExportData($cacheKey, $name)
    {
        $data = Cache::get($cacheKey);

        return $name == 'plan_name' ? $this->getPlanStat($data, $name) : $this->getDateStat($data, $name);
    }
    private function getDateStat($data, $name)
    {
        if (!$data && empty($data)) {
            return [];
        }

        $max = sizeof($data);

        $result = [];

        foreach ($data as $k => $v) {
            if ($k == $max) {
                $temp[$name] = trans('incomeexpense.count');
                $temp['expense'] = $v['total_expense'];
                $temp['income'] = $v['total_income'];
                $temp['income_expense'] = $v['total_income_expense'];
                $temp['times'] = $v['total_times'];
            } else {
                $temp[$name] = $k;
                $temp['expense'] = $v['expense'];
                $temp['income'] = $v['income'];
                $temp['income_expense'] = $v['income_expense'];
                $temp['times'] = $v['times'];
            }

            $result[] = $temp;
        }
        return $result;
    }

    private function getPlanStat($data, $name)
    {
        if (!$data && empty($data)) {
            return [];
        }

        $max = sizeof($data) - 1;

        $result = [];

        foreach ($data as $k => $v) {
            if ($k == $max) {
                $temp[$name] = trans('incomeexpense.count');
                $temp['expense'] = $v['expense_total'];
                $temp['income'] = $v['income_total'];
                $temp['income_expense'] = $v['income_expense_total'];
                $temp['times'] = $v['times_total'];
            } else {
                $temp[$name] = $v['plan_name'];
                $temp['expense'] = $v['new_expense'];
                $temp['income'] = $v['new_income'];
                $temp['income_expense'] = $v['income_expense'];
                $temp['times'] = $v['times'];
            }

            $result[] = $temp;
        }

        return $result;
    }
    /**
     * 按所有季度统计
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function allQuarterStat($param, $userId)
    {
        $stat = (isset($param['plan_id']) && $param['plan_id'] != '')
        ? app($this->incomeExpenseRecordsRepository)->allQuarterStat(explode(',', $param['plan_id']), $param['year'])
        : app($this->incomeExpenseStatRepository)->allQuarterStat($param['year']);

        $result = $this->handleStat($stat, 5, 'quarter');

        Cache::forever('all_quarter_data_' . $userId, $result);

        return $result;
    }
    /**
     * 按所有月份统计
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function allMonthStat($param, $userId)
    {
        $stat = (isset($param['plan_id']) && $param['plan_id'] != '')
        ? app($this->incomeExpenseRecordsRepository)->allMonthStat(explode(',', $param['plan_id']), $param['year'])
        : app($this->incomeExpenseStatRepository)->allMonthStat($param['year']);

        $result = $this->handleStat($stat, 13, 'month');

        Cache::forever('all_month_data_' . $userId, $result);

        return $result;
    }
    /**
     * 按一个月份所有天数统计
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function oneMonthStat($param, $userId)
    {
        $planId = (isset($param['plan_id']) && $param['plan_id'] != '') ? explode(',', $param['plan_id']) : '';

        $stat = app($this->incomeExpenseRecordsRepository)->oneMonthStat($planId, $param['year'], $param['month']);

        $days = cal_days_in_month(CAL_GREGORIAN, $param['month'], $param['year']);

        $data = $this->handleStat($stat, $days + 1, 'day');

        Cache::forever('one_month_data_' . $userId, $data);

        return $data;
    }
    /**
     * 统计一天的收支数据
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function oneDayStat($param, $userId)
    {
        $stat = app($this->incomeExpenseRecordsRepository)->oneDayStat($param['year'], $param['month'], $param['day']);

        $result = [];

        $expenseTotal = $incomeTotal = $inOutTotal = $timesTotal = 0;

        foreach ($stat as $v) {
            $expenseTotal += $v->expense;
            $incomeTotal += $v->income;
            $timesTotal += $v->times;
            $inOutTotal += $v->income - $v->expense;
            $v['new_expense'] = $this->money2($v->expense);
            $v['new_income'] = $this->money2($v->income);
            $v['income_expense'] = $this->money2($v->income - $v->expense);
            $result[] = $v;
        }

        $result[] = [
            'expense_total' => $this->money2($expenseTotal),
            'income_total' => $this->money2($incomeTotal),
            'income_expense_total' => $this->money2($inOutTotal),
            'times_total' => $timesTotal,
        ];

        Cache::forever('one_day_stat_data_' . $userId, $result);

        return $result;
    }
    /**
     * 按收支方案分别统计所有季度收支数据
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function allDiffQuarterStat($param, $userId)
    {
        $stats = [];

        foreach (explode(',', $param['plan_id']) as $planId) {
            $stat = app($this->incomeExpenseRecordsRepository)->statQuarterByPlanId($param['year'], $planId);

            $stats[$planId] = $this->handleStat($stat, 5, 'quarter');
        }

        Cache::forever('all_diff_quarter_data_' . $userId, $stats);

        return $stats;
    }
    /**
     * 按收支方案分别统计所有月份收支数据
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function allDiffMonthStat($param, $userId)
    {
        $stats = [];

        foreach (explode(',', $param['plan_id']) as $planId) {
            $stat = app($this->incomeExpenseRecordsRepository)->statMonthByPlanId($param['year'], $planId);

            $stats[$planId] = $this->handleStat($stat, 13, 'month');
        }

        Cache::forever('all_diff_month_data_' . $userId, $stats);

        return $stats;
    }
    /**
     * 按收支方案分别统计所有一个月所有天数收支数据
     *
     * @param array $param 统计参数
     *
     * @return array 统计结果
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function oneDiffMonthStat($param, $userId)
    {
        $stats = [];

        $days = cal_days_in_month(CAL_GREGORIAN, $param['month'], $param['year']);

        foreach (explode(',', $param['plan_id']) as $planId) {
            $stat = app($this->incomeExpenseRecordsRepository)->statOneMonthByPlanId($param['year'], $param['month'], $planId);

            $stats[$planId] = $this->handleStat($stat, $days + 1, 'day');
        }

        Cache::forever('one_diff_month_data_' . $userId, $stats);

        return $stats;
    }
    /**
     * 处理统计结果数据
     *
     * @param array $stat 统计结果数据
     * @param int $number 数据最大键值
     * @param sting $type 收支统计类型
     *
     * @return array 统计结果数据
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function handleStat($stat, $number = 5, $type = 'quarter')
    {
        $result = $_stat = [];

        if ($stat) {
            foreach ($stat as $v) {
                $_stat[$v->$type]['old_income'] = $v->income;
                $_stat[$v->$type]['old_expense'] = $v->expense;
                $_stat[$v->$type]['old_income_expense'] = $v->income - $v->expense;
                $_stat[$v->$type]['income'] = $this->money2($v->income);
                $_stat[$v->$type]['expense'] = $this->money2($v->expense);
                $_stat[$v->$type]['times'] = $v->times;
                $_stat[$v->$type]['income_expense'] = $this->money2($v->income - $v->expense);
            }
        }

        $inTotal = $outTotal = $inOutTotal = $totalRecords = 0;

        for ($i = 1; $i <= $number - 1; $i++) {
            if (isset($_stat[$i])) {
                $result[$i] = $_stat[$i];
                $inTotal += $_stat[$i]['old_income'];
                $outTotal += $_stat[$i]['old_expense'];
                $inOutTotal += $_stat[$i]['old_income_expense'];
                $totalRecords += $_stat[$i]['times'];
            } else {
                $result[$i]['income'] = '0.00';
                $result[$i]['expense'] = '0.00';
                $result[$i]['income_expense'] = '0.00';
                $result[$i]['old_income'] = 0;
                $result[$i]['old_expense'] = 0;
                $result[$i]['old_income_expense'] = 0;
                $result[$i]['times'] = 0;
            }
        }

        $result[$number]['total_income'] = $this->money2($inTotal);
        $result[$number]['total_expense'] = $this->money2($outTotal);
        $result[$number]['total_income_expense'] = $this->money2($inOutTotal);
        $result[$number]['total_times'] = $totalRecords;

        return $result;
    }
    /**
     * 为参数赋予默认值
     *
     * @param type $key 键值
     * @param array $data 原来的数据
     * @param type $default 默认值
     *
     * @return type 处理后的值
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    /**
     * 获取当前季度
     *
     * @return int 季度
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function getQuearter($month)
    {
        return ceil($month / 3);
    }
    /**
     * 将数值转为千位分隔符类型数值
     *
     * @param float $money
     *
     * @return string 转换后的数值
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function money2($money)
    {
        if ($money == 0) {
            return '0.00';
        }
        //保留两位小数
        $money =  number_format($money, 2, '.', '');
        
        $money = preg_replace('/(?<=[0-9])(?=(?:[0-9]{3})+(?![0-9]))/', ',', $money);

        if (strpos($money, '.') === false) {
            return $money . '.00';
        }
       
        return $money;
    }
    /**
     * 获取收支方案更新数组
     *
     * @param int $planId
     *
     * @return array  收支方案更新数组
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function planData($planId)
    {
        $data = [
            'real_expense' => 0,
            'real_income' => 0,
            'real_income_expense' => 0,
        ];

        if ($records = app($this->incomeExpenseRecordsRepository)->getRecordsByPlanId($planId)) {
            foreach ($records as $value) {
                $data['real_expense'] += $value->expense;
                $data['real_income'] += $value->income;
            }
        }
        $data['real_income_expense'] = $data['real_income'] - $data['real_expense'];
        return $data;
    }
    /**
     * 处理收支记录字段
     *
     * @param array $fields 收支记录字段
     *
     * @return array 收支记录字段
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function handleRecordFields($fields)
    {
        $_fields = [];

        foreach ($fields as $value) {
            if ($value == 'plan_id' || $value == 'plan_name') {
                $_fields[] = 'income_expense_plan.' . $value;
            } else if ($value == 'record_desc') {
                $_fields[] = 'income_expense_records_sub.record_desc';
            } else if ($value == 'creator') {
                $_fields[] = 'user.user_name as creator';
            } else {
                $_fields[] = 'income_expense_records.' . $value;
            }
        }

        return $_fields;
    }
    /**
     * 判断支持是否超预算
     *
     * @param int $planId
     * @param float $expense
     * @param int $recordId
     *
     * @return boolean
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function isOverBudget($planId, $expense, $recordId = 0)
    {
        //前端输入非数值报错解决
        ini_set("error_reporting", "E_ALL & ~E_NOTICE");
        if ($expense == 0 || $expense == 0.00) {
            return true;
        }

        $plan = app($this->incomeExpensePlanRepository)->getDetail($planId);
        if($plan->is_start == 0){
            return ['code' => ['0x018020', 'incomeexpense']];
        }
        if ($plan->expense_budget_alert != 1) {
            return true;
        }
        if ($recordId != 0) {
            $record = app($this->incomeExpenseRecordsRepository)->getDetail($recordId);
            if (($plan->real_expense + $expense - $record->expense) > $plan->expense_budget) {
                return false;
            }
        } else {
            if (($plan->real_expense + $expense) > $plan->expense_budget) {
                return false;
            }
        }

        return true;
    }
    private function editStat($type, ...$data)
    {
        $keys = $type == 'edit'
        ? ['expense', 'income', 'year', 'month', 'quarter', 'old_year', 'old_month', 'old_expense', 'old_income']
        : ['old_year', 'old_month', 'old_expense', 'old_income'];

        $statData = [];

        foreach ($data as $key => $value) {
            $statData[$keys[$key]] = $value;
        }

        return app($this->incomeExpenseStatRepository)->editStat($statData, $type);
    }


    public function handleLogParams($user , $content , $relation_id = '' ,$relation_table = '', $relation_title='')
    {
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => $relation_table,
            'relation_id' => $relation_id,
            'relation_title' => $relation_title,
        ];
        return $data;
    }
    public function exportData($data)
    {
        $header = [
            'plan_name'   => ['data' => trans("incomeexpense.Package_name"), 'style' => ['width' => '20']] ,
            'user_name'  => ['data' => trans("incomeexpense.creator"), 'style' => ['width' => '15']],
            'expense_budget2'   => ['data' => trans("incomeexpense.Spending_on_budget"), 'style' => ['width' => '15']], //支出预算
            'expense2'   => ['data' => trans("incomeexpense.expense2"), 'style' => ['width' => '15']],
            'real_expense2'  => ['data' => trans("incomeexpense.Total_spending"), 'style' => ['width' => '15']],//总支出
            'income_budget2' => ['data' => trans("incomeexpense.Revenue_forecast"), 'style' => ['width' => '15']],//收入预估
            'income2'  => ['data' => trans("incomeexpense.income2"), 'style' => ['width' => '15']],
            'real_income2'=> ['data' => trans("incomeexpense.Total_revenue"), 'style' => ['width' => '15']],//总收入
            'record_time'     => ['data' => trans("incomeexpense.Break_even_time"), 'style' => ['width' => '20']],
            'record_desc'  => ['data' => trans("incomeexpense.desc"), 'style' => ['width' => '50']],
        ];
        $param  = [];
        if(isset($data['created_at'])){
            $param['search']['created_at'] = $data['created_at'];
        }
        if(isset($data['record_time'])){
            $param['search']['record_time'] = $data['record_time'];
        }
        if(isset($data['plan_id'])){
            $param['search']['plan_id'] = $data['plan_id'];
        }
        $list = $this->listRecord($param,$data['user_info']);
        $list = isset($list['list'])?$list['list']:[];
        $data = [];
        foreach ($list as $value) {
            $value = $value->toArray();
            $value['record_desc']            = strip_tags($value['record_desc']);
            $value['record_desc']            = str_replace('&nbsp;', ' ', $value['record_desc']);
            $data[] = Arr::dot($value);
        }
        return compact('header', 'data');

    }
}
