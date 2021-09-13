<?php


namespace App\EofficeApp\Elastic\Commands;

use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Utils\JSON;
use Elasticsearch\Client;
use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder as ESClientBuilder;

/**
 * 创建索引
 *
 * Class CreateIndexCommand
 */
class CreateIndexCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        parent::__construct();

        // 从配置文件读取 Elasticsearch 服务器列表并创建
        $config = config('elastic.elasticsearch.hosts');
        if (is_string($config)) {
            $config = explode(',', $config);
        }
        $builder = ESClientBuilder::create()->setHosts($config);

        $this->client = $builder->build();
    }

    /**
     * 控制台命令名称.
     *
     * @var string
     */
    protected $signature = 'es:index:create {index} {--category} {--indexVersion}';

    /**
     * 控制台命令描述.
     *
     * @var string
     */
    protected $description = 'Create a index and add it to an alias(optional).';

    public function handle()
    {
        $startAt = microtime(true);
        $this->line("➤ Executing command: <info>{$this->getName()}</info>");


        $indexName = $this->argument('index');
        $category = $this->option('category');
        $version = $this->option('indexVersion');

        // 确认索引分类
        if (!$category) {
            $category = $this->ask( ' Please specify the category of the index',
               Constant::USER_CATEGORY
            );
        }

        // 确认索引版本
        if (!$version) {
            $version = $this->choice(
                ' Please specify the new index version',
                array('v1', 'v2'),
                'v1'
            );
        }

        try {
            // 判断新索引是否存在, 若存在则结束创建
            if ($this->client->indices()->exists(['index' => $indexName])) {
                $endAt = microtime(true);
                $duration = round($endAt - $startAt, 3) * 1000 .'ms';
                $this->line("<info>•</info> done [index <info>$indexName</info> exists]");
                $this->line("<info>✔</info> OK [$duration]");

                return;
            }

            $indexSchema = JSON::getElasticSearchIndexSchema($category, $version);

            if (!$indexSchema) {

                throw new \Exception('not found schema '.$category);
            }

            if (false) {
                // TODO 是否配置同义词接口
                $synonymsPath = '';
            }

            if (isset($indexSchema['settings']['analysis']['filter']['remote_synonym'])) {
                $indexSchema['settings']['analysis']['filter']['remote_synonym']['synonyms_path'] = $synonymsPath;
            }

            if (!isset($indexSchema['settings'])) {
                $indexSchema['settings'] = [];
            }

            $params = [
                'index' => $indexName,
                'body' => $indexSchema,
            ];


            /**
             * 返回实例
             *
             * array(3) {
             *      ["acknowledged"]=>
             *      bool(true)
             *      ["shards_acknowledged"]=>
             *      bool(true)
             *      ["index"]=>
             *      string(24) "eoffice_user_v1564385203"
             *  }
            */
            $this->client->indices()->create($params);

            // TODO 后续增加response处理

            $endAt = microtime(true);
            $duration = round($endAt - $startAt, 3) * 1000 .'ms';
            $this->line("<info>•</info> done [created index: <info>$indexName</info>]");
            $this->line("<info>✔</info> OK [$duration]");
        } catch (\Exception $exception) {
            $this->error("<error>✘</error> Failed，Error message is: {$exception->getMessage()}");
            $this->warn($exception->getMessage());
            // TODO 生成日志 删除索引
            exit(0);
        }
    }
}