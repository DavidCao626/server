<?php

namespace App\EofficeApp\Product\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Product\Entities\ProductTypeEntity;

/**
 * 产品类别Repository类
 *
 * @author 牛晓克
 *
 * @since  2017-12-12 创建
 */
class ProductTypeRepository extends BaseRepository
{
    public function __construct(ProductTypeEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取产品分类列表
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function getProductTypeByWhere($param, $fields = ['*'])
    {
    	$query = $this->entity->select($fields);

    	if(isset($param['search']) && !empty($param['search'])) {
			$query->wheres($param['search']);
		}

        if (isset($param['page']) && isset($param['limit'])) {
            $query->parsePage($param['page'], $param['limit']);
        }

		return $query->orderBy('product_type_sort','ASC')->get();
    }

    public function getProductTypeTotal($param)
    {
        $query = $this->entity;

        if(isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }

        return $query->count();
    }

    /**
     * 根据父级id获取产品分类列表
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function getProductTypeByParentId($parentId, $fields = ['*'])
    {
        return $this->entity->select($fields)->where('product_type_parent', $parentId)->get();
    }

    public function getProductTypeListOnSelect($param, $fields = ['*'])
    {
        $query = $this->entity->select($fields);

        if(isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }

        if (isset($param['page']) && isset($param['limit'])) {
            $query->parsePage($param['page'], $param['limit']);
        }

        return $query->orderBy('product_type_level','ASC')->orderBy('product_type_sort','ASC')->get();
    }

    public function getProductTypeParentId($typeId, $path = [])
    {
        $productType = $this->entity->where('product_type_id', $typeId)->first();
        if (!$productType) {
            return $path;
        }
        $path[] = $productType->product_type_id;
        if ($productType->product_type_parent != 0) {
            return $this->getProductTypeParentId($productType->product_type_parent, $path);
        } else {
            array_push($path, 0);
            unset($path[0]);
            $path = implode(',', array_reverse($path));
            return $path;
        }
    }
}
