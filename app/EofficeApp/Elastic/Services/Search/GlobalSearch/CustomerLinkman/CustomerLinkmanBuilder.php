<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerLinkman;


use App\EofficeApp\Customer\Entities\LinkmanEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Utils\Filter;
use Illuminate\Support\Facades\Log;

class CustomerLinkmanBuilder extends BaseBuilder
{
    /**
     * @param LinkmanEntity $entity
     */
    public $entity;

    /**
     * @param CustomerLinkmanManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Customer\Entities\LinkmanEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerLinkman\CustomerLinkmanManager';
        $this->alias = Constant::CUSTOMER_LINKMAN_ALIAS;
    }

    /**
     * 获取对应的CustomerLinkmanEntity
     *
     * @param string $id
     *
     * @return LinkmanEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取客户联系人entity
         *
         * @param LinkmanEntity
         */
        $customerLinkmanEntity = app($this->entity);
        $customerLinkman = $customerLinkmanEntity->where('linkman_id', $id)->first();

        return $customerLinkman;
    }

    /**
     * 生成客户联系人索引文档信息
     *
     * @param LinkmanEntity $customerLinkmanEntity
     *
     * @return array
     */
    public function generateDocument(LinkmanEntity $linkmanEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $remark = $linkmanEntity->linkman_remarks ?  Filter::emojiFilter(Filter::htmlFilter($linkmanEntity->linkman_remarks)) : '';
            $attachment = $this->getAttachmentInfo('customer_linkman', $linkmanEntity->linkman_id, $isUpdated);
            $document = [
                'linkman_id' => $linkmanEntity->linkman_id,
                'linkman_name' => $linkmanEntity->linkman_name,
                'mobile_phone_number' => $linkmanEntity->mobile_phone_number,
                'email' => $linkmanEntity->email,
                'address' => $linkmanEntity->address,  // 家庭住址
                'linkman_remark' => $remark,
                'sex' => $linkmanEntity->sex,
                'main_linkman' => $linkmanEntity->main_linkman,// 是否为主要联系人 1是 0不是
                'customer_id' => $linkmanEntity->customer_id,
                'create_time' => $linkmanEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::CUSTOMER_LINKMAN_CATEGORY,
                'attachment' => $attachment,
            ];

            $document['priority'] = self::getPriority(Constant::CUSTOMER_LINKMAN_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $linkmanEntity->linkman_id,
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