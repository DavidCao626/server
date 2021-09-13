<?php
namespace App\Jobs;

class AutoSignOutJob extends Job
{
    public $userId;
    public $signDate;
    public $number;
    public $signOutTime;
    /**
     * 数据导入导出
     *
     * @return void
     */
    public function __construct($userId, $signDate, $signOutTime, $number)
    {
        $this->userId = $userId;
        $this->signDate = $signDate;
        $this->number = $number;
        $this->signOutTime = $signOutTime;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return app('App\EofficeApp\Attendance\Services\AttendanceService')->autoSignOut($this->userId, $this->signDate, $this->signOutTime, $this->number);
    }
}
