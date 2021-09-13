<?php

/**
 * 数据导入配置文件
 * !!!每条配置的key值不能重复!!!
 * 数据导入模板文件名称请在语言包的import文件里配置
 * fieldsFrom：导入字段，{模块service}+{service成员方法}。返回的数据格式为请参考尾页示例
 * dataSubmit：导入数据，{模块service}+{service成员方法}。如importCustomer($data, $param)方法，接收两个参数
 *
 * @author qishaobo
 *
 * @since  2016-06-16 创建
 */
$import = [
	'customer' => [
		'fieldsFrom' => ['App\EofficeApp\Customer\Services\CustomerService', 'getImportFields','data'],
		'dataSubmit' => ['App\EofficeApp\Customer\Services\CustomerService', 'importCustomer'],
        'filter'     => ['App\EofficeApp\Customer\Services\CustomerService', 'importFilter'],
        'after'      => ['App\EofficeApp\Customer\Services\CustomerService', 'afterImport'],
        'primarys'   => ['customer_name', 'customer_number'],
	],
    'customer-linkman' => [
        'fieldsFrom' => ['App\EofficeApp\Customer\Services\LinkmanService', 'getImportFields','data'],
        'dataSubmit' => ['App\EofficeApp\Customer\Services\LinkmanService', 'importLinkman'],
        'filter'     => ['App\EofficeApp\Customer\Services\LinkmanService', 'importFilter'],
        'after'      => ['App\EofficeApp\Customer\Services\LinkmanService', 'afterImport'],
    ],
    'customer-supplier' => [
        'fieldsFrom' => ['App\EofficeApp\Customer\Services\SupplierService', 'getImportFields','data'],
        'dataSubmit' => ['App\EofficeApp\Customer\Services\SupplierService', 'importSupplier'],
        'filter'     => ['App\EofficeApp\Customer\Services\SupplierService', 'importFilter'],
        'after'      => ['App\EofficeApp\Customer\Services\SupplierService', 'afterImport'],
    ],
    'salary' => [
        'fieldsFrom' => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'getSalaryTemplateData', 'userInfo', 'params'],
        'dataSubmit' => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'importSalary'],
        'filter'     => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'importSalaryFilter'],
        'primarys'   => ['user_id'],
        'startRow'   => [3]
    ],
    'salaryPersonalDefault' => [
        'fieldsFrom' => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'getSalaryPersonalDefaultTemplateData', 'userInfo', 'params'],
        'dataSubmit' => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'importSalaryPersonalDefault'],
        'filter'     => ['App\EofficeApp\Salary\Services\SalaryImportExportService', 'importSalaryPersonalDefaultFilter'],
        'primarys'   => ['user_id']
    ],
    'book' => [
        'fieldsFrom' => ['App\EofficeApp\Book\Services\BookService', 'getImportBookFields','data'],
        'dataSubmit' => ['App\EofficeApp\Book\Services\BookService', 'importBook'],
        'filter'     => ['App\EofficeApp\Book\Services\BookService', 'importBookFilter'],
        'primarys'   => ['book_name', 'author'],
    ],
    'personnelFiles' => [
        'fieldsFrom' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'getImportPersonnelFilesFields','data'],
        'dataSubmit' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'importPersonnelFiles'],
        'filter'     => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'importPersonnelFilesFilter'],
        'primarys'   => ['no']
    ],
    'importPublicAddress' => [
        'fieldsFrom' => ['App\EofficeApp\Address\Services\AddressService', 'getPublicAddressTemplate', 'userInfo', 'params'],
        'dataSubmit' => ['App\EofficeApp\Address\Services\AddressService', 'importPublicAddress'],
        'filter'     => ['App\EofficeApp\Address\Services\AddressService', 'importPublicAddressFilter'],
        'primarys'   => ['primary_1']
    ],
    'importPersonalAddress' => [
        'fieldsFrom' => ['App\EofficeApp\Address\Services\AddressService', 'getPersonalAddressTemplate', 'userInfo', 'params'],
        'dataSubmit' => ['App\EofficeApp\Address\Services\AddressService', 'importPersonalAddress'],
        'filter'     => ['App\EofficeApp\Address\Services\AddressService', 'importPersonalAddressFilter'],
        'primarys'   => ['primary_1']
    ],
    'user' => [
        'fieldsFrom' => ['App\EofficeApp\User\Services\UserService', 'getImportUserFields', 'userInfo'],
        'dataSubmit' => ['App\EofficeApp\User\Services\UserService', 'importUser'],
        'primarys'   => ['user_accounts'],
    ],
    'orginazation' => [
        'fieldsFrom' => ['App\EofficeApp\User\Services\UserService', 'getImportOrginazationFields', 'userInfo'],
        'dataSubmit' => ['App\EofficeApp\User\Services\UserService', 'importOrginazation'],
        'primarys'   => ['user_accounts'],
    ],
    'attendance' => [
        'fieldsFrom' => ['App\EofficeApp\Attendance\Services\AttendanceMachineService', 'getImportAttendanceFields'],
        'dataSubmit' => ['App\EofficeApp\Attendance\Services\AttendanceMachineService', 'importAttendance'],
        'primarys'   => ['user_id','sign_date']
    ],
    'attendanceMachine' => [
        'fieldsFrom' => ['App\EofficeApp\Attendance\Services\AttendanceMachineService', 'ImportAttendanceMachineFields'],
        'dataSubmit' => ['App\EofficeApp\Attendance\Services\AttendanceMachineService', 'importAttendanceMachine'],
        'primarys'   => ['user_id']
    ],
    'officeSupplies' => [
        'fieldsFrom' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'getImportOfficeSuppliesFields', 'userInfo'],
        'dataSubmit' => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'importOfficeSupplies'],
        'filter'     => ['App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService', 'importOfficeSuppliesFilter'],
        'primarys'   => ['office_supplies_name', 'office_supplies_no'],
    ],
    'vacation' => [
        'fieldsFrom' => ['App\EofficeApp\Vacation\Services\VacationService', 'getUserVacationFields'],
        'dataSubmit' => ['App\EofficeApp\Vacation\Services\VacationService', 'importUserVacation'],
        'primarys'   => ['user_accounts']
    ],
    'synchro' => [
        'fieldsFrom' => ['App\EofficeApp\Attendance\Services\AttendanceService', 'getSynchroUserFields'],
        'dataSubmit' => ['App\EofficeApp\Attendance\Services\AttendanceService', 'importSynchroUser'],
        'filter'     => ['App\EofficeApp\Attendance\Services\AttendanceService', 'importSynchroFilter'],
        'primarys'   => ['user_accounts']
    ],
    'detail-layout' => [
        'fieldsFrom' => ['App\EofficeApp\Flow\Services\FlowFormService', 'getImportFormDetailLayoutFields','data', 'userInfo'],
        'dataSubmit' => ['App\EofficeApp\Flow\Services\FlowFormService', 'importFormDetailLayoutData','data'],
        'primarys'   => []
    ],
    'transLangPackage' => [
        'sheets' => true,
        'dataSubmit' => ['App\EofficeApp\Lang\Services\LangService', 'importLangPackage','data']
    ],
    'productInStock' => [
        'fieldsFrom' => ['App\EofficeApp\Storage\Services\StorageService', 'getImportInStockTemplate'],
        'dataSubmit' => ['App\EofficeApp\Storage\Services\StorageService', 'importInStock'],
        'primarys'   => ['product_number']
    ],
    'productOutStock' => [
        'fieldsFrom' => ['App\EofficeApp\Storage\Services\StorageService', 'getImportOutStockTemplate'],
        'filter'     => ['App\EofficeApp\Storage\Services\StorageService', 'importOutStockFilter'],
        'dataSubmit' => ['App\EofficeApp\Storage\Services\StorageService', 'importOutStock'],
        'primarys'   => ['product_number']
    ],
    'product' => [
        'fieldsFrom' => ['App\EofficeApp\Product\Services\ProductService', 'getProductFields','data'],
        'dataSubmit' => ['App\EofficeApp\Product\Services\ProductService', 'importProduct'],
        'filter'     => ['App\EofficeApp\Product\Services\ProductService', 'importProductFilter'],
        'primarys'   => ['product_number']
    ],
    'assetsStorage' => [
        'fieldsFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'getStorageFields','data'],
        'dataSubmit' => ['App\EofficeApp\Assets\Services\AssetsService', 'importStorage'],
        'filter'     => ['App\EofficeApp\Assets\Services\AssetsService', 'importStorageFilter'],
        'after'      => ['App\EofficeApp\Assets\Services\AssetsService', 'afterImport'],
        'primarys'   => []
    ],
    'assetsApplyRet' => [
        'fieldsFrom' => ['App\EofficeApp\Assets\Services\AssetsService', 'getRetFields'],
        'dataSubmit' => ['App\EofficeApp\Assets\Services\AssetsService', 'importRet'],
        'primarys'   => []
    ],
    'contract' => [
        'fieldsFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'getImportContractFields','data'],
        'dataSubmit' => ['App\EofficeApp\Contract\Services\ContractService', 'importContract'],
        'filter'     => ['App\EofficeApp\Contract\Services\ContractService', 'importContractFilter'],
        'after'      => ['App\EofficeApp\Contract\Services\ContractService', 'afterImportContract'],
    ],
    'contractProject' => [
        'fieldsFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'getImportProjectFields','data'],
        'filter'     => ['App\EofficeApp\Contract\Services\ContractService', 'importProjectFilter'],
        'dataSubmit' => ['App\EofficeApp\Contract\Services\ContractService', 'importProject','data'],
    ],
    'contractOrder' => [
        'fieldsFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'getImportOrderFields'],
        'filter'     => ['App\EofficeApp\Contract\Services\ContractService', 'importOrderFilter'],
        'dataSubmit' => ['App\EofficeApp\Contract\Services\ContractService', 'importOrder','data'],
    ],
    'contractRemind' => [
        'fieldsFrom' => ['App\EofficeApp\Contract\Services\ContractService', 'getImportRemindFields','data'],
        'filter'     => ['App\EofficeApp\Contract\Services\ContractService', 'importRemindFilter'],
        'dataSubmit' => ['App\EofficeApp\Contract\Services\ContractService', 'importRemind','data'],
    ],
    'chargeType' => [
        'fieldsFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'getChargeTypeFields'],
        'dataSubmit' => ['App\EofficeApp\Charge\Services\ChargeService', 'importChargeType'],
        'primarys'   => ['charge_type_id']
    ],
    'chargeWarning' => [
        'fieldsFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'getChargeWarningFields','userInfo'],
        'dataSubmit' => ['App\EofficeApp\Charge\Services\ChargeService', 'importChargeWarning'],
        'primarys'   => ['id']
    ],
    'chargeWarningSubject' => [
        'fieldsFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'getchargeWarningSubjectFields'],
        'dataSubmit' => ['App\EofficeApp\Charge\Services\ChargeService', 'importChargeWarningSubject'],
        'primarys'   => ['id']
    ],
    'charge' => [
        'fieldsFrom' => ['App\EofficeApp\Charge\Services\ChargeService', 'getChargeAddFields','userInfo'],
        'dataSubmit' => ['App\EofficeApp\Charge\Services\ChargeService', 'importCharge'],
        'primarys'   => ['charge_list_id']
    ],
    'customerProduct' => [
        'fieldsFrom' => ['App\EofficeApp\Customer\Services\ProductService', 'getProductFields','data'],
        'dataSubmit' => ['App\EofficeApp\Customer\Services\ProductService', 'importProduct'],
        'filter'     => ['App\EofficeApp\Customer\Services\ProductService', 'importFilter'],
        'after'      => ['App\EofficeApp\Customer\Services\ProductService', 'afterImport'],
    ],
    'heterogeneousSystemUser'=> [
        'fieldsFrom' => ['App\EofficeApp\UnifiedMessage\Services\UserBondingService', 'exportUserBonding'],
        'dataSubmit' => ['App\EofficeApp\UnifiedMessage\Services\UserBondingService', 'importUserBonding'],
       // 'filter'     => ['App\EofficeApp\UnifiedMessage\Services\UserBondingService', 'importUserBondingFilter'],
        'primarys'   => [],
    ],
    'modeling' => [
        'fieldFrom' => ['App\EofficeApp\FormModeling\Services\FormModelingService', 'getImportFields'],
        'filter' => ['App\EofficeApp\FormModeling\Services\FormModelingService', 'importDataFilterBack'],
        'dataSubmit' => ['App\EofficeApp\FormModeling\Services\FormModelingService', 'importCustomData'],
        'primarys'   => ['data_id']
    ]
];
// 合并第三方二开的导入配置
if (file_exists(config_path('third_import.php'))) {
    return $import + include 'third_import.php';
}
return $import;;

/*

[
    'header'    => [
        'customer_name'             => '客户名称',
        'phone_number'              => '电话号码',
        'fax_no'                    => '传真号码',
        'website'                   => '公司网址',
        'email'                     => 'EMAIL',
        'address'                   => '公司地址',
        'zip_code'                  => '邮政编码',
        'customer_number'           => '客户编号',
        'legal_person|sexFilter'    => '企业法人',
    ],
    'data'      => [									//可选填充模板数据
        [
            'customer_name'             => '客户名称',
            'phone_number'              => '110',
            'fax_no'                    => '110',
            'website'                   => 'baidu',
            'email'                     => '110',
            'address'                   => '上海',
            'zip_code'                  => '500236',
            'customer_number'           => 'AK110',
            'legal_person'              => '男',
        ]
    ]
];

*/
