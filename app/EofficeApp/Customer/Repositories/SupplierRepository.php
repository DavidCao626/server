<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\SupplierEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use DB;

class SupplierRepository extends BaseRepository
{

    const TABLE_NAME = 'customer_supplier';

    public function __construct(SupplierEntity $entity)
    {
        parent::__construct($entity);
    }

    public static function getPermissionIds($types, $own, $ids = [])
    {
        $result = [];
        $query  = DB::table(self::TABLE_NAME)->select(['supplier_id', 'supplier_creator']);
        if (!empty($ids)) {
            $query = $query->whereIn('supplier_id', $ids);
        }
        $lists = $query->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        $userId = $own['user_id'] ?? '';
        array_map(function ($type) use (&$result, $lists, $userId) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    $result[$type] = [];
                    // 编辑权限,创建人
                    foreach ($lists as $index => $item) {
                        if ($item->supplier_creator == $userId) {
                            $result[$type][] = $item->supplier_id;
                        }
                    }
                    break;

                case CustomerRepository::DELETE_MARK:
                    $result[$type] = [];
                    // 编辑权限,创建人
                    foreach ($lists as $index => $item) {
                        if ($item->supplier_creator == $userId) {
                            $result[$type][] = $item->supplier_id;
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
        $list   = DB::table(self::TABLE_NAME)->select(['supplier_creator'])->where('supplier_id', $id)->first();
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    if ($list->supplier_creator == $own['user_id']) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::DELETE_MARK:
                    if ($list->supplier_creator == $own['user_id']) {
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
        return (bool) DB::table('customer_product')->whereIn('supplier_id', $ids)->exists();
    }

    public static function updateSupplier(int $supplierId, array $data)
    {
        return DB::table(self::TABLE_NAME)->where('supplier_id', $supplierId)->update($data);
    }

    /* 检测是否供应商名称已存在*/
    public static function checkRepeatName($supplier_name,$supplier_id = null){
        $query = DB::table(self::TABLE_NAME)->where('supplier_name',$supplier_name);
        if($supplier_id){
            $query = $query->where('supplier_id','!=',$supplier_id);
        }
        return $query->first();
    }

    /* 获取供应商id*/
    public static function getSupplierSingleFields($field,$search = []){
        $query = DB::table(self::TABLE_NAME)->pluck($field);
        if($search){
            $query = app('App\EofficeApp\System\CustomFields\Repositories\FieldsRepository')->wheres($search);
        }
        return $query->toArray();
    }
}
