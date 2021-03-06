<?php


namespace App\EofficeApp\Salary\Controllers;


use App\EofficeApp\Salary\Exceptions\SalaryError;
use App\EofficeApp\Salary\Services\SalaryAdjustService;
use App\EofficeApp\Salary\Services\SalaryEntryService;
use App\EofficeApp\Salary\Services\SalaryFieldService;
use App\EofficeApp\Salary\Services\SalaryReportFormService;
use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Salary\Requests\SalaryRequest;
use App\EofficeApp\Salary\Services\SalaryReportService;
use App\EofficeApp\Salary\Services\SalaryReportSetService;
use App\EofficeApp\Salary\Services\SalaryBaseService;

class SalaryController extends Controller
{
    private $request;

    private $salaryService;

    private $salaryReportSetService;

    private $salaryReportService;

    private $salaryAdjustService;

    private $salaryFieldService;

    private $salaryReportFormService;

    private $salaryEntryService;

    public function __construct(
        Request $request,
        SalaryRequest $salaryRequest,
        SalaryBaseService $salaryService,
        SalaryReportSetService $salaryReportSetService,
        SalaryReportService $salaryReportService,
        SalaryAdjustService $salaryAdjustService,
        SalaryFieldService $salaryFieldService,
        SalaryReportFormService $salaryReportFormService,
        SalaryEntryService $salaryEntryService
    )
    {
        parent::__construct();
        $this->formFilter($request, $salaryRequest);
        $this->request = $request;
        $this->salaryService = $salaryService;
        $this->salaryReportSetService = $salaryReportSetService;
        $this->salaryReportService = $salaryReportService;
        $this->salaryAdjustService = $salaryAdjustService;
        $this->salaryFieldService = $salaryFieldService;
        $this->salaryReportFormService = $salaryReportFormService;
        $this->salaryEntryService = $salaryEntryService;
    }

    // ====================================================================================
    //                                      ??????????????????
    // ====================================================================================
    /**
     * ??????????????????
     */
    public function getSalaryReportSetList()
    {
        return $this->returnResult(
            $this->salaryReportSetService->getSalaryReportSetList($this->request->all())
        );
    }

    /**
     * ????????????
     */
    public function addSalaryReportSet()
    {
        return $this->returnResult(
            $this->salaryReportSetService->addSalaryReportSet($this->request->all())
        );
    }

    /**
     * ????????????
     * @param $id
     * @return array
     */
    public function editSalaryReportSet($id)
    {
        return $this->returnResult(
            $this->salaryReportSetService->editSalaryReportSet($this->request->all())
        );
    }

    /**
     * ????????????
     * @param $id
     * @return array
     */
    public function deleteSalaryReportSet($id)
    {
        return $this->returnResult(
            $this->salaryReportSetService->deleteSalaryReportSet($id)
        );
    }

    /**
     * ??????????????????
     * @param $id
     * @return array
     */
    public function getSalaryReportSetById($id)
    {
        return $this->returnResult(
            $this->salaryReportSetService->getSalaryReportSetById($id)
        );
    }


    public function getManageUser()
    {
        return $this->returnResult(
            $this->salaryReportSetService->getViewUser($this->own)
        );
    }


    // ====================================================================================
    //                                      ??????????????????
    // ====================================================================================
    /**
     * ??????????????????
     * @return array
     */
    public function addSalaryAdjust()
    {
        return $this->returnResult(
            $this->salaryAdjustService->addSalaryAdjust($this->request->all(), $this->own)
        );
    }

    /**
     * ??????????????????
     * @return array
     */
    public function getSalaryAdjust()
    {
        return $this->returnResult(
            $this->salaryAdjustService->getSalaryAdjust(
                $this->request->all(), $this->own['user_id']
            )
        );
    }

    /**
     * ?????????????????????????????????????????????
     */
    public function getSalaryAdjustOld()
    {
        return $this->returnResult(
            $this->salaryAdjustService->getSalaryAdjustOld($this->request->all())
        );
    }

    /**
     * ??????????????????????????????
     * @param $adjustId
     * @return array
     */
    public function getSalaryAdjustInfo($adjustId)
    {
        return $this->returnResult(
            $this->salaryAdjustService->getSalaryAdjustInfo($adjustId, $this->own)
        );
    }

    /**
     * ????????????????????????????????????
     * @param $userId
     * @return array
     */
    public function getLastSalaryAdjust($userId)
    {
        return $this->returnResult(
            $this->salaryAdjustService->getLastSalaryAdjust($userId, $this->request->all(), $this->own)
        );
    }


    // ====================================================================================
    //                                      ??????????????????
    // ====================================================================================

    /**
     * ????????????
     * @return array
     */
    public function getIndexSalaryReports()
    {
        return $this->returnResult(
            $this->salaryReportService->getSalaryReportList($this->request->all())
        );
    }

    /**
     * ????????????????????????
     * @return array
     */
    public function createSalaryReports()
    {
        return $this->returnResult(
            $this->salaryReportService->createSalaryReport(
                $this->request->all(), $this->own['user_id']
            )
        );
    }

    /**
     * ????????????????????????
     * @param $reportId
     * @return array
     */
    public function editSalaryReports($id)
    {
        return $this->returnResult(
            $this->salaryReportService->stopSalaryReport($id)
        );
    }

    /**
     * ????????????????????????
     * @param $reportId
     * @return array
     */
    public function deleteSalaryReports($id)
    {
        return $this->returnResult(
            $this->salaryReportService->deleteSalaryReport($id)
        );
    }

    /**
     * ??????????????????
     * @param $reportId
     * @return array
     */
    public function editSalaryReportsDate($id)
    {
        return $this->returnResult(
            $this->salaryReportService->editSalaryReportsDate(
                $this->request->all(), $id
            )
        );
    }


    // ====================================================================================
    //                                      ??????????????????
    // ====================================================================================
    /**
     * ?????????????????????????????????????????????
     * @param $userId
     * @param $reportId
     * @return array
     */
    public function isSalaryReport($userId, $reportId)
    {
        return $this->returnResult(
            $this->salaryEntryService->isSalaryReport($userId, $reportId, $this->own)
        );
    }

    /**
     * ????????????-??????
     * @return array
     */
    public function inputUserSalary()
    {
        return $this->returnResult(
            $this->salaryEntryService->inputUserSalary($this->request->all(), $this->own)
        );
    }

    /**
     * ????????????-??????????????????
     * @return array
     */
    public function multiReportedUserSalary()
    {
        return $this->returnResult(
            $this->salaryEntryService->multiReportedUserSalary($this->request->all(), $this->own)
        );
    }

    /**
     * ?????????????????????????????????????????????
     * @param $reportId
     * @return array
     */
    public function getEntryFieldLists($reportId)
    {
        return $this->returnResult(
            $this->salaryEntryService->getEntryFieldLists($reportId)
        );
    }

    /**
     * @param $reportId
     * @param $userId
     * @return array
     * @throws SalaryError
     */
    public function getEntryValuesWithNoInput($reportId, $userId)
    {
        $this->salaryReportSetService->checkReportUserPermission($userId, $this->own);

        return $this->returnResult(
            $this->salaryEntryService->getUserSalaryDetailWithNoInput($userId, $reportId)
        );
    }

    /**
     * ??????????????????????????????
     * @param $reportId
     * @param $userId
     * @return array
     * @throws SalaryError
     */
    public function getEntryValuesWithInput($reportId, $userId)
    {
        return $this->returnResult(
            $this->salaryEntryService->getUserSalaryDetailWithInput($userId, $reportId, $this->request['entry_values'])
        );
    }

    /**
     * ??????????????????????????????????????????????????????(??????)?????????????????????????????????????????????
     * @param  [type] $reportId [description]
     * @param  [type] $userId   [description]
     * @return [type]           [description]
     */
    public function getDeptEntryInitValues($reportId, $deptId)
    {
        return $this->returnResult(
            $this->salaryEntryService->getDeptEntryInitValues($reportId, $deptId, $this->own)
        );
    }

    // ====================================================================================
    //                                      ??????????????????
    // ====================================================================================
    /**
     * ??????????????????????????????????????????
     * @param $reportId
     * @return array
     */
    public function getIndexSalaryReportsEmployees($reportId)
    {
        return $this->returnResult(
            $this->salaryReportFormService->getEmployeeSalaryList(
                $reportId, $this->own, $this->request->all()
            )
        );
    }

    /**
     * ?????????????????????-????????????
     * @return array
     */
    public function getUserSalaryDetailByMobile()
    {
        return $this->returnResult(
            $this->salaryReportFormService->getUserSalaryDetailByMobile(
                $this->own['user_id'], $this->request->input('report_id')
            )
        );
    }

    /**
     * ????????????????????????
     * @return array
     */
    public function getMySalaryList()
    {
        return $this->returnResult(
            $this->salaryReportFormService->getMySalaryList(
                $this->own['user_id'], $this->request->all()
            )
        );
    }

    /**
     * ????????????????????????????????????
     * @param $userId
     * @return array
     */
    public function getSalaryForm($userId)
    {
        return $this->returnResult(
            $this->salaryReportFormService->getSalaryForm(
                $userId, $this->own, $this->request->all()
            )
        );
    }



    /**
     * ???????????????????????????
     * @return array
     */
    public function getMySalaryListByMobile()
    {
        return $this->returnResult(
            $this->salaryReportFormService->getMySalaryListByMobile($this->own['user_id'], $this->request->all())
        );
    }

    /**
     * ????????????
     * @return array
     */
    public function getSalaryReport()
    {
        return $this->returnResult(
            $this->salaryReportFormService->getSalaryReport($this->request->all(), $this->own)
        );
    }



    // ====================================================================================
    //                                      ???????????????
    // ====================================================================================
    /**
     * ??????????????????
     * @return array
     */
    public function getIndexSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryItemsList($this->request->all())
        );
    }

    /**
     * ????????????????????????
     * @return array
     */
    public function getNumericSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getNumericSalaryItems($this->request->all())
        );
    }

    /**
     * ??????????????????????????????????????????
     * @return array
     */
    public function getAllSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getAllSalaryItems($this->request->all())
        );
    }

    /**
     * ????????????????????????????????????
     * @return array
     */
    public function getMaxSort()
    {
        return $this->returnResult(
            $this->salaryFieldService->getMaxSort()
        );
    }

    /**
     * ????????????-???????????????
     * @return array
     */
    public function getDeletedSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getDeletedSalaryItems($this->request->all())
        );
    }

    /**
     * ???????????????????????????????????????????????????????????????
     * @param $field_id
     * @return array
     */
    public function getIndexSalaryItemsByFieldId($fieldId)
    {
        return $this->returnResult(
            $this->salaryFieldService->getIndexSalaryItemsByFieldId($fieldId)
        );
    }

    /**
     * ???????????????
     * @return array
     */
    public function addSalary()
    {
        return $this->returnResult(
            $this->salaryFieldService->addSalary($this->request->all())
        );
    }

    /**
     * ??????????????????
     * @param $field_id
     * @return array
     */
    public function editSalaryInfo($fieldId)
    {
        return $this->returnResult(
            $this->salaryFieldService->editSalaryInfo($this->request->all(), $fieldId)
        );
    }

    /**
     * ??????????????????
     * @param $field_id
     * @return array
     */
    public function deleteSalaryField($fieldId)
    {
        return $this->returnResult(
            $this->salaryFieldService->deleteSalary($fieldId)
        );
    }

    /**
     * ??????????????????????????????
     * @param $field_id
     * @return array
     */
    public function forceDeleteSalary($fieldId)
    {
        return $this->returnResult(
            $this->salaryFieldService->forceDeleteSalary($fieldId)
        );
    }

    /**
     * ?????????????????????????????????-?????????-???????????????????????????
     * @return array
     */
    public function getCalculateData()
    {
        return $this->returnResult(
            $this->salaryFieldService->getCalculateData()
        );
    }

    /**
     * ????????????????????????
     * @return array
     */
    public function getCalculateDetail()
    {
        return $this->returnResult(
            $this->salaryFieldService->getCalculateDetail($this->request->all())
        );
    }

    /**
     * ??????????????????????????????
     * @return array
     */
    public function getSalaryFieldsManageList()
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryFieldsManageList($this->request->all())
        );
    }

    /**
     * ??????????????????????????????
     * @return array
     */
    public function getSalaryFieldsParent()
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryFieldsParent($this->request->all())
        );
    }

    /**
     * ???????????????
     * @return array
     */
    public function salaryFieldMove()
    {
        return $this->returnResult(
            $this->salaryFieldService->salaryFieldMove($this->request->all())
        );
    }

    /**
     * ????????????????????????????????????
     * @return array
     */
    public function salaryFieldCanAdjust()
    {
        return $this->returnResult(
            $this->salaryFieldService->salaryFieldCanAdjust($this->request->all())
        );
    }

    /**
     * ?????????????????????????????????????????????????????????????????????
     * @return array
     */
    public function getSalaryFieldsInCount()
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryFieldsInCount($this->request->all())
        );
    }

    /**
     * @param $fieldId
     * @return array
     * @throws SalaryError
     */
    public function getSalaryPersonalDefaultList($fieldId)
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryPersonalDefaultList($fieldId, $this->request->all(), $this->own)
        );
    }

    /**
     * ?????????????????????
     * @param $fieldId
     * @return mixed
     * @throws SalaryError
     */
    public function setSalaryPersonalDefault($fieldId)
    {
        return $this->returnResult(
            $this->salaryFieldService->setSalaryPersonalDefault($fieldId, $this->request->all(), $this->own)
        );
    }

    public function getUserPersonalDefault($fieldId, $userId)
    {
        return $this->returnResult(
            $this->salaryFieldService->getUserPersonalDefault($fieldId, $userId)
        );
    }

    // ??????????????????????????????
    public function getSalaryBaseSet()
    {
        return $this->returnResult(
            $this->salaryService->getSalaryBaseSet()
        );
    }

    // ??????????????????????????????
    public function saveSalaryBaseSet()
    {
        return $this->returnResult(
            $this->salaryService->saveSalaryBaseSet($this->request->all())
        );
    }

}
