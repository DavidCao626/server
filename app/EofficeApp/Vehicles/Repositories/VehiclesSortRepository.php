<?php
namespace App\EofficeApp\Vehicles\Repositories;

use App\EofficeApp\Vehicles\Entities\VehiclesSortEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 车辆区分类表表知识库
 */
class VehiclesSortRepository extends BaseRepository
{
    public function __construct(VehiclesSortEntity $entity) {
        parent::__construct($entity);
    }

    /**
     *  车辆分类表信息列表
     * @param  [array] $data [description]
     * @return [type]       [description]
     */
    public function getVehiclesSortListRepository($param)
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['vehicles_sort_order'=>'ASC', 'vehicles_sort_id' => 'desc'],
        ];
        $param = array_merge($default, $param);
        return $this->entity
                ->select($param['fields'])
                ->wheres($param['search'])
                ->orders($param['order_by'])
                ->parsePage($param['page'], $param['limit'])
                ->with(['sortHasManySubject' => function ($query) {
                    $query->selectRaw('count(*) AS num')->addSelect('vehicles_sort_id')->groupBy('vehicles_sort_id');
                }])
                ->with(['hasOneUser' => function ($query) {
                    $query->select("user_id","user_name");
                }])
                ->with(['sortHasManyUser.hasOneUser' => function ($query) {
                    $query->select("user_id","user_name");
                }])
                ->with(['sortHasManyRole.hasOneRole' => function ($query) {
                    $query->select("role_id","role_name");
                }])
                ->with(['sortHasManyDept.hasOneDept' => function ($query) {
                    $query->select("dept_id","dept_name");
                }])
                ->get()
                // ->toArray()
                ;
    }

    /**
     * 车辆分类表信息获取，这个是带分类下属的车辆主题数量的，单纯获取用基类里的函数
     * @param  [array] $sortId [description]
     * @return [type]       [description]
     */
    public function vehiclesSortData($sortId)
    {
        $query = $this->entity
                    ->where("vehicles_sort_id",$sortId)
                    ->with(['sortHasManySubject' => function ($query) {
                        $query->selectRaw('count(*) AS num')
                            ->addSelect('vehicles_sort_id')
                            ->groupBy('vehicles_sort_id');
                    }])
                    ->with(['sortHasManySubjectList' => function ($query) {
                        $query->select('vehicles_sort_id',"vehicles_sort_id");
                    }])
                    ->with("sortHasManyUser","sortHasManyRole","sortHasManyDept")
                    ;
        return $query->get()->first();
    }

    /**
     * 获取有权限的类别列表
     * @param  array $param
     * @return json        [description]
     */
    function getPermissionVehiclesSortList($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $query = $this->entity;
        if(isset($param['search'])) {
            $param['search'] = json_decode($param['search'],true);
        }
        
        if(isset($param['search']['vehicles_sort_id'])) {
            $vehicles_sort_id = isset($param['search']['vehicles_sort_id']) ? $param['search']['vehicles_sort_id'] : '';
             $query = $query->wheres(['vehicles_sort_id' =>[$vehicles_sort_id, 'in']]);
             return $query->get();
        }

        if(isset($param['search']['vehicles_sort_name'])) {
            
            $vehicles_sort_id = isset($param['search']['vehicles_sort_name']) ? $param['search']['vehicles_sort_name'] : '';
        
            $query = $query->where('vehicles_sort_name','like', '%'.$vehicles_sort_id[0].'%');
            return $query->get();
        }

        $query = $query->where('member_dept', "all")
                        ->orWhere('member_role', "all")
                        ->orWhere('member_user', "all")
                        ->orWhereHas('sortHasManyDept', function ($query) use ($deptId) {
                            $query->wheres(['dept_id' => [explode(",", trim($deptId,",")), 'in']]);
                        })
                        ->orWhereHas('sortHasManyRole', function ($query) use ($roleId) {
                            $query->wheres(['role_id' => [explode(",", trim($roleId,",")), 'in']]);
                        })
                        ->orWhereHas('sortHasManyUser', function ($query) use ($userId) {
                            $query->wheres(['user_id' => [$userId]]);
                        })
                        ->orders(['vehicles_sort_order'=>'ASC','vehicles_sort_time'=>'ASC']);
        return $query->get();
    }

    /**
     * 获取有权限的【车辆主题列表】所属的【车辆类别列表】【这个路由，用在新建车辆页面，在有权限的类别下新建车辆】
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function getPermissionSubjectRelationSortList($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $query  = $this->entity;
        if(isset($param['search']) && is_array($param['search'])) {
            $param['search'] = $param['search'];
        }
       
        if(isset($param['search']['cooperation_sort_id'])) {
            $cooperation_sort_id = isset($param['search']['cooperation_sort_id']) ? $param['search']['cooperation_sort_id'] : '';
            $query = $query->wheres(['cooperation_sort_id' =>[$cooperation_sort_id, 'in']]);
            
        }

        if(isset($param['search']['cooperation_sort_name'])) {
            
            $cooperation_sort_id = isset($param['search']['cooperation_sort_name']) ? $param['search']['cooperation_sort_name'] : '';
        
            $query = $query->where('cooperation_sort_name','like', '%'.$cooperation_sort_id[0].'%');
        }
        $query  = $query->whereHas('sortHasManySubjectList.subjectHasManyPurview', function ($query) use ($userId) {
                $query->wheres(['user_id' => [$userId]]);
            });
        return $query->get();
    }

    /**
     * 获取车辆所属类别
     *
     * @param array $param
     */
    public function getVehiclesSort($param = [])
    {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        $vehiclesInfo = $this->entity
                             ->select(
                                 [
                                 'vehicles_sort_id',
                                 'vehicles_sort_name',
                                 'vehicles_sort_order'
                                 ]
                             )
                            ->wheres($param['search'])
                            ->get()
                            ->toArray();

        return $vehiclesInfo;
    }
}
