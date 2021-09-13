<?php

namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\PermissionEntity;
use App\EofficeApp\Customer\Services\CustomerService;
use DB;

class PermissionRepository extends BaseRepository
{

    const TABLE_NAME = 'customer_apply_permission';
    const APPLY_SHARE = 1;
    const APPLY_MANAGER = 3;
    const APPLY_SERVICE = 2;

    public function __construct(PermissionEntity $entity)
    {
        parent::__construct($entity);
    }

    public function hasApply($type, $customerId, $userId)
    {
        return (bool) $this->entity->where('proposer', $userId)->where('customer_id', $customerId)->where('apply_permission', $type)->where('apply_status', 1)->exists();
    }

    public function lists(array $param = [])
    {
        $default = [
            'fields'   => [
                'apply_id', 'apply_permission',
                'apply_status', 'created_at',
                'customer_id',
                'customer_name',
                'view_permission',
                'customer_manager_name',
                'apply_permission_to_user_name',
            ],
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['apply_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity->parseSelect($param['fields'], $this->entity);
        $query = $this->parseWheres($query, $param['search']);
        return $query->parseOrderBy($param['order_by'], $this->entity)->forPage($param['page'], $param['limit'])->get()->toArray();
    }

    public function total(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->parseWheres($this->entity, $where)->count();
    }

    public function parseWheres($query, array $where = [])
    {
        if (isset($where['customer_name'])) {
            $searchCustomer = ['customer_name' => $where['customer_name']];
            $query          = $query->whereHas('applyPermissionToCustomer', function ($query) use ($searchCustomer) {
                $query->wheres($searchCustomer);
            });
            unset($where['customer_name']);
        }

        if (isset($where['softCustomerIds']) && !empty($where['softCustomerIds'])) {
            $query = $query->whereNotIn('customer_id', $where['softCustomerIds']);
            unset($where['softCustomerIds']);
        }
        return $query->wheres($where);
    }

    public function getApplyPermissionDetail(array $where = [])
    {
        return $this->entity->wheres($where)->with(['user' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->first();
    }

    public function show($applyId)
    {
        return $this->entity->where('apply_id', $applyId)->with(['user' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->with(['customer' => function ($query) {
            $query->select(['customer_id', 'customer_name']);
        }])->first();
    }

    public function getColumnLists(array $applyIds)
    {
        return $this->entity->select(['proposer', 'customer_id', 'apply_permission'])->with(['applyPermissionToCustomer' => function ($query) {
            $query->select(['customer_id', 'customer_name']);
        }])->with(['user' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->whereIn('apply_id', $applyIds)->get();
    }

    public function audit($status, $applyIds, $reason)
    {
        return $this->entity->whereIn('apply_id', $applyIds)->update(['apply_status' => $status, 'reason' => $reason]);
    }

    // 验证申请权限输入
    public static function validateApplyPermissionInput(array &$input, $customerId, $userId)
    {
        $input['proposer']     = $userId;
        $input['customer_id']  = $customerId;
        $input['apply_status'] = CustomerService::APPLY_STATUS_ORIGIN;
        return true;
    }

    // 获取已经是申请，但是未通过的申请id集合
    public static function getAlreadyApplyIds($userId, array $customerIds)
    {
        $result = [];
        $lists  = DB::table(self::TABLE_NAME)->select(['customer_id', 'apply_permission'])->where('proposer', $userId)->whereIn('customer_id', $customerIds)->where('apply_status', 1)->get();
        if ($lists->isEmpty()) {
            return array_pad($result, 3, []);
        }
        $result = [
            self::APPLY_SHARE   => [],
            self::APPLY_MANAGER => [],
            self::APPLY_SERVICE => [],
        ];
        foreach ($lists as $index => $item) {
            if ($item->apply_permission == self::APPLY_SHARE) {
                $result[self::APPLY_SHARE][] = $item->customer_id;
            }
            if ($item->apply_permission == self::APPLY_MANAGER) {
                $result[self::APPLY_MANAGER][] = $item->customer_id;
            }
            if ($item->apply_permission == self::APPLY_SERVICE) {
                $result[self::APPLY_SERVICE][] = $item->customer_id;
            }
        }
        return array_merge($result);
    }
}
