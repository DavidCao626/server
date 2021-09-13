<?php
namespace App\Jobs;
use GuzzleHttp\Client;
use Queue;
use App\EofficeApp\LogCenter\Facades\LogCenter;

class webhookSendJob extends Job
{
    public $param;

    public function __construct($param)
    {
        $this->param = $param;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $params = $this->param;
        $data = $params['data'];
        $url = $params['url'];
        $log = $params['log'];
        $userId = $params['log']['log_creator'];
        $ip = $params['log']['log_ip'];
        if(!check_white_list($url, ['user_id'=>$userId, 'ip'=>$ip])) {
            return;
        }
        try {
            $client = new Client();
            $guzzleResponse = $client->request('POST', $url, ['form_params' => $data]);
            $status = $guzzleResponse->getStatusCode();
        } catch (\Exception $e) {
            $status = $e->getMessage();
        }
        if ($status == '200') {
            $this->addSystemLog($log['log_creator'], $log['log_content'], $url, $log['log_relation_table']);
        } else {
            $logContent = 'webhook失败，失败原因： ' . $status;
            $this->addSystemLog($log['log_creator'], $logContent, $url, $log['log_relation_table']);
        }
    }

    /**
     * 添加系统日志
     *
     * @param string $userId
     * @param string $logContent
     * @param string $url
     * @param string $log_relation_table
     * @param string $log_relation_id
     *
     * @return void
     */
    public function addSystemLog($userId, $logContent, $url, $log_relation_table = '', $log_relation_id = '') {
        $identifier  = "integration.webhook.fail";
        $logParams = $this->handleLogParams($userId, $logContent, $url, $log_relation_id, $log_relation_table);
        logCenter::info($identifier , $logParams);
    }

    /**
     * 处理日志参数
     *
     * @param string $user
     * @param string $content
     * @param string $relation_title
     * @param string $relation_id
     * @param string $relation_table
     *
     * @return array
     */
    public function handleLogParams($user, $content, $relation_title = '', $relation_id = '', $relation_table = '')
    {
        // 将日志内容中特殊字符转为实体符号

        $content = htmlspecialchars($content);

        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => $relation_table,
            'relation_id' => $relation_id,
            'relation_title' => $relation_title,
        ];

        return $data;
    }
}
