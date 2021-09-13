<?php
namespace App\EofficeApp\Attendance\Traits;

/**
 * Description of AttendanceParamsTrait
 *
 * @author lizhijun
 */
trait AttendanceOrgTrait 
{
    private $staticFullDeptNames = [];
    private $staticDepts = [];
    public function clearStaticDepartment()
    {
        $this->staticFullDeptNames = [];
        $this->staticDepts = [];
    }
    public function getFullDepartmentName($deptId, $delimiter = '/')
    {
        if (isset($this->staticFullDeptNames[$deptId])) {
            return $this->staticFullDeptNames[$deptId];
        }
        $dept = $this->getDepartmentInfo($deptId);
        if(!$dept) {
            return '';
        }
        $arrParentId = $dept->arr_parent_id ? explode(',', $dept->arr_parent_id) : [];
        if (empty($arrParentId)) {
            $fullDeptName = $dept->dept_name;
        } else {
            $fullDeptName = '';
            foreach ($arrParentId as $dId) {
                if ($dId != 0) {
                    $fullDeptName .= $this->getDepartmentInfo($dId, 'dept_name') . $delimiter;
                }
            }
            $fullDeptName .= $dept->dept_name;
        }
        $this->staticFullDeptNames[$deptId] = $fullDeptName;
        return $fullDeptName;
    }
    private function getDepartmentInfo($deptId, $columnKey = null) 
    {
        if (isset($this->staticDepts[$deptId])) {
            $dept = $this->staticDepts[$deptId];
        } else {
            $dept = app($this->departmentRepository)->getDetail($deptId);
            $this->staticDepts[$deptId] = $dept;
        }

        if ($columnKey) {
            return $dept->{$columnKey};
        }
        return $dept;
    }
}
