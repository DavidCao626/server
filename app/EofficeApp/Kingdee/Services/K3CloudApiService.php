<?php
namespace App\EofficeApp\Kingdee\Services;

use App\EofficeApp\Kingdee\Services\KingdeeService;
use Exception;

/**
 * K3集成 service
 */
class K3CloudApiService extends KingdeeService
{
    
    public function __construct()
    {
        parent::__construct();
        $this->kingdeeK3StaticDataRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3StaticDataRepository';
        $this->kingdeeK3CloudApiRepository = 'App\EofficeApp\Kingdee\Repositories\KingdeeK3CloudApiRepository';
        $this->flowFormService = 'App\EofficeApp\Flow\Services\FlowFormService';
    }

    // 访问k3登录器授权
    public function login($k3Account)
    {
        // $k3Account = $this->getK3AccountDetail(['accountId' => $AccountId]);
        if(!$k3Account || empty($k3Account['api_host']) || empty($k3Account['acct_id']) || empty($k3Account['username']) || empty($k3Account['password'])){
            return false;
        }
        $url = $k3Account['api_host'].$this->loginUrl;
        $loginData = [
            'acctID' => $k3Account['acct_id'],//帐套Id
            'username' => $k3Account['username'],//用户名
            'password' => $k3Account['password'],//密码
            'lcid' => $k3Account['lcid'] ?? '2052'//语言标识
        ];
        // var_dump($url);
        // var_dump($loginData);die;
        $token = md5($k3Account['api_host'].$k3Account['acct_id'].$k3Account['username']);
        
        return $this->K3http($url,$token,$loginData,true);
    }

    // 判断配置是否合法
    public function checkConfig($data)
    {
        $res = json_decode($this->login($data),true);
        if(isset($res['LoginResultType']) && $res['LoginResultType'] == 1){
            return true;
        }else{
            return ['code' => ['account_param_error', 'kingdee']];
        }
    }

    

    

    // 静态数据的函数

    // 新增静态数据
    public function addStaticData($data)
    {
        if(empty($data['name']) || empty($data['type']) || empty($data['data'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $insertData = [
            // 'table_id' => $data['table_id'],
            'name' => $data['name'],
            'type' => $data['type'],
            'data' => $data['type'] == 2 ? json_encode($data['data']) : $data['data']
        ];
        return app($this->kingdeeK3StaticDataRepository)->addStaticData($insertData);
    }

    // 获取静态数据
    public function getStaticData($data)
    {
        if(empty($data['kingdee_static_data_id']) ){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $data = app($this->kingdeeK3StaticDataRepository)->getStaticData($data['kingdee_static_data_id']);
        if($data && !empty($data['type']) && $data['type'] == 2 && !empty($data['data'])){
            $data['data'] = json_decode($data['data'],true);
        }
        return $data;
    }

    // 更新静态数据
    public function updateStaticData($data)
    {
        if(empty($data['name']) || empty($data['type']) || empty($data['data']) || empty($data['kingdee_static_data_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $updateData = [
            // 'table_id' => $data['table_id'],
            'name' => $data['name'],
            'type' => $data['type'],
            'data' => $data['type'] == 2 ? json_encode($data['data']) : $data['data']
        ];
        app($this->kingdeeK3StaticDataRepository)->updateStaticData($data['kingdee_static_data_id'],$updateData);
    }

    // 获取静态数据列表接口
    public function getStaticDataList($param)
    {
        $param['return_type'] = 'count';
        $dataList['total'] = app($this->kingdeeK3StaticDataRepository)->getStaticDataList($param);
        $param['return_type'] = 'array';
        $res = app($this->kingdeeK3StaticDataRepository)->getStaticDataList($param);
        $parseData = [];
        if($res){
            foreach($res as $value){
                $parseData[] = [
                    // 'table_name' => $value['table']['name'] ?? '',
                    'kingdee_static_data_id' => $value['kingdee_static_data_id'] ?? '',
                    'name' => $value['name'] ?? '',
                    'type' => $value['type'] ?? '',
                    'updated_at' => $value['updated_at'] ?? ''
                ];
            }
        }

        $dataList['list'] = $parseData;
        return $dataList;
    }

    // 删除静态数据
    public function deleteStaticData($data)
    {
        if(empty($data['kingdee_static_data_id']) ){
            return ['code' => ['lack_params', 'kingdee']];
        }
        return app($this->kingdeeK3StaticDataRepository)->deleteStaticData($data['kingdee_static_data_id']);
    }

    // cloudApi的函数
    // 新增cloudApi配置数据
    public function addCloudApiData($data)
    {
        if(empty($data['name']) || empty($data['form_id']) || empty($data['field']) || empty($data['data_type'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $insertData = [
            // 'account_id' => $data['account_id'],
            'name' => $data['name'],
            'form_id' => $data['form_id'],
            'field' => $data['field'] ,
            'filter' => $data['filter'] ?? '' ,
            'type' => 2,
            'data_type' => $data['data_type'] 
        ];
        return app($this->kingdeeK3CloudApiRepository)->addCloudApiData($insertData);
    }

    // 获取cloudApi配置数据
    public function getCloudApiData($data)
    {
        if(empty($data['kingdee_cloud_api_id']) ){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $data = app($this->kingdeeK3CloudApiRepository)->getCloudApiData($data['kingdee_cloud_api_id']);
        if($data && !empty($data['type']) && $data['type'] == 2 && !empty($data['data'])){
            $data['data'] = json_decode($data['data'],true);
        }
        return $data;
    }

    // 更新cloudApi配置数据
    public function updateCloudApiData($data)
    {
        // 此处需要考虑默认账套也可以被编辑，但不允许编辑账套
        if(empty($data['name']) || empty($data['form_id']) || empty($data['field']) || empty($data['data_type']) || empty($data['kingdee_cloud_api_id'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        $updateData = [
            // 'account_id' => $data['account_id'],
            'name' => $data['name'],
            'form_id' => $data['form_id'],
            'field' => $data['field'],
            'filter' => $data['filter'] ?? '' ,
            'data_type' => $data['data_type']
        ];
        app($this->kingdeeK3CloudApiRepository)->updateCloudApiData($data['kingdee_cloud_api_id'],$updateData);
    }

    // 获取cloudApi配置列表接口
    public function getCloudApiDataList($param)
    {
        $param['return_type'] = 'count';
        
        $dataList['total'] = app($this->kingdeeK3CloudApiRepository)->getCloudApiDataList($param);
        $param['return_type'] = 'array';
        $res = app($this->kingdeeK3CloudApiRepository)->getCloudApiDataList($param);
        $parseData = [];
        if($res){
            foreach($res as $value){
                $parseData[] = [
                    // 'account_name' => empty($value['account']['name']) ? trans("kingdee.default_account") : $value['account']['name'],
                    'kingdee_cloud_api_id' => $value['kingdee_cloud_api_id'] ?? '',
                    'name' => $value['name'] ?? '',
                    'form_id' => $value['form_id'] ?? '',
                    'field' => $value['field'] ?? '',
                    'filter' => $value['filter'] ?? '',
                    'data_type' => $value['data_type'] ?? '',
                    'updated_at' => $value['updated_at'] ?? ''
                ];
            }
        }

        $dataList['list'] = $parseData;
        return $dataList;
    }

    // 删除cloudApi配置
    public function deleteCloudApiData($data)
    {
        if(empty($data['kingdee_cloud_api_id']) ){
            return ['code' => ['lack_params', 'kingdee']];
        }
        return app($this->kingdeeK3CloudApiRepository)->deleteCloudApiData($data['kingdee_cloud_api_id']);
    }

    // 流程表单数据源获取接口列表
    public function getK3CloudApiList($data)
    {
        $cloudApiDataList = app($this->kingdeeK3CloudApiRepository)->getCloudApiDataList();
        if(empty($cloudApiDataList)){
            return [];
        }
        
        foreach($cloudApiDataList as $cloudApi){
            if(!empty($cloudApi['kingdee_cloud_api_id']) && !empty($cloudApi['name']) && !empty($cloudApi['field'])){
                $id = $cloudApi['kingdee_cloud_api_id'];
                $fields = explode(',',$cloudApi['field']);
                $parseApiList['cloudAPI_'.$id]['title'] = $cloudApi['name'];
                $parseApiList['cloudAPI_'.$id]['api'] = 'api/kingdee/k3/cloudapi/data?kingdee_cloud_api_id='.$id.'&account_id={account_id}&account_data_id={account_data_id}&form_id={form_id}';
                $parseApiList['cloudAPI_'.$id]['idField'] = 'id';
                $parseApiList['cloudAPI_'.$id]['titleField'] = 'field';
                $parseApiList['cloudAPI_'.$id]['custom_parent'][] = [
                    'title' => trans("kingdee.account_list"),
                    'key' => 'account_id',
                    'data_id' => 'account_data_id',
                    'parent' => '',
                ];
                // $parseApiList[$id]['custom_parent'][] = [
                //     'title' => trans("kingdee.account"),
                //     'key' => 'account_id',
                //     'parent' => '',
                // ];
            }
        }
        // 配置默认的动态数据源
        $parseApiList['smartAPI_'.'0'] = [
            'title' => trans("kingdee.smart_api"),
            'api' => 'api/kingdee/k3/smartApi?api_id={api_id}&account_id={account_id}&account_data_id={account_data_id}&form_id={form_id}',
            'idField' => 'id',
            'titleField' => 'field',
            'custom_parent' => [
                [
                    'title' => trans("kingdee.parent_control"),
                    'key' => 'api_id',
                    'parent' => '',
                ],
                [
                    'title' => trans("kingdee.account_list"),
                    'key' => 'account_id',
                    'data_id' => 'account_data_id',
                    'parent' => '',
                ]
            ]
        ];
        return $parseApiList;
    }
    // 流程表单数据源获取接口列表
    public function getK3StaticDataList($data)
    {
        // 拼接账套的基础接口
        $staticDataList['account_0'] = [
            'title' => trans("kingdee.account_list"),
            'api' => 'api/kingdee/k3/account/configlist',
            'idField' => 'kingdee_account_id',
            'titleField' => 'name'
        ];
        // 获取静态数据列表数据
        $param['search'] = ['type' => [2]];
        $staticData = $this->getStaticDataList($param);
        if(!empty($staticData['list']) && is_array($staticData['list']) && count($staticData['list']) > 0){
            foreach ($staticData['list'] as $static){
                $id = $static['kingdee_static_data_id'] ?? '';
                if(!$id){
                    continue;
                }
                $staticDataList['staticData_'.$id]['title'] = $static['name'];
                $staticDataList['staticData_'.$id]['api'] = 'api/kingdee/k3/staticData/data?kingdee_static_data_id='.$id;
                $staticDataList['staticData_'.$id]['idField'] = 'id';
                $staticDataList['staticData_'.$id]['titleField'] = 'field';
            }
        }
        return $staticDataList;

    }

    // 表单获取静态数据源数据
    public function getStaticDataSource($data)
    {
        $res = $this->getStaticData($data);
        // 再次简化解析数据
        if(isset($res['code']) || empty($res) || empty($res['data']) || !isset($res['type'])){
            // 下拉框也可以返回空字符串
            return '';
        }
        if($res['type'] == 2){
            // 下拉框需要解析
            $returnArray = [];
            foreach($res['data'] as $value){
                $returnArray[$value['key']] = $value['value'];
            }
            return $this->parseArrayToJson($returnArray);
        }
        if($res['type'] == 1){
            // 文本
            return $res['data'];
        }
        return '';
        
    }

    // 获取表单智能API的值
    public function getK3SmartApiData($data)
    {
        // 需要有api_id与账套的id，账套的控件data值，以及表单的id值
        // if(empty($data['api_id']) || empty($data['account_id']) || empty($data['account_data_id']) || empty($data['form_id'])){
        if(empty($data['api_id']) || empty($data['account_data_id']) || empty($data['form_id'])){
            return [];
        }
        // 此处传来的是父级的带有K3意义的key，所以要做一层解析后
        $cloudApiInfo = app($this->kingdeeK3CloudApiRepository)->getByFormId($data['api_id']);
        if(empty($cloudApiInfo['kingdee_cloud_api_id'])){
            return '';
        }
        $data = [
            'kingdee_cloud_api_id' => $cloudApiInfo['kingdee_cloud_api_id'],
            // 'kingdee_cloud_api_id' => $data['api_id'],// api的id值
            'account_id' => $data['account_id'] ?? '',
            'account_data_id' => $data['account_data_id'],
            'form_id' => $data['form_id'],
        ];
        return $this->getK3ApiData($data);
    }

    // 根据前端传入的DATA_控件值，校验该控件是下拉框
    public function checkAccountIsSelect($dataId,$fromId)
    {
        if(empty($dataId) || empty($fromId)){
            return false;
        }
        $res = app($this->flowFormService)->getFlowFormControlStructure(['search'=>['control_id'=>[$dataId],'form_id'=>[$fromId]]]);
        // $res = app($this->flowFormService)->getFlowFormControlStructure(['search'=>['control_id'=>['DATA_159'],'form_id'=>[93]]]);
        if(empty($res) || empty($res[0]['control_type'])){
            // 表示该控件不存在
            return false;
        }
        if($res[0]['control_type'] == 'select'){
            return true;
        }
        return false;
    }


    // 校验cloudApi配置信息
    // public function checkCloudApi($data)
    // {
    //     if(empty($data['name']) || empty($data['form_id']) || empty($data['field']) || empty($data['data_type']) || empty($data['type'])){
    //         return ['code' => ['lack_params', 'kingdee']];
    //     }
    //     $res = $this->requestK3CloudApiData($data);
    //     return $res;
    // }


    // 数据源请求数据接口
    function getK3ApiData($data)
    {
        // if(empty($data['kingdee_cloud_api_id']) || empty($data['account_id']) || empty($data['account_data_id']) || empty($data['form_id'])){
        if(empty($data['kingdee_cloud_api_id']) || empty($data['account_data_id']) || empty($data['form_id'])){
            return [];
            // return ['code' => ['lack_params', 'kingdee']];
        }
        // 判断父级的账套控件是否为下拉框
        $checkSelect = $this->checkAccountIsSelect($data['account_data_id'],$data['form_id']);
        if($checkSelect === false){
            return [];
        }
        $res = $this->getCloudApiData($data);
        // 此处必须要从父级控件中获取有account_id
        if(!empty($res['form_id']) && !empty($res['field'])){
            // 将账套id拼上去
            $res['account_id'] = $data['account_id'] ?? '';
            return $this->parseArrayToJson($this->requestK3CloudApiData($res));
        }
        return [];

    }

    // 将数组格式解析成id field格式返回
    public function parseArrayToJson($array,$id = 'id',$field = 'field')
    {
        $newArray = [];
        if(is_array($array) && count($array) > 0){
            if(!empty($array['code'])){
                return $array;
            }
            foreach($array as $key => $value){
                $newArray[] = [
                    $id => $key,
                    $field => $value,
                ];
            }
        }
        return $newArray;
    }


    // 请求k3cloudapi数据
    public function requestK3CloudApiData($data)
    {
        if(empty($data['form_id']) || empty($data['field']) || empty($data['type'])){
            return ['code' => ['lack_params', 'kingdee']];
        }
        if(empty($data['account_id'])){
            // 获取默认账套传入
            $defaultAccount = $this->getDefaultAccount();
            if(empty($defaultAccount) || empty($defaultAccount['kingdee_account_id'])){
                return ['code' => ['lack_of_default_account', 'kingdee']];
            }
            $data['account_id'] = $defaultAccount['kingdee_account_id'];
        }
        $accountInfo = app($this->kingdeeK3AccountConfigRepository)->getAccountDetail($data['account_id']);
        if(empty($accountInfo)){
            return [];
        }
        $loginInfo = $this->login($accountInfo);
        // dd($loginInfo);
        $responseData = $this->getList($data['form_id'],$data['field'],$accountInfo,$data['filter'] ?? '');
        return $responseData;

    }

    /** 统一获取数据列表接口
    * @param formId 表单控件id
    * @param fieldKey 需要返回的字段
    */
    public function getList($formId,$fieldKey,$k3Account,$filterString = ''){

        if(empty($formId) || empty($fieldKey) || empty($k3Account)){
            // 参数缺失
            return ['code' => ['lack_params', 'kingdee']];
        }
        $url = $k3Account['api_host'].$this->tableSearchUrl;
        $data = [
            'Data' => [
                "FormId" => $formId, 
                "FieldKeys" => $fieldKey, 
                "FilterString" => $filterString, 
            ]
        ];
        $token = md5($k3Account['api_host'].$k3Account['acct_id'].$k3Account['username']);

        // $path = public_path().'/kingdee/k3cloud/'.$token;
        // $path = $path.'/CloudSession';
        // $httpResult = $this->httpClient($url, json_encode($data),$path,false);
        $httpResult = $this->K3http($url,$token, json_encode($data),false);
        $httpResult = $httpResult ? json_decode($httpResult, true) : [];
        if(isset($httpResult[0][0]['Result']['ResponseStatus']['ErrorCode']) || !is_array($httpResult)){
            // 返回错误
            return ['code' => ['cloud_api_error', 'kingdee']];
        }
        
        return $this->parseToJsonString($httpResult);
    }

    public function parseToJsonString($httpResult){
        $data = [];
        // 解析成字符串对象返回
        if(is_array($httpResult)){
            foreach($httpResult as $value){
                if(!empty($value[0]) && !empty($value[1])){
                    $data[$value[0]] = $value[1];
                }
            }
            
        }
        return $data;
        
        // return json_encode($data);
    }
    
    // 只解析一个字段返回
    public function parseOneJsonString($httpResult,$key){
        $data = [];
        // 解析成字符串对象返回
        foreach($httpResult as $value){
            if(isset($value[$key])){
                $data[] = $value[$key];
            }
        }
        return json_encode($data);
    }

    // 用于下拉框数据源获取数据
    public function getSelectorDataList()
    {
        $dataList = [
            // 先添加默认的通用数据
            'account' => [
                'title' => trans("kingdee.account"),
                'api' => 'api/kingdee/k3/account/configlist',
                'idField' => 'kingdee_account_id',
                'titleField' => 'name'
            ],
            'table' => [
                'title' => trans("kingdee.k3_table"),
                'api' => 'api/kingdee/k3/table',
                'idField' => 'kingdee_table_id',
                'titleField' => 'name'
            ],
        ];

        // 合并静态数据源与webApi数据源
        // 获取静态数据源列表
        $resStaticData = app($this->kingdeeK3StaticDataRepository)->getStaticDataList();
        if($resStaticData){
            foreach($resStaticData as $value){
                $dataList[] = [
                    'title' => $value['table']['name'] ?? '',
                    'kingdee_static_data_id' => $value['kingdee_static_data_id'] ?? '',
                    'api' => $value['name'] ?? '',
                    'type' => $value['type'] ?? '',
                    'updated_at' => $value['updated_at'] ?? ''
                ];
            }
        }
        return $dataList;
    }


}
