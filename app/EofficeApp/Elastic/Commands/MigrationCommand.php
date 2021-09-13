<?php


namespace App\EofficeApp\Elastic\Commands;


use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrationCommand extends Command
{
    protected $signature = 'es:index:migrate {index} {category}';

    /**
     * @param Client $client
     */
    public $client;

    public function __construct()
    {
        parent::__construct();
        // 从配置文件读取 Elasticsearch 服务器列表并创建
        $config = config('elastic.elasticsearch.hosts');
        if (is_string($config)) {
            $config = explode(',', $config);
        }
        $this->client = ClientBuilder::create()->setHosts($config)->build();
    }

    public function handle()
    {
        // TODO 错误处理 1. builder中的entity不存在 2. rebuild失败报警和日志
        // 迁移数据过大可能存在内存溢出问题, 增大内存限制
        ini_set('memory_limit', -1);
        gc_collect_cycles();

        $startAt = microtime(true);
        $this->line("➤ Executing command: <info>{$this->getName()}</info>");

        $index = $this->argument('index');
        $category = $this->argument('category');

        try {
            /** @var BaseBuilder $baseBuilder */
            $baseBuilder = app('App\EofficeApp\Elastic\Services\Search\BaseBuilder');
            $builder = $baseBuilder->getBuilder($category);

            $result = $builder->build(null, $index);
            $total = isset($result['total']) ? $result['total'] : 0;
            $succeed = isset($result['succeed']) ? $result['succeed'] : 0;
            $failed = isset($result['failed']) ? $result['failed'] : 0;

            $endAt = microtime(true);
            $duration = round($endAt - $startAt, 3) * 1000 .'ms';
            $this->info('');
            $this->info("<info>•</info> done [Migration finished, total: $total, succeed: <info>$succeed</info>, failed: <comment>$failed</comment>]");
            $this->info("<info>✔</info> OK [$duration]");
        } catch (\Exception $exception) {
            $this->error("<error>✘</error> Failed！Error message is: {$exception->getMessage()}");
            $this->client->indices()->delete(['index' => $index]);
            Log::error($exception->getTraceAsString());
        }
    }
}