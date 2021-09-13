<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Notify;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use App\EofficeApp\Notify\Entities\NotifyEntity;
use Illuminate\Support\Facades\Log;

class NotifyBuilder extends BaseBuilder
{
    /**
     * @param NotifyEntity $entity
     */
    public $entity;

    /**
     * @param NotifyManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Notify\Entities\NotifyEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\Notify\NotifyManager';
        $this->alias = Constant::NOTIFY_ALIAS;
    }

    /**
     * 获取对应的entity
     *
     * @param int $id
     *
     * @return NotifyEntity|null
     */
    public function getRebuildEntity($id)
    {
        /** @var NotifyEntity $notifyEntity */
        $notifyEntity = app($this->entity);
        $notify = $notifyEntity->where('notify_id', $id)->first();

        return $notify;
    }

    /**
     * 生成公告索引信息
     *
     * @param NotifyEntity $notifyEntity
     *
     * @return array
     */
    public function generateDocument(NotifyEntity $notifyEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $attachment = $this->getAttachmentInfo('notify', $notifyEntity->notify_id, $isUpdated);
            $document = [
                'notify_id' => $notifyEntity->notify_id,
                'subject' =>  Filter::htmlFilter($notifyEntity->subject),
                'content' => Filter::entityFilter(Filter::emojiFilter(Filter::htmlFilter($notifyEntity->content))),
                'create_time' => $notifyEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::NOTIFY_CATEGORY,
                'attachment' => $attachment,
            ];
            $document['priority'] = self::getPriority( Constant::NOTIFY_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $notifyEntity->notify_id,
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