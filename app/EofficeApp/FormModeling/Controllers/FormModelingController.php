<?php

namespace App\EofficeApp\FormModeling\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\FormModeling\Services\FormModelingService;
use Illuminate\Http\Request;

/**
 * 表单建模
 *
 * @author: 白锦
 *
 * @since：2019-03-22
 *
 */
class FormModelingController extends Controller
{

    public function __construct(
        Request $request,
        FormModelingService $FormModelingService
    ) {
        parent::__construct();
        $this->formModelingService = $FormModelingService;
        $this->request             = $request;
    }

    /**
     * 获取表单模块列表
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return array
     */
    public function getFormModuleLists(Request $request)
    {
        $result = $this->formModelingService->getFormModuleLists($request->all());
        return $this->returnResult($result);
    }

    /**
     * 2019-03-22
     *
     * 保存表单字段
     *
     * @param Request $request
     * @param string $tableKey
     *
     * @return boolean
     */
    public function saveFormFields(Request $request, $tableKey)
    {
        return $this->returnResult($this->formModelingService->saveFormFields($request->all(), $tableKey));
    }

    /**
     * 保存列表页面模板
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function saveListLayout(Request $request)
    {
        $result = $this->formModelingService->saveListLayout($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取列表模板
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return array
     */
    public function getListLayout(Request $request, $tableKey)
    {
        $result = $this->formModelingService->getListLayout($tableKey, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取流程菜单链接配置
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return array
     */
    public function listCustomFields(Request $request, $tableKey)
    {
        $result = $this->formModelingService->listCustomFields($this->request->all(), $tableKey);
        return $this->returnResult($result);
    }

    /**
     * 模板保存
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return string or boolean
     */
    public function saveTemplate(Request $request)
    {
        $result = $this->formModelingService->saveTemplate($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除模板
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function deleteTemplate($id)
    {
        $result = $this->formModelingService->deleteTemplate($id);
        return $this->returnResult($result);
    }
    /**
     * 获取单个模板信息
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function getTemplate($id)
    {
        $result = $this->formModelingService->getTemplate($id);
        return $this->returnResult($result);
    }
    /**
     * 获取模板信息
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function getTemplateInfo(Request $request)
    {
        $result = $this->formModelingService->getTemplateInfo($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 获取模板列表
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function getTemplateList(Request $request, $tableKey)
    {
        $result = $this->formModelingService->getTemplateList($tableKey, $this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 编辑模板
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function editTemplate(Request $request, $id)
    {
        $result = $this->formModelingService->editTemplate($this->request->all(), $id);
        return $this->returnResult($result);
    }
    /**
     * 绑定模板
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function bindTemplate(Request $request)
    {
        $result = $this->formModelingService->bindTemplate($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 绑定模板
     *
     * @author 白锦
     *
     * @since  2019-03-22 创建
     *
     * @return boolean
     */
    public function getBindTemplate(Request $request, $tableKey)
    {
        $result = $this->formModelingService->getBindTemplate($tableKey, $this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 保存其他设置
     *
     * @param   Request  $request
     * @param   string   $tableKey
     *
     * @return  boolean
     */
    public function saveOtherSetting(Request $request, $tableKey)
    {
        $result = $this->formModelingService->saveOtherSetting($tableKey, $this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 获取设置
     *
     * @param   Request  $request
     * @param   string   $tableKey
     *
     * @return  array
     */
    public function getCustomMenu(Request $request, $tableKey)
    {
        $result = $this->formModelingService->getCustomMenu($tableKey, $this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 获取数据列表
     *
     * @param   Request  $request
     * @param   string   $tableKey
     *
     * @return  array
     */
    public function getCustomDataLists(Request $request, $tableKey)
    {
        $result = $this->formModelingService->getCustomDataLists($this->request->all(), $tableKey);
        return $this->returnResult($result);
    }
    /**
     * 保存详情页面
     *
     * @param   Request  $request
     * @param   string   $tableKey
     *
     * @return  array
     */
    public function addCustomData(Request $request, $tableKey)
    {
        $result = $this->formModelingService->addCustomData($this->request->all(), $tableKey);
        return $this->returnResult($result);
    }
    /**
     * 编辑详情页面
     *
     * @param   Request  $request
     * @param   string   $tableKey
     *
     * @return  array
     */
    public function editCustomData(Request $request, $tableKey, $dataId)
    {
        $result = $this->formModelingService->editCustomData($this->request->all(), $tableKey, $dataId);
        return $this->returnResult($result);
    }
    /**
     * 删除自定义数据
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return boolean
     */
    public function deleteCustomData($tableKey, $dataId)
    {
        return $this->returnResult($this->formModelingService->deleteCustomData($tableKey, $dataId));
    }
    /**
     * 获取自定义数据详情
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return object
     */
    public function getCustomDataDetail($tableKey, $dataId)
    {
        return $this->returnResult($this->formModelingService->getCustomDataDetail($tableKey, $dataId, $this->own));
    }
    /**
     * 解析数据
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return object
     */
    public function parseCustomData(Request $request)
    {
        return $this->returnResult($this->formModelingService->parseCustomData($this->request->all()));
    }
    /**
     * 保存权限
     *
     * @param   string   $tableKey  
     *
     * @return  array
     */
    public function savePermission(Request $request, $tableKey)
    {
        return $this->returnResult($this->formModelingService->savePermission($this->request->all(), $tableKey));
    }
    /**
     * 复制模板
     *
     * @param   int  $id  
     *
     * @return  array
     */
    public function copyTemplate($id)
    {
        return $this->returnResult($this->formModelingService->copyTemplate($id));
    }
     /**
     * 比较日期
     *
     * @param   int  $id  
     *
     * @return  array
     */
    public function compareDate()
    {
        return $this->returnResult($this->formModelingService->compareDate($this->request->all()));
    }
    /**
     * 验证唯一
     *
     *
     * @return  array
     */

    public function checkFieldsUnique()
    {
        return $this->returnResult($this->formModelingService->checkFieldsUnique($this->request->all()));
    }

    /**
     * 获取当前模板
     *
     *
     * @return  array
     */

    public function getCurrentTemplate(Request $request,$tableKey)
    {
        return $this->returnResult($this->formModelingService->getCurrentTemplate($tableKey,$this->request->all()));
    }
     public function getSystemApp(Request $request,$tableKey)
    {
        return $this->returnResult($this->formModelingService->getSystemApp($tableKey,$this->request->all()));
    }

    public function quickSave(Request $request,$tableKey)
    {
        return $this->returnResult($this->formModelingService->quickSave($tableKey,$this->request->all()));
    }

    public function exportMaterial(Request $request,$tableKey)
    {
        return $this->returnResult($this->formModelingService->exportMaterial($tableKey,$this->request->all()));
    }

    public function importMaterial(Request $request,$tableKey)
    {
        return $this->returnResult($this->formModelingService->importMaterial($tableKey,$this->request->all()));
    }
}
