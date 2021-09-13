<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


use App\EofficeApp\Salary\Enums\FieldTypes;
use App\EofficeApp\Salary\Helpers\SalaryHelpers;

class DefaultValueField extends SalaryField
{

    public function getValue($userId, $reportId)
    {
        $result = $this->config['result'];

        return $result;
    }

    public function formatValue($value)
    {
        if(!$this->isNumberType()) {
            return $value;
        }

        return parent::formatValue($value);
    }

    protected function parseConfig()
    {
        // DO NOTHING
    }

    public function getDependenceIds()
    {
        return '';
    }

    private function isNumberType()
    {
        return $this->config['field_type'] == FieldTypes::NUMBER;
    }

}
