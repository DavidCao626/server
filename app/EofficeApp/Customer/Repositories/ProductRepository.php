<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\ProductEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use DB;

class ProductRepository extends BaseRepository
{

    const TABLE_NAME = 'customer_product';

    public function __construct(ProductEntity $entity)
    {
        parent::__construct($entity);
    }

    public function lists(array $param = [])
    {
        $default = [
            'fields'          => [
                'product_id', 'product_name', 'product_type',
                'measure_unit', 'cost_price', 'sale_price',
                'product_id', 'product_creator',
            ],
            'user_fields'     => ['user_id', 'user_name'],
            'supplier_fields' => ['product_id', 'supplier_name'],
            'search'          => [],
            'page'            => 0,
            'limit'           => config('eoffice.pagesize'),
            'order_by'        => ['product_id' => 'desc'],
        ];

        $param          = array_merge($default, array_filter($param));
        $userFields     = $param['user_fields'];
        $supplierFields = $param['supplier_fields'];

        $query = $this->entity->select($param['fields'])->with(['productCreatorToUser' => function ($query) use ($userFields) {
            $query->select($userFields);
        }])->with(['productToSupplier' => function ($query) use ($supplierFields) {
            $query->select($supplierFields);
        }])->with(['hasManySales' => function ($query) {
            $query->select('product_id')->selectRaw("count('sales_id') as sales_num")->groupBy('product_id');
        }]);
        $query = $this->getProductsParseWhere($query, $param['search']);
        return $query->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get()->toArray();
    }

    public function total(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getProductsParseWhere($this->entity, $where)->count();
    }

    public function getProductsParseWhere($query, array $where = [])
    {
        if (isset($where['supplier_name'])) {
            $supplierName['supplier_name'] = $where['supplier_name'];
            $query                         = $query->whereHas('productToSupplier', function ($query) use ($supplierName) {
                $query->wheres($supplierName);
            });
            unset($where['supplier_name']);
        }
        return $query->wheres($where);
    }

    public static function getPermissionIds(array $types, $own, $ids = [])
    {
        $result = [];
        $query  = DB::table(self::TABLE_NAME)->select(['product_id', 'product_creator']);
        if (!empty($ids)) {
            $query = $query->whereIn('product_id', $ids);
        }
        $lists = $query->get();
        if ($lists->isEmpty()) {
            return array_pad($result, count($types), []);
        }
        $userId = $own['user_id'] ?? '';
        array_map(function ($type) use (&$result, $lists, $userId) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    $result[$type] = [];
                    // 编辑权限,创建人
                    foreach ($lists as $index => $item) {
                        if ($item->product_creator == $userId) {
                            $result[$type][] = $item->product_id;
                        }
                    }
                    break;

                case CustomerRepository::DELETE_MARK:
                    $result[$type] = [];
                    // 编辑权限,创建人
                    foreach ($lists as $index => $item) {
                        if ($item->product_creator == $userId) {
                            $result[$type][] = $item->product_id;
                        }
                    }
                    break;

                default:
                    break;
            }
        }, $types);
        return array_merge($result);
    }

    /**
     * 只有自己新建的供应商才能够删除和编辑
     */
    public static function validatePermission(array $types, $id, array $own)
    {
        $result = 0;
        $list   = DB::table(self::TABLE_NAME)->select(['product_creator'])->where('product_id', $id)->first();
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    if ($list->product_creator == $own['user_id']) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::DELETE_MARK:
                    if ($list->product_creator == $own['user_id']) {
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

    /**
     * 是否被销售记录占用，不可删除
     */
    public static function validateDeletes(array $ids): bool
    {
        return (bool) DB::table('customer_sales_record')->whereIn('product_id', $ids)->exists();
    }
}
