<?php

namespace App\EofficeApp\Product\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Product\Entities\ProductEntity;
use DB;

/**
 * 产品Repository类
 *
 * @author 牛晓克
 *
 * @since  2017-12-12 创建
 */
class ProductRepository extends BaseRepository
{
    public function __construct(ProductEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getAllSimpleProducts($fields = ['*'], $productIds = [], $withTrashed = false)
    {
        $query = $this->entity->select($fields);
        if(!empty($productIds)) {
             $query = $query->whereIn('product_id', $productIds);
        }
        if($withTrashed) {
            $query = $query->withTrashed();
        }
        return $query->get();
    }
    public function getProductByNumbers($numbers = [], $fields = ['*'], $withTrashed = false)
    {
        $query = $this->entity->select($fields)->whereIn('product_number', $numbers);
        if ($withTrashed) {
            $query = $query->withTrashed();
        };
        return $query->get();
    }
    /**
     * 获取产品列表
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getProductList($param, $fields = ['*'])
    {
        $query = $this->entity->select($fields);

        if (isset($param['with'])) {
        	$query->with('productToProductType');
        }
        if (isset($param['search']['multiSearch'])) {
            $multiSearchs = $param['search']['multiSearch'];
            unset($param['search']['multiSearch']);
            $query = $query->multiWheres($multiSearchs);
        }

        if (isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }

        if (isset($param['withTrashed']) && $param['withTrashed']) {
            $query = $query->withTrashed();
        }
        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : ['created_at' => 'desc','product_id'=>'desc'];
        if (isset($param['order_by'])) {
            if (is_string($param['order_by'])) {
                $param['order_by'] = json_decode($param['order_by'], true);
            }
            $query->orders($param['order_by']);
        }

        if (isset($param['page']) && isset($param['limit'])) {
            $query->parsePage($param['page'], $param['limit']);
        }
        return $query->get();
    }

    /**
     * 获取产品总数
     *
     * @return int
     *
     * @author 施奇
     *
     * @since 2018-08-20
     */
    public function getProductTotal($param)
    {
        $query = $this->entity->select('*');
        if (isset($param['search']['multiSearch'])) {
            $multiSearchs = $param['search']['multiSearch'];
            unset($param['search']['multiSearch']);
            $query = $query->multiWheres($multiSearchs);
        }
        if (isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }

        return $query->count();
    }

    /**
     * 获取产品预警值
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getAllProductAlertValueMap($productId, $type)
    {
        $field = $type == 1 ? 'product_alert_max' : 'product_alert_min';
        $query = $this->entity->select(['product_id', $field])->where($field, '!=', 0);

        if ($productId) {
            $query->whereIn('product_id', $productId);
        }

        return $query->get();
    }

    public function getProdyctUnit()
    {
    	return DB::table('custom_fields_table')->select('field_options')->where('field_table_key', 'product')->where('field_code','product_unit')->first();
    }

    public function getMaxProductNumber()
    {
        return $this->entity->select('product_number')->whereRaw("product_number REGEXP '^CP[0-9]{5}'")->withTrashed()->orderBy("product_number", "desc")->first();
    }

    public function productInContract($productId)
    {
        return DB::table("contract_t_order")->select("id")->where("product_id", $productId)->count();
    }

    public static function getProductByParentId($productTypeId){
        return DB::table("product")->where('product_type_id',$productTypeId)->whereNull('deleted_at')->first();
    }

    /**
     * 获取产品详情
     *
     * @return array
     *
     * @author zw
     *
     * @since 2020-05-25
     */
    public static function getProductDetail($where){
        return DB::table("product")->where($where)->whereNull('deleted_at')->first();
    }
}
