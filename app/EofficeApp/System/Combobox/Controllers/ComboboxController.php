<?php

namespace App\EofficeApp\System\Combobox\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Combobox\Requests\ComboboxRequest;
use App\EofficeApp\System\Combobox\Services\SystemComboboxService;

/**
 * 系统下拉框控制器:提供系统下拉框相关外部请求并提供返回值
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class ComboboxController extends Controller
{
    /**
     * 系统下拉框service
     *
     * @var object
     */
    private $systemComboboxService;

    public function __construct(
        SystemComboboxService $systemComboboxService,
        Request $request,
        ComboboxRequest $systemComboboxRequest
    ) {
        parent::__construct();
        $this->request = $request;
        $this->systemComboboxService = $systemComboboxService;
        $this->formFilter($request, $systemComboboxRequest);
    }

    /**
     * 获取系统下拉表
     *
     * @return  array   查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getIndexCombobox()
    {
        $result = $this->systemComboboxService->getComboboxList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 添加下拉表
     *
     * @return  array   成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createCombobox()
    {
        $result = $this->systemComboboxService->createCombobox($this->request->all());
        return $this->returnResult($result);
    }

   /**
     * 删除下拉表
     *
     * @param   int     $id 下拉表id
     *
     * @return  bool        是否删除
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteCombobox($id)
    {
        $result = $this->systemComboboxService->deleteCombobox($id);
        return $this->returnResult($result);
    }

    /**
     * 编辑拉表
     *
     * @param   int     $id 下拉表id
     *
     * @return  array       正确码或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function editCombobox($id)
    {
        $result = $this->systemComboboxService->updateCombobox($this->request->all(), $id);
        return $this->returnResult($result);
    }

    /**
     * 获取下拉表详情
     *
     * @param   int     $id 下拉表id
     *
     * @return  array       下拉表详情
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getCombobox($id)
    {
        $result = $this->systemComboboxService->getComboboxDetail($id);
        return $this->returnResult($result);
    }

    /**
     * 获取系统下拉表字段
     *
     * @param   int     $id 下拉表id
     *
     * @return  array       下拉表字段或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getIndexComboboxFields($id)
    {
        $result = $this->systemComboboxService->getFieldsList($id, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取所有下拉字段
     *
     * @apiTitle 获取所有下拉字段
     * @param {int} $id 下拉ID
     *
     * @paramExample {string} 参数示例
     * api/system/combobox/combobox-all-fields/1
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          "total": 6, // 下拉字段数量
     *          "list": [ // 下拉字段列表
     *              field_id: 68
                    combobox_id: 21
                    field_name: "例会"
                    field_value: 1
                    field_order: 0
                    is_default: 0
                    created_at: "-0001-11-30 00:00:00"
                    updated_at: "-0001-11-30 00:00:00"
                    deleted_at: null
                    combobox_name: "combobox_21"
                    combobox_identify: "MEETING_TYPE"
                    tag_id: 3
                    combobox_pinyin: ""
     *          ...
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getAllFields($id)
    {
        $result = $this->systemComboboxService->getAllFields($id, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 添加下拉表字段
     *
     * @param   int     $id 下拉表id
     *
     * @return  array       成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createComboboxFields($id)
    {
        $result = $this->systemComboboxService->createField($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑下拉表字段
     *
     * @param   int   $id      下拉表id
     * @param   int   $fieldId 下拉表字段id
     *
     * @return  array               成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function editComboboxFields($id, $fieldId)
    {
        $result = $this->systemComboboxService->updateField($this->request->all(), $id, $fieldId);
        return $this->returnResult($result);
    }

    /**
     * 删除下拉表字段
     *
     * @param   int   $id       下拉表id
     * @param   int   $fieldId  下拉表字段id
     *
     * @return  array            成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteComboboxFields($id, $fieldId)
    {
        $result = $this->systemComboboxService->deleteField($fieldId);
        return $this->returnResult($result);
    }

    /**
     * 获取下拉表标签
     *
     * @return  array   查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getIndexComboboxTags()
    {
        $result = $this->systemComboboxService->getTagsList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 添加下拉表标签
     *
     * @return  array   成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createComboboxTags()
    {
        $result = $this->systemComboboxService->createTag($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑下拉表标签
     *
     * @param   int     $id 标签id
     *
     * @return  array       成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function editComboboxTags($id)
    {
        $result = $this->systemComboboxService->updateTag($this->request->all(), $id);
        return $this->returnResult($result);
    }

    /**
     * 删除下拉表标签
     *
     * @param   int     $id 标签id
     *
     * @return  array       查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteComboboxTags($id)
    {
        $result = $this->systemComboboxService->deleteTag($id);
        return $this->returnResult($result);
    }


    public function getComboboxFieldsValueById($id)
    {
        $result = $this->systemComboboxService->getComboboxFieldsValueById($id);
        return $this->returnResult($result);
    }

    //下拉选择
	public function getComboboxFieldData($field){
		$combobox_name= "";
		$combobox_identify = "";
		if($field=='industry'){
			$combobox_name = "客户行业";
			$combobox_identify = "CUSTOMER_TRADE" ;
		}
		$param = $this->request->all();
		if(isset($param['search'])&&!is_array($param['search']))  $param['search'] = json_decode($param['search'],true);
		$result = $this->systemComboboxService->getComboboxFieldData($param,$combobox_name,$combobox_identify);
		return $this->returnResult($result);
	}

    //行业选择器
    public function getIndustry()
    {
        $result = $this->systemComboboxService->getIndustry($this->request->all());
        return $this->returnResult($result);
    }


    /**
     * 访问不存在方法处理
     *
     * @return string 提示信息
     *
     * @author: qishaobo
     *
     * @since：2015-10-21
     */
    public function __call($name, $param)
    {
        return 'function '.$name.' not exist';
    }
}
