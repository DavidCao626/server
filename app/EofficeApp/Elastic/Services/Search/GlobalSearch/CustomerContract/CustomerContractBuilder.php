<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerContract;


use App\EofficeApp\Customer\Entities\BusinessChanceEntity;
use App\EofficeApp\Customer\Entities\ContractEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use Illuminate\Support\Facades\Log;

class CustomerContractBuilder extends BaseBuilder
{
    /**
     * @param ContractEntity $entity
     */
    public $entity;

    /**
     * @param CustomerContractManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Customer\Entities\ContractEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerContract\CustomerContractManager';
        $this->alias = Constant::CUSTOMER_CONTRACT_ALIAS;
    }

    /**
     * 获取对应的 ContractEntity
     *
     * @param string $id
     *
     * @return ContractEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取客户合同entity
         *
         * @param ContractEntity
         */
        $customerContractEntity = app($this->entity);
        $customerContract = $customerContractEntity->where('contract_id', $id)->first();

        return $customerContract;
    }

    /**
     * 生成客户业务机会索引文档信息
     *
     * @param ContractEntity $customerContractEntity
     *
     * @return array
     */
    public function generateDocument(ContractEntity $customerContractEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $remark = $customerContractEntity->contract_remarks ? Filter::emojiFilter(Filter::htmlFilter($customerContractEntity->contract_remarks)): '';
            $attachment = $this->getAttachmentInfo('customer_contract', $customerContractEntity->contract_id, $isUpdated);
            $document = [
                'contract_id' => $customerContractEntity->contract_id,
                'contract_name' => $customerContractEntity->contract_name,
                'contract_amount' => $customerContractEntity->contract_amount,
                'affiliated_customers' => isset($customerContractEntity->customer) ?
                    $customerContractEntity->customer->customer_name : '',
                'contract_remarks' => $remark,
                'create_time' => $customerContractEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::CUSTOMER_CONTRACT_CATEGORY,
                'attachment' => $attachment,
            ];

            $document['priority'] = self::getPriority(Constant::CUSTOMER_CONTRACT_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $customerContractEntity->contract_id,
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