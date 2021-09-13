<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerBusinessChance;


use App\EofficeApp\Customer\Entities\BusinessChanceEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use Illuminate\Support\Facades\Log;

class CustomerBusinessChanceBuilder extends BaseBuilder
{
    /**
     * @param BusinessChanceEntity $entity
     */
    public $entity;

    /**
     * @param CustomerBusinessChanceManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Customer\Entities\BusinessChanceEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerBusinessChance\CustomerBusinessChanceManager';
        $this->alias = Constant::CUSTOMER_BUSINESS_CHANCE_ALIAS;
    }

    /**
     * 获取对应的 BusinessChanceEntity
     *
     * @param string $id
     *
     * @return BusinessChanceEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取客户业务机会entity
         *
         * @param BusinessChanceEntity
         */
        $customerBusinessChanceEntity = app($this->entity);
        $customerBusinessChance = $customerBusinessChanceEntity->where('chance_id', $id)->first();

        return $customerBusinessChance;
    }

    /**
     * 生成客户业务机会索引文档信息
     *
     * @param BusinessChanceEntity $customerBusinessChanceEntity
     *
     * @return array
     */
    public function generateDocument(BusinessChanceEntity $businessChanceEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $remark = $businessChanceEntity->chance_remarks ?  Filter::emojiFilter(Filter::htmlFilter($businessChanceEntity->chance_remarks)) : '';
            $document = [
                'chance_id' => $businessChanceEntity->chance_id,
                'chance_name' => $businessChanceEntity->chance_name,
                'quoted_price' => $businessChanceEntity->quoted_price,
                'business_star' => $businessChanceEntity->business_star,
                'affiliated_customers' => isset($businessChanceEntity->businessChanceToCustomer[0]) ?
                    $businessChanceEntity->businessChanceToCustomer[0]->customer_name : '',
                'chance_remarks' => $remark,
                'create_time' => $businessChanceEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::CUSTOMER_BUSINESS_CHANCE_CATEGORY,
            ];

            $document['priority'] = self::getPriority(Constant::CUSTOMER_BUSINESS_CHANCE_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $businessChanceEntity->chance_id,
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