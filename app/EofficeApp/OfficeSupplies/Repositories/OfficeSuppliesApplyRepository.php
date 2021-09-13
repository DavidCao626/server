<?php

namespace App\EofficeApp\OfficeSupplies\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesApplyEntity;

/**
 * office_supplies_apply资源库
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesApplyRepository extends BaseRepository
{
	public function __construct(OfficeSuppliesApplyEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getApplyList 获取申请列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]        $param [查询条件]
	 *
	 * @since 2015-11-05 创建
	 *
	 * @return [object]              [查询结果]
	 */
	public function getApplyList($param)
	{
        $default = array(
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['id' => 'desc'],
            'search' => [],
            'returntype' => 'array'
        );

		if(isset($param['apply_user']) && $param['apply_user']) {
			$param['search']['apply_user'] = [$param['apply_user']];
		}
        $param = array_merge($default, $param);
        if(isset($param['search']['dept_id']) && $param['search']['dept_id']) {
        	$deptId = $param['search']['dept_id'][0];
        	unset($param['search']['dept_id']);
        }else{
        	$deptId = '';
        }
		$query = $this->entity->select($param['fields'])
					->parsePage($param['page'], $param['limit'])
					->orders($param['order_by'])
					->wheres($param['search']);
        if(isset($param['office_supplies_id']) && $param['office_supplies_id']) {
            $query->where('office_supplies_id', $param['office_supplies_id']);
        }
        $query = $query->with(['applyBelongsToSupplies' => function($query)
                {
                    $query->select('id', 'office_supplies_name', 'type_id', 'stock_surplus', 'unit', 'specifications', 'usage')
                          ->with(['suppliesBelongsToType' => function($query)
                            {
                                $query->select('id', 'type_name','parent_id');
                            }]);
                }, 'applyBelongsToUser' => function($query)
                {
                    $query->select('user_id', 'user_name')->withTrashed();
                }, 'applyBelongsToUserSystemInfo' => function($query) {
                    $query->with(['userSystemInfoBelongsToDepartment' => function($query) {
                        $query->select('dept_id', 'dept_name');
                    }])->withTrashed();
                }])
                ->whereRaw("office_supplies_id IN (SELECT id FROM office_supplies)");
		if (!empty($deptId)) {
			$query = $query->whereHas('applyBelongsToUserSystemInfo', function($query) use ($deptId) {
								$query->whereHas('userSystemInfoBelongsToDepartment', function($query) use ($deptId) {
									if(isset($deptId) && $deptId) {
										$query->where('dept_id', $deptId);
									}
								})->withTrashed();
							});
		}

		if(isset($param['supplies_search'])) {
			$query = $query->whereHas('applyBelongsToSupplies', function($query) use ($param)
			{
				$query->wheres($param['supplies_search']);
			});
		}
		if(isset($param['type_search'])) {
			$query = $query->whereHas('applyBelongsToSupplies', function($query) use ($param)
			{
				$query->whereHas('suppliesBelongsToType', function($query) use($param)
				{
					$query->wheres($param['type_search']);
				});
			});
		}
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->get()->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
	}

	/**
	 * [getApplyCount 查询申请列表条数]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]        $param [查询条件]
	 *
	 * @since  2015-11-05 创建
	 *
	 * @return [int]                 [查询结果]
	 */
	public function getApplyCount($param)
	{
		$param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getApplyList($param);
	}

	/**
	 * [getApplyDetail 获取申请详情]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]            $applyId [申请ID]
	 *
	 * @since  2015-11-05 创建
	 *
	 * @return [object]                  [查询结果]
	 */
	public function getApplyDetail($applyId)
	{
		$applyDetail = $this->entity->where('id', $applyId)
					->with(['applyBelongsToSupplies' => function($query)
					{
						$query->select('id', 'office_supplies_name', 'unit', 'type_id', 'stock_surplus', 'usage', 'specifications','remind_min')
							  ->with(['suppliesBelongsToType' => function($query)
							  	{
							  		$query->select('id', 'type_name');
							  	}]);
					}, 'applyBelongsToUser' => function($query)
					{
						$query->select('user_id', 'user_name')->withTrashed();
					}, 'applyBelongsToUserSystemInfo' => function($query)
					{
						$query->with(['userSystemInfoBelongsToDepartment' => function($query) {
							$query->select('dept_id', 'dept_name');
						}])->withTrashed();
					}])
					->first();
		if(!empty($applyDetail)) {
			$applyDetail = $applyDetail->toArray();
		}
		return $applyDetail;
	}

	/**
     * [getStorageNo 获取申请编号]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]              [查询结果]
     */
	public function getApplyMaxNo()
	{
		return $this->entity->withTrashed()
					->orderBy('apply_bill', 'desc')
					->first();
	}

	/**
	 * [emptyStorageTable 清空申请表]
	 *
	 * @author 朱从玺
	 * @since  2015-11-05 创建
	 *
	 * @return [bool]            [清空结果]
	 */
	public function emptyApplyTable()
	{
		return $this->entity->truncate();
	}

    /**
     * 到期的办公用品申请列表
     *
     * @param
     *
     * @return array 到期的办公用品申请列表
     *
     * @author miaochenchen
     *
     * @since 2016-11-04
     */
    public function officeSuppliesReturnExpireList()
    {
    	return $query = $this->entity
    	                     ->select(['office_supplies.office_supplies_name','office_supplies_apply.apply_user','office_supplies_apply.return_date'])
    	                     ->leftJoin('office_supplies', 'office_supplies.id', '=', 'office_supplies_apply.office_supplies_id')
    	                     ->where('return_date', date('Y-m-d'))
    	                     ->where('real_return_date', '=', null)
    	                     ->get()->toArray();
    }

    //用户检验权限的apply详情
    public function getApplyDetailForCheck($applyId)
    {
        return $this->entity->select('id', 'office_supplies_id', 'apply_user', 'apply_status', 'return_status')
            ->with(['applyBelongsToSupplies' => function($query){
                $query->select('id', 'type_id', 'usage')->with([
                    'suppliesBelongsToType' => function($query){
                        $query->select('id', 'parent_id');
                    }
                ]);
            }])->find($applyId);
	}
	
	public function getOfficeSuppliesApplyName($id)
	{
		return $this->entity
		->leftJoin('office_supplies','office_supplies.id','=','office_supplies_apply.office_supplies_id')
		->whereIn('office_supplies_apply.id',$id)
		->get()
		->toArray();
	}
}
