<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceLeaveDiffStatEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;

/**
 * 班次排班映射资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceLeaveDiffStatRepository extends BaseRepository
{
    use AttendanceTrait;

    public function __construct(AttendanceLeaveDiffStatEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getHasMoneyDataInDay($year, $month, $day, $userId, $vacationId, $hasMoney = false)
    {
        $query = $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $userId)->where('vacation_id', $vacationId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        } else {
            $query->where('has_money', 0);
        }

        return $query->first();
    }

    public function getMoreUserAttendLeaveStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_days) as days,vacation_id')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy(['user_id', 'vacation_id'])->get();
        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id][$item->vacation_id] = $item->days;
            }
        }
        return $map;
    }

    public function getMoreUserAttendLeaveStatByDate($startDate, $endDate, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_days) as days,vacation_id')->SeparateDate($startDate, $endDate)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy(['user_id', 'vacation_id'])->get();
        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id][$item->vacation_id] = $item->days;
            }
        }
        return $map;
    }

    public function getMoreUserAttendLeaveHoursStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_hours) as hours,vacation_id')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy(['user_id', 'vacation_id'])->get();
        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id][$item->vacation_id] = $item->hours;
            }
        }
        return $map;
    }

    public function getMoreUserAttendLeaveHoursStatByDate($startDate, $endDate, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_hours) as hours,vacation_id')->SeparateDate($startDate, $endDate)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy(['user_id', 'vacation_id'])->get();
        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id][$item->vacation_id] = $item->hours;
            }
        }
        return $map;
    }

    public function getMoreUserTotalAttendLeaveStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_days) as days')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy('user_id')->get();

        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id] = $item;
            }
        }

        return $map;
    }

    public function getMoreUserTotalAttendLeaveStatByDate($startDate, $endDate, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_days) as days')->SeparateDate($startDate, $endDate)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy('user_id')->get();

        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id] = $item;
            }
        }

        return $map;
    }
    public function getMoreUserOneDayStatsByDate($userIds, $date)
    {
        $stat = $this->entity->where('date',$date)->whereIn('user_id', $userIds)->get();
        return $this->arrayGroupWithKeys($stat);
    }
    public function getMoreUserAttendLeaveRecordsByDate($startDate, $endDate, $userIds)
    {
        $stat = $this->entity->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }
    public function getMoreUserTotalAttendLeaveHoursStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_hours) as hours')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy('user_id')->get();

        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id] = $item;
            }
        }

        return $map;
    }

    public function getMoreUserTotalAttendLeaveHoursStatByDate($startDate, $endDate, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_hours) as hours')->SeparateDate($startDate, $endDate)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy('user_id')->get();

        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id] = $item;
            }
        }

        return $map;
    }

    public function diffGetOutStats($year, $month, $userIds)
    {
        $outStats = $this->entity->selectRaw('user_id,year,month,day,sum(leave_days) as leave_days,sum(leave_hours) as leave_hours,has_money')
            ->where('year', $year)
            ->where('month', $month)
            ->whereIn('user_id', $userIds)
            ->groupBy(['day', 'has_money', 'user_id'])
            ->get();
        return $outStats;
    }

    public function diffGetOutStatsByDate($startDate, $endDate, $userIds)
    {
        $outStats = $this->entity->selectRaw('user_id,year,month,day,sum(leave_days) as leave_days,sum(leave_hours) as leave_hours,has_money')
            ->SeparateDate($startDate, $endDate)
            ->whereIn('user_id', $userIds)
            ->groupBy(['day', 'has_money', 'user_id'])
            ->get();
        return $outStats;
    }

    /**
     * 获取某年之后的所有请假天数
     * @param $year
     * @param $userId
     * @param bool $hasMoney
     * @return array
     */
    public function getMoreUserAttendLeaveStatAfterThisYear($year, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_days) as days,vacation_id')->where('year', '>', $year)->whereIn('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }

        $stat = $query->groupBy(['user_id', 'vacation_id'])->get();
        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id][$item->vacation_id] = $item->days;
            }
        }
        return $map;
    }

    /**
     * 获取某些用户的某些假期的所有的请假记录总和
     */
    public function getUserTotalLeaveDays($userIds, $vacationIds)
    {
        $stat = $this->entity->selectRaw('user_id,vacation_id,sum(leave_days) as days')
            ->whereIn('user_id', $userIds)
            ->whereIn('vacation_id', $vacationIds)
            ->groupBy(['user_id', 'vacation_id'])
            ->get();
        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id][$item->vacation_id] = round($item['days'], 2);
            }
        }
        return $map;
    }

    public function getUserTotalLeaveHours($userIds, $vacationIds)
    {
        $stat = $this->entity->selectRaw('user_id,vacation_id,sum(leave_hours) as hours')
            ->whereIn('user_id', $userIds)
            ->whereIn('vacation_id', $vacationIds)
            ->groupBy(['user_id', 'vacation_id'])
            ->get();
        $map = [];
        if (count($stat) > 0) {
            foreach ($stat as $item) {
                $map[$item->user_id][$item->vacation_id] = round($item['hours'], 3);
            }
        }
        return $map;
    }


    /**
     * 获取一天某种假期的记录
     * @param $date
     * @param $vacationId
     */
    public function getOneRecord($wheres)
    {
        return $this->entity->wheres($wheres)->first();
    }

    /**
     * 获取某个用户某个月的请假天数
     * @param $year
     * @param $month
     * @param $userId
     * @param bool $hasMoney
     * @return mixed
     */
    public function getUserLeaveDaysByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }
        return $query->sum('leave_days');
    }
    public function getUserLeaveHoursByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId);

        if ($hasMoney) {
            $query->where('has_money', 1);
        }
        return $query->sum('leave_hours');
    }
}