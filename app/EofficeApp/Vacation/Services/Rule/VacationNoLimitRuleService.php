<?php

namespace App\EofficeApp\Vacation\Services\Rule;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Vacation\Traits\VacationTrait;

class VacationNoLimitRuleService extends BaseService implements VacationRuleInterface
{


    use VacationTrait;

    private $vacation;
    private $userIds;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 初始化一些参数
     * @param $vacation
     * @param $userId
     * @return $this|mixed
     */
    public function init($vacation, $userIds, $profiles, $records)
    {
        $this->vacation = $vacation;
        $this->userIds = is_array($userIds) ? $userIds : [$userIds];
        return $this;
    }

    /**
     * 假期类型创建时的操作
     * @return mixed
     */
    public function createEvent()
    {
        //nothing to do
    }

    /**
     * 假期类别编辑时的操作
     * @return mixed
     */
    public function editEvent($oldVacation)
    {

    }

    /**
     * 假期类别删除时的操作
     * @return mixed
     */
    public function delEvent()
    {

    }

    /**
     * 获取用户假期总额
     * @return mixed
     */
    public function getUserDays($log = '')
    {
        $res = array();
        foreach ($this->userIds as $userId) {
            $res[$userId]['cur'] = 0;
            $res[$userId]['his'] = 0;
        }
        return $res;
    }

    /**
     * 增加假期余额
     * @return mixed
     */
    public function increaseDays($field, $days)
    {
        return true;
    }

    /**
     * 减少假期余额
     * @return mixed
     */
    public function reduceDays($field, $days)
    {
        return true;
    }

    /**
     * 删除历史假期余额
     * @return mixed
     */
    public function delHistoryDays()
    {
        //nothing to do
    }

    /**
     * 每天执行定时任务
     * @return mixed|void
     */
    public function schedule()
    {
        //nothing to do
    }

    public function multIncreaseDays($data, $isHistory = false)
    {
        return true;
    }

    public function getCurCycleOutSendDays()
    {
        return 0;
    }

    /**
     * 获取某个用户某个假期本周期开始时间和结束时间
     * @param $userId
     * @param $joinDate
     * @return mixed
     */
    public function getCycleDateRange($userId)
    {
        return '-';
    }

    public function getBeforeDeadline($deadline, $notifyDay)
    {
        return [];
    }
}