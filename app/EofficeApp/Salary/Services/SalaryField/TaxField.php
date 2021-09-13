<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


class TaxField extends SalaryField
{
    // 工资范围
    private static $range = [0, 36000, 144000, 300000, 420000, 660000, 960000];
    // 速算扣除数
    private static $quickDeduct = [0, 2520, 16920, 31920, 52920, 85920, 181920];
    // 税率
    private static $rate = [0.03, 0.1, 0.2, 0.25, 0.3, 0.35, 0.45];
    // 税收基准项目
    private $benchmarkId;
    private $benchmarkValue;
    // 专项附加扣除
    private $deductionId;
    private $deductionValue;
    // 税收基数
    private $baseNumberId;
    private $baseNumberValue;

    public function getValue($userId, $reportId)
    {
        return $this->getTotalTax($this->getCalculateSalary());
    }

    /**
     * 获取计税收入
     */
    public function getCalculateSalary()
    {
        $idMap = SalaryFieldList::getListUseFieldIdKey($this->fieldList);
        $this->benchmarkValue = $idMap[$this->benchmarkId]['result'];
        $this->deductionValue = $idMap[$this->deductionId]['result'];
        $this->baseNumberValue = $idMap[$this->baseNumberId]['result'];

        $calNum = $this->benchmarkValue - $this->deductionValue - $this->baseNumberValue;

        return $calNum > 0 ? $calNum : 0;
    }

    /**
     * 获取工资应交税范围的index
     * @param $calculateSalary
     */
    public function getRangeIndex($calculateSalary)
    {
        for ($i = 0; $i < 6; $i++){
            if($calculateSalary > self::$range[$i+1]){
                continue;
            }
            return $i;
        }
        return $i;
    }

    /**
     * 获取总税
     * @param $calculateSalary
     * @return number
     */
    public function getTotalTax($calculateSalary)
    {
        $index = $this->getRangeIndex($calculateSalary);

        return $calculateSalary * self::$rate[$index] - self::$quickDeduct[$index];
    }

    public function getDependenceIds()
    {
        return implode(',', [
            $this->benchmarkId,
            $this->baseNumberId,
            $this->deductionId
        ]);
    }

    /**
     * @param $benchmark
     */
    public function setBenchmarkId($benchmark)
    {
        $this->benchmarkId = $benchmark;

        return $this;
    }

    /**
     * @param $deduction
     */
    public function setDeductionId($deduction)
    {
        $this->deductionId = $deduction;

        return $this;
    }

    /**
     * @param $baseNumber
     */
    public function setBaseNumberId($baseNumber)
    {
        $this->baseNumberId = $baseNumber;

        return $this;
    }

    protected function parseConfig()
    {
        $extra = json_decode($this->config['field_source']);
        $this->setBenchmarkId($extra->benchmark)
             ->setDeductionId($extra->deduction)
             ->setBaseNumberId($extra->base_number);
    }


}
