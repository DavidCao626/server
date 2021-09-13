<?php

namespace App\EofficeApp\IntegrationCenter\Services;

use App\EofficeApp\Base\BaseService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;

class TodoPushService extends BaseService
{
    private $iniParam;
    private $todoPushEcologyService;
    private $todoPushRepository;

    public function __construct()
    {
        parent::__construct();
        $this->todoPushEcologyService = 'App\EofficeApp\IntegrationCenter\Services\TodoPushEcologyService';
        $this->todoPushRepository = 'App\EofficeApp\IntegrationCenter\Repositories\TodoPushRepository';

    }

    /**
     *  开始推送
     * @param $params
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function pushData($params)
    {
        log_write_to_file($params, ['dirName'=>'todo-push','fileName'=>'eoToDoPush']);
        $todoPushAllData = app($this->todoPushRepository)->getFieldInfo([]);
        foreach ($todoPushAllData as $todoPush) {
            if ($todoPush['is_push']) {
                if($todoPush['todo_code']==='e-cology'){
                    $ip = $todoPush['ip'];
                    $syscode = $todoPush['syscode'];
                    //$internalIp = $todoPush['internal_ip'];
                    $result = app($this->todoPushEcologyService)->pushDataToEcology($params,$ip,$syscode);
                }else{
                    if (isset($todoPush['ip'])&&!empty($todoPush['ip'])){
                        $url = $todoPush['ip'];
                        $param['syscode'] = $todoPush['syscode']??'';
                        $param['push_data'] = $this->paramTrans($params);
                        $paramsJson = json_encode($param); //{"receiveUser":"WV00000027","deliverTime":"2020-08-12 18:01:05","deliverUser":"admin","operationType":"add","operationId":"1","operationTitle":"主办人提交","dateType":"1","flowId":12,"runId":3428,"processId":2}
                        $log = $this->repeatSend($paramsJson, $url);
                        $log['push_data'] = $paramsJson??"";
                        log_write_to_file($log,  ['dirName'=>'todo-push','fileName'=>'eoToDoPush']);
                    }
                }
            }
        }
    }

    /**
     * 推送数据调整
     * @param $params
     * @return mixed
     * @author [dosy]
     */
    public function paramTrans($params){
        if (isset($params['receiveUser'])&&!empty($params['receiveUser'])){
            $params['receiveUserAccount'] = $this->getUserAccount($params['receiveUser']);
        }
        if (isset($params['deliverUser'])&&!empty($params['deliverUser'])){
            $params['deliverUserAccount'] = $this->getUserAccount($params['deliverUser']);
        }
        $params['receivets'] = (string)$this->getMsecTime();
        return $params;
    }

    /**
     * 获取用户账号
     * @param $userId
     * @return string
     * @author [dosy]
     */
    public function getUserAccount($userId){
        $user = app("App\EofficeApp\User\Repositories\UserRepository")->getOneFieldInfo(['user_id'=>$userId]);
        $userAccount = $user->user_accounts ?? '';
        return $userAccount;
    }

    /**
     * 获取毫秒级时间戳
     * @return float
     * @author [dosy]
     */
    public function getMsecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
    /**
     * 一般三次推送
     * @param $paramsJson
     * @param $url
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function repeatSend($paramsJson, $url)
    {
        for ($i = 0; $i < 3; $i++) {
            $headers = [
                'Content-Type' => 'application/json',
            ];
            $jsonResult = $this->toPush($paramsJson, $url,$headers);
            if ($jsonResult) {
                $result = json_decode($jsonResult, true);
                if (isset($result['operResult']) && $result['operResult'] == 1) {
                    $log['push_err'] = 0;
                    $log['push_result'] = $result;
                    return $log;
                } else {
                    $log['push_err'] = 1;
                    $log['push_result'] = $jsonResult;
                    return $log;
                }
            }
        }
        $log['push_err'] = 2;
        $log['push_result'] = '';
        return $log;
    }

    /**
     * 推送
     * @param $paramsJson
     * @param $url
     * @param array $headers
     * @return bool|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function toPush($paramsJson, $url, $headers = [])
    {
        // 发请求
        try {
            $client = new Client();
            if ($headers) {
                $guzzleResponse = $client->request('POST', $url, ['body' => $paramsJson, 'headers' => $headers]);
            } else {
                $guzzleResponse = $client->request('POST', $url, ['body' => $paramsJson]);
            }
            $status = $guzzleResponse->getStatusCode();
            if ($status != '200') {
                return false;
            } else {
                $content = $guzzleResponse->getBody()->getContents();
                return $content;
            }
        } catch (\Exception $e) {
            log_write_to_file($e->getMessage(),  ['dirName'=>'todo-push','fileName'=>'eoToDoPush']);
            return false;
        }
    }

    /**
     * 获取列表
     * @return mixed
     * @author [dosy]
     */
    public function todoPushSystemList(){
        $data = app($this->todoPushRepository)->getTodoPushList();
        return $data;
    }

    /**
     * 获取详情
     * @param $id
     * @return array
     * @author [dosy]
     */
    public function todoPushSystemDetail($id){
        if ($id===0){
            return [];
        }else{
            $data = app($this->todoPushRepository)->getFieldInfo(['id'=>$id]);
            if (!empty($data)){
                $data = $data[0];
            }
            return $data;
        }
    }

    /**
     * 保存设置
     * @param $param
     * @return mixed
     * @author [dosy]
     */
    public function saveTodoPushSystemSetting($param){
        if(!isset($param['name'])||empty($param['name'])){
            return ['code'=>['name_error', 'integrationCenter.todo_push']];
        }
        if(!isset($param['todo_code'])||empty($param['todo_code'])){
            return ['code'=>['todo_code_error', 'integrationCenter.todo_push']];
        }
        if(!isset($param['syscode'])||empty($param['syscode'])){
            return ['code'=>['syscode_error', 'integrationCenter.todo_push']];;
        }
        if(!isset($param['ip'])||empty($param['ip'])){
            return ['code'=>['ip_error', 'integrationCenter.todo_push']];
        }
        $paramData =[
            'is_push'=>$param['is_push']??0,
            'todo_code'=>$param['todo_code'],
            'syscode'=>$param['syscode'],
            'name'=>$param['name'],
            //'internal_ip'=>$param['internal_ip']??'',
            'ip'=>$param['ip'],
        ];
        if (isset($param['id'])&&!empty($param['id'])){
            $where = ['id'=>$param['id']];
//            if (isset($param['todo_code'])&&!empty($param['todo_push'])){
//                $where['todo_code'] = $param['todo_code'];
//
//            }
            $result = app($this->todoPushRepository)->updateData($paramData, $where);
        }else{
            $result = app($this->todoPushRepository)->insertData($paramData);
        }
        $this->getPushStatus();
        return $result;
    }

    /**
     * 删除推送
     * @param $id
     * @author [dosy]
     */
    public function deleteTodoPushSystem($id){
        $where = [
            'id' => $id
        ];
        $check = app($this->todoPushRepository)->getFieldInfo($where);
        if ($check&&empty($check['todo_code']) ){
            app($this->todoPushRepository)->deleteById($id);
            $this->getPushStatus();
        }
    }
    /**
     * 整体判断推送开启状态
     */
    public function getPushStatus(){
        $data = app($this->todoPushRepository)->getUseingTodoPushList();
        $status = 0;
        if ($data) {
            $status = 1;
        }
        Redis::set('todu_push_status', $status);
        return $status;
    }
    /**
     * 添加内置推送对象
     * @param $todoCode
     * @param int $iniIsPush
     * @author [dosy]
     */
    public function addTodoSystem($todoCode,$name, $iniIsPush = 0)
    {
        if (is_string($todoCode) && is_numeric($iniIsPush)) {
            $where = [
                'todo_code' => $todoCode
            ];
            $check = app($this->todoPushRepository)->getFieldInfo($where);
            if (!$check) {
                $data = [
                    'todo_code' => $todoCode,
                    'is_push' => $iniIsPush,
                    'name' => $name,
                ];
                app($this->todoPushRepository)->insertData($data);
            }
        }
    }

    /**
     * 测试接口
     * @param $param
     * @author [dosy]
     */
    public function todoPushTest($param){
        log_write_to_file($param, ['dirName'=>'todo-push','fileName'=>'todoPushTest']);
    }
}
