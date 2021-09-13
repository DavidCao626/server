<?php
namespace App\EofficeApp\System\CustomFields\Traits;

use DB;
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
        $result             = [];
        if ($fieldCode) {
            $result = Redis::hget(self::CUSTOM_TABLE_FIELDS . $tableKey, $fieldCode);
        } else {
            $result = Redis::hgetall(self::CUSTOM_TABLE_FIELDS . $tableKey);
        }
        if (!$result && !$flag && $refreshFlag) {
            $this->refreshFields();
            $refreshFlag = false;
            $result      = $this->getRedisCustomFields($tableKey, $fieldCode, true);
        }
        return $result;
    }

    public function refreshFields()
    {
        $customFields = DB::table('custom_fields_table')->get();
        foreach ($customFields as $customField) {
            Redis::del(self::CUSTOM_TABLE_FIELDS . $customField->field_table_key);
        }
        foreach ($customFields as $fields) {
            $key   = self::CUSTOM_TABLE_FIELDS . $fields->field_table_key;
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
            foreach ($customLists as $fieldCode => $jsonItem) {
                $arrItem = json_decode($jsonItem, true);
                if (isset($arrItem['is_foreign_key']) && $arrItem['is_foreign_key']) {
                    $result = $fieldCode;
                }
            }
        }
        return $result;
    }

    public function rsGetShowFields($tableKey, $platform)
    {
        $result = [];
        if ($customLists = $this->getRedisCustomFields($tableKey)) {
            foreach ($customLists as $fieldCode => $jsonItem) {
                $arrItem = json_decode($jsonItem, true);

                if ($arrItem['field_hide'] || $arrItem['field_directive'] === 'tabs') {
                    continue;
                }
                // if ($platform == "pc" && $arrItem['field_list_show'] != 1) {
                //     continue;
                // }
                // if ($platform == "mobile" && $arrItem['mobile_list_field'] == '') {
                //     continue;
                // }
                if ($arrItem['field_list_show'] != 1 && $arrItem['mobile_list_field'] == '') {
                    continue;
                }

                if (in_array($arrItem['field_directive'], ['detail-layout', 'upload'])) {
                    continue;
                }
                if ($arrItem['field_directive'] === 'area') {
                    $fieldOption = json_decode($arrItem['field_options'], true);
                    if (!isset($fieldOption['relation'])) {
                        continue;
                    }
                    foreach ($fieldOption['relation'] as $tempItem) {
                        $result[] = $tempItem['field_code'];
                    }
                    continue;
                }
                $result[] = $fieldCode;
            }
        }
        if (config('customfields.fields.' . $tableKey)) {
            $extendFields = config('customfields.fields.' . $tableKey);
            if (!empty($extendFields)) {
                $result = array_unique(array_merge($result, $extendFields));
            }
        }
        return $result;
    }

    public function rsGetFieldValue($tableKey, $field, $item, $value = '')
    {
        $directive = $field->field_directive;
        if ($directive == 'upload') {
            $mainPrimaryKey = $this->getDataTablePrimaryKeyName($tableKey);
            return $this->{$directive . 'Directive'}($item->{$mainPrimaryKey}, $field, $tableKey);
        } else if ($directive == 'area') {
            return $this->{$directive . 'Directive'}($item, $field, $tableKey);
        } else if ($directive == 'select') {
            return $this->selectRedisParse($item, $field);
        } else if ($directive == 'selector') {
            return $this->selectorRedisParse($item, $field);
        } else {
            return $this->{$directive . 'Directive'}($value, $field);
        }

    }
    private function selectorRedisParse($data, $field, $get = true)
    {
        $value = $data->{$field->field_code};

        if (!$value) {
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
        global $array;

        $value          = $data->{$field->field_code};
        $fieldOption    = json_decode($field->field_options, true);
        $selectorConfig = $fieldOption['selectorConfig'];
        $category       = $selectorConfig['category'];
        $type           = $selectorConfig['type'];
        $multiple       = isset($selectorConfig['multiple']) && $selectorConfig['multiple'] == 1;

        if (isset($array[$category . $type . $value])) {
            return $array[$category . $type . $value];
        }

        if ($category == 'custom') {
            //自定义选择器
            $parseValue = $this->parseCustomSelectorValue($field, $data, $selectorConfig);
        } else {
            //系统内置选择器
            $selectorConfig = config('customfields.selector.' . $category . '.' . $type);

            $parseValue = empty($selectorConfig) ? '' : $this->getShowValueByPrimaryKey($selectorConfig[0], $selectorConfig[1], $value, $selectorConfig[2], $multiple);
        }
        return $array[$category . $type . $value] = $parseValue;
    }

    public function selectRedisParse($data, $field)
    {
        $value       = $data->{$field->field_code};
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
        $value           = $data->{$field->field_code};
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
                $parseValue = $this->parseFileSelectValue($sourceValue, $value, $multiple);
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
            return empty($selectConfig) ? '' : $this->getRedisShowValueByPrimaryKey($selectConfig[0], $selectConfig[1], $value, $selectConfig[2], $multiple);
        }

    }

    private function getRedisShowValueByPrimaryKey($table, $primaryKey, $id, $field, $mulit = false)
    {
        $tableConfig = config('customfields.table');

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
        $redis_key = self::PARSE_DATA . "select_combobox_" . $sourceValue;
        if (Redis::hget($redis_key, $value)) {
            return Redis::hget($redis_key, $value);
        }
        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();
        if (empty($combobox)) {
            return '';
        }
        $comboboxTableName = get_combobox_table_name($combobox->combobox_id);
        $combobox_datas    = DB::table('system_combobox_field')->select('field_id', 'field_value')->where('combobox_id', $combobox->combobox_id)->get();
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
            $directive    = $customFields->field_directive;
            return $this->{$directive . 'Directive'}($item, $customFields, $tableKey);
        }
    }

    public function listRedisCustomFields($tableKey, $platform)
    {
        $result = [];
        if ($customLists = $this->getRedisCustomFields($tableKey)) {
            foreach ($customLists as $fieldCode => $jsonItem) {
                $arrItem = json_decode($jsonItem, true);
                if (config('customfields.fields.' . $tableKey)) {
                    $extendFields = config('customfields.fields.' . $tableKey);
                    if (!empty($extendFields)) {
                        if (!in_array($arrItem['field_code'], $extendFields)) {
                            if ($arrItem['field_hide'] || $arrItem['field_directive'] === 'tabs') {
                                continue;
                            }
                            if ($platform == "pc" && $arrItem['field_list_show'] != 1) {
                                continue;
                            }
                            if ($platform == "mobile" && $arrItem['mobile_list_field'] == '') {
                                continue;
                            }

                            if (in_array($arrItem['field_directive'], ['detail-layout', 'upload'])) {
                                continue;
                            }
                        }
                    }
                } else {
                    if ($arrItem['field_hide'] || $arrItem['field_directive'] === 'tabs') {
                        continue;
                    }
                    if ($platform == "pc" && $arrItem['field_list_show'] != 1) {
                        continue;
                    }
                    if ($platform == "mobile" && $arrItem['mobile_list_field'] == '') {
                        continue;
                    }

                    if (in_array($arrItem['field_directive'], ['detail-layout', 'upload'])) {
                        continue;
                    }
                }

                $result[] = json_decode($jsonItem);
            }
        }

        return $result;
    }

    public function refreshReminds()
    {
        $customReminds = DB::table('custom_reminds')->get();
        Redis::del(self::CUSTOM_REMINDS);
        foreach ($customReminds as $customRemind) {
            $key   = self::CUSTOM_REMINDS;
            $value = json_encode($customRemind);
            Redis::hset($key, $customRemind->id, $value);
        }
        return true;
    }
    public function getRedisReminds()
    {
        static $refreshFlag = true;
        $result             = [];
        $result             = Redis::hgetall(self::CUSTOM_REMINDS);
        if (!$result && $refreshFlag) {
            $this->refreshReminds();
            $refreshFlag = false;
            $result      = $this->getRedisReminds();
        }
        return $result;
    }

}
