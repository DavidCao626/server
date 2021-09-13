<?php
namespace App\EofficeApp\FormModeling\Traits;

use DB;
use Lang;
use Illuminate\Support\Facades\Redis;
use Schema;

trait FieldsRedisTrait
{

    public function checkTableExist($tableKey)
    {
        if (!$table = Redis::hget(self::CUSTOM_EXIST_TABLE, $tableKey)) {
            if ($table = app($this->fieldsRepository)->getCustomDataTableName($tableKey) && Schema::hasTable($table)) {
                Redis::hset(self::CUSTOM_EXIST_TABLE, $tableKey, $table);
            }
        }
        return $table;
    }

    public function getCustomTable($tableKey)
    {
        if (!$table = Redis::hget(self::CUSTOM_TABLE, $tableKey)) {
            if ($table = app($this->fieldsRepository)->getCustomDataTableName($tableKey)) {
                Redis::hset(self::CUSTOM_TABLE, $tableKey, $table);
            }
        }
        return $table;
    }

    public function getRedisCustomFields($tableKey, $fieldCode = '', $flag = false)
    {
        static $refreshFlag = true;
        $result = [];
        if ($fieldCode) {
            $result = Redis::hget(self::CUSTOM_TABLE_FIELDS . $tableKey, $fieldCode);
        } else {
            $result = Redis::hgetall(self::CUSTOM_TABLE_FIELDS . $tableKey);
        }
        if ($fieldCode) {
            $data = $result;
        } else {
            $data = [];
            foreach ($result as $key => $value) {
                $data[] = json_decode($value);
            }
            $field_id = array_column($data, 'field_id');
            array_multisort($field_id, SORT_ASC, $data);
        }
        if (!$result && !$flag && $refreshFlag) {
            $this->refreshFields();
            $refreshFlag = false;
            $data = $this->getRedisCustomFields($tableKey, $fieldCode, true);
        }
        return $data;

    }

    public function refreshFields()
    {
        $customFields = DB::table('custom_fields_table')->get();
        foreach ($customFields as $customField) {
            Redis::del(self::CUSTOM_TABLE_FIELDS . $customField->field_table_key);
        }
        foreach ($customFields as $fields) {
            $key = self::CUSTOM_TABLE_FIELDS . $fields->field_table_key;
            $value = json_encode($fields);
            Redis::hset($key, $fields->field_code, $value);
        }
        return true;
    }
    public function deleteRedisFields($tableKey)
    {
        Redis::del(self::CUSTOM_TABLE_FIELDS . $tableKey);
        return true;
    }

    public function rsGetForeignKeyField($tableKey)
    {
        $result = '';
        if ($customLists = $this->getRedisCustomFields($tableKey)) {
            foreach ($customLists as $fieldCode => $arrItem) {
                if (isset($arrItem->is_foreign_key) && $arrItem->is_foreign_key) {
                    $result = $arrItem->field_code;
                }
            }
        }
        return $result;
    }

    public function rsGetFieldValue($tableKey, $field, $item, $value = '')
    {
        $directive = $field->field_directive;
        $directive = $this->transferDirective($directive);
        if ($directive == 'upload') {
            $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
            return $this->{$directive . 'Directive'}($item->{$mainPrimaryKey}, $item, $field);
        } else if ($directive == 'select' || $directive == 'selector') {
            return $this->{$directive . 'RedisParse'}($item, $field);
        }else {
            return $this->{$directive . 'Directive'}($value, $item, $field, true);
        }
    }
    private function selectorRedisParse($data, $field, $get = true)
    {
        $value = object_get($data, $field->field_code);
        if (empty($value) && $value !== 0 && $value !== '0') {
            return '';
        }
        $fieldOption = json_decode($field->field_options, true);

        $selectorConfig = $fieldOption['selectorConfig'];

        $multiple = (isset($selectorConfig['multiple']) && $selectorConfig['multiple'] == 1) ? true : false;
        if ($get) {
            if ($get === 'detail') {
                $jvalue = json_decode($value);
                if (empty($jvalue)) {
                    return $value;
                }
                return json_decode($value);
                // return $multiple ? json_decode($value) : $value;
            } else {
                return $this->parseRedisSelectorValue($data, $field);
            }
        } else {
            return $multiple ? json_encode($value) : $value;
        }
    }
    //$category, $type, $data, $multiple
    //$selectorConfig['category'], $selectorConfig['type']
    private function parseRedisSelectorValue($data, $field)
    {
        static $array;

        $value = $data->{$field->field_code};
        $fieldOption = json_decode($field->field_options, true);
        $selectorConfig = $fieldOption['selectorConfig'];
        $category = $selectorConfig['category'];
        $type = $selectorConfig['type'];
        $multiple = isset($selectorConfig['multiple']) && $selectorConfig['multiple'] == 1;

        if (isset($array[$category . $type . $value])) {
            return $array[$category . $type . $value];
        }

        if ($category == 'custom') {
            //自定义选择器
            $parseValue = $this->parseCustomSelectorValue($field, $data, $selectorConfig);
        } else {
            //系统内置选择器
            $selectorConfig = config('customfields.selector.' . $category . '.' . $type);
            $parseValue = empty($selectorConfig) ? [] : $this->getShowValueByPrimaryKey($selectorConfig, $value, $multiple, true);
        }
        return $array[$category . $type . $value] = $parseValue;
    }

    public function selectRedisParse($data, $field)
    {
        $value = $data->{$field->field_code};
        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
            return $this->defaultValue($value, $dataSourceMap, '');
        } else {
            $selectConfig = $fieldOption['selectConfig'];
            return $this->parseRedisSelectValue($selectConfig['sourceType'], $selectConfig['sourceValue'], $data, $field, false);
        }
    }

    private function parseRedisSelectValue($sourceType, $sourceValue, $data, $field, $multiple = false)
    {
        global $array;
        $value = $data->{$field->field_code};
        $jsonSourceValue = json_encode($sourceValue);
        if (isset($array[$sourceType . $jsonSourceValue . $value])) {
            return $array[$sourceType . $jsonSourceValue . $value];
        }
        switch ($sourceType) {
            case 'combobox': //系统内置下拉框
                $parseValue = $this->parseRedisComboboxValue($sourceValue, $value);
                break;
            case 'systemData': //系统数据下拉框
                $parseValue = $this->parseRedisSystemDataSelectValue($sourceValue, $value, $multiple);
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
                $parseValue = $this->parseFileSelectValue($sourceValue, $value, $data, $multiple);
                break;
        }

        return $array[$sourceType . $jsonSourceValue . $value] = $parseValue;
    }

    private function parseRedisSystemDataSelectValue($sourceValue, $value, $multiple)
    {
        $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
        if (empty($selectConfig)) {
            return '';
        }
        if (isset($selectConfig[$sourceValue['field']])) {
            $selectConfig = $selectConfig[$sourceValue['field']];
        }

        if (count($selectConfig) == 4) {
            $services = isset($selectConfig[0]) ? $selectConfig[0] : '';
            $method = isset($selectConfig[1]) ? $selectConfig[1] : '';
            $primaryKey = isset($selectConfig[2]) ? $selectConfig[2] : '';
            $field = isset($selectConfig[3]) ? $selectConfig[3] : '';
            if (method_exists(app($services), $method)) {
                $param = [];
                $idArray = explode(',', $value);
                $param['search'][$primaryKey] = [$idArray, "in"];
                $result = app($services)->$method($param);
                return implode(',', array_column($result, $field));
            }
        } else {
            return empty($selectConfig) ? '' : $this->getRedisShowValueByPrimaryKey($selectConfig[0] ?? '', $selectConfig[1] ?? '', $value, $selectConfig[2] ?? '', $multiple);
        }

    }

    private function getRedisShowValueByPrimaryKey($table, $primaryKey, $id, $field, $mulit = false)
    {
        $tableConfig = config('customfields.table');
        if (!$field) {
            return '';
        }
        if ($mulit) {
            $idArray = json_decode($id);
            if (empty($idArray)) {
                $idArray = explode(',', $id);
            }
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

    public function parseRedisComboboxValue($sourceValue, $value)
    {
        if ($this->current_lang) {
            $lang = $this->current_lang;
        } else {
            $lang = Lang::getLocale();
        }
        $redis_key = self::PARSE_DATA . $lang . "_" . "select_combobox_" . $sourceValue;
        if (Redis::hget($redis_key, $value)) {
            return Redis::hget($redis_key, $value);
        }
        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();
        if (empty($combobox)) {
            return '';
        }
        $comboboxTableName = get_combobox_table_name($combobox->combobox_id);
        $combobox_datas = DB::table('system_combobox_field')->select('field_id', 'field_value')->where('combobox_id', $combobox->combobox_id)->get();
        foreach ($combobox_datas as $combobox_data) {
            $field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $combobox_data->field_id);
            Redis::hset($redis_key, $combobox_data->field_value, $field_name);
        }
        return Redis::hget($redis_key, $value) ? Redis::hget($redis_key, $value) : '';
    }

    public function rsGetAreaField($tableKey, $field, $item, $value = '')
    {
        $customFields = $this->getRedisCustomFields($tableKey, $field);
        $customFields = json_decode($customFields);
        if (!empty($customFields)) {
            $customFields = $this->transferFieldOptions($customFields, $tableKey);
            $directive = $customFields->field_directive;
            return $this->{$directive . 'Directive'}($item, $customFields, $tableKey);
        }
    }

    //detaiLayoutTotal 明细合计字段
    public function listRedisCustomFields($tableKey, $filter = [], $all = false, $detaiLayoutTotal = false, $withOutProjectSystemFields = true)
    {
        $result = [];
        if ($customLists = $this->getRedisCustomFields($tableKey)) {
            foreach ($customLists as $fieldCode => $arrItem) {
                $fieldOption = json_decode($arrItem->field_options, true);
                //不是全部字段
                if (!$all) {
                    if (in_array($arrItem->field_directive, ['detail-layout', 'tabs'])) {
                        continue;
                    }

                    if (!$detaiLayoutTotal) {
                        if (isset($fieldOption['parentField']) && !empty($fieldOption['parentField'])) {
                            continue;
                        }
                    } else {
                        if (isset($fieldOption['parentField']) && !empty($fieldOption['parentField']) && !isset($fieldOption['aggregate'])) {
                            continue;
                        }
                    }
                }

                if (self::isProjectSeriesCustomKey($tableKey)) {
                    if ($withOutProjectSystemFields && $arrItem->is_system == 1){
                        continue;
                    }
                }
                if (!empty($filter)) {
                    if (in_array($arrItem->field_code, $filter)) {
                        $result[$arrItem->field_code] = $arrItem;
                    }
                } else {
                    $result[$arrItem->field_code] = $arrItem;
                }
            }
        }
        return $result;
    }
    public function refreshReminds()
    {
        $customReminds = DB::table('custom_reminds')->get();
        Redis::del(self::CUSTOM_REMINDS);
        foreach ($customReminds as $customRemind) {
            $key = self::CUSTOM_REMINDS;
            $value = json_encode($customRemind);
            Redis::hset($key, $customRemind->id, $value);
        }
        return true;
    }
    public function getRedisReminds()
    {
        static $refreshFlag = true;
        $result = [];
        $result = Redis::hgetall(self::CUSTOM_REMINDS);
        if (!$result && $refreshFlag) {
            $this->refreshReminds();
            $refreshFlag = false;
            $result = $this->getRedisReminds();
        }
        return $result;
    }

}
