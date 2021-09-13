<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailSendReceiveLogEntity;

/**
 * 邮件收发日志Repository类
 *
 * @since  2019-08-30 创建
 */
class WebmailSendReceiveLogRepository extends BaseRepository
{
    public function __construct(WebmailSendReceiveLogEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @since  2019-08-30
     */
    public function getLogs(array $param = [])
    {
        $default = [
            'fields' => ['*'],
            'search' => [],
            'page' => 1,
            'limit' => config('eoffice.pagesize'),
            'order_by' => [],
        ];

        $param = array_filter($param, function ($var) {
            return $var !== '';
        });
        $param = array_merge($default, $param);
        $query = $this->entity
            ->select($param['fields'])
            ->with(['creatorName' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->with(['Mail' => function ($query) {
            $query->select(['mail_id', 'mail_subject']);
        }])
        ->with(['customerRecords' => function($query) {
            $query->select(['mail_id', 'customer_id', 'linkman_id', 'record_id']);
        }]);

        $query = $this->getLogsParseWhere($query, $param['search']);

        $logs = $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
        foreach ($logs as $key => $log) {
            $logs[$key]['outbox_info'] = $log['outbox_info'] ? json_decode($log['outbox_info']) : [];
            $logs[$key]['mail_info'] = $log['mail_info'] ? json_decode($log['mail_info']) : [];
            $logs[$key]['request_data'] = $log['request_data'] ? json_decode($log['request_data']) : [];
        }
        return $logs;
    }

    /**
     * 获取数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getNum(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getLogsParseWhere($this->entity, $where)->count();
    }

    /**
     * 条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getLogsParseWhere($query, array $where = [])
    {
        // 需要处理 邮箱、操作人、操作时间、邮件主题、执行类型
        if (isset($where['webmail_outbox.account']) && !empty($where['webmail_outbox.account'])) {
            $query = $query->leftJoin('webmail_outbox', 'webmail_outbox.outbox_id', '=', 'webmail_send_receive_log.outbox_id');
        }
        if (isset($where['webmail_mail.mail_subject']) && !empty($where['webmail_mail.mail_subject'])) {
            $query = $query->leftJoin('webmail_mail', 'webmail_mail.mail_id', '=', 'webmail_send_receive_log.mail_id');
        }
        if (isset($where['created_at_advance']) && !empty($where['created_at_advance'])) {
            $where['webmail_send_receive_log.created_at'] = $where['created_at_advance'];
            unset($where['created_at_advance']);
        }
        return $query->wheres($where);
    }

    public function getStatusByMail($mailId)
    {
        return $this->entity->where('mail_id', $mailId)->value('result_status');
    }
}
