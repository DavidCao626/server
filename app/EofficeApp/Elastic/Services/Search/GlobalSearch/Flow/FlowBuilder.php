<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Flow;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Flow\Entities\FlowRunEntity;
use Illuminate\Support\Facades\Log;

class FlowBuilder extends BaseBuilder
{
    /**
     * @param FlowRunEntity $entity
     */
    public $entity;

    /**
     * @param FlowManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Flow\Entities\FlowRunEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\Flow\FlowManager';
        $this->alias = Constant::FLOW_ALIAS;
    }

    /**
     * 获取对应的entity
     *
     * @param int $id
     *
     * @return FlowRunEntity|null
     */
    public function getRebuildEntity($id)
    {
        /** @var FlowRunEntity $flowEntity */
        $flowEntity = app($this->entity);
        $flow = $flowEntity->where('run_id', $id)->first();

        return $flow;
    }

    /**
     * 生成邮件索引信息
     *
     * @param FlowRunEntity $flowEntity
     *
     * @return array
     */
    public function generateDocument(FlowRunEntity $flowEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $attachment = $this->getAttachmentInfo('flow_run', $flowEntity->run_id, $isUpdated);
            $create_time = $flowEntity->created_at ? $flowEntity->created_at->format('Y-m-d H:i:s') : null;
            $creator_name = $flowEntity->user_id ? $flowEntity->flowRunHasOneUser()->withTrashed()->first()->user_name : '';
            $document = [
                'run_id' => $flowEntity->run_id,
                'run_name' => $flowEntity->run_name,    // 流程名称
                'flow_id' => $flowEntity->flow_id,
                'run_seq_strip_tags' => $flowEntity->run_seq_strip_tags, // 无样式流水号
                'creator_name' => $creator_name,  // 部分流程注销
                'create_time' => $create_time,
                'category' => Constant::FLOW_CATEGORY,
                'attachment' => $attachment,
            ];
            $document['priority'] = self::getPriority(Constant::FLOW_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $flowEntity->run_id,
            ];

            $param['document'] = $document;

            return $param;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return [];
        }
    }
}