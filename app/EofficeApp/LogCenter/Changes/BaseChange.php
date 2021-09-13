<?php
namespace App\EofficeApp\LogCenter\Changes;
/**
 * Description of Change
 *
 * @author lizhijun
 */
class BaseChange 
{
    public $id = 'id';
    public $fields = [];
    public $dynamicFields = [];
    public function getField($key = null) 
    {
        if (empty($this->fields)) {
            $this->fields();
        }
        if ($key) {
            return $this->fields[$key] ?? '';
        }
        
        return $this->fields;
    }
    public function getDynamicField($key, $logData)
    {
        if (empty($this->dynamicFields)) {
            $this->dynamicFields($logData);
        }
        if ($key) {
            return $this->dynamicFields[$key] ?? '';
        }

        return $this->dynamicFields;
    }
}
