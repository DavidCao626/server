<?php
/**
 * 自定义假期天数计算方法
 *
 * 假期计算规则：参加工作时间不同的员工对应的年假余额不同
 *
 * 地址配置示例： http://127.0.0.1:8010/eoffice10/server/ext/vacation/example.php
 *
 * @author ShiQi
 *
 * @since 2018-03-13
 */

require __DIR__ . '/../../bootstrap/app.php';


class Example
{
    /**
     * 查询假期的用户id集合
     */
    private $userIds;

    /**
     * 当前日期
     * @var string
     */
    private $today;

    /**
     * 按月逐渐增加时的折扣比例
     * @var
     */
    private $percent;

    /**
     * 辅助服务类
     * @var
     */
    private $vacationStrategyService;

    public function __construct()
    {
        $this->userIds = $_REQUEST['userId'] ?? [];
        $this->today = date('Y-m-d', time());
        $this->percent = $this->getPercent($_REQUEST['month'] ?? false);
        $this->vacationStrategyService = app('App\EofficeApp\Vacation\Services\VacationStrategyService');
    }

    /**
     *响应json数据
     */
    public function response()
    {
        echo json_encode($this->calculate());
        exit();
    }

    /**
     * 计算假期余额
     * @return array
     */
    private function calculate()
    {
        if (!$this->userIds) {
            return [];
        }
        $profiles = $this->vacationStrategyService->getProfileInfo($this->userIds);
        if (!$profiles) {
            return [];
        }
        $response = [];
        foreach ($profiles as $userId => $profile) {
            if (isset($profile['work_date'])) {
                $days = $this->getVacationDays($profile['work_date']);
                $response[$userId] = round($days * $this->percent, 2);
            }
        }
        return $response;
    }

    /**
     * 假期余额和参加工作时间对应的区间
     * @param $workDate
     * @return int
     */
    private function getVacationDays($workDate)
    {
        $month = $this->diffMonth($workDate, $this->today);
        if ($month < 12) {
            //职工累计工作不满1年的，年休假0天;
            return 0;
        } elseif ($month >= 12 && $month < 120) {
            //职工累计工作已满1年不满10年的，年休假5天;
            return 5;
        } elseif ($month >= 120 && $month < 240) {
            //职工累计工作已满10年不满20年的，年休假10天;
            return 10;
        } else {
            //职工累计工作已满20年的，年休假15天。
            return 15;
        }
        return 0;
    }

    /**
     * 获取两个日期之间相差的月份
     * @param $startDate
     * @param $endDate
     * @return int
     */
    private function diffMonth($startDate, $endDate)
    {
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        if ($startDate >= $endDate) {
            return 0;
        }
        $startDate = explode('-', $startDate);
        $endDate = explode('-', $endDate);
        $diff1 = ($endDate[0] - $startDate[0]) * 12;
        $diff2 = $endDate[1] - $startDate[1];
        $diff3 = $endDate[2] >= $startDate[2] ? 0 : -1;
        return $diff1 + $diff2 + $diff3;
    }

    /**
     * 新的一年假期余额是一次性给足还是每隔m个月逐渐增加n天
     * @return int
     */
    private function getPercent($month)
    {
        $percent = 1;
        if ($month) {
            $m = intval(date('m', time()));
            $times = ceil($m / $month);
            $percent = $times / (12 / $month);
        }
        return $percent;
    }
}

$example = new Example();
$example->response();