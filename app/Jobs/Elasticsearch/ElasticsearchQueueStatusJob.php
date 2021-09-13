<?php


namespace App\Jobs\Elasticsearch;

use App\EofficeApp\Elastic\Configurations\Constant;
use App\Jobs\Job;

/**
 * 用来测试es队列是否开启的job
 *
 * Class ElasticsearchQueueStatusJob
 * @package App\Jobs\Elasticsearch
 */
class ElasticsearchQueueStatusJob extends Job
{
    /**
     * es队列名称
     *
     * @var string $queue
     */
    public $queue = Constant::ELASTIC_QUEUE;

    public function __construct()
    {
        // TODO
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // TODO
    }
}