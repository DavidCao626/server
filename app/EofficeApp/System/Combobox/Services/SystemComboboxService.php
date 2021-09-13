<?php

namespace App\EofficeApp\System\Combobox\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Project\NewServices\ProjectService;
use DB;
use Lang;
use Illuminate\Support\Facades\Redis;
use Schema;

/**
 * 系统下拉表Service类:提供系统下拉表相关服务
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SystemComboboxService extends BaseService
{
    /**
     * 系统下拉表资源
     * @var object
     */
    private $systemComboboxRepository;

    private $FieldsRepository;
    private $MenuRepository;
    /**
     * 下拉表标签资源
     * @var object
     */
    private $systemComboboxTagRepository;

    /**
     * 下拉表字段资源
     * @var object
     */
    private $systemComboboxFieldRepository;

    public function __construct()
    {
        $this->systemComboboxRepository      = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxRepository';
        $this->FieldsRepository              = 'App\EofficeApp\System\CustomFields\Repositories\FieldsRepository';
        $this->formModelingRepository        = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
        $this->systemComboboxTagRepository   = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxTagRepository';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->projectSupplementService      = 'App\EofficeApp\Project\Services\ProjectSupplementService';
        $this->MenuRepository                = 'App\EofficeApp\Menu\Repositories\MenuRepository';
        $this->langService                   = 'App\EofficeApp\Lang\Services\LangService';
    }

    /**
     * 获取下拉表数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxList($param = [])
    {
        $param             = $this->parseParams($param);
        $param['combobox'] = 1;
        $data              = app($this->systemComboboxTagRepository)->getTagsList($param);
        // 此处代码目的是进行默认字段验证
        $defaultFieds = config('eoffice.combobox');
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $s[] = $v;
                if (isset($v['combobox']) && !empty($v['combobox'])) {
                    foreach ($v['combobox'] as $key => $val) {
                        if (in_array($val['combobox_identify'], $defaultFieds)) {
                            $data[$k]['combobox'][$key]['default'] = true;
                        } else {
                            $data[$k]['combobox'][$key]['default'] = false;
                        }
                        if (isset($val['combobox_name']) && !empty($val['combobox_name'])) {
                            $data[$k]['combobox'][$key]['combobox_name'] = mulit_trans_dynamic("system_combobox.combobox_name.combobox_" . $val['combobox_id']);
                        }
                        $data[$k]['combobox'][$key]['combobox_name_lang'] = app($this->langService)->transEffectLangs("system_combobox.combobox_name.combobox_" . $val['combobox_id'], true);
                    }
                }
                if (isset($v['tag_name']) && !empty($v['tag_name'])) {
                    $data[$k]['tag_name'] = mulit_trans_dynamic("system_combobox_tag.tag_name.combobox_tag_" . $v['tag_id']);
                }
                $data[$k]['combobox_name_lang'] = app($this->langService)->transEffectLangs("system_combobox_tag.tag_name.combobox_tag_" . $v['tag_id'], true);
            }
        }
        return $data;
    }

    /**
     * 添加下拉表(二级菜单)
     *
     * @param  array $input 下拉表数据
     *
     * @return int|array    添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createCombobox($input)
    {
        $data = [
            'combobox_name' => $input['combobox_name'],
            'tag_id'        => $input['tag_id'],
        ];

        $comboboxName = convert_pinyin($input['combobox_name']);

        $data['combobox_identify'] = sha1($comboboxName[0]);
        $data['combobox_pinyin']   = $comboboxName[0];
        $combobox_lang             = isset($input['combobox_name_lang']) ? $input['combobox_name_lang'] : '';
        if ($comboboxObj = app($this->systemComboboxRepository)->insertData($data)) {
            $commonid = $comboboxObj->combobox_id;
            if (!empty($combobox_lang) && is_array($combobox_lang)) {
                foreach ($combobox_lang as $key => $value) {
                    $langData = [
                        'table'      => 'system_combobox',
                        'column'     => 'combobox_name',
                        'lang_key'   => "combobox_" . $commonid,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            } else {
                $langData = [
                    'table'      => 'system_combobox',
                    'column'     => 'combobox_name',
                    'lang_key'   => "combobox_" . $commonid,
                    'lang_value' => $data['combobox_name'],
                ];

                app($this->langService)->addDynamicLang($langData);
            }
            $list = app($this->systemComboboxRepository)->getDetail($commonid);

            $newData['combobox_identify'] = $commonid;
            $newData['combobox_name']     = 'combobox_' . $commonid;
            if ($common_id = app($this->systemComboboxRepository)->updateData($newData, ['combobox_id' => $commonid])) {

                return $commonid;
            }

        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除下拉表
     *
     * @param   int     $id    下拉表id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建

     */
    public function deleteCombobox($id)
    {
        $fieldNum = app($this->systemComboboxFieldRepository)->getTotal(['search' => ['combobox_id' => [$id]]]);
        $data     = app($this->systemComboboxFieldRepository)->getComboboxFieldList($id);
        $data     = array_column($data, "field_id");

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                app($this->systemComboboxFieldRepository)->deleteById($value);
                remove_dynamic_langs('system_combobox_field.field_name.combobox_field_' . $value);
            }
            if (app($this->systemComboboxRepository)->deleteById($id)) {
                remove_dynamic_langs('system_combobox.combobox_name.combobox_' . $id);
                return true;
            }
        } else {
            if (app($this->systemComboboxRepository)->deleteById($id)) {
                remove_dynamic_langs('system_combobox.combobox_name.combobox_' . $id);
                return true;
            }
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取下拉表详情
     *
     * @param   int     $id    下拉表id
     *
     * @return  array          查询结果或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxDetail($id)
    {
        if ($result = app($this->systemComboboxRepository)->getDetail($id)) {
            return $result->toArray();
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑下拉表数据
     *
     * @param   array   $input 编辑数据
     * @param   int     $id    下拉表id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function updateCombobox($input, $id)
    {
        if (isset($input['combobox_name'])) {
            $data['combobox_name'] = $input['combobox_name'];

            $comboboxName = convert_pinyin($input['combobox_name']);

            $data['combobox_pinyin'] = $comboboxName[0];
        }

        if (isset($input['tag_id'])) {
            $data['tag_id'] = $input['tag_id'];
        }
        $combobox_lang = isset($input['combobox_name_lang']) ? $input['combobox_name_lang'] : '';
        if (app($this->systemComboboxRepository)->updateData($data, ['combobox_id' => $id])) {
            if (!empty($combobox_lang) && is_array($combobox_lang)) {
                foreach ($combobox_lang as $key => $value) {
                    $langData = [
                        'table'      => 'system_combobox',
                        'column'     => 'combobox_name',
                        'lang_key'   => "combobox_" . $id,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            } else {
                $langData = [
                    'table'      => 'system_combobox',
                    'column'     => 'combobox_name',
                    'lang_key'   => "combobox_" . $id,
                    'lang_value' => $input['combobox_name'],
                ];

                app($this->langService)->addDynamicLang($langData);
            }
            $list = app($this->systemComboboxRepository)->getDetail($id);

            $newData['combobox_name'] = 'combobox_' . $id;
            if (app($this->systemComboboxRepository)->updateData($newData, ['combobox_id' => $id])) {

                return true;
            }
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取下拉表字段
     *
     * @param   int   $id    下拉框id
     * @param   array $param 查询条件
     *
     * @return  array        查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getFieldsList($id, $param = [])
    {
        $param                          = $this->parseParams($param);
        $param['search']['combobox_id'] = [$id];
        $langTable                      = app($this->langService)->getLangTable(null);
        $param['lang_table']            = $langTable;
        $data                           = $this->response(app($this->systemComboboxFieldRepository), 'getFieldsListTotal', 'getFieldsList', $param);
        if (isset($data['list']) && !empty($data['list'])) {
            foreach ($data['list'] as $key => $value) {
                $comboboxTableName = get_combobox_table_name($value['combobox_id']);
                if (isset($value['field_name']) && !empty($value['field_name'])) {
                    $data['list'][$key]['field_name']         = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $value['field_id']);
                    $data['list'][$key]['combobox_name_lang'] = app($this->langService)->transEffectLangs($comboboxTableName . ".field_name.combobox_field_" . $value['field_id'], true);
                }
            }
        }
        return $data;
    }

    /**
     * 获取下拉表字段
     *
     * @param   int   $id    下拉框id
     * @param   array $param 查询条件
     *
     * @return  array        查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-14 创建
     */
    public function getAllFields($id, $params = [])
    {
        $this->parseIds($id);
        $param           = ['getAll' => true];
        $param['fields'] = ['field_id', 'field_name', 'field_value', 'is_default'];
        $allFiledsId     = app($this->systemComboboxRepository)->comboboxAllFieldsId();
        //获取所有combobox_id
        $fieldsId = [];
        foreach ($allFiledsId as $value) {
            $fieldsId[] = $value['combobox_id'];
        }

        if (is_numeric($id) && in_array($id, $fieldsId)) {
            $param['combobox_id'] = [$id];
        } else if (strpos($id, ',') !== false) {
            $param['fields'][]              = 'combobox_id';
            $ids                            = array_filter(explode(',', $id));
            $param['search']['combobox_id'] = [$ids, 'in'];
        } else {
            $param['combobox_identify'] = [$id];
        }

        $params = $this->parseParams($params);
        // 如果传了is_default参数，拼接到param上
        if (isset($params["search"]) && count($params["search"])) {
            if (isset($params["search"]["is_default"])) {
                $param["search"] = $params["search"];
            } else {
                $param["search"] = $params["search"];
            }
        }
        $param['page']  = isset($params['page']) ? $params['page'] : 0;
        $param['limit'] = isset($params['limit']) ? $params['limit'] : 10;
        $list           = app($this->systemComboboxFieldRepository)->getFieldsList($param);
        $total          = app($this->systemComboboxFieldRepository)->getFieldsListTotal($param);
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $comboboxTableName = get_combobox_table_name($list[$key]['combobox_id']);
                if (isset($val['field_name']) && !empty($val['field_name'])) {
                    $list[$key]['field_name'] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $val['field_id']);
                }
            }
        }
        if (!empty($ids)) {
            if (isset($this->origin_combobox_item)) {
                foreach ($this->origin_combobox_item as $v) {
                    $listNew[$v] = [];
                }
            } else {
                foreach ($ids as $v) {
                    $listNew[$v] = [];
                }
            }

            foreach ($list as $v) {
                $key = $v['combobox_id'];
                if (isset($this->origin_combobox_arr[$key])) {
                    $key = $this->origin_combobox_arr[$key];
                }
                $listNew[$key][] = $v;
            }
            return $listNew;
        }
        return ['list' => $list, 'total' => $total];
    }

    public function parseIds(&$id)
    {
        if (empty($id)) {
            return false;
        }

        $combobox       = [];
        $combobox['5']  = 'MARITAL_STATUS';
        $combobox['6']  = 'EDUCATIONAL';
        $combobox['7']  = 'POLITICAL_STATUS';
        $combobox['8']  = 'COMPANY_SCALE';
        $combobox['9']  = 'COMPANY_SCALE1';
        $combobox['10'] = 'CUSTOMER_TRADE';
        $combobox['11'] = 'CONTACT_TYPE';
        $combobox['12'] = 'BUSINESS_TYPE';
        $combobox['13'] = 'BUSINESS_SOURCE';
        $combobox['14'] = 'AGREEMENT_TYPE';
        $combobox['15'] = 'BUSINESS_STAGE';
        $combobox['16'] = 'CUSTOMER_SOURCE';

        $combobox['17'] = 'CUSTOMER_TYPE';
        $combobox['18'] = 'VEHICLE_TYPE';
        $combobox['19'] = 'DEPRECIATION_STAGE';
        $combobox['20'] = 'MAINTENANCE_TYPE';
        $combobox['21'] = 'MEETING_TYPE';
        $combobox['22'] = 'USE_TYPE';
        $combobox['23'] = 'GET_TYPE';
        $combobox['35'] = 'BUSINESS_STATUS';
        $combobox['41'] = 'KHSX';
        $combobox['53'] = 'PROJECT_DEGREE';
        $combobox['52'] = 'PROJECT_PRIORITY';
        $combobox['51'] = 'PROJECT_TYPE';

        if (is_numeric($id) || strpos($id, ',') !== false) {
            $str   = $id;
            $idArr = array_filter(explode(',', $str));
            foreach ($idArr as $item) {
                $this->origin_combobox_item[] = $item;
                if (isset($combobox[$item])) {
                    $origin = app($this->systemComboboxRepository)->getComboboxIdByIdentify($combobox[$item]);
                    if (!empty($origin)) {
                        $id                                 = str_replace($item, $origin, $id);
                        $this->origin_combobox_arr[$origin] = $item;
                    }
                }
            }
        }
    }

    /**
     * 添加下拉表字段
     *
     * @param   array $datas  下拉表字段数据
     *
     * @return  int|array     添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createField($datas)
    {
        foreach ($datas as $input) {
            if (!$input) {
                continue;
            }
            if (isset($input['field_name']) && empty($input['field_name']) && $input['field_name'] != '0') {
                return ['code' => ['0x015035', 'system']];
            }
            $data = [
                'field_name'  => $input['field_name'],
                'field_value' => isset($input['field_value']) ? $input['field_value'] : '',
                'field_order' => $input['field_order'],
                'combobox_id' => $input['combobox_id'],
            ];
            $field_lang       = isset($input['combobox_name_lang']) ? $input['combobox_name_lang'] : '';

            $handle           = isset($input['handle']) ? $input['handle'] : "";
            $comboboxIdentify = "";
            // 20170915-dp-如果新加的是项目类型的选项，那么，调用函数，增加此选项下属的系统固定字段
            if ($comboboxInfo = app($this->systemComboboxRepository)->getDetail($input['combobox_id'])) {
                $comboboxInfo     = $comboboxInfo->toArray();
                $comboboxIdentify = $comboboxInfo["combobox_identify"];
            }
            // if (!isset($input['handle'])) {
            //     continue;
            // }
            if ($handle == 'insert') {
                $data['field_value'] = app($this->systemComboboxFieldRepository)->getMax($input['combobox_id']);
                $comboboxObj         = app($this->systemComboboxFieldRepository)->insertData($data);
                $commonid            = $comboboxObj->field_id ?? '';
                $comboboxTableNames  = get_combobox_table_name($input['combobox_id']);
                $local = Lang::getLocale();
                if (!empty($field_lang) && is_array($field_lang)) {
                    foreach ($field_lang as $key => $value) {
                        if (!empty($field_lang[$local]) || $field_lang[$local] == '0') {
                            $langData = [
                                'table'      => $comboboxTableNames,
                                'column'     => 'field_name',
                                'lang_key'   => "combobox_field_" . $commonid,
                                'lang_value' => $value,
                            ];
                            // $local = $key; //可选
                            app($this->langService)->addDynamicLang($langData, $key);

                        } else if (empty($field_lang[$local])) {
                            return ['code' => ['0x015035', 'system']];
                        }
                    }
                } else {
                    $langData = [
                        'table'      => $comboboxTableNames,
                        'column'     => 'field_name',
                        'lang_key'   => "combobox_field_" . $commonid,
                        'lang_value' => $data['field_name'],
                    ];
                    app($this->langService)->addDynamicLang($langData);
                }
                $list                  = app($this->systemComboboxFieldRepository)->getDetail($commonid);
                $newData['field_name'] = 'combobox_field_' . $commonid;
                $newData['field_order'] = $comboboxObj->field_order ?? '';
                app($this->systemComboboxFieldRepository)->updateData($newData, ['field_id' => $commonid]);

                // 20170915-dp-如果新加的是项目类型的选项，那么，调用函数，增加此选项下属的系统固定字段
                // if ($comboboxIdentify == "PROJECT_TYPE") {

                //     $tableKey              = " project_value_" . $data['field_value'];
                //     $config['menu_name']   = 'custom_config_project_value_' . $data['field_value'];
                //     $config['menu_code']   = $tableKey;
                //     $config['menu_parent'] = 160;
                //     $config['is_dynamic']  = 2;
                //     DB::table("custom_menu_config")->insert($config);
                //     // app($this->projectSupplementService)->addSystemComboboxForProjectTypeSelectItem($data);
                //     // app($this->FieldsRepository)->createEmptyCustomDataTable($tableKey);
                // }
            } else if ($handle == 'delete') {
                app($this->systemComboboxFieldRepository)->deleteById($input['field_id']);
            } else if ($handle == 'update') {
                $commonid          = $input['field_id'];
                $comboboxTableName = get_combobox_table_name($input['combobox_id']);
                $local = Lang::getLocale();

                if (!empty($field_lang) && is_array($field_lang)) {
                    foreach ($field_lang as $key => $value) {
                        
                        if (!empty($field_lang[$local]) || $field_lang[$local] == '0') {
                            $langData = [
                                'table'      => $comboboxTableName,
                                'column'     => 'field_name',
                                'lang_key'   => "combobox_field_" . $commonid,
                                'lang_value' => $value,
                            ];
                            // $local = $key; //可选
                            
                            app($this->langService)->addDynamicLang($langData, $key);

                        } else if (empty($field_lang[$local])) {
                            return ['code' => ['0x015035', 'system']];
                        }
                    }
                } else {
                    $langData = [
                        'table'      => $comboboxTableName,
                        'column'     => 'field_name',
                        'lang_key'   => "combobox_field_" . $commonid,
                        'lang_value' => $data['field_name'],
                    ];
                    app($this->langService)->addDynamicLang($langData);
                }
                $newData = [
                    "field_name"  => "combobox_field_" . $commonid,
                    'field_order' => $input['field_order'],
                ];
                app($this->systemComboboxFieldRepository)->updateData($newData, ['field_id' => $input['field_id']]);

                if ($comboboxInfo = app($this->systemComboboxRepository)->getDetail($input['combobox_id'])) {
                    $comboboxInfo     = $comboboxInfo->toArray();
                    $comboboxIdentify = $comboboxInfo["combobox_identify"];
                    $lang = Lang::getLocale();
                    $redisKey         = "parse:data_". $lang . "_select_combobox_" . $comboboxIdentify;
                    Redis::del($redisKey);
                }
            }
            // 20180123-dp-如果更新项目类型选项名称，那么要同步更新custom_menu_config表的menu_name，防止外发出错
            if ($comboboxIdentify == "PROJECT_TYPE") {
                // 此段，参考 EofficeApp\System\CustomFields\Services\FieldsService.php [getCustomModules]函数
                if($handle == "insert"){
                    $config['menu_name']   = 'custom_config_project_value_' . $data['field_value'];
                    $config['menu_code']   = "project_value_" . $data['field_value'];
                    $config['menu_parent'] = 160;
                    $config['is_dynamic']  = 2;
                    $config['add_permission']  = json_encode(['all' => 1]);
                    $config['edit_permission']  = json_encode(['all' => 1]);
                    $config['delete_permission']  = json_encode(['all' => 1]);
                    $config['view_permission']  = json_encode(['all' => 1]);
                    $config['import_permission']  = json_encode(['all' => 1]);
                    $config['export_permission']  = json_encode(['all' => 1]);
                    app($this->formModelingRepository)->insertCustomMenuConfig($config);
                    ProjectService::createDefaultRoles($data['field_value']); // 新建项目默认角色与权限
                    //Todo 任务自定义
//                    $config['menu_name']   = 'custom_config_project_task_value_' . $data['field_value'];
//                    $config['menu_code']   = "project_task_value_" . $data['field_value'];
//                    $config['menu_parent'] = 160;
//                    $config['is_dynamic']  = 2;
//                    $config['add_permission']  = json_encode(['all' => 1]);
//                    $config['edit_permission']  = json_encode(['all' => 1]);
//                    $config['delete_permission']  = json_encode(['all' => 1]);
//                    $config['view_permission']  = json_encode(['all' => 1]);
//                    $config['import_permission']  = json_encode(['all' => 1]);
//                    $config['export_permission']  = json_encode(['all' => 1]);
//                    app($this->formModelingRepository)->insertCustomMenuConfig($config);
                }else if ($handle == 'delete') {
                    $menu_code =  "project_value_" . $data['field_value'];
                    app($this->formModelingRepository)->deleteCustomMenu($menu_code);
                    //Todo 任务自定义
//                    $menu_code =  "project_task_value_" . $data['field_value'];
//                    app($this->formModelingRepository)->deleteCustomMenu($menu_code);
                }
                $langDatas  = [];
                $fieldValue = app($this->systemComboboxFieldRepository)->getMax($input['combobox_id']);
                if (!empty($field_lang) && is_array($field_lang)) {
                    foreach ($field_lang as $k => $v) {
                        $langDatas = [
                            'table'      => 'custom_menu_config',
                            'column'     => 'menu_name',
                            'lang_key'   => 'custom_config_project_value_' . $data['field_value'],
                            'lang_value' => $v,
                        ];
                        $local = $k; //可选
                        app($this->langService)->addDynamicLang($langDatas, $local);
                        //Todo 任务自定义
//                        $langDatas = [
//                            'table'      => 'custom_menu_config',
//                            'column'     => 'menu_name',
//                            'lang_key'   => 'custom_config_project_task_value_' . $data['field_value'],
//                            'lang_value' => ($k === 'zh-CN' ? '[任务]' : '[Task]') . $v,
//                        ];
//                        $local = $k; //可选
//                        app($this->langService)->addDynamicLang($langDatas, $local);
                    }
                } else {
                    $langDatas = [
                        'table'      => 'custom_menu_config',
                        'column'     => 'menu_name',
                        'lang_key'   => 'custom_config_project_value_' . $data['field_value'],
                        'lang_value' => $data['field_name'],
                    ];
                    app($this->langService)->addDynamicLang($langDatas);
                    //Todo 任务自定义
//                    $langDatas = [
//                        'table'      => 'custom_menu_config',
//                        'column'     => 'menu_name',
//                        'lang_key'   => 'custom_config_project_task_value_' . $data['field_value'],
//                        'lang_value' => (\Lang::getLocale() === 'zh-CN' ? '[任务]' : '[Task]') . $data['field_name'],
//                    ];
//                    app($this->langService)->addDynamicLang($langDatas);
                }
                // 20170915-dp-如果新加的是项目类型的选项，那么，调用函数，增加此选项下属的系统固定字段(配合多语言修改)
                if ($comboboxIdentify == "PROJECT_TYPE") {
                    if (!Schema::hasTable('custom_data_project_value_' . $data['field_value'])) {
                        app($this->projectSupplementService)->addSystemComboboxForProjectTypeSelectItem($data);
                    }
                    //Todo 任务自定义
//                    if (!Schema::hasTable('custom_data_project_task_value_' . $data['field_value'])) {
//                        app($this->projectSupplementService)->addSystemComboboxForProjectTaskTypeSelectItem($data);
//                    }
                }
            }
        }
        return true;
    }

    /**
     * 下拉表字段下拉表
     *
     * @param   int  $id  下拉表字段id
     *
     * @return  array     成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteField($id)
    {
        $comboboxFieldsObj = app($this->systemComboboxFieldRepository)->getComboboxFieldsValueById($id)->toArray();

        $comboboxInfo     = app($this->systemComboboxRepository)->getDetail($comboboxFieldsObj['combobox_id'])->toArray();
        $comboboxIdentify = $comboboxInfo["combobox_identify"];
        $lang = Lang::getLocale();
        $redisKey         = "parse:data_". $lang . "_select_combobox_" . $comboboxIdentify;
        Redis::del($redisKey);

        if ($comboboxInfo['combobox_identify'] == 'PROJECT_TYPE') {
            // 删项目
            $tableKey = "project_value_" . $comboboxFieldsObj['field_value'];

            app($this->MenuRepository)->deleteCustomFields([$tableKey]);
            $tableName = "custom_data_" . $tableKey;
            if (!empty($tableName)) {
                Schema::dropIfExists($tableName);
            }
            // 删任务
//            $tableKey = "project_task_value_" . $comboboxFieldsObj['field_value'];
//
//            app($this->MenuRepository)->deleteCustomFields([$tableKey]);
//            $tableName = "custom_data_" . $tableKey;
//            if (!empty($tableName)) {
//                Schema::dropIfExists($tableName);
//            }
//            app($this->projectSupplementService)->clearLangData($comboboxFieldsObj['field_value']); // 清除多语言
            ProjectService::deleteManagerTypeRoles($comboboxFieldsObj['field_value']); // 删除该类型相关的角色信息与权限
            if (app($this->systemComboboxFieldRepository)->deleteById($id)) {
                return true;
            }
        } else {
            if (app($this->systemComboboxFieldRepository)->deleteById($id)) {
                return true;
            }
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑下拉表字段
     *
     * @param   array   $input   编辑数据
     * @param   int     $fieldId 下拉表字段id
     *
     * @return  array            成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function updateField($input, $comboboxId, $fieldId)
    {

        if (isset($input['is_default'])) {
            $data  = ['is_default' => 0];
            $where = [
                'combobox_id' => [$comboboxId],
                'is_default'  => [1],
            ];

            app($this->systemComboboxFieldRepository)->updateData($data, $where);

            if ($input['is_default'] == 1) {
                $data  = ['is_default' => 1];
                $where = ['field_id' => [$fieldId]];
                app($this->systemComboboxFieldRepository)->updateData($data, $where);
            }

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取下拉表标签
     *
     * @param   array   $param  查询条件
     *
     * @return  array           查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getTagsList($param = [])
    {
        $param = $this->parseParams($param);

        return $this->response(app($this->systemComboboxTagRepository), 'getTotal', 'getTagsList', $param);
    }

    /**
     * 添加下拉表标签（一级菜单）
     *
     * @param   string    $tagName 标签名
     *
     * @return  int|array          添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createTag($tagName)
    {
        $data = [
            'tag_name' => $tagName['tag_name'],
        ];
        $tag_lang = isset($tagName['combobox_name_lang']) ? $tagName['combobox_name_lang'] : '';
        if ($tagObj = app($this->systemComboboxTagRepository)->insertData($data)) {
            if (!empty($tag_lang) && is_array($tag_lang)) {
                foreach ($tag_lang as $key => $value) {
                    $langData = [
                        'table'      => 'system_combobox_tag',
                        'column'     => 'tag_name',
                        'lang_key'   => "combobox_tag_" . $tagObj->tag_id,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            } else {
                $langData = [
                    'table'      => 'system_combobox_tag',
                    'column'     => 'tag_name',
                    'lang_key'   => "combobox_tag_" . $tagObj->tag_id,
                    'lang_value' => $data['tag_name'],
                ];

                app($this->langService)->addDynamicLang($langData);
            }
            $list = app($this->systemComboboxTagRepository)->getDetail($tagObj->tag_id);

            $newData['tag_name'] = 'combobox_tag_' . $tagObj->tag_id;
            if (app($this->systemComboboxTagRepository)->updateData($newData, ['tag_id' => $tagObj->tag_id])) {

                return $tagObj->tag_id;
            }
            return $tagObj->tag_id;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑下拉表标签
     *
     * @param   string  $tagName 标签名
     * @param   int     $id   下拉表标签id
     *
     * @return  array         成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function updateTag($tagName, $id)
    {
        $tag_lang = isset($tagName['combobox_name_lang']) ? $tagName['combobox_name_lang'] : '';

        $data = ['tag_name' => $tagName['tag_name']];
        if ($tagObj = app($this->systemComboboxTagRepository)->updateData($data, ['tag_id' => $id])) {
            if (!empty($tag_lang) && is_array($tag_lang)) {
                foreach ($tag_lang as $key => $value) {
                    $langData = [
                        'table'      => 'system_combobox_tag',
                        'column'     => 'tag_name',
                        'lang_key'   => "combobox_tag_" . $id,
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            } else {
                $langData = [
                    'table'      => 'system_combobox_tag',
                    'column'     => 'tag_name',
                    'lang_key'   => "combobox_tag_" . $id,
                    'lang_value' => $data['tag_name'],
                ];

                app($this->langService)->addDynamicLang($langData);
            }
            $list = app($this->systemComboboxTagRepository)->getDetail($id);

            $newData['tag_name'] = 'combobox_tag_' . $id;
            if (app($this->systemComboboxTagRepository)->updateData($newData, ['tag_id' => $id])) {

                return true;
            }
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除下拉表标签
     *
     * @param  int  $id   下拉表标签id
     *
     * @return array      成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteTag($id)
    {
        if (app($this->systemComboboxTagRepository)->deleteById($id)) {
            remove_dynamic_langs('system_combobox_tag.tag_name.combobox_tag_' . $id);
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 通过主键id获取下拉字段
     *
     * @param  int  $comboboxId 下拉框id
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxFieldsById($comboboxId)
    {
        $comboboxFieldsObj = app($this->systemComboboxRepository)->comboboxFields(['combobox_id' => [$comboboxId]]);
        if ($comboboxFieldsObj) {
            $comboboxTableName             = get_combobox_table_name($comboboxId);
            $comboboxFieldsObj->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $comboboxFieldsObj->field_id);
            return $comboboxFieldsObj->toArray();
        }
        return '';
    }

    /**
     * 通过主键id获取下拉字段
     *
     * @param  int  $comboboxId 下拉框id
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-12-16 创建
     */
    public function getComboboxOnlyFieldsById($comboboxId)
    {
        $comboboxFields    = $this->getComboboxFieldsById($comboboxId);
        $comboboxTableName = get_combobox_table_name($comboboxId);
        if (!empty($comboboxFields) && !empty($comboboxFields['combobox_fields'])) {
            $data = [];
            foreach ($comboboxFields['combobox_fields'] as $k => $v) {
                $data[$v['field_value']] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $v['field_id']);
            }
            return $data;
        }
        return [];
    }

    /**
     * 通过下拉框名称获取下拉字段
     *
     * @param  string  $comboboxName 下拉框名称
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxFieldsByName($comboboxName)
    {
        $comboboxFieldsObj = app($this->systemComboboxRepository)->comboboxFields(['combobox_name' => [$comboboxName]]);
        if ($comboboxFieldsObj) {
            $comboboxTableName             = get_combobox_table_name($comboboxFieldsObj->combobox_id);
            $comboboxFieldsObj->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $comboboxFieldsObj->field_id);
            return $comboboxFieldsObj->toArray();
        }
        return '';
    }

    /**
     * 通过下拉框名称获取字段名
     *
     * @param  string  $comboboxName 下拉框名称
     * @param  int  $fieldValue 字段值
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxFieldsNameByName($comboboxName, $fieldValue)
    {
        $comboboxFieldsObj = app($this->systemComboboxFieldRepository)->getComboboxFieldsName(['combobox_name' => $comboboxName], ['field_value' => $fieldValue]);
        if ($comboboxFieldsObj) {
            $comboboxTableName             = get_combobox_table_name($comboboxFieldsObj->combobox_id);
            $comboboxFieldsObj->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $comboboxFieldsObj->field_id);
            return $comboboxFieldsObj->toArray()['field_name'];
        }
        return '';
    }

    /**
     * 通过下拉框标识获取字段名
     *
     * @param  string  $comboboxIdentify 下拉框标识
     * @param  int  $fieldValue 字段值
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxFieldsNameByIdentify($comboboxIdentify, $fieldValue)
    {
        $comboboxFieldsObj = app($this->systemComboboxFieldRepository)->getComboboxFieldsName(['combobox_identify' => $comboboxIdentify], ['field_value' => $fieldValue]);
        if ($comboboxFieldsObj) {
            $comboboxTableName                                 = get_combobox_table_name($comboboxFieldsObj->combobox_id);
            return $comboboxFieldsObj->toArray()['field_name'] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $comboboxFieldsObj->field_id);
        }
        return '';
    }

    /**
     * 通过下拉框id获取字段名
     *
     * @param  int  $comboboxId 下拉框id
     * @param  int  $fieldValue 字段值
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxFieldsNameById($comboboxId, $fieldValue)
    {
        $comboboxFieldsObj = app($this->systemComboboxFieldRepository)->getComboboxFieldsName(['combobox_id' => $comboboxId], ['field_value' => $fieldValue]);
        $comboboxTableName = get_combobox_table_name($comboboxId);
        if ($comboboxFieldsObj) {
            $comboboxFieldsObj->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $comboboxFieldsObj->field_id);
            return $comboboxFieldsObj->toArray()['field_name'];
        }
        return '';
    }
    /**
     * 通过下拉框字段id获取字段值
     *
     * @param  int  $field_id 下拉框字段id
     *
     * @return array  查询结果
     *
     * @author baijin
     *
     * @since  2017-6-5 创建
     */

    public function getComboboxFieldsValueById($field_id)
    {
        $comboboxFieldsObj = app($this->systemComboboxFieldRepository)->getComboboxFieldsValueById($field_id);
        if ($comboboxFieldsObj) {
            return $comboboxFieldsObj->toArray()['field_value'];
        }
        return '';
    }

    //获取项目类型的
    public function getProjectTypeAll()
    {
        $param["combobox_identify"] = "PROJECT_TYPE";
        $data                       = app($this->systemComboboxFieldRepository)->getFieldsList($param);
        foreach ($data as $k => $v) {
            $comboboxTableName      = get_combobox_table_name($v['combobox_id']);
            $data[$k]['field_name'] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $v['field_id']);
        }
        return $data;
        // return app($this->systemComboboxFieldRepository)->getFieldsList($param);
    }

    //获取单位类型的
    public function getProductUnitAll()
    {
        $param["combobox_identify"] = "PRODUCT_UNIT";
        $data                       = app($this->systemComboboxFieldRepository)->getFieldsList($param);
        foreach ($data as $k => $v) {
            $comboboxTableName      = get_combobox_table_name($v['combobox_id']);
            $data[$k]['field_name'] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $v['field_id']);
        }
        return $data;
        // return app($this->systemComboboxFieldRepository)->getFieldsList($param);
    }

    //获取字段key
    public function getValueByName($comboboxName, $fieldName = "")
    {
        return app($this->systemComboboxFieldRepository)->getValueByName($comboboxName, $fieldName);
    }

    //获取字段key
    public function getValueByComboboxIdentify($comboboxIdentify, $fieldName = "")
    {
        return app($this->systemComboboxFieldRepository)->getValueByComboboxIdentify($comboboxIdentify, $fieldName);
    }

    //identify
    public function getComboboxFieldByIdentify($combobox_identify)
    {
        if (empty($combobox_identify)) {
            return [];
        }

        $param["combobox_identify"] = $combobox_identify;
        $data                       = app($this->systemComboboxFieldRepository)->getFieldsList($param);
        $result                     = [];
        foreach ($data as $v) {
            $comboboxTableName         = get_combobox_table_name($v['combobox_id']);
            $result[$v['field_value']] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $v['field_id']);
        }

        return $result;
    }

    //name
    public function getComboboxFieldByName($combobox_name)
    {
        if (empty($combobox_name)) {
            return [];
        }
        $local     = Lang::getLocale();
        $langTable = app($this->langService)->getLangTable($local);
        $combobox  = DB::select("select lang_key, lang_value, `table` from " . $langTable . " where lang_value =" . "'$combobox_name'" . "and `table` = 'system_combobox'");

        $comboboxName = isset($combobox[0]->lang_key) ? $combobox[0]->lang_key : '';
        $comboboxId = DB::table('system_combobox')->where('combobox_name', $comboboxName)->value('combobox_id');
        $param["combobox_id"] = $comboboxId;
        $data                   = app($this->systemComboboxFieldRepository)->getFieldsList($param);
        $result                 = [];
        foreach ($data as $v) {
            $comboboxTableName         = get_combobox_table_name($v['combobox_id']);
            $result[$v['field_value']] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $v['field_id']);
        }
        return $result;
    }
    //getComboboxField
    public function getComboboxField($combobox_name, $combobox_identify = null)
    {
        $result = [];
        if (empty($combobox_name) && empty($combobox_identify)) {
            return $result;
        }

        if (!empty($combobox_identify)) {
            $result = $this->getComboboxFieldByIdentify($combobox_identify);
        }
        $local     = Lang::getLocale();
        $langTable = app($this->langService)->getLangTable($local);
        $combobox  = DB::select("select lang_key, lang_value, `table` from " . $langTable . " where lang_value =" . "'$combobox_name'" . "and `table` = 'system_combobox'");

        if (empty($result)) {
            $result = $this->getComboboxFieldByName($combobox[0]->lang_key);
        }
        return $result;
    }

    public function getComboboxFieldData($param = array(), $combobox_name, $combobox_identify = null)
    {
        $result = [];
        if (empty($combobox_name) && empty($combobox_identify)) {
            return $result;
        }

        if (!empty($combobox_identify)) {
            $param["combobox_identify"] = $combobox_identify;
            $result                     = app($this->systemComboboxFieldRepository)->getFieldsList($param);
        }
        if (empty($result)) {
            if (isset($param["combobox_identify"])) {
                unset($param["combobox_identify"]);
            }

            $param["combobox_name"] = $combobox_name;
            $result                 = app($this->systemComboboxFieldRepository)->getFieldsList($param);
        }
        if ($result) {
            foreach ($result as $key => $value) {
                $comboboxTableName          = get_combobox_table_name($value['combobox_id']);
                $result[$key]['field_name'] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $value['field_id']);
            }
        }
        return $result;
    }

    public function getIndustry($param = [])
    {
        $result = app($this->systemComboboxFieldRepository)->getIndustry($param);
        return $result;
    }

    public function parseCombobox($type, $comboValue)
    {
        static $comboArr = [];
        if (isset($comboArr[$type]) && isset($comboArr[$type][$comboValue])) {
            return $comboArr[$type][$comboValue];
        }
        $lists = $this->getAllFields($type);
        if (!empty($lists)) {
            foreach ($lists['list'] as $key => $value) {
                if (!isset($comboArr[$type])) {
                    $comboArr[$type] = [];
                }
                $comboArr[$type][$value['field_value']] = $value['field_name'];
            }
        }
        return $comboArr[$type][$comboValue] ?? '';
    }

}
