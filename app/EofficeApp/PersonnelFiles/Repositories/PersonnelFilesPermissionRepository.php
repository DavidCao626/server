<?php


namespace App\EofficeApp\PersonnelFiles\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesPermissionEntity;

class PersonnelFilesPermissionRepository extends BaseRepository
{
    public function __construct(PersonnelFilesPermissionEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getPermissionList($params)
    {
        $defaults = [
            'page'		=> 0,
            'limit'		=> 10,
            'order_by'	=> ['id' => 'desc'],
        ];

        $param = array_merge($defaults, $params);

        return $this->entity
            ->when(isset($params['fields']) && !empty($params['fields']), function($query) use ($params) {
                $query->select($params['fields']);
            })
            ->when(isset($params['search']), function($query) use ($params) {
                $this->parseManagerSearch($params['search'], $query);
            })
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get();
    }

    public function getPermissionCount($param)
    {
        return $this->entity
            ->when(isset($param['search']), function($query) use ($param) {
                $this->parseManagerSearch($param['search'], $query);
            })
            ->count();
    }

    /**
     * @param $search
     * @param $query
     */
    private function parseManagerSearch($search, $query)
    {
        if(isset($search['user']) && !empty($search['user'])){
            $query->orWhere(function($query) use ($search){
                $query->where('manager_type', 'user')
                    ->whereIn('manager_id', $search['user']);
            });
        }
        unset($search['user']);

        if(isset($search['dept']) && !empty($search['dept'])){
            $query->orWhere(function($query) use ($search){
                $query->where('manager_type', 'dept')
                    ->whereIn('manager_id', $search['dept']);
            });
        }
        unset($search['dept']);

        if(isset($search['role']) && !empty($search['role'])){
            $query->orWhere(function($query) use ($search){
                $query->where('manager_type', 'role')
                    ->whereIn('manager_id', $search['role']);
            });
        }
        unset($search['role']);

        
        if(isset($search['role_name']) && !empty($search['role_name'])){
            $query->leftJoin('role', function($join) {
                $join->on('role.role_id', '=', 'personnel_files_permissions.manager_id');
            });
            $query->orWhere(function($query) use ($search){
                $query->where('manager_type', 'role');
            });
        }

        if(isset($search['dept_name']) && !empty($search['dept_name'])){
            $query->leftJoin('department', function($join) {
                $join->on('department.dept_id', '=', 'personnel_files_permissions.manager_id');
            });
            $query->orWhere(function($query) use ($search){
                $query->where('manager_type', 'dept');
            });
        }

        if(isset($search['user_name']) && !empty($search['user_name'])){
            $query->leftJoin('user', function($join) {
                $join->on('user.user_id', '=', 'personnel_files_permissions.manager_id');
            });
            $query->orWhere(function($query) use ($search){
                $query->where('manager_type', 'user');
            });
        }

        if(!empty($search)){
            $query->wheres($search);
        }
    }

//    public function getOwnPermissions($own, $range)
//    {
//        $search = [];
//        $search['user'] = isset($own['user_id']) ? [$own['user_id']] : [];
//        $search['dept'] = isset($own['dept_id']) ? [$own['dept_id']] : [];
//        $search['role'] = $own['role_id'] ?? [];
//        $permissions = PersonnelFilesPermissionEntity::select(['all_purview', 'dept_id', 'include_children'])
//            ->whereIn('range', $range)
//            ->when($search, function($query) use ($search) {
//                $this->parseManagerSearch($search, $query);
//            });
//    }

}
