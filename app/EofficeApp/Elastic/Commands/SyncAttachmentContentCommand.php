<?php


namespace App\EofficeApp\Elastic\Commands;


use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Services\Document\DocumentManager;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Arr;
/**
 * 同步附件内容
 * Class SyncAttachmentContentCommand
 * @package App\EofficeApp\Elastic\Commands
 */
class SyncAttachmentContentCommand extends Command
{
    // 根据分类获取对应的附件表
    const RELATION_TABLES = [
        'document' => 'document_content',
        'email' => 'email',
        'flow' => 'flow_run',
        'notify' => 'notify',
        'personnel_files' => 'personnel_files',
        'news' => 'news',
    ];

    protected $signature = 'es:index:attachment {category=all}';

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
        $categoryMap = $this->argument('category');

        $startAt = microtime(true);
        $this->line("➤ Executing command: <info>{$this->getName()}</info>");

        // 清空附件内容表
        DB::table(ElasticTables::ATTACHMENT_CONTENT_TABLE)->truncate();
        // 获取需要更新的所有分类
        $categories = $this->getRelationTablesByCategory($categoryMap);

        array_walk($categories, function ($category) {
            // 根据分类获取对应附件关联表中的所有数据
            $relationTableData = $this->getAttachmentRelationTableData($category);
            // 根据附件关联表获取附件内容等相关信息
            $insertData = $this->getRelationTableData($relationTableData, $category);
            DB::table(ElasticTables::ATTACHMENT_CONTENT_TABLE)->insert($insertData);
        });

        $endAt = microtime(true);
        $duration = round($endAt - $startAt, 3) * 1000 .'ms';
        $this->info('');
        $this->info("<info>•</info> done [Attachments content sync finished");
        $this->info("<info>✔</info> OK [$duration]");
    }

    /**
     * 根据分类获取对应附件关联表
     *
     * @param string $category 索引分类
     *
     * @return array
     */
    private function getRelationTablesByCategory($category): array
    {
        if ($category === 'all') {
            return self::RELATION_TABLES;
        } elseif (Arr::get(self::RELATION_TABLES, $category)) {
            return [Arr::get(self::RELATION_TABLES, $category)];
        } else {
            return [];
        }
    }

    /**
     * 获取分类对应的附件关联表中所有的数据
     *
     * @param string $category  索引分类
     *
     * @return array           分类对应的附件关联表的内容
     */
    private function getAttachmentRelationTableData($category)
    {
        // 根据table获取 attachment_relation_table获取
        $attachment_relation_table = 'attachment_relataion_'.$category;
        $data = DB::table($attachment_relation_table)->get();

        return $data;
    }

    /**
     * 获取附加关联表的内容
     *
     * @param array $relationTableData
     *
     * @return array
     */
    private function getRelationTableData($relationTableData, $cateogry)
    {
        $insertData = [];

        foreach ($relationTableData as $item) {
            $rel = DB::table('attachment_rel')->where('attachment_id', $item->attachment_id)->select('rel_id', 'year', 'month')->get()->first();

            if (!$rel) {
                continue;
            }

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

            if (!$attachmentInfo['content'] && !$attachmentInfo['imageInfo']) {
                continue;
            }

            $content = trim($attachmentInfo['content']);
            $imageInfo = json_encode($attachmentInfo['imageInfo']);

            $insertData[] = [
                'attachment_id' => $item->attachment_id,
                'attachment_relation_table' => $cateogry,
                'attachment_relation_entity_id' => $cateogry === 'flow_run' ? $item->run_id : $item->entity_id,
                'name' => $name,
                'type' => $type,
                'size' => $attachment->attachment_size,
                'content' => $content,
                'image_info' => $imageInfo,
            ];
        }

        return $insertData;
    }
}