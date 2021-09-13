<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\ContactRecordEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use DB;

class ContactRecordRepository extends BaseRepository
{

    const TABLE_NAME = 'customer_contact_record';
    // 联系记录回复表
    const COMMENT_TABLE = 'customer_contact_record_comment';
    const TABLE_USER = 'user';
    const TABLE_LINKMAN = 'customer_linkman';
    const TABLE_CUSTOMER = 'customer';

    public function __construct(ContactRecordEntity $entity)
    {
        parent::__construct($entity);
    }

    public function lists(array $params)
    {
        $default = [
            'fields'      => ['customer_id', 'record_id', 'record_content', 'record_start', 'record_end', 'linkman_id', 'record_type', 'record_creator', 'created_at', 'deleted_at','address'],
            'search'      => [],
            'page'        => 0,
            'limit'       => config('eoffice.pagesize'),
            'order_by'    => ['record_id' => 'desc'],
            'withComment' => 0,
        ];
        $params = array_merge($default, array_filter($params));
        $query  = $this->entity->select($params['fields'])->withTrashed()->with(['contactRecordCreator' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->with(['contactRecordCustomer' => function ($query) {
            $query->select(['customer_id', 'customer_name','customer_logo']);
        }])->with(['contactRecordLinkman' => function ($query) {
            $query->select(['linkman_id', 'linkman_name']);
        }]);
        if ($params['withComment']) {
            $query = $query->with(['HasManyComment' => function ($query) {
                $query->orderBy('comment_id', 'desc')->with('commentHasOneUser');
            }]);
        }
        $query = $this->tempTableJoin($query, $params['search']);
        $query = $this->getContactRecordParseWhere($query, $params['search']);
        return $query->wheres($params['search'])->orders($params['order_by'])->forPage($params['page'], $params['limit'])->get();
    }

    public function show($id)
    {
        return $this->entity->with(['contactRecordCustomer' => function ($query) {
            $query->select(['customer_id', 'customer_name']);
        }])->with(['contactRecordLinkman' => function ($query) {
            $query->select(['linkman_id', 'linkman_name']);
        }])->with(['contactRecordCreator' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->withTrashed()->find($id);
    }

    public function total(array $params)
    {
        $default = [
            'search' => [],
        ];
        $params = array_merge($default, array_filter($params));
        $query  = $this->entity->withTrashed();
        $query = $this->tempTableJoin($query, $params['search']);
        $query = $this->getContactRecordParseWhere($query, $params['search']);
        return $query->wheres($params['search'])->count();
    }

    // sql 太长导致mysql gone away
    public function tempTableJoin($query, &$searchs)
    {
        $whereValues = $searchs['customer_id'][0] ?? [];
        if (!empty($whereValues) && is_array($whereValues) && count($whereValues) > CustomerRepository::MAX_WHERE_IN) {
            $tableName = 'customer_contact_record'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($whereValues, CustomerRepository::MAX_WHERE_IN, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
            unset($searchs['customer_id']);
        }
        return $query;
    }

    public static function validateInput(array &$input, array $own)
    {
        if ((!isset($input['record_content']) || !$input['record_content']) && (!isset($input['attachments']) || count($input['attachments']) === 0)) {
            return ['code' => ['0x024018', 'customer']];
        }
        if (!isset($input['customer_id']) || !$input['customer_id']) {
            return ['code' => ['0x024019', 'customer']];
        }
        if (isset($input['time'])) {
            if (is_array($input['time']) && count($input['time']) === 2) {
                $recordTime = array_filter(array_values($input['time']));
                if (count($recordTime) === 2) {
                    list($input['record_start'], $input['record_end']) = array_values($input['time']);
                }
            }
            unset($input['time']);
        }
        $customerId = $input['customer_id'] ?? '';
        if(is_array($customerId)){
            foreach ($customerId as $key => $vo) {
                if($vo && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$vo], $own)){
                    return ['code' => ['0x024003', 'customer']];
                }
            }
        }else{
           if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
                return ['code' => ['0x024003', 'customer']];
            } 
        }
        return true;
    }

    public function customerContactRecords(int $customerId, $currentPage)
    {
        $query  = $this->entity->select(['record_id', 'record_content', 'record_start', 'record_end', 'linkman_id', 'record_type', 'record_creator', 'created_at', 'address', 'deleted_at'])->withTrashed()->where('customer_id', $customerId);
        $offset = ($currentPage - 1) * 10;
        return $query->with(['contactRecordCreator' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->with(['contactRecordLinkman' => function ($query) {
            $query->select(['linkman_id', 'linkman_name']);
        }])->with(['HasManyComment' => function ($query) {
            $query->orderBy('comment_id', 'desc')->with('commentHasOneUser');
        }])->orderBy('record_id', 'desc')->offset($offset)->limit(10)->get()->toArray();
    }

    public function customerContactRecordTotals(int $customerId, $currentPage)
    {
        return $this->entity->withTrashed()->where('customer_id', $customerId)->count();
    }

    public function getCountIds(array $linkmanIds)
    {
        $result = [];
        $lists  = $this->entity->select(['linkman_id'])->whereIn('linkman_id', $linkmanIds)->get();
        if (!$lists->isEmpty()) {
            foreach ($lists as $index => $item) {
                if (!isset($result[$item->linkman_id])) {
                    $result[$item->linkman_id] = 0;
                }
                ++$result[$item->linkman_id];
            }
        }
        return $result;
    }

    public static function validatePermission(array $types, $id, array $own)
    {
        $result = 0;
        $list   = DB::table(self::TABLE_NAME)->select(['record_creator', 'customer_id'])->where('record_id', $id)->first();
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::VIEW_MARK:
                    if ($list->record_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::UPDATE_MARK:
                    if ($list->record_creator == $own['user_id']) {
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

    // 客户合并
    public static function mergeToCustomer($targetCustomerId, $customerIds)
    {
        return DB::table(self::TABLE_NAME)->whereIn('customer_id', $customerIds)->update([
            'customer_id' => $targetCustomerId,
        ]);
    }

    public function getContactRecordParseWhere($query, array &$where = [])
    {
        $ids = [];
        if (isset($where['creator'])) {
            $tName = $where['creator'][0];
            $lists = DB::table(self::TABLE_USER)->select(['user_id'])->where('user_name', 'like', '%' . $tName . '%')->get();
            if (!$lists->isEmpty()) {
                $ids = array_column($lists->toArray(), 'user_id');
            }
            $where['record_creator'] = [$ids, 'in'];
            unset($where['creator']);
        }
        if (isset($where['customer'])) {
            $tName = $where['customer'][0];
            $lists = DB::table(self::TABLE_CUSTOMER)->select(['customer_id'])->where('customer_name', 'like', '%' . $tName . '%')->get();
            if (!$lists->isEmpty()) {
                $ids = array_column($lists->toArray(), 'customer_id');
                $originIds = isset($where['customer_id'][0]) ? $where['customer_id'][0] : [];
                $originIds && $ids = array_intersect($ids, $originIds);
            }
            $where['customer_id'] = [$ids, 'in'];
            unset($where['customer']);
        }

        if (isset($where['linkman'])) {
            $tName = $where['linkman'][0];
            $lists = DB::table(self::TABLE_LINKMAN)->select(['linkman_id'])->where('linkman_name', 'like', '%' . $tName . '%')->get();
            if (!$lists->isEmpty()) {
                $ids = array_column($lists->toArray(), 'linkman_id');
            }
            $where['linkman_id'] = [$ids, 'in'];
            unset($where['linkman']);
        }
        return $query->wheres($where);
    }
}
