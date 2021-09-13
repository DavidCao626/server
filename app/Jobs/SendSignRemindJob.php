<?php
namespace App\Jobs;

class SendSignRemindJob extends Job
{
    public $userId;
    public $signDate;
    public $number;
    public $type;
    /**
     * 数据导入导出
     *
     * @return void
     */
    public function __construct($userId, $signDate, $number, $type = 'sign_in')
    {
        $this->userId = $userId;
        $this->signDate = $signDate;
        $this->number = $number;
        $this->type = $type;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return app('App\EofficeApp\Attendance\Services\AttendanceService')->sendSignRemind($this->userId, $this->signDate, $this->number, $this->type);
    }
}
