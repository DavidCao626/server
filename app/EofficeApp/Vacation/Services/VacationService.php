<?php

namespace App\EofficeApp\Vacation\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Vacation\Traits\VacationCacheTrait;
use App\EofficeApp\Vacation\Traits\VacationLogTrait;
use App\EofficeApp\Vacation\Traits\VacationTrait;
use Eoffice;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;
class VacationService extends BaseService
{
    use VacationTrait, VacationCacheTrait, VacationLogTrait;

    private $notifyDay;

    private $expireKey;

    public function __construct(VacationLeaveService $vacationLeaveService)
    {
        parent::__construct();

        $this->vacationRepository = 'App\EofficeApp\Vacation\Repositories\VacationRepository';
        $this->vacationLogRepository = 'App\EofficeApp\Vacation\Repositories\VacationLogRepository';
        $this->vacationScaleLogRepository = 'App\EofficeApp\Vacation\Repositories\VacationScaleLogRepository';
        $this->vacationMemberRepository = 'App\EofficeApp\Vacation\Repositories\VacationMemberRepository';
        $this->vacationDaysUserRepository = 'App\EofficeApp\Vacation\Repositories\VacationDaysUserRepository';
        $this->vacationYearRepository = 'App\EofficeApp\Vacation\Repositories\VacationYearRepository';
        $this->vacationMonthRepository = 'App\EofficeApp\Vacation\Repositories\VacationMonthRepository';
        $this->vacationOnceRepository = 'App\EofficeApp\Vacation\Repositories\VacationOnceRepository';
        $this->vacationExpireRecordRepository = 'App\EofficeApp\Vacation\Repositories\VacationExpireRecordRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->departmentService = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->attendanceService = 'App\EofficeApp\Attendance\Services\AttendanceService';
        $this->attendanceLeaveRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceLeaveRepository';
        $this->attendanceLeaveDiffStatRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceLeaveDiffStatRepository';
        $this->vacationStrategyService = 'App\EofficeApp\Vacation\Services\VacationStrategyService';
        $this->vacationLeaveService = $vacationLeaveService;
        $this->getNotifyDay();
        $this->expireKey = 'vacation_expire_map';// ???????????????????????????key????????????????????????
    }

    private function getNotifyDay() {
        $record = app($this->vacationRepository)->getOneRecord();
        $this->notifyDay = $record->expire_remind_time ?? 10;
    }

    /**
     * ??????????????????
     * @param $vacationData
     * @return mixed
     */
    public function createVacation($data)
    {
        $setData = app($this->vacationRepository)->getVacationSet();
        $data = array_merge($data, [
            'expire_remind_time' => $setData['expire_remind_time'] ?? 10,
            'is_transform' => $setData['is_transform'] ?? 0,
            'conversion_ratio' => $setData['conversion_ratio'] ?? 480,
        ]);
        $data = $this->parseDaysRuleDetail($data);
        if($setData['is_transform'] == 0){
            $data['hours_rule_detail'] = app($this->vacationRepository)->getHours(0,$data);
        }else{
            $data['days_rule_detail'] = app($this->vacationRepository)->getHours(1,$data);
            $data['hours_rule_detail'] = app($this->vacationRepository)->getHours(0,$data);
        }
        if ($data['delay_days']) {
            $data['delay_days'] = round($data['delay_days']);
        }
        $res = app($this->vacationRepository)->insertData($data);
        if (!$res) {
            return ['code' => ['0x052015', 'vacation']];
        }
        $this->delAllVacationsCache();
        $vacationId = $res->vacation_id;
        if (!$this->setVacationMember($vacationId, $data['member'])) {
            return ['code' => ['0x052016', 'vacation']];
        }
        $this->listen($vacationId)->setLogReason($this->reason['create'])->stopLog()->saveLog();
        return $res;
    }

    /**
     * ????????????????????????
     * @param $vacationId
     * @return mixed
     */
    public function getVacationData($vacationId)
    {
        $data = app($this->vacationRepository)->getDetail($vacationId)->toArray();
        $setData = app($this->vacationRepository)->getVacationSet();
        if($setData['is_transform'] == 1){
            $data['days_rule_detail'] = app($this->vacationRepository)->getDays($data);
        }
        $data = $this->parseDaysRuleDetail($data);
        $data['member'] = $this->getVacationMember($vacationId);
        return $data;
    }

    /**
     * ????????????
     * @param $vacationId
     * @param $newVacationData
     * @return array|bool
     */
    public function modifyVacation($vacationId, $data)
    {
        $validate = $this->validate($data);
        if ($validate !== true) {
            return $validate;
        }
        $where = ['vacation_id' => [$vacationId]];
        $data = $this->parseDaysRuleDetail($data);
        $setData = app($this->vacationRepository)->getVacationSet();
        if($setData['is_transform'] == 0){
            $data['hours_rule_detail'] = app($this->vacationRepository)->getHours(0,$data);
        }else{
            $data['days_rule_detail'] = app($this->vacationRepository)->getHours(1,$data);
            $data['hours_rule_detail'] = app($this->vacationRepository)->getHours(0,$data);
        }
        if ($data['delay_days']) {
            $data['delay_days'] = round($data['delay_days']);
        }
        //???????????????????????????
        $oldVacation = app($this->vacationRepository)->getDetail($vacationId);
        $newVacation = (object)$data;
        $backups = ($data['backups'] ?? true) && $this->isGreatChange($newVacation, $oldVacation);
        if ($backups) {
            $key = 'vacation_backups_' . date('Y-m-d H:i:s');
            if (!$this->backupsDays($key)) {
                return ['code' => ['0x052017', 'vacation']];
            }
        }
        $this->listen($vacationId)->setLogReason($this->reason['edit'])->startLog();
        $this->repository($vacationId)->editEvent($newVacation);
        if ($res = app($this->vacationRepository)->updateData($data, $where)) {
            $this->delAllVacationsCache();
            $this->delVacationCacheKey($vacationId);
            $this->setVacationMember($vacationId, $data['member']);
            if ($backups) {
                $this->restoreDays($key);
            }
            $this->stopLog()->saveLog();
            return true;
        }
    }

    /**
     * ??????????????????
     * @param $data
     * @return array|bool
     */
    public function modifySet($data)
    {
        $data = $this->parseDaysRuleDetail($data);
        if ($res = app($this->vacationRepository)->updateData($data,[])) {
            $this->delAllVacationsCache();
            return true;
        }
    }

    /**
     * ????????????????????????
     * @param $data
     * @return array|bool
     */
    public function modifySetScale($data)
    {
        $data = $this->parseDaysRuleDetail($data);
        $data['conversion_ratio'] = round($data['conversion_ratio'] * 60,2);
        if ($data['conversion_ratio'] == 0) {
            return array('code' => array('0x052019', 'vacation'));
        }
        if ($res = app($this->vacationRepository)->updateData($data,[])) {
            if($data['is_transform'] == 0){
                $scaleName = 2;
                $scaleRatio = round($data['conversion_ratio'] / 60,2).':1';
            }else{
                $scaleName = 1;
                $scaleRatio = '1:'.round($data['conversion_ratio'] / 60,2);
            }
            $scaleData = [
                'scale_name' => $scaleName,
                'creator' => $data['creator'] ?? null,
                'scale_ratio' => $scaleRatio
            ];
            $scaleRes = app($this->vacationScaleLogRepository)->insertData($scaleData);
            if (!$scaleRes) {
                return ['code' => ['0x052020', 'vacation']];
            }
            $vacationYears = app($this->vacationYearRepository)->getAllByWhere([])->toArray();
            $vacationMonth = app($this->vacationMonthRepository)->getAllByWhere([])->toArray();
            $vacationExpireRecord = app($this->vacationExpireRecordRepository)->getAllByWhere([])->toArray();
            $vacationOnce = app($this->vacationOnceRepository)->getAllByWhere([])->toArray();
            $vacation = app($this->vacationRepository)->getAllByWhere([])->toArray();
            if(!empty($vacationYears)){
                $updateYears = app($this->vacationYearRepository)->getYearsHours($data['is_transform'], $data['conversion_ratio'], $vacationYears);
                if(!empty($updateYears)){
                    if(!app($this->vacationYearRepository)->batchUpdate($updateYears)){
                        return ['code' => ['0x052020', 'vacation']];
                    }
                }
            }
            if(!empty($vacationMonth)){
                $updateMonth = app($this->vacationYearRepository)->getYearsHours($data['is_transform'], $data['conversion_ratio'], $vacationMonth);
                if(!empty($updateMonth)){
                    if(!app($this->vacationMonthRepository)->batchUpdate($updateMonth)){
                        return ['code' => ['0x052020', 'vacation']];
                    }
                }
            }
            if(!empty($vacationOnce)){
                $updateOnce = app($this->vacationYearRepository)->getYearsHours($data['is_transform'], $data['conversion_ratio'], $vacationOnce);
                if(!empty($updateOnce)){
                    if(!app($this->vacationOnceRepository)->batchUpdate($updateOnce)){
                        return ['code' => ['0x052020', 'vacation']];
                    }
                }
            }
            if(!empty($vacationExpireRecord)){
                $updateExpireRecord = app($this->vacationExpireRecordRepository)->getYearsHours($data['is_transform'], $data['conversion_ratio'], $vacationExpireRecord);
                if(!empty($updateExpireRecord)){
                    if(!app($this->vacationExpireRecordRepository)->batchUpdate($updateExpireRecord)){
                        return ['code' => ['0x052020', 'vacation']];
                    }
                }
            }
            if(!empty($vacation)){
                $update = app($this->vacationRepository)->getYearsHours($data['is_transform'], $data['conversion_ratio'], $vacation);
                if(!empty($update)){
                    if(!app($this->vacationRepository)->batchUpdate($update)){
                        return ['code' => ['0x052020', 'vacation']];
                    }
                }
                foreach ($vacation as $item){
                    $this->delVacationCacheKey($item['vacation_id']);
                }
            }
            $this->delAllVacationsCache();
            return true;
        }
    }

    /**
     * ????????????
     * @param $data
     */
    public function sortVacation($data)
    {
        if (!$data) {
            return true;
        }
        $multipleData = [];
        foreach ($data as $item) {
            $multipleData[] = [
                'where' => ['vacation_id' => $item['vacation_id']],
                'update' => ['sort' => $item['sort']]
            ];
        }
        if (app($this->vacationRepository)->batchUpdate($multipleData)) {
            return $this->delAllVacationsCache();
        }
    }

    /**
     * ??????????????????
     * @param $params
     * @return array
     */
    public function getVacationList($params)
    {
        $params = $this->parseParams($params);

        //????????????????????????????????????
        if (!isset($params['useed']) || $params['useed'] != 'all') {
            $params['search']['enable'] = [1];
        }

        if (isset($params['user_id']) && !empty($params['user_id'])) {
            //?????????
//            $is_priority = 0;
            $userId = $params['user_id'];
            $vacationIds = $this->getUserHasVacationIds($userId);
            $params['search']['vacation_id'] = [$vacationIds, 'in'];
            //???????????????0??????????????????
//            $vacations = $this->getUserVacation([$userId], false, $vacationIds);
//            if(!empty($vacations)){
//                $temp = [];
//                foreach ($vacations[$params['user_id']] as $key => $vacation){
//                    if((isset($vacation['cur']) && ($vacation['cur'] > 0)) || (isset($vacation['his']) && ($vacation['his'] > 0))){
//                        $temp[] = $key;
//                    }
//                }
//                //??????????????????????????????
//                if(!empty($temp)){
//                    $params['search']['vacation_id'] = [$temp, 'in'];
//                    $params['order_by'] = ['vacation_num' => 'desc'];
//                    $vacationsMax = app($this->vacationRepository)->getVacationList($params)->toArray();
//                    if(!empty($vacationsMax)){
//                        $vacationNums = [];
//                        $ids = [];
//                        foreach ($vacationsMax as $vacationMax){
//                            $vacationNums[] = $vacationMax['vacation_num'];
//                            $ids[$vacationMax['vacation_num']][] = $vacationMax['vacation_id'];
//                        }
//                        if(max($vacationNums) != 0 || min($vacationNums) != 0){
//                            //?????????
//                            $is_priority = 1;
//                            $vacationMaxIds = $ids[max($vacationNums)];
//                            $params['search']['vacation_id'] = [$vacationMaxIds, 'in'];
//                        }
//                    }
//                }
//            }
//            if(!$is_priority){
//                $params['search']['vacation_id'] = [$vacationIds, 'in'];
//            }
        }
        return $this->response(app($this->vacationRepository), 'getVacationCount', 'getVacationList', $params);
    }

    /**
     * [deleteVacation ??????????????????]
     *
     * @param  [int]          $vacationId [????????????ID]
     *
     * @return [bool]                     [????????????]
     * @author ??????
     *
     */
    public function deleteVacation($vacationId)
    {
        //??????????????????????????????
        $leaves = app($this->attendanceLeaveRepository)->getOutSendDataLists(['search' => ['vacation_id' => [$vacationId]]]);
        if (count($leaves) > 0) {
            return array('code' => array('0x052001', 'vacation'));
        }
        $this->repository($vacationId)->delEvent();
        if($result = app($this->vacationRepository)->deleteByWhere(['vacation_id' => [$vacationId]])){
            $this->delAllVacationsCache();
            return true;
        }
    }

    /**
     * ????????????????????????
     * @param $params
     * @return array
     */
    public function getUserVacationList($params)
    {
        $params = $this->parseParams($params);
        if (isset($params['search']['dept_id'])) {
            $deptId = $params['search']['dept_id'][0];
            if ($deptId == 0) {
                unset($params['search']['dept_id']);
            } else {
                $deptArray = app($this->departmentService)->getTreeIds($deptId);
                $params['search']['dept_id'] = [$deptArray, 'in'];
            }
        }
        unset($params['order_by']);
        $users = app($this->userRepository)->getUserDeptName($params)->toArray();
        $total = app($this->userRepository)->getUserListTotal($params);
        $userIds = array_column($users, 'user_id');
        $userVacation = $this->getUserVacation($userIds);
        if (!$userVacation) {
            return ['total' => 0, 'list' => []];
        }
        $setData = app($this->vacationRepository)->getVacationSet();
        if($setData['is_transform'] == 0){
            $users = array_map(function ($user) use ($userVacation) {
                $userId = $user['user_id'];
                foreach ($userVacation[$userId] as $vacationId => $daysInfo) {
                    $days = $daysInfo['cur'] + $daysInfo['his'];
                    if ($daysInfo['his']) {
                        $days .= '(' . trans('vacation.0x052006') . $daysInfo['his'] . trans('vacation.0x052007') . ')';
                    }
                    $vacationKey = 'vacation' . $vacationId;
                    $user[$vacationKey] = $days;
                }
                return $user;
            }, $users);
        }else{
            $users = array_map(function ($user) use ($userVacation) {
                $userId = $user['user_id'];
                foreach ($userVacation[$userId] as $vacationId => $daysInfo) {
                    $days = $daysInfo['cur'] + $daysInfo['his'];
                    if ($daysInfo['his']) {
                        $days .= '(' . trans('vacation.0x052006') . $daysInfo['his'] . trans('vacation.0x052021') . ')';
                    }
                    $vacationKey = 'vacation' . $vacationId;
                    $user[$vacationKey] = $days;
                }
                return $user;
            }, $users);
        }
        return ['total' => $total, 'list' => $users];
    }

    /**
     * ????????????????????????
     * @param $userIds
     * @param bool $onlyIsLimit ??????????????????????????????????????????
     * @return array
     */
    public function getUserVacation($userIds, $onlyIsLimit = false, $vacationIds = null, $profiles = [])
    {
        if (!$userIds) {
            return [];
        }
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }
        $vacationIdDaysMap = array();
        if (!$vacationIds) {
            //????????????????????????
            if ($onlyIsLimit) {
                $vacationIds = $this->getEnableLimitVacations('vacation_id');
            } else {
                $vacationIds = $this->getEnableVacations('vacation_id');
            }
        }
        if ($vacationIds) {
            $profiles = empty($profiles) ? app($this->vacationStrategyService)->getProfileInfo($userIds) : $profiles;
            $records = $this->getVacationDayRecords($vacationIds, $userIds);
            foreach ($vacationIds as $vacationId) {
                $vacationIdDaysMap[$vacationId] = $this->repository($vacationId, $userIds, $profiles, $records[$vacationId] ?? [])->getUserDays();
            }
        }
        $userVacation = array();
        foreach ($userIds as $userId) {
            foreach ($vacationIds as $vacationId) {
                $default = ['cur' => 0, 'his' => 0];
                $userVacation[$userId][$vacationId] = $vacationIdDaysMap[$vacationId][$userId] ?? $default;
            }
        }
        return $userVacation;
    }

    public function getLogVacation($userIds, $onlyIsLimit = false, $vacationIds = null, $profiles = [])
    {
        if (!$userIds) {
            return [];
        }
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }
        $vacationIdDaysMap = array();
        if (!$vacationIds) {
            //????????????????????????
            if ($onlyIsLimit) {
                $vacationIds = $this->getEnableLimitVacations('vacation_id');
            } else {
                $vacationIds = $this->getEnableVacations('vacation_id');
            }
        }
        if ($vacationIds) {
            $profiles = empty($profiles) ? app($this->vacationStrategyService)->getProfileInfo($userIds) : $profiles;
            $records = $this->getVacationDayRecords($vacationIds, $userIds);
            foreach ($vacationIds as $vacationId) {
                $vacationIdDaysMap[$vacationId] = $this->repository($vacationId, $userIds, $profiles, $records[$vacationId] ?? [])->getUserDays(1);
            }
        }
        $userVacation = array();
        foreach ($userIds as $userId) {
            foreach ($vacationIds as $vacationId) {
                $default = ['cur' => 0, 'his' => 0];
                $userVacation[$userId][$vacationId] = $vacationIdDaysMap[$vacationId][$userId] ?? $default;
            }
        }
        return $userVacation;
    }

    /**
     * ????????????????????????????????????e????????????????????????????????????
     * @param $userId
     */
    public function getMyVacationData($userId)
    {
        return $this->getUserVacationData($userId);
    }

    /**
     * ?????????????????????????????????????????????
     * @param $userId
     * @return array
     */
    public function getUserVacationData($userId)
    {
        $vacationList = $this->getEnableLimitVacations();
        if($vacationList){
            $userVacation = $this->getUserVacation($userId, true)[$userId];
            $hasVacationIds = $this->getUserHasVacationIds($userId);
            foreach ($vacationList as $key => $value) {
                $vacationId = $value['vacation_id'];
                $vacationList[$key]['has'] = in_array($vacationId, $hasVacationIds);
                $vacationList[$key] = array_merge($vacationList[$key], $userVacation[$vacationId]);
                $vacationList[$key]['date_range'] = $this->repository($vacationId)->getCycleDateRange($userId);
            }
        }
        return $vacationList;
    }

    /**
     * @param $userId
     * ????????????????????????
     */
    public function getMineVacationData($userId, $params)
    {
        $hasVacationIds = $this->getUserHasVacationIds($userId);
        $params = $this->parseParams($params);
        $params['search']['vacation_id'] = [$hasVacationIds, 'in'];
        $params['search']['enable'] = [1];

        $vacationList = app($this->vacationRepository)->getVacationList($params)->toArray();
        if($vacationList){
            $userVacation = $this->getUserVacation($userId, false, array_column($vacationList, 'vacation_id'))[$userId];
            foreach ($vacationList as $key => $value) {
                $vacationId = $value['vacation_id'];
                $vacationList[$key] = array_merge($vacationList[$key], $userVacation[$vacationId]);
            }
        }
        $total = app($this->vacationRepository)->getVacationCount($params);
        return ['list' => $vacationList, 'total' => $total];
    }

    public function getVacationSetData()
    {
        $setData = app($this->vacationRepository)->getVacationSet();
        return ['setData' => $setData];
    }

    public function getVacationHistoryData($params)
    {
        $vacationList = app($this->vacationScaleLogRepository)->getScaleLogList($params)->toArray();
        $list = [];
        if(!empty($vacationList)){
            foreach ($vacationList as $item){
                $data = [
                    'creator' => app($this->userRepository)->getUserName($item['creator']),
                    'scale_name' => empty($item['scale_name']) ? '' : $item['scale_name'],
                    'scale_ratio' => empty($item['scale_ratio']) ? '' : $item['scale_ratio'],
                    'created_at' => empty($item['created_at']) ? '' : $item['created_at'],
                ];
                array_push($list,$data);
            }
        }
        $total = app($this->vacationScaleLogRepository)->getScaleLogCount($params);
        return ['list' => $list, 'total' => $total];
    }

    /**
     * ????????????????????????????????????
     * @param $userId
     * @param $params
     */
    public function getMineVacationUsedRecord($userId, $params)
    {
        $params = $this->parseParams($params);
        $params['search']['user_id'] = [$userId];
        $logs = app($this->vacationLogRepository)->getLogList($params)->toArray();
        $vacationMap = $this->arrayMapWithKey(app($this->vacationRepository)->getAllVacation(1)->toArray(), 'vacation_id');
        $setData = app($this->vacationRepository)->getVacationSet();
        $logs = array_map(function ($log) use ($vacationMap) {
            $log['vacation_name'] = $vacationMap[$log['vacation_id']]['vacation_name'] ?? '['.trans('vacation.delete').']';
            if(isset($vacationMap[$log['vacation_id']]) && isset($vacationMap[$log['vacation_id']]['deleted_at'])){
                $log['vacation_name'] = $vacationMap[$log['vacation_id']]['vacation_name'].'['.trans('vacation.delete').']' ?? '['.trans('vacation.delete').']';
            }else{
                $log['vacation_name'] = $vacationMap[$log['vacation_id']]['vacation_name'] ?? '['.trans('vacation.delete').']';
            }
            $log['is_transform'] = $vacationMap[$log['vacation_id']]['is_transform'] ?? 0;
            $log['user_name'] = app($this->userRepository)->getUserName($log['user_id']) ?? '';
            return $log;
        }, $logs);
        if(!empty($logs)){
            foreach ($logs as $key=>$log){
                if($log['vacation_name']){
                    if($setData['is_transform'] == 1){
                        if($logs[$key]['before_hours'] == 0){
                            $logs[$key]['before'] = round($logs[$key]['before'] * ($setData['conversion_ratio']/60),2);
                        }else{
                            $logs[$key]['before'] = round($logs[$key]['before_hours']/60,2);
                        }
                        if($logs[$key]['after_hours'] == 0){
                            $logs[$key]['after'] = round($logs[$key]['after'] * ($setData['conversion_ratio']/60),2);
                        }else{
                            $logs[$key]['after'] = round($logs[$key]['after_hours']/60,2);
                        }
                        $logs[$key]['change'] = round(($logs[$key]['after'] - $logs[$key]['before']),2);
                    }
                }else{
                    unset($logs[$key]);
                    continue;
                }
            }
        }
        return array_values($logs);
    }

    /**
     * ?????????????????????????????????
     * @param $userId
     * @param $data
     * @return bool
     */
    public function modifyUserVacation($userId, $data)
    {
        $update = array();
//        $setData = app($this->vacationRepository)->getVacationSet();
//        if($setData['is_transform'] == 0){
//            foreach ($data as $item) {
//                $update[] = [
//                    'user_id' => $userId,
//                    'vacation_id' => $item['vacation_id'],
//                    'cur' => $item['cur'],
//                ];
//            }
//        }else{
//            foreach ($data as $item) {
//                $update[] = [
//                    'user_id' => $userId,
//                    'vacation_id' => $item['vacation_id'],
//                    'cur' => round($item['cur']/($setData['conversion_ratio']/60),4),
//                ];
//            }
//        }
        foreach ($data as $item) {
            $update[] = [
                'user_id' => $userId,
                'vacation_id' => $item['vacation_id'],
                'cur' => $item['cur'],
            ];
        }
        $this->listen(array_column($data, 'vacation_id'), $userId)->setLogReason($this->reason['set'])->startLog();
        $res = $this->updateUserVacation($update);
        $this->stopLog()->saveLog();
        return $res;
    }

    /**
     * ?????????????????????????????????
     */
    private function updateUserVacation($data)
    {
        if (!$data) {
            return true;
        }
        $userIds = array_column($data, 'user_id');
        $vacationIds = array_column($data, 'vacation_id');
        $userVacation = $this->getUserVacation($userIds, $vacationIds);
        $increaseDays = array();
        //??????????????????
        $userIdsHasVacation = array();
        foreach ($vacationIds as $vacationId) {
            $userIdsHasVacation[$vacationId] = $this->getVacationMemberUseId($vacationId);
        }
//        $setData = app($this->vacationRepository)->getVacationSet();
//        if($setData['is_transform'] == 0){
//            foreach ($data as $item) {
//                $vacationId = $item['vacation_id'];
//                $userId = $item['user_id'];
//                if ($userIdsHasVacation[$vacationId] !== true &&
//                    !in_array($userId, $userIdsHasVacation[$vacationId])) {
//                    continue;
//                }
//                $new = round($item['cur'], $this->precision);
//                $old = $userVacation[$userId][$vacationId]['cur'] ?? 0;
//                if ($new != $old) {
//                    $increaseDays[$vacationId][$userId] = $new - $old;
//                }
//            }
//        }else{
//            foreach ($data as $item) {
//                $vacationId = $item['vacation_id'];
//                $userId = $item['user_id'];
//                if ($userIdsHasVacation[$vacationId] !== true &&
//                    !in_array($userId, $userIdsHasVacation[$vacationId])) {
//                    continue;
//                }
//                $new = round($item['cur'], $this->precision);
//                $old = round($userVacation[$userId][$vacationId]['cur']/($setData['conversion_ratio']/60),$this->precision) ?? 0;
//                if ($new != $old) {
//                    $increaseDays[$vacationId][$userId] = $new - $old;
//                }
//            }
//        }
        foreach ($data as $item) {
            $vacationId = $item['vacation_id'];
            $userId = $item['user_id'];
            if ($userIdsHasVacation[$vacationId] !== true &&
                !in_array($userId, $userIdsHasVacation[$vacationId])) {
                continue;
            }
            $new = round($item['cur'], $this->precision);
            $old = $userVacation[$userId][$vacationId]['cur'] ?? 0;
            if ($new != $old) {
                $increaseDays[$vacationId][$userId] = $new - $old;
            }
        }
        if ($increaseDays) {
            foreach ($increaseDays as $vacationId => $data) {
                $this->repository($vacationId, [])->multIncreaseDays($data,false);
            }
        }
        return true;
    }


    /**
     * ??????????????????
     * @param $data
     * @return array
     */
    public function leaveDataout($data)
    {
        if (!(isset($data['user_id']) && $data['user_id'])) {
            return ['code' => ['0x052011', 'vacation']];
        }
        if (!(isset($data['vacation_type']) && $data['vacation_type'])) {
            return ['code' => ['0x052002', 'vacation']];
        }
//        $days = (isset($data['days']) && $data['days']) ? $data['days'] : 0;
        $setData = app($this->vacationRepository)->getVacationSet();
        if($setData['is_transform'] == 0){
            $days = (isset($data['days']) && $data['days']) ? $data['days'] : 0;
        }else{
            $days = (isset($data['hours']) && $data['hours']) ? $data['hours'] : ((isset($data['days']) && $data['days']) ? $data['days'] : 0);
        }
        $userId = $data['user_id'];
        $vacationId = $data['vacation_type'];
        $this->listen($vacationId, $userId)->setLogReason($this->reason['leave'])->startLog();
        if($setData['is_transform'] == 0){
            $res = $this->repository($vacationId, [$userId])->reduceDays('outsend_days', $days);
        }else{
            $res = $this->repository($vacationId, [$userId])->reduceDays('outsend_hours', $days);
        }
        $this->stopLog()->saveLog();
        return $res;
    }

    /**
     * ??????????????????
     * @param $data
     * @return array
     */
    public function overtimeDataout($data)
    {
        if (!(isset($data['user_id']) && $data['user_id'])) {
            return ['code' => ['0x052011', 'vacation']];
        }
        if (isset($data['vacation_id'])) {
            $vacationId = $data['vacation_id'];
        } else {
            if (!$vacationId = app($this->vacationRepository)->getVacationId('?????????')) {
                return ['code' => ['0x052002', 'vacation']];
            }
        }
        $setData = app($this->vacationRepository)->getVacationSet();
        if($setData['is_transform'] == 0){
            $days = (isset($data['days']) && $data['days']) ? $data['days'] : 0;
        }else{
            $days = (isset($data['hours']) && $data['hours']) ? $data['hours'] : ((isset($data['days']) && $data['days']) ? $data['days'] : 0);
        }
        $userIds = $data['user_id'];
        if (!is_array($userIds)) {
            $userIds = explode(',', rtrim($userIds, ','));
        }
        $this->listen($vacationId, $userIds)->setLogReason($this->reason['overtime'])->startLog();
        if($setData['is_transform'] == 0){
            $res = $this->repository($vacationId, $userIds)->increaseDays('outsend_days', $days);
        }else{
            $res = $this->repository($vacationId, $userIds)->increaseDays('outsend_hours', $days);
        }
        $this->stopLog()->saveLog();
        return $res;
    }

    /**
     * ?????????????????????????????????
     * @param $userId
     * @param $vacationId
     * @return bool
     */
    public function deleteUserLastVacation($userId, $vacationId)
    {
        $userIds = is_array($userId) ? $userId : [$userId];
        $vacationIds = is_array($vacationId) ? $vacationId : [$vacationId];
        $this->listen($vacationIds, $userIds)->setLogReason($this->reason['clear'])->startLog();
        foreach ($vacationIds as $vacationId) {
            $this->repository($vacationId, $userIds)->delHistoryDays();
        }
        $this->stopLog()->saveLog();
        return true;
    }

    /**
     * ??????????????????????????????
     * @param $data
     * @return array|bool
     */
    public function multiSetUserVacation($data)
    {
        $vacation = app($this->vacationRepository)->getDetail($data['vacationId']);
        if (!$vacation) {
            return array('code' => array('0x052002', 'vacation'));
        }
        if ($vacation->enable != 1) {
            return array('code' => array('0x052003', 'vacation'));
        }
        if ($vacation->is_limit != 1) {
            return array('code' => array('0x052004', 'vacation'));
        }
        if ($vacation->is_delay != 1 && $data['time'] == 'his') {
            return array('code' => array('0x052005', 'vacation'));
        }
        $userIds = $this->getMultSetUserIds($data);
        $this->listen($vacation->vacation_id, $userIds)->setLogReason($this->reason['mult_set'])->startLog();
        switch ($data['time']) {
            case 'cur':
                $update = array();
                $days = $data['days'];
                $vacationId = $vacation->vacation_id;
                foreach ($userIds as $userId) {
                    $update[] = [
                        'user_id' => $userId,
                        'vacation_id' => $vacationId,
                        'cur' => $days
                    ];
                }
                $this->updateUserVacation($update);
                break;
            case 'his':
                $this->repository($vacation->vacation_id, $userIds)->delHistoryDays();
                break;
            default:
        }
        $this->stopLog()->saveLog();
        return true;
    }

    /**
     * ???????????????????????????id
     * @param $data
     * @return array
     */
    private function getMultSetUserIds($data)
    {
        if ($data['setUserType'] == 1) {
            $params = [
                'returntype' => 'array'
            ];
            $userIdArray = array_column(app($this->userRepository)->getUserList($params), 'user_id');
        } else {
            $userIdArray = $data['users'];
            if ($data['depts'] != []) {
                $params = [
                    'search' => [
                        'dept_id' => [$data['depts'], 'in']
                    ],
                    'returntype' => 'array'
                ];
                $deptUserIdArray = array_column(app($this->userRepository)->getUserList($params), 'user_id');
                $userIdArray = array_merge($userIdArray, $deptUserIdArray);
            }
            if ($data['roles'] != []) {
                $params = [
                    'search' => [
                        'role_id' => [$data['roles'], 'in']
                    ],
                    'returntype' => 'array'
                ];
                $roleUserIdArray = array_column(app($this->userRepository)->getUserList($params), 'user_id');
                $userIdArray = array_merge($userIdArray, $roleUserIdArray);
            }
            $userIdArray = array_unique($userIdArray);
        }
        return $userIdArray;
    }

    /**
     *  ????????????????????????
     * @param $params
     * @return array
     */
    public function userVacationExport($params)
    {
        $header = [
            'user_name' => '????????????',
            'user_has_one_system_info.user_system_info_belongs_to_department.dept_name' => '????????????'
        ];
        $vacationList = app($this->vacationRepository)->getVacationList([
            'search' => [
                'enable' => [1],
                'is_limit' => [1]
            ]
        ]);
        if ($vacationList) {
            foreach ($vacationList as $value) {
                $header['vacation' . $value->vacation_id] = $value->vacation_name;
            }
        }
        $params['search'] = json_encode($params['search']);
        $userVacationList = $this->getUserVacationList($params)['list'];
        $data = [];
        foreach ($userVacationList as $value) {
            $data[] = Arr::dot($value);
        }
        return compact('header', 'data');
    }

    /**
     * ????????????????????????
     * @return array
     */
    public function getUserVacationFields()
    {
        $header = ['user_accounts' => trans('vacation.0x052009')];
        $params = [
            'search' => [
                'enable' => [1],
                'is_limit' => [1]
            ]
        ];
        $vacationList = app($this->vacationRepository)->getVacationList($params);
        foreach ($vacationList as $value) {
            $header['cur_vacation_' . $value->vacation_id] = $value->vacation_name;
            if (in_array($value->cycle, [1, 2]) && $value->is_delay == 1) {
                $header['his_vacation_' . $value->vacation_id] = $value->vacation_name . '(' . trans('vacation.0x052008') . ')';
            }
        }
        return [
            'header' => $header
        ];
    }

    /**
     * ??????????????????
     * @param $data
     * @param $params
     * @return array|string
     */
    public function importUserVacation($data, $params)
    {
        if (empty($data)) {
            return [];
        }
        //?????????????????????????????????????????????????????????apiToken???????????????????????????
        //$this->apiToken = $params['token'] ?? false;
        $userAccounts = array_unique(array_column($data, 'user_accounts'));
        //????????????
        $userParam = [
            'returntype' => 'object',
            'fields' => ['user_id', 'user_accounts'],
            'search' => ['user_accounts' => [$userAccounts, 'in']]
        ];
        $userInfo = app($this->userRepository)->getUserList($userParam);
        $vacationUser = $userInfo->mapWithKeys(function ($item) {
            return [$item['user_accounts'] => $item['user_id']];
        });
        $type = $params['type'];
        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];
        $curData = [];
        $hisData = [];
        $userVacation = $this->getUserVacation(array_values($vacationUser->all()));
//        $vacationSet = app($this->vacationRepository)->getVacationSet();
//        if($vacationSet['is_transform'] == 0){
//            $num = 1;
//        }else{
//            $num = $vacationSet['conversion_ratio']/60;
//        }
        foreach ($data as $key => $value) {
            $value['user_accounts'] = is_float($value['user_accounts']) ? (string)$value['user_accounts'] : $value['user_accounts'];
            if (!isset($vacationUser[$value['user_accounts']])) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("vacation.0x052011"));
                $info['error']++;
                continue;
            }
            $userId = $vacationUser[$value['user_accounts']];
            if ($value['user_accounts'] == '') {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("vacation.0x052010"));
                $info['error']++;
                continue;
            } else if (!isset($vacationUser[$value['user_accounts']])) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("vacation.0x052011"));
                $info['error']++;
                continue;
            }
            foreach ($value as $header => $importValue) {
                if (!$importValue && is_string($importValue)) {
                    continue;
                }
                list($vacationId, $time) = $this->getVacationIdCycle($header);
                if (!$vacationId) {
                    continue;
                }
                $days = floatval($importValue);
                $cur = $userVacation[$userId][$vacationId]['cur'];
                $his = $userVacation[$userId][$vacationId]['his'];
                switch ($time) {
                    case 'cur':
                        //?????????????????????????????????????????????????????????????????????
                        if ((($type == 1 && !$cur) || ($type == 2 && $cur) || $type == 4)) {
                            if ($cur != $days) {
                                $curData[$vacationId][$userId] = round($days - $cur, $this->precision);
                            }
                        }
                        break;
                    case 'his':
                        //?????????????????????????????????????????????????????????????????????
                        if ((($type == 1 && !$his) || ($type == 2 && $his) || $type == 4)) {
                            if ($his != $days) {
                                $hisData[$vacationId][$userId] = round($days - $his, $this->precision);
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
            $info['success']++;
            $data[$key]['importResult'] = importDataSuccess();
        }
        //??????????????????????????????
        $vacationIds = array_unique(array_merge(array_keys($curData), array_keys($hisData)));
        $userIds = array_merge(array_values($curData), array_values($hisData));
        $userIds = array_map(function ($row) {
            return array_key_first($row);
        }, $userIds);
        $userIds = array_unique($userIds);
        $this->listen($vacationIds, $userIds)->setLogReason($this->reason['import'])->startLog();
        if ($curData) {
            foreach ($curData as $vacationId => $data1) {
                $this->repository($vacationId, [])->multIncreaseDays($data1);
            }
        }
        if ($hisData) {
            foreach ($hisData as $vacationId => $data2) {
                $this->repository($vacationId, [])->multIncreaseDays($data2, true);
            }
        }
        $this->stopLog()->saveLog();

        return ['data' => $data, 'info' => $info];
    }


    /**
     * ????????????????????????????????????,?????????
     * @param $userId
     * @param $vacationId
     * @return mixed
     */
    public function getUserVacationDays($userId, $vacationId)
    {
        $vacationData = app($this->vacationRepository)->getDetail($vacationId);
        $daysInfo = $this->getUserVacation($userId, false, [$vacationId])[$userId][$vacationId];
        $outSendDays = $this->repository($vacationId, [$userId])->getCurCycleOutSendDays();
        $vacationData->thisDays = $daysInfo['cur'];
        $vacationData->lastDays = $daysInfo['his'];
        $vacationData->totalDays = round($daysInfo['cur'] + $daysInfo['his'], $this->precision);
        $vacationSet = app($this->vacationRepository)->getVacationSet();

        //1.???????????????????????????????????????????????????
        $totalLeaveDays = $this->vacationLeaveService->getAllLeaveDays([$userId], [$vacationId])[$userId][$vacationId] ?? 0;
        $vacationData->historyLeaveDays = $totalLeaveDays;
        //2.???????????????????????????????????????
        if (in_array($vacationData->cycle, [1, 2])) {
            if ($vacationData->cycle == 1) {
                $startDate = date('Y', time()) . '-01-01';
                $endDate = date('Y', time()) . '-12-31';
            } else {
                $startDate = date('Y-m-1');
                $endDate = date('Y-m-d', strtotime(date('Y-m-1', strtotime('next month')) . '-1 day'));
            }
            //?????????????????????????????????
            if($vacationSet['is_transform'] == 0){
                $leaveDays = app($this->attendanceLeaveDiffStatRepository)->getMoreUserAttendLeaveStatByDate($startDate, $endDate, [$userId]);
            }else{
                $leaveDays = app($this->attendanceLeaveDiffStatRepository)->getMoreUserAttendLeaveHoursStatByDate($startDate, $endDate, [$userId]);
            }
            if (isset($leaveDays[$userId][$vacationId])) {
                $leaveDays = $leaveDays[$userId][$vacationId];
            } else {
                $leaveDays = 0;
            }
            $vacationData->leaveDays = $leaveDays;
        } else {
            $vacationData->leaveDays = $totalLeaveDays;
        }
        //3.????????????????????????????????????
        $vacationData->cycleTotalDays = round($daysInfo['cur'] - $outSendDays, $this->precision);
        return $vacationData;
    }

    /**
     * ??????????????????????????????????????????????????????
     * @param  [string]
     * @return [array]
     */
    public function getUserVacationDaysByName($userId, $vacationName)
    {
        $vacationName = urldecode($vacationName);
        if (empty($vacationName)) return [];
        $vacationId = app($this->vacationRepository)->getVacationId($vacationName);
        if (empty($vacationId)) return [];
        return $this->getUserVacationDays($userId, $vacationId);
    }

    /**
     * ???????????????????????????????????????
     */
    public function crontab()
    {
        set_time_limit(600);
        ini_set('memory_limit', '-1');
        $vacations = $this->getAllVacations();
        $userIds = app($this->userRepository)->getAllUserIdString();
        $userIds = explode(',', $userIds);
        $profiles = app($this->vacationStrategyService)->getProfileInfo($userIds);
        $this->listen(array_column($vacations, 'vacation_id'), $userIds)->setLogReason($this->reason['system'])->startLog();
        foreach ($vacations as $vacation) {
            $vacationId = $vacation['vacation_id'];
            $this->repository($vacationId, $userIds, $profiles)->schedule();
        }
        $this->stopLog()->saveLog();
    }

    /**
     * ??????????????????????????????
     */
    public function onAddUser($userId)
    {
        $vacations = $this->getAllVacations();
        $vacations = collect($vacations)->filter(function ($vacation) {
            return $vacation['cycle'] == 3 && $vacation['once_auto_days'] > 0;
        })->toArray();
        if (!$vacations) {
            return true;
        }
        $setData = app($this->vacationRepository)->getVacationSet();
        $this->listen(array_column($vacations, 'vacation_id'), $userId)->setLogReason($this->reason['join'])->startLog();
        foreach ($vacations as $vacation) {
            if ($vacation['cycle'] == 3 && $vacation['once_auto_days'] > 0) {
                $days = $vacation['once_auto_days'];
                $vacationId = $vacation['vacation_id'];
                if($setData['is_transform'] == 0){
                    $this->repository($vacationId, [$userId])->increaseDays('days', $days);
                }else{
                    $this->repository($vacationId, [$userId])->increaseDays('hours', $days);
                }
            }
        }
        $this->stopLog()->saveLog();
    }

    /**
     * ???????????????????????????????????????????????????
     */
    public function onProfileChange($userId, $profiles) {
        $vacations = $this->getAllVacations();
        $vacations = collect($vacations)->filter(function ($vacation) {
            return $vacation['cycle'] == 1 && $vacation['give_method'] != 1;
        })->toArray();
        if (!$vacations) {
            return true;
        }
        $oldProfiles = isset($profiles['old']) && !empty($profiles['old']) ? [$userId => $profiles['old']] : app($this->vacationStrategyService)->getProfileInfo([$userId]);
        $newProfiles = isset($profiles['new']) && !empty($profiles['new']) ? [$userId => $profiles['new']] : app($this->vacationStrategyService)->getProfileInfo([$userId]);
        $this->listen(array_column($vacations, 'vacation_id'), [$userId])->setLogReason($this->reason['system'])->startLog($oldProfiles);
        // ?????????????????????
        $update = [];
        foreach ($vacations as $vacation) {
            $data = $this->repository($vacation['vacation_id'], [$userId], $newProfiles)->getUserDays();
            foreach ($data as $item) {
                $update[] = [
                    'user_id' => $userId,
                    'vacation_id' => $vacation['vacation_id'],
                    'cur' => $item['cur'],
                ];
            }
        }
        $res = $this->updateUserVacation($update);
        $this->stopLog($newProfiles)->saveLog();
        return true;
    }

    /**
     * ??????????????????
     */
    public function sendExpireNotify()
    {
        $userIds = app($this->userRepository)->getAllUserIdString();
        $userIds = explode(',', $userIds);
        $data = $this->getToOutOfDate($this->notifyDay, $userIds);
        if (!$data) {
            Redis::del($this->expireKey);
            return;
        }
        $setData = app($this->vacationRepository)->getVacationSet();
        $isTransform = $setData['is_transform'] ?? 0;
        foreach ($data as $item) {
            // ??????id????????????????????????id?????????key??????????????????
            $userKey = $item['user_id'].'_'.strtotime($item['date']).'_'.$item['vacation_id'];
            $hasRemind = Redis::hget($this->expireKey, $userKey);
            // ???????????????????????????
            if ($hasRemind) {
                continue;
            }
            $message['remindMark'] = 'vacation-expire';
            $message['toUser'] = $item['user_id'];
            $message['contentParam'] = [
                'vacationDay' => $item['day'].($isTransform ? trans('vacation.0x052021') : trans('vacation.0x052007')),
                'vacationName' => $item['vacation_name'],
                'vacationExpireDate' => $item['date']
            ];
            Eoffice::sendMessage($message);

            Redis::hset($this->expireKey, $userKey, 1);
        }
    }

    public function getExpireRecords($userId)
    {
        return $this->getToOutOfDate($this->notifyDay, [$userId]);
    }

    /**
     * ???????????????????????????
     * @param $day ????????????????????????
     */
    private function getToOutOfDate($day, $userIds)
    {
        $data = array();
        $deadline = date('Y-m-d', strtotime("+$day day"));//???????????????????????????????????????????????????????????????????????????
        $vacations = $this->getAllVacations();
        $profiles = app($this->vacationStrategyService)->getProfileInfo($userIds);
        foreach ($vacations as $vacation) {
            $res = $this->repository($vacation['vacation_id'], $userIds, $profiles)->getBeforeDeadline($deadline, $this->notifyDay);
            $res = $res ? $res : [];
            $data = array_merge($data, $res);
        }
        $data = collect($data)->sortBy("user_id")
            ->sortBy("date")
            ->filter(function ($item) {
                return $item['day'] > 0 && $item['date'] >= date('Y-m-d');
            })
            ->toArray();
        return array_values($data);
    }

    /**
     * ?????????????????????????????????????????????????????????????????????????????????????????????
     */
    public function parseLeaveDays($leaveDays, $startTime, $endTime, $vacationId)
    {
        $vacation = app($this->vacationRepository)->getDetail($vacationId);
        if(!isset($vacation)){
            return $leaveDays;
        }
        //???????????????????????????24?????????
        if (isset($vacation) && $vacation->is_natural_day) {
            $leaveDays = $this->splitDateTime($startTime, $endTime);
        }
        if (!$leaveDays) {
            return $leaveDays;
        }
        //????????????????????????
        $leaveDays = array_map(function ($data) use ($vacation) {
            if ($data[0] == 0 && $data[1] == 0) {
                return $data;
            }
            list($day, $hour, $ratio) = $data;
            switch ($vacation->min_leave_unit) {
                //????????????
                case 5:
                    //?????????????????????????????????????????????????????????????????????????????????????????????????????????0??????1??????
                    $day = $this->ceil($hour / $ratio, 1);
                    break;
                //???????????????
                case 4:
                    $day = $this->ceil($hour / $ratio, 0.5);
                    break;
                //????????????
                case 3:
                    $day = $this->ceil($hour, 1) / $ratio;
                    break;
                //???????????????
                case 2:
                    $day = $this->ceil($hour, 0.5) / $ratio;
                    break;
                //????????????
                default:
                    //nothing to do
            }
            //???????????????????????????????????????????????????
            if ($vacation->min_leave_unit != 1) {
                $hour = $day * $ratio;
            }
//            $day = $day > 1 ? 1 : $day;
            return [round($day, 3), round($hour, 2)];
        }, $leaveDays);
        return $leaveDays;
    }
}