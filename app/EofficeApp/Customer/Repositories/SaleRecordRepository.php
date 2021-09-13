<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\SalesRecordEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use DB;

class SaleRecordRepository extends BaseRepository
{

    const TABLE_NAME = 'customer_sales_record';

    private $randNumber;

    public function __construct(SalesRecordEntity $entity)
    {
        parent::__construct($entity);
    }

    public function lists(array $param = [])
    {
        $default = [
            'fields'          => ['*'],
            'user_fields'     => ['user_id', 'user_name'],
            'customer_fields' => ['customer_id', 'customer_name', 'customer_manager', 'customer_service_manager'],
            'product_fields'  => ['product_id', 'product_name'],
            'search'          => [],
            'page'            => 0,
            'limit'           => config('eoffice.pagesize'),
            'order_by'        => ['sales_id' => 'desc'],
        ];

        $param          = array_merge($default, array_filter($param));
        $userFields     = $param['user_fields'];
        $customerFields = $param['customer_fields'];
        $productFields  = $param['product_fields'];
        $query = $this->entity->select($param['fields'])->with(['salesRecordToUser' => function ($query) use ($userFields) {
            $query->select($userFields);
        }])->with(['salesRecordToCustomer' => function ($query) use ($customerFields) {
            $query->select($customerFields);
        }])->with(['salesRecordToProduct' => function ($query) use ($productFields) {
            $query->select($productFields);
        }]);
        $query = $this->parseSalesRecordsWhere($query, $param['search']);
        return $query->orders($param['order_by'])->forPage($param['page'], $param['limit'])->get()->toArray();
    }

    public function total(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->parseSalesRecordsWhere($this->entity, $where)->count();
    }

    public static function getPermissionIds(array $types, array $own, array $ids)
    {
        $result = [];
        $query  = DB::table(self::TABLE_NAME)->select(['sales_id', 'customer_id', 'sales_creator']);
        if (!empty($ids)) {
            $query = $query->whereIn('sales_id', $ids);
        }
        $lists = $query->get();
        if ($lists->isEmpty()) {
            return array_pad($result, count($types), []);
        }
        $userId = $own['user_id'] ?? '';
        array_map(function ($type) use (&$result, $own, $ids, $lists, $userId) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    $result[$type] = [];
                    // 编辑权限,对客户具有编辑权限 + 创建人
                    $customerIds = [];
                    foreach ($lists as $index => $item) {
                        // 创建人
                        if ($item->sales_creator == $userId) {
                            $result[$type][] = $item->sales_id;
                            continue;
                        }
                        if (!isset($customerIds[$item->customer_id])) {
                            $customerIds[$item->customer_id] = [];
                        }
                        $customerIds[$item->customer_id][] = $item->sales_id;
                    }
                    if (!empty($customerIds)) {
                        $updateCustomerIds = CustomerRepository::getUpdateIds($own, array_keys($customerIds));
                        if (!empty($updateCustomerIds)) {
                            foreach ($customerIds as $iCustomerId => $iLinkmanIds) {
                                if (in_array($iCustomerId, $updateCustomerIds)) {
                                    $result[$type] = array_merge($result[$type], $iLinkmanIds);
                                }
                            }
                        }
                    }
                    break;

                default:
                    break;
            }
        }, $types);
        return array_merge($result);
    }

    public function parseSalesRecordsWhere($query, array $where = [])
    {
        if (isset($where['product_name'])) {
            $productName['product_name'] = $where['product_name'];
            $query                       = $query->whereHas('salesRecordToProduct', function ($query) use ($productName) {
                $query->wheres($productName);
            });
            unset($where['product_name']);
        }
        if (isset($where['customer_name'])) {
            $customerName['customer_name'] = $where['customer_name'];
            $query                         = $query->whereHas('salesRecordToCustomer', function ($query) use ($customerName) {
                $query->wheres($customerName);
            });
            unset($where['customer_name']);
        }
        if (isset($where['customer_ids'])) {
            $max_length = 300;
            if (count($where['customer_ids']) < 300) {
                $query = $query->whereIn('customer_id', $where['customer_ids']);
            } else {
                $this->randNumber = rand() . uniqid();
                $tableName        = 'customer_sales_record' . $this->randNumber;
                DB::statement("CREATE TEMPORARY TABLE  if not exists $tableName (`data_id` int(11) NOT NULL,PRIMARY KEY (`data_id`))");
                DB::table("$tableName")->truncate();
                $temp_sql = "insert into $tableName (data_id) values ";
                foreach ($where['customer_ids'] as $k => $v) {
                    $temp_sql .= "($v),";
                }
                $temp_sql = mb_substr($temp_sql, 0, -1);
                DB::insert($temp_sql);
                $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
            }
            unset($where['customer_ids']);
        }
        $whereValues = $where['customer_id'][0] ?? [];
        if (!empty($whereValues) && count($whereValues) > CustomerRepository::MAX_WHERE_IN) {
            $tableName = 'customer_sales_record'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(11) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($whereValues, CustomerRepository::MAX_WHERE_IN, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
            unset($where['customer_id']);
        }
        return $query->wheres($where)->has('salesRecordToCustomer')->has('salesRecordToProduct');
    }

    public static function validateInput(array &$input, $id = 0, $own = [])
    {
        $customerId   = $input['customer_id'] ?? 0;
        $productId    = $input['product_id'] ?? 0;
        $salesDate    = $input['sales_date'] ?? '';
        $salesAmount  = $input['sales_amount'] ?? '';
        $salesPrice   = $input['sales_price'] ?? '';
        $salesMan     = $input['salesman'] ?? '';
        $discount     = $input['discount'] ?? 0;
        $salesRemarks = $input['sales_remarks'] ?? '';
        $salesCreator = $input['sales_creator'] ?? 0;
        if (!$customerId) {
            return ['code' => ['0x024026', 'customer']];
        }
        if (!$productId) {
            return ['code' => ['0x024029', 'customer']];
        }
        if ($discount && !is_numeric($discount)) {
            return ['code' => ['0x024002', 'customer']];
        }
        $input = [
            'customer_id'   => $customerId,
            'product_id'    => $productId,
            'sales_date'    => $salesDate,
            'sales_amount'  => $salesAmount,
            'sales_price'   => $salesPrice,
            'salesman'      => $salesMan,
            'discount'      => $discount,
            'sales_remarks' => $salesRemarks,
        ];
        if (!$id) {
            $input['sales_creator'] = $salesCreator;
        }
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return true;
    }

    public function show(int $id)
    {
        return $this->entity->with(['salesRecordToCustomer' => function ($query) {
            $query->select(['customer_id', 'customer_name']);
        }])->with(['salesRecordToProduct' => function ($query) {
            $query->select(['product_id', 'product_name']);
        }])->with(['salesRecordToUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->find($id);
    }

    public static function validatePermission(array $types, $id, array $own)
    {
        $result = 0;
        $list   = DB::table(self::TABLE_NAME)->select(['sales_creator', 'customer_id'])->where('sales_id', $id)->first();
        if (empty($list)) {
            return $result;
        }
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::VIEW_MARK:
                    if ($list->sales_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::UPDATE_MARK:
                    if ($list->sales_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
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

    public static function getProductName($sale_ids){
        $query = DB::table(self::TABLE_NAME)
            ->select('customer_sales_record.sales_id','customer_sales_record.product_id','customer_product.product_name');
        if($sale_ids){
            $query = $query->whereIn('sales_id', $sale_ids);
        };
        $query = $query->where('customer_sales_record.product_id','!=',0)->leftJoin('customer_product','customer_product.product_id','=','customer_sales_record.product_id');
        return $query->get()->toArray();
    }
}
