<?php
namespace App\EofficeApp\IncomeExpense\Permissions;
class IncomeExpensePermission
{
    public function __construct()
    {
        $this->incomeExpensePlanRepository = 'App\EofficeApp\IncomeExpense\Repositories\IncomeExpensePlanRepository';

        $this->incomeExpensePlanTypeRepository = 'App\EofficeApp\IncomeExpense\Repositories\IncomeExpensePlanTypeRepository';

        $this->incomeExpenseRecordsRepository = 'App\EofficeApp\IncomeExpense\Repositories\IncomeExpenseRecordsRepository';

    }
     /**
     * 验证新建收支记录权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function addRecord($own, $data, $urlData)
    {
        if(!isset($data['plan_id']) || empty($data['plan_id'])) {
            return ['code' => ['0x018002', 'incomeexpense']];
        }
        $planId = $data['plan_id'];
        $param['own'] = $own;
        $param['search']['plan_status'] = [1];
        $plans = app($this->incomeExpensePlanRepository)->listPlan($param);
        $id = [];
        foreach ($plans as $key => $value) {
            $id[] = $value->plan_id;
        }
        if(!in_array($planId,$id)){
            return ['code' => ['0x018021', 'incomeexpense']];
        }
        return true;
    }

    /**
     * 验证编辑收支记录权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editRecord($own, $data, $urlData)
    {
        $currentUserId = $own['user_id'];

        if(!isset($urlData['recordId']) || empty($urlData['recordId'])) {
            return ['code' => ['0x018007', 'incomeexpense']];
        }
       
        $recordId = $urlData['recordId'];

        $record = app($this->incomeExpenseRecordsRepository)->getSimpleRecordInfo($recordId);
        
        if ($record->creator == $currentUserId && $record->is_flow_record != 1) {
            return true;
        }
        return false;
    }
     /**
     * 验证删除收支记录权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteRecord($own, $data, $urlData)
    {
        $currentUserId = $own['user_id'];

        if(!isset($urlData['recordId']) || empty($urlData['recordId'])) {
            return ['code' => ['0x018007', 'incomeexpense']];
        }
       
        $recordId = $urlData['recordId'];

        $record = app($this->incomeExpenseRecordsRepository)->getSimpleRecordInfo($recordId);
        
        if(($record->is_flow_record != 1 && $currentUserId == $record->creator) || ($record->is_flow_record == 1 && $currentUserId == 'admin')){
            return true;
        }
        return false;
    }
     /**
     * 验证结束方案权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function endPlan($own, $data, $urlData)
    {
        //方案状态（0未启动，1进行中，2结束）
        if(!isset($urlData['planId']) || empty($urlData['planId'])) {
            return ['code' => ['0x018002', 'incomeexpense']];
        }
        $planId  = $urlData['planId'];
        $plan    = app($this->incomeExpensePlanRepository)->showPlan($planId, true);
        if($plan->plan_status == 0)
        {
            return ['code' => ['0x018020', 'incomeexpense']];
        }
        return true;
    }
}
