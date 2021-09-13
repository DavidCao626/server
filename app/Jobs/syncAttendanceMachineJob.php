<?php
namespace App\Jobs;

class SyncAttendanceMachineJob extends Job {

    public $param;
    public $user_id;
    /**
     * 域同步
     *
     * @return void
     */
    public function __construct($param,$user_id) {
        $this->param = $param;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $param = $this->param;
        $user_id = $this->user_id;
        $result = app('App\EofficeApp\Attendance\Services\AttendanceMachineService')->syncQueueAttendance($param,$user_id);

        return $result;
    }
}
