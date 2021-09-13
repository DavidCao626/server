<?php

namespace App\EofficeApp\IntegrationCenter\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\IntegrationCenter\Services\TencentCloudVideoconferenceService;

/**
 * 集成中心，视频会议service
 * 功能：
 * 1、对外，被会议模块调用，实现创建视频会议等功能，传入需要创建的视频会议类型(目前只有腾讯视频会议)
 *
 * @author: Dingpeng
 *
 * @since：2020-04-12
 */
class VideoconferenceService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->thirdPartyVideoconferenceRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyVideoconferenceRepository';
    }

    /**
     * [功能函数][创建视频会议，此函数目前只有会议模块调用]
     * @param  [object] $interfaceParams [接口参数数组 ['interface_id:视频会议接口id，必填']]
     * @param  [object] $conferenceParams [会议参数数组 ['meeting_name:会议名称'...]]
     * @return [type]         [创建成功返回一个json，包含结果]
     */
    public function createVideoconference($interfaceParams, $conferenceParams) {
        $createResult = [];
        $interfaceId = $interfaceParams['interface_id'] ?? '';
        $interfaceDetail = app($this->thirdPartyVideoconferenceRepository)->getVideoconferenceInterfaceInfo($interfaceId);
        // 解析type，拼接配置子项的内容
        $type = $interfaceDetail['type'] ?? '';
        if($type == '1') {
            // 发起腾讯视频会议，取参数，去调用腾讯视频会议接口
            $tencentVideoconference = new TencentCloudVideoconferenceService($interfaceDetail);
            $createResult = $tencentVideoconference->createConference($conferenceParams);
        }
        return $createResult;
    }

    /**
     * [功能函数][编辑视频会议，此函数目前只有会议模块调用]
     * @param  [object] $interfaceParams [接口参数数组 ['interface_id:视频会议接口id，必填']]
     * @param  [object] $conferenceParams [会议参数数组 [meeting_id:会议id;meeting_name:会议名称;meeting_begin_time:;meeting_end_time:;meeting_apply_user:创建者id;]]
     * @return [type]         [编辑成功返回一个json字符串]
     */
    public function modifyVideoconference($interfaceParams, $conferenceParams) {
        $modifyResult = [];
        $interfaceId = $interfaceParams['interface_id'] ?? '';
        $interfaceDetail = app($this->thirdPartyVideoconferenceRepository)->getVideoconferenceInterfaceInfo($interfaceId);
        // 解析type，拼接配置子项的内容
        $type = $interfaceDetail['type'] ?? '';
        if($type == '1') {
            // 发起腾讯视频会议，取参数，去调用腾讯视频会议接口
            $tencentVideoconference = new TencentCloudVideoconferenceService($interfaceDetail);
            $modifyResult = $tencentVideoconference->modifyConference($conferenceParams);
        }
        return $modifyResult;
    }

    /**
     * [功能函数][取消视频会议，此函数目前只有会议模块调用]
     * @param  [object] $interfaceParams [接口参数数组 ['interface_id:视频会议接口id，必填']]
     * @param  [object] $conferenceParams [会议参数数组 [meeting_id:会议id;meeting_apply_user:创建者id]]
     * @return [type]         [取消成功，返回空数组]
     */
    public function cancelVideoconference($interfaceParams, $conferenceParams) {
        $modifyResult = [];
        $interfaceId = $interfaceParams['interface_id'] ?? '';
        $interfaceDetail = app($this->thirdPartyVideoconferenceRepository)->getVideoconferenceInterfaceInfo($interfaceId);
        // 解析type，拼接配置子项的内容
        $type = $interfaceDetail['type'] ?? '';
        if($type == '1') {
            // 发起腾讯视频会议，取参数，去调用腾讯视频会议接口
            $tencentVideoconference = new TencentCloudVideoconferenceService($interfaceDetail);
            $modifyResult = $tencentVideoconference->cancelConference($conferenceParams);
        }
        return $modifyResult;
    }

    /**
     * [配置函数]获取视频会议接口，配置详情
     * @param  [type] $interfaceId [description]
     * @return [type]              [description]
     */
    public function getVideoconferenceInterfaceDetail($interfaceId) {
        $interfaceDetail = [];
        $interfaceDetail = app($this->thirdPartyVideoconferenceRepository)->getVideoconferenceInterfaceInfo($interfaceId);
        // 解析type，拼接配置子项的内容
        $type = $interfaceDetail['type'] ?? '';
        if($type == '1') {
            // 从 has_one_tencent_cloud 中解析
            $tencentInfo = $interfaceDetail['has_one_tencent_cloud'] ?? [];
            unset($interfaceDetail['has_one_tencent_cloud']);
            $interfaceDetail = $interfaceDetail + $tencentInfo;
        }

        return $interfaceDetail;
    }

    /**
     * [配置函数]获取视频会议接口，配置列表
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getVideoconferenceInterfaceList($params) {

        $params = $this->parseParams($params);

        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];
        $count = 0;

        if ($response == 'both' || $response == 'count') {
            $count = app($this->thirdPartyVideoconferenceRepository)->getCount($params);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->thirdPartyVideoconferenceRepository)->getList($params) as $new) {
                $list[] = $new;
            }
        }
        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);

    }

    /**
     * [配置函数]新增视频会议接口
     * @param  [type] $param     [description]
     * @return [type]           [description]
     */
    public function addVideoconferenceInterface($param, $userInfo) {
        $result = [];
        // 判断要新增的视频会议接口的类型
        $type = $param['type'] ?? '';
        // 腾讯视频会议
        if($type == '1') {
            // 调腾讯会议的service，把配置明细插进去
            $tencentVideoconferenceObject = new TencentCloudVideoconferenceService();
            $configResult = $tencentVideoconferenceObject->addInterfaceConfig($param);
            // 解析出config_id
            if($configResult) {
                $configId = $configResult->config_id ?? 0;
                $insertData = array_intersect_key($param, array_flip(app($this->thirdPartyVideoconferenceRepository)->getTableColumns()));
                if($configId) {
                    $insertData['config_id'] = $configId;
                }
                $insertData['creator'] = $userInfo['user_id'] ?? '';
                // 创建接口
                $result = app($this->thirdPartyVideoconferenceRepository)->insertData($insertData);
            }
            if(empty($result)) {
                // 新增视频会议接口失败
                $result = ['code' => ['new_video_conferencing_interface_failed', 'integrationCenter']];
            }
        }
        return $result;
    }

    /**
     * [配置函数]编辑视频会议接口
     * @param  [type] $param     [description]
     * @param  [type] $videoconferenceId [配置主表的主表id]
     * @return [type]           [description]
     */
    public function editVideoconferenceInterface($param, $videoconferenceId) {
        $result = [];
        // 判断要编辑的视频会议接口的类型
        $type = $param['type'] ?? '';
        // 腾讯视频会议
        if($type == '1') {
            // 调腾讯会议的service，编辑配置明细
            $tencentVideoconferenceObject = new TencentCloudVideoconferenceService();
            $configEditResult = $tencentVideoconferenceObject->editInterfaceConfig($param);
            // 编辑主表内容
            $updateData = array_intersect_key($param, array_flip(app($this->thirdPartyVideoconferenceRepository)->getTableColumns()));
            $where = ['videoconference_id' => [$videoconferenceId]];
            $result = app($this->thirdPartyVideoconferenceRepository)->updateData($updateData, $where);
            // 如果是设为默认，把其他的取消
            if (isset($param['is_default']) && $param['is_default'] == 1) {
                app($this->thirdPartyVideoconferenceRepository)->updateData(['is_default' => 0], ['videoconference_id' => [$videoconferenceId, '<>']]);
            }
        }
        return $result;
    }

    /**
     * [配置函数]删除视频会议接口
     * @param  [type] $data     [description]
     * @param  [type] $videoconferenceId [description]
     * @return [type]           [description]
     */
    public function deleteVideoconferenceInterface($videoconferenceId, $userInfo) {
        $result = [];
        // 取一下info
        $interfaceDetail = app($this->thirdPartyVideoconferenceRepository)->getVideoconferenceInterfaceInfo($videoconferenceId);
        // 判断要删除的视频会议接口的类型
        $type = $interfaceDetail['type'] ?? '';
        // 腾讯视频会议
        if($type == '1') {
            // 调腾讯会议的service，删除配置明细
            $tencentVideoconferenceObject = new TencentCloudVideoconferenceService();
            $configDeleteResult = $tencentVideoconferenceObject->deleteInterfaceConfig($interfaceDetail);
            // 删除主表内容
            $where = ['videoconference_id' => [$videoconferenceId]];
            $result = app($this->thirdPartyVideoconferenceRepository)->deleteByWhere($where);
        }
        return $result;
    }
}
