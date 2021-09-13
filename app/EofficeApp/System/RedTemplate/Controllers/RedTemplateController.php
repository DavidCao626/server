<?php

namespace App\EofficeApp\System\RedTemplate\Controllers;

use Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\RedTemplate\Services\RedTemplateService;

/**
 * 套红模板管理控制器:提供套红模板管理理模块请求的实现方法
 *
 * @author miaochenchen
 *
 * @since  2016-09-28 创建
 */
class RedTemplateController extends Controller
{
    /** @var object 套红模板service对象*/
    private $redTemplateService;

    public function __construct(
        RedTemplateService $redTemplateService
    ) {
        parent::__construct();
        $this->redTemplateService = $redTemplateService;
    }

    /**
     * 新建套红模板
     *
     * @return int 新建套红模板id
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    function createRedTemplate()
    {
        $result = $this->redTemplateService->createRedTemplate(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 删除套红模板
     *
     * @param  int|string $templateId 套红模板id,多个用逗号隔开
     *
     * @return bool 操作是否成功
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    function deleteRedTemplate($templateId)
    {
        $result = $this->redTemplateService->deleteRedTemplate($templateId);
        return $this->returnResult($result);
    }

    /**
     * 编辑套红模板
     *
     * @param  int $templateId 套红模板id
     *
     * @return array 套红模板详情
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    function editRedTemplate($templateId)
    {
        $result = $this->redTemplateService->editRedTemplate($templateId, Request::all());
        return $this->returnResult($result);
    }

    /**
     * 查询套红模板详情
     *
     * @param  int 套红模板id
     *
     * @return array 套红模板详情
     *
     * @author miaochenchen
     *
     * @since  2016-09-28
     */
    function getRedTemplate($templateId)
    {
        $result = $this->redTemplateService->getRedTemplateDetail($templateId);
        return $this->returnResult($result);
    }

    /**
     * 获取套红模板列表
     *
     * @return array 套红模板列表
     *
     * @author miaochenchen
     *
     * @since  2016-09-28
     */
    function getRedTemplateList()
    {
        $result = $this->redTemplateService->getRedTemplateList(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 获取有权限的套红模板列表
     *
     * @return array 套红模板列表
     *
     * @author nitianhua
     *
     * @since  2018-12-18
     */
    function getMyRedTemplateList()
    {
        $result = $this->redTemplateService->getMyRedTemplateList($this->own, Request::all());
        return $this->returnResult($result);
    }

    /**
     * 访问不存在方法处理
     *
     * @return string 提示信息
     *
     * @author: miaochenchen
     *
     * @since：2016-09-28
     */
    public function __call($name, $param)
    {
        return 'function '.$name.' not exist';
    }
}
