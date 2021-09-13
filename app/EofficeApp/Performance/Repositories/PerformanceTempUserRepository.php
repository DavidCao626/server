<?php

namespace App\EofficeApp\Performance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Performance\Entities\PerformanceTempUserEntity;

/**
 * performance_temp_user资源库
 *
 * @author  朱从玺
 *
 * @since   2016-05-04
 * 
 */
class PerformanceTempUserRepository extends BaseRepository
{
	public function __construct(PerformanceTempUserEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [searchTempUser 查询模板适用用户数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]          $search [查询条件]
	 *
	 * @return [object]                 [查询结果]
	 */
	public function searchTempUser($search)
	{
		return $this->entity->wheres($search)->get();
	}

	/**
	 * [insertData 插入模板用户关联数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]      $data [插入数据]
	 *
	 * @return [object]           [插入结果]
	 */
	public function createData($data)
	{
		return $this->entity->firstOrCreate($data);
	}
}