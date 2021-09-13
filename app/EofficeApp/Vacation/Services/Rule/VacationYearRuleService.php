<?php

namespace App\EofficeApp\Vacation\Services\Rule;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Vacation\Traits\VacationTrait;
use Carbon\Carbon;

class VacationYearRuleService extends BaseService implements VacationRuleInterface
{


    use VacationTrait;

    private $vacation;
    private $userIds;
    private $profiles;
    private $records;
    private $vacationStrategyService;
    private $vacationYearRepository;
    private $vacationService;
    private $vacationRepository;

    public function __construct()
    {
        parent::__construct();
        $this->vacationStrategyService = 'App\EofficeApp\Vacation\Services\VacationStrategyService';
        $this->vacationService = 'App\EofficeApp\Vacation\Services\VacationService';
        $this->vacationYearRepository = 'App\EofficeApp\Vacation\Repositories\VacationYearRepository';
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
        $this->profiles = $profiles;
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
            return app($this->vacationYearRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
        }
        return true;
    }

    /**
     * 假期类别删除时的操作
     * @return mixed
     */
    public function delEvent()
    {
        return app($this->vacationYearRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
    }

    /**
     * 获取用户假期总额
     * @return mixed
     */
    public function getUserDays($log = '')
    {
        $res = array();
        if (!$this->userIds) {
            return $res;
        }
        //当前年
        $currentDays = $this->getCurrentUserDays();
        //历史
        $historyDays = $this->getHistoryYearDays();
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
        //兼容到到职日处理
        $yearUserIds = $this->getYearUserIdGroup($this->userIds);
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 1){
//            $days = round($days/$vacationSet['conversion_ratio']/60,4);
            $days = round($days * 60,2);
        }
        foreach ($yearUserIds as $year => $userIds) {
            $wheres = array(
                'user_id' => [$userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'year' => [$year]
            );
            $records = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
            $updateUserId = array_values(array_column($records, 'user_id'));
            $insertUserId = array_diff($this->userIds, $updateUserId);
            if ($updateUserId) {
                $wheres['user_id'][0] = $updateUserId;
                if (!app($this->vacationYearRepository)->increaseDays($wheres, $field, $days)) {
                    return false;
                }
            }
            if ($insertUserId) {
                $data = array();
                foreach ($insertUserId as $userId) {
                    $data[] = [
                        'user_id' => $userId,
                        'vacation_id' => $this->vacation->vacation_id,
                        'year' => $year,
                        $field => $days,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                if (!app($this->vacationYearRepository)->insertMultipleData($data)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 增加外发余额
     * @param $field
     * @param $days
     * @return bool
     */
    public function increaseOutsendDays($field, $days)
    {
        //兼容到到职日处理
        $yearUserIds = $this->getYearUserIdGroup($this->userIds);
        foreach ($yearUserIds as $year => $userIds) {
            $wheres = array(
                'user_id' => [$userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'year' => [$year]
            );
            $records = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
            $updateUserId = array_values(array_column($records, 'user_id'));
            $insertUserId = array_diff($this->userIds, $updateUserId);
            if ($updateUserId) {
                $wheres['user_id'][0] = $updateUserId;
                if (!app($this->vacationYearRepository)->increaseDays($wheres, $field, $days)) {
                    return false;
                }
            }
            if ($insertUserId) {
                $data = array();
                foreach ($insertUserId as $userId) {
                    $data[] = [
                        'user_id' => $userId,
                        'vacation_id' => $this->vacation->vacation_id,
                        'year' => $year,
                        $field => $days,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                if (!app($this->vacationYearRepository)->insertMultipleData($data)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function multIncreaseDays($data, $isHistory = false)
    {
        //兼容到职日
        $yearUserIds = $this->getYearUserIdGroup(array_keys($data));
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            $num = 1;
        }else{
            $num = 60;
        }
        foreach ($yearUserIds as $year => $userIds) {
            if ($isHistory) {
                $year = $year - 1;
            }
            $wheres = [
                'vacation_id' => [$this->vacation->vacation_id],
                'user_id' => [$userIds, 'in'],
                'year' => [$year]
            ];
            $records = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
            $existUserId = array_column($records, 'user_id');
            $insert = array();
            $update = array();
            foreach ($userIds as $userId) {
                $days = $data[$userId];
                if (in_array($userId, $existUserId)) {
                    $update[$userId] = round($days * $num,$this->precision);
//                    $update[$userId] = $days;
                } else {
                    $insert[] = [
                        'vacation_id' => $this->vacation->vacation_id,
                        'user_id' => $userId,
                        'hours' => round($days * 60,$this->precision),
                        'days' => $days,
                        'year' => $year,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
            if ($insert) {
                app($this->vacationYearRepository)->insertMultipleData($insert);
            }
            if ($update) {
                app($this->vacationYearRepository)->multIncreaseDays($this->vacation->vacation_id, $update, ['year' => $year]);
            }
        }
        return true;
    }

    /**
     * 减少假期余额
     * @return mixed
     */
    public function reduceDays($field, $days)
    {
        $userId = $this->userIds[0];
        $year = $this->getUserCurrentYear($userId);
        if (!$year) {
            return ['code' => ['0x052018', 'vacation']];
        }
        $wheres = array(
            'user_id' => [$userId],
            'vacation_id' => [$this->vacation->vacation_id],
            'year' => [$year, '<']
        );
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 1){
            $days = round($days * 60,2);
        }
        $records = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
        $records = collect($records)->sortBy('year')->toArray();
        if ($records) {
            foreach ($records as $record) {
                if($vacationSet['is_transform'] == 0){
                    $hisDays = $record['days'];
                    //历史假期大于需要扣除的时候即退出
                    if ($hisDays >= $days) {
                        return app($this->vacationYearRepository)->reduceDaysById($record['id'], 'days', $days);
                    } else {
                        if (app($this->vacationYearRepository)->deleteById($record['id'])) {
                            $days -= $hisDays;
                        }
                    }
                }else{
                    $hisDays = $record['hours'];
                    //历史假期大于需要扣除的时候即退出
                    if ($hisDays >= $days) {
                        return app($this->vacationYearRepository)->reduceDaysById($record['id'], 'hours', $days);
                    } else {
                        if (app($this->vacationYearRepository)->deleteById($record['id'])) {
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
        $yearUserIds = $this->getYearUserIdGroup($this->userIds);
        foreach ($yearUserIds as $year => $userIds) {
            $wheres = [
                'user_id' => [$userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'year' => [$year, '<']
            ];
            app($this->vacationYearRepository)->deleteByWhere($wheres);
        }
        return true;
    }

    /**
     * 每天执行定时任务
     * @return mixed|void
     */
    public function schedule()
    {
        switch ($this->vacation->cycle_point) {
            //元旦清零定时任务
            case 1:
                $this->scheduleForNewYear();
                break;
            //到职日清零定时任务
            case 2:
                $this->scheduleForJoinDate();
                break;
            default:
                return;
        }
    }

    /**
     * 元旦清零定时任务
     */
    private function scheduleForNewYear()
    {
        //当前周期假期余额变为历史假期余额
        $curYear = date('Y');
        if ($this->isLastDay()) {
            $data = array();
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
                        'year' => $curYear,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
            $wheres = [
                'user_id' => [$this->userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'year' => [$curYear]
            ];
            app($this->vacationYearRepository)->deleteByWhere($wheres);
            if ($data) {
                app($this->vacationYearRepository)->insertMultipleData($data);
            }
        }
        //不延长假期有效期
        if (!$this->vacation->is_delay) {
            $this->delHistoryDays();
        } else {
            switch ($this->vacation->delay_unit) {
                //过期单位是天
                case 1:
                    $preDays = $this->vacation->delay_days - 1;//保留天数包括今天
                    $maxYear = Carbon::now()->subDay($preDays)->subYearNoOverflow()->format('Y');
                    break;
                //过期单位是月
                case 2:
                    $preMonth = $this->vacation->delay_days;
                    $maxYear = Carbon::now()->addDay(1)->subMonthNoOverflow($preMonth)->format('Y') - 1;
                    break;
                default:
                    return;
            }
            $wheres = [
                'user_id' => [$this->userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id],
                'year' => [$maxYear, '<=']
            ];
            app($this->vacationYearRepository)->deleteByWhere($wheres);
        }
    }

    /**
     * 到职日清零定时任务
     */
    private function scheduleForJoinDate()
    {
        $allProfiles = $this->profiles;
        //查询当前是周期最后一天的用户
        $profiles = $this->getLastJoinDayProfile($this->profiles);
        if ($profiles) {
            $this->profiles = $profiles;
            $this->userIds = array_keys($profiles);
            $yearUserIds = $this->getYearUserIdGroup($this->userIds);
            $data = array();
            if ($this->vacation->is_delay) {
                $userVacation = $this->getUserDays();
                foreach ($yearUserIds as $year => $userIds) {
                    foreach ($userIds as $userId) {
                        $days = $userVacation[$userId]['cur'] ?? 0;
                        if (!$days) {
                            continue;
                        }
                        $data[] = [
                            'vacation_id' => $this->vacation->vacation_id,
                            'user_id' => $userId,
                            'days' => $days,
                            'hours' => round($days * 60,2),
                            'year' => $year,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
            //删除用户当前假期
            foreach ($yearUserIds as $year => $userIds) {
                $wheres = [
                    'user_id' => [$userIds, 'in'],
                    'vacation_id' => [$this->vacation->vacation_id],
                    'year' => [$year]
                ];
                app($this->vacationYearRepository)->deleteByWhere($wheres);
            }
            if ($data) {
                app($this->vacationYearRepository)->insertMultipleData($data);
            }
        }
        //不延长假期有效期
        if (!$this->vacation->is_delay) {
            $this->delHistoryDays();
        } else {
            $deleteWheres = array();
            $preDays = $this->vacation->delay_days - 1;//保留天数包括今天
            //$maxYear = Carbon::now()->subDay($preDays)->subYearNoOverflow()->format('Y-m-d');
            //在这之前产生的假期应该被清除掉
            $maxDate = Carbon::now()->subDay($preDays)->subYearNoOverflow()->format('Y-m-d');
            $year = explode('-', $maxDate)[0];
            foreach ($allProfiles as $userId => $profile) {
                if (!isset($profile['join_date'])) {
                    continue;
                }
                list($Y, $m, $d) = explode('-', $profile['join_date']);
                $createDate = $year . '-' . $m . '-' . $d;
                if ($createDate <= $maxDate) {
                    $deleteWheres[$year][] = $userId;
                }
            }
            if ($deleteWheres) {
                foreach ($deleteWheres as $year => $userIds) {
                    $wheres = [
                        'user_id' => [$userIds, 'in'],
                        'vacation_id' => [$this->vacation->vacation_id],
                        'year' => [$year, '<=']
                    ];
                    app($this->vacationYearRepository)->deleteByWhere($wheres);
                }
            }
        }
    }

    /**
     * 根据入职时间判断今天是不是当前周期最后一天，返回是最后一天的人事档案
     * @param $profiles
     * @return array
     */
    private function getLastJoinDayProfile($profiles)
    {
        $res = array();
        if (!$profiles) {
            return $res;
        }
        $date = date("Y-m-d", strtotime('+1 days'));
        list($year, $month, $day) = explode('-', $date);
        foreach ($profiles as $userId => $profile) {
            if (isset($profile['join_date'])) {
                $date = $profile['join_date'];
                list($Y, $m, $d) = explode('-', $date);
                if ($m == $month && $d == $day) {
                    $res[$userId] = $profile;
                }
            }
        }
        return $res;
    }

    public function getCurCycleOutSendDays()
    {
        $userId = $this->userIds[0];
        $year = $this->getUserCurrentYear($userId);
        if (!$year) {
            return 0;
        }
        $wheres = array(
            'user_id' => [$userId],
            'vacation_id' => [$this->vacation->vacation_id],
            'year' => [$year]
        );
        $records = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            return $records[0]['outsend_days'] ?? 0;
        }else{
            return isset($records[0]['outsend_hours']) ? round($records[0]['outsend_hours']/60,2) : 0;
        }
    }

    /**
     * 获取某个用户某个假期本周期开始时间和结束时间
     * @param $userId
     * @param $joinDate
     * @return mixed
     */
    public function getCycleDateRange($userId)
    {
        if ($this->vacation->cycle_point == 1) {
            return date('Y') . '-01-01~' . date('Y') . '-12-31';
        } else {
            $curYear = $this->getUserCurrentYear($userId);
            $records = app($this->vacationStrategyService)->getProfileInfo([$userId]);
            if (isset($records[$userId]['join_date'])) {
                $date = $records[$userId]['join_date'];
                list($year, $month, $day) = explode('-', $date);
                $startDate = $curYear . "-$month-$day";
                $endDate = ($curYear + 1) . "-$month-$day";
                $endDate = date('Y-m-d', strtotime($endDate) - 24 * 3600);
                return $startDate . '~' . $endDate;
            } else {
                return '-';
            }
        }
    }

    /**
     * 获取截止日前所有要过期的信息
     * @param $deadline
     * @return mixed|void
     */
    public function getBeforeDeadline($deadline, $notifyDay)
    {
        $data = array();
        //先获取本周期时间段
        $userDateRange = $this->getDateRange($this->userIds, $this->profiles);
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        //元旦清零
        if ($this->vacation->cycle_point == 1) {
            $date = date('Y-12-31');
            if ($this->vacation->is_delay) {
                $date = $this->addDuration($date, $this->vacation->delay_days, $this->vacation->delay_unit);
            }
            if ($date <= $deadline) {
                $userVacation = $this->getCurrentUserDays();
                foreach ($userVacation as $userId => $day) {
                    if ($day > 0) {
//                        if($vacationSet['is_transform'] == 1){
//                            $day = round($day * ($vacationSet['conversion_ratio']/60),2);
//                        }
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
        } else {
            //到入职日清零
            $userVacation = $this->getCurrentUserDays();
            foreach ($userDateRange as $userId => $range) {
                //快要过期的假期
                $date = $range[1];
                if ($this->vacation->is_delay) {
                    $date = $this->addDuration($date, $this->vacation->delay_days, $this->vacation->delay_unit);
                }
                if ($date <= $deadline) {
                    if (isset($userVacation[$userId]) && $userVacation[$userId] > 0) {
//                        if($vacationSet['is_transform'] == 1){
//                            $userVacation[$userId] = round($userVacation[$userId] * ($vacationSet['conversion_ratio']/60),2);
//                        }
                        $data[] = [
                            'user_id' => $userId,
                            'date' => $date,
                            'vacation_id' => $this->vacation->vacation_id,
                            'vacation_name' => $this->vacation->vacation_name,
                            'day' => $userVacation[$userId]
                        ];
                    }
                }
            }
        }
        //如果是允许延长有效期，还有历史假期提醒
        if ($this->vacation->is_delay) {
            $records = array();
            $yearUserIds = $this->getYearUserIdGroup($this->userIds);
            foreach ($yearUserIds as $year => $userIds) {
                $wheres = array(
                    'user_id' => [$userIds, 'in'],
                    'vacation_id' => [$this->vacation->vacation_id],
                    'year' => [$year, '<']
                );
                $queryData = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
                $records = array_merge($records, $queryData);
            }
            if ($records) {
                foreach ($records as $record) {
                    $userId = $record['user_id'];
                    if (isset($userDateRange[$userId])) {
                        $endDate = $userDateRange[$userId][1];
                        $endDate = explode('-', $endDate);
                        //当前年的最后一天，替换成历史年的最后一天
                        $endDate[0] = $this->vacation->cycle_point == 2 ? $record['year'] + 1 : $record['year'];
                        $endDate = implode('-', $endDate);
                        $date = $this->addDuration($endDate, $this->vacation->delay_days, $this->vacation->delay_unit);
                        if ($date <= $deadline) {
//                            if($vacationSet['is_transform'] == 1){
//                                $record['days'] = round($record['days'] * ($vacationSet['conversion_ratio']/60),2);
//                            }
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
        }
        return $data;
    }

    /**
     * 获取用户当前年的周期
     * @param $userIds
     * @param $profiles
     */
    private function getDateRange($userIds, $profiles)
    {
        $res = array();
        foreach ($userIds as $userId) {
            if ($this->vacation->cycle_point == 1) {
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
            } else {
                if (!isset($profiles[$userId]['join_date'])) {
                    continue;
                }
                $joinDate = $profiles[$userId]['join_date'];
                list($year, $month, $day) = explode('-', $joinDate);
                $currentYearJoinDate = date('Y') . '-' . $month . '-' . $day;
                //当前年还没有到入职日
                if (date('Y-m-d') < $currentYearJoinDate) {
                    $year = date('Y') - 1;
                } else {
                    $year = date('Y');
                }
                $startDate = $year . "-$month-$day";
                $endDate = ($year + 1) . "-$month-$day";
                $endDate = date('Y-m-d', strtotime($endDate) - 24 * 3600);
            }
            $res[$userId] = [$startDate, $endDate];
        }
        return $res;
    }

    /**
     * 是否是当前周期的最后一天
     */
    private function isLastDay()
    {
        return date('m-d') == '12-31';
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
        $profiles = $this->profiles;
        foreach ($this->userIds as $userId) {
            $days = 0;
            //到职日没找到对应的入职信息
            if ($this->vacation->cycle_point == 2) {
                if (!isset($profiles[$userId]['join_date']) || $profiles[$userId]['join_date'] > date('Y-m-d')) {
                    $userDays[$userId] = 0;
                    continue;
                }
            }
            if ($this->vacation->days_rule_method == 1) {
                $days = $this->vacation->days_rule_detail;
//                $vacationSet = app($this->vacationRepository)->getVacationSet();
//                if($vacationSet['is_transform'] == 0){
//                    $days = $this->vacation->days_rule_detail;
//                }else{
//                    $days = round($this->vacation->hours_rule_detail/$vacationSet['conversion_ratio'],$this->precision);
////                    echo "<pre>".print_r($this->vacation);die();
//                }
            } else {
                $type = [2 => 'work_date', 3 => 'join_date', 4 => 'birthday'];
                $type = $type[$this->vacation->days_rule_method];
                if (isset($profiles[$userId][$type])) {
                    $startDate = $profiles[$userId][$type];
                    $endDate = date('Y-m-d');
                    $days = $this->parseUserDays($startDate, $endDate, $type);
                }
            }
            //新员工入职折算
            if ($this->vacation->add_user_cut) {
                if (!isset($profiles[$userId]['join_date'])) {
                    $days = 0;
                } else {
                    $joinDate = $profiles[$userId]['join_date'];
                    //今年入职的
                    if (date('Y', strtotime($joinDate)) == date('Y')) {
                        $month = intval(date('m', strtotime($joinDate)));
                        //今年工作的月份百分比
                        $workPercent = 1 - ($month - 1) / 12;
                        $days *= $workPercent;
                    }
                }
            }
            //按月释放
            if ($this->vacation->one_time_give == 0) {
                if ($this->vacation->cycle_point == 1) {
                    $percent = $this->getPercent(1);
                    $days = round($days * $percent, $this->precision);
                } else {
                    $dateRange = $this->getCycleDateRange($userId);
                    $dateRange = explode('~', $dateRange);
                    $startDate = $dateRange[0];
                    $diffMonth = Carbon::create($startDate)->diffInMonths(date('Y-m-d'));
                    $days = ($diffMonth + 1) / 12 * $days;
                }
            } elseif ($this->vacation->one_time_give == 2) {
                //按天释放
                if ($this->vacation->cycle_point == 1) {
                    $startDate = date('Y-01-01');
                    $endDate = date('Y-12-31');
                } else {
                    $dateRange = $this->getCycleDateRange($userId);
                    list($startDate, $endDate) = explode('~', $dateRange);
                }
                $yearTotalDay = Carbon::create($startDate)->diffInDays($endDate) + 1;
                $days = ((Carbon::now()->diffInDays($startDate) + 1) / $yearTotalDay) * $days;
            }
            if ($days) {
                $userDays[$userId] = round($days, $this->precision);
            }
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
     * 根据规则计算出假期天数
     * @param $startDate
     * @param $endDate
     * @param $ruleDetail
     * @return int
     */
    private function parseUserDays($startDate, $endDate, $type)
    {
        if ($type == 'birthday') {
            $year = (new \Carbon\Carbon($startDate))->age;
        } else {
            $year = round(Carbon::parse($startDate)->floatDiffInRealYears($endDate), $this->precision);
        }
        $ruleDetail = $this->vacation->days_rule_detail;
        $ruleDetail = json_decode($ruleDetail, true);
        $ruleDetail = collect($ruleDetail)->sortByDesc('year')->toArray();
        if (!$ruleDetail) {
            return 0;
        }
        foreach ($ruleDetail as $level) {
            if ($year >= $level['year']) {
                return $level['days'];
            }
        }
//        $vacationSet = app($this->vacationRepository)->getVacationSet();
//        if($vacationSet['is_transform'] == 0){
//            $ruleDetail = $this->vacation->days_rule_detail;
//            $ruleDetail = json_decode($ruleDetail, true);
//            $ruleDetail = collect($ruleDetail)->sortByDesc('year')->toArray();
//            if (!$ruleDetail) {
//                return 0;
//            }
//            foreach ($ruleDetail as $level) {
//                if ($year >= $level['year']) {
//                    return $level['days'];
//                }
//            }
//        }else{
//            $ruleDetail = $this->vacation->hours_rule_detail;
//            $ruleDetail = json_decode($ruleDetail, true);
//            $ruleDetail = collect($ruleDetail)->sortByDesc('year')->toArray();
//            if (!$ruleDetail) {
//                return 0;
//            }
//            foreach ($ruleDetail as $level) {
//                if ($year >= $level['year']) {
//                    return $level['hours'];
//                }
//            }
//        }
        return 0;
    }

    /**
     * 获取当年的记录
     * @param bool $isUserMap
     * @return array
     */
    private function getCurrentYearRecords()
    {
        $records = array();
        $yearUserIds = $this->getYearUserIdGroup($this->userIds);
        foreach ($yearUserIds as $year => $userIds) {
            if ($this->records !== false) {
                $queryData = collect($this->records)->whereIn('user_id', $userIds)->where('year', $year)->toArray();
            } else {
                $wheres = array(
                    'user_id' => [$userIds, 'in'],
                    'vacation_id' => [$this->vacation->vacation_id],
                    'year' => [$year]
                );
                $queryData = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
            }
            $records = array_merge($records, $queryData);
        }
        return $records;
    }

    /**
     * 获取历史年的假期余额
     * @param bool $isUserMap
     * @return array
     */
    private function getHistoryYearDays()
    {
        $res = array();
        $records = array();
        $yearUserIds = $this->getYearUserIdGroup($this->userIds);
        foreach ($yearUserIds as $year => $userIds) {
            if ($this->records !== false) {
                $queryData = collect($this->records)
                    ->whereIn('user_id', $userIds)
                    ->where('year', '<', $year)
                    ->toArray();
            } else {
                $wheres = array(
                    'user_id' => [$userIds, 'in'],
                    'vacation_id' => [$this->vacation->vacation_id],
                    'year' => [$year, '<']
                );
                $queryData = app($this->vacationYearRepository)->getAllByWhere($wheres)->toArray();
            }
            $records = array_merge($records, $queryData);
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
            //历史年小于0的不展示
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
        $records = $this->getCurrentYearRecords();
        $records = $this->arrayMapWithKey($records, 'user_id');
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
     * 映射当前年和用户id
     * @return array
     */
    private function getYearUserIdGroup($userIds)
    {
        if (!$userIds) {
            return [];
        }
        $this->profiles = app($this->vacationStrategyService)->getProfileInfo($userIds);
        $group = array();
        foreach ($userIds as $userId) {
            if ($this->vacation->cycle_point == 1) {
                $year = date('Y');
                $group[$year][] = $userId;
            } else {
                if (!isset($this->profiles[$userId]['join_date'])) {
                    continue;
                }
                $joinDate = $this->profiles[$userId]['join_date'];
                list($year, $month, $day) = explode('-', $joinDate);
                $currentYearJoinDate = date('Y') . '-' . $month . '-' . $day;
                //当前年还没有到入职日
                if (date('Y-m-d') < $currentYearJoinDate) {
                    $year = date('Y') - 1;
                } else {
                    $year = date('Y');
                }
                $group[$year][] = $userId;
            }
        }
        return $group;
    }

    /**
     * 获取用户当前周期的年份，按入职日开始算一年用
     */
    private function getUserCurrentYear($userId)
    {
        $data = $this->getYearUserIdGroup([$userId]);
        if (!$data) {
            return false;
        }
        return array_key_first($data);
    }
}