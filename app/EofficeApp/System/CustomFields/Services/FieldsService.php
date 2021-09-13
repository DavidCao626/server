<?php
namespace App\EofficeApp\System\CustomFields\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\CustomFields\Repositories\FieldsRepository;
use App\EofficeApp\System\CustomFields\Traits\FieldsRedisTrait;
use App\EofficeApp\System\CustomFields\Traits\FieldsTrait;
use DB;
use Schema;

/**
 * @自定义字段服务类
 *
 * @author 李志军
 */
class FieldsService extends BaseService
{
    use FieldsTrait;
    use FieldsRedisTrait;
    private $fieldsRepository; //自定义字段资源库对象
    private $customTableConfigRepository;
    private $attachmentService;
    private $UserMenuService;
    private $userMenuRepository;
    private $diffDirectives = ['upload', 'detailLayout'];
    const CUSTOM_TABLE      = 'custom:table';
    // tablekey 对应已存在的 数据表
    const CUSTOM_EXIST_TABLE = 'custom:table';
    // tablekey 对应自定义字段，前缀
    const CUSTOM_TABLE_FIELDS = 'custom:table_fields_';
    const PARSE_DATA          = 'parse:data_';
    const CUSTOM_REMINDS      = 'custom:reminds';
    /**
     * @注册自定义字段资源库对象
     * @param \App\EofficeApp\Repositories\FieldsRepository $fieldsRepository
     */
    public function __construct()
    {
        parent::__construct();
        $this->fieldsRepository              = 'App\EofficeApp\System\CustomFields\Repositories\FieldsRepository';
        $this->menuRepository                = 'App\EofficeApp\Menu\Repositories\MenuRepository';
        $this->customTableConfigRepository   = 'App\EofficeApp\System\CustomFields\Repositories\CustomTableConfigRepository';
        $this->attachmentService             = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->systemComboboxRepository      = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxRepository';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->userMenuService               = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->langService                   = 'App\EofficeApp\Lang\Services\LangService';
        $this->userService                   = 'App\EofficeApp\User\Services\UserService';
        $this->userMenuRepository            = 'App\EofficeApp\Menu\Repositories\UserMenuRepository';
        $this->empowerService                = "App\EofficeApp\Empower\Services\EmpowerService";
        $this->formModelingService           = "App\EofficeApp\FormModeling\Services\FormModelingService";
    }

    /**
     * 2017-08-08
     *
     * 获取自定义模块，菜单
     *
     * @return array
     */
    public function getCustomModules($params)
    {
        $params           = $this->parseParams($params);
        $menuParent       = [];
        $module           = app($this->fieldsRepository)->getParent($params);
        $permissionModule = app($this->empowerService)->getPermissionModules();
        foreach ($module as $k => $v) {
            if ($v->menu_parent < 1000 && !in_array($v->menu_parent, $permissionModule)) {
                continue;
            }
            $menuInfo = app($this->menuRepository)->getDetail($v->menu_parent);
            if (!empty($menuInfo)) {
                $menuParent[] = [
                    'title'       => trans_dynamic("menu.menu_name.menu_" . $menuInfo->menu_id),
                    'module'      => $menuInfo->menu_name_zm,
                    'menu_parent' => $v->menu_parent,
                ];
            }
        }
        foreach ($menuParent as $key => $value) {
            $getMenu = app($this->fieldsRepository)->getMenu($value['menu_parent'], $params);
            foreach ($getMenu as $k => $v) {
                $v->menu_name = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
            }
            $menuParent[$key]['category_sub'] = $getMenu;
        }
        return $menuParent;
    }
    /**
     *
     *
     * 获取自定义模块，具有设置自定义提醒的菜单
     *
     * @return array
     */
    public function getCustomRemindModules()
    {
        $menuParent = [];
        $module     = app($this->fieldsRepository)->getParent();
        foreach ($module as $k => $v) {
            $menuInfo = app($this->menuRepository)->getDetail($v->menu_parent);
            if (!empty($menuInfo)) {
                $menuParent[$k]['title']       = trans_dynamic("menu.menu_name.menu_" . $menuInfo->menu_id);
                $menuParent[$k]['module']      = $menuInfo->menu_name_zm;
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
                    default:
                        $menuParent[$k]['menu_type'] = $menuInfo->menu_id;
                        break;
                }
            }
        }
        $menuParentResult = [];
        foreach ($menuParent as $key => $value) {
            $getMenu = app($this->fieldsRepository)->getRemindMenuSet($value['menu_parent']);
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

            // if (isset($menuParent[$key]['category_sub'])) {
            //     $menuParentResult[] = $menuParent[$key];
            // }
        }
        return $menuParent;
    }

    /**
     * 2017-08-09
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
        $moduleKey = $tableKey;
        if ($tableKey == 'my_borrow') {
            $tableKey = 'book_manage';
        }
        $result = app($this->fieldsRepository)->listCustomFields($tableKey, $this->parseParams($param));
        foreach ($result as $key => $value) {
            $this->transferFieldOptions($value, $tableKey);
            $result[$key]->field_name = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $value->field_code);
            if (isset($param['lang']) && !empty($param['lang'])) {
                $result[$key]->field_name_lang = app($this->langService)->transEffectLangs("custom_fields_table.field_name." . $tableKey . "_" . $value->field_code);
            }
        }
        if (config('customfields.fields_show.' . $moduleKey)) {
            $fields = config('customfields.fields_show.' . $moduleKey);
            if ($fields) {
                $services = isset($fields[0]) ? $fields[0] : '';
                $method   = isset($fields[1]) ? $fields[1] : '';
                if (method_exists(app($services), $method)) {
                    $result = app($services)->$method($result);
                }
            }
        }
        return $result;
    }

    public function transferFieldOptions($data, $tableKey, $flag = false)
    {
        $patten = '/\$(\w+)\$/';
        preg_match_all($patten, $data->field_options, $variableName);

        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            $field_options_lang = [];
            foreach ($variableName[1] as $k => $v) {
                if ($flag) {
                    $transContent = mulit_trans_dynamic("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v", [], 'zh-CN');
                } else {
                    $transContent = mulit_trans_dynamic("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v");
                }
                $data->field_options    = str_replace($variableName[0][$k], $transContent, $data->field_options);
                $field_options_lang[$v] = app($this->langService)->transEffectLangs("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v", true);
            }
            if (!empty($field_options_lang)) {
                $data->field_options_lang = $field_options_lang;
            }
        }
        return $data;
    }

    /**
     * 2017-08-08
     *
     * 获取自定义页面列表
     *
     * @param array $param
     * @param string $tableKey
     *
     * @return array
     */
    public function getCustomDataLists($param, $tableKey, $own)
    {
        $result['list'] = [];
        //判断数据表是否存在
        // if ($isExist = $this->checkTableExist($tableKey)) {
        //      return [];
        // }
        if (!app($this->fieldsRepository)->hasCustomDataTable($tableKey)) {
            return [];
        }
        //解析参数
        $param = $this->parseParams($param);
        //获取返回数据格式
        $response = $param['response'] ?? 'both';
        //获取过滤条件
        $param['filter'] = $this->getFilterCondition($tableKey, $own, $param);

        //判断是否存在按外键筛选
        $foreignKey = $param['foreign_key'] ?? '';
        if ($foreignKey && $foreignKeyField = $this->rsGetForeignKeyField($tableKey)) {
            $param['filter'][$foreignKeyField] = [$foreignKey];
        }

        $platform        = $this->defaultValue('platform', $param, 'pc');
        $fields          = $this->listRedisCustomFields($tableKey, $platform);
        $fieldsMap       = [];
        $aggregateFields = [];
        if (count($fields) > 0) {
            foreach ($fields as $field) {
                $fieldsMap[$field->field_code] = $field;
                $fieldOption                   = json_decode($field->field_options);
                if (isset($fieldOption)) {
                    if (isset($fieldOption->format) && isset($fieldOption->format->aggregate)) {
                        array_push($aggregateFields, $field->field_code);
                    }
                }
            }
        }

        // 获取总数
        if (in_array($response, ['both', 'count']) || count($aggregateFields) > 0) {
            if (count($aggregateFields) > 0) {
                $param['aggregate'] = $aggregateFields;
            }
            if (!isset($param['aggregate'])) {
                // 如果有配置文件，并且返回不是null，就不需要再次获取
                $customTotalConfigs = config('customfields.customTotal') ?? [];
                if (isset($customTotalConfigs[$tableKey])) {
                    list($customTotalClass, $customTotalMethod) = $customTotalConfigs[$tableKey];
                    $result['total']                            = app($customTotalClass)->$customTotalMethod($param);
                }
            }
            if (!isset($result['total']) || $result['total'] === null) {
                $data_total      = app($this->fieldsRepository)->getCustomDataTotal($tableKey, $param);
                $result['total'] = $data_total->total;
            }
            // 格式化合计数据
            if (isset($data_total) && count($aggregateFields) > 0) {
                $aggregate = [];
                foreach ($aggregateFields as $field_code) {
                    $field     = $fieldsMap[$field_code];
                    $directive = $field->field_directive;
                    // 仅text和number类型支持合计
                    $aggregate[$field_code] = $this->{$directive . 'Directive'}($data_total->{$field_code}, $field, true, true);
                }
                $result['aggregate'] = $aggregate;
            }
        }
        // 仅返回数据
        if (isset($result['total']) && $response == 'count') {
            return $result['total'];
        }

        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        $originFields   = $this->rsGetShowFields($tableKey, $platform);
        if (isset($param['fields']) && empty($param['fields'])) {
            $originFields = array_unique(array_merge($originFields, $param['fields']));
        }
        $param['fields'] = $originFields;
        array_push($param['fields'], $mainPrimaryKey);
        if (empty($param['fields'])) {
            return $result;
        }

        $lists = app($this->fieldsRepository)->getCustomDataList($tableKey, $param);
        if (!empty($lists)) {
            foreach ($lists as $itemKey => $item) {
                foreach ($fieldsMap as $key => $field) {
                    $value     = isset($item->$key) ? $item->$key : '';
                    $field     = $this->transferFieldOptions($field, $tableKey);
                    $directive = $field->field_directive;
                    if ($directive == "select" || $directive == "selector") {
                        $lists[$itemKey]->{'raw_' . $key} = $value;
                    }
                    $lists[$itemKey]->{$key} = $this->rsGetFieldValue($tableKey, $field, $item, $value);
                }
            }
        }
        // 返回结果集
        if ($response == 'both') {
            $resultData = ['total' => $result['total'], 'list' => $lists];
        } else if ($response == 'data') {
            $resultData = $lists;
        } else {
            $resultData = $result['total'];
        }
        return $resultData;
    }

    public function parseCutomData($params, $own = '')
    {

        // 特殊字段的处理
        $data_source_config = [
            'office_supplies_storage' => [
                'method' => 'App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService@getOfficeSupplies',
                'fields' => [
                    'stock_surplus'      => 'stock_surplus',
                    'storage_remind_max' => 'remind_max',
                    'storage_remind_min' => 'remind_min',
                ],
            ],
        ];

        if (!isset($params['module']) || !isset($params['field'])) {
            return '';
        }

        $params     = $this->parseParams($params);
        $tableKey   = $params['module'];
        $field_code = $params['field'];
        $menu       = $this->tableKeyTransferMenu($tableKey);
        if (isset($own['menus']['menu']) && !in_array($menu, $own['menus']['menu'])) {
            return ['code' => ['0x016024', 'fields']];
        }
        //获取字段信息
        $fields = app($this->fieldsRepository)->listCustomFields($tableKey, ['search' => [
            'field_hide' => [0],
            'field_code' => [$field_code],
        ]]);

        if ($fields->isEmpty()) {
            return '';
        }
        $field = $fields->shift();
        $field = $this->transferFieldOptions($field, $tableKey);

        if (isset($data_source_config[$tableKey])) {
            $moduleConfig = $data_source_config[$tableKey];
            if (isset($moduleConfig['fields']) && is_array($moduleConfig['fields']) && isset($moduleConfig['fields'][$field_code])) {
                if (isset($moduleConfig['method'])) {
                    list($servcie, $method) = explode('@', $moduleConfig['method']);
                    if (method_exists(app($servcie), $method)) {
                        $data              = app($servcie)->$method($params['search_value']);
                        $data[$field_code] = $data[$moduleConfig['fields'][$field_code]];
                    }
                }
            }
        }

        if (!isset($data)) {
            $primaryKey = $this->getDataTablePrimaryKeyName($tableKey);
            $data       = app($this->fieldsRepository)->parseCutomData($tableKey, $primaryKey, $params);
        }

        $result = '';
        if (!empty($field) && !empty($data)) {
            $directive = $field->field_directive;
            switch ($directive) {
                case 'upload':
                    $result = $this->{$directive . 'Directive'}($data->$primaryKey, $field, $tableKey);
                    break;
                case 'area':
                case 'select':
                case 'selector':
                    $result = $this->{$directive . 'Directive'}($data, $field);
                    break;
                default:
                    $result = $this->{$directive . 'Directive'}($data->$field_code, $field);
            }
        }
        return $result;
    }

    public function tableKeyTransferMenu($tableKey,$method="menu")
    {
        if ($res = config('customfields.'.$method."." . $tableKey)) {
            $menu_id = config('customfields.'.$method."." . $tableKey);
        } else {
            if (strstr($tableKey, "project")) {
                $menu_id = "161";
            } else {
                $menu_id = $tableKey;
            }
        }
        return $menu_id;
    }
    /**
     * 2017-08-09
     *
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
        if ($tableKey == 'my_borrow') {
            $tableKey = 'book_manage';
        }
        if (!app($this->fieldsRepository)->hasCustomDataTable($tableKey)) {
            return [];
        }
        $menu = $this->tableKeyTransferMenu($tableKey);
//        if (isset($own['menus']['menu']) && !in_array($menu, $own['menus']['menu'])) {
        if(is_string($menu)){
            $menu = [$menu];
        }
//        if (isset($own['menus']['menu']) && !array_intersect($menu, $own['menus']['menu'])) {
//            return ['code' => ['0x016024', 'fields']];
//        }
        $filterConfig = config('customfields.detailBefore.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                if (!$power = app($services)->$method($dataId, $own)) {
                    return ['code' => ['0x016024', 'fields']];
                }
            }
        }

        if (!$dataId || $dataId == 0) {
            return ['code' => ['0x016020', 'fields']];
        }
        $fieldsMap = $this->getShowFieldsMap($tableKey);
        $detail    = app($this->fieldsRepository)->getCustomDataDetail($tableKey, $dataId);
        if (!count($detail)) {
            return [];
        }

        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);

        $data[$mainPrimaryKey] = $detail->{$mainPrimaryKey};
        foreach ($fieldsMap as $fieldCode => $field) {
            $field         = $this->transferFieldOptions($field, $tableKey);
            $field_options = json_decode($field->field_options);
            if (!isset($field_options->parentField)) {
                if ($field->field_directive == 'detail-layout' || $field->field_directive == 'upload') {
                    if ($field->field_directive == 'detail-layout') {
                        $data[$fieldCode] = $this->detailLayoutDirective($detail->{$mainPrimaryKey}, $field, $tableKey, true);
                    } else {
                        $data[$fieldCode] = $this->{$field->field_directive . 'Directive'}($detail->{$mainPrimaryKey}, $field, $tableKey, 'detail');
                    }

                } else if ($field->field_directive == 'area' || $field->field_directive == 'select' || $field->field_directive == 'selector') {
                    $data[$fieldCode] = $this->{$field->field_directive . 'Directive'}($detail, $field, 'detail');
                } else {
                    if (isset($detail->$fieldCode)) {
                        $data[$fieldCode] = $this->{$field->field_directive . 'Directive'}($detail->{$fieldCode}, $field, 'detail');
                    } else {
                        $data[$fieldCode] = "";
                    }
                }
            }

        }
        $filterConfig = config('customfields.__DEATAIL__.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                $data = app($services)->$method($data);
            }
        }
        return $data;
    }

    /**
     * 2017-08-09
     *
     * 删除自定义页面数据
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return boolean
     */
    public function deleteCustomData($tableKey, $dataId)
    {
        if (!$dataId || $dataId == 0) {
            return ['code' => ['0x016020', 'fields']];
        }

        $dataIdArray = explode(',', rtrim($dataId, ','));

        if (empty($dataIdArray)) {
            return true;
        }
        $filterConfig = config('customfields.deleteBefore.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                if (!$power = app($services)->$method($dataId)) {
                    return ['code' => ['0x016024', 'fields']];
                }
            }
        }
        return app($this->fieldsRepository)->deleteCustomData($tableKey, $dataIdArray);
    }

    /**
     * 2017-08-09
     *
     * 新建自定义页面数据
     *
     * @param array $data
     * @param string $tableKey
     *
     * @return boolean
     */
    public function addCustomData($data, $tableKey)
    {
        if (empty($data)) {
            return "";
        }
        $filterConfig = config('customfields.addBefore.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                if (!$power = app($services)->$method($data)) {
                    return ['code' => ['0x016024', 'fields']];
                }
            }
        }
        if (isset($data['outsource'])) {
            $data = $this->parseOutsourceData($data, $tableKey);
        }
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            $data['detailLayout'][$key] = $value;
                            unset($data[$key]);
                        }
                    }

                }
            }
        }

        $fieldsMap = $this->getShowFieldsMap($tableKey);
        foreach ($fieldsMap as $k => $v) {
            if (isset($v->field_options)) {
                $field_options = json_decode($v->field_options);
                if (isset($field_options->validate)) {
                    $validate = $field_options->validate;
                    if (isset($validate->required) && $validate->required == 1) {
                        if (!empty($field_options) && !empty($field_options->parentField)) {
                            $parent = $field_options->parentField;
                            if (!empty($parent) && !empty($data['detailLayout'])) {
                                $customFields = app($this->fieldsRepository)->custom_fields_table($tableKey, $parent);
                                if (isset($data['detailLayout'][$parent]) && $customFields->field_hide != 1 && !empty($data['detailLayout'][$parent]) && is_array($data['detailLayout'][$parent])) {
                                    foreach ($data['detailLayout'][$parent] as $key => $value) {
                                        if (!isset($value[$k])) {
                                            return ['code' => ['0x016021', 'fields']];
                                        }
                                        if ($value[$k] === '' || $value[$k] === []) {
                                            return ['code' => ['0x016021', 'fields']];
                                        }
                                    }
                                }
                            }
                        } else {
                            //项目的自定义字段不验证主表数据，已在自己模块处理
                            if (strpos($tableKey, 'project_value_') !== false &&
                                strpos($k, 'sub_') === false) {
                                continue;
                            }
                            if (!isset($data[$k])) {
                                return ['code' => ['0x016021', 'fields']];
                            }
                            if ($data[$k] === '' || $data[$k] === []) {
                                return ['code' => ['0x016021', 'fields']];
                            }
                        }
                    }

                }
            }

        }
        $parseData   = $this->parseCustomFormData($tableKey, $data, 'true');
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
        $filterConfig = config('customfields.add.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                $filterAdd = app($services)->$method($parseData['main']);
                if (isset($filterAdd['code'])) {
                    return $filterAdd;
                }
            }
        }
        $dataId = app($this->fieldsRepository)->addCustomData($tableKey, $parseData['main']);
        if ($dataId) {
            if (isset($data['outsource'])) {
                $this->saveCustomSubData($dataId, $parseData['detailLayout'], $tableKey, false, 1); //明细字段新增
            } else {
                $this->saveCustomSubData($dataId, $parseData['detailLayout'], $tableKey); //明细字段新增
            }
            $this->addCustomAttachmentData($dataId, $parseData['upload'], $tableKey); //附件关系新增

            return $dataId;
        }

        return false;
    }
    public function checkUpload($fieldsMap, $parseData)
    {
        foreach ($fieldsMap as $k => $v) {
            if (isset($v->field_options)) {
                $field_options = json_decode($v->field_options);
                if (isset($field_options->uploadConfig)) {
                    $uploadConfig   = $field_options->uploadConfig;
                    $multiple       = (isset($uploadConfig->multiple) && $uploadConfig->multiple == 1) ? true : false;
                    $onlyImage      = (isset($uploadConfig->onlyImage) && $uploadConfig->onlyImage == 1) ? true : false;
                    $upload         = $parseData['upload'];
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
     * 2017-08-09
     *
     * 编辑自定义页面数据
     *
     * @param array $data
     * @param string $tableKey
     * @param int $dataId
     *
     * @return boolean
     */
    public function editCustomData($data, $tableKey, $dataId)
    {
        $filterConfig = config('customfields.editBefore.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                if (!$power = app($services)->$method($data, $dataId)) {
                    return ['code' => ['0x016024', 'fields']];
                }
            }
        }
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            $data['detailLayout'][$key] = $value;
                            unset($data[$key]);
                        }
                    }

                }
            }
        }
        $fieldsMap = $this->getShowFieldsMap($tableKey);
        foreach ($fieldsMap as $k => $v) {
            if (isset($v->field_options)) {
                $field_options = json_decode($v->field_options);
                if ($field_options->type == "detail-layout") {
                    if (empty($data[$k])) {
                        app($this->fieldsRepository)->deleteCustomSubData($tableKey, $k, $dataId);
                    }
                }
                if (isset($field_options->validate)) {
                    $validate = $field_options->validate;
                    if (isset($validate->required) && $validate->required == 1) {
                        if (!empty($field_options) && !empty($field_options->parentField)) {
                            $parent = $field_options->parentField;
                            if (!empty($parent) && !empty($data['detailLayout'])) {
                                $customFields = app($this->fieldsRepository)->custom_fields_table($tableKey, $parent);
                                if (isset($data['detailLayout'][$parent]) && $customFields->field_hide != 1 && !empty($data['detailLayout'][$parent]) && is_array($data['detailLayout'][$parent])) {
                                    foreach ($data['detailLayout'][$parent] as $key => $value) {
                                        if (!isset($value[$k]) || $value[$k] === '' && $value[$k] != 0) {
                                            return ['code' => ['0x016021', 'fields']];
                                        }
                                    }
                                }
                            }
                        } else {
                            if (!isset($data[$k]) || $data[$k] === '' && $data[$k] != 0) {
                                //项目的自定义字段不验证主表数据，已在自己模块处理
                                if (strpos($tableKey, 'project_value_') !== false &&
                                    strpos($k, 'sub_') === false) {
                                    continue;
                                }
                                return ['code' => ['0x016021', 'fields']];
                            }
                        }
                    }

                }
            }
        }
        $parseData = $this->parseCustomFormData($tableKey, $data, false);
        if (isset($data['detailLayout'])) {
            $parseData['detailLayout'] = $data['detailLayout'];
        }

        $filterConfig = config('customfields.after.' . $tableKey);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                $filterReturn = app($services)->$method($data);
                if (isset($filterReturn['code'])) {
                    return $filterReturn;
                }
            }
        }
        app($this->fieldsRepository)->editCustomData($tableKey, $parseData['main'], $dataId);
        $this->saveCustomSubData($dataId, $parseData['detailLayout'], $tableKey, true); //明细字段编辑

        $this->addCustomAttachmentData($dataId, $parseData['upload'], $tableKey, 'update'); //附件关系新增

        return true;
    }

    /**
     * 2017-08-11
     *
     * 获取自动查询的自定义数据列表
     *
     * @param array $param
     * @param string $tableKey
     *
     * @return array
     */
    public function getCustomDataAutoSearchLists($param, $tableKey, $own)
    {
        $param = $this->parseParams($param);

        if (!isset($param['search'])) {
            return [];
        }

        $searchKeys = array_keys($param['search']);

        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);

        $param['fields'] = array_merge($searchKeys, [$mainPrimaryKey]);

        $param['filter'] = $this->getFilterCondition($tableKey, $own, $param);
        //判断是否存在按外键筛选
        if (isset($param['foreign_key']) && $param['foreign_key']) {
            if ($foreignKeyField = app($this->fieldsRepository)->getForeignKeyField($tableKey)) {
                $param['filter'][$foreignKeyField] = [$param['foreign_key']];
            }
        }
        return app($this->fieldsRepository)->getCustomDataList($tableKey, $param);
    }

    /**
     * 2017-08-07
     *
     * 保存自定义字段
     *
     * @param array $data
     * @param string $tableKey
     *
     * @return boolean
     */
    public function saveCustomFields($data, $tableKey)
    {
        // if (empty($data)) {
        //     return ['code' => ['0x016017', 'fields']];
        // }

        $saveData = [
            'update' => [],
            'add'    => [],
            'delete' => [],
        ];

        foreach ($data as $item) {

            if ($item['modify_flag'] == 0) {
                if (isset($item['is_system']) && $item['is_system'] == 1) {
                    return ['code' => ['0x016010', 'fields']];
                }

                if ($item['field_id'] == 0) {
                    return ['code' => ['0x016007', 'fields']];
                }
                $saveData['delete'][$item['field_id']] = $item;
            } else if ($item['modify_flag'] == 1) {
                if (!isset($item['field_name']) && !$item['field_name']) {
                    return ['code' => ['0x016002', 'fields']];
                }

                if (!isset($item['field_code']) && !$item['field_code']) {
                    return ['code' => ['0x016016', 'fields']];
                }

                // if (app($this->fieldsRepository)->hasCustomFieldsByName($item['field_name'], $tableKey)) {
                //     return ['code' => ['0x016015', 'fields']];
                // }
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
                if (!isset($item['field_name']) && !$item['field_name']) {
                    return ['code' => ['0x016002', 'fields']];
                }

                if (!isset($item['field_code']) && !$item['field_code']) {
                    return ['code' => ['0x016016', 'fields']];
                }

                // if (app($this->fieldsRepository)->hasCustomFieldsByName($item['field_name'], $tableKey, $item['field_id'])) {
                //     return ['code' => ['0x016015', 'fields']];
                // }

                $saveData['update'][$item['field_id']] = $this->getFormCustomFieldsData($item, $tableKey);
            }
        }
        app($this->fieldsRepository)->createEmptyCustomDataTable($tableKey); //创建自定义字段数据表

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
            // 删除外键菜单文件
            FieldsRepository::destoryCustomTabMenus();
            return true;
        }

        
        return ['code' => ['0x016018', 'fields']];
    }

    /**
     * 2017-08-08
     *
     * 获取自定义字段详情
     *
     * @param type $fieldId
     *
     * @return json | 自定义字段详情
     */
    public function showCustomField($fieldId, $tableKey)
    {
        if ($fieldId == 0) {
            return ['code' => ['0x016007', 'fields']];
        }

        return app($this->fieldsRepository)->showCustomFields($fieldId);
    }

    /**
     * 获取过滤条件
     *
     * @param string $tableKey
     * @param array $own
     *
     * @return array
     */
    private function getFilterCondition($tableKey, $own = '', &$param = '')
    {
        $filterConfig = config('customfields.filter.' . $tableKey);

        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method   = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                return app($services)->$method($param, $own);
            }
        }

        return [];
    }

    /**
     * 2017-08-09
     *
     * 获取自定义页面展示的字段映射数组
     *
     * @param string $tableKey
     *
     * @return array
     */
    private function getShowFieldsMap($tableKey)
    {
        $fieldsMap = [];
        $fields    = app($this->fieldsRepository)->listCustomFields($tableKey, ['search' => ['field_hide' => [0], 'field_directive' => ['tabs', '!=']]]);
        if (count($fields) > 0) {
            foreach ($fields as $field) {
                $fieldsMap[$field->field_code] = $field;
            }
        }

        return $fieldsMap;
    }

    /**
     * 2017-08-08
     *
     * 新增自定义数据附件关联关系
     *
     * @param int $dataId
     * @param array $attachmentData
     * @param string $tableKey
     *
     * @return boolean
     */
    private function addCustomAttachmentData($dataId, $attachmentData, $tableKey, $flag = 'add')
    {
        if (empty($attachmentData)) {
            return true;
        }

        $tableName = app($this->fieldsRepository)->getCustomDataTableName($tableKey);

        foreach ($attachmentData as $fieldCode => $attachmentIds) {
            $entityTableData = [
                'table_name' => $tableName,
                'fileds'     => [
                    [
                        "field_name"   => "entity_id",
                        "field_type"   => "integer",
                        "field_length" => "11",
                        "field_common" => trans('fields.Relational_record'),
                    ], [
                        "field_name"   => "entity_column",
                        "field_type"   => "string",
                        "field_length" => "50",
                        "field_common" => trans('fields.management_table_corresponds'),
                    ],
                ],
            ];

            $conditons = [
                "entity_column" => [$fieldCode],
                'entity_id'     => [$dataId],
                "wheres"        => ["entity_id" => [$dataId], 'entity_column' => [$fieldCode]],
            ];
            app($this->attachmentService)->attachmentRelation($entityTableData, $conditons, $attachmentIds, $flag);
        }

        return true;
    }

    /**
     * 2017-08-08
     *
     * 保存自定义字段页面明细字段表数据
     *
     * @param int $dataId
     * @param array $detailData
     * @param string $tableKey
     *
     * @return boolean
     */
    private function saveCustomSubData($dataId, $detailData, $tableKey, $edit = false, $outsource = 0)
    {
        if (empty($detailData)) {
            return true;
        }
        $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
        foreach ($detailData as $fieldCode => $data) {
            if ($edit) {
                app($this->fieldsRepository)->deleteCustomSubData($tableKey, $fieldCode, $dataId);
            }
            if (!empty($data)) {
                foreach ($data as $key => $value) {
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
                        app($this->fieldsRepository)->addCustomSubData($tableKey, $fieldCode, $value);
                    }

                }

                // app($this->fieldsRepository)->addCustomSubData($tableKey, $fieldCode, $data);
            }
        }

        return true;
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

        return $array[$tableKey] = app($this->fieldsRepository)->getCustomDataPrimayKey($tableKey);
    }

    /**
     * 2017-08-11
     *
     * 解析自定义表单数据
     *
     * @param string $tableKey
     * @param array $data
     * @param boolean $add
     *
     * @return array
     */
    private function parseCustomFormData($tableKey, $data, $add = true)
    {
        $fieldsMap = $this->getShowFieldsMap($tableKey);

        $parseData = [

            'detailLayout' => [],
            'upload'       => [],
            'main'         => ['updated_at' => date('Y-m-d H:i:s')],

        ];

        if ($add) {
            $parseData['main']['created_at'] = date('Y-m-d H:i:s');
        }

        foreach ($fieldsMap as $fieldCode => $field) {
            if (isset($data[$fieldCode])) {
                $directive = $field->field_directive;
                if ($directive == "detail-layout" || $directive == "upload") {
                    $parseData[$directive][$fieldCode] = $data[$fieldCode];
                } else if ($directive == 'area') {
                    $areaData = $data[$fieldCode];

                    if (!empty($areaData) && is_array($areaData)) {
                        foreach ($areaData as $key => $item) {
                            $parseData['main'][$key] = $item;
                        }
                    }
                } else if ($directive == 'select' || $directive == 'selector') {
                    if (is_array($data[$fieldCode])) {
                        if (count($data[$fieldCode]) > 0) {
                            $parseData['main'][$fieldCode] = json_encode($data[$fieldCode]);
                        } else {
                            $parseData['main'][$fieldCode] = '';
                        }
                    } else {
                        $parseData['main'][$fieldCode] = trim($data[$fieldCode]);
                    }
                } else {
                    $parseData['main'][$fieldCode] = $this->{$directive . 'Directive'}($data[$fieldCode], $field, false);
                    if (!is_array($parseData['main'][$fieldCode])) {
                        $parseData['main'][$fieldCode] = trim($parseData['main'][$fieldCode]);
                    }
                }
            }
        }
        return $parseData;
    }

    /**
     * 2017-08-09
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
        if ($fieldId = app($this->fieldsRepository)->addCustomField($insert)) {
            $fieldOption = json_decode($data['field_options'], true);
            if (!empty($data['sub_fields']) && !empty($fieldOption) && !empty($fieldOption['parentField'])) {
                return true;
            }
            if ($data['field_directive'] == 'detail-layout') {
                //明细字段

                return app($this->fieldsRepository)->createCustomDataSubTable($tableKey, $data['field_code'], $data['sub_fields']);
            } else if ($data['field_directive'] == 'area') {

                foreach ($fieldOption['relation'] as $item) {
                    $item['field_data_type'] = 'varchar';

                    app($this->fieldsRepository)->addCustomColumn($item, $tableKey);
                }

                return true;
            } else if ($data['field_directive'] == 'upload') {
                //附件字段
                return true;
            } else {
                //其他字段
                if (!empty($fieldOption) && !empty($fieldOption['parentField'])) {
                    $tableKey = app($this->fieldsRepository)->getCustomDataTableName($tableKey);
                    $tableKey = $tableKey . "_" . $fieldOption['parentField'];
                    return app($this->fieldsRepository)->addLayoutColumn($data, $tableKey);
                } else {
                    return app($this->fieldsRepository)->addCustomColumn($data, $tableKey);
                }

            }
        }

        app($this->fieldsRepository)->deleteCustomField($fieldId);

        return false;
    }

    /**
     * 2017-08-08
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
        $this->deleteRedisFields($tableKey);
        if (!$fieldInfo = app($this->fieldsRepository)->showCustomField($fieldId)) {
            return true;
        }
        if (app($this->fieldsRepository)->deleteCustomField($fieldId)) {
            $fieldDirective = $fieldInfo->field_directive;
            if ($fieldDirective == 'upload') {
                //附件字段
                return true;
            } else if ($fieldDirective == 'detail-layout') {
                //明细字段
                app($this->fieldsRepository)->dropTable('custom_data_' . $tableKey . '_' . $item['field_code']);
                return true;
            } else if ($fieldDirective == 'area') {
                $fieldOption = json_decode($fieldInfo->field_options, true);

                foreach ($fieldOption['relation'] as $item) {
                    app($this->fieldsRepository)->deleteCustomDataTableColumn($item['field_code'], $tableKey);
                }
                return true;
            } else {
                $fieldOption = json_decode($fieldInfo->field_options, true);
                if (!empty($fieldOption) && !empty($fieldOption['parentField'])) {
                    $tableKey = "custom_data_" . $item['field_table_key'] . "_" . $fieldOption['parentField'];
                    return app($this->fieldsRepository)->deleteLayoutTableColumn($item['field_code'], $tableKey);
                } else {
                    //其他字段
                    return app($this->fieldsRepository)->deleteCustomDataTableColumn($item['field_code'], $tableKey);
                }

            }
        }

        return false;
    }

    /**
     * 2017-08-08
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
        return app($this->fieldsRepository)->updateCustomField($data, $fieldId);
    }

    /**
     * 2017-08-07
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
        $field_name = isset($data['field_name_lang']) ? $data['field_name_lang'] : '';
        if (!empty($field_name) && is_array($field_name)) {
            foreach ($field_name as $key => $value) {
                $langData = [
                    'table'      => 'custom_fields_table',
                    'column'     => 'field_name',
                    'lang_key'   => $tableKey . "_" . $data['field_code'],
                    'lang_value' => $value,
                ];
                $local = $key; //可选
                app($this->langService)->addDynamicLang($langData, $local);

            }
        } else {
            $langData = [
                'table'      => 'custom_fields_table',
                'column'     => 'field_name',
                'lang_key'   => $tableKey . "_" . $data['field_code'],
                'lang_value' => $data['field_name'],
            ];

            app($this->langService)->addDynamicLang($langData);
        }

        $field_options_lang = isset($data['field_options_lang']) ? $data['field_options_lang'] : '';
        if (!empty($field_options_lang) && is_array($field_options_lang)) {
            foreach ($field_options_lang as $option => $field_options) {
                if (is_array($field_options)) {
                    foreach ($field_options as $key => $value) {
                        $local = $key; //可选
                        if (trim($value) === '') {
                            remove_dynamic_langs("custom_fields_table.field_options.{$tableKey}_{$data['field_code']}.{$option}", $local);
                        } else {
                            $langOption = [
                                'table'      => 'custom_fields_table',
                                'column'     => 'field_options',
                                'option'     => $tableKey . "_" . $data['field_code'],
                                'lang_key'   => $option,
                                'lang_value' => $value,
                            ];
                            app($this->langService)->addDynamicLang($langOption, $local);
                        }
                    }
                }
            }
        }

        $fieldData = [
            'field_name'        => $tableKey . "_" . $data['field_code'],
            'field_directive'   => $this->defaultValue('field_directive', $data, 'text'),
            'field_search'      => $this->defaultValue('field_search', $data, 0),
            'field_filter'      => $this->defaultValue('field_filter', $data, 0),
            'field_list_show'   => $this->defaultValue('field_list_show', $data, 0),
            'field_options'     => $this->defaultValue('field_options', $data, ''),
            'field_explain'     => $this->defaultValue('field_explain', $data, ''),
            'field_sort'        => $this->defaultValue('field_sort', $data, 0),
            'field_hide'        => $this->defaultValue('field_hide', $data, 0),
            'field_allow_order' => $this->defaultValue('field_allow_order', $data, 0),
            'mobile_list_field' => $this->defaultValue('mobile_list_field', $data, ''),
            'is_foreign_key'    => $this->defaultValue('is_foreign_key', $data, 0),
            'updated_at'        => date('Y-m-d H:i:s'),
            'sub_fields'        => $this->defaultValue('sub_fields', $data, ''),
            'field_table_key'   => $tableKey,
            'field_code'        => $data['field_code'],
        ];
        //新增字段需要更多属性
        if ($add) {
            $fieldData['field_data_type'] = $this->defaultValue('field_data_type', $data, 'varchar');
            $fieldData['is_system']       = $this->defaultValue('is_system', $data, 0);
            $fieldData['created_at']      = date('Y-m-d H:i:s');
        }

        return $fieldData;
    }

    //old code ====================================================================================//
    /**
     * @新建自定义字段
     * @param array $fieldData
     * @return json | 字段id
     */
    public function saveFields($data, $tableKey)
    {
        // if (empty($data)) {
        //     return ['code' => ['0x016017', 'fields']];
        // }

        $update = $add = $delete = [];

        foreach ($data as $item) {
            if ($item['modify_flag'] == 0) {
                if ($item['is_system'] == 1) {
                    return ['code' => ['0x016010', 'fields']];
                }
                if ($item['field_id'] == 0) {
                    return ['code' => ['0x016007', 'fields']];
                }
                $delete[$item['field_id']] = $item['field_code'];
            } else if ($item['modify_flag'] == 1) {
                if (!isset($item['field_name']) && !$item['field_name']) {
                    return ['code' => ['0x016002', 'fields']];
                }

                if (!isset($item['field_code']) && !$item['field_code']) {
                    return ['code' => ['0x016016', 'fields']];
                }

                // if (app($this->fieldsRepository)->hasFieldsByName($item['field_name'], $tableKey)) {
                //     return ['code' => ['0x016015', 'fields']];
                // }

                $add[] = $this->getFormFieldsData($item, $tableKey);
            } else if ($item['modify_flag'] == 2) {
                if (!isset($item['field_name']) && !$item['field_name']) {
                    return ['code' => ['0x016002', 'fields']];
                }

                if (!isset($item['field_code']) && !$item['field_code']) {
                    return ['code' => ['0x016016', 'fields']];
                }

                // if (app($this->fieldsRepository)->hasFieldsByName($item['field_name'], $tableKey, $item['field_id'])) {
                //     return ['code' => ['0x016015', 'fields']];
                // }

                $update[$item['field_id']] = $this->getFormFieldsData($item, $tableKey, true);
            }
        }
        $module = $this->getTableConfig($tableKey, 'module');

        if (!$this->createTableAndEntity($tableKey, $module)) {
            return ['code' => ['0x016001', 'fields']];
        }

        $addErrors = $updateErrors = $deleteErrors = 0;

        if (!empty($add)) {
            foreach ($add as $data) {
                if (!$this->addField($data, $tableKey, $module)) {
                    $addErrors++;
                }
            }
        }

        if (!empty($update)) {
            foreach ($update as $fieldId => $data) {
                if (!app($this->fieldsRepository)->updateFields($data, $fieldId, $module)) {
                    $updateErrors++;
                }
            }
        }

        if (!empty($delete)) {
            foreach ($delete as $fieldId => $fieldCode) {
                if (!$this->deleteFields($fieldId, $fieldCode, $tableKey, $module)) {
                    $deleteErrors++;
                }
            }
        }

        if ($addErrors == 0 && $updateErrors == 0 && $deleteErrors == 0) {
            return true;
        }

        // return ['code' => ['0x016018', 'fields']];
    }

    private function addField($fieldData, $tableKey, $module)
    {
        if (!$fieldId = app($this->fieldsRepository)->insertFields($fieldData, $module)) {
            return false;
        }

        if (!app($this->fieldsRepository)->addColumn($fieldData, $tableKey)) {
            app($this->fieldsRepository)->deleteFields($fieldId, $module);
            return false;
        }

        return true;
    }

    /**
     * @删除自定义字段
     * @param type $fieldId
     * @return json | 成功与否
     */
    private function deleteFields($fieldId, $fieldCode, $tableKey, $module)
    {
        if (app($this->fieldsRepository)->deleteFields($fieldId, $module)) {
            return app($this->fieldsRepository)->deleteColumn($fieldCode, $tableKey);
        }

        return false;
    }

    private function getFormFieldsData($data, $moduleKey, $isUpdate = false)
    {
        if ($isUpdate) {
            $fieldData = [
                'field_name'      => $data['field_name'],
                'field_search'    => $this->defaultValue('field_search', $data, 1),
                'field_list_show' => $this->defaultValue('list_show', $data, 0),
                'field_options'   => $this->defaultValue('field_options', $data, ''),
                'field_explain'   => $this->defaultValue('field_explain', $data, ''),
                'field_sort'      => $this->defaultValue('field_sort', $data, 0),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];
        } else {
            $fieldData = [
                'field_table_key' => $moduleKey,
                'field_code'      => $data['field_code'],
                'field_name'      => $data['field_name'],
                'field_type'      => $this->defaultValue('field_type', $data, 0),
                'field_search'    => $this->defaultValue('field_search', $data, 1),
                'field_list_show' => $this->defaultValue('list_show', $data, 0),
                'field_options'   => $this->defaultValue('field_options', $data, ''),
                'field_explain'   => $this->defaultValue('field_explain', $data, ''),
                'field_sort'      => $this->defaultValue('field_sort', $data, 0),
                'is_system'       => $this->defaultValue('is_system', $data, 0),
                'created_at'      => date('Y-m-d H:i:s'),
            ];
        }

        return $fieldData;
    }

    /**
     * @获取自定义字段详情
     * @param type $fieldId
     * @return json | 自定义字段详情
     */
    public function showFields($fieldId, $tableKey)
    {
        if ($fieldId == 0) {
            return ['code' => ['0x016007', 'fields']];
        }

        $module = $this->getTableConfig($tableKey, 'module');

        return app($this->fieldsRepository)->showFields($fieldId, $module);
    }

    public function createFieldsTable($moduleName)
    {
        if ($moduleName == '') {
            return ['code' => ['0x016012', 'fields']];
        }

        if (app($this->fieldsRepository)->createFieldsTable($moduleName)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * @创建自定义表和实体（new）
     * @return boolean
     */
    private function createTableAndEntity($tableKey, $module)
    {
        if (!$this->createSubEntity($tableKey, $module)) {
            return false;
        }

        if (!app($this->fieldsRepository)->createEmptySubTable($tableKey)) {
            return false;
        }

        return true;
    }

    /**
     * 新建子表实体类（new）
     *
     * @param type $tableKey
     * @param type $module
     * @return boolean
     */
    public function createSubEntity($tableKey, $module)
    {
        $moduleName = $this->getModuleFolderName($module);

        $entityPath = base_path('app/EofficeApp/' . $moduleName . '/Entities/');

        $entityName = $this->getEntityName($tableKey);

        $fullEntityName = $entityPath . $entityName . '.php';

        if (file_exists($fullEntityName)) {
            return true;
        }

        $handle = fopen($fullEntityName, 'w+');

        fwrite($handle, "<?php
        namespace App\EofficeApp\\" . $moduleName . "\Entities;
        use App\EofficeApp\Base\BaseEntity;
        class " . $entityName . "  extends BaseEntity
        {
            public \$table          = '" . $tableKey . "_sub';
        }");

        fclose($handle);

        return true;
    }

    public function getTableConfig($tableKey, $field = '')
    {
        static $modules;

        if (isset($modules[$tableKey])) {
            if ($field) {
                return $modules[$tableKey]->{$field};
            }
            return $modules[$tableKey];
        }
        $config = app($this->customTableConfigRepository)->getConfigInfo($tableKey);

        $modules[$tableKey] = $config;
        if ($field) {
            return $config->{$field};
        }

        return $config;
    }

    /**
     * 获取驼峰模块名称
     * @return 驼峰模块名称
     */
    private function getModuleFolderName($module)
    {
        $moduleName = '';

        foreach (explode('_', $module) as $value) {
            $moduleName .= ucfirst($value);
        }

        return $moduleName;
    }

    /**
     * @获取实体名称
     * @return string 实体名称
     */
    private function getEntityName($tableKey)
    {
        $entityName = '';

        foreach (explode('_', $tableKey) as $value) {
            $entityName .= ucfirst($value);
        }

        return $entityName . 'SubEntity';
    }

    /**
     * @获取自定义字段列表
     * @param type $param
     * @return array | 自定义字段列表
     */
    public function listFields($param, $tableKey)
    {
        $fieldsMap = [];
        if (strstr($tableKey, "project")) {
            $fields = app($this->fieldsRepository)->listCustomFields($tableKey, ['search' => ['field_hide' => [0], 'is_system' => [0], 'field_directive' => ['tabs', '!=']]]);
        } else {
            $fields = app($this->fieldsRepository)->listCustomFields($tableKey, ['search' => ['field_hide' => [0], 'field_directive' => ['tabs', '!=']]]);
        }

        if (count($fields) > 0) {
            foreach ($fields as $field) {
                $fieldsMap[$field->field_code] = $field;
            }
        }

        return $fieldsMap;
    }

    /**
     * @获取列表显示字段
     * @return array | 字段列表
     */
    public function getAllShowFields($tableKey)
    {
        $module = $this->getTableConfig($tableKey, 'module');
        return app($this->fieldsRepository)->listFields(['search' => ['field_list_show' => [1]]], $tableKey, $module);
    }

    /**
     * @获取可搜索字段
     * @return array | 字段类别
     */
    public function getAllSearchFields($tableKey)
    {
        $module = $this->getTableConfig($tableKey, 'module');
        return app($this->fieldsRepository)->listFields(['search' => ['field_search' => [1]]], $tableKey, $module);
    }

    public function getAllVerifyFields($tableKey = '')
    {
        return [];
    }

    /**
     * 20170922 dp，增加service里对函数 createEmptyCustomDataTable 的封装，用在 ProjectSeeder.php 里调用
     * @param  [type] $tableKey [description]
     * @return [type]           [description]
     */
    public function createEmptyCustomDataTable($tableKey)
    {
        return app($this->fieldsRepository)->createEmptyCustomDataTable($tableKey);
    }

    /**
     * 20170922 dp，增加service里对函数 addCustomColumn 的封装，用在 ProjectSeeder.php 里调用
     * @param  [type] $tableKey [description]
     * @return [type]           [description]
     */
    public function addCustomTableColumn($data, $tableKey)
    {
        return app($this->fieldsRepository)->addCustomColumn($data, $tableKey);
    }

    //获取自定义菜单列表
    public function getCustomMenuList($param)
    {
        $parent = app($this->fieldsRepository)->getCustomMenuList($param);
        $data   = [];
        foreach ($parent as $k => $v) {
            $menu_name = trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
            $data[]    = ['id' => $v->menu_code, 'title' => $menu_name, 'module_type' => 'system_custom', 'isSystemCustom' => true, 'menu_parent' => $v->menu_parent];
        }
        return $data;
    }
    //获取自定义页面列表
    public function getCustomMenuParent($param)
    {
        $parent = app($this->fieldsRepository)->getParent($param);
        $data   = [];
        foreach ($parent as $k => $v) {
            $title    = '';
            $menuInfo = app($this->menuRepository)->getDetail($v->menu_parent);
            if ($menuInfo) {
                $title = mulit_trans_dynamic("menu.menu_name.menu_" . $menuInfo->menu_id);
            }
            $data[] = ['id' => $v->menu_parent, 'title' => $title, 'module_type' => 'custom_page', 'isSystemCustom' => true, 'menu_parent' => $v->menu_parent];
        }
        return $data;
    }

    public function getCustomMenuChild($data)
    {
        $parent_id = isset($data['parent_id']) ? $data['parent_id'] : '';

        $result = [];
        if (!empty($parent_id)) {
            //针对项目特殊处理
            $config = config('flowoutsend.from_type');
            if ($config && isset($config[$parent_id])) {
                return [['id' => $config[$parent_id]['id'], 'title' => trans("outsend.from_type_" . $parent_id . "_id"), 'baseId' => $config[$parent_id]['baseId'], 'baseFiles' => trans("outsend.from_type_" . $parent_id . "_base_files")]];
            }
            //正常取下级菜单
            $child = app($this->fieldsRepository)->getMenu($parent_id);
            foreach ($child as $k => $v) {
                if ($v->is_dynamic == 2) {
                    $title    = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $v->menu_code);
                    $result[] = ['id' => $v->menu_code, 'title' => $title];
                }
            }
        }
        return $result;

    }

    /**
     * 流程外发获取模块字段
     * 
     * @return array
     */
    public function getCustomFields($data)
    {
        $result          = [];
        $field_table_key = isset($data['key']) ? $data['key'] : '';
        if (!$field_table_key) {
            return [];
        }

        // 先针对项目模块特殊处理
        $config = config('flowoutsend.from_type');
        if ($config) {
            $data_handle_type = $data['data_handle_type'] ?? 0;
            $projectNoUpdateFields = $data_handle_type ? config('flowoutsend.custom_module.project')['no_update_fields'] : [];
            // 寻找配置文件中是否有需要特殊处理的菜单
            foreach ($config as $menu => $menuConfig) {
                if ($menuConfig['id'] == $field_table_key) {
                    return $result = $this->getFromTypeFieldsForFlowOutSend($menu, $projectNoUpdateFields, $data_handle_type, $menuConfig);
                }
            }
        }

        // 其他模块处理
        $fields = app($this->fieldsRepository)->getCustomFields($field_table_key);
        if (!empty($fields)) {
            $config = config('flowoutsend.module');
            if (isset($config[$field_table_key])) {
                // 调用处理函数，格式化返回参数
                $result = app($this->formModelingService)->handleFieldsParams($field_table_key, $fields);
            } else {
                $result[0]['hasChilen'] = [];
                $result[0]['id']        = '';
                $result[0]['title']     = trans('fields.Custom_Fields');
                foreach ($fields as $k => $v) {
                    $field_options = json_decode($v->field_options);
                    $type          = $field_options->type;

                    //当前字段是否为必填
                    $validate = $field_options->validate ?? 'empty';
                    $is_required = ($validate == 'empty') ? 0 : ($validate->required ?? 0);

                    if ($type != "detail-layout" && !isset($field_options->parentField)) {
                        $result[0]['hasChilen'][] = ['id' => $v->field_code, 'title' => mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code), 'type' => $type, 'parent_id' => '', 'is_required' => $is_required];
                    }
                }
                foreach ($fields as $k => $v) {
                    $field_options = json_decode($v->field_options);
                    $type          = $field_options->type;
                    if ($type == "detail-layout" && !isset($field_options->parentField)) {
                        $fileName     = $v->field_name;
                        $detailLayout = ['id' => $v->field_code, 'title' => mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code) . "(" . trans('fields.Detailed_layout') . ")", 'type' => $type, 'parent_id' => '', 'is_required' => 0];
                        array_push($result[0]['hasChilen'], $detailLayout);
                        foreach ($fields as $key => $value) {
                            $field_options = json_decode($value->field_options);
                            $child_type    = $field_options->type;

                            //当前字段是否为必填
                            $validate = $field_options->validate ?? 'empty';
                            $is_required = ($validate == 'empty') ? 0 : ($validate->required ?? 0);

                            if (isset($field_options->parentField) && $field_options->parentField == $v->field_code) {
                                $child = ['id' => $value->field_code, 'title' => mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $value->field_code), 'type' => $child_type, 'parent_id' => $v->field_code, 'is_required' => $is_required];
                                array_push($result[0]['hasChilen'], $child);
                            }
                        }

                    }
                }
            }
        }
        return $result;

    }
    public function getSystemCustomFields($data)
    {
        $result          = [];
        $field_table_key = isset($data['key']) ? $data['key'] : '';

        if (!empty($field_table_key)) {

            $fields = app($this->fieldsRepository)->getCustomFields($field_table_key);
            if (!empty($fields)) {

                // 调用样式统一处理函数
                $result = app($this->formModelingService)->handleFieldsParams($field_table_key, $fields);

//                foreach ($fields as $k => $v) {
//                    $field_options = json_decode($v->field_options);
//                    $type          = $field_options->type;
//                    if ($type != "detail-layout" && !isset($field_options->parentField)) {
//                        $field_name = mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code);
//                        $result[]   = ['id' => $v->field_code, 'title' => $field_name, 'type' => $type, 'parent_id' => ''];
//                    }
//                }
//                foreach ($fields as $k => $v) {
//                    $field_options = json_decode($v->field_options);
//                    $type          = $field_options->type;
//                    if ($type == "detail-layout" && !isset($field_options->parentField)) {
//                        $fileName     = mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code);
//                        $detailLayout = ['id' => $v->field_code, 'title' => $fileName . "(" . trans("fields.Detailed_layout") . ")", 'type' => $type, 'parent_id' => ''];
//                        array_push($result, $detailLayout);
//                        foreach ($fields as $key => $value) {
//                            $field_options = json_decode($value->field_options);
//                            $child_type    = $field_options->type;
//                            if ($child_type != "detail-layout" && isset($field_options->parentField) && $field_options->parentField == $v->field_code) {
//                                $fileName = mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $value->field_code);
//                                $child    = ['id' => $value->field_code, 'title' => $fileName, 'type' => $child_type, 'parent_id' => $v->field_code];
//                                array_push($result, $child);
//                            }
//                        }
//
//                    }
//                }
            }
        }
        return $result;

    }
    public function getCustomField($data)
    {
        $result          = [];
        $field_table_key = isset($data['key']) ? $data['key'] : '';

        $fields = app($this->fieldsRepository)->getCustomFields($field_table_key);

        if (!empty($fields)) {

            // 调用处理函数，格式化返回参数
            $result = app($this->formModelingService)->handleFieldsParams($field_table_key, $fields);

//            foreach ($fields as $k => $v) {
//                $field_options = json_decode($v->field_options);
//                $type          = $field_options->type;
//                if ($type != "detail-layout" && !isset($field_options->parentField)) {
//                    $result[] = ['id' => $v->field_code, 'title' => mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code), 'type' => $type, 'parent_id' => ''];
//                }
//            }
//
//            foreach ($fields as $k => $v) {
//                $field_options = json_decode($v->field_options);
//                $type          = $field_options->type;
//
//                if ($type == "detail-layout" && !isset($field_options->parentField)) {
//                    $fileName     = $v->field_name;
//                    $detailLayout = ['id' => $v->field_code, 'title' => mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $v->field_code) . "(明细布局)", 'type' => $type, 'parent_id' => ''];
//                    array_push($result, $detailLayout);
//                    foreach ($fields as $key => $value) {
//                        $field_options = json_decode($value->field_options);
//                        $child_type    = $field_options->type;
//
//                        if ($child_type != "detail-layout" && isset($field_options->parentField) && $field_options->parentField == $v->field_code) {
//
//                            $child = ['id' => $value->field_code, 'title' => mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $value->field_code), 'type' => $child_type, 'parent_id' => $v->field_code];
//                            array_push($result, $child);
//                        }
//                    }
//                }
//            }
        }
        return $result;
    }
    //获取明细控件父级
    public function getCustomParentField($data)
    {
        $result          = '';
        $field_table_key = isset($data['key']) ? $data['key'] : '';
        $field_code      = isset($data['field_code']) ? $data['field_code'] : '';
        if (!empty($field_table_key) && !empty($field_code)) {
            $fields = app($this->fieldsRepository)->custom_fields_table($field_table_key, $field_code);
            if (!empty($fields)) {
                $field_options = json_decode($fields->field_options);
                if (isset($field_options->parentField) && !empty($field_options->parentField)) {
                    $result = $field_options->parentField;
                }
            }
        }
        return $result;

    }
    public function exportFields($tableKey, $param = [], $own, $name = null)
    {
        $param['filter'] = $this->getFilterCondition($tableKey, $own, $param);
        $condition       = ['search' => ['field_hide' => [0], 'field_directive' => ['tabs', '!=']]];
        $fieldList       = $this->listCustomFields($condition, $tableKey);
        $headerArr       = [];
        $fieldsMap       = [];
        foreach ($fieldList as $key => $field) {
            $fields      = $this->transferFieldOptions($field, $tableKey);
            $fieldOption = json_decode($fields->field_options, true);
            $type        = $fieldOption['type'];
            if ($type == 'upload' || $type == 'detail-layout' || $type == 'tabs' || isset($fieldOption['parentField'])) {
                continue;
            }
            $headerArr[$field->field_code] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $field->field_code), 'style' => ['width' => '15']];
            $fieldsMap[$field->field_code] = $field;
            $directive                     = $field->field_directive;
            if ($type == 'checkbox' || $directive == 'radio' || $directive == 'select' || $directive == 'selector') {
                $tempList   = $this->{$directive . "DirectiveExcel"}($field);
                $tempResult = [];
                if (!empty($tempList) && is_array($tempList)) {
                    array_map(function ($item) use (&$tempResult) {
                        list($key, $value) = array_values((array) $item);
                        $tempResult[$key]  = $value;
                    }, $tempList);
                }
                $dataLists[$field->field_code] = $tempResult;
            }
        }
        $list = app($this->fieldsRepository)->getCustomDataList($tableKey, $param);
        //解析数据列值
        if (!empty($fieldsMap)) {
            $dataShow = [];
            $temp_arr = [];
            foreach ($list as $itemKey => $item) {
                $originalData = clone $item; //复制原始数据，避免查找到已解析好的数据
                foreach ($fieldsMap as $key => $field) {
                    $directive = $field->field_directive;
                    if ($directive == "area") {

                        $temp_arr[$key] = $this->{$directive . 'Directive'}($item, $field, $tableKey);
                    } else if ($directive == 'select' || $directive == 'selector') {
                        $temp_arr[$key] = $this->{$directive . 'Directive'}($originalData, $field);

                    } else {
                        $temp_arr[$key] = $item->$key;
                        $value          = json_decode($item->$key);
                        if (is_array($value)) {
                            if (isset($dataLists[$field->field_code])) {
                                foreach ($value as $i => $v) {
                                    if (isset($dataLists[$field->field_code][$v])) {
                                        $value[$i] = $dataLists[$field->field_code][$v];
                                    } else {
                                        $value = '';
                                    }
                                }
                            }
                            if (empty($value)) {
                                $resultValue = '';
                            } else {
                                if (is_array($value)) {
                                    $resultValue = implode(',', $value);
                                }

                            }

                        } else {
                            if (isset($dataLists[$field->field_code])) {
                                if (isset($dataLists[$field->field_code][$item->$key])) {
                                    $resultValue = $dataLists[$field->field_code][$item->$key];
                                } else {
                                    $resultValue = '';
                                }
                            } else {
                                $resultValue = $item->$key;
                            }
                        }
                        $temp_arr[$key] = $resultValue;
                        if (empty($item->{$key}) && $item->{$key} !== 0 && $item->{$key} !== "0") {
                            $temp_arr[$key] = '';
                        }
                    }
                    if ($directive == "number") {
                        $field_options = json_decode($field->field_options);
                        $format        = $field_options->format;
                        if (!empty($format)) {
                            if (isset($format->amountInWords) && $format->amountInWords == 1) {
                                if (!empty($item->{$key})) {
                                    $number         = floatval($item->{$key});
                                    $temp_arr[$key] = $this->cny($number);
                                }
                            }
                            if (isset($format->thousandSeparator) && $format->thousandSeparator == 1 && (!isset($format->amountInWords) || (isset($format->amountInWords) && ($format->amountInWords != 1)))) {

                                if (!empty($item->{$key})) {
                                    if (isset($format->decimalPlacesDigit) && !empty($format->decimalPlacesDigit)) {
                                        $temp_arr[$key] = number_format($item->{$key}, $format->decimalPlacesDigit);
                                    } else {
                                        $temp_arr[$key] = number_format($item->{$key});
                                    }
                                }
                            }
                        }
                    } else if ($directive == "editor") {
                        $temp_arr[$key] = strip_tags($item->{$key});
                        if (strpos($temp_arr[$key], '&nbsp;') !== false) {
                            $temp_arr[$key] = str_replace("&nbsp;", " ", "$temp_arr[$key]");
                        }
                    } else if (in_array($directive, ['date', 'time', 'datetime'])) {
                        $temp_arr[$key] = $this->{$directive . 'Directive'}($item->{$key}, $field);
                    }
                }
                $dataShow[] = $temp_arr;
            }
        }
        $result = [
            'sheetName' => $name === null ? trans('fields.customTemplateName') : $name,
            'header'    => $headerArr,
            'data'      => $dataShow,
        ];
        return $result;

    }
    public function getImportFields($tableKey, $param = [], $name = "")
    {
        $condition            = ['search' => ['field_hide' => [0], 'field_directive' => ['tabs', '!=']]];
        $fieldList            = $this->listCustomFields($condition, $tableKey);
        $excelLists           = $headerArr           = [];
        $importDisabledFields = config('customfields.importDisabledFields.' . $tableKey);
        foreach ($fieldList as $key => $field) {
            if (!empty($importDisabledFields)) {
                if (in_array($field->field_code, $importDisabledFields)) {
                    continue;
                }
            }
            $field       = $this->transferFieldOptions($field, $tableKey);
            $dataLists   = [];
            $fieldOption = json_decode($field->field_options, true);
            $type        = $fieldOption['type'];
            if ($type == 'upload' || $type == 'area' || $type == 'detail-layout' || $type == 'tabs' || isset($fieldOption['parentField'])) {
                continue;
            }
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
            $headerArr[$field->field_code] = ['data' => $headerText, 'style' => ['width' => '15']];
            $directive                     = $field->field_directive;
            if ($directive == "radio" || $directive == "checkbox") {
                $dataLists['header'] = [
                    'id'    => 'ID',
                    'title' => trans("fields.name"),
                ];
                $dataLists['sheetName'] = $field->field_name;
                $dataLists['data']      = $this->{$directive . "DirectiveExcel"}($field);
                $excelLists[]           = $dataLists;
            }
            if ($directive == 'selector') {
                $header = [];
                $header = $this->selectorDirectiveExcel($field, 1);
                if (empty($header)) {
                    $header = ['id', 'title'];
                }
                $dataLists['header'] = [
                    $header[0] => 'ID',
                    $header[1] => trans("fields.name"),
                ];
                $dataLists['sheetName'] = $field->field_name;
                $dataLists['data']      = $this->{$directive . "DirectiveExcel"}($field);
                $excelLists[]           = $dataLists;
            }
            if ($directive == 'select') {
                $header = [];
                $header = $this->selectDirectiveExcel($field, 1);
                if (empty($header)) {
                    $header = ['id', 'title'];
                }
                $dataLists['header'] = [
                    $header[0] => 'ID',
                    $header[1] => trans("fields.name"),
                ];
                $dataLists['sheetName'] = $field->field_name;
                $dataLists['data']      = $this->{$directive . "DirectiveExcel"}($field);
                $excelLists[]           = $dataLists;
            }
        }
        if (empty($name)) {
            $name = trans('fields.Custom_field_import_template');
        }
        $result = [
            '0' => [
                'sheetName' => $name,
                'header'    => $headerArr,
            ],

        ];
        if (!empty($excelLists)) {
            foreach ($excelLists as $key => $arr) {
                $result[] = $arr;
            }
        }
        return $result;

    }

    public function importCustomData($tableKey, $datas, $params)
    {
        $table          = app($this->fieldsRepository)->getCustomDataTableName($tableKey);
        $params['type'] = isset($params['type']) ? $params['type'] : 1;
        if ($params['type'] == 3) {
            //新增数据并清除原有数据
            DB::table($table)->delete();
        }
        foreach ($datas as $key => $data) {
            if (!isset($data['importReason'])) {
                $parseData   = $this->parseCustomFormData($tableKey, $data, 'true');
                $datas[$key] = $parseData['main'];
                if (isset($data['importResult'])) {
                    $datas[$key]['importResult'] = $data['importResult'];
                }
                if (isset($data['data_id'])) {
                    $parseData['main']['data_id'] = $data['data_id'];
                }
                if ($params['type'] == 1 || $params['type'] == 3) {
                    $dataId = app($this->fieldsRepository)->addCustomData($tableKey, $parseData['main'], $params);
                    if (!$dataId) {
                        $datas[$key]['importResult'] = importDataFail();
                        $datas[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                    }
                } else if ($params['type'] == 2) {
                    $primaryKey  = isset($params['primaryKey']) ? $params['primaryKey'] : '';
                    $data        = $parseData['main'];
                    $primaryData = isset($data[$primaryKey]) ? $data[$primaryKey] : '';
                    if (!empty($primaryKey) && $primaryData !== '') {
                        $dataId = app($this->fieldsRepository)->updateCustomData($tableKey, $data, $primaryKey, $primaryData);
                        if (!$dataId) {
                            $datas[$key]['importResult'] = importDataFail();
                            $datas[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                        }
                    }
                } else if ($params['type'] == 4) {
                    $data        = $parseData['main'];
                    $primaryKey  = isset($params['primaryKey']) ? $params['primaryKey'] : '';
                    $primaryData = isset($data[$primaryKey]) ? $data[$primaryKey] : '';
                    $oldData     = DB::table($table)->where($primaryKey, $primaryData)->first();
                    if (!empty($oldData)) {
                        $dataId = app($this->fieldsRepository)->updateCustomData($tableKey, $data, $primaryKey, $primaryData);
                    } else {
                        $dataId = app($this->fieldsRepository)->addCustomData($tableKey, $parseData['main'], $params);
                    }
                    if (!$dataId) {
                        $datas[$key]['importResult'] = importDataFail();
                        $datas[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
                    }
                }
            }
        }
        return ['data' => $datas];

    }

    public function importDataFilter($tableKey, $data, $params)
    {
        $fieldsMap = $this->getShowFieldsMap($tableKey);
        foreach ($fieldsMap as $k => $v) {
            if (isset($v->field_options)) {
                $field_options = json_decode($v->field_options);
                if (isset($v->field_directive) && $v->field_directive == "area") {
                    continue;
                }
                if (isset($field_options->parentField) && !empty($field_options->parentField)) {
                    continue;
                }
                if (isset($field_options->validate)) {
                    $validate = $field_options->validate;
                    if (isset($validate->required) && $validate->required == 1) {
                        if (!isset($data[$k])) {
                            return mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $v->field_code) . trans("fields.required_field");
                        }
                        if ($data[$k] === '' || $data[$k] === []) {
                            return mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $v->field_code) . trans("fields.required_field");
                        }

                    }

                }
            }
            if (isset($v->field_directive) && in_array($v->field_directive, ['datetime', 'date', 'time'])) {
                if (isset($data[$k]) && !empty($data[$k]) && !$this->checkDate($v->field_directive, $data[$k])) {
                    return mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $v->field_code) . trans("fields.0x016025");
                }
            }

        }
    }

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
        }catch(\Exception $e){
            return false;
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
    public function getFiledName($field_table_key, $field_code)
    {
        $customfields = app($this->fieldsRepository)->custom_fields_table($field_table_key, $field_code);
        if (!empty($customfields)) {
            return mulit_trans_dynamic("custom_fields_table.field_name." . $field_table_key . "_" . $customfields->field_code);
        }
        return '';
    }
    public function getRootFiledName($menu)
    {
        $customfields = app($this->fieldsRepository)->custom_menu_table($menu);
        if (!empty($customfields)) {
            return mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $customfields->menu_code);
        }
        return '';
    }
    //获取自定义字段定时提醒内容
    public function getReminds()
    {
        if (!Schema::hasTable('custom_reminds')) {
            return false;
        }
        $reminds  = $this->getRedisReminds();
        $param    = [];
        $sendData = [];
        foreach ($reminds as $remind) {

            $remind = json_decode($remind);
            if ($remind->field_hide == 1) {
                continue;
            }
            $field_code = $remind->field_code;
            $type       = $remind->type;
            if (empty($remind->option)) {
                continue;
            }
            $option         = json_decode($remind->option);
            $reminds_select = isset($remind->reminds_select) ? $remind->reminds_select : '';

            $sendMethod = [];
            if (!empty($reminds_select)) {
                $reminds_select = explode(",", $reminds_select);
                foreach ($reminds_select as $select) {
                    $sendMethod[] = app($this->fieldsRepository)->tranferRemindName($select);
                }
            }
            $date            = date("Y-m-d", time());
            $tableKey        = $remind->field_table_key;
            $custom_menu     = app($this->fieldsRepository)->custom_menu_table($tableKey);
            $sms_menu        = $this->transferMenu($custom_menu);
            $primaryKey      = $this->getDataTablePrimaryKeyName($tableKey);
            $field_directive = $remind->field_directive;
            $lists           = app($this->fieldsRepository)->getCustomDataList($tableKey, $param);
            if (strpos($tableKey, "project_value") !== false) {
                $project_value                    = explode("_", $tableKey);
                $project_type                     = isset($project_value[2]) ? $project_value[2] : '';
                $search['search']['manager_type'] = [$project_type];
                $lists                            = app($this->fieldsRepository)->getUniqueCustomData($tableKey, $search);
                $primaryKey                       = "manager_id";
            } elseif ($tableKey == 'archives_appraisal') {
                $primaryKey = "appraisal_data_id";
            }
            $extra  = json_decode($remind->extra);
            $target = isset($extra->target) ? $extra->target : '';
            if ($target != "relation") {
                $toUser = $this->transferUser($remind->extra, $remind->field_table_key, $lists);
            }
            $contentParam = ['field_code' => $field_code, 'tableKey' => $tableKey, 'content' => $remind->content, 'directive' => $field_directive];
            // $content      = $this->transferContent($contentParam, $lists);
            $remind_date = [];
            foreach ($lists as $list) {
                if (isset($list->$field_code)) {
                    $content = $this->transferContent($contentParam, $list);

                    if ($target == "relation") {
                        $toUser = $this->transferUser($remind->extra, $remind->field_table_key, $list);
                    }

                    $data   = $list->$field_code;
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
                                $hour          = isset($time->hour) ? $time->hour : 0;
                                $minute        = isset($time->minute) ? $time->minute : 0;
                                $day           = isset($time->day) ? $time->day : 0;
                                $remind_time   = strtotime($origin_time . "-" . $day . "days -" . $hour . "hours -" . $minute . "minutes");
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
                                $hour          = isset($time->hour) ? $time->hour : 0;
                                $minute        = isset($time->minute) ? $time->minute : 0;
                                $day           = isset($time->day) ? $time->day : 0;
                                $remind_time   = strtotime($origin_time . "+" . $day . "days +" . $hour . "hours +" . $minute . "minutes");
                                $remind_date[] = ['date' => date("Y-m-d H:i", $remind_time), 'params' => $params, 'content' => $content, 'user' => $toUser];
                            }
                        } else {
                            if (!empty($option)) {
                                foreach ($option as $k => $time) {
                                    $option_type = $time->type;
                                    if ($option_type == "day") {
                                        $remind_time   = ['function_name' => 'dailyAt', 'param' => $data];
                                        $remind_date[] = ['date' => $remind_time, 'params' => $params, 'content' => $content, 'user' => $toUser];
                                    } else if ($option_type == "week") {
                                        $week        = $time->week;
                                        $actual_week = $this->transferTime($week);
                                        $remind_time = ['function_name' => 'weekly', 'param' => $actual_week . "," . $data, 'user' => $toUser];

                                        $remind_date[] = ['date' => $remind_time, 'params' => $params, 'content' => $content, 'user' => $toUser];
                                    } else if ($option_type == "month") {
                                        $day           = $time->day;
                                        $remind_time   = ['function_name' => 'monthlyOn', 'param' => $day . "," . $data];
                                        $remind_date[] = ['date' => $remind_time, 'params' => $params, 'content' => $content, 'user' => $toUser];
                                    } else if ($option_type == "year") {
                                        $times         = explode(':', $data);
                                        $hour          = $times[0];
                                        $minute        = $times[1];
                                        $month         = $time->month;
                                        $day           = $time->day;
                                        $remind_time   = ['function_name' => 'cron', 'param' => $minute . "," . $hour . "," . $day . "," . $month . "," . "*"];
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
                    'sendMethod'  => $sendMethod,
                    'type'        => $type,
                    'sms_menu'    => $sms_menu,
                ];
            }
        }
        return $sendData;
    }

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
                default:
                    $sms_menu = $menu_parent;
            }
            return $sms_menu;
        }
    }

    public function transferContent($contentParam, $list)
    {
        $content    = $contentParam['content'];
        $tableKey   = $contentParam['tableKey'];
        $field_code = $contentParam['field_code'];
        $patten     = '/\#(\w+)\#/';
        preg_match_all($patten, $content, $variableName);
        $field_options_lang = [];
        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            foreach ($variableName[1] as $k => $v) {
                $customFields = app($this->fieldsRepository)->custom_fields_table($tableKey, $v);
                if(empty($customFields)){
                    continue;
                }
                $customFields = $this->transferFieldOptions($customFields, $tableKey, true);
                $directive    = $customFields->field_directive;
                switch ($directive) {
                    case "upload":
                        $result = $this->{$directive . 'Directive'}($list->{$v}, $customFields, $tableKey);
                        break;
                    case "area":
                    case 'select':
                    case 'selector':
                        $result = $this->{$directive . 'Directive'}($list, $customFields);
                        break;
                    default:
                        $result = $this->{$directive . 'Directive'}($list->{$v}, $customFields);
                }
                $content = str_replace($variableName[0][$k], $result, $content);
            }
        }
        return $content;
    }

    public function restoreContent($tableKey, $content)
    {
        $patten = '/\#(\w+)\#/';
        preg_match_all($patten, $content, $variableName);
        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            foreach ($variableName[1] as $k => $v) {
                $field_name = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $v);
                $field_name = "{" . $field_name . "}";
                $content    = str_replace($variableName[0][$k], $field_name, $content);

            }
        }
        return $content;
    }

    public function transferUser($extra, $tableKey, $value)
    {
        $extra  = json_decode($extra);
        $user   = [];
        $target = isset($extra->target) ? $extra->target : '';
        if ($target == "all") {
            $res  = app($this->userService)->getAllUserIdString();
            $user = explode(",", $res);
            return $user;
            //全部成员
        } else if ($target == "currentMenu") {
            //当前菜单
            $custom_menu_config = app($this->fieldsRepository)->custom_menu_table($tableKey);
            if ($custom_menu_config->is_dynamic == 1) {
                $menu_id = config('customfields.menu.' . $tableKey);
            } else {
                $menu_id = $tableKey;
                if ($custom_menu_config->menu_parent == "160") {
                    $menu_id = 161;
                }
            }
            $userMenus = app($this->userMenuService)->getMenuUser($menu_id);
            $user      = array_unique($userMenus);
            return $user;
        } else if ($target == "relation") {
            //来源字段值
            $relationField = $extra->relationField;
            $user          = [];
            if (isset($value->$relationField)) {
                $user_id = $value->$relationField;
                $user_id = json_decode($user_id, true);
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
            $definite   = $extra->definite;
            $dept_user  = [];
            $roles_user = [];
            $user       = [];
            if (isset($definite->depts) && !empty($definite->depts)) {
                $dept            = $definite->depts;
                $param['search'] = ["dept_id" => [$dept, "in"]];
                $res             = app($this->userService)->getAllUserIdString($param);
                $dept_user       = explode(",", $res);
            }

            if (isset($definite->roles) && !empty($definite->roles)) {
                $roles           = $definite->roles;
                $param['search'] = ["role_id" => [$roles, "in"]];
                $res             = app($this->userService)->getAllUserIdString($param);
                $roles_user      = explode(",", $res);
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

    public function parseOutsourceData($data, $tableKey)
    {
        if (isset($data['data']) && !empty($data['data'])) {
            foreach ($data['data'] as $k => $v) {
                if ($v === "") {
                    unset($data['data'][$k]);
                }
            }
        }
        $customFields = app($this->fieldsRepository)->getCustomFields($tableKey);
        if (count($customFields) > 0) {
            foreach ($customFields as $field) {
                // if ($field->field_directive == "select") {
                //     if (isset($data["$field->field_code"])) {
                //         $name                       = $data["$field->field_code"];
                //         $value                      = $this->outsourceSelect($name, $field);
                //         $data["$field->field_code"] = $value;
                //     }

                // }

                // if ($field->field_directive == "radio") {
                //     if (isset($data["$field->field_code"])) {
                //         $name                       = $data["$field->field_code"];
                //         //$value                      = $this->outsourceRadioDirective($name, $field);
                //         $data["$field->field_code"] = $name;
                //     }

                // }
                if ($field->field_directive == "selector") {
                    $fieldOption    = json_decode($field->field_options, true);
                    $selectorConfig = $fieldOption['selectorConfig'];
                    $multiple       = (isset($selectorConfig['multiple']) && $selectorConfig['multiple'] == 1) ? true : false;
                    if ($multiple) {
                        if (isset($data["$field->field_code"])) {
                            $value                      = $data["$field->field_code"];
                            $value                      = explode(",", $value);
                            $data["$field->field_code"] = $value;
                        }
                    }

                }
                if ($field->field_directive == "area") {
                    $fieldOption = json_decode($field->field_options, true);
                    if (isset($data["$field->field_code"])) {
                        $value    = explode(",", $data["$field->field_code"]);
                        $relation = $fieldOption['relation'];
                        $arr      = [];
                        foreach ($relation as $k => $v) {
                            $file_code         = $v['field_code'];
                            $arr["$file_code"] = $value[$k];
                        }
                        $data["$field->field_code"] = $arr;
                    }

                }

                if ($field->field_directive == "checkbox") {
                    if (isset($data["$field->field_code"])) {
                        $name = $data["$field->field_code"];
                        if (!is_array($name)) {
                            $name = explode(",", $name);
                        }
                        //$value                      = $this->outsourceCheckboxDirective($name, $field);
                        $data["$field->field_code"] = $name;
                    }

                }
            }
        }

        return $data;
    }
    public function checkPermission($tableKey,$method="menu")
    {
       $menuId =  $this->tableKeyTransferMenu($tableKey,$method);
       $menus = app($this->userMenuService)->getUserMenus(own('user_id'));
       app($this->userMenuService)->clearCache();
       if(!is_array($menuId)){
            $menuId = [$menuId];
       }
       if(isset($menus['menu']) && !array_intersect($menuId,$menus['menu'])){
            return ['code' => ['0x009002', 'menu']];
       }
       return true;
    }

    /**
     * 获取流程外发中的项目外发配置
     * 
     * @param $menu 项目模块ID
     * @param $projectNoUpdateFields 不支持更新的字段
     * @param $data_handle_type 外发处理类型，0表示新增，1表示更新
     * 
     * @author zyx
     * @since 20200828 重构
     * 
     * @return array
     */
    public function getFromTypeFieldsForFlowOutSend($menu, $projectNoUpdateFields, $data_handle_type, $menuConfig) {
        array_push($projectNoUpdateFields, 'creator');
        //获取所有子菜单
        $childInfo = app($this->fieldsRepository)->getMenu($menu);
        if ($childInfo) {
            //获取所有字段 拼接父菜单 返回
            $_key        = 0;
            $resultArray = [];
            foreach ($childInfo as $child) {
                if ($menu == 160) {
                    if (
                        (strstr($child->menu_code, 'project_value') === false) && 
                        (strstr($child->menu_code, 'project_task_value') === false)
                    ) {
                        continue;
                    }
                    // 更新模式过滤任务子菜单
                    if ($data_handle_type && (strstr($child->menu_code, 'project_task_value') !== false)) {
                        continue;
                    }
                }
                $resultArray[$_key]['id']        = (strstr($child->menu_code, 'project_task_value') !== false) ? $menuConfig['additional']['id'] : $child->menu_code;
                $resultArray[$_key]['title']     = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $child->menu_code);
                $resultArray[$_key]['hasChilen'] = [];
                $childField                      = $this->getCustomField(['key' => $child->menu_code]);
                $tmpKey = 0;
                foreach ($childField as $i => $_value) {
                    // 20200330,zyx,外发更新模式，过滤不支持更新的字段
                    if ($data_handle_type && $projectNoUpdateFields && in_array($_value['id'], $projectNoUpdateFields)) {
                        continue;
                    }
                    // 160项目不显示creator
                    if (($menu == 160) && ($_value['id'] == 'creator')) {
                        continue;
                    }
                    $resultArray[$_key]['hasChilen'][$tmpKey]['parent_menu'] = (strstr($child->menu_code, 'project_task_value') !== false) ? 'additional' : $child->menu_code;
                    $resultArray[$_key]['hasChilen'][$tmpKey]['title']       = $_value['title'];
                    $resultArray[$_key]['hasChilen'][$tmpKey]['id']          = $_value['id'];
                    $resultArray[$_key]['hasChilen'][$tmpKey]['type']        = (strstr($child->menu_code, 'project_task_value') !== false) ? 'additional' : ($_value['type'] ?? '');
                    $resultArray[$_key]['hasChilen'][$tmpKey]['parent_id']   = $_value['parent_id'] ?? '';
                    $resultArray[$_key]['hasChilen'][$tmpKey]['rootTitle']   = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $child->menu_code);
                    // 增加是否必填参数
                    $resultArray[$_key]['hasChilen'][$tmpKey]['is_required'] = $_value['is_required'];
                    $tmpKey++;
                }
                // 如果有项目任务字段，先存标识
                if (strstr($child->menu_code, 'project_task_value') !== false) {
                    $projectTaskKey = $_key;
                    $projectTaskValue = $resultArray[$_key];
                }

                $_key++;
            }
            // 把项目任务字段放在最后
            if (isset($projectTaskKey)) {
                unset($resultArray[$projectTaskKey]);
                $resultArray = array_values($resultArray);
                $resultArray[] = $projectTaskValue;
            }
            // if (isset($menuConfig['additional']) && !empty($menuConfig['additional']) && !$data_handle_type) { // 20200330,zyx,增加数据处理模式判断，外发更新模式不返回additional配置
            //     $resultArray[$_key]['id']        = $menuConfig['additional']['id'];
            //     $resultArray[$_key]['title']     = trans('outsend.from_type_' . $menu . '.additional.title');
            //     $resultArray[$_key]['hasChilen'] = [];
            //     $i                               = 0;
            //     foreach ($menuConfig['additional']['fields'] as $_k => $_v) {
            //         // $_title                                             = trans('outsend.from_type_' . $thismenu . '.additional.fields.' . $_k . '.field_name');
            //         $_title                                             = is_array(trans('outsend.from_type_' . $menu . '.additional.fields.' . $_k)) ? trans('outsend.from_type_' . $menu . '.additional.fields.' . $_k . '.field_name') : trans('outsend.from_type_' . $menu . '.additional.fields.' . $_k);// 兼容多语言文件参数结构不一致的情况
            //         $resultArray[$_key]['hasChilen'][$i]['title']       = $_title;
            //         $resultArray[$_key]['hasChilen'][$i]['id']          = $_k;
            //         $resultArray[$_key]['hasChilen'][$i]['parent_menu'] = 'additional';
            //         $resultArray[$_key]['hasChilen'][$i]['type']        = 'additional';
            //         $resultArray[$_key]['hasChilen'][$i]['parent_id']   = '';
            //         $resultArray[$_key]['hasChilen'][$i]['rootTitle']   = $menuConfig['additional']['title'];

            //         // 是否必填参数
            //         if (is_array($_v) && isset($_v['required'])) {
            //             $resultArray[$_key]['hasChilen'][$i]['is_required'] = $_v['required'];
            //         } else {
            //             $resultArray[$_key]['hasChilen'][$i]['is_required'] = 0;
            //         }

            //         $i++;
            //     }
            // }
            return $resultArray;
        }
    }
}
