<?php

namespace App\EofficeApp\System\Prompt\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Prompt\Entities\PromptTypeEntity;

/**
 * 提示语类别Repository类:提供提示语类别表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-12-28 创建
 */
class PromptTypeRepository extends BaseRepository
{

    public function __construct(PromptTypeEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取提示语类别列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
	 */
	public function getPromptTypes(array $param = [])
	{
		$default = [
			'fields'	=> ['*'],
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by'	=> ['prompt_type_id' => 'ASC'],
			'page'      => 0,
		];

        $param = array_filter($param, function($var) {
            return $var !== '';
        });

        $param = array_merge($default, $param);

		$query = $this->entity
			->select($param['fields'])
			->wheres($param['search'])
			->orders($param['order_by']);

		if (isset($param['prompts'])) {
			$query->with('prompts');
		}

		return $query->parsePage($param['page'], $param['limit'])
		->get()->toArray();
	}

	/**
	 * 获取提示语类别详情
	 *
	 * @param  int 	$typeid 类型id
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
	 */
	public function getPromptType($typeid, array $param = [])
	{
		$query = $this->entity;

		if (isset($param['prompts'])) {
			$query->with('prompts');
		}

		return $query->find($typeid);
	}
}
