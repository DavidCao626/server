<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryEntity;
use DB;

/**
 * 微博Repository类:提供微博表操作
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryRepository extends BaseRepository
{
    public function __construct(DiaryEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查看微博列表
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function diaryList($param = [])
    {
        $default = [
            // 'fields'    => ['diary_id', 'diary_content', 'diary_date', 'created_at', 'replys_user_name', 'diary_user_name','plan_kind','plan_template','plan_status','plan_scope_date_start','plan_scope_date_end','address'],
            'fields'    => ['diary_id', 'diary_content', 'diary_date', 'created_at', 'diary_reply_id', 'diary_reply_content', 'replys_created_at', 'replys_user_name', 'diary_user_name','plan_kind','plan_template','plan_status','plan_scope_date_start','plan_scope_date_end','address'],
            'search'    => [],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['diary_date' => 'desc', 'diary_id' => 'desc'],
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
        ->parseSelect($param['fields'], $this->entity)
        ->with(['replys' => function($query){
            $query->orderBy('created_at','desc');
        }])
        ->wheres($param['search']);
        if (isset($param['mobile'])) {
            $query = $query->withCount('replys');
        }

        $data = $query
        ->orders($param['order_by'])
        ->withCount('replys')
        ->parsePage($param['page'], $param['limit'])
        ->with(['userSystemInfo.userSystemInfoBelongsToDepartment' => function($query) {
              $query->select('dept_id', 'dept_name');
          }])
        ->with(['userHasManyRole.hasOneRole' => function($query) {
              $query->select('role_id', 'role_name');
          }])
        ->with(['diaryLikePeople' => function($query) {
              $query->select('diary_id', 'user_id');
          }])
        ->get()
        ->toArray();

        // 统计点赞人数
        if ($data) {
            foreach ($data as $k => &$v) {
                if (isset($v['diary_like_people'])) {
                    $v['diary_like_people']['count'] = count($v['diary_like_people']);
                    // 查询出姓名
                    foreach ($v['diary_like_people'] as $key => &$value) {
                        if (isset($value['user_id'])) {
                           $value['user_name'] = DB::table('user')->where('user_id',$value['user_id'])->pluck('user_name')->toArray()[0] ?? '';
                        }
                    }
                }
            }
        }
        
        if (isset($param['mobile'])) {
            return $data;
        }

        return $this->parseResult($data, $param['fields'], $this->entity->relationFields);
    }

    /**
     * 查询日志报表
     *
     * @param  array  $search 查询条件
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function diaryReportList($search = [])
    {
        return $this->entity
            ->wheres($search)
            ->get()->toArray();
    }

    /**
     * 查询微博详情
     *
     * @param  array  $search 查询条件
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-12-23
     */
    public function getDiaryDetail($search = [])
    {
        return $this->entity
            ->wheres($search)
            ->first();
    }

    /**
     * 工作计划，获取某条工作计划
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function getDiaryPlanInfo($param) {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by' => ['diary_id' => 'ASC'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity;
        $query = $query
                ->select($param['fields'])
                ->wheres($param['search'])
                // ->orders($param['order_by'])
                // ->with(["revertHasOneUser" => function($query) {
                //     $query->select("user_id","user_name");
                // }])
                // ->with(["firstRevertHasManyRevert" => function($query) {
                //     $query->select("*")
                //             ->with(["revertHasOneUser" => function($query) {
                //                 $query->select("user_id","user_name");
                //             }]);
                // }])
                // ->with(["revertHasOneBlockquote" => function($query) {
                //     $query->select("*")
                //             ->with(["revertHasOneUser" => function($query) {
                //                 $query->select("user_id","user_name");
                //             }]);
                // }])
                ;
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "first") {
            return $query->get()->first();
        }
    }

    /**
     * 更新微博的创建时间为更新时间
     * @param $diaryId
     * @return bool
     */
    public function updateDiaryCreatedAt($diaryId)
    {
        $diary = $this -> entity -> find($diaryId);
        if($diary) {
            $diary -> created_at = $diary -> updated_at;
            return $diary -> save();
        }
        return false;
    }
}
