<?php
 
namespace App\EofficeApp\ServerManage\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\ServerManage\Services\ServerManageService;
use Queue;
use App\Jobs\testJob;

/**
 * 服务器管理平台
 */
class ServerManageController extends Controller {

    public function __construct( 
        Request $request,
        ServerManageService $serverManageService 
    ) {
        parent::__construct();
        $this->request = $request;
        $this->serverManageService = $serverManageService;
    }

    /**
     * 获取服务状态
     */
    public function getServerStatus() {
        return $this->returnResult($this->serverManageService->operateExe(1, $this->own));
    }

    /**
     * 立即更新
     */
    public function startUpdateNow() {
        return $this->returnResult($this->serverManageService->operateExe(2, $this->own, ['time' => '0']));
    }
    /**
     * 稍后更新
     */
    public function startUpdateLater() {
        return $this->returnResult($this->serverManageService->operateExe(2, $this->own, ['time' => '1']));
    }
    /**
     * 取消更新
     */
    public function cancelUpdate() {
        return $this->returnResult($this->serverManageService->operateExe(2, $this->own, ['time' => '-1']));
    }

    /**
     * 获取新版本信息
     */
    public function getNewVersionInfo() {
        return $this->returnResult($this->serverManageService->getNewVersionInfo($this->request->all(), $this->own));
    }

    /**
     * 设置更新时间
     */
    // public function setUpdateTime() {
    //     return $this->returnResult($this->serverManageService->setUpdateTime($this->request->all()));
    // }
}
