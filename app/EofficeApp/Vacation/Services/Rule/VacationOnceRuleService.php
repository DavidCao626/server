<?php

namespace App\EofficeApp\Vacation\Services\Rule;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Vacation\Traits\VacationTrait;

class VacationOnceRuleService extends BaseService implements VacationRuleInterface
{


    use VacationTrait;

    private $vacation;
    private $userIds;
    private $records;
    private $vacationOnceRepository;
    private $vacationExpireRecordRepository;
    private $vacationRepository;

    public function __construct()
    {
        parent::__construct();
        $this->vacationOnceRepository = 'App\EofficeApp\Vacation\Repositories\VacationOnceRepository';
        $this->vacationExpireRecordRepository = 'App\EofficeApp\Vacation\Repositories\VacationExpireRecordRepository';
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
            app($this->vacationExpireRecordRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
            return app($this->vacationOnceRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
        }
        //永久改多少天后删除
        if ($this->vacation->no_cycle_expire_mode == 1 && $newVacation->no_cycle_expire_mode == 2) {
            $wheres = array(
                'vacation_id' => [$this->vacation->vacation_id]
            );
            $vacationDays = app($this->vacationOnceRepository)->getAllByWhere($wheres)->toArray();
            if ($vacationDays) {
                $records = array();
                foreach ($vacationDays as $item) {
                    $record['user_id'] = $item['user_id'];
                    $record['vacation_id'] = $item['vacation_id'];
                    $record['days'] = $item['days'];
                    $record['hours'] = $item['hours'];
                    $record['created_date'] = date('Y-m-d');
                    $records[] = $record;
                }
//                $vacationSet = app($this->vacationRepository)->getVacationSet();
//                if($vacationSet['is_transform'] == 0){
//                    foreach ($vacationDays as $item) {
//                        $record['user_id'] = $item['user_id'];
//                        $record['vacation_id'] = $item['vacation_id'];
//                        $record['days'] = $item['days'];
//                        $record['created_date'] = date('Y-m-d');
//                        $records[] = $record;
//                    }
//                }else{
//                    foreach ($vacationDays as $item) {
//                        $record['user_id'] = $item['user_id'];
//                        $record['vacation_id'] = $item['vacation_id'];
//                        $record['days'] = round($item['days']/($vacationSet['conversion_ratio']/60),4);
//                        $record['created_date'] = date('Y-m-d');
//                        $records[] = $record;
//                    }
//                }
                $this->addVacationExpireRecord($records);
            }
        }
        //多少天后删除改永久
        if ($this->vacation->no_cycle_expire_mode == 2 && $newVacation->no_cycle_expire_mode == 1) {
            app($this->vacationExpireRecordRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
        }
        return true;
    }

    /**
     * 假期类别删除时的操作
     * @return mixed
     */
    public function delEvent()
    {
        app($this->vacationOnceRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
        app($this->vacationExpireRecordRepository)->deleteByWhere(['vacation_id' => [$this->vacation->vacation_id]]);
        return true;
    }

    /**
     * 获取用户假期总额
     * @return mixed
     */
    public function getUserDays($log = '')
    {
        $res = array();
        if ($this->records !== false) {
            $records = collect($this->records)->whereIn('user_id', $this->userIds)->toArray();
        } else {
            $wheres = array(
                'user_id' => [$this->userIds, 'in'],
                'vacation_id' => [$this->vacation->vacation_id]
            );
            $records = app($this->vacationOnceRepository)->getAllByWhere($wheres)->toArray();
        }
        $records = $this->arrayMapWithKey($records, 'user_id');
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            foreach ($this->userIds as $userId) {
                $days = 0;
                if (isset($records[$userId])) {
                    $record = $records[$userId];
                    $days = $record['days'] + $record['outsend_days'];
                    $days = round($days, $this->precision);
                }
                $res[$userId]['cur'] = $days < 0 ? 0 : $days;
                $res[$userId]['his'] = 0;
            }
        }else{
            foreach ($this->userIds as $userId) {
                $days = 0;
                if (isset($records[$userId])) {
                    $record = $records[$userId];
                    $days = ($record['hours'] + $record['outsend_hours'])/60;
                    $days = round($days, 2);
                }
                $res[$userId]['cur'] = $days < 0 ? 0 : $days;
                $res[$userId]['his'] = 0;
            }
        }

        return $res;
    }

    /**
     * 增加假期余额
     * @return mixed
     */
    public function increaseDays($field, $days)
    {
        $wheres = array(
            'user_id' => [$this->userIds, 'in'],
            'vacation_id' => [$this->vacation->vacation_id],
        );
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 1){
//            $days = round($days/$vacationSet['conversion_ratio']/60,4);
            $days = round($days * 60,2);
        }
        $records = app($this->vacationOnceRepository)->getAllByWhere($wheres)->toArray();
        $updateUserId = array_values(array_column($records, 'user_id'));
        $insertUserId = array_diff($this->userIds, $updateUserId);
        if ($updateUserId) {
            $wheres['user_id'][0] = $updateUserId;
            if (!app($this->vacationOnceRepository)->increaseDays($wheres, $field, $days)) {
                return false;
            }
        }
        if ($insertUserId) {
            $data = array();
            foreach ($insertUserId as $userId) {
                $data[] = [
                    'user_id' => $userId,
                    'vacation_id' => $this->vacation->vacation_id,
                    $field => $days,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            if (!app($this->vacationOnceRepository)->insertMultipleData($data)) {
                return false;
            }
        }
        //永久性不会有过期记录
        if ($this->vacation->no_cycle_expire_mode != 2) {
            return true;
        }
        if ($days <= 0) {
            return true;
        }
        $records = array();
        if($vacationSet['is_transform'] == 0){
            foreach ($this->userIds as $userId) {
                $item['user_id'] = $userId;
                $item['vacation_id'] = $this->vacation->vacation_id;
                $item['days'] = $days;
                $item['created_date'] = date('Y-m-d');
                $records[] = $item;
            }
        }else{
            foreach ($this->userIds as $userId) {
                $item['user_id'] = $userId;
                $item['vacation_id'] = $this->vacation->vacation_id;
                $item['hours'] = $days;
                $item['created_date'] = date('Y-m-d');
                $records[] = $item;
            }
        }
        return $this->addVacationExpireRecord($records);
    }

    /**
     * 减少假期余额
     * @return mixed
     */
    public function reduceDays($field, $days)
    {
        $wheres = array(
            'user_id' => [$this->userIds, 'in'],
            'vacation_id' => [$this->vacation->vacation_id],
        );
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 1){
            $days = round($days * 60,2);
        }
        if (app($this->vacationOnceRepository)->increaseDays($wheres, $field, -$days)) {
            //永久性不用执行下面，只有到期自动扣除的需要执行下面
            if ($this->vacation->no_cycle_expire_mode == 1) {
                return true;
            }
            //到期要扣除的日志抵消下，优先抵消时间靠前的
            foreach ($this->userIds as $userId) {
                $this->delVacationExpireRecord($userId, $days);
            }
            return true;
        }
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
        //永久性没定时任务什么事
        if ($this->vacation->no_cycle_expire_mode == 1) {
            return true;
        }
        $preDays = $this->vacation->no_cycle_expire_days;
        $startDate = date('Y-m-d', time() - 3600 * 24 * $preDays);
        $wheres = [
            'vacation_id' => [$this->vacation->vacation_id],
            'created_date' => [$startDate, '<=']
        ];
        $records = app($this->vacationExpireRecordRepository)->getAllByWhere($wheres)->toArray();
        if (!$records) {
            return true;
        }
        $records = $this->arrayMapWithKey($records, 'user_id', true);
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            foreach ($records as $userId => $userRecords) {
                $recordIds = array_column($userRecords, 'id');
                $totalDays = array_sum(array_values(array_column($userRecords, 'days')));
                $wheres = array(
                    'user_id' => [$userId],
                    'vacation_id' => [$this->vacation->vacation_id],
                );
                if (app($this->vacationOnceRepository)->increaseDays($wheres, 'days', -$totalDays)) {
                    //删除过期记录
                    app($this->vacationExpireRecordRepository)->deleteByWhere([
                        'id' => [$recordIds, 'in']
                    ]);
                }
            }
        }else{
            foreach ($records as $userId => $userRecords) {
                $recordIds = array_column($userRecords, 'id');
                $totalDays = array_sum(array_values(array_column($userRecords, 'hours')));
                $wheres = array(
                    'user_id' => [$userId],
                    'vacation_id' => [$this->vacation->vacation_id],
                );
                if (app($this->vacationOnceRepository)->increaseDays($wheres, 'hours', -$totalDays)) {
                    //删除过期记录
                    app($this->vacationExpireRecordRepository)->deleteByWhere([
                        'id' => [$recordIds, 'in']
                    ]);
                }
            }
        }
        return true;
    }

    public function multIncreaseDays($data, $isHistory = false)
    {
        $userIds = array_keys($data);
        $wheres = [
            'vacation_id' => [$this->vacation->vacation_id],
            'user_id' => [$userIds, 'in'],
        ];
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            $num = 1;
        }else{
            $num = 60;
        }
        $records = app($this->vacationOnceRepository)->getAllByWhere($wheres)->toArray();
        $existUserId = array_column($records, 'user_id');
        $insert = array();
        $update = array();
        foreach ($data as $userId => $days) {
            if (in_array($userId, $existUserId)) {
//                $update[$userId] = $days;
                $update[$userId] = round($days * $num,$this->precision);
            } else {
                $insert[] = [
                    'vacation_id' => $this->vacation->vacation_id,
                    'user_id' => $userId,
//                    'days' => round($days/$num,4),
                    'hours' => round($days * 60,$this->precision),
                    'days' => $days,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        if ($insert) {
            app($this->vacationOnceRepository)->insertMultipleData($insert);
        }
        if ($update) {
            app($this->vacationOnceRepository)->multIncreaseDays($this->vacation->vacation_id, $update);
        }
        //永久性不会有过期记录
        if ($this->vacation->no_cycle_expire_mode != 2) {
            return true;
        }
        $records = array();
        foreach ($data as $userId => $days) {
            if ($days == 0) {
                continue;
            }
            //管理员编辑减少的话，少了几天就优先扣除下要过期的记录，类似于请假的天的扣减
            if ($days < 0) {
//                $this->delVacationExpireRecord($userId, -$days);//-$days>0
                $this->delVacationExpireRecord($userId, -(round($days * $num, $this->precision)));//-$days>0
                continue;
            }
            $item['user_id'] = $userId;
            $item['vacation_id'] = $this->vacation->vacation_id;
//            $item['days'] = round($days/$num,4);
            $item['days'] = $days;
            $item['hours'] = round($days * 60,$this->precision);
            $item['created_date'] = date('Y-m-d');
            $records[] = $item;
        }
        return $this->addVacationExpireRecord($records);
    }

    public function getCurCycleOutSendDays()
    {
        $userId = $this->userIds[0];
        $wheres = array(
            'user_id' => [$userId],
            'vacation_id' => [$this->vacation->vacation_id]
        );
        $records = app($this->vacationOnceRepository)->getAllByWhere($wheres)->toArray();
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            return $records[0]['outsend_days'] ?? 0;
        }else{
            return isset($records[0]['outsend_hours']) ? round($records[0]['outsend_hours']/60,2) : 0;
        }
    }

    /**
     * 添加假期过期记录
     * @param $data
     * 【
     *   [
     *     'user_id'=>'admin',
     *     'vacation_id'=>1,
     *     'days'=>1.5,
     *     'created_date'=>'2019-01-01',
     *  ]
     * 】
     */
    private function addVacationExpireRecord($data)
    {
        if (!$data) {
            return true;
        }
        $data = array_map(function ($row) {
            $row['created_at'] = date('Y-m-d H:i:s');
            $row['updated_at'] = date('Y-m-d H:i:s');
            return $row;
        }, $data);
        return app($this->vacationExpireRecordRepository)->insertMultipleData($data);
    }

    /**
     * 假期减少，删除对应的到期扣除记录
     * @param $userId
     * @param $vacationId
     * @param $days
     */
    private function delVacationExpireRecord($userId, $days)
    {
        $wheres = array(
            'user_id' => [$userId],
            'vacation_id' => [$this->vacation->vacation_id],
        );
        $records = app($this->vacationExpireRecordRepository)->getAllByWhere($wheres)->toArray();
        $records = collect($records)->sortBy('created_date')->toArray();
        if (!$records) {
            return true;
        }
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            foreach ($records as $record) {
                $recordDays = $record['days'];
                //历史假期大于需要扣除的时候即退出
                if ($recordDays >= $days) {
                    return app($this->vacationExpireRecordRepository)->reduceDaysById($record['id'], 'days', $days);
                } else {
                    if (app($this->vacationExpireRecordRepository)->deleteById($record['id'])) {
                        $days -= $recordDays;
                    }
                }
            }
        }else{
            foreach ($records as $record) {
//                $days = round($days * 60,2);
                $recordDays = $record['hours'];
                //历史假期大于需要扣除的时候即退出
                if ($recordDays >= $days) {
                    return app($this->vacationExpireRecordRepository)->reduceDaysById($record['id'], 'hours', $days);
                } else {
                    if (app($this->vacationExpireRecordRepository)->deleteById($record['id'])) {
                        $days -= $recordDays;
                    }
                }
            }
        }
        return true;
    }

    /* 获取某个用户某个假期本周期开始时间和结束时间
    * @param $userId
    * @param $joinDate
    * @return mixed
    */
    public function getCycleDateRange($userId)
    {
        if ($this->vacation->no_cycle_expire_mode == 2) {
            return trans('vacation.valid_within_day_after_issuance', ['days' => $this->vacation->no_cycle_expire_days]);
        }
        return trans('vacation.forever');
    }

    public function getBeforeDeadline($deadline, $notifyDay)
    {
        $data = array();
        if ($this->vacation->no_cycle_expire_mode != 2) {
            return [];
        }
        //发放多少天后自动过期
        $wheres = [
            'vacation_id' => [$this->vacation->vacation_id],
            'user_id' => is_array($this->userIds) ? [$this->userIds, 'in'] : [$this->userIds]
        ];
        $records = app($this->vacationExpireRecordRepository)->getAllByWhere($wheres)->toArray();
        if (!$records) {
            return [];
        }
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        foreach ($records as $record) {
            $userId = $record['user_id'];
            $date = $this->addDuration($record['created_date'], $this->vacation->no_cycle_expire_days, 1);
            if ($date <= $deadline) {
//                if($vacationSet['is_transform'] == 1){
//                    $record['days'] = round($record['days'] * ($vacationSet['conversion_ratio']/60),2);
//                }
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
        return $data;
    }
}