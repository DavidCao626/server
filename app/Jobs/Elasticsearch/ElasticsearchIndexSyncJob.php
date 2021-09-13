<?php


namespace App\Jobs\Elasticsearch;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Repositories\ElasticStashIndexRepository;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

/**
 * 更新es即时更新期间贮藏的数据
 *
 * Class ElasticsearchIndexSyncJob
 * @package App\Jobs
 */
class ElasticsearchIndexSyncJob extends Job
{
    /**
     * @var array $needToUpdateCategories
     */
    private $needToUpdateCategories;

    /**
     * @var ElasticStashIndexRepository $repository
     */
    private $repository;

    /**
     * es队列名称
     *
     * @var string $queue
     */
    public $queue = Constant::ELASTIC_QUEUE;

    public function __construct($needToUpdateCategories, $queue = null)
    {
        $this->needToUpdateCategories = $needToUpdateCategories;
        $this->repository = app('App\EofficeApp\Elastic\Repositories\ElasticStashIndexRepository');

        if ($queue) {
            $this->queue = $queue;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        array_map(function ($category) {
            // 验证是否为有效分类
           $this->updateStashIndex($category);
        }, $this->needToUpdateCategories);
    }

    /**
     * 更新指定
     * @param string $category
     */
    private function updateStashIndex($category)
    {
        try {
            $ids = $this->repository->getTargetStashIndexByCategory($category);
            /** @var BaseBuilder $baseBuilder */
            $baseBuilder = app('App\EofficeApp\Elastic\Services\Search\BaseBuilder');
            $builder = $baseBuilder->getBuilder($category);

            // 待更新超过1000 则批量更新
            if (count($ids) < 1000) {
                foreach ($ids as $id) {
                    $builder->build($id, $category, 10, true);
                }
            } else {
                $builder->build(null, $category, 10, true);
            }

            $this->repository->deleteTargetIndexByCategory($category);

        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
        }

    }
}