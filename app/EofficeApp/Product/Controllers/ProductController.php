<?php
namespace App\EofficeApp\Product\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Product\Requests\ProductRequest;
use App\EofficeApp\Product\Services\ProductService;
use Illuminate\Http\Request;

/**
 * 产品管理模块控制器
 *
 * @author  牛晓克
 *
 * @since  2017-12-12 创建
 */
class ProductController extends Controller
{
    private $request;

    private $productService;

    public function __construct(
        Request $request,
        ProductRequest $productRequest,
        ProductService $productService
    ) {
        parent::__construct();
        $this->formFilter($request, $productRequest);
        $this->request        = $request;
        $this->productService = $productService;
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
    public function addProductType()
    {
        return $this->returnResult($this->productService->addProductType($this->request->all()));
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
    public function editProductType($productTypeId)
    {
        return $this->returnResult($this->productService->editProductType($productTypeId, $this->request->all()));
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
        return $this->returnResult($this->productService->deleteProduct($productId));
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
        return $this->returnResult($this->productService->deleteProductType($productTypeId));
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
        return $this->returnResult($this->productService->typeMigrate($productTypeId, $parentId));
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
        return $this->returnResult($this->productService->batchMoveType($productTypeId, $parentId));
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
        return $this->returnResult($this->productService->batchDeleteType($productTypeId));
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
    public function getProductTypeList()
    {
        return $this->returnResult($this->productService->getProductTypeList($this->request->all()));
    }

    public function getProductTypeListByList()
    {
        return $this->returnResult($this->productService->getProductTypeListByList($this->request->all()));
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
    public function getProductTypeByParentId($parentId)
    {
        return $this->returnResult($this->productService->getProductTypeByParentId($parentId));
    }

    /**
     * 获取产品列表
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-18
     */
    public function getProductList()
    {
        return $this->returnResult($this->productService->getProductList($this->request->all()));
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
        return $this->returnResult($this->productService->getProductNumber());
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
    public function checkProductNumber()
    {
        return $this->returnResult($this->productService->checkProductNumber($this->request->all()));
    }

    /**
     * 根据产品类别id获取产品
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-18
     */
    public function getProductByTypeId($productTypeId)
    {
        return $this->returnResult($this->productService->getProductByTypeId($this->request->all(), $productTypeId));
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
    public function getProductTypeWithProduct($parentId)
    {
        return $this->returnResult($this->productService->getProductTypeWithProduct($this->request->all(), $parentId));
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
        return $this->returnResult($this->productService->getProductTypeParent($productTypeId));
    }

    /**
     * 获取所有子级分类（包括本分类）
	 *
	 *@param int
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function getAllChildProductTypeId($productTypeId)
    {
        return $this->returnResult($this->productService->getAllChildProductTypeId($productTypeId, true));
    }

    /**
     * 获取所有子级分类（不包括本分类）
     *
     *@param int
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function getChildrenTypeIds($productTypeId)
    {
        return $this->returnResult($this->productService->getAllChildProductTypeId($productTypeId, false));
    }

    /**
     * 获取所有子级分类
     *
	 *@param array
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function getChildProductTypeIdByArray()
    {
        return $this->returnResult($this->productService->getChildProductTypeIdByArray($this->request->all()));
    }

    /**
     * 是否有子分类
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function hasChildrenOrNot()
    {
    	return $this->returnResult($this->productService->hasChildrenOrNot($this->request->all()));
    }

    /**
     * 新增产品
     *
     * @return bool
     *
     * @author 牛晓克
     *
     * @since 2017-12-19
     */
    public function addProduct()
    {
        return $this->returnResult($this->productService->addProduct($this->request->all()));
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
        return $this->returnResult($this->productService->sortTypes($types));
    }
    public function getProductTypeListOnSelect()
    {
        return $this->returnResult($this->productService->getProductTypeListOnSelect($this->request->all()));
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
        return $this->returnResult($this->productService->getProductInfo($productId));
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
    public function getProductCustomDataDetail($tableKey)
    {
        return $this->returnResult($this->productService->getProductCustomDataDetail($tableKey,$this->request->all()));
    }
}
