<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


class SalaryFieldList
{
    private $fields;

    public function __construct($fields)
    {
        $this->setFields($fields);
    }

    /**
     * 给薪资项按依赖排序，排成能依次计算的顺序
     * @return array
     */
    public function sortListForCalculate()
    {
        $complete = false;
        $sortedList = [];
        $sortedIds = [];
        while(!$complete) {
            $complete = true;
            foreach($this->fields as $key => $field) {
                $dependence = array_filter(explode(',', $field['dependence_ids']));
                if(empty(array_diff($dependence, $sortedIds))){
                    $sortedList[$field['field_id']] = $field;
                    $sortedIds[] = $field['field_id'];
                    unset($this->fields[$key]);
                    $complete = false;
                }
            }
        }
        $this->setFields($sortedList);

        return $sortedList;
    }

    /**
     * 将计算好的值附到薪资项列表中
     * @param $fieldId
     * @param $value
     */
    public function appendValueToFieldList($fieldId, $value)
    {
        foreach($this->fields as &$field) {
            if($field['field_id'] == $fieldId){
                $field['result'] = $value;
            }
        }

        return $this->fields;
    }

    /**
     * 获取用薪资项代码作为键的薪资项列表
     * @param $list
     * @return array
     */
    public static function getListUseFieldCodeKey($list)
    {
        $result = [];
        foreach($list as $value){
            $result[$value['field_code']] = $value;
        }

        return $result;
    }

    public static function getListUseFieldIdKey($list)
    {
        $result = [];
        foreach($list as $value){
            $result[$value['field_id']] = $value;
        }

        return $result;
    }


    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

}
