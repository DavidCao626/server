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
    //                                      薪资权限相关
    // ====================================================================================
    /**
     * 权限设置列表
     */
    public function getSalaryReportSetList()
    {
        return $this->returnResult(
            $this->salaryReportSetService->getSalaryReportSetList($this->request->all())
        );
    }

    /**
     * 新增权限
     */
    public function addSalaryReportSet()
    {
        return $this->returnResult(
            $this->salaryReportSetService->addSalaryReportSet($this->request->all())
        );
    }

    /**
     * 编辑权限
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
     * 删除权限
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
     * 查看权限详情
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
    //                                      薪资调整相关
    // ====================================================================================
    /**
     * 新建薪酬调整
     * @return array
     */
    public function addSalaryAdjust()
    {
        return $this->returnResult(
            $this->salaryAdjustService->addSalaryAdjust($this->request->all(), $this->own)
        );
    }

    /**
     * 薪酬调整列表
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
     * 调整前薪资，表单系统数据中使用
     */
    public function getSalaryAdjustOld()
    {
        return $this->returnResult(
            $this->salaryAdjustService->getSalaryAdjustOld($this->request->all())
        );
    }

    /**
     * 获取一条薪资调整记录
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
     * 获取最新一条薪资调整记录
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
    //                                      薪资上报相关
    // ====================================================================================

    /**
     * 上报列表
     * @return array
     */
    public function getIndexSalaryReports()
    {
        return $this->returnResult(
            $this->salaryReportService->getSalaryReportList($this->request->all())
        );
    }

    /**
     * 新建薪酬上报流程
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
     * 终止薪酬上报流程
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
     * 删除薪酬上报流程
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
     * 修改上报年月
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
    //                                      薪资录入相关
    // ====================================================================================
    /**
     * 录入时获取当前用户薪资是否上报
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
     * 薪资录入-上报
     * @return array
     */
    public function inputUserSalary()
    {
        return $this->returnResult(
            $this->salaryEntryService->inputUserSalary($this->request->all(), $this->own)
        );
    }

    /**
     * 薪资录入-部门批量上报
     * @return array
     */
    public function multiReportedUserSalary()
    {
        return $this->returnResult(
            $this->salaryEntryService->multiReportedUserSalary($this->request->all(), $this->own)
        );
    }

    /**
     * 获取上报的薪资项（不含父级项）
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
     * 上报输入时获取薪资值
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
     * 按部门上报，初始化，获取：部门下人员(递归)；每个人的薪资值；已填报的值；
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
    //                                      薪资报表相关
    // ====================================================================================
    /**
     * 查询某次上报记录员工薪酬报表
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
     * 移动端我的薪资-薪资详情
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
     * 查询我的薪酬列表
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
     * 人事卡片页面获取薪资报表
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
     * 移动端我的薪酬列表
     * @return array
     */
    public function getMySalaryListByMobile()
    {
        return $this->returnResult(
            $this->salaryReportFormService->getMySalaryListByMobile($this->own['user_id'], $this->request->all())
        );
    }

    /**
     * 薪资报表
     * @return array
     */
    public function getSalaryReport()
    {
        return $this->returnResult(
            $this->salaryReportFormService->getSalaryReport($this->request->all(), $this->own)
        );
    }



    // ====================================================================================
    //                                      薪资项相关
    // ====================================================================================
    /**
     * 获取薪酬项目
     * @return array
     */
    public function getIndexSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryItemsList($this->request->all())
        );
    }

    /**
     * 获取数值型薪资项
     * @return array
     */
    public function getNumericSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getNumericSalaryItems($this->request->all())
        );
    }

    /**
     * 薪资管理薪资项中间列模糊查询
     * @return array
     */
    public function getAllSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getAllSalaryItems($this->request->all())
        );
    }

    /**
     * 新建薪资项，获取默认序号
     * @return array
     */
    public function getMaxSort()
    {
        return $this->returnResult(
            $this->salaryFieldService->getMaxSort()
        );
    }

    /**
     * 薪资管理-回收站列表
     * @return array
     */
    public function getDeletedSalaryItems()
    {
        return $this->returnResult(
            $this->salaryFieldService->getDeletedSalaryItems($this->request->all())
        );
    }

    /**
     * 薪资调整获取薪资项调整前值；某个薪资项详情
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
     * 新建薪资项
     * @return array
     */
    public function addSalary()
    {
        return $this->returnResult(
            $this->salaryFieldService->addSalary($this->request->all())
        );
    }

    /**
     * 编辑薪酬项目
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
     * 删除薪酬项目
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
     * 回收站彻底删除薪资项
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
     * 可选的计算数据，薪资项-预设值-系统数据选择器使用
     * @return array
     */
    public function getCalculateData()
    {
        return $this->returnResult(
            $this->salaryFieldService->getCalculateData()
        );
    }

    /**
     * 获取系统数据详情
     * @return array
     */
    public function getCalculateDetail()
    {
        return $this->returnResult(
            $this->salaryFieldService->getCalculateDetail($this->request->all())
        );
    }

    /**
     * 薪资管理薪资项中间列
     * @return array
     */
    public function getSalaryFieldsManageList()
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryFieldsManageList($this->request->all())
        );
    }

    /**
     * 父级薪资项选择器接口
     * @return array
     */
    public function getSalaryFieldsParent()
    {
        return $this->returnResult(
            $this->salaryFieldService->getSalaryFieldsParent($this->request->all())
        );
    }

    /**
     * 薪资项移动
     * @return array
     */
    public function salaryFieldMove()
    {
        return $this->returnResult(
            $this->salaryFieldService->salaryFieldMove($this->request->all())
        );
    }

    /**
     * 薪资调整外发，薪资项下拉
     * @return array
     */
    public function salaryFieldCanAdjust()
    {
        return $this->returnResult(
            $this->salaryFieldService->salaryFieldCanAdjust($this->request->all())
        );
    }

    /**
     * 获取可参与计算的薪资项，薪资项计算公式控件使用
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
     * 设置个人基准值
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

    // 薪酬，基础设置，获取
    public function getSalaryBaseSet()
    {
        return $this->returnResult(
            $this->salaryService->getSalaryBaseSet()
        );
    }

    // 薪酬，基础设置，保存
    public function saveSalaryBaseSet()
    {
        return $this->returnResult(
            $this->salaryService->saveSalaryBaseSet($this->request->all())
        );
    }

}
