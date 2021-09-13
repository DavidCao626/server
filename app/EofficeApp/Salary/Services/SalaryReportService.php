<?php


namespace App\EofficeApp\Salary\Services;


use App\EofficeApp\Menu\Services\UserMenuService;
use App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultHistoryRepository;
use App\EofficeApp\Salary\Repositories\SalaryReportRepository;
use App\EofficeApp\Salary\Repositories\SalaryRepository;
use Eoffice;
use Illuminate\Database\Eloquent\Collection;

class SalaryReportService extends SalaryBaseService
{

    private $salaryReportSetService;

    private $salaryFieldService;

    public $salaryAdjustService;

    private $salaryReportRepository;

    private $userMenuService;

    public $salaryRepository;

    private $salaryHistoryService;

    public $salaryFieldPersonalDefaultHistoryRepository;

    public function __construct(
        SalaryReportSetService $salaryReportSetService,
        SalaryFieldService $salaryFieldService,
        SalaryAdjustService $salaryAdjustService,
        SalaryReportRepository $salaryReportRepository,
        UserMenuService $userMenuService,
        SalaryRepository $salaryRepository,
        SalaryHistoryService $salaryHistoryService,
        SalaryFieldPersonalDefaultHistoryRepository $salaryFieldPersonalDefaultHistoryRepository
    )
    {
        parent::__construct();
        $this->salaryReportSetService = $salaryReportSetService;
        $this->salaryFieldService = $salaryFieldService;
        $this->salaryAdjustService = $salaryAdjustService;
        $this->salaryReportRepository = $salaryReportRepository;
        $this->userMenuService = $userMenuService;
        $this->salaryRepository = $salaryRepository;
        $this->salaryHistoryService = $salaryHistoryService;
        $this->salaryFieldPersonalDefaultHistoryRepository = $salaryFieldPersonalDefaultHistoryRepository;
    }

    /**
     * 上报列表
     * @param $param
     * @return array
     */
    public function getSalaryReportList($param)
    {
        $param = $this->parseParams($param);
        return $this->response($this->salaryReportRepository, 'getTotal', 'getSalaryReportList', $param);
    }

    /**
     * 新建薪酬上报流程
     * @param $data
     * @param string $userId
     * @return array|bool
     */
    public function createSalaryReport($data, $userId = 'admin')
    {
        if ($data['end_date'] < $data['start_date']) {
            return ['code' => ['0x038001', 'salary']];
        }

        $currentDate = date("Y-m-d");

        if ($data['end_date'] < $currentDate) {
            return ['code' => ['0x038002', 'salary']];
        }

        if($this->existUnfinishedReport()){
            return ['code' => ['0x038021', 'salary']];
        }

        $data['status'] = 1;

        if (empty($data['title'])) {
            $data['title'] = date('Y') . trans('common.year') . date('m') . trans('salary.month') . trans('salary.salary_report');
        }
        [$data['year'], $data['month']] = explode('-', $data['report_year_and_month']);
        unset($data['report_year_and_month']);

        $result = $this->salaryReportRepository->insertData($data);

        if ($result) {
            if ($data['start_date'] <= $currentDate) {
                $this->salaryMessage($data['title'], $result['report_id'], false);
            }

            $this->salaryFieldService->createHistoryData($result['report_id']);

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 发送新建薪资上报消息提醒
     * @param $title
     * @param $reportId
     * @param bool $flag
     * @return bool
     */
    public function salaryMessage($title, $reportId, $flag = true)
    {
        $hasPermissionUserIds = $this->userMenuService->getMenuRoleUserbyMenuId(19);

        if (!empty($hasPermissionUserIds)) {
            $sendData['remindMark']   = 'salary-start';
            $sendData['toUser']       = implode(',', $hasPermissionUserIds);
            $sendData['contentParam'] = ['flowRemark' => $title];
            $sendData['stateParams']  = ['report_id' => $reportId];
            if($flag){
                return $sendData;
            }else{
                Eoffice::sendMessage($sendData);
                return true;
            }
        }

        return false;
    }

    /**
     * 终止薪酬上报流程
     * @param $reportId
     * @return bool
     */
    public function stopSalaryReport($reportId)
    {
        $data  = ['status' => 2];
        $where = ['report_id' => $reportId];
        return $this->salaryReportRepository->updateData($data, $where);
    }

    /**
     * 删除薪酬上报流程
     * @param int|string $reportId 薪酬上报流程id,多个用逗号隔开
     * @return mixed
     */
    public function deleteSalaryReport($reportId)
    {
        $reportIds = array_filter(explode(',', $reportId));
        /** @var Collection $reports */
        $reports = $this->salaryReportRepository->entity
            ->select('report_id')->find($reportIds);

        foreach($reports as $report){
            $report->payDetails()->delete();
            $report->salaries()->delete();
            $report->personalDefaultHistories()->delete();
            $report->fieldHistories()->delete();
            $report->delete();
        }

        return true;
    }

    /**
     * 修改上报日期
     * @param $data
     * @param $reportId
     * @return bool
     */
    public function editSalaryReportsDate($data, $reportId)
    {
        $date = explode('-', $data['yearAndMonth']);
        $data = [
            'year' => $date[0],
            'month' => $date[1]
        ];
        $where = ['report_id' => $reportId];

        return $this->salaryReportRepository->updateData($data, $where);
    }

    public function existUnfinishedReport()
    {
        $date = date('Y-m-d');

        return (bool) $this->salaryReportRepository->entity
            ->where('status', 1)
            ->where('end_date', '>=', $date)
            ->first();
    }


}
