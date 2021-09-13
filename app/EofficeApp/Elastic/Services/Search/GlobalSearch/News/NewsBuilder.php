<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\News;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use App\EofficeApp\News\Entities\NewsEntity;
use Illuminate\Support\Facades\Log;

class NewsBuilder extends BaseBuilder
{
    /**
     * @param NewsEntity $entity
     */
    public $entity;

    /**
     * @param NewsManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\News\Entities\NewsEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\News\NewsManager';
        $this->alias = Constant::NEWS_ALIAS;
    }

    /**
     * 获取对应的entity
     *
     * @param int $id
     *
     * @return NewsEntity|null
     */
    public function getRebuildEntity($id)
    {
        /** @var NewsEntity $newsEntity */
        $newsEntity = app($this->entity);
        // 草稿状态自己可查看
        $news = $newsEntity->where('news_id', $id)->first();

        return $news;
    }

    /**
     * 生成邮件索引信息
     *
     * @param NewsEntity $newsEntity
     *
     * @return array
     */
    public function generateDocument(NewsEntity $newsEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $attachment = $this->getAttachmentInfo('news', $newsEntity->news_id, $isUpdated);
            $document = [
                'news_id' => $newsEntity->news_id,
                'title' => $newsEntity->title,
                'news_desc' =>  Filter::htmlFilter($newsEntity->news_desc),
                'content' =>  Filter::emojiFilter(Filter::htmlFilter($newsEntity->content)),
                'create_time' => $newsEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::NEWS_CATEGORY,
                'attachment' => $attachment,
            ];
            $document['priority'] = self::getPriority(Constant::NEWS_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $newsEntity->news_id,
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