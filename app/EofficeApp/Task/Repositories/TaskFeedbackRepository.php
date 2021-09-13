<?php

namespace App\EofficeApp\Task\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Task\Entities\TaskFeedbackEntity;

class TaskFeedbackRepository extends BaseRepository
{
	public function __construct(TaskFeedbackEntity $taskFeedbackEntity)
	{
		parent::__construct($taskFeedbackEntity);
	}

	/**
	 * [getFeedbackInfo 获取单个反馈信息]
	 *
	 * @method 朱从玺
	 *
	 * @param  [int]             $feedbackId [反馈ID]
	 *
	 * @return [object]                      [查询结果]
	 */
	public function getFeedbackInfo($feedbackId)
	{
		return $this->entity->with(['user' => function($query)
					{
						$query->select('user_id', 'user_name');
					}, 'parentFeedback' => function($query)
					{
						$query->select('id', 'feedback_content', 'user_id', 'created_at')
							  ->with(['user' => function($query)
							  	{
							  		$query->select('user_id', 'user_name');
							  	}]);
					}])->find($feedbackId);
	}

	/**
	 * [getTaskFeedback 获取任务的回复列表]
	 *
	 * @method 朱从玺
	 *
	 * @param  [int]             $taskId [任务ID]
	 *
	 * @return [object]                  [查询结果]
	 */
	public function getTaskFeedback($params)
	{
		$defaultParams = [
			'fields' => ['*'],
			'page' => 0,
			'limit' => config('eoffice.pagesize'),
			'order_by' => ['created_at' => 'desc'],
			'search' => []
		];

		$params = array_merge($defaultParams, $params);

		return $this->entity->where('task_id', $params['taskId'])
					->where('parent_id', 0)
					->wheres($params['search'])
					->orders($params['order_by'])
					->parsePage($params['page'], $params['limit'])
					->with(['user' => function($query)
					{
						$query->select('user_id', 'user_name');
					}, 'feedbackHasManySon' => function($query)
					{
						$query->select('id', 'parent_id', 'feedback_content', 'user_id', 'created_at')
							  ->with(['user' => function($query)
							  	{
							  		$query->select('user_id', 'user_name');
							  	}]);
					}])
					->get();
	}

	/**
	 * [getTaskFeedbackCount 获取任务反馈列表条数]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]               $params [查询条件]
	 *
	 * @return [int]                         [查询结果]
	 */
	public function getTaskFeedbackCount($params)
	{
		$search = isset($params['search']) ? $params['search'] : [];

		return $this->entity->where('task_id', $params['taskId'])
					->where('parent_id', 0)
					->wheres($search)->count();
	}

	/**
	 * [getFeedbackCount 获取反馈条数]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]           $param [查询条件]
	 *
	 * @return [int]                    [查询结果]
	 */
	public function getFeedbackCount($param)
	{
		return $this->getFeedbackWhere($param)->count();
	}

	/**
	 * [getFeedbackList 获取反馈列表]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]           $param [查询条件]
	 *
	 * @return [object]                 [查询结果]
	 */
	public function getFeedbackList($param)
	{
		$defaultParam = [
			'fields' => ['*'],
			'page' => 0,
			'limit' => config('eoffice.pagesize'),
			'order_by' => ['created_at' => 'desc'],
			'search' => []
		];

		$param = array_merge($defaultParam, $param);

		//整理查询条件
		$query = $this->getFeedbackWhere($param);

		return $query->select($param['fields'])
					 ->wheres($param['search'])
					 ->with(['user' => function($query)
					 {
						$query->select('user_id', 'user_name');
					 }, 'parentFeedback' => function($query)
					 {
						$query->select('id', 'feedback_content', 'user_id', 'created_at')
							  ->with(['user' => function($query)
							  	{
							  		$query->select('user_id', 'user_name');
							  	}]);
					 }, 'task' => function($query)
					 {
					 	$query->select('id', 'task_name');
					 }])
					 ->parsePage($param['page'], $param['limit'])
					 ->orders($param['order_by'])
					 ->get();
	}

	/**
	 * [getFeedbackWhere 整理反馈列表查询条件]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]            $param [查询条件]
	 *
	 * @return [object]                  [ORM对象]
	 */
	public function getFeedbackWhere($param)
	{
		$query = $this->entity;

		if(isset($param['feedback_type'])) {
			switch ($param['feedback_type']) {
				case 'unread':		//未读反馈
					$query = $query->whereHas('unread', function($query) use ($param)
					{
						$query->where('user_id', $param['user_id']);
					});
					break;
				case 'atMe':		//@我的反馈
					# code...
					break;
				case 'notMine':		//非本人反馈
					$query = $query->whereIn('task_id', $param['sharedPowerArray'])
								   ->where('user_id', '!=', $param['user_id']);
					break;
				case 'replyMe':		//回复我的
					$query = $query->whereIn('task_id', $param['sharedPowerArray'])
								   ->whereHas('parentFeedback', function($query) use ($param)
								   	{
								   		$query->where('user_id', $param['user_id']);
								   	});
					break;
				case 'manageTask':	//我负责任务的反馈
					$query = $query->whereIn('task_id', $param['manageTask']);
					break;
				case 'joinTask':	//我参与任务的反馈
					$query = $query->whereIn('task_id', $param['joinTask']);
					break;
				case 'createTask':	//我创建任务的反馈
					$query = $query->whereIn('task_id', $param['createTask']);
					break;
				case 'followTask':	//我关注任务的反馈
					$query = $query->whereIn('task_id', $param['followTask']);
					break;
				case 'sharedTask':	//共享任务的反馈
					$query = $query->whereIn('task_id', $param['sharedTask']);
					break;
				case 'myFeedback':	//我的反馈
					$query = $query->where('user_id', $param['user_id']);
					break;
				
				default:
					$query = $query->where('user_id', $param['user_id']);
					break;
			}
		}

		return $query;
	}
}