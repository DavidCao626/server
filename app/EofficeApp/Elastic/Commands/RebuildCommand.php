<?php


namespace App\EofficeApp\Elastic\Commands;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository;
use App\EofficeApp\Elastic\Services\Log\LogService;
use App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redis;

class RebuildCommand extends Command
{
    /**
     * 控制台命令名称.
     *
     * @var string
     */
    protected $signature = 'es:index:rebuild {alias=all} {operator=schedule} {--delete} {--schedule}';

    private $allUpdate = false; // 是否为全部索引更新

    public function handle()
    {
        // 迁移数据过大可能存在内存溢出问题, 增大内存限制
        ini_set('memory_limit', -1);

        $alias = $this->argument('alias');
        $operator = $this->argument('operator');
        $delete = $this->option('delete');
        $schedule = $this->option('schedule');

        $updateLogCategory = 'all'; // 更新日志分类

        $startAt = microtime(true);
        $this->info("➤ Executing command: <info>{$this->getName()}</info>");

        // 索引版本
        $version = $this->getVersion();

        // 获取 alias 若无则全部重建
        if ($alias === 'all') {
            $categories = Constant::$allIndices;
            $this->allUpdate = true;
        } else {
            $index = Constant::$allIndices[$alias] ?? '';
            $updateLogCategory = $index;
            $categories = [$alias => $index];
        }

        // 重建
        foreach ($categories as $alias => $category) {
            $this->rebuild($alias, $category, $version, $delete, $schedule);
        }

        $endAt = microtime(true);
        $duration = round($endAt - $startAt, 3) * 1000 .'ms';
        $this->info('• done [rebuilt]');
        $this->info("✔ OK [$duration]");

        // 增加更新日志
        $this->addUpdateLog($operator, $updateLogCategory);
    }

    public function rebuild($alias, $category, $version, $delete, $schedule = false)
    {
        if (!$category || !isset(Constant::$allIndices[$alias])) {
            return false;
        }
        // 处理缓存中更新相关信息
        if (!$schedule) {
            $this->updateInfoInRedisBeforeMigration($category);
        }

        // 创建新索引
        $newIndexName = Constant::ALIAS_PREFIX.$category.'_v'.time();

        $this->call('es:index:create', [
            'index' => $newIndexName,
            '--category' => $category,
            '--indexVersion' => $version,
        ]);

        // 迁移数据
        $this->call('es:index:migrate', [
            'index' => $newIndexName,
            'category' => $category,
        ]);

        // 切换 alias
        $this->call('es:alias:rename', [
            'index' => $newIndexName,
            'alias' => $alias,
            '--delete' => $delete,
        ]);

        // 保留指定版本
        $this->call('es:index:preserve', [
            'category' => $category,
            'newIndex' => $newIndexName,
        ]);

        // 当前指定索引创建完成
        $this->info('[done]index '.$alias.' rebuild');

        // 处理缓存中更新相关信息
        $this->updateInfoInRedisAfterMigration($category);

        return true;
    }

    /**
     * 获取schema版本
     */
    private function getVersion()
    {
        /** @var ElasticSearchConfigRepository $repository */
        $repository = app('App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository');

        return $repository->getSchemaVersion();
    }

    /**
     * 记录更新日志
     *
     * @param string $operator 操作者
     * @param string $category 更新索引分类
     */
    private function addUpdateLog($operator, $category = 'all'): void
    {
        if ($operator && $category) {
            // 记录更新日志
            /** @var LogService $logService */
            $logService = app('App\EofficeApp\Elastic\Services\Log\LogService');
            $logService->addUpdateLog($operator, $category);
        }
    }

    /**
    数据迁移完成后处理缓存中更新相关信息
     *
     * @param string $category 索引分类
     */
    private function updateInfoInRedisBeforeMigration($category)
    {
        $redisKey = RedisKey::REDIS_UPDATE;
        $updateInfo = Redis::get($redisKey);
        if (!$updateInfo) {
            $updateInfo = ManagementPlatformService::getElasticUpdateByQueueStructure();
        } else {
            $updateInfo = json_decode($updateInfo, true);;
        }

        // 如果为全部更新, 则更新all中相关数据
        $updateInfo['running'] = true;      // 更新进行中
        $updateInfo['runningCategory'] = $category; // 正在更新的分类
        $updateInfo['all']['status'] = 'running';   // TODO 状态需统一处理为常量

        // 从redis预处理中删除相关分类
        $key = array_search($category, $updateInfo['all']['prepared']);
        if ($key !== false) {
            unset($updateInfo['all']['prepared'][$key]);
        }

        // 从redis已完成中删除相关分类
        $key = array_search($category, $updateInfo['all']['finished']);
        if ($key !== false) {
            unset($updateInfo['all']['finished'][$key]);
        }

        $redisValue = json_encode($updateInfo);
        Redis::set($redisKey, $redisValue);
    }

    /**
     * 数据迁移完成后处理缓存中更新相关信息
     *
     * @param string $category 索引分类
     */
    private function updateInfoInRedisAfterMigration($category)
    {
        $redisKey = RedisKey::REDIS_UPDATE;
        $updateInfo = Redis::get($redisKey);

        if (!$updateInfo) {
            return false;
        }
        $updateInfo = json_decode($updateInfo, true);
        /**
         * 全部更新
         *  1. 清空正在更新索引
         *  2. 将正在更新索引移入已完成
         *  3. 若待更新索引为空则全部更新完成
         */
        $updateInfo['runningCategory'] = '';
        $finishedCategory = array_unique($updateInfo['all']['finished']);
        if (!in_array($category, $finishedCategory)) {
            array_push($finishedCategory, $category);
            $updateInfo['all']['finished'] = $finishedCategory;
        }

        if (!$updateInfo['all']['prepared']) {
            $updateInfo['running'] = false;
            $updateInfo['all']['status'] = 'finished';

            // 全部跟新为插件开启时数据迁移，处理迁移完成后相关逻辑
            if ($this->allUpdate) {
                /** @var ManagementPlatformService $managerPlatformService */
                $managerPlatformService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
                $managerPlatformService->afterDataMigration();
            }
        }

        // 如果全部更新仍处于更新中并且全部更新中存在与更新索引，则继续更新
        if (($updateInfo['all']['status'] === 'running') && $updateInfo['all']['prepared']) {
            $updateInfo['running'] = true;
        }

        $updateInfo['category'][$category]['updateCount'] = 0;
        $redisValue = json_encode($updateInfo);
        Redis::set($redisKey, $redisValue);

        return true;
    }
}