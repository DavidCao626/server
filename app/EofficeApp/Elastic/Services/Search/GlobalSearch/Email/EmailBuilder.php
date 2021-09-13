<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Email;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use App\EofficeApp\Email\Entities\EmailEntity;
use Illuminate\Support\Facades\Log;

class EmailBuilder extends BaseBuilder
{
    /**
     * @param EmailEntity $entity
     */
    public $entity;

    /**
     * @param EmailManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Email\Entities\EmailEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\Email\EmailManager';
        $this->alias = Constant::EMAIL_ALIAS;
    }

    /**
     * 获取对应的EmailEntity
     *
     * @param string $id
     *
     * @return EmailEntity|null
     */
    public function getRebuildEntity($id)
    {
        /** @var EmailEntity $emailEntity */
        $emailEntity = app($this->entity);
        $email = $emailEntity->where('email_id', $id)->where('deleted', false)->first();

        return $email;
    }

    /**
     * 生成邮件索引信息
     *
     * @param EmailEntity $emailEntity
     *
     * @return array
     */
    public function generateDocument(EmailEntity $emailEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $attachment = $this->getAttachmentInfo('email', $emailEntity->email_id, $isUpdated);
            $create_time = $emailEntity->created_at ?
                $emailEntity->created_at->format('Y-m-d H:i:s') : null;
            $document = [
                'email_id' => $emailEntity->email_id,
                'subject' => Filter::htmlFilter($emailEntity->subject),
                'content' => Filter::entityFilter(Filter::emojiFilter(Filter::htmlFilter($emailEntity->content))),
                'create_time' => $create_time,
                'category' => Constant::EMAIL_CATEGORY,
                'attachment' => $attachment,
            ];
            $document['priority'] = self::getPriority(Constant::EMAIL_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $emailEntity->email_id,
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
