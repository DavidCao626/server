<?php


namespace App\Jobs\Elasticsearch;

use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProcessor;
use App\Jobs\Job;

/**
 * 即时跟新es索引的队列
 *
 * Class ElasticsearchJob
 * @package App\Jobs
 */
class ElasticsearchJob extends Job
{
    /**
     * @var array $params
     */
    public $params;

    /**
     * es处理器
     *
     * @var string $processor;
     */
    public $processor;

    /**
     * es队列名称
     *
     * @var string $queue
     */
    public $queue = Constant::ELASTIC_QUEUE;

    /**
     * 任务可以尝试的最大次数。
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 任务可以执行的最大秒数 (超时时间)。
     *
     * @var int
     */
    public $timeout = 0;

    public function __construct($params, $queue = null)
    {
        $this->params = $params;
        $this->processor = 'App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProcessor';

//        if ($queue) {
//            $this->queue = $queue;
//        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        
        $type = $this->params['type'] ?? '';
        $data = $this->params['data'] ?? [];

        /** @var ElasticsearchProcessor $processor */
        $processor = app($this->processor);
        $processor->handle($type, $data);
    }
}