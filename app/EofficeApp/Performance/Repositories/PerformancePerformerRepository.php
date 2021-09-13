<?php

namespace App\EofficeApp\Performance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Performance\Entities\PerformancePerformerEntity;

/**
 * performance_performer资源库
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 *
 */
class PerformancePerformerRepository extends BaseRepository
{
	public function __construct(PerformancePerformerEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getPerformerInfo 获取某个用户考核人设置信息]
	 *
	 * @author 朱从玺
	 *
	 * @param  [string]           $userId [用户ID]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [object]                   [查询结果]
	 */
	public function getPerformerInfo($userId)
	{
		return $this->entity->where('performance_user', $userId)
					->with(['performerHasOneUser' => function($query)
                    {
                        $query->select('user_id', 'user_name');
                    }])

					->first();
	}

	/**
	 * [getPerformerUserByWhere 获取特定条件下的用户ID数组]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]                  $where [查询条件]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [array]                         [查询结果]
	 */
	public function getPerformerUserByWhere($where)
	{
		return $this->entity->wheres($where)
					->leftJoin('user', 'performance_performer.performance_user', '=', 'user.user_id')
					->select('performance_performer.performance_user', 'user.user_name')
					->with(['userHasOneSystemInfo' => function ($query) {
                        $query->select(['user_id', 'user_status']);
                    }])
                    ->whereHas("userHasOneSystemInfo", function ($query) {
                        $query->where('user_status', '!=', '0');
                    })
					->get();
	}

	public function getPerformerList($where)
	{
		return $this->entity->wheres($where)->get();
	}

	public function getPerformerCount($where)
	{
		return $this->entity->wheres($where)->count();
	}
}