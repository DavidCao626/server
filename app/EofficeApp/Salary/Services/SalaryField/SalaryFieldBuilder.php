<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


use App\EofficeApp\Salary\Enums\FieldDefaultSet;
use App\EofficeApp\Salary\Enums\FieldTypes;

class SalaryFieldBuilder
{
    private $config;

    private $fieldType;

    private $fieldDefaultSet;

    private $fieldList;

    private $params;

    public function __construct($config, $params = [])
    {
        $this->setConfig($config);
        $this->setParams($params);
    }

    /**
     * @param $fieldList
     * @return SalaryField
     */
    public function build($fieldList)
    {
        $fieldManagerClass = $this->getFieldManager();
        /** @var SalaryField $fieldManager */
        $fieldManager = new $fieldManagerClass($this->config);

        $fieldManager->setFieldList($fieldList);
        $fieldManager->setParams($this->params);

        return $fieldManager;
    }

    /**
     * @return SalaryField
     */
    public function simpleBuild()
    {
        $fieldManagerClass = $this->getFieldManager();

        return new $fieldManagerClass($this->config);
    }

    public function setFieldList($fieldList)
    {
        $this->fieldList = $fieldList;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 获取薪资管理类
     * @return string SalaryField::class
     */
    public function getFieldManager()
    {
        switch ($this->fieldType){
            case FieldTypes::NUMBER:
                switch ($this->fieldDefaultSet){
                    case FieldDefaultSet::FORMULA:
                        return FormulaField::class;

                    case FieldDefaultSet::SYSTEM_DATA:
                        return CalculateField::class;

                    case FieldDefaultSet::TAX:
                        return TaxField::class;

                    case FieldDefaultSet::LAST_REPORT_DATA:
                        return LastReportDataField::class;

                    default:
                        return DefaultValueField::class;
                }
            case FieldTypes::FROM_FILE:
                return FromFileField::class;
            default:
                return DefaultValueField::class;
        }
    }


    public function setConfig($config)
    {
        $this->config = $config;
        $this->fieldType = $config['field_type'];
        $this->fieldDefaultSet = $config['field_default_set'];

        return $this;
    }



}
