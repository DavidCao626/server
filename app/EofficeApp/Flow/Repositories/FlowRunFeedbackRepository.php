<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowRunFeedbackEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程签办反馈表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunFeedbackRepository extends BaseRepository
{
    public function __construct(FlowRunFeedbackEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 【流程签办反馈】，获取签办反馈列表
     *
     * @method getFlowFeedbackListRepository
     *
     * @param  array                         $param [description]
     *
     * @return [type]                               [description]
     */
    function getFlowFeedbackListRepository($param = [])
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['edit_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                      ->select($param['fields'])
                      ->wheres($param['search'])
                      ->where('run_id',$param['run_id'])
                      ->orders($param['order_by'])
                      // ->forPage($param['page'], $param['limit'])
                      ->with(["flowRunFeedbackHasOneUser" => function($query){
                              $query = $query->select("user.user_id","user.user_name" , 'user_system_info.dept_id' , 'department.dept_name')->withTrashed();
                              $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'user.user_id');
                              $query = $query->leftJoin('department', 'department.dept_id', '=', 'user_system_info.dept_id');
                        }])
                      ->with(["flowRunFeedbackHasOneNode" => function($query){
                              $query->select("process_name","node_id");
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
     * 【流程签办反馈】，获取签办反馈列表数量
     *
     * @method getFlowFeedbackListRepository
     *
     * @param  array                         $param [description]
     *
     * @return [type]                               [description]
     */
    function getFeedbackListTotal($param = [])
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['edit_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity
                      ->select($param['fields'])
                      ->wheres($param['search'])
                      ->where('run_id',$param['run_id'])
                      ->count();
    }

    /**
     * 获取某条流程签办反馈详情
     *
     * @method getFlowFeedBackDetail
     *
     * @param  [type]                $feedbackId [description]
     * @param  [type]                $param      [description]
     *
     * @return [type]                            [description]
     */
    function getFlowFeedBackDetail($feedbackId,$param) {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['edit_time'=>'desc'],
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
                ->select($param['fields'])
                ->where("feedback_id",$feedbackId)
                ->wheres($param['search'])
                ->orders($param['order_by'])
                ->with(["flowRunFeedbackHasOneUser" => function($query) {
                    $query->select("user_id","user_name")->withTrashed();
                }])
                ;
        return $query->first();
    }

    /**
     * 计算生成某个人员在某条流程的下一个签办反馈的id
     *
     * @method getNextRunFeedbackIdRepository
     *
     * @param  [type]                         $runId [description]
     *
     * @return [type]                                [description]
     */
    function getNextRunFeedbackIdRepository($runId,$userId)
    {
        $query = $this->entity
                      ->selectRaw("MAX(run_feedback_id) cnt")
                      ->where('run_id',$runId)
                      ->where('user_id',$userId);
        return $query->first();
    }
}
