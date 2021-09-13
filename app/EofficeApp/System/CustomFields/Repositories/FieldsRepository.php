<?php
namespace App\EofficeApp\System\CustomFields\Repositories;

use DB;
use Schema;

/**
 *@自定义字段资源库类
 *
 * @author 李志军
 */
class FieldsRepository
{
    private $charset = 'utf8'; //数据表字符集

    private $limit;

    /** @var int 默认列表页 */
    private $page = 0;

    /** @var array  默认排序 */
    private $orderBy = ['created_at' => 'desc'];

    private $dataTablePrefix = 'custom_data_'; //数据表前缀

    private $fieldTableName = 'custom_fields_table';

    private $subDataTableName = [];
    private $dataTableName    = [];
    private $noPrefixTables   = [];
    private $randNumber       = '';

    const MAX_WHERE_IN = 5000;

    const CUSTOM_FIELD_TABLE = 'custom_fields_table';
    const CUSTOM_MENU_TABLE  = 'custom_menu_config';
    const MENUS_FILE         = 'menu.txt';
    const HIDE_MENUS_FILE    = 'hide_menu.txt';

    private $module_parse_data_rule = [
        'book_manage'             => [
            'foreignKey' => 'book_id',
        ],
        'office_supplies_storage' => [
            'foreignKey' => 'office_supplies_id',
        ],
        'archives_file'           => [
            'foreignKey' => 'file_id',
        ],
        'archives_borrow'         => [
            'sort' => 'created_at',
        ],
        'archives_appraisal'      => [
            'sort' => 'created_at',
        ],
    ];

    private $field_value_relation = [
        'archives_appraisal' => [
            'appraisal_type' => [
                1 => 'file', // 文件
                2 => 'volume', // 案卷
            ],
        ],
        'archives_borrow'    => [
            'borrow_type' => [
                1 => 'file', // 文件
                2 => 'volume', // 案卷
            ],
        ],
    ];

    public function __construct()
    {
        $this->limit = config('eoffice.pagesize');
    }
    /**
     * 2017-08-10
     *
     * 获取自定义菜单
     *
     * @param string $menuCode
     * @param array $fields
     *
     * @return object
     */
    public function getCustomMenu($menuCode, $fields = ['*'])
    {
        return DB::table('custom_menu_config')->select($fields)->where('menu_code', $menuCode)->first();
    }
    /**
     * 2017-08-10
     *
     * 获取自定义菜单所属模块
     *
     * @param string $menuCode
     * @param array $fields
     *
     * @return object
     */
    public function getCustomModule($menuCode, $fields = ['*'])
    {
        $menu = $this->getCustomMenu($menuCode, ['menu_parent']);

        return DB::table('menu')->select($fields)->where('menu_id', $menu->menu_parent)->first();
    }
    /**
     * 2017-08-07
     *
     * 新建自定义字段
     *
     * @param array $fieldData
     *
     * @return int
     */
    public function addCustomField($fieldData)
    {
        $insertId = DB::table($this->fieldTableName)->insertGetId($fieldData);
        if (isset($fieldData['field_options']) && !empty($fieldData['field_options'])) {
            $field_options = json_decode($fieldData['field_options']);
            if (isset($field_options->timer)) {
                $fieldData['field_id'] = $insertId;
                $insert                = $this->getFormCustomRemindsData($fieldData, true);
                DB::table("custom_reminds")->insertGetId($insert);
            }
        }
        return $insertId;
    }

    public function getFormCustomRemindsData($data, $add = false)
    {
        $field_options = json_decode($data['field_options']);
        $timer         = $field_options->timer;
        if (isset($timer->previous)) {
            $type = 'previous';
        } elseif (isset($timer->period)) {
            $type = 'period';
        } else {
            $type = 'delay';
        }
        $extra      = json_encode($field_options->timer);
        $field_hide = isset($data['field_hide']) ? $data['field_hide'] : 0;
        $fieldData  = [
            'reminds_select'  => isset($timer->method) ? implode(',', $timer->method) : '',
            'content'         => isset($timer->content) ? $timer->content : '',
            'target'          => isset($timer->target) ? $timer->target : '',
            'type'            => $type,
            'option'          => isset($timer->$type) ? json_encode($timer->$type) : '',
            'extra'           => $extra,
            'field_hide'      => $field_hide,
            'field_directive' => isset($data['field_directive']) ? $data['field_directive'] : '',
        ];
        $fieldData['field_table_key'] = $data['field_table_key'];
        $fieldData['field_code']      = $data['field_code'];
        if ($add) {
            $fieldData['created_at'] = date('Y-m-d H:i:s');
            $fieldData['id']         = $data['field_id'];
        }
        return $fieldData;

    }

    /**
     * 2017-08-07
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
        if (isset($fieldData['field_options']) && !empty($fieldData['field_options'])) {
            $field_options = json_decode($fieldData['field_options']);
            if (isset($field_options->timer)) {
                $insert         = $this->getFormCustomRemindsData($fieldData);
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
        return DB::table($this->fieldTableName)->where('field_id', $fieldId)->update($fieldData);
    }

    /**
     * 2017-08-07
     *
     * 删除自定义字段
     *
     * @param int $fieldId
     *
     * @return boolean
     */
    public function deleteCustomField($fieldId)
    {
        DB::table("custom_reminds")->where('id', $fieldId)->delete();
        return DB::table($this->fieldTableName)->where('field_id', $fieldId)->delete();
    }
    /**
     * 2017-08-07
     *
     * 显示一个字段的详情信息
     *
     * @param int $fieldId
     *
     * @return object
     */
    public function showCustomField($fieldId)
    {
        return DB::table($this->fieldTableName)->where('field_id', $fieldId)->first();
    }
    /**
     * 2017-08-10
     *
     * 判断字段名称是否存在
     *
     * @param string $fieldName
     * @param string $tableKey
     * @param int $fieldId
     *
     * @return int
     */
    public function hasCustomFieldsByName($fieldName, $tableKey, $fieldId = 0)
    {
        $query = DB::table($this->fieldTableName)->where('field_table_key', $tableKey)->where('field_name', $fieldName);

        if ($fieldId) {
            $query->where('field_id', '!=', $fieldId);
        }

        return $query->count();
    }
    /**
     * 2017-08-07
     *
     * 新增数据表列
     *
     * @param array $fieldData
     * @param string $tableKey
     *
     * @return boolean
     */
    public function addCustomColumn($fieldData, $tableKey)
    {
        $customDataTable = $this->getCustomDataTableName($tableKey);

        if (!Schema::hasColumn($customDataTable, $fieldData['field_code'])) {
            Schema::table($customDataTable, function ($table) use ($fieldData) {
                $comment = isset($fieldData['field_explain']) ? $fieldData['field_explain'] : '';

                if ($fieldData['field_data_type'] == 'text') {
                    $table->text($fieldData['field_code'])->comment($comment)->nullable();
                } else {
                    $table->string($fieldData['field_code'], 255)->comment($comment)->nullable();
                }
            });
        }
        return Schema::hasColumn($customDataTable, $fieldData['field_code']);
    }

    public function addLayoutColumn($fieldData, $customDataTable)
    {
        if (!Schema::hasColumn($customDataTable, $fieldData['field_code'])) {
            Schema::table($customDataTable, function ($table) use ($fieldData) {
                $comment = isset($fieldData['field_explain']) ? $fieldData['field_explain'] : '';

                if ($fieldData['field_data_type'] == 'text') {
                    $table->text($fieldData['field_code'])->comment($comment)->nullable();
                } else {
                    $table->string($fieldData['field_code'], 255)->comment($comment)->nullable();
                }
            });
        }
        return Schema::hasColumn($customDataTable, $fieldData['field_code']);
    }

    /**
     * 2017-08-07
     *
     * 删除自定义字段数据表列
     *
     * @param sting $column
     * @param sting $tableKey
     *
     * @return boolean
     */
    public function deleteCustomDataTableColumn($column, $tableKey)
    {
        $customDataTable = $this->getCustomDataTableName($tableKey);

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
     * 2017-08-07
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
            $table->softDeletes();
            $table->timestamps();
        });

        return Schema::hasTable($customDataTable);
    }
    /**
     * 2017-08-08
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
     * 2017-08-08
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
     * 2017-08-08
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
     * 2017-08-08
     *
     * 创建自定义数据子表
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
                    $comment = isset($field['field_explain']) ? $field['field_explain'] : '';

                    if ($field['field_directive'] == 'text') {
                        $table->text($field['field_code'])->comment($comment)->nullable();
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
     * 2017-08-08
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
    /**
     * 2017-08-08
     *
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
     * 获取外键字段
     *
     * @param string $tableKey
     *
     * @return object
     */
    public function getForeignKeyField($tableKey)
    {
        $result = DB::table($this->fieldTableName)->where('field_table_key', $tableKey)->where('is_foreign_key', 1)->first();

        return $result ? $result->field_code : false;
    }
    /**
     * 2017-08-08
     *
     * 获取自定义字段列表
     *
     * @param string $tableKey
     * @param array $param
     * @return array
     */
    public function listCustomFields($tableKey, $param)
    {
        $query = DB::table($this->fieldTableName)->where('field_table_key', $tableKey);

        if (isset($param['fields']) && !empty($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        if (isset($param['search']) && !empty($param['search'])) {
            $query = $this->wheres($query, $param['search']);
        }
        return $query->orderBy('field_sort', 'asc')->get();
    }
    /**
     * 2017-08-08
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
        $table      = $this->getCustomDataTableName($tableKey);
        $primaryKey = $this->getCustomDataPrimayKey($tableKey);
        $query      = DB::table($table);
        if (isset($param['search']) && !empty($param['search'])) {
            if (isset($param['search']['birthday'])) {
                if (count(explode('-', $param['search']['birthday'][0][0])) == 2) {
                    $query = $query->whereRaw("substring_index(birthday, '-', -2) between '" . $param['search']['birthday'][0][0] . "' and '" . $param['search']['birthday'][0][1] . "'");
                    unset($param['search']['birthday']);
                }
            }
            $query = $this->wheres($query, $param['search']);
        }
        if (Schema::hasColumn($table, 'deleted_at') && !isset($param['withTrashed'])) {
            $query = $query->whereNull('deleted_at');
        }
        if (!empty($param['filter'])) {
            $whereIn = [];
            foreach ($param['filter'] as $key => $value) {
                // in查询过长问题
                if (isset($value[1]) && strtoupper($value[1]) == 'IN') {
                    if (count($value[0]) < self::MAX_WHERE_IN) {
                        break;
                    }
                    $whereIn['key']   = $key;
                    $whereIn['value'] = $value[0];
                    unset($param['filter'][$key]);
                    break;
                }
            }
            if (!empty($whereIn)) {
                $this->randNumber = rand() . rand();
                $tableName        = $table . $this->randNumber;
                DB::statement("CREATE TEMPORARY TABLE if not exists $tableName (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
                // sql 太长导致mysql gone away
                $customerIdArr = array_chunk($whereIn['value'], self::MAX_WHERE_IN, true);
                foreach ($customerIdArr as $key => $item) {
                    $ids      = implode("),(", $item);
                    $temp_sql = "insert into {$tableName} (data_id) values ({$ids});";
                    DB::insert($temp_sql);
                }
                $query = $this->wheres($query, $param['filter'])->join("$tableName", $tableName . ".data_id", '=', $whereIn['key']);
            } else {
                $query = $this->wheres($query, $param['filter']);
            }
        }
        $selectFields = ['COUNT(1) as total'];
        if (isset($param['aggregate']) && is_array($param['aggregate']) && count($param['aggregate']) > 0) {
            foreach ($param['aggregate'] as $field_code) {
                array_push($selectFields, "SUM({$field_code}) as {$field_code}");
            }
        }
        return $query->select(DB::raw(implode(', ', $selectFields)))->first();
    }
    /**
     * 2017-08-08
     *
     * @param string $tableKey
     * @param array $param
     *
     * @return array
     */
    public function getCustomDataList($tableKey, $param)
    {
        $table   = $this->getCustomDataTableName($tableKey);
        $limit   = (isset($param['limit']) && $param['limit']) ? $param['limit'] : $this->limit;
        $page    = (isset($param['page']) && $param['page']) ? $param['page'] : $this->page;
        $orderBy = (isset($param['order_by']) && !empty($param['order_by'])) ? $param['order_by'] : [$table . '.created_at' => 'desc']; //项目有join其它表，因此加上表名
        $fields  = (isset($param['fields']) && !empty($param['fields'])) ? $param['fields'] : ['*'];
        if ($tableKey == "office_supplies_storage") {
            $query = DB::table($table)->select("office_supplies_storage.*", "office_supplies.stock_surplus", "office_supplies.remind_max as storage_remind_max", "office_supplies.remind_min as storage_remind_min")
                ->leftJoin('office_supplies', 'office_supplies.id', '=', 'office_supplies_storage.office_supplies_id');
            $query = $query->whereNull('office_supplies_storage.deleted_at');
            if (isset($param['search']['type_id'])) {
                $type_id = $param['search']['type_id'];
                unset($param['search']['type_id']);
                $param['search']['office_supplies.type_id'] = $type_id;
            }
        } else {
            $query = DB::table($table)->select($fields);
            if (Schema::hasColumn($table, 'deleted_at') && !isset($param['withTrashed'])) {
                $query = $query->whereNull($table . '.deleted_at');
            }
        }

        if (isset($param['search']) && !empty($param['search'])) {
            if (isset($param['search']['birthday'])) {
                if (count(explode('-', $param['search']['birthday'][0][0])) == 2) {
                    $query = $query->whereRaw("substring_index(birthday, '-', -2) between '" . $param['search']['birthday'][0][0] . "' and '" . $param['search']['birthday'][0][1] . "'");
                    unset($param['search']['birthday']);
                }
            }
            $query = $this->wheres($query, $param['search']);
        }
        $tableName = '';
        if (!empty($param['filter'])) {
            $whereIn = [];
            foreach ($param['filter'] as $key => $value) {
                // in查询过长问题
                if (isset($value[1]) && ($value[1] == 'in' || $value[1] == 'IN')) {
                    if (count($value[0]) < self::MAX_WHERE_IN) {
                        break;
                    }
                    $whereIn['key']   = $key;
                    $whereIn['value'] = $value[0];
                    unset($param['filter'][$key]);
                    break;
                }
            }
            if (!empty($whereIn)) {
                if (!$this->randNumber) {
                    $this->randNumber = rand() . rand();
                    $tableName        = $table . $this->randNumber;
                    DB::statement("CREATE TEMPORARY TABLE  if not exists $tableName (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
                    $customerIdArr = array_chunk($whereIn['value'], self::MAX_WHERE_IN, true);
                    foreach ($customerIdArr as $key => $item) {
                        $ids      = implode("),(", $item);
                        $temp_sql = "insert into {$tableName} (data_id) values ({$ids});";
                        DB::insert($temp_sql);
                    }
                }
                $tableName = $table . $this->randNumber;
                $query     = $this->wheres($query, $param['filter'])->join("$tableName", $tableName . ".data_id", '=', $whereIn['key']);
            } else {
                $query = $this->wheres($query, $param['filter']);
            }

        }
        $query = $this->orders($query, $orderBy);
        $query = $this->parsePage($query, $page, $limit);

        //项目的自定义字段join其它表
        if (strpos($tableKey, 'project_value_') !== false) {
            $this->buildProjectQuery($query, $table);
        }

        return $query->get()->toArray();
    }

    //构建项目自定义字段的查询对象
    private function buildProjectQuery($query)
    {
        $mainTableName = 'project_manager';
        $teamTableName = 'project_team';
        $query->join($mainTableName, "{$mainTableName}.manager_id", '=', 'data_id');
        $query->join($teamTableName, "{$mainTableName}.manager_id", '=', "{$teamTableName}.team_project");
    }
    /**
     * 2017-08-08
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
     * 2017-08-08
     *
     * 排序
     *
     * @param object $query
     * @param array $orders
     *
     * @return object
     */
    private function orders($query, $orders)
    {
        if (!empty($orders)) {
            foreach ($orders as $field => $order) {
                $query = $query->orderBy($field, $order);
            }
        }

        return $query;
    }
    /**
     * 2017-08-08
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
            'between'     => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in'          => 'whereIn',
            'not_in'      => 'whereNotIn',
            'null'        => 'whereNull',
            'not_null'    => 'whereNotNull',
            'or'          => 'orWhere',
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
                $query = $query->where($field, $operator, $value);
            }
        }
        return $query;
    }
    /**
     * 2017-08-08
     *
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
     * 2017-08-08
     *
     * 新建自定义数据
     *
     * @param string $tableKey
     * @param array $data
     *
     * @return int
     */
    public function addCustomData($tableKey, $data, $params = [])
    {
        $id = DB::table($this->getCustomDataTableName($tableKey))->insertGetId($data);
        if ($id && isset($params['after'])) {
            $className = isset($params['after'][0]) ? $params['after'][0] : '';
            $method    = isset($params['after'][1]) ? $params['after'][1] : '';
            if ($className === '' || $method === '') {
                return $id;
            }
            app($className)->$method([
                'data'  => $data,
                'id'    => $id,
                'param' => $params,
            ]);
        }
        return $id;
    }

    public function updateCustomData($tableKey, $data, $primaryKey, $dataId)
    {
        return DB::table($this->getCustomDataTableName($tableKey))->where($primaryKey, $dataId)->update($data);
    }
    /**
     * 2017-08-08
     *
     * 编辑自定义数据
     *
     * @param string $tableKey
     * @param array $mainData
     * @param int $dataId
     *
     * @return boolean
     */
    public function editCustomData($tableKey, $mainData, $dataId)
    {
        return DB::table($this->getCustomDataTableName($tableKey))->where($this->getCustomDataPrimayKey($tableKey), $dataId)->update($mainData);
    }
    /**
     * 2017-08-08
     *
     * 获取自定义数据详情
     *
     * @param string $tableKey
     * @param int $dataId
     *
     * @return object
     */
    public function getCustomDataDetail($tableKey, $dataId)
    {
        $table = $this->getCustomDataTableName($tableKey);
        if ($tableKey == "office_supplies_storage") {
            return DB::table($this->getCustomDataTableName($tableKey))
                ->select("office_supplies_storage.id", "office_supplies_storage.*", "office_supplies.stock_surplus", "office_supplies.remind_max as storage_remind_max", "office_supplies.remind_min as storage_remind_min")
                ->leftJoin('office_supplies', 'office_supplies.id', '=', 'office_supplies_storage.office_supplies_id')
                ->where('office_supplies_storage.id', $dataId)->first();
        } else {
            $query = DB::table($table)->where($this->getCustomDataPrimayKey($tableKey), $dataId);
            if (Schema::hasColumn($table, 'deleted_at')) {
                $query = $query->whereNull($table . '.deleted_at');
            }
            //项目的自定义字段join其它表
            if (strpos($tableKey, 'project_value_') !== false) {
                $this->buildProjectQuery($query, $tableKey);
            }
            return $query->first();
        }

    }
    /**
     * 2017-08-11
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

        $result = DB::select("SHOW COLUMNS FROM $tableKey WHERE `Key` = 'PRI'");

        return $array[$tableKey] = $result[0]->Field;
    }
    /**
     * 2017-08-08
     *
     * 新建自定义子表数据
     *
     * @param string $tableKey
     * @param string $subTableKey
     * @param array $data
     *
     * @return type
     */
    public function addCustomSubData($tableKey, $subTableKey, $data)
    {
        return DB::table($this->getCustomDataSubTableName($tableKey, $subTableKey))->insert($data);
    }
    /**
     * 2017-08-08
     *
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
     * 2017-08-08
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
     * 2017-08-08
     *
     * 获取所有自定义明细字段
     *
     * @param string $tableKey
     * @param array $fields
     *
     * @return array
     */
    private function getCustomDetailFields($tableKey, $fields = ['*'])
    {
        return DB::table($this->fieldTableName)->select($fields)->where('field_table_key', $tableKey)->where('field_directive', 'detail-layout')->get();
    }

    //old code=============================================================================//
    /**
     * @获取自定义字段列表
     * @param type $param
     * @return array | 自定义字段列表
     */
    public function listFields($param, $tableKey, $module)
    {
        $query = DB::table($module . '_fields')->where('field_table_key', $tableKey);

        if (isset($param['fields']) && !empty($param['fields'])) {
            $query = $query->select($param['fields']);
        }

        if (isset($param['search']) && !empty($param['search'])) {
            foreach ($param['search'] as $key => $value) {
                $operator = isset($value[1]) ? $value[1] : '=';

                $val = $operator != 'like' ? $value[0] : '%' . $value[0] . '%';

                $query = $query->where($key, $operator, $val);
            }
        }

        return $query->orderBy('field_sort', 'asc')->get();
    }
    /**
     * @创建自定义字段表
     * @return boolean
     */
    public function createEmptySubTable($tableKey)
    {
        if (Schema::hasTable($tableKey . "_sub")) {
            return true;
        }

        Schema::create($tableKey . "_sub", function ($table) use ($tableKey) {
            $table->charset = $this->charset;
            $table->integer($tableKey . "_id");
            $table->primary($tableKey . "_id");
        });

        return Schema::hasTable($tableKey . "_sub");
    }
    /**
     * @插入自定义字段
     * @param type $fieldData
     * @return 自定义字段id
     */
    public function insertFields($fieldData, $module)
    {
        return DB::table($module . '_fields')->insertGetId($fieldData);
    }
    /**
     * @更新自定义字段
     * @param type $fieldData
     * @param type $fieldId
     * @return boolean
     */
    public function updateFields($fieldData, $fieldId, $module)
    {
        return DB::table($module . '_fields')->where('field_id', $fieldId)->update($fieldData);
    }
    /**
     * @获取自定义字段详情
     * @param type $fieldId
     * @return 字段详情
     */
    public function showFields($fieldId, $module)
    {
        return DB::table($module . '_fields')->where('field_id', $fieldId)->first();
    }
    /**
     * @删除自定义字段
     * @param type $fieldId
     * @return boolean
     */
    public function deleteFields($fieldId, $module)
    {
        return DB::table($module . '_fields')->where('field_id', $fieldId)->delete();
    }
    /**
     *
     * @param type $moduleName
     * @return type
     */
    public function createFieldsTable($moduleName)
    {
        Schema::create($moduleName . '_fields', function ($table) {
            $table->increments('field_id')->comment('自定义字段id');
            $table->string('field_table_key', 255)->comment('自定义表id');
            $table->string('field_code', 50)->comment('字段编码（用于生成表字段）');
            $table->string('field_name', 50)->comment('字段名称');
            $table->string('field_type', 20)->comment('字段类型（0[varchar], 1[text]）')->default(0);
            $table->tinyInteger('field_is_json')->comment('字段属性（0: string, 1:json）')->default(0);
            $table->tinyInteger('field_search')->comment('是否允许搜索（0不是，1是）')->default(1)->nullable();
            $table->tinyInteger('field_list_show')->comment('是否在列表显示(0不是，1是)')->default(0);
            $table->string('field_explain', 250)->comment('字段说明')->nullable();
            $table->smallInteger('field_sort')->comment('字段排序')->nullable();
            $table->text('field_options')->comment('字段属性集合')->nullable();
            $table->tinyInteger('is_system')->comment('是否是系统字段(1是，0不是)')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        return Schema::hasTable($moduleName . '_fields');
    }
    /**
     * @添加自定义表列
     * @param type $fieldData
     * @return boolean
     */
    public function addColumn($fieldData, $tableKey)
    {
        Schema::table($tableKey . '_sub', function ($table) use ($fieldData) {
            $comment = isset($fieldData['field_explain']) ? $fieldData['field_explain'] : '';
            if ($fieldData['field_type'] == 1) {
                $table->text($fieldData['field_code'])->comment($comment)->nullable();
            } else {
                $table->string($fieldData['field_code'], 255)->comment($comment)->nullable();
            }
        });

        return Schema::hasColumn($tableKey . '_sub', $fieldData['field_code']);
    }
    /**
     * @删除表列
     * @param type $column
     * @return boolean
     */
    public function deleteColumn($column, $tableKey)
    {
        Schema::table($tableKey . '_sub', function ($table) use ($column) {
            $table->dropColumn($column);
        });

        if (Schema::hasColumn($tableKey . '_sub', $column)) {
            return false;
        }

        return true;
    }
    /**
     * @param type $fieldName
     * @param type $moduleKey
     * @param type $fieldId
     * @return type
     */
    public function hasFieldsByName($fieldName, $moduleKey, $fieldId = 0)
    {
        if ($fieldId) {
            return DB::table($moduleKey . '_fields')->where('field_id', '!=', $fieldId)->where('field_name', $fieldName)->count();
        }

        return DB::table($moduleKey . '_fields')->where('field_name', $fieldName)->count();
    }

    public function getParent($param = [])
    {
        $query = DB::table('custom_menu_config');
        if (isset($param['is_dynamic'])) {
            $query = $query->where('is_dynamic', $param['is_dynamic']);
        }
        if (isset($params['search']) && !empty($params['search'])) {
            $query = $this->wheres($query, $params['search']);
        }
        return $query->groupBy('menu_parent')->get();
    }
    public function getCustomMenuList($param = [])
    {
        $query = DB::table('custom_menu_config');
        if (isset($param['is_dynamic'])) {
            $query = $query->where('is_dynamic', $param['is_dynamic']);
        }
        return $query->get();
    }
    public function arrayTobject($array)
    {
        if (is_array($array)) {
            $obj = new \StdClass();
            foreach ($array as $key => $val) {
                $obj->$key = $val;
            }
        } else {
            $obj = $array;
        }
        return $obj;
    }
    public function getMenu($parent, $params = [])
    {
        $query = DB::table('custom_menu_config')->where('menu_parent', $parent);
        if (isset($params['search']) && !empty($params['search'])) {
            $query = $this->wheres($query, $params['search']);
        }
        $result = $query->get();

        foreach ($result as $key => $value) {
            $menu_name = $value->menu_name;
            $menu      = DB::table("lang_zh_cn")->where("lang_value", $menu_name)->first();
            if (!empty($menu)) {
                $lang_key               = $menu->lang_key;
                $result[$key]->lang_key = $lang_key;
            }

        }
        return $result;
    }
    public function deleteProjectType()
    {
        DB::table("custom_menu_config")->where('menu_code', 'like', "%" . "project_value" . "%")->delete();
    }
    public function insertCustomMenuConfig($config)
    {
        DB::table("custom_menu_config")->insert($config);
    }

    public function custom_fields_table($table_key, $field_code)
    {
        $where = [
            ["field_table_key", $table_key],
            ["field_code", $field_code],
        ];
        return DB::table($this->fieldTableName)->where($where)->first();
    }
    public function custom_menu_table($id)
    {
        return DB::table("custom_menu_config")->where("menu_code", $id)->first();
    }

    public function getCustomFields($id)
    {
        return DB::table($this->fieldTableName)->where("field_table_key", $id)->get();
    }

    public function getReminds()
    {
        return DB::table("custom_reminds")->where("field_hide", "!=", 1)->get();
    }

    public function tranferRemindName($id)
    {
        $result      = DB::table("reminds")->where("id", $id)->first();
        $remind_name = isset($result->reminds) ? $result->reminds : '';
        return $remind_name;
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
    // 获取有设置自定义提醒的自定字段菜单
    public function getRemindMenuSet($parent)
    {
        $result = DB::table('custom_menu_config')
            ->where('custom_menu_config.menu_parent', $parent)
            ->get();
        $remindResult = [];
        foreach ($result as $key => $value) {

            $menu_name = $value->menu_name;
            $menu      = DB::table("lang_zh_cn")->where("lang_key", $menu_name)->first();
            if (!empty($menu)) {
                $lang_key               = $menu->lang_key;
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
                    ->select(['project_manager.*', 'project_team.team_person', "$tableName.*"])
                    ->leftJoin('project_team', 'project_team.team_project', '=', 'project_manager.manager_id')
                    ->leftJoin($tableName, "$tableName.data_id", 'project_manager.manager_id');
            }
        } else {
            $query = DB::table($tableName);
            if (isset($this->module_parse_data_rule[$tableKey])) {
                $relation = $this->module_parse_data_rule[$tableKey];
                if (isset($relation['foreignKey'])) {
                    $primaryKey = $relation['foreignKey'];
                }
                if (isset($relation['sort'])) {
                    //创建时间排序
                    $query->orderBy($relation['sort'], 'desc');
                } else {
                    $query->orderBy('created_at', 'desc');
                }
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
                $query = $this->wheres($query, $params['search']);
            }
            return $query->first();
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
        $result    = self::getFileMenus($tableKey);
        // 文件不存在
        if (empty($result)) {
            list($menuCodes, $result) = self::paraseCustomForeignMenus($tableKey);
            if (!empty($menuCodes)) {
                $menuLists = DB::table(self::CUSTOM_MENU_TABLE)->select(['menu_code'])->whereIn('menu_code', $menuCodes)->get();
                if (!$menuLists->isEmpty()) {
                    foreach ($menuLists as $key => $list) {
                        $tempView = [
                            "custom/list",
                            ['menu_code' => $list->menu_code, 'primary_key'=> self::getPrimaryKey($list->menu_code)]
                        ];
                        $result[] = [
                            'key'       => $list->menu_code,
                            'view'      => $tempView,
                            'menu_code' => $list->menu_code,
                            'count'     => '',
                        ];
                    }
                }
            }
            // 保存为文件
            self::saveCustomTabMenus($tableKey, $result);
        }
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
                $tLang     = mulit_trans_dynamic("custom_fields_table.field_name." . $item['menu_code'] . "_" . $item['foreign_key']);
                $tableName = $item['menu_code'];
                if (strpos($tableKey, 'project_value')) {
                    $tableName = 'costom_data_' . $tableName;
                }
                $item['id']   = self::getCustomForeignValue($id, $tableName, $item['foreign_key'], $item['key']);
                $item['view'] = [
                    "custom/detail",
                    ['menu_code'=>$item['key'], 'primary_key'=> self::getPrimaryKey($item['menu_code']), 'id' => $item['id']]
                ];
            } else if (isset($item['key'])) {
                if (!$tLang = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $item['key'])) {
                    $tLang = trans($tableKey . '.' . $item['key']);
                }
            }
            $item['title'] = $tLang;
            $result[$key]  = $item;
        }
        return self::toggleHideMenus($tableKey, $result);
    }

    private static function paraseCustomForeignMenus(string $tableKey)
    {
        $menuCodes  = $result  = [];
        $fieldLists = DB::table(self::CUSTOM_FIELD_TABLE)->select(['field_table_key', 'field_options', 'field_code'])->where('is_foreign_key', 1)->whereNull('deleted_at')->get();
        if (!$fieldLists->isEmpty()) {
            foreach ($fieldLists as $key => $item) {
                $fieldOptions = json_decode($item->field_options, true);
                $tType        = $fieldOptions['type'] ?? '';
                if ($tType != 'selector') {
                    continue;
                }
                $tSelectorConfigs = $fieldOptions['selectorConfig'] ?? [];
                // 根据选择器配置获取tablekey
                $tTableKey = self::parseTableKeyBySelectorConfigs($tSelectorConfigs);
                // 自己的页面设置了外键
                if ($item->field_table_key == $tableKey) {
                    if (!$tTableKey) {
                        continue;
                    }
                    $result[] = [
                        'key'         => $tTableKey,
                        'foreign_key' => $item->field_code,
                        'menu_code'   => $item->field_table_key,
                    ];
                    continue;
                }
                // 其它页面设置了外键，设置的不是当前tablekey的跳过
                if ($tTableKey != $tableKey) {
                    continue;
                }
                $menuCodes[] = $item->field_table_key;
            }
        }
        return [$menuCodes, $result];
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

    private static function originMenusMerge(&$menus, $callback): void
    {
        $originMenus     = $callback();
        $originMenusKeys = array_column($originMenus, 'key');
        foreach ($menus as $key => $item) {
            if (isset($item['key']) && in_array($item['key'], $originMenusKeys)) {
                unset($menus[$key]);
            }
        }
        $menus = array_merge($originMenus, $menus);
    }

    public static function getFileMenus(string $tableKey)
    {
        $result   = [];
        $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::MENUS_FILE;
        if (file_exists($menuPath)) {
            $allMenus = json_decode(file_get_contents($menuPath), true);
            $result   = $allMenus[$tableKey] ?? [];
        }
        return $result;
    }

    // 读取配置，根据选择器配置获取tablekey
    public static function parseTableKeyBySelectorConfigs(array $selectConfigs)
    {
        $category = $selectConfigs['category'] ?? '';
        $type     = $selectConfigs['type'] ?? '';
        $configs  = config('customfields.menuForeignKey');
        return $configs[$category . '_' . $type] ?? '';
    }

    // 保存文件
    public static function saveCustomTabMenus(string $tableKey, array $menus): void
    {
        $menuDir  = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR;
        $menuPath = $menuDir . self::MENUS_FILE;
        $allMenus = [];
        if (file_exists($menuPath)) {
            $allMenus = json_decode(file_get_contents($menuPath), true);
        }
        $allMenus[$tableKey] = $menus;
        if (!is_dir($menuDir)) {
            dir_make($menuDir);
        }
        file_put_contents($menuPath, json_encode($allMenus));
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

    /**
     * 获取隐藏的菜单menu_code
     * @return array
     */
    public static function getHideMenus(string $tableKey): array
    {
        $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::HIDE_MENUS_FILE;
        $result   = [];
        if (file_exists($menuPath)) {
            $menuLists = json_decode(file_get_contents($menuPath), true);
            $result    = $menuLists[$tableKey] ?? [];
        }
        return $result;
    }

    // 菜单显示隐藏切换,记录再hide_menu.txt文件中
    public static function toggleCustomTabMenus(string $tableKey, string $menuCode): bool
    {
        $menuPath  = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::HIDE_MENUS_FILE;
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
        return true;
        // $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::MENUS_FILE;
        // if (!file_exists($menuPath)) {
        //     self::getCustomTabMenus($tableKey, $callback);
        // }
        // $menuLists = json_decode(file_get_contents($menuPath), true);
        // if (!isset($menuLists[$tableKey]) || empty($menuLists[$tableKey])) {
        //     return false;
        // }
        // foreach ($menuLists[$tableKey] as $index => $item) {
        //     if (isset($item['key']) && $item['key'] == $menuCode) {
        //         $menuLists[$tableKey][$index]['isShow'] = isset($item['isShow']) ? !$item['isShow'] : true;
        //         break;
        //     }
        // }
        // file_put_contents($menuPath, json_encode($menuLists));
        // return true;
    }

    // 删除菜单文件
    public static function destoryCustomTabMenus(string $tableKey = '')
    {
        $menuPath = EOFFICE_ROOT . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . self::MENUS_FILE;
        if (file_exists($menuPath)) {
            if (!$tableKey) {
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
    }

    public static function updateCustomTabMenus()
    {

    }

    // 获取外键的值
    public static function getCustomForeignValue($id, string $tableKey, $column, string $foreignTable)
    {
        if (!$id) {
            return 0;
        }
        $table     = $tableKey;
        $isDynamic = DB::table(self::CUSTOM_MENU_TABLE)->where('menu_code', $tableKey)->value('is_dynamic');
        if ($isDynamic == 2) {
            $table = 'custom_data_' . $table;
        }
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return 0;
        }
        $primaryKey = self::getPrimaryKey($tableKey);
        $id         = DB::table($table)->where([$primaryKey => $id])->value($column);
        if (!$id) {
            return 0;
        }
        $data = app('App\EofficeApp\System\CustomFields\Services\FieldsService')->getCustomDataDetail($foreignTable, $id);
        return empty($data) ? 0 : $id;
    }
}
