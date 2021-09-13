<?php

namespace App\EofficeApp\FormModeling\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\FormModeling\Repositories\FormModelingRepository;
use App\EofficeApp\FormModeling\Traits\FieldsRedisTrait;
use App\EofficeApp\FormModeling\Traits\FieldsTrait;
use App\EofficeApp\Project\NewServices\ProjectService;
use App\EofficeApp\User\Repositories\UserRepository;
use DB;
use Illuminate\Support\Facades\Log;
use Lang;
use Illuminate\Support\Facades\Redis;
use Schema;
use App\EofficeApp\ImportExport\Facades\Export;
use Illuminate\Support\Arr;
/**
 * 流程建模
 *
 * @author: 白锦
 *
 * @since：2019-03-22
 */
class FormModelingService extends BaseService
{
    use FieldsTrait;
    use FieldsRedisTrait;
    // tablekey 对应已存在的 数据表
    const CUSTOM_EXIST_TABLE = 'custom:table';
    // tablekey 对应自定义字段，前缀
    const CUSTOM_TABLE_FIELDS = 'custom:table_fields_';
    const PARSE_DATA = 'parse:data_';
    const CUSTOM_REMINDS = 'custom:reminds';
    public $current_lang;
    public $server_info;
    public $current_user_id;
    public $current_user_info;
    private $own = [];
    public function __construct()
    {
        $this->formModelingRepository = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->menuRepository = 'App\EofficeApp\Menu\Repositories\MenuRepository';
        $this->empowerService = "App\EofficeApp\Empower\Services\EmpowerService";
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->externalDatabaseService = 'App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService';
        $this->departmentDirectorRepository = "App\EofficeApp\System\Department\Repositories\DepartmentDirectorRepository";
        $this->departmentService = "App\EofficeApp\System\Department\Services\DepartmentService";
        $this->systemSecurityService = "App\EofficeApp\System\Security\Services\SystemSecurityService";
        $this->attendanceOutSendService = 'App\EofficeApp\Attendance\Services\AttendanceOutSendService';

    }

    /**
     * 获取表单模块列表
     *
     * @author 白锦
     *
     * @param
     *
     * @since  2019-03-22 创建
     *
     * @return array     返回结果
     */
    public function getFormModuleLists($params)
    {
        $params = $this->parseParams($params);
        $isExceptProjectTask = Arr::get($params, 'except_project_task', 0); // 是否排除项目任务的自定义字段
        $menuParent = [];
        $module = app($this->formModelingRepository)->getParent($params);
        $permissionModule = app($this->empowerService)->getPermissionModules();
        $menuFolder = [];
        $folder = [];
        foreach ($module as $k => $v) {
            if ($v->menu_parent < 1000 && !in_array($v->menu_parent, $permissionModule)) {
                continue;
            }
            $menuInfo = app($this->menuRepository)->getDetail($v->menu_parent);
            if (!empty($menuInfo)) {
                if ($menuInfo->menu_parent != 0) {
                    $parent = app($this->menuRepository)->getDetail($menuInfo->menu_parent);
                    if (isset($menuFolder[$menuInfo->menu_parent])) {
                        $menuFolder[$menuInfo->menu_parent]['menu_id'][] = $v->menu_parent;
                    } else {
                        $menuFolder[$menuInfo->menu_parent] = [
                            'menu_name' => trans_dynamic("menu.menu_name.menu_" . $parent->menu_id),
                            'menu_name_zm' => $parent->menu_name_zm,
                            'menu_from' => $parent->menu_from,
                            'menu_id' => [$v->menu_parent],
                        ];
                    }
                    $folder[] = $menuInfo->menu_parent;
                } else {
                    $menuParent[] = [
                        'menu_name' => trans_dynamic("menu.menu_name.menu_" . $menuInfo->menu_id),
                        'menu_name_zm' => $menuInfo->menu_name_zm,
                        'menu_from' => $menuInfo->menu_from,
                        'menu_id' => $v->menu_parent,
                    ];
                }
            }
        }
       
        foreach ($menuFolder as $key => $value) {
            array_push($value['menu_id'], $key);
            $getMenu = app($this->formModelingRepository)->getMenu($value['menu_id'], $params);
            foreach ($getMenu as $k => $v) {
                $v->menu_name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
            }
            $menuFolder[$key]['menu_id'] = $key;
            $menuFolder[$key]['children'] = $getMenu;
        }
        foreach ($menuParent as $key => $value) {
            if (in_array($value['menu_id'], $folder)) {
                unset($menuParent[$key]);
                continue;
            }
            $getMenu = app($this->formModelingRepository)->getMenu($value['menu_id'], $params);
            foreach ($getMenu as $k => $v) {
                $v->menu_name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
            }
            $menuParent[$key]['children'] = $getMenu;
        }

        $isExceptProjectTask && self::exceptProjectTaskCustomInfo($menuParent);
        return array_merge($menuParent, $menuFolder);
    }

    /**
     * 2019-03-23
     *
     * 保存自定义字段
     *
     * @param array $data
     * @param string $tableKey
     *
     * @return boolean
     */
    public function saveFormFields($data, $tableKey)
    {
        $saveData = [
            'update' => [],
            'add' => [],
            'delete' => [],
        ];
        $this->deleteRedisFields($tableKey);
        foreach ($data as $item) {
            //删除字段
            if ($item['modify_flag'] == 0) {
                // if (isset($item['is_system']) && $item['is_system'] == 1) {
                //     return ['code' => ['0x016010', 'fields']];
                // }
                if ($item['field_id'] == 0) {
                    return ['code' => ['0x016007', 'fields']];
                }
                $saveData['delete'][$item['field_id']] = $item;
            } else if ($item['modify_flag'] == 1) {
                //新增字段
                if (!isset($item['field_name']) && !$item['field_name']) {
                    return ['code' => ['0x016002', 'fields']];
                }
                if (!isset($item['field_code']) && !$item['field_code']) {
                    return ['code' => ['0x016016', 'fields']];
                }
                $customFields = app($this->formModelingRepository)->customFieldsNumber($tableKey, $item['field_code']);
                if ($customFields > 0) {
                    return ['code' => ['0x016034', 'fields']];
                }
                //获取明细
                if (!empty($item['field_directive']) && $item['field_directive'] == "detail-layout") {
                    $detailLayout = $item['field_code'];
                    foreach ($data as $v) {
                        if ($v['modify_flag'] == 1 && isset($v['field_options'])) {
                            $field_options = json_decode($v['field_options']);
                            if (isset($field_options->parentField) && $field_options->parentField == $detailLayout) {
                                $item['sub_fields'][] = $v;
                            }
                        }
                    }
                }
                $saveData['add'][] = $this->getFormCustomFieldsData($item, $tableKey, true);
            } else if ($item['modify_flag'] == 2) {
                //更新字段
                if (!isset($item['field_name']) && !$item['field_name']) {
                    return ['code' => ['0x016002', 'fields']];
                }
                if (!isset($item['field_code']) && !$item['field_code']) {
                    return ['code' => ['0x016016', 'fields']];
                }
                $saveData['update'][$item['field_id']] = $this->getFormCustomFieldsData($item, $tableKey);
            } else if ($item['modify_flag'] == 3) {
                //先删除再新增
                $saveData['deleteAdd'][] = $this->getFormCustomFieldsData($item, $tableKey, true);
            }
        }
        $this->saveLangFormFields($data, $tableKey); //保存多语言名字
        app($this->formModelingRepository)->createEmptyCustomDataTable($tableKey); //创建自定义字段数据表
        $errors = 0;
        foreach ($saveData as $key => $data) {
            if (!empty($data)) {
                $method = $key . 'CustomField';

                foreach ($data as $key => $item) {
                    if (!$this->$method($tableKey, $item, $key)) {
                        $errors++;
                    }
                }
            }
        }
        if ($errors == 0) {
            $this->refreshFields();
            $this->refreshReminds();
            // // 删除外键菜单文件
            FormModelingRepository::destroyCustomTabMenus();
            $locale = Lang::getLocale();
            Redis::del('lang_' . $locale);
            Redis::del('mulitlang_' . $locale);
            // 删除字段时，同步清空项目那边的权限数据
            if (self::isProjectSeriesCustomKey($tableKey)) {
                ProjectService::deleteDataRoleDeleteByFormModeling($saveData['delete'], $tableKey);
            }
            return true;
        }

        return ['code' => ['0x016018', 'fields']];
    }

    public function deleteAddCustomField($tableKey, $item, $key)
    {
        $id = app($this->formModelingRepository)->custom_fields_table($tableKey, $item['field_code']);
        //先删除字段再新增字段
        $this->deleteCustomField($tableKey, $item, $id);
        $this->addCustomField($tableKey, $item, $id);
        return true;
    }

    /**
     * 2019-03-23
     *
     * 获取自定义字段表单数据
     *
     * @param array $data
     * @param string $tableKey
     * @param boolean $add
     *
     * @return array
     */
    private function getFormCustomFieldsData($data, $tableKey, $add = false)
    {
        $fieldData = [
            'field_name' => $tableKey . "_" . $data['field_code'],
            'field_directive' => $this->defaultValue('field_directive', $data, 'text'),
            // 'field_search' => $this->defaultValue('field_search', $data, 0),
            // 'field_filter' => $this->defaultValue('field_filter', $data, 0),
            // 'field_list_show' => $this->defaultValue('field_list_show', $data, 0),
            'field_options' => $this->defaultValue('field_options', $data, ''),
            // 'field_explain' => $this->defaultValue('field_explain', $data, ''),
            // 'field_sort' => $this->defaultValue('field_sort', $data, 0),
            // 'field_hide' => $this->defaultValue('field_hide', $data, 0),
            // 'field_allow_order' => $this->defaultValue('field_allow_order', $data, 0),
            // 'mobile_list_field' => $this->defaultValue('mobile_list_field', $data, ''),
            'is_foreign_key' => $this->defaultValue('is_foreign_key', $data, 0),
            'updated_at' => date('Y-m-d H:i:s'),
            'sub_fields' => $this->defaultValue('sub_fields', $data, ''),
            'field_table_key' => $tableKey,
            'field_code' => $data['field_code'],
        ];
        //新增字段需要更多属性
        if ($add) {
            $fieldData['field_data_type'] = $this->defaultValue('field_data_type', $data, 'varchar');
            $fieldData['is_system'] = $this->defaultValue('is_system', $data, 0);
            $fieldData['created_at'] = date('Y-m-d H:i:s');
        }
        // $this->saveLangFormFields($data, $tableKey); //保存多语言名字
        return $fieldData;
    }
    /**
     * 保存多语言名字
     *
     * @param   array   $data
     * @param   string  $tableKey
     *
     * @return  boolean
     */
    public function saveLangFormFields($datas, $tableKey)
    {

        //获取目前系统多语言
        $saveMultiLang = [];
        $saveLang = [];
        $saveOptionLang = [];
        foreach ($datas as $data) {
            $field_name = isset($data['field_name_lang']) ? $data['field_name_lang'] : '';
            //字段名字保存
            if (!empty($field_name) && is_array($field_name)) {
                foreach ($field_name as $key => $value) {
                    $langData = [
                        'table' => 'custom_fields_table',
                        'column' => 'field_name',
                        'lang_key' => $tableKey . "_" . $data['field_code'],
                        'lang_value' => $value,
                    ];
                    $saveMultiLang[$key][] = $langData;
                }
            } else {
                $saveLang[] = [
                    'table' => 'custom_fields_table',
                    'column' => 'field_name',
                    'lang_key' => $tableKey . "_" . $data['field_code'],
                    'lang_value' => $data['field_name'],
                ];

            }
            //option多语言保存
            $field_options_lang = isset($data['field_options_lang']) ? $data['field_options_lang'] : '';

            if (!empty($field_options_lang) && is_array($field_options_lang)) {
                foreach ($field_options_lang as $option => $field_options) {
                    if (is_array($field_options)) {
                        foreach ($field_options as $key => $value) {
                            if (trim($value) === '') {
                                remove_dynamic_langs("custom_fields_table.field_options.{$tableKey}_{$data['field_code']}.{$option}", $key);
                            } else {
                                $langOption = [
                                    'table' => 'custom_fields_table',
                                    'column' => 'field_options',
                                    'option' => $tableKey . "_" . $data['field_code'],
                                    'lang_key' => $option,
                                    'lang_value' => $value,
                                ];
                                //var_dump($langOption);
                                $saveOptionLang[$key][] = $langOption;
                            }
                        }
                    }
                }
            }

        }
        if (!empty($saveMultiLang)) {
            foreach ($saveMultiLang as $key => $value) {
                app($this->langService)->mulitAddDynamicLang($value, $key);
            }
        }
        if (!empty($saveLang)) {
            app($this->langService)->mulitAddDynamicLang($saveLang);
        }
        if (!empty($saveOptionLang)) {
            foreach ($saveOptionLang as $key => $value) {
                app($this->langService)->mulitAddDynamicLang($value, $key);
            }
        }
        return true;
    }

    /**
     * 2019-03-25
     *
     * 添加自定义字段
     *
     * @param array $data
     * @param string $tableKey
     *
     * @return boolean
     */
    private function addCustomField($tableKey, $data, $key)
    {
        $insert = $data;
        unset($insert['sub_fields']);
        if ($fieldId = app($this->formModelingRepository)->addCustomField($insert)) {
            $fieldOption = json_decode($data['field_options'], true);
            if (!empty($fieldOption) && !empty($fieldOption['parentField'])) {

                $table = app($this->formModelingRepository)->getCustomDataSubTableName($tableKey, $fieldOption['parentField']);
            } else {
                $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
            }
            if ($data['field_directive'] == 'detail-layout') {
                //明细字段创建副表
                return app($this->formModelingRepository)->createCustomDataSubTable($tableKey, $data['field_code'], $data['sub_fields']);
            } else if ($data['field_directive'] == 'area') {
                foreach ($fieldOption['relation'] as $item) {
                    $item['field_data_type'] = 'varchar';
                    app($this->formModelingRepository)->addCustomColumn($item, $tableKey, $table);
                }
                return true;
            } else if ($data['field_directive'] == 'upload') {
                //附件字段
                return true;
            } else {
                //其他字段
                app($this->formModelingRepository)->addCustomColumn($data, $tableKey, $table);
                if (isset($fieldOption['aggregate'])) {
                    $fieldData['field_code'] = '_total_' . $data['field_code'];
                    $fieldData['field_data_type'] = 'varchar';
                    $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
                    app($this->formModelingRepository)->addCustomColumn($fieldData, $tableKey, $table);
                    //更新明细合计历史数据
                    app($this->formModelingRepository)->updateDetailLayoutTotal($tableKey, $fieldOption['parentField'], $data['field_code']);
                    return true;
                }
                return true;
            }
        }
        app($this->formModelingRepository)->deleteCustomField($fieldId);
        
        return false;
    }
    /**
     * 2019-03-25
     *
     * 删除自定义字段
     *
     * @param int $fieldId
     * @param array $item
     * @param string $tableKey
     *
     * @return boolean
     */
    private function deleteCustomField($tableKey, $item, $fieldId)
    {
        // $this->deleteRedisFields($tableKey);
        if (!$fieldInfo = app($this->formModelingRepository)->showCustomField($fieldId)) {
            return true;
        }
        $fieldOption = json_decode($fieldInfo->field_options, true);
        if (app($this->formModelingRepository)->deleteCustomField($fieldId)) {
            //删除模版中的字段
            app($this->formModelingRepository)->deleteLayoutFields($tableKey, $item['field_code']);
            $fieldDirective = $fieldInfo->field_directive;
            if (!empty($fieldOption) && !empty($fieldOption['parentField'])) {
                $total_field_code = '_total_' . $item['field_code'];
                $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
                app($this->formModelingRepository)->deleteCustomDataTableColumn($total_field_code, $tableKey, $table);
                app($this->formModelingRepository)->deleteLayoutFields($tableKey, $total_field_code);
                $table = app($this->formModelingRepository)->getCustomDataSubTableName($tableKey, $fieldOption['parentField']);
            } else {
                $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
            }

            if ($fieldDirective == 'upload') {
                //附件字段
                return true;
            } else if ($fieldDirective == 'detail-layout') {
                //明细字段
                app($this->formModelingRepository)->dropTable('custom_data_' . $tableKey . '_' . $item['field_code']);
                return true;
            } else if ($fieldDirective == 'area') {
                foreach ($fieldOption['relation'] as $item) {
                    app($this->formModelingRepository)->deleteCustomDataTableColumn($item['field_code'], $tableKey, $table);
                }
                return true;
            } else {
                return app($this->formModelingRepository)->deleteCustomDataTableColumn($item['field_code'], $tableKey, $table);
            }
        }

        return false;
    }
    /**
     * 2019-03-25
     *
     * 更新自定义字段
     *
     * @param array $data
     * @param int $fieldId
     *
     * @return boolean
     */
    private function updateCustomField($tableKey, $data, $fieldId)
    {
        unset($data['sub_fields']);
        if (!$fieldInfo = app($this->formModelingRepository)->showCustomField($fieldId)) {
            return true;
        }
        $newFieldOption = json_decode($data['field_options'], true);
        $fieldOption = json_decode($fieldInfo->field_options, true);

        if (!empty($fieldOption) && !empty($fieldOption['parentField'])) {
            $table = app($this->formModelingRepository)->getCustomDataSubTableName($tableKey, $fieldOption['parentField']);
            $fieldData['field_code'] = '_total_' . $data['field_code'];
            if (isset($newFieldOption['aggregate'])) {
                $fieldData['field_data_type'] = 'varchar';
                $mainTable = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
                app($this->formModelingRepository)->addCustomColumn($fieldData, $tableKey, $mainTable);

                app($this->formModelingRepository)->updateDetailLayoutTotal($tableKey, $newFieldOption['parentField'], $data['field_code']);
            } else {
                app($this->formModelingRepository)->deleteCustomDataTableColumn($fieldData['field_code'], $tableKey, $table);
                app($this->formModelingRepository)->deleteLayoutFields($tableKey, $fieldData['field_code']);
            }
        } else {
            $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
        }
        if ($data['field_directive'] == 'area') {
            if ($fieldOption['relation'] > $newFieldOption['relation'] && isset($fieldOption['relation'][2]['field_code'])) {
                app($this->formModelingRepository)->deleteCustomDataTableColumn($fieldOption['relation'][2]['field_code'], $tableKey, $table);
            }
            if ($fieldOption['relation'] < $newFieldOption['relation'] && isset($newFieldOption['relation'][2])) {
                app($this->formModelingRepository)->addCustomColumn($newFieldOption['relation'][2], $tableKey, $table);
            }
        }

        return app($this->formModelingRepository)->updateCustomField($data, $fieldId);
    }
    /**
     * 2019-03-25
     *
     * 获取自定义字段列表
     *
     * @param array $param
     * @param string $tableKey
     *
     * @return array
     */
    public function listCustomFields($param, $tableKey)
    {
        $result = app($this->formModelingRepository)->listCustomFields($tableKey, $this->parseParams($param));
        $data = [];
        if (isset($param['exportFields']) && $param['exportFields']) {
            $result = $this->filterExportFields($result);
        }
        foreach ($result as $key => $value) {
            if (isset($param['exceptFields']) && !empty($param['exceptFields']) && in_array($value->field_code, explode(",", $param['exceptFields']))) {
                continue;
            }
            $this->transferFieldOptions($value, $tableKey, $param);
            $value->field_name = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $value->field_code);
            if (isset($param['lang']) && !empty($param['lang'])) {
                $value->field_name_lang = app($this->langService)->transEffectLangs("custom_fields_table.field_name." . $tableKey . "_" . $value->field_code);
            }
            $data[] = $value;
        }
        // 定义表单时自定义模块需要拼接指定模板的标签名称
        if (isset($param['type']) && ($param['type'] == 'editForm')) {
            $data = $this->handleCustomFieldsTitle($tableKey, $data);
        }

        return $data;
    }

    /**
     * 多语言转化option
     *
     * @param   array  $data
     * @param   string  $tableKey
     * @param   boolean  $flag
     *
     * @return  array
     */
    public function transferFieldOptions($data, $tableKey, $param)
    {
        $patten = '/\$(\w+)\$/';
        preg_match_all($patten, $data->field_options, $variableName);
        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            $field_options_lang = [];
            foreach ($variableName[1] as $k => $v) {
                $transContent = mulit_trans_dynamic("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v");
                $noTransfer = isset($param['noTransfer'])&&$param['noTransfer']?1:0;
                if(!$noTransfer){
                    if (strstr($transContent, "\\")) {
                        $transContent = str_replace("\\", "\\\\", $transContent);
                    }
                    $data->field_options = str_replace($variableName[0][$k], $transContent, $data->field_options);
                }
                if (isset($param['lang']) && !empty($param['lang'])) {
                    $field_options_lang[$v] = app($this->langService)->transEffectLangs("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v", true);
                }
            }
            if (!empty($field_options_lang) && isset($param['lang']) && !empty($param['lang'])) {
                $data->field_options_lang = $field_options_lang;
            }
        }
        return $data;
    }

    /**
     * 2019-07-30
     *
     * 保存模板
     *
     * @param   array  $data
     *
     * @return  boolean
     */
    public function saveTemplate($data)
    {
        $tableKey = $data['table_key'];
        //格式化字段
        $save = $this->parseTemplateData($data, $tableKey);
        $save['created_at'] = date('Y-m-d H:i:s');
        $id = app($this->formModelingRepository)->saveTemplate($save);
        //多语言处理
        $this->multiTemplateLanguage($data, $id);
        return $id;

    }

    /**
     * 2019-07-30
     *
     * 编辑模板
     *
     * @param   array  $data
     * @param   string  $tableKey
     *
     * @return  boolean
     */
    public function editTemplate($data, $id)
    {
        $tableKey = $data['table_key'];
        //格式化字段
        $update = $this->parseTemplateData($data, $tableKey);
        //多语言处理
        $this->multiTemplateLanguage($data, $id);
        return app($this->formModelingRepository)->editTemplate($update, $id);
    }

    /**
     * 删除模板
     *
     * @param   string  $tableKey
     *
     * @return  boolean
     */
    public function deleteTemplate($id)
    {
        return app($this->formModelingRepository)->deleteTemplate($id);
    }

    /**
     * 格式化模板字段
     *
     * @param   array  $data  [$data description]
     *
     * @return  [type]         [return description]
     */
    public function parseTemplateData($data, $tableKey)
    {
        $fieldData = [
            'content' => $this->defaultValue('content', $data, ''),
            'extra' => $this->defaultValue('extra', $data, ''),
            'terminal' => $this->defaultValue('terminal', $data, 'pc'),
            'template_type' => $this->defaultValue('template_type', $data, ''),
            'from' => $this->defaultValue('from', $data, 1),
            'table_key' => $tableKey,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (isset($data['name'])) {
            $fieldData['name'] = $data['name'];
        }
        return $fieldData;
    }
    /**
     * 模版多语言
     *
     * @param   array  $data
     * @param   int  $id
     *
     * @return  boolean
     */
    public function multiTemplateLanguage($data, $id)
    {
        //如果是列表 自定义列多语言
        $list_column_lang = isset($data['list_column_lang']) ? $data['list_column_lang'] : '';
        if (!empty($list_column_lang) && is_array($list_column_lang)) {
            foreach ($list_column_lang as $option => $list_column) {
                if (is_array($list_column)) {
                    foreach ($list_column as $key => $value) {
                        $local = $key; //可选
                        if (trim($value) === '') {
                            remove_dynamic_langs("custom_template.$id.$option", $local);
                        } else {
                            $langOption = [
                                'table' => 'custom_template',
                                'column' => $id,
                                'lang_key' => $option,
                                'lang_value' => $value,
                            ];
                            app($this->langService)->addDynamicLang($langOption, $local);
                        }
                    }
                }
            }
        }
        //如果是表单模版 多语言
        $field_style_lang = isset($data['field_style_lang']) ? $data['field_style_lang'] : '';
        if (!empty($field_style_lang) && is_array($field_style_lang)) {
            foreach ($field_style_lang as $field => $field_style) {
                if (is_array($field_style)) {
                    foreach ($field_style as $option => $value) {
                        if (is_array($value)) {
                            foreach ($value as $lang => $v) {
                                if (empty($v)) {
                                    remove_dynamic_langs("custom_template.{$id}.$option", $lang);
                                } else {
                                    $langOption = [
                                        'table' => 'custom_template',
                                        'column' => $id,
                                        'lang_key' => $option,
                                        'lang_value' => $v,
                                    ];
                                    app($this->langService)->addDynamicLang($langOption, $lang);
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 获取模板列表
     *
     * @param   string  $tableKey
     *
     * @return  array
     */
    public function getTemplateList($tableKey, $param)
    {
        $templates = app($this->formModelingRepository)->getTemplateList($tableKey, $this->parseParams($param));
        $bindTemplate = $this->getBindTemplate($tableKey, $param);
        $bindId = [];
        foreach ($bindTemplate as $key => $value) {
            $bindId[] = $value->bind_template;
        }
        foreach ($templates as $k => $template) {
            if (in_array($template->template_id, $bindId)) {
                $templates[$k]->active = 1;
            } else {
                $templates[$k]->active = 0;
            }
        }
        $total = app($this->formModelingRepository)->getTemplateTotal($tableKey, $this->parseParams($param));
        return ['list' => $templates, 'total' => $total];
    }

    /**
     * 获取模板信息
     *
     * @param   int  $id
     * @param   array  $param
     *
     * @return  array
     */
    public function getTemplate($id)
    {
        $data = app($this->formModelingRepository)->getTemplate($id);
        return $this->transferTemplateExtra($data);
    }
    /**
     * 获取绑定的模板
     *
     * @param   [type]  $param  [$param description]
     *
     * @return  [type]          [return description]
     */
    public function getBindTemplate($tableKey, $param)
    {
        $data = app($this->formModelingRepository)->getBindTemplate($tableKey, $param);

        if (isset($param['bind_type'])) {
            return $this->transferTemplateExtra($data);
        }
        return $data;

    }

    /**
     * 多语言转化模板
     *
     * @param   array  $data
     *
     * @return  array
     */
    public function transferTemplateExtra($data, $lang = true)
    {
        if (!$data || !isset($data->table_key)) {
            return $data;
        }
        $tableKey = $data->table_key;
        $patten = '/\$([A-Za-z0-9_|]+)\$/';
        $extra = json_decode($data->extra, true);
        preg_match_all($patten, $data->extra, $variableName);
        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            $field_options_lang = [];
            foreach ($variableName[1] as $k => $v) {

                $transContent = mulit_trans_dynamic("custom_template.{$data->template_id}.$v");
                if ($v == 'tabLayout|0') {
                    $data->extra = str_replace($variableName[0][$k], trans('fields.0x016036'), $data->extra);
                    continue;
                }
                if (strstr($transContent, "\\")) {
                    $transContent = str_replace("\\", "\\\\", $transContent);
                }
                $data->extra = str_replace($variableName[0][$k], $transContent, $data->extra);
                if ($data->template_type == 1) {
                    $field_options_lang[$v] = app($this->langService)->transEffectLangs("custom_template.{$data->template_id}.$v", true);
                } else {
                    $pattens = '/([A-Za-z0-9_|]+)\|/';
                    preg_match_all($pattens, $v, $preg);
                    if (!empty($preg) && isset($preg[0]) && isset($preg[1]) && isset($preg[1][0])) {
                        $field_options_lang[$preg[1][0]][$v] = app($this->langService)->transEffectLangs("custom_template.{$data->template_id}.$v", true);
                    }
                }

            }
            if ($lang) {
                if ($data->template_type == 1) {
                    $data->list_column_lang = $field_options_lang;

                } else {
                    $data->field_style_lang = $field_options_lang;
                }

            }
        }
        return $data;
    }

    /**
     * 设置绑定模板
     *
     * @param   array  $data
     * @param   array  $tableKey 如果传了就使用该tableKey
     *
     * @return  boolean
     */
    public function bindTemplate($data, $tableKey = null)
    {
        foreach ($data as $key => $value) {
            $fieldData = [
                'bind_type' => $this->defaultValue('bind_type', $value, ''),
                'bind_template' => $this->defaultValue('bind_template', $value, ''),
                'terminal' => $this->defaultValue('terminal', $value, 'pc'),
                'table_key' => $tableKey ? $tableKey : $value['table_key'],
            ];
            app($this->formModelingRepository)->saveBindTemplate($fieldData);
        }
        return true;

    }

    /**
     * 获取配置信息
     *
     * @param   string  $tableKey
     * @param   array  $param
     *
     * @return  object
     */
    public function getCustomMenu($tableKey, $param)
    {
        if (isset($param['fields']) && is_string($param['fields'])) {
            $param['fields'] = explode(',', rtrim($param['fields'], ','));
            // if (!in_array('menu_code', $param['fields'])) {
            //     $param['fields'][] = 'menu_code';
            // }
        }
        $customMenu = app($this->formModelingRepository)->getCustomMenu($tableKey, $param);
        if (isset($customMenu->add_permission)) {
            $addPermission = $this->parsePermission($customMenu->add_permission);
            $customMenu->add = $this->getPermissionValue($addPermission);
        }
        if (isset($customMenu->import_permission)) {
            $importPermission = $this->parsePermission($customMenu->import_permission);
            $customMenu->import = $this->getPermissionValue($importPermission);
        }
        if (isset($customMenu->export_permission)) {
            $exportPermission = $this->parsePermission($customMenu->export_permission);
            $customMenu->export = $this->getPermissionValue($exportPermission);
        }
        if (isset($customMenu->menu_code)) {
            $customMenu->menu_name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $customMenu->menu_code);
        }
        return $customMenu;

    }
    /**
     * 获取数据列表
     *
     * @param   string  $tableKey
     * @param   array  $param
     *
     * @return  array
     */
    public function getCustomDataLists($param, $tableKey, $own = '')
    {
        if (!$own) {
            $own = own();
        }
        $result['list'] = [];
        //判断数据表是否存在
        if (!app($this->formModelingRepository)->hasCustomDataTable($tableKey)) {
            return [];
        }
        $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
        //解析参数
        $param = $this->parseParams($param);
        //获取返回数据格式
        $response = $param['response'] ?? 'both';
        //获取过滤条件
        $param['filter'] = $this->getCustomConfig($tableKey, 'filter', $param, $own);

        //判断是否存在按外键筛选
        $foreignKey = $param['foreign_key'] ?? '';
        if ($foreignKey && $foreignKeyField = $this->rsGetForeignKeyField($tableKey)) {
            $param['filter'][$foreignKeyField] = [$foreignKey];
        }
        $filterFields = isset($param['fields']) ? $param['fields'] : [];
        $fields = $this->listRedisCustomFields($tableKey, $filterFields, false, true, false);
        if (empty($fields)) {
            return [];
        }
        //权限
        $customPower = app($this->formModelingRepository)->getCustomMenu($tableKey);
        //编辑权限
        $editPermission = $this->parsePermission($customPower->edit_permission);
        //查看权限
        $viewPermission = $this->parsePermission($customPower->view_permission);
        //删除权限
        $deletePermission = $this->parsePermission($customPower->delete_permission);
        //不在部门角色用户可查看选择器的用户
        if ($viewPermission && $viewPermission != 'all' && isset($viewPermission['user']) && !in_array(own('user_id'), $viewPermission['user'])) {
            $relationView = $viewPermission['relation'];
            if ($relationView) {
                $param['viewPermission'] = ['field' => $relationView, 'value' => $viewPermission['creator']];
            } else {
                if ($response == 'both') {
                    return ['total' => 0, 'list' => []];
                } else if ($response == 'data') {
                    return [];
                } else {
                    return 0;
                }
            }
        }
        //获取默认排序
        $aggregateFields = [];
        $platform = (isset($param['platform']) && !empty($param['platform'])) ? $param['platform'] : 'pc';
        $listParam = [
            'terminal' => $platform,
            'bind_type' => 1,
        ];
        $listLayout = app($this->formModelingRepository)->getBindTemplate($tableKey, $listParam);
        if (!empty($listLayout) && isset($listLayout->extra)) {
            $extra = json_decode($listLayout->extra, true);
            if (isset($extra['defaultOrder']) && !empty($extra['defaultOrder'])) {
                $param['defaultOrder'] = $extra['defaultOrder'];
            }
            if (isset($param['aggregate_field']) && !empty($param['aggregate_field'])) {
                $aggregateFields = explode(",", $param['aggregate_field']);
            } else {
                if (isset($extra['list']) && !empty($extra['list'])) {
                    foreach ($extra['list'] as $k => $v) {
                        if (isset($v['aggregate'])) {
                            $aggregateFields[] = $k;
                        }
                    }
                }
            }
            $param['aggregate'] = $aggregateFields;
        }
        // 获取总数
        $total = app($this->formModelingRepository)->getCustomDataTotal($tableKey, $param);
        $result['total'] = $total->total;
        
        // 仅返回数据
        if ($result['total'] && $response == 'count') {
            return $result['total'];
        }
        //解决数值排序问题
        $redisFields = $this->getRedisCustomFields($tableKey);
        $numberFields = [];
        foreach ($redisFields as $redisField) {
            if ($redisField->field_directive == "number") {
                $numberFields[] = $table . "." . $redisField->field_code;
            }
        }
        $param['numberFields'] = $numberFields;
        //获取列表数据
        //处理修改每页条数后重新获取数据的问题
        if ($result['total'] == 0) {
            $param['page'] = 1;
        }
        $limit = $param['limit'] ?? config('eoffice.pagesize');
        $totalPage = ceil($result['total'] / $limit);
        $page = $param['page'] ?? 0;
        if ($totalPage < $page) {
            $param['page'] = $totalPage;
        }
        
        $lists = app($this->formModelingRepository)->getCustomDataList($tableKey, $param);
        //列表存redis获取全局多语言
        if (!$this->current_lang) {
            $this->current_lang = Lang::getLocale();
        }
        $newList = $lists;
        
        //解析列表数据
        if (!empty($lists)) {
            foreach ($lists as $itemKey => $item) {
                $newList[$itemKey]->delete = 1;
                $newList[$itemKey]->edit = 1;
                //编辑权限
                $editRelation = isset($editPermission['relation']) ? $editPermission['relation'] : '';
                $editRelationData = isset($item->$editRelation) ? $item->$editRelation : '';
                $newList[$itemKey]->edit = $this->getPermissionValue($editPermission, $editRelationData);
                
                $deleteRelation = isset($deletePermission['relation']) ? $deletePermission['relation'] : '';
                $deleteRelationData = isset($item->$deleteRelation) ? $item->$deleteRelation : '';
                $newList[$itemKey]->delete = $this->getPermissionValue($deletePermission, $deleteRelationData);
                $info = clone $item;
                foreach ($fields as $key => $field) {
                    $value = isset($item->$key) ? $item->$key : '';
                    $field = $this->transferFieldOptions($field, $tableKey, []);
                    $option = json_decode($field->field_options, true);
                    $directive = $field->field_directive;
                    if ($directive == "select" || $directive == "selector" || $directive == "radio" || $directive == "checkbox") {
                        $newList[$itemKey]->{'raw_' . $key} = $value;
                    }

                    $parseValue = $this->rsGetFieldValue($tableKey, $field, $info, $value);
                    if ($directive == "selector") {
                        $newList[$itemKey]->{'map_' . $key} = $parseValue ?: null;

                        $newList[$itemKey]->{$key} = is_array($parseValue) ? implode(', ',array_values($parseValue)) : '';
                    } else {
                        $newList[$itemKey]->{$key} = $parseValue;
                    }
                    
                    if (isset($option['parentField'])) {
                        $detailLayoutTotal = '_total_' . $field->field_code;
                        $field->type = 'total';
                        $value = isset($item->$detailLayoutTotal) ? $item->$detailLayoutTotal : '';
                        $newList[$itemKey]->$detailLayoutTotal = $this->numberDirective($value, $info, $field);
                    }
                }
            }
        }
        //合计
        $aggregate = [];
        if (!empty($aggregateFields)) {
            foreach ($aggregateFields as $field) {
                $aggregate[$field] = 0;
                if (isset($total->{$field}) && !empty($total->{$field})) {
                    $aggregate[$field] = $total->{$field};
                }
            }
        }
        $result['list'] = $newList;
        // 返回结果集
        if ($response == 'both') {
            $resultData = ['total' => $result['total'], 'list' => $result['list']];
            if (!empty($aggregate)) {
                $resultData['aggregate'] = $aggregate;
            }
        } else if ($response == 'data') {
            $resultData = $result['list'];
        } else {
            $resultData = $result['total'];
        }
        $afterConfig = config('customfields.listAfter.' . $tableKey);
        if ($afterConfig) {
            $resultData = $this->getCustomConfig($tableKey, 'listAfter', $resultData, $own);
        }
        return $resultData;
    }
    /**
     * 获取过滤条件
     *
     * @param string $tableKey
     * @param array $param
     *
     * @return array
     */
    private function getCustomConfig($tableKey, $config, &$param = '', $id = '')
    {
        if (self::isProjectCustomKey($tableKey)) {
            $tableKey = 'project_value_'; // 处理后，所有项目只需要配置一个回调
        }

        $filterConfig = config('customfields.' . $config . '.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                if ($id) {
                    $power = app($services)->$method($param, $id);
                } else {
                    $power = app($services)->$method($param);
                }
                if ($power === false) {
                    return ['code' => ['0x016024', 'fields']];
                }
                return $power;
            }
        }
    }
    /**
     * 获取自定义数据表主键名称
     *
     * @staticvar array $array
     * @param string $tableKey
     *
     * @return string
     */
    private function getDataTablePrimaryKeyName($tableKey)
    {
        static $array;

        if (isset($array[$tableKey])) {
            return $array[$tableKey];
        }

        return $array[$tableKey] = app($this->formModelingRepository)->getCustomDataPrimayKey($tableKey);
    }
    //新增数据
    public function addCustomData($data, $tableKey, $from = null)
    {
        if (empty($data)) {
            return "";
        }
        // from 为空则验证权限
        if (!$from) {
            //获取权限
            $power = $this->getCustomConfig($tableKey, 'addBefore', $data);
            if (isset($power['code'])) {
                return $power;
            }
            $customMenu = app($this->formModelingRepository)->getCustomMenu($tableKey);

            if (isset($customMenu->add_permission)) {
                $addPermission = $this->parsePermission($customMenu->add_permission);
                $currentCreator = (isset($data['creator']) && !empty($data['creator'])) ? $data['creator'] : own('user_id');
                $addPower = $this->getPermissionValue($addPermission,'' ,['user_id' => $currentCreator]);
                if (!$addPower) {
                    return ['code' => ['0x016029', 'fields']];
                }
            }
        }
        $import = false;
        if (isset($data['outsource'])) {
            $import = true;
            $data = $this->parseOutsourceData($data, $tableKey);
        }
        $fieldsMap = $this->listRedisCustomFields($tableKey, [], true);

        $parseData = $this->parseCustomFormData($tableKey, $fieldsMap, $data, 'true',$import);
        $auth = $this->authDataRequired($fieldsMap, $parseData, $tableKey);
        if (isset($auth['code'])) {
            return $auth;
        }
        $this->filterSaveData($parseData);
        $checkUpload = $this->checkUpload($fieldsMap, $parseData);
        if (isset($checkUpload['code'])) {
            return $checkUpload;
        }
        if (isset($data['detailLayout'])) {
            $parseData['detailLayout'] = $data['detailLayout'];
        }
        if (isset($data['data_id'])) {
            $parseData['main']['data_id'] = $data['data_id'];
        }
        $customDataTable = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
        if (Schema::hasColumn($customDataTable, 'creator')) {
            $parseData['main']['creator'] = (isset($data['creator']) && !empty($data['creator'])) ? $data['creator'] : own('user_id'); //创建人
        }
        $dataId = app($this->formModelingRepository)->addCustomData($tableKey, $parseData['main']);
        $parseData['main']['id'] = $dataId;
        $filterAdd = $this->getCustomConfig($tableKey, 'addDataAfter', $parseData['main']);
        if (isset($filterAdd['code'])) {
            return $filterAdd;
        }
        $subDataParam = [];
        $subDataParam['edit'] = false;
        $subDataParam['fieldsMap'] = $fieldsMap;
        if ($dataId) {
            if (isset($data['outsource'])) {
                $subDataParam['outsource'] = true;
                $this->saveCustomSubData($dataId, $parseData['detailLayout'], $tableKey, $subDataParam); //明细字段新增
            } else {
                $this->saveCustomSubData($dataId, $parseData['detailLayout'], $tableKey, $subDataParam); //明细字段新增
            }
            //保存明细合计值
            $this->saveDetailLayoutTotalFields($fieldsMap, $dataId);
            //附件关系新增
            $this->addCustomAttachmentData($dataId, $parseData['upload'], $tableKey);

            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($tableKey, $dataId);
            return $dataId;
        }

        return false;
    }
    public function saveDetailLayoutTotalFields($fieldsMap, $dataId)
    {
        $totalFields = [];
        if ($fieldsMap) {
            foreach ($fieldsMap as $field) {
                $field_options = json_decode($field->field_options, true);
                if (isset($field_options['aggregate']) && isset($field_options['parentField'])) {
                    app($this->formModelingRepository)->saveDetailLayoutTotalFields($field->field_table_key, $field_options['parentField'], $field->field_code, $dataId);
                }
            }
        }
        return $totalFields;
    }
    public function authDataValid($tabKey, $data, $param = [])
    {
        $fieldsMap = app($this->formModelingRepository)->listCustomFields($tabKey, $param);
        
        if ($fieldsMap->isNotEmpty()) {
            $fieldsMap = $fieldsMap->keyBy('field_code');
        }
        $res['main'] = $data;
        $auth = $this->authDataRequired($fieldsMap, $res, $tabKey);
        if (isset($auth['code'])) {
            return $auth;
        } else {
            return true;
        }

    }
    public function authDataRequired($fieldsMap, $data, $tableKey)
    {
        $unDisplayFields = [];
        foreach ($fieldsMap as $k => $v) {
            $fieldName = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $v->field_code);
            if (isset($v->field_options)) {
                $field_options = json_decode($v->field_options);
                if(isset($field_options->parentField) && in_array($field_options->parentField,$unDisplayFields)){
                    //明细布局设置隐藏，整个明细布局不验证
                    continue;
                }
                if(isset($field_options->displayCondition)){
                    $displayCondition = $field_options->displayCondition;
                    $content = $displayCondition->content;
                    $res = $this->getFormulaStr($content,$data['main']);
                    if(!$res){
                        $unDisplayFields[]  =  $k;
                        continue;
                    }
                }
                if (isset($field_options->validate)) {
                    $validate = $field_options->validate;
                    //验证唯一
                    if (isset($validate->unique) && $validate->unique == 1) {
                        
                        //验证明细
                        if (!empty($field_options) && !empty($field_options->parentField)) {
                            $parent = $field_options->parentField;
                            if (!empty($parent) && !empty($data['detailLayout'])) {
                                if (isset($data['detailLayout'][$parent]) && !empty($data['detailLayout'][$parent]) && is_array($data['detailLayout'][$parent])) {
                                    $uniqueData = [];
                                    foreach ($data['detailLayout'][$parent] as $key => $value) {
                                        if (isset($value[$k]) && $value[$k] !== '') {
                                            //未插入数据库的数据唯一性处理
                                            if (isset($uniqueData[$k .'.' .$value[$k]])) {
                                                return ['code' => ['0x016031', 'fields'], 'dynamic' => $fieldName . trans('fields.exist')];
                                            }
                                            $uniqueData[$k .'.' .$value[$k]] = 1;
                                            $subTable = app($this->formModelingRepository)->getCustomDataSubTableName($tableKey, $parent);
                                            $checkUnique = [
                                                'table' => $subTable,
                                                'field' => $k,
                                                'value' => $value[$k],
                                                'is_system' => $v->is_system,
                                            ];
                                            $res = app($this->formModelingRepository)->checkDataUnique($checkUnique);
                                            if (!$res) {
                                                return ['code' => ['0x016031', 'fields'], 'dynamic' => $fieldName . trans('fields.exist')];
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            
                            if (isset($data['main'][$k]) && !emptyWithoutZero($data['main'][$k])) {
                                //未插入数据库的数据唯一性处理
                                if (isset($this->mainUniqueData[$k . '.' . $data['main'][$k]])) {
                                    return ['code' => ['0x016031', 'fields'], 'dynamic' => $fieldName . trans('fields.exist')];
                                }
                                $this->mainUniqueData[$k . '.' . $data['main'][$k]] = 1;
                                
                                $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
                                //编辑如果不变则不验证唯一
                                $id = isset($data['main']['id']) ? $data['main']['id'] : '';
                                $checkUnique = [
                                    'table' => $table,
                                    'field' => $k,
                                    'value' => $data['main'][$k],
                                    'primaryKey' => $this->getDataTablePrimaryKeyName($tableKey),
                                    'primaryValue' => $id,
                                    'data' => $data['main'],
                                    'is_system' => $v->is_system,

                                ];
                                //模块特殊处理唯一性
                                if (config('customfields.checkUnique.' . $tableKey)) {
                                    $check = $this->getCustomConfig($tableKey, 'checkUnique', $checkUnique);
                                    if (isset($check['code'])) {
                                        return ['code' => ['0x016031', 'fields'], 'dynamic' => $fieldName . trans('fields.exist')];
                                    }
                                } else {
                                    $res = app($this->formModelingRepository)->checkDataUnique($checkUnique);
                                    if (!$res) {
                                        return ['code' => ['0x016031', 'fields'], 'dynamic' => $fieldName . trans('fields.exist')];
                                    }
                                }
                            }
                        }

                    }
                    //验证必填
                    if (isset($validate->required) && $validate->required == 1) {
                        if (isset($field_options->disabled) && $field_options->disabled == 1) {
                            continue;
                        }
                        if (!empty($field_options) && !empty($field_options->parentField)) {
                            $parent = $field_options->parentField;
                            if (!empty($parent) && !empty($data['detailLayout'])) {
                                // $customFields = app($this->formModelingRepository)->custom_fields_table($tableKey, $parent);
                                if (array_key_exists($parent, $data['detailLayout']) && !empty($data['detailLayout'][$parent]) && is_array($data['detailLayout'][$parent])) {
                                    if ($v->field_directive == "area") {
                                        $relations = $field_options->relation;
                                        if (!empty($relations)) {
                                            foreach ($data['detailLayout'][$parent] as $key => $value) {
                                                foreach ($relations as $relation) {
                                                    if ((isset($value[$relation->field_code]) && $value[$relation->field_code]) || !array_key_exists($relation->field_code, $value)) {
                                                        break;
                                                    }
                                                    if (!isset($value[$relation->field_code])) {
                                                        return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                                                    }
                                                    $value[$relation->field_code] = trim($value[$relation->field_code]);
                                                    if ($value[$relation->field_code] === '' || $value[$relation->field_code] === []) {
                                                        return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        foreach ($data['detailLayout'][$parent] as $key => $value) {
                                            //如果没有key直接不验证
                                            if (!array_key_exists($k, $value)) {
                                                continue;
                                            }
                                            if (!isset($value[$k])) {
                                                return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                                            }
                                            $value[$k] = trim($value[$k]);
                                            if ($value[$k] === '' || $value[$k] === []) {
                                                return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                                            }
                                        }
                                    }

                                }
                            }
                        } else if ($v->field_directive == "area") {
                            $relations = $field_options->relation;
                            if (!empty($relations)) {
                                foreach ($relations as $relation) {
                                    if ((isset($data['main'][$relation->field_code]) && $data['main'][$relation->field_code]) || !array_key_exists($relation->field_code, $data['main'])) {
                                        break;
                                    }
                                    if (!isset($data['main'][$relation->field_code])) {
                                        return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                                    }
                                    if ($data['main'][$relation->field_code] === '' || $data['main'][$relation->field_code] === []) {
                                        return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                                    }
                                }
                            }
                        } else {
                            //如果没有key直接不验证
                            if (!array_key_exists($k, $data['main'])) {
                                continue;
                            }
                            if (!isset($data['main'][$k])) {
                                return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                            }
                            $data['main'][$k] = trim($data['main'][$k]);
                            if ($data['main'][$k] === '' || $data['main'][$k] === []) {
                                return ['code' => ['0x016021', 'fields'], 'dynamic' => $fieldName . trans('fields.required_field')];
                            }
                        }
                    }

                }
            }
        }
    }

    public function checkFieldsUnique($data)
    {
        if (isset($data['value']) && !emptyWithoutZero($data['value'])) {
            $value = $data['value'];
            $tableKey = $data['table_key'];
            $fieldCode = $data['field_code'];
            $field = app($this->formModelingRepository)->custom_fields_table($tableKey, $fieldCode);
            if (!empty($field)) {
                $field_options = json_decode($field->field_options);
                if (isset($field_options->validate)) {
                    $validate = $field_options->validate;
                    if (isset($validate->unique) && $validate->unique == 1) {
                        if (strstr($tableKey, "project_value") && $field->is_system == 1) {
                            $table = 'project_manager';
                            //编辑如果不变则不验证唯一
                            $id = isset($data['manager_id']) ? $data['manager_id'] : '';
                            $checkUnique = [
                                'table' => $table,
                                'field' => $fieldCode,
                                'value' => $value,
                                'primaryKey' => 'manager_id',
                                'primaryValue' => $id,
                                'is_system' => object_get($field, 'is_system', 0),
                            ];
                            return app($this->formModelingRepository)->checkDataUnique($checkUnique);
                        }
//                         //验证明细
                        //                         if (!empty($field_options) && !empty($field_options->parentField)) {
                        //                             $parent = $field_options->parentField;
                        //                             $subTable = app($this->formModelingRepository)->getCustomDataSubTableName($tableKey, $parent);
                        //                             return app($this->formModelingRepository)->checkDataUnique($subTable, $fieldCode, $value);
                        //                         } else {
                        //                             //非明细
                        //                             $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
                        //
                        //                             return app($this->formModelingRepository)->checkDataUnique($table, $fieldCode, $value);
                        //
                        //                         }

                    }

                }
            }

        }

        return 1;
    }

    public function editCustomData($data, $tableKey, $dataId, $diffCallback = null, $from = null)
    {
        $detail = app($this->formModelingRepository)->getCustomDataDetail($tableKey, $dataId);
        if (empty($detail)) {
            return ['code' => ['0x016035', 'fields']];
        }
        if (!$from) {
            //处理编辑时候权限
            $customPower = app($this->formModelingRepository)->getCustomMenu($tableKey);
            //查看权限
            $dataFrom = $data['data_from'] ?? 'modeling';
            $own = $dataFrom === 'flow' ? ($data['own'] ?? null) : null;
            unset($data['data_from'], $data['own']);
            if (isset($data['outsourceForEdit'])) {
                $own = $own ?: $this->own;
            }
            $editPermission = $this->parsePermission($customPower->edit_permission, $own);
            $viewPermission = $this->parsePermission($customPower->view_permission, $own);
            $relationEdit = $editPermission['relation'] ?? '';
            $edit = $this->getPermissionValue($editPermission, $detail->{$relationEdit} ?? '', $own);
            $relationView = $viewPermission['relation'] ?? '';
            $view = $this->getPermissionValue($viewPermission, $detail->{$relationView} ?? '', $own);

            if (!$edit || !$view) {
                return ['code' => ['0x016027', 'fields']];
            }
            $power = $this->getCustomConfig($tableKey, 'editBefore', $data, $dataId);
            if (isset($power['code'])) {
                return $power;
            }
        }
        $fieldsMap = $this->listRedisCustomFields($tableKey, [], true);
        $import = false;
        if (isset($data['outsourceForEdit'])) {
            $import = true;
            $data = $this->parseOutsourceData($data, $tableKey);
        }
        $parseData = $this->parseCustomFormData($tableKey, $fieldsMap, $data, false,$import);
        $authData = $parseData;
        $authData['main']['id'] = $dataId;
        $auth = $this->authDataRequired($fieldsMap, $authData, $tableKey);
        if (isset($auth['code'])) {
            return $auth;
        }
        $this->filterSaveData($parseData);
        app($this->formModelingRepository)->editCustomData($tableKey, $parseData['main'], $dataId);
        $filterConfig = $this->getCustomConfig($tableKey, 'editDataAfter', $authData['main']);
        if (isset($filterConfig['code'])) {
            return $filterConfig;
        }
        $subDataParam = [];
        $subDataParam['edit'] = true;
        $subDataParam['fieldsMap'] = $fieldsMap;
        if ($diffCallback && is_callable($diffCallback)) {
            $diffCallback($dataId, $parseData['detailLayout'], $tableKey, $subDataParam); //明细字段编辑
        } else {
            $this->saveCustomSubData($dataId, $parseData['detailLayout'], $tableKey, $subDataParam); //明细字段编辑
        }
        //保存明细合计值
        $this->saveDetailLayoutTotalFields($fieldsMap, $dataId);
        $this->addCustomAttachmentData($dataId, $parseData['upload'], $tableKey, 'update'); //附件关系新增

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($tableKey, $dataId);

        return true;
    }
    private function filterSaveData(&$data)
    {
        $data['main'] = $this->filterSubSaveData($data['main']);
        if (!empty($data['detailLayout'])) {
            foreach ($data['detailLayout'] as $k => $_data) {
                if (!empty($_data)) {
                    $_parseData = [];
                    foreach ($_data as $d) {
                         $_parseData[] = $this->filterSubSaveData($d);
                    }
                    $data['detailLayout'][$k] = $_parseData;
                }
            }
        }
    }
    private function filterSubSaveData(&$data)
    {
        $parseData = [];
        foreach ($data as $field => $value) {
            $pos = strpos($field, '_text_');
            if ($pos === false || $pos !== 0) {
                $parseData[$field] = $value;
            }
        }
        return $parseData;
    }
    /**
     * 解析自定义表单详情数据
     *
     * @param   string  $tableKey
     * @param   array  $data
     * @param   boolean  $add
     *
     * @return  array
     */
    private function parseCustomFormData($tableKey, $fieldsMap, $data, $add = true, $import = false)
    {
        $parseData = [
            'detailLayout' => [],
            'upload' => [],
            'main' => []
        ];
        
        $fields = [];
        foreach ($fieldsMap as $fieldCode => $field) {
            $fieldOption = json_decode($field->field_options, true);
            //明细字段
            if ($field->field_directive == 'detail-layout') {
                continue;
            } else if (isset($fieldOption['parentField']) && !empty($fieldOption['parentField'])) {
                $parent = $fieldOption['parentField'];
                $fields['detail'][$parent][$field->field_code] = $field;
            } else if ($field->field_directive == 'upload') {
                if (isset($data[$fieldCode])) {
                    $parseData['upload'][$field->field_code] = isset($data[$fieldCode]) ? $data[$fieldCode] : [];
                }
            } else {
                //普通字段
                $fields['noraml'][$field->field_code] = $field;
            }
        }
        if (isset($fields['noraml']) && !empty($fields['noraml'])) {
            $parseData['main'] = $this->parseCustomFormDataDetail($fields['noraml'], $data, $parseData['main'], $import);
        }
        if ($add) {
            $parseData['main']['created_at'] = date('Y-m-d H:i:s');
        }
        $parseData['main']['updated_at'] = date('Y-m-d H:i:s');
        if (isset($fields['detail']) && !empty($fields['detail'])) {
            foreach ($fields['detail'] as $parent => $value) {
                $res = [];
                if (isset($data[$parent])) {
                    $parseData['detailLayout'][$parent] = [];
                    if (!empty($data[$parent])) {
                        $res = [];
                        foreach ($data[$parent] as $v) {
                            $res[] = $this->parseCustomFormDataDetail($value, $v, [], $import);
                        }
                        $parseData['detailLayout'][$parent] = $res;
                    }
                }
            }
        }
        return $this->dropDetailLayoutLine($parseData);
    }

    public function parseCustomFormDataDetail($fieldsMap, $data, $res, $import)
    {
        foreach ($fieldsMap as $fieldCode => $field) {
            $directive = $this->transferDirective($field->field_directive);
            if (key_exists($fieldCode, $data)) {
                //excel导入 转化中文逗号为英文逗号
                $data[$fieldCode] = str_replace('，', ',', $data[$fieldCode]);

                if ($directive == "upload") {
                    $res[$fieldCode] = $data[$fieldCode];
                    // return $data[$fieldCode];
                } else if ($directive == 'area') {
                    $areaData = $data[$fieldCode];
                    if ($import) {
                        $fieldOption = json_decode($field->field_options, true);
                        $relation = $fieldOption['relation'];
                        $area = [];
                        if (is_string($data[$fieldCode])) {
                            $areaData = explode(",", $data[$fieldCode]);
                            foreach ($relation as $key => $item) {
                                $area[$item['field_code']] = isset($areaData[$key]) ? $areaData[$key] : '';
                            }
                        }else{
                            $area = $data[$fieldCode];
                        }
                        $areaData = $area;
                    }
                    if (!empty($areaData) && is_array($areaData)) {
                        foreach ($areaData as $key => $item) {
                            $res[$key] = $item;
                        }
                    }
                } else if ($directive == 'select' || $directive == 'selector') {
                    if (is_array($data[$fieldCode])) {
                        if (count($data[$fieldCode]) > 0) {
                            $res[$fieldCode] = implode(',', $data[$fieldCode]);
                        } else {
                            $res[$fieldCode] = '';
                        }
                    } else {
                        $res[$fieldCode] = trim($data[$fieldCode]);
                    }
                } else {
                    $res[$fieldCode] = $this->{$directive . 'Directive'}($data[$fieldCode], (object) $data, $field, false);
                    $res[$fieldCode] = !is_array($res[$fieldCode]) ? trim($res[$fieldCode]) : '';
                }
                if ($import && isset($data['detail_unique_id'])) {
                    $res['detail_data_id'] = $data['detail_unique_id'];
                }
                if (isset($data['_text_' . $fieldCode])) {
                    $res['_text_' . $fieldCode] = $data['_text_' . $fieldCode];
                }
            }
        }
        return $res;
    }

    /**
     * 解析值转成具体信息
     *
     * @param   array  $data
     * @param   string  $tableKey
     *
     * @return  array
     */
    public function parseOutsourceData($data, $tableKey)
    {
        if (isset($data['data']) && !empty($data['data'])) {
            foreach ($data['data'] as $k => $v) {
                if ($v === "") {
                    unset($data['data'][$k]);
                }
            }
        }
        $customFields = app($this->formModelingRepository)->getCustomFields($tableKey);
        if (count($customFields) > 0) {
            foreach ($customFields as $field) {
                $fieldOption = json_decode($field->field_options, true);
                if ($field->field_directive == "selector") {
                    $selectorConfig = $fieldOption['selectorConfig'];
                    $multiple = (isset($selectorConfig['multiple']) && $selectorConfig['multiple'] == 1) ? true : false;
                    if ($multiple) {
                        if (isset($data["$field->field_code"])) {
                            $value = $data["$field->field_code"];
                            if ($value === '') {
                                $data["$field->field_code"] = [];
                            } else {
                                if (is_string($value)) {
                                    $value = explode(",", $value);
                                    $data["$field->field_code"] = $value;
                                }
                            }
                        }
                    }
                }
                if ($field->field_directive == "area") {
                    if (isset($data["$field->field_code"]) && !empty($data["$field->field_code"])) {
                        $value = explode(",", $data["$field->field_code"]);
                        $relation = $fieldOption['relation'];
                        $arr = [];
                        foreach ($relation as $k => $v) {
                            $file_code = $v['field_code'];
                            $arr["$file_code"] = isset($value[$k]) ? $value[$k] : '';
                        }
                        $data["$field->field_code"] = $arr;
                    }

                }
                if ($field->field_directive == "checkbox") {
                    if (isset($fieldOption['singleMode']) && $fieldOption['singleMode'] == 1) {
                        continue;
                    } else {
                        if (isset($data["$field->field_code"])) {
                            $name = $data["$field->field_code"];
                            if (!$name && $name !== "0" && $name !== 0) {
                                continue;
                            }
                            if (!is_array($name) && strpos($name, '[') === false) {
                                $name = explode(",", $name);
                            }
                            $data["$field->field_code"] = $name;
                        }
                    }
                }
            }
        }
        return $data;
    }
    /**
     * 检查附件
     *
     * @param   array  $fieldsMap
     * @param   array  $parseData
     *
     * @return  array
     */
    public function checkUpload($fieldsMap, $parseData)
    {
        foreach ($fieldsMap as $k => $v) {
            if (isset($v->field_options)) {
                $field_options = json_decode($v->field_options);
                if (isset($field_options->uploadConfig)) {
                    $uploadConfig = $field_options->uploadConfig;
                    $multiple = (isset($uploadConfig->multiple) && $uploadConfig->multiple == 1) ? true : false;
                    $onlyImage = (isset($uploadConfig->onlyImage) && $uploadConfig->onlyImage == 1) ? true : false;
                    $upload = $parseData['upload'];
                    $attachmentData = [];
                    if (isset($upload[$k]) && !is_array($upload[$k])) {
                        $attachmentData = explode(",", $upload[$k]);
                    }
                    if (!$multiple) {
                        if (count($attachmentData) > 1) {
                            return ['code' => ['0x016022', 'fields']];
                        }
                    }
                    $uploadFileStatusTemp = config('eoffice.uploadFileStatus');
                    if ($onlyImage && !empty($attachmentData)) {
                        foreach ($attachmentData as $attachment) {
                            $att = app($this->attachmentService)->getOneAttachmentById($attachment);
                            if (!empty($att['type'])) {
                                if (!in_array($att['type'], $uploadFileStatusTemp[1])) {
                                    return ['code' => ['0x016023', 'fields']];
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * 明细字段保存
     *
     * @param   int  $dataId
     * @param   array  $detailData
     * @param   string  $tableKey
     * @param   boolean  $edit
     * @param   boolean  $outsource
     *
     * @return  boolean
     */
    public function saveCustomSubData($dataId, $detailData, $tableKey, $param)
    {
        if (empty($detailData)) {
            return true;
        }
        $edit = isset($param['edit']) ? $param['edit'] : false;
        $outsource = isset($param['outsource']) ? $param['outsource'] : false;
        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        $fieldsMap = isset($param['fieldsMap']) ? $param['fieldsMap'] : [];
        $upload = [];
        foreach ($fieldsMap as $code => $field) {
            $field_options = json_decode($field->field_options, true);
            if (isset($field_options['parentField']) && $field_options['parentField'] && $field->field_directive == 'upload') {
                $upload[] = $code;
            }
        }
        foreach ($detailData as $fieldCode => $data) {
            if ($edit) {
                app($this->formModelingRepository)->deleteCustomSubData($tableKey, $fieldCode, $dataId);
            }
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $info = $value;
                    if (!empty($value)) {
                        if ($outsource == 1) {
                            $value = $this->parseOutsourceData($value, $tableKey);
                        }

                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                if (empty($v) && $v !== "0" && $v !== 0) {
                                    unset($value[$k]);
                                }
                                if (is_array($v) && !empty($v)) {
                                    $value[$k] = json_encode($v);
                                }
                            }
                            $val = implode(",", $value);
                            if (strpos('$val', 'sub') === false) {
                                if (isset($value[$mainPrimaryKey])) {
                                    unset($value[$mainPrimaryKey]);
                                }
                                if (isset($value['detail_data_id'])) {
                                    unset($value['detail_data_id']);
                                }
                            }
                        }
                        if (!empty($value)) {
                            $value[$mainPrimaryKey] = $dataId;
                        }
                        if (!empty($upload)) {
                            foreach ($upload as $uploadField) {
                                if (isset($value[$uploadField])) {
                                    unset($value[$uploadField]);
                                }
                            }
                        }
                        $res = [];
                        $update = $edit ? 'update' : '';
                        $dataDetailId = app($this->formModelingRepository)->addCustomSubData($tableKey, $fieldCode, $value);
                        if (!empty($upload)) {
                            foreach ($upload as $uploadField) {
                                if (isset($info[$uploadField]) && !empty($info[$uploadField])) {
                                    $res[$uploadField] = $info[$uploadField];
                                    $this->addCustomAttachmentData($dataDetailId, $res, $tableKey, $update); //附件关系新增
                                }
                            }
                        }

                    }

                }
            }
        }
        return true;
    }
    /**
     * 详情数据附件保存
     *
     * @param   int  $dataId
     * @param   array  $attachmentData
     * @param   string  $tableKey
     * @param   boolean  $flag
     *
     * @return  boolean
     */
    private function addCustomAttachmentData($dataId, $attachmentData, $tableKey, $flag = 'add')
    {
        if (empty($attachmentData)) {
            return true;
        }
        $tableName = app($this->formModelingRepository)->getCustomDataTableName($tableKey);

        foreach ($attachmentData as $fieldCode => $attachmentIds) {
            $entityTableData = [
                'table_name' => $tableName,
                'fileds' => [
                    [
                        "field_name" => "entity_id",
                        "field_type" => "integer",
                        "field_length" => "11",
                        "field_common" => trans('fields.Relational_record'),
                    ], [
                        "field_name" => "entity_column",
                        "field_type" => "string",
                        "field_length" => "50",
                        "field_common" => trans('fields.management_table_corresponds'),
                    ],
                ],
            ];
            $conditons = [
                "entity_column" => [$fieldCode],
                'entity_id' => [$dataId],
                "wheres" => ["entity_id" => [$dataId], 'entity_column' => [$fieldCode]],
            ];
            try {
                app($this->attachmentService)->attachmentRelation($entityTableData, $conditons, $attachmentIds, $flag);
            } catch (\Throwable $th) {
                return ['code' => ['0x016038', 'fields']];
            }
        }

        return true;
    }
    /**
     * 删除自定义页面数据
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return boolean
     */
    public function deleteCustomData($tableKey, $dataId, $own = null, $from = null)
    {
        if (!$dataId || $dataId == 0) {
            return ['code' => ['0x016020', 'fields']];
        }
        
        $detail = app($this->formModelingRepository)->getCustomDataDetail($tableKey, $dataId);
        if (empty($detail)) {
            return ['code' => ['0x016035', 'fields']];
        }
        if ($from !== 'flow') {//流程外发删除不需要走权限
            //处理删除时候权限
            $customPower = app($this->formModelingRepository)->getCustomMenu($tableKey);
            $deletePermission = $this->parsePermission($customPower->delete_permission, $own);
            $viewPermission = $this->parsePermission($customPower->view_permission, $own);
            $relationView =  $viewPermission['relation'] ?? '';
            $view = $this->getPermissionValue($viewPermission, $detail->{$relationView} ?? '', $own);
            $relationDelete = $deletePermission['relation'] ?? '';
            $delete = $this->getPermissionValue($deletePermission, $detail->{$relationDelete} ?? '', $own);
            if (!$delete || !$view) {
                return ['code' => ['0x016028', 'fields']];
            }
        }
        $dataIdArray = explode(',', rtrim($dataId, ','));
        if (empty($dataIdArray)) {
            return true;
        }
        $power = $this->getCustomConfig($tableKey, 'deleteBefore', $dataIdArray);
        if (isset($power['code'])) {
            return $power;
        }

        $result = app($this->formModelingRepository)->deleteCustomData($tableKey, $dataIdArray);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($tableKey, $dataId);

        return $result;
    }

    /**
     * 获取自定义页面数据详情
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return array
     */
    public function getCustomDataDetail($tableKey, $dataId, $own = '')
    {
        if (!$dataId) {
            return [];
        }
        if (!app($this->formModelingRepository)->hasCustomDataTable($tableKey)) {
            return [];
        }
        if (!$dataId || $dataId == 0) {
            return ['code' => ['0x016020', 'fields']];
        }
        $power = $this->getCustomConfig($tableKey, 'detailBefore', $dataId);
        if (isset($power['code'])) {
            return $power;
        }
        //权限
        $customPower = app($this->formModelingRepository)->getCustomMenu($tableKey);
        //查看权限
        $viewPermission = $this->parsePermission($customPower->view_permission);
        $relationView = isset($viewPermission['relation']) ? $viewPermission['relation'] : '';
        //不在部门角色用户可查看选择器的用户
        if ($viewPermission && $viewPermission != 'all' && isset($viewPermission['user']) && !in_array(own('user_id'), $viewPermission['user'])) {
            if ($relationView) {
                $power['viewPermission'] = ['field' => $relationView, 'value' => $viewPermission['creator']];
            }
        }
        $detail = app($this->formModelingRepository)->getCustomDataDetail($tableKey, $dataId, $power);
        if (empty($detail)) {
            return [];
        }
        if ($viewPermission && $viewPermission != 'all' && isset($viewPermission['user']) && !in_array(own('user_id'), $viewPermission['user'])) {
            $creator = isset($detail->$relationView) ? explode(",", $detail->$relationView) : [];
            if (empty(array_intersect($creator, $viewPermission['creator']))) {
                return ['code' => ['0x016026', 'fields']];
            }
        }
        $fieldsMap = $this->listRedisCustomFields($tableKey, [], true, false, false);
        $fieldsArray = array_keys($fieldsMap);
        $fieldsMapArray = $this->getAllFieldsByGroup($fieldsMap);

        $data = [];
        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        $data[$mainPrimaryKey] = isset($detail->{$mainPrimaryKey}) ? $detail->{$mainPrimaryKey} : '';
        //解析值
        //普通值
        if (isset($fieldsMapArray['noramlFields']) && $fieldsMapArray['noramlFields']) {
            foreach ($fieldsMapArray['noramlFields'] as $fieldCode) {
                $data[$fieldCode] = $this->commonParseCustomData($fieldsMap[$fieldCode], $detail, $mainPrimaryKey, 'detail');
            }
        }
        //明细值
        if (isset($fieldsMapArray['layOut']) && $fieldsMapArray['layOut']) {
            foreach ($fieldsMapArray['layOut'] as $fieldCode => $layout) {
                $data[$fieldCode] = $this->commonParseCustomData($fieldsMap[$fieldCode], $detail, $mainPrimaryKey, 'detail');
                foreach ($data[$fieldCode] as $index => $layoutData) {
                    foreach ($layout as $value) {
                        $currentField = $fieldsMap[$value];
                        $layoutData = (object)array_merge($data,(array)$layoutData);
                        $data[$fieldCode][$index]->$value = $this->commonParseCustomData($currentField, $layoutData, 'detail_data_id', 'detail');
                    }
                }
            }
        }
        $detailAfter = $this->getCustomConfig($tableKey, 'detailAfter', $data, $dataId);
        if (!empty($detailAfter) && !isset($detailAfter['code'])) {
            $data = $detailAfter;
        }
        return $data;
    }
    public function getAllFieldsByGroup($fieldsMap)
    {
        $arr = [];
        foreach ($fieldsMap as $fieldCode => $field) {
            $field_options = json_decode($field->field_options, true);
            if (isset($field_options['parentField']) && $field_options['parentField']) {
                $arr['layOut'][$field_options['parentField']][] = $fieldCode;
            } else if ($field->field_directive == 'detail-layout') {
                continue;
            } else {
                $arr['noramlFields'][] = $fieldCode;
            }
        }
        return $arr;
    }

    //公共解析自定义字段数据
    public function commonParseCustomData($field, $detail, $mainPrimaryKey, $type, $returnMap = true)
    {
        $field_directive = $field->field_directive;
        $fieldCode = $field->field_code;
        $value = isset($detail->{$fieldCode}) ? $detail->{$fieldCode} : '';
        $primarValue = isset($detail->{$mainPrimaryKey}) ? $detail->{$mainPrimaryKey} : '';
        $field_directive = $this->transferDirective($field_directive);
        //附件类型
        if ($field_directive == 'upload' || $field_directive == 'detailLayout') {
            return $this->{$field_directive . 'Directive'}($primarValue, $detail, $field, $type);
        } else {
            if ($field_directive == 'selector') {
                return $this->{$field_directive . 'Directive'}($value, $detail, $field, $type, $returnMap);
            } else {
                return $this->{$field_directive . 'Directive'}($value, $detail, $field, $type);
            }
        }
    }

    /**
     * 导出
     *
     * @param   string  $tableKey
     * @param   array  $param
     * @param   array  $own
     * @param   string  $name
     * @param   callable  $callable
     *
     * @return  array
     */
    public function exportFields($tableKey, $param = [], $own, $name = null, callable $callable = null)
    {
        if (!$this->server_info) {
            $this->server_info = $param['server_info'];
        }
        if (!$this->current_user_id) {
            $this->current_user_id = $own['user_id'];
        }
        if (!$this->current_user_info) {
            $this->current_user_info = $own;
        }
        $param['filter'] = $this->getCustomConfig($tableKey, 'filter', $param, $own);
        // $fieldList = $this->listRedisCustomFields($tableKey,[],true);
        $headerArr = [];
        // foreach ($fieldList as $key => $field) {
        //     $fields = $this->transferFieldOptions($field, $tableKey, []);
        //     $fieldOption = json_decode($fields->field_options, true);
        //     $type = $fieldOption['type'];
        //     if ($type == 'tabs') {
        //         continue;
        //     }
        //     $headerArr[$field->field_code] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $field->field_code)];
        //     $fieldsMap[$field->field_code] = $field;
        //     $directive = $field->field_directive;
        // }
        //权限
        $customPower = app($this->formModelingRepository)->getCustomMenu($tableKey);
        //查看权限
        $viewPermission = $this->parsePermission($customPower->view_permission, $own);
        $relationView = isset($viewPermission['relation']) ? $viewPermission['relation'] : '';
        //不在部门角色用户可查看选择器的用户
        if ($viewPermission && $viewPermission != 'all' && isset($viewPermission['user']) && !in_array($own['user_id'], $viewPermission['user'])) {
            if ($relationView) {
                $param['viewPermission'] = ['field' => $relationView, 'value' => $viewPermission['creator']];
            }
        }
        $platform = (isset($param['platform']) && !empty($param['platform'])) ? $param['platform'] : 'pc';
        $listParam = [
            'terminal' => $platform,
            'type' => 1,
        ];
        $listLayout = app($this->formModelingRepository)->getBindTemplate($tableKey, $listParam);
        if (!empty($listLayout) && isset($listLayout->extra)) {
            $extra = json_decode($listLayout->extra, true);
            if (isset($extra['defaultOrder']) && !empty($extra['defaultOrder'])) {
                $param['defaultOrder'] = $extra['defaultOrder'];
            }
        }
        $list = app($this->formModelingRepository)->getCustomDataList($tableKey, $param);
        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        //解析数据列值
        // if (!empty($fieldsMap)) {
        //     $dataShow = [];
        //     $temp_arr = [];
        //     foreach ($fieldsMap as $key => $field) {
        //         foreach ($list as $itemKey => $item) {
        //             $originalData = clone $item; //复制原始数据，避免查找到已解析好的数据
        //             $value = isset($item->{$key}) ? $item->{$key} : '';
        //             $directive = $field->field_directive;
        //             $directive = $this->transferDirective($directive);
        //             if ($directive == "editor") {
        //                 $temp_arr[$itemKey][$key] = strip_tags($item->{$key});
        //                 if (strpos($temp_arr[$itemKey][$key], '&nbsp;') !== false) {
        //                     // $temp_arr[$key] = str_replace("&nbsp;", " ", "$temp_arr[$key]");
        //                     $temp_arr[$itemKey][$key] = str_replace("&nbsp;", " ", $temp_arr[$itemKey][$key]);
        //                 }
        //             } elseif ($directive == "dynamicInfo") {
        //                 $temp_arr[$itemKey][$key] = trans('fields.0x016039');
        //             }else {
        //                 if ($directive == "upload") {
        //                     $res = $this->{$directive . 'Directive'}($item->{$mainPrimaryKey}, $item, $field);
        //                     $temp_arr[$itemKey][$key]['data'] = !empty($res) ? implode(",", $res) : [];
        //                     $temp_arr[$itemKey][$key]['type'] = 'attachement';
        //                 } else {
        //                     $temp_arr[$itemKey][$key] = $this->{$directive . 'Directive'}($value, $item, $field);
        //                 }
        //             }
        //         }
        //         // $dataShow[] = $temp_arr;
        //     }
        // }
        $fieldsMap = $this->listRedisCustomFields($tableKey, [], true, false, false);
        $fieldsArray = array_keys($fieldsMap);
        $fieldsMapArray = $this->getAllFieldsByGroup($fieldsMap);
        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        $data = [];
        foreach ($list as $itemKey => $item) {
            $flag = true;
            $data[$itemKey][$mainPrimaryKey] = isset($item->{$mainPrimaryKey}) ? $item->{$mainPrimaryKey} : '';
            //解析值
            //普通值
            if (isset($fieldsMapArray['noramlFields']) && $fieldsMapArray['noramlFields']) {
                foreach ($fieldsMapArray['noramlFields'] as $fieldCode) {
                    $customFields = $this->transferFieldOptions($fieldsMap[$fieldCode], $tableKey,[]);
                    $directive = $customFields->field_directive;
                    $headerArr[$fieldCode] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $fieldCode)];
                    $data[$itemKey][$fieldCode] = $this->commonParseCustomData($customFields, $item, $mainPrimaryKey, 'list', false);
                    if($directive == 'editor'){
                        $data[$itemKey][$fieldCode] = strip_tags($data[$itemKey][$fieldCode]);
                        if (strpos($data[$itemKey][$fieldCode], '&nbsp;') !== false) {
                            $data[$itemKey][$fieldCode] = str_replace("&nbsp;", " ", $data[$itemKey][$fieldCode]);
                        }
                    } elseif ($directive == "dynamic-info") {
                            $data[$itemKey][$fieldCode] = trans('fields.0x016039');
                    } elseif ($directive == "upload") {
                        $res = [];
                        $res['data'] = !empty($data[$itemKey][$fieldCode]) ? implode(",", $data[$itemKey][$fieldCode]) : [];
                        $res['type'] = 'attachement';
                        $data[$itemKey][$fieldCode] = $res;
                    }
                }
            }
            //明细值
            if (isset($fieldsMapArray['layOut']) && $fieldsMapArray['layOut']) {
                foreach ($fieldsMapArray['layOut'] as $fieldCode => $layout) {
                    $customFields = $this->transferFieldOptions($fieldsMap[$fieldCode], $tableKey,[]);
                    $res = $this->commonParseCustomData($customFields, $item, $mainPrimaryKey, 'list', false);
                    $data[$itemKey][$fieldCode] = ['merge_data' => $res->toArray()];
                    $children = [];
                    foreach ($layout as $value) {
                        $currentField = $fieldsMap[$value];
                        $children[$currentField->field_code] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $currentField->field_code)];
                    }
                    foreach ($data[$itemKey][$fieldCode]['merge_data'] as $index => $layoutData) {
                        foreach ($layout as $value) {
                            $currentField = $this->transferFieldOptions($fieldsMap[$value], $tableKey,[]);
                            $directive = $currentField->field_directive;
                            $layoutData = (object)array_merge((array)$item,(array)$layoutData);
                            $data[$itemKey][$fieldCode]['merge_data'][$index]->$value = $this->commonParseCustomData($currentField, $layoutData, 'detail_data_id', 'list', false);
                            if($directive == 'editor'){
                                $data[$itemKey][$fieldCode]['merge_data'][$index]->$value = strip_tags($data[$itemKey][$fieldCode]['merge_data'][$index]->$value);
                                if (strpos($data[$itemKey][$fieldCode]['merge_data'][$index]->$value, '&nbsp;') !== false) {
                                    $data[$itemKey][$fieldCode]['merge_data'][$index]->$value = str_replace("&nbsp;", " ", $data[$itemKey][$fieldCode]['merge_data'][$index]->$value);
                                }
                            } elseif ($directive == "dynamic-info") {
                                $data[$itemKey][$fieldCode]['merge_data'][$index]->$value = trans('fields.0x016039');
                            } elseif ($directive == "upload") {
                                $res = [];
                                $res['data'] = !empty($data[$itemKey][$fieldCode]['merge_data'][$index]->$value) ? implode(",", $data[$itemKey][$fieldCode]['merge_data'][$index]->$value) : [];
                                $res['type'] = 'attachement';
                                $data[$itemKey][$fieldCode]['merge_data'][$index]->$value = $res;
                            }
                        }
                    }
                    $headerArr[$fieldCode] = [
                        'data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $fieldCode),
                        'type' => 'detailLayout',
                        'children' => $children
                    ];

                }
            }
            // 自定义处理，参数分别是过滤后新值与完整的旧值，回调函数第一个参数使用地址参数就能修改这里的数据
            if (is_callable($callable)) {
                $callable($data[$itemKey], $item);
            }
        }
        $sheetName = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $tableKey);
        $result = [
            'sheetName' => $name === null ? $sheetName : $name,
            'header' => $headerArr,
            'data' => $data,
        ];
        return $result;
    }

    /**
     * @param $builder
     * @param array $param
     * @param array $appointFields 排除不需要导出的字段，主要是为了附件字段不要导出
     * @param callable|null $handle 处理单条数据
     * @param callable|null $callback 处理所有数据与header
     * @return array
     */
    public function export($builder, $param = [], $appointFields = [], callable $handle = null, callable $callback = null)
    {
        $own = $param['user_info'] ?? [];
        if (!$this->server_info) {
            $this->server_info = $param['server_info'];
        }
        if (!$this->current_user_id) {
            $this->current_user_id = $own['user_id'];
        }
        if (!$this->current_user_info) {
            $this->current_user_info = $own;
        }
        $tableKey = $param['module_key'] ?? null;
        $param['filter'] = $this->getCustomConfig($tableKey, 'filter', $param, $own);
        $headerArr = [];
        //权限
        $customPower = app($this->formModelingRepository)->getCustomMenu($tableKey);
        //查看权限
        $viewPermission = $this->parsePermission($customPower->view_permission, $own);
        $relationView = isset($viewPermission['relation']) ? $viewPermission['relation'] : '';
        //不在部门角色用户可查看选择器的用户
        if ($viewPermission && $viewPermission != 'all' && isset($viewPermission['user']) && !in_array($own['user_id'], $viewPermission['user'])) {
            if ($relationView) {
                $param['viewPermission'] = ['field' => $relationView, 'value' => $viewPermission['creator']];
            }
        }
        $platform = (isset($param['platform']) && !empty($param['platform'])) ? $param['platform'] : 'pc';
        $listParam = [
            'terminal' => $platform,
            'type' => 1,
        ];
        $listLayout = app($this->formModelingRepository)->getBindTemplate($tableKey, $listParam);
        if (!empty($listLayout) && isset($listLayout->extra)) {
            $extra = json_decode($listLayout->extra, true);
            if (isset($extra['defaultOrder']) && !empty($extra['defaultOrder'])) {
                $param['defaultOrder'] = $extra['defaultOrder'];
            }
        }

        $list = app($this->formModelingRepository)->getCustomDataList($tableKey, $param);
        //$mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        $fieldsMap = $this->listRedisCustomFields($tableKey, [], true, false, false);
        $fieldsMap = $appointFields ? array_extract($fieldsMap, $appointFields) : $fieldsMap; // 如果指定了字段，就过滤掉其它
        //$fieldsArray = array_keys($fieldsMap);
        $fieldsMapArray = $this->getAllFieldsByGroup($fieldsMap);
        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        $data = [];
        $attachments = [];
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        foreach ($list as $itemKey => $item) {
            $flag = true;
            $data[$itemKey][$mainPrimaryKey] = isset($item->{$mainPrimaryKey}) ? $item->{$mainPrimaryKey} : '';
            //解析值
            //普通值

            if (isset($fieldsMapArray['noramlFields']) && $fieldsMapArray['noramlFields']) {
                foreach ($fieldsMapArray['noramlFields'] as $fieldKey => $fieldCode) {
                    $customFields = $this->transferFieldOptions($fieldsMap[$fieldCode], $tableKey,[]);
                    $directive = $customFields->field_directive;
                    $headerArr[$fieldCode] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $fieldCode)];
                    $data[$itemKey][$fieldCode] = $this->commonParseCustomData($customFields, $item, $mainPrimaryKey, 'list', false);
                    if($directive == 'editor'){
                        $data[$itemKey][$fieldCode] = strip_tags($data[$itemKey][$fieldCode]);
                        if (strpos($data[$itemKey][$fieldCode], '&nbsp;') !== false) {
                            $data[$itemKey][$fieldCode] = str_replace("&nbsp;", " ", $data[$itemKey][$fieldCode]);
                        }
                    } elseif ($directive == "dynamic-info") {
                        $data[$itemKey][$fieldCode] = trans('fields.0x016039');
                    } elseif ($directive == "upload") {
                        $attachmentIds = $data[$itemKey][$fieldCode] ?? [];
                        list($attachmentData, $oneAttachments) = $this->handleExportAttachment($attachmentService, $attachmentIds, $itemKey);
                        $attachments = array_merge($attachments, $oneAttachments);
                        $data[$itemKey][$fieldCode] = empty($attachmentData) ? ['data' => ''] : $attachmentData;
                    }
                }
            }
            if (isset($fieldsMapArray['layOut']) && $fieldsMapArray['layOut']) {
                foreach ($fieldsMapArray['layOut'] as $fieldCode => $layout) {
                    $customFields = $this->transferFieldOptions($fieldsMap[$fieldCode], $tableKey,[]);
                    $res = $this->commonParseCustomData($customFields, $item, $mainPrimaryKey, 'list', false);
                    $data[$itemKey][$fieldCode] = $res->toArray();
                    $children = [];
                    foreach ($layout as $value) {
                        $currentField = $fieldsMap[$value];
                        $children[$currentField->field_code] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $currentField->field_code)];
                    }
                    foreach ($data[$itemKey][$fieldCode] as $index => $layoutData) {
                        $data[$itemKey][$fieldCode][$index] = (array)$layoutData;
                        $layoutData = (object)array_merge((array)$item,(array)$layoutData);
                        foreach ($layout as $value) {
                            $currentField = $this->transferFieldOptions($fieldsMap[$value], $tableKey,[]);
                            $directive = $currentField->field_directive;
                            $data[$itemKey][$fieldCode][$index][$value] = $this->commonParseCustomData($currentField, $layoutData, 'detail_data_id', 'list', false);
                            if($directive == 'editor'){
                                $data[$itemKey][$fieldCode][$index][$value] = strip_tags($data[$itemKey][$fieldCode][$index][$value]);
                                if (strpos($data[$itemKey][$fieldCode][$index][$value], '&nbsp;') !== false) {
                                    $data[$itemKey][$fieldCode][$index][$value] = str_replace("&nbsp;", " ", $data[$itemKey][$fieldCode][$index][$value]);
                                }
                            } elseif ($directive == "dynamic-info") {
                                $data[$itemKey][$fieldCode][$index][$value] = trans('fields.0x016039');
                            } elseif ($directive == "upload") {
                                $detailAttachmentIds = $data[$itemKey][$fieldCode][$index][$value] ?? [];
                                list($detailAttachmentData, $detailOneAttachments) = $this->handleExportAttachment($attachmentService, $detailAttachmentIds, $itemKey . '/' . $index);
                                $attachments = array_merge($attachments, $detailOneAttachments);
                                $data[$itemKey][$fieldCode][$index][$value] = empty($detailAttachmentData) ? ['data' => ''] : $detailAttachmentData;
                            }
                        }
                    }

                    $headerArr[$fieldCode] = [
                        'data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $fieldCode),
                        'type' => 'detailLayout',
                        'children' => $children
                    ];

                }
            }
            // 自定义处理，参数分别是过滤后新值与完整的旧值，回调函数第一个参数使用地址参数就能修改这里的数据
            if (is_callable($handle)) {
                $handle($data[$itemKey], $item);
            }
        }
        // 整个数据都给模块处理
        $sheetName = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $tableKey);
        if (is_callable($callback)) {
            $callback($data, $headerArr, $sheetName, $attachments);
        }
        if ($data) {
            foreach ($data as $key => $value) {
                foreach ($value as $k => $val) {
                    if (is_array($val) && !isset($val['url'])) {
                        foreach ($val as $ks => $v) {
                            if (!empty($v) && is_array($v)) {
                                foreach ($v as $item => $itemValue) {
                                    if (isset($val['url'])) {
                                        $data[$key][$k]['data'][$ks][$item] = $itemValue;
                                    } else {
                                        if (isset($itemValue['url']) || is_array($itemValue)) {
                                            $data[$key][$k]['data'][$ks][$item] = $itemValue;
                                        } else {
                                            $data[$key][$k]['data'][$ks][$item] = ['data' => $itemValue];
                                        }
                                    }
                                }
                            } else {
                                $data[$key][$k][$ks] = $v;
                            }
                        }
                    } else if (is_array($val) && isset($val['url'])) {
                        $data[$key][$k] = $val;
                    } else {
                        if (isset($data[$key][$k]['url'])) {
                            $data[$key][$k] = $val;
                        } else {
                            $data[$key][$k] = ['data' => $val];
                        }
                    }
                }
            }
        }

        // 生产excel文件
        $response = $builder->setTitle($sheetName)->setHeader($headerArr)->setData($data)->generate();
        // 有附件导出zip
        if ($attachments && count($attachments) > 0) {
            $attachments[] = [$response[0] . '.' . $builder->getSuffix() => $response[1]];

            return Export::saveAsZip($attachments, $sheetName);
        }

        return $response;
    }

    public function handleExportAttachment($attachmentService, $attachmentIds, $categoryKey = 0)
    {
        $result = [];
        $exportAttachments = [];
        if (!empty($attachmentIds)) {
            if (count($attachmentIds) > 1) {
                $attachmentPath = './attachment/' . $categoryKey . '/';
                $attachments = $attachmentService->getMoreAttachmentById($attachmentIds);
                $attachmentName = '';
                foreach ($attachments as $attachment) {
                    $exportAttachments[] = [$attachmentPath . $attachment['attachment_id'] . '/' . $attachment['attachment_name'] => $attachment['temp_src_file']];
                    $attachmentName .= ',' . $attachment['attachment_name'] . "\r\n";
                }
                $result['url'] = $attachmentPath;
                $result['data'] = ltrim($attachmentName, ',');
            } else {
                $attachment = $attachmentService->getOneAttachmentById($attachmentIds[0]);
                if ($attachment) {
                    $attachmentPath = './attachment/' . $attachment['attachment_id'] . '/' . $attachment['attachment_name'];
                    $exportAttachments[] = [$attachmentPath => $attachment['temp_src_file']];
                    $result['data'] = $attachment['attachment_name'];
                    $result['url'] = $attachmentPath;
                }
            }
        }
        return [$result, $exportAttachments];
    }
    /**
     * 获取导入模板字段
     *
     * @param   string  $tableKey
     * @param   array  $param
     * @param   string  $name
     *
     * @return  array
     */
    public function getImportFields($tableKey, $param = [], $name = "")
    {
        if (!$this->current_user_info) {
            $this->current_user_info = isset($param['user_info']) ? $param['user_info'] : own('');
        }
        $newFieldList = [];
        if (isset($param['custom_detail'])) {
            $name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $tableKey) . trans('fields.Detailed_layout') . trans('fields.import_template');
            $parent = $param['custom_detail'];
            $fieldList = $this->listRedisCustomFields($tableKey, [], true);
            foreach ($fieldList as $code => $field) {
                $fieldOption = json_decode($field->field_options, true);
                if (isset($fieldOption['parentField']) && $fieldOption['parentField'] == $parent) {
                    $newFieldList[$code] = $field;
                }
            }
            $fieldList = $newFieldList;
        } else {
            $fieldList = $this->listRedisCustomFields($tableKey,[],true);
        }
        $excelLists = $headerArr = [];
        $importDisabledFields = config('customfields.importDisabledFields.' . $tableKey);
        $area = 0;
        foreach ($fieldList as $key => $field) {
            if (!empty($importDisabledFields)) {
                if (in_array($field->field_code, $importDisabledFields)) {
                    continue;
                }
            }
            $field = $this->transferFieldOptions($field, $tableKey, []);
            $dataLists = [];
            $fieldOption = json_decode($field->field_options, true);
            $type = $fieldOption['type'];
            if ($type == 'detail-layout' || $type == 'tabs') {
                continue;
            }
            $headerText = $this->getHeaderName($field);
            if(isset($fieldOption['parentField']) && !isset($param['custom_detail'])){
                $children[$fieldOption['parentField']][$field->field_code] = ['data' => $headerText];
                $headerArr[$fieldOption['parentField']] = [
                    'data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $fieldOption['parentField']),
                    'type' => 'detailLayout',
                    'children' => $children[$fieldOption['parentField']]
                ];
            }else{
                $headerArr[$field->field_code] = ['data' => $headerText, 'style' => ['width' => '15']];
            }
            $directive = $field->field_directive;
            if (!isset($param['show_header'])) {
                if ($directive == "radio" || $directive == "checkbox") {
                    $dataLists['header'] = [
                        'id' => 'ID',
                        'title' => trans("fields.name"),
                    ];
                    $dataLists['sheetName'] = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $field->field_code);
                    $dataLists['data'] = $this->{$directive . "DirectiveExcel"}($field);
                    $excelLists[] = $dataLists;
                }
                if ($directive == 'selector') {
                    $header = [];
                    $header = $this->{$directive . "DirectiveExcel"}($field, 1);
                    if (empty($header)) {
                        $header = ['id', 'title'];
                    }
                    $dataLists['header'] = [
                        $header[0] => 'ID',
                        $header[1] => trans("fields.name"),
                    ];
                    $dataLists['sheetName'] = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $field->field_code);
                    $dataLists['data'] = $this->{$directive . "DirectiveExcel"}($field);
                    $excelLists[] = $dataLists;
                }
                if ($directive == 'area') {
                    if ($area == 0) {
                        $config = [
                            'province' => ['province', 'province_id', 'province_name'],
                            'city' => ['city', 'city_id', 'city_name'],
                            'district' => ['district', 'district_id', 'district_name'],
                        ];
                        foreach ($config as $key => $item) {
                            $dataLists['header'] = [
                                $item[1] => 'ID',
                                $item[2] => trans("fields.name"),
                            ];
                            $dataLists['sheetName'] = trans("fields.{$key}");
                            $dataLists['data'] = $this->{$directive . "DirectiveExcel"}($item);
                            array_push($excelLists, $dataLists);
                        }
                        $area++;
                    }
                }
                if ($directive == 'select') {
                    if (isset($fieldOption['selectConfig']['sourceType']) && $fieldOption['selectConfig']['sourceType'] == "file") {
                        continue;
                    }
                    $header = [];
                    $header = $this->selectDirectiveExcel($field, 1);

                    if (empty($header)) {
                        $header = ['id', 'title'];
                    }

                    $dataLists['header'][$header[0]] = 'ID';
                    if (isset($header[1])) {
                        $dataLists['header'][$header[1]] = trans("fields.name");
                    }
                    $dataLists['sheetName'] = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $field->field_code);
                    $dataLists['data'] = $this->{$directive . "DirectiveExcel"}($field);
                    $excelLists[] = $dataLists;
                }
            }
        }
        if (empty($name)) {
            // $name = trans('fields.Custom_field_import_template');
            $name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $tableKey) . trans('fields.import_template');
        }
        $result = [
            '0' => [
                'sheetName' => $name,
                'header' => $headerArr,
            ],

        ];
        if (!empty($excelLists)) {
            foreach ($excelLists as $key => $arr) {
                $result[] = $arr;
            }
        }
        return $result;
    }
    public function getHeaderName($field)
    {
        $tableKey = $field->field_table_key;
        $fieldOption = json_decode($field->field_options, true);
        $headerText = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $field->field_code);
        if ($field->field_directive == "checkbox") {
            if (isset($fieldOption['validate']) && isset($fieldOption['validate']['required']) && $fieldOption['validate']['required'] == "1") {
                if (isset($fieldOption['singleMode']) && $fieldOption['singleMode'] == "1") {
                    $headerText = $headerText . trans('fields.required');
                } else {
                    $headerText = $headerText . trans('fields.Need_to_fill');
                }
            } else {
                if (!isset($fieldOption['singleMode'])) {
                    $headerText = $headerText . trans('fields.Use_commas');
                }
            }
        } elseif ($field->field_directive == "selector") {
            if (isset($fieldOption['validate']) && isset($fieldOption['validate']['required']) && $fieldOption['validate']['required'] == "1") {
                if (isset($fieldOption['selectorConfig']) && isset($fieldOption['selectorConfig']['multiple']) && $fieldOption['selectorConfig']['multiple'] == "1") {
                    $headerText = $headerText . trans('fields.Need_to_fill');
                } else {
                    $headerText = $headerText . trans('fields.required');
                }
            } else {
                if (isset($fieldOption['selectorConfig']) && isset($fieldOption['selectorConfig']['multiple']) && $fieldOption['selectorConfig']['multiple'] == "1") {
                    $headerText = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $field->field_code) . trans('fields.Use_commas');
                }
            }
        } else {
            if (isset($fieldOption['validate'])) {
                $validate = $fieldOption['validate'];
                if (isset($validate['required']) && $validate['required'] == "1") {
                    $headerText = $headerText . trans('fields.required');
                }
            }
        }
        return $headerText;
    }
    public function getImportResultTitle($tableKey)
    {
        return mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $tableKey) . trans('import.import_report');
    }
    /**
     * 导入数据
     *
     * @param   string  $tableKey
     * @param   array  $datas
     * @param   array  $params
     *
     * @return  array
     */
    public function importCustomData($tableKey, $datas, $params)
    {
        // h获取当前用户ID
        $currentUserId = isset($params['user_info']) && $params['user_info'] ? $params['user_info']['user_id'] : '';
        $this->own = $params['user_info'] ?? [];
        if (!$this->current_user_info) {
            $this->current_user_info = $this->own;
        }
        $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
        $params['type'] = isset($params['type']) ? $params['type'] : 1;
        if ($params['type'] == 3) {
            //新增数据并清除原有数据
            app($this->formModelingRepository)->deleteCustomDataByParams($table, $params);
        }
        $primary = $this->getDataTablePrimaryKeyName($tableKey);
        $fieldsMap = $this->listRedisCustomFields($tableKey,[],true);
        if (isset($params['custom_detail'])) {
            return $datas;
        }
        foreach ($fieldsMap as $field) {
            Redis::hdel('custom_import', $tableKey . "_" . $field->field_code);
        }
        $subDataParam = [];
        $subDataParam['fieldsMap'] = $fieldsMap;
        foreach ($datas as $key => $data) {
            $data['outsource'] = true;
            if (!isset($data['importReason'])) {
                $data['creator'] = $currentUserId;
                $parseData = $this->parseCustomFormData($tableKey, $fieldsMap, $data, true, true);
                if (isset($data['importResult'])) {
                    $datas[$key]['importResult'] = $data['importResult'];
                }  
                if (isset($data['data_id'])) {
                    $parseData['main']['data_id'] = $data['data_id'];
                }
                if (Schema::hasColumn($table, 'creator')) {
                    $parseData['main']['creator'] = $params['user_info']['user_id']; //创建人
                }
                if ($params['type'] == 1 || $params['type'] == 3) {
                    $data['current_user_id'] = $currentUserId;
                    $dataId = $this->addCustomData($data, $tableKey);
                    //print_r($dataId);exit;
                    if (!$dataId || isset($dataId['code'])) {
                        $data['success'] = false;
                        $datas[$key]['importResult'] = importDataFail();
                        $datas[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                    } else {
                        $data['data_id'] = $dataId;
                        $data['success'] = true;
                    }

                    $this->checkInsertResult($datas[$key]['importResult'],$datas[$key]['importReason'],$dataId);
                } else if ($params['type'] == 2) {
                    $primaryKey = isset($params['primaryKey']) ? $params['primaryKey'] : '';
                    $main = $parseData['main'];
                    $primaryData = isset($main[$primaryKey]) ? $main[$primaryKey] : '';
                    if (!empty($primaryKey) && $primaryData !== '') {
                        $res = DB::table($table)->where($primaryKey, $primaryData)->first();
                        if ($res) {
                            $data['outsourceForEdit'] = 1;
                            $data['current_user_id'] = $currentUserId;
                            $update = $this->editCustomData($data, $tableKey,$res->$primary);
                            if (!$update || isset($update['code'])) {
                                $data['success'] = false;
                                $datas[$key]['importResult'] = importDataFail();
                                $datas[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                            } else {
                                $data['data_id'] = $res->$primary;
                                $data['success'] = true;
                            }
                            $this->checkInsertResult($datas[$key]['importResult'],$datas[$key]['importReason'],$update);
                        } else {
                            $data['success'] = false;
                            $datas[$key]['importResult'] = importDataFail();
                            $datas[$key]['importReason'] = importDataFail(trans("import.update_data_not_found"));
                        }
                    } else {
                        $data['success'] = false;
                        $datas[$key]['importResult'] = importDataFail();
                        $datas[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                    }
                } else if ($params['type'] == 4) {
                    $data['outsourceForEdit'] = 1;
                    $data['current_user_id'] = $currentUserId;
                    $primaryKey = isset($params['primaryKey']) ? $params['primaryKey'] : '';
                    $primaryData = isset($data[$primaryKey]) ? $data[$primaryKey] : '';
                    $query = DB::table($table)->where($primaryKey, $primaryData);
                    if (!empty($params['search'])) {
                        $query = app($this->formModelingRepository)->wheres($query, $params['search']);
                    }
                    $oldData = $query->first();
                    if (!empty($oldData)) {
                        $dataId = $this->editCustomData($data, $tableKey,$oldData->$primary);
                    } else {
                        $dataId = $this->addCustomData($data, $tableKey);
                    }
                    if (!$dataId || isset($dataId['code'])) {
                        $data['success'] = false;
                        $datas[$key]['importResult'] = importDataFail();
                        $datas[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                    } else {
                        $data['data_id'] = $dataId;
                        $data['success'] = true;
                    }
                    $this->checkInsertResult($datas[$key]['importResult'],$datas[$key]['importReason'],$dataId);

                }
                $this->importAfterCallback($params, $data);
            }
        }

        return ['data' => $datas];

    }

    public function checkInsertResult(&$importResult , &$importReason, $insertResult){
        if(isset($insertResult['code'])){
            $importResult = importDataFail();
            try{
                $importReason = importDataFail(trans($insertResult['code'][1] . "." . $insertResult['code'][0]));
            }catch (\Exception $e){
                $importReason = importDataFail("import fail");
            }
        }
    }
    private function importAfterCallback($param, $data)
    {
        $after = $param['after'] ?? null;
        if($after && is_array($after)) {
            list($service, $method) = $after;
            $data['data'] = $data;
            $data['id'] = $data['data_id'] ?? null;
            $data['param'] = $param;
            return app($service)->{$method}($data);
        }
    }
    public function importDataFilterBack($tableKey, $data, $param = [])
    {
        foreach ($data as $k => $v) {
            $result = $this->importDataFilter($tableKey, $v, $param);
            if (!empty($result)) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail($result);
                continue;
            } else {
                $data[$k]['importResult'] = importDataSuccess();
            }
        }
        return $data;
    }
    /**
     * 导入数据过滤
     *
     * @param   string
     * @param   array
     * @param   array
     *
     * @return  array|string
     */
    public function importDataFilter($tableKey, $data, $params)
    {
        $this->own = $params['user_info'] ?? [];
        if (!$this->current_user_info) {
            $this->current_user_info = $this->own;
        }
        $fieldsMap = $this->listRedisCustomFields($tableKey, [], true);
        if (isset($params['custom_detail'])) {
            return true;
        }
        $parseData = $this->parseCustomFormData($tableKey, $fieldsMap, $data, 'true',true);
        if($params['type'] == 2 || $params['type'] == 4){
            $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
            $primary = $this->getDataTablePrimaryKeyName($tableKey);
            $primaryKey = isset($params['primaryKey']) ? $params['primaryKey'] : '';
            $primaryData = isset($parseData['main'][$primaryKey]) ? $parseData['main'][$primaryKey] : '';
            $res = DB::table($table)->where($primaryKey, $primaryData)->first();
            if(isset($res->$primary)){
                $parseData['main']['id'] = $res->$primary;
            }
        }
        $auth = $this->authDataRequired($fieldsMap, $parseData, $tableKey);
        if (isset($auth['code'])) {
            return isset($auth['dynamic'])? $auth['dynamic']:$auth['code'];
        }
        // 20201216-dp-不要在这里return！
        // 调查到一个关于导入报告的问题就是因为这里return $data导致的
    }
    public function dropDetailLayoutLine($parseData)
    {
        if(isset($parseData['detailLayout']) && !empty($parseData['detailLayout'])){
            foreach ($parseData['detailLayout'] as $field => $fieldInfo) {
                if(is_array($fieldInfo)){
                    $data = $this->deleteEndEmptyRows($fieldInfo);
                    $parseData['detailLayout'][$field] = $data;
                }
            }
        }
        return $parseData;
    }

    public function deleteEndEmptyRows($data)
    {
        $indexes = array_reverse(array_keys($data));
        foreach ($indexes as $index) {
            $i = 0;
            foreach ($data[$index] as $value) {
                if (is_array($value) && !empty($value)) {
                    ++$i;
                }
                if (!is_array($value) && (trim($value) !== '' && trim($value) !== null)) {
                    ++$i;
                }
            }
            if ($i == 0) {
                unset($data[$index]);
            }
        }

        return $data;
    }
    /**
     * 导入检查日期类型
     *
     * @param   string  $directive
     * @param   string  $date
     *
     * @return  boolean
     */
    public function checkDate($directive, $date)
    {
        $formatString = '';
        switch ($directive) {
            case "datetime":
                $formatString = 'Y-m-d H:i:s';
                break;
            case "date":
                $formatString = 'Y-m-d';
                break;
            case "time":
                $formatString = 'H:i:s';
                break;
        }
        try {
            $transferDate = (new \DateTime($date))->format($formatString);
            return $transferDate == $date;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * 获取定时提醒模块
     *
     * @return  array
     */
    public function getCustomRemindModules()
    {
        $menuParent = [];
        $module = app($this->formModelingRepository)->getParent();
        foreach ($module as $k => $v) {
            $menuInfo = app($this->menuRepository)->getDetail($v->menu_parent);
            if (!empty($menuInfo)) {
                $menuParent[$k]['title'] = trans_dynamic("menu.menu_name.menu_" . $menuInfo->menu_id);
                $menuParent[$k]['module'] = $menuInfo->menu_name_zm;
                $menuParent[$k]['menu_parent'] = $v->menu_parent;
                switch ($menuInfo->menu_id) {
                    case "44":
                        $menuParent[$k]['menu_type'] = "customer";
                        break;
                    case "264":
                        $menuParent[$k]['menu_type'] = "office_supplies";
                        break;
                    case "415":
                        $menuParent[$k]['menu_type'] = "personnel_files";
                        break;
                    case "160":
                        $menuParent[$k]['menu_type'] = "project";
                        break;
                    case "920":
                        $menuParent[$k]['menu_type'] = "assets";
                        break;
                    case "600":
                        $menuParent[$k]['menu_type'] = "car";
                        break;
                    case "150":
                        $menuParent[$k]['menu_type'] = "contract";
                        break;
                    case "233":
                        $menuParent[$k]['menu_type'] = "book";
                        break;
                    default:
                        $menuParent[$k]['menu_type'] = $menuInfo->menu_id;
                        break;
                }
            }
        }
        $menuParentResult = [];
        foreach ($menuParent as $key => $value) {
            $getMenu = app($this->formModelingRepository)->getRemindMenuSet($value['menu_parent']);
            if (!empty($getMenu)) {
                foreach ($getMenu as $k => $v) {
                    $v->menu_name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
                    if (isset($v->remind_set) && !empty($v->remind_set)) {
                        foreach ($v->remind_set as $keys => $val) {
                            if (isset($val->field_table_key)) {
                                $val->field_name = mulit_trans_dynamic("custom_fields_table.field_name." . $val->field_table_key . "_" . $val->field_code);
                            }
                        }

                    }
                }
                $menuParent[$key]['category_sub'] = $getMenu;
            }
        }
        return $menuParent;
    }

    /**
     * 获取自定义菜单列表(流程用到)
     *
     * @param   array  $param
     *
     * @return  array
     */
    public function getCustomMenuList($param)
    {
        $parent = app($this->formModelingRepository)->getParent($param, 0);
        $data = [];
        foreach ($parent as $k => $v) {
            $menu_name = trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
            $data[] = ['id' => $v->menu_code, 'title' => $menu_name, 'module_type' => 'system_custom', 'isSystemCustom' => true, 'menu_parent' => $v->menu_parent];
        }
        return $data;
    }
    /**
     * 按模块获取自定义字段（流程用到）
     *
     * @param   array
     *
     * @return  array
     */
    public function getSystemCustomFields($data)
    {
        $result = [];
        $field_table_key = isset($data['key']) ? $data['key'] : '';
        $data_handle_type = $data['data_handle_type'] ?? 0;
        if (!empty($field_table_key)) {
            $fields = $this->listRedisCustomFields($field_table_key, [], true);
            if (!empty($fields)) {
                // 调用处理函数，格式化返回参数
                $result = $this->handleFieldsParams($field_table_key, $fields, true, $data_handle_type);
            }
        }
        return $result;
    }
    /**
     * 获取字段名称
     *
     * @param   string  $field_table_key
     * @param   string  $field_code
     *
     * @return  string
     */
    public function getFiledName($field_table_key, $field_code)
    {
        $customfields = app($this->formModelingRepository)->custom_fields_table($field_table_key, $field_code);
        if (!empty($customfields)) {
            return mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $customfields->field_code);
        }
        return '';
    }
    /**
     * 返回父级模块名字
     *
     * @param   string  $menu
     *
     * @return  string
     */
    public function getRootFiledName($menu)
    {
        $customfields = app($this->formModelingRepository)->getCustomMenu($menu);
        if (!empty($customfields)) {
            return mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $customfields->menu_code);
        }
        return '';
    }
    /**
     * 获取明细控件父级
     *
     * @param   array  $data
     *
     * @return  array
     */
    public function getCustomParentField($data)
    {
        $result = '';
        $field_table_key = isset($data['key']) ? $data['key'] : '';
        $field_code = isset($data['field_code']) ? $data['field_code'] : '';
        if (!empty($field_table_key) && !empty($field_code)) {
            $fields = app($this->formModelingRepository)->custom_fields_table($field_table_key, $field_code);
            if (!empty($fields)) {
                $field_options = json_decode($fields->field_options);
                if (isset($field_options->parentField) && !empty($field_options->parentField)) {
                    $result = $field_options->parentField;
                }
            }
        }
        return $result;

    }
    /**
     * 解析自定义字段值
     *
     * @param   array  $params
     *
     * @return  array
     */
    public function parseCustomData($params)
    {
        $own = own('');

        if (!isset($params['module']) || !isset($params['field'])) {
            return '';
        }

        $params = $this->parseParams($params);
        $tableKey = $params['module'];
        $field_code = $params['field'];
        // $menu       = $this->tableKeyTransferMenu($tableKey);
        // if (isset($own['menus']['menu']) && !in_array($menu, $own['menus']['menu'])) {
        //     return ['code' => ['0x016024', 'fields']];
        // }
        //获取字段信息
        $field = app($this->formModelingRepository)->custom_fields_table($tableKey, $field_code);
        if (empty($field)) {
            return ['code' => ['0x016024', 'fields']];
        }
        $field = $this->transferFieldOptions($field, $tableKey, []);

        if (!isset($data)) {
            $primaryKey = $this->getDataTablePrimaryKeyName($tableKey);
            if (isset($params['search_field'])) {
                $primaryKey = $params['search_field'];
            }
            // 项目的团队字段要单独获取
            if (self::isProjectCustomKey($tableKey) && $field_code == 'team_person') {
                $managerId = Arr::get($params, 'search.primary_key.0', 0);
                $users = $managerId ? ProjectService::getProjectTeamPerson($managerId) : [];
                $userNames = implode(',', Arr::pluck($users, 'user_id'));
                $data = [(object)['team_person' => $userNames]];
            } else {
                $data = app($this->formModelingRepository)->parseCutomData($tableKey, $primaryKey, $params);
            }
        }

        //特殊字段数据处理
        $filterConfig = config('customfields.parseFormData.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                $power = app($services)->$method($data, $params);
                if ($power || $power === '' || $power === []) {
                    return $power;
                }
            }
        }
        $res = [];
        if (!empty($field) && !empty($data)) {
            foreach ($data as $value) {
                $result = '';
                $directive = $field->field_directive;
                $info = isset($value->$field_code) ? $value->$field_code : '';
                switch ($directive) {
                    //文本类型不解析
                    case 'number':
                        if (isset($value->$field_code)) {
                            $result = $value->$field_code;
                        }
                        break;
                    case 'upload':
                        $result = $this->{$directive . 'Directive'}($value->$primaryKey, $value, $field);
                        return $result;
                        break;
                    case 'selector':
                        $result = $this->{$directive . 'Directive'}($info, $value, $field, true, false);
                        break;
                    default:
                        // if (isset($value->$field_code)) {

                        $result = $this->{$directive . 'Directive'}($info, $value, $field);
                        // }
                        break;
                }
                if ($result !== '') {
                    $res[] = $result;
                }

            }

        }
        if (empty($res)) {
            return '';
        }
        $response = '';
        foreach ($res as $item) {
            if (is_array($item)) {
                $response .= json_encode($item) . ',';
            } else {
                $response .= $item . ',';
            }
        }
        return rtrim($response, ',');
    }
    /**
     * @获取自定义字段列表
     * @param type $param
     * @return array | 自定义字段列表
     */
    public function listFields($param = [], $tableKey)
    {
        return $this->listRedisCustomFields($tableKey, [], true);
    }

    /**
     * 获取自定义字段定时提醒内容
     *
     * @return  array
     */
    public function getReminds()
    {
        if (!Schema::hasTable('custom_reminds') || !Schema::hasTable('custom_template')) {
            return false;
        }
        $reminds = $this->getRedisReminds();
        $sendData = [];
        foreach ($reminds as $remind) {
            $param = [];
            $remind = json_decode($remind);
            if (empty($remind->option)) {
                continue;
            }
            $extra = json_decode($remind->extra);
            $field_code = $remind->field_code;
            $type = $remind->type;
            $option = json_decode($remind->option);
            $reminds_select = isset($remind->reminds_select) ? $remind->reminds_select : '';
            $sendMethod = [];
            if (!empty($reminds_select)) {
                $reminds_select = explode(",", $reminds_select);
                $sendMethod = app($this->formModelingRepository)->tranferRemindName($reminds_select);
            }
            $date = date("Y-m-d", time());
            $tableKey = $remind->field_table_key;
            $custom_menu = app($this->formModelingRepository)->getCustomMenu($tableKey);
            $sms_menu = $this->transferMenu($custom_menu);
            $primaryKey = $this->getDataTablePrimaryKeyName($tableKey);
            $field_directive = $remind->field_directive;
            //明细获取数据
            if (isset($extra->parentField) && $extra->parentField) {
                $parentField = $extra->parentField;
                $subTable = app($this->formModelingRepository)->getCustomDataSubTableName($tableKey, $parentField);
                $param['detailLayout'] = $subTable;
            }
            if ($tableKey == 'personnel_files') {
                $param['search']['status'] = [2, '!='];
            }
            $lists = app($this->formModelingRepository)->getCustomDataList($tableKey, $param);
            if (strpos($tableKey, "project_value") !== false) {
                $project_value = explode("_", $tableKey);
                $project_type = isset($project_value[2]) ? $project_value[2] : '';
                $search['search']['manager_type'] = [$project_type];
                $lists = app($this->formModelingRepository)->getUniqueCustomData($tableKey, $search);
                $primaryKey = "manager_id";
            } elseif ($tableKey == 'archives_appraisal') {
                $primaryKey = "appraisal_data_id";
            }
            if (!isset($extra->timer)) {
                continue;
            }
            $timer = $extra->timer;
            $target = isset($timer->target) ? $timer->target : '';
            if ($target != "relation") {
                $toUser = $this->transferUser($timer, $remind->field_table_key, $lists);
            }
            $contentParam = ['field_code' => $field_code, 'tableKey' => $tableKey, 'content' => $remind->content, 'directive' => $field_directive];
            $remind_date = [];
            foreach ($lists as $list) {
                if (isset($list->$field_code)) {
                    $content = $this->transferContent($contentParam, $list);

                    if ($target == "relation") {
                        $toUser = $this->transferUser($timer, $remind->field_table_key, $list);
                    }

                    $data = $list->$field_code;
                    $params = ['field_table_key' => $tableKey, 'id' => $list->$primaryKey, 'field_code' => $field_code];
                    if (!empty($data)) {
                        if ($type == "previous") {
                            if ($field_directive == "time") {
                                $origin_time = $date . " " . $data;
                            } else if ($field_directive == "date") {
                                $origin_time = $data . " " . "00:00:00";
                            } else {
                                $origin_time = $data;
                            }
                            foreach ($option as $k => $time) {
                                $hour = isset($time->hour) ? $time->hour : 0;
                                $minute = isset($time->minute) ? $time->minute : 0;
                                $day = isset($time->day) ? $time->day : 0;
                                $remind_time = strtotime($origin_time . "-" . $day . "days -" . $hour . "hours -" . $minute . "minutes");
                                $remind_date[] = ['date' => date("Y-m-d H:i", $remind_time), 'params' => $params, 'content' => $content, 'user' => $toUser];
                            }
                        } elseif ($type == "delay") {
                            if ($field_directive == "time") {
                                $origin_time = $date . " " . $data;
                            } else if ($field_directive == "date") {
                                $origin_time = $data . " " . "00:00:00";
                            } else {
                                $origin_time = $data;
                            }
                            foreach ($option as $k => $time) {
                                $hour = isset($time->hour) ? $time->hour : 0;
                                $minute = isset($time->minute) ? $time->minute : 0;
                                $day = isset($time->day) ? $time->day : 0;
                                $remind_time = strtotime($origin_time . "+" . $day . "days +" . $hour . "hours +" . $minute . "minutes");
                                $remind_date[] = ['date' => date("Y-m-d H:i", $remind_time), 'params' => $params, 'content' => $content, 'user' => $toUser];
                            }
                        } else {
                            if (!empty($option)) {
                                foreach ($option as $k => $time) {
                                    $option_type = $time->type;
                                    if ($option_type == "day") {
                                        $remind_time = ['function_name' => 'dailyAt', 'param' => $data];
                                        $remind_date[] = ['date' => $remind_time, 'params' => $params, 'content' => $content, 'user' => $toUser];
                                    } else if ($option_type == "week") {
                                        $week = $time->week;
                                        $actual_week = $this->transferTime($week);
                                        $remind_time = ['function_name' => 'weekly', 'param' => $actual_week . "," . $data, 'user' => $toUser];

                                        $remind_date[] = ['date' => $remind_time, 'params' => $params, 'content' => $content, 'user' => $toUser];
                                    } else if ($option_type == "month") {
                                        $day = $time->day;
                                        $remind_time = ['function_name' => 'monthlyOn', 'param' => $day . "," . $data];
                                        $remind_date[] = ['date' => $remind_time, 'params' => $params, 'content' => $content, 'user' => $toUser];
                                    } else if ($option_type == "year") {
                                        $times = explode(':', $data);
                                        $hour = $times[0];
                                        $minute = $times[1];
                                        $month = $time->month;
                                        $day = $time->day;
                                        $remind_time = ['function_name' => 'cron', 'param' => $minute . "," . $hour . "," . $day . "," . $month . "," . "*"];
                                        $remind_date[] = ['date' => $remind_time, 'params' => $params, 'content' => $content, 'user' => $toUser];
                                    }
                                }
                            }

                        }
                    }
                }
            }
            if (!empty($remind_date)) {
                $sendData[] = [
                    'remind_date' => $remind_date,
                    'sendMethod' => $sendMethod,
                    'type' => $type,
                    'sms_menu' => $sms_menu,
                ];
            }
        }
        return $sendData;
    }
    /**
     * 转化名字（定时提醒）
     *
     * @param   string  $extra
     * @param   string  $tableKey
     * @param   object  $value
     *
     * @return  string
     */
    public function transferUser($extra, $tableKey, $value)
    {
        $user = [];
        $target = isset($extra->target) ? $extra->target : '';
        if ($target == "all") {
            $res = app($this->userService)->getAllUserIdString();
            $user = explode(",", $res);
            return $user;
            //全部成员
        } else if ($target == "currentMenu") {
            //当前菜单
            $custom_menu_config = app($this->formModelingRepository)->getCustomMenu($tableKey);
            if ($custom_menu_config->is_dynamic == 1) {
                $menu_id = config('customfields.menu.' . $tableKey);
            } else {
                $menu_id = $tableKey;
                if ($custom_menu_config->menu_parent == "160") {
                    $menu_id = 161;
                }
            }
            if (is_array($menu_id)) {
                $user = [];
                foreach ($menu_id as $value) {
                    $userTemp = app($this->userMenuService)->getMenuRoleUserbyMenuId($value);
                    $user = array_merge($userTemp, $user);
                }
                $user = array_unique($user);
            } else {
                $user = app($this->userMenuService)->getMenuRoleUserbyMenuId($menu_id);
            }
            return $user;
        } else if ($target == "relation") {
            //来源字段值
            $relationField = $extra->relationField;
            $user = [];
            if (isset($value->$relationField)) {
                $user_id = $value->$relationField;

                if (strpos($user_id, '[') !== false) {
                    $user_id = json_decode($user_id, true);
                } else {
                    $user_id = explode(",", $user_id);
                }
                if (is_array($user_id)) {
                    foreach ($user_id as $v) {
                        array_push($user, $v);
                    }
                } else {
                    array_push($user, $value->$relationField);
                }
            }
            $user = array_unique($user);
            return $user;

        } else if ($target == "definite") {
            //指定对象
            $definite = $extra->definite;
            $dept_user = [];
            $roles_user = [];
            $user = [];
            if (isset($definite->depts) && !empty($definite->depts)) {
                $dept = $definite->depts;
                $param['search'] = ["dept_id" => [$dept, "in"]];
                $res = app($this->userService)->getAllUserIdString($param);
                $dept_user = explode(",", $res);
            }

            if (isset($definite->roles) && !empty($definite->roles)) {
                $roles = $definite->roles;
                $param['search'] = ["role_id" => [$roles, "in"]];
                $res = app($this->userService)->getAllUserIdString($param);
                $roles_user = explode(",", $res);
            }

            if (isset($definite->users) && !empty($definite->users)) {
                $user = $definite->users;
            }
            $users = array_merge($dept_user, $roles_user, $user);
            $users = array_unique($users);
            return $users;
        } else {
            return $user;
        }

    }
    /**
     * 转化内容（定时提醒）
     *
     * @param   array  $contentParam
     * @param   array  $list
     *
     * @return  array
     */
    public function transferContent($contentParam, $list)
    {
        $content = $contentParam['content'];
        $tableKey = $contentParam['tableKey'];
        $field_code = $contentParam['field_code'];
        $patten = '/\#(\w+)\#/';
        preg_match_all($patten, $content, $variableName);
        $field_options_lang = [];
        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            foreach ($variableName[1] as $k => $v) {
                $customFields = app($this->formModelingRepository)->custom_fields_table($tableKey, $v);
                if (empty($customFields)) {
                    continue;
                }
                $customFields = $this->transferFieldOptions($customFields, $tableKey, true);
                $directive = $customFields->field_directive;
                $value = isset($list->{$v}) ? $list->{$v} : '';
                switch ($directive) {
                    case "upload":
                        break;
                    case "selector":
                        $result = $this->{$directive . 'Directive'}($value, $list, $customFields, true, false);
                        break;
                    default:
                        $result = $this->{$directive . 'Directive'}($value, $list, $customFields);
                }
                $content = str_replace($variableName[0][$k], $result, $content);
            }
        }
        return $content;
    }
    /**
     * tablekey转化菜单id
     *
     * @param   string  $custom_menu
     *
     * @return  string
     */
    public function transferMenu($custom_menu)
    {
        $menu_parent = isset($custom_menu->menu_parent) ? $custom_menu->menu_parent : '';
        if (!empty($menu_parent)) {
            switch ($menu_parent) {
                case "44":
                    $sms_menu = "customer";
                    break;
                case "264":
                    $sms_menu = "office_supplies";
                    break;
                case "415":
                    $sms_menu = "personnel_files";
                    break;
                case "160":
                    $sms_menu = "project";
                    break;
                case "920":
                    $sms_menu = "assets";
                    break;
                case "600":
                    $sms_menu = "car";
                    break;
                case "150":
                    $sms_menu = "contract";
                    break;
                case "233":
                    $sms_menu = "book";
                    break;
                default:
                    $sms_menu = $menu_parent;
            }
            return $sms_menu;
        }
    }
    /**
     * 数字转化日期
     *
     * @param   int  $week
     *
     * @return string
     */
    public function transferTime($week)
    {
        switch ($week) {
            case 1:
                return "mondays";
                break;
            case 2:
                return "tuesdays";
                break;
            case 3:
                return "wednesdays";
                break;
            case 4:
                return "thursdays";
                break;
            case 5:
                return "fridays";
                break;
            case 6:
                return "saturdays";
                break;
            case 7:
                return "sundays";
                break;
        }
    }
    /**
     * 翻译内容(系统提醒用到)
     *
     * @param   string  $tableKey
     * @param   array  $content
     *
     * @return  array
     */
    public function restoreContent($tableKey, $content)
    {
        $patten = '/\#(\w+)\#/';
        preg_match_all($patten, $content, $variableName);
        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            foreach ($variableName[1] as $k => $v) {
                $field_name = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $v);
                $field_name = "{" . $field_name . "}";
                $content = str_replace($variableName[0][$k], $field_name, $content);
            }
        }
        return $content;
    }

    public function savePermission($data, $tableKey)
    {
        if (isset($data['key']) && isset($data['info'])) {
            $key = $data['key'] . '_' . 'permission';
            $range = json_encode($data['info']);
            $update = [$key => $range];

            $userIp  = $this->getClientIp();
            $message = '修改用户: '. own()['user_name']. '操作时间: '. date('Y-m-d H:i:s').'操作模块: '. $tableKey.'操作ip:'.$userIp .'操作数据: '.json_encode($update);
            // 记录日志
            Log::Info($message);
            return app($this->formModelingRepository)->saveOtherSetting($tableKey, $update);
        }
    }
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            //nginx 代理模式下，获取客户端真实IP
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            //客户端的ip
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }

        return $ip;
    }
    public function getPermissionValue($permission, $relUserId = '', $own = null)
    {
        if ($permission == 'all') {
            return 1;
        } else {
            $currentUserId = $own ? ($own['user_id'] ?? '') : own('user_id');
            $relUserId = $relUserId ? explode(",", $relUserId) : [];
            if (!empty($permission)) {
                if (in_array($currentUserId, $permission['user']) || !empty(array_intersect($relUserId, $permission['creator']))) {
                    return 1;
                }
            }

            return 0;
        }
    }

    public function parsePermission($data, $own = '')
    {
        if (empty($data)) {
            return [];
        }
        if (!$own) {
            $own = own();
        }
        $own = $own ?: $this->own;
        $data = json_decode($data);
        if (isset($data->all) && $data->all == 1) {
            return 'all';
        }
        //创建人本人
        $creator = [];
        //创建人直属上级
        $superior = [];
        //创建人全部上级
        $ancestor = [];
        //创建人同部门
        $sameDept = [];
        //创建人同角色
        $sameRole = [];
        //创建人直属部门负责人
        $belongsDept = [];
        //创建人全部上级部门负责人
        $belongsSuperior = [];
        //用户
        $user = [];
        //部门
        $dept = [];
        //角色
        $role = [];

        /** @var UserRepository $userRepository */
        $userRepository = app('App\EofficeApp\User\Repositories\UserRepository');

        if (isset($data->myself) && $data->myself == 1) {
            //创建人本人
            $creator = [$own['user_id']];
        }
        if (isset($data->superior) && $data->superior == 1) {
            //创建人直属上级
            $superior = app($this->userService)->getSubordinateArrayByUserId($own['user_id'], ['include_leave' => true]);
            $superior = isset($superior['id']) ? $superior['id'] : [];
        }
        if (isset($data->ancestor) && $data->ancestor == 1) {
            //创建人全部上级
            $ancestor = app($this->userService)->getSubordinateArrayByUserId($own['user_id'], ['all_subordinate' => 1, 'include_leave' => true]);
            $ancestor = isset($ancestor['id']) ? $ancestor['id'] : [];
        }
        if (isset($data->same_dept) && $data->same_dept == 1) {
            //创建人同部门
//            $param = [];
//            $param['search']['dept_id'] = [$own['dept_id'], 'in'];
//            $response = app($this->userService)->userSystemList($param, $own);
//            $sameDept = array_column($response['list'], 'user_id');
            $deptId = is_array($own['dept_id']) ? $own['dept_id'] : [$own['dept_id']];
            $sameDept = $userRepository->getUserIdsByDeptIds($deptId);
        }
        if (isset($data->same_role) && $data->same_role == 1) {
            //创建人同角色
//            $param = [];
//            $param['search']['role_id'] = [$own['role_id'], 'in'];
//            $response = app($this->userService)->userSystemList($param, $own);
//            $sameRole = array_column($response['list'], 'user_id');
            $sameRole = $userRepository->getUserIdsByRoleIds($own['role_id']);
        }
        if (isset($data->belongs_dept_director) && $data->belongs_dept_director == 1) {
            //创建人直属部门负责人
            $director = app($this->departmentDirectorRepository)->getManageDeptByUser($own['user_id'])->pluck('dept_id')->toArray();
            if (!empty($director)) {
                $belongsDept = app($this->userRepository)->getUserByAllDepartment($director)->pluck('user_id')->toArray();
            }
        }
        if (isset($data->belongs_superior_dept_director) && $data->belongs_superior_dept_director == 1) {
            //创建人全部上级部门负责人
            $belongsSuperior = [];
            $director = app($this->departmentDirectorRepository)->getManageDeptByUser($own['user_id'])->pluck('dept_id')->toArray();
            foreach ($director as $key => $value) {
                $allChildren = app($this->departmentService)->allChildren($value);
                $deptIds = explode(',', $allChildren);
                $tempUser = app($this->userRepository)->getUserByAllDepartment($deptIds)->pluck('user_id')->toArray();
                $belongsSuperior = array_values(array_unique(array_merge($belongsSuperior, $tempUser)));
            }
        }
        if (isset($data->user) && !empty($data->user)) {
            //用户
            $user = $data->user;
        }
        if (isset($data->dept) && !empty($data->dept)) {
            //部门
            $deptIds = $data->dept;
//            $param = [];
//            $param['search']['dept_id'] = [$deptIds, 'in'];
//            $response = app($this->userService)->userSystemList($param, $own);
//            $dept = array_column($response['list'], 'user_id');
            $dept = $userRepository->getUserIdsByDeptIds($deptIds);
        }
        if (isset($data->role) && !empty($data->role)) {
            //角色
            $roleIds = $data->role;
//            $param = [];
//            $param['search']['role_id'] = [$roleIds, 'in'];
//            $response = app($this->userService)->userSystemList($param, $own);
//            $role = array_column($response['list'], 'user_id');
            $role = $userRepository->getUserIdsByRoleIds($roleIds);
        }
        return ['creator' => array_unique(array_merge($creator, $superior, $ancestor, $sameDept, $sameRole, $belongsDept, $belongsSuperior)), 'relation' => isset($data->relation_field) ? $data->relation_field : '', 'user' => array_unique(array_merge($user, $dept, $role))];
    }
    public function checkPermission($tableKey, $method = "menu")
    {
        $menuId = $this->tableKeyTransferMenu($tableKey, $method);
        $menus = app($this->userMenuService)->getUserMenus(own('user_id'));
        if (!is_array($menuId)) {
            $menuId = [$menuId];
        }
        if (isset($menus['menu']) && !array_intersect($menuId, $menus['menu'])) {
            return ['code' => ['0x009002', 'menu']];
        }
        return true;
    }
    public function tableKeyTransferMenu($tableKey, $method = "menu")
    {
        if ($res = config('customfields.' . $method . "." . $tableKey)) {
            $menu_id = config('customfields.' . $method . "." . $tableKey);
        } else {
            if (strstr($tableKey, "project")) {
                $menu_id = "161";
            } else {
                $menu_id = $tableKey;
            }
        }
        return $menu_id;
    }

    public function copyTemplate($id)
    {
        $template = app($this->formModelingRepository)->getTemplate($id);
        $copy = [
            'content' => $template->content,
            'extra' => $template->extra,
            'terminal' => $template->terminal,
            'name' => $template->name . " - " . trans('fields.copy'),
            'template_type' => $template->template_type,
            'from' => $template->from,
            'table_key' => $template->table_key,
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $copyId = app($this->formModelingRepository)->saveTemplate($copy);
        //多语言复制
        $effectLocales = $this->getEffectLocales();
        if (!empty($effectLocales)) {
            foreach ($effectLocales as $effectLocale) {
                $table = app($this->langService)->getLangTable($effectLocale);
                $langData = app($this->formModelingRepository)->getTemplateLanguage($id, $table);
                if ($langData) {
                    foreach ($langData as $key => $value) {
                        $langOption = [
                            'table' => 'custom_template',
                            'column' => $copyId,
                            'lang_key' => $value->lang_key,
                            'lang_value' => $value->lang_value,
                        ];
                        app($this->langService)->addDynamicLang($langOption, $effectLocale);
                    }
                }
            }
        }
        return $copyId;
    }
    private function getEffectLocales()
    {
        $packages = app($this->langService)->getLangPackages(['page' => 0, 'search' => ['effect' => [1]]]);
        $locales = [];
        if ($packages['total'] > 0) {
            foreach ($packages['list'] as $item) {
                $locales[] = $item->lang_code;
            }
        }
        return $locales;
    }

    /**
     * @desc   处理用户/自定义模块各参数,统一格式后返回
     *
     * @param  string $field_table_key 模块标题
     * @param  array  $fields          模块参数数组
     * @param  int    $formModel       是否是表单建模，如果是=1，在返回的时候需要拼接表单模板设置中的已应用模板标签名
     * @param  int    $data_handle_type 是否是流程外发配置页面获取系统自定义字段，且是外发更新模式，需要过滤部分不允许更新的字段
     *
     * @return array  $result
     */
    public function handleFieldsParams($field_table_key, $fields, $formModel = null, $data_handle_type = 0)
    {
        $result = [];
        if (!empty($fields)) {
            // 20200327,zyx,外发更新模式获取字段需要过滤不允许更新的字段
            $field_config = [];
            if ($data_handle_type) {
                $config = config('flowoutsend.custom_module');
                $field_config = $config[$field_table_key]['no_update_fields'] ?? [];
            }
            //先处理普通字段
            foreach ($fields as $k => $v) {
                // 20200327,zyx,如果是获取模块更新或删除字段，且当前字段属于限制更新的字段则跳过
                if ($data_handle_type && $field_config && in_array($v->field_code, $field_config)) {
                    continue;
                }
                $field_options = json_decode($v->field_options);
                $type = $field_options->type;
                //不是明细字段且没有父字段，即普通字段
                if ($type != "detail-layout" && !isset($field_options->parentField)) {
                    $field_name = mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code);

                    //当前字段是否为必填
                    $validate = $field_options->validate ?? 'empty';
                    $is_required = ($validate == 'empty') ? 0 : ($validate->required ?? 0);

                    $result[] = ['id' => $v->field_code, 'title' => $field_name, 'type' => $type, 'parent_id' => '', 'is_required' => $is_required, 'is_required_for_show' => $is_required, 'checked_num' => 0];
                    // 删除当前单元
                    unset($fields[$k]);
                }
            }

            // 新建模式展示创建人，更新删除不显示
            if (!$data_handle_type) {
                $customMenu = app($this->formModelingRepository)->getCustomMenu($field_table_key);
                if ($customMenu->is_dynamic == 2) {
                    $creator =  [
                        'id' =>  'creator',
                        'title' =>   trans("fields.creator"),
                        'type' =>  'selector',
                        'is_required' =>  0,
                        'checked_num' =>  0,
                        'is_required_for_show' => 0
                    ];
                    array_push($result,$creator);
                }
            }
            // 明细字段单独处理
            foreach ($fields as $k => $v) {
                $field_options = json_decode($v->field_options);
                $type = $field_options->type;

                //是明细字段且没有父字段，即是明细字段的标题字段，标题字段不是必填
                if ($type == "detail-layout" && !isset($field_options->parentField)) {
                    $fileName = mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code);
                    $detailLayout = ['id' => $v->field_code, 'title' => $fileName . "(" . trans("fields.Detailed_layout") . ")", 'type' => $type, 'parent_id' => '', 'is_required' => 0, 'is_required_for_show' => 0, 'checked_num' => 0];
                    array_push($result, $detailLayout);

                    foreach ($fields as $key => $value) {
                        $field_options = json_decode($value->field_options);
                        $child_type = $field_options->type;

                        //当前明细字段标题内的子字段（具体明细字段）
                        if ($child_type != "detail-layout" && isset($field_options->parentField) && $field_options->parentField == $v->field_code) {
                            $fileName = mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $value->field_code);

                            //当前字段是否为必填
                            $validate = $field_options->validate ?? 'empty';
                            $is_required = ($validate == 'empty') ? 0 : ($validate->required ?? 0);

                            $child = ['id' => $value->field_code, 'title' => $fileName, 'type' => $child_type, 'parent_id' => $v->field_code, 'is_required' => $is_required, 'is_required_for_show' => $is_required, 'checked_num' => 0];
                            array_push($result, $child);
                        }
                    }
                }
            }
        }

        // 20191122,zyx,如果是表单建模，则取自定义字段-新建页面布局的样式
        if ($formModel) {
            $result = $this->handleCustomFieldsTitle($field_table_key, $result);
        }

        return $result;
    }

    /**
     * @desc  处理自定义表单字段返回值，拼接标签名称
     * @param $field_table_key
     * @param $field_params
     *
     * @return array $field_params
     *
     * @author zyx
     * @since 2019/11/25
     */
    public function handleCustomFieldsTitle($field_table_key, $field_params)
    {
        // 定义表单时获取自定义字段返回值为对象，需转为数组
        if (count($field_params)) {
            foreach ($field_params as $fieldParamKey => $fieldParamVal) {
                $field_params[$fieldParamKey] = is_object($fieldParamVal) ? $fieldParamVal->toArray() : $fieldParamVal;
            }
        }

        // 获取pc端新建表单使用的模板ID
        $res = DB::table('custom_layout_setting')->select('bind_template')->where(['table_key' => $field_table_key])->where(['bind_type' => 2])->where(['terminal' => 'pc'])->first();

        // 如果没有定义模板则直接返回原字段列表
        if (!$res) {
            return $field_params;
        }

        // 获取指定模板样式信息
        $template = $this->getTemplate($res->bind_template);
        $content = json_decode(is_object($template) ? $template->content : $template['content'], true); // 标签ID
        $extra = json_decode(is_object($template) ? $template->extra : $template['extra'], true); // 标签名称
        $tabsArr = [];
        // 遍历content，组成标签数组
        foreach ($content as $contentVal) {
            // 找出标签单元
            if (is_array($contentVal) && isset($contentVal['field_options']) && ($contentVal['field_options']['type'] == 'tabs')) {
                foreach ($contentVal['tabs'] as $tabKey => $tabVal) {
                    if (isset($tabVal['fields'])) { // 标签中有字段
                        $fieldTmpArr = [];
                        foreach ($tabVal['fields'] as $fieldKey => $fieldVal) {
                            if (is_array($fieldVal)) { // 是明细字段，把所有明细子字段都放在和父字段同级的位置，方便后面调用
                                $fieldTmpArr[] = $fieldVal['key']; // 明细字段父字段
                                foreach ($fieldVal['fields'] as $detailVal) {
                                    $fieldTmpArr[] = $detailVal['key']; // 明细字段子字段
                                }
                            } else {
                                $fieldTmpArr[] = $fieldVal; // 非明细字段
                            }
                        }
                        $tabVal['fields'] = $fieldTmpArr;
                    }
                    $tabVal['field_code'] = $contentVal['field_code'];
                    $id = $tabVal['id'];
                    $tabVal['name'] = $extra[$contentVal['field_code']]['tabs'][$id];
                    $tabsArr[] = $tabVal;
                }
            }
        }

        // 有返回字段且标签数组不为空，则需要拼接标签名称
        if ($field_params && $tabsArr) {
            // 遍历字段，如果有父标签，则把标签名称拼接后返回
            foreach ($field_params as $resKey => $resVal) {
                foreach ($tabsArr as $tabKey => $tabVal) {
                    if (isset($tabVal['fields'])) {
                        if (isset($resVal['id']) && in_array($resVal['id'], $tabVal['fields'])) { // 外发设置获取自定义表单字段使用
                            $field_params[$resKey]['title'] = "[" . $tabVal['name'] . "] " . $resVal['title'];
                            continue;
                        } else if (isset($resVal['receiveFileds']) && in_array($resVal['receiveFileds'], $tabVal['fields'])) { // 外发设置获取字段对应关系使用
                            $field_params[$resKey]['receiveFiledsTitle'] = "[" . $tabVal['name'] . "] " . $resVal['receiveFiledsTitle'];
                            continue;
                        } else if (isset($resVal['field_code']) && in_array($resVal['field_code'], $tabVal['fields'])) { // 定义表单时获取字段信息
                            $field_params[$resKey]['field_name'] = "[" . $tabVal['name'] . "] " . $resVal['field_name'];
                            continue;
                        }
                    }
                }
            }
        }

        return $field_params;
    }

    // 用户项目管理模块调用，解析项目导出的数据
    public function parseCustomListData($tableKey, $lists, $fields = null, $editPermission = false, $deletePermission = false)
    {
        if (is_null($fields)) {
            $fields = $this->listRedisCustomFields($tableKey, [], false, true, false);
            if (empty($fields)) {
                return [];
            }
        }
        $newList = $lists;
        foreach ($lists as $itemKey => $item) {
            foreach ($fields as $key => $field) {
                $value = isset($item->$key) ? $item->$key : '';
                $field = $this->transferFieldOptions($field, $tableKey, []);
                $option = json_decode($field->field_options, true);
                $directive = $field->field_directive;
                if ($directive == "select" || $directive == "selector" || $directive == "radio" || $directive == "checkbox") {
                    $newList[$itemKey]->{'raw_' . $key} = $value;
                }
                $newList[$itemKey]->delete = 1;
                $newList[$itemKey]->edit = 1;
                if (key_exists('creator', $item)) {
                    $newList[$itemKey]->delete = $this->getPermissionValue($deletePermission, $item->creator);
                    $newList[$itemKey]->edit = $this->getPermissionValue($editPermission, $item->creator);
                }
                $newList[$itemKey]->{$key} = $this->rsGetFieldValue($tableKey, $field, $item, $value);
                if (isset($option['parentField'])) {
                    $detailLayoutTotal = '_total_' . $field->field_code;
                    $value = isset($item->$detailLayoutTotal) ? $item->$detailLayoutTotal : '';
                    $newList[$itemKey]->$detailLayoutTotal = $this->rsGetFieldValue($tableKey, $field, $item, $value);
                }
            }
            unset($lists[$itemKey]);
        }
        return $newList;
    }

    // 过滤掉不导出的字段
    private function filterExportFields($fields)
    {
        foreach ($fields as $key => $value) {
            $fieldOption = json_decode($value->field_options, true);
            $type = $fieldOption['type'];
            // 支持图片附件导出了，因此放开限制
            if ( /*$type == 'upload' || */$type == 'detail-layout' || $type == 'tabs' || isset($fieldOption['parentField'])) {
                unset($fields[$key]);
            }
        }
        return $fields;
    }


    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param  string $tableKey
     * @param  int|string $dataId
     */
    public function updateGlobalSearchDataByQueue($tableKey, $dataId)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchMessageByTable($tableKey, $dataId);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    public static function isProjectCustomKey($tableKey)
    {
        return strpos($tableKey, 'project_value') !== false;
    }

    public static function isProjectTaskCustomKey($tableKey)
    {
        return strpos($tableKey, 'project_task_value') !== false;
    }

    // 项目系列的表
    public static function isProjectSeriesCustomKey($tableKey)
    {
        return self::isProjectCustomKey($tableKey) || self::isProjectTaskCustomKey($tableKey);
    }

    public static function exceptProjectTaskCustomInfo(&$menuList)
    {
        foreach ($menuList as $key => $menu) {
            if (Arr::get($menu, 'menu_id') == 160) {
                $children = Arr::get($menu, 'children', []);
                foreach ($children as $childKey => $child) {
                    if (self::isProjectTaskCustomKey(object_get($child, 'menu_code'))) {
                        unset($children[$childKey]);
                    }
                }
                $menuList[$key]['children'] = $children->values();
                break;
            }
        }
    }

    public function addOutsendData($data, $tableKey)
    {
        if (!isset($data['creator']) || !$data['creator']) {
            if (!isset($data['current_user_id']) || !$data['current_user_id']) {
                return ['code' => ['0x016043', 'fields']];
            }
            $data['creator'] = $data['current_user_id'];
        }
        
        $res = $this->addCustomData($data, $tableKey, 'flow');
        $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
        $primaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        if (isset($res['code']) || !$res) {
            if (isset($res['code'])) {
                return $res;
            } else {
                return ['code' => ['0x016033', 'fields']];
            }
        } else {
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => $table,
                        'field_to' => $primaryKey,
                        'id_to' => $res,
                    ],

                ],
            ];
        }
    }

    public function editOutsendData($data, $tableKey)
    {
        // 客户二次开发
        if (isset($data['to_detail_data']) && $data['to_detail_data']) {
            unset($data['to_detail_data']);
            return $this->editOutsendDataNext($data, $tableKey, function($dataId, $detailData, $tableKey, $param) {
                if (empty($detailData)) {
                    return true;
                }
//                $edit = isset($param['edit']) ? $param['edit'] : false;
                $outsource = isset($param['outsource']) ? $param['outsource'] : false;
                $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
                $fieldsMap = isset($param['fieldsMap']) ? $param['fieldsMap'] : [];
                $upload = [];
                foreach ($fieldsMap as $code => $field) {
                    $field_options = json_decode($field->field_options, true);
                    if (isset($field_options['parentField']) && $field_options['parentField'] && $field->field_directive == 'upload') {
                        $upload[] = $code;
                    }
                }
                foreach ($detailData as $fieldCode => $data) {
//                    if ($edit) {
//                        app($this->formModelingRepository)->deleteCustomSubData($tableKey, $fieldCode, $dataId);
//                    }
                    $allSubData = app($this->formModelingRepository)->getCustomSubDataByMainId($tableKey, $fieldCode, $dataId);
                    $allSubDataId = count($allSubData) > 0 ? array_column($allSubData->toArray(), 'detail_data_id') : [];
                    if (!empty($data)) {
                        foreach ($data as $key => $value) {
                            $info = $value;
                            if (!empty($value)) {
                                // 明细数据ID不存在则不往下执行
                                if (!isset($value['detail_data_id']) || !$value['detail_data_id']) {
                                    continue;
                                }
                                $detailDataId = intval($value['detail_data_id']);
                                // 明细数据ID不在当前主数据的明细ID里也不执行
                                if (!in_array($detailDataId, $allSubDataId)) {
                                    continue;
                                }
                                if ($outsource == 1) {
                                    $value = $this->parseOutsourceData($value, $tableKey);
                                }

                                if (is_array($value)) {
                                    foreach ($value as $k => $v) {
                                        if (empty($v) && $v !== "0" && $v !== 0) {
                                            unset($value[$k]);
                                        }
                                        if (is_array($v) && !empty($v)) {
                                            $value[$k] = json_encode($v);
                                        }
                                    }
//                                    $val = implode(",", $value);
//                                    if (strpos('$val', 'sub') === false) {
//                                        if (isset($value[$mainPrimaryKey])) {
//                                            unset($value[$mainPrimaryKey]);
//                                        }
//                                        if (isset($value['detail_data_id'])) {
//                                            unset($value['detail_data_id']);
//                                        }
//                                    }
                                }
                                if (!empty($value)) {
                                    $value[$mainPrimaryKey] = $dataId;
                                }
                                if (!empty($upload)) {
                                    foreach ($upload as $uploadField) {
                                        if (isset($value[$uploadField])) {
                                            unset($value[$uploadField]);
                                        }
                                    }
                                }
                                $res = [];
                                app($this->formModelingRepository)->editCustomSubData($tableKey, $fieldCode, $value, $detailDataId);
                                if (!empty($upload)) {
                                    foreach ($upload as $uploadField) {
                                        if (isset($info[$uploadField]) && !empty($info[$uploadField])) {
                                            $res[$uploadField] = $info[$uploadField];
                                            $this->addCustomAttachmentData($detailDataId, $res, $tableKey, 'update'); //附件关系新增
                                        }
                                    }
                                }

                            }
                        }
                    }
                }
                return true;
            });
        }
        return $this->editOutsendDataNext($data, $tableKey);
        
    }
    private function editOutsendDataNext($data, $tableKey, $terminal = null) 
    {
        if (isset($data['unique_id']) && $data['unique_id']) {
            $uniqueId = $data['unique_id'];
            $editData = $data['data'];
            if (!isset($editData['creator']) || !$editData['creator']) {
                if (!isset($data['current_user_id']) || !$data['current_user_id']) {
                    return ['code' => ['0x016043', 'fields']];
                }
                $editData['creator'] = $data['current_user_id'];
            }
            $editData['data_from'] = 'flow';
            $editData['own'] = $this->getSimpleUser($editData['creator']);
            $res = $this->editCustomData($editData, $tableKey, $uniqueId, $terminal, 'flow');
            if (isset($res['code']) || !$res) {
                return isset($res['code']) ? $res : ['code' => ['0x016033', 'fields']];
            } else {
                return [
                    'status' => 1,
                    'dataForLog' => [
                        [
                            'table_to' => app($this->formModelingRepository)->getCustomDataTableName($tableKey),
                            'field_to' => $this->getDataTablePrimaryKeyName($tableKey),
                            'id_to' => $uniqueId,
                        ],
                    ],
                ];
            }
        } 
        return ['code' => ['0x016035', 'fields']];
        
    }
    private function getSimpleUser($userId)
    {
        $user = app($this->userRepository)->getUserSystemInfo($userId);
        $own = [
            'user_id' => $userId
        ];
        if ($user) {
            if (isset($user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment)) {
                $department = $user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment;
                $own['dept_id'] = $department->dept_id ?? 0;
            }
            if (isset($user->userHasManyRole) && !empty($user->userHasManyRole)) {
                $roleId = [];
                foreach($user->userHasManyRole as $value){
                    if (isset($value->hasOneRole) && !empty($value->hasOneRole)) {
                        $roleId[] = $value->hasOneRole->role_id;
                    }
                }
                $own['role_id'] = $roleId;
            }
        }
        return $own;
    }

    public function deleteOutsendData($data, $tableKey)
    {
        if (isset($data['unique_id']) && $data['unique_id']) {
            $unique_id = $data['unique_id'];
            $table = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
            $primaryKey = $this->getDataTablePrimaryKeyName($tableKey);
            $currentUserId = $data['current_user_id'] ?? null;
            if (!$currentUserId) {
                return ['code' => ['0x016043', 'fields']];
            }
            $own = $this->getSimpleUser($currentUserId);
            $res = $this->deleteCustomData($tableKey, $unique_id, $own, 'flow');
            if (isset($res['code'])) {
                return $res;
            } else {
                return [
                    'status' => 1,
                    'dataForLog' => [
                        [
                            'table_to' => $table,
                            'field_to' => $primaryKey,
                            'id_to' => $unique_id,
                        ],
                    ],
                ];
            }
        } else {
            return ['code' => ['0x016035', 'fields']];
        }
    }
    public function getCurrentTemplate($tableKey, $param)
    {

        $bindTemplate = $this->getBindTemplate($tableKey, $param);
        $res = [];
        foreach ($bindTemplate as $template) {
            $template = $this->transferTemplateExtra($template, true);
            if ($template->terminal == 'pc' && in_array($template->bind_type, [1, 2])) {
                $res[] = $template;
            }
            if ($template->terminal == 'mobile' && in_array($template->bind_type, [1])) {
                $res[] = $template;
            }
        }
        return $res;
    }
    public function getSystemApp($tableKey, $params)
    {
        $params = $this->parseParams($params);
        $getMenu = app($this->formModelingRepository)->getMenu($tableKey, $params);
        foreach ($getMenu as $k => $v) {
            $getMenu[$k]->menu_name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
        }
        return $getMenu;

    }
    public function quickSave($tableKey, $data)
    {
        //保存字段
        $fields = isset($data['field_list']) ? $data['field_list'] : [];
        $this->saveFormFields($fields, $tableKey);

        //web端列表模板
        $webList = $data['web_list'];
        $webListId = $this->saveBindTemplate($tableKey, $webList, 'pc', '1');
        //web端表单模板
        $webForm = $data['web_form'];
        $webFormId = $this->saveBindTemplate($tableKey, $webForm, 'pc', '2');
        //手机端列表模板
        $mobileList = $data['mobile_list'];
        $mobileListId = $this->saveBindTemplate($tableKey, $mobileList, 'mobile', '1');
        //手机端表单模板
        $mobileForm = $data['mobile_form'];
        $mobileFormId = $this->saveBindTemplate($tableKey, $mobileForm, 'mobile', '2');

        return true;
    }
    /**
     * 应用模板
     *
     * @param   array  $data
     *
     * @return  boolean
     */
    public function saveBindTemplate($tableKey, $data, $terminal, $type)
    {
        $param = [
            'terminal' => $terminal,
            'bind_type' => $type,
        ];
        $listLayout = app($this->formModelingRepository)->getBindTemplate($tableKey, $param);
        if ($listLayout && isset($listLayout->template_id)) {
            $id = $listLayout->template_id;
            $data['table_key'] = $tableKey;
            if ($type == '2') {
                $fieldData = [
                    [
                        'bind_type' => 3,
                        'bind_template' => $id,
                        'terminal' => $terminal,
                        'table_key' => $tableKey,
                    ],
                    [
                        'bind_type' => 4,
                        'bind_template' => $id,
                        'terminal' => $terminal,
                        'table_key' => $tableKey,
                    ],
                ];
                $this->bindTemplate($fieldData);
            }

            return $this->editTemplate($data, $id);
        }

    }
    public function transferDirective($directive)
    {
        switch ($directive) {
            case 'detail-layout':
                return 'detailLayout';
                break;
            case 'dynamic-info':
                return 'dynamicInfo';
                break;
            default:
                return $directive;
                break;
        }
    }
    public function exportMaterial($tableKey, $param = [])
    {

        //获取模块信息
        $moduleInfo = $this->getCustomMenu($tableKey, []);
        $parent = app($this->menuRepository)->getDetail($moduleInfo->menu_parent);
        $moduleInfo->parent_name = trans_dynamic("menu.menu_name.menu_" . $parent->menu_id);
        $this->changeModuleInfo($tableKey, $moduleInfo);
        //获取字段信息

        $fieldsParam = ['lang' => true,'noTransfer'=>1];
        $fieldsInfo = $this->listCustomFields($fieldsParam, $tableKey);
        //获取模板信息
        $templates = app($this->formModelingRepository)->getTemplateList($tableKey, []);
        foreach ($templates as $key => $value) {
            $templates[$key] = $this->transferTemplateExtra($value);
        }
        $bind = app($this->formModelingRepository)->getSettingLayout($tableKey);
        $result = [
            'version' => version(),
            'module' => $moduleInfo,
            'fields' => $fieldsInfo,
            'template' => $templates,
            'bind' => $bind,
        ];
        $result = json_encode($result);
        return $result;
    }

    public function importMaterial($tableKey, $datas)
    {
        if (empty($datas['attachment_id']) && empty($datas['print_model'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!empty($datas['attachment_id'])) {
            $attachmentFile = app($this->attachmentService)->getOneAttachmentById($datas['attachment_id']);
            $attachmentName = $attachmentFile['attachment_name'] ?? '';
            $fileContent = '';
            if (isset($attachmentFile['temp_src_file'])) {
                $fileContent = file_get_contents($attachmentFile['temp_src_file']);
            }
        } else {
            $fileContent = $datas['print_model'];
        }
        if (empty($fileContent)) {
            return $fileContent;
        }
        $info = json_decode($fileContent, true);
        $version = version();//当前版本信息
        if(isset($info['version']) && $info['version'] > $version){
            return ['code' => ['0x016042', 'fields']];
        }
        if (!isset($datas['source']) || (isset($datas['source']) && $datas['source'] != 'preview')) {
            if (!isset($info['module']['menu_code']) || (isset($info['module']['menu_code']) && $tableKey != $info['module']['menu_code'])) {
                if (self::isProjectCustomKey($tableKey) && self::isProjectCustomKey($info['module']['menu_code'])) {
                    //项目跳过
                } else if (self::isProjectTaskCustomKey($tableKey) && self::isProjectTaskCustomKey($info['module']['menu_code'])) {
                    //项目任务跳过
                }else{
                    return ['code' => ['0x016041', 'fields']];
                }
            }
        }
        $total = app($this->formModelingRepository)->getCustomDataTotal($tableKey, ['join_main_table' => false]);
        if (isset($total->total) && $total->total > 0) {
            return ['code' => ['0x016040', 'fields']];
        }
        //存字段信息
        $historyFieldsInfo = $this->listCustomFields([], $tableKey);
        $historyFields = [];
        $tableName = app($this->formModelingRepository)->getCustomDataTableName($tableKey);
        foreach ($historyFieldsInfo as $v) {
            if(isset($v['is_system']) && $v['is_system'] ==0){
                $field = $v['field_code'];
                if (Schema::hasColumn($tableName, $field)) {
                    Schema::table($tableName, function ($table) use ($field) {
                        $table->dropColumn($field);
                    });
                }
            }
            app($this->formModelingRepository)->deleteCustomField($v['field_id']);
        }
        $currentFieldsInfo = $info['fields'];
        $currentFields = [];
        foreach ($currentFieldsInfo as $value) {
            $value['modify_flag'] = 1;
            $currentFields[] = $value;
        }
        if(!empty($currentFields)){
            $saveFields = $this->saveFormFields($currentFields, $tableKey);
            if(isset($saveFields['code'])){
                return $saveFields['code'];
            }
            // 项目任务特殊处理，系统字段被插入副标，需要删除；这里删除可以不影响其它逻辑，新增的地方修改担心影响整体逻辑
            if (self::isProjectSeriesCustomKey($tableKey)) {
                foreach ($info['fields'] as $key => $item) {
                    if ($item['is_system']) {
                        $field = $item['field_code'];
                        if (Schema::hasColumn($tableName, $field)) {
                            Schema::table($tableName, function ($table) use ($field) {
                                $table->dropColumn($field);
                            });
                        }
                    }
                }
            }
        }
        //存模板信息
        //删除历史模板
        app($this->formModelingRepository)->deleteTemplateByKey($tableKey);
        //新增模板
        if (isset($info['template']) && !empty($info['template'])) {
            foreach ($info['template'] as $template) {
                $template['table_key'] = $tableKey;
                $templateId = $this->saveTemplate($template);
                $oldTemplateId = $template['template_id'];
                if (isset($info['bind']) && !empty($info['bind'])) {
                    foreach ($info['bind'] as $key => $bind) {
                        if ($bind['bind_template'] == $oldTemplateId) {
                            $info['bind'][$key]['bind_template'] = $templateId;
                            $info['bind'][$key]['table_key'] = $tableKey;
                        }
                    }
                }

            }
        }
        //保存绑定关系
        if (isset($info['bind']) && !empty($info['bind'])) {
            app($this->formModelingRepository)->deleteSettingLayout($tableKey);
            $this->bindTemplate($info['bind'], $tableKey);
        }
        return true;
    }

    private function changeModuleInfo($tableKey, &$moduleInfo) {
        // 对项目与任务特殊处理，在自助平台存储统一的menu_code，前端也会根据这个值查询素材
        if (self::isProjectSeriesCustomKey($tableKey)) {
            if (self::isProjectCustomKey($tableKey)) {
                $moduleInfo->menu_code = 'project_value';
            } else if (self::isProjectTaskCustomKey($tableKey)) {
                $moduleInfo->menu_code = 'project_task_value';
            }
        }
    }

}
