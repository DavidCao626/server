<?php

namespace App\EofficeApp\Product\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Product\Repositories\ProductRepository;
use DB;

/**
 * 产品管理service类，用来调用所需资源
 *
 * @author 牛晓克
 *
 * @since  2017-12-12 创建
 */
class ProductService extends BaseService
{
    private $productRepository;

    private $productTypeRepository;
    private $attachmentService;
    private $fieldsService;

    public function __construct()
    {
        parent::__construct();
        $this->productRepository = 'App\EofficeApp\Product\Repositories\ProductRepository';
        $this->productTypeRepository = 'App\EofficeApp\Product\Repositories\ProductTypeRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    }

    /**
     * 新增产品分类
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function addProductType($data)
    {

        $parentId = isset($data['parent']['product_type_id']) ? $data['parent']['product_type_id'] : 0;
        $hasChildren = isset($data['parent']['has_children']) ? $data['parent']['has_children'] : 0;
        $level = isset($data['parent']['product_type_level']) ? (int)$data['parent']['product_type_level'] + 1 : 1;

        $insertData = [];

        foreach ($data['type_name'] as $key => $value) {
            $insertData[] = [
                "product_type_name" => trim($value),
                "product_type_parent" => $parentId,
                "product_type_level" => $level,
            ];
        }

        if (!app($this->productTypeRepository)->insertMultipleData($insertData)) {
            return ['code' => ['0x000003', 'common']];
        }

        if ($hasChildren == 0) {
            $where = ['product_type_id' => $parentId];
            $updateData = ['has_children' => 1];
            app($this->productTypeRepository)->updateData($updateData, $where);
        }

        return true;
    }

    /**
     * 编辑产品分类
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function editProductType($productTypeId, $data)
    {
        $typeName = $data['product_type_name'];
        $where = ['product_type_id' => $productTypeId];
        $updateData = ['product_type_name' => trim($typeName)];

        if (!app($this->productTypeRepository)->updateData($updateData, $where)) {
            return ['code' => ['0x000003', 'common']];
        }

        return true;
    }

    /**
     * 删除产品分类
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function deleteProductType($productTypeId)
    {
        // 该分类是否有子分类
        if ($this->hasChildrenTypes($productTypeId)) {
            return ['code' => ['0x048001', 'product']];
        }

        // 该分类下是否有产品
        if ($this->hasProductsOfType($productTypeId)) {
            return ['code' => ['0x048002', 'product']];
        }
        // 父级
        $detail = $this->getParentType($productTypeId);
        $parentId = isset($detail['product_type_parent']) ? $detail['product_type_parent'] : 0;

        if (!app($this->productTypeRepository)->deleteById($productTypeId)) {
            return ['code' => ['0x000003', 'common']];
        }

        if ($parentId != 0) {
            $param = ["search" => ["product_type_parent" => [$parentId]]];
            $record = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_id']);
            if (count($record) == 0) {
                if (!app($this->productTypeRepository)->updateData(["has_children" => 0], ['product_type_id' => $parentId])) {
                    return ['code' => ['0x000003', 'common']];
                }
            }
        }

        return true;
    }

    /**
     * 分类是否有子分类
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function hasChildrenTypes($productTypeId)
    {
        $typeArray = $this->getAllChildProductTypeId($productTypeId, false);

        if (count($typeArray) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 分类下是否有产品
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function hasProductsOfType($productTypeId)
    {
        $typeArray = $this->getAllChildProductTypeId($productTypeId, true);
        $param = ["search" => ["product_type_id" => [$typeArray, "in"]]];
        $productArray = app($this->productRepository)->getProductList($param, ['product_id']);

        if (count($productArray) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 产品分类转移
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function typeMigrate($productTypeId, $parentId)
    {
        $where = ['product_type_id' => $productTypeId];
        $param = [
            "search" => [
                "product_type_id" => [$parentId],
            ],
        ];

        $level = 1;

        if ($parentId != 0) {
            $record = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_level']);
            if (isset($record[0]) && isset($record[0]['product_type_level'])) {
                if ($record[0]['product_type_level'] > 2) {
                    return ['code' => ['0x048003', 'product']];
                } else {
                    $level = (int)$record[0]['product_type_level'] + 1;
                }
            } else {
                return ['code' => ['0x000003', 'common']];
            }
        }
        // 更新前,获取分类信息备用
        $typeInfo = app($this->productTypeRepository)->getProductTypeByWhere(["search" => ["product_type_id" => [$productTypeId]]]);

        $updateData = ['product_type_parent' => $parentId, "product_type_level" => $level];

        if (!app($this->productTypeRepository)->updateData($updateData, $where)) {
            return ['code' => ['0x000003', 'common']];
        }

        // 新父级has_children更新
        if ($parentId != 0) {
            if (!app($this->productTypeRepository)->updateData(["has_children" => 1], ['product_type_id' => $parentId])) {
                return ['code' => ['0x000003', 'common']];
            }
        }
        // 原父级has_children更新
        if (isset($typeInfo[0]) && isset($typeInfo[0]['product_type_parent'])) {
            $parent = $typeInfo[0]['product_type_parent'];
            $record = app($this->productTypeRepository)->getProductTypeByParentId($parent, ['product_type_id']);
            if (count($record) == 0) {
                $update = ['has_children' => 0];
                $where = ['product_type_id' => $parent];
                if (!app($this->productTypeRepository)->updateData($update, $where)) {
                    return ['code' => ['0x000003', 'common']];
                }
            }
        }

        return true;
    }

    /**
     * 产品批量转移
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function batchMoveType($productTypeId, $parentId)
    {
        $fromIds = explode(',', rtrim($productTypeId, ','));

        // 更新前,获取分类信息备用
        $typeInfo = app($this->productTypeRepository)->getProductTypeByWhere(["search" => ["product_type_id" => [$fromIds[0]]]]);

        foreach ($fromIds as $value) {
            $where = ['product_type_id' => $value];
            $param = [
                "search" => [
                    "product_type_id" => [$parentId],
                ],
            ];
            $level = 1;

            if ($parentId != 0) {
                $record = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_level']);
                if (isset($record[0]) && isset($record[0]['product_type_level'])) {
                    if ($record[0]['product_type_level'] > 2) {
                        return ['code' => ['0x048003', 'product']];
                    } else {
                        $level = (int)$record[0]['product_type_level'] + 1;
                    }
                } else {
                    return ['code' => ['0x000003', 'common']];
                }
            }

            $updateData = ['product_type_parent' => $parentId, "product_type_level" => $level];

            if (!app($this->productTypeRepository)->updateData($updateData, $where)) {
                return ['code' => ['0x000003', 'common']];
            }
        }

        // 父级has_children更新
        if ($parentId != 0) {
            if (!app($this->productTypeRepository)->updateData(["has_children" => 1], ['product_type_id' => $parentId])) {
                return ['code' => ['0x000003', 'common']];
            }
        }

        // 原父级has_children更新
        if (isset($typeInfo[0]) && isset($typeInfo[0]['product_type_parent'])) {
            $parent = $typeInfo[0]['product_type_parent'];
            $record = app($this->productTypeRepository)->getProductTypeByParentId($parent, ['product_type_id']);
            if (count($record) == 0) {
                $update = ['has_children' => 0];
                $where = ['product_type_id' => $parent];
                if (!app($this->productTypeRepository)->updateData($update, $where)) {
                    return ['code' => ['0x000003', 'common']];
                }
            }
        }

        return true;
    }

    /**
     * 删除产品
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function deleteProduct($productId)
    {
        // 判断合同订单是否有该产品
        $ids = explode(',',trim($productId,','));
        foreach ($ids as $key => $id){
            $productInContract = app($this->productRepository)->productInContract($id);
            if ($productInContract > 0) {
                return ['code' => ['0x048004', 'product']];
            }
        }
        if (!app($this->productRepository)->deleteById($ids)) {
            return ['code' => ['0x000003', 'common']];
        }

        return true;
    }

    /**
     * 产品批量删除
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function batchDeleteType($productTypeId)
    {
        if ($this->hasChildrenOrNot(["type_id" => $productTypeId])) {
            return ['code' => ['0x048001', 'product']];
        }

        $fromIds = explode(',', rtrim($productTypeId, ','));
        $param = [
            'search' => [
                'product_type_id' => [$fromIds, 'in'],
            ],
        ];
        $list = app($this->productRepository)->getProductList($param);

        if (count($list) > 0) {
            return ['code' => ['0x048002', 'product']];
        }

        // 父级
        $detail = $this->getParentType($fromIds[0]);
        $parentId = isset($detail['product_type_parent']) ? $detail['product_type_parent'] : 0;

        $where = ['product_type_id' => [$fromIds, 'in']];
        if (!app($this->productTypeRepository)->deleteByWhere($where)) {
            return ['code' => ['0x000003', 'common']];
        }

        if ($parentId != 0) {
            $param = ["search" => ["product_type_parent" => [$parentId]]];
            $record = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_id']);
            if (count($record) == 0) {
                if (!app($this->productTypeRepository)->updateData(["has_children" => 0], ['product_type_id' => $parentId])) {
                    return ['code' => ['0x000003', 'common']];
                }
            }
        }

        return true;
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
    public function getProductTypeList($param = [], $fields = ['*'])
    {
        $param = $this->parseParams($param);

        $productTypeList = app($this->productTypeRepository)->getProductTypeByWhere($param, $fields)->toArray();
        self::filterType($productTypeList);
        $productTypeList = array_map(function ($row) {
            $row['product_type_parent_path'] = app($this->productTypeRepository)->getProductTypeParentId($row['product_type_id']);
            return $row;
        }, $productTypeList);

        return $productTypeList;
    }

    private function filterType(&$data){
        if($data){
            foreach ($data as $key => $vo){
                // 处理未分类
                if($vo['product_type_id'] == 1){
                    if(!ProductRepository::getProductByParentId($vo['product_type_id'])){
                        unset($data[$key]);
                    }
                }
                sort($data);
                if($orderFields = array_column($data,'product_type_sort')){
                    array_multisort($orderFields,SORT_ASC,$data);
                }
            }
        }
    }

    public function getProductTypeListByList($param = [], $fields = ['*'])
    {
        $param = $this->parseParams($param);
        $data = $this->getProductTypeList($param, $fields);

        $total = app($this->productTypeRepository)->getProductTypeTotal($param);

        return ['list' => $data, 'total' => $total];
    }

    public function getProductTypeListOnSelect($param = [])
    {
        return $this->response(app($this->productTypeRepository), 'getProductTypeTotal', 'getProductTypeListOnSelect', $this->parseParams($param));
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
        $param = [
            'search' => [
                'product_type_parent' => [$parentId],
            ],
        ];
        if($result = app($this->productTypeRepository)->getProductTypeByWhere($param, $fields)){
            if($fields == ['*']){
                $result = $result->toArray();
                // 处理未分类
                self::filterType($result);
            }

        };
        return $result ? $result : [];

    }

    /**
     * 根据父级id获取所有子级产品分类id
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-13
     */
    public function getAllChildProductTypeId($parentId, $hasSelf = false)
    {
        if ($parentId == 0) {
            return $this->getAllProductTypeId();
        }

        $typeArray = [];
        $first = $this->getProductTypeByParentId($parentId, ['product_type_id']);

        if (!empty($first)) {
            foreach ($first as $value) {
                $typeArray[] = $value->product_type_id;
            }

            $param = [
                'search' => [
                    'product_type_parent' => [$typeArray, 'in'],
                ],
            ];
            $second = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_id']);

            if (!empty($second)) {
                foreach ($second as $v) {
                    $typeArray[] = $v->product_type_id;
                }
            }

        }

        if ($hasSelf) {
            // 是否包含当前id
            $typeArray[] = $parentId;
        }

        return $typeArray;
    }

    public function getChildProductTypeIdByArray($data)
    {
        $typeArray = [];
        $typeStr = $data['type_id'];
        $typeArr = explode(',', trim($typeStr, ','));
        $param = [
            "search" => [
                "product_type_parent" => [$typeArr, 'in'],
            ],
        ];

        $first = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_id']);

        if (!empty($first)) {
            foreach ($first as $value) {
                $typeArray[] = $value->product_type_id;
            }

            $param = [
                'search' => [
                    'product_type_parent' => [$typeArray, 'in'],
                ],
            ];
            $second = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_id']);

            if (!empty($second)) {
                foreach ($second as $v) {
                    $typeArray[] = $v->product_type_id;
                }
            }

        }

        return $typeArray;
    }

    /**
     * 根据父级id获取所有子级产品分类列表
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-14
     */
    public function getAllChildProductType($parentId)
    {

    }

    /**
     * 获取产品最大预警值
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-154
     */
    public function getAllProductMaxAlertValueMap($productId = false)
    {
        $mapObject = app($this->productRepository)->getAllProductAlertValueMap($productId, 1);
        $map = [];

        if (!empty($mapObject)) {
            foreach ($mapObject as $key => $value) {
                $map[$value['product_id']] = $value['product_alert_max'];
            }
        }

        return $map;
    }

    /**
     * 获取产品最小预警值
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getAllProductMinAlertValueMap($productId = false)
    {
        $mapObject = app($this->productRepository)->getAllProductAlertValueMap($productId, 2);
        $map = [];

        if (!empty($mapObject)) {
            foreach ($mapObject as $key => $value) {
                $map[$value['product_id']] = $value['product_alert_min'];
            }
        }

        return $map;
    }

    /**
     * 获取所有产品id
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getAllProductId($param = [])
    {
        $productIds = app($this->productRepository)->getProductList($param, ['product_id']);
        $productId = [];

        if (!empty($productIds)) {
            foreach ($productIds as $value) {
                $productId[] = $value['product_id'];
            }
        }

        return $productId;
    }

    /**
     * 获取所有产品分类id
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getAllProductTypeId()
    {
        $productTypeIds = app($this->productTypeRepository)->getProductTypeByWhere([], ['product_type_id']);
        $productTypeId = [];

        if (!empty($productTypeIds)) {
            foreach ($productTypeIds as $value) {
                $productTypeId[] = $value['product_type_id'];
            }
        }

        return $productTypeId;
    }

    /**
     * 获取产品列表
     *
     * @param array $param
     * @param array $fields
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getProductList($param, $fields = ['*'])
    {
        $param = $this->parseParams($param);
        if (isset($param['search']) && !empty($param['search']) && isset($param['type'])) {
            // 获取所有子级
            $searchArray = $param['search'];
            $parentId = isset($searchArray['product_type_id']) ? $searchArray['product_type_id'][0] : 0;
            $typeArray = $this->getAllChildProductTypeId($parentId, true);
            $param['search']['product_type_id'] = [$typeArray, 'in'];
        }
        $list = app($this->productRepository)->getProductList($param, $fields)->toArray();
        $record = DB::table('system_combobox')->where('combobox_identify', 'PRODUCT_UNIT')->first();
        if (!empty($record) && isset($record->combobox_id)) {
            $comboboxId = $record->combobox_id;
        }
        $this->comboboxObj = app('App\EofficeApp\System\Combobox\Services\SystemComboboxService');

        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['product_type_name'] = isset($value['product_to_product_type']) ? $value['product_to_product_type']['product_type_name'] : '';
                $productUtil = isset($value['product_unit']) ? $value['product_unit'] : 0;
                $list[$key]['product_unit'] = isset($comboboxId) && isset($value['product_unit']) ? $this->comboboxObj->getComboboxFieldsNameById($comboboxId, $value['product_unit']) : '';
                $list[$key]['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'product', 'entity_id' => ['entity_id' => [$value['product_id']], 'entity_column' => ['product_image']]]);
                if (!empty($list[$key]['attachment_id'][0])) {
                    $list[$key]['attachment_thumb'] = app($this->attachmentService)->getThumbAttach($list[$key]['attachment_id'][0]);
                } else {
                    $list[$key]['attachment_thumb'] = false;
                }
                $list[$key]['product_image'] = $list[$key]['attachment_id'];
            }
        }
        $total = app($this->productRepository)->getProductTotal($param);

        return ['list' => $list, 'total' => $total];
    }

    // 获取产品单位
    public function getProdyctUnit($return = false)
    {
        $this->comboboxObj = app('App\EofficeApp\System\Combobox\Services\SystemComboboxService');
        //获取所有单位信息
        $unit = $this->comboboxObj->getProductUnitAll();
        $array = [];
        foreach ($unit as $v) {
            $array[$v['field_value']] = $v['field_name'];
        }
        return $array;
    }

    public function getAllSimpleProducts($productIds = [], $withTrashed = false)
    {
        $products = app($this->productRepository)->getAllSimpleProducts(['product_id', 'product_name', 'product_number'], $productIds, $withTrashed);
        $map = [];
        if (count($products) > 0) {
            foreach ($products as $product) {
                $map[$product->product_id] = $product;
            }
        }
        return $map;
    }

    public function getProductIdByNumber($numbers)
    {
        $products = app($this->productRepository)->getProductByNumbers($numbers);
        $map = [];
        if (count($products) > 0) {
            foreach ($products as $product) {
                $map[$product->product_number] = $product->product_id;
            }
        }
        return $map;
    }

    /**
     * 根据id获取产品列表
     *
     * @param array $productId
     * @param array $fields
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getProductsById($productId, $fields)
    {
        $param = ['search' => []];
        $param['withTrashed'] = 1;
        $param['with'] = 1;
        $param['search'] = [
            'product_id' => [$productId, 'in'],
        ];

        if ($fields[0] != '*' && !in_array('product_id', $fields)) {
            $fields[] = 'product_id';
        }
        $products = app($this->productRepository)->getProductList($param, $fields)->toArray();
        $product = [];

        if (!empty($products)) {
            $record = DB::table('system_combobox')->where('combobox_identify', 'PRODUCT_UNIT')->first();
            if (!empty($record) && isset($record->combobox_id)) {
                $comboboxId = $record->combobox_id;
            }
            $this->comboboxObj = app('App\EofficeApp\System\Combobox\Services\SystemComboboxService');
            foreach ($products as $key => $value) {
                $productType = $value['product_to_product_type'];
                $productUtil = isset($value['product_unit']) ? $value['product_unit'] : '';
                $value['product_unit'] = isset($comboboxId) && isset($value['product_unit']) ? $this->comboboxObj->getComboboxFieldsNameById($comboboxId, $value['product_unit']) : '';
                $value['product_type_name'] = isset($productType['product_type_name']) ? $productType['product_type_name'] : '';
                $value['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'product', 'entity_id' => ['entity_id' => [$value['product_id']], 'entity_column' => ['product_image']]]);
                if (!empty($value['attachment_id'][0])) {
                    $value['attachment_thumb'] = app($this->attachmentService)->getThumbAttach($value['attachment_id'][0]);
                } else {
                    $value['attachment_thumb'] = false;
                }
                $product[$products[$key]['product_id']] = $value;

            }
        }

        return $product;
    }

    /**
     * 根据产品类别获取产品id
     *
     * @param int $productTypeId
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-15
     */
    public function getProductIdByProductTypeId($productTypeId, $withTrashed = false)
    {
        if (empty($productTypeId)) {
            return [];
        }
        $productIds = [];
        $typeArray = $this->getAllChildProductTypeId($productTypeId, true);
        $param = [
            "search" => [
                "product_type_id" => [$typeArray, 'in'],
            ],
            'withTrashed' => $withTrashed
        ];

        $list = app($this->productRepository)->getProductList($param, ['product_id']);

        if (!empty($list)) {
            foreach ($list as $value) {
                $productIds[] = $value['product_id'];
            }
        }

        return $productIds;
    }

    /**
     * 获取产品编号
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-18
     */
    public function getProductNumber()
    {
        $number = "CP00001";
        $param = [
            "search" => [
                "product_number" => ['CP%', 'like'],
            ],
            "order_by" => ["product_number" => "desc"],
            "page" => 1,
            "limit" => 1,
        ];

        $record = app($this->productRepository)->getMaxProductNumber();

        if (!empty($record) && isset($record['product_number'])) {
            $maxNumber = $record['product_number'];
            $num = substr($maxNumber, 2) + 1;
            $number = "CP" . str_pad($num, 5, "0", STR_PAD_LEFT);
        }

        return $number;

    }

    /**
     * 产品编号唯一检查
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-18
     */
    public function checkProductNumber($data)
    {
        $newNumber = $data['product_number'];

        if (empty($newNumber)) {
            return 2;
        }

        $param = [
            "search" => [
                "product_number" => [$newNumber],
            ],
//            "withTrashed" => 1
        ];

        $record = app($this->productRepository)->getProductList($param, ['product_id']);

        return count($record) > 0 ? 1 : 2; // 1:编号存在
    }

    public function getProductByTypeId($param, $productTypeId)
    {
        if (isset($param['search'])) {
            if (is_string($param['search'])) {
                $param['search'] = json_decode($param['search'], true);
            }
            $typeArray = $this->getAllChildProductTypeId($productTypeId, true);
            $param['search']['product_type_id'] = [$typeArray, 'in'];
        }
        $respone = $this->response(app($this->productRepository), 'getProductTotal', 'getProductList', $this->parseParams($param));
        return $this->handleProductUnit($respone);
    }

    /**
     * 处理产品下拉时单位字段
     *
     * @return array
     *
     * @author 施奇
     *
     * @since 2018-08-21
     */
    private function handleProductUnit($respone)
    {
        if (!isset($respone['list']) || empty($respone['list'])) {
            return $respone;
        }
        $return = ['total' => $respone['total']];
        $product_ids = [];
        foreach ($respone['list'] as $v) {
            $product_ids[] = $v['product_id'];
        }
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        $products_info = $this->getProductsById($product_ids, ['product_unit']);
        foreach ($respone['list'] as $v) {
            $v['product_unit'] = $products_info[$v['product_id']]['product_unit'];
            $v['product_image'] = $attachmentService->getAttachmentIdsByEntityId(['entity_table' => 'product', 'entity_id' => $v['product_id']]);
            $return['list'][] = $v;
        }
        return $return;
    }

    /**
     * 产品类别树api(带产品)
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-18
     */
    public function getProductTypeWithProduct($param, $parentId)
    {
        $data = [];
        $typeSearch = ['search' => ["product_type_parent" => [$parentId]]];
        $data = app($this->productTypeRepository)->getProductTypeByWhere($typeSearch)->toArray();

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if ($value['has_children'] == 0) {
                    $data[$key]['has_children'] = 1;
                }
            }
        }

        $productSearch = ['search' => ["product_type_id" => [$parentId]]];
        $product = app($this->productRepository)->getProductList($productSearch);
        if (!empty($product)) {
            foreach ($product as $key => $value) {
                $data[] = [
                    "product_type_id" => $parentId,
                    "product_id" => $value['product_id'],
                    "product_type_name" => $value['product_name'],
                    "product_type_parent" => $parentId,
                    "has_children" => 0,
                ];
            }
        }
        return $data;
    }

    /**
     * 获取产品父级类别
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function getProductTypeParent($productTypeId)
    {
        $array = [
            ["product_type_id" => 0, "product_type_name" => trans("product.all_categorys"), "product_type_level" => 0],
        ];
        $parent = $this->getParentType($productTypeId);
        $parentId = $parent['product_type_parent'];
        $array[] = $parent;

        while ($parentId != 0) {
            $parent = $this->getParentType($parentId);
            $parentId = $parent['product_type_parent'];
            $arr = [$parent];
            array_splice($array, 1, 0, $arr);
        }
        return $array;
    }

    public function getParentType($productTypeId)
    {
        $param = ['search' => ['product_type_id' => [$productTypeId]]];
        $record = app($this->productTypeRepository)->getProductTypeByWhere($param);
        if (!empty($record) && !empty($record[0])) {
            return $record[0];
        } else {
            return ['code' => ['0x000003', 'common']];
        }
    }

    /**
     * 是否有子分类
     *
     * @return bool,(true)有子分类
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function hasChildrenOrNot($data)
    {
        $typeStr = $data['type_id'];
        $typeArray = explode(',', trim($typeStr, ','));
        $param = [
            "search" => [
                "product_type_parent" => [$typeArray, 'in'],
            ],
        ];
        $children = app($this->productTypeRepository)->getProductTypeByWhere($param, ['product_type_id']);

        return count($children) > 0 ? true : false;
    }

    /**
     * 导出产品列表
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function exportProduct($param)
    {
        $param = $this->parseParams($param);
        $own = $param['user_info'];
        return app($this->formModelingService)->exportFields("product", $param, $own, trans('product.product_export'));
    }

    /**
     * 导入模板
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function getProductFields($param)
    {
        return app($this->formModelingService)->getImportFields('product', $param, trans("product.product_import_template"));
    }

    /**
     * 导入列表
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function importProduct($data, $param)
    {
        app($this->formModelingService)->importCustomData('product', $data, $param);

        return ['data' => $data];
    }

    /**
     * 导入筛选
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function importProductFilter($data, $param)
    {
        $typeList = app($this->productTypeRepository)->getProductTypeByWhere([], ["product_type_id"]);
        $typeListArray = [];
        if (!empty($typeList)) {
            foreach ($typeList as $key => $value) {
                $typeListArray[] = $value["product_type_id"];
            }
        }
        $unitArray = $this->getProdyctUnit(false);
        $lastItemProductNumber = null;
        //记录下用户填写的导入成功的编号
        $successProductNumbers = array();
        $model = app($this->formModelingService);
        foreach ($data as $key => $value) {
            $result = $model->importDataFilter('product', $value, $param);
            if (!empty($result)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($result);
                continue;
            } else {
                if($value['product_alert_min'] > $value['product_alert_max']){
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("product.upper_less_than_lower"));
                    continue;
                }
                if ($param['type'] == 2) {
                    if (isset($param['primaryKey']) && $param['primaryKey'] != '') {
                        $paramTemp = [
                            'search' => [
                                $param['primaryKey'] => [$value[$param['primaryKey']]]
                            ]
                        ];
                        $record = app($this->productRepository)->getProductList($paramTemp, ['product_id']);
                        if (count($record) > 0) {
                            $data[$key]['importResult'] = importDataSuccess();
                        } else {
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail(trans("product.no_data_to_update"));
                            continue;
                        }
                    }
                }
            }
            if($value['product_type_id'] == '' || !isset($value['product_type_id'])){
                $data[$key]['product_type_id'] = 1;
            }
            if ($param['type'] == '1') {
                $check = $this->checkProductNumber(['product_number' => $value['product_number']]);

                if ($check == 1 || in_array($value['product_number'], $successProductNumbers)) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("product.product_number_repeat"));
                    continue;
                }
            }
            // 导入时编号必填，新增并更新时，如果有多个为空编号，无法后端生成
            if (key_exists('product_number',$value) && empty($value['product_number'])) {
                if ($param['type'] == 2) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("product.product_number_empty"));
                    continue;
                } else {
                    //非更新类型，产品编号为空时，系统自动生成编号
                    if (!$lastItemProductNumber) {
                        $lastItemProductNumber = $this->getProductNumber();
                        $data[$key]['product_number'] = $lastItemProductNumber;
                    } else {
                        $num = substr($lastItemProductNumber, 2) + 1;
                        $lastItemProductNumber = "CP" . str_pad($num, 5, "0", STR_PAD_LEFT);
                        $data[$key]['product_number'] = $lastItemProductNumber;
                    }
                }
            }else{
                // 导入时编号存在
                list($string,$number) = [substr($value['product_number'],0,2),substr($value['product_number'],2)];
                if($string == 'CP' && strlen($number) == 5){
                    $lastItemProductNumber = $value['product_number'];
                }
            }
            if (isset($value['product_name']) && empty($value['product_name'])) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("product.product_name_empty"));
                continue;
            }
            if (isset($value['product_type_id']) && $value['product_type_id'] != "" && !in_array($value['product_type_id'], $typeListArray)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("product.product_type_empty"));
                continue;
            }
            if (isset($value['product_unit']) && $value['product_unit'] != "" && !isset($unitArray[$value['product_unit']])) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("product.product_unit_empty"));
                continue;
            }
            $data[$key]['importResult'] = importDataSuccess();
            //用户通过导入模板填写的编号可能重复
            if ($value['product_number']) {
                $successProductNumbers[] = $value['product_number'];
            }
        }
        return $data;
    }

    // 外发
    public function addProductOutSend($data){

        if (isset($data['data']) && isset($data['data']['outsource'])) {

            $data = $data['data'];

            if($data['product_alert_min'] > $data['product_alert_max']){
                return ['code' => ['upper_less_than_lower', 'product']];
            }
            if (!isset($data['product_type_id']) || $data['product_type_id'] == '') {
                $data['product_type_id'] = 1;
            }
            if (!isset($data['product_number']) || $data['product_number'] == "") {
                // 未输入产品编号，后端自动生成
                $data['product_number'] = $this->getProductNumber();

                $id = app($this->formModelingService)->addCustomData($data, "product");
            } else {
                $record = $this->checkProductNumber($data);

                if ($record == 1) {
                    return ['code' => ['0x048005', 'product']];
                } else {
                    $id = app($this->formModelingService)->addCustomData($data, "product");
                }
            }
            if(isset($id['code'])){
                return $id;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'product',
                        'field_to' => 'product_id',
                        'id_to' => $id
                    ]
                ]
            ];

        }
    }

    /**
     * 新增产品
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function addProduct($data)
    {
        // 外发
        if (isset($data['data']) && isset($data['data']['outsource'])) {
            unset($data['data']['outsource']);
            $data = $data['data'];
            if (!isset($data[0])) {
                $data = [$data];
            }
            foreach ($data as $row) {
                if($row['product_alert_min'] > $row['product_alert_max']){
                    return ['code' => ['upper_less_than_lower', 'product']];
                }
                $result = $this->addProduct($row);
                if (isset($result['code'])) {
                    return $result;
                }
            }
            return true;
        }
        if (!isset($data['product_type_id']) || $data['product_type_id'] == '') {
            $data['product_type_id'] = 1;
        }
        if (!isset($data['product_number']) || $data['product_number'] == "") {
            // 未输入产品编号，后端自动生成
            $data['product_number'] = $this->getProductNumber();

            $id = app($this->formModelingService)->addCustomData($data, "product");
        } else {
            $record = $this->checkProductNumber($data);

            if ($record == 1) {
                return ['code' => ['0x048005', 'product']];
            } else {
                $id = app($this->formModelingService)->addCustomData($data, "product");
            }
        }
        if(isset($id['code'])){
            return $id;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'product',
                    'field_to' => 'product_id',
                    'id_to' => $id
                ]
            ]
        ];
    }

    /**
     * 产品分类排序
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2018-04-17
     */
    public function sortTypes($types)
    {
        $productTypeIds = explode(',', rtrim($types, ','));

        if (empty($productTypeIds) || $productTypeIds[0] == 0) {
            return ['code' => ['0x041006', 'product']];
        }

        foreach ($productTypeIds as $key => $value) {
            $data = ['product_type_sort' => $key + 1];
            app($this->productTypeRepository)->updateData($data, ['product_type_id' => $value]);
        }

        return true;
    }

    /**
     * 产品详情
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2018-04-25
     */
    public function getProductInfo($productId)
    {
        $param['with'] = 1;
        $param['withTrashed'] = 1;
        $param['search'] = ['product_id' => [$productId]];
        $products = $this->getProductList($param);
        if (count($products['list']) > 0) {
            return $products['list'][0];
        }

        return false;
    }

    /**
     * 获取产品自定义字段详情
     *
     * @return array
     *
     * @author 施奇
     *
     * @since 2018-10-25
     */
    public function getProductCustomDataDetail($tableKey, $param)
    {
        if (isset($param['product_id']) && !empty($param['product_id'])) {
            return app($this->formModelingService)->getCustomDataDetail($tableKey, $param['product_id']);
        }
        if (isset($param['product_number']) && !empty($param['product_number'])) {
            $product = app($this->productRepository)->getProductByNumbers([$param['product_number']], ['*'], true)->toArray();
            if ($product) {
                $product_id = $product[0]['product_id'];
                return app($this->formModelingService)->getCustomDataDetail($tableKey, $product_id);
            }
        }
        return true;
    }

    // 产品管理外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $updateData = $data['data'] ?? [];
        unset($updateData['current_user_id']);
        $dataDetail = ProductRepository::getProductDetail(['product_id'=>$data['unique_id']]);
        if(!$dataDetail){
            return ['code' => ['0x024011','customer']];
        }
        if(empty($updateData['product_number'])){
            $updateData['product_number'] = $dataDetail->product_number ? $dataDetail->product_number : '';

        }
        if(isset($updateData['product_type_id']) && ($updateData['product_type_id'] == '' || $updateData['product_type_id'] == 0)){
            $updateData['product_type_id'] = $dataDetail->product_type_id ? $dataDetail->product_type_id : 1;
        }
        if(!$result = app($this->formModelingService)->editCustomData($updateData,'product',$data['unique_id'])){
            return $result;
        };
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'product',
                    'field_to' => 'product_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 产品管理外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $dataDetail = ProductRepository::getProductDetail(['product_id'=>$data['unique_id']]);
        if(!$dataDetail){
            return ['code' => ['0x024011','customer']];
        }
        $result = $this->deleteProduct($data['unique_id']);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'product',
                    'field_to' => 'product_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }
}
