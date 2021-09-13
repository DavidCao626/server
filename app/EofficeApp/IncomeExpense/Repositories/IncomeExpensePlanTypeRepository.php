<?php
namespace App\EofficeApp\IncomeExpense\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanTypeEntity;
/**
 * 收支方案类别资源库对象
 * 
 * @author 李志军
 * 
 * @since 2015-10-20
 */
class IncomeExpensePlanTypeRepository extends BaseRepository
{
	/** @var string 主键 */
	private $primaryKey = 'plan_type_id';
	
	/** @var int 默认列表条数 */
	private $limit		= 20;
	
	/** @var int 默认列表页 */
	private $page		= 0;
	
	/** @var array  默认排序 */
	private $orderBy	= ['plan_type_id' => 'desc'];
	
	/**
	 * 注册收支方案类别实体
	 * 
	 * @param \App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanTypeEntity $entity
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function __construct(IncomeExpensePlanTypeEntity $entity) 
	{
		parent::__construct($entity);
	}
	/**
	 * 获取方案类别列表
	 * 
	 * @param array $param 查询参数
	 * 
	 * @return array 方案类别列表
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function listPlanType($param)
	{
		$param['fields']	= empty($param['fields']) ? ['*'] : $param['fields'];
		
		$param['limit']		= !isset($param['limit']) ? $this->limit : $param['limit'];
		
		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;
		
		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];
		
		$query = $this->entity->select($param['fields']);
		
		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		
		return $query->orders($param['order_by'])
					// ->forPage($param['page'], $param['limit'])
					->get();
	}
	public function getAllPlanTypeMap()
	{
		$planTypes = $this->entity->select(['plan_type_id','plan_type_name'])->get();

		$planTypesMap = [];

		if($planTypes){
			foreach ($planTypes as $key => $value) {
				$planTypesMap[$value['plan_type_id']] = $value['plan_type_name'];
			}
		}

		return $planTypesMap;
	}
	/**
	 * 获取方案类别数量
	 * 
	 * @param array $search 查询参数
	 * 
	 * @return array  类别数量
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function getPlanTypeCount($search)
	{
		$query = $this->entity;
		
		if (!empty($search['search'])) {
			$query = $query->wheres($search['search']);
		}
		
		return $query->count();
	}
	/**
	 * 新建方案类别
	 * 
	 * @param array $data 方案类别数据
	 * 
	 * @return object 新建结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function addPlanType($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * 编辑方案类别
	 * 
	 * @param array $data 方案类别数据
	 * @param int $planTypeId
	 * 
	 * @return boolean 编辑结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function editPlanType($data, $planTypeId)
	{
		return $this->entity->where($this->primaryKey, $planTypeId)->update($data);
	}
	/**
	 * 获取方案类别详情
	 * 
	 * @param int $planTypeId
	 * 
	 * @return object 方案类别详情
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function showPlanType($planTypeId)
	{
		return $this->entity->where($this->primaryKey, $planTypeId)->first();
	}
	/**
	 * 删除方案类别
	 * 
	 * @param int $planTypeId
	 * 
	 * @return boolean 删除结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function deletePlanType($planTypeId)
	{
		return $this->entity->destroy($planTypeId);
	}
}
