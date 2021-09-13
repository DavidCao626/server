<?php

namespace App\EofficeApp\Vacation\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 假期余额变动日志
 * Trait VacationLogTrait
 * @package App\EofficeApp\Vacation\Traits
 */
trait VacationLogTrait
{

    private $before = [];
    private $after = [];
    private $logVacationIds;
    private $logUserIds;
    private $logReason;
    //假期余额变动的几个原因
    private $reason = [
        'create' => 1,    //创建假期类型
        'edit' => 2,    //编辑假期类型、规则
        'set' => 3,    //单个编辑用户假期
        'mult_set' => 4,    //批量编辑用户假期
        'import' => 5,    //导入
        'expire' => 6,    //过期
        'overtime' => 7,    //加班
        'leave' => 8,    //请假
        'clear' => 9,//删除历史假期
        'system' => 10,//系统执行
        'join' => 11,//新员工入职
    ];

    /**
     * 记录日志的假期对象和人员
     * @param $vacationId
     */
    public function listen($vacationIds, $userIds = true)
    {
        if (!is_array($vacationIds)) {
            $vacationIds = [$vacationIds];
        }
        if ($userIds === true) {
            $userIds = app($this->userRepository)->getAllUserIdString();
            $userIds = explode(',', $userIds);
        } elseif (!is_array($userIds)) {
            $userIds = [$userIds];
        }
        $this->logVacationIds = $vacationIds;
        $this->logUserIds = $userIds;
        return $this;
    }

    public function setLogReason($reason)
    {
        $this->logReason = $reason;
        return $this;
    }

    /**
     * 开始记录日志
     */
    public function startLog($profiles=[])
    {
        $this->before = $this->getUserLogVacation($profiles);
        return $this;
    }

    /**
     * 结束记录日志
     */
    public function stopLog($profiles=[])
    {
        $this->after = $this->getUserLogVacation($profiles);
        return $this;
    }

    /**
     * 存储日志
     */
    public function saveLog()
    {
        $compares = $this->compareLog();
        $logs = array_map(function ($log) {
            $log['reason'] = $this->logReason;
            $log['date'] = date('Y-m-d');
            $log['created_at'] = date('Y-m-d H:i:s');
            $log['updated_at'] = date('Y-m-d H:i:s');
            return $log;
        }, $compares);
        app('App\EofficeApp\Vacation\Repositories\VacationLogRepository')->insertMultipleData($logs);
    }

    /**
     * 比较日志
     */
    private function compareLog()
    {
        $compare = array();
        $setData = app($this->vacationRepository)->getVacationSet();
        //本次循环记录下变更和删除的，新增的在下面循环记录
        foreach ($this->before as $userId => $data) {
            foreach ($data as $vacationId => $dayInfo) {
                $c1 = $this->before[$userId][$vacationId]['cur'] ?? 0;
                $h1 = $this->before[$userId][$vacationId]['his'] ?? 0;
                $c2 = $this->after[$userId][$vacationId]['cur'] ?? 0;
                $h2 = $this->after[$userId][$vacationId]['his'] ?? 0;
//                $c1 = isset($this->before[$userId][$vacationId]['cur']) ? round($this->before[$userId][$vacationId]['cur']/$num,4) : 0;
//                $h1 = isset($this->before[$userId][$vacationId]['his']) ? round($this->before[$userId][$vacationId]['his']/$num,4) : 0;
//                $c2 = isset($this->after[$userId][$vacationId]['cur']) ? round($this->after[$userId][$vacationId]['cur']/$num,4) : 0;
//                $h2 = isset($this->after[$userId][$vacationId]['his']) ? round($this->after[$userId][$vacationId]['his']/$num,4) : 0;
                $change = isset($this->before[$userId][$vacationId]) && isset($this->after[$userId][$vacationId]);
                $delete = isset($this->before[$userId][$vacationId]) && !isset($this->after[$userId][$vacationId]);
                if ($change || $delete) {
                    if ($c1 != $c2) {
                        if($setData['is_transform'] == 0){
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => $c1,
                                'after' => $c2,
                                'change' => $c2 - $c1,
                                'before_hours' => $c1 * $setData['conversion_ratio'],
                                'after_hours' => $c2 * $setData['conversion_ratio'],
                                'change_hours' => ($c2 - $c1) * $setData['conversion_ratio'],
                                'vacation_id' => $vacationId,
                                'when' => 1
                            ];
                        }else{
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => round($c1/($setData['conversion_ratio']/60),4),
                                'after' => round($c2/($setData['conversion_ratio']/60),4),
                                'change' => round(($c2 - $c1)/($setData['conversion_ratio']/60),4),
                                'before_hours' => $c1 * 60,
                                'after_hours' => $c2 * 60,
                                'change_hours' => ($c2 - $c1) * 60,
                                'vacation_id' => $vacationId,
                                'when' => 1
                            ];
                        }
                    }
                    if ($h1 != $h2) {
                        if($setData['is_transform'] == 0){
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => $h1,
                                'after' => $h2,
                                'change' => $h2 - $h1,
                                'before_hours' => $h1 * $setData['conversion_ratio'],
                                'after_hours' => $h2 * $setData['conversion_ratio'],
                                'change_hours' => ($h2 - $h1) * $setData['conversion_ratio'],
                                'vacation_id' => $vacationId,
                                'when' => 2
                            ];
                        }else{
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => round($h1/($setData['conversion_ratio']/60),4),
                                'after' => round($h2/($setData['conversion_ratio']/60),4),
                                'change' => round(($h2 - $h1)/($setData['conversion_ratio']/60),4),
                                'before_hours' => $h1 * 60,
                                'after_hours' => $h2 * 60,
                                'change_hours' => ($h2 - $h1) * 60,
                                'vacation_id' => $vacationId,
                                'when' => 2
                            ];
                        }
                    }
                }
            }
        }
        //本次循环只记录新增的
        foreach ($this->after as $userId => $data) {
            foreach ($data as $vacationId => $dayInfo) {
//                $c1 = isset($this->before[$userId][$vacationId]['cur']) ? round($this->before[$userId][$vacationId]['cur']/$num,4) : 0;
//                $h1 = isset($this->before[$userId][$vacationId]['his']) ? round($this->before[$userId][$vacationId]['his']/$num,4) : 0;
//                $c2 = isset($this->after[$userId][$vacationId]['cur']) ? round($this->after[$userId][$vacationId]['cur']/$num,4) : 0;
//                $h2 = isset($this->after[$userId][$vacationId]['his']) ? round($this->after[$userId][$vacationId]['his']/$num,4) : 0;
                $c1 = $this->before[$userId][$vacationId]['cur'] ?? 0;
                $h1 = $this->before[$userId][$vacationId]['his'] ?? 0;
                $c2 = $this->after[$userId][$vacationId]['cur'] ?? 0;
                $h2 = $this->after[$userId][$vacationId]['his'] ?? 0;
                $add = !isset($this->before[$userId][$vacationId]) && isset($this->after[$userId][$vacationId]);
                if ($add) {
                    if ($c1 != $c2) {
                        if($setData['is_transform'] == 0){
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => $c1,
                                'after' => $c2,
                                'change' => $c2 - $c1,
                                'before_hours' => $c1 * $setData['conversion_ratio'],
                                'after_hours' => $c2 * $setData['conversion_ratio'],
                                'change_hours' => ($c2 - $c1) * $setData['conversion_ratio'],
                                'vacation_id' => $vacationId,
                                'when' => 1
                            ];
                        }else{
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => round($c1/($setData['conversion_ratio']/60),4),
                                'after' => round($c2/($setData['conversion_ratio']/60),4),
                                'change' => round(($c2 - $c1)/($setData['conversion_ratio']/60),4),
                                'before_hours' => $c1 * 60,
                                'after_hours' => $c2 * 60,
                                'change_hours' => ($c2 - $c1) * 60,
                                'vacation_id' => $vacationId,
                                'when' => 1
                            ];
                        }
                    }
                    if ($h1 != $h2) {
                        if($setData['is_transform'] == 0){
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => $h1,
                                'after' => $h2,
                                'change' => $h2 - $h1,
                                'before_hours' => $h1 * $setData['conversion_ratio'],
                                'after_hours' => $h2 * $setData['conversion_ratio'],
                                'change_hours' => ($h2 - $h1) * $setData['conversion_ratio'],
                                'vacation_id' => $vacationId,
                                'when' => 2
                            ];
                        }else{
                            $compare[] = [
                                'user_id' => $userId,
                                'before' => round($h1/($setData['conversion_ratio']/60),4),
                                'after' => round($h2/($setData['conversion_ratio']/60),4),
                                'change' => round(($h2 - $h1)/($setData['conversion_ratio']/60),4),
                                'before_hours' => $h1 * 60,
                                'after_hours' => $h2 * 60,
                                'change_hours' => ($h2 - $h1) * 60,
                                'vacation_id' => $vacationId,
                                'when' => 2
                            ];
                        }
                    }
                }
            }
        }
        return $compare;
    }

    /**
     * 获取用户某个假期的假期余额信息
     * @param $vacationId
     * @return mixed
     */
    private function getUserLogVacation($profiles)
    {
        if (!$this->logUserIds || !$this->logVacationIds) {
            return [];
        }
        $userVacation = $this->getLogVacation($this->logUserIds, false, $this->logVacationIds, $profiles);
        return $userVacation;
    }
}
