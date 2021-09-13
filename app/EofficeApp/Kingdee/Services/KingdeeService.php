<?php
namespace App\EofficeApp\Kingdee\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * K3集成 service
 */
class KingdeeService extends BaseService
{
    protected $loginUrl = '/K3Cloud/Kingdee.BOS.WebApi.ServicesStub.AuthService.ValidateUser.common.kdsvc';
    protected $tableSearchUrl = '/K3Cloud/Kingdee.BOS.WebApi.ServicesStub.DynamicFormService.ExecuteBillQuery.common.kdsvc';
    protected $saveUrl = '/K3Cloud/Kingdee.BOS.WebApi.ServicesStub.DynamicFormService.Save.common.kdsvc';
    public function __construct()
    {
        parent::__construct();
        $this->kingdeeK3AccountConfigRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3AccountConfigRepository';
        $this->kingdeeK3TableRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3TableRepository';
        $this->kingdeeK3TableFlowRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3TableFlowRepository';
        $this->kingdeeK3TableFlowFieldRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3TableFlowFieldRepository';
        $this->k3CloudDataParseService = 'App\EofficeApp\Kingdee\Services\K3CloudDataParseService';
        $this->k3CloudApiService = 'App\EofficeApp\Kingdee\Services\K3CloudApiService';
        $this->kingdeeK3LogRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3LogRepository';
        $this->kingdeeK3StaticDataRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3StaticDataRepository';
    }

    
    public function addK3Account($data)
    {
        if(empty($data['name']) || empty($data['api_host']) || empty($data['acct_id']) || empty($data['username']) || empty($data['password'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        if(empty($data['lcid'])){
            $data['lcid'] = '2052';// 默认中文
        }
        if(isset($data['default']) && $data['default'] == 1){
            app($this->kingdeeK3AccountConfigRepository)->updateAllNotDefalut();
        }
        $insertAccount = [
            'name' => $data['name'],
            'api_host' => $data['api_host'],
            'acct_id' => $data['acct_id'],
            'username' => $data['username'],
            'password' => $data['password'],
            'lcid' => $data['lcid'],
            'login_url' => empty($data['login_url']) ? $data['api_host'].$this->loginUrl : $data['login_url'],
            'table_search_url' => empty($data['table_search_url']) ? $data['api_host'].$this->tableSearchUrl : $data['table_search_url'],
            'save_url' => empty($data['save_url']) ? $data['api_host'].$this->saveUrl : $data['save_url'],
            'status' => 1, // 默认启用
            'default' => $data['default'] ?? 0 // 默认账套
        ];
        return app($this->kingdeeK3AccountConfigRepository)->addAccount($insertAccount);

    }

    /**
     * 获取K3账套列表
     */
    public function getK3AccountList($param)
    {
        $data['list'] = app($this->kingdeeK3AccountConfigRepository)->getAccountList();
        $param['return_type'] = 'count';
        $data['total'] = app($this->kingdeeK3AccountConfigRepository)->getAccountList($param);
        return $data;
    }


    /**
     * 获取单个K3账套详情
     */
    public function getK3AccountDetail($data)
    {
        if(empty($data['accountId'])){
            return ['code' => ['lack_params', 'kingdee']]; 
        }
        return app($this->kingdeeK3AccountConfigRepository)->getAccountDetail($data['accountId']);
    }

    /**
     * 删除单个K3账套详情
     */
    public function deleteK3Account($data)
    {
        if(empty($data['accountId'])){
            return ['code' => ['lack_params', 'kingdee']]; 
        }
        
        // 删除关联的k3单据
        $param['search'] = [
            'account_id' => [$data['accountId']]
        ];
        $tableList = app($this->kingdeeK3TableRepository)->getTableList($param);
        // 每个执行单据的删除操作
        // if(is_array($tableList) && count($tableList) < 1){
        //     // return true;
        // }else{
        //     foreach ($tableList as $table){
        //         $this->deleteK3Table(['kingdee_table_id' => $table['kingdee_table_id'] ?? '']);
        //     }
        // }

        // 账套底下有单据不允许删除
        if(is_array($tableList) && count($tableList) > 0){
            return ['code' => ['delete_account_deny', 'kingdee']];
        }
        
        // 删除本身
        app($this->kingdeeK3AccountConfigRepository)->deleteAccount($data['accountId']);
        return true;
    }
    /**
     * 更新单个K3账套详情
     */
    public function updateK3Account($data)
    {
        if(empty($data['kingdee_account_id']) || empty($data['name']) || empty($data['api_host']) || empty($data['acct_id']) || empty($data['username']) || empty($data['password'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        if(empty($data['lcid'])){
            $data['lcid'] = '2052';// 默认中文
        }
        if(isset($data['default']) && $data['default'] == 1){
            app($this->kingdeeK3AccountConfigRepository)->updateAllNotDefalut();
        }
        $updateAccount = [
            'name' => $data['name'],
            'api_host' => $data['api_host'],
            'acct_id' => $data['acct_id'],
            'username' => $data['username'],
            'password' => $data['password'],
            'lcid' => $data['lcid'],
            'login_url' => empty($data['login_url']) ? $data['api_host'].$this->loginUrl : $data['login_url'],
            'table_search_url' => empty($data['table_search_url']) ? $data['api_host'].$this->tableSearchUrl : $data['table_search_url'],
            'save_url' => empty($data['save_url']) ? $data['api_host'].$this->saveUrl : $data['save_url'],
            'default' => $data['default'] ?? 0 // 默认账套
        ];
        return app($this->kingdeeK3AccountConfigRepository)->updateAccount($data['kingdee_account_id'],$updateAccount);
    }

    /**
     * 新建k3cloud单据
     * 
     */
    public function addK3Table($data)
    {
        if(empty($data['name']) || empty($data['web_api_content']) || empty($data['account_id']) || empty($data['form_id'])){
            return ['code' => ['lack_params', 'kingdee']]; 
        }
        $insertData = [
            'name' => $data['name'],
            'account_id' => $data['account_id'],
            'form_id' => $data['form_id'],
            'web_api_content' => $data['web_api_content']
        ];
        return app($this->kingdeeK3TableRepository)->addTable($insertData);
    } 

    /**
     * 获取k3cloud单据列表
     * 
     */
    public function getK3TableList($param)
    {
        if(!empty($param['accountId'])){
            $param['search'] = ['account_id'=>[$param['accountId']]];
        }
        $data['list'] = app($this->kingdeeK3TableRepository)->getTableList($param);
        $param['return_type'] = 'count';
        $data['total'] = app($this->kingdeeK3TableRepository)->getTableList($param);
        return $data;
    } 

    /**
     * 获取k3cloud单据详情
     * 
     */
    public function getK3TableDetail($data)
    {
        if(empty($data['kingdee_table_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        
        return app($this->kingdeeK3TableRepository)->getTableDetail($data['kingdee_table_id']);
    } 

    /**
     * 更新k3cloud单据详情
     * 
     */
    public function updateK3TableDetail($data)
    {
        if(empty($data['kingdee_table_id']) || empty($data['name']) || empty($data['web_api_content']) || empty($data['account_id']) || empty($data['form_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $updateData = [
            'name' => $data['name'],
            'account_id' => $data['account_id'],
            'form_id' => $data['form_id'],
            'web_api_content' => $data['web_api_content']
        ];
        return app($this->kingdeeK3TableRepository)->updateTable($data['kingdee_table_id'],$updateData);
    } 

    /**
     * 根据单据id获取账套信息
     * 
     */
    public function getK3AccountByTable($data)
    {
        if(empty($data['kingdee_table_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        
        return app($this->kingdeeK3TableRepository)->getK3AccountByTableId($data['kingdee_table_id']);
    } 

    /**
     * 删除k3cloud单据
     * 
     */
    public function deleteK3Table($data)
    {
        if(empty($data['kingdee_table_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        
        // 删除该单据下的所有与流程的绑定关系
        // 获取单据下的关联id
        $resK3FlowList = app($this->kingdeeK3TableFlowRepository)->getK3FlowList(['search' => ['k3table_id' => ['=',$data['kingdee_table_id']]]]);
        if(is_array($resK3FlowList) && count($resK3FlowList) > 0){
            $k3FlowIds = [];
            foreach($resK3FlowList as $k3FlowDetail){
                $k3FlowIds[] = $k3FlowDetail['kingdee_table_flow_id'] ?? '';
            }
            // dd($k3FlowIds);
            // 删除该单据下的所有流程表单关联字段
            app($this->kingdeeK3TableFlowFieldRepository)->DeleteByIds($k3FlowIds);
        }
        app($this->kingdeeK3TableFlowRepository)->deleteK3FlowByTableId($data['kingdee_table_id']);
        
        // 删除该单据下的静态数据已弃用
        // app($this->kingdeeK3StaticDataRepository)->deleteStaticDataByTableId($data['kingdee_table_id']);

        // 删除该单据下的日志
        app($this->kingdeeK3LogRepository)->deleteLogByTableId($data['kingdee_table_id']);

        app($this->kingdeeK3TableRepository)->deleteTable($data['kingdee_table_id']);
        return true;
    }
    
    /**
     * 根据单据获取字段列表
     * 
     */
    public function getK3TableField($data)
    {
        if(empty($data['kingdee_table_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $res = $this->getK3TableDetail($data);
        if(empty($res['web_api_content'])){
            return '';
        }else{
            // dd($this->parseTableContent($res['web_api_content']));
            $content = $this->parseTableContent($res['web_api_content']);
            if(!empty($content['code'])){
                return $content;
            }
            return $this->parseContentToArray($content);
        }
    }





    /**
     * 解析k3webApi的content内容
     */
    public function parseTableContent($content,$postData = false)
    {
        if(!$content){
            return ['code' => ['model_parse_error', 'kingdee']];
        }
        // 解析请求体
        $str = mb_strstr($content,trans("kingdee.content_json_data",[],'zh-CN'));
        $str = mb_strstr($str,trans("kingdee.content_field_explan",[],'zh-CN'),true);
        $str = trim($str,trans("kingdee.content_json_data",[],'zh-CN'));
        $str = json_decode($str,true);
        if(empty($str['Model']) || !is_array($str['Model'])){
            return ['code' => ['model_parse_error', 'kingdee']]; 
        }
        if($postData){
            return $str;
        }
        // var_dump($str);
        // var_dump($str['Model']['FPAYBILLENTRY']);

        // 解析注释文本
        $str2 = mb_strstr($content,trans("kingdee.content_field_explan",[],'zh-CN'));
        $str2 = trim($str2,trans("kingdee.content_field_explan",[],'zh-CN'));
        // 去除必填项的干扰
        $str2 = str_replace(trans("kingdee.content_require",[],'zh-CN'), '', $str2);
        $str2 = str_replace(' ', '', $str2);
        $str2 = str_replace("\n", '|', $str2);
        $str2 = str_replace("\r\n", '|', $str2);

        $str2 = explode('|', $str2);
        unset($str2[0]);
        $parseArr = [];
        foreach($str2 as $value){
            $tempArr = explode('：', $value);// 使用中文的冒号
            $parseArr[trim($tempArr[1])] = trim($tempArr[0]);
        }
        // var_dump($parseArr);die;

        foreach($str['Model'] as $key => $value){
            //制作筛选后的字段列表
            // 支持两层嵌套解析且排除number类型
            if(is_array($value) && isset($value[0]) && is_array($value[0]) && count($value[0]) > 0){
                // 说明是明细
                foreach ($value[0] as $index => $data) {
                    // 明细数组的key拼上字段解释
                    // $parsedStr[$key][$index] = isset($parseArr[$index]) ? $parseArr[$index] : '';
                    $parsedStr[$key][$index] = ['name' => isset($parseArr[$index]) ? $parseArr[$index] : '','type' => $this->checkTableFieldType($data)];
                }
                if(!isset($parsedStr[$key]['oa_k3_entity_name'])){
                    $arr = explode('-',isset($parseArr[$key]) ? $parseArr[$key].'-'.$key : '');
                    // 此处根据系统多语言返回，暂时先返回中文
                    $parsedStr[$key]['oa_k3_entity_name'] = $arr[0] ?? '';
                }
            }else{
                // 表示是单据头
                // 默认1是文本也就是字符串，2是FNumber数组
                $parsedStr[$key] = ['name' => isset($parseArr[$key]) ? $parseArr[$key] : '','type' => $this->checkTableFieldType($value)];
                // $parsedStr[$key] = isset($parseArr[$key]) ? $parseArr[$key] : '';
            }
        }
        return $parsedStr;
    }

    public function checkTableFieldType($data)
    {
        // 由于外发过去的数字类型使用字符串也可以生效因此不做转化
        if(is_array($data)){
            if(empty($data)){
                // 格式是空对象
                $type = 4;
            }else{
                if(isset($data['FNumber']) || isset($data['FNUMBER'])){
                    $type = 2;
                }else if(isset($data['FName']) || isset($data['FNAME'])){
                    $type = 3;
                }else{
                    $type = 4;
                }
            }
        }else{
            $type = 1;
        }
        return $type;
    }

    // 将解析后的单据字段再次解析，返回前端展示所用的标准数组格式
    public function parseContentToArray($content)
    {
        if(empty($content) || !is_array($content) || count($content) <= 1){
            return '';
        }
        // $list = [''];
        foreach($content as $key => $value){
            // dd(current($value));
            if(is_array($value) && count($value) >=2 && is_array(current($value))){
                // 表示是明细列表
                // 解析明细的字段说明
                $keyArr = explode('-',$key);
                // $keyName = $keyArr[1] ?? '';
                $list[$keyArr[0]] = $value;
                // $list[$keyArr[0]][$keyArr[0].'-name'] = $keyName;
                continue;
            }
            $list['BASIC'][$key] = $value;
            // 根据多语言返回
            $list['BASIC']['oa_k3_entity_name'] = trans("kingdee.basic");
        }
        return $list;
    }

    /**
     * 新增k3单据与流程的关联
     * 
     */
    public function addK3TableFlow($data)
    {
        // 没有流程的话直接返回
        
        if(empty($data['flow_info']['kingdee_table_id']) || empty($data['flow_info']['flow_id']) || empty($data['flow_info']['description'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $insertData = [
            'k3table_id' => $data['flow_info']['kingdee_table_id'],
            'flow_id' => $data['flow_info']['flow_id'],
            'description' => $data['flow_info']['description'],
            'check' => $data['flow_info']['check'] ?? 0
        ];
        // 维护k3单据与流程关联表
        $resAddK3Flow = app($this->kingdeeK3TableFlowRepository)->addK3Flow($insertData);
        if(empty($resAddK3Flow['kingdee_table_flow_id'])){
            return ['code' => ['k3_flow_attach_exist', 'kingdee']];
        }
        // 字段关系维护
        $k3FlowId = $resAddK3Flow['kingdee_table_flow_id']; //k3与流程的关联id
        if(!empty($data['field'])){
            $insertField = [];
            foreach($data['field'] as $key => $value){
                $table_head_name = $value['oa_k3_entity_name'] ?? '';
                $table_head = $key;
                foreach($value as $fieldKey => $field){
                    if($fieldKey == 'oa_k3_entity_name' || (!(isset($field['available']) && $field['available'] < 0) && empty($field['bindKey']))){
                        continue;
                    }
                    $insertField[] = [
                        'k3_table_flow_id' => $k3FlowId,
                        'k3_name' => $field['name'],
                        'k3_key' => $fieldKey,
                        'oa_control_id' => empty($field['bindKey']) ? '' : $field['bindKey'],
                        'value_type' => $field['type'] ?? 1,
                        'available' => $field['available'] ?? 1,
                        'table_head' => $table_head,
                        'table_head_name' => $table_head_name,
                    ];
                }
            }
            if(!empty($insertField) && count($insertField) > 0){
                app($this->kingdeeK3TableFlowFieldRepository)->mutipInsert($insertField);
            }
        }

        return ['k3_table_flow_id' => $k3FlowId];

    }

    /**
     * 获取k3单据与流程的关联信息
     * 
     */
    public function getK3TableFlow($data)
    {
        // 判断k3_table_flow_id是否存在
        if(empty($data['k3_table_flow_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $resK3Flow = app($this->kingdeeK3TableFlowRepository)->getK3flow($data['k3_table_flow_id']);
        if(empty($resK3Flow['kingdee_table_flow_id']) || empty($resK3Flow['k3table_id']) || empty($resK3Flow['flow_id']) || empty($resK3Flow['description'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $flowInfo = [
            'kingdee_table_flow_id' => $resK3Flow['kingdee_table_flow_id'],
            'kingdee_table_id' => $resK3Flow['k3table_id'],
            'flow_id' => $resK3Flow['flow_id'],
            'description' => $resK3Flow['description'],
            'flow_name' => $resK3Flow['flow']['flow_name'] ?? '',
            'check' => $resK3Flow['check'] ?? 0
        ];
        $attachField = app($this->kingdeeK3TableFlowFieldRepository)->getFieldList(['k3_table_flow_id' => $resK3Flow['kingdee_table_flow_id']]);
        // 获取全部字段将已解析的覆盖上去
        $tableField = $this->getK3TableField(['kingdee_table_id' => $resK3Flow['k3table_id']]);
        if(!empty($tableField['code'])){
            return $tableField;
        }
        $returnData = [];
        $returnData['flow_info'] = $flowInfo;
        // 解析attachField
        $parseAttach = [];
        if(is_array($attachField) && count($attachField) > 0){
            foreach($attachField as $attach){
                if(!empty($attach['table_head']) && !empty($attach['k3_key'])){
                    $parseAttach[$attach['table_head'].'-'.$attach['k3_key']] = [
                        'k3_key' => $attach['k3_key'],
                        'k3_name' => $attach['k3_name'],
                        'oa_control_id' => $attach['oa_control_id'],
                        'value_type' => $attach['value_type'],
                        'available' => $attach['available']
                    ];
                }
            }
        }
        // dd($parseAttach);
        if(!is_array($tableField)){
            return ['code' => ['model_parse_error', 'kingdee']];
        }
        foreach($tableField as $key => $value){
            $returnData['bar_list'][$key] = $value['oa_k3_entity_name'] ?? '';
            $returnData['field_list'][$key] =[];
            foreach($value as $fieldName => $field){
                if($fieldName == 'oa_k3_entity_name'){
                    $returnData['field_list'][$key][$fieldName] = $field;
                    continue;
                }
                // 判断是否已有关联关系
                if(isset($parseAttach[$key.'-'.$fieldName])){
                    $returnData['field_list'][$key][$fieldName] = [
                        'name' => $parseAttach[$key.'-'.$fieldName]['k3_name'] ?? '',
                        'bindKey' => $parseAttach[$key.'-'.$fieldName]['oa_control_id'] ?? '',
                        'type' => $parseAttach[$key.'-'.$fieldName]['value_type'] ?? 1,
                        'available' => $parseAttach[$key.'-'.$fieldName]['available'] ?? 1,
                    ];
                }else{
                    $returnData['field_list'][$key][$fieldName] = [
                        'name' => $field['name'],
                        'bindKey' => '',
                        'type' => $field['type'],
                        'available' => 1,
                    ];
                }
            }
        }
        // dd($returnData);
        return $returnData;


    }

    // 更新k3单据与流程字段的关联信息
    public function updateK3TableFlow($data)
    {
        if(empty($data['flow_info']['kingdee_table_id']) || empty($data['flow_info']['flow_id']) || empty($data['flow_info']['description']) || empty($data['flow_info']['kingdee_table_flow_id'] || !isset($data['flow_info']['check']))){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $k3FlowId = $data['flow_info']['kingdee_table_flow_id']; //k3与流程的关联id
        $updateData = [
            'description' => $data['flow_info']['description'],
            'check' => $data['flow_info']['check']
        ];
        // 判断是否存在
        $checkK3Flow = app($this->kingdeeK3TableFlowRepository)->checkK3FlowExist($k3FlowId);
        if(!$checkK3Flow){
            return ['code' => ['k3_flow_attach_not_exist', 'kingdee']];
        }
        // 维护k3单据与流程关联表
        app($this->kingdeeK3TableFlowRepository)->updateK3Flow($k3FlowId,$updateData);
        // 字段关系维护
        
        if(!empty($data['field']) && is_array($data['field']) && count($data['field']) > 0){
            // 删除全部旧的然后执行插入
            app($this->kingdeeK3TableFlowFieldRepository)->multiDelete($k3FlowId);
            $insertField = [];
            foreach($data['field'] as $key => $value){
                $table_head_name = $value['oa_k3_entity_name'] ?? '';
                $table_head = $key;
                foreach($value as $fieldKey => $field){
                    // 排除oa_k3_entity_name,
                    if($fieldKey == 'oa_k3_entity_name' || (!(isset($field['available']) && $field['available'] < 0) && empty($field['bindKey']))){
                        continue;
                    }
                    $insertField[] = [
                        'k3_table_flow_id' => $k3FlowId,
                        'k3_name' => $field['name'],
                        'k3_key' => $fieldKey,
                        'oa_control_id' => empty($field['bindKey']) ? '' : $field['bindKey'],
                        'value_type' => $field['type'] ?? 1,
                        'available' => $field['available'] ?? 1,
                        'table_head' => $table_head,
                        'table_head_name' => $table_head_name,
                    ];
                }
            }
            if(!empty($insertField) && count($insertField) > 0){
                app($this->kingdeeK3TableFlowFieldRepository)->mutipInsert($insertField);
            }
        }
    }


    // 更新k3单据与流程字段的关联信息
    public function getK3TableFlowList($data)
    {
        $param = [
            'return_type' => 'count'
        ];
        if(isset($data['page']) && isset($data['limit'])){
            $param['page'] = $data['page'];
            $param['limit'] = $data['limit'];
        }
        if(!empty($data['table_id'])){
            $param['search'] = ['k3table_id' => ['=',$data['table_id']]];
        }
        $dataList['total'] = app($this->kingdeeK3TableFlowRepository)->getK3FlowList($param);
        $param['return_type'] = 'array';
        $res = app($this->kingdeeK3TableFlowRepository)->getK3FlowList($param);
        $parseData = [];
        if($res){
            foreach($res as $value){
                $parseData[] = [
                    'table_name' => $value['k3table']['name'] ?? '',
                    'flow_name' => $value['flow']['flow_name'] ?? '',
                    'flow_id' => $value['flow_id'] ?? '',
                    'table_id' => $value['k3table_id'] ?? '',
                    'table_flow_id' => $value['kingdee_table_flow_id'] ?? '',
                    'description' => $value['description'] ?? ''
                ];
            }
        }

        $dataList['list'] = $parseData;
        return $dataList;
    }

    // 删除k3与流程的关联信息
    public function deleteK3Flow($data)
    {
        if(empty($data['kingdee_table_flow_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $k3FlowId = $data['kingdee_table_flow_id'];
        // 删除关联记录
        app($this->kingdeeK3TableFlowRepository)->deleteK3Flow($k3FlowId);
        // 删除字段关联
        app($this->kingdeeK3TableFlowFieldRepository)->multiDelete($k3FlowId);

        // 其他相关内容的删除
    }

    // 流程外发接受处
    public function k3OutSend($param, $flowData)
    {
        $data = $flowData;
        // file_put_contents('D://kindee.txt',json_encode($flowData));die;
        // $data = json_decode(file_get_contents('D://kindee.txt'),true);
        // 获取k3table与flow的关联id,根据它查kingdee_table_id
        // $param['voucher_config']
        $param['kingdee_table_id'] = $param['voucher_config'] ?? 1;
        if(empty($param['kingdee_table_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $tableDetail = $this->getK3TableDetail($param);
        if(empty($tableDetail['web_api_content']) || empty($tableDetail['account_id']) || empty($data['flow_id']) || empty($tableDetail['form_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }else{
            $model = $this->parseTableContent($tableDetail['web_api_content'],true);
        }
        // 根据单据id与流程id查出关联id
        $tableFlowInfo = app($this->kingdeeK3TableFlowRepository)->getInfoByTableAndFlow($param['kingdee_table_id'],$data['flow_id']);
        if(empty($tableFlowInfo) && empty($tableFlowInfo['kingdee_table_flow_id'])){
            return ['code' => ['k3_flow_attach_not_exist', 'kingdee']];
        }
        // 获取发送体模板
        $parsedData = app($this->k3CloudDataParseService)->parseData($data,$model,$tableFlowInfo['kingdee_table_flow_id']);
        // 根据单据id获取账套信息
        $accountInfo = $this->getK3AccountDetail(['accountId' => $tableDetail['account_id']]);
        
        app($this->k3CloudApiService)->login($accountInfo);
        $k3return  = $this->sendToK3GenerateTable($parsedData,$accountInfo,$tableDetail['form_id']);
        // 记录日志
        $logContent = [
            'table_id' => $param['kingdee_table_id'],
            'flow_id' => $data['flow_id'] ?? 0,
            'status' => isset($k3return['error']) ? 0 : 1,
            'origin_data' => json_encode($data),
            'parsed_data' => json_encode($parsedData),
            'return_data' => json_encode($k3return),
            'run_name' => $data['run_name'] ?? '',
            'run_id' => $data['run_id'] ?? ''
        ];
        $this->addK3Log($logContent);
    }

    // 发送至k3生成单据方法
    public function sendToK3GenerateTable($parsedData,$accountInfo,$formId)
    {
        $url = $accountInfo['api_host'].$this->saveUrl;
        $sendData = [
            'formid' => $formId,
            'data'   => $parsedData
        ];
        $token = md5($accountInfo['api_host'].$accountInfo['acct_id'].$accountInfo['username']);

        $httpResult = $this->K3http($url,$token, json_encode($sendData),false);
        $httpResult = $httpResult ? json_decode($httpResult, true) : [];
        if(isset($httpResult[0][0]['Result']['ResponseStatus']['ErrorCode']) || (isset($httpResult['Result']['ResponseStatus']['IsSuccess']) && $httpResult['Result']['ResponseStatus']['IsSuccess'] == false)){
            // 返回错误
            return ['error' => $httpResult];
        }
        return $httpResult;
    }

    // 流程外发选择器数据源接口
    public function getK3TableSelectByFlow($data)
    {
        if(empty($data['flow_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $param = [
            'search' => ['flow_id' => ['=',$data['flow_id']]],
            'return_type' => 'count'
        ];
        $resFlowList['total'] = app($this->kingdeeK3TableFlowRepository)->getK3FlowList($param);
        $param['return_type'] = 'array';
        $res = app($this->kingdeeK3TableFlowRepository)->getK3FlowList($param);

        $parseData = [];
        if($res){
            foreach($res as $value){
                $parseData[] = [
                    'table_name' => $value['k3table']['name'] ?? '',
                    'flow_name' => $value['flow']['flow_name'] ?? '',
                    'flow_id' => $value['flow_id'] ?? '',
                    'table_id' => $value['k3table_id'] ?? '',
                    'kingdee_table_flow_id' => $value['kingdee_table_flow_id'] ?? '',
                    'description' => $value['description'] ?? ''
                ];
            }
        }
        $resFlowList['list'] = $parseData;
        return $resFlowList;
    }


    








    // 调用系统getHttps函数
    /**
     * @param token md5(api_host.acct_id.username)
     */
    public function K3http($url , $token , $data = [] , $isLogin = false)
    {
        if(empty($url) || empty($token)){
            return false;
        }
        $path = public_path().'/kingdee/k3cloud/'.$token;
        if(!file_exists($path) || !File::isWritable($path)) {
            mkdir($path, 0777, true);
        }
        $path = $path.'/CloudSession';
        if($isLogin){
            // 登录授权
            $header = array("Content-Type: multipart/form-data");
            $option = [[CURLOPT_COOKIEJAR,$path],[CURLOPT_HEADER,0]];
            return getHttps($url,$data,$header,$option);
        }else{
            $header = array("Content-Type: application/json");
            $option = [[CURLOPT_COOKIEFILE,$path],[CURLOPT_HEADER,0]];
            return getHttps($url,$data,$header,$option);
        }
    }


    /**
     * 仅用于测试校验发起接口环境，并不参与实际功能
     * @param string $url
     * @param string $data
     * @return json
     */
    // public function httpClient($url, $data=[],$cookie_jar,$isLogin) {
    //     try {
    //         // if (is_array($data)) {
    //         //     $data = http_build_query($data, null, '&');
    //         // }
    //         if(!$isLogin){
    //             $alldata = [
    //                 'url' => $url,
    //                 'data' => $data,
    //                 'cookie_jar' => $cookie_jar,
    //                 'isLogin' => $isLogin,
    //             ];
    //             // var_dump($alldata);die;
    //         }
            
    //         $ch = curl_init();
    //         curl_setopt($ch, CURLOPT_URL,$url);
    //         curl_setopt($ch, CURLOPT_HEADER, 0);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //         curl_setopt($ch, CURLOPT_POST, 1);
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            
    //         if($isLogin){
    //             curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
    //         }
    //         else{
    //             $header = array("Content-Type: application/json");
    //             if (!empty($header)) {
    //                 curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    //             }
    //             curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
    //         }
    //         $response = curl_exec($ch);
    //         curl_close($ch);
    //         return $response;
    //     } catch (Exception $e) {
    //         $this->httpErrorMsg = $e->getMessage();
    //         var_dump($this->httpErrorMsg);
    //         return false;
    //     }
    // }

    // 添加日志的函数
    public function addK3Log($data)
    {
        if(empty($data['table_id']) || empty($data['flow_id']) || empty($data['origin_data'])){
            return false;
        }
        $insertData = [
            'table_id' => $data['table_id'],
            'flow_id' => $data['flow_id'],
            'status' => $data['status'] ?? 0,
            'origin_data' => $data['origin_data'] ,
            'parsed_data' => $data['parsed_data'] ?? '',
            'return_data' => $data['return_data'] ?? '',
            'run_name' => $data['run_name'] ?? '',
            'run_id' => $data['run_id'] ?? ''
        ];
        app($this->kingdeeK3LogRepository)->addLog($insertData);

    }

    public function getK3LogList($param)
    {
        $param['return_type'] = 'count';
        $dataList['total'] = app($this->kingdeeK3LogRepository)->getLogList($param);
        $param['return_type'] = 'array';
        $res = app($this->kingdeeK3LogRepository)->getLogList($param);
        $parseData = [];
        if($res){
            foreach($res as $value){
                $parseData[] = [
                    'flow_name' => $value['flow']['flow_name'] ?? '',
                    'table_name' => $value['k3table']['name'] ?? '',
                    'kingdee_k3_log_id' => $value['kingdee_k3_log_id'] ?? '',
                    'status' => $value['status'] ?? '',
                    'run_name' => $value['run_name'] ?? '',
                    'created_at' => $value['created_at'] ?? '',
                    'flow_id' => $value['flow_id'] ?? '',
                    'run_id' => $value['run_id'] ?? ''
                ];
            }
        }

        $dataList['list'] = $parseData;
        return $dataList;
    }

    public function getK3LogDetail($param)
    {
        if(empty($param['kingdee_k3_log_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $res = app($this->kingdeeK3LogRepository)->getLogDetail($param['kingdee_k3_log_id']);
        $res['flow_name'] = $res['flow']['flow_name'] ?? '';
        $res['table_name'] = $res['k3table']['name'] ?? '';
        return $res;
    }


    public function getDefaultAccount()
    {
        $defaultAccount = app($this->kingdeeK3AccountConfigRepository)->getDefaultAccount();
        if(empty($defaultAccount)){
            return [];
        }
        return $defaultAccount;
    }



    
}
