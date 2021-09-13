<?php

namespace App\EofficeApp\XiaoE\Services;


use App\EofficeApp\XiaoE\Traits\FlowTrait;
use App\EofficeApp\XiaoE\Traits\XiaoETrait;

/**
 * 小e数据初始化
 * @author shiqi
 */
class InitService extends BaseService
{
    use FlowTrait;
    use XiaoETrait;
    private $userRepository;
    private $flowRunService;
    private $systemService;

    public function __construct()
    {
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->flowRunService = 'App\EofficeApp\Flow\Services\FlowRunService';
        $this->systemService = 'App\EofficeApp\XiaoE\Services\SystemService';
    }

    /**
     * 小e后台配置表单增加了新功能，但是客户并没有升级，向下兼容，避免无法展示表单
     * @param $method
     * @param $arguments
     * @return array|bool
     */
    public function __call($method, $arguments)
    {
        $data = $arguments[0];
        $return = array();
        $return['params'] = json_decode($data['params'], true);
        return $return;
    }

    /**
     * 获取流程标题
     * @param $data
     * @return array
     */
    public function getFlowTitle($data)
    {
        $return = array();
        $return['params'] = json_decode($data['params'], true);
        $intention = $data['intentName'];
        $config = $this->config($intention);
        $flowId = $config['flow_id'] ?? null;
        $return['params']['flowId'] = $flowId;
        if (!$flowId) {
            $return['params']['title'] = '';
            return $return;
        }
        $userId = $data['userId'];
        $title = $this->flowNewPageGenerateFlowRunName(['form_data' => [], 'flow_id' => $flowId], $userId);
        $return['params']['title'] = $title;
        return $return;
    }
}
