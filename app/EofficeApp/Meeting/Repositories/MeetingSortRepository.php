<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSortEntity;
use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Lang\Services\LangService;
use Lang;
use DB;

/**
 * 协作区分类表表知识库
 */
class MeetingSortRepository extends BaseRepository
{
    public function __construct(MeetingSortEntity $entity, LangService $langService) {
        parent::__construct($entity);
        $this->langService = $langService;
    }

    /**
     * 协作分类表信息列表
     * @param  [array] $data [description]
     * @return [type]       [description]
     */
    public function getMeetingSortListRepository($param)
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['meeting_sort_order'=>'ASC'],
        ];
        $local = Lang::getLocale();
        $langTable = $this->langService->getLangTable($local);
        $param = array_merge($default, $param);
        $param['fields'] = ['*'];
        $param['lang_table'] = $langTable;
        if (isset($param['search']['meeting_sort_name']) && !empty($param['search']['meeting_sort_name'])) {
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['meeting_sort_name']) && !empty($param['search']['meeting_sort_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['meeting_sort_name'],
                    'table' => ['meeting_sort', 'like']
                ];
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['meeting_sort_name'] = [$langKeys, 'in'];
                } else {
                    return [];
                }
            }
        }
        return $this->entity
                ->select($param['fields'])
                ->wheres($param['search'])
                ->orders($param['order_by'])
                ->parsePage($param['page'], $param['limit'])
                ->with(['sortHasManySubject' => function ($query) {
                    $query->selectRaw('count(*) AS num')->addSelect('meeting_sort_id')->groupBy('meeting_sort_id');
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
                ->get();
    }

    /**
     * 协作分类表信息获取，这个是带分类下属的协作主题数量的，单纯获取用基类里的函数
     * @param  [array] $sortId [description]
     * @return [type]       [description]
     */
    public function meetingSortData($sortId)
    {
        $params['fields'] = ['meeting_sort.*', 'meeting_rooms.*'];
        $query = $this->entity
                    ->where("meeting_sort_id",$sortId)
                    ->with(['sortHasManySubject' => function ($query) {
                        $query->selectRaw('count(*) AS num')
                            ->addSelect('meeting_sort_id')
                            ->groupBy('meeting_sort_id');
                    }])
                    ->with(['sortHasManySubjectList' => function ($query) {
                        $query->select("meeting_sort_id");
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
    function getPermissionMeetingSortList($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $query = $this->entity;
        if(isset($param['search'])) {
            $param['search'] = json_decode($param['search'],true);
        }

        if(isset($param['search']['meeting_sort_id'])) {
            $meeting_sort_id = isset($param['search']['meeting_sort_id']) ? $param['search']['meeting_sort_id'] : '';
             $query = $query->wheres(['meeting_sort_id' =>[$meeting_sort_id, 'in']]);
             return $query->get();
        }

        if(isset($param['search']['meeting_sort_name'])) {

            $meeting_sort_id = isset($param['search']['meeting_sort_name']) ? $param['search']['meeting_sort_name'] : '';

            $query = $query->where('meeting_sort_name','like', '%'.$meeting_sort_id[0].'%');
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
                        ->orders(['meeting_sort_order'=>'ASC','meeting_sort_time'=>'ASC']);
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
    // 解析DB模式的多条件查询
    public function parseWheres($query, $wheres)
    {
        $operators = [
            'between'       => 'whereBetween',
            'not_between'   => 'whereNotBetween',
            'in'            => 'whereIn',
            'not_in'        => 'whereNotIn'
        ];

        if (empty($wheres)) {
            return $query;
        }

        foreach ($wheres as $field=>$where) {
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                $query = $query->$whereOp($field, $where[0]);
            } else {
                $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                $query = $query->where($field, $operator, $value);
            }
        }
        return $query;
    }
}
