<?php

namespace App\Jobs;

use App\Utils\Email;

class EmailJob extends Job
{
    public $param;
    // 超时时间
    public $timeout = 120;
    // 重试时间 - 任务执行超过这个时间再执行一次
    public $delay = 120;
    /**
     * 邮件收发
     *
     * @return void
     */
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
        $param = $this->param;
        $handle = 'handle'.ucwords($param['handle']);
        $this->$handle($param['param']);

    }

    /**
     * 发送邮件
     *
     * @return void
     */
    public function handleSend($param)
    {
        (new Email)->sendMail($param);
    }

    /**
     * 接收邮件
     *
     * @return void
     */
    public function handleReceive($param)
    {
        (new Email)->receiveMail($param['inboxServer'], $param['receive'], $param['userId']);
    }

    public function handleFolder($param)
    {
        (new Email)->handleWebmail($param, $param['type'], $param['userId']);
    }

}
