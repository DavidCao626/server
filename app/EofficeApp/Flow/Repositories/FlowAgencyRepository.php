<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowAgencyEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程委托表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowAgencyRepository extends BaseRepository
{
    public function __construct(FlowAgencyEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_agency表的数据
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowAgencyList($param = [])
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['start_time'=>'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                      ->select($param['fields'])
                      ->wheres($param['search'])
                      ->orders($param['order_by'])
                      ->with(["flowAgencyHasOneAgentUser" => function($query){
                            $query->select("user_id","user_name")->withTrashed();
                        }])
                      ->with(["flowAgencyHasOneByAgentUser" => function($query){
                            $query->select("user_id","user_name")->withTrashed();
                        }])
                      ->with("flowAgencyHasManyFlowAgencyDetail");
        // 分组参数
        if(isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        return $query->get();
    }

    /**
     * 判断每个办理人是否有委托流程
     * @param array $param
     * @return mixed
     */
    function checkFlowHaveAgentRepository($param = [])
    {
        $userId      = $param["user_id"];
        $flowId      = intval($param["flow_id"]);
        $currentTime = date("Y-m-d H:i:s",time());
        $query = $this->entity
            ->where("by_agent_id",$userId)
            ->where("status",'!=','1')
            ->whereRaw("(INSTR(flow_id_string,'".$flowId.",')=1 OR INSTR(flow_id_string,',".$flowId.",')>0)")
            ->where(function ($query) use($currentTime) {
                $query = $query->where(function($query) use ($currentTime){
                    $query = $query->where('start_time','<',$currentTime)->where('end_time','>',$currentTime);
                })
                ->orWhere(function($query) use ($currentTime){
                    $query = $query->where('start_time','<',$currentTime)->where('end_time','0000-00-00 00:00:00');
                });
            });
            //->whereRaw("(('".$currentTime."' BETWEEN start_time AND end_time) OR ('".$currentTime."' > start_time AND end_time = '0000-00-00 00:00:00'))");
        return $query->get();
    }

    /**
     * 判断某个用户是否已经将某条流程委托出去了。
     *
     * @method checkFlowHaveAgentRepository
     *
     * @param  array                        $param [description]
     *
     * @return [type]                              [description]
     */
    function checkFlowAlreadyAgentRepository($param = [])
    {

        $userId      = $param["user_id"];
        $flowId      = intval($param["flow_id"]);
        $currentTime = date("Y-m-d H:i:s",time());
        $startTime   = $param['start_time'] ?? '';
        $endTime     = $param['end_time'] ?? '';
        $query = $this->entity
                      ->where("by_agent_id",$userId)
                      ->where("status",'!=','1')
                      ->whereRaw("(INSTR(flow_id_string,'".$flowId.",')=1 OR INSTR(flow_id_string,',".$flowId.",')>0)");
        if (!empty($startTime) && !empty($endTime)) {
            //$query = $query->whereRaw("((end_time = '0000-00-00 00:00:00' || '".$startTime."' < end_time) && ('".$endTime."' > start_time))");
            $query = $query->where(function ($query) use($startTime, $endTime) {
                $query = $query->where(function($query) use ($startTime, $endTime){
                    $query = $query->where('end_time','>',$startTime)->orWhere('end_time','0000-00-00 00:00:00');
                })->where('start_time','<',$endTime);
            });
        } else {
            //$query = $query->whereRaw("(( end_time = '0000-00-00 00:00:00' ) OR ('".$currentTime."' < end_time AND end_time != '0000-00-00 00:00:00'))");
            $query = $query->where(function ($query) use($currentTime) {
                $query = $query->where(function($query) use ($currentTime){
                    $query = $query->where('end_time','>',$currentTime)->where('end_time','!=', '0000-00-00 00:00:00');
                })->orWhere('end_time','0000-00-00 00:00:00');
            });
        }
        return $query->get();
    }

    /**
     * 更新 flow_agency 的数据，这里默认是多维的where条件。
     *
     * @method updateFlowRunProcessData
     *
     * @param  array                    $param [data:数据;wheres:可以批量解析的条件;whereRaw:原生解析的条件]
     *
     * @return [type]                          [description]
     */
    function updateFlowAgencyData($param = [])
    {
        $data  = $param["data"];
        $query = $this->entity->wheres($param["wheres"]);
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析 whereIn
        if(isset($param['whereIn'])) {
            foreach ($param['whereIn'] as $key => $whereIn) {
                $query = $query->whereIn("flow_agency_id",$whereIn);
            }
        }
        return (bool) $query->update($data);
    }
    /**
     * 获取节点默认主办人
     *
     */
    function getFlowAgencyUserInfo($params) {
        $query = $this->entity;
        if (is_array($params['user_id']) && count($params['user_id']) > 1000) {
            $chunks = array_chunk($params['user_id'], 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $ch) {
                    $query = $query->orWhereIn('agent_id',$ch);
                }
            });
            unset($chunks);
            unset($params['user_id']);
        }else{
            $query = $query->whereIn('agent_id',$params['user_id']);
        }
        $query = $query->where('flow_id_string','!=','');
        if(isset($params['flow_agency_id']) && !empty($params['flow_agency_id'])) {
            if (is_array($params['flow_agency_id']) && count($params['flow_agency_id']) > 1000) {
                $_chunks = array_chunk($params['flow_agency_id'], 1000);
                $query = $query->where(function ($query) use ($_chunks) {
                    foreach ($_chunks as $ch) {
                        $query = $query->orWhereIn('flow_agency_id',$ch);
                    }
                });
                unset($_chunks);
                unset($params['flow_agency_id']);
            }else{
                $query = $query->whereIn('flow_agency_id',$params['flow_agency_id']);
            }
        }
        return $query->get();
    }
    /**
     * 判断办理人是否有委托人
     *
     * @method checkFlowHaveAgentRepository
     *
     * @param  array                        $param [description]
     *
     * @return [type]                              [description]
     */
    function checkUsersFlowHaveAgentRepository($param = [])
    {
        $userId      = $param["user_id"];
        $flowId      = intval($param["flow_id"]);
        if (is_string( $userId)) {
            $userId = explode(',',  $userId );
        }
        $currentTime = date("Y-m-d H:i:s",time());
        $query = $this->entity
                      ->select('by_agent_id')
                      ->whereIn("by_agent_id",$userId)
                      ->where("status",'!=','1')
                      ->whereRaw("FIND_IN_SET(?,`flow_id_string`)" , [$flowId])
                      //->whereRaw("(('".$currentTime."' BETWEEN start_time AND end_time) OR ('".$currentTime."' > start_time AND end_time = '0000-00-00 00:00:00'))");
                      ->where(function ($query) use($currentTime) {
                          $query = $query->where(function($query) use ($currentTime){
                              $query = $query->where('start_time','<',$currentTime)->where('end_time','>',$currentTime);
                          })
                          ->orWhere(function($query) use ($currentTime){
                              $query = $query->where('start_time','<',$currentTime)->where('end_time','0000-00-00 00:00:00');
                          });
                      });
        return $query->get();
    }
}
