<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


use App\EofficeApp\Salary\Exceptions\SalaryError;
use Illuminate\Support\Facades\DB;

class FormulaField extends SalaryField
{
    private $formula;

    private static $htmlReg = '/<(\S*?) [^>]*>(.*?)<\/\1>/';

    private static $codeReg = '/id=[\'"](.*?)[\'"]/';

    public function getValue($userId, $reportId)
    {
        if($this->formula == ''){
            return 0;
        }
        $fieldsWithCodeKey = SalaryFieldList::getListUseFieldCodeKey($this->fieldList);
        $handledFormula = $this->handleFormulaForEval($this->formula, $fieldsWithCodeKey);

        $result = 0;
        try {
            eval("\$result = $handledFormula;");
        } catch (\Throwable $t) {
            $err = new SalaryError('0x038020');
            $err->setDynamic(trans('salary.0x038020', ['field' => $this->config['field_name']]));
            throw $err;
        }

        return $result;
    }

    /**
     * 获取供计算的计算公式
     * @param $formula
     * @param $fieldsWithCodeKey
     */
    public function handleFormulaForEval($formula, $fieldsWithCodeKey)
    {
        $handledFormula = '';

        $matches = $this->getMatches($formula);
        $htmlCodes = $matches[0];
        $contents = $matches[2];
        // 20201026-dingpeng
        $htmlEntityDecode = [
            '&gt;' => '>',
            '&lt;' => '<',
            '&gt;=' => '>=',
            '&lt;=' => '<=',
            '&nbsp;' => '',
            '=' => '==',
        ];
        if(!empty($htmlCodes)){
            foreach($htmlCodes as $key => $html){
                if (strpos($html, 'control') !== false) {
                    preg_match(self::$codeReg, $html, $codeMatch);
                    if($codeMatch){
                        $code = $codeMatch[1];
                        if(!isset($fieldsWithCodeKey[$code])){
                            $handledFormula .= "0";
                            continue;
                        }
                        $handledFormulaValueItem = $fieldsWithCodeKey[$code]['result'];
                        if($handledFormulaValueItem < 0) {
                            $handledFormula .= "(".$fieldsWithCodeKey[$code]['result'].")";
                        } else {
                            $handledFormula .= $fieldsWithCodeKey[$code]['result'];
                        }
                    }
                }else{
                    // 拼接符号
                    if(isset($contents[$key])){
                        $contentsKey = $contents[$key];
                        // 20201026-dingpeng
                        if (isset($htmlEntityDecode[$contentsKey])) {
                            $handledFormula .= $htmlEntityDecode[$contentsKey];
                        } else {
                            $handledFormula .= $contentsKey;
                        }
                    }
                }
            }
        }

        return $handledFormula;
    }

    /**
     * @param $formula
     * @return mixed
     */
    public function getMatches($formula)
    {
        preg_match_all(self::$htmlReg, $formula, $match);

        return $match;
    }

    public function getDependenceIds()
    {
        $fieldsInfo = DB::table('salary_fields')
            ->select('field_id', 'field_code')
            ->get();

        $codeIdMap = [];
        foreach ($fieldsInfo as $field) {
            $codeIdMap[$field->field_code] = $field->field_id;
        }

        preg_match_all(self::$codeReg, $this->formula, $fieldMatch);
        $ids = [];
        foreach($fieldMatch[1] as $field){
            if(array_key_exists($field, $codeIdMap)){
                $ids[] = $codeIdMap[$field];
            }
        }

        return implode(',', $ids);
    }

    protected function parseConfig()
    {
        $this->formula = $this->config['field_source'];
    }

}
