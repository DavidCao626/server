<?php

namespace App\EofficeApp\Vacation\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

trait VacationTrait
{

    private $apiToken;
    private $precision = 4;

    public function queryCustomUrl($url, $userIds)
    {
        return $this->httpPost($url, ['userId' => $userIds]);
    }


    public function httpPost($url, $data = [])
    {
        $guzzleClient = new Client();
        $response = $guzzleClient->request('POST', $url, [
            'form_params' => $data
        ]);
        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }

    public function getApiToken()
    {
        if (!$this->apiToken) {
            if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']) {
                return explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[1];
            }
            if (isset($_REQUEST['api_token'])) {
                return $_REQUEST['api_token'];
            }
            return false;
        } else {
            return $this->apiToken;
        }
    }

    /**
     * 同步导入数据格式不一样
     * @param $data
     * @return array
     */
    private function handleImportData($data)
    {
        if (isset($data['header']) && isset($data['data'])) {
            $dataSync = [];
            for ($i = 0; $i < count($data['data']); $i++) {
                $row = [];
                for ($j = 0; $j < count($data['header']); $j++) {
                    $row[$data['header'][$j]] = $data['data'][$i][$j];
                }
                $dataSync[] = $row;
            }
            $data = $dataSync;
        }
        return $data;
    }

    /**
     * 从导入的数据中分离出假期id和周期
     */
    public function getVacationIdCycle($importKey)
    {
        $id = false;
        $time = false;
        if (strpos($importKey, 'vacation') !== false) {
            list($time, $lable, $id) = explode('_', $importKey);
        }
        return [intval($id), $time];
    }

    /**
     * 测试自定义url
     */
    public function testCustomUrl($url)
    {
        $apiToken = $this->getApiToken();
        $loginUser = Cache::get($apiToken);
        $userId = $loginUser->user_id;
        try {
            $response = $this->httpPost($url, ['userId' => [$userId], 'api_token' => $apiToken]);
            if (!is_array($response)) {
                return ['code' => ['0x052014', 'vacation']];
            }
            return true;
        } catch (\Exception $exception) {
            return ['code' => ['0x052013', 'vacation']];
        }
    }

    /**
     * 创建假期类型的时候验证数据是否正确
     * @param $vacation
     */
    public function validate($vacation)
    {
        //验证是否存在相同名称的假期
        if (isset($vacation['vacation_id'])) {
            $vacationParams = [
                'search' => [
                    'vacation_name' => [$vacation['vacation_name']],
                    'vacation_id' => [$vacation['vacation_id'], '!=']
                ]
            ];
            $vacationList = app($this->vacationRepository)->getVacationList($vacationParams)->toArray();
            if ($vacationList) {
                return array('code' => array('0x052012', 'vacation'));
            }
        }
        return true;
    }

    /**
     * 生成定时任务时需要
     * @return string
     */
    public function getCrontabToken()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $uuid = substr($charid, 0, 8) . substr($charid, 8, 4) . substr($charid, 12, 4) . substr($charid, 16, 4) . substr($charid, 20, 12);
        $exportToken = md5($uuid . time());
        //定时任务标识
        Cache::add($exportToken, 'crontab', 10);
        return $exportToken;
    }

    /**
     * 解析规则json
     */
    public function parseDaysRuleDetail($data)
    {
        if (isset($data['days_rule_detail'])) {
            if (is_array($data['days_rule_detail'])) {
                $daysRuleDetail = $data['days_rule_detail'];
                //按年升序
                $daysRuleDetail = array_values(
                    collect($daysRuleDetail)
                        ->filter(function ($value) {
                            return !is_null($value['year']) && !is_null($value['days']);
                        })
                        ->sortBy('year')
                        ->toArray()
                );
                $data['days_rule_detail'] = json_encode($daysRuleDetail);
            } else {
                $data['days_rule_detail'] = json_decode($data['days_rule_detail'], true);
            }
        }
        return $data;
    }

    public function parseHoursRuleDetail($data)
    {
        if (isset($data['hours_rule_detail'])) {
            if (is_array($data['hours_rule_detail'])) {
                $daysRuleDetail = $data['hours_rule_detail'];
                //按年升序
                $daysRuleDetail = array_values(
                    collect($daysRuleDetail)
                        ->filter(function ($value) {
                            return !is_null($value['year']) && !is_null($value['hours']);
                        })
                        ->sortBy('year')
                        ->toArray()
                );
                $data['hours_rule_detail'] = json_encode($daysRuleDetail);
            } else {
                $data['hours_rule_detail'] = json_decode($data['hours_rule_detail'], true);
            }
        }
        return $data;
    }

    /**
     * 编辑假期成员
     * @param $data
     * @param $id
     * @return array
     */
    public function setVacationMember($vacationId, $data)
    {
        $data['all_member'] = $this->defaultValue('all_member', $data, 0);
        $data['dept_id'] = $this->arrayToStr($this->defaultValue('dept_id', $data, []));
        $data['role_id'] = $this->arrayToStr($this->defaultValue('role_id', $data, []));
        $data['user_id'] = $this->arrayToStr($this->defaultValue('user_id', $data, []));
        if (app($this->vacationMemberRepository)->getDetailByVacationId($vacationId)) {
            app($this->vacationMemberRepository)->updateData($data, ['vacation_id' => $vacationId]);
        } else {
            $data['vacation_id'] = $vacationId;
            app($this->vacationMemberRepository)->insertData($data);
        }
        Cache::forget($this->getMemberCacheKey($vacationId));
        $this->getVacationMemberUseId($vacationId);
        return true;
    }

    /**
     * 获取假期成员详情
     * @param $id
     * @return array
     */
    public function getVacationMember($vacationId)
    {

        $member = app($this->vacationMemberRepository)->getDetailByVacationId($vacationId);
        if (!$member) {
            return (object)[
                'all_member' => 0,
                'dept_id' => [],
                'user_id' => [],
                'role_id' => []
            ];
        }
        $member->dept_id = $member->dept_id ? explode(',', $member->dept_id) : [];
        $member->user_id = $member->user_id ? explode(',', $member->user_id) : [];
        $member->role_id = $member->role_id ? explode(',', $member->role_id) : [];
        return $member;
    }

    /**
     * 过滤数组中对应的字段值并赋默认值
     *
     * @param string $key
     * @param array $data
     * @param type $default
     *
     * @return int|array|string...
     */
    public function defaultValue($key, $data, $default = '')
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    public function arrayToStr($array, $glue = ',')
    {
        if (is_string($array)) {
            return false;
        }

        if (empty($array)) {
            return '';
        }

        return implode($glue, $array);
    }

    /**
     * 根据不同的假期类别创建不同的规则实例
     * @param $vacationId
     * @param $userIds
     * @return \Laravel\Lumen\Application|mixed|string|null
     */
    private function repository($vacationId, $userIds = [], $profiles = [], $records = false)
    {
        //过滤下不在范围内的用户
        $filterUserIds = $this->getVacationMemberUseId($vacationId);
        if ($filterUserIds !== true) {
            $userIds = array_intersect($userIds, $filterUserIds);
        }
        $vacation = $this->getVacationDetailFromCache($vacationId);
        $types = [0 => 'NoLimit', 1 => 'Year', 2 => 'Month', 3 => 'Once'];
        $typeKey = 0;
        if(isset($vacation) && !empty($vacation)){
            if(isset($vacation->is_limit) && isset($vacation->cycle)){
                if($vacation->is_limit == 1){
                    $typeKey = $vacation->cycle;
                }
            }
        }
        $type = $types[$typeKey];
        $repository = "App\EofficeApp\Vacation\Services\Rule\Vacation{$type}RuleService";
        $instance = app($repository);
        return $instance->init($vacation, $userIds, $profiles, $records);
    }

    private function getVacationDayRecords($vacationIds, $userIds)
    {
        $res = array();
        $vacations = $this->getAllVacations();
        $vacationMap = $this->arrayMapWithKey($vacations, 'vacation_id');
        $typeVacationIds = array();
        $types = [1 => 'Year', 2 => 'Month', 3 => 'Once'];
        foreach ($vacationIds as $vacationId) {
            if (isset($vacationMap[$vacationId])) {
                $vacation = $vacationMap[$vacationId];
                if (!$vacation['is_limit']) {
                    continue;
                }
                $type = $types[$vacation['cycle']];
                $typeVacationIds[$type][] = $vacation['vacation_id'];
            }
        }
        if (!$typeVacationIds) {
            return $res;
        }
        $records = array();
        foreach ($typeVacationIds as $type => $vacationIds) {
            $wheres = array(
                'user_id' => [$userIds, 'in'],
                'vacation_id' => [$vacationIds, 'in'],
            );
            $queryRecords = app("App\EofficeApp\Vacation\Repositories\Vacation{$type}Repository")->getAllByWhere($wheres)->toArray();
            $records = array_merge($records, $queryRecords);
        }
        if (!$records) {
            return $res;
        }
        foreach ($records as $record) {
            $vacationId = $record['vacation_id'];
            $res[$vacationId][] = $record;
        }
        return $res;
    }

    /**
     * 新的一年假期余额是一次性给足还是每隔m个月逐渐增加n天
     * @return int
     */
    private function getPercent($month)
    {
        $percent = 1;
        if ($month) {
            $m = intval(date('m', time()));
            $times = ceil($m / $month);
            $percent = $times / (12 / $month);
        }
        return $percent;
    }

    /**
     * 按数组中的某个元素的值作为键生成新的数组
     * @param $key
     */
    private function arrayMapWithKey($array, $key, $group = false)
    {
        if (!$array) {
            return [];
        }
        $map = array();
        foreach ($array as $item) {
            if ($group) {
                $map[$item[$key]][] = $item;
            } else {
                $map[$item[$key]] = $item;
            }
        }
        return $map;
    }

    /**
     * 假期编辑是否是大的改变，大的改变需要备份数据
     * @param $newVacation
     * @param $oldVacation
     * @return bool
     */
    public function isGreatChange($newVacation, $oldVacation)
    {
        //限制转换成不限制的
        $changeLimit = $oldVacation->is_limit && !$newVacation->is_limit;
        //改变周期：如年度改为月度
        $changeCycle = $oldVacation->cycle != $newVacation->cycle;
        //改变发放方式
        $changeGiveMethod = $oldVacation->give_method != $newVacation->give_method;
        return $changeLimit || $changeCycle || $changeGiveMethod;
    }

    /**
     * 备份假期余额
     */
    public function backupsDays($key)
    {
        $vacations = $this->getAllVacations();
        $vacationIds = array_column($vacations, 'vacation_id');
        $userIds = app($this->userRepository)->getAllUserIdString();
        $userIds = explode(',', $userIds);
        $userVacation = $this->getUserVacation($userIds, false, $vacationIds);
        if (Cache::get($key)) {
            Cache::forget($key);
        }
        return Cache::add($key, $userVacation, 60 * 24 * 7);
    }

    /**
     * 还原假期余额
     */
    public function restoreDays($key)
    {
        $oldUserVacation = Cache::get($key);
        if (!$oldUserVacation) {
            return false;
        }

        $vacations = $this->getAllVacations();
        $vacationIds = array_column($vacations, 'vacation_id');
        $userIds = app($this->userRepository)->getAllUserIdString();
        $userIds = explode(',', $userIds);
        $newUserVacation = $this->getUserVacation($userIds, false, $vacationIds);

        $curData = [];
        $hisData = [];
        if ($newUserVacation) {
            foreach ($newUserVacation as $userId => $vacationDays) {
                if ($vacationDays) {
                    foreach ($vacationDays as $vacationId => $oldDays) {
                        $oldCur = $oldDays['cur'];
                        $oldHis = $oldDays['his'];
                        $newCur = $oldUserVacation[$userId][$vacationId]['cur'] ?? 0;
                        $newHis = $oldUserVacation[$userId][$vacationId]['his'] ?? 0;
                        if ($newCur != $oldCur) {
                            $curData[$vacationId][$userId] = round($newCur - $oldCur, $this->precision);
                        }
                        if ($newHis != $oldHis) {
                            $hisData[$vacationId][$userId] = round($newHis - $oldHis, $this->precision);
                        }
                    }
                }
            }
        }
        if ($curData) {
            foreach ($curData as $vacationId => $data) {
                $this->repository($vacationId, [])->multIncreaseDays($data);
            }
        }
        if ($hisData) {
            foreach ($hisData as $vacationId => $data) {
                $this->repository($vacationId, [])->multIncreaseDays($data, true);
            }
        }
        return true;
    }

    /**
     * 外部调用
     * @param $userId
     * @return mixed
     */
    public function getUserHasVacationIds($userId)
    {
        $user = app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($userId);
        $deptId = $user['dept_id'];
        $roleIds = explode(',', $user['role_id']);
        $vacationIds = app($this->vacationMemberRepository)->getOwnVacationIds($userId, $deptId, $roleIds);
        return $vacationIds;
    }

    private function splitDateTime($startTime, $endTime)
    {
        $res = array();
        $startDate = date('Y-m-d', strtotime($startTime));
        $endDate = date('Y-m-d', strtotime($endTime));
        if ($startDate == $endDate) {
            $secords = strtotime($endTime) - strtotime($startTime);
            $res[$startDate] = [$secords / 3600 / 24, $secords / 3600, 24];
        } else {
            $dates = $this->getDateFromRange($startDate, $endDate);
            foreach ($dates as $date) {
                if ($date == $startDate) {
                    $newStartTime = $startTime;
                    $newEndTime = $date . ' 23:59:59';
                } else if ($date == $endDate) {
                    $newStartTime = $date . ' 00:00:00';
                    $newEndTime = $endTime;
                } else {
                    $newStartTime = $date . ' 00:00:00';
                    $newEndTime = $date . ' 23:59:59';
                }
                $secords = strtotime($newEndTime) - strtotime($newStartTime);
                $res[$date] = [$secords / 3600 / 24, $secords / 3600, 24];
            }
        }
        return $res;
    }

    /**
     * 获取指定日期段内每一天的日期
     * @param  Date $startdate 开始日期
     * @param  Date $enddate 结束日期
     * @return Array
     */
    private function getDateFromRange($startDate, $endDate)
    {
        $stimestamp = strtotime($startDate);
        $etimestamp = strtotime($endDate);
        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;
        // 保存每天日期
        $date = array();
        for ($i = 0; $i < $days; $i++) {
            $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
        }
        return $date;
    }

    /**
     * 向上取整
     * @param $n
     * @param $base 基数，如0.5,
     */
    private function ceil($n, $base)
    {
        return ceil($n / $base) * $base;
    }

    /**
     * 获取某个月的第一天和最后一天
     * @return array
     */
    private function getMonthStartEnd()
    {
        $firstday = date('Y-m-01', time());
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
    }

    /**
     * 往后增加某个时间段
     * @param $startDate 开始时间
     * @param $delay   天数或者月数
     * @param $unit     单位：1天，2月
     */
    private function addDuration($startDate, $delay, $unit)
    {
        if ($unit == 1) {
            $date = date('Y-m-d', strtotime($startDate) + 3600 * 24 * $delay);
        } else {
            $date = Carbon::create($startDate)->addMonthNoOverflow($delay)->format('Y-m-d');
        }
        return $date;
    }
}
