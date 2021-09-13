<?php

namespace App\EofficeApp\FlowModeling\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\FlowModeling\Requests\FlowModelingRequest;
use App\EofficeApp\FlowModeling\Services\FlowModelingService;
use Illuminate\Http\Request;

/**
 * 流程建模
 *
 * @author: 缪晨晨
 *
 * @since：2018-02-28
 *
 */
class FlowModelingController extends Controller
{

    public function __construct(
        Request $request,
        FlowModelingService $flowModelingService,
        FlowModelingRequest $flowModelingRequest
    ) {
        parent::__construct();
        $this->flowModelingService = $flowModelingService;
        $this->flowModelingRequest = $request;
        $this->formFilter($request, $flowModelingRequest);
        $this->request = $request;
    }

    /**
     * 获取模块树
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return array
     */
    public function getFlowModuleTree($moduleParent)
    {
        $result = $this->flowModelingService->getFlowModuleTree($moduleParent);
        return $this->returnResult($result);
    }

    /**
     * 获取模块列表
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return array
     */
    public function getFlowModuleList()
    {
        $result = $this->flowModelingService->getFlowModuleList();
        return $this->returnResult($result);
    }

    /**
     * 查询模块树(提供给选择器)
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return array
     */
    public function searchFlowModuleTreeForSelector()
    {
        $result = $this->flowModelingService->searchFlowModuleTreeForSelector($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取模块信息
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return array
     */
    public function getFLowModuleInfoByModuleId($moduleId)
    {
        $result = $this->flowModelingService->getFLowModuleInfoByModuleId($moduleId);
        return $this->returnResult($result);
    }

    /**
     * 获取流程菜单链接配置
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return array
     */
    public function getFlowMenuConfig()
    {
        $result = $this->flowModelingService->getFlowMenuConfig();
        return $this->returnResult($result);
    }

    /**
     * 添加模块
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return string or boolean
     */
    public function addFlowModule()
    {
        $result = $this->flowModelingService->addFlowModule($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑模块
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return boolean
     */
    public function editFlowModule($moduleId)
    {
        $result = $this->flowModelingService->editFlowModule($moduleId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除模块
     *
     * @author 缪晨晨
     *
     * @since  2018-02-28 创建
     *
     * @return boolean
     */
    public function deleteFlowModule($moduleId)
    {
        $result = $this->flowModelingService->deleteFlowModule($moduleId);
        return $this->returnResult($result);
    }
}
