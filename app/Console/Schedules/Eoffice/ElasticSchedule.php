<?php


namespace App\Console\Schedules\Eoffice;


use App\Console\Schedules\Schedule;
use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository;
use App\Jobs\Elasticsearch\ElasticsearchJob;
use DB;
use Illuminate\Console\Scheduling\Event;
use Schema;
use Illuminate\Support\Facades\Redis;


/**
 * Class ElasticSchedule
 * @package App\Console\Schedules\Eoffice
 *
 * 全文搜索引擎定时更新
 */
class ElasticSchedule implements Schedule
{
    /**
     * @var ElasticSearchConfigRepository $repository
     */
    public $repository;

    public function __construct()
    {
        /** @var ElasticSearchConfigRepository $repository */
        $this->repository = app('App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository');
    }

    /**
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     */
    public function call($schedule)
    {
        if (!Schema::hasTable(ElasticTables::ELASTIC_CONFIG_TABLE)) {
            return;
        }

        $config = $this->getElasticScheduleConfig();

        if ($config && $config[ConfigOptions::UPDATE_BY_SCHEDULE]) {
            $period = (int)$config[ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE][ConfigOptions::SCHEDULE_UPDATE_PERIOD];
            $updateDailyTime = $config[ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE][ConfigOptions::SCHEDULE_UPDATE_DAY_TIME];
            $updateWeeklyTime = $config[ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_SCHEDULE][ConfigOptions::SCHEDULE_UPDATE_WEEK_TIME];

            if ($period === ConfigOptions::SCHEDULE_UPDATE_PERIOD_DAY) {
                /**
                 * 防止定时任务阻塞
                 *  1. runInBackground() @see Event::runInBackground() windows下暂时无法运行
                 *  2. $schedule->job()  @see \Illuminate\Console\Scheduling\Schedule::job()  系统中虽然可以运行但需要捕捉异常
                 */
//                $schedule->command('es:index:rebuild')->dailyAt($updateDailyTime)->runInBackground();
//                $schedule->command('es:index:rebuild')->dailyAt($updateDailyTime);
                $schedule->call(function () {
                    $this->dispatch();
                })->dailyAt($updateDailyTime);
            } else {
//                $schedule->command('es:index:rebuild')->weeklyOn($updateWeeklyTime, $updateDailyTime);
                $schedule->call(function () {
                    $this->dispatch();
                })->weeklyOn($updateWeeklyTime, $updateDailyTime);
            }
        }
    }

    /**
     * 分发定时任务
     */
    private function dispatch(): void
    {
        dispatch(new ElasticsearchJob([
            'type' => ConfigOptions::QUEUE_UPDATE_FUNCTION_REINDEX,
            'data' => [
                'type' => ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH,
                'schedule' => true,
            ],
        ], Constant::DEFAULT_QUEUE));
    }

    /**
     * 获取elasticsearch定时更新
     *
     * @param bool $refreshCache
     *
     * @return array
     */
    private function getElasticScheduleConfig($refreshCache = false) : array
    {
        $config = Redis::get(RedisKey::REDIS_SCHEDULE_CONFIG);

        if ($refreshCache || !$config) {
            $data = $this->repository->refreshConfigCache();

            return $data;
        }

        return json_decode($config, true);
    }
}