<?php
namespace App\EofficeApp\System\Domain\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Domain\Services\DomainService;
use App\EofficeApp\System\Domain\Services\DomainSyncService;
use Illuminate\Http\Request;

/**
 * @域集成管理控制器
 *
 * @author nixuiaoke
 */
class DomainController extends Controller
{
    private $domainService; // DomainService 对象

    /**
     * @注册 DomainService 对象
     * @param \App\EofficeApp\Services\DomainService $domainService
     */
    public function __construct(
        Request $request,
        DomainService $domainService
    ) {
        parent::__construct();

        $this->request       = $request;
        $this->domainService = $domainService;
        $userInfo            = $this->own;
        $this->userId        = $userInfo['user_id'];
    }

    /**
     * @测试域连接
     * @return bool
     */
    public function testDomainConnect()
    {
        $result = $this->domainService->testDomainConnect($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * @添加域配置
     * @return bool
     */
    public function saveDomain()
    {
        $result = $this->domainService->saveDomain($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * @获取域配置列表
     * @return array
     */
    public function getDomainInfo()
    {
        $result = $this->domainService->getDomainInfo();
        return $this->returnResult($result);
    }

    /**
     * @同步数据
     * @return array
     */
    public function syncDomain()
    {
        $result = $this->domainService->syncDomain($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * @同步日志
     * @return array
     */
    public function getSyncLogs()
    {
        $result = $this->domainService->getSyncLogs($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * @同步详情
     * @return array
     */
    public function getSyncLogDetail($logId)
    {
        $result = $this->domainService->getSyncLogDetail($this->request->all(), $logId);
        return $this->returnResult($result);
    }

    /**
     * @获取同步结果
     * @return str
     */
    public function getSyncResult($logId)
    {
        $result = $this->domainService->getSyncResult($logId);
        return $this->returnResult($result);
    }

    /**
     * @删除同步记录
     * @return str
     */
    public function deleteSyncRecord($logId)
    {
        $result = $this->domainService->deleteSyncRecord($logId);
        return $this->returnResult($result);
    }

    /**
     * 获取自动同步配置
     */
    public function getDomainSyncConfig()
    {
        /** @var DomainSyncService $service */
        $service = app('App\EofficeApp\System\Domain\Services\DomainSyncService');

        return $this->returnResult($service->getDomainSyncConfig());
    }

    /**
     * 设置自动同步配置
     */
    public function setDomainSyncConfig()
    {
        /** @var DomainSyncService $service */
        $service = app('App\EofficeApp\System\Domain\Services\DomainSyncService');
        /** @var Request $request */
        $request = $this->request;
        $result = $service->setDomainSyncConfig($request);

        return $this->returnResult($result);
    }
}
