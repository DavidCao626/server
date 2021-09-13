<?php

namespace App\EofficeApp\Performance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Performance\Entities\PerformancePersonalEntity;

/**
 * performance_personnel资源库
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 *
 */
class PerformancePersonalRepository extends BaseRepository
{
	public function __construct(PerformancePersonalEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getPersonelData 获取用户考核数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]          $where [查询条件]
	 *
	 * @since  2015-10-23 创建
	 *
	 * @return [object]                [查询结果]
	 */
	public function getPersonelDatas($where)
	{
		return $this->entity->wheres($where)->get();
	}

	public function getPersonelData($where)
	{
		return $this->entity->wheres($where)->first();
	}
}