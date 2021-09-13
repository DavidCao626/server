<?php
namespace App\EofficeApp\Kingdee\Services;

use App\EofficeApp\Base\BaseService;
use Exception;

/**
 * K3集成 service
 */
class K3CloudDataParseService extends BaseService
{

    public function __construct()
    {
        parent::__construct();
        $this->kingdeeK3TableFlowRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3TableFlowRepository';
        $this->kingdeeK3TableFlowFieldRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3TableFlowFieldRepository';
    }

    // 解析数据返回已解析的数据体
    /**
     * @param data 外发数据体
     */
    // public function parseData($k3TableFLowId,$data)
    public function parseData($data,$model,$k3TableFLowId)
    {
        $tableFlowInfo = app($this->kingdeeK3TableFlowRepository)->getK3flow($k3TableFLowId);
        if(empty($k3TableFLowId) || empty($tableFlowInfo)){
            // 缺少参数
            return false;
        }
        // 添加一些单据头的配置信息
        if(isset($tableFlowInfo['check']) && $tableFlowInfo['check'] == 1 && isset($model['IsAutoSubmitAndAudit'])){
            $model['IsAutoSubmitAndAudit'] = 'true';
        }

        // 获取字段的值类型数据
        $fieldList = app($this->kingdeeK3TableFlowFieldRepository)->getFieldList(['k3_table_flow_id' => $k3TableFLowId]);
        if(empty($fieldList) || !is_array($fieldList) || count($fieldList) < 1){
            // 字段未做关联处理
            return false;
        }
        $orginData = $model;
        // model 是模板数据
        $model = $model['Model'] ?? '';
        if(!$model){
            // 模型数据错误
            return '';
        }
        // 解析字段内容
        $parseField = [];
        foreach ($fieldList as $field){
            // $parseField[$field['table_head']. '-' .$field['k3_key']] = [
            //     'oa_control_id' => $field['oa_control_id'],
            //     'value_type' => $field['value_type']
            // ];
            if(empty($field['table_head']) || empty($field['k3_key']) || ((!(isset($field['available']) && $field['available'] < 0)) && empty($field['oa_control_id'])) || !isset($field['value_type'])){
                continue;
            }
            if($field['table_head'] == 'BASIC'){
                // 表示是基础信息的关联
                // 判断该字段是否传递
                if(isset($field['available']) && $field['available'] < 0 && isset($model[$field['k3_key']])){
                    unset($model[$field['k3_key']]);
                }
                // 那么在表单中也是基础信息，就不去明细下查找了
                if(!empty($data[$field['oa_control_id']]) && isset($model[$field['k3_key']])){
                    // 如果有关联表单控件且外发过来的值不为空则替换
                    if($field['value_type'] == 1){
                        $model[$field['k3_key']] = $data[$field['oa_control_id']];
                        continue;
                    }
                    if($field['value_type'] == 3){
                        $model[$field['k3_key']] = $this->parseValuetype($data[$field['oa_control_id'].'_TEXT'],$field['value_type']);
                        continue;
                    }
                    $model[$field['k3_key']] = $this->parseValuetype($data[$field['oa_control_id']],$field['value_type'],$model[$field['k3_key']]);
                }
            }else{
                // 表示是明细部分的关联
                // 判断该字段是否传递
                if(isset($field['available']) && $field['available'] < 0 && isset($model[$field['table_head']][0][$field['k3_key']])){
                    unset($model[$field['table_head']][0][$field['k3_key']]);
                }
                // 获取到父级明细的控件id
                $entityName = substr($field['oa_control_id'],0,strrpos($field['oa_control_id'],'_'));
                if(!empty($data[$entityName]) && !empty($data[$entityName]['id'])){
                    $entityCount = count($data[$entityName]['id']);
                    // 遍历该明细列表替换赋值
                    for ($i = 0 ;$i < $entityCount; $i++) {
                        // 此处$model[$field['table_head']][$i][$field['k3_key']]中的$i必须改成0否则第二行明细是默认是不存在的。
                        if(!isset($model[$field['table_head']][0][$field['k3_key']]) || !isset($data[$entityName][$field['oa_control_id']][$i])){
                            // 判断模板中是否有这个值
                            continue;
                        }
                        if($field['value_type'] == 1){
                            $model[$field['table_head']][$i][$field['k3_key']] = $data[$entityName][$field['oa_control_id']][$i];
                            continue;
                        }
                        if($field['value_type'] == 3){
                            //file_put_contents('D://res.txt',json_encode($data[$entityName])."\r\n",FILE_APPEND);
                            //file_put_contents('D://res.txt',$data[$entityName][$field['oa_control_id'].'_TEXT'][$i],FILE_APPEND);
                            $model[$field['table_head']][$i][$field['k3_key']] = $this->parseValuetype($data[$entityName][$field['oa_control_id'].'_TEXT'][$i],$field['value_type']);
                            continue;
                        }
                        $model[$field['table_head']][$i][$field['k3_key']] = $this->parseValuetype($data[$entityName][$field['oa_control_id']][$i],$field['value_type'],$model[$field['table_head']][0][$field['k3_key']]);
                    }
                }
            }
        }
        // dd($model);
        $orginData['Model'] = $model;
        return $orginData;

    }

    public function parseValuetype($data,$valueType,$model = [])
    {
        switch ($valueType)
        {
            case 1:
                // 文本
                return $data;
                break;
            // case 2:
            //     // 数值
            //     return $this->praseNumber($data);
            //     break;
            case 2:
                // FNumber数组
                return $this->praseToFNumberArray($data);
                break;
            case 3:
                // FName数组
                return $this->praseToFNameArray($data);
                break;
            case 4:
                // 自适应
                if(empty($model)){
                    // 空对象的处理
                    return new \StdClass();
                    // return json_encode([], JSON_FORCE_OBJECT);
                }
                return $this->praseToAutoArray($model,$data);
                break;
            default:
                break;
        }
    }

    /** 解析为FNumber格式返回
    *
    */
    public function praseToFNumberArray($num)
    {
        return ['FNumber' => $num];
    }

    /** 解析为FName格式返回
    *
    */
    public function praseToFNameArray($name)
    {
        return ['FName' => $name];
    }
    /** 解析为自定义格式返回
    *
    */
    public function praseToAutoArray(Array $model,$value)
    {
        return [array_keys($model)[0] => $value];
    }

    /** 将数据解析到保留2位小数，四舍五入
    * @param num 数据
    * @param figure 保留位数
    * @param rounding 是否四舍五入
    */
    public function praseNumber($num,$figure = 2,$rounding = false)
    {
        if($rounding){
            return round($num,$figure);
        }else{
            return sprintf("%.2f",substr(sprintf("%.3f", $num), 0, -2));
        }
    }




}
