<?php
namespace App\EofficeApp\System\CustomFields\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\System\CustomFields\Services\FieldsService;
use App\EofficeApp\Base\Controller;
/**
 * @自定义字段控制器
 *
 * @author 李志军
 */
class CustomFieldsController extends Controller
{
	private $fieldsService;//服务类对象

	/**
	 * @注册服务类对象
	 * @param \App\EofficeApp\Services\FieldsService $fieldsService
	 */
	public function __construct(FieldsService $fieldsService)
	{
		parent::__construct();

		$this->fieldsService = $fieldsService;
	}
    /**
     * 2017-08-08
     *
     * 获取字段列表
     *
     * @param Request $request
     * @param string $tableKey
     *
     * @return array
     */
    public function listCustomFields(Request $request,$tableKey)
	{
		return $this->returnResult($this->fieldsService->listCustomFields($request->all(), $tableKey));
	}

    /**
     * 2017-08-08
     *
     * 保存自定义字段
     *
     * @param Request $request
     * @param string $tableKey
     *
     * @return boolean
     */
    public function saveCustomFields(Request $request, $tableKey)
	{
		return $this->returnResult($this->fieldsService->saveCustomFields($request->all(), $tableKey));
	}
	/**
     * 2017-08-08
     *
     * 获取自定义字段详情
     *
     * @param string $tableKey
     * @param int $fieldId
     *
     * @return boject
     */
    public function showCustomField($tableKey, $fieldId)
	{
		return $this->returnResult($this->fieldsService->showCustomField($fieldId, $tableKey));
	}
    /**
     * 2017-08-08
     *
     * 获取自定义模块，菜单
     *
     * @return array
     */
    public function getCustomModules(Request $request)
    {
        return $this->returnResult($this->fieldsService->getCustomModules($request->all()));
    }
    /**
     * 2017-08-08
     *
     * 获取自定义数据列表
     *
     * @param Request $request
     * @param string $tableKey
     *
     * @return array
     */
    public function getCustomDataLists(Request $request, $tableKey)
    {
        return $this->returnResult($this->fieldsService->getCustomDataLists($request->all(), $tableKey, $this->own));
    }
    /**
     * 2017-08-08
     *
     * 获取自定义数据详情
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return object
     */
    public function getCustomDataDetail($tableKey, $dataId)
    {
        return $this->returnResult($this->fieldsService->getCustomDataDetail($tableKey, $dataId, $this->own));
    }
    /**
     * 2017-08-08
     *
     * 删除自定义数据
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return boolean
     */
    public function deleteCustomData($tableKey, $dataId)
    {
        return $this->returnResult($this->fieldsService->deleteCustomData($tableKey, $dataId));
    }
    /**
     * 2017-08-08
     *
     * 新增自定义数据
     *
     * @param Request $request
     * @param string $tableKey
     *
     * @return boolean
     */
    public function addCustomData(Request $request, $tableKey)
    {
        return $this->returnResult($this->fieldsService->addCustomData($request->all(), $tableKey));
    }
    /**
     * 2017-08-08
     *
     * 编辑自定义数据
     *
     * @param Request $request
     * @param string $tableKey
     * @param int $dataId
     *
     * @return boolean
     */
    public function editCustomData(Request $request, $tableKey, $dataId)
    {
        return $this->returnResult($this->fieldsService->editCustomData($request->all(), $tableKey, $dataId));
    }
    /**
     * 2017-08-10
     *
     * 自动查询返回数据列表
     *
     * @param Request $request
     * @param string $tableKey
     *
     * @return array
     */
    public function getCustomDataAutoSearchLists(Request $request, $tableKey)
    {
        return $this->returnResult($this->fieldsService->getCustomDataAutoSearchLists($request->all(), $tableKey, $this->own));
    }

    //old code =====================================================================================//
    /**
	 * 新建字段表
	 *
	 * @param string $moduleName
	 *
	 * @return  json 新建结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-11
	 */
	public function createFieldsTable($moduleName)
	{
		return $this->returnResult($this->fieldsService->createFieldsTable($moduleName));
	}
    /**
	 * @获取字段详情
	 * @param type $fieldId
	 * @return json | 详情信息
	 */
	public function showFields($tableKey, $fieldId)
	{
		return $this->returnResult($this->fieldsService->showFields($fieldId, $tableKey));
	}
    /**
	 * @新建字段
	 * @param \App\Http\Requests\StoreFieldsRequest $request
	 * @param type $tableId
	 * @return json | 字段Id
	 */
	public function saveFields(Request $request, $tableKey)
	{
		return $this->returnResult($this->fieldsService->saveFields($request->all(), $tableKey));
	}
    /**
	 * @获取自定义字段列表
	 * @param \Illuminate\Http\Request $request
	 * @return json | 自定义字段列表
	 */
	public function listFields(Request $request,$tableKey)
	{
		return $this->returnResult($this->fieldsService->listFields($request->all(), $tableKey));
	}

    public function getCustomMenuParent(Request $request){
        return $this->returnResult($this->fieldsService->getCustomMenuParent(['is_dynamic'=>2]));
    }
    public function getCustomMenuChild(Request $request){
        return $this->returnResult($this->fieldsService->getCustomMenuChild($request->all()));
    }

    public function getCustomFields(Request $request){
        return $this->returnResult($this->fieldsService->getCustomFields($request->all()));
    }
    public function getReminds(Request $request){
        return $this->returnResult($this->fieldsService->getReminds($request->all()));
    }
    
     public function parseCutomData(Request $request){
        return $this->returnResult($this->fieldsService->parseCutomData($request->all(), $this->own));
    }
}
