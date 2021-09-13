<?php


namespace App\EofficeApp\PersonnelFiles\Services;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Department\Services\DepartmentService;
use App\Exceptions\ErrorMessage;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;


class PersonnelFilesDepartment extends BaseService
{
    private $departmentService;

    private $personnelFilesPermission;

    public function __construct(
        DepartmentService $departmentService,
        PersonnelFilesPermission $personnelFilesPermission
    )
    {
        parent::__construct();
        $this->departmentService = $departmentService;
        $this->personnelFilesPermission = $personnelFilesPermission;
    }

    /**
     * @param $deptId
     * @param $params
     * @param $own
     * @return array
     */
    public function queryDeptChildren($deptId, $params, $own)
    {
        $depts = $this->deptChildren($deptId, $params, $own);

        $this->filterDeptsDisabled($depts, $own);

        return $depts;
    }

    /**
     * @param $deptId
     * @param $params
     * @param $own
     * @return array
     */
    public function manageDeptChildren($deptId, $params, $own)
    {
        $depts = $this->deptChildren($deptId, $params, $own);

        $this->filterDeptsDisabled($depts, $own, true);

        return $depts;
    }

    /**
     * @param $deptId
     * @param $params
     * @param $own
     * @return array
     */
    private function deptChildren($deptId, $params, $own)
    {
        return $this->departmentService->children($deptId, $params, $own)->toArray();
    }

    /**
     * @param $depts
     * @param $own
     * @param bool $manage
     * @param bool $delete 删除没有子集的disable元素
     */
    private function filterDeptsDisabled(&$depts, $own, $manage = false, $delete = true)
    {
        $permittedDepts = $manage ? $this->personnelFilesPermission->getManagePermittedDepts($own)
            : $this->personnelFilesPermission->getQueryPermittedDepts($own);

        if($permittedDepts == 'all') {
            return;
        }
        foreach($depts as $key => &$dept){
            $dept['disabled'] = false;
            if(!in_array($dept['dept_id'], $permittedDepts)){
                $dept['disabled'] = true;
                if(!$dept['has_children'] && $delete){
                    unset($depts[$key]);
                }
            }
        }
        $depts = array_values($depts);
    }

    /**
     * @param $params
     * @param $own
     * @param bool $manage
     * @return array
     */
    public function getDescendantDepartments($params, $own, $manage = false)
    {
        $params = $this->parseParams($params);

        $result = $this->departmentService->listDept($params);
        if(!empty($result['list'])){
            $this->filterDeptsDisabled($result['list'], $own, $manage, false);
        }

        return $result;
    }

    /**
     * @param $params
     * @param $own
     * @param bool $manage
     * @return array
     */
    public function getFilteredDepartments($params, $own, $manage = false)
    {
        $params = $this->parseParams($params);

        $deptIds = $manage ? $this->personnelFilesPermission->getManagePermittedDepts($own)
            : $this->personnelFilesPermission->getQueryPermittedDepts($own);

        PermissionHandler::mergeDeptPermitParams($deptIds, $params);

        return $this->departmentService->listDept($params);
    }


}
