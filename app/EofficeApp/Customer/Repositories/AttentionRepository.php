<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\AttentionEntity;
use DB;

class AttentionRepository extends BaseRepository
{

    const TABLE_NAME = 'customer_attention';

    public function __construct(AttentionEntity $entity)
    {
        parent::__construct($entity);
    }

    public function cancelAttention($customerId, $userId)
    {
        return $this->entity->where('customer_id', $customerId)->where('user_id', $userId)->delete();
    }

    public function attention($id, $userId, $flag)
    {
        $data = [
            'customer_id' => $id,
            'user_id'     => $userId,
        ];
        if (!$flag) {
            $this->entity->create($data);
        } else {
            $this->entity->where($data)->delete();
        }
        return true;
    }

    // 获取关注的客户id
    public static function getAttentionIds($userId, array $customerIds = [])
    {
        $query = DB::table(self::TABLE_NAME)->select('customer_id')->where('user_id', $userId);
        if (!empty($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }
        $lists = $query->get();
        if ($lists->isEmpty()) {
            return [];
        }
        return array_merge(array_unique(array_column($lists->toArray(), 'customer_id')));
    }
}
