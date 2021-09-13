<?php

namespace App\EofficeApp\HighMeterIntegration\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\HighMeterIntegration\Services\HighMeterIntegrationService;

/**
 *
 */
class HighMeterIntegrationController extends Controller {

    private $request;
    private $highMeterIntegrationService;

    public function __construct(
        Request $request, HighMeterIntegrationService $highMeterIntegrationService
    ) {
        parent::__construct();
        $this->request = $request;
        $this->highMeterIntegrationService = $highMeterIntegrationService;
    }

    /**
     * 列表数据
     * @author yml <[<email address>]>
     * @return [type] [description]
     */
    public function getList()
    {
        $result = $this->highMeterIntegrationService->getList($this->request->all());
        return $this->returnResult($result);
    }

    public function getConfig($settingId)
    {
        $result = $this->highMeterIntegrationService->getConfig($settingId);
        return $this->returnResult($result);
    }
    /**
     * 修改配置
     * @param $settingId
     * @return array
     */
    public function editSetting($settingId)
    {
        $result = $this->highMeterIntegrationService->editSetting($this->request->all(), $settingId);
        return $this->returnResult($result);
    }

    /** 检测是否有启用高拍仪
     * @return array
     */
    public function checkOpen()
    {
        $result = $this->highMeterIntegrationService->checkOpen();
        return $this->returnResult($result);
    }

    public function getBaseUrl()
    {
        $result = $this->highMeterIntegrationService->getBaseUrl($this->own);
        return $this->returnResult($result);
    }

    public function savePrivateBaseUrl()
    {
        $result = $this->highMeterIntegrationService->savePrivateBaseUrl($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
}
