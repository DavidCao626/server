<?php


namespace App\EofficeApp\Salary\Services;


use App\EofficeApp\Salary\Repositories\SalaryFieldHistoryRepository;

class SalaryHistoryService extends SalaryBaseService
{
    private $salaryFieldHistoryRepository;

    private $salaryFieldService;

    public function __construct(
        SalaryFieldHistoryRepository $salaryFieldHistoryRepository,
        SalaryFieldService $salaryFieldService
    )
    {
        parent::__construct();
        $this->salaryFieldHistoryRepository = $salaryFieldHistoryRepository;
        $this->salaryFieldService = $salaryFieldService;
    }

    /**
     * 查询薪酬项目
     * @param  array $param 查询条件
     * @return array 查询结果
     */
    public function getSalaryItemsHistoryList($param)
    {
        $param = $this->parseParams($param);
        if (isset($param['user_id']) && $param['user_id'] != '') {
            $userId    = $param['user_id'];
            $items = $this->salaryFieldHistoryRepository->getSalaryItems($param, true);
            foreach ($items as $key => $value) {
                if ($value['field_default_set'] == 3) {
                    $items[$key]['field_default'] = $this->salaryFieldService->getSystemData($value['field_source'], $userId);
                }
            }
            return $items;
        }
        return $this->salaryFieldHistoryRepository->getSalaryItems($param, true);
    }


}
