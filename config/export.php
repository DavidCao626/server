<?php
/**
 * 数据导出配置文件
 * !!!每条配置的key值不能重复!!!
 * 导出文件名称请在语言包的export文件里配置，导出文件后名称格式为：e-office_{文件名称}_日期时间
 * dataFrom : 数据来源，{模块service}+{service成员方法}。如exportCustomer($param)，接收一个参数。返回的数据格式为请参考尾页示例
 * fileType : 导出的文件类型，默认为excel。支持excel、eml(多个eml文件会生成一个zip文件)
 * compress : 是否将导出文件进行压缩，默认没有这个配置，配置为 true ，会压缩为一个zip进行下载
 * customCompress : 在配置了[compress]的基础上，如果配置了此数组，会在压缩时调用这个自定义函数(返回一个文件列表)，压缩时会将文件一起压缩进去
 * customCreateFile : 自定义生成导出文件的方法，{模块service}+{service成员方法}。接收两个参数。返回数组包括file_name(不包含后缀)
 * @author qishaobo
 *
 * @since  2016-06-16 创建
 */
/***
 * 导出excel文件，如果数据为拼接的html table，格式为xls， 其余为xlsx
 */
$export = [
    'exportTest'               => [
        'dataFrom' => ['App\EofficeApp\ImportExport\Tests\ExportExampleTest', 'exportTest'],
        'fileType' => '.xlsx',
    ],
    'customer'               => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\CustomerService', 'exportCustomer'],
        'fileType' => '.xlsx',
    ],
    'customerBusinessChance' => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\BusinessChanceService', 'exportBusinessChance'],
        'fileType' => '.xlsx',
    ],
    'email'                  => [
        'dataFrom' => ['App\EofficeApp\Email\Services\EmailService', 'exportEmailTest'],
        'fileType' => '.eml',
    ],
    'emails'                 => [
        'dataFrom' => ['App\EofficeApp\Email\Services\EmailService', 'downloadEml'],
        'fileType' => '.eml',
    ],
    'user'                   => [
        'dataFrom' => ['App\EofficeApp\User\Services\UserService', 'exportUser'],
        'fileType' => '.xlsx',
    ],
    'officeSuppliesPurchase' => [
        'dataFrom' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'exportOfficeSuppliesPurchase'],
        'fileType' => '.xlsx',
    ],
    'officeSuppliesApply'    => [
        'dataFrom' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'exportOfficeSuppliesApply'],
        'fileType' => '.xlsx',
    ],
    'officeSuppliesMyApply'  => [
        'dataFrom' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'exportOfficeSuppliesMyApply'],
        'fileType' => '.xlsx',
    ],
    'officeSuppliesStorage'  => [
        'dataFrom' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'exportOfficeSuppliesStorage'],
        'fileType' => '.xlsx',
    ],
    'officeSuppliesInfo'  => [
        'dataFrom' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'exportOfficeSuppliesInfo'],
        'fileType' => '.xlsx',
    ],
    'incomeExpenseStat'      => [
        'dataFrom' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'exportPlanStat'],
        'fileType' => '.xlsx',
    ],
    'incomeExpenseDiffStat'  => [
        'dataFrom' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'exportPlanDiffStat'],
        'fileType' => '.xlsx',
    ],
    'personnelFiles'         => [
        'dataFrom' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'exportPersonnelFiles'],
        'fileType' => '.xlsx',
    ],
    'attendanceSchedulingExport'       => [
        'dataFrom' => ['App\EofficeApp\Attendance\Services\AttendanceSettingService', 'exportScheduling'],
        'fileType' => '.schedule',
        'generator_type' => 0
    ],
    'attendanceHolidayExport'       => [
        'dataFrom' => ['App\EofficeApp\Attendance\Services\AttendanceSettingService', 'exportHoliday'],
        'fileType' => '.holiday',
        'generator_type' => 0
    ],
    'attendanceExport'       => [
        'dataFrom' => ['App\EofficeApp\Attendance\Services\AttendanceImportExportService', 'export'],
        'fileType' => '.xlsx'
    ],
    'performanceMonths'      => [
        'dataFrom' => ['App\EofficeApp\Performance\Services\PerformanceService', 'exportPerformanceMonths'],
        'fileType' => '.xlsx',
    ],
    'performanceSeasons'     => [
        'dataFrom' => ['App\EofficeApp\Performance\Services\PerformanceService', 'exportPerformanceSeasons'],
        'fileType' => '.xlsx',
    ],
    'performanceHalfYears'   => [
        'dataFrom' => ['App\EofficeApp\Performance\Services\PerformanceService', 'exportPerformanceHalfYears'],
        'fileType' => '.xlsx',
    ],
    'performanceYears'       => [
        'dataFrom' => ['App\EofficeApp\Performance\Services\PerformanceService', 'exportPerformanceYears'],
        'fileType' => '.xlsx',
    ],
    'exportAddress'          => [
        'dataFrom' => ['App\EofficeApp\Address\Services\AddressService', 'exportAddress'],
        'fileType' => '.xlsx',
    ],
    'flowSearch'             => [
        'dataFrom' => ['App\EofficeApp\Flow\Services\FlowExportService', 'exportFlowSearchData'],
    	'generator_type' => 1,
    	'compress' => true,
        'compressVerify' => ['App\EofficeApp\Flow\Services\FlowExportService', 'verifyExportFlowSearchDataCompress'],
        'customCompress' => ['App\EofficeApp\Flow\Services\FlowExportService', 'exportFlowSearchDataAndAttachment'],
    	'fileType' => '.xls',
    ],
    'flowOverTime'           => [
        'dataFrom' => ['App\EofficeApp\Flow\Services\FlowExportService', 'exportFlowOverTimeData'],
        'fileType' => '.xlsx',
    ],
    'flowSupervise'          => [
        'dataFrom' => ['App\EofficeApp\Flow\Services\FlowExportService', 'exportFlowSuperviseData'],
        'fileType' => '.xls',
    ],
    'flowCopy'               => [
        'dataFrom' => ['App\EofficeApp\Flow\Services\FlowExportService', 'exportFlowCopyData'],
        'fileType' => '.xls',
    ],
    'taskReport'             => [
        'dataFrom' => ['App\EofficeApp\Task\Services\TaskService', 'exportTaskReport'],
        'fileType' => '.xlsx',
    ],
    'taskSubordinate'        => [
        'dataFrom' => ['App\EofficeApp\Task\Services\TaskService', 'exportTaskSubordinate'],
        'fileType' => '.xlsx',
    ],

    'dingtalkExport'         => [
        'dataFrom' => ['App\EofficeApp\Dingtalk\Services\DingtalkService', 'dingtalkExport'],
        'fileType' => '.xlsx',
    ],

    'chargeList'             => [
        'dataFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'chargeListExport'],
        'fileType' => '.xlsx',
    ],
    'chargeHtml'             => [
        'dataFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'chargeHtmlExport'],
        'fileType' => '.xls',
    ],
    'chargeStatistics'       => [
        'dataFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'chargeStatisticsExport'],
        'fileType' => '.xlsx',
    ],
    'chargeDetails'          => [
        'dataFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'chargeDetailsExport'],
        'fileType' => '.xlsx',
    ],
    'vacation'               => [
        'dataFrom' => ['App\EofficeApp\Vacation\Services\VacationService', 'userVacationExport'],
        'fileType' => '.xlsx',
    ],
    'salaryReport'           => [
        'dataFrom' => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'getSalaryReportsExport'],
        'fileType' => '.xls',
    ],
    'salaryReportReport'     => [
        'dataFrom' => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'exportSalaryReport'],
        'fileType' => '.xls',
    ],
    'mySalaryReport'         => [
        'dataFrom' => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'mySalaryReport'],
        'fileType' => '.xls',
    ],
    'diaryReport'            => [
        'dataFrom' => ['App\EofficeApp\Diary\Services\DiaryService', 'getDiaryReport'],
        'fileType' => '.xlsx',
    ],
    'diaryList'              => [
        'dataFrom' => ['App\EofficeApp\Diary\Services\DiaryService', 'exportDiarys'],
        'fileType' => '.xlsx',
    ],
    'vehiclesAnalysis'       => [
        'dataFrom' => ['App\EofficeApp\Vehicles\Services\VehiclesService', 'exportVehiclesAnalysisData'],
        'fileType' => '.xlsx',
    ],
    'register'               => [
        'dataFrom' => ['App\EofficeApp\Empower\Services\EmpowerService', 'getEmpowerData'],
        'fileType' => '.xlsx',
    ],
    'reportExport'           => [
        'dataFrom' => ['App\EofficeApp\Report\Services\ReportService', 'exportReportData'],
        'fileType' => '.xlsx',
    ],
    'incomeExpense'           => [
        'dataFrom' => ['App\EofficeApp\IncomeExpense\Services\IncomeExpenseService', 'exportData'],
        'fileType' => '.xlsx',
    ],
    'vote'                   => [
        'dataFrom' => ['App\EofficeApp\Vote\Services\VoteService', 'exportVoteData'],
        'fileType' => '.xlsx',
    ],
    'systemLog'              => [
        'dataFrom' => ['App\EofficeApp\System\Log\Services\LogService', 'exportSystemLog'],
        'fileType' => '.xlsx',
    ],
    'exportInventoryData'    => [
        'dataFrom' => ['App\EofficeApp\Storage\Services\StorageService', 'exportInventoryData'],
        'fileType' => '.xlsx',
    ],
    'product'                => [
        'dataFrom' => ['App\EofficeApp\Product\Services\ProductService', 'exportProduct'],
        'fileType' => '.xlsx',
    ],
    'exportLangPackage'     => [
        'dataFrom' => ['App\EofficeApp\Lang\Services\LangService', 'exportLangPackage'],
        'fileType' => '.xlsx',
    ],
    'assetsStorage'          => [
        'dataFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'exportStorage'],
        'fileType' => '.xlsx',
    ],
    'assetsApply'            => [
        'dataFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'exportApply'],
        'fileType' => '.xlsx',
    ],
    'assetsExportRet'            => [
        'dataFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'assetsExportRet'],
        'fileType' => '.xlsx',
    ],
    'assetsAccount'          => [
        'dataFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'exportAccount'],
        'fileType' => '.xlsx',
    ],
    'assetsInventory'        => [
        'dataFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'exportInventory'],
        'fileType' => '.xlsx',
    ],

    'assetsApproval'         => [
        'dataFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'exportApproval'],
        'fileType' => '.xlsx',
    ],
    'assetsRepair'           => [
        'dataFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'exportRepair'],
        'fileType' => '.xlsx',
    ],
    'contractSettlement'     => [
        'dataFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'exportSettlement'],
        'fileType' => '.xlsx',
    ],
    'contractStatistical'    => [
        'dataFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'exportStatistical'],
        'fileType' => '.xlsx',
    ],
    'contractManager'        => [
        'dataFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'exportManager'],
        'fileType' => '.xlsx',
    ],
    'meeting'                => [
        'dataFrom' => ['App\EofficeApp\Meeting\Services\MeetingService', 'exportMeetingRecord'],
        'fileType' => '.xlsx',
    ],
    'meetingDetail'          => [
        'dataFrom' => ['App\EofficeApp\Meeting\Services\MeetingService', 'exportMeetingDetailInfo'],
        'fileType' => '.xlsx',
    ],
    'meetingApproval'        => [
        'dataFrom' => ['App\EofficeApp\Meeting\Services\MeetingService', 'exportMeetingMyApproval'],
        'fileType' => '.xlsx',
    ],
    'contactRecords'        => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\ContactRecordService', 'exportContactRecords'],
        'fileType' => '.xlsx',
    ],
    'suppliers'             => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\SupplierService', 'exportSuppliers'],
        'fileType' => '.xlsx',
    ],
    'customerLinkman'       => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\LinkmanService', 'exportLinkman'],
        'fileType' => '.xlsx',
    ],
    'exportScheduleViewList' => [
        'dataFrom' => ['App\EofficeApp\Calendar\Services\CalendarRecordService', 'exportScheduleViewList'],
        'fileType' => '.xls',
    ],
    'customerProduct'       => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\ProductService', 'exportProduct'],
        'fileType' => '.xlsx',
    ],
    'customerContract'      => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\ContractService', 'exportContract'],
        'fileType' => '.xlsx',
    ],
    'projectMemberReport'                   => [
        'dataFrom' => ['App\EofficeApp\Project\Services\ProjectService', 'exportProjectMembersReport'],
        'fileType' => '.xls',
    ],
    'projectReport'                   => [
        'dataFrom' => ['App\EofficeApp\Project\Services\ProjectService', 'exportProjectReport'],
        'fileType' => '.xls',
    ],
    'project'                   => [
        'dataFrom' => ['App\EofficeApp\Project\Services\ProjectService', 'exportProject'],
        'fileType' => '.xls',
    ],
    'projectTask'                   => [
        'dataFrom' => ['App\EofficeApp\Project\Services\ProjectService', 'exportProjectTask'],
        'fileType' => '.xlsx',
    ],
    'storageStock'          => [
        'dataFrom' => ['App\EofficeApp\Storage\Services\StorageService', 'exportStock'],
        'fileType' => '.xlsx',
    ],
    'invoice' => [
        'dataFrom' => ['App\EofficeApp\Invoice\Services\InvoiceService', 'exportInvoice'],
        'fileType' => '.xlsx',
    ],
    'contractExportOrder'          => [
        'dataFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'contractExportOrder'],
        'fileType' => '.xlsx',
    ],
    'contractExportRemind'          => [
        'dataFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'contractExportRemind'],
        'fileType' => '.xlsx',
    ],
    'book'          => [
        'dataFrom' => ['App\EofficeApp\Book\Services\BookService', 'exportBook'],
        'fileType' => '.xlsx',

    ],
    'formModeling'          => [
        'dataFrom' => ['App\EofficeApp\FormModeling\Services\FormModelingService', 'export'],
        'fileType' => '.xlsx',

    ],
];
// 合并第三方二开的导出配置
if (file_exists(config_path('third_export.php'))) {
    return $export + include 'third_export.php';
}
return $export;

/*

数据来源格式:

type 1: 1个sheet的excel
[
'header'    =>  [
'customer_name'             => '客户名称',
'phone_number'              => '电话号码',
],
'data'      =>  [
[
'customer_name'             => '111',
'phone_number'              => '222',
],
[
'customer_name'             => '333',
'phone_number'              => '444',
],
]
]

type 2: 多个sheet的excel
[
0 => [
'sheetName' => 'sheet1',
'header'    =>  [
'customer_name'             => '客户名称',
'phone_number'              => '电话号码',
],
'data'      =>  [
[
'customer_name'             => '111',
'phone_number'              => '222',
],
[
'customer_name'             => '334',
'phone_number'              => '555',
],
]
],
1 => [
'sheetName' => 'sheet2',
'header'    =>  [
'fax_no'                    => '传真号码',
'website'                   => '公司网址',
],
'data'      =>  [
[
'fax_no'                    => 'aaaa',
'website'                   => 'bbbb',
],
[
'fax_no'                    => 'cccc',
'website'                   => 'dddd',
],
]
]
];

type 3: excel字符串
'<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
<table width="100%" style="border-collapse:collapse;font-size:12px;" border="1" cellspacing="0" cellpadding="3" bordercolor="#000000">
<tr style="background: #31309c; color: #FFFFFF; font-weight: bold;">
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
</tr>
<tr>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
</tr>
</table>
</html>'

type 3: 字符串(excel、eml)
'<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
<table width="100%" style="border-collapse:collapse;font-size:12px;" border="1" cellspacing="0" cellpadding="3" bordercolor="#000000">
<tr style="background: #31309c; color: #FFFFFF; font-weight: bold;">
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
<td nowrap>112233</td>
</tr>
<tr>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
<td nowrap style="vnd.ms-excel.numberformat:@">888999</td>
</tr>
</table>
</html>'

type 4: 多个字符串的数组(多个eml)

 */
