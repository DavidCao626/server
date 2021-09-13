<?php

namespace App\EofficeApp\System\Security\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Security\Entities\SystemParamsEntity;

/**
 * system_params表资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class SystemParamsRepository extends BaseRepository
{
	public function __construct(SystemParamsEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getParamsByWhere 获取系统参数]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]            $where [查询条件]
	 *
	 * @since  2015-10-29 创建
	 *
	 * @return [object]                  [查询结果]
	 */
	public function getParamsByWhere($where)
	{
		return $this->entity->wheres($where)->get();
	}
	public function paramKeyExits($where)
	{
		return $this->entity->wheres($where)->get();
	}

    /**
     * [modifySystemParams 编辑系统参数]
     *
     * @author lixx
     *
     * @since 2018-10-24 创建
     * @param [array] $where  查询条件
     * @param $data
     */
	public function modifySystemParams($where,$data)
    {
        return $this->entity->where($where)->update($data);
    }
}