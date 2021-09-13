<?php


namespace App\EofficeApp\Elastic\Services\Search;

use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Elastic\Entities\AttachmentContentEntity;
use App\EofficeApp\Elastic\Repositories\AttachmentContentRepository;
use App\EofficeApp\Elastic\Services\Document\DocumentManager;
use App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService;
use App\EofficeApp\Elastic\Utils\Camelize;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class BaseBuilder
{
    /**
     * @param BaseManager $manager
     */
    public $manager;

    /**
     * @param string $type
     */
    public $type;

    /**
     * @param AttachmentContentRepository $attachmentContentEntity
     */
    public $attachmentContentRepository;

    public function __construct()
    {
        $this->type = Constant::COMMON_INDEX_TYPE;
        $this->attachmentContentRepository = app('App\EofficeApp\Elastic\Repositories\AttachmentContentRepository');
    }

    /**
     * 创建索引
     *
     * @param null $id
     * @param null $targetIndex
     * @param int  $step
     *
     * @return array
     */
    public function build($id = null, $targetIndex = null, $step = 10, $updateAttachment = false)
    {
        if (isset($id)) {
            return $this->rebuildOne($id, $updateAttachment);
        } else {
            return $this->rebuildAll($targetIndex, $step, $updateAttachment);
        }
    }

    /**
     * 创建指定索引
     *
     * @param string $id    制定用户的user_id
     *
     * @return array
     */
    public function rebuildOne($id, $updateAttachment = false)
    {
        $succeedCount = 0;
        $failedCount = 0;
        $totalCount = 0;
        // 获取索引对应的entity
        $object = $this->getRebuildEntity($id);

        // 若没有此对象则删除
        if (!$object) {
            app($this->manager)->delete($id);
            return ['succeed' => 1, 'failed' => 0, 'total' => 1];
        }

        // 生成document
        if (!$document = $this->generateDocument($object, null, $updateAttachment)) {

            return ['succeed' => 0, 'failed' => 0, 'total' => 0];
        }

        $manager = app($this->manager);
        // 创建索引
        $response = $manager->create([$document]);

        // 根据状态后续处理
        if ($response) {
            ++$succeedCount;
        } else {
            ++$failedCount;
        }
        ++$totalCount;

        return ['succeed' => $succeedCount, 'failed' => $failedCount, 'total' => $totalCount];
    }
    /**
     * 批量创建索引
     *
     * @param string|null $targetIndex
     * @param int $step
     *
     * @return array
     */
    public function rebuildAll($targetIndex, $step, $updateAttachment = false)
    {
        $totalCount = 0;
        $succeedCount = 0;
        $failedCount = 0;

        if (is_array($this->entity)) {
            $entities = $this->entity;

            // 统计数量
            foreach ($entities as $entity) {
                $entity = app($entity);
                $query = $entity->newQuery();
                $totalCount += $query->count();
            }

            if ($totalCount <= 0) {
                return ['succeed' => $succeedCount, 'failed' => $failedCount, 'total' => $totalCount];
            }

            foreach ($entities as $entity) {
                $entity = app($entity);
                $query = $entity->newQuery();
                $this->chunkQueryCreate($query, $step, $targetIndex, $succeedCount, $totalCount, $updateAttachment);
            }
        } else {
            $entity = app($this->entity);
            $query = $entity->newQuery();
            $totalCount = $query->count();

            if ($totalCount <= 0) {
                return ['succeed' => $succeedCount, 'failed' => $failedCount, 'total' => $totalCount];
            }

            $this->chunkQueryCreate($query, $step, $targetIndex, $succeedCount, $totalCount, $updateAttachment);
        }

        $failedCount = $totalCount - $succeedCount > 0 ? $totalCount - $succeedCount : 0;

        return ['succeed' => $succeedCount, 'failed' => $failedCount, 'total' => $totalCount];
    }

    /**
     * 批量处理索引创建
     */
    protected function chunkQueryCreate($query, $step, $targetIndex, &$succeedCount, $totalCount, $updateAttachment = false)
    {
        $manager = app($this->manager);
        /**
         * 分批创建索引文档
         */
        $query->chunk($step, function ($objects) use ($targetIndex, $manager, &$succeedCount, $totalCount, $updateAttachment) {

            foreach ($objects as $object) {

                if (!$document = $this->generateDocument($object, $targetIndex, $updateAttachment)) {
                    continue;
                }

                $documents[] = $document;
            }

            $response = $manager->create($documents);

            if (!$response['errors']) {
                $succeedCount += count($response['items']);
            } else {
                Log::error(json_encode($response));
            }

            // 将更新进度存入Redis
            $this->updateProcess($totalCount, $succeedCount);
        });
    }
    /**
     * 获取指定分类的builder
     */
    public function getBuilder($category)
    {
        $dir =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\\';
        $builder = Camelize::toCamelCase($category);
        $file =  $dir.$builder.'\\'.$builder.'Builder';

        return app($file);
    }

    /**
     * 更新指定分类索引的更新进度
     *
     * @param int $totalCount
     * @param int $updateCount
     */
    protected function updateProcess($totalCount, $updateCount)
    {
        $category = str_replace('eoffice_', '', $this->alias);
        $redisKey = RedisKey::REDIS_UPDATE;
        $data =  Redis::get($redisKey);
        if (!$data) {
            $data = ManagementPlatformService::getElasticUpdateByQueueStructure();
            $data['runningCategory'] = $category;
        } else {
            $data = json_decode($data, true);
        }
        $data['category'][$category]['totalCount'] = $totalCount;
        $data['category'][$category]['updateCount'] = $updateCount;
        $redisValue = json_encode($data);
        Redis::set($redisKey, $redisValue);

        return;
    }

    /**
     * 获取附件内容信息
     *
     * @param string $table
     * @param string|int $entityId
     *
     * @return array
     */
    protected function getAttachmentInfo($table, $entityId, $isUpdated = false): array
    {
        /** @var AttachmentContentRepository $repository */
        $repository =  $this->attachmentContentRepository;

        // 是否更新附件, 将附件内容同步到附件内容表
        if ($isUpdated) {
            /** @var DocumentManager $manager */
            $manager = app('App\EofficeApp\Elastic\Services\Document\DocumentManager');
            $manager->updateAttachmentContentByCategoryAndEntityId($table, $entityId);
        }

        $data = $repository->getAttachmentInfo($table, $entityId);
        $attachment = [];

        foreach ($data as $item) {
            if (!$item) {
                continue;
            }

            $attachment[] = [
                'content' => $item['content'],
                'relation_entity_id' => $item['attachment_relation_entity_id'],
                'relation_table' => $item['attachment_relation_table'],
                'attachment_name' => $item['name'],
                'attachment_type' => $item['type'],
            ];
        }

        return $attachment;
    }

    /**
     * 获取分类的搜索优先级
     *
     * @return int
     */
    protected function getPriority($category)
    {
        switch ($category) {
            case Constant::USER_CATEGORY:
                return 100;
            case Constant::SYSTEM_LOG_CATEGORY:
                return 1;
            default:
                return 10;
        }
    }
}