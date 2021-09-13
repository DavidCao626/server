<?php

namespace App\EofficeApp\IntegrationCenter\Services;

use App\EofficeApp\Base\BaseService;
use GuzzleHttp\Client;

/**
 * 集成中心-腾讯视频会议接口service
 * 功能：
 * 1、对内，处理接口鉴权、腾讯视频会议接口封装
 * 2、对外，被 VideoconferenceService 调用，实现创建视频会议等功能
 * 备注：
 * 1、前端的配置的保存，不在这个service里面处理，在 ThirdPartyInterfaceService 里面处理
 *
 * @author: Dingpeng
 *
 * @since：2020-04-12
 */
class TencentCloudVideoconferenceService extends BaseService
{
    // 所有API请求地址，前缀
    protected $_serverHost = 'https://api.meeting.qq.com';
    // 接口[创建会议]，请求URI
    protected $_createUri = '/v1/meetings';

    protected $_modifyUri = '/v1/meetings/{id}';

    protected $_cancelUri = '/v1/meetings/{id}/cancel';

    // 接口需要的固定值参数:secret_id
    private $secret_id;
    // 接口需要的固定值参数:secret_key
    private $secret_key;
    // 接口需要的固定值参数:app_id
    private $app_id;
    // 接口需要的固定值参数:sdk_id (非必填-20200611)
    private $sdk_id;

    public function __construct($interfaceDetail = []) {
        parent::__construct();
        $this->thirdPartyVideoconferenceTencentCloudRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyVideoconferenceTencentCloudRepository';
        $this->thirdPartyVideoconferenceRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyVideoconferenceRepository';

        // 传了接口配置详情
        if(!empty($interfaceDetail)) {
            // 解析固定参数
            $configInfo = $interfaceDetail['has_one_tencent_cloud'] ?? [];
            $this->secret_id = trim($configInfo['secret_id']) ?? '';
            $this->secret_key = trim($configInfo['secret_key']) ?? '';
            $this->app_id = trim($configInfo['app_id']) ?? '';
            $this->sdk_id = trim($configInfo['sdk_id']) ?? '';
        }

    }

    /**
     * 创建视频会议
     * @param  [array] $config           [接口相关配置]
     * @param  [array] $conferenceParams [会议参数]
     *                                   [meeting_name:会议名称]
     *                                   [meeting_begin_time:]
     *                                   [meeting_end_time:]
     *                                   [meeting_apply_user:创建者id]
     * @return [array]                   [description]
     */
    public function createConference($conferenceParams) {
        $result = [];
        // 取配置的参数
        $secretId = $this->secret_id;
        $secretKey = $this->secret_key;
        $appId = $this->app_id;
        $sdkId = $this->sdk_id;
        $timestamp = time();
        if(!$secretId || !$secretKey || !$appId) {
            // 接口参数缺失，视频会议创建失败，请到集成中心配置相关接口
            $result = ['code' => ['video_conferencing_create_failed_api_parameter', 'integrationCenter']];
            return $result;
        } else {
            // 构建参数
            // X-TC-Key 对应 SecretId |必填|参与签名计算|
            $X_TC_Key = $secretId;
            // X-TC-Timestamp 单位为秒|必填|参与签名计算|
            $X_TC_Timestamp = $timestamp;
            // X-TC-Nonce 随机正整数|必填|参与签名计算|
            $X_TC_Nonce = rand(1, 10000);
            // AppId 对应 App ID|必填|
            $AppId = $appId;
            // X-TC-Signature 放置由下面的签名方法产生的签名|必填|
            $X_TC_Signature = "";
            // ！1-串联 Header 参数
            $headerString = sprintf("X-TC-Key=%s&X-TC-Nonce=%s&X-TC-Timestamp=%s",$X_TC_Key,$X_TC_Nonce,$X_TC_Timestamp);

            // 会议需要的参数
            $startTime = $conferenceParams['meeting_begin_time'] ?? '';
            $endTime = $conferenceParams['meeting_end_time'] ?? '';
            if(empty($startTime)) {
                // 时间参数必填
                $result = ['code' => ['conference_create_failed_needed_start_time', 'integrationCenter']];
                return $result;
            }
            if(empty($endTime)) {
                // 时间参数必填
                $result = ['code' => ['conference_create_failed_needed_end_time', 'integrationCenter']];
                return $result;
            }
            // 转成时间戳
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            // 会议主题[必填]
            $subject = $conferenceParams['meeting_name'] ?? '';
            if(empty($subject)) {
                $result = ['code' => ['conference_create_failed_needed_subject', 'integrationCenter']];
                return $result;
            } else {
                $subject = str_replace('"', '', $subject);
            }
            // meeting_apply_user:创建者id
            $creator = $conferenceParams['meeting_apply_user'] ?? 'tester';
            $params = [
                "userid" => $creator,
                "instanceid" => 1,
                "subject" => $subject,
                "type" => 0,
                "start_time" => (string)$startTime,
                "end_time" => (string)$endTime,
                "settings" => [
                    "mute_enable_join" =>True,
                    "allow_unmute_self" =>True,
                    "mute_all" => False,
                    "host_video" =>True,
                    "participant_video" => False,
                    "enable_record" => False,
                    "play_ivr_on_leave" => False,
                    "play_ivr_on_join" => False,
                    "live_url" => False
                  ]
            ];
            $paramsJson = json_encode($params, JSON_PRETTY_PRINT);

            // ！2-组签名串
            $stringToSign = 'POST' . "\n" .
                           $headerString . "\n" .
                           $this->_createUri . "\n" .
                           $paramsJson;
            // ！3-计算签名（这样生成的已经是16位的了）
            $hmacSha256 = hash_hmac('sha256', $stringToSign, $secretKey);
            $req_signature = base64_encode($hmacSha256);
            // 构建header
            $guzzleHeader = [
                'AppId' => $AppId,
                'SdkId' => $sdkId,
                'Content-Type' => "application/json",
                'X-TC-Key' =>   $X_TC_Key,
                'X-TC-Nonce' => $X_TC_Nonce,
                'X-TC-Signature' => $req_signature,
                'X-TC-Timestamp' => $X_TC_Timestamp,
            ];
            // 拼接url
            $url = $this->_serverHost.$this->_createUri;
            // 发请求
            try {
                $client = new Client();
                $guzzleResponse = $client->request('POST', $url, ['body' => $paramsJson, 'headers' => $guzzleHeader]);
                $status = $guzzleResponse->getStatusCode();
            } catch (\Exception $e) {
                if ($e->hasResponse()) {
                    $errorResponse = $e->getResponse();
                    $status = $errorResponse->getStatusCode();
                    $errorMessage = $errorResponse->getBody()->getContents();
                    // 解析$errorMessage
                    $errorMessage = json_decode($errorMessage, true);
                    $errorInfo = $errorMessage['error_info'] ?? [];
                    $message = $errorInfo['message'] ?? '';
                }
            }
            if($status != '200') {
                return [
                    'code' => ['0x010005', 'integrationCenter'], // 视频会议创建失败
                    'dynamic' => trans("integrationCenter.video_conferencing_create_failed_interface_error_message", ['message' => $message]), // 视频会议创建失败，接口错误信息：$message。
                ];
            }
            $result = $guzzleResponse->getBody()->getContents();
            // 处理错误
            $resultParse = json_decode($result,true);
            if(isset($resultParse['error_info'])) {
                $errorInfo = $resultParse['error_info'];
                // $errorCode = $errorInfo['error_code'] ?? '';
                $message = $errorInfo['message'] ?? '';
                return [
                    'code' => ['0x010005', 'integrationCenter'], // 视频会议创建失败
                    'dynamic' => trans("integrationCenter.video_conferencing_create_failed_interface_error_message", ['message' => $message]), // 视频会议创建失败，接口错误信息：$message。
                ];
            }
        }
        return $result;
    }

    /**
     * 编辑视频会议
     * @param  [array] $config           [接口相关配置]
     * @param  [array] $conferenceParams [会议参数]
     *                                   [meeting_id:会议id]
     *                                   [meeting_name:会议名称]
     *                                   [meeting_begin_time:]
     *                                   [meeting_end_time:]
     *                                   [meeting_apply_user:创建者id]
     * @return [array]                   [description]
     */
    public function modifyConference($conferenceParams) {
        // {
        //   "meeting_number": 1,
        //   "meeting_info_list": [
        //   {
        //     "meeting_id": "7567454748865986568",
        //     "meeting_code": "040468657"
        //   }
        //   ]
        // }

        $result = [];
        // 取配置的参数
        $secretId = $this->secret_id;
        $secretKey = $this->secret_key;
        $appId = $this->app_id;
        $sdkId = $this->sdk_id;
        $timestamp = time();
        if(!$secretId || !$secretKey || !$appId) {
            // 接口参数缺失，视频会议创建失败，请到集成中心配置相关接口
            $result = ['code' => ['video_conferencing_modify_failed_api_parameter', 'integrationCenter']];
            return $result;
        } else {
            // 构建参数
            // X-TC-Key 对应 SecretId |必填|参与签名计算|
            $X_TC_Key = $secretId;
            // X-TC-Timestamp 单位为秒|必填|参与签名计算|
            $X_TC_Timestamp = $timestamp;
            // X-TC-Nonce 随机正整数|必填|参与签名计算|
            $X_TC_Nonce = rand(1, 10000);
            // AppId 对应 App ID|必填|
            $AppId = $appId;
            // X-TC-Signature 放置由下面的签名方法产生的签名|必填|
            $X_TC_Signature = "";
            // ！1-串联 Header 参数
            $headerString = sprintf("X-TC-Key=%s&X-TC-Nonce=%s&X-TC-Timestamp=%s",$X_TC_Key,$X_TC_Nonce,$X_TC_Timestamp);

            // 会议需要的参数
            // 会议ID[必填]
            $meetingId = $conferenceParams['meeting_id'] ?? '';
            if(empty($meetingId)) {
                // 视频会议编辑失败，未获取到会议ID
                $result = ['code' => ['conference_modify_failed_needed_meeting_id', 'integrationCenter']];
                return $result;
            }
            // 替换uri中的变量
            $this->_modifyUri = str_replace('{id}', $meetingId, $this->_modifyUri);
            $startTime = $conferenceParams['meeting_begin_time'] ?? '';
            $endTime = $conferenceParams['meeting_end_time'] ?? '';
            if(empty($startTime)) {
                // 时间参数必填
                $result = ['code' => ['conference_modify_failed_needed_start_time', 'integrationCenter']];
                return $result;
            }
            if(empty($endTime)) {
                // 时间参数必填
                $result = ['code' => ['conference_modify_failed_needed_end_time', 'integrationCenter']];
                return $result;
            }
            // 转成时间戳
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            // 会议主题[必填]
            $subject = $conferenceParams['meeting_name'] ?? '';
            if(empty($subject)) {
                $result = ['code' => ['conference_modify_failed_needed_subject', 'integrationCenter']];
                return $result;
            } else {
                $subject = str_replace('"', '', $subject);
            }
            $creator = $conferenceParams['meeting_apply_user'] ?? 'tester';
            $params = [
                "meetingId" => $meetingId,
                "userid" => $creator,
                "instanceid" => 1,
                "subject" => $subject,
                "start_time" => (string)$startTime,
                "end_time" => (string)$endTime,
                // "settings" => [
                //     "mute_enable_join" =>True,
                //     "allow_unmute_self" =>False,
                //     "mute_all" => False,
                //     "host_video" =>True,
                //     "participant_video" => False,
                //     "enable_record" => False,
                //     "play_ivr_on_leave" => False,
                //     "play_ivr_on_join" => False,
                //     "live_url" => False
                //   ]
            ];
            $paramsJson = json_encode($params, JSON_PRETTY_PRINT);

            // ！2-组签名串
            $stringToSign = 'PUT' . "\n" .
                           $headerString . "\n" .
                           $this->_modifyUri . "\n" .
                           $paramsJson;

            // ！3-计算签名（这样生成的已经是16位的了）
            $hmacSha256 = hash_hmac('sha256', $stringToSign, $secretKey);
            $req_signature = base64_encode($hmacSha256);
            // 构建header
            $guzzleHeader = [
                'AppId' => $AppId,
                'SdkId' => $sdkId,
                'Content-Type' => "application/json",
                'X-TC-Key' =>   $X_TC_Key,
                'X-TC-Nonce' => $X_TC_Nonce,
                'X-TC-Signature' => $req_signature,
                'X-TC-Timestamp' => $X_TC_Timestamp,
            ];
            // 拼接url
            $url = $this->_serverHost.$this->_modifyUri;
            // 发请求 PUT
            try {
                $client = new Client();
                $guzzleResponse = $client->request('PUT', $url, ['body' => $paramsJson, 'headers' => $guzzleHeader]);
                $status = $guzzleResponse->getStatusCode();
            } catch (\Exception $e) {
                if ($e->hasResponse()) {
                    $errorResponse = $e->getResponse();
                    $status = $errorResponse->getStatusCode();
                    $errorMessage = $errorResponse->getBody()->getContents();
                    // 解析$errorMessage
                    $errorMessage = json_decode($errorMessage, true);
                    $errorInfo = $errorMessage['error_info'] ?? [];
                    $message = $errorInfo['message'] ?? '';
                }
            }
            if($status != '200') {
                return [
                    'code' => ['0x010006', 'integrationCenter'], // 视频会议编辑失败
                    'dynamic' => trans("integrationCenter.video_conferencing_modify_failed_interface_error_message", ['message' => $message]), // 视频会议编辑失败，接口错误信息：$message。
                ];
            }
            $result = $guzzleResponse->getBody()->getContents();
            // 处理错误
            $resultParse = json_decode($result,true);
            if(isset($resultParse['error_info'])) {
                $errorInfo = $resultParse['error_info'];
                $message = $errorInfo['message'] ?? '';
                return [
                    'code' => ['0x010006', 'integrationCenter'], // 视频会议编辑失败
                    'dynamic' => trans("integrationCenter.video_conferencing_modify_failed_interface_error_message", ['message' => $message]), // 视频会议编辑失败，接口错误信息：$message。
                ];
            }
        }
        return $result;
    }

    /**
     * 取消视频会议
     * @param  [array] $config           [接口相关配置]
     * @param  [array] $conferenceParams [会议参数]
     *                                   [meeting_id:会议id]
     *                                   [meeting_apply_user:创建者id]
     * @return [array]                   [description]
     */
    public function cancelConference($conferenceParams) {
        $result = [];
        // 取配置的参数
        $secretId = $this->secret_id;
        $secretKey = $this->secret_key;
        $appId = $this->app_id;
        $sdkId = $this->sdk_id;
        $timestamp = time();
        if(!$secretId || !$secretKey || !$appId) {
            // 接口参数缺失，视频会议取消失败，请到集成中心配置相关接口
            $result = ['code' => ['video_conferencing_cancel_failed_api_parameter', 'integrationCenter']];
            return $result;
        } else {
            // 构建参数
            // X-TC-Key 对应 SecretId |必填|参与签名计算|
            $X_TC_Key = $secretId;
            // X-TC-Timestamp 单位为秒|必填|参与签名计算|
            $X_TC_Timestamp = $timestamp;
            // X-TC-Nonce 随机正整数|必填|参与签名计算|
            $X_TC_Nonce = rand(1, 10000);
            // AppId 对应 App ID|必填|
            $AppId = $appId;
            // X-TC-Signature 放置由下面的签名方法产生的签名|必填|
            $X_TC_Signature = "";
            // ！1-串联 Header 参数
            $headerString = sprintf("X-TC-Key=%s&X-TC-Nonce=%s&X-TC-Timestamp=%s",$X_TC_Key,$X_TC_Nonce,$X_TC_Timestamp);

            // 会议需要的参数
            // 会议ID[必填]
            $meetingId = $conferenceParams['meeting_id'] ?? '';
            if(empty($meetingId)) {
                // 视频会议取消失败，未获取到会议ID
                $result = ['code' => ['conference_modify_failed_needed_meeting_id', 'integrationCenter']];
                return $result;
            }
            // 替换uri中的变量
            $this->_cancelUri = str_replace('{id}', $meetingId, $this->_cancelUri);
            $creator = $conferenceParams['meeting_apply_user'] ?? 'tester';
            $params = [
                "userid" => $creator,
                "instanceid" => (int)1,
                "reason_code" => (int)1,
                "reason_detail" => 'cancel conference',
            ];
            $paramsJson = json_encode($params, JSON_PRETTY_PRINT);

            // ！2-组签名串
            $stringToSign = 'POST' . "\n" .
                           $headerString . "\n" .
                           $this->_cancelUri . "\n" .
                           $paramsJson;
            // ！3-计算签名（这样生成的已经是16位的了）
            $hmacSha256 = hash_hmac('sha256', $stringToSign, $secretKey);
            $req_signature = base64_encode($hmacSha256);
            // 构建header
            $guzzleHeader = [
                'AppId' => $AppId,
                'SdkId' => $sdkId,
                'Content-Type' => "application/json",
                'X-TC-Key' =>   $X_TC_Key,
                'X-TC-Nonce' => $X_TC_Nonce,
                'X-TC-Signature' => $req_signature,
                'X-TC-Timestamp' => $X_TC_Timestamp,
            ];
            // 拼接url
            $url = $this->_serverHost.$this->_cancelUri;
            // 发请求
            try {
                $client = new Client();
                $guzzleResponse = $client->request('POST', $url, ['body' => $paramsJson, 'headers' => $guzzleHeader]);
                $status = $guzzleResponse->getStatusCode();
            } catch (\Exception $e) {
                if ($e->hasResponse()) {
                    $errorResponse = $e->getResponse();
                    $status = $errorResponse->getStatusCode();
                    $errorMessage = $errorResponse->getBody()->getContents();
                    // 解析$errorMessage
                    $errorMessage = json_decode($errorMessage, true);
                    $errorInfo = $errorMessage['error_info'] ?? [];
                    $message = $errorInfo['message'] ?? '';
                }
            }
            if($status != '200') {
                return [
                    'code' => ['0x010007', 'integrationCenter'], // 视频会议取消失败
                    'dynamic' => trans("integrationCenter.video_conferencing_cancel_failed_interface_error_message", ['message' => $message]), // 视频会议取消失败，接口错误信息：$message。
                ];
            }
            $result = $guzzleResponse->getBody()->getContents();
            // 处理错误
            $resultParse = json_decode($result,true);
            if(isset($resultParse['error_info'])) {
                $errorInfo = $resultParse['error_info'];
                $message = $errorInfo['message'] ?? '';
                return [
                    'code' => ['0x010007', 'integrationCenter'], // 视频会议取消失败
                    'dynamic' => trans("integrationCenter.video_conferencing_cancel_failed_interface_error_message", ['message' => $message]), // 视频会议取消失败，接口错误信息：$message。
                ];
            }
            // 取消成功，返回 Body 为空。
        }
        return $result;
    }

    /**
     * 获取腾讯会议的详情
     * @param [type] $param [description]
     */
    public function getInterfaceDetail($interfaceId) {
        // 传参，获取type=1，腾讯会议的
        $videoconferenceInterfaceDetail = app($this->thirdPartyVideoconferenceRepository)->getVideoconferenceInterfaceInfo($interfaceId, ['search' => ['type' => ['1']]]);
        return $videoconferenceInterfaceDetail;
    }

    /**
     * 创建腾讯视频会议的配置明细
     * @param [type] $param [description]
     */
    public function addInterfaceConfig($param) {
        $data = array_intersect_key($param, array_flip(app($this->thirdPartyVideoconferenceTencentCloudRepository)->getTableColumns()));
        $configResult = app($this->thirdPartyVideoconferenceTencentCloudRepository)->insertData($data);
        return $configResult;
    }

    /**
     * 编辑腾讯视频会议的配置明细
     * @param [type] $param [description]
     */
    public function editInterfaceConfig($param) {
        $configEditResult = '';
        $configId = $param['config_id'] ?? '';
        if($configId) {
            unset($param['config_id']);
            $data = array_intersect_key($param, array_flip(app($this->thirdPartyVideoconferenceTencentCloudRepository)->getTableColumns()));
            if(!empty($data)) {
                $where = ['config_id' => [$configId]];
                $configEditResult = app($this->thirdPartyVideoconferenceTencentCloudRepository)->updateData($data, $where);
            }
        }
        return $configEditResult;
    }

    /**
     * 删除腾讯视频会议的配置明细
     * @param [type] $param [description]
     */
    public function deleteInterfaceConfig($param) {
        $configDeleteResult = '';
        $configId = $param['config_id'] ?? '';
        if($configId) {
            $where = ['config_id' => [$configId]];
            $configDeleteResult = app($this->thirdPartyVideoconferenceTencentCloudRepository)->deleteByWhere($where);
        }
        return $configDeleteResult;
    }

}
