<?php
namespace App\EofficeApp\IncomeExpense\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IncomeExpense\Entities\IncomeExpenseRecordsEntity;
use App\EofficeApp\IncomeExpense\Entities\IncomeExpenseRecordsSubEntity;
use App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanEntity;
use DB;
/**
 * 收支记录资源库对象
 * 
 * @author 李志军
 * 
 * @since 2015-10-20
 */
class IncomeExpenseRecordsRepository extends BaseRepository
{ 
	/** @var object 收支记录子表实体对象 */
	private $incomeExpenseRecordsSubEntity;
	
	/** @var object 收支方案实体对象 */
	private $incomeExpensePlanEntity;
	
	/** @var string 主键 */
	private $primaryKey = 'record_id';
	
	/** @var int 默认列表条数 */
	private $limit		= 20;
	
	/** @var int 默认列表页 */
	private $page		= 0;
	
	/** @var array  默认排序 */
	private $orderBy	= ['income_expense_records.created_at' => 'desc'];
	
	/**
	 * 注册收支记录相关实体对象
	 * 
	 * @param \App\EofficeApp\IncomeExpense\Entities\IncomeExpenseRecordsEntity $entity
	 * @param \App\EofficeApp\IncomeExpense\Entities\IncomeExpenseRecordsSubEntity $incomeExpenseRecordsSubEntity
	 * @param \App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanEntity $incomeExpensePlanEntity
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function __construct(
		IncomeExpenseRecordsEntity $entity, 
		IncomeExpenseRecordsSubEntity $incomeExpenseRecordsSubEntity,
		IncomeExpensePlanEntity $incomeExpensePlanEntity
		) 
	{
		parent::__construct($entity);
		
		$this->incomeExpenseRecordsSubEntity = $incomeExpenseRecordsSubEntity;
		
		$this->incomeExpensePlanEntity = $incomeExpensePlanEntity;
	}
	/**
	 * 获取记录列表
	 * 
	 * @param type $param 查询参数
	 * 
	 * @return array 记录列表 
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function listRecord($param, $own)
	{
		$param['fields']	= isset($param['fields']) 
							? $param['fields']
						 	: ['income_expense_plan.plan_id',
						 	'income_expense_plan.plan_name',
						 	'income_expense_plan.expense_budget',
						 	'income_expense_plan.income_budget',
						 	'income_expense_plan.real_expense',
						 	'income_expense_plan.real_income',
						 	'user.user_name',
						 	'income_expense_records.record_id',
                            'income_expense_records.is_flow_record',
						 	'income_expense_records.expense',
						 	'income_expense_records.income',
						 	'income_expense_records.creator',
						 	'income_expense_records.record_time',
						 	'income_expense_records.created_at',
						 	'income_expense_records_sub.record_desc',
						 	'income_expense_records_sub.income_detail',
						 	'income_expense_records_sub.expense_detail',
						 	'income_expense_records_sub.record_extra'
							 ];
		if(isset($param['limit'])  && !isset($param['page'])){
			$param['page'] = 1;
		}
		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;
		
		$param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;
		
		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
		
		$query = $this->entity
			->select(['record_id'])
			->leftJoin('income_expense_plan', 'income_expense_plan.plan_id', '=', 'income_expense_records.plan_id')
			->where(function ($query) use($own) {
				$query->where('income_expense_plan.all_user',1)
					->orWhere('income_expense_plan.creator', $own['user_id'])
					->orWhere(function ($query) use($own) {
                        $query->orWhereRaw("FIND_IN_SET(?, income_expense_plan.user_id)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, income_expense_plan.dept_id)", [$own['dept_id']]);
                        foreach ($own['role_id'] as $roleId) {
                            $query->orWhereRaw("FIND_IN_SET(?, income_expense_plan.role_id)", [$roleId]);
                        }
					});
			});
		if (isset($param['search']) && !empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		$query = $query ->whereNull("income_expense_plan.deleted_at");
		$records = $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get();
		
		$list = [];
		$size = count($records);
		if($size){
			$listQuery = $this->entity->select($param['fields'])
						->leftJoin('user', 'user.user_id', '=', 'income_expense_records.creator')
						->leftJoin('income_expense_plan', 'income_expense_plan.plan_id', '=', 'income_expense_records.plan_id')
						->leftJoin('income_expense_records_sub', 'income_expense_records_sub.record_id', '=', 'income_expense_records.record_id');
			$orderRaw  = 'field(income_expense_records.record_id,';
			$recordIds = [];
			foreach ($records as $key => $record) {
				if($size == $key + 1){
					$orderRaw .= $record->record_id . ')';
				} else {
					$orderRaw .= $record->record_id . ',';
				}
				$recordIds[] = $record->record_id;
			}
			$list = $listQuery->whereIn('income_expense_records.record_id',$recordIds)->orderByRaw($orderRaw)->get();
		}

		return $list;
	}
	public function getRecordAttachmentIds($entityId) {

        return DB::table('attachment_relataion_incomeexpense_record')->select(['attachment_id'])->where('entity_id', $entityId)->get();
    }
	/**
	 * 获取记录数量
	 * 
	 * @param array $search 查询参数
	 * 
	 * @return int 记录数量
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function getRecordCount($search,$own) 
	{	
		$query = $this->entity
			->select(['record_id'])
			->leftJoin('income_expense_plan', 'income_expense_plan.plan_id', '=', 'income_expense_records.plan_id')
			->where(function ($query) use($own) {
				$query->where('income_expense_plan.all_user',1)
					->orWhere('income_expense_plan.creator', $own['user_id'])
					->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, income_expense_plan.user_id)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, income_expense_plan.dept_id)", [$own['dept_id']]);
                        foreach ($own['role_id'] as $roleId) {
                            $query->orWhereRaw("FIND_IN_SET(?, income_expense_plan.role_id)", [$roleId]);
                        }
					});
			});
		if (isset($search) && !empty($search)) {
			$query = $query->wheres($search);
		}
		$query = $query ->whereNull("income_expense_plan.deleted_at");
		return $query->count();
	}
	/**
	 * 新建收支记录
	 * 
	 * @param array $data
	 * @param array $subData
	 * 
	 * @return int 记录id
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function addRecord($data)
	{
		$subData = $data['sub_data'];

		unset($data['sub_data']);
		
		if(!$record = $this->entity->create($data)) {
			return false;
		}
		
		$subData['record_id'] = $record->record_id;

		$this->incomeExpenseRecordsSubEntity->insert($subData);

		return $record->record_id;
	}
	/**
	 * 编辑收支记录
	 * 
	 * @param array $data
	 * @param array $subData
	 * @param int $recordId
	 * 
	 * @return boolean 编辑结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function editRecord($data, $recordId)
	{
		$subData = $data['sub_data'];

		unset($data['sub_data']);

		if($this->entity->where($this->primaryKey, $recordId)->update($data)) {
			$this->incomeExpenseRecordsSubEntity->where($this->primaryKey, $recordId)->update($subData);
			
			return true;
		}
		
		return false;
	}
	/**
	 * 删除收支记录
	 * 
	 * @param int $recordId
	 * 
	 * @return boolean 删除结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function deleteRecord($recordId) 
	{
		if($this->entity->where($this->primaryKey, $recordId)->delete()) {
			$this->incomeExpenseRecordsSubEntity->where($this->primaryKey, $recordId)->delete();
			
			return true;
		}
		
		return false;
	}
	/**
	 * 根据方案id获取记录
	 * 
	 * @param int $planId
	 * 
	 * @return array 记录
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function getRecordsByPlanId($planId)
	{
		return $this->entity->where('plan_id',$planId)->get();
	}
	public function getRecordCountByPlanId($planId)
	{
		return $this->entity->where('plan_id',$planId)->count();
	}
	/**
	 * 获取简单记录详情
	 * 
	 * @param int $recordId
	 * 
	 * @return object 记录详情
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function getSimpleRecordInfo($recordId)
	{
		return $this->entity->where($this->primaryKey, $recordId)->first();
	}
	/**
	 * 获取详细的记录详情
	 * 
	 * @param int $recordId
	 * @param array $fields
	 * 
	 * @return object 记录详情
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function getRecordInfo($recordId, $fields)
	{
		
		return $this->entity
			->select($fields)
			->leftJoin('user', 'user.user_id', '=', 'income_expense_records.creator')
			->leftJoin('income_expense_plan', 'income_expense_plan.plan_id', '=', 'income_expense_records.plan_id')
			->leftJoin('income_expense_records_sub', 'income_expense_records_sub.record_id', '=', 'income_expense_records.record_id')
			->where('income_expense_records.record_id', $recordId)->first();
	}
	/**
	 * 获取记录详情
	 * 
	 * @param int $recordId
	 * 
	 * @return object 记录详情
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function showRecord($recordId)
	{
		return $this->entity
			->select(['income_expense_plan.*','user.user_name','income_expense_records.*','income_expense_records_sub.*'])
			->leftJoin('user', 'user.user_id', '=', 'income_expense_records.creator')
			->leftJoin('income_expense_plan', 'income_expense_plan.plan_id', '=', 'income_expense_records.plan_id')
			->leftJoin('income_expense_records_sub', 'income_expense_records_sub.record_id', '=', 'income_expense_records.record_id')
			->where('income_expense_records.record_id', $recordId)->first();
	}
	/**
	 * 按所有季节统计
	 * 
	 * @param int $planId
	 * @param int $year
	 * 
	 * @return array 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function allQuarterStat($planId, $year)
	{
		return $this->entity
			->selectRaw('SUM(`expense`) AS `expense`,SUM(`income`) AS `income`,COUNT(`record_id`) AS `times`,`quarter`')
			->whereIn('plan_id',$planId)
			->where('year',$year)
			->groupBy('quarter')
			->orderBy('quarter','asc')->get();
	}
	/**
	 * 按所有月份统计
	 * 
	 * @param int $planId
	 * @param int $year
	 * 
	 * @return array 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function allMonthStat($planId, $year)
	{
		return $this->entity
			->selectRaw('SUM(`expense`) AS `expense`,SUM(`income`) AS `income`,COUNT(`record_id`) AS `times`,`month`')
			->whereIn('plan_id',$planId)
			->where('year',$year)
			->groupBy('month')
			->orderBy('month','asc')->get();
	}
	/**
	 * 统计一个月所有天数收支数据
	 * 
	 * @param int $planId
	 * @param int $year
	 * @param int $month
	 * 
	 * @return array 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function oneMonthStat($planId, $year, $month)
	{
		$query =  $this->entity
			->selectRaw('SUM(`expense`) AS `expense`,SUM(`income`) AS `income`,COUNT(`record_id`) AS `times`,`day`');
		
		if($planId != '') {
			$query = $query->whereIn('plan_id',$planId);
		}
		
		return $query->where('year',$year)
				->where('month',$month)
				->groupBy('day')
				->orderBy('day','asc')->get();
	}
	/**
	 * 统计一天的收支数据
	 * 
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * 
	 * @return array 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function oneDayStat($year,$month,$day){
		return $this->entity
			->selectRaw('SUM(income_expense_records.expense) AS `expense`,SUM(income_expense_records.income) AS `income`,COUNT(income_expense_records.record_id) AS `times`,income_expense_plan.plan_name')
			->leftJoin('income_expense_plan', 'income_expense_plan.plan_id', '=', 'income_expense_records.plan_id')
			->where('income_expense_records.year',$year)
			->where('income_expense_records.month',$month)
			->where('income_expense_records.day',$day)
			->groupBy('income_expense_records.plan_id')->get();
    }
	/**
	 * 按收支方案统计全部季度的收支数据
	 * 
	 * @param int $year
	 * @param int $planId
	 * 
	 * @return array 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function statQuarterByPlanId($year, $planId)
	{
		return $this->entity
			->selectRaw('SUM(`expense`) AS `expense`,SUM(`income`) AS `income`,COUNT(`record_id`) AS `times`,`quarter`')
			->where('plan_id',$planId)
			->where('year',$year)
			->groupBy('quarter')
			->orderBy('quarter','asc')->get();
	}
	/**
	 * 按收支方案统计全部月份的收支数据
	 * 
	 * @param int $year
	 * @param int $planId
	 * 
	 * @return array 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function statMonthByPlanId($year, $planId)
	{
		return $this->entity
			->selectRaw('SUM(`expense`) AS `expense`,SUM(`income`) AS `income`,COUNT(`record_id`) AS `times`,`month`')
			->where('plan_id',$planId)
			->where('year',$year)
			->groupBy('month')
			->orderBy('month','asc')->get();
	}
	/**
	 * 按收支方案统计一个月份的收支数据
	 * 
	 * @param int $year
	 * @param int $month
	 * @param int $planId
	 * 
	 * @return array 统计结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function statOneMonthByPlanId($year, $month, $planId)
	{
		return  $this->entity
			->selectRaw('SUM(`expense`) AS `expense`,SUM(`income`) AS `income`,COUNT(`record_id`) AS `times`,`day`')
			->where('plan_id',$planId)
			->where('year',$year)
			->where('month',$month)
			->groupBy('day')
			->orderBy('day','asc')->get();
	}
}
