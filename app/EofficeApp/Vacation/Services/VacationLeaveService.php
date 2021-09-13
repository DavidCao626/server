<?php

namespace App\EofficeApp\Vacation\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Attendance\Repositories\AttendanceLeaveRepository;
use App\EofficeApp\Attendance\Repositories\AttendanceLeaveDiffStatRepository;

class VacationLeaveService extends BaseService
{
    protected $attendanceLeaveRepository;
    protected $attendanceLeaveDiffStatRepository;
    private $vacationRepository;

    public function __construct(
        AttendanceLeaveRepository $attendanceLeaveRepository,
        AttendanceLeaveDiffStatRepository $attendanceLeaveDiffStatRepository
    )
    {
        parent::__construct();
        $this->attendanceLeaveRepository = $attendanceLeaveRepository;
        $this->attendanceLeaveDiffStatRepository = $attendanceLeaveDiffStatRepository;
        $this->vacationRepository = 'App\EofficeApp\Vacation\Repositories\VacationRepository';
    }

    public function getAllLeaveDays($userIds, $vacationIds)
    {
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }
        if (!$vacationIds) {
            $vacationIds = [$vacationIds];
        }
        $vacationSet = app($this->vacationRepository)->getVacationSet();
        if($vacationSet['is_transform'] == 0){
            return $this->attendanceLeaveDiffStatRepository->getUserTotalLeaveDays($userIds, $vacationIds);
        }else{
            return $this->attendanceLeaveDiffStatRepository->getUserTotalLeaveHours($userIds, $vacationIds);
        }
    }
}