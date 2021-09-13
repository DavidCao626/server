<?php
namespace App\EofficeApp\FormModeling\Traits;

use Carbon\Carbon;
use DB;
use Schema;
trait FieldsTrait
{
    private $apiToken;
    /**
     * 普通的单行文本框控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function textDirective($value, $item, $field, $get = true)
    {
        if (!$value) {
            $value = isset($item->{$field->field_code}) ? $item->{$field->field_code} : '';
        }
        $result = $value;
        if ($get) {
            if ($value === null) {
                $result = '';
            } else {
                $fieldOptions = json_decode($field->field_options);
                //格式化
                if (isset($fieldOptions->format)) {
                    $format = $fieldOptions->format;
                    if (!empty($format)) {
                        //计算公式
                        if (isset($format->sourceType) && $format->sourceType == 'formula' && isset($format->sourceValue->calculateType) && !isset($fieldOptions->parentField)) {
                            $result = $this->computationalFormula($format, $item);
                        }
                        //解析sql
                        if (isset($format->sourceType) && $format->sourceType == 'sql') {
                            $sourceValue = (array) $format->sourceValue;
                            $result = $this->parseSelectValue($format->sourceType, $sourceValue, $item, $field, false);
                        }
                        //解析来自数据源
                        if (isset($format->sourceType) && $format->sourceType == 'systemData') {
                            $result = $this->textSystemData($format, $item, $result);
                        }
                        // 需要格式化
                        if (isset($format->type) && is_numeric($result)) {
                            if ((isset($format->decimalPlaces) && $format->decimalPlaces) || (isset($format->amountInWords) && $format->amountInWords) || (isset($format->thousandSeparator) && $format->thousandSeparator) || (isset($format->rounding) && $format->rounding)) {
                                $result = $this->numberDirective($result, $item, $field, $get);

                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
    //单行文本计算公式
    public function computationalFormula($format, $item)
    {
        $sourceValue = $format->sourceValue;
        if ($sourceValue->calculateType == 1) {
            //数值计算
            $result = $this->getFormulaStr($sourceValue->calculateContent, (array) $item);
            if ( is_float($result) && strpos(strtolower(strval($result)), 'e')) {
                return 0;
            }
            return $result;
        } else {
            $unit = [
                1 => 'day',
                2 => 'hour',
                3 => 'month',
                4 => 'year',
            ];
            $begin_date = '';
            $end_date = '';
            //日期时间计算
            if (isset($sourceValue->startField)) {
                if (isset($sourceValue->startField->now) && $sourceValue->startField->now == 1) {
                    $begin_date = 'now';
                } else {
                    $begin_date = isset($item->{$sourceValue->startField}) ? $item->{$sourceValue->startField} : '';
                }
            }
            if (isset($sourceValue->endField)) {
                if (isset($sourceValue->endField->now) && $sourceValue->endField->now == 1) {
                    $end_date = 'now';
                } else {
                    $end_date = isset($item->{$sourceValue->endField}) ? $item->{$sourceValue->endField} : '';
                }
            }
            if ($end_date == '0000-00-00' || $begin_date == '0000-00-00') {
                return 0;
            }
            $current_user = own('user_id')?own('user_id'):$this->current_user_id;
            $relation_user = isset($sourceValue->relationUser) ? $item->{$sourceValue->relationUser} : $current_user;
            $param = [
                'start' => $begin_date,
                'end' => $end_date,
                'unit' => isset($unit[$sourceValue->unit]) ? $unit[$sourceValue->unit] : '',
                'restrict' => isset($sourceValue->restrict) ? $sourceValue->restrict : '',
                'relation_user' => $relation_user,
            ];
            return $this->compareDate($param);
        }
        return $result;
    }

    //单行解析数据源
    public function textSystemData($format, $item, $value)
    {

        $sourceValue = (array) $format->sourceValue;
        $parseParam = [];
        $parseParam['module'] = $sourceValue['menu'];
        $parseParam['field'] = $sourceValue['field'];
        if (isset($sourceValue['dependence'])) {
            $parentId = isset($sourceValue['dependence']->parent_id) ? $sourceValue['dependence']->parent_id : '';
            if (is_object($parentId)) {
                if (isset($parentId->dependField)) {
                    $parent = $parentId->dependField;
                    $parseParam['search_value'] = isset($item->{$parent}) ? $item->{$parent} : '';
                }
                if (isset($parentId->searchField)) {
                    $searchField = $parentId->searchField;
                    $parseParam['search_field'] = $searchField;
                }
            } else {
                $parseParam['search_value'] = isset($item->{$parentId}) ? $item->{$parentId} : '';
            }
        }
        global $textSystemData;
        $formatString = json_encode($parseParam);
        if (isset($textSystemData[$formatString])) {
            return $textSystemData[$formatString];
        }
        $result = $this->parseCustomData($parseParam);
        if (isset($result['code'])) {
            $result = $value;
        }
        $textSystemData[$formatString] = $result ? $result : '';
        return $result;
    }

    public function compareDate($param)
    {
        $begin_date = isset($param['start']) ? $param['start'] : ''; //开始日期
        $end_date = isset($param['end']) ? $param['end'] : ''; //结束日期
        $unit = isset($param['unit']) ? $param['unit'] : 'day'; //单位 年 月  天 小时
        $restrict = isset($param['restrict']) ? $param['restrict'] : 1; //1.无 2.排序休息时间 3.排除工作时间
        $relation_user = isset($param['relation_user']) ? $param['relation_user'] : ''; //没有指定时，使用当前用户。此属性只有选择了“排序休息时间”、“排除工作时间”才有意义
        if ($begin_date == 'now') {
            $begin_date = date('Y-m-d H:i:s', time());
        }
        if ($end_date == 'now') {
            $end_date = date('Y-m-d H:i:s', time());
        }
        if (!$end_date || !$begin_date) {
            return '';
        }
        if ($restrict == 1) {
            //非考勤计算
            //日期格式加一天
            if (strtotime($begin_date) <= strtotime($end_date)) {
                if ((new \DateTime($end_date))->format('Y-m-d') == $end_date) {
                    $end_date = date('Y-m-d H:i:s', strtotime($end_date . '+ 1day'));
                }
            } else {
                if ((new \DateTime($begin_date))->format('Y-m-d') == $begin_date) {
                    $begin_date = date('Y-m-d H:i:s', strtotime($begin_date . '+ 1day'));
                }
            }
            switch ($unit) {
                case 'year':
                    $res = Carbon::parse($begin_date)->floatDiffInRealYears($end_date, false);
                    break;
                case 'month':
                    $res = Carbon::parse($begin_date)->floatDiffInRealMonths($end_date, false);
                    break;
                case 'day':
                    $res = Carbon::parse($begin_date)->floatDiffInRealDays($end_date, false);
                    break;
                case 'hour':
                    $res = Carbon::parse($begin_date)->floatDiffInRealHours($end_date, false);
            }

        } else {
            if (!$relation_user) {
                return '';
            }
            if ($restrict == 2) {
                //排除休息时间
                $attendance = app($this->attendanceOutSendService)->getLeaveOrOutDays($begin_date, $end_date, $relation_user);
            } else if ($restrict == 3) {
                //排除工作时间
                $attendance = app($this->attendanceOutSendService)->getOvertimeDays($begin_date, $end_date, $relation_user);
            }
            switch ($unit) {
                case 'year':
                    $res = $attendance['days'] / 365;
                    break;
                case 'month':
                    $res = $attendance['days'] / 30;
                    break;
                case 'day':
                    $res = $attendance['days'];
                    break;
                case 'hour':
                    $res = $attendance['hours'];
                    break;
            }
        }
        if (strpos($res, 'E') !== false || strpos($res, 'e') !== false) {
            $res = number_format($res, 8);
            return chop($res, 0);
        } else {
            return round($res, 8);
        }
    }
    // 获取计算公式
    public function getFormulaStr($condition, $formData, $validate = true)
    {
        if (!$condition) {
            return true;
        } else {
            $matchList = [];
            $controlArray = [];
            $isCountersign = false;
            $htmlEntityDecode = [
                '&gt;' => '>',
                '&lt;' => '<',
                '&gt;=' => '>=',
                '&lt;=' => '<=',
            ];
            $regexStr = '/<(\S*?) [^>]*>(.*?)<\/\1>/';
            preg_match_all($regexStr, $condition, $matchArray);
            if (!empty($matchArray) && isset($matchArray[0]) && isset($matchArray[2])) {
                foreach ($matchArray[0] as $key => $value) {
                    $matchHtml = $value;
                    $matchValue = isset($matchArray[2][$key]) ? $matchArray[2][$key] : '';

                    // 控件
                    if (strpos($matchHtml, 'control') !== false) {
                        preg_match('/id=[\'"](.*?)[\'"]/', $matchHtml, $idMatchArray);

                        if (!empty($idMatchArray)) {
                            $idMatchSonArray = explode('_', $idMatchArray[1]);
                            $idValue = "";
                            if (count($idMatchSonArray) == 3) {
                                // 明细合计项
                                if (isset($formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1] . '_amount'][$idMatchArray[1]])) {
                                    $idValue = $formData[$idMatchSonArray[0] . '_' . $idMatchSonArray[1] . '_amount'][$idMatchArray[1]];
                                    $controlArray[] = $idMatchArray[1];
                                } else {
                                    if (isset($formData['_text_' . $idMatchArray[1]])) {
                                        $idValue = strval($formData['_text_' . $idMatchArray[1]]);
                                    } else {
                                        $idValue = isset($formData[$idMatchArray[1]]) ? strval($formData[$idMatchArray[1]]) : '';
                                    }
                                }

                            } else {
                                if (isset($formData['_text_' . $idMatchArray[1]])) {
                                    $idValue = strval($formData['_text_' . $idMatchArray[1]]);
                                } else {
                                    $idValue = isset($formData[$idMatchArray[1]]) ? preg_replace('/<.*?>/', '', $formData[$idMatchArray[1]]) : '';
                                    if (is_array($idValue)) {
                                        $idValue = implode(',', $idValue);
                                    }
                                }

                                $controlArray[] = $idMatchArray[1];
                            }
                            // $matchList[] = !empty($idValue) ? $idValue : '""';
                            $matchListValueItem = $idValue === "" ? '0' : $idValue;
                            if (getType($matchListValueItem) == "array") {
                                $matchListValueItem = json_encode($matchListValueItem);
                            }
                            $matchList[] = $matchListValueItem;
                        } else {
                            $matchList[] = $matchValue;
                        }
                    }

                    //与或运算符
                    else if (strpos($matchHtml, 'and-or-operator') !== false) {
                        $matchValue = strtoupper(trim($matchValue));
                        if ($matchValue == "AND") {
                            $matchList[] = "&&";
                        } else if ($matchValue == "OR") {
                            $matchList[] = "||";
                        } else {
                            $matchList[] = $matchValue;
                        }
                    }
                    //比较运算符或者关系运算符
                    else if (strpos($matchHtml, 'compare-operator') !== false || strpos($matchHtml, 'relational-operator') !== false) {
                        if (isset($htmlEntityDecode[$matchValue])) {
                            $matchList[] = $htmlEntityDecode[$matchValue];
                        } else {
                            $matchList[] = $matchValue;
                        }
                    }
                    //字符串
                    else {
                        $matchList[] = $matchValue;
                    }
                }
                //处理括号
                $hasLeftBracket = true;
                $leftBracketIndex = -1;

                for ($i = 0; $i < count($matchList); $i++) {
                    if (isset($matchList[$i]) && $matchList[$i] == '(') {
                        $hasLeftBracket = true;
                        $leftBracketIndex = $i;
                    } else if ($matchList[$i] == ')') {
                        if (!$hasLeftBracket) {
                            // 括号不匹配

                        } else {
                            //处理括号之间的内容
                            //去除括号
                            $tempArray = [];
                            for ($j = $leftBracketIndex + 1; $j < $i; $j++) {
                                $tempArray[] = $matchList[$j];
                            }
                            if ($validate) {
                                //数值运算
                                foreach ($tempArray as $k => $v) {
                                    if (in_array('+', $tempArray) || in_array('-', $tempArray)) {
                                        if ($v != '+' && $v != '-') {
                                            $tempArray[$k] = floatval($v);
                                        }
                                    } else {
                                        if (in_array('=', $tempArray)) {
                                            if ($v != '=') {
                                                $tempArray[$k] = strval($v);
                                            }
                                        }
                                    }
                                }
                            }
                            //优先计算括号内的内容
                            $evalValue = implode(' ', $this->formulaValue($tempArray));
                            //替换掉所有的&nbsp;、html标签、空格
                            $evalValue = preg_replace('/&nbsp;|<[^>]+>|\s*/', '', $evalValue);
                            if ($evalValue === '') {
                                $tempResult = true;
                            } else {
                                if ($validate == false) {
                                    $evalValue = $evalValue;
                                } else {
                                    if (in_array('+', $tempArray) || in_array('-', $tempArray)) {
                                        $evalValue = "(" . $evalValue . ");";
                                    } else {
                                        $evalValue = $evalValue;
                                    }
                                }
                                try {
                                    eval("\$tempResult=$evalValue;");
                                } catch (\Throwable $th) {
                                    $tempResult = '';
                                }
                            }
                            array_splice($matchList, $leftBracketIndex, ($i - $leftBracketIndex + 1), "'" . $tempResult . "'");
                            $i = 0;
                        }
                    }
                }
            }
            if ($validate) {
                foreach ($matchList as $k => $v) {
                    if (in_array('=', $matchList)) {
                        if ($v != '=') {
                            $matchList[$k] = strval($v);
                        }
                    }
                }
            }
            //处理字符串和函数
            $formulaParsedArray = $this->formulaValue($matchList);
            //替换掉所有的&nbsp;、html标签、空格
            $formulaParsedStr = implode(' ', $formulaParsedArray);
            // |<[^>]+>表单值已经过滤 这里不过滤
            $formulaParsedStr = preg_replace('/&nbsp;|\s*/', '', $formulaParsedStr);
            if ($formulaParsedStr === '') {
                return true;
            }
            try {
                eval("\$result=$formulaParsedStr;");
                return $result;
            } catch (\Exception $e) {
                return '';
            } catch (\Error $error) {
                return '';
            }
        }
    }
    /**
     * 功能函数，转化条件控件符号为判断表达式
     *
     * @method formulaValue
     *
     * @param  [type]             $infoValue         [出口条件内容数组]
     *
     * @return [type]                   [description]
     */
    public function formulaValue($infoValue)
    {
        // + - * / > < 两边的字符都必须是数值型
        // + - 两边的值如果不是数值型则会被转成0
        // * / 两边的值如果不是数值型则会被转成1
        // > < 两边的值如果不是数值型则会忽略此条件 两边的值都转成0 确保值为假的
        $mustNumber = [">", "<", ">=", "<=", "+", "-", "*", "/"];
        $mustString = ["=", "!=", "^=", "$=", "*=", "|=", "~~",":"];

        for ($i = 0; $i < count($infoValue); $i++) {
            //替换掉所有的&nbsp;、html标签、空格
            $infoValue[$i] = preg_replace('/&nbsp;|<[^>]+>|\s*/', '', $infoValue[$i]);
            if (in_array($infoValue[$i], $mustNumber)) {
                if (isset($infoValue[$i - 1]) && isset($infoValue[$i + 1]) && is_string($infoValue[$i - 1]) && is_string($infoValue[$i + 1])) {

                    //只保留数字、小数点、负号
                    $leftNumber = preg_replace('/[^0-9.-]/', '', strval($infoValue[$i - 1]));
                    $rightNumber = preg_replace('/[^0-9.-]/', '', strval($infoValue[$i + 1]));

                    //检查符号两边的是否是数字
                    if (!is_numeric($leftNumber) || !is_numeric($rightNumber)) {
                        if ($infoValue[$i] == '+' || $infoValue[$i] == '-') {
                            $rightNumber = 0;
                        } else if ($infoValue[$i] == '*' || $infoValue[$i] == '/') {
                            $rightNumber = 1;
                        } else if ($infoValue[$i] == '>' || $infoValue[$i] == '<') {
                            $leftNumber = 0;
                            $rightNumber = 0;
                        }
                    } else {
                        $infoValue[$i - 1] = floatval($leftNumber);
                        $infoValue[$i + 1] = floatval($rightNumber);
                    }
                } else {
                    // 错误
                }
            } else if (in_array($infoValue[$i], $mustString)) {

                if (isset($infoValue[$i - 1]) && isset($infoValue[$i + 1]) && is_string($infoValue[$i - 1]) && is_string($infoValue[$i + 1])) {

                    //"^=", "$=", "*=", "|=" 四个需要转换成对应的js函数
                    //= 转成 ==
                    switch ($infoValue[$i]) {
                        case '=':
                            $infoValue[$i - 1] = "'" . $infoValue[$i - 1] . "'";
                            $infoValue[$i] = " == ";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                        case '^=':
                            $infoValue[$i - 1] = "'" . substr($infoValue[$i - 1], 0, strlen($infoValue[$i + 1])) . "'";
                            $infoValue[$i] = " == ";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                        case '$=':
                            $infoValue[$i - 1] = "'" . substr($infoValue[$i - 1], -strlen($infoValue[$i + 1])) . "'";
                            $infoValue[$i] = " == ";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                        case '*=':
                            if (strpos($infoValue[$i - 1], $infoValue[$i + 1]) !== false) {
                                $infoValue[$i - 1] = 1;
                            } else {
                                $infoValue[$i - 1] = 0;
                            }
                            $infoValue[$i] = "";
                            $infoValue[$i + 1] = "";
                            break;
                        case '|=':
                            if (strpos($infoValue[$i - 1], $infoValue[$i + 1]) !== false) {
                                $infoValue[$i - 1] = 0;
                            } else {
                                $infoValue[$i - 1] = 1;
                            }
                            $infoValue[$i] = "";
                            $infoValue[$i + 1] = "";
                            break;
                        case '~~':
                            $leftArray = is_array($infoValue[$i - 1]) ? $infoValue[$i - 1] : explode(',', trim($infoValue[$i - 1], ','));
                            $rightArray = is_array($infoValue[$i + 1]) ? $infoValue[$i + 1] : explode(',', trim($infoValue[$i + 1], ','));
                            $result = [];
                            for ($m = 0; $m < count($leftArray); $m++) {
                                if (in_array($leftArray[$m], $rightArray)) {
                                    $result[] = $leftArray[$m];
                                }
                            }
                            $infoValue[$i - 1] = '(';
                            $infoValue[$i] = "'" . implode(',', $result) . "'";
                            $infoValue[$i + 1] = ')';
                            break;
                        default:
                            $infoValue[$i - 1] = "'" . $infoValue[$i - 1] . "'";
                            $infoValue[$i + 1] = "'" . $infoValue[$i + 1] . "'";
                            break;
                    }
                }
            }
        }

        return $infoValue;
    }

    // public function tagDirective($value, $field, $get = true)
    // {
    //     if ($value === null) {
    //         return '';
    //     }

    //     if ($get) {
    //         if ($get === 'detail') {
    //             return json_decode($value);
    //         } else {
    //             $value = json_decode($value);
    //             if (is_array($value)) {
    //                 $val = [];
    //                 $user_id = own('user_id');
    //                 $public = DB::table('tag')->where('tag_type', 'public')->whereIn('tag_id', $value)->get();
    //                 $private = DB::table('tag')->where('tag_type', 'private')->where('tag_creator', $user_id)->whereIn('tag_id', $value)->get();
    //                 foreach ($public as $key => $value) {
    //                     array_push($val, $value->tag_name);
    //                 }
    //                 foreach ($private as $key => $value) {
    //                     array_push($val, $value->tag_name);
    //                 }
    //                 return $val;
    //             }
    //         }
    //     } else {
    //         return json_encode($value);
    //     }
    // }
    /**
     * 区域指令解析
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function areaDirective($value, $data, $field, $get = true)
    {
        $fieldOption = json_decode($field->field_options, true);

        $relation = $fieldOption['relation'];

        $config = [
            'province' => ['province', 'province_id', 'province_name'],
            'city' => ['city', 'city_id', 'city_name'],
            'district' => ['district', 'district_id', 'district_name'],
        ];

        if ($get) {
            $parseValueArray = [];

            if ($get === 'detail') {
                foreach ($relation as $item) {
                    $fieldCode = $item['field_code'];
                    $parseValueArray[$fieldCode] = $data->{$fieldCode};
                }

                return $parseValueArray;
            } else {
                foreach ($relation as $item) {
                    $type = $item['sourceValue'];

                    $fieldCode = $item['field_code'];
                    $parseValueArray[] = $this->getShowValueByPrimaryKey($config[$type], $data->{$fieldCode});
                }

                return (!empty($parseValueArray[0]) || !empty($parseValueArray[1])) ? implode(' ', $parseValueArray) : "";
            }
        } else {
            return $data;
        }
    }

    /**
     * 日期字段
     *
     * @param string $value
     * @param object $field
     *
     * @return date
     */
    public function dateDirective($value, $item, $field)
    {
        if (empty($value) || $value === '0000-00-00') {
            $value = '';
        }
        return $value;
    }

    /**
     * 时间字段
     *
     * @param string $value
     * @param object $field
     *
     * @return time
     */
    public function timeDirective($value, $item, $field)
    {
        $result = $value;
        if (empty($result) || $result === '00:00:00') {
            $result = '';
        }
        return $result;
    }

    /**
     * 日期时间字段
     *
     * @param string $value
     * @param object $field
     *
     * @return datetime
     */
    public function datetimeDirective($value, $item, $field)
    {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            $value = '';
        }
        return $value;
    }
    public function reserveDecimal($number, $position)
    {
        $ary = explode('.', (string) $number);
        if (isset($ary[1])) {
            if (strlen($ary[1]) > $position) {
                $decimal = substr($ary[1], 0, $position);
                $result = $ary[0] . '.' . $decimal;
                return (float) $result;
            } else {
                return $number;
            }
        } else {
            $pos = pow(10, $position);
            $result = floor($number * $pos) / $pos;
            return $result;
        }

    }
    /**
     * 数字字段
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return number
     */
    public function numberDirective($value, $item, $field, $get = true)
    {
        $result = '';
        if (is_numeric($value)) {
            $result = $value;
            if ($get) {
                if ($get !== 'detail') {
                    $fieldOptions = json_decode($field->field_options);
                    $format = $fieldOptions->format;
                    if (isset($field->type) && $field->type='total') {
                        $format = $fieldOptions->aggregate;
                    }
                    if (!empty($format)) {
                        // 保留小数
                        if (isset($format->decimalPlaces) && $format->decimalPlaces) {
                            $decimalPlacesDigit = $format->decimalPlacesDigit;
                            if (isset($format->rounding) && $format->rounding == 1) {
                                $result = round($value, $format->decimalPlacesDigit);
                            } else {
                                //截位处理
                                // $number = pow(10, $format->decimalPlacesDigit);
                                // $result = ($value * $number)/ $number;
                                // $result = sprintf("%.".$format->decimalPlacesDigit."f",$result);
                                // $result = substr( sprintf( "%.".$format->decimalPlacesDigit."f" , $value), 0, - 1);   // 10.45
                                $result = $this->reserveDecimal($value, $format->decimalPlacesDigit);
                            }
                        } else {
                            //获取小数长度
                            $list = explode('.', strval($result));
                            if (isset($list[1])) {
                                $decimalPlacesDigit = strlen($list[1]);
                            } else {
                                $decimalPlacesDigit = 0;
                            }
                        }

                        if (isset($format->amountInWords) && $format->amountInWords == 1) {
                            $number = number_format($result, 2, '.', '');
                            $result = $this->cny($number);
                        } else if (isset($format->thousandSeparator) && $format->thousandSeparator == 1) {
                            $result = number_format($result, $decimalPlacesDigit);
                        } else {
                            $result = number_format($result, $decimalPlacesDigit, '.', '');
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function cny($num)
    {
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        $number = $num;
        //精确到分后面就不要了，所以只留两个小数位
        $num = round($num, 2);
        //将数字转化为整数
        $num = $num * 100;
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                //获取最后一位数字
                $n = substr($num, strlen($num) - 1, 1);
            } else {
                $n = $num % 10;
            } //每次将最后一位数字转化为中文
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            //去掉数字最后一位了
            $num = $num / 10;
            $num = (int) $num;
            //结束循环
            if ($num == 0) {
                break;
            }

        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            //utf8一个汉字相当3个字符
            $m = substr($c, $j, 6);
            //处理数字中很多0的情况,每次循环去掉一个汉字“零”
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j - 3;
                $slen = $slen - 3;
            }
            $j = $j + 3;
        }
        //这个是为了去掉类似23.0中最后一个“零”字
        if (substr($c, strlen($c) - 3, 3) == '零') {
            $c = substr($c, 0, strlen($c) - 3);
        }

        //将处理的汉字加上“整”
        if (empty($c)) {
            return "零元整";
        } else {
            if(ceil($number) ==$number){
                return $c . "整";
            }else{
                return $c;
            }

        }
    }

    private function _cny_map_unit($list, $units)
    {
        $ul = count($units);
        $xs = array();
        foreach (array_reverse($list) as $x) {
            $l = count($xs);
            if ($x != "0" || !($l % 4)) {
                $n = ($x == '0' ? '' : $x) . (@$units[($l - 1) % $ul]);
            } else {
                $n = is_numeric(@$xs[0][0]) ? $x : '';
            }
            array_unshift($xs, $n);
        }
        return $xs;
    }

    /**
     * 手机号控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function phoneDirective($value, $item, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $result;
    }
    /**
     * 邮件控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function emailDirective($value, $item, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $result;
    }
    /**
     * 身份证号控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function idCardDirective($value, $item, $field, $get = true)
    {
        if (empty($value) && $value !== 0 && $value !== "0") {
            return '';
        }
        return $value;
    }

    /**
     * 网址控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function urlDirective($value, $item, $field, $get = true)
    {
        if (empty($value) && $value !== 0 && $value !== "0") {
            return '';
        }
        return $value;
    }
    /**
     * 自定义格式
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function customDirective($value, $item, $field, $get = true)
    {
        if (empty($value) && $value !== 0 && $value !== "0") {
            return '';
        }
        return $value;
    }
    /**
     * 单选控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string|int
     */
    public function radioDirective($value, $item, $field, $get = true)
    {
        if ($get) {
            if ($get === 'detail') {
                return $value;
            } else {
                $fieldOption = json_decode($field->field_options, true);
                if (isset($fieldOption['datasource'])) {
                    $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
                } else {
                    $dataSourceMap = '';
                }
                return $this->defaultValue($value, $dataSourceMap, '');
            }
        } else {
            return $value;
        }
    }
    /**
     * 文本域控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function textareaDirective($value, $item, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $result;
    }
    public function dynamicInfoDirective($value, $item, $field, $get = true)
    {
        return $value;
    }
    /**
     * 下拉框控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string|int
     */
    public function selectDirective($value, $item, $field, $get = true)
    {
        if ($get) {
            if ($get === 'detail') {
                return $value;
            } else {
                if (!$value && $value !== "0" && $value !== 0) {
                    return '';
                }
                $fieldOption = json_decode($field->field_options, true);
                if (isset($fieldOption['datasource'])) {
                    $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);

                    return $this->defaultValue($value, $dataSourceMap, '');
                } else {
                    $selectConfig = $fieldOption['selectConfig'];
                    return $this->parseSelectValue($selectConfig['sourceType'], $selectConfig['sourceValue'], $item, $field, false);
                }
            }
        } else {
            return $value;
        }
    }
    /**
     * 多选控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return array
     */
    public function checkboxDirective($value, $item, $field, $get = true)
    {
        $result = "";

        if (!isset($value) || $value === '') {
            return $result;
        }
        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['singleMode']) && $fieldOption['singleMode'] == 1) {
            $decodeValue = $value;
            if (!is_array($value)) {
                if (strpos($value, '[') !== false) {
                    $decodeValue = json_decode($value);
                    $decodeValue = $decodeValue[0];
                }
            }
        } else {
            $decodeValue = $value;
            if (!is_array($value)) {
                if (strpos($value, '[') !== false) {
                    $decodeValue = json_decode($value);
                } else {
                    $decodeValue = explode(",", $value);
                }
            }
        }

        if ($get) {
            if ($get === 'detail') {
                $result = $decodeValue;
            } else {
                if (isset($fieldOption['singleMode']) && $fieldOption['singleMode'] == 1) {
                    if (empty($decodeValue)) {
                        $decodeValue = [0];
                    } else {
                        $decodeValue = [1];
                    }
                }
                if (!empty($decodeValue) && isset($fieldOption['datasource']) && is_array($fieldOption['datasource'])) {
                    $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
                    $parseValue = [];
                    if (is_array($decodeValue)) {
                        foreach ($decodeValue as $item) {
                            $parseValue[] = $this->defaultValue($item, $dataSourceMap, '');
                        }
                        $result = implode(',', $parseValue);
                    }
                }
            }
        } else {
            $result = $decodeValue;
            if (is_array($decodeValue)) {
                $result = json_encode($decodeValue);
            }

        }
        return $result;
    }
    /**
     * 附件上传控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return array
     */
    public function uploadDirective($value, $item, $field, $get = true)
    {
        $tableKey = $field->field_table_key;
        if ($get) {
            $attachmentRelationTable = 'attachment_relataion_' . app($this->formModelingRepository)->getCustomDataTableName($tableKey);
            //详情页解析
            // if ($get == 'detail') {
                $attachments = [];

                if (Schema::hasTable($attachmentRelationTable) && $value) {
                    $list = DB::table($attachmentRelationTable)->select(['attachment_id'])->where('entity_id', $value)->where('entity_column', $field->field_code)->get();

                    if (count($list) > 0) {
                        foreach ($list as $item) {
                            $attachments[] = $item->attachment_id;
                        }
                    }
                }

                return $attachments;
            // } else {
            //     //列表页解析
            //     if (Schema::hasTable($attachmentRelationTable) && $value) {
            //         return DB::table($attachmentRelationTable)->select(['attachment_id'])->where('entity_id', $value)->where('entity_column', $field->field_code)->count();
            //     }

            //     return 0;
            // }
        } else {
            return $value;
        }
    }
    /**
     * 编辑器控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string
     */
    public function editorDirective($value, $item, $field, $get = true)
    {
        $result = $value;
        if (!$result) {
            $result = '';
        }
        return $value;
    }
    /**
     * 选择器控件
     *
     * @param string $value
     * @param object $field
     * @param boolean $get
     *
     * @return string|array|int
     */
    public function selectorDirective($value, $data, $field, $get = true, $returnMap = true)
    {
        $value = isset($data->{$field->field_code}) ? $data->{$field->field_code} : '';

        if (empty($value) && $value !== 0 && $value !== '0') {
            return '';
        }
        $fieldOption = json_decode($field->field_options, true);

        $selectorConfig = $fieldOption['selectorConfig'];

        $multiple = (isset($selectorConfig['multiple']) && $selectorConfig['multiple'] == 1) ? true : false;
        if ($get) {
            if ($get === 'detail') {
                if (is_array($value) || (is_string($value) && empty(json_decode($value)))) {
                    return $value;
                }
                $result =  json_decode($value, true, 512,JSON_BIGINT_AS_STRING);
                return is_array($result) ? $result : strval($result);
            } else {
                return $this->parseSelectorValue($field, $data, $selectorConfig, $returnMap);
            }
        } else {
            if (is_array($value)) {
                return $multiple ? json_encode($value) : $value;
            } else {
                return $value;
            }

        }
    }
    /**
     * 明细字段控件
     *
     * @param array $value
     * @param object $field
     * @param boolean $get
     *
     * @return array
     */
    public function detailLayoutDirective($value, $item, $field, $get = true)
    {
        $tableKey = $field->field_table_key;
        if ($get) {
            $tablekey = app($this->formModelingRepository)->getCustomDataTableName($tableKey);

            $fieldDataSubTable = $tablekey . '_' . $field->field_code;
            $primaryKey = $this->getDataTablePrimaryKeyName($tableKey);

            if (Schema::hasTable($fieldDataSubTable)) {
                $data = DB::table($fieldDataSubTable)->where($primaryKey, $value)->orderBy("detail_data_id", "asc")->get();
                foreach ($data as $key => $value) {
                    foreach ($value as $k => $v) {
                        if(preg_match("/^\[.*\]$/",$v)){
                            $data[$key]->$k = json_decode($v);
                        }
                    }
                }

                return $data;
            }

        } else {
            return $value;
        }
    }

    private function getDataTablePrimaryKeyName($tableKey)
    {
        global $array;

        if (isset($array[$tableKey])) {
            return $array[$tableKey];
        }

        return $array[$tableKey] = app($this->formModelingRepository)->getCustomDataPrimayKey($tableKey);
    }

    /**
     * 解析下来框值
     *
     * @param string $sourceType
     * @param string $sourceValue
     * @param int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSelectValue($sourceType, $sourceValue, $data, $field, $multiple = false)
    {
        global $array;
        $value = isset($data->{$field->field_code})?$data->{$field->field_code}:'';

        $jsonSourceValue = json_encode($sourceValue);
        if (isset($array[$sourceType . $jsonSourceValue . $value])) {
            return $array[$sourceType . $jsonSourceValue . $value];
        }

        switch ($sourceType) {
            case 'combobox': //系统内置下拉框
                $parseValue = $this->parseComboboxValue($sourceValue, $value, $multiple);
                break;
            case 'systemData': //系统数据下拉框
                $parseValue = $this->parseSystemDataSelectValue($sourceValue, $value, $multiple);
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
                $parseValue = $this->parseFileSelectValue($sourceValue, $value, $data, $multiple);
                break;
        }

        return $array[$sourceType . $jsonSourceValue . $value] = $parseValue;
    }
    /**
     * 解析来自sql语句的下拉框值
     *
     * @param array $sourceValue
     * @param string $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSqlSelectValue($sourceValue, $data, $field, $multiple)
    {
        $sql = $sourceValue['sql'];
        $value = isset($data->{$field->field_code})?$data->{$field->field_code}:'';

        if ($multiple) {
            $value = json_decode($value, true);
            if (empty($value)) {
                $value = '';
            }
        }

        if (!$value && $value !== 0) {
            return '';
        }

        if (!is_array($value)) {
            $value = [$value];
        }
        $list = $this->getSqlData($sourceValue, $data);
        $result = [];
        if (is_array($list) && count($list) > 0) {
            $keys = array_keys(json_decode(json_encode($list[0]), true));
            if (count($keys) >= 1) {
                $idField = $keys[0];
                $showField = isset($keys[1]) ? $keys[1] : $keys[0];
                if ($field->field_directive == 'text') {
                    $result = array_column($list, $showField);
                } else {
                    foreach ($list as $item) {
                        $index = array_search($item->$idField, $value);
                        if ($index !== false) {
                            // 位置没对应是问题
                            array_push($result, $item->$showField);
                            array_splice($value, $index, 1);
                        }
                        if (count($value) === 0) {
                            break;
                        }
                    }
                }

            }
        }
        return implode(',', $result);
    }

    public function getSqlData($sourceValue, $data)
    {
        global $sqlArr;
        $jsonSourceValue = json_encode($sourceValue);

        $sql = $sourceValue['sql'];
        // 处理依赖字段
        if (isset($sourceValue['dependence']) && is_array($sourceValue['dependence']) && count($sourceValue['dependence']) > 0) {
            foreach ($sourceValue['dependence'] as $key) {
                if (isset($data->{'raw_' . $key})) {
                    $sql = str_replace('#' . $key . '#', $data->{'raw_' . $key}, $sql);
                } else if (isset($data->$key)) {
                    $sql = str_replace('#' . $key . '#', $data->$key, $sql);
                }

            }
        }
        // 替换系统常量
        $sql = $this->strReplace($sql);
        if (isset($sourceValue['externalDB']) && !empty($sourceValue['externalDB'])) {
            $param = [
                'database_id' => $sourceValue['externalDB'],
                'sql' => $sql,
            ];
            $list = app($this->externalDatabaseService)->getExternalDatabasesDataBySql($param);
            if($list && is_array($list)) {
                $list = $list['list'];
            } else {
                $list = '';
            }
        } else {
            $list = DB::select($sql);
        }
        $sqlArr[$jsonSourceValue] = $list ? $list : '';
        return $list;
    }
    public function strReplace($sql)
    {
        $userInfo = $this->current_user_info ? $this->current_user_info : own('');
        $systemConstants = [
            '#loginUserId#' => 'user_id',
            '#loginUserName#' => 'user_name',
            '#loginUserAccount#' => 'user_accounts',
            '#loginUserRoleId#' => 'role_id',
            '#loginUserDeptId#' => 'dept_id',
            '#loginUserDeptName#' => 'dept_name',
        ];

        $result = $sql;
        foreach ($systemConstants as $key => $item) {
            if (strpos($result, $key) !== false) {
                $value = $userInfo[$item];
                if (is_array($value) && count($value) > 0) {
                    $value = $value[0];
                } /* else {
                $value = '';
                }*/
                $result = str_replace($key, $value, $result);
            }
        }

        return $result;
    }
    /**
     * 组合in sql语句
     *
     * @param array $values
     * @param string $key
     *
     * @return string
     */
    private function setInSql($values, $key)
    {
        $query = " `$key` IN (";

        foreach ($values as $v) {
            $query .= "'$v',";
        }

        $query = rtrim($query, ',') . ')';

        return $query;
    }
    /**
     * 解析来自外部文件的下拉框值
     *
     * @staticvar array $array
     * @param array $sourceValue
     * @param string|int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseFileSelectValue($sourceValue, $value, $datas, $multiple)
    {
        global $array;
        $result = '';
        $dependence = isset($sourceValue['dependence']) ? json_encode($sourceValue['dependence']) : '';
        if (!empty($value)) {
            // if (isset($array[$sourceValue['filePath'] . $dependence])) {
            //     $data = $array[$sourceValue['filePath'] . $dependence];
            // } else {
                if (isset($sourceValue['dependence']) && !empty($sourceValue['dependence'])) {
                    foreach ($sourceValue['dependence'] as $k => $v) {
                        $parent[$v] = $datas->$v;
                    }
                }
                if (!empty($parent)) {
                    $json = app('App\EofficeApp\Api\Services\ApiService')->getUrlData(['url' => $sourceValue['filePath'], 'parent' => json_encode($parent)]);
                } else {
                    $json = app('App\EofficeApp\Api\Services\ApiService')->getUrlData(['url' => $sourceValue['filePath']]);
                }
                if($json && is_array($json)) {
                    $data = json_decode($json['content'], true);
                } else {
                    $data = [];
                }
                $array[$sourceValue['filePath'] . $dependence] = $data;
            // }
            if ($multiple) {
                $parseValue = '';

                foreach (json_decode($value) as $v) {
                    $parseValue .= (isset($data[$v]) ? $data[$v] : '') . ',';
                }

                $result = rtrim($parseValue, ',');
            }
            if (isset($data[$value])) {
                $result = $data[$value];
            }

        }
        return $result;
    }
    /**
     * 解析系统数据下拉框
     *
     * @param array $sourceValue
     * @param string|int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSystemDataSelectValue($sourceValue, $value, $multiple)
    {
        $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
        if (empty($selectConfig)) {
            return '';
        }
        if (isset($selectConfig[$sourceValue['field']])) {
            $selectConfig = $selectConfig[$sourceValue['field']];
        }

        if (count($selectConfig) == 4) {
            $services = isset($selectConfig[0]) ? $selectConfig[0] : '';
            $method = isset($selectConfig[1]) ? $selectConfig[1] : '';
            $primaryKey = isset($selectConfig[2]) ? $selectConfig[2] : '';
            $field = isset($selectConfig[3]) ? $selectConfig[3] : '';
            if (method_exists(app($services), $method)) {
                $param = [];
                $idArray = explode(',', $value);
                $param['search'][$primaryKey] = [$idArray, "in"];
                $result = app($services)->$method($param);
                return implode(',', array_column($result, $field));
            }
        } else {
            return empty($selectConfig) ? '' : $this->getShowValueByPrimaryKey($selectConfig, $value, $multiple);
        }

    }
    /**
     * 解析系统内置下拉框
     *
     * @param string $sourceValue
     * @param string $value
     * @param boolean $multiple
     *
     * @return type
     */
    private function parseComboboxValue($sourceValue, $value, $multiple)
    {

        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();
        if (empty($combobox)) {
            return '';
        }
        $comboboxTableName = get_combobox_table_name($combobox->combobox_id);
        $query = DB::table('system_combobox_field')->select('field_name', 'field_id')->where('combobox_id', $combobox->combobox_id);

        if ($multiple) {
            $fieldValues = json_decode($value);

            if (empty($fieldValues)) {
                return [];
            }
            $comboboxFields = $query->whereIn('field_value', $fieldValues)->get()->toArray();
            return implode(',', array_column($comboboxFields, 'field_name'));
        } else {
            $comboboxFields = $query->where('field_value', $value)->first();
            if (isset($comboboxFields->field_name)) {
                $comboboxFields->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $comboboxFields->field_id);
                return $comboboxFields->field_name;
            } else {
                return "";
            }
        }
    }
    /**
     * 解析选择器的值
     *
     * @staticvar array $array
     * @param string $category
     * @param string $type
     * @param object $data
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseSelectorValue($field, $data, $config, $returnMap = true)
    {
        global $array;

        $value = $data->{$field->field_code};
        if(is_array($value)){
            $value = implode(",",$value);
        }
        $category = $config['category'];
        $type = $config['type'];
        if (isset($array[$category . $type . $value])) {
            return $array[$category . $type . $value];
        }

        if ($category == 'custom') {
            //自定义选择器
            $parseValue = $this->parseCustomSelectorValue($field, $data, $config, $returnMap);
        } else {
            $multiple = (isset($config['multiple']) && $config['multiple'] == 1) ? true : false;
            //系统内置选择器
            $selectorConfig = config('customfields.selector.' . $category . '.' . $type);
            $parseValue = empty($selectorConfig) ? [] : $this->getShowValueByPrimaryKey($selectorConfig, $value, $multiple, $returnMap);
        }
        return $array[$category . $type . $value] = $parseValue;
    }
    private function getApiToken()
    {
        if (!$this->apiToken) {
            if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']) {
                return explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[1];
            }
            if (isset($_REQUEST['api_token'])) {
                return $_REQUEST['api_token'];
            }
            return false;
        } else {
            return $this->apiToken;
        }
    }
    /**
     * 解析自定义选择器的值
     *
     * @param string $type
     * @param int|string $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseCustomSelectorValue($field, $data, $config, $returnMap = true)
    {
        $result = [];
        $value = $data->{$field->field_code};
        if (!$selectorConfig = $this->getCustomSelectorConfig($config['type'])) {
            return $result;
        }
        $multiple = (isset($config['multiple']) && $config['multiple'] == 1) ? true : false;
        if ($multiple) {
            try {
                if (!is_array($value)) {
                    if (strpos($value, '[') !== false) {
                        $value = json_decode($value);
                    } else {
                        $value = explode(",", $value);
                    }
                }
                if (is_array($value) && count($value) === 0) {
                    $value = '';
                }
            } catch (\Exception $e) {
                $value = '';
            }
        }

        if ($value) {
            $idField = $selectorConfig['idField'];
            $showField = $selectorConfig['showField'];
            if (isset($selectorConfig['source']) && !empty($selectorConfig['source'])) {
                // 自定义api
                static $urlArray;
                if (isset($urlArray[$selectorConfig['source']])) {
                    $result = $urlArray[$selectorConfig['source']];
                } else {
                    $url = $this->mergeUrl($selectorConfig['source']);
                    $result = getHttps($url);
                    $urlArray[$selectorConfig['source']] = $result;
                }
                $resultArray = json_decode($result, true);
                $res = [];
                if (isset($resultArray['data']) && !empty($resultArray['data'])) {
                    $res = isset($resultArray['data']['list']) ? $resultArray['data']['list'] : $resultArray['data'];
                }
                $result = [];
                $value = is_array($value) ? $value : [$value];
                if (!empty($res) && is_array($res)) {
                    foreach ($res as $k => $v) {
                        if (in_array($v[$idField], $value)) {
                            $result[$v[$idField]] = $v[$showField];
                        }
                    }
                }
                if ($returnMap) {
                    return $result;
                } else {
                    return implode(',', array_values($result));
                }

            } else {
                $sql = $selectorConfig['extendConfig']['source'] ?? '';
                $filePath = $selectorConfig['extendConfig']['filePath'] ?? '';
                if (!empty($sql)) {
                    //替换系统变量
                    $sql = $this->strReplace($sql);
                    //替换依赖字段值
                    if (isset($config['dependence']) && is_array($config['dependence'])) {
                        foreach ($config['dependence'] as $key => $fieldCode) {
                            if (isset($data->{'raw_' . $fieldCode})) {
                                $parentValue = $data->{'raw_' . $fieldCode};
                            } else {
                                $parentValue = $data->$fieldCode;
                            }
                            $sql = str_replace("@$key@", $parentValue, $sql);
                        }
                    }

                    if (is_array($value)) {
                        $parsedValue = "'" . implode("','", $value) . "'";
                    } else {
                        $parsedValue = "'" . $value . "'";
                    }

                    $sql = preg_replace('/<\/?id>/i', ' ', $sql); // 取消id标签
                    $sql = preg_replace('/<\w+>(.|\n)*?<\/\w+>/', ' ', $sql); //删除剩余的标签
                    $sql = preg_replace('/@value/i', $parsedValue, $sql); //替换具体值

                    $params = [
                        'sql' => $sql,
                        'page' => 0,
                    ];

                    if (isset($selectorConfig['extendConfig']['externalDB'])) {
                        $params['database_id'] = $selectorConfig['extendConfig']['externalDB'];
                    }
                    $responseData = app('App\EofficeApp\Api\Services\ApiService')->testSql($params, 'execute');
                    if (is_array($responseData) && isset($responseData['list']) && is_array($responseData['list']) && count($responseData['list']) > 0) {
                        $result = [];
                        $value = is_array($value) ? $value : [$value];
                        foreach ($responseData['list'] as $data) {
                            if (in_array($data->$idField, $value)) {
                                $result[$data->$idField] = $data->$showField;
                            }
                        }
                    }
                } else if (!empty($filePath)) {
                    $params = [
                        'url' => $filePath,
                        'page' => 0,
                    ];

                    // 依赖字段值
                    if (isset($config['dependence']) && is_array($config['dependence'])) {
                        foreach ($config['dependence'] as $key => $fieldCode) {
                            $params[$key] = $data->$fieldCode;
                        }
                    }

                    if (is_array($value)) {
                        $params[$idField] = implode(',', $value);
                    } else {
                        $params[$idField] = $value;
                    }
                    $responseData = app('App\EofficeApp\Api\Services\ApiService')->getUrlData($params);
                    if (is_array($responseData) && isset($responseData['content'])) {
                        $list = [];
                        try {
                            $responseData = json_decode($responseData['content']);
                            if (isset($responseData->list) && is_array($responseData->list)) {
                                $list = $responseData->list;
                            }
                        } catch (\Exception $e) {

                        }
                        if (count($list) > 0) {
                            $result = [];
                            $value = is_array($value) ? $value : [$value];
                            foreach ($list as $data) {
                                if (in_array($data->$idField, $value)) {
                                    $result[$data->$idField] = $data->$showField;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($returnMap) {
            return $result;
        } else {
            return implode(',', array_values($result));
        }
    }

    public function mergeUrl($url)
    {
        if ($this->server_info) {
            $_SERVER = $this->server_info;
        }
        $domain = isset($_SERVER['REQUEST_ROOT']) ? $_SERVER['REQUEST_ROOT'] : app('request')->root();
        $domain = $domain . "/" . $url;
        if (strpos($domain, '?') === false) {
            $url = $domain . '?api_token=' . $this->getApiToken();
        } else {
            $url = $domain . '&api_token=' . $this->getApiToken();
        }
        return $url;
    }
    /**
     * 解析外部数据库的值
     *
     * @param array $dbConfig
     * @param string $primaryKey
     * @param string $showColumn
     * @param string $tableName
     * @param string|int $value
     * @param boolean $multiple
     *
     * @return string
     */
    private function parseExternalDatabaseValue($dbConfig, $primaryKey, $showColumn, $tableName, $value, $multiple)
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionConfig = [
            'driver' => 'pdo_' . $dbConfig->driver,
            'host' => $dbConfig->host,
            'port' => $dbConfig->port,
            'user' => $dbConfig->username,
            'password' => $dbConfig->password ? $dbConfig->password : '',
            'dbname' => $dbConfig->database,
            // 20210524-丁鹏，修改公共部分；1、删除这里的charset（dbal更新到3.0，pdo_sqlsrv的方式不再支持charset属性）；2、经过和刘宁确认，前端目前没有“数据源来自外部系统，选字段，解析值”的功能，所以此函数对应的“case 'outside': //外部数据库下拉框”分支应该没用到；3、基于“2”，改后没法测试，会跟测试单独沟通
            // 'charset' => 'utf8',
        ];

        try {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionConfig, $config);

            $query = "SELECT `$showColumn` FROM $tableName WHERE 1=1 ";

            if ($multiple) {
                $query .= " AND " . $this->setInSql(json_decode($value, true), $primaryKey);

                if ($result = $conn->fetchAll($query)) {
                    return implode(',', array_column($result, $showColumn));
                }
            } else {
                if ($result = $conn->fetchAssoc($query . " AND $primaryKey = ?", array($value))) {
                    return $result[$showColumn];
                }
            }

            return '';
        } catch (\Exception $e) {
            // return trans('fields.failed_connection');
            return '';
        }
    }
    /**
     * 获取外部数据库配置
     *
     * @staticvar array $array
     * @param int $databaseId
     *
     * @return object
     */
    private function getExternalDatabaseConfig($databaseId)
    {
        global $array;

        if (isset($array[$databaseId])) {
            return $array[$databaseId];
        }

        $result = DB::table('external_database')->where('database_id', $databaseId)->first();

        return $array[$databaseId] = $result;
    }
    /**
     * 获取自定义选择器配置信息
     *
     * @staticvar array $array
     * @param string $type
     *
     * @return array;
     */
    private function getCustomSelectorConfig($type)
    {
        global $array;

        if (isset($array[$type])) {
            return $array[$type];
        }
        $result = DB::table('customize_selector')->select(['config'])->where('identifier', $type)->first();

        if ($result && $result->config) {
            return $array[$type] = json_decode($result->config, true);
        }

        return false;
    }
    /**
     * 根据唯一键值获取显示的值
     *
     * @param string $table
     * @param string $primaryKey
     * @param int $id
     * @param array $field
     * @param boolean $mulit
     *
     * @return string
     */
    private function getShowValueByPrimaryKey($selectConfig, $id, $mulit = false, $isSelector = false)
    {
        if (count($selectConfig) > 3) {
            $services = isset($selectConfig[0]) ? $selectConfig[0] : '';
            $method = isset($selectConfig[1]) ? $selectConfig[1] : '';
            $primaryKey = isset($selectConfig[2]) ? $selectConfig[2] : '';
            $field = isset($selectConfig[3]) ? $selectConfig[3] : '';
            if (method_exists(app($services), $method)) {
                if ($this->current_user_info) {
                    $userInfo = $this->current_user_info;
                } else {
                    $userInfo = own('');
                }
                $result = app($services)->$method($id, $userInfo);
                // 优化返回列表数据的结构
                if ($isSelector) {
                    return $this->arrayMapWidthKey($result, $primaryKey, $field);
                } else {
                    return implode(',', array_column($result, $field));
                }
            }
        } else {
            $table = $selectConfig[0];
            $primaryKey = $selectConfig[1];
            $field = $selectConfig[2];
            $tableConfig = config('customfields.table');
            $idArray = json_decode($id);
            if (empty($idArray) && is_string($id) && strpos($id, ',') !== false) {
                $idArray = explode(',', $id);
            }
            if (is_array($idArray)) {
                if (is_array($idArray)) {
                     $idString = implode("','",$idArray);
                    $query = DB::table($table)->select([$primaryKey, $field])->whereIn($primaryKey, $idArray)->orderByRaw("FIELD($primaryKey,  '$idString')");
                } else {
                    $query = DB::table($table)->select([$primaryKey, $field])->where($primaryKey, $idArray);
                }
                if (Schema::hasColumn($table, 'deleted_at')) {
                    $query = $query->whereNull($table . '.deleted_at');
                }
                $datas = $query->get()->toArray();
                $result = [];
                foreach ($datas as $key => $value) {
                    if (in_array($table, $tableConfig)) {
                        $value = mulit_trans_dynamic("$table.$field" . '.' . $value);
                    }
                    $result[] = (array) $value;
                }
                if ($isSelector) {
                    return $this->arrayMapWidthKey($result, $primaryKey, $field);
                } else {
                    return implode(',', array_column($result, $field));
                }
            } else {
                $query = DB::table($table)->select([$field])->where($primaryKey, $id);
                if (Schema::hasColumn($table, 'deleted_at')) {
                    $query = $query->whereNull($table . '.deleted_at');
                }
                $data = $query->first();
                if ($isSelector) {
                    if (in_array($table, $tableConfig)) {
                        return $data ? [$id => mulit_trans_dynamic("$table.$field" . '.' . $data->{$field})] : [$id => ''];
                    }

                    return $data ? [$id => $data->{$field}] : [$id => ''];
                } else {
                    if (in_array($table, $tableConfig)) {
                        return $data ? mulit_trans_dynamic("$table.$field" . '.' . $data->{$field}) : '';
                    }
                    return $data ? $data->{$field} : '';
                }
            }

        }

    }
    private function arrayMapWidthKey($data, $keyField, $valueField)
    {
        $map = [];
        if (count($data) > 0) {
            foreach ($data as $item) {
                if (is_object($item)) {
                    $map[$item->{$keyField}] = $item->{$valueField};
                } else {
                    $map[$item[$keyField]] = $item[$valueField];
                }
            }
        }
        return $map;
    }
    /**
     * @获取默认值
     *
     * @author 李志军
     *
     * @param string $key
     * @param array $data
     * @param type $default
     *
     * @return 相应的值
     */
    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    /**
     * 二维对象数组转为一维键值映射数组
     *
     * @param array $dataSource
     * @param string $keyName
     * @param string $valueName
     *
     * @return array
     */
    private function parseDatasourcKeyValueMap($dataSource, $keyName = 'id', $valueName = 'title')
    {
        $map = [];
        if (!empty($dataSource)) {
            foreach ($dataSource as $item) {
                $map[$item[$keyName]] = $item[$valueName];
            }
        }

        return $map;
    }

    public function outsourceSelect($value, $field)
    {
        if (!$value && $value === '') {
            return '';
        }
        $fieldOption = json_decode($field->field_options, true);

        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $this->outsourceDatasourcKeyValueMap($fieldOption['datasource']);

            return $this->defaultValue($value, $dataSourceMap, '');
        } else {
            $selectConfig = $fieldOption['selectConfig'];

            return $this->outsourceSelectValue($selectConfig['sourceType'], $selectConfig['sourceValue'], $value);
        }
    }

    private function outsourceDatasourcKeyValueMap($dataSource, $keyName = 'id', $valueName = 'title')
    {
        $map = [];

        if (!empty($dataSource)) {
            foreach ($dataSource as $item) {
                $map[$item[$valueName]] = $item[$keyName];
            }
        }

        return $map;
    }

    private function outsourceSelectValue($sourceType, $sourceValue, $value)
    {
        global $array;

        $jsonSourceValue = json_encode($sourceValue);

        if (isset($array[$sourceType . $jsonSourceValue . $value])) {
            return $array[$sourceType . $jsonSourceValue . $value];
        }

        switch ($sourceType) {
            case 'combobox': //系统内置下拉框
                $parseValue = $this->outsourceComboboxValue($sourceValue, $value);
                break;
            case 'systemData': //系统数据下拉框
                $parseValue = $this->outsourceSystemDataSelectValue($sourceValue, $value);
                break;
            case 'outside': //外部数据库下拉框
                if ($dbConfig = $this->getExternalDatabaseConfig($sourceValue['database_id'])) {
                    $parseValue = $this->outsourceExternalDatabaseValue($dbConfig, $sourceValue['idField'], $sourceValue['showField'], $sourceValue['table_name'], $value);
                } else {
                    $parseValue = $value;
                }
                break;
            case 'sql': //自定义sql语句下拉框
                $parseValue = $this->outsourceSqlSelectValue($sourceValue, $value);
                break;
            case 'file': //来自外部文件下拉框
                $parseValue = $this->outsourceFileSelectValue($sourceValue, $value);
                break;
        }

        return $parseValue;
    }

    private function outsourceComboboxValue($sourceValue, $value)
    {

        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();

        $query = DB::table('system_combobox_field')->select(['field_value'])->where('combobox_id', $combobox->combobox_id);

        $comboboxFields = $query->where('field_name', $value)->first();

        return isset($comboboxFields->field_value) ? $comboboxFields->field_value : "";

    }

    private function outsourceSystemDataSelectValue($sourceValue, $value)
    {
        $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
        if (isset($selectConfig[$sourceValue['field']])) {
            $selectConfig = $selectConfig[$sourceValue['field']];
        }

        return empty($selectConfig) ? '' : $this->outsourceShowValueByPrimaryKey($selectConfig[0], $selectConfig[1], $value, $selectConfig[2]);
    }

    private function outsourceShowValueByPrimaryKey($table, $primaryKey, $id, $field)
    {
        if ($table == "vehicles") {
            $id = strstr($id, '(', true);
        }
        $data = DB::table($table)->select([$primaryKey])->where($field, $id)->first();
        $cc = DB::table($table)->select([$primaryKey])->where($field, $id)->toSql();
        return $data ? $data->{$primaryKey} : '';
    }

    private function outsourceSqlSelectValue($sourceValue, $value)
    {
        $sql = $sourceValue['sql'] . ' where ';
        $idField = $sourceValue['idField'];
        $showField = $sourceValue['showField'];

        if (empty($value)) {
            return "";
        } else {
            if (strpos($sourceValue['sql'], "where") !== false) {
                $sql = $this->strReplace($sourceValue['sql']);
                $sql = $sql . ' and ';
            }
            $result = DB::select($sql . "`$showField` = '$value'");

        }

        return $result[0]->{$idField};
    }
    private function outsourceFileSelectValue($sourceValue, $value)
    {
        global $array;
        if (!empty($value)) {
            if (isset($array[$sourceValue['filePath']])) {
                $data = $array[$sourceValue['filePath']];
            } else {
                $json = app('App\EofficeApp\Api\Services\ApiService')->getUrlData(['url' => $sourceValue['filePath']]);

                $data = json_decode($json['content'], true);

                $array[$sourceValue['filePath']] = $data;
            }
            $data = array_flip($data);
            return $data[$value];
        }

    }

    private function outsourceExternalDatabaseValue($dbConfig, $primaryKey, $showColumn, $tableName, $value)
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionConfig = [
            'driver' => 'pdo_' . $dbConfig->driver,
            'host' => $dbConfig->host,
            'port' => $dbConfig->port,
            'user' => $dbConfig->username,
            'password' => $dbConfig->password ? $dbConfig->password : '',
            'dbname' => $dbConfig->database,
            // 20210524-丁鹏，修改公共部分；1、删除这里的charset（dbal更新到3.0，pdo_sqlsrv的方式不再支持charset属性）；2、经过和刘宁确认，前端目前没有“数据源来自外部系统，选字段，解析值”的功能，所以此函数对应的“case 'outside': //外部数据库下拉框”分支应该没用到；3、基于“2”，改后没法测试，会跟测试单独沟通
            // 'charset' => 'utf8',
        ];

        try {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionConfig, $config);
            $query = "SELECT `$primaryKey` FROM $tableName WHERE 1=1 ";
            if ($result = $conn->fetchAssoc($query . " AND $showColumn = ?", array($value))) {
                return $result[$primaryKey];
            }

            return '';
        } catch (\Exception $e) {
            return '获取外部数据库数据失败';
        }
    }

    public function outsourceCheckboxDirective($value, $item, $field)
    {
        $result = [];
        $fieldOption = json_decode($field->field_options, true);

        if (isset($fieldOption['singleMode']) && $fieldOption['singleMode'] == 1) {
            if (empty($value)) {
                $value = [0];
            } else {
                $value = [1];
            }
        }

        if (!empty($value) && isset($fieldOption['datasource']) && is_array($fieldOption['datasource'])) {
            $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
            $dataSourceMap = array_flip($dataSourceMap);
            foreach ($value as $item) {
                $data = $this->defaultValue($item, $dataSourceMap, '');
                if ($data !== '') {
                    $result[] = $data;
                }

            }
        }
        return $result;
    }

    public function outsourceRadioDirective($value, $item, $field)
    {
        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $this->parseDatasourcKeyValueMap($fieldOption['datasource']);
            $dataSourceMap = array_flip($dataSourceMap);
        } else {
            $dataSourceMap = '';
        }

        return $this->defaultValue($value, $dataSourceMap, '');

    }

    public function radioDirectiveExcel($field)
    {

        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $fieldOption['datasource'];
        } else {
            $dataSourceMap = '';
        }
        return $dataSourceMap;
    }

    public function selectDirectiveExcel($field, $header = 0)
    {

        $fieldOption = json_decode($field->field_options, true);

        if (isset($fieldOption['datasource'])) {
            if ($header == 1) {
                return '';
            }
            return $fieldOption['datasource'];
        } else {

            $selectConfig = $fieldOption['selectConfig'];

            return $this->parseSelectValueExcel($selectConfig['sourceType'], $selectConfig['sourceValue'], $header);
        }

    }

    private function parseSelectValueExcel($sourceType, $sourceValue, $header)
    {
        switch ($sourceType) {
            case 'combobox': //系统内置下拉框
                if ($header == 1) {
                    return ['field_value', 'field_name'];
                }
                $parseValue = $this->parseComboboxValueExcel($sourceValue);
                break;
            case 'systemData': //系统数据下拉框
                if ($header == 1) {
                    $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
                    if (isset($selectConfig[$sourceValue['field']])) {
                        $selectConfig = $selectConfig[$sourceValue['field']];
                    }
                    if (count($selectConfig) == 4 && !is_array($selectConfig[0])) {
                        return [$selectConfig[2], $selectConfig[3]];
                    } else {
                        return [$selectConfig[1], $selectConfig[2]];
                    }

                }
                $parseValue = $this->parseSystemDataSelectValueExcel($sourceValue);
                break;
            case 'outside': //外部数据库下拉框
                if ($dbConfig = $this->getExternalDatabaseConfig($sourceValue['database_id'])) {
                    if ($header == 1) {
                        return [$sourceValue['idField'], $sourceValue['showField']];
                    }
                    $parseValue = $this->parseExternalExcel($dbConfig, $sourceValue['idField'], $sourceValue['showField'], $sourceValue['table_name']);
                    return $parseValue;
                }
                break;
            case 'sql': //自定义sql语句下拉框
                $parseValue = $this->parseSqlSelectValueExcel($sourceValue, $header);
                break;
            case 'file': //来自外部文件下拉框
                // if ($header == 1) {
                //     return $this->parseFileSelectValueExcel($sourceValue, 1);
                // }
                // $parseValue = $this->parseFileSelectValueExcel($sourceValue);
                return [];
                break;
        }

        return $parseValue;
    }

    private function parseComboboxValueExcel($sourceValue)
    {

        $combobox = DB::table('system_combobox')->select(['combobox_id'])->where('combobox_identify', $sourceValue)->first();
        if (empty($combobox)) {
            return [];
        }
        $result = DB::table('system_combobox_field')->select('field_value', 'field_name', 'field_id')->where('combobox_id', $combobox->combobox_id)->get();
        foreach ($result as $key => $value) {
            $comboboxTableName = get_combobox_table_name($combobox->combobox_id);
            $result[$key]->field_name = mulit_trans_dynamic($comboboxTableName . ".field_name.combobox_field_" . $value->field_id);

        }
        foreach ($result as $k => $v) {
            unset($v->field_id);
        }
        return json_decode(json_encode($result), true);
    }

    private function parseSystemDataSelectValueExcel($sourceValue)
    {

        $selectConfig = config('customfields.systemDataSelect.' . $sourceValue['module']);
        if (empty($selectConfig)) {
            return '';
        }
        if (isset($selectConfig[$sourceValue['field']])) {
            $selectConfig = $selectConfig[$sourceValue['field']];
        }

        if (count($selectConfig) == 4) {
            $services = isset($selectConfig[0]) ? $selectConfig[0] : '';
            $method = isset($selectConfig[1]) ? $selectConfig[1] : '';
            $primaryKey = isset($selectConfig[2]) ? $selectConfig[2] : '';
            $field = isset($selectConfig[3]) ? $selectConfig[3] : '';
            if (method_exists(app($services), $method)) {
                $param = [];
                $result = app($services)->$method($param);
                return $result;
            }
        } else {
            return empty($selectConfig) ? '' : $this->getShowValueByPrimaryKeyExcel($selectConfig);
        }
    }

    private function parseSqlSelectValueExcel($sourceValue, $header)
    {
        $result = [];
        if (!isset($sourceValue['dependence'])) {
            $sql = $sourceValue['sql'];
            $sql = $this->strReplace($sql);
            if (isset($sourceValue['externalDB']) && !empty($sourceValue['externalDB'])) {
                $param = [
                    'database_id' => $sourceValue['externalDB'],
                    'sql' => $sql,
                ];
                $list = app($this->externalDatabaseService)->getExternalDatabasesDataBySql($param);
                $result = $list['list'];
            } else {
                $result = DB::select($sql);
            }
        }
        if ($result && is_array($result)) {
            if ($header == 1) {
                $arr = (array) $result[0];
                return array_keys($arr);
            }
            foreach ($result as $key => $value) {
                $result[$key] = (array) $value;
            }
        }

        return $result;
    }

    private function parseFileSelectValueExcel($sourceValue, $header = 0)
    {

        $json = app('App\EofficeApp\Api\Services\ApiService')->getUrlData(['url' => $sourceValue['filePath']]);

        $data = json_decode($json['content'], true);
        if ($header == 1) {
            $k = [];
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $k[] = $key;
                }
            }
            return $k;
        }

        return [$data];
    }

    public function selectorDirectiveExcel($field, $header = 0)
    {
        $fieldOption = json_decode($field->field_options, true);

        $selectorConfig = $fieldOption['selectorConfig'];
        return $this->parseSelectorValueExcel($selectorConfig['category'], $selectorConfig['type'], $header);

    }

    private function parseSelectorValueExcel($category, $type, $header)
    {
        if ($category == 'custom') {
            //自定义选择器
            if ($header == 1) {
                $selectorConfig = $this->getCustomSelectorConfig($type);
                return [$selectorConfig['idField'], $selectorConfig['showField']];
            }
            $parseValue = $this->parseCustomSelectorValueExcel($type);
        } else {
            //系统内置选择器
            $selectorConfig = config('customfields.selector.' . $category . '.' . $type);
            if ($header == 1) {
                if (count($selectorConfig) > 3) {
                    return [$selectorConfig[2], $selectorConfig[3]];
                } else {
                    return [$selectorConfig[1], $selectorConfig[2]];
                }
            }
            $parseValue = empty($selectorConfig) ? '' : $this->getShowValueByPrimaryKeyExcel($selectorConfig);
        }
        return $parseValue;
    }

    private function parseCustomSelectorValueExcel($type)
    {
        $result = [];
        $selectorConfig = $this->getCustomSelectorConfig($type);
        $idField = $selectorConfig['idField'];
        $showField = $selectorConfig['showField'];
        if (isset($selectorConfig['source']) && !empty($selectorConfig['source'])) {
            //自定义api
            // return [];
            // 自定义api
            $url = $this->mergeUrl($selectorConfig['source']);
            $result = getHttps($url);
            $resultArray = json_decode($result, true);
            $res = [];
            if (isset($resultArray['data']) && !empty($resultArray['data'])) {
                $res = isset($resultArray['data']['list']) ? $resultArray['data']['list'] : $resultArray['data'];
            }
            $result = [];

            if (!empty($res) && is_array($res)) {
                foreach ($res as $k => $v) {
                    $result[] = [
                        $idField => $v[$idField],
                        $showField => $v[$showField],
                    ];
                }
            }
            return $result;

        } else {
            if (isset($selectorConfig['extendConfig'])) {
                $extendConfig = $selectorConfig['extendConfig'];

                if (isset($extendConfig['parents'])) {
                    // return [];
                } else {
                    $sql = $extendConfig['source'] ?? '';
                    $filePath = $extendConfig['filePath'] ?? '';

                    if (!empty($sql)) {
                        $sql = $this->strReplace($sql);
                        $sql = preg_replace('/<\w+>.*<\/\w+>/', ' ', $sql); //删除全部标签
                        $params = [
                            'sql' => $sql,
                            'page' => 0,
                        ];
                        if (isset($extendConfig['externalDB'])) {
                            $params['database_id'] = $extendConfig['externalDB'];
                        }
                        $responseData = app('App\EofficeApp\Api\Services\ApiService')->testSql($params, 'execute');
                        if (is_array($responseData) && isset($responseData['list']) && is_array($responseData['list']) && count($responseData['list']) > 0) {
                            foreach ($responseData['list'] as $data) {
                                $result[] = [
                                    $idField => isset($data->$idField)?$data->$idField:'',
                                    $showField => isset($data->$showField)?$data->$showField:'',
                                ];
                            }
                        }
                    } else if (!empty($filePath)) {
                        $params = [
                            'sql' => $sql,
                            'page' => 0,
                        ];

                        $responseData = app('App\EofficeApp\Api\Services\ApiService')->getUrlData($params);
                        if (is_array($responseData) && isset($responseData['content'])) {
                            $list = [];
                            try {
                                $responseData = json_decode($responseData['content']);
                                if (isset($responseData->list) && is_array($responseData->list)) {
                                    $list = $responseData->list;
                                }
                            } catch (\Exception $e) {}
                            if (count($list) > 0) {
                                foreach ($list as $data) {
                                    $result[] = [
                                        $idField => isset($data->$idField)?$data->$idField:'',
                                        $showField => isset($data->$showField)?$data->$showField:'',
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
    private function parseExternalExcel($dbConfig, $primaryKey, $showColumn, $tableName)
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionConfig = [
            'driver' => 'pdo_' . $dbConfig->driver,
            'host' => $dbConfig->host,
            'port' => $dbConfig->port,
            'user' => $dbConfig->username,
            'password' => $dbConfig->password ? $dbConfig->password : '',
            'dbname' => $dbConfig->database,
            // 20210524-丁鹏，修改公共部分；1、删除这里的charset（dbal更新到3.0，pdo_sqlsrv的方式不再支持charset属性）；2、经过和刘宁确认，前端目前没有“数据源来自外部系统，选字段，解析值”的功能，所以此函数对应的“case 'outside': //外部数据库下拉框”分支应该没用到；3、基于“2”，改后没法测试，会跟测试单独沟通
            // 'charset' => 'utf8',
        ];

        try {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionConfig, $config);

            $query = "SELECT `$primaryKey`,`$showColumn` FROM $tableName WHERE 1=1 ";
            if ($result = $conn->fetchAll($query)) {
                return $result;
            }
        } catch (\Exception $e) {
            return '获取外部数据库数据失败';
        }
    }
    private function getShowValueByPrimaryKeyExcel($selectorConfig)
    {
        if (count($selectorConfig) > 3) {
            $services = isset($selectorConfig[0]) ? $selectorConfig[0] : '';
            $method = isset($selectorConfig[1]) ? $selectorConfig[1] : '';
            $primaryKey = isset($selectorConfig[2]) ? $selectorConfig[2] : '';
            $field = isset($selectorConfig[3]) ? $selectorConfig[3] : '';
            if (method_exists(app($services), $method)) {
                $userInfo = $this->current_user_info ? $this->current_user_info : own('');
                $result = app($services)->$method(null, $userInfo);
                return json_decode(json_encode($result), true);
            }
        } else {
            $table = $selectorConfig[0];
            $field1 = $selectorConfig[1];
            $field2 = $selectorConfig[2];
            $tableConfig = config('customfields.table');
            $result = DB::table($table)->select($field1, $field2);
            if (Schema::hasColumn($table, 'deleted_at')) {
                $result = $result->whereNull($table . '.deleted_at');
            }
            $result = $result->get();
            if (in_array($table, $tableConfig)) {
                foreach ($result as $key => $value) {
                    $result[$key]->$field2 = mulit_trans_dynamic("$table.$field2" . '.' . $value->$field2);
                }
                return json_decode(json_encode($result), true);
            } else {
                return json_decode(json_encode($result), true);
            }
        }
    }

    public function checkboxDirectiveExcel($field)
    {
        $fieldOption = json_decode($field->field_options, true);
        if (isset($fieldOption['datasource'])) {
            $dataSourceMap = $fieldOption['datasource'];
        } else {
            $dataSourceMap = '';
        }
        return $dataSourceMap;

    }

    public function areaDirectiveExcel($item)
    {
        $parseValueArray = [];
        $parseValueArray = $this->getShowValueByPrimaryKeyExcel($item);
        return $parseValueArray;
    }
}
