<?php


namespace App\EofficeApp\Elastic\Commands;


use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

/**
 * 保留索引版本
 */
class PreservedVersionCommand extends Command
{
    protected $signature = 'es:index:preserve {category} {newIndex}';

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
        $this->info("➤ Executing command: <info>{$this->getName()}</info>");
        $startAt = microtime(true);
        $category = $this->argument('category');
        $newIndex = $this->argument('newIndex');
        // 根据分类获取当前所有索引名
        $name = Constant::ALIAS_PREFIX.$category.'_v';
        // 按索引名聚合, TODO 整理聚合foundation
        $agg = $this->client->search([
            'index' => $name.'*',
            'body'=> [
                'size' => 0,
                'aggs' => [
                    'getAllNames' => [
                        'terms' => [
                            'field' => '_index',
                            'size' => ConfigOptions::INDEX_LIMIT_BY_CATEGORY
                        ]
                    ]
                ]
            ]
        ]);

        if (isset($agg['aggregations']['getAllNames']['buckets'])) {
            $buckets = $agg['aggregations']['getAllNames']['buckets'];
            // 获取该分类的所有索引名(去除新增索引/指定索引)
            $bucketsKeys = array_column($buckets, 'key');
            $allNames = array_diff($bucketsKeys, [$newIndex]);

            // 按照创建时间排序
            $deleteNames = [];
            foreach ($allNames as $key => $value)
            {
                $newKey = str_replace($name, '', $value);
                $deleteNames[$newKey] = $value;
            }
            krsort($deleteNames);

            $limit = ConfigOptions::DEFAULT_INDICES_PRESERVED;

            if ($deleteNames) {
                $deleteNames = array_slice($deleteNames, $limit - 1);
                if ($deleteNames) {
                    $deleteNamesStr = implode(',', $deleteNames);
                    // 批量删除
                    $this->client->indices()->delete([
                        'index' => $deleteNamesStr
                    ]);
                }
            }
        }

        $endAt = microtime(true);
        $duration = round($endAt - $startAt, 3) * 1000 .'ms';
        $this->info("• done [remove indices out of limit]");
        $this->info("<info>✔</info> OK [$duration]");
    }
}