<?php
namespace App\EofficeApp\System\Security\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Security\Services\SystemSecurityService;
use App\EofficeApp\System\Security\Requests\SecurityRequest;

/**
 * 系统性能安全设置控制器
 *
 * @author  朱从玺
 *
 * @since  2015-10-28
 */
class SecurityController extends Controller
{
	/**
	 * [$service 系统上传设置service]
	 *
	 * @var [object]
	 */
	protected $service;

	/**
	 * [$request request验证]
	 *
	 * @var [object]
	 */
	protected $request;

	public function __construct(
		Request $request,
		SystemSecurityService $service,
		SecurityRequest $securityRequest
	)
	{
		parent::__construct();

		$this->service = $service;
		$this->formFilter($request, $securityRequest);
        $this->request = $request;
	}

	/**
	 * [getModuleUploadList 获取上传设置列表]
	 *
	 * @method 朱从玺
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [json]              [查询结果]
	 */
	public function getModuleUploadList() {
		$param = $this->request->all();

		$result = $this->service->getModuleUploadList($param);

		return $this->returnResult($result);
	}

	/**
	 * [getModuleUpload 获取某个模块的上传设置]
	 *
	 * @method 朱从玺
	 *
	 * @param  [int]          $functionAbbreviation [模块ID]
	 *
	 * @since  2015-10-29 创建
	 *
	 * @return [json]                      			[查询结果]
	 */
	public function getModuleUpload($module)
	{
		$result = $this->service->getModuleUpload($module);

		return $this->returnResult($result);
	}

	/**
	 * [modifyModuleUpload 编辑上传设置]
	 *
	 * @method 朱从玺
	 *
	 * @param  [int]              $id [主键ID]
	 *
	 * @since  2015-10-29 创建
	 *
	 * @return [json]                 [编辑结果]
	 */
	public function modifyModuleUpload($id)
	{
		$newData = $this->request->all();

		$result = $this->service->modifyModuleUpload($id, $newData);

		return $this->returnResult($result);
	}

    /**
     * 获取上传附件名称规则
     * @author yangxingqiang
     * @param $id
     * @return array
     */
    public function getUploadFileRule($id)
    {
        $result = $this->service->getUploadFileRule($id);
        return $this->returnResult($result);
    }

    public function modifyUploadFileRule($id)
    {
        $newData = $this->request->all();

        $result = $this->service->modifyUploadFileRule($id, $newData);

        return $this->returnResult($result);
    }

	/**
	 * [getSecurityOption 获取系统安全选项]
	 *
	 * @method 朱从玺
	 *
	 * @param  [string]            $params [查询选项类型]
	 *
	 * @since  2015-10-29 创建
	 *
	 * @return [json]                      [查询结果]
	 */
	public function getSecurityOption($params)
	{
		$result = $this->service->getSecurityOption($params);

		return $this->returnResult($result);
	}

    /**
     * [getSecurityLevel 获取系统安全等级]
     *
     * @return array
     */
    public function getSecurityLevel()
    {
        $result = $this->service->getSecurityLevel();

        return $this->returnResult($result);
    }

	/**
	 * [modifySecurityOption 编辑系统安全选项]
	 *
	 * @method 朱从玺
	 *
	 * @param  [string]               $params [编辑选项类型]
	 *
	 * @since  2015-10-29 创建
	 *
	 * @return [json]                         [编辑结果]
	 */
	public function modifySecurityOption($params)
	{
		$data = $this->request->all();

		$result = $this->service->modifySecurityOption($params, $data);

		return $this->returnResult($result);
	}

	/**
	 * [resetCapabilityOption 系统性能安全恢复初始设置]
	 *
	 * @method 朱从玺
	 *
	 * @param  [string]              $paramKey [恢复选项]
	 *
	 * @since  2015-10-29 创建
	 *
	 * @return [json]                          [恢复结果]
	 */
	public function resetCapabilityOption($paramKey)
	{
		$result = $this->service->resetCapabilityOption($paramKey);

		return $this->returnResult($result);
	}

	/**
	 * [getParamsData 获取配置的数据]
	 *
	 * @method 朱从玺
	 *
	 * @param  [string]        $paramsKey [要查询的数据名]
	 *
	 * @return [object]                   [查询结果]
	 */
	public function getParamsData($paramKey)
	{
		$result = $this->service->getParamsData($paramKey);

		return $this->returnResult($result);
	}

    /**
     * [modifyParamsData 编辑配置的数据]
     *
     * @method lixx
     *
     * @param  [string]        $paramsKey [要编辑的数据名]
     *
     * @return [object]                   [编辑结果]
     */
    public function modifyParamsData($paramKey)
    {
        $result = $this->service->modifyParamsData($this->request->all(),$paramKey);

        return $this->returnResult($result);
    }

	/**
	 * 获取系统标题
	 *
	 * @author 丁鹏
	 *
	 * @return [type]                        [description]
	 */
	public function getSystemTitleSetting()
	{
		$result = $this->service->getSystemTitleSetting($this->request->all());
        return $this->returnResult($result);
	}

	/**
	 * 设置系统标题
	 *
	 * @author 丁鹏
	 *
	 * @return [type]                           [description]
	 */
	public function modifySystemTitleSetting()
	{
		$result = $this->service->modifySystemTitleSetting($this->request->all());
        return $this->returnResult($result);
	}
	/**
	 * 设置编辑器是否允许写js脚本
	 *
	 */
	public function setSecurityEditor()
	{
		$result = $this->service->setSecurityEditor($this->request->all());

		return $this->returnResult($result);
	}
	/**
     * 获取白名单列表
     */
	public function getWhiteAddress()
    {
        $result = $this->service->getWhiteAddress($this->request->all());
        return $this->returnResult($result);
    }
    /**
     *新增白名单
     */
    public function addWhiteAddress()
    {
        $result = $this->service->addWhiteAddress($this->request->all(),$this->own['user_id']);
        return $this->returnResult($result);
    }
    /**
     *删除一个白名单
     */
    public function deleteAWhiteAddress($whiteAddressId)
    {
        $result = $this->service->deleteAWhiteAddress($whiteAddressId);
        return $this->returnResult($result);
    }
    /**
     * 编辑一条白名单
     */
    public function modifyAWhiteAddress($whiteAddressId)
    {
        $result = $this->service->modifyAWhiteAddress($this->request->all(),$whiteAddressId,$this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 【水印】获取水印设置数据
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function getWatermarkSettingInfo($type)
    {
    	$param = $this->request->all();
        $result = $this->service->getWatermarkSettingInfo($param,$type,$this->own);
        return $this->returnResult($result);
    }
    /**
     * 【水印】获取水印预览页面html
     * @return [type]       [description]
     */
    public function getWatermarkPriviewHtml()
    {
    	$param = $this->request->all();
        $result = $this->service->getWatermarkPriviewHtml($param);
        return $this->returnResult($result);
    }

    /**
     * 【水印】保存水印设置
     * @return [type] [description]
     */
    public function saveWatermarkSetting()
    {
    	$param = $this->request->all();
        $result = $this->service->saveWatermarkSetting($param,$this->own);
        return $this->returnResult($result);
    }
    // 获取系统参数
    public function getSystemParams() {
        $result = $this->service->getSystemParams($this->request->all());
        return $this->returnResult($result);
    }
    public function setSystemParams() {
        $result = $this->service->setSystemParams($this->request->all());
        return $this->returnResult($result);
    }
}
