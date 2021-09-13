<?php

namespace App\EofficeApp\FrontComponent\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\FrontComponent\Entities\CustomizeSelectorEntity;
/**
 * customize_system表资源库
 *
 */
class CustomizeSelectorRepository extends BaseRepository
{
	public function __construct(CustomizeSelectorEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getBookByWhere 获取系统数据列表]
	 *
	 */
	public function getList($param)
	{
		$default = array(
			'fields' 	=> ['*'],
			'page' 		=> 0,
			'limit' 	=> config('eoffice.pagesize'),
			'order_by' 	=> ['id' => 'asc'],
			'search' 	=> [],
		);

		$param = array_merge($default, $param);
		
		$query = $this->entity->wheres($param['search'])
					->select($param['fields'])
					->parsePage($param['page'], $param['limit'])
					->orders($param['order_by']);
		return $query->get();
	}
	/**
	 * [getBookByWhere 获取系统数据]
	 *
	 */
	public function getOne($identifier)
	{
		
		$query = $this->entity->where('identifier',$identifier);
		return $query->first();
	}
}