<?php

namespace App\EofficeApp\Performance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Base\ModelTrait;
use App\EofficeApp\Performance\Entities\PerformancePlanEntity;

/**
 * performance_plan资源库
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 *
 */
class PerformancePlanRepository extends BaseRepository
{
    use ModelTrait;

	public function __construct(PerformancePlanEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getAllPlan 获取所有考核方案信息]
	 *
	 * @author 朱从玺
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [type]     [查询结果]
	 */
	public function getAllPlan()
	{
		return $this->entity->select('*')->get();
	}

	/**
	 * [getTempList 获取考核模板中间列]
	 *
	 * @author 朱从玺
	 *
	 * @return [object]      [查询结果]
	 */
	public function getTempList($params = [])
	{
		$data = $this->entity->select('id', 'plan_name')
                ->with(['performanceTemp' => function($query) use ($params)
                {
                    $query->select('id', 'plan_id', 'temp_name')
                          ->orderBy('temp_name', 'asc');
                    if(isset($params['search']) && isset($params['search']['temp_name'])){
                        $query = $this->wheres($query, ['temp_name' => $params['search']['temp_name']]);
                    }
                }])
                ->get();
		foreach($data as $key => $value) {
			$value->plan_name = mulit_trans_dynamic("performance_plan.plan_name.performance_plan_". $value->id);
		}
		return $data;
	}

	/**
	 * [modifyPlan 修改考核方案信息/批量更新]
	 *
	 * @author 朱从玺
	 *
	 * @param  [type]     $planId      [考核方案ID]
	 * @param  [type]     $newPlanData [修改数据]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [type]                  [修改结果]
	 */
	public function modifyPlan($planId, $newPlanData)
	{
		$plan = $this->entity->find($planId);

		if($plan) {
			return $plan->fill($newPlanData)->save();
		}else {
			return array('code' => array('0x010014', 'performance'));
		}
	}
}
