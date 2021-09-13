<?php

namespace App\EofficeApp\Vacation\Services\Rule;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Vacation\Traits\VacationTrait;
use Carbon\Carbon;

class VacationMonthRuleService extends BaseService implements VacationRuleInterface
{


    use VacationTrait;

    private $vacation;
    private $userIds;
    private $records;
    private $vacationRepository;

    public function __construct()
    {
        parent::__construct();
        $this->vacationMonthRepository = 'App\EofficeApp\Vacation\Repositories\VacationMonthRepository';
        $this->vacationRepository = 'App\EofficeApp\Vacation\Repositories\VacationRepository';
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
        $this->records = $records;
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
    public function editEvent($newVacation)
    {
        if ($this->isGreatChange($newVacation, $this->vacation)) {
            return app($this->vacationMonthRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
        }
        return true;
    }

    /**
     * 假期类别删除时的操作
     * @return mixed
     */
    public function delEvent()
    {
        return app($this->vacationMonthRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
    }

    /**
     * 获取用户假期总额
     * @return mixed
     */
    public function getUserDays($log = '')
    {
        $res = array();
        //当前月
        $currentDays = $this->getCurrentUserDays();
        //历史
        $historyDays = $this->getHistoryMonthDays();
//        $vacationSet = app($this->vacationRepository)->getVacationSet();
//        if($vacationSet['is_transform'] == 0){
//            foreach ($this->userIds as $userId) {
//                $currDays = $currentDays[$userId] ?? 0;
//                $hisDays = $historyDays[$userId] ?? 0;
//                $res[$userId] = [
//                    'cur' => $currDays,
//                    'his' => $hisDays
//                ];
//            }
//        }else{
//            foreach ($this->userIds as $userId) {
//                $currDays = $currentDays[$userId] ?? 0;
//                $hisDays = $historyDays[$userId] ?? 0;
//                if($log == 1){
//                    $res[$userId] = [
//                        'cur' => round($currDays * ($vacationSet['conversion_ratio']/60),4),
//                        'his' => round($hisDays * ($vacationSet['conversion_ratio']/60),4)
//                    ];
//                }else{
//                    $res[$userId] = [
//                        'cur' => round($currDays * ($vacationSet['conversion_ratio']/60),2),
//                        'his' => round($hisDays * ($vacationSet['conversion_ratio']/60),2)
//                    ];
//                }
//            }
//        }
        foreach ($this->userIds as $userId) {
            $currDays = $currentDays[$userId] ?? 0;
            $hisDays = $historyDays[$userId] ?? 0;
            $res[$userId] = [
                'cur' => $currDays,
                'his' => $hisDays
            ];
        }
        return $res;
    }

    /**
     * 增加假期余额
     * @return mixed
     */
    public function increaseDays($field, $days)
    {
        $month = date('Y-m');
        $wheres = array(
            'user_id' => [$this->userIds, 'in'],
            'vacation_id' => [$this->vacation->vacation_id],
            'month' => [$month]
        );
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 1){
            $days = round($days * 60,2);
        }
        $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
        $updateUserId = array_values(array_column($records, 'user_id'));
        $insertUserId = array_diff($this->userIds, $updateUserId);
        if ($updateUserId) {
            $wheres['user_id'][0] = $updateUserId;
            if (!app($this->vacationMonthRepository)->increaseDays($wheres, $field, $days)) {
                return false;
            }
        }
        if ($insertUserId) {
            $data = array();
            foreach ($insertUserId as $userId) {
                $data[] = [
                    'user_id' => $userId,
                    'vacation_id' => $this->vacation->vacation_id,
                    'month' => $month,
                    $field => $days,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            if (!app($this->vacationMonthRepository)->insertMultipleData($data)) {
                return false;
            }
        }
        return true;
    }

    public function increaseOutsendDays($field, $days)
    {
        $month = date('Y-m');
        $wheres = array(
            'user_id' => [$this->userIds, 'in'],
            'vacation_id' => [$this->vacation->vacation_id],
            'month' => [$month]
        );
//        $vacationSet = app($this->vacationRepository)->getVacationSet();
//        if($vacationSet['is_transform'] != 0){
//            $days = round($days * 60,2);
//        }
        $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
        $updateUserId = array_values(array_column($records, 'user_id'));
        $insertUserId = array_diff($this->userIds, $updateUserId);
        if ($updateUserId) {
            $wheres['user_id'][0] = $updateUserId;
            if (!app($this->vacationMonthRepository)->increaseDays($wheres, $field, $days)) {
                return false;
            }
        }
        if ($insertUserId) {
            $data = array();
            foreach ($insertUserId as $userId) {
                $data[] = [
                    'user_id' => $userId,
                    'vacation_id' => $this->vacation->vacation_id,
                    'month' => $month,
                    $field => $days,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            if (!app($this->vacationMonthRepository)->insertMultipleData($data)) {
                return false;
            }
        }
        return true;
    }

    public function multIncreaseDays($data, $isHistory = false)
    {
        $month = date('Y-m');
        if ($isHistory) {
            $month = $this->getLastMonth();
        }
        $userIds = array_keys($data);
        $wheres = [
            'vacation_id' => [$this->vacation->vacation_id],
            'user_id' => [$userIds, 'in'],
            'month' => [$month]
        ];
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            $num = 1;
        }else{
            $num = 60;
        }
        $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
        $existUserId = array_column($records, 'user_id');
        $insert = array();
        $update = array();
        foreach ($data as $userId => $days) {
            if (in_array($userId, $existUserId)) {
                $update[$userId] = round($days * $num,$this->precision);
            } else {
                $insert[] = [
                    'vacation_id' => $this->vacation->vacation_id,
                    'user_id' => $userId,
                    'hours' => round($days * 60,$this->precision),
                    'days' => $days,
                    'month' => $month,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        if ($insert) {
            app($this->vacationMonthRepository)->insertMultipleData($insert);
        }
        if ($update) {
            app($this->vacationMonthRepository)->multIncreaseDays($this->vacation->vacation_id, $update, ['month' => $month]);
        }
    }

    public function getBeforeDeadline($deadline, $notifyDay)
    {
        $data = array();
        $date = date('Y-m-d', strtotime(date('Y-m-1', strtotime('next month')) . '-1 day'));
        if ($this->vacation->is_delay) {
            $date = $this->addDuration($date, $this->vacation->delay_days, $this->vacation->delay_unit);
        }
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if ($date <= $deadline) {
            $userVacation = $this->getCurrentUserDays();
            foreach ($userVacation as $userId => $day) {
                if ($day > 0) {
//                    if($vacationSet['is_transform'] == 1){
//                        $day = round($day * ($vacationSet['conversion_ratio']/60),2);
//                    }
                    $data[] = [
                        'user_id' => $userId,
                        'date' => $date,
                        'vacation_id' => $this->vacation->vacation_id,
                        'vacation_name' => $this->vacation->vacation_name,
                        'day' => $day
                    ];
                }
            }
        }
        //延迟有效期还有历史假期过期信息
        if ($this->vacation->is_delay) {
            $wheres = array(
                'user_id' => is_array($this->userIds) ? [$this->userIds, 'in'] : [$this->userIds],
                'vacation_id' => [$this->vacation->vacation_id],
                'month' => [date('Y-m'), '<']
            );
            $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
            if ($records) {
                foreach ($records as $record) {
                    $userId = $record['user_id'];
                    $startDate = $record['month'] . '-01';
                    $endDate = date('Y-m-d', strtotime("$startDate +1 month -1 day"));
                    // 历史延长假期最后过期时间
                    $date = $this->addDuration($endDate, $this->vacation->delay_days, $this->vacation->delay_unit);
                    $deadline = date('Y-m-d', strtotime("$date -$notifyDay day"));
                    if (date('Y-m-d') >= $deadline) {
                        if($vacationSet['is_transform'] == 0){
                            $days = $record['days'];
                        }else{
                            $days = round($record['hours']/60,2);
                        }
                        $data[] = [
                            'user_id' => $userId,
                            'date' => $date,
                            'vacation_id' => $this->vacation->vacation_id,
                            'vacation_name' => $this->vacation->vacation_name,
                            'day' => $days
                        ];
                    }
                }
            }
        }
        return $data;
    }

    private function getLastMonth()
    {
        return date('Y-m', strtotime('last month'));
    }

    /**
     * 减少假期余额
     * @return mixed
     */
    public function reduceDays($field, $days)
    {
        $userId = $this->userIds[0];
        $wheres = array(
            'user_id' => [$userId],
            'vacation_id' => [$this->vacation->vacation_id],
            'month' => [date('Y-m'), '<']
        );
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 1){
            $days = round($days * 60,2);
        }
        $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
        $records = collect($records)->sortBy('month')->toArray();
        if ($records) {
            foreach ($records as $record) {
                if($vacationSet['is_transform'] == 0){
                    $hisDays = $record['days'];
                    //历史假期大于需要扣除的时候即退出
                    if ($hisDays >= $days) {
                        return app($this->vacationMonthRepository)->reduceDaysById($record['id'], 'days', $days);
                    } else {
                        if (app($this->vacationMonthRepository)->deleteById($record['id'])) {
                            $days -= $hisDays;
                        }
                    }
                }else{
                    $hisDays = $record['hours'];
                    //历史假期大于需要扣除的时候即退出
                    if ($hisDays >= $days) {
                        return app($this->vacationMonthRepository)->reduceDaysById($record['id'], 'hours', $days);
                    } else {
                        if (app($this->vacationMonthRepository)->deleteById($record['id'])) {
                            $days -= $hisDays;
                        }
                    }
                }
            }
        }
        //历史假期扣除完了再扣当前假期
        if ($days > 0) {
            return $this->increaseOutsendDays($field, -$days);
        }
    }

    /**
     * 删除历史假期余额
     * @return mixed
     */
    public function delHistoryDays()
    {
        $wheres = [
            'user_id' => [$this->userIds, 'in'],
            'vacation_id' => [$this->vacation->vacation_id],
            'month' => [date('Y-m'), '<']
        ];
        return app($this->vacationMonthRepository)->deleteByWhere($wheres);
    }

    /**
     * 每天执行定时任务
     * @return mixed|void
     */
    public function schedule()
    {
        //当前周期假期余额变为历史假期余额
        $curMonth = date('Y-m');
        if ($this->isLastDay()) {
            $data = array();
            //延长有效期
            if ($this->vacation->is_delay) {
                $userVacation = $this->getUserDays();
                foreach ($userVacation as $userId => $daysInfo) {
                    $days = $daysInfo['cur'];
                    if (!$days) {
                        continue;
                    }
                    $data[] = [
                        'vacation_id' => $this->vacation->vacation_id,
                        'user_id' => $userId,
                        'days' => $days,
                        'hours' => round($days * 60,2),
                        'month' => $curMonth,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
            $wheres = [
                'user_id' => [$this->userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'month' => [$curMonth]
            ];
            app($this->vacationMonthRepository)->deleteByWhere($wheres);
            if ($data) {
                app($this->vacationMonthRepository)->insertMultipleData($data);
            }
        }
        //不延长假期有效期
        if (!$this->vacation->is_delay) {
            $this->delHistoryDays();
        } else {
            switch ($this->vacation->delay_unit) {
                //过期单位是天
                case 1:
                    $preDays = $this->vacation->delay_days - 1;
                    $maxMonth = Carbon::now()->subDay($preDays)->subMonthNoOverflow()->format('Y-m');
                    break;
                //过期单位是月
                case 2:
                    $preMonth = $this->vacation->delay_days + 1;
                    $maxMonth = Carbon::now()->addDay(1)->subMonthNoOverflow($preMonth)->format('Y-m');
                    break;
                default:
                    return;
            }
            $wheres = [
                'user_id' => [$this->userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'month' => [$maxMonth, '<=']
            ];
            app($this->vacationMonthRepository)->deleteByWhere($wheres);
        }
    }

    public function getCurCycleOutSendDays()
    {
        $userId = $this->userIds[0];
        $wheres = array(
            'user_id' => [$userId],
            'vacation_id' => [$this->vacation->vacation_id],
            'month' => [date('Y-m')]
        );
        $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            return $records[0]['outsend_days'] ?? 0;
        }else{
            return isset($records[0]['outsend_hours']) ? round($records[0]['outsend_hours']/60,2) : 0;
        }
    }

    /**
     * 判断是否是当月的最后一天
     */
    private function isLastDay()
    {
        $nowDate = date('Y-m-1', strtotime(date('Y-m-d')));
        $lastDay = date('d', strtotime($nowDate.' +1 month -1 day'));
        return date('d') == $lastDay;
    }

    /**
     * 获取当前年用户假期余额
     * @return mixed
     */
    private function getCurrentUserDays()
    {
        //手动发放
        $userDays = array();
        switch ($this->vacation->give_method) {
            //手动
            case 1:
                $userDays = $this->getUserGiveDays();
                break;
            //自动
            case 2:
                $userDays = $this->getAutoUserDays();
                break;
            //自定义文件
            case 3:
                $userDays = $this->getCustomUserDays();
                break;
            default:
        }

        return $userDays;
    }

    /**
     * 获取用户手动设置的天数
     */
    private function getUserGiveDays()
    {
        return $this->formatDynamicUserDays([]);
    }

    /**
     * 自动发放天数计算
     * @return array
     */
    private function getAutoUserDays()
    {
        $userDays = array();
        foreach ($this->userIds as $userId) {
//            $vacationSet = app($this->vacationRepository)->getVacationSet();
//            if($vacationSet['is_transform'] == 0){
//                $days = $this->vacation->days_rule_detail;
//            }else{
//                $days = round($this->vacation->hours_rule_detail/$vacationSet['conversion_ratio'],$this->precision);
//            }
            $days = $this->vacation->days_rule_detail;
            $userDays[$userId] = round($days, $this->precision);
        }
        return $this->formatDynamicUserDays($userDays);
    }

    /**
     * 用户自定义文件的天数
     */
    private function getCustomUserDays()
    {
        try {
            $userDays = $this->queryCustomUrl($this->vacation->out_url, $this->userIds);
        } catch (\Exception $exception) {
            $userDays = [];
        }
        return $this->formatDynamicUserDays($userDays);
    }

    /**
     * 获取当年的记录
     * @param bool $isUserMap
     * @return array
     */
    private function getCurrentMonthRecords($isUserMap = false)
    {
        if ($this->records !== false) {
            $records = collect($this->records)->whereIn('user_id', $this->userIds)->where('month', date('Y-m'))->toArray();
        } else {
            $wheres = array(
                'user_id' => [$this->userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'month' => [date('Y-m')]
            );
            $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
        }
        if ($isUserMap) {
            $records = $this->arrayMapWithKey($records, 'user_id');
        }
        return $records;
    }

    /**
     * 获取历史月的假期余额
     * @param bool $isUserMap
     * @return array
     */
    private function getHistoryMonthDays()
    {
        $res = array();
        if ($this->records !== false) {
            $records = collect($this->records)->whereIn('user_id', $this->userIds)->where('month', '<', date('Y-m'))->toArray();
        } else {
            $wheres = array(
                'user_id' => [$this->userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'month' => [date('Y-m'), '<']
            );
            $records = app($this->vacationMonthRepository)->getAllByWhere($wheres)->toArray();
        }
        if (!$records) {
            return $res;
        }
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        foreach ($records as $record) {
            $userId = $record['user_id'];
            if($vacationSet['is_transform'] == 0){
                $days = round($record['days'] + $record['outsend_days'], $this->precision);
            }else{
                $days = round(($record['hours'] + $record['outsend_hours'])/60, 2);
            }
            //历史月小于0的不展示
            $days = $days < 0 ? 0 : $days;
            if (!isset($res[$userId])) {
                $res[$userId] = $days;
            } else {
                $res[$userId] += $days;
            }
        }
        return $res;
    }

    /**
     * 动态计算出的天数并不是当前剩余天数
     * @param $userDays
     */
    private function formatDynamicUserDays($userDays)
    {
        $res = array();
        $records = $this->getCurrentMonthRecords(true);
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        foreach ($this->userIds as $userId) {
            $days = $userDays[$userId] ?? 0;
            if($vacationSet['is_transform'] == 0){
                if (isset($records[$userId])) {
                    $record = $records[$userId];
                    $days = $days + $record['days'] + $record['outsend_days'];
                    $days = round($days, $this->precision);
                }
            }else{
                if (isset($records[$userId])) {
                    $days = $days * $vacationSet['conversion_ratio'];
                    $record = $records[$userId];
                    $days = round(($days + $record['hours'] + $record['outsend_hours'])/60, 2);
                    //echo "<pre>".print_r(111);die();
                }else{
                    $days = round($days * ($vacationSet['conversion_ratio']/60),2);
                }
            }
            $res[$userId] = $days;
        }
        return $res;
    }

    /**
     * 获取某个用户某个假期本周期开始时间和结束时间
     * @param $userId
     * @param $joinDate
     * @return mixed
     */
    public function getCycleDateRange($userId)
    {
        $startEnd = $this->getMonthStartEnd();
        return implode('~', $startEnd);
    }
}