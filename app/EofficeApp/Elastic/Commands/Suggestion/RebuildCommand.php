<?php


namespace App\EofficeApp\Elastic\Commands\Suggestion;


use App\EofficeApp\Elastic\Configurations\Constant;
use Illuminate\Console\Command;

/**
 * 重建建议词索引
 *
 * Class RebuildCommand
 * @package App\EofficeApp\Elastic\Commands\Suggestion
 */
class RebuildCommand extends Command
{
    protected $signature = 'es:suggestion:rebuild';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $startAt = microtime(true);
        $this->info("➤ Executing command: <info>{$this->getName()}</info>");

        // 创建新索引
        $newIndexName = Constant::ALIAS_PREFIX.Constant::SUGGESTIONS_CATEGORY.'_v'.time();

        $this->call('es:index:create', [
            'index' => $newIndexName,
            '--category' => Constant::SUGGESTIONS_CATEGORY,
            '--indexVersion' => 'v1',
        ]);

        // 提取建议词
        $this->call('es:suggestion:discover', [
            'index' => $newIndexName,
            '--category' => Constant::SUGGESTIONS_CATEGORY,
            '--indexVersion' => 'v1',
        ]);


        // 建议词入库
        $this->call('es:suggestion:migrate', [
            'index' => $newIndexName,
            '--category' => Constant::SUGGESTIONS_CATEGORY,
            '--indexVersion' => 'v1',
        ]);


        // 切换 alias
        $this->call('es:alias:rename', [
            'index' => $newIndexName,
            '--category' => Constant::SUGGESTIONS_CATEGORY,
            '--indexVersion' => 'v1',
        ]);


        $endAt = microtime(true);
        $duration = round($endAt - $startAt, 3) * 1000 .'ms';
        $this->info('<info>•</info> done [rebuilt]');
        $this->info("<info>✔</info> OK [$duration]");
    }
}