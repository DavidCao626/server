<?php

namespace App\EofficeApp\System\Prompt\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Prompt\Entities\PromptEntity;

/**
 * 提示语Repository类:提供提示语表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-12-28 创建
 */
class PromptRepository extends BaseRepository
{

    public function __construct(PromptEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取提示语列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
	 */
	public function getPrompts(array $param = [])
	{
		$default = [
			'fields'	=> ['*'],
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by'	=> ['prompt_id' => 'ASC'],
			'page'      => 0,
		];

        $param = array_filter($param, function($var) {
            return $var !== '';
        });

        $param = array_merge($default, $param);

		return $this->entity
			->select($param['fields'])
			->wheres($param['search'])
			->orders($param['order_by'])
			->parsePage($param['page'], $param['limit'])
			->get()
			->toArray();
	}

	/**
	 * 获取提示语列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-12-30 创建
	 */
	public function getLoginPrompts()
    {
        return $this->entity
            ->whereHas('promptType', function ($query) {
                $query->where('prompt_type_status', '=', '1');
            })
            ->pluck('prompt_content');
    }
    public function getListByTypeId($typeId)
	{
		return $this->entity
			->where('prompt_type_id', $typeId)
            ->get()->toArray();
	}
}
