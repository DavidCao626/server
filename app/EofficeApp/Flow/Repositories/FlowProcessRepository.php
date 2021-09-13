<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 定义流程节点表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessRepository extends BaseRepository
{
    public function __construct(FlowProcessEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_process表的数据
     *
     * @method getFlowProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowProcessList($param = [])
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['process_id'=>'asc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                      ->select($param['fields'])
                      ->with("flowProcessHasManyUser")
                      ->wheres($param['search'])
                      ->orders($param['order_by']);
        if(isset($param['flow_id']) && !empty($param['flow_id'])) {
            if(is_array($param['flow_id'])) {
                $query = $query->whereIn('flow_id',$param['flow_id']);
            }else{
                $query = $query->where('flow_id',$param['flow_id']);
            }
        }
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "first") {
            return $query->first();
        }
    }

    /**
     * 获取定义流程那里的流程节点列表
     *
     * @method getFlowDefineProcessList
     *
     * @param  [type]                   $param [description]
     *
     * @return [type]                          [description]
     */
    function getFlowDefineProcessList($param)
    {
        $flowId = $param["flow_id"];
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['sort'=>'asc','created_at'=>'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->orders($param['order_by'])
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->where("flow_id",$flowId);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "first") {
            return $query->first();
        }
    }

    /**
     * 获取流程节点详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeDetail($nodeId,$fromId = '')
    {
        if (empty($nodeId) || $nodeId == 'undefined') {
            return false;
        }
        if (isset($GLOBALS['getFlowNodeDetail'.$nodeId]) && !empty($GLOBALS['getFlowNodeDetail'.$nodeId]) ) {
            return $GLOBALS['getFlowNodeDetail'.$nodeId];
        }
        $query = $this->entity
                    ->with(["flowProcessHasManyUser" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessHasManyRole" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessHasManyDept" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with("flowProcessHasManyCopyUser")
                    ->with("flowProcessHasManyCopyRole")
                    ->with("flowProcessHasManyCopyDept")
                    ->with("flowProcessHasManyControlOperation.controlOperationDetail")
                    ->with("flowProcessHasManyOutsend.outsendHasManyFields")
                    ->with("flowProcessHasManyOutsend.outsendHasManyDependentFields")
                    ->with(["flowProcessHasManyOutsend"=> function($query) {
                        $query->orderBy('id','asc');
                    }])
                    ->with(["flowProcessHasManySunWorkflow" => function($query){
                        $query->orderBy('id', 'asc');
                    }])
                    ->with(["flowProcessHasManyOverTimeRemind"=> function($query) {
                        $query->orderBy('id','asc');
                    }])
                    ->with(["flowProcessHasManyDefaultUser.hasOneUser" => function ($query) {
                        $query->select("user_id","user_name")->withTrashed();
                    }])
                    ->with(["flowProcessHasManyDefaultUser" => function($query
                        ) {
                        $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_process_default_user.user_id')->whereNotIn('user_system_info.user_status', [0,2])->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessDefaultUserHostHasOneUser" => function ($query) {
                        $query->select("user_id","user_name")->where("user_accounts", "!=", "")->withTrashed();
                    }])
                    ->with("flowProcessHasManyOutCondition")
                    ->with("flowProcessHasManyDataValidate")
                    ;
        $datas = $query->where('node_id' , $nodeId)->get();
        if ($datas->isEmpty()) {
          return $datas;
        }
        foreach ($datas as $k1 => $v1) {
            $GLOBALS['getFlowNodeDetail'.$v1['node_id']] = $v1;
        }
        return $GLOBALS['getFlowNodeDetail'.$nodeId] ;
    }
    /**
     * 获取流程抄送节点详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeCopyDetail($nodeId)
    {
       $query = $this->entity
                    ->where('node_id' , $nodeId)
                    ->with("flowProcessHasManyCopyUser")
                    ->with("flowProcessHasManyCopyRole")
                    ->with("flowProcessHasManyCopyDept");
          return  $query->first();
    }

    /**
     * 获取流程子流程详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeSunFlowDetail($nodeId)
    {
       $query = $this->entity
                    ->where('node_id' , $nodeId)
                    ->with(["flowProcessHasManySunWorkflow" => function($query){
                        $query->orderBy('id', 'asc');
                    }]);
          return  $query->first();
    }

       /**
     * 获取流程节点详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeUserDetail($nodeId)
    {
        $query = $this->entity
                    ->where('node_id' , $nodeId)
                    ->with(["flowProcessHasManyUser" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessHasManyRole" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessHasManyDept" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessHasManyDefaultUser.hasOneUser" => function ($query) {
                        $query->select("user_id","user_name")->withTrashed();
                    }])
                    ->with(["flowProcessHasManyDefaultUser" => function($query
                        ) {
                        $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_process_default_user.user_id')->whereNotIn('user_system_info.user_status', [0,2])->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessDefaultUserHostHasOneUser" => function ($query) {
                        $query->select("user_id","user_name")->where("user_accounts", "!=", "")->withTrashed();
                    }]);
          return  $query->first();

    }
      /**
     * 获取流程节点详情
     *
     * @method
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeUserInfoDetail($nodeId)
    {
        $query = $this->entity
                    ->where('node_id' , $nodeId)
                    ->with(["flowProcessHasManyUser" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessHasManyRole" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }])
                    ->with(["flowProcessHasManyDept" => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }]);
          return  $query->first();

    }
     /**
     * 获取外发和超时详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeOutsendDetail($nodeId)
    {
        $query = $this->entity
                    ->where('node_id' , $nodeId)
                    ->select('flow_outsend_toggle' , 'node_id' , 'press_add_hour_turn','process_name')
                     ->with(["flowProcessHasManyOverTimeRemind"=> function($query) {
                        $query->orderBy('id','asc');
                    }])
                    ->with("flowProcessHasManyOutsend");
          return  $query->first();

    }
    /**
     * 获取流程节点字段控制详情
     *
     * @method getFlowNodeControlOperationDetail
     *
     * @param  [string]            $nodeId [description]
     * @param  [string]            $formId [description]
     *
     * @return [type]                    [description]
     */
    public function getFlowNodeControlOperationDetail($nodeId,$fromId = '')
    {
        if (!empty($GLOBALS['getNodeControlOperationDetail'.$nodeId])) {
            return $GLOBALS['getNodeControlOperationDetail'.$nodeId];
        }
        $query = $this->entity->with("flowProcessHasManyControlOperation.controlOperationDetail");
        $GLOBALS['getNodeControlOperationDetail'.$nodeId] = $query->find($nodeId);
        return $GLOBALS['getNodeControlOperationDetail'.$nodeId] ;
    }
    /**
     * 获取外发详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeOutsendInfoDetail($nodeId)
    {
        $query = $this->entity
                    ->where('node_id' , $nodeId)
                    ->select('flow_outsend_toggle' , 'process_name')
                    ->with("flowProcessHasManyOutsend");
          return  $query->first();

    }

    /**
     * 获取连接到某一节点的所有
     *
     */
    function getTargetNodeId($nodeId)
    {
        $query = $this->entity
        ->select('node_id','process_to');
        $query->WhereRaw('find_in_set(?,process_to)' , [$nodeId]);
        return $query->get();
    }
    /**
     * 获取一条流程默认主办人人员中离职人员数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowQuitUserList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_process.process_default_manage');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id')
        ->select(['user.user_name','flow_process.process_default_manage as user_id','flow_process.node_id'])
        ->where("flow_process.process_default_manage",'!=','')
        ->where("flow_process.flow_id",$id)
        ->where("user_system_info.user_status",2);
        return $query->get()->toArray();
    }
    /**
     * 获取一条流程默认主办人数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowHandleUserList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_process.process_default_manage')
        ->select(['user.user_name','flow_process.process_default_manage as user_id','flow_process.node_id'])
        ->where("flow_process.process_default_manage",'!=','')
        ->where("flow_process.flow_id",$id);
        return $query->get()->toArray();
    }

    // 从flow_process表出发，查当前用户可以新建的固定流程，传入后面的getlist里面去，避免速度慢的问题
    function getFixedFlowTypeInfoByUserInfo($param) {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['flow'=>'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        // unset($param['search']['fieldSearchVal']);
        // unset($param['search']['favorite']);
        $query = $this->entity
                      ->select($param['fields'])
                      // ->wheres($param['search'])
                      ->where('head_node_toggle',"1")
                      ->where(function ($query) use($userId,$roleId,$deptId){
                          $query->where('process_user','ALL');
                          $query->orWhere('process_role','ALL');
                          $query->orWhere('process_dept','ALL');
                          if($userId) {
                              $query->orWhereHas('FlowProcessHasManyUser', function ($query) use ($userId) {
                                  $query->wheres(['user_id' => [$userId]]);
                              });
                          }
                          if($roleId) {
                              $query->orWhereHas('FlowProcessHasManyRole', function ($query) use ($roleId) {
                                  $query->wheres(['role_id' => [explode(",", trim($roleId,",")), 'in']]);
                              });
                          }
                          if($deptId) {
                              $query->orWhereHas('FlowProcessHasManyDept', function ($query) use ($deptId) {
                                  $query->wheres(['dept_id' => [explode(",", trim($deptId,",")), 'in']]);
                              });
                          }
                      });
                      // ->orders($param['order_by']);
        if(isset($param['search']['flow_id']) && !empty($param['search']['flow_id'])) {
            $tempSearchFlowId = ['flow_id' => $param['search']['flow_id']];
            $query = $query->wheres($tempSearchFlowId);
            // if(is_array($param['search']['flow_id'])) {
            //     $query = $query->whereIn('flow_id',$param['search']['flow_id']);
            // }else{
            //     $query = $query->where('flow_id',$param['search']['flow_id']);
            // }
        }
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }
    function getFlowProcessListOutSelf($flowId,$search)
    {
        $query = $this->entity
                      ->select(['node_id','process_name as node_name','sort'])
                      ->whereNotIn('node_id',$search)
                      ->where('flow_id',$flowId);
        return $query->get()->toArray();
    }
    function findMaxSort($flowId)
    {
      return $this->entity->where('flow_id',$flowId)->max('sort');
    }
    /**
     * 根据流程ID 获取所有有流出节点的节点
     *
     */
    function getAllNodeDetail($flowId) {
        if (!$flowId) {
            return ['code' => ['0x000003', 'common']];
        }
        return $this->entity->where('flow_id',$flowId)->where("process_to", '!=', '')->get();
    }
    /**
     * 根据流程ID 获取所有节点
     *
     */
    function getAllNodeByFlowId($flowId) {
        if($flowId == 'all') {
            return $this->entity->get();
        }
        return $this->entity->whereIn('flow_id',$flowId)->get();
    }
    /**
     * 获取智能获取
     *
     */
    function getFlowProcessAutoGetUserInfo($param = [])
    {
        $query = $this->entity;
        if(isset($param['search']['flow_id'][0]) && is_array($param['search']['flow_id'][0])) {
            if (count($param['search']['flow_id'][0]) > 1000) {
                $chunks = array_chunk($param['search']['flow_id'][0], 1000);
                $query = $query->where(function ($query) use ($chunks) {
                    foreach ($chunks as $ch) {
                        $query = $query->orWhereIn('flow_id',$ch);
                    }
                });
                unset($chunks);
                unset($param['search']['flow_id']);
            }
        }
        $query = $query->wheres($param['search']);
        return $query->get();
    }
    /**
     * 获取节点默认主办人
     *
     */
    function getProcessDefaultManageUserInfo($allNodeIds,$all_quit_user_ids) {
        $query = $this->entity;
        if (is_array($allNodeIds) && count($allNodeIds) > 1000) {
            $chunks = array_chunk($allNodeIds, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $ch) {
                    $query = $query->orWhereIn('node_id',$ch);
                }
            });
            unset($chunks);
            unset($allNodeIds);
        }else{
            $query = $query->whereIn('node_id',$allNodeIds);
        }
        if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
            $_chunks = array_chunk($all_quit_user_ids, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $_ch) {
                    $query = $query->orWhereIn('process_default_manage',$_ch);
                }
            });
            unset($_chunks);
            unset($all_quit_user_ids);
        }else{
            $query = $query->whereIn('process_default_manage',$all_quit_user_ids);
        }
        return $query->get();
    }
    /**
     * 获取某个流程的node
     * @param  [type] $flowId [description]
     * @return [type] $sortId [description]
     */
    function findFlowNode($nodeId,$field="*")
    {
        $query = $this->entity
                    ->select($field)
                    ->where('node_id',$nodeId)->first();
        $query = $query ? $query->toArray() : [];
        if($field == "*" || is_array($field)) {
            return $query;
        } else if(is_string($field)) {
            return isset($query[$field]) ? $query[$field] : "";
        }
    }
    /**
     * 根据流程ID 获取首节点的节点ID
     *
     */
    function getFirstNodeId($flow)
    {
        $flowProcessInfo = $this->entity
                                ->select('node_id')
                                ->where('flow_id', $flow)
                                ->where('head_node_toggle', '1')
                                ->first();
        if (empty($flowProcessInfo) || !isset($flowProcessInfo->node_id)) {
            return false;
        }
        return $flowProcessInfo->node_id;
    }

    /**
     *通过run_id、flow_id获取流程节点、节点外发记录等信息
     *
     * @author  zyx
     *
     * @param   mixed   $param
     *
     * @return  array
     */
    public function getFlowOutsendList($param)
    {
        $run_id = $param['run_id'];
        $flow_id = $param['flow_id'];

        $query = $this->entity;
        // 获取各节点信息
        $result = $query
            ->with(['flowProcessHasManyOutsend' => function ($query) {
                $query->select('id', 'node_id', 'custom_module_menu')->orderBy('id', 'asc');
            }])
            ->select('node_id', 'sort', 'process_name')
            ->where('flow_id', '=', $flow_id)
            ->orderBy('sort')
            ->get()
            ->toArray();

        // 遍历结果,找出每个节点的外发记录 //失败记录
        foreach ($result as $k => $v) {
            if ($v['flow_process_has_many_outsend'] != []) {// 该节点有外发
                $relation_id_str = "(";
                foreach ($v['flow_process_has_many_outsend'] as $vv) {
                    $relation_id_str .= "'" . $run_id . ',' . $vv['id'] . "',";
                }
                $relation_id_str = trim($relation_id_str, ',') . ")";
                $result[$k]['log'] = DB::table('system_flow_log')
                    ->select(DB::raw("log_id, log_content, log_time, is_failed, log_relation_id, substring(log_relation_id, locate(',', log_relation_id)+1) as outsend_id"))
                    ->whereRaw("log_type = 'outsend' and log_relation_field = 'run_id,outsend_id' and log_relation_id in $relation_id_str")
                    ->orderBy('outsend_id', 'asc')
                    ->get()
                    ->toArray();
                // and log_relation_table = 'flow_run,flow_process'   关联表条件删除，以适应对外部数据库外发记录的读取
                // and ((is_failed = 1) or (is_failed is null and log_content like '%数据外发失败%')) 此处查出节点所有外发记录，不论成功或失败，方便确定是哪个外发
            }
        }

        return $result;
    }

    /**
     * 获取节点控制属性
     * @param $nodeId
     * @return mixed
     */
    function getFlowNodeControlOperations($nodeId)
    {
        $datas = DB::select("SELECT a.operation_id AS ao_id, a.node_id, a.control_id, b.* FROM flow_process_control_operation AS a LEFT JOIN flow_process_control_operation_detail AS b ON a.operation_id = b.operation_id WHERE node_id = $nodeId");
        $returnData = [];
        if ($datas) {
            $opIdArr = [];
            $i = 0;
            foreach ($datas as $k => $v) {
                if (is_object($v)) {
                    $tmp = json_decode(json_encode($v), TRUE);
                } else {
                    $tmp = $v;
                }
                // 当前op_id已经统计过
                if (in_array($tmp['ao_id'], array_keys($opIdArr))) {
                    $numDetail = count($returnData[$opIdArr[$tmp['operation_id']]]['control_operation_detail']);
                    $returnData[$opIdArr[$tmp['operation_id']]]['control_operation_detail'][$numDetail]['auto_detail_id'] = $tmp['auto_detail_id'];
                    $returnData[$opIdArr[$tmp['operation_id']]]['control_operation_detail'][$numDetail]['operation_id'] = $tmp['operation_id'];
                    $returnData[$opIdArr[$tmp['operation_id']]]['control_operation_detail'][$numDetail]['operation_type'] = $tmp['operation_type'];
                } else { // 当前op_id尚未统计过
                    $opIdArr[$tmp['operation_id']] = $i;

                    $returnData[$i]['operation_id'] = $tmp['ao_id'];
                    $returnData[$i]['node_id'] = $tmp['node_id'];
                    $returnData[$i]['control_id'] = $tmp['control_id'];
                    $returnData[$i]['control_operation_detail'][0]['auto_detail_id'] = $tmp['auto_detail_id'];
                    $returnData[$i]['control_operation_detail'][0]['operation_id'] = $tmp['operation_id'];
                    $returnData[$i]['control_operation_detail'][0]['operation_type'] = $tmp['operation_type'];

                    $i++;
                }
            }
        }
        return $returnData;
    }
    /**
     * 获取流程节点详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeDetailByPermission($param)
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'returntype' => 'first',
        ];
        $param = array_merge($default, array_filter($param));
         $query = $this->entity
                    ->select($param['fields'])
                    ->with(['flowProcessHasManyUser' =>function( $query){
                        $query->select("user_id", "id");
                    } ])
                    ->with(['flowProcessHasManyRole' => function( $query){
                        $query->select("role_id", "id");
                    }])
                    ->with(['flowProcessHasManyDept' => function( $query){
                        $query->select("dept_id", "id");
                    }]);
        if (isset($param['search']['node_id'])) {
          $query =  $query->where('node_id' , $param['search']['node_id']);
        }
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "first") {
            return $query->first();
        }
    }

	function getProcessNames($nodeId){
    	return $this->entity->select('node_id', 'process_name')
    	->whereIn('node_id', $nodeId)
    	->get();
	}


    /**
     * 获取节点部分信息，流程定时触发任务使用
     * @param $nodeId
     * @return array
     */
    public function getNodeInfoSimple($nodeId) {
        $query = $this->entity
                    ->with(["flowProcessHasManyUser" => function ($query) {
                        $query->orderBy("auto_id", "asc");
                    }])
                    ->with(["flowProcessHasManyRole" => function ($query) {
                        $query->orderBy("auto_id", "asc");
                    }])
                    ->with(["flowProcessHasManyDept" => function ($query) {
                        $query->orderBy("auto_id", "asc");
                    }]);
        return $query->find($nodeId)->toArray();
    }
}
