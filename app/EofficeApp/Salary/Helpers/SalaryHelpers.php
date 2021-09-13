<?php


namespace App\EofficeApp\Salary\Helpers;


use App\EofficeApp\Salary\Enums\DecimalFormat;

class SalaryHelpers
{
    public static function valueFormat($value, $format, $decimal = 0, $overZero = false)
    {
        $value = ($overZero && $value < 0) ? 0 : $value;
        switch ($format) {
            case DecimalFormat::ROUND: //四舍五入
                return is_numeric($value) ? number_format($value, $decimal, '.', '') : $value;
                break;
            case DecimalFormat::FLOOR: //向下取整
                return floor($value);
                break;
            case DecimalFormat::CEILING: //向上取整
                return ceil($value);
                break;
            default: //向上取整
                return 0;
                break;
        }
    }

    /**
     * 获取计算公式
     * @param $str
     * @param $fields
     * @return array|int
     */
    public static function getFormulaStr($str, $fields)
    {
        if ($str == '') {
            return 0;
        }
        preg_match_all('/<(\S*?) [^>]*>(.*?)<\/\1>/', $str, $match);
        $formula   = '';
        $matchHtml = $match[0];
        $matchValue = $match[2];
        if(!empty($matchHtml)){
            foreach ($matchHtml as $key => $value) {
                if (strpos($value, 'control') !== false) {
                    preg_match('/id=[\'"](.*?)[\'"]/', $value, $idMatch);
                    if($idMatch){
                        $id = $idMatch[1];
                        if(!isset($fields[$id])){
                            $formula .= "0";
                            continue;
                        }
                        if($fields[$id][0] == 2){
                            $temp = self::getFormulaStr($fields[$id][1], $fields);

                            if (is_array($temp)) {
                                return $temp;
                            }else{
                                $formula .= $temp;
                            }
                        }else{
                            if(!isset($fields[$id][3])) $fields[$id][3] = 0;
                            $formula .= $fields[$id][3];
                        }
                    }
                }else{
                    if(isset($matchValue[$key])){
                        $formula .= $matchValue[$key];
                    }
                }
            }
        }
        $data = 0;
        try {
            eval("\$data = $formula;");
        } catch (\Throwable $t) {
            return ['code' => ['0x038018', 'salary']];
        }

        return $data;
    }

}
