<?php

namespace App\EofficeApp\Salary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryReportSetEntity;

class SalaryReportSetRepository extends BaseRepository
{
    public function __construct(SalaryReportSetEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getSetList($param)
    {
        $default = [
            'search'   => [],
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['salary_report_set.id' => 'desc'],
            'returntype' => 'object',
        ];

        // 解析 [无账号发薪]配置，开了开关(值为1)后：
        // 1、进入薪酬权限菜单，只显示管理范围是“人事档案”的列表。
        // 3、记录开了开关情况下的薪酬权限，为特殊状态，标识字段(permission_status:common-默认;personnel-人事)，用于步骤1的筛选。默认的薪酬权限数据，是common.
        if(isset($param['pay_without_account'])) {
            $payWithoutAccountConfig = $param['pay_without_account'];
            unset($param['pay_without_account']);
        } else {
            $payWithoutAccountConfig = '0';
        }

        $param = array_merge($default, array_filter($param));

        $query = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', 'user.user_id', '=', 'salary_report_set.manager_user');

        // if(isset($param['search']['department.dept_id'])){
        	$query->leftJoin('department', 'department.dept_id', '=', 'salary_report_set.manager_dept');
        // }
        // if(isset($param['search']['role.role_id'])){
        	$query->leftJoin('role', 'role.role_id', '=', 'salary_report_set.manager_role');
        // }

        if($payWithoutAccountConfig == '1') {
            $query = $query->where('permission_status', 'personnel');
        } else {
            $query = $query->where('permission_status', 'common');
        }

        $query = $query->wheres($param['search'])
		            ->orders($param['order_by'])
		            ->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            if (isset($param['groupBy'])) {
               return $query->get()->count();
            } else {
               return $query->count();
            }
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    public function getSetTotal($param)
    {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getSetList($param);
    	// $query = $this->entity->select(['id'])->leftJoin('user', 'user.user_id', '=', 'salary_report_set.manager');

     //    if(isset($param['search']['user_system_info.dept_id'])){
     //    	$query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'salary_report_set.manager');
     //    }
     //    if(isset($param['search']['user_role.role_id'])){
     //    	$query->leftJoin('user_role', 'user_role.user_id', '=', 'salary_report_set.manager');
     //    }
     //    if (isset($param['search'])) {
     //    	$query->wheres($param['search']);
     //    }
     //    return $this->entity->count();
    }

    public function getSimpleData($param)
    {
        $payWithoutAccountConfig = $param['pay_without_account'] ?? '0';
        $query = $this->entity;
        if($payWithoutAccountConfig == '1') {
            $query = $query->where('permission_status', 'personnel');
        } else {
            $query = $query->where('permission_status', 'common');
        }
        return $query->wheres($param['search'])->get();
    }
}
