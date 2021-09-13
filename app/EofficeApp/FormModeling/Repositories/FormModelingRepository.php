<?php

namespace App\EofficeApp\FormModeling\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\FormModeling\Entities\FormModelingEntity;
use App\EofficeApp\FormModeling\Services\FormModelingService;
use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use DB;
use Illuminate\Support\Facades\Redis;
use Schema;
use Illuminate\Support\Arr;
/**
 * 表单建模
 *
 * @author:白锦
 *
 * @since：2019-03-22
 *
 */
class FormModelingRepository extends BaseRepository
{
    private $subDataTableName = [];
    private $noPrefixTables = [];
    private $dataTablePrefix = 'custom_data_'; //数据表前缀
    private $charset = 'utf8'; //数据表字符集
    private $limit;
    private $page = 0;
    private $orderBy = ['created_at' => 'desc'];
    private $randNumber = '';
    const MAX_WHERE_IN = 5000;
    const CUSTOM_MENU_TABLE = 'custom_menu_config';
    const HIDE_MENUS_FILE = 'hide_menu.txt';
    const MENUS_FILE = 'menu.txt';
    const FOREIGN_MENUS = 'customer:foreign_menus';
    const MENUS = 'menus';

    const FOREIGN_HIDE_MENUS = 'customer:foreign_hide_menus';
    const HIDE_MENUS = 'hide_menus';
    private $module_parse_data_rule = [
        'book_manage' => [
            'foreignKey' => 'book_id',
        ],
        'office_supplies_storage' => [
            'foreignKey' => 'office_supplies_id',
        ],
        'archives_file' => [
            'foreignKey' => 'file_id',
        ],
    ];

    private $field_value_relation = [
        'archives_appraisal' => [
            'appraisal_type' => [
                1 => 'file', // 文件
                2 => 'volume', // 案卷
            ],
        ],
        'archives_borrow' => [
            'borrow_type' => [
                1 => 'file', // 文件
                2 => 'volume', // 案卷
            ],
        ],
    ];
    public function __construct(
        FormModelingEntity $entity
    ) {
        parent::__construct($entity);
        $this->limit = config('eoffice.pagesize');
    }

    /**
     * 获取表单模块列表
     *
     * @author 白锦
     *
     * @param string $menuCode
     * @param array  $fields
     *
     * @since  2019-03-22 创建
     *
     * @return array     返回结果
     */
    public function getCustomModule($menuCode, $fields = ['*'])
    {
        $menu = $this->getCustomMenu($menuCode, ['menu_parent']);

        return DB::table('menu')->select($fields)->where('menu_id', $menu->menu_parent)->first();
    }
    /**
     * 获取模块列表
     *
     * @param   array  $param
     *
     * @return  array          返回结果
     */
    public function getParent($param = [], $group_by = 1)
    {
        $query = DB::table('custom_menu_config');
        if (isset($param['is_dynamic'])) {
            $query = $query->where('is_dynamic', $param['is_dynamic']);
        }
        if (isset($param['search']) && !empty($param['search'])) {
            $query = $this->wheres($query, $param['search']);
        }
        if ($group_by) {
            return $query->groupBy('menu_parent')->get();
        } else {
            return $query->get();
        }

    }

    public function getMenu($parent, $params = [])
    {
        $query = DB::table('custom_menu_config');
        if (is_array($parent)) {
            $query = $query->whereIn('menu_parent', $parent);
        } else {
            $query = $query->where('menu_parent', $parent);
        }
        if (isset($params['search']) && !empty($params['search'])) {
            $query = $this->wheres($query, $params['search']);
        }
        $result = $query->orderBy('id', 'asc')->get();

        foreach ($result as $key => $value) {
            $menu_name = $value->menu_name;
            $menu = DB::table("lang_zh_cn")->where("lang_value", $menu_name)->first();
            if (!empty($menu)) {
                $lang_key = $menu->lang_key;
                $result[$key]->lang_key = $lang_key;
            }
        }
        return $result;
    }
    /**
     * 2019-03-25
     *
     * 创建自定义字段数据表
     *
     * @param string $tableKey
     *
     * @return boolean
     */
    public function createEmptyCustomDataTable($tableKey)
    {
        $customDataTable = $this->getCustomDataTableName($tableKey);
        if (Schema::hasTable($customDataTable)) {
            return true;
        }
        Schema::create($customDataTable, function ($table) use ($tableKey) {
            $table->charset = $this->charset;
            $table->increments($this->getCustomDataPrimayKey($tableKey));
            $table->string('creator', 50)->comment('创建人');
            $table->softDeletes();
            $table->timestamps();
        });
        return Schema::hasTable($customDataTable);
    }
    /**
     * 2019-03-25
     *
     * 获取自定义数据表名称
     *
     * @param string $tableKey
     *
     * @return string
     */
    public function getCustomDataTableName($tableKey)
    {
        if (isset($this->dataTableName[$tableKey])) {
            return $this->dataTableName[$tableKey];
        }

        return $this->dataTableName[$tableKey] = $this->hasPrefix($tableKey) ? $this->dataTablePrefix . $tableKey : $tableKey;
    }
    /**
     * 2019-03-25
     *
     * 获取自定义数据表主键名称
     *
     * @param string $tableKey
     *
     * @return string
     */
    public function getCustomDataPrimayKey($tableKey)
    {
        if ($this->hasPrefix($tableKey)) {
            return 'data_id';
        }

        static $array;

        if (isset($array[$tableKey])) {
            return $array[$tableKey];
        }
        if(!Schema::hasTable($tableKey)){
            return 'data_id';
        }
        $result = DB::select("SHOW COLUMNS FROM $tableKey WHERE `Key` = 'PRI'");

        return $array[$tableKey] = $result[0]->Field;
    }

    /**
     * 2019-03-25
     *
     * 新建自定义字段
     *
     * @param array $fieldData
     *
     * @return int
     */
    public function addCustomField($fieldData)
    {
        $insertId = $this->entity->insertGetId($fieldData);
        //如果有提醒字段，同时更新定时提醒表
        if (isset($fieldData['field_options']) && !empty($fieldData['field_options'])) {
            $field_options = json_decode($fieldData['field_options']);
            if (isset($field_options->timer)) {
                $fieldData['field_id'] = $insertId;
                $insert = $this->getFormCustomRemindsData($fieldData, true);
                DB::table("custom_reminds")->insertGetId($insert);
            }
        }
        return $insertId;
    }
    /**
     * 获取定时提醒数据
     *
     * @param   array  $data
     * @param   string  $add
     *
     * @return  array
     */
    public function getFormCustomRemindsData($data, $add = false)
    {
        $field_options = json_decode($data['field_options']);
        $timer = $field_options->timer;
        if (isset($timer->previous)) {
            $type = 'previous';
        } elseif (isset($timer->period)) {
            $type = 'period';
        } else {
            $type = 'delay';
        }
        $field_hide = isset($data['field_hide']) ? $data['field_hide'] : 0;
        $fieldData = [
            'reminds_select' => isset($timer->method) ? implode(',', $timer->method) : '',
            'content' => isset($timer->content) ? $timer->content : '',
            'target' => isset($timer->target) ? $timer->target : '',
            'type' => $type,
            'option' => isset($timer->$type) ? json_encode($timer->$type) : '',
            'extra' => $data['field_options'],
            'field_hide' => $field_hide,
            'field_directive' => isset($data['field_directive']) ? $data['field_directive'] : '',
        ];
        $fieldData['field_table_key'] = $data['field_table_key'];
        $fieldData['field_code'] = $data['field_code'];
        if ($add) {
            $fieldData['created_at'] = date('Y-m-d H:i:s');
            $fieldData['id'] = $data['field_id'];
        }
        return $fieldData;

    }

    /**
     * 2019-03-25
     *
     * 更新自定义字段
     *
     * @param array $fieldData
     * @param int $fieldId
     *
     * @return boolean
     */
    public function updateCustomField($fieldData, $fieldId)
    {
        //如果有提醒字段，同时更新定时提醒表
        if (isset($fieldData['field_options']) && !empty($fieldData['field_options'])) {
            $field_options = json_decode($fieldData['field_options']);
            if (isset($field_options->timer)) {
                $insert = $this->getFormCustomRemindsData($fieldData);
                $custom_reminds = DB::table("custom_reminds")->where('id', $fieldId)->first();
                if (empty($custom_reminds)) {
                    $insert['id'] = $fieldId;
                    DB::table("custom_reminds")->insert($insert);
                } else {
                    DB::table("custom_reminds")->where('id', $fieldId)->update($insert);
                }

            } else {
                DB::table("custom_reminds")->where('id', $fieldId)->delete();
            }
        }
        return $this->entity->where('field_id', $fieldId)->update($fieldData);
    }

    /**
     * 2019-03-25
     *
     * 删除自定义字段
     *
     * @param int $fieldId
     *
     * @return boolean
     */
    public function deleteCustomField($fieldId)
    {
        //如果有提醒字段，同时更新定时提醒表
        DB::table("custom_reminds")->where('id', $fieldId)->delete();
        return $this->entity->where('field_id', $fieldId)->delete();
    }
    /**
     * 2019-03-25
     *
     * 创建明细布局副表
     *
     * @param string $tableKey
     * @param array $fields
     *
     * @return boolean
     */
    public function createCustomDataSubTable($tableKey, $subTableKey, $fields)
    {
        $customDataSubTable = $this->getCustomDataSubTableName($tableKey, $subTableKey);
        if (Schema::hasTable($customDataSubTable)) {
            return true;
        }

        $mainPrimaryKey = $this->getCustomDataPrimayKey($tableKey);

        Schema::create($customDataSubTable, function ($table) use ($fields, $mainPrimaryKey) {
            $table->charset = $this->charset;
            $table->increments("detail_data_id");
            $table->integer($mainPrimaryKey);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $comment = isset($field['field_name']) ? $field['field_name'] : '';
                    $fieldType = isset($field['field_data_type']) ? $field['field_data_type'] : 'text';
                    if ($fieldType == 'text') {
                        if ($field['field_directive'] == 'editor') {
                            $table->mediumText($field['field_code'])->comment($comment)->nullable();
                        } else {
                            $table->text($field['field_code'])->comment($comment)->nullable();
                        }
                    } else {
                        $table->string($field['field_code'], 255)->comment($comment)->nullable();
                    }
                }
            }
            $table->softDeletes();
            $table->timestamps();
            $table->index($mainPrimaryKey);
        });

        return Schema::hasTable($customDataSubTable);
    }
    /**
     * 2019-03-25
     *
     * 获取自定义子数据表名称
     *
     * @param string $tableKey
     *
     * @return string
     */
    public function getCustomDataSubTableName($tableKey, $subTableKey)
    {
        if (isset($this->subDataTableName[$tableKey . $subTableKey])) {
            return $this->subDataTableName[$tableKey . $subTableKey];
        }

        return $this->subDataTableName[$tableKey . $subTableKey] = $this->hasPrefix($tableKey) ? $this->dataTablePrefix . $tableKey . '_' . $subTableKey : $tableKey . '_' . $subTableKey;
    }
    /**
     * 2019-03-25
     *
     * 判断表是否有前缀
     *
     * @param string $tableKey
     *
     * @return boolean
     */
    private function hasPrefix($tableKey)
    {
        if (isset($this->noPrefixTables[$tableKey])) {
            return $this->noPrefixTables[$tableKey];
        }
        $tableConfig = $this->getCustomMenu($tableKey, ['is_dynamic']);
        if (empty($tableConfig)) {
            return false;
        }
        return $this->noPrefixTables[$tableKey] = $tableConfig->is_dynamic == 1 ? false : true;
    }
    /**
     * 2019-03-25
     *
     * 获取自定义菜单
     *
     * @param string $menuCode
     * @param array $fields
     *
     * @return object
     */
    public function getCustomMenu($menuCode, $param = [])
    {
        $fields = isset($param['fields']) ? $param['fields'] : ['*'];
        return DB::table('custom_menu_config')->select($fields)->where('menu_code', $menuCode)->first();
    }

    /**
     * 2019-03-25
     *
     * 删除自定义菜单
     *
     * @param string $menuCode
     * @param array $fields
     *
     * @return object
     */
    public function deleteCustomMenu($menuCode)
    {
        return DB::table('custom_menu_config')->where('menu_code', $menuCode)->delete();
    }

    public function insertCustomMenuConfig($config)
    {
        DB::table("custom_menu_config")->insert($config);
    }
    /**
     * 2019-03-25
     *
     * 新增数据表列
     *
     * @param array $fieldData
     * @param string $tableKey
     *
     * @return boolean
     */
    public function addCustomColumn($fieldData, $tableKey,$customDataTable)
    {
        $fieldData['field_data_type'] = isset($fieldData['field_data_type']) ? $fieldData['field_data_type'] : 'varchar';
        if (!Schema::hasColumn($customDataTable, $fieldData['field_code'])) {
            Schema::table($customDataTable, function ($table) use ($fieldData, $tableKey) {
                $comment = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $fieldData['field_code']);
                if ($fieldData['field_data_type'] == 'text') {
                    if ($fieldData['field_directive'] == 'editor') {
                        $table->mediumText($fieldData['field_code'])->comment($comment)->nullable();
                    } else {
                        $table->text($fieldData['field_code'])->comment($comment)->nullable();
                    }
                } else {
                    $table->string($fieldData['field_code'], 255)->comment($comment)->nullable();
                }
            });
        }
        return Schema::hasColumn($customDataTable, $fieldData['field_code']);
    }
    /**
     * 2019-03-25
     *
     * 新增明细表列
     *
     * @param array $fieldData
     * @param string $customDataTable
     *
     * @return boolean
     */
    public function addLayoutColumn($fieldData, $tableKey, $subTableKey)
    {
        $customDataTable = $this->getCustomDataSubTableName($tableKey, $subTableKey);
        if (!Schema::hasColumn($customDataTable, $fieldData['field_code'])) {
            Schema::table($customDataTable, function ($table) use ($fieldData, $tableKey) {
                $comment = mulit_trans_dynamic("custom_fields_table.field_name." . $tableKey . "_" . $fieldData['field_code']);
                if ($fieldData['field_data_type'] == 'text') {
                    if ($fieldData['field_directive'] == 'editor') {
                        $table->mediumText($fieldData['field_code'])->comment($comment)->nullable();
                    } else {
                        $table->text($fieldData['field_code'])->comment($comment)->nullable();
                    }
                } else {
                    $table->string($fieldData['field_code'], 255)->comment($comment)->nullable();
                }
            });
        }
        return Schema::hasColumn($customDataTable, $fieldData['field_code']);
    }
    /**
     * 2019-03-25
     *
     * 删除明细字段列
     *
     * @param   string  $column
     * @param   string  $customDataTable
     *
     * @return  boolean
     */
    public function deleteLayoutTableColumn($column, $customDataTable)
    {

        if (!Schema::hasColumn($customDataTable, $column)) {
            return true;
        }

        Schema::table($customDataTable, function ($table) use ($column) {
            $table->dropColumn($column);
        });

        if (Schema::hasColumn($customDataTable, $column)) {
            return false;
        }

        return true;
    }
    /**
     * 2019-03-25
     *
     * 删除自定义字段数据表列
     *
     * @param sting $column
     * @param sting $tableKey
     *
     * @return boolean
     */
    public function deleteCustomDataTableColumn($column, $tableKey,$customDataTable)
    {

        if (!Schema::hasColumn($customDataTable, $column)) {
            return true;
        }

        Schema::table($customDataTable, function ($table) use ($column) {
            $table->dropColumn($column);
        });

        if (Schema::hasColumn($customDataTable, $column)) {
            return false;
        }

        return true;
    }

    /**
     * 2019-03-25
     *
     * 删除数据表
     *
     * @param string $tableName
     *
     * @return boolean
     */
    public function dropTable($tableName)
    {
        return Schema::dropIfExists($tableName);
    }

    /**
     * 2019-03-25
     *
     * 显示一个字段的详情信息
     *
     * @param int $fieldId
     *
     * @return object
     */
    public function showCustomField($fieldId)
    {
        return $this->entity->where('field_id', $fieldId)->first();
    }
    /**
     * 2019-03-25
     *
     * 获取自定义字段列表
     *
     * @param string $tableKey
     * @param array $param
     * @return array
     */
    public function listCustomFields($tableKey, $param)
    {
        $query = $this->entity->where([
            ['field_table_key', '=', $tableKey],
            ['field_directive', '<>', 'tabs'],
        ]);

        if (isset($param['fields']) && !empty($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        if (isset($param['search']) && !empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->orderBy('field_id', 'asc')->get();
    }
    /**
     * 2019-03-25
     *
     * 拼接查询条件
     *
     * @param object $query
     * @param array $wheres
     *
     * @return object
     */
    public function wheres($query, $wheres)
    {
        $operators = [
            'between' => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in' => 'whereIn',
            'not_in' => 'whereNotIn',
            'null' => 'whereNull',
            'not_null' => 'whereNotNull',
            'or' => 'orWhere',
        ];

        if (empty($wheres)) {
            return $query;
        }
        if (isset($wheres['whereRaw'])) {
            $query = $query->whereRaw($wheres['whereRaw']);
            unset($wheres['whereRaw']);
        }
        foreach ($wheres as $field => $where) {
            if (!is_array($where)) {
                continue;
            }
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                if ($operator == 'null' || $operator == 'not_null') {
                    $query = $query->$whereOp($field);
                } else {
                    $query = $query->$whereOp($field, $where[0]);
                }
            } else {
                $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                if(is_numeric($value)){
                    $value = $value + 0;
                }
                $query = $query->where($field, $operator, $value);
            }
        }
        return $query;
    }

    public function scopeMultiWheres($query, $wheres)
    {
        if (empty($wheres)) {
            return $query;
        }

        //初始属性,and关系
        $whereString = 'where';
        $whereHas = 'whereHas';

        $operators = [
            'between' => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in' => 'whereIn',
            'not_in' => 'whereNotIn',
        ];

        $orOperators = [
            'between' => 'orWhereBetween',
            'not_between' => 'orWhereNotBetween',
            'in' => 'orWhereIn',
            'not_in' => 'orWhereNotIn',
        ];

        //or关系
        if (isset($wheres['__relation__']) && $wheres['__relation__'] == 'or') {
            $operators = $orOperators;
            $whereString = 'orWhere';
            $whereHas = 'orWhereHas';
        }

        //删除__relation__
        if (isset($wheres['__relation__'])) {
            unset($wheres['__relation__']);
        }

        //判断是不是整体都是关联查询
        $searchFields = array_keys($wheres);
        if (isset($this->allFields) && isset($this->allFields[$searchFields[0]])) {
            $firstRelation = $this->allFields[$searchFields[0]][0];
        } else {
            $firstRelation = '';
        }

        if ($firstRelation && empty(array_diff($searchFields, $this->relationFields[$firstRelation]))) {
            $relationStatus = true;
        } else {
            $relationStatus = false;
        }

        //整体关联查询,即这一层的所有查询都在同一个关联关系下
        if ($relationStatus) {
            $query = $query->$whereHas($firstRelation, function ($query) use ($wheres, $operators, $whereString) {
                foreach ($wheres as $field => $where) {
                    $field = $this->allFields[$field][1];
                    $operator = isset($where[1]) ? $where[1] : '=';
                    $operator = strtolower($operator);

                    if (isset($operators[$operator])) {
                        $whereOp = $operators[$operator];
                        $query = $query->$whereOp($field, $where[0]);
                    } else {
                        $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                        $query = $query->$whereString($field, $operator, $value);
                    }
                }
            });
            //不是整体关联查询,则这一层查询条件为并列关系,同一个关联关系下的参数也是
        } else {
            foreach ($wheres as $field => $where) {
                $operator = isset($where[1]) ? $where[1] : '=';
                $operator = strtolower($operator);

                if (isset($this->allFields) && isset($this->allFields[$field])) {
                    $query = $query->$whereHas($this->allFields[$field][0], function ($query) use ($where, $operators, $operator, $field, $whereString) {
                        $field = $this->allFields[$field][1];

                        if (isset($operators[$operator])) {
                            $whereOp = $operators[$operator];
                            $query = $query->$whereOp($field, $where[0]);
                        } else {
                            $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                            $query = $query->$whereString($field, $operator, $value);
                        }
                    });
                    //键值包含multiSearch为下一层,递归调用本函数
                } elseif (strpos($field, 'multiSearch') !== false) {
                    $query = $query->$whereString(function ($query) use ($where) {
                        $this->scopeMultiWheres($query, $where);
                    });
                } else {
                    if (isset($operators[$operator])) {
                        $whereOp = $operators[$operator];
                        $query = $query->$whereOp($field, $where[0]);
                    } else {
                        $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                        $query = $query->$whereString($field, $operator, $value);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * 字段数量
     *
     * @param   string  $table_key
     * @param   string  $field_code
     *
     * @return  int
     */
    public function customFieldsNumber($table_key, $field_code)
    {
        $where = [
            ["field_table_key", $table_key],
            ["field_code", $field_code],
        ];
        return $this->entity->where($where)->count();
    }
    public function custom_fields_table($table_key, $field_code)
    {
        $where = [
            ["field_table_key", $table_key],
            ["field_code", $field_code],
        ];
        return $this->entity->where($where)->first();
    }

    public function saveTemplate($data)
    {
        return DB::table('custom_template')->insertGetId($data);
    }

    public function editTemplate($data, $id)
    {
        return DB::table('custom_template')->where('template_id', $id)->update($data);
    }

    /**
     * 删除模板
     *
     * @param   int $id
     *
     * @return  boolean
     */
    public function deleteTemplate($id)
    {
        return DB::table('custom_template')->whereIn('template_id', explode(',', $id))->delete();
    }

    public function deleteTemplateByKey($tableKey)
    {
        return DB::table('custom_template')->where('table_key',$tableKey)->delete();
    }

    /**
     * 获取模板
     *
     * @param   int  $id
     *
     * @return  object
     */
    public function getTemplate($id)
    {
        return DB::table('custom_template')->where('template_id', $id)->first();
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
        $fields = isset($param['fields']) ? $param['fields'] : ['*'];
        $limit = (isset($param['limit']) && $param['limit']) ? $param['limit'] : $this->limit;
        $page = (isset($param['page']) && $param['page']) ? $param['page'] : $this->page;
        $orderBy = (isset($param['order_by']) && !empty($param['order_by'])) ? $param['order_by'] : $this->orderBy;
        $query = DB::table('custom_template')
            ->select($fields)
            ->where('custom_template.table_key', $tableKey);
        if (isset($param['search']) && !empty($param['search'])) {
            $query = $this->wheres($query, $param['search']);
        }
        $query = $this->orders($query, $orderBy);
        $query = $this->parsePage($query, $page, $limit);
        return $query->get();
    }
    /**
     * 获取模板列表
     *
     * @param   string  $tableKey
     *
     * @return  array
     */
    public function getTemplateTotal($tableKey, $param)
    {
        $query = DB::table('custom_template')
            ->where('custom_template.table_key', $tableKey);

        if (isset($param['search']) && !empty($param['search'])) {
            $query = $this->wheres($query, $param['search']);
        }
        return $query->count();
    }

    /**
     * 获取绑定模板
     *
     * @param   string  $tableKey
     *
     * @return  object
     */
    public function getBindTemplate($tableKey, $param)
    {
        $terminal = isset($param['terminal']) ? $param['terminal'] : '';
        $bind_type = isset($param['bind_type']) ? $param['bind_type'] : '';
        if (isset($param['fields'])) {
            $fields = explode(',', $param['fields']);
            array_push($fields, 'custom_layout_setting.bind_template');
        } else {
            $fields = ['custom_template.*', 'custom_layout_setting.bind_type', 'custom_layout_setting.bind_template', 'custom_layout_setting.bind_id'];
        }
        $query = DB::table('custom_layout_setting')
            ->select($fields)
            ->leftJoin('custom_template', 'custom_layout_setting.bind_template', '=', 'custom_template.template_id')
            ->where('custom_layout_setting.table_key', $tableKey);
        if ($terminal) {
            $query = $query->where('custom_layout_setting.terminal', $terminal);

        }
        if ($bind_type) {
            return $query->where('bind_type', $bind_type)->first();
        } else {
            return $query->get();
        }

    }
    /**
     * 2019-04-03
     *
     * 排序
     *
     * @param object $query
     * @param array $orders
     *
     * @return object
     */
    private function orders($query, $orders, $numberFields = [])
    {
        if (!empty($orders)) {
            foreach ($orders as $field => $order) {
                if (in_array($field, $numberFields)) {
                    //处理文本框数值排序
                    if($order == 'asc' || $order == 'desc'){
                        $query = $query->orderByRaw("$field+0  $order");
                    }
                } else {
                    $query = $query->orderBy($field, $order);
                }
            }
        }
        return $query;
    }
    /**
     * 2019-04-03
     *
     * 分页
     *
     * @param object $query
     * @param int $start
     * @param int $limit
     * @param boolean $isPage
     *
     * @return object
     */
    private function parsePage($query, $start, $limit, $isPage = true)
    {
        $start = (int) $start;

        if ($isPage && $start == 0) {
            return $query;
        }

        if ($isPage) {
            $start = ($start - 1) * $limit;
        }

        $query->offset($start)->limit($limit);

        return $query;
    }

    /**
     * 保存绑定模板
     *
     * @param   array $data
     * @param   int  $id
     *
     * @return  boolean
     */
    public function saveBindTemplate($data)
    {
        $bind = DB::table('custom_layout_setting')->where('table_key', $data['table_key'])->where('terminal', $data['terminal'])->where('bind_type', $data['bind_type'])->first();
        if ($bind) {
            return DB::table('custom_layout_setting')->where('table_key', $data['table_key'])->where('terminal', $data['terminal'])->where('bind_type', $data['bind_type'])->update($data);
        } else {
            return DB::table('custom_layout_setting')->insert($data);
        }
    }

    public function saveOtherSetting($tableKey, $data)
    {
        return DB::table('custom_menu_config')
            ->where('menu_code', $tableKey)->update($data);
    }
    /**
     * 2019-05-05
     *
     * 判断是否有自定义数据表
     *
     * @param string $tableKey
     *
     * @return boolean
     */
    public function hasCustomDataTable($tableKey)
    {
        return Schema::hasTable($this->getCustomDataTableName($tableKey));
    }
    public function multiOrders($orderBy, $table, $type = '')
    {
        if (FormModelingService::isProjectSeriesCustomKey($table)) {
            $table = '';
        }
        if ($type == 'default') {
            $orderByParam = array();
            foreach ($orderBy as $key => $value) {
                if (!empty($value) && is_array($value)) {
                    if (isset($value['key'])) {
                        if ($table) {
                            $orderByParam[$table . "." . $value['key']] = $value['type'];
                        } else {
                            $orderByParam[$value['key']] = $value['type'];
                        }
                    }
                }
            }
            return $orderByParam;
        } else {
            $orderByParam = array();
            // 多字段排序处理
            if (is_array($orderBy) && count($orderBy) > 1) {
                foreach ($orderBy as $key => $value) {
                    if (!empty($value) && is_array($value)) {
                        if (isset($value['key'])) {
                            $orderByParam[$value['key']] = $value['type'];
                        } else {
                            $orderByParam[key($value)] = current($value);
                        }
                    } else {
                        return $orderBy;
                    }
                }
                return $orderByParam;
            }
            if (is_array($orderBy)) {
                foreach ($orderBy as $order => $type) {
                    if ($table) {
                        $orderByParam[$table . "." . $order] = $type;
                    } else {
                        $orderByParam[$order] = $type;
                    }
                }
            }
            return $orderByParam;
        }

    }

    /**
     * 2019-05-05
     *
     * @param string $tableKey
     * @param array $param
     *
     * @return array
     */
    public function getCustomDataList($tableKey, $param)
    {
        $table = $this->getCustomDataTableName($tableKey);
        $limit = (isset($param['limit']) && $param['limit']) ? $param['limit'] : $this->limit;
        $page = (isset($param['page']) && $param['page']) ? $param['page'] : $this->page;
        if (isset($param['order_by']) && !empty($param['order_by'])) {
            $orderBy = $param['order_by'];
            $orderBy = $this->multiOrders($orderBy, $table);
        } else {
            //获取默认排序
            if (isset($param['defaultOrder']) && !empty($param['defaultOrder'])) {
                $orderBy = $this->multiOrders($param['defaultOrder'], $table, 'default');
            } else {
                $filterConfig = config('customfields.dataListRepositoryOrder.' . $tableKey);
                if ($filterConfig) {
                    $orderBy = $filterConfig;
                } else {
                    $orderBy = [$table . '.created_at' => 'desc'];
                }
            }
        }
        $fields = isset($param['fields']) ? $param['fields'] : ['*'];
        $query = DB::table($table)->select($fields);
        if (Schema::hasColumn($table, 'deleted_at') && !isset($param['withTrashed'])) {
            $query = $query->whereNull($table . '.deleted_at');
        }
        if (isset($param['search']) && !empty($param['search'])) {
            if (isset($param['search']['birthday'])) {
                if (count(explode('-', $param['search']['birthday'][0][0])) == 2) {
                    $query = $query->whereRaw("date_format(`birthday`, '%m-%d')>=?", [$param['search']['birthday'][0][0]])
                          ->whereRaw("date_format(`birthday`, '%m-%d')<= ?", [$param['search']['birthday'][0][1]]);
                    unset($param['search']['birthday']);
                }
                if(isset($param['search']['birthday'][1]) && $param['search']['birthday'][1] == ">="){
                    $query = $query->whereRaw("date_format(`birthday`, '%m-%d')>=?", [$param['search']['birthday'][0]]);
                    unset($param['search']['birthday']);
                }
                if(isset($param['search']['birthday'][1]) && $param['search']['birthday'][1] == "<="){
                    $query->whereRaw("date_format(`birthday`, '%m-%d')<=?", [$param['search']['birthday'][0]]);
                    unset($param['search']['birthday']);
                }
            }
            if (isset($param['search']['multiSearch']) && !empty($param['search']['multiSearch'])) {
                $query = $this->scopeMultiWheres($query, $param['search']);
            } else {
                $query = $this->wheres($query, $param['search']);

            }
        }
        $tableName = '';
        if (!empty($param['filter'])) {
            $query = $this->filterCustomData($param, $query, $table);
        }
        if (!empty($param['viewPermission']) && Schema::hasColumn($table, $param['viewPermission']['field'])) {
            $query = $query->where(function ($query) use($param) {
                foreach($param['viewPermission']['value'] as $roleId){
                    $query->orWhereRaw('find_in_set(?,'.security_filter($param['viewPermission']['field']).')', [$roleId]);
                }
            });
        }
        $numberFields = isset($param['numberFields']) ? $param['numberFields'] : [];
        //项目的自定义字段join其它表
        if (FormModelingService::isProjectCustomKey($tableKey)) {
            $this->buildProjectQuery($query);
        } else if (FormModelingService::isProjectTaskCustomKey($tableKey)) {
            $this->buildProjectTaskQuery($query);
        }
        //获取明细数据
        if (isset($param['detailLayout']) && $param['detailLayout']) {
            $primaryKey = $this->getCustomDataPrimayKey($tableKey);
            $query = $query->leftJoin($param['detailLayout'], $param['detailLayout'] . '.'.$primaryKey, '=', $table . '.' . $primaryKey);
        }
        $query = $this->orders($query, $orderBy, $numberFields);
        $query = $this->parsePage($query, $page, $limit);
        return $query->get()->toArray();
    }

    //构建项目自定义字段的查询对象
    private function buildProjectQuery($query)
    {
        $mainTableName = 'project_manager';
        $query->rightjoin($mainTableName, "{$mainTableName}.manager_id", '=', 'data_id')->whereNull('project_manager.deleted_at');
//        $query->join($teamTableName, "{$mainTableName}.manager_id", '=', "{$teamTableName}.team_project");
    }

    //构建项目自定义字段的查询对象
    private function buildProjectTaskQuery($query)
    {
        $mainTableName = 'project_task';
        $query->rightjoin($mainTableName, "{$mainTableName}.task_id", '=', 'data_id')->whereNull("{$mainTableName}.deleted_at");
    }

    /**
     * 2019-05-05
     *
     * 获取自定义数据总数
     *
     * @param string $tableKey
     * @param array $param
     *
     * @return int
     */
    public function getCustomDataTotal($tableKey, $param)
    {
        $table = $this->getCustomDataTableName($tableKey);
        if(!Schema::hasTable($table)){
            return false;
        }
        $primaryKey = $this->getCustomDataPrimayKey($tableKey);

        $query = DB::table($table);
        if (isset($param['search']) && !empty($param['search'])) {
            if (isset($param['search']['birthday'])) {
                if (count(explode('-', $param['search']['birthday'][0][0])) == 2) {
                    $query = $query->whereRaw("date_format(`birthday`, '%m-%d')>=?", [$param['search']['birthday'][0][0]])
                          ->whereRaw("date_format(`birthday`, '%m-%d')<= ?", [$param['search']['birthday'][0][1]]);
                    unset($param['search']['birthday']);
                }
                if(isset($param['search']['birthday'][1]) && $param['search']['birthday'][1] == ">="){
                    $query = $query->whereRaw("date_format(`birthday`, '%m-%d')>=?", [$param['search']['birthday'][0]]);
                    unset($param['search']['birthday']);
                }
                if(isset($param['search']['birthday'][1]) && $param['search']['birthday'][1] == "<="){
                    $query->whereRaw("date_format(`birthday`, '%m-%d')<=?", [$param['search']['birthday'][0]]);
                    unset($param['search']['birthday']);
                }
            }
            if (isset($param['search']['multiSearch']) && !empty($param['search']['multiSearch'])) {
                $query = $this->scopeMultiWheres($query, $param['search']);
            } else {
                $query = $this->wheres($query, $param['search']);

            }
        }
        if (Schema::hasColumn($table, 'deleted_at') && !isset($param['withTrashed'])) {
            $query = $query->whereNull("{$table}.deleted_at");
        }
        if (!empty($param['filter'])) {
            $query = $this->filterCustomData($param, $query, $table);
        }
        // if (!empty($param['viewPermission']) && Schema::hasColumn($table, 'creator')) {
        //     $query = $this->wheres($query, $param['viewPermission']);
        // }
        if (!empty($param['viewPermission']) && Schema::hasColumn($table, $param['viewPermission']['field'])) {
            $query = $query->where(function ($query) use($param) {
                foreach($param['viewPermission']['value'] as $roleId){
                    $query->orWhereRaw('find_in_set(?,'.security_filter($param['viewPermission']['field']).')',[$roleId]);
                }
            });
        }
        $selectFields = ['COUNT(1) as total'];
        if (isset($param['aggregate']) && is_array($param['aggregate']) && count($param['aggregate']) > 0) {
            foreach ($param['aggregate'] as $field_code) {
                array_push($selectFields, "SUM({$field_code}) as {$field_code}");
            }
        }

        // 设置该参数跳过join主表
        if (Arr::get($param, 'join_main_table') !== false) {
            if (FormModelingService::isProjectCustomKey($tableKey)) {
                $this->buildProjectQuery($query);
            }
            if (FormModelingService::isProjectTaskCustomKey($tableKey)) {
                $this->buildProjectTaskQuery($query);
            }
        }

        return $query->select(DB::raw(implode(', ', $selectFields)))->first();
    }
    /**
     * 处理wherein太长问题
     *
     * @param   array  $param
     * @param   array  $query
     * @return  array
     */
    public function filterCustomData($param, $query, $table)
    {
        $whereIn = [];
        foreach ($param['filter'] as $key => $value) {
            // in查询过长问题
            if (isset($value[1]) && strtoupper($value[1]) == 'IN') {
                if (count($value[0]) < self::MAX_WHERE_IN) {
                    break;
                }
                $whereIn['key'] = $key;
                $whereIn['value'] = $value[0];
                unset($param['filter'][$key]);
                break;
            }
        }
        if (!empty($whereIn)) {
            $this->randNumber = rand() . rand();
            $tableName = $table . $this->randNumber;
            DB::statement("CREATE TEMPORARY TABLE if not exists $tableName (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            // sql 太长导致mysql gone away
            $customerIdArr = array_chunk($whereIn['value'], self::MAX_WHERE_IN, true);
            foreach ($customerIdArr as $key => $item) {
                $ids = implode("),(", $item);
                $temp_sql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($temp_sql);
            }
            $query = $this->wheres($query, $param['filter'])->join("$tableName", $tableName . ".data_id", '=', $whereIn['key']);
        } else {
            $query = $this->wheres($query, $param['filter']);
        }
        return $query;
    }
    /**
     * 根据key获取字段信息
     *
     * @param   string  $id
     *
     * @return  array
     */
    public function getCustomFields($id)
    {
        return $this->entity->where("field_table_key", $id)->get();
    }
    /**
     * 新增自定义数据
     *
     * @param   string  $tableKey  [$tableKey description]
     * @param   array  $data      [$data description]
     * @param   array  $params    [$params description]
     *
     * @return  int
     */
    public function addCustomData($tableKey, $data, $params = [])
    {
        $id = DB::table($this->getCustomDataTableName($tableKey))->insertGetId($data);
        if ($id && isset($params['after'])) {
            $className = isset($params['after'][0]) ? $params['after'][0] : '';
            $method = isset($params['after'][1]) ? $params['after'][1] : '';
            if ($className === '' || $method === '') {
                return $id;
            }
            app($className)->$method([
                'data' => $data,
                'id' => $id,
                'param' => $params,
            ]);
        }
        return $id;
    }
    /**
     * 更新自定义数据
     *
     * @param   string  $tableKey
     * @param   array  $data
     * @param   int  $primaryKey
     * @param   int  $dataId
     *
     * @return  int
     */
    public function updateCustomData($tableKey, $data, $primaryKey, $dataId, $params)
    {
        $query = DB::table($this->getCustomDataTableName($tableKey))->where($primaryKey, $dataId);
        if (isset($params['search'])) {
            $query = $this->wheres($query, $params['search']);
        }
        return $query->update($data);
    }

    /**
     * 更新自定义数据
     *
     * @param   string  $tableKey
     * @param   array  $data
     * @param   int  $primaryKey
     * @param   int  $dataId
     *
     * @return  int
     */
    public function getCustomData($tableKey, $primaryKey, $dataId, $params)
    {
        $query = DB::table($this->getCustomDataTableName($tableKey))->where($primaryKey, $dataId);
        if (isset($params['search'])) {
            $query = $this->wheres($query, $params['search']);
        }
        return $query->get();
    }

    /**
     * 根据条件删除原有数据
     * @param $table
     * @param $params
     * @return bool
     */
    public function deleteCustomDataByParams($table, $params)
    {
        $query = DB::table($table);
        if (isset($params['search'])) {
            $query = $this->wheres($query, $params['search']);
        }
        return $query->delete();
    }

    /**
     * 删除自定义子表数据
     *
     * @param string $tableKey
     * @param string $subTableKey
     * @param int $dataId
     *
     * @return boolean
     */
    public function deleteCustomSubData($tableKey, $subTableKey, $dataId)
    {
        return DB::table($this->getCustomDataSubTableName($tableKey, $subTableKey))->where($this->getCustomDataPrimayKey($tableKey), $dataId)->delete();
    }
    /**
     * 新建自定义子表数据
     *
     * @param string $tableKey
     * @param string $subTableKey
     * @param array $data
     *
     * @return boolean
     */
    public function addCustomSubData($tableKey, $subTableKey, $data)
    {
        return DB::table($this->getCustomDataSubTableName($tableKey, $subTableKey))->insertGetId($data);
    }
    public function editCustomSubData($tableKey, $subTableKey, $data, $dataId)
    {
        return DB::table($this->getCustomDataSubTableName($tableKey, $subTableKey))->where('detail_data_id', $dataId)->update($data);
    }
    public function getCustomSubDataByMainId($tableKey, $subTableKey, $mainDataId)
    {
        return DB::table($this->getCustomDataSubTableName($tableKey, $subTableKey))->where('data_id', $mainDataId)->get();
    }
    public function saveDetailLayoutTotalFields($tableKey, $subTableKey, $field_code, $dataId)
    {
        $sum = DB::table($this->getCustomDataSubTableName($tableKey, $subTableKey))->where($this->getCustomDataPrimayKey($tableKey), $dataId)->sum($field_code);
        $layoutTotalFields = '_total_' . $field_code;
        $table = $this->getCustomDataTableName($tableKey);
        $primaryKey = $this->getCustomDataPrimayKey($tableKey);
        if (Schema::hasColumn($table, $layoutTotalFields)) {
            return DB::table($table)->where($primaryKey, $dataId)->update([$layoutTotalFields => $sum]);
        }
    }
    //更新明细合计历史数据
    public function updateDetailLayoutTotal($tableKey, $subTableKey, $field_code)
    {
        $primaryKey = $this->getCustomDataPrimayKey($tableKey);
        $subTable = $this->getCustomDataSubTableName($tableKey, $subTableKey);
        $layoutTotalFields = '_total_' . $field_code;
        $table = $this->getCustomDataTableName($tableKey);
        if (Schema::hasColumn($table, $layoutTotalFields)) {
            DB::update(
                "UPDATE $table
                left join (SELECT SUM($subTable.$field_code) as
                total,$table.$primaryKey from $table left join $subTable on
                $table.$primaryKey = $subTable.$primaryKey
                GROUP BY $table.$primaryKey) custom_data on custom_data.$primaryKey = $table.$primaryKey
                set $table.$layoutTotalFields = custom_data.total"
            );
        }
    }
    /**
     * 编辑详情数据
     *
     * @param   string  $tableKey
     * @param   array  $mainData
     * @param   int  $dataId
     *
     * @return  boolean
     */
    public function editCustomData($tableKey, $mainData, $dataId)
    {
        return DB::table($this->getCustomDataTableName($tableKey))->where($this->getCustomDataPrimayKey($tableKey), $dataId)->update($mainData);
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
        $primaryKey = $this->getCustomDataPrimayKey($tableKey);
        //批量删除
        $result = DB::table($this->getCustomDataTableName($tableKey))->whereIn($primaryKey, $dataId)->delete();

        if ($result) {
            //清理明细字段中的数据
            $detailFields = $this->getCustomDetailFields($tableKey, ['field_code']);

            //删除明细字段表对应的数据
            foreach ($detailFields as $detailField) {
                if ($this->hasCustomDataSubTable($tableKey, $detailField->field_code)) {
                    // 批量删除
                    $result = DB::table($this->getCustomDataSubTableName($tableKey, $detailField->field_code))->whereIn($primaryKey, $dataId)->delete();
                    if ($result) {
                        break;
                    }
                }
            }
        }
        return $result;
    }
    /**
     * 获取所有自定义明细字段
     *
     * @param string $tableKey
     * @param array $fields
     *
     * @return array
     */
    private function getCustomDetailFields($tableKey, $fields = ['*'])
    {
        return $this->entity->select($fields)->where('field_table_key', $tableKey)->where('field_directive', 'detail-layout')->get();
    }

    /**
     * 判断是否有自定义子数据表
     *
     * @param string $tableKey
     * @param string $subTableKey
     *
     * @return boolean
     */
    public function hasCustomDataSubTable($tableKey, $subTableKey)
    {
        return Schema::hasTable($this->getCustomDataSubTableName($tableKey, $subTableKey));
    }

    /**
     * 获取自定义数据详情
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return object
     */
    public function getCustomDataDetail($tableKey, $dataId, $param = [])
    {
        $isProject = FormModelingService::isProjectCustomKey($tableKey);
        $isTask = FormModelingService::isProjectTaskCustomKey($tableKey);

        $table = $this->getCustomDataTableName($tableKey);
        $primaryKey = $this->getCustomDataPrimayKey($tableKey);
        $isProject && $primaryKey = 'manager_id'; // 项目的主键修改为manager_id，如果是data_id的话，会存在附属表无数据，主表有数据，但却拿不到数据的情况

        $query = DB::table($table)->where($primaryKey, $dataId);
        if (Schema::hasColumn($table, 'deleted_at') && !isset($param['withTrashed'])) {
            $query = $query->whereNull($table . '.deleted_at');
        }
        // if (!empty($param['viewPermission']) && Schema::hasColumn($table, 'creator')) {
        //     $query = $this->wheres($query, $param['viewPermission']);
        // }
        if (!empty($param['viewPermission']) && Schema::hasColumn($table, $param['viewPermission']['field'])) {
            $query = $query->where(function ($query) use($param) {
                foreach($param['viewPermission']['value'] as $roleId){
                    $query->orWhereRaw('find_in_set(?,'.security_filter($param['viewPermission']['field']).')', [$roleId]);
                }
            });
        }
        //项目的自定义字段join其它表
        if ($isProject) {
            $this->buildProjectQuery($query);
        }
        if ($isTask) {
            $this->buildProjectTaskQuery($query);
        }
        return $query->first();

    }

    /**
     * 获取有设置自定义提醒的自定字段菜单
     *
     * @param   int  $parent
     *
     * @return  array
     */
    public function getRemindMenuSet($parent)
    {
        $result = DB::table('custom_menu_config')
            ->where('custom_menu_config.menu_parent', $parent)
            ->get();
        $remindResult = [];
        foreach ($result as $key => $value) {

            $menu_name = $value->menu_name;
            $menu = DB::table("lang_zh_cn")->where("lang_key", $menu_name)->first();
            if (!empty($menu)) {
                $lang_key = $menu->lang_key;
                $result[$key]->lang_key = $lang_key;
            }
            $menuRemind = DB::table('custom_reminds')
                ->leftJoin('custom_fields_table', 'custom_fields_table.field_id', '=', 'custom_reminds.id')
                ->where('custom_reminds.field_table_key', $value->menu_code)->get();
            $result[$key]->remind_set = $menuRemind;
            if (!empty($result[$key]->remind_set)) {
                $remindResult[] = $result[$key];
            }
        }
        return $remindResult;
    }

    public function parseCutomData($tableKey, $primaryKey, $params)
    {
        $tableName = $this->getCustomDataTableName($tableKey);
        if (strpos($tableKey, "project_value") !== false) {
            $primaryKey = "manager_id";
            if (!empty($tableName)) {
                $query = DB::table("project_manager")
                    ->select(['project_manager.*', "$tableName.*"])
//                    ->leftJoin('project_team', 'project_team.team_project', '=', 'project_manager.manager_id')
                    ->leftJoin($tableName, "$tableName.data_id", 'project_manager.manager_id');
            }
        } else {
            $query = DB::table($tableName);
            if (isset($this->module_parse_data_rule[$tableKey])) {
                $relation = $this->module_parse_data_rule[$tableKey];
                if (isset($relation['foreignKey'])) {
                    $primaryKey = $relation['foreignKey'];
                }
                $query->orderBy('created_at', 'desc');
            }
        }
        if (isset($query)) {
            if (isset($params['search_value'])) {
                $query = $query->where($primaryKey, $params['search_value']);
            } else if (isset($params['search']) && !empty($params['search'])) {
                if (isset($this->field_value_relation[$tableKey])) {
                    $fieldValueMap = $this->field_value_relation[$tableKey];
                    foreach ($params['search'] as $key => $search) {
                        if (isset($fieldValueMap[$key])) {
                            $params['search'][$key][0] = $fieldValueMap[$key][$search[0]];
                        }
                    }
                }
                foreach ($params['search'] as $key => $search) {
                    if(empty($key) || $key =="primary_key"){
                        $params['search'][$primaryKey] = $search;
                        unset($params['search'][$key]);
                    }
                    if(strpos($key, 'multiSearch') !== false){
                        foreach ($params['search'][$key] as $k => $v) {
                            if(empty($k) || $k =="primary_key"){
                                $params['search'][$key][$primaryKey] = $v;
                                unset($params['search'][$key][$k]);
                            }
                        }
                    }
                }
                $paramSearch = json_encode($params['search']);
                if (strpos($paramSearch, 'multiSearch') !== false) {
                    $query = $this->scopeMultiWheres($query, $params['search']);
                } else {
                    $query = $this->wheres($query, $params['search']);

                }
            }
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                $query = $query->whereNull($tableName . '.deleted_at');
            }
            return $query->get();
        }
    }

    public function tranferRemindName($id)
    {
        return DB::table("reminds")->whereIn("id", $id)->pluck('reminds')->toArray();
    }

    public function getUniqueCustomData($tableKey, $params = '')
    {
        $tableName = $this->getCustomDataTableName($tableKey);
        if (strpos($tableKey, "project_value") !== false) {
            $query = DB::table("project_manager")
                ->select(['project_manager.*', 'project_team.team_person', "$tableName.*"])
                ->leftJoin('project_team', 'project_team.team_project', '=', 'project_manager.manager_id')
                ->leftJoin($tableName, "$tableName.data_id", 'project_manager.manager_id');
            if (isset($params['search']) && !empty($params['search'])) {
                $query = $this->wheres($query, $params['search']);
            }
            return $query->get()->toArray();
        }

    }

    /**
     * 获取外键菜单
     * @param $primaryKey 自定义字段表示
     * @param $callback 回调函数，自己模块的菜单，如果重新生成文件，会拼装在前面
     * @param $id 主键id,用于获取自己表单内主键，该条数据的value值
     * @param $flag 强制刷新找出最新的
     */
    public static function getCustomTabMenus(string $tableKey, $callback, $id = null, $flag = false): array
    {
        $menuCodes = [];
        $result = self::getFileMenus($tableKey);
        // 文件不存在
        if (empty($result)) {
            [$menuCodes, $result] = self::paraseCustomForeignMenus($tableKey);
            if (!empty($menuCodes)) {
                $menuLists = DB::table(self::CUSTOM_MENU_TABLE)->select(['menu_code'])->whereIn('menu_code', $menuCodes)->get();
                if (!$menuLists->isEmpty()) {
                    foreach ($menuLists as $key => $list) {
                        $tempView = [
                            "custom/list",
                            ['menu_code' => $list->menu_code, 'primary_key' => self::getPrimaryKey($list->menu_code)],
                        ];
                        $result[] = [
                            'key' => $list->menu_code,
                            'view' => $tempView,
                            'menu_code' => $list->menu_code,
                            'count' => '',
                        ];
                    }
                }
            }
            // 保存为文件
            self::saveCustomTabMenus($tableKey, $result);
        }
        self::filterCustomTabs($result); // 过滤无菜单权限的
        // 内置的固定菜单，重复设置外键，不展示菜单
        self::originMenusMerge($result, $callback);
        if (empty($result)) {
            return $result;
        }
        foreach ($result as $key => $item) {
            //写死的不处理
            if (isset($item['title'])) {
                continue;
            }
            $tLang = '';
            // 自己表单内含有外键需要展示详情页
            if (isset($item['foreign_key'])) {
                $tLang = mulit_trans_dynamic("custom_fields_table.field_name." . $item['menu_code'] . "_" . $item['foreign_key']);
                $tableName = $item['menu_code'];
                if (strpos($tableKey, 'project_value')) {
                    $tableName = 'costom_data_' . $tableName;
                }
                self::getCustomForeignValue($id, $tableName, $item['foreign_key'], $item);
                $item['view'] = [
                    "custom/detail",
                    ['menu_code' => $item['key'], 'primary_key' => self::getPrimaryKey($item['menu_code']), 'id' => $item['id']],
                ];
            } else if (isset($item['key'])) {
                if (!$tLang = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $item['key'])) {
                    $tLang = trans($tableKey . '.' . $item['key']);
                }
            }
            $item['title'] = $tLang;
            $result[$key] = $item;
        }
        return self::toggleHideMenus($tableKey, $result);
    }

    private static function filterCustomTabs(&$tabs, $own = null)
    {
        !$own && $own = user();
        if ($tabs) {
            $menuIds = Arr::get($own, 'menus.menu', []);
            $systemMenuIds = config('customfields.detailMenu', []);
            foreach ($tabs as $key => $tab) {
                $menuKey = $tab['key'];
                if (is_numeric($menuKey)) {
                    $menuId = [$menuKey];
                } else if (FormModelingService::isProjectCustomKey($menuKey)) {
                    $menuId = [160];
                } else {
                    $tempMenuId = Arr::get($systemMenuIds, $menuKey, []);
                    $menuId = is_array($tempMenuId) ? $tempMenuId : [$tempMenuId];
                }
                // 无权限则删除；只有明确获取到id的才过滤，如果没获取的就不过滤，避免未知的类型导致强制过滤了
                if ($menuId && !array_intersect($menuId, $menuIds)) {
                    unset($tabs[$key]);
                }
            }
        }
    }

    private static function toggleHideMenus($tableKey, $menus): array
    {
        $hideMenus = self::getHideMenus($tableKey);
        foreach ($menus as $key => $item) {
            if (!isset($item['key'])) {
                $menus[$key]['isShow'] = false;
                continue;
            }
            $menus[$key]['isShow'] = true;
            if (in_array($item['key'], $hideMenus)) {
                $menus[$key]['isShow'] = false;
            }
        }
        return $menus;
    }
    /**
     * 获取隐藏的菜单menu_code
     * @return array
     */
    public static function getHideMenus(string $tableKey): array
    {
        if (!Redis::exists(self::FOREIGN_HIDE_MENUS)) {
            if ($data = DB::table('system_params')->where('param_key', 'customer_hide_menus')->value('param_value')) {
                Redis::hset(self::FOREIGN_HIDE_MENUS, self::HIDE_MENUS, $data);
            };
        }
        $menuLists = json_decode(Redis::hget(self::FOREIGN_HIDE_MENUS, self::HIDE_MENUS), true);
        $result = $menuLists[$tableKey] ?? [];
        /*
        $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::HIDE_MENUS_FILE;
        $result = [];
        if (file_exists($menuPath)) {
        $menuLists = json_decode(file_get_contents($menuPath), true);
        $result = $menuLists[$tableKey] ?? [];
        }
         */
        return $result;
    }
    // 删除文件缓存
    public static function destroyCustomTabMenus(string $tableKey = '')
    {
        if (Redis::exists('customer:foreign_menus')) {
            Redis::del('customer:foreign_menus');

        }
        return true;
        /*
        $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::MENUS_FILE;
        if (file_exists($menuPath)) {
            if (!$tableKey) {
                chmod($menuPath, 0777);
                @file_put_contents($menuPath, '');
                @unlink($menuPath);
            } else {
                $menuLists = json_decode(file_get_contents($menuPath), true);
                if (isset($menuLists[$tableKey])) {
                    unset($menuLists[$tableKey]);
                    file_put_contents($menuPath, json_encode($menuLists));
                }
            }
        }
        return true;
        */
    }

    private static function originMenusMerge(&$menus, $callback): void
    {
        $originMenus = $callback();
        $originMenusKeys = array_column($originMenus, 'key');
//        foreach ($menus as $key => $item) {
//            if (isset($item['key']) && in_array($item['key'], $originMenusKeys)) {
//                unset($menus[$key]);
//            }
//        }
        $menus = array_merge($originMenus, $menus);
    }
    // 保存文件
    public static function saveCustomTabMenus(string $tableKey, array $menus): void
    {
        $allMenus = [];
        if (Redis::exists(self::FOREIGN_MENUS)) {
            $allMenus = json_decode(Redis::hget(self::FOREIGN_MENUS, self::MENUS),true);
        }
        $allMenus[$tableKey] = $menus;
        // 存入到redis当中
        Redis::hset(self::FOREIGN_MENUS, self::MENUS, json_encode($allMenus));

        /*
    $menuDir = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR;
    $menuPath = $menuDir . self::MENUS_FILE;
    if (file_exists($menuPath)) {
    $allMenus = json_decode(file_get_contents($menuPath), true);
    }
    $allMenus[$tableKey] = $menus;
    if (!is_dir($menuDir)) {
    mkdir($menuDir, 0777);
    chmod($menuDir, 0777); //umask（避免用户缺省权限属性与运算导致权限不够）
    }
    file_put_contents($menuPath, json_encode($allMenus));
     */
    }
    // 获取tablekey对应的主键field
    public static function getPrimaryKey(string $tableKey)
    {
        $isDynamic = DB::table(self::CUSTOM_MENU_TABLE)->where('menu_code', $tableKey)->value('is_dynamic');
        if ($isDynamic == 2) {
            return 'data_id';
        }
        $result = DB::select("SHOW COLUMNS FROM $tableKey WHERE `Key` = 'PRI'");

        return isset($result[0]) ? $result[0]->Field : '';
    }
    // 获取外键的值
    public static function getCustomForeignValue($id, string $tableKey, $column, &$config)
    {
        $foreignTable = $config['key'];
        if (!$id) {
            $config['id'] = 0;
            return;
        }
        $table = $tableKey;
        $isDynamic = DB::table(self::CUSTOM_MENU_TABLE)->where('menu_code', $tableKey)->value('is_dynamic');
        if ($isDynamic == 2) {
            $table = 'custom_data_' . $table;
        }
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            $config['id'] = 0;
            return;
        }
        $primaryKey = self::getPrimaryKey($tableKey);
        $id = DB::table($table)->where([$primaryKey => $id])->value($column);
        if (!$id) {
            $config['id'] = 0;
            return;
        }
        // 项目单独拿值，因为$foreignTable=project_value_,并不带具体的数值，这里替换掉
        if (FormModelingService::isProjectCustomKey($foreignTable)) {
            $project = ProjectManagerEntity::buildQuery()->find($id);
            if ($project) {
                $config['key'] .= $project->manager_type; // 拼接正确类型
                $config['id'] = $id;
            } else {
                $config['id'] = 0;
            }
        } else {
            $data = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getCustomDataDetail($foreignTable, $id);
            $config['id'] = empty($data) ? 0 : $id;
        }

        return;
    }

    public static function getFileMenus(string $tableKey)
    {
        $result = [];
        if (Redis::exists('customer:foreign_menus')) {
            $allMenus = json_decode(Redis::hget('customer:foreign_menus', 'menus'), true);
            $result = $allMenus[$tableKey] ?? [];
        }
        /*
        $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::MENUS_FILE;
        if (file_exists($menuPath)) {
        $allMenus = json_decode(file_get_contents($menuPath), true);
        $result = $allMenus[$tableKey] ?? [];
        }
         */
        return $result;
    }

    public static function paraseCustomForeignMenus(string $tableKey)
    {
        $menuCodes = $result = [];
        $fieldLists = DB::table('custom_fields_table')->select(['field_table_key', 'field_options', 'field_code'])->where('is_foreign_key', 1)->whereNull('deleted_at')->get();
        if (!$fieldLists->isEmpty()) {
            foreach ($fieldLists as $key => $item) {
                $fieldOptions = json_decode($item->field_options, true);
                $tType = $fieldOptions['type'] ?? '';
                if ($tType != 'selector') {
                    continue;
                }
                $tSelectorConfigs = $fieldOptions['selectorConfig'] ?? [];
                // 根据选择器配置获取tablekey
                $tTableKey = self::parseTableKeyBySelectorConfigs($tSelectorConfigs);
                // 自己的页面设置了外键
                if ($tTableKey == 'contract') {
                    $tTableKey = 'contract_t';
                }
                if ($item->field_table_key == $tableKey) {
                    if (!$tTableKey) {
                        continue;
                    }
                    $result[] = [
                        'key' => $tTableKey,
                        'foreign_key' => $item->field_code,
                        'menu_code' => $item->field_table_key,
                    ];
                    continue;
                }
                // 其它页面设置了外键，设置的不是当前tablekey的跳过
                if ($tTableKey != $tableKey && !($tTableKey == 'project_value_' && strpos($tableKey, 'project_value_') === 0)) {
                    continue;
                }
                $menuCodes[] = $item->field_table_key;
            }
        }
        return [$menuCodes, $result];
    }
    // 读取配置，根据选择器配置获取tablekey
    public static function parseTableKeyBySelectorConfigs(array $selectConfigs)
    {
        $category = $selectConfigs['category'] ?? '';
        $type = $selectConfigs['type'] ?? '';
        $configs = config('customfields.menuForeignKey');
        return $configs[$category . '_' . $type] ?? '';
    }
    // 菜单显示隐藏切换,记录再hide_menu.txt文件中
    public static function toggleCustomTabMenus(string $tableKey, string $menuCode): bool
    {
        $menuLists = DB::table('system_params')->where('param_key', 'customer_hide_menus')->value('param_value');
        $menuLists = $menuLists ? json_decode($menuLists, true) : [];
        if (!isset($menuLists[$tableKey])) {
            $menuLists[$tableKey] = [];
        }
        if (in_array($menuCode, $menuLists[$tableKey])) {
            $menuLists[$tableKey] = array_diff($menuLists[$tableKey], [$menuCode]);
        } else {
            array_push($menuLists[$tableKey], $menuCode);
        }
        $menuLists[$tableKey] = array_values($menuLists[$tableKey]);
        array_unique($menuLists[$tableKey]);
        $menuLists = json_encode($menuLists);
        if (DB::table('system_params')->where('param_key', 'customer_hide_menus')->update(['param_value' => $menuLists])) {
            // 更新成功写入缓存
            Redis::hset(self::FOREIGN_HIDE_MENUS, self::HIDE_MENUS, $menuLists);
        };
        /*
        $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::HIDE_MENUS_FILE;
        $menuLists = [];
        if (file_exists($menuPath)) {
        $menuLists = json_decode(file_get_contents($menuPath), true);
        }
        if (!isset($menuLists[$tableKey])) {
        $menuLists[$tableKey] = [];
        }
        if (in_array($menuCode, $menuLists[$tableKey])) {
        $menuLists[$tableKey] = array_diff($menuLists[$tableKey], [$menuCode]);
        } else {
        array_push($menuLists[$tableKey], $menuCode);
        }
        $menuLists[$tableKey] = array_values($menuLists[$tableKey]);
        array_unique($menuLists[$tableKey]);
        file_put_contents($menuPath, json_encode($menuLists));
         */
        return true;
    }

    public function deleteLayoutFields($tableKey, $field_code)
    {
        //删除列表字段
        $this->deleteListLayoutFields($tableKey, $field_code);
        //删除模版字段
        $this->deleteLayoutFormFields($tableKey, $field_code);

    }
    //删除列表字段
    public function deleteListLayoutFields($tableKey, $field_code)
    {
        $listLayouts = DB::table('custom_template')->where('table_key', $tableKey)->where('template_type', 1)->get();
        foreach ($listLayouts as $key => $listLayout) {
            $content = json_decode($listLayout->content);
            $afterContent = [];
            if (isset($content->list) && !empty($content->list)) {
                if ($list = $this->deleteArrayValue($content->list, $field_code)) {
                    $afterContent['list'] = $list;
                }
            }
            if (isset($content->order) && !empty($content->order)) {
                if ($order = $this->deleteArrayValue($content->order, $field_code)) {
                    $afterContent['order'] = $order;
                }
            }
            if (isset($content->filter) && !empty($content->filter)) {
                if ($filter = $this->deleteArrayValue($content->filter, $field_code)) {
                    $afterContent['filter'] = $filter;
                }
            }
            if (isset($content->fuzzy) && !empty($content->fuzzy)) {
                if ($fuzzy = $this->deleteArrayValue($content->fuzzy, $field_code)) {
                    $afterContent['fuzzy'] = $fuzzy;
                }
            }
            if (isset($content->field) && !empty($content->field)) {
                if ($field = $this->deleteArrayValue($content->field, $field_code)) {
                    $afterContent['field'] = $field;
                }
            }
            if (isset($content->advanced) && !empty($content->advanced)) {
                if ($advanced = $this->deleteArrayValue($content->advanced, $field_code)) {
                    $afterContent['advanced'] = $advanced;
                }
            }
            $extra = json_decode($listLayout->extra);
            $afterExtra = [];
            if (isset($extra->list) && !empty($extra->list)) {
                if ($list = $this->deleteArrayValue($extra->list, $field_code, 'key')) {
                    $afterExtra['list'] = $list;
                }
            }
            if (isset($extra->defaultOrder) && !empty($extra->defaultOrder)) {
                if ($defaultOrder = $this->deleteArrayValue($extra->defaultOrder, $field_code, 'other')) {
                    $afterExtra['defaultOrder'] = $defaultOrder;
                }
            }
            if (isset($extra->item) && !empty($extra->item)) {
                $item = $extra->item;
                if (isset($item->primary->fields) && !empty($item->primary->fields)) {
                    $primary = $this->deleteArrayValue($item->primary->fields, $field_code);
                    if ($primary) {
                        $afterExtra['item']['primary'] = ['fields' => $primary];
                    }
                }
                if (isset($item->remark->fields) && !empty($item->remark->fields)) {
                    $remark = $this->deleteArrayValue($item->remark->fields, $field_code);
                    if ($remark) {
                        $afterExtra['item']['remark'] = ['fields' => $remark];
                    }
                }
                if (isset($item->secondary->fields) && !empty($item->secondary->fields)) {
                    $secondary = $this->deleteArrayValue($item->secondary->fields, $field_code);
                    if ($secondary) {
                        $afterExtra['item']['secondary'] = ['fields' => $secondary];
                    }
                }
                if (isset($item->tag->fields) && !empty($item->tag->fields)) {
                    $tag = $this->deleteArrayValue($item->tag->fields, $field_code);
                    if ($tag) {
                        $afterExtra['item']['tag'] = ['fields' => $tag];
                    }
                }
                if (isset($item->time->fields) && !empty($item->time->fields)) {
                    $time = $this->deleteArrayValue($item->time->fields, $field_code);
                    if ($time) {
                        $afterExtra['item']['time'] = ['fields' => $time];
                    }
                }
            }
            $update = ['content' => json_encode($afterContent)];
            if (empty($afterExtra)) {
                $update['extra'] = '';
            } else {
                $update['extra'] = json_encode($afterExtra);
            }
            DB::table('custom_template')->where('template_id', $listLayout->template_id)->update($update);

        }
    }
    public function deleteLayoutFormFields($tableKey, $field_code)
    {
        $listLayouts = DB::table('custom_template')->where('table_key', $tableKey)->where('template_type', 2)->get();
        foreach ($listLayouts as $key => $listLayout) {
            $content = json_decode($listLayout->content);
            if (isset($content->tabs)) {
                $this->deleteTabFields($content->tabs, $field_code);
            } else {
                foreach ($content as $key => $value) {
                    if (is_string($value) && $value === $field_code) {
                        unset($content[$key]);
                        continue;
                    } else if (isset($value->tabs)) {
                        $this->deleteTabFields($value->tabs, $field_code);
                    } else {
                        if (isset($value->key) && $value->key === $field_code) {
                            unset($content[$key]);
                            continue;
                        }
                        if (isset($value->fields)) {
                            foreach ($value->fields as $k => $v) {
                                if ($v->key === $field_code) {
                                    unset($content[$key]->fields[$k]);
                                    $content[$key]->fields = array_values($content[$key]->fields);
                                    continue;
                                }
                            }
                        }
                    }
                }
                $content = array_values($content);
            }
            $extra = json_decode($listLayout->extra);
            if (!isset($extra->tabs)) {
                $extra = $this->deleteArrayValue($extra, $field_code, 'key');
            }
            $update = ['content' => json_encode($content)];
            if (empty($extra)) {
                $update['extra'] = '';
            } else {
                $update['extra'] = json_encode($extra);
            }
            DB::table('custom_template')->where('template_id', $listLayout->template_id)->update($update);
        }
    }
    public function deleteTabFields($tabs, $field_code)
    {
        foreach ($tabs as $k => $v) {
            if (isset($v->fields)) {
                foreach ($v->fields as $f => $field) {
                    if (is_string($field) && $field === $field_code) {
                        unset($v->fields[$f]);
                        continue;
                    } else {
                        if (isset($field->key) && $field->key === $field_code) {
                            unset($v->fields[$f]);
                            continue;
                        }
                        if (isset($field->fields)) {
                            foreach ($field->fields as $n => $m) {
                                if ($m->key === $field_code) {
                                    unset($v->fields[$f]->fields[$n]);
                                    $v->fields[$f]->fields = array_values($v->fields[$f]->fields);
                                    continue;
                                }
                            }
                        }
                    }
                }
                $v->fields = array_values($v->fields);
                $tabs[$k] = $v;
            }
        }
    }

    public function deleteArrayValue($arr, $field_code, $type = 'value')
    {
        if (!empty($arr)) {
            $arr = (array) $arr;
            foreach ($arr as $key => $value) {
                if ($type == 'other') {
                    if ($value->key === $field_code) {
                        unset($arr[$key]);
                        continue;
                    }
                } else {
                    if (isset($value->key) && $value->key === $field_code) {
                        unset($arr[$key]);
                        continue;
                    }
                    if ($$type === $field_code) {
                        unset($arr[$key]);
                        continue;
                    }
                }
            }
            if ($type != 'key') {
                $arr = array_values($arr);
            }
            return $arr;
        }

    }

    public function getTemplateLanguage($id, $table = 'lang_zh_cn')
    {
        return DB::table($table)->where('table', 'custom_template')->where('option', $id)->get();
    }

    public function checkDataUnique($data)
    {
        // 修改项目系统字段验证唯一性的表
        if (strpos($data['table'], 'project_value_') !== false && Arr::get($data, 'is_system') == 1) {
            $data['table'] = 'project_manager';
        }
        $table = $data['table'];
        $field = $data['field'];
        $value = $data['value'];
        if(is_array($value)){
            return 1;
        }
        if (!is_string($value)) {
            $value = "$value";
        }
        if (isset($data['primaryValue']) && !emptyWithoutZero($data['primaryValue'])) {
            //编辑
            $res = DB::table($table)->where($field, $value)->where($data['primaryKey'], '<>', $data['primaryValue']);
        } else {
            //新建
            $res = DB::table($table)->where($field, $value);
        }
        if (Schema::hasColumn($table, 'deleted_at') && $table != 'customer' && $table != 'income_expense_plan' && $table != 'office_supplies_storage' && $table != 'contract_t') {
            $res = $res->whereNull('deleted_at')->count();
        } else if($table == 'contract_t'){
            $res = $res->whereNull('deleted_at')->where('recycle_status',0)->count();
        }else{
            $res = $res->count();
        }
        if ($res > 0) {
            return 0;
        }
        return 1;
    }
    public function getSettingLayout($tableKey)
    {
       return  DB::table('custom_layout_setting')->where('table_key',$tableKey)->get();
    }
    public function deleteSettingLayout($tableKey)
    {
       return  DB::table('custom_layout_setting')->where('table_key',$tableKey)->delete();
    }
}
