<?php
namespace App\EofficeApp\IncomeExpense\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IncomeExpense\Entities\IncomeExpenseStatEntity;
/**
 * 收支统计资源库类
 * 
 * @author 李志军
 * 
 * @since 2015-10-20
 */
class IncomeExpenseStatRepository  extends BaseRepository
{
	/**
	 * 注册收支统计实体对象
	 * 
	 * @param \App\EofficeApp\IncomeExpense\Entities\IncomeExpenseStatEntity $entity
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function __construct(IncomeExpenseStatEntity $entity) 	
	{
		parent::__construct($entity);
	}
	/**
	 * 添加统计数据
	 * 
	 * @param array $data 统计数据
	 * 
	 * @return object 新建结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function addStat($data) 
	{
		$oldStat = $this->entity->where('month', $data['month'])->where('year', $data['year'])->first();

		if ($oldStat) {
			$data['income']		+= $oldStat->income;
			$data['expense']	+= $oldStat->expense;
			$data['times']		+= $oldStat->times;

			return $this->entity->where('month', $data['month'])->where('year', $data['year'])->update($data);
		} else {
			return $this->entity->create($data);
		}
	}
	/**
	 * 编辑统计数据
	 * 
	 * @param array $data 统计数据
	 * @param string $type 判断编辑类型
	 * 
	 * @return bool 编辑结果
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function editStat($data, $type = 'edit')
	{
		$result = true;
		if($delete = $this->entity->where('month', $data['old_month'])->where('year', $data['old_year'])->first()){
			$deleteStat = [
				'income'	=> $delete->income - $data['old_income'],
	            'expense'	=> $delete->expense - $data['old_expense'],
	            'times'		=> $delete->times - 1
			];
			$result = $this->entity->where('month', $data['old_month'])->where('year', $data['old_year'])->update($deleteStat);
		}

		if($type == 'delete'){
			return $result;
		}

		$editStat = [
            'income'	=> $data['income'],
            'expense'	=> $data['expense'],
            'quarter'	=> $data['quarter'],
            'times'		=> 1
        ];

		if($edit = $this->entity->where('month', $data['month'])->where('year', $data['year'])->first()){
			$editStat['income'] 	+= $edit->income;
			$editStat['expense'] 	+= $edit->expense;
			$editStat['times'] 		+= $edit->times;
			
			return $this->entity->where('month', $data['month'])->where('year', $data['year'])->update($editStat);
		} 

		$editStat['month'] 		= $data['month'];
		$editStat['quarter'] 	= $data['quarter'];
		$editStat['year'] 		= $data['year'];

		return $this->entity->create($editStat);
	}
	/**
	 * 统计一年四季度的收支数据
	 * 
	 * @param int $year
	 * 
	 * @return array 统计数据
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function allQuarterStat($year)
	{
		return $this->entity
			->selectRaw('SUM(`expense`) AS `expense`,SUM(`income`) AS `income`,SUM(`times`) AS `times`,`year`,`quarter`')
			->where('year',$year)
			->groupBy('quarter')
			->orderBy('quarter','asc')
			->get();
	}
	/**
	 * 统计一年12个月的收支数据
	 * 
	 * @param int $year
	 * 
	 * @return array 统计数据
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-10-20
	 */
	public function allMonthStat($year)
	{
		return $this->entity
			->where('year',$year)
			->orderBy('month','asc')
			->get();
	}
}
