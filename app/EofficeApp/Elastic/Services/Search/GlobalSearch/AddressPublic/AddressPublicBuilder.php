<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\AddressPublic;


use App\EofficeApp\Address\Entities\AddressPublicEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use Illuminate\Support\Facades\Log;

class AddressPublicBuilder extends BaseBuilder
{
    /**
     * @param AddressPublicEntity $entity
     */
    public $entity;

    /**
     * @param AddressPublicManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Address\Entities\AddressPublicEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\AddressPublic\AddressPublicManager';
        $this->alias = Constant::PUBLIC_ADDRESS_ALIAS;
    }

    /**
     * 获取对应的entity
     *
     * @param int $id
     *
     * @return AddressPublicEntity|null
     */
    public function getRebuildEntity($id)
    {
        /** @var AddressPublicEntity $publicAddressEntity */
        $publicAddressEntity = app($this->entity);
        $publicAddress = $publicAddressEntity->where('address_id', $id)->first();

        return $publicAddress;
    }

    /**
     * 生成索引信息
     *
     * @param AddressPublicEntity $publicAddressEntity
     *
     * @return array
     */
    public function generateDocument(AddressPublicEntity $publicAddressEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $create_time = $publicAddressEntity->created_at ?
                $publicAddressEntity->created_at->format('Y-m-d H:i:s') : null;
            $document = [
                'address_id' => $publicAddressEntity->address_id,
                'name' => $publicAddressEntity->primary_1,         // 姓名
                'phone' => $publicAddressEntity->primary_3,        // 电话
                'serial_number' => $publicAddressEntity->primary_7,// 序号
                'email' => $publicAddressEntity->primary_9,        // 邮箱
                'create_time' => $create_time,
                'category' => Constant::PUBLIC_ADDRESS_CATEGORY
            ];

            $document['priority'] = self::getPriority(Constant::PUBLIC_ADDRESS_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $publicAddressEntity->address_id,
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