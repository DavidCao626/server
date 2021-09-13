<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Document;


use App\EofficeApp\Document\Entities\DocumentContentEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use DB;
use Illuminate\Support\Facades\Log;

class DocumentBuilder extends BaseBuilder
{
    /**
     * @param DocumentContentEntity $entity
     */
    public $entity;

    /**
     * @param DocumentManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Document\Entities\DocumentContentEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\Document\DocumentManager';
        $this->alias = Constant::DOCUMENT_ALIAS;
    }

    /**
     * 获取对应的DocumentContentEntity
     *
     * @param string $id
     *
     * @return DocumentContentEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取文档entity
         *
         * @param DocumentContentEntity $documentEntity
         */
        $documentEntity = app($this->entity);
        $document = $documentEntity->where('document_id', $id)->first();

        return $document;
    }

    /**
     * 生成文档内容信息
     *
     * @param DocumentContentEntity $documentEntity
     *
     * @return array
     */
    public function generateDocument(DocumentContentEntity $documentEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $attachment = $this->getAttachmentInfo('document_content', $documentEntity->document_id, $isUpdated);
            $isFlowDocument = $documentEntity->folder_type == 5; // 流程归档文件内容需清空
            $document = [
                'document_id' => $documentEntity->document_id,
                'subject' => Filter::htmlFilter($documentEntity->subject),
                'content' =>  $isFlowDocument ? '' : Filter::entityFilter(Filter::htmlFilter($documentEntity->content)),
                'create_time' => $documentEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::DOCUMENT_CATEGORY,
                'attachment' => $attachment
            ];
            $document['priority'] = self::getPriority(Constant::DOCUMENT_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $documentEntity->document_id,
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