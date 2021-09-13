<?php
namespace App\EofficeApp\Flow\Services;
use App\EofficeApp\Flow\Services\FlowBaseService;
use DB;
use Schema;
/**
 * 流程service类，用来调用所需资源，满足流程controller的需求。函数和controller一一对应。
 * 其余的流程的服务，放在其他的服务类里。
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormService extends FlowBaseService
{

    public function __construct(
    ) {
        parent::__construct();
    }

    /**
     * 【流程表单解析】
     * 传参数 $templateId 的时候，从表单模板里取控件，进行解析-20171121
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return array    流程数据
     */
    function getParseForm($formId,$data=[],$templateId="")
    {
        $fields = ['control_id', 'control_title', 'control_type', 'control_attribute', 'control_parent_id'];

        // 显示字段
        if (isset($data['fields'])) {
            array_merge($fields, explode(',', trim($data['fields'], ',')));
        }
        //判断是否需要获取明细字段
        if ( (isset($data['exclude_detail_layout']) && $data['exclude_detail_layout'] ) || isset($data['exclude_detail_layout_children']) ) {
             // 判断是否是要获取流程各种模板的表单解析
            // 20180129-参考flowService里面的这个函数的调用，这里是用来获取子表单预览时控件数据的。
            if($templateId) {
                $formControlStructure = $this->getFlowChildFormControlStructure(['search' => ["form_id" => [$templateId],'control_parent_id'=>[''] ]]);
            } else {
                $formControlStructure = $this->getFlowFormControlStructure(["search"=>["form_id" => [$formId] ,'control_parent_id'=>[''] ] , "fields" =>$fields ]);
            }
        } else {
            if($templateId) {
                $formControlStructure = $this->getFlowChildFormControlStructure(['search' => ["form_id" => [$templateId] ]]);
            } else {
                $formControlStructure = $this->getFlowFormControlStructure(["search"=>["form_id" => [$formId]] , "fields" =>$fields ]);
            }
        }

        if(!$formControlStructure) {
            return [];
        }

        $result = [];
        $mingxi = [];
        $secondMingxi = [];
        $controlTitleArr = [];
        foreach ($formControlStructure as $innerControlValue) {
            $controlTitleArr[$innerControlValue['control_id']] = $innerControlValue['control_title'];
        }
        foreach ($formControlStructure as $formControlvalue) {
            if ($formControlvalue['control_type'] == "column") {
                continue;
            }
            // 控件属性
            $controlAttr = $formControlvalue['control_attribute'] = json_decode($formControlvalue['control_attribute'], true);
            if (isset($controlAttr['pItemType']) && $controlAttr['pItemType']=='column') {
                $formControlvalue['old_control_parent_id'] = $formControlvalue['control_parent_id'];
                $formControlvalue['control_parent_id'] = $controlAttr['control_grandparent_id'];
            }
            $countControlId = count(explode('_', $formControlvalue['control_id']));
            //简易版表单排除水平布局、垂直布局、描述控件
            if($formControlvalue['control_type'] == "horizontal-layout" || $formControlvalue['control_type'] == "vertical-layout" || $formControlvalue['control_type'] == "label"){
                continue;
            }
            if($formControlvalue['control_type'] == "detail-layout"){
                if (isset($data['exclude_detail_layout']) && $data['exclude_detail_layout']) {
                    // 排除明细字段和子项
                    continue;
                }
                //仅仅排除子项并做处理
                if (isset($data['exclude_detail_layout_children']) && $data['exclude_detail_layout_children']) {
                    $layoutInfo = [];
                    if (isset($formControlvalue['control_attribute']['data-efb-layout-info'])) {
                        if (is_array($formControlvalue['control_attribute']['data-efb-layout-info'])) {
                            $layoutInfo = $formControlvalue['control_attribute']['data-efb-layout-info'];
                        }else {
                            $layoutInfo = json_decode($formControlvalue['control_attribute']['data-efb-layout-info'],true);
                        }
                    }
                    foreach ($layoutInfo  as $layoutInfoKey => $layoutInfoValue) {
                        $newLayoutInfo = [];
                        if ( isset($layoutInfoValue['isAmount']) && $layoutInfoValue['isAmount']) {
                              $newLayoutInfo['control_id'] = $layoutInfoValue['id'];
                              $newLayoutInfo['control_type'] = $formControlvalue['control_type'];
                              $newLayoutInfo['control_title'] = $formControlvalue['control_title']."(".$layoutInfoValue['title'].")【".trans('flow.aggregate_field')."】";
                              array_push($result, $newLayoutInfo);
                        }
                    }
                    continue;
                }
                $formControlvalue['haschilen'] = 1;
                $controlTitle= '';
                if (isset($formControlvalue['control_parent_id']) && isset($controlTitleArr[$formControlvalue['control_parent_id']])) {
                    $controlTitle = "【" .$controlTitleArr[$formControlvalue['control_parent_id']]. "】";
                }
                if (isset($formControlvalue['old_control_parent_id']) && isset($controlTitleArr[$formControlvalue['old_control_parent_id']])) {
                    $controlTitle .= "【" .$controlTitleArr[$formControlvalue['old_control_parent_id']]. "】";
                }
                $controlTitle .= $formControlvalue['control_title'];
                $formControlvalue['control_title'] = $controlTitle;
                if (isset($formControlvalue['control_parent_id']) && $formControlvalue['control_parent_id'] && !isset($mingxi[$formControlvalue['control_parent_id']])) {
                    $mingxi[$formControlvalue['control_parent_id']] = [];
                }
                if ($countControlId == 3) {
                    if (!isset($secondMingxi[$formControlvalue['control_id']])) {
                        $secondMingxi[$formControlvalue['control_id']] = [];
                    }
                    array_push($mingxi[$formControlvalue['control_parent_id']], $formControlvalue);
                }else {
                    array_push($result, $formControlvalue);
                }
            //普通字段
            }else{
                // 非明细父级字段是否有子项
                $formControlvalue['haschilen'] = isset($controlAttr['children']) ? 1 : 0;


                // 明细字段一级控件增加明细父级名称前缀
                if ($formControlvalue['control_parent_id']) {
                    if ($countControlId == 4) {
                        // 如果还没有明细父级单元则先补充
                        if (!isset($secondMingxi[$formControlvalue['control_parent_id']])) {
                            $secondMingxi[$formControlvalue['control_parent_id']] = [];
                        }
                    }else if($countControlId == 3) {
                        // 如果还没有明细父级单元则先补充
                        if (!isset($mingxi[$formControlvalue['control_parent_id']])) {
                            $mingxi[$formControlvalue['control_parent_id']] = [];
                        }
                    }

                    // 所有明细控件的父级title
                    if (isset($controlTitleArr[$formControlvalue['control_parent_id']])) {
                        $layoutControlTitle = $controlTitleArr[$formControlvalue['control_parent_id']];
                    }
                    if (isset($formControlvalue['old_control_parent_id']) && isset($controlTitleArr[$formControlvalue['old_control_parent_id']])) {
                        $formControlvalue['control_title'] = '【' . $layoutControlTitle . "】" . "【" .$controlTitleArr[$formControlvalue['old_control_parent_id']]. "】" . $formControlvalue['control_title'];
                    }else {
                        $formControlvalue['control_title'] = '【' . $layoutControlTitle . "】" . $formControlvalue['control_title'];
                    }
                }

                // 不展示明细控件
                if (isset($data['exclude_detail_layout_children']) ) {
                    unset($formControlvalue['control_attribute']);
                    unset($formControlvalue['form_id']);
                    $data['status'] = '';
                }

                if (isset($data['status']) && $data['status'] == 'export') {
                    // 导出模式不展示隐藏控件
                    if (empty($formControlvalue['control_attribute']['data-efb-hide']) || $formControlvalue['control_attribute']['data-efb-hide'] != 'true') {
                         if (!empty($formControlvalue['control_parent_id'])) {
                            if ($countControlId == 4) {
                                // 插入到对应的父级控件后面
                                array_push($secondMingxi[$formControlvalue['control_parent_id']], $formControlvalue);
                            }else if($countControlId == 3) {
                                // 插入到对应的父级控件后面
                                array_push($mingxi[$formControlvalue['control_parent_id']], $formControlvalue);
                            }
                        } else {
                            array_push($result, $formControlvalue);
                        }
                    }
                } else {
                    if ($formControlvalue['control_parent_id']) {
                        // 如果需要排除明细二级的父级
                        if (
                            isset($data['exclude_detail_layout_type_column']) &&
                            $data['exclude_detail_layout_type_column'] &&
                            ($formControlvalue['control_type'] == 'column')
                        ) {
                        } else {
                            if ($countControlId == 4) {
                                // 插入到对应的副级控件后面
                                array_push($secondMingxi[$formControlvalue['control_parent_id']], $formControlvalue);
                            }else if($countControlId == 3) {
                                // 插入到对应的副级控件后面
                                array_push($mingxi[$formControlvalue['control_parent_id']], $formControlvalue);
                            }
                        }
                    } else {
                         array_push($result, $formControlvalue);
                    }
                }
            }
        }
        $isExport = false;
        // 流程查询过滤二级明细
        if (isset($data['status']) && $data['status'] == 'export') {
            $isExport = true;
        }

        if (!$result) {
            // 将二级明细子项放在父级后边
            if (!$isExport && $secondMingxi && $mingxi) {
                $slistSort = array_column($mingxi, 'control_id');
                foreach ($secondMingxi as $smingKey => $smingValue) {
                    if (!empty($smingValue)) {
                        array_splice($mingxi, array_search($smingKey, $slistSort) + 1, 0, $smingValue);
                    }
                }
            }
            return $mingxi;
        }
        // 先将一级明细子项放在父级后边
        if ($mingxi && $result) {
            foreach ($mingxi as $mingKey => $mingValue) {
                if (!empty($mingValue)) {
                    $listSort = array_column($result, 'control_id');
                    array_splice($result, array_search($mingKey, $listSort) + 1, 0, $mingValue);
                }
            }
        }
        // 继续将二级明细子项放在父级后边
        if (!$isExport && $secondMingxi && $result) {
            foreach ($secondMingxi as $smingKey => $smingValue) {
                if (!empty($smingValue)) {
                    $slistSort = array_column($result, 'control_id');
                    array_splice($result, array_search($smingKey, $slistSort) + 1, 0, $smingValue);
                }
            }
        }
        return $result;
    }

    /**
     * 更新控件序号表数据(flow-form-control-sort)
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function modifyFlowFormControlSort($param,$formId)
    {
        $getListParam["search"]["flow_form_id"] = [$formId];
        $getListParam["returntype"] = "object";
        $controlSort = app($this->flowFormControlSortRepository)->getFlowFormControlSortList($getListParam);
        // 已有的分类数据
        $controlSortIdArray = $controlSort->pluck("sort_id")->toArray();
        // 传递过来的分类数据
        foreach ($param as $key => $value) {
            if(!in_array($value["sort_id"], $controlSortIdArray)) {
                // 不在数据库里，插入
                $insertControlSortData = [
                    "flow_form_id" => $formId,
                    "control_sort_id" => "",
                    "belongs_group" => "",
                    "control_id" => ""
                ];
                app($this->flowFormControlSortRepository)->insertData($insertControlSortData);
            } else {
                // 在数据库里，更新
                $updateControlSortData = [
                    "flow_form_id" => $formId,
                    "control_sort_id" => "",
                    "belongs_group" => "",
                    "control_id" => ""
                ];
                app($this->flowFormControlSortRepository)->updateData($updateControlSortData,["sort_id" => [$value["sort_id"]]]);
            }
        }
        return "1";
    }

    /**
     * 更新控件分组表数据(flow-form-control-group)
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function modifyFlowFormControlGroup($param,$formId)
    {
        $getListParam["search"]["flow_form_id"] = [$formId];
        $getListParam["returntype"] = "object";
        $controlGroup = app($this->flowFormControlGroupRepository)->getFlowFormControlGroupList($getListParam);
        // 已有的分类数据
        $controlGroupIdArray = $controlGroup->pluck("group_id")->toArray();
        // 传递过来的分类数据
        foreach ($param as $key => $value) {
            if(!in_array($value["group_id"], $controlGroupIdArray)) {
                // 不在数据库里，插入
                $insertControlGroupData = [
                    "flow_form_id" => $formId,
                    "group_name" => "",
                    "group_sort_id" => ""
                ];
                app($this->flowFormControlGroupRepository)->insertData($insertControlGroupData);
            } else {
                // 在数据库里，更新
                $updateControlGroupData = [
                    "flow_form_id" => $formId,
                    "group_name" => "",
                    "group_sort_id" => ""
                ];
                app($this->flowFormControlGroupRepository)->updateData($updateControlGroupData,["group_id" => [$value["group_id"]]]);
            }
        }
        return "1";
    }
    /**
     * 导出表单明细字段 获取导出字段
     * @param  [type] $param [description]
     */
    function getImportFormDetailLayoutFields($userInfo, $param) {
        if(isset($param['params'])) {
            $param = $param['params'];
        }
        if(!isset($param['formId']) || empty($param['formId'])) {
            return [];
        }
        if(!isset($param['controlId']) || empty($param['controlId'])) {
            return [];
        }
        if (!empty($param['runId'])) {
            if (!isset($param['nodeOperation']) || empty($param['nodeOperation'])) {
                return [];
            }
        }
        // pageMain 解析出来的表单模板规则的info
        $formTemplateRuleInfo = isset($param["formTemplateRuleInfo"]) ? $param["formTemplateRuleInfo"] : "";
        if($formTemplateRuleInfo) {
            $formTemplateRuleInfo = json_decode($formTemplateRuleInfo,true);
        }
        // 字段控制
        if (!empty($param['runId']) || (isset($param['status']) && $param['status'] == "new")) {
            $nodeOperation = json_decode($param['nodeOperation'],true);
            if (!empty($param['runId']) && empty($nodeOperation)) {
                return [];
            }
        }
        // 这里不存在获取打印模板的情况，所以直接获取运行模板
        $ruleId = isset($formTemplateRuleInfo["run"]) ? $formTemplateRuleInfo["run"] : "";
        $templateId = '';
        if($ruleId) {
            $ruleInfo = app($this->flowFormTemplateRuleRepository)->getDetail($ruleId);
            // 子表单id
            $templateId = (isset($ruleInfo["template_id"]) && $ruleInfo["template_id"] && $ruleInfo["template_id"] >0) ? $ruleInfo["template_id"] : "";
        } else {
            // 判断有无parentId,无parentId说明是子表单
            if (!empty($param['parentId']) && isset($param['status']) && $param['status'] == "preview") {
                $templateId = $param['formId'];
            }
        }
        //子表单
        if($templateId) {
            $getListParam["search"]["form_id"] = [$templateId];
            $getListParam["search"]["control_parent_id"] = [$param['controlId']];
            $controlSort = app($this->flowChildFormControlStructureRepository)->getFlowFormControlStructure($getListParam);
        }else{
            $getListParam["search"]["form_id"] = [$param['formId']];
            $getListParam["search"]["control_parent_id"] = [$param['controlId']];
            $controlSort = $this->getFlowFormControlStructure($getListParam);
        }
        $data = [];
        $data[0]['header'] = [];
        foreach ($controlSort as $key => $value) {
            $controlAttribute = $value["control_attribute"];
            $controlAttribute = json_decode($controlAttribute,true);
            if (empty($controlAttribute['children']) && (!empty($param['runId']) || (isset($param['status']) && $param['status'] == "new"))) {
                if (empty($nodeOperation[$value['control_id']]) || (
                    is_array($nodeOperation[$value['control_id']])) &&
                    (!in_array('edit', $nodeOperation[$value['control_id']]) && !in_array('attachmentUpload', $nodeOperation[$value['control_id']]))) {
                    continue;
                }
            }
            if(isset($controlAttribute['data-efb-hide']) && ($controlAttribute['data-efb-hide'] == true || $controlAttribute['data-efb-hide'] == 'true')) {
                continue;
            }
            if (!empty($controlAttribute['children'])) {
                if (!is_array($controlAttribute['children'])) {
                    $controlAttribute['children'] = json_decode($controlAttribute['children'], true);
                }
                if (empty($controlAttribute['children'])) {
                    continue;
                }
                foreach ($controlAttribute['children'] as $childrenValue) {
                    if (empty($childrenValue['type']) || empty($childrenValue['title']) || empty($childrenValue['attribute']['id'])) {
                        continue;
                    }
                    if ((!empty($param['runId']) || (isset($param['status']) && $param['status'] == "new")) && (empty($nodeOperation[$childrenValue['attribute']['id']]) || (
                        is_array($nodeOperation[$childrenValue['attribute']['id']])) &&
                        (!in_array('edit', $nodeOperation[$childrenValue['attribute']['id']]) && !in_array('attachmentUpload', $nodeOperation[$childrenValue['attribute']['id']])))) {
                        continue;
                    }
                    $data[0]['header'][$value['control_title'].'_'.$childrenValue['title']] = $value['control_title'].'_'.$childrenValue['title'];
                    $format = $this->getControlDataFormat($childrenValue['type']);
                    $data[1]['data'][] = [
                        'title'        => $value['control_title'].'_'.$childrenValue['title'],
                        'control_type' => $format['title'] ?? '',
                        'value_type'   => $format['format'] ?? ''
                    ];
                }
            } else {
                $controlType = $value['control_type'] ?? '';
                if ($controlType == 'data-selector' && isset($controlAttribute['data-efb-data-selector-category']) && isset($controlAttribute['data-efb-data-selector-type'])) {
                    $sheetKey = max(array_keys($data)) + 1;
                    if ($controlAttribute['data-efb-data-selector-category'] == 'common' && $controlAttribute['data-efb-data-selector-type'] == 'dept') {
                        $data[$sheetKey] = $this->getDepartmentInfoForDetailLayoutImport();
                    } else if ($controlAttribute['data-efb-data-selector-category'] == 'project' && $controlAttribute['data-efb-data-selector-type'] == 'myJoinProject') {
                        $data[$sheetKey] = $this->getProjectInfoForDetailLayoutImport($userInfo);
                    }
                }
                $data[0]['header'][$value['control_title']] = $value['control_title'];
                $format = $this->getControlDataFormat($value['control_type']);
                $data[1]['data'][] = [
                    'title'        => $value['control_title'] ?? '',
                    'control_type' => $format['title'] ?? '',
                    'value_type'   => $format['format'] ?? ''
                ];
            }
        }
        if (!empty($data[0]['header'])) {
            $data[0]['sheetName'] = trans('flow.detail_layout_fields_3');
            $data[1]['sheetName'] = trans('flow.detail_layout_fields_4');
            $data[1]['header']['title'] = ['data'=>trans('flow.detail_layout_fields_0'),'style'=>['width'=>25]];
            $data[1]['header']['control_type'] = ['data'=>trans('flow.detail_layout_fields_1'),'style'=>['width'=>25]];
            $data[1]['header']['value_type'] = ['data'=>trans('flow.detail_layout_fields_2'),'style'=>['width'=>25]];
        }
        return $data;
    }

    public function getDepartmentInfoForDetailLayoutImport()
    {
        $departmentSheet = [];
        $deptList = app($this->departmentRepository)->listDept(['fields' => ['dept_id', 'dept_name', 'arr_parent_id']]);
        if (empty($deptList)) {
            return [];
        }
        $departmentSheet['sheetName'] = trans('export.dept_info');
        $departmentSheet['header']['dept_name'] = ['data' => trans('export.dept_name'),'style'=>['width'=>30]];
        $departmentSheet['header']['dept_id'] = ['data' => trans('export.dept_id'),'style'=>['width'=>10]];
        $departmentSheet['header']['dept_path'] = ['data' => trans('export.dept_path'),'style'=>['width'=>120]];
        $departmentSheet['header']['input_dept_name'] = ['data' => trans('export.input_dept_name'),'style'=>['width'=>30]];
        $departmentSheet['header']['output_dept_id'] = ['data' => trans('export.output_dept_id'),'style'=>['width'=>10]];
        $deptIdNameArray = [];
        foreach ($deptList as $deptKey => $deptValue) {
            $deptIdNameArray[$deptValue['dept_id']] = $deptValue['dept_name'];
        }
        $deptCount = count($deptList) + 1;
        foreach ($deptList as $deptListKey => $deptListValue) {
            $deptList[$deptListKey]['dept_path'] = '';
            if ($deptListValue['arr_parent_id'] == '0') {
                // 如果是顶级部门
                $deptList[$deptListKey]['dept_path'] = $deptListValue['dept_name'];
            } else {
                // 如果是子部门
                $deptParentId = explode(',', $deptListValue['arr_parent_id']);
                $deptPathArr = array();
                foreach ($deptParentId as $deptParentIdKey => $deptParentIdVal) {
                    if ($deptParentIdKey == '0') continue;
                    if (isset($deptIdNameArray[$deptParentIdVal])) {
                        $deptList[$deptListKey]['dept_path'] .= $deptIdNameArray[$deptParentIdVal].'/';
                    }
                }
                $deptList[$deptListKey]['dept_path'] .= $deptListValue['dept_name'];
            }
        }
        for ($i=0; $i < 30; $i++) {
            $deptList[$i]['input_dept_name'] = '';
            $deptList[$i]['output_dept_id'] = '=IFERROR(VLOOKUP(D'.($i+2).',A2:B'.$deptCount.',2,0),0)';
        }
        $departmentSheet['data'] = $deptList;
        return $departmentSheet;
    }

    public function getProjectInfoForDetailLayoutImport($userInfo)
    {
        $projectSheet = [];
        // 获取我参与的项目
        $projectList = app($this->projectService)->thirdMineProjectQuery($userInfo, ['project_type' => 'join', 'select' => ['manager_id', 'manager_name']])->get()->toArray();
        if (empty($projectList)) {
            return [];
        }
        $projectSheet['sheetName'] = trans('export.project_info');
        $projectSheet['header']['manager_name'] = ['data' => trans('export.manager_name'),'style'=>['width'=>50]];
        $projectSheet['header']['manager_id'] = ['data' => trans('export.manager_id'),'style'=>['width'=>10]];
        $projectSheet['header']['input_manager_name'] = ['data' => trans('export.input_manager_name'),'style'=>['width'=>30]];
        $projectSheet['header']['output_manager_id'] = ['data' => trans('export.output_manager_id'),'style'=>['width'=>10]];
        $projectCount = count($projectList) + 1;
        for ($i=0; $i < 30; $i++) {
            $projectList[$i]['input_manager_name'] = '';
            $projectList[$i]['output_manager_id'] = '=IFERROR(VLOOKUP(C'.($i+2).',A2:B'.$projectCount.',2,0),0)';
        }
        $projectSheet['data'] = $projectList;
        return $projectSheet;
    }

    function getControlDataFormat($value)
    {
        $result = [];
        switch ($value) {
            case 'text':
                $result['title'] = trans('flow.detail_layout_fields_text');
                $result['format'] = trans('flow.detail_layout_fields_5');
                break;
            case 'textarea':
                $result['title'] = trans('flow.detail_layout_fields_textarea');
                $result['format'] = trans('flow.detail_layout_fields_5');
                break;
            case 'radio':
                $result['title'] = trans('flow.detail_layout_fields_radio');
                $result['format'] = trans('flow.detail_layout_fields_5');
                break;
            case 'checkbox':
                $result['title'] = trans('flow.detail_layout_fields_checkbox');
                $result['format'] = trans('flow.detail_layout_fields_5');
                break;
            case 'select':
                $result['title'] = trans('flow.detail_layout_fields_select');
                $result['format'] = trans('flow.detail_layout_fields_6');
                break;
            case 'editor':
                $result['title'] = trans('flow.detail_layout_fields_editor');
                $result['format'] = trans('flow.detail_layout_fields_5');
                break;
            case 'data-selector':
                $result['title'] = trans('flow.detail_layout_fields_data_selector');
                $result['format'] = trans('flow.detail_layout_fields_6');
                break;
            case 'upload':
                $result['title'] = trans('flow.detail_layout_fields_upload');
                $result['format'] = trans('flow.detail_layout_fields_8');
                break;
            case 'dynamic-info':
                $result['title'] = trans('flow.detail_layout_fields_dynamic_info');
                $result['format'] = trans('flow.detail_layout_fields_7');
                break;
            default:
                $result['title'] = trans('flow.detail_layout_fields_title');
                $result['format'] = trans('flow.detail_layout_fields_5');
                break;
        }
        return $result;
    }
    function importFormDetailLayoutData($data,$param) {
        if(!isset($param['params'])) {
            // 导入参数配置错误
            return ['code' => ['0x030033', 'flow']];
        }
        $type = $param['type'];
        $param = $param['params'];
        //判断参数是否完整
        if(!isset($param['formId']) || empty($param['formId'])) {
            // 导入参数配置错误
            return ['code' => ['0x030033', 'flow']];
        }
        if(!isset($param['controlId']) || empty($param['controlId'])) {
            // 导入参数配置错误
            return ['code' => ['0x030033', 'flow']];
        }
        $controlId = explode('_',$param['controlId']);
        if(!isset($controlId[1]) || empty($controlId[1])) {
            // 导入参数配置错误
            return ['code' => ['0x030033', 'flow']];
        }
        // 字段控制
        if (!empty($param['runId']) || (isset($param['status']) && $param['status'] == "new")) {
            $nodeOperation = json_decode($param['nodeOperation'],true);
            if (empty($nodeOperation)) {
                return ['code' => ['0x030033', 'flow']];
            }
        }
        // pageMain 解析出来的表单模板规则的info
        $formTemplateRuleInfo = isset($param["formTemplateRuleInfo"]) ? $param["formTemplateRuleInfo"] : "";
        if($formTemplateRuleInfo) {
            $formTemplateRuleInfo = json_decode($formTemplateRuleInfo,true);
        }
        // 处理header
        $controlToTitle = [];
        $newData = [];
        // 这里不存在获取打印模板的情况，所以直接获取运行模板
        $ruleId = isset($formTemplateRuleInfo["run"]) ? $formTemplateRuleInfo["run"] : "";
        $templateId = '';
        if($ruleId) {
            $ruleInfo = app($this->flowFormTemplateRuleRepository)->getDetail($ruleId);
            // 子表单id
            $templateId = (isset($ruleInfo["template_id"]) && $ruleInfo["template_id"] && $ruleInfo["template_id"] >0) ? $ruleInfo["template_id"] : "";
        } else {
            // 判断有无parentId,无parentId说明是子表单
            if (!empty($param['parentId']) && isset($param['status']) && $param['status'] == "preview") {
                $templateId = $param['formId'];
            }
        }
        //子表单
        if($templateId) {
            $getListParam["search"]["form_id"] = [$templateId];
            $getListParam["search"]["control_parent_id"] = [$param['controlId']];
            $controlInfo = app($this->flowChildFormControlStructureRepository)->getFlowFormControlStructure($getListParam);
        }else{
            $getListParam["search"]["form_id"] = [$param['formId']];
            $getListParam["search"]["control_parent_id"] = [$param['controlId']];
            $controlInfo = $this->getFlowFormControlStructure($getListParam);
        }
         // $controlInfo = $this->getFlowFormControlStructure(['search'=>['control_parent_id'=>[$param['controlId']],'form_id'=>[$param['formId']]]]);
        if (empty($controlInfo)) {
            return $newData;
        }

        $controlInfoArray = [];
        foreach ($controlInfo as $key => $value) {
            $controlAttribute = $value["control_attribute"];
            $controlAttribute = json_decode($controlAttribute, true);
            if (empty($controlAttribute['children']) && (!empty($param['runId']) || (isset($param['status']) && $param['status'] == "new"))) {
                if (empty($nodeOperation[$value['control_id']]) || (
                    is_array($nodeOperation[$value['control_id']])) &&
                    (!in_array('edit', $nodeOperation[$value['control_id']]) && !in_array('attachmentUpload', $nodeOperation[$value['control_id']]))) {
                    continue;
                }
            }
            if(isset($controlAttribute['data-efb-hide']) && ($controlAttribute['data-efb-hide'] == true || $controlAttribute['data-efb-hide'] == 'true')) {
                continue;
            }
            if (empty($value['control_title']) || !in_array($value['control_title'], $data['header'])) {
                if (empty($controlAttribute['children'])) {
                    return ['code' => ['0x030033', 'flow']];
                }
            }
            if (!empty($value['control_id'])) {
                $controlInfoArray[$value['control_id']] = $value;
                $controlAttribute = !empty($value['control_attribute']) ? json_decode($value['control_attribute'], true) : [];
                if (!empty($controlAttribute['data-efb-format'])) {
                    $controlInfoArray[$value['control_id']]['data-efb-format'] = $controlAttribute['data-efb-format'];
                }
                if (!empty($controlAttribute['children'])) {
                    if (!is_array($controlAttribute['children'])) {
                        $controlAttribute['children'] = json_decode($controlAttribute['children'], true);
                    }
                    if (empty($controlAttribute['children'])) {
                        continue;
                    }
                    foreach ($controlAttribute['children'] as $childrenValue) {
                        if (empty($childrenValue['type']) || empty($childrenValue['title']) || empty($childrenValue['attribute']['id'])) {
                            continue;
                        }
                        if ((!empty($param['runId']) || (isset($param['status']) && $param['status'] == "new")) && (empty($nodeOperation[$childrenValue['attribute']['id']]) || (
                            is_array($nodeOperation[$childrenValue['attribute']['id']])) &&
                            (!in_array('edit', $nodeOperation[$childrenValue['attribute']['id']]) && !in_array('attachmentUpload', $nodeOperation[$childrenValue['attribute']['id']])))) {
                            continue;
                        }
                        $controlToTitle[$value['control_title'].'_'.$childrenValue['title']] = $childrenValue['attribute']['id'];
                    }
                } else {
                    $controlToTitle[$value['control_title']] = $value['control_id'];
                }
            }
        }

        if (empty($controlInfoArray) || empty($controlToTitle)) {
            return $newData;
        }

        // 处理data数据
        foreach ($data['data'] as $key => $value) {
            foreach ($value as $_key => $_value) {
                if ($_value === null) {
                    $_value = '';
                }
                if(isset($data['header'][$_key])) {
                    $title = $data['header'][$_key];
                    $controlId = $controlToTitle[$title];
                    $controlType = $controlInfoArray[$controlId]['control_type'] ?? '';
                    $controlAttribute = $controlInfoArray[$controlId]['control_attribute'] ?? '';
                    $controlAttribute = json_decode($controlAttribute, true);
                    if (!empty($controlType) && ($controlType != 'upload') && is_array($_value)) {
                        // 如果非附件上传控件里拿到的是数组的值，说明是图片之类的数据，直接处理为空
                        $_value = '';
                    }
                    if ($controlType == 'text' && (!empty($_value) && !empty($controlInfoArray[$controlId]['data-efb-format']) && in_array($controlInfoArray[$controlId]['data-efb-format'], ['date', 'time', 'datetime']))) {
                        if (!is_string($_value)) {
                            $newData[$key][$controlId] = '';
                            continue;
                        }
                        $valueStrToTime = strtotime($_value);
                        if (!$valueStrToTime) {
                            $newData[$key][$controlId] = '';
                            continue;
                        }
                        switch ($controlInfoArray[$controlId]['data-efb-format']) {
                            case 'date':
                                $_value = date("Y-m-d", $valueStrToTime);
                                break;
                            case 'time':
                                $_value = date("H:i", $valueStrToTime);
                                break;
                            case 'datetime':
                                $_value = date("Y-m-d H:i", $valueStrToTime);
                                break;
                        }
                    }
                    if ($controlType == 'text' && (!empty($_value) && !empty($controlInfoArray[$controlId]['data-efb-format']) && $controlInfoArray[$controlId]['data-efb-format'] == 'number')) {
                        if (!is_numeric($_value)) {
                            $_value = preg_replace('/[^\d.]/', '', $_value);
                        }
                    }
                    if ($controlType == 'checkbox') {
                        if (!is_string($_value) && !is_numeric($_value)) {
                            $_value = '';
                        }
                        $_value = explode(',', trim($_value, ','));
                    }

                    if ($controlType == 'data-selector' && isset($controlAttribute['data-efb-data-selector-category']) && isset($controlAttribute['data-efb-data-selector-type'])) {
                        if ($controlAttribute['data-efb-data-selector-category'] == 'common' && $controlAttribute['data-efb-data-selector-type'] == 'dept') {
                            $deptIdInfo = DB::table('department')->select('dept_id')->where('dept_name', $_value)->first();
                            $_value = $deptIdInfo->dept_id ?? $_value;
                        } else if ($controlAttribute['data-efb-data-selector-category'] == 'project' && $controlAttribute['data-efb-data-selector-type'] == 'myJoinProject') {
                            $projectIdInfo = DB::table('project_manager')->select('manager_id')->where('manager_name', $_value)->first();
                            $_value = $projectIdInfo->manager_id ?? $_value;
                        }
                    }

                    $newData[$key][$controlId] = $_value;
                }
            }
        }
        return $newData;
        //获取流程原有明细数据
        // $tableName = 'zzzz_flow_data_'.$param['formId'].'_'.$controlId[1];
        // $oldData = [];
        // if (Schema::hasTable($tableName)) {
        //     if(isset($param['runId']) && !empty($param['runId'])) {
        //         $runId = $param['runId'];
        //         $tableData = DB::table($tableName)->where('run_id', $runId)->get();
        //         if(!empty($tableData)) {
        //             foreach ($tableData as $key => $oldDatas) {
        //                 if($oldDatas->amount == 'amount') {
        //                     continue;
        //                 }
        //                 foreach ($controlToTitle as $file) {
        //                     $oldData[$key][$file] = $oldDatas->$file;
        //                 }
        //             }
        //         }
        //     }
        // }

        // //根据导入类型合并原油数据和文件数据
        // $result = [];
        // switch ($type) {
        //     case 1://仅新增数据
        //         $result = array_merge($oldData,$newData);
        //         break;
        //     case 3://新增数据并清除原有数据
        //         $result = $newData;
        //         break;
        //     default:
        //         $result = $newData;
        //         break;
        // }
        // return $result;
    }


    /**
     * 【流程表单】 子表单-生成子表单
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function createChildForm($param)
    {
        if(!isset($param['parent_id']) || empty($param['parent_id'])) {
            return ['code' => ['0x000003', 'common']];
        }

        //新建flowChildType表
        $childTypeData = [
            'form_name' => (!isset($param['form_name']) || empty($param['form_name'])) ? '未命名' : $param['form_name'],
            'print_model' => $param['print_model'] ?? '',
            'parent_id' => $param['parent_id'],
            'form_type' => $param['form_type'],
            'form_description' => $param['form_description'] ?? '',
            'created_at' => date('Y-m-d H:i:s',time()),
        ];
        $childFormId = app($this->flowChildFormTypeRepository)->insertGetId($childTypeData);

        if (!$childFormId) {
            return false;
        }

        // 表单控件为空
        if(!isset($param['control']) || !$param['control']) {
            return $childFormId;
        }

        $controlSort = 0;
        // 遍历控件插入控件数据表formsructure
        foreach ($param['control'] as $controlKey => $controlValue) {
            $controlStructureData = [
                "form_id"           => $childFormId,
                "control_id"        => $controlKey,
                "control_title"     => $controlValue["title"],
                "control_type"      => $controlValue["type"],
                "control_attribute" => json_encode($controlValue["attribute"]),
                "control_parent_id" => "",
                "sort" => $controlSort
            ];
            $controlSort++;
            app($this->flowChildFormControlStructureRepository)->insertData($controlStructureData);

            // 明细字段处理
            if($controlValue["type"] == "detail-layout") {
                $detailInfo = $controlValue["info"] ?? array();
                if(count($detailInfo)) {
                    $tmpDetailInfo = $detailInfo;
                    foreach ($tmpDetailInfo as $tmpKey => $tmpDetailValue) {
                        // 明细二级单元处理成一级单元
                        if (
                            (
                                (
                                    isset($tmpDetailValue['type']) &&
                                    ($tmpDetailValue['type'] == "column")
                                ) ||
                                (
                                    isset($tmpDetailValue['attribute']['type']) &&
                                    ($tmpDetailValue['attribute']['type'] == 'column')
                                )
                            ) &&
                            isset($tmpDetailValue['attribute']['children'])
                        ) {
                            $grandchildrenArr = is_string($tmpDetailValue['attribute']['children']) ? json_decode($tmpDetailValue['attribute']['children'], true) : $tmpDetailValue['attribute']['children'];
                            if (!$grandchildrenArr) {
                                continue;
                            }

                            foreach ($grandchildrenArr as $grandChildValue) {
                                // 父级保存实际父级，另在attribute中保存祖父级
                                $grandChildValue['control_parent_id'] = $tmpDetailValue['attribute']['id'];
                                $grandChildValue['attribute']['control_grandparent_id'] = $controlKey;
                                $detailInfo[$grandChildValue['attribute']['id'] ?? $grandChildValue['id']] = $grandChildValue;
                            }
                        }
                        // 二级明细字段处理
                        if($tmpDetailValue["type"] == "detail-layout") {
                            $_detailInfo = $tmpDetailValue["info"] ?? array();
                            if(count($_detailInfo)) {
                                $_tmpDetailInfo = $_detailInfo;
                                foreach ($_tmpDetailInfo as $tmpDetailValue) {
                                    // 明细二级单元处理成一级单元
                                    if (
                                        (
                                            (
                                                isset($tmpDetailValue['type']) &&
                                                ($tmpDetailValue['type'] == "column")
                                            ) ||
                                            (
                                                isset($tmpDetailValue['attribute']['type']) &&
                                                ($tmpDetailValue['attribute']['type'] == 'column')
                                            )
                                        ) &&
                                        isset($tmpDetailValue['attribute']['children'])
                                    ) {
                                        $grandchildrenArr = is_string($tmpDetailValue['attribute']['children']) ? json_decode($tmpDetailValue['attribute']['children'], true) : $tmpDetailValue['attribute']['children'];
                                        if (!$grandchildrenArr) {
                                            continue;
                                        }

                                        foreach ($grandchildrenArr as $grandChildValue) {
                                            // 父级保存实际父级，另在attribute中保存祖父级
                                            $grandChildValue['control_parent_id'] = $tmpDetailValue['attribute']['id'];
                                            $grandChildValue['attribute']['control_grandparent_id'] = $controlKey;
                                            $_detailInfo[$grandChildValue['attribute']['id'] ?? $grandChildValue['id']] = $grandChildValue;
                                        }
                                    }
                                }

                                // 将明细子项挨个插入
                                $detailSort = 0;
                                foreach ($_detailInfo as $detailKey => $detailValue) {
                                    if(isset($detailValue["title"])) {
                                        $controlStructurelayoutData = [
                                            "form_id"           => $childFormId,
                                            "control_id"        => $detailKey,
                                            "control_title"     => $detailValue["title"],
                                            "control_type"      => $detailValue["type"],
                                            "control_attribute" => json_encode($detailValue["attribute"]),
                                            "control_parent_id" => $detailValue['control_parent_id'] ?? $tmpKey,
                                            "sort"              => $detailSort
                                        ];
                                        $detailSort++;
                                        app($this->flowChildFormControlStructureRepository)->insertData($controlStructurelayoutData);
                                    }
                                }
                            }
                        }
                    }

                    // 将明细子项挨个插入
                    $detailSort = 0;
                    foreach ($detailInfo as $detailKey => $detailValue) {
                        if(isset($detailValue["title"])) {
                            $controlStructurelayoutData = [
                                "form_id"           => $childFormId,
                                "control_id"        => $detailKey,
                                "control_title"     => $detailValue["title"],
                                "control_type"      => $detailValue["type"],
                                "control_attribute" => json_encode($detailValue["attribute"]),
                                "control_parent_id" => $detailValue['control_parent_id'] ?? $controlKey,
                                "sort"              => $detailSort
                            ];
                            $detailSort++;
                            app($this->flowChildFormControlStructureRepository)->insertData($controlStructurelayoutData);
                        }
                    }
                }
            }
        }
        return $childFormId;
    }
    /**
     * 【流程表单】 子表单-子表单列表
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function getChildFormList($parentId, $page =0, $limit = 10)
    {
        if(empty($parentId)) {
            return ['code' => ['0x000003', 'common']];
        }
        $param = [
            'search'=>['parent_id'=>[$parentId]],
            'fields'=>['form_id', 'form_name', 'parent_id', 'form_type', 'form_description'],
            'autoFixPage' => 1,
        ];
        $param['page'] = $page;
        $param['limit'] = $limit;
        return $this->response(app($this->flowChildFormTypeRepository), 'getFlowFormTotal', 'getFlowForm', $param);
    }
    /**
     * 【流程表单】 子表单-子表单列表
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function getChildFormListByFlowId($flowId,$param)
    {
        if(empty($flowId)) {
            return [];
        }
        $parentId = app($this->flowTypeRepository)->getDetail($flowId);
        if($parentId) {
            $parentId = $parentId->form_id;
        }else{
            return [];
        }
        $params = ['search'=>['parent_id'=>[$parentId]],'fields'=>['form_id','form_name']];
        $total = app($this->flowChildFormTypeRepository)->getFlowFormTotal($params);
        $list = app($this->flowChildFormTypeRepository)->getFlowForm($params);

        // 主表单前是否加提示
        $preMainTitle = (isset($param['no_pre_main_title']) && $param['no_pre_main_title']) ? '' : '[主表单]';

        //追加主表单信息
        $parentInfo = app($this->flowFormTypeRepository)->getDetail($parentId);
        $data = [
            [
                'form_id' => -1,
                'form_name' => $preMainTitle . $parentInfo->form_name
            ]
        ];

        $list = array_merge($data, $list);
        $total = $total + 1;
        return ['total' => $total, 'list' => $list];
    }
    /**
     * 【流程表单】 子表单-获取子表单详情
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function getChildFormDetail($formId)
    {
        if(empty($formId)) {
            return ['code' => ['0x000003', 'common']];
        }
        $chlidFormDetail = app($this->flowChildFormTypeRepository)->getDetail($formId);
        return $chlidFormDetail;
    }
    /**
     * 【流程表单】 子表单-删除单个子表单
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function deleteChildForm($formId)
    {
        if(empty($formId)) {
            return ['code' => ['0x000003', 'common']];
        }
        // 判断待删除的子表单，是否被表单模板规则占用，被占用的，不能删除
        $param = [
            'search'=>['template_id'=>[$formId]],
            'returntype'=>'object'
        ];
        $ruleInfo = app($this->flowFormTemplateRuleRepository)->getFlowFormTemplateRule($param);
        if($ruleInfo->count() > 0) {
            $ruleInfoFlow = $ruleInfo->pluck("flow_id")->toArray();
            $ruleInfoFlow = array_unique($ruleInfoFlow);
            $flowTypeInfo = app($this->flowTypeRepository)->getFlowTypeList(["search"=>["flow_id"=>[$ruleInfoFlow,"in"]],"returntype"=>"object"]);
            $flowTypeInfo = $flowTypeInfo->pluck("flow_name")->toArray();
            $flowTypeName = implode(",", $flowTypeInfo);
            return ["flag" => "occupy","flow_name" => $flowTypeName];
        } else {
            app($this->flowChildFormControlStructureRepository)->deleteByWhere(['form_id'=>[$formId]]);
            app($this->flowChildFormTypeRepository)->deleteByWhere(['form_id'=>[$formId]]);
            return true;
        }
    }
    /**
     * 【流程表单】 子表单-编辑单个子表单
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function editChildForm($formId,$data)
    {
        if(empty($formId)) {
            return ['code' => ['0x000003', 'common']];
        }
        if(empty($data)) {
            return false;
        }

        if(!isset($data['control'])) {
            $data['control'] = [];
        }

        $updataData = [
            'form_name' => $data['form_name'] ?? '未命名',
            'print_model' => $data['print_model'] ?? '',
            'form_description' => $data['form_description'] ?? '',
        ];
        if(isset($data['form_type']) && !empty($data['form_type'])) {
            $updataData['form_type'] = $data['form_type'];
        }

        //更新flowChildType表
        if(app($this->flowChildFormTypeRepository)->updateData($updataData, ['form_id'=>[$formId]])) {
            $formControlArray = [];
            // 获取原控件列表
            $formControlStructure = app($this->flowChildFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$formId]]]);

            //清除原数据
            app($this->flowChildFormControlStructureRepository)->deleteByWhere(['form_id'=>[$formId]]);
            $controlSort = 0;

            foreach ($data['control'] as $controlKey => $controlValue) {
                $controlStructureData = [
                    "form_id"           => $formId,
                    "control_id"        => $controlKey,
                    "control_title"     => $controlValue["title"],
                    "control_type"      => $controlValue["type"],
                    "control_attribute" => json_encode($controlValue["attribute"]),
                    "control_parent_id" => "",
                    "sort" => $controlSort
                ];
                $controlSort ++;
                app($this->flowChildFormControlStructureRepository)->insertData($controlStructureData);
                $formControlArray[$controlKey] = $controlKey;
                // 明细字段处理
                if($controlValue["type"] == "detail-layout") {
                    $detailInfo = isset($controlValue["info"]) ? $controlValue["info"] : array();
                    $tmpDetailInfo = $detailInfo;
                    foreach ($tmpDetailInfo as $tmpKey => $tmpDetailValue) {
                        // 明细二级单元处理成一级单元
                        if (
                            (
                                (
                                    isset($tmpDetailValue['type']) &&
                                    ($tmpDetailValue['type'] == "column")
                                ) ||
                                (
                                    isset($tmpDetailValue['attribute']['type']) &&
                                    ($tmpDetailValue['attribute']['type'] == 'column')
                                )
                            ) &&
                            isset($tmpDetailValue['attribute']['children'])
                        ) {
                            $grandchildrenArr = is_string($tmpDetailValue['attribute']['children']) ? json_decode($tmpDetailValue['attribute']['children'], true) : $tmpDetailValue['attribute']['children'];
                            if (!$grandchildrenArr) {
                                continue;
                            }

                            foreach ($grandchildrenArr as $grandChildValue) {
                                // 父级保存实际父级，另在attribute中保存祖父级
                                $grandChildValue['control_parent_id'] = $tmpDetailValue['attribute']['id'];
                                $grandChildValue['attribute']['control_grandparent_id'] = $controlKey;
                                $_tempKey = $grandChildValue['attribute']['id'];
                                $detailInfo[$grandChildValue['attribute']['id'] ?? $grandChildValue['id']] = $grandChildValue;
                                // 子项为二级明细情况处理
                                $temp_detailInfo = isset($grandChildValue["info"]) ? $grandChildValue["info"] : array();
                                if (!empty($temp_detailInfo)) {
                                    $_tmpSecondDetailInfo = $temp_detailInfo;
                                    foreach ($_tmpSecondDetailInfo as $temp_tmpDetailValue) {
                                        // 明细二级单元处理成一级单元
                                        if (
                                            (
                                                (
                                                    isset($temp_tmpDetailValue['type']) &&
                                                    ($temp_tmpDetailValue['type'] == "column")
                                                ) ||
                                                (
                                                    isset($temp_tmpDetailValue['attribute']['type']) &&
                                                    ($temp_tmpDetailValue['attribute']['type'] == 'column')
                                                )
                                            ) &&
                                            isset($temp_tmpDetailValue['attribute']['children'])
                                        ) {
                                            $grandchildrenArr = is_string($temp_tmpDetailValue['attribute']['children']) ? json_decode($temp_tmpDetailValue['attribute']['children'], true) : $temp_tmpDetailValue['attribute']['children'];
                                            if (!$grandchildrenArr) {
                                                continue;
                                            }

                                            foreach ($grandchildrenArr as $grandChildValue) {
                                                // 父级保存实际父级，另在attribute中保存祖父级
                                                $grandChildValue['control_parent_id'] = $temp_tmpDetailValue['attribute']['id'];
                                                $grandChildValue['attribute']['control_grandparent_id'] = $_tempKey;
                                                $temp_detailInfo[$grandChildValue['attribute']['id'] ?? $grandChildValue['id']] = $grandChildValue;
                                            }
                                        }
                                    }
                                    if(count($temp_detailInfo)) {
                                        $detailSort = 0;
                                        foreach ($temp_detailInfo as $detailKey => $detailValue) {
                                            if(isset($detailValue["title"])) {
                                                $controlStructurelayoutData = [
                                                    "form_id"           => $formId,
                                                    "control_id"        => $detailKey,
                                                    "control_title"     => $detailValue["title"],
                                                    "control_type"      => $detailValue["type"],
                                                    "control_attribute" => json_encode($detailValue["attribute"]),
                                                    "control_parent_id" => $detailValue['control_parent_id'] ?? $_tempKey,
                                                    "sort"              => $detailSort
                                                ];
                                                $detailSort ++;
                                                app($this->flowChildFormControlStructureRepository)->insertData($controlStructurelayoutData);
                                                $formControlArray[$detailKey] = $detailKey;
                                            }
                                        }
                                    }
                                }
                            }

                        }

                        // 二级明细字段处理
                        if($tmpDetailValue["type"] == "detail-layout") {
                            $_detailInfo = isset($tmpDetailValue["info"]) ? $tmpDetailValue["info"] : array();
                            $tmpSecondDetailInfo = $_detailInfo;
                            foreach ($tmpSecondDetailInfo as $_tmpDetailValue) {
                                // 明细二级单元处理成一级单元
                                if (
                                    (
                                        (
                                            isset($_tmpDetailValue['type']) &&
                                            ($_tmpDetailValue['type'] == "column")
                                        ) ||
                                        (
                                            isset($_tmpDetailValue['attribute']['type']) &&
                                            ($_tmpDetailValue['attribute']['type'] == 'column')
                                        )
                                    ) &&
                                    isset($_tmpDetailValue['attribute']['children'])
                                ) {
                                    $grandchildrenArr = is_string($_tmpDetailValue['attribute']['children']) ? json_decode($_tmpDetailValue['attribute']['children'], true) : $_tmpDetailValue['attribute']['children'];
                                    if (!$grandchildrenArr) {
                                        continue;
                                    }

                                    foreach ($grandchildrenArr as $grandChildValue) {
                                        // 父级保存实际父级，另在attribute中保存祖父级
                                        $grandChildValue['control_parent_id'] = $_tmpDetailValue['attribute']['id'];
                                        $grandChildValue['attribute']['control_grandparent_id'] = $controlKey;
                                        $_tempKey = $grandChildValue['attribute']['id'];
                                        $_detailInfo[$grandChildValue['attribute']['id'] ?? $grandChildValue['id']] = $grandChildValue;
                                        // 子项为二级明细情况处理
                                        $temp_detailInfo = isset($grandChildValue["info"]) ? $grandChildValue["info"] : array();
                                        if (!empty($temp_detailInfo)) {
                                            $_tmpSecondDetailInfo = $temp_detailInfo;
                                            foreach ($_tmpSecondDetailInfo as $temp_tmpDetailValue) {
                                                // 明细二级单元处理成一级单元
                                                if (
                                                    (
                                                        (
                                                            isset($temp_tmpDetailValue['type']) &&
                                                            ($temp_tmpDetailValue['type'] == "column")
                                                        ) ||
                                                        (
                                                            isset($temp_tmpDetailValue['attribute']['type']) &&
                                                            ($temp_tmpDetailValue['attribute']['type'] == 'column')
                                                        )
                                                    ) &&
                                                    isset($temp_tmpDetailValue['attribute']['children'])
                                                ) {
                                                    $grandchildrenArr = is_string($temp_tmpDetailValue['attribute']['children']) ? json_decode($temp_tmpDetailValue['attribute']['children'], true) : $temp_tmpDetailValue['attribute']['children'];
                                                    if (!$grandchildrenArr) {
                                                        continue;
                                                    }

                                                    foreach ($grandchildrenArr as $grandChildValue) {
                                                        // 父级保存实际父级，另在attribute中保存祖父级
                                                        $grandChildValue['control_parent_id'] = $temp_tmpDetailValue['attribute']['id'];
                                                        $grandChildValue['attribute']['control_grandparent_id'] = $_tempKey;
                                                        $temp_detailInfo[$grandChildValue['attribute']['id'] ?? $grandChildValue['id']] = $grandChildValue;
                                                    }
                                                }
                                            }
                                            if(count($temp_detailInfo)) {
                                                $detailSort = 0;
                                                foreach ($temp_detailInfo as $detailKey => $detailValue) {
                                                    if(isset($detailValue["title"])) {
                                                        $controlStructurelayoutData = [
                                                            "form_id"           => $formId,
                                                            "control_id"        => $detailKey,
                                                            "control_title"     => $detailValue["title"],
                                                            "control_type"      => $detailValue["type"],
                                                            "control_attribute" => json_encode($detailValue["attribute"]),
                                                            "control_parent_id" => $detailValue['control_parent_id'] ?? $_tempKey,
                                                            "sort"              => $detailSort
                                                        ];
                                                        $detailSort ++;
                                                        app($this->flowChildFormControlStructureRepository)->insertData($controlStructurelayoutData);
                                                        $formControlArray[$detailKey] = $detailKey;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if(count($_detailInfo)) {
                                $detailSort = 0;
                                foreach ($_detailInfo as $detailKey => $detailValue) {
                                    if(isset($detailValue["title"])) {
                                        $controlStructurelayoutData = [
                                            "form_id"           => $formId,
                                            "control_id"        => $detailKey,
                                            "control_title"     => $detailValue["title"],
                                            "control_type"      => $detailValue["type"],
                                            "control_attribute" => json_encode($detailValue["attribute"]),
                                            "control_parent_id" => $detailValue['control_parent_id'] ?? $tmpKey,
                                            "sort"              => $detailSort
                                        ];
                                        $detailSort ++;
                                        app($this->flowChildFormControlStructureRepository)->insertData($controlStructurelayoutData);
                                        $formControlArray[$detailKey] = $detailKey;
                                    }
                                }
                            }
                        }
                    }
                    if(count($detailInfo)) {
                        $detailSort = 0;
                        foreach ($detailInfo as $detailKey => $detailValue) {
                            if(isset($detailValue["title"])) {
                                $controlStructurelayoutData = [
                                    "form_id"           => $formId,
                                    "control_id"        => $detailKey,
                                    "control_title"     => $detailValue["title"],
                                    "control_type"      => $detailValue["type"],
                                    "control_attribute" => json_encode($detailValue["attribute"]),
                                    "control_parent_id" => $detailValue['control_parent_id'] ?? $controlKey,
                                    "sort"              => $detailSort
                                ];
                                $detailSort ++;
                                app($this->flowChildFormControlStructureRepository)->insertData($controlStructurelayoutData);
                                $formControlArray[$detailKey] = $detailKey;
                            }
                        }
                    }
                }
            }

            // 被删除的子表单控件把原数据另存
            foreach($formControlStructure as $v){
                if(
                    !isset($formControlArray[$v['control_id']]) || // 新控件列表中不存在
                    !$data['control'] // 新表单为空，将全部原控件都另存
                ){
                    $formControlDropArray = [];
                    $formControlDropArray['form_id'] = $formId;
                    $formControlDropArray['control_id'] = $v['control_id'];
                    $formControlDropArray['control_type'] = $v['control_type'];
                    $formControlDropArray['control_title'] = $v['control_title'];
                    $formControlDropArray['control_parent_id'] = $v['control_parent_id'];
                    $formControlDropArray['control_attribute'] = $v['control_attribute'];
                    app($this->flowChildFormControlDropRepository)->insertData($formControlDropArray);
                }
            }
            return true;
        }
    }
    /**
     * 【流程表单】 子表单-根据父表单删除所有子表单
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function deleteChildFormByParent($parentId)
    {
        if(empty($parentId)) {
            return ['code' => ['0x000003', 'common']];
        }
        //收集子表单id
        $chlidFormList = app($this->flowChildFormTypeRepository)->getFlowForm(['search'=>['parent_id'=>[$parentId]],'returntype'=>'object']);
        if($chlidFormList->count()>0) {
            $chlidFormList = $chlidFormList->pluck('form_id')->toArray();
        }else{
            $chlidFormList = [];
        }
        if(!empty($chlidFormList)) {
            app($this->flowChildFormControlStructureRepository)->deleteByWhere(['form_id'=>[$chlidFormList,'in']]);
        }
        app($this->flowChildFormTypeRepository)->deleteByWhere(['parent_id'=>[$parentId]]);
        return true;
    }
    /**
     * 【流程表单】 子表单-编辑所有子表单
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function editAllChildForm($data)
    {
        if(!empty($data)){
            foreach ($data as $value) {
                $formId = $value['form_id'];
                unset($value['form_id']);
                $this->editChildForm($formId,$value);
            }
            return true;
        }
        return false;
    }

    /**
     * 处理表单控件多语言 将各个地方调用app($this->flowFormControlStructureRepository)->getFlowFormControlStructure
     * 的方法集中替换成这个方法  在这里统一处理
     */
    function getFlowFormControlStructure($param) {
        if(isset($param['fields']) && !empty($param['fields'])) {
            if (!is_array($param['fields'])) {
                $param['fields'] = explode(',', $param['fields']);
            }
            $param['fields'] = array_unique(array_merge($param['fields'],['control_title','control_id','form_id']));
        }else{
            $param['fields'] = ['*'];
        }
        $flowFormData = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure($param);
        foreach ($flowFormData as $key => $value) {
            $transKey = 'flow_form_control_structure.control_title.'.$value['form_id'].'_'.$value['control_id'];
            //contorl_title转化 这里特殊处理一下明细子项的，后期明细子项的也加上多语言了这里就去掉判断
            if (substr_count($value['control_id'], '_') >= 2) {
                $flowFormData[$key]['control_title'] = $value['control_title'];
            } else {
                $controlTitle = mulit_trans_dynamic($transKey);
                $flowFormData[$key]['control_title'] = $controlTitle ?: $value['control_title'];
            }
            //属性里的title转化
            if(isset($value["control_attribute"]) && !empty($value["control_attribute"])) {
                $controlAttribute = $value["control_attribute"];
                $controlAttribute = json_decode($controlAttribute,true);
                $controlAttribute["title"] = $flowFormData[$key]['control_title'];
                $flowFormData[$key]['control_attribute'] = json_encode($controlAttribute);
            }
        }
        return $flowFormData;
    }


    /**
     * 处理子表单控件多语言
     */
    function getFlowChildFormControlStructure($param) {
        if(isset($param['fields']) && !empty($param['fields'])) {
            $param['fields'] = array_unique(array_merge($param['fields'],['control_title','control_id','form_id']));
        }else{
            $param['fields'] = ['*'];
        }
        $flowFormData = app($this->flowChildFormControlStructureRepository)->getFlowFormControlStructure($param);
        foreach ($flowFormData as $key => $value) {
            $transKey = 'flow_child_form_control_structure.control_title.'.$value['form_id'].'_'.$value['control_id'];
            //contorl_title转化 这里特殊处理一下明细子项的，后期明细子项的也加上多语言了这里就去掉判断
            if (substr_count($value['control_id'], '_') == 2) {
                $flowFormData[$key]['control_title'] = $value['control_title'];
            } else {
                $controlTitle = mulit_trans_dynamic($transKey);
                $flowFormData[$key]['control_title'] = $controlTitle ?: $value['control_title'];
            }
            //属性里的title转化
            if(isset($value["control_attribute"]) && !empty($value["control_attribute"])) {
                $controlAttribute = $value["control_attribute"];
                $controlAttribute = json_decode($controlAttribute,true);
                if(isset($controlAttribute["title"])){
                    $controlAttribute["title"] = $flowFormData[$key]['control_title'];
                }
                $flowFormData[$key]['control_attribute'] = json_encode($controlAttribute);
            }
        }
        return $flowFormData;
    }

    /**
     *  【流程表单】 更新主表单-子表单结构
     *   formId：主表单
     *   childFormId：子表单
     */
    public function changeFormControlStructure($formId, $childFormId = null, $updateType= null) {
    	if(empty($formId)){
    		return false;
    	}
    	if(empty($childFormId)){
    		return false;
        }
        // 要处理的子表单变量
        $childFormIdToHandle = is_array($childFormId) ? $childFormId : explode(',', trim($childFormId,','));

        // 获取主表单基本数据
    	$flowForm = app($this->flowFormTypeRepository)->getDetail($formId);
		// 子表单控件内容为空
		if(empty($flowForm['print_model'])){
			return false;
        }
    	// 获取主表单结构
    	$mainFormControlStructure = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$formId]]]);
        // 解析获取主表单控件属性
        $mainPrintControl[$flowForm['form_type']] = $this->getFormControl($flowForm['print_model'], $flowForm['form_type']);

        // 全部子表单列表
        $chlidFormList = app($this->flowChildFormTypeRepository)->getFieldInfo(['parent_id'=>$formId]);
        // 子表单列表预处理
        foreach($chlidFormList as $key => $value){
            // 不在区间的子表单不处理
        	if(!in_array($value['form_id'], $childFormIdToHandle)) {
				unset($chlidFormList[$key]);
            }
            // 如果子表单类型和主表单不一致，暂时不能更新属性
            if ($value['form_type'] != $flowForm['form_type']) {
				unset($chlidFormList[$key]);
            }
            // 子表单的表单类型和主表单不同时再去获取一次表单控件属性
            // if (
            //     ($value['form_type'] != $flowForm['form_type']) &&
            //     !isset($mainPrintControl[$value['form_type']])
            // ) {
            //     $mainPrintControl[$value['form_type']] = $this->getFormControl($flowForm['print_model'], $value['form_type']);
            // }
        }

        // 遍历要处理的子表单
        foreach($chlidFormList as $value){
            // 子表单类型
            $childFormType = $value['form_type'];
            // 标准版子表单暂时只能更新控件属性
            $currentUpdateType = ($childFormType == 'complex') ? 'attribute' : $updateType;
            // 主表单控件属性使用和子表单相同表单类型的数据
            $currentMainPrintControl = $mainPrintControl[$value['form_type']];

            $printModel = $value['print_model'];
            // 获取子表单控件属性
            $childPrintControl = $this->getFormControl($printModel, $value['form_type']);
            // 获取子表单控件结构
            $childFormControlStructure = app($this->flowChildFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$value['form_id']]]]);

            // 被删除的子表单控件
        	$childControl = app($this->flowChildFormControlDropRepository)->getFlowFormTrashedControl(['search' => ["form_id" => [$value['form_id']]]]);

        	// $childControl = [];
        	// if ($flowForm['form_type'] == 'simple' && $updateType != 'structure'){
        	// 	$childControl = app($this->flowChildFormControlDropRepository)->getFlowFormTrashedControl(['search' => ["form_id" => [$value['form_id']]]]);
        	// } else if ($flowForm['form_type'] == 'simple' && $updateType == 'structure'){
            // 	$childControl = app($this->flowChildFormControlDropRepository)->getFlowFormTrashedControl(['search' => ["form_id" => [$value['form_id']]]]);

        	// } else {
        		// $childControl = app($this->flowChildFormControlDropRepository)->getFlowFormTrashedControl(['search' => ["form_id" => [$value['form_id']]]]);
            // }

            // 只更新控件属性时
        	if ($currentUpdateType != 'structure') {
                $formControlArray = [];
                // 子表单控件ID->auto_id
        		foreach($childFormControlStructure as $item){
        			$formControlArray[$item['control_id']] = $item['control_auto_id'];
                }
                // 主表单中有、子表单中没有、子表单已删除控件也没有的控件补充进子表单已删除控件列表
        		foreach($mainFormControlStructure as $control){
        			if(!isset($formControlArray[$control['control_id']]) && !isset($childControl[$control['control_id']])){
        				$childControl[$control['control_id']] = $control['control_id'];
        			}
        		}
            }

            // 待更新控件
            $attributeArr = [];
            $itemArr = [];
            // 遍历子表单控件结构
            foreach($childFormControlStructure as $item){
                // 遍历主表单控件结构
            	foreach($mainFormControlStructure as $control){
                    // 主表单、子表单都存在的控件，将structure表字段修改
					if($item['control_id'] == $control['control_id']){
                        $updateData = [
                            'control_attribute' => $control['control_attribute'], // control_attribute字段
                            'control_type' => $control['control_type'], // control_type字段
                            'control_title' => $control['control_title'], // control_title字段
                        ];
						app($this->flowChildFormControlStructureRepository)->updateData($updateData, ['control_auto_id' => $item['control_auto_id']]);
						$attributeArr[$item['control_id']] = $control['control_attribute']; // 主表单控件属性
						$itemArr[$item['control_id']] = $item['control_attribute']; // 子表单控件属性
					}
                }

            	// 控件内容替换
            	$element = $childPrintControl[$item['control_id']] ?? ''; // 子表单控件
            	$elementOut = $currentMainPrintControl[$item['control_id']] ?? ''; // 主表单控件
            	if($element && $elementOut) {
            		if($item['control_type'] == 'editor'){
            			if(strpos($printModel, $element) === false){
            				$element = str_replace("</p> ","</p>\n", $element);
            				$elementOut = str_replace("</p> ","</p>\n", $elementOut);
            			}
            		}
            		if (($childFormType == 'simple') && ($currentUpdateType != 'structure')){
            			if($item['control_type'] == 'detail-layout'){
            				$detailInfo = json_decode($element,true);
            				$childHtmlArray = [];
            				if(isset($detailInfo['data-efb-layout-info']) && count($detailInfo['data-efb-layout-info'])){
                                // 统计子表单中不在控件structure表里的明细字段
            					foreach($detailInfo['data-efb-layout-info'] as $layoutItem){
            						if(!isset($childControl[$layoutItem['id']])){
            							$childHtmlArray[$layoutItem['id']] = $layoutItem;
            						}
            					}
            				}
            				$elementInfo = json_decode($elementOut,true);
            				if(isset($elementInfo['data-efb-layout-info']) && count($elementInfo['data-efb-layout-info'])){
            					$detailHtmlArray = [];
                                $layoutArray = [];
                                // 统计主表单中不在控件structure表里的明细字段
            					foreach($elementInfo['data-efb-layout-info'] as $layoutItem){
            						if(!isset($childControl[$layoutItem['id']])){
            							$detailHtmlArray[$layoutItem['id']] = $layoutItem;
            						}
                                }
                                // 主表单、主表单中都有、但不在structure表中的明细子控件，用主表单的属性替换子表单中的属性
            					foreach($childHtmlArray as $k => $layoutItem){
            						$layoutArray[] = isset($detailHtmlArray[$k]) ? $detailHtmlArray[$k] : $layoutItem;
            					}
            					$elementInfo['data-efb-layout-info'] = $layoutArray;
            					$elementInfo['data-efb-field-count'] = count($layoutArray);
            					$elementOut = json_encode($elementInfo,JSON_UNESCAPED_UNICODE);
            					$elementOut = str_replace("\/","/",$elementOut);
            				}
            			}
            		}
            		if ($childFormType == 'simple'){
            			// 极速版明细
            			if($item['control_type']=='detail-layout' || $item['control_type']=='horizontal-layout' || $item['control_type']=='vertical-layout' ){
            				$layoutArray = [];
                            $detailHtmlArray = [];
                            // 主表单中有这个明细控件
            				if(isset($attributeArr[$item['control_id']])){
                                $decodeArr = json_decode($attributeArr[$item['control_id']], true);
            					if(isset($decodeArr['data-efb-layout-info']) && $decodeArr['data-efb-layout-info']){
            						$decodeArr['data-efb-layout-info'] = json_decode($decodeArr['data-efb-layout-info'],true);
            						foreach($decodeArr['data-efb-layout-info'] as $layoutItem){
                                        // 统计主表单中未删除的明细字段
            							if(!isset($childControl[$layoutItem['id']])){
            								$layoutArray[] = $layoutItem;
            								$detailHtmlArray[$layoutItem['id']] = $layoutItem;
            							}
            						}
            					}
            					if($currentUpdateType != 'structure'){
            						$layoutArray = [];
            						$itemArr  = json_decode($itemArr[$item['control_id']],true);
            						$childHtmlArray = [];
            						$itemDetailArr = [];
            						if(isset($itemArr['data-efb-layout-info']) && $itemArr['data-efb-layout-info']){
            							$itemDetailArr['data-efb-layout-info'] = is_array($itemArr['data-efb-layout-info']) ? $itemArr['data-efb-layout-info'] : json_decode($itemArr['data-efb-layout-info'],true);
            							foreach($itemDetailArr['data-efb-layout-info'] as $layoutItem){
                                            // 统计子表单中未删除的明细控件
            								if(!isset($childControl[$layoutItem['id']])){
            									$childHtmlArray[$layoutItem['id']] = $layoutItem;
            								}
            							}
                                    }
                                    // 遍历子表单明细控件子控件
            						foreach($childHtmlArray as $k => $layoutItem){
                                        // 找出在子表单、主表单中都存在的明细子控件，使用主表单的替换子表单明细子控件
            							if(isset($detailHtmlArray[$k])){
            								$layoutArray[] = $detailHtmlArray[$k];
            							}
            						}
            					}
            					$decodeArr['data-efb-layout-info'] = $layoutArray;
            					$decodeArr['data-efb-field-count'] = count($layoutArray);
            					$decodeStr = json_encode($decodeArr,JSON_UNESCAPED_UNICODE);
            					$decodeStr = str_replace("\/","/",$decodeStr);
            					app($this->flowChildFormControlStructureRepository)->updateData(['control_attribute' => $decodeStr], ['control_auto_id'=>$item['control_auto_id']]);
            				}
            			}
            			// 极速版布局
            			if($item['control_type']=='horizontal-layout' || $item['control_type']=='vertical-layout'){
            				$elementInfo = json_decode($elementOut,true);
            				if(isset($elementInfo['data-efb-layout-info']) && count($elementInfo['data-efb-layout-info'])>0){
            					$detailHtmlArray = [];
            					$layoutArray = [];
            					foreach($elementInfo['data-efb-layout-info'] as $layoutItem){
            						if(!isset($childControl[$layoutItem['id']])){
            							$layoutArray[] = $layoutItem;
            							$detailHtmlArray[$layoutItem['id']] = $layoutItem;
            						}
            					}
            					if($currentUpdateType != 'structure'){
            						$layoutArray = [];
            						$childHtmlArray = [];
            						$detailInfo = json_decode($element,true);
            						if (isset($detailInfo['data-efb-layout-info']) && count($detailInfo['data-efb-layout-info'])>0){
            							foreach($detailInfo['data-efb-layout-info'] as $layoutItem){
            								if(!isset($childControl[$layoutItem['id']])){
            									$childHtmlArray[$layoutItem['id']] = $layoutItem;
            								}
            							}
            						}
            						foreach($childHtmlArray as $k => $layoutItem){
            							if(isset($detailHtmlArray[$k])){
            								$layoutItem = $detailHtmlArray[$k];
            							}
            							$layoutArray[] = $layoutItem;
            						}
            					}
            					$elementInfo['data-efb-layout-info'] = $layoutArray;
            					$elementInfo['data-efb-field-count'] = count($layoutArray);
            					$elementOut = json_encode($elementInfo,JSON_UNESCAPED_UNICODE);
            					$elementOut = str_replace("\/","/",$elementOut);
            				}
            			}
            		}
            		if ($childFormType != 'simple'){
            			// 标准版明细
            			if($item['control_type']=='detail-layout'){
            				$childHtml  = str_get_html($element);
            				$childHtmlArray = [];
            				foreach ($childHtml->find('div[data-efb-control=detail-layout]') as $htmlElement) {
            					$controlAttr = str_replace("&quot;",'"',$htmlElement->attr['data-efb-layout-info']);
            					$decodeArr = json_decode($controlAttr,true);
            					foreach($decodeArr as $layoutItem){
            						if(!isset($childControl[$layoutItem['id']])){
            							$childHtmlArray[$layoutItem['id']] = $layoutItem;
            						}
            					}
            				}
            				$detailHtml = str_get_html($elementOut);
            				foreach ($detailHtml->find('div[data-efb-control=detail-layout]') as $htmlElement) {
            					$controlAttr = $htmlElement->attr['data-efb-layout-info'];
								$controlAttr = str_replace("&quot;",'"',$controlAttr);
            					$decodeArr = json_decode($controlAttr,true);
            					$detailHtmlArray = [];
            					$layoutArray = [];
            					foreach($decodeArr as $layoutItem){
            						if(!isset($childControl[$layoutItem['id']])){
            							$detailHtmlArray[$layoutItem['id']] = $layoutItem;
            						}
            					}
            					foreach($childHtmlArray as $k => $layoutItem){
									if(isset($detailHtmlArray[$k])){
										$layoutItem = $detailHtmlArray[$k];
									}
            						$layoutArray[] = $layoutItem;
            					}
            					$childFormArr = json_encode($layoutArray,JSON_UNESCAPED_UNICODE);
            					$childFormArr = str_replace("\/","/",$childFormArr);
            					$controlAttr = str_replace('"',"&quot;",$controlAttr);
            					$childFormArr = str_replace('"',"&quot;",$childFormArr);
            					$elementOut = str_replace($controlAttr,$childFormArr,$elementOut);
            					if(empty($layoutArray)){
            						$elementOut = "";
            					}
            				}
            				if(isset($attributeArr[$item['control_id']])){
            					$itemArr  = json_decode($itemArr[$item['control_id']],true);
            					$decodeArr = json_decode($attributeArr[$item['control_id']],true);
            					$childHtmlArray = [];
            					$detailHtmlArray = [];
                                $layoutArray = [];
            					if(isset($itemArr['data-efb-layout-info'])){
            						$detailHtmlArray = [];
            						$itemDetailArr['data-efb-layout-info'] = is_array($itemArr['data-efb-layout-info']) ? $itemArr['data-efb-layout-info'] : json_decode($itemArr['data-efb-layout-info'], true);

            						foreach($itemDetailArr['data-efb-layout-info'] as $layoutItem){
                                        $layoutItemId = is_array($layoutItem) ? $layoutItem['id'] : $layoutItem->id;
            							if(!isset($childControl[$layoutItemId])){
            								$childHtmlArray[$layoutItemId] = $layoutItem;
            							}
            						}
            					}
            					if(isset($decodeArr['data-efb-layout-info'])){
            						$decodeArr['data-efb-layout-info'] = is_array($decodeArr['data-efb-layout-info']) ? $decodeArr['data-efb-layout-info'] : json_decode($decodeArr['data-efb-layout-info'], true);
            						foreach($decodeArr['data-efb-layout-info'] as $layoutItem){
                                        $layoutItemId = is_array($layoutItem) ? $layoutItem['id'] : $layoutItem->id;
            							if(!isset($childControl[$layoutItemId])){
            								$detailHtmlArray[$layoutItemId] = $layoutItem;
            							}
            						}
            					}
            					foreach($childHtmlArray as $k => $layoutItem){
            						if(isset($detailHtmlArray[$k])){
            							$layoutItem = $detailHtmlArray[$k];
            						}
            						$layoutArray[] = $layoutItem;
            					}
            					$decodeArr['data-efb-layout-info'] = $layoutArray;
            					$decodeArr['data-efb-field-count'] = count($layoutArray);
            					$decodeStr = json_encode($decodeArr,JSON_UNESCAPED_UNICODE);
            					$decodeStr = str_replace("\/","/",$decodeStr);
            					app($this->flowChildFormControlStructureRepository)->updateData(['control_attribute' => $decodeStr],['control_auto_id'=>$item['control_auto_id']]);
            				}
            			}
                    }
            		$printModel = str_replace($element, $elementOut,$printModel);
            	}
            }

            // 更新print_model
            $updateData = ['print_model' => $printModel];
            if (!($childFormType == 'simple' && $currentUpdateType == 'structure')) {
            	// $elementOut = str_replace("\/","/",$elementOut);
            	app($this->flowChildFormTypeRepository)->updateData($updateData, ['form_id'=>$value['form_id']]);
            }
            // 极速版更新属性和结构
            if ($childFormType == 'simple' && $currentUpdateType == 'structure'){
            	$childDroppedControl = app($this->flowChildFormControlDropRepository)->getFlowFormTrashedControl(['search' => ["form_id" => [$value['form_id']]]]);
            	$updateData = ['print_model' => $flowForm['print_model']];
            	if(empty($childDroppedControl)){
            		app($this->flowChildFormTypeRepository)->updateData($updateData, ['form_id'=>$value['form_id']]);
            	}else{
            		$childModel = $flowForm['print_model'];
            		// $childArray = $this->getFormControl($flowForm['print_model'],$flowForm['form_type']);
            		$formData = json_decode($childModel, true);
            		if(isset($formData['controlItems'])) {
            			$elementArray = [];
            			foreach($formData['controlItems'] as $v){
							if($v['type'] != 'detail-layout' && $v['type'] != 'horizontal-layout' && $v['type'] != 'vertical-layout') {
                                // 过滤掉子表单中已经删除的非明细控件，只保留子表单、主表单都有的非明细项
								if(!isset($childDroppedControl[$v['id']])){
									$elementArray[] = $v;
								}
							} else {
                                // 子表单中该明细父级如果已被删除则跳过
                                if (isset($childDroppedControl[$v['id']])) {
                                    continue;
                                }
								if(count($v['data-efb-layout-info']) > 0){
									$layoutArray = [];
									foreach($v['data-efb-layout-info'] as $layoutItem){
                                        // 过滤掉子表单中已经删除的明细控件子项，只保留子表单、主表单都有的明细子项
										if(!isset($childDroppedControl[$layoutItem['id']])){
											$layoutArray[] = $layoutItem;
										}
									}
									$v['data-efb-layout-info'] = $layoutArray;
									$v['data-efb-field-count'] = count($layoutArray);
									$elementArray[] = $v;
								}
							}
            			}
            			$formData['controlItems'] = $elementArray;
            		}
            		$childModel = json_encode($formData,JSON_UNESCAPED_UNICODE);
            		$childModel = str_replace('\/','/',$childModel);
					$updateData = ['print_model' => $childModel];
					app($this->flowChildFormTypeRepository)->updateData($updateData, ['form_id'=>$value['form_id']]);
            	}
            	$formControlArray = [];
                $flowFormDataArray = [];
                // 子表单控件ID->auto_id
            	foreach($childFormControlStructure as $item){
            		$formControlArray[$item['control_id']] = $item['control_auto_id'];
                }
                // 遍历主表单控件结构
            	foreach($mainFormControlStructure as $control){
            		$flowFormDataArray[$control['control_id']] = $control['control_auto_id'];
            		$structure = [];
            		$structure['form_id'] = $value['form_id'];
            		$structure['sort'] = $control['sort'];
            		$structure['control_id'] = $control['control_id'];
            		$structure['control_title'] = $control['control_title'];
            		$structure['control_attribute'] = $control['control_attribute'];
            		$structure['control_type'] = $control['control_type'];
                    $structure['control_parent_id'] = $control['control_parent_id'];
                    // 子表单没有且不是子表单已经删除的控件，即是主表单新增控件，单独进行插入
                    if(
                        !isset($formControlArray[$control['control_id']]) &&
                        !in_array($control['control_id'], $childDroppedControl)
                    ){
						app($this->flowChildFormControlStructureRepository)->insertData($structure);
					}
                }
                // 遍历子表单控件结构
            	foreach($childFormControlStructure as $item){
                    // 子表单中有但主表单中没有的控件进行删除
            		if(!isset($flowFormDataArray[$item['control_id']])){
						app($this->flowChildFormControlStructureRepository)->deleteByWhere(['control_auto_id'=>[$item['control_auto_id']]]);
            		}
                }

            	$detailArr = [];
            	$parentArr = [];
            	$childFormControlStructure = app($this->flowChildFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$value['form_id']]]]);
            	foreach($childFormControlStructure as $item){
                    // 找出有明细子控件的明细父控件
					if(!empty($item['control_parent_id'])){
						$detailArr[$item['control_parent_id']] = 1;
                    }
                    // 找出所有的明细父控件
					if($item['control_type'] == 'detail-layout'){
						$parentArr[$item['control_id']] = $item['control_auto_id'];
					}
            	}
            	foreach($parentArr as $k => $v){
                    // 如果某个明细父控件没有子控件则删除
					if(!isset($detailArr[$k])){
						app($this->flowChildFormControlStructureRepository)->deleteByWhere(['control_auto_id'=>[$v]]);
					}
            	}
            }
        	// 处理原控件
            // $elementOut = '';
            // foreach($mainFormControlStructure as $control){
            // 	$controlId =$control['control_id'];
			// 	if(isset($currentMainPrintControl[$controlId])&&!isset($printControl[$controlId])){
			// 		$controlStructureData = [
			// 				"form_id"           => $value['form_id'],
			// 				"control_id"        => $control['control_id'],
			// 				"control_title"     => $control['control_title'],
			// 				"control_type"      => $control['control_type'],
			// 				"control_attribute" => $control['control_attribute'],
			// 				"control_parent_id" =>  $control['control_parent_id'],
			// 				"sort" =>  $control['sort'],
			// 		];
			// 		$elementOut = $currentMainPrintControl[$controlId] ?? '';
			// 		if(!empty($elementOut)){
			// 			$printModel .= $elementOut;
			// 		}
			// 	}
            // }
            // if(!empty($printModel)){
            	// $updateData = ['print_model' => $printModel];
            // }
        }
        return true;
    }

    /**
     *  【流程表单】 获取表单属性
     */
    public function getFormControl($printModel,$formType){
        $elementArray = [];

        // 极速版表单属性解析
        if($formType=='simple') {
			$formData = json_decode($printModel,true);
			if(isset($formData['controlItems'])) {
				foreach($formData['controlItems'] as $v){
					$elementStr = json_encode($v,JSON_UNESCAPED_UNICODE);
					$elementArray[$v['id']] = str_replace('\/','/',$elementStr);
				}
            }
            return $elementArray;
        }

        // 标准版表单属性解析
        $printModelHtml = str_get_html($printModel);
    	// 单行文本框
    	foreach ($printModelHtml->find('input[data-efb-control=text]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('span[data-efb-control=text]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 多行文本框
    	foreach ($printModelHtml->find('textarea[data-efb-control=textarea]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('span[data-efb-control=textarea]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 明细
    	foreach ($printModelHtml->find('div[data-efb-control=detail-layout]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 单选框
    	foreach ($printModelHtml->find('span[data-efb-control=radio]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('input[data-efb-control=radio]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 复选框
    	foreach ($printModelHtml->find('span[data-efb-control=checkbox]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('input[data-efb-control=checkbox]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 编辑器
    	foreach ($printModelHtml->find('div[data-efb-control=editor]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 系统数据
    	foreach ($printModelHtml->find('input[data-efb-control=data-selector]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('span[data-efb-control=data-selector]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 签名图片
    	foreach ($printModelHtml->find('div[data-efb-control=signature-picture]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 动态信息
    	foreach ($printModelHtml->find('div[data-efb-control=dynamic-info]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 电子签章
    	foreach ($printModelHtml->find('div[data-efb-control=electronic-signature]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 下拉框
    	foreach ($printModelHtml->find('button[data-efb-control=select]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('select[data-efb-control=select]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('span[data-efb-control=select]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 附件上传
    	foreach ($printModelHtml->find('button[data-efb-control=upload]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	foreach ($printModelHtml->find('span[data-efb-control=upload]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// 条码类型
        foreach ($printModelHtml->find('span[data-efb-control=barcode]') as $htmlElement) {
            $elementArray[$htmlElement->id] = $htmlElement.'';
        }

    	// 会签
    	foreach ($printModelHtml->find('div[data-efb-control=countersign]') as $htmlElement) {
    		$elementArray[$htmlElement->id] = $htmlElement.'';
    	}
    	// $simpleModel = str_get_html(stripslashes($printModel));
    	// foreach ($simpleModel->find('div[data-efb-control=countersign]') as $htmlElement) {
    	// 	if(!isset($elementArray[$htmlElement->id])){
    	// 		$simpleHtml = $this->getNeedBetween($printModel,'data-efb-image-height','data-efb-allow-hide-user-name');
    	// 		// $elementArray[$htmlElement->id] = $simpleHtml;
    	// 	}
    	// }

    	return $elementArray;
    }

    function getNeedBetween($kw1,$mark1,$mark2){
    	$kw=$kw1;
    	$kw='str'.$kw.'str';
    	$st =stripos($kw,$mark1);
    	$ed =stripos($kw,$mark2);
    	if(($st==false||$ed==false)||$st>=$ed){
    		return 0;
    	}
    	$kw=substr($kw,($st),($ed-$st-1));
    	return $kw;
    }

    /**
     * [检查表单素材版本]
     * @param  [array] $params [['attachment_id' => 表单附件ID，如果传了附件ID就不需要再传print_model, 'print_model' => 表单源码内容非必填，如果没传attachment_id则必填, 'form_name' => 表单名称或表单素材名称非必填]]
     * @return [boolean]         [description]
     */
    public function checkFormVersion($params)
    {
        $printModel = '';
        if (!empty($params['attachment_id'])) {
            $attachmentFile = app($this->attachmentService)->getOneAttachmentById($params['attachment_id']);
            $params['form_name'] = $attachmentFile['attachment_name'] ?? '';
            if (isset($attachmentFile['temp_src_file'])) {
                $printModel = file_get_contents($attachmentFile['temp_src_file']);
            }
        } else {
            $printModel = $params['print_model'] ?? '';
        }
        if (empty($printModel)) {
            return false;
        }
        // 检查表单版本和系统版本
        $pattern = '/<eoffice-version hidden="true">(.*?)<\/eoffice-version>/i';
        preg_match_all($pattern, $printModel, $matchArray);
        if (!empty($matchArray[1][0]) && check_version_larger_than_system_version($matchArray[1][0])) {
            $formName = $params['form_name'] ?? '';
            if (empty($formName)) {
                return ['code' => ['form_source_code_version_too_high', 'flow']];
            } else {
                return ['code' => ['form_material_version_too_high', 'flow'], 'dynamic' => trans('flow.form_material_version_too_high', ['form_name' => $formName])];
            }
        }
        return true;
    }

    /**
     * 升级10.0的表单为10.5版本的（为了兼容10.0导入的表单）
     *
     * @author miaochenchen
     *
     * @since  2019-08-12 创建
     *
     * @return array
     */
    public function updateFormHtml($params)
    {
        $checkFormVersion = $this->checkFormVersion($params);
        if (!$checkFormVersion || isset($checkFormVersion['code'])) {
            return $checkFormVersion;
        }
        if (empty($params['form_type'])) {
            return false;
        }
        // 将表单内容中的版本号去掉
        $pattern = '/<eoffice-version hidden="true">(.*?)<\/eoffice-version>/i';
        $params['print_model'] = preg_replace($pattern, "",$params['print_model']);
        if ($params['form_type'] == 'simple') {
            // 极速版表单
            if (!empty($params['print_model'])) {
                // 替换会签控件属性名称
                $params['print_model'] = str_replace('data-efb-allow-hide-username', 'data-efb-allow-hide-user-name', $params['print_model']);
                $params['print_model'] = str_replace('data-efb-allow-hide-userimg', 'data-efb-allow-hide-user-img', $params['print_model']);
                // 替换系统数据控件多选属性
                $params['print_model'] = str_replace('data-efb-selector-mode', 'data-efb-data-selector-mode', $params['print_model']);
                $printModel = json_decode($params['print_model'], true);
                if (!empty($printModel['controlItems']) && is_array($printModel['controlItems'])) {
                    foreach ($printModel['controlItems'] as $key => $value) {
                        if (!empty($value['type']) && $value['type'] == 'upload' && !empty($value['data-efb-attachment-template'])) {
                            $newAttachmentTemplate = $this->filterAttachmentTemplateString($value['data-efb-attachment-template']);
                            if ($value['data-efb-attachment-template'] != $newAttachmentTemplate) {
                                $params['print_model'] = str_replace($value['data-efb-attachment-template'], $newAttachmentTemplate, $params['print_model']);
                            }
                            continue;
                        }
                        if (!empty($value['type']) && $value['type'] == 'detail-layout' && !empty($value['data-efb-layout-info']) && is_array($value['data-efb-layout-info'])) {
                            foreach ($value['data-efb-layout-info'] as $layOutKey => $layoutValue) {
                                if (!empty($layoutValue['type']) && $layoutValue['type'] == 'upload' && !empty($layoutValue['data-efb-attachment-template'])) {
                                    $newAttachmentTemplate = $this->filterAttachmentTemplateString($layoutValue['data-efb-attachment-template']);
                                    if ($layoutValue['data-efb-attachment-template'] != $newAttachmentTemplate) {
                                        $params['print_model'] = str_replace($layoutValue['data-efb-attachment-template'], $newAttachmentTemplate, $params['print_model']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // 标准版表单
            $printModelHtml = str_get_html($params['print_model']);
            $elementArray   = [];
            if (!is_object($printModelHtml)) {
                return false;
            }
            // 单行文本框
            foreach ($printModelHtml->find('input[data-efb-control=text]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 多行文本框
            foreach ($printModelHtml->find('textarea[data-efb-control=textarea]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 明细
            foreach ($printModelHtml->find('div[data-efb-control=detail-layout]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 单选框
            foreach ($printModelHtml->find('span[data-efb-control=radio]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            foreach ($printModelHtml->find('input[data-efb-control=radio]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 复选框
            foreach ($printModelHtml->find('span[data-efb-control=checkbox]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            foreach ($printModelHtml->find('input[data-efb-control=checkbox]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 编辑器
            foreach ($printModelHtml->find('div[data-efb-control=editor]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 系统数据
            foreach ($printModelHtml->find('input[data-efb-control=data-selector]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 签名图片
            foreach ($printModelHtml->find('div[data-efb-control=signature-picture]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 动态信息
            foreach ($printModelHtml->find('div[data-efb-control=dynamic-info]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 电子签章
            foreach ($printModelHtml->find('div[data-efb-control=electronic-signature]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 下拉框
            foreach ($printModelHtml->find('button[data-efb-control=select]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            foreach ($printModelHtml->find('select[data-efb-control=select]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            foreach ($printModelHtml->find('span[data-efb-control=select]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 附件上传
            foreach ($printModelHtml->find('button[data-efb-control=upload]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            foreach ($printModelHtml->find('span[data-efb-control=upload]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            // 会签
            foreach ($printModelHtml->find('div[data-efb-control=countersign]') as $htmlElement) {
                $elementArray[] = $htmlElement;
            }
            if (empty($elementArray)) {
                return $params;
            }
            $arrayCount = sizeof($elementArray);

            for ($i = 0; $i < $arrayCount; $i++) {
                $elementOut     = '';
                $element        = $elementArray[$i];
                $dataEfbControl = $this->getElementAttr($element, "DATA-EFB-CONTROL");
                if (!empty($dataEfbControl)) {
                    switch ($dataEfbControl) {
                        case 'text':
                            // 单行文本框
                            $elementOut = trim($element);
                            $elementOut = ltrim($elementOut, '<input');
                            $elementOut = '<span' . $elementOut;
                            $elementOut = preg_replace('#/\s*>#', '>', $elementOut);
                            $elementOut = rtrim($elementOut, '>');
                            $elementOut = $elementOut . '></span>';
                            break;
                        case 'data-selector':
                            // 系统数据
                            $dataEfbDataSelectorMode = $this->getElementAttr($element, "DATA-EFB-DATA-SELECTOR-MODE");
                            $elementOut = trim($element);
                            $elementOut = ltrim($elementOut, '<input');
                            $elementOut = '<span' . $elementOut;
                            $elementOut = preg_replace('#/\s*>#', '>', $elementOut);
                            $elementOut = rtrim($elementOut, '>');
                            $elementOut = $elementOut . '>系统数据</span>';
                            if (!empty($dataEfbDataSelectorMode)) {
                                $replaceArray = [
                                    'data-efb-selector-mode="single"',
                                    "data-efb-selector-mode='single'",
                                    'data-efb-selector-mode="multiple"',
                                    "data-efb-selector-mode='multiple'"
                                ];
                                $elementOut = str_replace($replaceArray, '', $elementOut);
                            } else {
                                $elementOut = str_replace('data-efb-selector-mode', 'data-efb-data-selector-mode', $elementOut);
                            }
                            break;
                        case 'textarea':
                            // 多行文本框
                            $elementOut = trim($element);
                            $elementOut = ltrim($elementOut, '<textarea');
                            $elementOut = '<span' . $elementOut;
                            $elementOut = rtrim($elementOut, 'textarea>');
                            $elementOut = $elementOut . 'span>';
                            break;
                        case 'detail-layout':
                            // 明细
                            $elementOut = $element;
                            $dataEfbLayoutInfo = $this->getElementAttr($element, "DATA-EFB-LAYOUT-INFO");
                            $dataEfbLayoutInfoArray = [];
                            if (!empty($dataEfbLayoutInfo)) {
                                $dataEfbLayoutInfo = str_replace('&quot;', '"', $dataEfbLayoutInfo);
                                $dataEfbLayoutInfoArray = json_decode($dataEfbLayoutInfo, true);
                            }
                            if (!empty($dataEfbLayoutInfoArray)) {
                                foreach ($dataEfbLayoutInfoArray as $layoutInfoKey => $layoutInfoValue) {
                                    if (isset($layoutInfoValue['type']) && $layoutInfoValue['type'] == 'upload' && !empty($layoutInfoValue['data-efb-attachment-template'])) {
                                        $newAttachmentTemplate = $this->filterAttachmentTemplateString($layoutInfoValue['data-efb-attachment-template']);
                                        if ($layoutInfoValue['data-efb-attachment-template'] != $newAttachmentTemplate) {
                                            $elementOut = str_replace($layoutInfoValue['data-efb-attachment-template'], $newAttachmentTemplate, $elementOut);
                                        }
                                    }
                                }
                            }
                            break;
                        case 'editor':case 'signature-picture':case 'dynamic-info':case 'electronic-signature':
                            // 编辑器、签名图片、动态信息、电子签章
                            $elementOut = $element;
                            break;
                        case 'radio':case 'checkbox':
                            // 单选框、复选框
                            if (strpos($element, '<input') !== false) {
                                $elementOut = trim($element);
                                $elementOut = ltrim($elementOut, '<input');
                                $elementOut = '<span' . $elementOut;
                                $elementOut = preg_replace('#/\s*>#', '>', $elementOut);
                                $elementOut = rtrim($elementOut, '>');
                                $elementOut = $elementOut . '></span>';
                            } else {
                                $elementOut = $element;
                            }
                            break;
                        case 'select':
                            // 下拉框
                            if (strpos($element, '<button') !== false) {
                                $elementOut = trim($element);
                                $elementOut = ltrim($elementOut, '<button');
                                $elementOut = '<span' . $elementOut;
                                $elementOut = rtrim($elementOut, 'button>');
                                $elementOut = $elementOut . 'span>';
                            } else if (strpos($element, '<select') !== false) {
                                $elementOut = trim($element);
                                $elementOut = ltrim($elementOut, '<select');
                                $elementOut = '<span' . $elementOut;
                                $elementOut = rtrim($elementOut, 'select>');
                                $elementOut = $elementOut . 'span>';
                            } else {
                                $elementOut = $element;
                            }
                            $dataType = $this->getElementAttr($elementOut, "TYPE");
                            if (!empty($dataType)) {
                                $elementOut = str_replace(['type="'.$dataType.'"', "type='".$dataType."'"], '', $elementOut);
                            }
                            break;
                        case 'countersign':
                            // 会签
                            $elementOut = str_replace('data-efb-allow-hide-username', 'data-efb-allow-hide-user-name', $element);
                            $elementOut = str_replace('data-efb-allow-hide-userimg', 'data-efb-allow-hide-user-img', $elementOut);
                            break;
                        case 'upload':
                            // 附件上传
                            if (strpos($element, '<button') !== false) {
                                $elementOut = trim($element);
                                $elementOut = ltrim($elementOut, '<button');
                                $elementOut = '<span' . $elementOut;
                                $elementOut = rtrim($elementOut, 'button>');
                                $elementOut = $elementOut . 'span>';
                            } else {
                                $elementOut = $element;
                            }
                            $dataType = $this->getElementAttr($elementOut, "TYPE");
                            if (!empty($dataType)) {
                                $elementOut = str_replace(['type="'.$dataType.'"', "type='".$dataType."'"], '', $elementOut);
                            }
                            // 替换附件模板里内容，如果附件在当前系统存在，则保留
                            $oldAttachmentTemplate = $this->getElementAttr($elementOut, "DATA-EFB-ATTACHMENT-TEMPLATE");
                            $newAttachmentTemplate = $this->filterAttachmentTemplateString($oldAttachmentTemplate);
                            if ($oldAttachmentTemplate != $newAttachmentTemplate) {
                                $elementOut = str_replace($oldAttachmentTemplate, $newAttachmentTemplate, $elementOut);
                            }
                            break;
                        default:
                            break;
                    }
                    if (strpos($elementOut, 'control-default-') === false) {
                        $elementOut = str_replace('class="', 'class="control-default-'.$dataEfbControl. ' ', $elementOut);
                        $elementOut = str_replace("class='", "class='control-default-".$dataEfbControl. ' ', $elementOut);
                    }
                }

                if (!empty($elementOut)) {
                    $params['print_model'] = str_replace($element, $elementOut, $params['print_model']);
                }
            }
        }
        return $params;
    }

    // 过滤附件控件附件模板中的附件，如果在系统中存在则保留，如果不存在则删除
    public function filterAttachmentTemplateString($oldAttachmentTemplate)
    {
        if ($oldAttachmentTemplate === '') {
            return $oldAttachmentTemplate;
        }
        $attachmentTemplate = trim($oldAttachmentTemplate, ',');
        $newAttachmentTemplate = '';
        if (!empty($attachmentTemplate)) {
            $attachmentTemplateArray = explode(',', $attachmentTemplate);
            $templateCount = count($attachmentTemplateArray);
            $attachmentList = app($this->attachmentService)->getAttachments(['attach_ids' => $attachmentTemplate]);
            if (!empty($attachmentList) && is_array($attachmentList)) {
                $newAttachmentListCount = count($attachmentList);
                if ($templateCount == $newAttachmentListCount) {
                    return $oldAttachmentTemplate;
                } else {
                    $newAttachmentIdList = [];
                    foreach ($attachmentList as $attachmentKey => $attachmentValue) {
                        if (!empty($attachmentValue['attachment_id'])) {
                            $newAttachmentIdList[] = $attachmentValue['attachment_id'];
                        }
                    }
                    if (!empty($newAttachmentIdList)) {
                        $newAttachmentTemplate = implode(',', $newAttachmentIdList);
                    }
                }
            }
        }
        return $newAttachmentTemplate;
    }

    /**
     *   获取元素属性
     */
    public function getElementAttr($ELEMENT, $ATTR)
    {
        //-- 先取元素名 --
        $POS    = strpos($ELEMENT, " ");
        $E_NAME = substr($ELEMENT, 1, $POS - 1);
        //-- 分类别取值 --
        $ATTR_DATA = "";

        if ($ATTR == "NAME") {
            $ATTR_DATA = $E_NAME;
        } else if ($ATTR == "TITLE" || $ATTR == "CLASS" || $ATTR == "TYPE" || $ATTR == "DATAFLD" || $ATTR == "DATASRC" || $ATTR == "THOUSANDFORMAT" || $ATTR == "ID" || $ATTR == "FORMAT" || $ATTR == "STARTDATE" || $ATTR == "STARTTIME" || $ATTR == "ENDDATE" || $ATTR == "ENDTIME" || $ATTR == 'STYLE' || $ATTR == 'MORNINGSTART' || $ATTR == 'MORNINGEND' || $ATTR == 'AFTERNOONSTART' || $ATTR == 'AFTERNOONEND' || $ATTR == 'ROUNDOFF' || $ATTR == 'UPPERCASE' || $ATTR == 'DATASOURCEID' || $ATTR == 'DATASOURCE' || $ATTR == 'AUTOUSER' || $ATTR == 'AUTODATETIME' || $ATTR == 'EXCLUDEVACATION' || $ATTR == 'DETAILCHARNAME' || $ATTR == 'DETAILCHARTYPE' || $ATTR == 'DETAILCHARWIDTH' || $ATTR == 'DETAILCHARTOTAL' || $ATTR == 'DETAILCHARDROPVALUES' || $ATTR == 'DETAILCHARFORMULA' || $ATTR == 'DETAILCHARDECIMAL' || $ATTR == 'DETAILCHARROUNDOFF' || $ATTR == 'DETAILCHARFORMULAFORMAT' || $ATTR == 'DATA-EFB-SOURCE' || $ATTR == 'DATA-EFB-CONTROL' || $ATTR == 'DATA-EFB-SOURCE-VALUE' || $ATTR == 'DATA-EFB-OPTIONS' || $ATTR == 'DATA-EFB-SELECTED-OPTIONS' || $ATTR == 'DATA-EFB-WITH-TEXT' || $ATTR == 'DATA-EFB-LAYOUT-INFO' || $ATTR == 'DATA-EFB-ATTACHMENT-TEMPLATE' || $ATTR == 'DATA-EFB-DATA-SELECTOR-MODE') {
            if ($ATTR == "ID" || $ATTR == "TITLE" || $ATTR == "CLASS" || $ATTR == "THOUSANDFORMAT" || $ATTR == "ID" || $ATTR == "FORMAT" || $ATTR == "STARTDATE" || $ATTR == "STARTTIME" || $ATTR == "ENDDATE" || $ATTR == "ENDTIME" || $ATTR == 'UPPERCASE' || $ATTR == 'DATASOURCEID' || $ATTR == 'DATASOURCE' || $ATTR == 'DETAILCHARNAME' || $ATTR == 'DETAILCHARTYPE' || $ATTR == 'DETAILCHARWIDTH' || $ATTR == 'DETAILCHARTOTAL' || $ATTR == 'DETAILCHARDROPVALUES' || $ATTR == 'DETAILCHARFORMULA' || $ATTR == 'DETAILCHARDECIMAL' || $ATTR == 'DETAILCHARROUNDOFF' || $ATTR == 'DETAILCHARFORMULAFORMAT' || $ATTR == 'DATA-EFB-SOURCE' || $ATTR == 'DATA-EFB-CONTROL' || $ATTR == 'DATA-EFB-SOURCE-VALUE' || $ATTR == 'DATA-EFB-OPTIONS' || $ATTR == 'DATA-EFB-SELECTED-OPTIONS' || $ATTR == 'DATA-EFB-WITH-TEXT' || $ATTR == 'DATA-EFB-LAYOUT-INFO' || $ATTR == 'DATA-EFB-ATTACHMENT-TEMPLATE' || $ATTR == 'DATA-EFB-DATA-SELECTOR-MODE') {
                $ATTR = strtolower($ATTR);
            } else if ($ATTR == "DATAFLD") {
                $ATTR = "dataFld";
            } else if ($ATTR == "DATASRC") {
                $ATTR = "dataSrc";
            } else if ($ATTR == "MORNINGSTART") {
                $ATTR = "morning_start";
            } else if ($ATTR == "MORNINGEND") {
                $ATTR = "morning_end";
            } else if ($ATTR == "AFTERNOONSTART") {
                $ATTR = "afternoon_start";
            } else if ($ATTR == "AFTERNOONEND") {
                $ATTR = "afternoon_end";
            } else if ($ATTR == "ROUNDOFF") {
                $ATTR = "round-off";
            } else if ($ATTR == "AUTOUSER") {
                $ATTR = "auto_user";
            } else if ($ATTR == "AUTODATETIME") {
                $ATTR = "auto_datetime";
            } else if ($ATTR == "EXCLUDEVACATION") {
                $ATTR = "exclude_vacation";
            }

            $bool = strstr($ELEMENT, $ATTR) || strstr($ELEMENT, strtolower($ATTR));
            if ($bool) {
                $p = strpos($ELEMENT, "$ATTR="); //$p 43
                if ($p === false) {
                    $att = strtolower($ATTR);
                    $p   = strpos($ELEMENT, "$att=");
                }

                //如果依然是空，那么就是没有匹配到
                if ($p === false) {
                    return;
                }
                //$POS = strpos ( $ELEMENT, "$ATTR=" ) + strlen ( $ATTR ) + 1;
                $POS  = $p + strlen($ATTR) + 1; //$POS 58
                $POS1 = strpos($ELEMENT, ">", $POS); //$POS1 61
                // 双引号标识，有的表单里面全是用的单引号，这里处理下
                $doubleQuotationMark = true;

                if ($ATTR == "dataSrc" || $ATTR == "title") {
                    $POS++;
                    $POS2 = strpos($ELEMENT, "\"", $POS);
                } else {
                    if ($ATTR == 'data-efb-layout-info') {
                        if ($tempPos = strpos($ELEMENT, '}]"', $POS)) {
                            $POS2      = $tempPos + 3;
                            $ATTR_DATA = substr($ELEMENT, $POS, $POS2 - $POS);
                            $ATTR_DATA = str_replace("\"", "", $ATTR_DATA);
                            return $ATTR_DATA;
                        } else if ($tempPos = strpos($ELEMENT, "}]'", $POS)) {
                            $POS2      = $tempPos + 3;
                            $ATTR_DATA = substr($ELEMENT, $POS, $POS2 - $POS);
                            $ATTR_DATA = str_replace("'", "", $ATTR_DATA);
                            return $ATTR_DATA;
                        } else {
                            return $ATTR_DATA;
                        }
                    } else {
                        if ($tempPos = strpos($ELEMENT, '" ', $POS)) {
                            $POS2 = $tempPos + 1;
                        } elseif ($tempPos = strpos($ELEMENT, "' ", $POS)) {
                            $doubleQuotationMark = false;
                            $POS2 = $tempPos + 1;
                        } elseif ($tempPos = strpos($ELEMENT, '">', $POS)) {
                            $POS2 = $tempPos + 1;
                        } else {
                            return $ATTR_DATA;
                        }
                    }
                }
                if ($POS2 < $POS1 && $POS2 != 0) {
                    $POS1 = $POS2;
                }

                $ATTR_DATA = substr($ELEMENT, $POS, $POS1 - $POS);
                if ($doubleQuotationMark) {
                    $searchStr = "\"";
                } else {
                    $searchStr = "'";
                }
                $ATTR_DATA = str_replace($searchStr, "", $ATTR_DATA);
            }
        } else if ($ATTR == "VALUE") {
            if ($E_NAME == "INPUT" || $E_NAME == "IMG") {
                if (!strstr($ELEMENT, "type=\"checkbox\"")) //textfield
                {
                    $POS = strpos($ELEMENT, "value=");
                    if ($POS > 0) {
                        $value_pattern = "/value=\"[^\"]*\"/";
                        preg_match_all($value_pattern, $ELEMENT, $value_pattern_array);
                        $value     = $value_pattern_array[0][0];
                        $ATTR_DATA = substr($value, 7, strlen($value) - 8);

                    } else {
                        $ATTR_DATA = "";
                    }

                } else if (strstr($ELEMENT, " checked=\"checked\"")) {
                    $ATTR_DATA = "on";
                }

                $ATTR_DATA = str_replace("\"", "", $ATTR_DATA);
            } else if ($E_NAME == "TEXTAREA") {
                $POS       = strpos($ELEMENT, ">") + 1;
                $POS1      = strpos($ELEMENT, "<", $POS);
                $ATTR_DATA = substr($ELEMENT, $POS, $POS1 - $POS);
            } else if ($E_NAME == "SELECT") {
                $POS       = strpos($ELEMENT, ">") + 1;
                $POS1      = strpos($ELEMENT, "</SELECT>", $POS);
                $ATTR_DATA = substr($ELEMENT, $POS, $POS1 - $POS);
            }
        }
        return $ATTR_DATA;
    }
}
