<?php

namespace App\EofficeApp\Performance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Performance\Entities\PerformanceTempEntity;

/**
 * performance_temp资源库
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 *
 */
class PerformanceTempRepository extends BaseRepository
{
	public function __construct(PerformanceTempEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getTempList 获取模版列表,以模版表为主表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]       $params [查询条件]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [object]              [查询结果]
	 */
	public function getTempList($params)
	{
		$default = [
			'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['id'=>'ASC'],
		];

		$params = array_merge($default, $params);

		$data = $this->entity
            ->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get();
            foreach($data as $key => $value) {
                $value->plan_name = mulit_trans_dynamic("performance_plan.plan_name.performance_plan_". $value->id);
            }
            return $data;
	}
}