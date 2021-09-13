<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use DB;

class ReportService extends BaseService
{

    const COMBOBOX_TYPE      = 17;
    const COMBOBOX_FROM      = 16;
    const COMBOBOX_STATUS    = 8;
    const COMBOBOX_SCALE     = 9;
    const COMBOBOX_INDUSTRY  = 10;
    const COMBOBOX_ATTRIBUTE = 41;

    public $customerSelectField = [
        'customer_type'      => 17,
        'customer_from'      => 16,
        'customer_status'    => 8,
        'scale'              => 9,
        'customer_industry'  => 10,
        'customer_attribute' => 41,
    ];

    public function __construct()
    {
        $this->repository              = 'App\EofficeApp\Customer\Repositories\ProductRepository';
        $this->customerRepository      = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
        $this->userService             = 'App\EofficeApp\User\Services\UserService';
        $this->userRepository          = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->contactRecordRepository = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
        $this->systemComboboxService   = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
    }

    public function getCustomerReportByTypes($types, $param = [])
    {
        $fieldsType = $this->getCustomerComboboxs();
        $fields     = ['customer_type', 'customer_status', 'customer_attribute', 'scale'];
        $search     = empty($param['search']) ? [] : json_decode($param['search'], true);
        $types      = array_filter(explode(',', $types));
        if (in_array('manager', $types)) {
            return $this->getCustomerReportByManager($types[1], $fieldsType, $search);
        }
        if (in_array('byDate', $types)) {
            return $this->getCustomerReportByDate($types[1], $types[2], $fieldsType, $search);
        }
        return $this->getCustomerReportByFeilds($types, $fieldsType, $search);
    }

    public function getCustomerComboboxs()
    {
        static $fieldsType;

        if (empty($fieldsType)) {
            $data = app($this->systemComboboxService)->getAllFields('8,9,17,41');

            $selectField = array_flip($this->customerSelectField);

            if (!empty($data)) {
                foreach (['8', '9', '17', '41'] as $v) {
                    $fieldsType[$selectField[$v]] = array_column($data[$v], 'field_name', 'field_value');
                }
            }
        }

        return $fieldsType;
    }

    public function getCustomerReportByFeilds($types, $fieldsType, $search = [])
    {
        $data   = [];
        $fields = ['customer_type', 'customer_status', 'customer_attribute', 'scale'];

        if(empty(self::paraseSearch($search))){
            if(isset($search['customer_manager_name']) || isset($search['customer_service_manager_name'])){
                return [];
            }
        };
        foreach ($types as $field) {
            if (in_array($field, $fields)) {
                $data[$field] = app($this->customerRepository)->getCustomerReportByType($field, $search);
            } else {
                $data[$field] = [];
            }
        }

        $data['fields_type'] = $fieldsType;

        return $data;
    }


    public function getCustomerReportByManager($field, $fieldsType, $search = [])
    {
        // 如果传的是中文名称到对应数据表获取用户id
        if(empty(self::paraseSearch($search))){
            if(isset($search['customer_manager_name']) || isset($search['customer_service_manager_name'])){
                return [];
            }
        };
        $result = app($this->customerRepository)->getCustomerReportByManager($field, $search);
        if ($field == 'total') {
            $customers = array_column($result, 'customer');
            $users     = app($this->userRepository)->getUserWithInfoByIds($customers, ['user_id', 'user_name'], '');
            $users     = array_column($users, 'user_name', 'user_id');

            foreach ($result as $v) {
                if (!isset($users[$v['customer']]) || $v['customer_num'] == 0) {
                    continue;
                }

                $dataReport['manager'][]      = $users[$v['customer']];
                $dataReport['客户数量'][] = $v['customer_num'];
            }

            $dataReport['types'] = ['客户数量'];

            return $dataReport;
        }
        if (empty($result)) {
            return [];
        }

        foreach ($result as $v) {
            $tempList = explode('|', $v['customer']);
            if (empty($tempList) || empty($tempList[0]) || empty($tempList[1])) {
                continue;
            }
            list($customer, $key)  = $tempList;
            $types[]               = $key;
            $customers[]           = $customer;
            $data[$customer][$key] = $v['customer_num'];
        }

        if (empty($types) || empty($customers)) {
            return [];
        }

        $users = app($this->userRepository)->getUserWithInfoByIds($customers, ['user_id', 'user_name'], '');
        $users = array_column($users, 'user_name', 'user_id');

        $types      = array_unique($types);
        $fieldsType = $fieldsType[$field];

        foreach ($types as $type) {
            $dataReport['types'][] = isset($fieldsType[$type]) ? $fieldsType[$type] : '未定义';
        }

        foreach ($data as $key => $v) {
            if (!isset($users[$key])) {
                continue;
            }

            $dataReport['manager'][] = $users[$key];

            $num = 0;
            foreach ($types as $type) {
                if (!isset($fieldsType[$type])) {
                    $num += isset($v[$type]) ? $v[$type] : 0;
                    continue;
                }

                $dataReport[$fieldsType[$type]][] = isset($v[$type]) ? $v[$type] : 0;
            }

            if ($num > 0) {
                $dataReport['未定义'][] = $num;
            }
        }

        return $dataReport;
    }

    public function getCustomerReportByDate($byDate, $field, $fieldsType, $search = [])
    {
        // 如果传的是中文名称到对应数据表获取用户id
        if(empty(self::paraseSearch($search))){
            if(isset($search['customer_manager_name']) || isset($search['customer_service_manager_name'])){
                return [];
            }
        };
        $result = app($this->customerRepository)->getCustomerReportByDate($byDate, $field, $search);

        if ($field == 'total') {
            return [
                'byDate' => array_column($result, 'customer_date'),
                '客户数量'   => array_column($result, 'customer_num'),
                'types'  => ['客户数量'],
            ];

            return $data;
        }

        if (empty($result)) {
            return [];
        }

        foreach ($result as $v) {
            $tempList = explode('|', $v['customer']);
            if (empty($tempList) || empty($tempList[0]) || empty($tempList[1])) {
                continue;
            }
            list($customer, $key)  = explode('|', $v['customer']);
            $types[]               = $key;
            $customers[]           = $customer;
            $data[$customer][$key] = $v['customer_num'];
        }
        if (empty($types)) {
            return [];
        }

        $types      = array_unique($types);
        $fieldsType = $fieldsType[$field];

        foreach ($types as $type) {
            $dataReport['types'][] = isset($fieldsType[$type]) ? $fieldsType[$type] : '未定义';
        }

        foreach ($data as $key => $v) {
            $dataReport['byDate'][] = $key;

            $num = 0;
            foreach ($types as $type) {
                if (!isset($fieldsType[$type])) {
                    $num += isset($v[$type]) ? $v[$type] : 0;
                    continue;
                }

                $dataReport[$fieldsType[$type]][] = isset($v[$type]) ? $v[$type] : 0;
            }

            if ($num > 0) {
                $dataReport['未定义'][] = $num;
            }
        }

        return $dataReport;
    }

    public static function paraseSearch(&$search) : void {
        if(isset($search['customer_manager_name'])){
            $customerName = isset($search['customer_manager_name'][0]) ? $search['customer_manager_name'][0] : '';
            if($userLists = DB::table('user')->where('user_name', 'like', '%' . $customerName . '%')->pluck('user_id')->toArray())
            {
                unset($search['customer_manager_name']);
                $paraseSearch = [
                    'customer_manager' => [$userLists,'in']
                ];
                $search = array_merge($search,$paraseSearch);
            }
        }
        if(isset($search['customer_service_manager_name'])){
            $customerName = isset($search['customer_service_manager_name'][0]) ? $search['customer_service_manager_name'][0] : '';
            if($userLists = DB::table('user')->where('user_name', 'like', '%' . $customerName . '%')->pluck('user_id')->toArray())
            {
                unset($search['customer_service_manager_name']);
                $paraseSearch = [
                    'customer_service_manager' => [$userLists,'in']
                ];
                $search = array_merge($search,$paraseSearch);
            }
        }
    }
}
