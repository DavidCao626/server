<?php


namespace App\EofficeApp\Salary\Services;


use App\EofficeApp\Salary\Repositories\SalaryReportRepository;

class SalaryRemindService extends SalaryBaseService
{
    private $salaryReportRepository;

    private $salaryReportService;

    public function __construct(
        SalaryReportRepository $salaryReportRepository,
        SalaryReportService $salaryReportService
    )
    {
        $this->salaryReportRepository = $salaryReportRepository;
        $this->salaryReportService = $salaryReportService;
    }

    /**
     * @return array
     */
    public function salaryReportStartRemind()
    {
        $currentDate = date('Y-m-d');
        $where       = ['start_date' => [$currentDate], 'status' => [1]];
        $reportList  = $this->salaryReportRepository->getSalaryReportList(['search' => $where]);
        $message     = [];
        if (count($reportList)) {
            foreach ($reportList as $key => $value) {
                $createdDate = date('Y-m-d', strtotime($value['created_at']));
                if ($value['start_date'] > $createdDate) {
                    $sendData = $this->salaryReportService->salaryMessage($value['title'], $value['report_id']);
                    if($sendData){
                        $message[] = $sendData;
                    }
                }
            }
        }

        return $message;
    }
}
