<?php
namespace App\EofficeApp\Cooperation\Repositories;

use App\EofficeApp\Cooperation\Entities\CooperationSubjectEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 协作区主题表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSubjectRepository extends BaseRepository
{
    public function __construct(CooperationSubjectEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取某条协作详情
     *
     * @method getCooperationSubjectDetail
     *
     * @param  [type]                      $subjectId [description]
     *
     * @return [type]                                 [description]
     */
    function getCooperationSubjectDetail($subjectId,$param = [])
    {
        $query = $this->entity
                    ->with(["subjectHasOneUser" => function($query){
                        $query->select("user_id","user_name");
                    }])
                    ->with(["subjectBelongsToSort" => function($query){
                        $query->select("cooperation_sort_id","cooperation_sort_name");
                    }])
                    ->with("subjectHasManyUser","subjectHasManyRole","subjectHasManyDept","subjectHasManyManage")
                    ->with(["subjectHasManyPurview.purviewHasOneUser" => function($query){
                            $query->select("user_id","user_name");
                      }]);
                    // 编辑删除权限判断
                    if(isset($param["user_id"])) {
                        $query->with(["subjectHasManyManageForPower" => function($query) use ($param){
                            $query->where(['user_id' => [$param["user_id"]]]);
                        }]);
                    }
        return $query->find($subjectId);
    }

    /**
     * 验证协作主题的权限，用在获取详情前
     * @param  [type] $subjectId [description]
     * @return [type]          [description]
     */
    public function verifySubjectPurview($subjectId,$param) {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $query = $this->entity->select(['subject_id']);
        $query = $query->where(function ($query) use($subjectId,$userId,$roleId,$deptId){
            $query = $query->where('subject_id',$subjectId);
            $query = $query->where(function ($query) use($userId,$roleId,$deptId) {
                $query->where('subject_dept','all')
                    ->orWhere('subject_role','all')
                    ->orWhere('subject_user','all');
                if($deptId) {
                    $query->orWhereHas('subjectHasManyDept', function ($query) use ($deptId) {
                        $query->wheres(['dept_id' => [explode(",", trim($deptId,",")), 'in']]);
                    });
                }
                if($roleId) {
                    $query->orWhereHas('subjectHasManyRole', function ($query) use ($roleId) {
                        $query->wheres(['role_id' => [explode(",", trim($roleId,",")), 'in']]);
                    });
                }
                if($userId) {
                    $query->orWhereHas('subjectHasManyUser', function ($query) use ($userId) {
                        $query->wheres(['user_id' => [$userId]]);
                    });
                    $query->orWhereHas('subjectHasManyManage', function ($query) use ($userId) {
                        $query->wheres(['user_id' => [$userId]]);
                    });
                    $query->orWhere('subject_creater',$userId);
                }
            });
        });
        return $query->get();
    }

    /**
     * 协作主题表信息列表
     * @param  [array] $param [description]
     * @return [type]       [description]
     */
    public function getCooperationSubjectList($param)
    {
        $date = isset($param['date']) ? $param['date'] : date('Y-m-d', time());
        $begin = date('Y-m-d'.' 00:00:00', strtotime($date));
        $end = date('Y-m-d'.' 23:59:59', strtotime($date));
        // 验证权限的时候，用户id必填！
        $userId     = isset($param["user_id"]) ? $param["user_id"]:"";
        if($userId == "") {
            return $this->entity;
        }
        $roleId        = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId        = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $validDate     = isset($param["search"]["valid_date"]) ? $param["search"]["valid_date"][0]:false;
        $subjectTime   = isset($param["search"]["subject_time"]) ? $param["search"]["subject_time"][0]:false;
        $manageUser    = isset($param["search"]["manage_user_id"]) ? $param["search"]["manage_user_id"][0]:false;
        $purviewImport = isset($param["search"]["purview_import"]) ? $param["search"]["purview_import"][0]:false;
        $purviewTime   = isset($param["search"]["purview_time"]) ? $param["search"]["purview_time"][0]:false;
        $invalid = false;
        $hadEnd = false;
        if(isset($param["search"]["status"])){
            if($param["search"]["status"][0] == 'hadEnd'){
                $hadEnd = isset($param["search"]["status"]) ? $param["search"]["status"][0]:false;
            }else if($param["search"]["status"][0] == 'invalid'){
                $invalid = isset($param["search"]["status"]) ? $param["search"]["status"][0]:false;
            }
            unset($param["search"]["status"]);
        }else{
            $hadEnd        = isset($param["search"]["hadEnd"]) ? $param["search"]["hadEnd"][0]:false;
            $invalid       = isset($param["search"]["invalid"]) ? $param["search"]["invalid"][0]:false;
        }
        if($purviewImport !== false) {
            unset($param["search"]["purview_import"]);
        }
        if($purviewTime !== false) {
            unset($param["search"]["purview_time"]);
        }
        if($hadEnd !== false) {
            unset($param["search"]["hadEnd"]);
        }
        if($invalid !== false) {
            unset($param["search"]["invalid"]);
        }
        $excludeOvertimeCooperation = true;

        // 判断是否传了search参数
        if(isset($param["search"])) {
            $excludeOvertimeCooperation = false;
        }
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['cooperation_subject.subject_id'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity;

        $currentTime = date("Y-m-d H:i:s",time());
        // 传 invalid 筛选，就把未生效和过期协作展示出来，否则，不展示
        if($hadEnd == "hadEnd" || $invalid == "invalid") {
            // 筛选出未生效和过期协作
            $query = $query->where(function ($query) use($currentTime,$hadEnd,$invalid,$userId) {
                if($hadEnd == "hadEnd") {
                    $query->where('subject_end','<=', $currentTime)
                          ->where('subject_end','!=', '0000-00-00 00:00:00')
                          ->whereNotNull('subject_end');
                }
                if($invalid == "invalid") {
                    $query->where('subject_start','>', $currentTime);
                    if($userId) {
                        $query = $query->where(function ($query) use($userId) {
                            $query = $query->whereHas('subjectHasManyManage', function ($query) use ($userId) {
                                $query->wheres(['user_id' => [$userId]]);
                            });
                            $query = $query->orWhere('subject_creater',$userId);
                        });
                    }
                }
            });
        } else if (!isset($param['revert'])) {
            // 有效的默认条件
            $query = $query->where(function ($query) use($currentTime) {
                $query->where('subject_start','<=', $currentTime)
                    ->where(function ($query) use($currentTime) {
                        $query->orWhere('subject_end','>', $currentTime)
                            ->orWhere('subject_end','=', '0000-00-00 00:00:00')
                            ->orWhereNull('subject_end');
                });
            });
        }
        // 查询条件处理
        // 有效时间
        if($validDate !== false) {
            unset($param["search"]["valid_date"]);
            $query = $query->where(function ($query) use($validDate) {
                if($validDate["0"]) {
                    $query->where('subject_start','>=', $validDate["0"]);
                }
                if($validDate["1"]) {
                    $query->where('subject_end','<=', $validDate["1"]);
                }
            });
        }
        // 创建时间
        if($subjectTime !== false) {
            unset($param["search"]["subject_time"]);
            $query = $query->where(function ($query) use($subjectTime) {
                if($subjectTime["0"]) {
                    $query->where('subject_time','>=', $subjectTime["0"]);
                }
                if($subjectTime["1"]) {
                    $query->where('subject_time','<=', $subjectTime["1"]);
                }
            });
        }
        if($manageUser !== false) {
            unset($param["search"]["manage_user_id"]);
            $query = $query->whereHas('subjectHasManyManage', function ($query) use ($manageUser) {
                        $query->whereIn("user_id",$manageUser);
                    });
        }
        // 筛选[我关注的][未读的]
        if($purviewImport || $purviewTime) {
            $query = $query->whereHas('subjectHasManyPurview', function ($query) use ($userId,$purviewImport,$purviewTime) {
                    $query->wheres(['user_id' => [$userId]]);
                    if($purviewImport !== false) {
                        $query->where("purview_import",$purviewImport);
                    }
                    if($purviewTime !== false) {
                        $query->where("purview_time",'0000-00-00 00:00:00');
                    }
                });
        }
       
        

        //关联，[关注&未读]数据
        $query = $query->with(["subjectHasManyPurview" => function($query) use ($userId){
                    $query->select("*")->where(['user_id' => [$userId]]);
                }]);
        $query = $query->leftJoin('cooperation_purview', 'cooperation_subject.subject_id', '=', 'cooperation_purview.subject_id')->where(['user_id' => [$userId]])->orderBy("cooperation_purview.purview_import", 'desc');
        // 获取最新回复
        if (isset($param['revert']) && $param['revert']) {
            $query->with(["subjectHasManyRevert" => function($query) use ($userId, $begin, $end){
                $query->with(['firstRevertHasManyRevert', 'revertHasOneBlockquote'])->where(['revert_user' => [$userId]])->whereBetween('revert_time', [$begin,$end])->orderBy("cooperation_revert.revert_time", 'desc');
                }]);
        }
       
        $query = $query->select($param['fields'])
                ->wheres($param['search'])
                ->orders($param['order_by'])
                ->with(["subjectHasOneUser" => function($query){
                    $query->select("user_id","user_name")
                    ->withTrashed();
                }]);
        // 判断权限
        $query = $query->where(function ($query) use($deptId,$roleId,$userId) {
                $query = $query->where('subject_user','all')
                                ->orWhere('subject_role','all')
                                ->orWhere('subject_dept','all');
                if($deptId) {
                    $query = $query->orWhereHas('subjectHasManyDept', function ($query) use ($deptId) {
                        $query->wheres(['dept_id' => [explode(",", trim($deptId,",")), 'in']]);
                    });
                }
                if($roleId) {
                    $query = $query->orWhereHas('subjectHasManyRole', function ($query) use ($roleId) {
                        $query->wheres(['role_id' => [explode(",", trim($roleId,",")), 'in']]);
                    });
                }
                if($userId) {
                    $query = $query->orWhereHas('subjectHasManyUser', function ($query) use ($userId) {
                        $query->wheres(['user_id' => [$userId]]);
                    });
                    $query = $query->orWhereHas('subjectHasManyManage', function ($query) use ($userId) {
                        $query->wheres(['user_id' => [$userId]]);
                    });
                    $query = $query->orWhere('subject_creater',$userId);
                }
        });
        // 编辑删除权限判断
        if($userId !== false) {
            $query = $query->with(["subjectHasManyManageForPower" => function($query) use ($userId){
                        $query->where(['user_id' => [$userId]]);
                    }]);
        }
        // 关联协作分类名称
        $query = $query->with(["subjectBelongsToSort" => function($query){
                    $query->select("cooperation_sort_id","cooperation_sort_name");
                }]);
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 获取协作列表数量
     *
     * @method getCooperationSubjectListTotal
     *
     * @param  [type]                         $param [description]
     *
     * @return [type]                                [description]
     */
    function getCooperationSubjectListTotal($param)
    {
        $param["page"] = 0;
        $param["returntype"] = isset($param["returntype"])?$param["returntype"]:"count";
        return $this->getCooperationSubjectList($param);
    }
    // 获取有查看权限的协作列表
    function getHasPurviewSubjectList($param) {
        $query = $this->entity->select(['subject_id']);
        $userId     = isset($param["user_id"]) ? $param["user_id"]:"";
        if($userId == "") {
            return $this->entity;
        }
        $roleId        = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId        = isset($param["dept_id"]) ? $param["dept_id"]:"";
        // 判断权限
        $query = $query->where(function ($query) use($deptId,$roleId,$userId) {
            $query = $query->where('subject_user','all')
                            ->orWhere('subject_role','all')
                            ->orWhere('subject_dept','all');
            if($deptId) {
                $query = $query->orWhereHas('subjectHasManyDept', function ($query) use ($deptId) {
                    $query->wheres(['dept_id' => [explode(",", trim($deptId,",")), 'in']]);
                });
            }
            if($roleId) {
                $query = $query->orWhereHas('subjectHasManyRole', function ($query) use ($roleId) {
                    $query->wheres(['role_id' => [explode(",", trim($roleId,",")), 'in']]);
                });
            }
            if($userId) {
                $query = $query->orWhereHas('subjectHasManyUser', function ($query) use ($userId) {
                    $query->wheres(['user_id' => [$userId]]);
                });
                $query = $query->orWhereHas('subjectHasManyManage', function ($query) use ($userId) {
                    $query->wheres(['user_id' => [$userId]]);
                });
                $query = $query->orWhere('subject_creater',$userId);
            }
        });
        return $query->get();
    }
    function getCooperationManageUser($subjectId) {
        if (!$subjectId) {
            return ['code' => ['0x000003','common']];
        }

        $query = $this->entity;
        return $query = $query->select(["cooperation_subject.subject_id", "subject_manage", "cooperation_subject_manage.user_id"])->leftJoin('cooperation_subject_manage', function($join) {
            $join->on('cooperation_subject.subject_id', '=', 'cooperation_subject_manage.subject_id');
        })->where('cooperation_subject.subject_id', $subjectId)->get()->toArray();
    }
    public function getSortDetail($sortId) {
        $query = $this->entity;
        return $query = $query->leftJoin('cooperation_subject_user', function($join) {
            $join->on('cooperation_subject.subject_id', '=', 'cooperation_subject_user.subject_id');
        })->leftJoin('cooperation_subject_role', function($join) {
            $join->on('cooperation_subject.subject_id', '=', 'cooperation_subject_role.subject_id');
        })->leftJoin('cooperation_subject_department', function($join) {
            $join->on('cooperation_subject.subject_id', '=', 'cooperation_subject_department.subject_id');
        })
        ->where('cooperation_subject.subject_id', '=', $sortId)->get()->toArray();
    }
    /**
     * 获取即将开始的协作列表
     *
     * @param
     *
     * @return array 即将开始的协作列表
     *
     */
    public function listBeginCooperation($begin,$end)
    {
        $param['fields']    = isset($param['fields']) ? $param['fields'] : ['*'];
        $query = $this->entity
            ->select($param['fields'])
            // ->where('subject_enabled', '!=', 2)
            ->whereBetween('subject_start', [$begin,$end]);
        return $query->get()->toArray();
    }
}
