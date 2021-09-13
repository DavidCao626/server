<?php

namespace App\EofficeApp\ElectronicSign\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\SyncQiyuesuoJob;
use Queue;

/**
 * 电子签署 service
 */
class QiyuesuoRelatedResourceService extends BaseService
{
    public function __construct()
    {
        parent::__construct();        
        $this->qiyuesuoService = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoService';
        // 契约锁印章
        $this->qiyuesuoSealRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealRepository';
        // 契约锁物理用印集成配置
        $this->qiyuesuoSealApplySettingRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplySettingRepository';
        // 契约锁电子合同集成配置
        $this->qiyuesuoSettingRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSettingRepository'; 
        // 契约锁业务分类的Repository
        $this->qiyuesuoCategoryRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoCategoryRepository';
        // 契约锁模板的Repository
        $this->qiyuesuoTemplateRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoTemplateRepository';
        $this->qiyuesuoSyncRelatedResourceLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSyncRelatedResourceLogRepository';
    }
    /**
     * 队列执行同步任务
     *
     * @param [type] $data
     * @param [type] $userInfo
     *
     * @return void
     * @author yuanmenglin
     * @since 
     */
    public function syncTask($data, $userInfo)
    {
        $actions = $data['actions'] ? explode(',', $data['actions']) : [];
        foreach ($actions as $action) {
            $type = $action ?? '';
            $params = compact('type', 'userInfo');
            Queue::push(new SyncQiyuesuoJob($params));
        }
        return true;
    }
    /**
     * 获取物理印章
     *
     * @return void
     * @author yuanmenglin
     * @since 
     */
    public function getPhysicalSeal($data)
    {
        $data['type'] = 'PHYSICS';
        return $this->getSeals($data);
    }
    /**
     * 获取电子印章
     *
     * @return void
     * @author yuanmenglin
     * @since 
     */
    public function getElectronicSeal($data)
    {
        $data['type'] = 'ELECTRONIC';
        return $this->getSeals($data);
    }

    public function getSeals($data)
    {
        $flowId = $data['flow_id'] ?? '';
        $type = $data['type'] ?? '';
        $company = isset($data['company']) ? trim($data['company']) : '';
        if ($company) {
            $where['ownerName'] = $company;
        }
        $seals = [];
        if ($flowId && $type){
            if ($type == 'PHYSICS') {
                $serverId = app($this->qiyuesuoSealApplySettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            } else {
                $serverId = app($this->qiyuesuoSettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            }
            if (isset($serverId) && !empty($serverId)) {
                $where['serverId'] = $serverId;
                $where['category'] = $type;
                $seals = app($this->qiyuesuoSealRepository)->getFieldInfo($where);
            }
        }
        return $seals;
    }

    /**
     * 获取业务分类
     *
     * @return void
     * @author yuanmenglin
     * @since 
     */
    public function getCategory($data)
    {
        $flowId = $data['flow_id'] ?? '';
        $category = [];
        $company = trim($data['company']) ?? '';
        if ($company) {
            $where['tenantName'] = $company;
        }
        if ($flowId) {
            $physicalServerId = app($this->qiyuesuoSealApplySettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            $electronicServerId = app($this->qiyuesuoSettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            $serverId = '';
            if ($physicalServerId) {
                $serverId = $physicalServerId;
                $type = 'PHYSICAL';
            }
            if ($electronicServerId) {
                $serverId = $electronicServerId;
                $type = 'ELECTRONIC';
            }
            if ($serverId) {
                $where['serverId'] = $serverId;
                $where['type'] = $type;
                $category = app($this->qiyuesuoCategoryRepository)->getFieldInfo($where);
            }
        }
        return $category;
    }

    public function getPhysicalCategory($data)
    {
        $flowId = $data['flow_id'] ?? '';
        $category = [];
        $company = trim($data['company']) ?? '';
        if ($company) {
            $where['tenantName'] = $company;
        }
        if ($flowId) {
            $physicalServerId = app($this->qiyuesuoSealApplySettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            $serverId = '';
            if ($physicalServerId) {
                $serverId = $physicalServerId;
                $type = 'PHYSICAL';
            }
            if ($serverId) {
                $where['serverId'] = $serverId;
                $where['type'] = $type;
                $category = app($this->qiyuesuoCategoryRepository)->getFieldInfo($where);
            }
        }
        return $category;
    }

    public function getElectronicCategory($data)
    {
        $flowId = $data['flow_id'] ?? '';
        $category = [];
        $company = trim($data['company']) ?? '';
        if ($company) {
            $where['tenantName'] = $company;
        }
        if ($flowId) {
            $electronicServerId = app($this->qiyuesuoSettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            $serverId = '';
            if ($electronicServerId) {
                $serverId = $electronicServerId;
                $type = 'ELECTRONIC';
            }
            if ($serverId) {
                $where['serverId'] = $serverId;
                $where['type'] = $type;
                $category = app($this->qiyuesuoCategoryRepository)->getFieldInfo($where);
            }
        }
        return $category;
    }
    /**
     * 获取模板列表
     *
     * @return void
     * @author yuanmenglin
     * @since 
     */
    public function getTemplate($data)
    {
        $flowId = $data['flow_id'] ?? '';
        $category = [];
        $company = trim($data['company']) ?? '';
        if ($company) {
            $where['tenantName'] = $company;
        }
        if ($flowId) {
            $physicalServerId = app($this->qiyuesuoSealApplySettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            $electronicServerId = app($this->qiyuesuoSettingRepository)->getFieldValue('serverId', ['workflowId' => $flowId]);
            if ($physicalServerId) {
                $serverId = $physicalServerId;
            }
            if ($electronicServerId) {
                $serverId = $electronicServerId;
            }
            $serverId = $physicalServerId ?? $electronicServerId;
            if ($serverId) {
                $where['serverId'] = $serverId;
                $category = app($this->qiyuesuoTemplateRepository)->getFieldInfo($where);
            }
        }
        return $category;
    }

    public function getLogs($params)
    {
        $params = $this->parseParams($params);

        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];
        $count = 0;
        if (!in_array($response, ['both', 'count', 'data'])) {
            return ['code' => ['0x093517', 'electronicsign']];
        }
        if ($response == 'both' || $response == 'count') {
            $count = app($this->qiyuesuoSyncRelatedResourceLogRepository)->getCount($params);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->qiyuesuoSyncRelatedResourceLogRepository)->getList($params) as $new) {
                $list[] = $new;
            }
        }

        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }
}