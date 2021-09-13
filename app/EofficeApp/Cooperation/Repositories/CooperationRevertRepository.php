<?php
namespace App\EofficeApp\Cooperation\Repositories;

use App\EofficeApp\Cooperation\Entities\CooperationRevertEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 协作区回复表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationRevertRepository extends BaseRepository
{
    public function __construct(CooperationRevertEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取协作回复列表，取第一级，然后关联子集
     * @param  array $param 查询条件
     * @return array       查询结果
     */
    function getCooperationRevertAllRepository($param) {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['revert_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);
        $date = isset($param['date']) ? $param['date'] : date('Y-m-d', time());
        $begin = date('Y-m-d'.' 00:00:00', strtotime($date));
        $end = date('Y-m-d'.' 23:59:59', strtotime($date));
        $userId     = isset($param["user_id"]) ? $param["user_id"]: "";
        $query = $this->entity;
        if (isset($param['search']) && !empty($param['search'])) {
            $query = $query->wheres($param['search']);
            $query = $query->select($param['fields'])
                ->where("subject_id",$param["subject_id"])
                ->orders($param['order_by'])
                ->with(["revertHasOneUser" => function($query) {
                    $query->select("user_id","user_name");
                }])
                ->with(["firstRevertHasManyRevert" => function($query) {
                    $query->select("*")
                            ->with(["revertHasOneUser" => function($query) {
                                $query->select("user_id","user_name");
                            }]);
                }])
                ->with(["revertHasOneBlockquote" => function($query) {
                    $query->select("*")
                            ->with(["revertHasOneUser" => function($query) {
                                $query->select("user_id","user_name");
                            }]);
                }]);
                if (isset($param['revert']) && $param['revert']) {
                    $query = $query->where(['user_id' => [$userId]])->whereBetween('revert_time', [$begin,$end])->orderBy("cooperation_revert.revert_time", 'desc');
                }
                // 翻页判断
                $query = $query->parsePage($param['page'], $param['limit']);
                // 返回值类型判断
                if($param["returntype"] == "array") {
                    $list = [];
                    $data = $query->get()->toArray();
                    if (!empty($data)) {
                        foreach ($data as $key => $value) {
                            $list = $this->getRevertParent($param, $data[$key]['revert_parent']);
                            if (!empty($list)) {
                                unset($data[$key]);
                            }

                            if (!empty($data)) {
                                $data = array_merge($data, $list);
                            }else{
                                $data = $list;
                            }
                        }
                        $datas = $this->arrayGetOne($data);
                        return $datas;
                    }
                } else if($param["returntype"] == "count") {
                    return $query->count();
                } else if($param["returntype"] == "object") {
                    return $query->get();
                }
        }else{
            $query = $query->select($param['fields'])
                ->where("subject_id",$param["subject_id"]);
                if (!isset($param['revert'])) {
                    $query = $query->where("revert_parent","0");
                }
                
                $query->orders($param['order_by'])
                ->with(["revertHasOneUser" => function($query) {
                    $query->select("user_id","user_name");
                }])
                ->with(["firstRevertHasManyRevert" => function($query) {
                    $query->select("*")
                            ->with(["revertHasOneUser" => function($query) {
                                $query->select("user_id","user_name");
                            }]);
                }])
                ->with(["revertHasOneBlockquote" => function($query) {
                    $query->select("*")
                            ->with(["revertHasOneUser" => function($query) {
                                $query->select("user_id","user_name");
                            }]);
                }]);
        }
        

        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        if (isset($param['revert']) && $param['revert'] && $param["returntype"] == "array") {
            $query = $query->where(['revert_user' => [$userId]])->whereBetween('revert_time', [$begin,$end])->orderBy("cooperation_revert.revert_time", 'desc');
            $list = [];
            $data = $query->get()->toArray();
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $list = $this->getRevertParent($param, $data[$key]['revert_parent']);
                    if (!empty($list)) {
                        unset($data[$key]);
                    }

                    if (!empty($data)) {
                        $data = array_merge($data, $list);
                    }else{
                        $data = $list;
                    }
                }
                $datas = $this->arrayGetOne($data);

                return $datas;
            }
        } else if (isset($param['revert']) && $param['revert'] && $param["returntype"] == "count") {
            $param["returntype"] = "array";
            $query = $query->where(['revert_user' => [$userId]])->whereBetween('revert_time', [$begin,$end])->orderBy("cooperation_revert.revert_time", 'desc');
            $list = [];
            $data = $query->get()->toArray();
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $list = $this->getRevertParent($param, $data[$key]['revert_parent']);
                    if (!empty($list)) {
                        unset($data[$key]);
                    }

                    if (!empty($data)) {
                        $data = array_merge($data, $list);
                    }else{
                        $data = $list;
                    }
                }
                $datas = $this->arrayGetOne($data);

                return count($datas);
            }
        } else if (!isset($param['revert']) && $param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if(!isset($param['revert']) && $param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    // 搜索获取恢复的父级
    public function getRevertParent($param, $parentId) {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['revert_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity;
        $query = $query->select($param['fields'])
            ->where("subject_id",$param["subject_id"])
            ->where("revert_id",$parentId)
            ->orders($param['order_by'])
            ->with(["revertHasOneUser" => function($query) {
                $query->select("user_id","user_name");
            }])
            ->with(["firstRevertHasManyRevert" => function($query) {
                $query->select("*")
                        ->with(["revertHasOneUser" => function($query) {
                            $query->select("user_id","user_name");
                        }]);
            }])
            ->with(["revertHasOneBlockquote" => function($query) {
                $query->select("*")
                        ->with(["revertHasOneUser" => function($query) {
                            $query->select("user_id","user_name");
                        }]);
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

    function arrayGetOne($param){
        $a = array();
        $revertId = [];
        if (!empty($param)) {
            foreach($param as $key => $value) {

                if (!in_array($value, $a)) {
                    $a[$key] = $value;
                }
                if(!in_array($value['revert_id'], $revertId)) {
                    $revertId[] = $value['revert_id'];
                    $a[$key] = $value;
                }else{
                    unset($param[$key]);
                }
            }
        }
        return $a;
    }
    /**
     * 获取协作回复列表
     * 这是个备份！！！
     * @param  array $param 查询条件
     * @return array       查询结果
     */
    function getCooperationRevertAllRepositoryOld($param) {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['revert_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
                ->select($param['fields'])
                ->where("subject_id",$param["subject_id"])
                ->wheres($param['search'])
                ->orders($param['order_by'])
                ->with(["revertHasOneUser" => function($query) {
                    $query->select("user_id","user_name");
                }])
                ->with(["revertHasOneRevert" => function($query) {
                    $query->select("revert_id","revert_content","revert_user","revert_time")
                            ->with(["revertHasOneUser" => function($query) {
                                $query->select("user_id","user_name");
                            }]);
                }])
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
        }
    }

    /**
     * 获取协作回复列表的数量
     *
     * @method getCooperationRevertAllRepositoryTotal
     *
     * @param  [type]                                 $param [description]
     *
     * @return [type]                                        [description]
     */
    function getCooperationRevertAllRepositoryTotal($param) {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getCooperationRevertAllRepository($param);
    }

    /**
     * 获取协作某条回复的详情
     *
     * @method getCooperationRevertDetail
     *
     * @param  [type]                     $revertId [description]
     *
     * @return [type]                               [description]
     */
    function getCooperationRevertDetail($revertId,$param) {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['revert_time'=>'desc'],
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
                ->select($param['fields'])
                ->where("revert_id",$revertId)
                ->wheres($param['search'])
                ->orders($param['order_by'])
                ->with(["revertHasOneUser" => function($query) {
                    $query->select("user_id","user_name");
                }])
                ->with(["revertHasOneRevert" => function($query) {
                    $query->select("revert_id","revert_content","revert_user","revert_time")
                            ->with(["revertHasOneUser" => function($query) {
                                $query->select("user_id","user_name");
                            }]);
                }])
                ;
        return $query->get()->first();
    }

    /**
     * 获取父级回复的回复人
     * @param  int $revertId 父级回复id
     * @return array          回复人id
     */
    function getParentRevertUser($revertId) {
        return $this->entity
                    ->find($revertId)
                    ->revert_user;
    }

    /**
     * 获取[某个协作主题的]相关文档列表
     * @param  string $subjectId 协作主题id
     * @return array       获取相关文档列表结果数据
     */
    function getCooperationAboutDocumentRepository($subjectId)
    {
        return $this->entity
                  ->select("revert_document")
                  ->where('subject_id',$subjectId)
                  ->where('revert_document','!=','')
                  ->get()
                  ->toArray();
    }

    /**
     * 获取[协作主题的某条回复的]相关文档列表
     * @param  string $revertId 协作主题的某条回复的id
     * @return array       获取相关文档列表结果数据
     */
    function getCooperationRevertAboutDocumentRepository($revertId)
    {
        return $this->entity
                    ->find($revertId)
                    ->revert_document;
    }

    /**
     * 获取相关附件列表
     * @param  string $subjectId 协作主题id
     * @return array       获取相关附件列表结果数据
     */
    function getCooperationAboutAttachmentRepository($subjectId)
    {
        return $this->entity
                  ->select(["attachment_id","attachment_name"])
                  ->where('subject_id',$subjectId)
                  ->where('attachment_id','!=','')
                  ->get();
    }

    /**
     * 获取[协作主题的某条回复的]相关附件列表
     * @param  string $revertId 协作主题的某条回复的id
     * @return array       获取相关附件列表结果数据
     */
    function getCooperationRevertAboutAttachmentRepository($revertId)
    {
        return $this->entity
                    ->select(["attachment_id","attachment_name"])
                    ->find($revertId)
                    ->toArray();
    }
}
