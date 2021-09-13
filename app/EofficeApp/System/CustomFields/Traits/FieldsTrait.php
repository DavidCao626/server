<?php
namespace App\EofficeApp\System\CustomFields\Traits;

use DB;
use Schema;
/**
 * 20210524-修改“'charset' => 'utf8',”问题，跟李志军确认，此部分代码废弃，已经迁移到FormModeling模块
 */
trait FieldsTrait
{
    /**
     * 普通的单行文本框控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function textDirective($value, $field, $get = true, $aggregate = false)
    {
        $result = $value;
        if ($value === null) {
            $result = '';
        } else {
            $fieldOptions = json_decode($field->field_options);
            if (isset($fieldOptions->format)) {
                $format = $fieldOptions->format;
                if (!empty($format) && isset($format->sourceType)) {
                    // 需要格式化
                    if ((isset($format->decimalPlaces) && $format->decimalPlaces)
                        || (isset($format->amountInWords) && $format->amountInWords)
                        || (isset($format->thousandSeparator) && $format->thousandSeparator)
                        || (isset($format->rounding) && $format->rounding)) {
                        $result = $this->numberDirective($value, $field, $get, $aggregate);
                    }
                }
            }
        }
        return $result;
    }

    public function tagDirective($value, $field, $get = true)
    {
        if ($value === null) {
            return '';
        }

        if ($get) {
            if ($get === 'detail') {
                return json_decode($value);
            } else {
                $value = json_decode($value);
                if (is_array($value)) {
                    $val     = [];
                    $user_id = own('user_id');
                    $public  = DB::table('tag')->where('tag_type', 'public')->whereIn('tag_id', $value)->get();
                    $private = DB::table('tag')->where('tag_type', 'private')->where('tag_creator', $user_id)->whereIn('tag_id', $value)->get();
                    foreach ($public as $key => $value) {
                        array_push($val, $value->tag_name);
                    }
                    foreach ($private as $key => $value) {
                        array_push($val, $value->tag_name);
                    }
                    return $val;
                }
            }
        } else {
            return json_encode($value);
        }
    }
    /**
     * 区域指令解析
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function areaDirective($value, $field, $get = true)
    {
        $fieldOption = json_decode($field->field_options, true);

        $relation = $fieldOption['relation'];

        $config = [
            'province' => ['province', 'province_id', 'province_name'],
            'city'     => ['city', 'city_id', 'city_name'],
        ];

        if ($get) {
            $parseValueArray = [];

            if ($get === 'detail') {
                foreach ($relation as $item) {
                    $fieldCode                   = $item['field_code'];
                    $parseValueArray[$fieldCode] = $value->{$fieldCode};
                }

                return $parseValueArray;
            } else {
                foreach ($relation as $item) {
                    $type = $item['sourceValue'];

                    $fieldCode = $item['field_code'];

                    $parseValueArray[] = $this->getShowValueByPrimaryKey($config[$type][0], $config[$type][1], $value->{$fieldCode}, $config[$type][2]);
                }

                return (!empty($parseValueArray[0]) || !empty($parseValueArray[1])) ? implode(' ', $parseValueArray) : "";
            }
        } else {
            return $value;
        }
    }

    /**
     * 日期字段
     *
     * @param string $value
     * @param object $field
     *
     * @return date
     */
    public function dateDirective($value, $field)
    {
        $result = $value;
        if (empty($result) || $result === '0000-00-00') {
            $result = '';
        }
        return $result;
    }

    /**
     * 时间字段
     *
     * @param string $value
     * @param object $field
     *
     * @return time
     */
    public function timeDirective($value, $field)
    {
        $result = $value;
        if (empty($result) || $result === '00:00:00') {
            $result = '';
        }
        return $result;
    }

    /**
     * 日期时间字段
     *
     * @param string $value
     * @param object $field
     *
     * @return datetime
     */
    public function datetimeDirective($value, $field)
    {
        $result = $value;
        if (empty($result) || $result === '0000-00-00 00:00:00') {
            $result = '';
        }
        return $result;
    }

    /**
     * 数字字段
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return number
     */
    public function numberDirective($value, $field, $get = true, $aggregate = false)
    {
        $result = '';
        if (is_numeric($value)) {
            $result = $value;
            if ($get) {
                if ($get !== 'detail') {
                    $fieldOptions = json_decode($field->field_options);
                    $format       = $fieldOptions->format;
                    if (!empty($format)) {
                        // 合计可能重新定义了格式化配置
                        if ($aggregate && isset($format->aggregate)) {
                            $format = $format->aggregate;
                        }
                        // 保留小数
                        if (isset($format->decimalPlaces) && $format->decimalPlaces) {
                            $decimalPlacesDigit = $format->decimalPlacesDigit;
                            if (isset($format->rounding) && $format->rounding == 1) {
                                $result = round($value, $format->decimalPlacesDigit);
                            } else {
                                //截位处理
                                $number = pow(10, $format->decimalPlacesDigit);
                                $result = floor($value * $number) / $number;
                            }
                        } else {
                            //获取小数长度
                            $list = explode('.', strval($result));
                            if (isset($list[1])) {
                                $decimalPlacesDigit = strlen($list[1]);
                            } else {
                                $decimalPlacesDigit = 0;
                            }
                        }

                        if (isset($format->amountInWords) && $format->amountInWords == 1) {
                            $number = number_format($result, 2, '.', '');
                            $result = $this->cny($number);
                        } else if (isset($format->thousandSeparator) && $format->thousandSeparator == 1) {
                            $result = number_format($result, $decimalPlacesDigit);
                        } else {
                            $result = number_format($result, $decimalPlacesDigit, '.', '');
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function cny($ns)
    {
        static $cnums     = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        static $cnyunits  = array('元', '角', '分');
        $grees            = array('拾', '佰', '仟', '万', '拾', '佰', '仟', '亿');
        $ns               = $ns ? sprintf("%.2f", $ns) : '0.00';
        @list($ns1, $ns2) = explode('.', $ns, 2);
        $ns2              = array_filter(array($ns2[1], $ns2[0]));
        $ret              = $this->_cny_map_unit(str_split($ns1), $grees);
        if (empty($ret[0])) {
            $ret = [0];
        }
        $ret = array_merge($ns2, array(implode('', $ret), ''));
        $ret = implode("", array_reverse($this->_cny_map_unit($ret, $cnyunits)));
        return str_replace(array_keys($cnums), $cnums, $ret);
    }

    private function _cny_map_unit($list, $units)
    {
        $ul = count($units);
        $xs = array();
        foreach (array_reverse($list) as $x) {
            $l = count($xs);
            if ($x != "0" || !($l % 4)) {
                $n = ($x == '0' ? '' : $x) . (@$units[($l - 1) % $ul]);
            } else {
                $n = is_numeric(@$xs[0][0]) ? $x : '';
            }
            array_unshift($xs, $n);
        }
        return $xs;
    }

    /**
     * 手机号控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function phoneDirective($value, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $result;
    }
    /**
     * 邮件控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function emailDirective($value, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $result;
    }
    /**
     * 身份证号控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function idCardDirective($value, $field, $get = true)
    {
        if (empty($value) && $value !== 0 && $value !== "0") {
            return '';
        }
        return $value;
    }
    /**
     * 自定义格式
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function customDirective($value, $field, $get = true)
    {
        if (empty($value) && $value !== 0 && $value !== "0") {
            return '';
        }
        return $value;
    }
    /**
     * 单选控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string|int
     */
    public function radioDirective($value, $field, $get = true)
    {
        if ($get) {
            if ($get === 'detail') {
                return $value;
            } else {
                $fieldOption = json_decode($field->field_options, true);
                if (isset($fieldOption['datasource'])) {
                    $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
                } else {
                    $dataSourceMap = '';
                }

                return $this->defaultValue($value, $dataSourceMap, '');
            }
        } else {
            return $value;
        }
    }
    /**
     * 文本域控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function textareaDirective($value, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $result;
    }
    /**
     * 下拉框控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string|int
     */
    public function selectDirective($data, $field, $get = true)
    {
        $value = $data->{$field->field_code};

        if ($get) {
            if ($get === 'detail') {
                return $value;
            } else {
                if (!$value && $value !== "0" && $value !== 0) {
                    return '';
                }
                $fieldOption = json_decode($field->field_options, true);

                if (isset($fieldOption['datasource'])) {
                    $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);

                    return $this->defaultValue($value, $dataSourceMap, '');
                } else {
                    $selectConfig = $fieldOption['selectConfig'];
                    return $this->parseSelectValue($selectConfig['sourceType'], $selectConfig['sourceValue'], $data, $field, false);
                }
            }
        } else {
            return $value;
        }
    }
    /**
     * 多选控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return array
     */
    public function checkboxDirective($value, $field, $get = true)
    {
        $result = "";
        if ($get) {
            $decodeValue = json_decode($value, true);

            if ($get === 'detail') {
                $result = $decodeValue;
            } else {
                $fieldOption = json_decode($field->field_options, true);
                if (isset($fieldOption['singleMode']) && $fieldOption['singleMode'] == 1) {
                    if (empty($decodeValue)) {
                        $decodeValue = [0];
                    } else {
                        $decodeValue = [1];
                    }
                }

                if (!empty($decodeValue) && isset($fieldOption['datasource']) && is_array($fieldOption['datasource'])) {
                    $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);

                    $parseValue = [];
                    if (is_array($decodeValue)) {
                        foreach ($decodeValue as $item) {
                            $parseValue[] = $this->defaultValue($item, $dataSourceMap, trans('fields.They_not_exist'));
                        }
                        $result = implode(',', $parseValue);
                    }

                }
            }
        } else {
            if (!is_array($value)) {
                if ($value === '') {
                    return $result;
                }
                if (strpos($value, '[') !== false) {
                    $value = json_decode($value);
                } else {
                    $value = explode(",", $value);
                }
            }
            $result      = json_encode($value);
            $fieldOption = json_decode($field->field_options, true);
            if (isset($fieldOption['singleMode'])) {
                $result = $value[0];
            }

        }
        return $result;
    }
    /**
     * 附件上传控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return array
     */
    public function uploadDirective($value, $field, $tableKey, $get = true)
    {
        if ($get) {
            $attachmentRelationTable = 'attachment_relataion_' . app($this->fieldsRepository)->getCustomDataTableName($tableKey);
            //详情页解析
            if ($get == 'detail') {
                $attachments = [];

                if (Schema::hasTable($attachmentRelationTable) && $value) {
                    $list = DB::table($attachmentRelationTable)->select(['attachment_id'])->where('entity_id', $value)->where('entity_column', $field->field_code)->get();

                    if (count($list) > 0) {
                        foreach ($list as $item) {
                            $attachments[] = $item->attachment_id;
                        }
                    }
                }

                return $attachments;
            } else {
                //列表页解析
                if (Schema::hasTable($attachmentRelationTable) && $value) {
                    return DB::table($attachmentRelationTable)->select(['attachment_id'])->where('entity_id', $value)->where('entity_column', $field->field_code)->count();
                }

                return 0;
            }
        } else {
            return $value;
        }
    }
    /**
     * 编辑器控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function editorDirective($value, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $value;
    }
    /**
     * 选择器控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string|array|int
     */
    public function selectorDirective($data, $field, $get = true)
    {
        $value = $data->{$field->field_code};

        if (empty($value) && $value !== 0 && $value !== '0') {
            return '';
        }
        $fieldOption = json_decode($field->field_options, true);

        $selectorConfig = $fieldOption['selectorConfig'];

        $multiple = (isset($selectorConfig['multiple']) && $selectorConfig['multiple'] == 1) ? true : false;
        if ($get) {
            if ($get === 'detail') {
                if (empty(json_decode($value))) {
                    return $value;
                }
                return json_decode($value);
            } else {
                return $this->parseSelectorValue($field, $data, $selectorConfig);
            }
        } else {
            if (is_array($value)) {
                return $multiple ? json_encode($value) : $value;
            } else {
                return $value;
            }

        }
    }
    /**
     * 明细字段控件
     *
     * @param array $value
     * @param object $field
     * @param boolean $get
     *
     * @return array
     */
    public function detailLayoutDirective($value, $field, $tableKey, $get = true)
    {
        if ($get) {
            $tablekey = app($this->fieldsRepository)->getCustomDataTableName($tableKey);

            $fieldDataSubTable = $tablekey . '_' . $field->field_code;
            $primaryKey        = $this->getDataTablePrimaryKeyName($tableKey);
            if (Schema::hasTable($fieldDataSubTable)) {
                $data = DB::table($fieldDataSubTable)->where($primaryKey, $value)->orderBy("detail_data_id", "asc")->get();
                foreach ($data as $key => $value) {
                    foreach ($value as $k => $v) {
                        if (strpos($v, '[') !== false) {
                            $data[$key]->$k = json_decode($v);
                        }
                    }
                }

                return $data;
            }

        } else {
            return $value;
        }
    }

    private function getDataTablePrimaryKeyName($tableKey)
    {
        global $array;

        if (isset($array[$tableKey])) {
            return $array[$tableKey];
        }

        return $array[$tableKey] = app($this->fieldsRepository)->getCustomDataPrimayKey($tableKey);
    }

    /**
     * 解析下来框值
     *
     * @param string $sourceType
     * @param string $sourceValue
     * @param int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSelectValue($sourceType, $sourceValue, $data, $field, $multiple = false)
    {
        global $array;

        $value = $data->{$field->field_code};

        $jsonSourceValue = json_encode($sourceValue);
        if (isset($array[$sourceType . $jsonSourceValue . $value])) {
            return $array[$sourceType . $jsonSourceValue . $value];
        }

        switch ($sourceType) {
            case 'combobox': //系统内置下拉框
                $parseValue = $this->parseComboboxValue($sourceValue, $value, $multiple);
                break;
            case 'systemData': //系统数据下拉框
                $parseValue = $this->parseSystemDataSelectValue($sourceValue, $value, $multiple);
                break;
            case 'outside': //外部数据库下拉框
                if ($dbConfig = $this->getExternalDatabaseConfig($sourceValue['database_id'])) {
                    $parseValue = $this->parseExternalDatabaseValue($dbConfig, $sourceValue['idField'], $sourceValue['showField'], $sourceValue['table_name'], $value, $multiple);
                } else {
                    $parseValue = $value;
                }
                break;
            case 'sql': //自定义sql语句下拉框
                $parseValue = $this->parseSqlSelectValue($sourceValue, $data, $field, $multiple);
                break;
            case 'file': //来自外部文件下拉框
                $parseValue = $this->parseFileSelectValue($sourceValue, $value, $multiple);
                break;
        }

        return $array[$sourceType . $jsonSourceValue . $value] = $parseValue;
    }
    /**
     * 解析来自sql语句的下拉框值
     *
     * @param array $sourceValue
     * @param string $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSqlSelectValue($sourceValue, $data, $field, $multiple)
    {
        $sql       = $sourceValue['sql'];
        $value     = $data->{$field->field_code};

        if ($multiple) {
            $value = json_decode($value, true);
            if (empty($value)) {
                $value = '';
            }
        }

        if (!$value && $value !== 0) {
            return  '';
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $sql = $sourceValue['sql'];
        // 处理依赖字段
        if (isset($sourceValue['parent']) && is_array($sourceValue['parent']) && count($sourceValue['parent']) > 0) {
            foreach($sourceValue['parent'] as $key) {
                if (!empty($key)) {
                    if (isset($data->{'raw_'.$key})) {
                    $sql = str_replace('#'.$key.'#', $data->{'raw_'.$key}, $sql);
                } else {
                    $sql = str_replace('#'.$key.'#', $data->{$key}, $sql);
                }
                }
            }
        }
        // 替换系统常量
        $sql = $this->strReplace($sql);
        $list = DB::select($sql);

        $result = [];
        if (is_array($list) && count($list) > 0) {
            $keys = array_keys(json_decode(json_encode($list[0]), true));
            if (count($keys) === 1) {
                $result = $value;
            } else if (count($keys) > 1) {
                $idField = $keys[0];
                $showField = $keys[1];
                foreach($list as $item) {
                    $index = array_search($item->$idField, $value);
                    if ($index !== false) {
                        // 位置没对应是问题
                        array_push($result, $item->$showField);
                        array_splice($value, $index, 1);
                    }
                    if (count($value) === 0) {
                        break;
                    }
                }
            }
        }
        return implode(',', $result);
    }
    public function strReplace($sql)
    {
        $systemConstants = [
            '#loginUserId#' => 'user_id',
            '#loginUserName#' => 'user_name',
            '#loginUserAccount#' => 'user_accounts',
            '#loginUserRoleId#' => 'role_id',
            '#loginUserDeptId#' => 'dept_id',
            '#loginUserDeptName#' => 'dept_name',
        ];

        $result = $sql;
        foreach($systemConstants as $key => $item) {
            if (strpos($result, $key) !== false) {
                $value = own($item);
                if (is_array($value) && count($value) > 0) {
                    $value = $value[0];
                }/* else {
                    $value = '';
                }*/
                $result = str_replace($key, $value, $result);
            }
        }

        return $result;
    }
    /**
     * 组合in sql语句
     *
     * @param array $values
     * @param string $key
     *
     * @return string
     */
    private function setInSql($values, $key)
    {
        $query = " `$key` IN (";

        foreach ($values as $v) {
            $query .= "'$v',";
        }

        $query = rtrim($query, ',') . ')';

        return $query;
    }
    /**
     * 解析来自外部文件的下拉框值
     *
     * @staticvar array $array
     * @param array $sourceValue
     * @param string|int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseFileSelectValue($sourceValue, $value, $multiple)
    {
        global $array;

        $result = '';
        if (!empty($value)) {
            if (isset($array[$sourceValue['filePath']])) {
                $data = $array[$sourceValue['filePath']];
            } else {
                $json = app('App\EofficeApp\Api\Services\ApiService')->getUrlData(['url' => $sourceValue['filePath']]);

                $data = json_decode($json['content'], true);

                $array[$sourceValue['filePath']] = $data;
            }

            if ($multiple) {
                $parseValue = '';

                foreach (json_decode($value) as $v) {
                    $parseValue .= (isset($data[$v]) ? $data[$v] : '') . ',';
                }

                $result = rtrim($parseValue, ',');
            }

            $result = $data[$value];
        }
        return $result;
    }
    /**
     * 解析系统数据下拉框
     *
     * @param array $sourceValue
     * @param string|int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSystemDataSelectValue($sourceValue, $value, $multiple)
    {
        $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
        if (empty($selectConfig)) {
            return '';
        }
        if (isset($selectConfig[$sourceValue['field']])) {
            $selectConfig = $selectConfig[$sourceValue['field']];
        }

        if (count($selectConfig) == 4) {
            $services   = isset($selectConfig[0]) ? $selectConfig[0] : '';
            $method     = isset($selectConfig[1]) ? $selectConfig[1] : '';
            $primaryKey = isset($selectConfig[2]) ? $selectConfig[2] : '';
            $field      = isset($selectConfig[3]) ? $selectConfig[3] : '';
            if (method_exists(app($services), $method)) {
                $param                        = [];
                $idArray                      = explode(',', $value);
                $param['search'][$primaryKey] = [$idArray, "in"];
                $result                       = app($services)->$method($param);
                return implode(',', array_column($result, $field));
            }
        } else {
            return empty($selectConfig) ? '' : $this->getShowValueByPrimaryKey($selectConfig[0], $selectConfig[1], $value, $selectConfig[2], $multiple);
        }

    }
    /**
     * 解析系统内置下拉框
     *
     * @param string $sourceValue
     * @param string $value
     * @param boolean $multiple
     *
     * @return type
     */
    private function parseComboboxValue($sourceValue, $value, $multiple)
    {

        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();
        if (empty($combobox)) {
            return '';
        }
        $comboboxTableName = get_combobox_table_name($combobox->combobox_id);
        $query             = DB::table('system_combobox_field')->select('field_name', 'field_id')->where('combobox_id', $combobox->combobox_id);

        if ($multiple) {
            $fieldValues = json_decode($value);

            if (empty($fieldValues)) {
                return [];
            }
            $comboboxFields = $query->whereIn('field_value', $fieldValues)->get()->toArray();
            return implode(',', array_column($comboboxFields, 'field_name'));
        } else {
            $comboboxFields = $query->where('field_value', $value)->first();
            if (isset($comboboxFields->field_name)) {
                $comboboxFields->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $comboboxFields->field_id);
                return $comboboxFields->field_name;
            } else {
                return "";
            }
        }
    }
    /**
     * 解析选择器的值
     *
     * @staticvar array $array
     * @param string $category
     * @param string $type
     * @param object $data
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSelectorValue($field, $data, $config)
    {
        global $array;

        $value    = $data->{$field->field_code};
        $category = $config['category'];
        $type     = $config['type'];
        if (isset($array[$category . $type . $value])) {
            return $array[$category . $type . $value];
        }

        if ($category == 'custom') {
            //自定义选择器
            $parseValue = $this->parseCustomSelectorValue($field, $data, $config);
        } else {
            $multiple = (isset($config['multiple']) && $config['multiple'] == 1) ? true : false;
            //系统内置选择器
            $selectorConfig = config('customfields.selector.' . $category . '.' . $type);

            $parseValue = empty($selectorConfig) ? '' : $this->getShowValueByPrimaryKey($selectorConfig[0], $selectorConfig[1], $value, $selectorConfig[2], $multiple);
        }
        return $array[$category . $type . $value] = $parseValue;
    }
    /**
     * 解析自定义选择器的值
     *
     * @param string $type
     * @param int|string $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseCustomSelectorValue($field, $data, $config)
    {
        $result = '';
        $value  = $data->{$field->field_code};
        if (!$selectorConfig = $this->getCustomSelectorConfig($config['type'])) {
            // $result = $value;
        } else {
            $multiple = (isset($config['multiple']) && $config['multiple'] == 1) ? true : false;
            if ($multiple) {
                try {
                    $value = json_decode($value, true);
                    if (is_array($value) && count($value) === 0) {
                        $value = '';
                    }
                } catch (\Exception $e) {
                    $value = '';
                }
            }

            if ($value) {
                $idField   = $selectorConfig['idField'];
                $showField = $selectorConfig['showField'];
                if (isset($selectorConfig['source']) && !empty($selectorConfig['source'])) {
                    // 自定义api（暂不支持）
                    // $url = app('Request')->root();
                    // $url = preg_replace('/api/.*/', $url);

                } else {
                    $sql      = $selectorConfig['extendConfig']['source'] ?? '';
                    $filePath = $selectorConfig['extendConfig']['filePath'] ?? '';
                    if (!empty($sql)) {
                        //替换系统变量
                        $sql = $this->strReplace($sql);
                        //替换依赖字段值
                        if (isset($config['parent']) && is_array($config['parent'])) {
                            foreach ($config['parent'] as $key => $fieldCode) {
                                if (isset($data->{'raw_' . $fieldCode})) {
                                    $parentValue = $data->{'raw_' . $fieldCode};
                                } else {
                                    $parentValue = $data->$fieldCode;
                                }
                                $sql = str_replace("@$key@", $parentValue, $sql);
                            }
                        }

                        if (is_array($value)) {
                            $parsedValue = "'".implode("','", $value)."'";
                        } else {
                            $parsedValue = "'".$value."'";
                        }

                        $sql = preg_replace('/<\/?id>/i', ' ', $sql); // 取消id标签
                        $sql = preg_replace('/<\w+>(.|\n)*?<\/\w+>/', ' ', $sql); //删除剩余的标签
                        $sql = preg_replace('/@value/i', $parsedValue, $sql); //替换具体值

                        $params = [
                            'sql'  => $sql,
                            'page' => 0,
                        ];

                        if (isset($selectorConfig['extendConfig']['externalDB'])) {
                            $params['database_id'] = $selectorConfig['extendConfig']['externalDB'];
                        }
                        $responseData = app('App\EofficeApp\Api\Services\ApiService')->testSql($params, 'execute');
                        if (is_array($responseData) && isset($responseData['list']) && is_array($responseData['list']) && count($responseData['list']) > 0) {
                            $result = [];
                            $value  = is_array($value) ? $value : [$value];
                            foreach ($responseData['list'] as $data) {
                                if (in_array($data->$idField, $value)) {
                                    $result[] = $data->$showField;
                                }
                            }
                            $result = implode(',', $result);
                        }
                    } else if (!empty($filePath)) {
                        $params = [
                            'url'  => $filePath,
                            'page' => 0,
                        ];

                        // 依赖字段值
                        if (isset($config['parent']) && is_array($config['parent'])) {
                            foreach ($config['parent'] as $key => $fieldCode) {
                                $params[$key] = $data->$fieldCode;
                            }
                        }

                        if (is_array($value)) {
                            $params[$idField] = implode(',', $value);
                        } else {
                            $params[$idField] = $value;
                        }
                        $responseData = app('App\EofficeApp\Api\Services\ApiService')->getUrlData($params);
                        if (is_array($responseData) && isset($responseData['content'])) {
                            $list = [];
                            try {
                                $responseData = json_decode($responseData['content']);
                                if (isset($responseData->list) && is_array($responseData->list)) {
                                    $list = $responseData->list;
                                }
                            } catch (\Exception $e) {}
                            if (count($list) > 0) {
                                $result = [];
                                $value  = is_array($value) ? $value : [$value];
                                foreach ($list as $data) {
                                    if (in_array($data->$idField, $value)) {
                                        $result[] = $data->$showField;
                                    }
                                }
                                $result = implode(',', $result);
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
    /**
     * 解析外部数据库的值
     *
     * @param array $dbConfig
     * @param string $primaryKey
     * @param string $showColumn
     * @param string $tableName
     * @param string|int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseExternalDatabaseValue($dbConfig, $primaryKey, $showColumn, $tableName, $value, $multiple)
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionConfig = [
            'driver'   => 'pdo_' . $dbConfig->driver,
            'host'     => $dbConfig->host,
            'port'     => $dbConfig->port,
            'user'     => $dbConfig->username,
            'password' => $dbConfig->password ? $dbConfig->password : '',
            'dbname'   => $dbConfig->database,
            // 20210524-丁鹏，修改公共部分；1、删除这里的charset（dbal更新到3.0，pdo_sqlsrv的方式不再支持charset属性）；2、经过和刘宁确认，前端目前没有“数据源来自外部系统，选字段，解析值”的功能，所以此函数对应的“case 'outside': //外部数据库下拉框”分支应该没用到；3、基于“2”，改后没法测试，会跟测试单独沟通
            // 'charset'  => 'utf8',
        ];

        try {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionConfig, $config);

            $query = "SELECT `$showColumn` FROM $tableName WHERE 1=1 ";

            if ($multiple) {
                $query .= " AND " . $this->setInSql(json_decode($value, true), $primaryKey);

                if ($result = $conn->fetchAll($query)) {
                    return implode(',', array_column($result, $showColumn));
                }
            } else {
                if ($result = $conn->fetchAssoc($query . " AND $primaryKey = ?", array($value))) {
                    return $result[$showColumn];
                }
            }

            return '';
        } catch (\Exception $e) {
            // return trans('fields.failed_connection');
            return '';
        }
    }
    /**
     * 获取外部数据库配置
     *
     * @staticvar array $array
     * @param int $databaseId
     *
     * @return object
     */
    private function getExternalDatabaseConfig($databaseId)
    {
        global $array;

        if (isset($array[$databaseId])) {
            return $array[$databaseId];
        }

        $result = DB::table('external_database')->where('database_id', $databaseId)->first();

        return $array[$databaseId] = $result;
    }
    /**
     * 获取自定义选择器配置信息
     *
     * @staticvar array $array
     * @param string $type
     *
     * @return array;
     */
    private function getCustomSelectorConfig($type)
    {
        global $array;

        if (isset($array[$type])) {
            return $array[$type];
        }

        $result = DB::table('customize_selector')->select(['config'])->where('identifier', $type)->first();

        if ($result && $result->config) {
            return $array[$type] = json_decode($result->config, true);
        }

        return false;
    }
    /**
     * 根据唯一键值获取显示的值
     *
     * @param string $table
     * @param string $primaryKey
     * @param int $id
     * @param array $field
     * @param boolean $mulit
     *
     * @return string
     */
    private function getShowValueByPrimaryKey($table, $primaryKey, $id, $field, $mulit = false)
    {

        $tableConfig = config('customfields.table');
        $idArray = json_decode($id);
        if (empty($idArray) && is_string($id) && strpos($id, ',') !== false) {
            $idArray = explode(',', $id);
        }

        if (is_array($idArray)) {
            if (is_array($idArray)) {
                $datas = DB::table($table)->select([$field])->whereIn($primaryKey, $idArray)->get()->toArray();
            } else {
                $datas = DB::table($table)->select([$field])->where($primaryKey, $idArray)->get()->toArray();
            }

            $result = [];
            foreach ($datas as $key => $value) {
                if (in_array($table, $tableConfig)) {
                    $value = mulit_trans_dynamic("$table.$field" . '.' . $value);
                }

                $result[] = (array) $value;
            }
            return implode(',', array_column($result, $field));
        } else {
            $data = DB::table($table)->select([$field])->where($primaryKey, $id)->first();
            if (in_array($table, $tableConfig)) {
                return $data ? mulit_trans_dynamic("$table.$field" . '.' . $data->{$field}) : '';
            } else {
                return $data ? $data->{$field} : '';
            }
            return $data ? $data->{$field} : '';
        }
    }
    /**
     * @获取默认值
     *
     * @author 李志军
     *
     * @param string $key
     * @param array $data
     * @param type $default
     *
     * @return 相应的值
     */
    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    /**
     * 二维对象数组转为一维键值映射数组
     *
     * @param array $dataSource
     * @param string $keyName
     * @param string $valueName
     *
     * @return array
     */
    private function parseDatasourcKeyValueMap($dataSource, $keyName = 'id', $valueName = 'title')
    {
        $map = [];
        if (!empty($dataSource)) {
            foreach ($dataSource as $item) {
                $map[$item[$keyName]] = $item[$valueName];
            }
        }

        return $map;
    }

    public function outsourceSelect($value, $field)
    {
        if (!$value && $value === '') {
            return '';
        }
        $fieldOption = json_decode($field->field_options, true);

        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $this->outsourceDatasourcKeyValueMap($fieldOption['datasource']);

            return $this->defaultValue($value, $dataSourceMap, '');
        } else {
            $selectConfig = $fieldOption['selectConfig'];

            return $this->outsourceSelectValue($selectConfig['sourceType'], $selectConfig['sourceValue'], $value);
        }
    }

    private function outsourceDatasourcKeyValueMap($dataSource, $keyName = 'id', $valueName = 'title')
    {
        $map = [];

        if (!empty($dataSource)) {
            foreach ($dataSource as $item) {
                $map[$item[$valueName]] = $item[$keyName];
            }
        }

        return $map;
    }

    private function outsourceSelectValue($sourceType, $sourceValue, $value)
    {
        global $array;

        $jsonSourceValue = json_encode($sourceValue);

        if (isset($array[$sourceType . $jsonSourceValue . $value])) {
            return $array[$sourceType . $jsonSourceValue . $value];
        }

        switch ($sourceType) {
            case 'combobox': //系统内置下拉框
                $parseValue = $this->outsourceComboboxValue($sourceValue, $value);
                break;
            case 'systemData': //系统数据下拉框
                $parseValue = $this->outsourceSystemDataSelectValue($sourceValue, $value);
                break;
            case 'outside': //外部数据库下拉框
                if ($dbConfig = $this->getExternalDatabaseConfig($sourceValue['database_id'])) {
                    $parseValue = $this->outsourceExternalDatabaseValue($dbConfig, $sourceValue['idField'], $sourceValue['showField'], $sourceValue['table_name'], $value);
                } else {
                    $parseValue = $value;
                }
                break;
            case 'sql': //自定义sql语句下拉框
                $parseValue = $this->outsourceSqlSelectValue($sourceValue, $value);
                break;
            case 'file': //来自外部文件下拉框
                $parseValue = $this->outsourceFileSelectValue($sourceValue, $value);
                break;
        }

        return $parseValue;
    }

    private function outsourceComboboxValue($sourceValue, $value)
    {

        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();

        $query = DB::table('system_combobox_field')->select(['field_value'])->where('combobox_id', $combobox->combobox_id);

        $comboboxFields = $query->where('field_name', $value)->first();

        return isset($comboboxFields->field_value) ? $comboboxFields->field_value : "";

    }

    private function outsourceSystemDataSelectValue($sourceValue, $value)
    {
        $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
        if (isset($selectConfig[$sourceValue['field']])) {
            $selectConfig = $selectConfig[$sourceValue['field']];
        }

        return empty($selectConfig) ? '' : $this->outsourceShowValueByPrimaryKey($selectConfig[0], $selectConfig[1], $value, $selectConfig[2]);
    }

    private function outsourceShowValueByPrimaryKey($table, $primaryKey, $id, $field)
    {
        if ($table == "vehicles") {
            $id = strstr($id, '(', true);
        }
        $data = DB::table($table)->select([$primaryKey])->where($field, $id)->first();
        $cc   = DB::table($table)->select([$primaryKey])->where($field, $id)->toSql();
        return $data ? $data->{$primaryKey} : '';
    }

    private function outsourceSqlSelectValue($sourceValue, $value)
    {
        $sql       = $sourceValue['sql'] . ' where ';
        $idField   = $sourceValue['idField'];
        $showField = $sourceValue['showField'];

        if (empty($value)) {
            return "";
        } else {
            if (strpos($sourceValue['sql'], "where") !== false) {
                $sql = $this->strReplace($sourceValue['sql']);
                $sql = $sql . ' and ';
            }
            $result = DB::select($sql . "`$showField` = '$value'");

        }

        return $result[0]->{$idField};
    }
    private function outsourceFileSelectValue($sourceValue, $value)
    {
        global $array;
        if (!empty($value)) {
            if (isset($array[$sourceValue['filePath']])) {
                $data = $array[$sourceValue['filePath']];
            } else {
                $json = app('App\EofficeApp\Api\Services\ApiService')->getUrlData(['url' => $sourceValue['filePath']]);

                $data = json_decode($json['content'], true);

                $array[$sourceValue['filePath']] = $data;
            }
            $data = array_flip($data);
            return $data[$value];
        }

    }

    private function outsourceExternalDatabaseValue($dbConfig, $primaryKey, $showColumn, $tableName, $value)
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionConfig = [
            'driver'   => 'pdo_' . $dbConfig->driver,
            'host'     => $dbConfig->host,
            'port'     => $dbConfig->port,
            'user'     => $dbConfig->username,
            'password' => $dbConfig->password ? $dbConfig->password : '',
            'dbname'   => $dbConfig->database,
            // 20210524-丁鹏，修改公共部分；1、删除这里的charset（dbal更新到3.0，pdo_sqlsrv的方式不再支持charset属性）；2、经过和刘宁确认，前端目前没有“数据源来自外部系统，选字段，解析值”的功能，所以此函数对应的“case 'outside': //外部数据库下拉框”分支应该没用到；3、基于“2”，改后没法测试，会跟测试单独沟通
            // 'charset'  => 'utf8',
        ];

        try {
            $conn  = \Doctrine\DBAL\DriverManager::getConnection($connectionConfig, $config);
            $query = "SELECT `$primaryKey` FROM $tableName WHERE 1=1 ";
            if ($result = $conn->fetchAssoc($query . " AND $showColumn = ?", array($value))) {
                return $result[$primaryKey];
            }

            return '';
        } catch (\Exception $e) {
            return '获取外部数据库数据失败';
        }
    }

    public function outsourceCheckboxDirective($value, $field)
    {
        $result      = [];
        $fieldOption = json_decode($field->field_options, true);

        if (isset($fieldOption['singleMode']) && $fieldOption['singleMode'] == 1) {
            if (empty($value)) {
                $value = [0];
            } else {
                $value = [1];
            }
        }

        if (!empty($value) && isset($fieldOption['datasource']) && is_array($fieldOption['datasource'])) {
            $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
            $dataSourceMap = array_flip($dataSourceMap);
            foreach ($value as $item) {
                $data = $this->defaultValue($item, $dataSourceMap, '');
                if ($data !== '') {
                    $result[] = $data;
                }

            }
        }
        return $result;
    }

    public function outsourceRadioDirective($value, $field)
    {
        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
            $dataSourceMap = array_flip($dataSourceMap);
        } else {
            $dataSourceMap = '';
        }

        return $this->defaultValue($value, $dataSourceMap, '');

    }

    public function radioDirectiveExcel($field)
    {

        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $fieldOption['datasource'];
        } else {
            $dataSourceMap = '';
        }
        return $dataSourceMap;
    }

    public function selectDirectiveExcel($field, $header = 0)
    {

        $fieldOption = json_decode($field->field_options, true);

        if (isset($fieldOption['datasource'])) {
            if ($header == 1) {
                return '';
            }
            return $fieldOption['datasource'];
        } else {

            $selectConfig = $fieldOption['selectConfig'];

            return $this->parseSelectValueExcel($selectConfig['sourceType'], $selectConfig['sourceValue'], $header);
        }

    }

    private function parseSelectValueExcel($sourceType, $sourceValue, $header)
    {
        switch ($sourceType) {
            case 'combobox': //系统内置下拉框
                if ($header == 1) {
                    return ['field_value', 'field_name'];
                }
                $parseValue = $this->parseComboboxValueExcel($sourceValue);
                break;
            case 'systemData': //系统数据下拉框
                if ($header == 1) {
                    $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
                    if (isset($selectConfig[$sourceValue['field']])) {
                        $selectConfig = $selectConfig[$sourceValue['field']];
                    }
                    if (count($selectConfig) == 4 && !is_array($selectConfig[0])) {
                        return [$selectConfig[2], $selectConfig[3]];
                    } else {
                        return [$selectConfig[1], $selectConfig[2]];
                    }

                }
                $parseValue = $this->parseSystemDataSelectValueExcel($sourceValue);
                break;
            case 'outside': //外部数据库下拉框
                if ($dbConfig = $this->getExternalDatabaseConfig($sourceValue['database_id'])) {
                    if ($header == 1) {
                        return [$sourceValue['idField'], $sourceValue['showField']];
                    }
                    $parseValue = $this->parseExternalExcel($dbConfig, $sourceValue['idField'], $sourceValue['showField'], $sourceValue['table_name']);
                    return $parseValue;
                }
                break;
            case 'sql': //自定义sql语句下拉框
                if ($header == 1) {
                    return [$sourceValue['idField'], $sourceValue['showField']];
                }
                $parseValue = $this->parseSqlSelectValueExcel($sourceValue);
                break;
            case 'file': //来自外部文件下拉框
                if ($header == 1) {
                    $this->parseFileSelectValueExcel($sourceValue, 1);
                }
                $parseValue = $this->parseFileSelectValueExcel($sourceValue);
                break;
        }

        return $parseValue;
    }

    private function parseComboboxValueExcel($sourceValue)
    {

        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();
        if (empty($combobox)) {
            return [];
        }
        $result = DB::table('system_combobox_field')->select('field_value', 'field_name', 'field_id')->where('combobox_id', $combobox->combobox_id)->get();
        foreach ($result as $key => $value) {
            $comboboxTableName        = get_combobox_table_name($combobox->combobox_id);
            $result[$key]->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $value->field_id);

        }
        foreach ($result as $k => $v) {
            unset($v->field_id);
        }
        return json_decode(json_encode($result), true);
    }

    private function parseSystemDataSelectValueExcel($sourceValue)
    {

        $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
        if (empty($selectConfig)) {
            return '';
        }
        if (isset($selectConfig[$sourceValue['field']])) {
            $selectConfig = $selectConfig[$sourceValue['field']];
        }

        if (count($selectConfig) == 4) {
            $services   = isset($selectConfig[0]) ? $selectConfig[0] : '';
            $method     = isset($selectConfig[1]) ? $selectConfig[1] : '';
            $primaryKey = isset($selectConfig[2]) ? $selectConfig[2] : '';
            $field      = isset($selectConfig[3]) ? $selectConfig[3] : '';
            if (method_exists(app($services), $method)) {
                $param  = [];
                $result = app($services)->$method($param);
                return $result;
            }
        } else {
            return empty($selectConfig) ? '' : $this->getShowValueByPrimaryKeyExcel($selectConfig[0], $selectConfig[1], $selectConfig[2]);
        }
    }

    private function parseSqlSelectValueExcel($sourceValue)
    {
        $result = [];
        if (!isset($sourceValue['parent'])) {
            $sql    = $sourceValue['sql'];
            $sql    = $this->strReplace($sql);
            $result = DB::select($sql);
        }
        return $result;
    }

    private function parseFileSelectValueExcel($sourceValue, $header = 0)
    {

        $json = app('App\EofficeApp\Api\Services\ApiService')->getUrlData(['url' => $sourceValue['filePath']]);

        $data = json_decode($json['content'], true);
        if ($header == 1) {
            $k = [];
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $k[] = $key;
                }
            }

            return $k;
        }
        return $data;
    }

    public function selectorDirectiveExcel($field, $header = 0)
    {
        $fieldOption = json_decode($field->field_options, true);

        $selectorConfig = $fieldOption['selectorConfig'];

        return $this->parseSelectorValueExcel($selectorConfig['category'], $selectorConfig['type'], $header);

    }

    private function parseSelectorValueExcel($category, $type, $header)
    {

        if ($category == 'custom') {
            //自定义选择器
            if ($header == 1) {
                $selectorConfig = $this->getCustomSelectorConfig($type);
                return [$selectorConfig['idField'], $selectorConfig['showField']];
            }
            $parseValue = $this->parseCustomSelectorValueExcel($type);
        } else {
            //系统内置选择器
            $selectorConfig = config('customfields.selector.' . $category . '.' . $type);
            if ($header == 1) {
                return [$selectorConfig[1], $selectorConfig[2]];
            }
            $parseValue = empty($selectorConfig) ? '' : $this->getShowValueByPrimaryKeyExcel($selectorConfig[0], $selectorConfig[1], $selectorConfig[2]);
        }
        return $parseValue;
    }

    private function parseCustomSelectorValueExcel($type)
    {
        $result         = [];
        $selectorConfig = $this->getCustomSelectorConfig($type);

        if (isset($selectorConfig['source']) && !empty($selectorConfig['source'])) {
            //自定义api
            // return [];
        } else {
            if (isset($selectorConfig['extendConfig'])) {
                $extendConfig = $selectorConfig['extendConfig'];
                $idField = $selectorConfig['idField'];
                $showField = $selectorConfig['showField'];
                if (isset($extendConfig['parents'])) {
                    // return [];
                } else {
                    $sql    = $extendConfig['source'] ?? '';
                    $filePath = $extendConfig['filePath'] ?? '';

                    if (!empty($sql)) {
                        $sql    = $this->strReplace($sql);
                        $sql    = preg_replace('/<\w+>.*<\/\w+>/', ' ', $sql); //删除全部标签
                        $params = [
                            'sql'  => $sql,
                            'page' => 0,
                        ];
                        if (isset($extendConfig['externalDB'])) {
                            $params['database_id'] = $extendConfig['externalDB'];
                        }
                        $responseData = app('App\EofficeApp\Api\Services\ApiService')->testSql($params, 'execute');
                        if (is_array($responseData) && isset($responseData['list']) && is_array($responseData['list']) && count($responseData['list']) > 0) {
                            foreach($responseData['list'] as $data) {
                                $result[] = [
                                    $idField => $data->$idField,
                                    $showField => $data->$showField,
                                ];
                            }
                        }
                    } else if (!empty($filePath)) {
                        $params = [
                            'sql'  => $sql,
                            'page' => 0,
                        ];

                        $responseData = app('App\EofficeApp\Api\Services\ApiService')->getUrlData($params);
                        if (is_array($responseData) && isset($responseData['content'])) {
                            $list = [];
                            try {
                                $responseData = json_decode($responseData['content']);
                                if (isset($responseData->list) && is_array($responseData->list)) {
                                    $list = $responseData->list;
                                }
                            } catch (\Exception $e) {}
                            if (count($list) > 0) {
                                foreach ($list as $data) {
                                    $result[] = [
                                        $idField => $data->$idField,
                                        $showField => $data->$showField,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
    private function parseExternalExcel($dbConfig, $primaryKey, $showColumn, $tableName)
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionConfig = [
            'driver'   => 'pdo_' . $dbConfig->driver,
            'host'     => $dbConfig->host,
            'port'     => $dbConfig->port,
            'user'     => $dbConfig->username,
            'password' => $dbConfig->password ? $dbConfig->password : '',
            'dbname'   => $dbConfig->database,
            // 20210524-丁鹏，修改公共部分；1、删除这里的charset（dbal更新到3.0，pdo_sqlsrv的方式不再支持charset属性）；2、经过和刘宁确认，前端目前没有“数据源来自外部系统，选字段，解析值”的功能，所以此函数对应的“case 'outside': //外部数据库下拉框”分支应该没用到；3、基于“2”，改后没法测试，会跟测试单独沟通
            // 'charset'  => 'utf8',
        ];

        try {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionConfig, $config);

            $query = "SELECT `$primaryKey`,`$showColumn` FROM $tableName WHERE 1=1 ";
            if ($result = $conn->fetchAll($query)) {
                return $result;
            }
        } catch (\Exception $e) {
            return '获取外部数据库数据失败';
        }
    }
    private function getShowValueByPrimaryKeyExcel($table, $field1, $field2)
    {
        $tableConfig = config('customfields.table');
        if (in_array($table, $tableConfig)) {
            $result = DB::table($table)->select($field1, $field2)->get();
            foreach ($result as $key => $value) {
                $result[$key]->$field2 = mulit_trans_dynamic("$table.$field2" . '.' . $value->$field2);
            }
            return json_decode(json_encode($result), true);
            $value = mulit_trans_dynamic("$table.$field" . '.' . $value);
        } else {
            $result = DB::table($table)->select($field1, $field2)->get();
            return json_decode(json_encode($result), true);
        }

    }

    public function checkboxDirectiveExcel($field)
    {
        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $fieldOption['datasource'];
        } else {
            $dataSourceMap = '';
        }
        return $dataSourceMap;

    }

}
