<?php


namespace App\EofficeApp\Elastic\Commands;

use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService;
use App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService;
use App\EofficeApp\Elastic\Services\Document\DocumentManager;
use App\EofficeApp\Elastic\Services\User\UserBuilder;
use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder as ESClientBuilder;
use DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class TestEsCommand extends Command
{
    public $client;

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
    protected $signature = 'es:index:test';

    public function handle()
    {
        $this->testProcessBar();
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function testProcessBar()
    {
        $output = new ConsoleOutput();
        // creates a new progress bar (50 units)
        $progressBar = new ProgressBar($output, 50);

        // starts and displays the progress bar
        $progressBar->start();

        $i = 0;
        while ($i++ < 50) {
            // ... do some work

            // advances the progress bar 1 unit
            $progressBar->advance();

            // you can also advance the progress bar by more than 1 unit
            // $progressBar->advance(3);
        }

        // ensures that the progress bar is at 100%
        $progressBar->finish();
    }

    /**
     * 同步附件内容表
     */
    public function syncAttachment($table = 'document_content')
    {
        // 根据table获取 attachment_relation_table获取
        $attachment_relation_table = 'attachment_relataion_'.$table;
        $data = DB::table($attachment_relation_table)->get();

        $insertData = [];
        foreach ($data as $item) {
            $rel = DB::table('attachment_rel')->where('attachment_id', $item->attachment_id)->select('rel_id', 'year', 'month')->get()->first();
            $attachment_files_table = 'attachment_'.$rel->year.'_'.$rel->month;

            $attachment = DB::table($attachment_files_table)->where('rel_id', $rel->rel_id)->get()->first();
            $type = $attachment->attachment_type;
            $name = $attachment->attachment_name;
            $path = $attachment->attachment_path;
            $affect_name = $attachment->affect_attachment_name;

            $file = getAttachmentDir().$path.$affect_name;

            /** @var DocumentManager $manager */
            $manager = app('App\EofficeApp\Elastic\Services\Document\DocumentManager');
            $info = ['type' => $type, 'path'=> $file];

            $attachmentInfo = $manager->readContent($info);
            $content = trim($attachmentInfo['content']);
            $imageInfo = json_encode($attachmentInfo['imageInfo']);

            $insertData[] = [
                'relation_id' => $item->attachment_id,
                'attachment_relation_table' => $table,
                'attachment_relation_entity_id' => $item->entity_id,
                'name' => $name,
                'type' => $type,
                'size' => $attachment->attachment_size,
                'content' => $content,
                'image_info' => $imageInfo,
            ];
        }
        // 获取attachment_rel 和 attachment
        // 获取 type 和 path
        // 读取内容
        // 存入 attachment_content
        DB::table(ElasticTables::ATTACHMENT_CONTENT_TABLE)->insert($insertData);
    }

    /**
     * 词典初始化测试
     */
    public function initDic()
    {
        /** @var SynonymDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService');
        $service->initializedDatabase();

        /** @var ExtensionDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService');
        $service->initializedDatabase();
    }

    /**
     * 消息队列删除测试
     */
    public function deleteByQueue()
    {
        try {
            $processor = app('App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProcessor');
            $processor->documentReindex(['category' => 'address_private', 'id' => 56]);
        } catch (\Exception $exception) {
            dd($exception);
        }
    }
    /**
     * 索引创建测试
     */
    public function createTest()
    {
        $startAt = microtime(true);
        $this->info("➤ Executing command: <info>{$this->getName()}</info>");

        /** @var UserBuilder $builder */
        $builder  = app('App\EofficeApp\Elastic\Services\User\UserBuilder');
        $builder->build();

        $endAt = microtime(true);
        $duration = round($endAt - $startAt, 3) * 1000 .'ms';
        $this->info("<info>•</info> done [created index]");
        $this->info("<info>✔</info> OK [$duration]");
    }

    /**
     * 别名测试
     */
    public function aliasTest()
    {
        $params['body'] = [
            'actions' => [
                [
                    'add' => [
                        'index' => 'eoffice_user_v1564391795',
                        'alias' => 'test'
                    ]
                ]
            ]
        ];

        $params['body'] = [
            'actions' => [
                ['remove_index' => ['index' => 'eoffice_user_v1564394274']],

            ]
        ];
        $this->client->indices()->updateAliases($params);
    }

    /**
     * 初始化测试
     */
    public function initTest()
    {
        $repository = app('App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository');
        $repository->initConfig();
    }

    /**
     * 部分更新测试
     */
    public function updatePartly()
    {
        $params = [
            'index' => 'eoffice_user',
            'type' => 'for_client',
            'id' => 'WV00000371',
            'body' => [
                'doc' => [
                    'user_name' => 'test'
                ]
            ]
        ];

        $this->client->update($params);
    }
}