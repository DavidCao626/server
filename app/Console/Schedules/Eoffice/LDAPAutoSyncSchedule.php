<?php


namespace App\Console\Schedules\Eoffice;


use App\Console\Schedules\Schedule;
use App\EofficeApp\System\Domain\Services\DomainService;
use App\EofficeApp\System\Domain\Services\DomainSyncService;
use App\Jobs\SyncDomainJob;
use Queue;
use Illuminate\Support\Facades\Redis;

/**
 * LDAP自动同步定时任务
 *
 * Class LDAPAutoSyncSchedule
 * @package App\Console\Schedules\Eoffice
 */
class LDAPAutoSyncSchedule implements Schedule
{
    public function call($schedule)
    {
        $config = $this->getAutoSyncConfig();
        if (isset($config[DomainSyncService::LDAP_AUTO_SYNC_SWITCH]) && $config[DomainSyncService::LDAP_AUTO_SYNC_SWITCH]) {
            switch ($config[DomainSyncService::LDAP_AUTO_SYNC_PERIOD]) {
                case DomainSyncService::LDAP_AUTO_SYNC_PERIOD_DAY:
                    $schedule->call(function () {
                        $this->dispatch();
                    })->dailyAt($config[DomainSyncService::LDAP_AUTO_SYNC_TIME]);
                    break;
                case DomainSyncService::LDAP_AUTO_SYNC_PERIOD_WEEK:
                    $schedule->call(function () {
                        $this->dispatch();
                    })->weeklyOn($config[DomainSyncService::LDAP_AUTO_SYNC_WEEK_DAY], $config[DomainSyncService::LDAP_AUTO_SYNC_TIME]);
                    break;
                case DomainSyncService::LDAP_AUTO_SYNC_PERIOD_MONTH:
                    $schedule->call(function () {
                        $this->dispatch();
                    })->monthlyOn($config[DomainSyncService::LDAP_AUTO_SYNC_MONTH_DAY], $config[DomainSyncService::LDAP_AUTO_SYNC_TIME]);
                    break;
                    break;
                default:
                    // nothing
            }
        }
    }

    /**
     * 分发定时任务
     */
    private function dispatch(): void
    {
        /** @var DomainService $domainService */
        $domainService = app('App\EofficeApp\System\Domain\Services\DomainService');
        $param = $domainService->getDomainInfo();
        $param = $param->toArray();
        $param['user_id'] = $messageReceiverStr = get_system_param(DomainSyncService::LDAP_AUTO_SYNC_MESSAGE_RECEIVER, '');
        if (!isset($param['password'])) {
            $param['password'] = '';
        }
        $test = $domainService->testDomainConnect($param);

        if(isset($test['code'])){
            return;
        }

        Queue::push(new SyncDomainJob($param));
    }

    /**
     * 获取自动更新相关配置
     */
    private function getAutoSyncConfig($refreshCache = false)
    {
        $config = Redis::get(DomainSyncService::LDAP_AUTO_SYNC_CONFIG);

        if ($refreshCache || !$config) {
            /** @var DomainSyncService $service */
            $service = app('App\EofficeApp\System\Domain\Services\DomainSyncService');
            $data = $service->refreshConfigCache();

            return $data;
        }

        return json_decode($config, true);
    }
}