<?php

namespace App\EofficeApp\Project\Services;

use DB;
use Eoffice;
use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Arr;
/**
 * 项目service的补充service
 * 1、去掉了对下拉框 SystemComboboxService 的引用，避免互相引用的问题，for function addSystemComboboxForProjectTypeSelectItem
 *
 * @author: dp
 *
 * @since：2015-10-19
 */
class ProjectSupplementService extends BaseService {

    public function __construct(
    ) {
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->formModelingRepository = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
    }

    /**
     * 提供给 System\Combobox\Services\SystemComboboxService.php 调用的函数，用来增加项目类型下拉选项对应的默认系统自定义字段
     * @param [type] $data [description]
     */
    function addSystemComboboxForProjectTypeSelectItem($data) {
        if(isset($data["field_value"]) && $data["field_value"]) {
            $field_table_key = "project_value_".$data["field_value"];
            // 删除原有
            DB::table('custom_fields_table')->where('field_table_key',$field_table_key)->where('is_system',1)->delete();
            $data = $this->getProjectManagerFieldInfo($field_table_key);
            // $this->fieldsService->saveCustomFields($data,$field_table_key);
            $cnLangData = [];
            $enLangData = [];
            foreach ($data as $key => $value) {
                $tableKey = $value['field_table_key'];
                $field_code = $value['field_code'];
                $data[$key]['field_name'] = $tableKey . '_' . $field_code;
                $cnLangData[] = [
                    'table'      => 'custom_fields_table',
                    'column'     => 'field_name',
                    'lang_key'   => $tableKey . "_" . $field_code,
                    'lang_value' => $value['field_name_cn'],
                ];
                $enLangData[] = [
                    'table'      => 'custom_fields_table',
                    'column'     => 'field_name',
                    'lang_key'   => $tableKey . "_" . $field_code,
                    'lang_value' => $value['field_name_en'],
                ];
                unset($data[$key]['field_name_cn']);
                unset($data[$key]['field_name_en']);
            }
            $langService = app('App\EofficeApp\Lang\Services\LangService');
            $langService->mulitAddDynamicLang($cnLangData, 'zh-CN');
            $langService->mulitAddDynamicLang($enLangData, 'en');
            DB::table('custom_fields_table')->insert($data);
            app($this->formModelingRepository)->createEmptyCustomDataTable($tableKey);
            $this->addProjectTemplate($field_table_key);
        }
    }


    function addSystemComboboxForProjectTaskTypeSelectItem($data) {
        if(isset($data["field_value"]) && $data["field_value"]) {
            $managerType = $data["field_value"];
            $this->addProjectTaskCustom($managerType); // 任务自定义字段
        }
    }

    public function checkProjectManagerUnique($data, $managerId = null)
    {
        $checkKey = ['manager_number', 'manager_name']; // 需要检查的字段

        $checkData = [
            'table_key' => 'project_value_' . Arr::get($data, 'manager_type'),
            'manager_id' => $managerId
        ];
        foreach ($checkKey as $key) {
            $checkData['field_code'] = $key;
            $checkData['value'] = Arr::get($data, $key);
            $res = app($this->formModelingService)->checkFieldsUnique($checkData);
            if (isset($res['code'])) {
                return $res;
            } else if (!$res) {
                $fieldName = mulit_trans_dynamic("custom_fields_table.field_name." . $checkData['table_key'] . "_" . $key);
                return ['code' => ['0x016031', 'fields'], 'dynamic' => $fieldName . trans('fields.exist')];
            }
        }
        return true;
    }

    public function getProjectManagerFieldInfo($fieldTableKey)
    {
        $data   = [];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_number','field_name_cn' => '项目编号','field_name_en' => 'Item Number','field_data_type' => 'varchar','field_options' => '{"type":"text","format":{"type":"text","placeholder":""},"fullRow":2,"remark":"项目编号可以为数字","fullCell":true}','field_directive' => 'text','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_name','field_name_cn' => '项目名称','field_name_en' => 'project name','field_data_type' => 'varchar','field_options' => '{"type":"text","format":{"type":"text"},"fullRow":2,"fullCell":true,"validate":{"required":1},"disabledAttrs":["required"]}','field_directive' => 'text','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_begintime','field_name_cn' => '项目周期_开始','field_name_en' => 'Project cycle_start','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"date"},"default":1,"validate":{"required":1},"disabledAttrs":["required"]}','field_directive' => 'date','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_endtime','field_name_cn' => '项目周期_结束','field_name_en' => 'Project cycle_end','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"date"},"validate":{"required":1},"disabledAttrs":["required"]}','field_directive' => 'date','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_person','field_name_cn' => '项目负责人','field_name_en' => 'Project manager','field_data_type' => 'text','field_options' => '{"type":"selector","validate":{"required":1},"disabledAttrs":["required"],"selectorConfig":{"category":"common","type":"user","multiple":1},"fullRow":2,"fullCell":true,"default":true}','field_directive' => 'selector','is_system' => '1'];
        // $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_type','field_name_cn' => '项目类型','field_data_type' => 'varchar','field_options' => '{"type":"select","selectConfig":{"sourceType":"combobox","sourceValue":"PROJECT_TYPE"},"fullRow":2,"fullCell":true}','field_directive' => 'select','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_fast','field_name_cn' => '紧急程度','field_name_en' => 'Emergency level','field_data_type' => 'varchar','field_options' => '{"type":"select","selectConfig":{"sourceType":"combobox","sourceValue":"PROJECT_DEGREE"},"fullRow":2,"fullCell":true}','field_directive' => 'select','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_level','field_name_cn' => '优先级别','field_name_en' => 'Priority level','field_data_type' => 'varchar','field_options' => '{"type":"select","selectConfig":{"sourceType":"combobox","sourceValue":"PROJECT_PRIORITY"},"fullRow":2,"fullCell":true}','field_directive' => 'select','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_examine','field_name_cn' => '项目审批人','field_name_en' => 'Project Approver','field_data_type' => 'text','field_options' => '{"type":"selector","validate":{"required":1},"disabledAttrs":["required"],"selectorConfig":{"multiple":1,"category":"common","type":"user"},"fullRow":2,"fullCell":true,"default":true}','field_directive' => 'selector','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_monitor','field_name_cn' => '项目监控人','field_name_en' => 'Project Monitor','field_data_type' => 'text','field_options' => '{"type":"selector","selectorConfig":{"multiple":1,"category":"common","type":"user"},"fullRow":2,"fullCell":true,"default":true}','field_directive' => 'selector','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'team_person','field_name_cn' => '项目团队','field_name_en' => 'Project team','field_data_type' => 'text','field_options' => '{"type":"selector","selectorConfig":{"multiple":1,"category":"common","type":"user"},"fullRow":2,"fullCell":true,"default":true}','field_directive' => 'selector','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_explain','field_name_cn' => '项目描述','field_name_en' => 'Project description','field_data_type' => 'text','field_options' => '{"type":"editor","fullRow":-1,"height":""}','field_directive' => 'editor','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'manager_creater','field_name_cn' => '创建人','field_name_en' => 'Founder','field_data_type' => 'text','field_options' => '{"type":"selector","validate":{"required":1},"selectorConfig":{"category":"common","type":"user"},"fullRow":2,"fullCell":true,"disabled":1,"default":true}','field_directive' => 'selector','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'creat_time','field_name_cn' => '创建时间','field_name_en' => 'Creation time','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"datetime"},"default":1,"fullRow":2,"fullCell":true,"disabled":1}','field_directive' => 'datetime','is_system' => '1'];
        return $data;
    }
    public function getProjectTaskFieldInfo($fieldTableKey)
    {
        $data   = [];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_name','field_name_cn' => '任务名称','field_name_en' => 'Mission name','field_data_type' => 'varchar','field_options' => '{"type":"text","format":{"type":"text"},"validate":{"required":1},"disabledAttrs":["required","unique"]}','field_directive' => 'text','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'sort_id','field_name_cn' => '排序','field_name_en' => 'Sorting level','field_data_type' => 'varchar','field_options' => '{"type":"text","format":{"type":"number","decimalPlaces":0,"decimalPlacesDigit":0,"rounding":0},"validate":{},"disabledAttrs":["required", "unique"]}','field_directive' => 'number','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_persondo','field_name_cn' => '任务执行人','field_name_en' => 'Task executor','field_data_type' => 'text','field_options' => '{"type":"selector","validate":{"required":1},"disabledAttrs":["required", "unique"],"selectorConfig":{"category":"common","type":"user","multiple":0},"default":true}','field_directive' => 'selector','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_begintime','field_name_cn' => '计划周期-开始','field_name_en' => 'Planning cycle_start','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"date"},"default":1,"validate":{"required":1},"disabledAttrs":["required", "unique"]}','field_directive' => 'date','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_endtime','field_name_cn' => '计划周期-结束','field_name_en' => 'Planning cycle_end','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"date"},"validate":{"required":1},"disabledAttrs":["required", "unique"]}','field_directive' => 'date','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'working_days','field_name_cn' => '工时','field_name_en' => 'Working days','field_data_type' => 'varchar','field_options' => '{"type":"text","format":{"type":"number","decimalPlaces":0,"decimalPlacesDigit":0,"rounding":0},"validate":{},"disabled":1}','field_directive' => 'number','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_explain','field_name_cn' => '任务描述','field_name_en' => 'Mission details','field_data_type' => 'text','field_options' => '{"type":"editor","fullRow":-1,"height":"","disabledAttrs":["required"]}','field_directive' => 'editor','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'weights','field_name_cn' => '权重','field_name_en' => 'Weights','field_data_type' => 'varchar','field_options' => '{"type":"text","disabledAttrs":["required", "unique"],"default":"1"}','field_directive' => 'text','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_level','field_name_cn' => '任务级别','field_name_en' => 'Task level','field_data_type' => 'varchar','field_options' => '{"type":"select","selectConfig":{"sourceType":"combobox","sourceValue":"PROJECT_PRIORITY"},"disabledAttrs":["required"]}','field_directive' => 'select','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_mark','field_name_cn' => '里程碑','field_name_en' => 'Milestone','field_data_type' => 'varchar','field_options' => '{"type":"checkbox","validate":{},"datasource":[{"id":0,"title":"$option_0$"},{"id":1,"title":"$option_1$"}],"default":0,"singleMode":1,"disabledAttrs":["required"]}','field_directive' => 'checkbox','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_remark','field_name_cn' => '备注','field_name_en' => 'Note','field_data_type' => 'text','field_options' => '{"type":"editor","fullRow":-1,"height":"","disabledAttrs":["required"]}','field_directive' => 'editor','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_persent','field_name_cn' => '进度','field_name_en' => 'Schedule','field_data_type' => 'varchar','field_options' => '{"type":"text","format":{"type":"number","decimalPlaces":0,"decimalPlacesDigit":0,"rounding":0},"validate":{},"disabled":1}','field_directive' => 'number','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'start_date','field_name_cn' => '开始日期','field_name_en' => 'Start date','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"date"},"disabled":1}','field_directive' => 'date','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'complete_date','field_name_cn' => '完成日期','field_name_en' => 'Complete date','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"date"},"disabled":1}','field_directive' => 'date','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'is_overdue','field_name_cn' => '逾期','field_name_en' => 'Is overdue','field_data_type' => 'varchar','field_options' => '{"type":"checkbox","validate":{},"datasource":[{"id":0,"title":"$option_0$"},{"id":1,"title":"$option_1$"}],"default":0,"singleMode":1,"disabled":1}','field_directive' => 'checkbox','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'task_creater','field_name_cn' => '创建人','field_name_en' => 'Founder','field_data_type' => 'text','field_options' => '{"type":"selector","validate":{"required":1},"selectorConfig":{"category":"common","type":"user"},"disabled":1,"default":true}','field_directive' => 'selector','is_system' => '1'];
        $data[] = ['field_table_key' => $fieldTableKey,'field_code' => 'creat_time','field_name_cn' => '创建时间','field_name_en' => 'Creation time','field_data_type' => 'varchar','field_options' => '{"type":"datetime","datetime":{"type":"datetime"},"default":1,"disabled":1}','field_directive' => 'datetime','is_system' => '1'];
        return $data;
    }

    public function addProjectTaskCustom($managerType)
    {
        $field_table_key = "project_task_value_{$managerType}";
        // 删除原有
        DB::table('custom_fields_table')->where('field_table_key',$field_table_key)->where('is_system',1)->delete();
        $data = $this->getProjectTaskFieldInfo($field_table_key);
        $cnLangData = [];
        $enLangData = [];
        foreach ($data as $key => $value) {
            $tableKey = $value['field_table_key'];
            $field_code = $value['field_code'];
            $data[$key]['field_name'] = $tableKey . '_' . $field_code;
            $cnLangData[] = [
                'table'      => 'custom_fields_table',
                'column'     => 'field_name',
                'lang_key'   => $tableKey . "_" . $field_code,
                'lang_value' => $value['field_name_cn'],
            ];
            $enLangData[] = [
                'table'      => 'custom_fields_table',
                'column'     => 'field_name',
                'lang_key'   => $tableKey . "_" . $field_code,
                'lang_value' => $value['field_name_en'],
            ];
            unset($data[$key]['field_name_cn']);
            unset($data[$key]['field_name_en']);
        }
        $this->insertCheckboxOptionLang($cnLangData, $enLangData, $field_table_key); // 插入多选的多语言
        $langService = app('App\EofficeApp\Lang\Services\LangService');
        $langService->mulitAddDynamicLang($cnLangData, 'zh-CN');
        $langService->mulitAddDynamicLang($enLangData, 'en');
        DB::table('custom_fields_table')->insert($data);

        app($this->formModelingRepository)->createEmptyCustomDataTable($tableKey);
        $this->addTaskTemplate($field_table_key); // 添加任务模板
    }

    private function insertCheckboxOptionLang(&$cnLang, &$enLang, $tableKey)
    {
        $options = [
            'is_overdue' => [['否', 'Not'], ['是', 'Yes']],
            'task_mark' => [['否', 'Not'], ['标记为里程碑', 'Marked as a milestone']]
        ];
        foreach ($options as $key => $values) {
            foreach ($values as $value => $lang) {
                $cnLang[] = [
                    'table'      => 'custom_fields_table',
                    'column'     => 'field_options',
                    'lang_key'   => "option_{$value}",
                    'lang_value' => $lang[0],
                    'option' => "{$tableKey}_{$key}"
                ];
                $enLang[] = [
                    'table'      => 'custom_fields_table',
                    'column'     => 'field_options',
                    'lang_key'   => "option_{$value}",
                    'lang_value' => $lang[1],
                    'option' => "{$tableKey}_{$key}"
                ];
            }
        }
    }

    // 删除自定义字段多语言数据
    public function clearLangData($managerType)
    {
        $tableKeys = [
            \App\EofficeApp\Project\NewServices\ProjectService::getProjectCustomTableKey($managerType),
            \App\EofficeApp\Project\NewServices\ProjectService::getProjectTaskCustomTableKey($managerType)
        ];
        foreach ($tableKeys as $tableKey) {
            DB::table('lang_zh_cn')->where('lang_key', 'like', "%{$tableKey}%")->orWhere('option', 'like', "%{$tableKey}%")->delete();
            DB::table('lang_en')->where('lang_key', 'like', "%{$tableKey}%")->orWhere('option', 'like', "%{$tableKey}%")->delete();
        }
    }

    private function insertLangData($table, $column, $option, $langKey, array $langValue)
    {
        foreach ($langValue as $key => $value) {
            $langData[] = [
                'table'      => $table,
                'column'     => $column,
                'option'     => $option,
                'lang_key'   => $langKey,
                'lang_value' => $value,
            ];
            $tableName = $key === 0 ? 'lang_zh_cn' : 'lang_en';
            DB::table($tableName)->insert($langData);
        }
    }

    /**
     * 添加任务自定义字段模板
     */
    private function addTaskTemplate($tableKey)
    {
        $content = [
            "task_name",
            "sort_id",
            "task_persondo",
            "task_level",
            "task_mark",
            "task_begintime",
            "task_endtime",
            "working_days",
            "weights",
            "task_explain",
            "task_remark",
            "task_persent",
//            "start_date",
            "complete_date",
            "is_overdue",
            "task_creater",
            "creat_time"];
        $extra = '{"task_name":{"height":150,"fullRow":-1},"sort_id":{"remark":"$sort_id|remark$"},"task_explain":{"height":150,"fullRow":-1},"task_remark":{"height":150,"fullRow":-1}}';
        $pcTemplate = [
            'name' => '未命名',
            'content' =>json_encode($content),
            'extra' => $extra,
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'pc',
            'template_type' => 2,
        ];

        $mobileTemplate = [
            'name' => '未命名',
            'content' =>json_encode($content),
            'extra' => '{}',
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'mobile',
            'template_type' => 2,
        ];

        $pcListTemplate = [
            'name' => '未命名列表模板',
            'content' => '{"list":["task_name","task_persondo","task_persent","is_overdue","task_begintime","working_days"],"order":["sort_id","task_persent","task_name","task_begintime","task_endtime","is_overdue","working_days"],"filter":["task_level"],"fuzzy":["task_name"],"field":["task_name"],"advanced":["task_name","task_persondo","task_creater","task_begintime","task_endtime","complete_date"]}',
            'extra' =>  '{"list":{"task_name":{"bind":1},"task_persondo":{"bind":1},"task_persent":{"bind":1},"is_overdue":{"bind":1},"task_begintime":{"bind":1},"working_days":{"bind":1}},"defaultOrder":[{"type":"asc","key":"sort_id"}]}',
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'pc',
            'template_type' => 1,
        ];

        $mobileListTemplate = [
            'name' => '未命名列表模板',
            'content' => '{"order":["task_name","sort_id","task_begintime","task_endtime","working_days","task_persent","is_overdue"],"filter":["task_level"],"fuzzy":["task_name"],"advanced":["task_name","task_persondo","task_begintime","task_endtime","complete_date","task_creater"]}',
            'extra' => '{"item":{"primary":{"fields":["task_name"]},"remark":{"fields":["task_persondo"]},"time":{"fields":["task_begintime"]},"tag":{"fields":["task_persent"]},"secondary":{"fields":["task_endtime","is_overdue"]}},"defaultOrder":[{"key":"sort_id","type":"asc"}]}',
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'mobile',
            'template_type' => 1,
        ];
        $pcResultId = DB::table('custom_template')->insertGetId($pcTemplate);
        $mobileResultId = DB::table('custom_template')->insertGetId($mobileTemplate);
        $pcListResultId = DB::table('custom_template')->insertGetId($pcListTemplate);
        $mobileListResultId = DB::table('custom_template')->insertGetId($mobileListTemplate);
        $layoutSetting = [
            [
                'table_key' => $tableKey,
                'bind_template' => $pcListResultId,
                'bind_type' => 1,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $pcResultId,
                'bind_type' => 2,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $pcResultId,
                'bind_type' => 3,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $pcResultId,
                'bind_type' => 4,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileListResultId,
                'bind_type' => 1,
                'terminal' => 'mobile',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileResultId,
                'bind_type' => 2,
                'terminal' => 'mobile',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileResultId,
                'bind_type' => 3,
                'terminal' => 'mobile',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileResultId,
                'bind_type' => 4,
                'terminal' => 'mobile',
            ],
        ];
        DB::table('custom_layout_setting')->insert($layoutSetting);
        // 排序提示文字多语言
        $this->insertLangData('custom_template', $pcResultId, $pcResultId, 'sort_id|remark', ['值越小越靠前', 'The smaller the value, the earlier']);
    }


    /**
     * 添加项目自定义字段模板
     */
    private function addProjectTemplate($tableKey)
    {
        $content = [
            "manager_number",
            "manager_name",
            "manager_begintime",
            "manager_endtime",
            "manager_person",
            "manager_fast",
            "manager_level",
            "manager_examine",
            "manager_monitor",
            "team_person",
            "manager_explain",
            "manager_creater",
            "creat_time"
        ];
        $extra = '{"manager_number":{"placeholder":"$manager_number|placeholder$","remark":"$manager_number|remark$","fullRow":2,"fullCell":1},"manager_name":{"fullRow":2,"fullCell":1},"manager_person":{"fullRow":2,"fullCell":1},"manager_fast":{"fullRow":2,"fullCell":1},"manager_level":{"fullRow":2,"fullCell":1},"manager_examine":{"fullRow":2,"fullCell":1},"manager_monitor":{"fullRow":2,"fullCell":1},"team_person":{"fullRow":2,"fullCell":1},"manager_explain":{"fullRow":-1},"manager_creater":{"fullRow":2,"fullCell":1},"creat_time":{"fullRow":2,"fullCell":1}}';
        $pcTemplate = [
            'name' => '桌面端表单默认模板',
            'content' =>json_encode($content),
            'extra' => $extra,
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'pc',
            'template_type' => 2,
        ];

        $mobileTemplate = [
            'name' => '移动端表单默认模板',
            'content' =>json_encode($content),
            'extra' => '{}',
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'mobile',
            'template_type' => 2,
        ];

        $pcListTemplate = [
            'name' => '桌面端列表默认模板',
            'content' => '{"list":["manager_number","manager_name","manager_begintime","manager_endtime","manager_person","manager_fast","manager_level"],"order":["manager_number","manager_name","manager_begintime","manager_endtime","manager_fast","manager_level"],"filter":["manager_fast","manager_level"],"fuzzy":["manager_name","manager_number"],"field":["manager_name","manager_number"],"advanced":["manager_name","manager_number","manager_fast","manager_level","manager_begintime","manager_endtime","creat_time","manager_creater"]}',
            'extra' =>  '{"list":{"manager_number":{"bind":1},"manager_name":{"bind":1},"manager_begintime":{"bind":1},"manager_endtime":{"bind":1},"manager_person":{"bind":1},"manager_fast":{"bind":1},"manager_level":{"bind":1}}}',
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'pc',
            'template_type' => 1,
        ];

        $mobileListTemplate = [
            'name' => '移动端列表默认模板',
            'content' => '{"order":["manager_number","manager_name","manager_begintime","manager_endtime","manager_fast","manager_level"],"filter":["manager_fast","manager_level"],"fuzzy":["manager_number","manager_name"],"advanced":["manager_number","manager_name","manager_begintime","manager_endtime","manager_fast","manager_level","manager_creater","creat_time"]}',
            'extra' => '{"item":{"primary":{"fields":["manager_name"]},"time":{"fields":["manager_begintime"]},"secondary":{"fields":["manager_person"]},"remark":{"fields":["manager_number"]}}}',
            'table_key' => $tableKey,
            'from' => 1,
            'terminal' => 'mobile',
            'template_type' => 1,
        ];
        $pcResultId = DB::table('custom_template')->insertGetId($pcTemplate);
        $mobileResultId = DB::table('custom_template')->insertGetId($mobileTemplate);
        $pcListResultId = DB::table('custom_template')->insertGetId($pcListTemplate);
        $mobileListResultId = DB::table('custom_template')->insertGetId($mobileListTemplate);
        $this->setProjectNumberRemarkLang($pcResultId);
        $layoutSetting = [
            [
                'table_key' => $tableKey,
                'bind_template' => $pcListResultId,
                'bind_type' => 1,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $pcResultId,
                'bind_type' => 2,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $pcResultId,
                'bind_type' => 3,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $pcResultId,
                'bind_type' => 4,
                'terminal' => 'pc',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileListResultId,
                'bind_type' => 1,
                'terminal' => 'mobile',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileResultId,
                'bind_type' => 2,
                'terminal' => 'mobile',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileResultId,
                'bind_type' => 3,
                'terminal' => 'mobile',
            ],
            [
                'table_key' => $tableKey,
                'bind_template' => $mobileResultId,
                'bind_type' => 4,
                'terminal' => 'mobile',
            ],
        ];
        DB::table('custom_layout_setting')->insert($layoutSetting);
    }

    // 添加项目编号提示语的多语言
    private function setProjectNumberRemarkLang($customTemplatePcFormId) {
        $langs = ['项目编号可以为数字', 'The item number can be numeric'];
        $data = [];
        foreach ($langs as $lang) {
            $data[] = [
                'lang_key' => 'manager_number|remark',
                'lang_value' => $lang,
                'table' => 'custom_template',
                'column' => $customTemplatePcFormId,
                'option' => $customTemplatePcFormId,
            ];
        }
        DB::table('lang_zh_cn')->insert($data[0]);
        DB::table('lang_en')->insert($data[1]);
    }
}
