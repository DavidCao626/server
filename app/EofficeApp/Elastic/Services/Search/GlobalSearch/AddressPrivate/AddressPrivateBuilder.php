<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\AddressPrivate;


use App\EofficeApp\Address\Entities\AddressPrivateEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use Illuminate\Support\Facades\Log;

class AddressPrivateBuilder extends BaseBuilder
{
    /**
     * @param AddressPrivateEntity $entity
     */
    public $entity;

    /**
     * @param AddressPrivateManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\Address\Entities\AddressPrivateEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\AddressPrivate\AddressPrivateManager';
        $this->alias = Constant::PRIVATE_ADDRESS_ALIAS;
    }

    /**
     * 获取对应的entity
     *
     * @param int $id
     *
     * @return AddressPrivateEntity|null
     */
    public function getRebuildEntity($id)
    {
        /** @var AddressPrivateEntity $privateAddressEntity */
        $privateAddressEntity = app($this->entity);
        $privateAddress = $privateAddressEntity->where('address_id', $id)->first();

        return $privateAddress;
    }

    /**
     * 生成索引信息
     *
     * @param AddressPrivateEntity $privateAddressEntity
     *
     * @return array
     */
    public function generateDocument(AddressPrivateEntity $privateAddressEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $create_time = $privateAddressEntity->created_at ?
                $privateAddressEntity->created_at->format('Y-m-d H:i:s') : null;
            $document = [
                'address_id' => $privateAddressEntity->address_id,
                'name' => $privateAddressEntity->primary_1,         // 姓名
                'phone' => $privateAddressEntity->primary_3,        // 电话
                'serial_number' => $privateAddressEntity->primary_7,// 序号
                'email' => $privateAddressEntity->primary_9,        // 邮箱
                'create_time' => $create_time,
                'category' => Constant::PRIVATE_ADDRESS_CATEGORY
            ];

            $document['priority'] = self::getPriority(Constant::PRIVATE_ADDRESS_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $privateAddressEntity->address_id,
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