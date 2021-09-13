<?php


namespace App\Console\Schedules\Eoffice;


use App\Console\Schedules\Schedule;
use App\EofficeApp\Menu\Services\UserMenuService;
use App\EofficeApp\Vehicles\Repositories\VehiclesInsuranceMessageConfigRepository;
use DB;
use Eoffice;
use Schema;
use Illuminate\Support\Facades\Redis;

class VehiclesRemindSchedule implements Schedule
{
    /**
     * @var string 车辆
     */
    private $vehiclesTableName = 'vehicles';

    /**
     * @var string 车辆保险
     */
    private $insuranceTableName = 'vehicles_insurance';

    /**
     * @var string 车辆年检
     */
    private $annualInspectionTableName = 'vehicles_annual_inspection';

    /**
     * 车辆保险/年检提醒, 可以固定时间提醒, 也可以按照提前时间提醒
     * 1. 按照固定时间提醒
     *  1.1 车辆保险会在上月提醒下个月到期的所有车辆, 也会在本月提醒一次本月到期的所有车辆
     *  1.2 车辆年检会在上个月提醒下个月到期的所有车辆
     * 2. 按照提前时间提醒
     *  2. 车辆保险/年检会按照指定的提前时间提醒到期的所有车辆
     *
     */
    public function call($schedule)
    {
        if (!Schema::hasTable('vehicles_insurance_message_config')) {
            return false;
        }

        if (!Schema::hasColumn('vehicles_insurance_message_config', 'type')) {
            return false;
        }
        $schedule->call(function () {
            $interval = 1; //minutes
            //用车维护开始消息提醒
            if ($messages = app('App\EofficeApp\Vehicles\Services\VehiclesService')->vehiclesMaintenanceBeginRemind($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }

            //用车维护结束消息提醒
            if ($messages = app('App\EofficeApp\Vehicles\Services\VehiclesService')->vehiclesMaintenanceEndRemind($interval)) {
                foreach ($messages as $message) {
                    Eoffice::sendMessage($message);
                }
            }
        })->everyMinute();
        /**
         * 处理方法
         *  1. 先从缓存中读取车辆提醒配置
         *  2. 若存在, 则
         *   2.1 分别获取 保险提前提醒/当月提醒/次月提醒 年检提前提醒/次月提醒开关
         *   2.2 若对应开关开启, 分别调用对应的定时任务
         *  3. 若不存在, 则查询数据库, 生成缓存, 再按 2. 中处理
         */
        try {
            $config = $this->getNotifyConfig();
            // 保险提前时间通知
            if (isset($config['insurance_advance_time_notify_switch']) && !empty($config['insurance_advance_time_notify_config'])) {
                $this->insuranceAdvanceTimeNotify($schedule, $config['insurance_advance_time_notify_switch'],$config['insurance_advance_time_notify_config']);
            }
            // 保险当月到期通知
            if (isset($config['insurance_current_month_notify_switch']) && !empty($config['insurance_current_month_notify_config'])) {
                $this->insuranceCurrentMonthNotify($schedule, $config['insurance_current_month_notify_switch'],$config['insurance_current_month_notify_config']);
            }
            // 保险次月到期通知
            if (isset($config['insurance_next_month_notify_switch']) && !empty($config['insurance_next_month_notify_config'])) {
                $this->insuranceNextMonthNotify($schedule, $config['insurance_next_month_notify_switch'],$config['insurance_next_month_notify_config']);
            }
            // 年检提前时间通知
            if (isset($config['inspection_advance_time_notify_switch']) && !empty($config['inspection_advance_time_notify_config'])) {
                $this->inspectionAdvanceTimeNotify($schedule, $config['inspection_advance_time_notify_switch'],$config['inspection_advance_time_notify_config']);
            }
            // 年检次月到期通知
            if (isset($config['inspection_next_month_notify_switch']) && !empty($config['inspection_next_month_notify_config'])) {
                $this->inspectionNextMonthNotify($schedule, $config['inspection_next_month_notify_switch'],$config['inspection_next_month_notify_config']);
            }
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * 车辆保险提前时间通知
     */
    private function insuranceAdvanceTimeNotify($schedule, $switch, $config)
    {
        if ($switch && isset($config['advance_time']) && isset($config['advance_notify_time'])) {
            $schedule->call(function () use ($config)  {
                $this->insuranceNotifyInAdvance($config['advance_time']);
            })->dailyAt($config['advance_notify_time']);
        }
    }

    /**
     * 车辆保险当月到期通知
     */
    private function insuranceCurrentMonthNotify($schedule, $switch, $config)
    {
        if ($switch && isset($config['current_month_date']) && isset($config['current_month_notify_time'])) {
            $schedule->call(function () {
                $this->insuranceScheduleCurrentMonth();
            })->monthlyOn($config['current_month_date'], $config['current_month_notify_time']);
        }
    }
    /**
     * 车辆保险次月到期通知
     */
    private function insuranceNextMonthNotify($schedule, $switch, $config)
    {
        if ($switch && isset($config['next_month_date']) && isset($config['next_month_notify_time'])) {
            $schedule->call(function () {
                $this->insuranceScheduleNextMonth();
            })->monthlyOn($config['next_month_date'], $config['next_month_notify_time']);
        }
    }
    /**
     * 车辆年检提前时间通知
     */
    private function inspectionAdvanceTimeNotify($schedule, $switch, $config)
    {
        if ($switch && isset($config['advance_time']) && isset($config['advance_notify_time'])) {
            $schedule->call(function () use ($config) {
                $this->inspectionNotifyInAdvance($config['advance_time']);
            })->dailyAt($config['advance_notify_time']);
        }
    }
    /**
     * 车辆年检次月到期通知
     */
    private function inspectionNextMonthNotify($schedule, $switch, $config)
    {
        if ($switch && isset($config['next_month_date']) && isset($config['next_month_notify_time'])) {
            $schedule->call(function () {
                $this->annualInspectionScheduleNextMonth();
            })->monthlyOn($config['next_month_date'], $config['next_month_notify_time']);;
        }
    }
    /**
     * 获取车辆提醒相关配置
     */
    private function getNotifyConfig($refreshCache = false)
    {
        $config = Redis::get(VehiclesInsuranceMessageConfigRepository::VEHICLES_INSURANCE_NOTIFY_CONFIG);

        if ($refreshCache || !$config) {
            /** @var VehiclesInsuranceMessageConfigRepository $repository */
            $repository = app('App\EofficeApp\Vehicles\Repositories\VehiclesInsuranceMessageConfigRepository');
            $data = $repository->refreshConfigCache();

            return $data;
        }

        return json_decode($config, true);
    }


    /**
     * 提前指定天数年检通知
     *
     * @param int $advanceTime 提前的天数
     */
    public function inspectionNotifyInAdvance($advanceTime)
    {
        // 若当天通知
        if ($advanceTime < 1) {
            $time = time();
            $end = date('Y-m-d 00:00:00', $time);

            $vehiclesInspection = DB::table($this->annualInspectionTableName)->where('vehicles_annual_inspection_end_time', '>=', $end)
                ->where('vehicles_annual_inspection_end_time', '<=', $end)
                ->whereNull('deleted_at')
                ->pluck('vehicles_id')
                ->toArray();
        }  else { // 若提前通知
            $time = strtotime( '+'.$advanceTime.' days');
            $beginTime = $time - 24 * 3600;
            $begin = date('Y-m-d 00:00:00', $beginTime);
            $end = date('Y-m-d 00:00:00', $time);

            $vehiclesInspection = DB::table($this->annualInspectionTableName)->where('vehicles_annual_inspection_end_time', '>', $begin)
                ->where('vehicles_annual_inspection_end_time', '<=', $end)
                ->whereNull('deleted_at')
                ->pluck('vehicles_id')
                ->toArray();
        }

        if ($vehiclesInspection) {
            $notifyTime = date('Y-m-d', $time);
            $inspectionVehicles = DB::table($this->vehiclesTableName)->whereIn('vehicles_id', $vehiclesInspection)
                ->select('vehicles_id', 'vehicles_code', 'vehicles_name')
                ->whereNull('deleted_at')
                ->get()
                ->toArray();
            $inspectionInfo = [];
            foreach ($inspectionVehicles as $inspectionVehicle) {
                $inspectionInfo[] = $inspectionVehicle->vehicles_name.'('.$inspectionVehicle->vehicles_code.')';
            }

            $vehiclesNames = implode(', ', $inspectionInfo);
            $this->sendInspectionAdvanceMessage($vehiclesNames, $notifyTime);
        }
    }

    /**
     * 提前指定天数保险通知
     *
     * @param int $advanceTime 提前的天数
     */
    public function insuranceNotifyInAdvance($advanceTime)
    {
        // 若当天通知
        if ($advanceTime < 1) {
            $end = date('Y-m-d 00:00:00', time());
            $vehiclesInsurance = DB::table($this->insuranceTableName)->where('vehicles_insurance_end_time', '>=', $end)
                ->where('vehicles_insurance_end_time', '<=', $end)
                ->whereNull('deleted_at')
                ->pluck('vehicles_id')
                ->toArray();
        }  else { // 若提前通知
            $time = strtotime( '+'.$advanceTime.' days');
            $beginTime = $time - 24 * 3600;
            $begin = date('Y-m-d 00:00:00', $beginTime);
            $end = date('Y-m-d 00:00:00', $time);
            $notifyTime = date('Y-m-d', $time);

            $vehiclesInsurance = DB::table($this->insuranceTableName)->where('vehicles_insurance_end_time', '>', $begin)
                ->where('vehicles_insurance_end_time', '<=', $end)
                ->whereNull('deleted_at')
                ->pluck('vehicles_id')
                ->toArray();
        }

        if ($vehiclesInsurance) {
            $insuranceVehicles = DB::table($this->vehiclesTableName)->whereIn('vehicles_id', $vehiclesInsurance)
                ->select('vehicles_id', 'vehicles_code', 'vehicles_name')
                ->whereNull('deleted_at')
                ->get()
                ->toArray();
            $insuranceInfo = [];
            foreach ($insuranceVehicles as $insuranceVehicle) {
                $insuranceInfo[] = $insuranceVehicle->vehicles_name.'('.$insuranceVehicle->vehicles_code.')';
            }

            $vehiclesNames = implode(', ', $insuranceInfo);
            $this->sendInsuranceAdvanceMessage($vehiclesNames, $notifyTime);
        }
    }

    /**
     * 按提前天数发送消息
     *
     * @param string $vehiclesNames
     * @param string $endTime
     */
    private function sendInsuranceAdvanceMessage($vehiclesNames, $endTime)
    {
        $params = ['vehiclesNames' => $vehiclesNames, 'endTime' => $endTime];

        $this->sendVehiclesMessage('car-insureEndAdvance', $params);
    }

    /**
     * 按提前天数发送消息
     *
     * @param string $vehiclesNames
     * @param string $endTime
     */
    private function sendInspectionAdvanceMessage($vehiclesNames, $endTime)
    {
        $params = ['vehiclesNames' => $vehiclesNames, 'endTime' => $endTime];

        $this->sendVehiclesMessage('car-inspectEndAdvance', $params);
    }

    /**
     * 检查当月到期的保险
     */
    public function insuranceScheduleCurrentMonth()
    {
        $begin = $this->getCurrentMonthBegin(); // 月初
        $end = $this->getCurrentMonthEnd(); // 月末
        $now = date('Y-m-d 00:00:00', time());
        // 检查保险
        $res = DB::table($this->insuranceTableName)->where('vehicles_insurance_end_time', '>=', $begin)
            ->where('vehicles_insurance_end_time', '>=', $now)
            ->where('vehicles_insurance_end_time', '<=', $end)
            ->whereNull('deleted_at')
            ->pluck('vehicles_id')
            ->toArray();

        $insurances = array_unique($res);
        if ($insurances) {
            $insuranceVehicles = DB::table($this->vehiclesTableName)->whereIn('vehicles_id', $insurances)
                ->select('vehicles_id', 'vehicles_code', 'vehicles_name')
                ->whereNull('deleted_at')
                ->get()
                ->toArray();
            $insuranceInfo = [];
            foreach ($insuranceVehicles as $insuranceVehicle) {
                $insuranceInfo[] = $insuranceVehicle->vehicles_name.'('.$insuranceVehicle->vehicles_code.')';
            }

            $vehiclesNames = implode(', ', $insuranceInfo);
            $this->sendInsuranceMessage($vehiclesNames);
        }
    }

    /**
     * 检查次月到期的保险
     */
    public function insuranceScheduleNextMonth()
    {
        $begin = $this->getNextMonthBegin(); // 月初
        $end = $this->getNextMonthEnd(); // 月末
        // 检查保险
        $res = DB::table($this->insuranceTableName)->where('vehicles_insurance_end_time', '>=', $begin)
            ->where('vehicles_insurance_end_time', '<=', $end)
            ->whereNull('deleted_at')
            ->pluck('vehicles_id')
            ->toArray();

        $insurances = array_unique($res);
        if ($insurances) {
            $insuranceVehicles = DB::table($this->vehiclesTableName)->whereIn('vehicles_id', $insurances)
                ->select('vehicles_id', 'vehicles_code', 'vehicles_name')
                ->whereNull('deleted_at')
                ->get()
                ->toArray();
            $insuranceInfo = [];
            foreach ($insuranceVehicles as $insuranceVehicle) {
                $insuranceInfo[] = $insuranceVehicle->vehicles_name.'('.$insuranceVehicle->vehicles_code.')';
            }

            $vehiclesNames = implode(', ', $insuranceInfo);
            $this->sendInsuranceNextMessage($vehiclesNames);
        }
    }
    /**
     * 检查次月到期的年检
     */
    public function annualInspectionScheduleNextMonth()
    {
        $begin = $this->getNextMonthBegin(); // 月初
        $end = $this->getNextMonthEnd(); // 月末

        // 检查年检
        $res = DB::table($this->annualInspectionTableName)->where('vehicles_annual_inspection_end_time', '>=', $begin)
            ->where('vehicles_annual_inspection_end_time', '<=', $end)
            ->whereNull('deleted_at')
            ->pluck('vehicles_id')
            ->toArray();

        $inspections = array_unique($res);
        if ($inspections) {
            $insuranceVehicles = DB::table($this->vehiclesTableName)->whereIn('vehicles_id', $inspections)
                ->select('vehicles_id', 'vehicles_code', 'vehicles_name')
                ->whereNull('deleted_at')
                ->get()
                ->toArray();
            $inspectionInfo = [];
            foreach ($insuranceVehicles as $insuranceVehicle) {
                $inspectionInfo[] = $insuranceVehicle->vehicles_name.'('.$insuranceVehicle->vehicles_code.')';
            }

            $inspectVehiclesNames = implode(', ', $inspectionInfo);
            $this->sendAnnualInspectionMessage($inspectVehiclesNames);
        }
    }

    /**
     * 发送当月车辆保险到期消息
     *
     * @param string $vehiclesNames
     */
    private function sendInsuranceMessage($vehiclesNames)
    {
        $params = ['vehiclesNames' => $vehiclesNames];

        $this->sendVehiclesMessage('car-insureEnd', $params);
    }

    /**
     * 发送次月保险到期消息
     *
     * @param string $vehiclesNames
     */
    private function sendInsuranceNextMessage($vehiclesNames)
    {
        $params = ['vehiclesNames' => $vehiclesNames];

        $this->sendVehiclesMessage('car-insureNext', $params);
    }

    /**
     * 发送次月年检到期消息
     *
     * @param string $vehiclesNames
     */
    private function sendAnnualInspectionMessage($vehiclesNames)
    {
        $params = ['vehiclesNames' => $vehiclesNames];

        $this->sendVehiclesMessage('car-inspectEnd', $params);
    }

    /**
     * 发送车辆相关
     *
     * @param string $type
     * @param array  $params
     */
    private function sendVehiclesMessage($type, $params): void
    {
        $sendData['remindMark']     = $type;
        $sendData['toUser']         =  $this->getUsers();
        $sendData['contentParam']   = $params;
        $sendData['stateParams']    = [];
        Eoffice::sendMessage($sendData);
    }

    /**
     * 获取用户
     *
     * @return string
     */
    private function getUsers(): string
    {
        /** @var UserMenuService $service */
        $service = app('App\EofficeApp\Menu\Services\UserMenuService');
        $users = $service->getMenuRoleUserbyMenuId(604);

        return implode(',', $users);
    }

    /**
     * 获取本月开始时间
     *
     * @return string
     */
    private function getCurrentMonthBegin(): string
    {
        return date("Y-m-01 00:00:00", time());
    }
    /**
     * 获取本月结束时间
     *
     * @return string
     */
    private function getCurrentMonthEnd(): string
    {
        return date("Y-m-t 23:59:59", time());
    }
    /**
     * 获取下个月开始时间
     *
     * @return string
     */
    private function getNextMonthBegin(): string
    {
        $nextMonthTime = strtotime('next month');

        return date("Y-m-01 00:00:00", $nextMonthTime);
    }

    /**
     * 获取下个月结束时间
     *
     * @return string
     */
    private function getNextMonthEnd(): string
    {
        $nextMonthTime = strtotime('next month');

        return date("Y-m-t 23:59:59", $nextMonthTime);
    }
}