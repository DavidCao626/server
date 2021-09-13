<?php
namespace App\EofficeApp\Cooperation\Repositories;

use App\EofficeApp\Cooperation\Entities\CooperationSortEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区分类表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSortRepository extends BaseRepository
{
    public function __construct(CooperationSortEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 协作分类表信息列表
     * @param  [array] $data [description]
     * @return [type]       [description]
     */
    public function getCooperationSortListRepository($param)
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['cooperation_sort_order'=>'ASC'],
        ];
        $param = array_merge($default, $param);
        return $this->entity
                ->select($param['fields'])
                ->wheres($param['search'])
                ->orders($param['order_by'])
                ->forPage($param['page'], $param['limit'])
                ->with(['sortHasManySubject' => function ($query) {
                    $query->selectRaw('count(*) AS num')->addSelect('cooperation_sort_id')->groupBy('cooperation_sort_id');
                }])
                ->with(['hasOneUser' => function ($query) {
                    $query->select("user_id","user_name");
                }])
                ->with(['sortHasManyUser.hasOneUser' => function ($query) {
//                    $query->select("user_id","user_name");
                    $query->select("user_id","user_name")->where('user_accounts', '!=', '');
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
     * 协作分类表信息获取，这个是带分类下属的协作主题数量的，单纯获取用基类里的函数
     * @param  [array] $sortId [description]
     * @return [type]       [description]
     */
    public function cooperationSortData($sortId)
    {
        $query = $this->entity
                    ->where("cooperation_sort_id",$sortId)
                    ->with(['sortHasManySubject' => function ($query) {
                        $query->selectRaw('count(*) AS num')
                            ->addSelect('cooperation_sort_id')
                            ->groupBy('cooperation_sort_id');
                    }])
                    ->with(['sortHasManySubjectList' => function ($query) {
                        $query->select('subject_id',"cooperation_sort_id");
                    }])
                    ->with("sortHasManyUser","sortHasManyRole","sortHasManyDept")
                    ;
        return $query->get()->first();
    }

    /**
     * 获取有权限的协作类别列表
     * @param  array $param
     * @return json        [description]
     */
    function getPermissionCooperationSortList($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $query = $this->entity;

        if(isset($param['search'])) {
            $param['search'] = json_decode($param['search'],true);
        }

        if(isset($param['search']['cooperation_sort_id'])) {
            $cooperation_sort_id = isset($param['search']['cooperation_sort_id']) ? $param['search']['cooperation_sort_id'] : '';
            $query = $query->where('cooperation_sort_id', '=', $cooperation_sort_id);
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
                        ->orders(['cooperation_sort_order'=>'ASC','cooperation_sort_time'=>'ASC']);
        if(isset($param['search']['cooperation_sort_name'])) {

            $cooperation_sort_id = isset($param['search']['cooperation_sort_name']) ? $param['search']['cooperation_sort_name'] : '';

            // $query = $query->where('cooperation_sort_name','like', '%'.$cooperation_sort_id[0].'%');
            $query = $query->wheres($param['search']);
            return $query->get();
        }
        return $query->get();
    }

    /**
     * 获取有权限的【协作主题列表】所属的【协作类别列表】【这个路由，用在新建协作页面，在有权限的类别下新建协作】
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

    public function getSortDetail($sortId) {
        $query = $this->entity;
        return $query = $query->leftJoin('cooperation_sort_member_user', function($join) {
            $join->on('cooperation_sort.cooperation_sort_id', '=', 'cooperation_sort_member_user.cooperation_sort_id');
        })->leftJoin('cooperation_sort_member_role', function($join) {
            $join->on('cooperation_sort.cooperation_sort_id', '=', 'cooperation_sort_member_role.cooperation_sort_id');
        })->leftJoin('cooperation_sort_member_department', function($join) {
            $join->on('cooperation_sort.cooperation_sort_id', '=', 'cooperation_sort_member_department.cooperation_sort_id');
        })
        ->where('cooperation_sort.cooperation_sort_id', '=', $sortId)->get()->toArray();
    }

}
