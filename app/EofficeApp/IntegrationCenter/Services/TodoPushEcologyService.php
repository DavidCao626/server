<?php

namespace App\EofficeApp\IntegrationCenter\Services;

use App\EofficeApp\Base\BaseService;
use GuzzleHttp\Client;

class TodoPushEcologyService extends BaseService
{
    private $allInterfaceRoute = [];

    public function __construct()
    {
        parent::__construct();
        $this->iniData();
    }

    public function iniData()
    {
        $this->allInterfaceRoute = [
            'create_update_todo_flow' => '/rest/ofs/ReceiveRequestInfoByJson',
            'delete_flow' => '/rest/ofs/deleteUserRequestInfoByJson',

        ];
    }

    /**
     *  数据推送分类处理
     * @param $params
     * @param $ip
     * @param $syscode
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author [dosy]
     */
    public function pushDataToEcology($params, $ip, $syscode)
    {
        $dateTypeTitle = [
            '1' => '增加待办',
            '2' => '增加已办',
            '3' => '增加办结',
            '4' => '删除',
            '5' => '挂起',
            '6' => '停用',
            '7' => '启用',
        ];
        if (isset($params['dateType']) && !empty($params['dateType'])) {
            $createUpdateTodoFlow = ['1', '2', '3', '7'];
            $deleteTodoFlow = ['4', '5', '6'];
            if ($params['dateType'] == '2' || $params['dateType'] == '3') {
                $params['viewType'] = '1';
            }
            if (in_array($params['dateType'], $createUpdateTodoFlow)) { //推送待办
                $url = $ip . $this->allInterfaceRoute['create_update_todo_flow'];
                $pushData = $this->todoDataCreateAndUpdateConversion($params, $syscode);
            } elseif (in_array($params['dateType'], $deleteTodoFlow)) {  //删除待办
                $url = $ip . $this->allInterfaceRoute['delete_flow'];
                $pushData = $this->todoDataDeleteConversion($params, $syscode);
            } else {
                return;
            }
            $paramsJson = json_encode($pushData);
            $log = $this->repeatSend($paramsJson, $url);
            $log['push_data'] = $pushData??"";
            log_write_to_file($log, ['dirName'=>'todo-push','fileName'=>'eoToEc']);
            // 删除办理人本流程之前的数据

            if ($params['dateType'] == '1' && $params['processId'] > 1 && $params['viewType'] != 1) {
                $processId = $params['processId'] - 1;
                for($i = $processId; $i > 0; $i--) {
                    $params['processId'] = $i;
                    $pushData = $this->todoDataDeleteConversion($params, $syscode);
                    $paramsJson = json_encode($pushData);
                    $url = $ip . $this->allInterfaceRoute['delete_flow'];
                    $log = $this->repeatSend($paramsJson, $url);
                    $log['push_data'] = $pushData??"";
                    log_write_to_file($log,  ['dirName'=>'todo-push','fileName'=>'eoToEc']);
                }
            }
        }
        // $interfaceRoute = $this->allInterfaceRoute['create_flow'];
    }

    /**
     * 待办数据新增或变更处理
     * @param $params
     * @param $syscode
     * @param $internalIp
     * @return mixed
     * @author [dosy]
     */
    public function todoDataCreateAndUpdateConversion($params, $syscode)
    {
        $data['syscode'] = $syscode;
        //流程任务id
        $data['flowid'] = (string)($params['receiveUser'].'+'.$params['processId'].'+'.$params['runId']);
        //$data['flowid'] = (string)$params['runId'];
        //$data['flowid'] = $params['flowId'];
        //标题
        $data['requestname'] = (string)$this->getRequestName($params['runId']);
        //流程类型
        $data['workflowname'] = (string)$this->getFlowWordName($params['flowId']);
        //步骤名称（节点名称）
        $data['nodename'] = (string)$this->getNodeName($params['runId'],$params['processId']);
        //PC地址
        $data['pcurl'] = '/eoffice10/client/web/flow/handle/' . $params['flowId'] . ';run_id=' . $params['runId'].';flow_run_process_id='. $params['flowRunProcessId'];
        //APP地址
        $data['appurl'] = '/eoffice10/client/mobile/flow/handle/' . $params['flowId'] . ';run_id=' . $params['runId'].';flow_run_process_id='. $params['flowRunProcessId'];
        //流程处理状态
        switch ($params['dateType']) {
            case '1':
                $data['isremark'] = '0';
                break;
            case '2':
                $data['isremark'] = '2';
                break;
            case '3':
                $data['isremark'] = '4';
                break;
            case '7':
                $data['isremark'] = '0';
                break;
            default:
                $data['isremark'] = '0';
                break;
        }
        //流程查看状态
        $data['viewtype'] = (string)$params['viewType'] ?? "0";
        //创建人（原值）
        $data['creator'] = $this->getUserAccount($params['deliverUser']);
        //创建日期时间
        if($params['deliverTime']){
            $data['createdatetime'] = date('Y-m-d H:i:s', strtotime($params['deliverTime']));
        }else{
            $data['createdatetime'] = date('Y-m-d H:i:s', time());
        }
       // $data['createdatetime'] =  $params['deliverTime'];
        //接收人（原值）
        $data['receiver'] = $this->getUserAccount($params['receiveUser']);
        // $data['receiver'] = 'wld';
        $data['receivedatetime'] = date('Y-m-d H:i:s', time());
        //接收日期时间
        $data['receivets'] = (string)$this->getMsecTime();
        return $data;
    }

    public function getUserAccount($userId){
        $user = app("App\EofficeApp\User\Repositories\UserRepository")->getOneFieldInfo(['user_id'=>$userId]);
        $userAccount = $user->user_accounts ?? '';
        return $userAccount;
    }

    public function getFlowWordName($flowId){
        $flowTypeInfo = app("App\EofficeApp\Flow\Repositories\FlowTypeRepository")->getDetail($flowId, false, ['flow_name']);
        $flowWordName = $flowTypeInfo->flow_name ?? '';
        return $flowWordName;
    }

    public function getRequestName($runId){
        $flowRunInfo = app("App\EofficeApp\Flow\Repositories\FlowRunRepository")->getDetail($runId, false, ['run_name']);
        $requestname = $flowRunInfo->run_name ?? '';
        return $requestname;
    }

    public function getNodeName($runId,$processId){
        $flowRunProcessInfo = app("App\EofficeApp\Flow\Repositories\FlowRunProcessRepository")->getFlowRunProcessList(['fields'=> ['flow_process', 'free_process_step'],'search'=>['run_id'=>[$runId],'process_id'=>[$processId]],'returntype'=>'first']);
        $nodeId = $flowRunProcessInfo->flow_process ?? '';
        $freeProcessStep = $flowRunProcessInfo->free_process_step ?? '';
        $nodeInfo = app("App\EofficeApp\Flow\Repositories\FlowProcessRepository")->getDetail($nodeId, false, ['process_name']);
        $nodeName = $nodeInfo->process_name ?? '';
        if($freeProcessStep) {
            $freeProcessInfo = app("App\EofficeApp\Flow\Repositories\FlowProcessFreeStepRepository")->getFieldInfo('', ['process_name'],['run_id'=>[$runId],'node_id'=>[$nodeId],'step_id'=>[$freeProcessStep]]);
            $nodeName = $freeProcessInfo[0]['process_name'] ?? '';
        }
        return $nodeName;
    }

    /**
     * 待办删除
     * @param $params
     * @param $syscode
     * @return mixed
     * @author [dosy]
     */
    public function todoDataDeleteConversion($params, $syscode)
    {
        if (isset($params['receiveUser'])&&isset($params['processId'])&&isset($params['runId'])){
            $data['syscode'] = $syscode;
            $data['flowid'] = (string)($params['receiveUser'].'+'.$params['processId'].'+'.$params['runId']);
            $data['userid'] = $this->getUserAccount($params['receiveUser']);
            return $data;
        }else{
            return [];
        }


    }

    /**
     * 获取url
     * @param $flowId
     * @param $runId
     * @return string
     * @author [dosy]
     */
    public function getFlowUrl($flowId, $runId)
    {
        $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $ip = $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"]; //apache 的配置文件 httpd.conf 中的 ServerName 值。
        //http://58.247.82.74:18010/eoffice10/client/web/flow/handle/3;run_id=27
        $url = $httpType . $ip . '/eoffice10/client/web/flow/handle/' . $flowId . ';run_id=' . $runId;
        return $url;
    }


    /**
     * 三次请求
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
                'accept' => '*/*',
                'connection' => 'Keep-Alive',
                'Content-Type' => 'application/json',
            ];
            $jsonResult = $this->toPush($paramsJson, $url, $headers);
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
     * @param $headers
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
            log_write_to_file($e->getMessage(),['dirName'=>'todo-push','fileName'=>'eoToEc']);
            return false;
        }
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
}
