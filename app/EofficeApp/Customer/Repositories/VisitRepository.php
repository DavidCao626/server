<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\VisitEntity;
use DB;

class VisitRepository extends BaseRepository
{

    // 表名
    const TABLE_NAME = 'customer_will_visit';
    // 提醒人表名
    const TABLE_REMIND = 'customer_will_visit_reminder';
    // 提醒下拉框的标识
    const VISIT_TYPE_MARK = 11;

    public function __construct(VisitEntity $entity)
    {
        parent::__construct($entity);
    }

    public function lists(array $param = [])
    {
        $default = [
            'fields'          => [
                'visit_id', 'customer_id', 'visit_creator',
                'create_time', 'visit_content', 'linkman_id', 'visit_time',
            ],
            'user_fields'     => ['user_id', 'user_name'],
            'customer_fields' => ['customer_id', 'customer_name'],
            'search'          => [],
            'page'            => 0,
            'limit'           => config('eoffice.pagesize'),
            'order_by'        => ['visit_id' => 'desc'],
        ];

        $param          = array_merge($default, array_filter($param));
        $userFields     = $param['user_fields'];
        $customerFields = $param['customer_fields'];

        $query = $this->entity->select($param['fields'])
            ->with(['willVisitUser' => function ($query) use ($userFields) {
                $query->select($userFields);
            }])->with(['willVisitCustomer' => function ($query) use ($customerFields) {
            $query->select($customerFields);
        }])->with(['willVisitLinkman' => function ($query) {
            $query->select(['linkman_id', 'linkman_name']);
        }])->with(['hasManyReminder.hasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->wheres($param['search']);
        return $query->orders($param['order_by'])->forPage($param['page'], $param['limit'])->get()->toArray();
    }

    public function total(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->entity->wheres($where)->count();
    }

    // 获取被关注的客户id
    public static function getHasVisitIds($userId, array $customerIds)
    {
        $query = DB::table(self::TABLE_NAME)->select('customer_id')->where('visit_creator', $userId);
        // 提醒人包含 $userId处理
        $mVisits = DB::table(self::TABLE_REMIND)->where('user_id', $userId)->pluck('visit_id')->toArray();
        $customer_id =  DB::table(self::TABLE_NAME)->whereIn('visit_id',$mVisits)->pluck('customer_id')->toArray();
        if (!empty($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }
        $lists = $query->get();
//        if ($lists->isEmpty()) {
//            return [];
//        }
        $customerId = array_merge(array_unique(array_column($lists->toArray(), 'customer_id')));
        $returnId = array_unique(array_merge($customer_id, $customerId));
        sort($returnId);
        return $returnId;
//        return array_merge(array_unique(array_column($lists->toArray(), 'customer_id')));
    }

    // 获取提醒人或创建人为自己的visitid集合
    public static function getVisitIds($customerId, $userId)
    {
        $visitIds = DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->where('visit_creator', $userId)->pluck('visit_id')->toArray();
        $mVisits = DB::table(self::TABLE_REMIND)->where('user_id', $userId)->pluck('visit_id')->toArray();
        return array_unique(array_merge($visitIds, $mVisits));
    }

    public function getWillVisitDetail($visitId, $where = [])
    {
        $query = $this->entity
            ->with(['willVisitCustomer' => function ($query) {
                $query->select(['customer_id', 'customer_name']);
            }])
            ->with(['willVisitLinkman' => function ($query) {
                $query->select(['linkman_id', 'linkman_name','mobile_phone_number']);
            }])
            ->with(['willVisitUser' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }])
            ->with(['hasManyReminder.hasOneUser' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }]);

        if ($where) {
            $query = $query->whereHas('willVisitCustomer', function ($query) use ($where) {
                $query = $query->customerPermission($where['users'], $where['userInfo']);
            });
        }

        return $query->find($visitId);
    }

    public static function validateInput(array &$input, $id = 0, $own)
    {
        if (!$id) {
            $input['create_time'] = date('Y-m-d H:i:s', time());
        }
        $customerId = $input['customer_id'] ?? '';
        if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return true;
    }

    public static function customerDetailMenuCount($customerId)
    {
        return DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->count();
    }

    public static function insertReminder(array $iData)
    {
        return DB::table(self::TABLE_REMIND)->insert($iData);
    }

    // 客户合并
    public static function mergeToCustomer($targetCustomerId, $customerIds)
    {
        return DB::table(self::TABLE_NAME)->whereIn('customer_id', $customerIds)->update([
            'customer_id' => $targetCustomerId,
        ]);
    }

    public static function validatePermission(array $types, $id, array $own)
    {
        $result = 0;
        $list   = DB::table(self::TABLE_NAME)->select(['visit_creator', 'customer_id'])->where('visit', $id)->first();
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::VIEW_MARK:
                    if ($list->visit_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::UPDATE_MARK:
                    if ($list->visit_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::DELETE_MARK:
                    if ($validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }, $types);
        return $result;
    }

    // 查询当天访问提醒
    public function willVisitRemindLists($interval)
    {
        $start  = date("Y-m-d H:i:s");
        $end    = date("Y-m-d H:i:s", strtotime("+$interval minutes -1 seconds"));
        $search = [
            'visit_time' => [[$start, $end], 'between'],
        ];
        return $this->entity->with(['willVisitCustomer' => function ($query) {
            $query->select(['customer_id', 'customer_name']);
        }])->with(['willVisitLinkman' => function ($query) {
            $query->select(['linkman_id', 'linkman_name', 'mobile_phone_number']);
        }])->with(['hasManyReminder' => function ($query) {
            $query->select(['user_id', 'visit_id']);
        }])->wheres($search)->get()->toArray();
    }

    public static function deleteVisits(array $visitIds)
    {
        if (!$validate = DB::table(self::TABLE_NAME)->whereIn('visit_id', $visitIds)->delete()) {
            return false;
        }
        return self::deleteReminds($visitIds);
    }

    public static function deleteReminds(array $visitIds)
    {
        return DB::table(self::TABLE_REMIND)->whereIn('visit_id', $visitIds)->delete();
    }

    public static function getCustomerIds(array $visitIds)
    {
        $result = [];
        $lists = DB::table(self::TABLE_NAME)->select(['customer_id'])->whereIn('visit_id', $visitIds)->get();
        if (!$lists->isEmpty()) {
            $result = array_column($lists->toArray(), 'customer_id');
        }
        return $result;
    }

    public static function getCustomerList(array $visitIds){
        $lists = DB::table(self::TABLE_NAME)->select('*')->whereIn('visit_id', $visitIds)->get();
        return $lists;
    }
}
