<?php

namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Base\BaseService;
use Eoffice;

/**
 * 流程提醒service类
 *
 * 按功能拆分的service，将提醒功能相关的代码放在此service中
 *
 * @author zyx
 * @since 20210115
 */
class FlowRemindService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->flowTypeRepository = 'App\EofficeApp\Flow\Repositories\FlowTypeRepository';
    }

    /**
     * 子流程触发提醒
     *
     * @param array $param
     */
    public function sonFlowCreationRemind($param)
    {
        $sendData = [];

        // 提醒类型
        $sendData['remindMark'] = 'flow-sonFlowCreation';
        // 提醒接收人
        $sendData['toUser'] = $param['to_user'];
        // contentparam参数，供提醒设置时使用
        $sendData['contentParam'] = ['flowTitle' => $param['run_name']];
        // stateparam参数，供消息中心跳转使用
        $sendData['stateParams'] = ["flow_id" => $param['flow_id'], "run_id" => $param['run_id']];;
        // moduletype
        $sendData['module_type'] = app($this->flowTypeRepository)->getFlowSortByFlowId($param['flow_id']);

        // 发送提醒
        Eoffice::sendMessage($sendData);
    }
}