<?php


namespace App\EofficeApp\Elastic\Commands;


use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\ClientBuilder as ESClientBuilder;
use Illuminate\Console\Command;

/**
 * 创建索引别名
 */
class RenameAliasCommand extends Command
{
    /**
     * @param Client $client
     */
    public $client;

    protected $signature = 'es:alias:rename {alias} {index} {--delete}';

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
        $startAt = microtime(true);
        $this->info("➤ Executing command: <info>{$this->getName()}</info>");

        $alias = $this->argument('alias');
        $index = $this->argument('index');
        $delete = $this->option('delete');

        // 切换别名
        $searchConfigService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigService');
        $searchConfigService->switchAliasByIndex($index, $alias, $delete);

        $endAt = microtime(true);
        $duration = round($endAt - $startAt, 3) * 1000 .'ms';
        $this->info("• done [alias $alias to $index]");
        $this->info("✔ OK [$duration]");
    }
}