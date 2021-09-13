<?php

namespace App\EofficeApp\System\CommonTemplate\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\CommonTemplate\Services\CommonTemplateService;

/**
 * 公共模板管理控制器:提供公共模板管理理模块请求的实现方法
 *
 * @author qishaobo
 *
 * @since  2016-01-22 创建
 */
class CommonTemplateController extends Controller
{
    /** @var object 公共模板service对象*/
    private $commonTemplateService;

    public function __construct(
        Request $request,
        CommonTemplateService $commonTemplateService
    ) {
        parent::__construct();
        $this->request = $request;
        $this->commonTemplateService = $commonTemplateService;
    }

    /**
     * 新建公共模板
     *
     * @return int 新建公共模板id
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function createCommonTemplate()
    {
        $result = $this->commonTemplateService->createCommonTemplate($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除公共模板
     *
     * @param  int|string $templateId 公共模板id,多个用逗号隔开
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function deleteCommonTemplate($templateId)
    {
        $result = $this->commonTemplateService->deleteCommonTemplate($templateId);
        return $this->returnResult($result);
    }

    /**
     * 编辑公共模板
     *
     * @param  int $templateId 公共模板id
     *
     * @return array 公共模板详情
     *
     * @author: qishaobo
     *
     * @since：2016-01-22
     */
    function editCommonTemplate($templateId)
    {
        $result = $this->commonTemplateService->editCommonTemplate($templateId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 查询公共模板详情
     *
     * @param  int 公共模板id
     *
     * @return array 公共模板详情
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function getCommonTemplate($templateId)
    {
        $result = $this->commonTemplateService->getCommonTemplateDetail($templateId);
        return $this->returnResult($result);
    }

    /**
     * 获取公共模板列表
     *
     * @return array 公共模板列表
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function getIndexCommonTemplate()
    {
        $result = $this->commonTemplateService->getCommonTemplateList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 访问不存在方法处理
     *
     * @return string 提示信息
     *
     * @author: qishaobo
     *
     * @since：2016-01-22
     */
    public function __call($name, $param)
    {
        return 'function '.$name.' not exist';
    }
    public function importContentTemplate() {
        $result = $this->commonTemplateService->importContentTemplate($this->request->all());
        return $this->returnResult($result);
    }
    public function exportContentTemplate($id) {
        $result = $this->commonTemplateService->exportContentTemplate($id);
        return $this->returnResult($result);
    }
}