<?php

namespace App\EofficeApp\System\Security\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Security\Entities\SystemUploadEntity;

/**
 * system_upload表资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class SystemUploadRepository extends BaseRepository
{
	public function __construct(SystemUploadEntity $entity)
	{
		parent::__construct($entity);
	}
	
	/**
	 * [getAllModule 获取上传设置列表]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]       $param [查询条件]
	 *
	 * @return [array]              [查询结果]
	 */
	public function getAllModule($param)
	{
		return $this->entity
					->forPage($param['page'], $param['limit'])
					->get()
					->toArray();
	}
	
	/**
	 * [getOneModule 获取模块上传设置的数据]
	 *
	 * @method 朱从玺
	 *
	 * @param  [int]         $functionAbbreviation [模块ID]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [array]                   		   [查到的数据]
	 */
	public function getOneModule($functionAbbreviation)
	{
		$oneInfo = $this->entity->where('function_abbreviation', $functionAbbreviation)->first();

		return $oneInfo;
	}
}