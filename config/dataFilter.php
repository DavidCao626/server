<?php

/**
 * 用户列表api通过user_id过滤方法配置
 * !!!每条配置的key值不能重复!!!
 * dataFrom：数据来源，{模块service}+{service成员方法}，返回需要的用户id数组和是否包含当前获取的用户id的判断
 *
 * @author miaochenchen
 *
 * @since  2017-01-09 创建
 */

return [
    // 配置示例
    // 'departmentDirector' => [
    //  'dataFrom' => ['App\EofficeApp\System\Department\Services\DepartmentService', 'filterDepartmentDirectorSelector']
    // ],
    'flowSubmitModalHostUserSelectDirector' => [
        'dataFrom' => ['App\EofficeApp\Flow\Services\FlowService', 'flowSubmitModalHostUserSelectDirector']
    ],
    'flowDefineHostUserSelectDirector' => [
        'dataFrom' => ['App\EofficeApp\Flow\Services\FlowService', 'flowDefineHostUserSelectDirector']
    ],
    // 手机版微博主页高级搜索
    'diaryAttentionSelectorDirector' => [
        'dataFrom' => ['App\EofficeApp\Diary\Services\DiaryService', 'diaryAttentionSelectorDirector']
    ],
    // 薪酬筛选
    'salaryViewUserOrgDirector' => [
        'dataFrom' => ['App\EofficeApp\Salary\Services\SalaryReportSetService', 'salaryViewUserOrgDirector']
    ],
    // 客户公海
    'seasUserCustomerFilter' => [
        'dataFrom' => ['App\EofficeApp\Customer\Services\CustomerService', 'UserSelectorFilter']
    ],
    'personnelFilesUserIdDirector' => [
        'dataFrom' => ['App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService', 'personnelFilesUserIdDirector']
    ]

];

/*
// 配置的方法（例： filterDepartmentDirectorSelector ）返回的数据格式示例
// 第一种格式 需要过滤掉部分用户
$result = [
    'user_id' => ['WV00000001','WV00000002',......],
    'isNotIn' => true or false  //为true时是包含当前得到的user_id，默认为true，可不返回该参数，为false时，排除当前得到的user_id
];
// 第二种格式 不显示任何人员
$result = [
    'user_id' => []
];
// 第二种格式 显示全体人员
$result = "";


// 前端选择器配置示例
<eui-selector multiple type="user" ng-model="vm.deptInfo.director" config="vm.deptDirectorConfig"></eui-selector>
self.deptDirectorConfig = {
    initParam: {
        "search": {
            "user_id": ['admin'],
        }
        // 增加dataFilter参数，返回该文件配置的方法查询到的user_id，用于过滤，若前端search中也有user_id，将合并到一起，in或not in筛选统一由isNotIn配置的参数决定
        "dataFilter": 'departmentDirector'
    }
}
 */
