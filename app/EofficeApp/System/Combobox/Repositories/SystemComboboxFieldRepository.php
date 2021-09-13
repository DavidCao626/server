<?php

namespace App\EofficeApp\System\Combobox\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Combobox\Entities\SystemComboboxFieldEntity;
use App\EofficeApp\Lang\Services\LangService;
use Lang;
use DB;
/**
 * 下拉字段Repository类:提供下拉字段表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SystemComboboxFieldRepository extends BaseRepository {

    public function __construct(SystemComboboxFieldEntity $entity, LangService $langService) {
        parent::__construct($entity);
        $this->langService = $langService;
    }

    /**
     * 获取下拉表字段列表
     *
     * @param  array $param 查询条件
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getFieldsList(array $param = []) {
        $local = Lang::getLocale();
        $langTable = $this->langService->getLangTable($local);
        $default = [
            'fields' => ['system_combobox_field.*', $langTable.'.lang_value', $langTable.'.lang_key'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['field_order' => 'asc'],
            'returntype' => 'array'
        ];

        $param = array_merge($default, $param);
        $param['fields'] = ['*'];
        $param['lang_table'] = $langTable;
        $query = $this->entity;

         // 多语言查询支持 对field_name字段的查询转换为lang表lang_value字段的查询
        if (isset($param['search']['field_name']) && !empty($param['search']['field_name'])) {
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['field_name']) && !empty($param['search']['field_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['field_name'],
                    'table' => ['system_combobox_field', 'like']
                ];
                if (isset($param['combobox_identify']) && !empty($param['combobox_identify'])) {
                    $tempSearchParam = [
                        'lang_value' => $param['search']['field_name'],
                        'table' => ['system_combobox_field_' . strtolower($param['combobox_identify'][0])]
                    ];
                }
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['field_name'] = [$langKeys, 'in'];
                } else {
                    return [];
                }
            }
        }
        $query = $query->select($param['fields'])
                ->wheres($param['search'])
                ->orders($param['order_by']);

        if ($param['page'] > 0) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }

        // if (isset($param['combobox_identify'])) {
        //     $where['combobox_identify'] = [$param['combobox_identify']];
        //     $query = $query->whereHas('fieldsCombobox', function ($query) use ($where) {
        //         $query->wheres($where);
        //     });
        // }

        // if (isset($param['combobox_name'])) {
        // 	$where['combobox_name'] = [$param['combobox_name']];
        // 	$query = $query->whereHas('fieldsCombobox', function ($query) use ($where) {
        // 		$query->wheres($where);
        // 	});
        // }
        if (isset($param['combobox_identify'])) {
            $where['combobox_identify'] = [$param['combobox_identify']];
            $query = $query->leftjoin('system_combobox', 'system_combobox.combobox_id', '=', "system_combobox_field.combobox_id")->where('system_combobox.combobox_identify', $param['combobox_identify']);
        }
        if (isset($param['search']['field_value'])) {
            $field_value = $param['search']['field_value'];
            $query = $query->wheres($param['search']);
        }
        if (isset($param['combobox_id'])) {
            $query = $query->where('system_combobox_field.combobox_id', $param['combobox_id']);
        }
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->get()->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
        // return $query->get()->toArray();
    }

    public function getFieldsListTotal(array $param=[]) {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getFieldsList($param);
    }

    // 获取行业选择器,(手机版)
    public function getIndustry($param = [])
    {
        $local = Lang::getLocale();
        $langTable = $this->langService->getLangTable($local);
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['field_order' => 'asc'],
        ];
        $param = array_merge($default, $param);
        $param['lang_table'] = $langTable;
        if (isset($param['search']) && !empty($param['search'])) {
            $param['search'] = json_decode($param['search'],true);
        }
        if (isset($param['search']['field_name']) && !empty($param['search']['field_name'])) {
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['field_name']) && !empty($param['search']['field_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['field_name'],
                    'table' => ['system_combobox_field', 'like']
                ];
                if (isset($param['combobox_identify']) && !empty($param['combobox_identify'])) {
                    $tempSearchParam = [
                        'lang_value' => $param['search']['field_name'],
                        'table' => ['system_combobox_field_' . strtolower($param['combobox_identify'][0])]
                    ];
                }
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['field_name'] = [$langKeys, 'in'];
                } else {
                    return [];
                }
            }
        }
        $query = $this->entity
                ->select($param['fields'])
                ->wheres($param['search'])
                ->orders($param['order_by']);
        if ($param['page'] > 0) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }

        $query = $query->whereHas('fieldsCombobox', function ($query) {
            $query->where('combobox_identify', 'CUSTOMER_TRADE');
        });

        $data = [];
        $data['list'] = $query->get()->toArray();
        $data['total'] = $this->getIndustryTotal($param);
        if (isset($data['list']) && !empty($data['list'])) {
            foreach($data['list'] as $key => $value) {
            $comboboxTableName = get_combobox_table_name($value['combobox_id']);
               $data['list'][$key]['field_name'] = mulit_trans_dynamic($comboboxTableName. '.field_name.combobox_field_'.$value['field_id']);
            }
        }
        return $data;

    }

    public function getIndustryTotal($param)
    {
        if (isset($param['search']['field_name']) && !empty($param['search']['field_name'])) {
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['field_name']) && !empty($param['search']['field_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['field_name'],
                    'table' => ['system_combobox_field', 'like']
                ];
                if (isset($param['combobox_identify']) && !empty($param['combobox_identify'])) {
                    $tempSearchParam = [
                        'lang_value' => $param['search']['field_name'],
                        'table' => ['system_combobox_field_' . strtolower($param['combobox_identify'][0])]
                    ];
                }
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['field_name'] = [$langKeys, 'in'];
                } 
            }
        }
        $query = $this->entity
                ->select($param['fields'])
                ->wheres($param['search']);
        $query = $query->whereHas('fieldsCombobox', function ($query) {
            $query->where('combobox_identify', 'CUSTOMER_TRADE');
        });
        return $query->count();
    }


    /**
     * 获取下拉表字段数量
     *
     * @param  array 	$search 	查询条件
     *
     * @return int 					查询数量
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRepeat(array $search = []) {
        return $this->entity
                        ->where('combobox_id', $search['combobox_id'])
                        ->where(function ($query) use($search) {
                            $query->where('field_name', $search['field_name'])
                            ->where('field_value', $search['field_value']);
                        })
                        ->count();
    }

    /**
     * 获取下拉表最大值
     *
     * @param  int 	$combobox_id combobox id
     *
     * @return int 				 最大值
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getMax($combobox_id) {
        $value = $this->entity
                ->where('combobox_id', $combobox_id)
                ->max('field_value');
        return $value + 1;
    }

    public function getComboboxFieldsValueById($id){
         return $this->entity ->where("field_id",$id)->first();
    }

    /**
     * 获取字段名
     *
     * @param  string  $comboboxName 下拉框名称
     * @param  int  $fieldValue      字段值
     *
     * @return array                 查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getComboboxFieldsName($comboboxWhere, $fieldWhere) {
        return $this->entity
                        ->select(['field_id', 'combobox_id', 'field_name', 'field_value'])
                        ->where($fieldWhere)
                        ->whereHas('fieldsCombobox', function($query) use($comboboxWhere) {
                            $query->where($comboboxWhere);
                        })
                        ->first();
    }

    public function getNameByValue($fieldValue, $comboboxIdentify) {

        $data = $this->entity->select(["field_name", "field_id", 'system_combobox.combobox_id'])
                        ->leftJoin('system_combobox', 'system_combobox.combobox_id', '=', 'system_combobox_field.combobox_id')
                        ->where("system_combobox.combobox_identify", $comboboxIdentify)
                        ->where("system_combobox_field.field_value", $fieldValue)->first();
        if(!empty($data)) {
            $comboboxTableName = get_combobox_table_name($data->combobox_id);
            $data->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_". $data->field_id);
            return $data;
        }
        return $data;

    }

    /**
     * 不带$fieldValue去获取field_name，返回field_value=>field_name
     * dingpeng-20180814
     * @param  [type] $comboboxIdentify [description]
     * @return [type]                   [description]
     */
    public function getSystemComboboxFieldNameAll($comboboxIdentify) {
        $data = $this->entity->select(["field_name", "field_id", "field_value", 'system_combobox.combobox_id'])
                        ->leftJoin('system_combobox', 'system_combobox.combobox_id', '=', 'system_combobox_field.combobox_id')
                        ->where("system_combobox.combobox_identify", $comboboxIdentify)
                        ->get()->toArray();
        if(!empty($data)) {
            $result = [];
            // 处理多语言
            foreach ($data as $key => $value) {
                $valueItem = $value;
                $fieldValue = isset($value["field_value"]) ? $value["field_value"] : "";
                if($fieldValue) {
                    $comboboxTableName = get_combobox_table_name($value["combobox_id"]);
                    $valueItem["field_name"] = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_". $value["field_id"]);
                    $result[$fieldValue] = $valueItem;
                }
            }
            return $result;
        }
        return $data;

    }

    //获取value
    public function getValueByName($comboboxName,$fieldName) {
    	if(empty(trim($fieldName))||empty(trim($comboboxName))) return 0;
    	$field_value =  $this->entity->select(["field_value"])
    	->leftJoin('system_combobox', 'system_combobox.combobox_id', '=', 'system_combobox_field.combobox_id')
    	->where("system_combobox.combobox_name",trim($comboboxName))
    	->where("system_combobox_field.field_name",trim($fieldName))->first();
    	$field_value = !empty($field_value)?json_decode($field_value,true):array();
    	return isset($field_value['field_value'])?$field_value['field_value']:0;
    }

    //获取value
    public function getValueByComboboxIdentify($comboboxIdentify,$fieldName) {
        if(empty(trim($fieldName))||empty(trim($comboboxIdentify))) return 0;
        $field_value =  $this->entity->select(["field_value"])
        ->leftJoin('system_combobox', 'system_combobox.combobox_id', '=', 'system_combobox_field.combobox_id')
        ->where("system_combobox.combobox_identify",trim($comboboxIdentify))
        ->where("system_combobox_field.field_name",trim($fieldName))->first();
        $field_value = !empty($field_value)?json_decode($field_value,true):array();
        return isset($field_value['field_value'])?$field_value['field_value']:0;
    }

    //根据下拉框ID获取下拉选项
    public function getComboboxFieldList($id){
        return $this->entity ->where("combobox_id",$id)->get()->toArray();
    }
    //根据下拉框多语言标识获取下拉value
    public function getValueByLangKey($langKey){
        return $this->entity ->where("field_name",$langKey)->first();
    }

    // 解析DB模式的多条件查询
    public function parseWheres($query, $wheres)
    {
        $operators = [
            'between'       => 'whereBetween',
            'not_between'   => 'whereNotBetween',
            'in'            => 'whereIn',
            'not_in'        => 'whereNotIn'
        ];

        if (empty($wheres)) {
            return $query;
        }

        foreach ($wheres as $field=>$where) {
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                $query = $query->$whereOp($field, $where[0]);
            } else {
                $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                $query = $query->where($field, $operator, $value);
            }
        }
        return $query;
    }

    /**
     * 根据combobox_id 获取field_value => field_name
     * @return array
     */
    public function getComboboxFieldsNameByComboboxId(int $comboboxId)
    {
        $result = [];
        $lists = $this->entity->select(['field_id', 'field_value', 'field_name'])->where('combobox_id', $comboboxId)->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        $table = get_combobox_table_name($comboboxId);
        foreach ($lists as $index => $item) {
            $result[$item->field_value] = mulit_trans_dynamic($table . ".field_name.combobox_field_". $item->field_id);
        }
        return $result;
    }

    public function getFieldValueByFieldNameKey($fieldKey) {
        return $this->entity->select(['field_id', 'field_value', 'field_name'])->where('field_name', $fieldKey)->get()->toArray();
    }
}
