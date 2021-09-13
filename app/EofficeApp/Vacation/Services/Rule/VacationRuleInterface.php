<?php

namespace App\EofficeApp\Vacation\Services\Rule;

interface  VacationRuleInterface
{

    /**
     * 初始化一些参数
     * @param $vacation
     * @param $userId
     * @return mixed
     */
    public function init($vacation, $userIds, $profiles, $records);

    /**
     * 假期类型创建时的操作
     * @return mixed
     */
    public function createEvent();

    /**
     * 假期类别编辑时的操作
     * @return mixed
     */
    public function editEvent($newVacation);

    /**
     * 假期类别删除时的操作
     * @return mixed
     */
    public function delEvent();

    /**
     * 获取用户假期余额
     * @return mixed
     */
    public function getUserDays();

    /**
     * 增加假期余额
     * @return mixed
     */
    public function increaseDays($field, $days);

    /**
     * 减少假期余额
     * @return mixed
     */
    public function reduceDays($field, $days);

    /**
     * 删除历史假期余额
     * @return mixed
     */
    public function delHistoryDays();

    /**
     * 每天执行定时任务需要执行的操作
     * @return mixed
     */
    public function schedule();

    /**
     * 批量编辑假期余额
     * @param $data
     * @param $isHistory
     * @return mixed
     */
    public function multIncreaseDays($data, $isHistory);

    /**
     * 获取当前周期的外发天数（请假和加班）
     * @return mixed
     */
    public function getCurCycleOutSendDays();

    /**
     * 获取某个用户某个假期本周期开始时间和结束时间
     * @param $userId
     * @param $joinDate
     * @return mixed
     */
    public function getCycleDateRange($userId);

    /**
     * 获取某个日期之前将要过期的假期信息
     * @param $deadline
     * @return mixed
     * [
     *   ['user_id'=>'admin','date'=>'2020-12-31','vacation_name'=>'年假','days'=3],
     *   ...
     * ]
     */
    public function getBeforeDeadline($deadline, $notifyDay);
}