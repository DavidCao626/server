<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesLogEntity;

/**
 * 档案日志记录Repository类:提供档案日志记录相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesLogRepository extends BaseRepository
{

    public function __construct(ArchivesLogEntity $entity)
    {
        parent::__construct($entity);
    }

 	/**
	 * 获取日志列表
	 *
	 * @param   array  $param 查询参数
	 *
	 * @return  array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
	 */
	public function getLogList(array $param = [])
	{
		$default = [
			'fields'	=> ['*'],
			'page'  	=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['log_id' => 'asc'],
		];

		$param = array_merge($default, array_filter($param));

		return $this->entity
			->select($param['fields'])
			->wheres($param['search'])
			->orders($param['order_by'])
			->forPage($param['page'], $param['limit'])
			->with(['logCreatorHasOneUser' => function($query) {
				$query->select(['user_id', 'user_name']);
			}])
 			->get()
			->toArray();
	}
}