<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryTemplateUserModalEntity;

/**
 * 模板设置信息表
 *
 * @author dp
 *
 * @since  2015-10-20 创建
 */
class DiaryTemplateUserModalRepository extends BaseRepository
{
    public function __construct(DiaryTemplateUserModalEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 基础函数，获取此表数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果
     *
     * @author dp
     *
     * @since  2015-10-21
     */
    public function getDiaryTemplateUserModalList($param = [])
    {
        $default = [
            'fields'    => ['*'],
            // 'page'      => 0,
            // 'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            // 'order_by'  => ['revert_time'=>'desc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
                ->select($param['fields'])
                ->wheres($param['search'])
                // ->with("hasManyUser")
                // ->orders($param['order_by'])
                ;
        // 翻页判断
        // $query = $query->parsePage($param['page'], $param['limit']);
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
     * 查看模板设置信息
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果
     *
     * @author dp
     *
     * @since  2015-10-21
     */
    public function diaryTemplateSetUserList($param = [])
    {
        $default = [
            'fields'    => ['diary_template_user_modal.*',"user.user_name"],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by' => ['user.list_number' => 'ASC', 'user.user_id' => 'ASC'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);

        $query = $this->entity
                ->leftJoin('user', 'user.user_id', '=', 'diary_template_user_modal.user_id');
        // 模板种类（数字1-5，对应：日报,周报,月报,半年报,年报）
        $kindId = $param["template_kind"];
        // 对模板1-3的筛选/查询
        $templateNumber = isset($param["search"]["templateNumber"]) ? $param["search"]["templateNumber"][0]:false;
        if($templateNumber !== false) {
            unset($param["search"]["templateNumber"]);
            if($templateNumber !== "") {
                $query->where("template_kind".$kindId,$templateNumber);
            }
        }
        // echo "<pre>";
        // print_r($param);
        $query = $query
                ->select($param['fields'])
                // ->wheres($param['search'])
                ->multiWheres($param['search'])
                ->orders($param['order_by'])
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
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
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

    function diaryTemplateSetUserListTotal($param) {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->diaryTemplateSetUserList($param);
    }

    /**
     * 此函数用来同步 diary_template_user_modal 里面的用户
     * 查，用户里有，user_modal 没有，且有效的用户
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function templateUserModalCheckAndSyncUser($param = [])
    {
        $default = [
            'fields'    => ['user_system_info.user_id'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by' => ['user_id' => 'ASC'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);

        $query = $this->entity
                ->select($param['fields'])
                ->rightJoin('user_system_info', 'user_system_info.user_id', '=', 'diary_template_user_modal.user_id')
                ->whereNull("diary_template_user_modal.user_id")
                ->where("user_system_info.user_status",">","0")
                ->where("user_system_info.user_status","!=","2")
                ;
        // 翻页判断
        // $query = $query->parsePage($param['page'], $param['limit']);
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
     * 此函数用来查询 diary_template_user_modal 里面的无效用户
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function templateUserModalInvalidUser($param = [])
    {
        $default = [
            'fields'    => ['diary_template_user_modal.user_id'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by' => ['user_id' => 'ASC'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);

        $query = $this->entity
                ->select($param['fields'])
                ->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'diary_template_user_modal.user_id')
                ->whereNull("user_system_info.user_id")
                ->orWhere("user_system_info.user_status","=","2")
                ;
        // 翻页判断
        // $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

}
