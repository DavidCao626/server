<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerContactRecord;


use App\EofficeApp\Customer\Entities\BusinessChanceEntity;
use App\EofficeApp\Customer\Entities\ContactRecordEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use Illuminate\Support\Facades\Log;

class CustomerContactRecordBuilder extends BaseBuilder
{
    /**
     * @param ContactEntity $entity
     */
    public $entity;

    /**
     * @param CustomerContactRecordManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Customer\Entities\ContactRecordEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerContactRecord\CustomerContactRecordManager';
        $this->alias = Constant::CUSTOMER_CONTACT_RECORD_ALIAS;
    }

    /**
     * 获取对应的 ContactRecordEntity
     *
     * @param string $id
     *
     * @return ContactRecordEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取客户联系记录entity
         *
         * @param ContactRecordEntity
         */
        $customerContactRecordEntity = app($this->entity);
        $customerContactRecord = $customerContactRecordEntity->where('record_id', $id)->withTrashed()->first();

        return $customerContactRecord;
    }

    /**
     * 生成客户客户联系记录索引文档信息
     *
     * @param ContactRecordEntity $contactRecordEntity
     *
     * @return array
     */
    public function generateDocument(ContactRecordEntity $customerContactRecordEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $remark = $customerContactRecordEntity->record_content ? Filter::emojiFilter(Filter::htmlFilter($customerContactRecordEntity->record_content)): '';
            $attachment = $this->getAttachmentInfo('customer_contact_record', $customerContactRecordEntity->record_id, $isUpdated);
            $document = [
                'record_id' => $customerContactRecordEntity->record_id,
                'customer_id' => $customerContactRecordEntity->customer_id,
                'linkman_name' => isset($customerContactRecordEntity->contactRecordLinkman) ? $customerContactRecordEntity->contactRecordLinkman->linkman_name : '',
                'record_creator' => isset($customerContactRecordEntity->contactRecordCreator) ?
                    $customerContactRecordEntity->contactRecordCreator->user_name : '',
                'affiliated_customers' => isset($customerContactRecordEntity->contactRecordCustomer) ?
                    $customerContactRecordEntity->contactRecordCustomer->customer_name : '',
                'record_content' => $remark,
                'record_start' => $customerContactRecordEntity->record_start,
                'record_end' => $customerContactRecordEntity->record_end,
                'create_time' => $customerContactRecordEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::CUSTOMER_CONTACT_RECORD_CATEGORY,
                'attachment' => $attachment,
            ];

            $document['priority'] = self::getPriority(Constant::CUSTOMER_CONTACT_RECORD_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $customerContactRecordEntity->record_id,
            ];
            $param['document'] = $document;

            return $param;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return [];
        }
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
                $query = $entity->newQuery()->withTrashed();
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
            $query = $entity->newQuery()->withTrashed();
            $totalCount = $query->count();

            if ($totalCount <= 0) {
                return ['succeed' => $succeedCount, 'failed' => $failedCount, 'total' => $totalCount];
            }

            $this->chunkQueryCreate($query, $step, $targetIndex, $succeedCount, $totalCount, $updateAttachment);
        }

        $failedCount = $totalCount - $succeedCount > 0 ? $totalCount - $succeedCount : 0;

        return ['succeed' => $succeedCount, 'failed' => $failedCount, 'total' => $totalCount];
    }
}