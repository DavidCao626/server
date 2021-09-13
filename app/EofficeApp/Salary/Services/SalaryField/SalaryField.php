<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


use App\EofficeApp\Salary\Helpers\SalaryHelpers;
use Illuminate\Database\Eloquent\Model;

abstract class SalaryField
{
    protected $config;

    protected $fieldList;

    protected $params;

    public function __construct($config)
    {
        $this->setConfig($config);
        $this->parseConfig();
    }

    abstract public function getValue($userId, $reportId);

    abstract protected function parseConfig();

    abstract public function getDependenceIds();

    /**
     * 获取格式化过后的值
     * @param $userId
     * 20201231-dp-$userId传入一个数组，user_id 是代码运行的原始id，是传入的； transform_user_id是转换后的用户id，用于取绩效、考勤
     * $userInfo = ['user_id' => $userId, 'transform_user_id' => $transformUserId];
     * app\EofficeApp\Salary\Services\SalaryField\ 目录下的不同实现 getValue 的类，需要自己辨别使用哪个user_id字段
     * @param $reportId
     * @return false|float|int|string
     */
    public function getFormatValue($userId, $reportId)
    {
        $value = $this->getValue($userId, $reportId);

        return $this->formatValue($value);
    }

    public function formatValue($value)
    {
        $fieldType = $this->config['field_type'] ?? '';
        if($fieldType == '5') {
            // 数据源来自文件，不格式化
            return $value;
        }
        $formatValue = SalaryHelpers::valueFormat(
            $value,
            $this->config['field_format'],
            $this->config['field_decimal'],
            $this->config['over_zero']
        );

        if(!is_numeric($formatValue)){
            return $formatValue;
        }

        return $formatValue;
    }

    public function setFieldList($fieldList)
    {
        $this->fieldList = $fieldList;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function setConfig($config)
    {
        if($config instanceof Model){
            $config = $config->toArray();
        }
        $this->config = (array) $config;
    }

}
