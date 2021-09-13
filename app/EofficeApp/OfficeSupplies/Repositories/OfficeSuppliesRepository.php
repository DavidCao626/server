<?php

namespace App\EofficeApp\OfficeSupplies\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesEntity;

/**
 * office_supplies资源库
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesRepository extends BaseRepository
{
	public function __construct(OfficeSuppliesEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getPurchaseList 获取采购列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]           $param [查询条件]
	 *
	 * @since  2015-11-05 创建
	 *
	 * @return [object]                 [查询结果]
	 */
	public function getPurchaseList($param)
	{
		$default = array(
        	'fields' => ['*'],
        	'page' => 0,
        	'limit' => config('eoffice.pagesize'),
        	'order_by' => ['id' => 'asc'],
        	'search' => []
        );

        $param = array_merge($default, $param);

		$query = $this->entity->select($param['fields'])
					  ->parsePage($param['page'], $param['limit'])
					  ->orders($param['order_by'])
					  ->wheres($param['search'])
					  ->with(['suppliesHasManyStorage' => function($query)
					  {
					  	$query->selectRaw('max(price) as max_price,min(price) as min_price,office_supplies_id')->groupBy('office_supplies_id');
					  }])
					  ->with(['suppliesBelongsToType' => function($query)
					  {
					  	$query->select('id', 'type_name');
					  }]);

		if(isset($param['search']['stock_remind'])) {
			if($param['search']['stock_remind'][0]) {
				$query = $query->whereRaw('remind_min > stock_surplus');
			}else {
				$query = $query->where('stock_surplus', '<', 0);
			}
		}else {
			$query = $query->where(function($query)
					{
						$query->where(function($query)
						{
							$query->where('stock_remind', 1)
								  ->whereRaw('remind_min > stock_surplus');
						})
						->orWhere('stock_surplus', '<', 0);
					});
		}

		return $query->get()->toArray();
	}

	/**
	 * [getPurchaseCount 获取采购列表数据条数]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]           $param [查询条件]
	 *
	 * @since  2015-11-05 创建
	 *
	 * @return [int]                    [数据数]
	 */
	public function getPurchaseCount($param)
	{
		$search = isset($param['search']) ? $param['search'] : [];

		$query = $this->entity->wheres($search);

		if(isset($param['search']['stock_remind'])) {
			if($param['search']['stock_remind'][0]) {
				$query = $query->whereRaw('remind_min > stock_surplus');
			}else {
				$query = $query->where('stock_surplus', '<', 0);
			}
		}else {
			$query = $query->where(function($query)
					{
						$query->where(function($query)
						{
							$query->where('stock_remind', 1)
								  ->whereRaw('remind_min > stock_surplus');
						})
						->orWhere('stock_surplus', '<', 0);
					});
		}

		return $query->count();
	}

	/**
	 * [emptyStorageTable 库存归零]
	 *
	 * @author 朱从玺
	 *
	 * @since  2015-11-05 创建
	 *
	 * @return [bool]            [更新结果]
	 */
	public function emptySuppliesStock()
	{
		return $this->entity->where('id','>=','0')->update(['stock_total' => 0, 'stock_surplus' => 0]);
	}

	public function getSuppliesTotal($param){

        if (!isset($param['search'])) {
            $param['search'] = [];
        }
        if (isset($param['extra']['search'])){
            $param['search'] = array_merge($param['search'],$param['extra']['search']);
        }
        return $this->getTotal($param);

    }

	/**
	 * [getSuppliesList 获取办公用品列表,没有type层]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]           $params [查询条件]
	 *
	 * @return [object]                  [查询结果]
	 */
	public function getSuppliesList($param)
	{
		$default = array(
        	'fields' => ['*'],
        	'page' => 0,
        	'limit' => config('eoffice.pagesize'),
        	'order_by' => ['id' => 'asc'],
        	'search' => []
        );

        $param = array_merge($default, $param);
		$query = $this->entity->select($param['fields'])->with(['suppliesBelongsToType' => function($query)
            {
                $query->select('id', 'type_name','parent_id');
            }]);
		$query = $query->with(['attachments' => function($query){
		    $query->select('entity_id', 'attachment_id');
        }]);

		if (isset($param['search']) && !empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		if (isset($param['extra'])){
		    if(!empty($param['extra']['search'])){
                $query = $query->wheres($param['extra']['search']);
            }
        }

		$result =  $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get();
		foreach ($result as $key => $value){
		    if($value['specifications']){
		        $result[$key]['office_supplies_name'] .= "({$value['specifications']})";
            }
        }
	    return $result;
	}

    /**
     * 获取某种类型的办公用品id
     * @param $type_ids_arr
     */
	public function getAllSuppliesListByTypeIds($type_ids_arr)
    {
        $result = $this->entity->select(['*'])->wheres(['type_id'=>[$type_ids_arr,'in']])->get();
        if(count($result) > 0){
            return $result -> toArray();
        }else{
            return [];
        }
    }

    /**
     * @根据条件判断该办公用品是否存在
     *
     * @author miaochenchen
     *
     * @param array $where
     *
     * @return boolean
     */
    public function judgeOfficeSuppliesExists($where) {
        $result = $this->entity->select(['id'])->multiWheres($where)->first();
        if(empty($result)) {
            return false;
        }else{
            return $result['id'];
        }
    }
}
