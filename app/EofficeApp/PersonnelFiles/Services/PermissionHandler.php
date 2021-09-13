<?php


namespace App\EofficeApp\PersonnelFiles\Services;


class PermissionHandler
{
    /**
     * 处理部门权限参数
     * @param $deptIds
     * @param $params
     */
    public static function mergeDeptPermitParams($deptIds, &$params)
    {
        if($deptIds != 'all'){
            if(isset($params['search']['dept_id']) && !empty($params['search']['dept_id'])) {
                $deptSearch = $params['search']['dept_id'];
                if(is_array($params['search']['dept_id'][0])){
                    $deptSearch = $params['search']['dept_id'][0];
                }
                $deptIds = array_values(array_intersect($deptIds, $deptSearch));
            }
            $params['search']['dept_id'] = [$deptIds, 'in'];
            if(count($deptIds) == 1){
                $params['search']['dept_id'] = $deptIds;
            }
        }
    }

}
