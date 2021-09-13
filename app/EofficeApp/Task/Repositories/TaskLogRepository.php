<?php

namespace App\EofficeApp\Task\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Task\Entities\TaskLogEntity;
use Carbon\Carbon;

class TaskLogRepository extends BaseRepository
{
	public function __construct(TaskLogEntity $taskLogEntity)
	{
		parent::__construct($taskLogEntity);
	}

	/**
	 * [getLogCount 获取日志条数]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]      $param [查询条件]
	 *
	 * @return [int]               [查询结果]
	 */
	public function getLogCount($param)
	{
		$search = isset($param['search']) ? $param['search'] : [];

		return $this->entity->wheres($search)->count();
	}

	/**
	 * [getLogList 获取日志列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]      $param [查询条件]
	 *
	 * @return [object]            [查询结果]
	 */
	public function getLogList($param)
	{
		$defaultParam = [
			'fields' => ['*'],
			'page' => 0,
			'limit' => config('eoffice.pagesize'),
			'order_by' => ['id' => 'desc'],
			'search' => []
		];

		$param = array_merge($defaultParam, $param);

		$query = $this->entity;

		if(isset($param['task_search'])) {
			$query = $query->whereHas('task', function($query) use ($param)
						   {
								$query->withTrashed()->wheres($param['task_search']);
						   });
		}

		return $query->select($param['fields'])
					  ->wheres($param['search'])
					  ->with(['task' => function($query)
					  {
						$query->withTrashed()->select('id', 'task_name');
					  }, 'user' => function($query)
					  {
						$query->select('user_id', 'user_name');
					  }])
					  ->parsePage($param['page'], $param['limit'])
					  ->orders($param['order_by'])
					  ->get();
	}

	public function dailyLogByUserId($userId, $day) {
        $query = $this->entity->newQuery();
        $query->where('user_id', $userId);
        $carbon = Carbon::createFromTimestamp(strtotime($day));
        $betweenDate = [$carbon->startOfDay()->toDateTimeString(), $carbon->endOfDay()->toDateTimeString()];
        $query->whereBetween('created_at', $betweenDate);
        $query->orderByDesc('created_at');
        return $query->get();
    }
}