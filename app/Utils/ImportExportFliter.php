<?php

namespace App\Utils;

use App\EofficeApp\System\Combobox\Services\SystemComboboxService;
use App\EofficeApp\Book\Services\BookService;
use App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService;
use App\EofficeApp\User\Services\UserService;

class ImportExportFliter
{
    protected $comboboxService;
    protected $bookService;
    protected $officeSuppliesService;
    protected $userService;

    public function __construct(SystemComboboxService $comboboxService,
                                BookService $bookService,
                                OfficeSuppliesService $officeSuppliesService,
                                UserService $userService)
    {
        $this->comboboxService          = $comboboxService;
        $this->bookService              = $bookService;
        $this->officeSuppliesService    = $officeSuppliesService;
        $this->userService              = $userService;
    }

    /**
     * 过滤性别
     *
     * @param  string $key  键名
     *
     * @return string 过滤后的值
     *
     * @author qishaobo
     *
     * @since  2016-04-19
     */
    public function sexFilter($key) {
        $data = [
            '0' => trans('common.woman'),
            '1' => trans('common.man')
        ];

        return $this->filterData($data, $key);
    }

    /**
     * 过滤流程紧急程度
     *
     * @param  string $key  键名
     *
     * @return string 过滤后的值
     *
     * @author miaochenchen
     *
     * @since  2016-11-22
     */
    public function instancyTypeFilter($key) 
    {
        $data = app('App\EofficeApp\Flow\Services\FlowSettingService')->getInstancyMapOptions();
        
        return $this->filterData($data, $key);
    }

    //在职状态
    public function workStatus($key)
    {
        $userStatusList = $this->userService->userStatusList([]);

        $data = [];
        foreach($userStatusList['list'] as $k => $v) {
            $data[$v['status_id']] = $v['status_name'];
        }

        return $this->filterData($data, $key);
    }

    //政治面貌
    public function politicsFilter($key)
    {
        $politicsValue = $this->comboboxService->getAllFields('POLITICAL_STATUS');

        $data = [];
        foreach ($politicsValue as $key => $value) {
            $data[$value['field_id']] = $value['field_name'];
        }

        return $this->filterData($data, $key);
    }

    //婚姻状态
    public function marrayFilter($key)
    {
        $politicsValue = $this->comboboxService->getAllFields('MARITAL_STATUS');

        $data = [];
        foreach ($politicsValue as $key => $value) {
            $data[$value['field_id']] = $value['field_name'];
        }

        return $this->filterData($data, $key);
    }

    //学历
    public function educationFilter($key)
    {
        $politicsValue = $this->comboboxService->getAllFields('EDUCATIONAL');

        $data = [];
        foreach ($politicsValue as $key => $value) {
            $data[$value['field_id']] = $value['field_name'];
        }

        return $this->filterData($data, $key);
    }

    //图书类别
    public function bookType($key)
    {
        $typeList = $this->bookService->getBookTypeList();

        $data = [];
        foreach ($typeList as $key => $value) {
            $data[$value['id']] = $value['type_name'];
        }

        return $this->filterData($data, $key);
    }

    //图书借阅范围
    public function borrowFilter($key)
    {
        $data = [
            // 0 => '未归还',
            // 1 => '已归还'
            0 => trans('common.no_return'),
            1 => trans('common.has_returned')
        ];

        return $this->filterData($data, $key);
    }

    //办公用品类型
    public function suppliesType($suppliesType)
    {
        $typeList = $this->officeSuppliesService->getOfficeSuppliesAllTypeList(array());

        $data = [];
        foreach ($typeList as $key => $value) {
            $data[$value['id']] = $value['type_name'];
        }

        return $this->filterData($data, $suppliesType);
    }

    //办公用品使用方式
    public function suppliesUsage($suppliesUsage)
    {
        $data = [
            // 0 => '使用',
            // 1 => '借用'
            0 => trans('common.used'),
            1 => trans('common.borrow')
        ];

        return $this->filterData($data, $suppliesUsage);
    }

    //审批状态
    public function checkStatus($key)
    {
        $data = [
            // 0 => '审批中',
            // 1 => '已通过'
            0 => trans('common.in_approval'),
            1 => trans('common.have_been_through')
        ];

        return $this->filterData($data, $key);
    }

    /**
     * 返回过滤值
     *
     * @param  array $data  数据源
     * @param  string $key  数据键名
     *
     * @return string 过滤后的值
     *
     * @author qishaobo
     *
     * @since  2016-04-19
     */
    public function filterData($data, $key) {

        if (isset($data[$key])) {
            return $data[$key];
        }

        $data = array_flip($data);

        if (isset($data[$key])) {
            return $data[$key];
        }

        return '';
    }
}