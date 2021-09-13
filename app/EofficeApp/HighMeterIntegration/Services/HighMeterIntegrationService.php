<?php

namespace App\EofficeApp\HighMeterIntegration\Services;

use App\EofficeApp\Base\BaseService;

/**
 * 高拍仪集成模块，后端service
 *
 * @author: yml
 *
 * @since：2020-02-17
 */
class HighMeterIntegrationService extends BaseService
{
    /**
     * @var string
     */
    private $highMeterSettingRepository;
    /**
     * @var string
     */
    private $highMeterBaseUrlSettingRepository;

    public function __construct(
    ) {
        parent::__construct();
        $this->highMeterSettingRepository = 'App\EofficeApp\HighMeterIntegration\Repositories\HighMeterSettingRepository';
        $this->highMeterBaseUrlSettingRepository = 'App\EofficeApp\HighMeterIntegration\Repositories\HighMeterBaseUrlSettingRepository';
    }

    public function getList($param)
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->highMeterSettingRepository), 'getCount', 'getList', $param);
    }

    public function editSetting($param, $settingId)
    {
        $param = array_intersect_key($param, array_flip(app($this->highMeterSettingRepository)->getTableColumns()));
        if ($param) {
            return  app($this->highMeterSettingRepository)->updateData($param, ['setting_id' => $settingId]);
        } else {
            return ['code' => ['enable_failed', 'integrationCenter.high_meter']];
        }
    }

    public function checkOpen()
    {
        $open = app($this->highMeterSettingRepository)->getFieldInfo(['is_open' => 1]);
        return ['is_open' => $open ? 1 : 0];
    }

    public function getBaseUrl($userInfo)
    {
        $userId = $userInfo['user_id'];
        $privateUrl = app($this->highMeterSettingRepository)->getOneFieldInfo(['is_open' => 1]);
        $private = app($this->highMeterBaseUrlSettingRepository)->getOneFieldInfo(['setting_id' => [$privateUrl['setting_id']], 'user_id' => [$userId]]);
        if ($private && $private['base_url']) {
            $private['pdf_file_save_address'] = $privateUrl['pdf_file_save_address'] ?? '';
            $private['pdf_file_save_picture_quality'] = $privateUrl['pdf_file_save_picture_quality'] ?? '';
            $private['auto_open_choose_pdf'] = $privateUrl['auto_open_choose_pdf'] ?? '';
            return $private;
        }
        return $privateUrl;
    }

    public function savePrivateBaseUrl($param, $userInfo)
    {
        $user_id = $userInfo['user_id'];
        if (!$param || !isset($param['base_url'])){
            return ['code' =>['', '参数错误']];
        }
        $baseUrl = $param['base_url'];
        $param = array_intersect_key($param, array_flip(app($this->highMeterBaseUrlSettingRepository)->getTableColumns()));
        $setting = app($this->highMeterBaseUrlSettingRepository)->getOneFieldInfo(['user_id' => $user_id, 'setting_id' => $param['setting_id']]);
        if ($setting) {
            return app($this->highMeterBaseUrlSettingRepository)->updateData(['base_url' => $baseUrl], ['id' => $setting->id]);
        } else {
            $param['user_id'] = $user_id;
            return app($this->highMeterBaseUrlSettingRepository)->insertData($param);
        }
//        return app($this->highMeterBaseUrlSettingRepository)->updateOrCreate($param);
    }

    public function getConfig($settingId)
    {
        $config = app($this->highMeterSettingRepository)->getDetail($settingId);
        if ($config) {
            return $config;
        } else {
            return ['code' => ['get_failed', 'integrationCenter.high_meter']];
        }
    }
}
