<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerWillVisit;


use App\EofficeApp\Customer\Entities\BusinessChanceEntity;
use App\EofficeApp\Customer\Entities\VisitEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use Illuminate\Support\Facades\Log;

class CustomerWillVisitBuilder extends BaseBuilder
{
    /**
     * @param VisitEntity $entity
     */
    public $entity;

    /**
     * @param CustomerWillVisitManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Customer\Entities\VisitEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerWillVisit\CustomerWillVisitManager';
        $this->alias = Constant::CUSTOMER_WILL_VISIT_ALIAS;
    }

    /**
     * 获取对应的 VisitEntity
     *
     * @param string $id
     *
     * @return VisitEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取客户提醒entity
         *
         * @param VisitEntity
         */
        $customerVisitEntity = app($this->entity);
        $customerVisit= $customerVisitEntity->where('visit_id', $id)->first();

        return $customerVisit;
    }

    /**
     * 生成客户提醒索引文档信息
     *
     * @param VisitEntity $customerVisitEntity
     *
     * @return array
     */
    public function generateDocument(VisitEntity $customerVisitEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $document = [
                'visit_id' => $customerVisitEntity->visit_id,
                'linkman_name' => isset($customerVisitEntity->willVisitLinkman) ?
                    $customerVisitEntity->willVisitLinkman->linkman_name : '',
                'affiliated_customers' => isset($customerVisitEntity->willVisitCustomer) ?
                    $customerVisitEntity->willVisitCustomer->customer_name : '',
                'creator_name' => $customerVisitEntity->willVisitUser->user_name,
                'customer_id' => $customerVisitEntity->customer_id,
                'visit_content' => Filter::emojiFilter(Filter::htmlFilter($customerVisitEntity->visit_content)),
                'visit_time' => $customerVisitEntity->visit_time,
                'create_time' => $customerVisitEntity->create_time,
                'category' => Constant::CUSTOMER_WILL_VISIT_CATEGORY,
            ];

            $document['priority'] = self::getPriority(Constant::CUSTOMER_WILL_VISIT_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $customerVisitEntity->visit_id,
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