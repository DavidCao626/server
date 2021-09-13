<?php

namespace App\Console;

use App\Console\Schedules\Schedule as EofficeSchedule;
use DB;
use Eoffice;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Redis;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Push::class,
        \App\Console\Commands\EofficeCaseUpdate::class,
        \App\Console\Commands\Update::class,
        \App\Console\Commands\CreateApiDoc::class,
        \App\Console\Commands\Test::class,
        \App\EofficeApp\Elastic\Commands\TestEsCommand::class,
        \App\EofficeApp\Elastic\Commands\CreateIndexCommand::class,
        \App\EofficeApp\Elastic\Commands\RenameAliasCommand::class,
        \App\EofficeApp\Elastic\Commands\PreservedVersionCommand::class,
        \App\EofficeApp\Elastic\Commands\MigrationCommand::class,
        \App\EofficeApp\Elastic\Commands\RebuildCommand::class,
        \App\EofficeApp\Elastic\Commands\SyncAttachmentContentCommand::class,
        \App\EofficeApp\Elastic\Commands\Suggestion\RebuildCommand::class,
        \App\EofficeApp\Elastic\Commands\Suggestion\DiscoverCommand::class,
        \App\EofficeApp\Elastic\Commands\Suggestion\MigrationCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // 建议将本函数的定时任务迁移到Eoffice定时任务目录下。
        // e-office10标准定时任务调用
        $this->callSchedules($schedule);
        // 第三方定时任务调用
        $this->callSchedules($schedule, 'Third');
    }
    /**
     * 调用定时任务文件夹里面的所有任务
     *
     * @param Schedule $schedule
     * @param string $folder
     */
    public function callSchedules($schedule, $folder = 'Eoffice')
    {
        $schedulesFolder = __DIR__ . '/Schedules/' . $folder;

        $scheduleFiles = $this->getScheduleFiles($schedulesFolder);

        array_map(function ($className) use ($schedule, $folder) {
            // 创建定时任务对象
            $classObj = app('App\Console\Schedules\\' . $folder . '\\' . $className);
            // 判断是否实现了定时任务接口，实现了则调用
            if ($classObj instanceof EofficeSchedule) {
                $classObj->call($schedule);
            }
        }, $scheduleFiles);
    }
    /**
     * 获取当前定时任务目录下的所有定时任务文件
     *
     * @param string $schedulesFolder
     *
     * @return array
     */
    private function getScheduleFiles($schedulesFolder)
    {
        $scheduleFiles = [];
        $suffix = 'Schedule.php';

        $handler = opendir($schedulesFolder);

        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (substr_compare($filename, $suffix, -strlen($suffix)) === 0) {
                    list($className, $fileSuffix) = explode('.', $filename);

                    $scheduleFiles[] = $className;
                }
            }
        }

        closedir($handler);

        return $scheduleFiles;
    }
}
