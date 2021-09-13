<?php

namespace App\EofficeApp\Charge\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Exception;
use Cache;
use App\EofficeApp\Project\NewServices\ProjectService;

/**
 * 费用预警服务
 */
class ChargeSettingService extends BaseService
{
    public function __construct()
    {
        $this->chargeTypeRepository           = 'App\EofficeApp\Charge\Repositories\ChargeTypeRepository';
        $this->chargeSettingRepository        = 'App\EofficeApp\Charge\Repositories\ChargeSettingRepository';
        $this->chargeListRepository           = 'App\EofficeApp\Charge\Repositories\ChargeListRepository';
        $this->userRoleRepository             = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->userSystemInfoRepository       = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userRepository                 = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->departmentRepository           = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->departmentService              = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->companyRepository              = 'App\EofficeApp\System\Company\Repositories\CompanyRepository';
        $this->langService                    = 'App\EofficeApp\Lang\Services\LangService';
        $this->projectManagerRepository       = 'App\EofficeApp\Project\Repositories\ProjectManagerRepository';
        $this->projectService                 = 'App\EofficeApp\Project\Services\ProjectService';
        $this->chargePermissionRepository     = 'App\EofficeApp\Charge\Repositories\ChargePermissionRepository';
        $this->userService                    = 'App\EofficeApp\User\Services\UserService';
        $this->roleService                    = 'App\EofficeApp\Role\Services\RoleService';
    }

    /**
     * 获取费用设置列表
     * @param array $data 费用检索
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-25
     */
    public function getChargeSetList($param, $own)
    {
        $param   = $this->parseParams($param);
        $setType = isset($param['search']['set_type'][0]) ? $param['search']['set_type'][0] : 0;
        $projectIds = ProjectService::thirdMineProjectId($own);
        switch($setType) {
            // 公司
            case 1:
                break;
            // 部门
            case 2:
                $param['fields'] = ['charge_setting.*', 'dept_name'];
                break;
            // 个人
            case 3:
                $param['fields'] = ['charge_setting.*', 'user_name', 'user_status'];
                break;
            // 项目
            case 5:
                $param['fields'] = ['charge_setting.*', 'manager_name'];
                // if (isset($param['search']['project_id'][0])) {
                //     if (!in_array($param['search']['project_id'][0], $projectIds)) {
                //         return [];
                //     }
                // } else {
                //     $param['search']['project_id'] = [$projectIds, 'in'];
                // }
                $param['project_id'] = $projectIds;
                break;
            // 全部
            default:
                $param['project_id'] = $projectIds;
                break;
        }
        $response = $this->response(app($this->chargeSettingRepository), 'getChargeSetTotal', 'getChargeSetList', $param);
        if(isset($response['list']) && !empty($response['list'])){
            foreach ($response['list'] as $key => $value) {
                $response['list'][$key]['user_name'] = $response['list'][$key]['user_name']?$response['list'][$key]['user_name']:trans('charge.Deleted');
                if($response['list'][$key]['user_status'] == 0){
                    $response['list'][$key]['user_name'] = trans('charge.Deleted');
                }
                $response['list'][$key]['dept_name'] = $response['list'][$key]['dept_name']?$response['list'][$key]['dept_name']:trans('charge.Deleted');
            }
        }
       
        return $response;
    }

    // public function getAllChargeSet($param) {
    //     return $this->response(app($this->chargeSettingRepository), 'getChargeSetTotal', 'getChargeSetList', $param);
    // }

    // public function getDeptChargeSet($param) {
    //     $param['fields'] = ['charge_setting.*', 'dept_name'];
    //     return $this->response(app($this->chargeSettingRepository), 'getChargeSetTotal', 'getChargeSetList', $param);
    // }

    // public function getUserChargeSet($param) {
    //     $param['fields'] = ['charge_setting.*', 'user_name', 'user_status'];
    //     return $this->response(app($this->chargeSettingRepository), 'getChargeSetTotal', 'getChargeSetList', $param);
    // }
    // 处理项目权限
    private function handleProjectData($data, $own) {
        foreach ($data as $key => $value) {
            if ($value['set_type'] == 5) {
                // 获取项目权限 
                $purview = app($this->projectService)->getProjectCheckPrivate([
                    'user_id' => $own['user_id'],
                    'manager_id' => $value['project_id']
                ]);
                if ($purview !== true) {
                    continue;
                }
            }
        }
    }
}