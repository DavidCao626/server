<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Customer;


use App\EofficeApp\Customer\Entities\CustomerEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use Illuminate\Support\Facades\Log;

class CustomerBuilder extends BaseBuilder
{
    /**
     * @param CustomerEntity $entity
     */
    public $entity;

    /**
     * @param CustomerManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Customer\Entities\CustomerEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\Customer\CustomerManager';
        $this->alias = Constant::CUSTOMER_ALIAS;
    }

    /**
     * 获取对应的CustomerEntity
     *
     * @param string $id
     *
     * @return CustomerEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取客户entity
         *
         * @param CustomerEntity
         */
        $customerEntity = app($this->entity);
        $customer = $customerEntity->where('customer_id', $id)->first();

        return $customer;
    }

    /**
     * 生成客户索引文档信息
     *
     * @param CustomerEntity $customerEntity
     *
     * @return array
     */
    public function generateDocument(CustomerEntity $customerEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            if (!$customerEntity || false) {
                return null;
            }
            $attachment = $this->getAttachmentInfo('customer_linkman', $customerEntity->customer_id, $isUpdated);
            $create_time = $customerEntity->created_at ?
                $customerEntity->created_at->format('Y-m-d H:i:s') : null;
            $document = [
                'customer_id' => $customerEntity->customer_id,
                'customer_name' => $customerEntity->customer_name,  // 客户名称
                'customer_number' => $customerEntity->customer_number,// 客户编号
                'phone_number' => $customerEntity->phone_number,   // 电话号码
                'customer_type' => $customerEntity->customer_type,  // 客户类型
                'customer_status' => $customerEntity->customer_status,// 客户状态
                'category' => Constant::CUSTOMER_CATEGORY,
                'create_time' => $create_time,
                'attachment' => $attachment,
            ];

            $document['priority'] = self::getPriority(Constant::CUSTOMER_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $customerEntity->customer_id,
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