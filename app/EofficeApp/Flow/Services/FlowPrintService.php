<?php
namespace App\EofficeApp\Flow\Services;
use Eoffice;
use DB;
use Schema;
use App\Utils\Utils;
use App\EofficeApp\Flow\Services\FlowBaseService;

/**
 * 流程打印service类
 * 用来处理流程打印相关的内容
 *
 * @author zyx
 *
 * @since  20200525
 */
class FlowPrintService extends FlowBaseService
{

    public function __construct(
    ) {
        parent::__construct();
    }

    /**
     * 新增流程打印日志
     *
     * @author zyx
     * @since 20200506
     */
    public function addFlowRunPrintLog($param, $own)
    {
        $insertData = [
            'flow_run_id' => $param['run_id'],
            'flow_run_process_id' => $param['node_id'],
            'print_user_id' => $param['user_id'] ? $param['user_id'] : $own['user_id'],
            'print_time' => date('Y-m-d H:i:s'),
            'print_user_ip' => getClientIp(),
            'print_type' => $param['type'] // 打印类型，页面page和表单form
        ];
        $result = app($this->flowRunPrintLogRepository)->insertData($insertData);
        // 直接拉取日志数量
        if ($result->id) {
            return app($this->flowRunPrintLogRepository)->getFlowRunPrintLogListTotal(['returnType' => 'count', 'run_id' => $param['run_id']]);
        }
        return $result;
    }

    /**
     * 获取流程打印日志
     *
     * @author zyx
     * @since 20200506
     */
    public function getFlowRunPrintLog($params, $own)
    {
        $result = $this->response(app($this->flowRunPrintLogRepository), 'getFlowRunPrintLogListTotal', 'getFlowRunPrintLogList', $this->parseParams($params));
        return $result;
    }
}
