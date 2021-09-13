<?php

namespace App\EofficeApp\IntegrationCenter\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Cache;

/**
 * 集成中心-第三方接口集成模块，后端service
 *
 * @author: YML
 *
 * @since：2020-04-02
 */
class ThirdPartyInterfaceService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->thirdPartyOcrRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyOcrRepository';
        $this->thirdPartyOcrTencentRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyOcrTencentRepository';
        $this->thirdPartyInvocieCloudRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyInvoiceCloudRepository';
        $this->thirdPartyInvoiceCloudTeamsYunRepository = 'App\EofficeApp\IntegrationCenter\Repositories\ThirdPartyInvoiceCloudTeamsYunRepository';
        $this->videoconferenceService = 'App\EofficeApp\IntegrationCenter\Services\VideoconferenceService';
    }

    /**
     * 为集成中心-第三方接口集成提供列表数据
     * @param  [type] $data [description]
     * @param  [type] $type [type可选值['ocr:OCR识别','videoconference:视频会议']]
     * @return [type]       [description]
     */
    public function getList($data, $type)
    {
        if (!$type) {
            return [];
        }
        switch(strtolower($type)) {
            case 'ocr':
                return $this->getOcrList($data);
                break;
            case 'invoice_cloud':
                return $this->getInvoiceCloudList($data);
            case 'videoconference':
                // 调用视频会议service，获取视频会议接口列表
                return app($this->videoconferenceService)->getVideoconferenceInterfaceList($data);
                break;
            default:
                return [];
        }
    }

    public function getOcrList($params = [])
    {
        $params = $this->parseParams($params);

        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];
        $count = 0;

        if ($response == 'both' || $response == 'count') {
            $count = app($this->thirdPartyOcrRepository)->getCount($params);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->thirdPartyOcrRepository)->getList($params) as $new) {
                $list[] = $new;
            }
        }
        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }

    /**
     * 集成中心-新增第三方接口
     * @param  [type] $data     [接口参数]
     * @param  [type] $type     [type可选值['ocr:OCR识别','videoconference:视频会议']]
     * @param  [type] $user     [description]
     * @return [type]           [description]
     */
    public function addThirdPartyInterface($data, $type, $user)
    {
        if (!$type) {
            return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
        switch(strtolower($type)) {
            case 'videoconference':
                // 调用视频会议service，新增视频会议接口
                return app($this->videoconferenceService)->addVideoconferenceInterface($data, $user);
                break;
            default:
                return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
    }

    /**
     * 为第三方接口集成，编辑接口信息
     * @param  [type] $data     [description]
     * @param  [type] $type     [type可选值['ocr:OCR识别','videoconference:视频会议']]
     * @param  [type] $configId [description]
     * @param  [type] $user     [description]
     * @return [type]           [description]
     */
    public function editThirdPartyInterface($data, $type, $configId, $user)
    {
        if (!$type) {
            return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
        switch(strtolower($type)) {
            case 'ocr':
                return $this->editOcr($data, $configId, $user);
                break;
            case 'invoice_cloud':
                return $this->editInvoiceCloud($data, $configId, $user);
            case 'videoconference':
                // 调用视频会议service，编辑视频会议接口
                return app($this->videoconferenceService)->editVideoconferenceInterface($data, $configId);
                break;
            default:
                return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
    }

    public function editOcr($data, $configId, $user)
    {
        $ocr = array_intersect_key($data, array_flip(app($this->thirdPartyOcrRepository)->getTableColumns()));
        if (!isset($ocr['type'])) {
            $ocr['type'] = 1;
        }
        if ($ocr['type'] == 1) {
            $tencentOcr = array_intersect_key($data, array_flip(app($this->thirdPartyOcrTencentRepository)->getTableColumns()));
            if ($configId) {
                //编辑
                unset($tencentOcr['config_id']);
                $where = ['config_id' => [$configId]];
                if ($tencentOcr) {
                    app($this->thirdPartyOcrTencentRepository)->updateData($tencentOcr, $where);
                }
                if ($ocr) {
                    $res = app($this->thirdPartyOcrRepository)->updateData($ocr, $where);
                    if (isset($ocr['is_use']) && $ocr['is_use'] == 1) {
                        app($this->thirdPartyOcrRepository)->updateData(['is_use' => 0], ['config_id' => [$configId, '<>']]);
                    }
                }
                return true;
            } else {
                // 新建
                if ($tencentOcr) {
                    $config = app($this->thirdPartyOcrTencentRepository)->insertData($tencentOcr);
                    if ($config) {
                        $configId = $config->config_id ?? 0;
                        $ocr['config_id'] = $configId;
                        $ocr['created_at'] = $ocr['updated_at'] = time();
                        $ocr['creator'] = $user['user_id'] ?? 0;
                        $res = app($this->thirdPartyOcrRepository)->insertData($ocr);
                        return $res;
                    }
                } else {
                    return ['code' => ['param_error', 'integrationCenter.thirdPartyInterface']];
                }
            }
        }
    }

    public function getThirdPartyInterface($configId, $user, $type)
    {
        if (!$type) {
            return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
        switch(strtolower($type)) {
            case 'ocr':
                return $this->getThirdPartyInterfaceOcr($configId, $user);
                break;
            case 'invoice_cloud':
                return $this->getThirdPartyInterfaceInvoiceCloud($configId, $user);
                break;
            default:
                return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
    }

    /**
     * 调用OCR接口的Repository，获取OCR接口详情
     * 20200416-dingpeng-此函数对外的那个路由被注释掉了(原因参考路由那一行)，现在只在此service内调用
     *
     * @param  [type] $ocrId [description]
     * @param  array  $user  [description]
     * @return [type]        [description]
     */
    public function getThirdPartyInterfaceOcr($ocrId, $user = [], $params = [])
    {
        if ($ocrId) {
            $ocr = app($this->thirdPartyOcrRepository)->getDetail($ocrId);
        } else if ($params){
            $ocr = app($this->thirdPartyOcrRepository)->getOneFieldInfo($params);
        }
       $type = $ocr->type ?? 0;
       if ($type == 1) {
           $ocr['tencent'] = app($this->thirdPartyOcrTencentRepository)->getDetail($ocr->config_id);
       }
       return $ocr;
    }

    /**
     * 为第三方接口集成，删除接口信息
     * @param  [type] $type     [type可选值['ocr:OCR识别','videoconference:视频会议']]
     * @param  [type] $configId [description]
     * @param  [type] $user     [description]
     * @return [type]           [description]
     */
    public function deleteThirdPartyInterface($type, $configId, $user)
    {
        if (!$type) {
            return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
        switch(strtolower($type)) {
            case 'ocr':
                return $this->deleteOcr($configId);
                break;
            case 'videoconference':
                // 调用视频会议service，删除视频会议接口
                return app($this->videoconferenceService)->deleteVideoconferenceInterface($configId, $user);
                break;
            default:
                return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
    }

    public function deleteOcr($configId)
    {
        $where = ['config_id' => [$configId]];
        $ocr =  app($this->thirdPartyOcrRepository)->getOneFieldInfo($where);
        if (!$ocr) {
            return ['code' => ['config_not_found', 'integrationCenter.thirdPartyInterface']];
        }
        $deleteOcr = app($this->thirdPartyOcrRepository)->deleteByWhere($where);
        if ($deleteOcr) {
            if ($ocr['type'] == 1) {
                app($this->thirdPartyOcrTencentRepository)->deleteByWhere($where);
            }
        }
        return true;
    }

    /**
     * 获取启用的腾讯开放API配置
     * @return array
     */
    public function getTencentOcrConfig()
    {
        $ocr =  app($this->thirdPartyOcrRepository)->getTencentOcr();

        return $ocr['has_one_tencent_ocr'] ?? [];
    }

    public function addInvoiceCloud()
    {

    }

    public function editInvoiceCloud($data, $configId, $user)
    {
        $invoiceCloud = array_intersect_key($data, array_flip(app($this->thirdPartyInvocieCloudRepository)->getTableColumns()));
        if (!isset($invoiceCloud['type'])) {
            $invoiceCloud['type'] = 1;
        }
        if ($invoiceCloud['type'] == 1) {
            $teamsYun = array_intersect_key($data, array_flip(app($this->thirdPartyInvoiceCloudTeamsYunRepository)->getTableColumns()));
            if ($configId) {
                //编辑
                unset($teamsYun['config_id']);
                $where = ['config_id' => [$configId]];
                if ($teamsYun) {
                    app($this->thirdPartyInvoiceCloudTeamsYunRepository)->updateData($teamsYun, $where);
                    // 清空发票云用户信息
                    if (isset($teamsYun['cid']))
                        Cache::forget('invoice_cloud_teamsyun_users_'. $teamsYun['cid']);
                }
                if ($invoiceCloud) {
                    $res = app($this->thirdPartyInvocieCloudRepository)->updateData($invoiceCloud, $where);
                    if (isset($invoiceCloud['is_use']) && $invoiceCloud['is_use'] == 1) {
                        app($this->thirdPartyInvocieCloudRepository)->updateData(['is_use' => 0], ['config_id' => [$configId, '<>']]);
                    }
                }
                return true;
            } else {
                // 新建
                if ($teamsYun) {
                    $config = app($this->thirdPartyInvoiceCloudTeamsYunRepository)->insertData($teamsYun);
                    if ($config) {
                        $configId = $config->config_id ?? 0;
                        $invoiceCloud['config_id'] = $configId;
                        $invoiceCloud['created_at'] = $invoiceCloud['updated_at'] = time();
                        $invoiceCloud['creator'] = $user['user_id'] ?? 0;
                        $res = app($this->thirdPartyInvocieCloudRepository)->insertData($invoiceCloud);
                        return $res;
                    }
                } else {
                    return ['code' => ['param_error', 'integrationCenter.thirdPartyInterface']];
                }
            }
        } else {
            return true;
        }
    }

    public function getInvoiceCloudList($params = [])
    {
        $params = $this->parseParams($params);
        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];
        $count = 0;

        if ($response == 'both' || $response == 'count') {
            $count = app($this->thirdPartyInvocieCloudRepository)->getCount($params);
        }
        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->thirdPartyInvocieCloudRepository)->getList($params) as $new) {
                $list[] = $new;
            }
        }
        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }

    public function getThirdPartyInterfaceInvoiceCloud($invoiceCloudId, $params = [])
    {
        return  app($this->thirdPartyInvocieCloudRepository)->getThirdPartyInterfaceInvoiceCloud($invoiceCloudId, $params);
    }

     public function getThirdPartyInterfaceByWhere($type, $user, $param)
     {
         if (!$type) {
             return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
         }
         switch (strtolower($type)) {
             case 'ocr':
                 return $this->getThirdPartyInterfaceOcr(0, $user, $param);
             case 'invoice_cloud':
                 return $this->getThirdPartyInterfaceInvoiceCloud(0, $param);
             default:
                 return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
         }
     }
    /**
     * 集成中心-第三方接口配置-获取某个接口详情
     * @param  [type] $type     [接口类型，必填，可选值['ocr:OCR识别','videoconference:视频会议']]
     * @param  [type] $configId [description]
     * @return [type]           [description]
     */
    public function getThirdPartyInterfaceInfo($type, $configId)
    {
        if (!$type) {
            return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
        // 分发
        switch(strtolower($type)) {
            case 'ocr':
                return $this->getThirdPartyInterfaceOcr($configId);
            case 'videoconference':
                // 调用视频会议service，获取详情
                return app($this->videoconferenceService)->getVideoconferenceInterfaceDetail($configId);
                break;
            case 'invoice_cloud':
                return $this->getThirdPartyInterfaceInvoiceCloud($configId);
                break;
            default:
                return ['code' => ['type_error', 'integrationCenter.thirdPartyInterface']];
        }
    }

    /** 保存团队信息到配置
     * @param $param
     * @param $configId
     * @return bool
     */
    public function UpdateInvoiceCloudTeamsYunUseConfig($param, $where)
    {
        $teamsYun = array_intersect_key($param, array_flip(app($this->thirdPartyInvoiceCloudTeamsYunRepository)->getTableColumns()));
        if ($teamsYun) {
            app($this->thirdPartyInvoiceCloudTeamsYunRepository)->updateData($teamsYun, $where);
        }
        return true;
    }

    /** 根据openapi应用id获取发票云配置
     * @param $applicationId
     * @return mixed
     */
    public function getInvoiceCloudTeamsYunUseConfigByApplicationId($applicationId)
    {
        return app($this->thirdPartyInvoiceCloudTeamsYunRepository)->getOnefieldInfo(['open_application_id' => [$applicationId]]);
    }

}
