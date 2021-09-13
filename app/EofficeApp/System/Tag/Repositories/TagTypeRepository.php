<?php

namespace App\EofficeApp\System\Tag\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Tag\Entities\TagTypeEntity;

/**
 * 标签分类Repository类:提供标签分类表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-05-30 创建
 */
class TagTypeRepository extends BaseRepository
{

    public function __construct(TagTypeEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取标签分类列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return object        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
	 */
	public function getTagTypes(array $param = [])
	{
		$default = [
			'fields'	=> ['*'],
			'page'  	=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['tag_type_id' => 'asc'],
			'withTag'	=> 0
		];

		$param = array_merge($default, $param);

		$query = $this->entity
			->select($param['fields'])
			->wheres($param['search'])
			->orders($param['order_by']);

		if ($param['withTag'] == 1) {
			$query = $query->with('hasManyTags');
		}

		if ($param['page'] > 0)	{
			$query = $query->forPage($param['page'], $param['limit']);
		}

		return $query->get();
	}

}