<?php

namespace App\EofficeApp\OfficeSupplies\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesStorageEntity;
use Illuminate\Support\Arr;
/**
 * office_supplies_storage资源库
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesStorageRepository extends BaseRepository
{
	public function __construct(OfficeSuppliesStorageEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getStorageDetail 获取入库记录]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]              $storageId [入库ID]
	 *
	 * @since 2015-11-04 创建
	 *
	 * @return [object]                      [查询结果]
	 */
	public function getStorageDetail($storageId)
	{
		return $this->entity
					->where('id', $storageId)
					->with(['storageBelongsToUser' => function($query)
					{
						$query->select('user_id', 'user_name');
					}, 'storageBelongsToSupplies' => function($query)
					{
						$query->select('id', 'office_supplies_name', 'specifications', 'stock_surplus');
					}, 'storageBelongsToType' => function($query)
					{
						$query->select('id', 'type_name');
					}])
					->first();
	}

    /**
     * [storageBillExists 是否存在入库单据号]
     * @param $storage_bill
     * @return bool
     */
	public function storageBillExists($storage_bill)
    {
        $exists = $this->entity
            ->where('storage_bill',$storage_bill)
            ->withTrashed()
            ->exists();

        return $exists;
    }

	/**
     * [getStorageNo 获取入库编号]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]              [查询结果]
     */
	public function getStorageMaxNo()
	{
		return $this->entity->withTrashed()
					->orderBy('id', 'desc')
					->first();
	}

	/**
	 * [getStorageCount 获取入库列表数量]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]          $param [查询条件]
	 *
	 * @since 2015-11-04 创建
	 *
	 * @return [int]                   [查询结果]
	 */
	public function getStorageCount($param)
	{
		if(isset($param['office_supplies_id']) && $param['office_supplies_id'])	{
			$default['search'] = ['office_supplies_id' => [$param['office_supplies_id']]];
			$param = array_merge($default, $param);
		}

		$param['search'] = isset($param['search']) ? $param['search'] : [];

		$query = $this->entity->wheres($param['search']);

		if(isset($param['supplies_search'])) {
			$query = $query->whereHas('storageBelongsToSupplies', function($query) use ($param)
			{
				$query->wheres($param['supplies_search']);
			});
		}

		//查询已删除的
        if (Arr::get($param, 'withTrashed')) {
            $query->withTrashed();
        }

		return $query->count();
	}

	/**
	 * [getStorageList 获取入库记录列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]          $param [查询条件]
	 *
	 * @since 2015-11-04 创建
	 *
	 * @return [object]                [查询结果]
	 */
	public function getStorageList($param)
	{
        $default = array(
        	'fields' => ['*'],
        	'page' => 0,
        	'limit' => config('eoffice.pagesize'),
        	'order_by' => ['id' => 'desc'],
        	'search' => []
        );
		if(isset($param['office_supplies_id']) && $param['office_supplies_id'])	{
			$param['search']['office_supplies_id'] = [$param['office_supplies_id']];
		}
        $param = array_merge($default, $param);
		$query = $this->entity->select($param['fields'])
					->parsePage($param['page'], $param['limit'])
					->orders($param['order_by'])
					->wheres($param['search'])
					->with(['storageBelongsToUser' => function($query)
					{
					  	$query->select('user_id', 'user_name');
					}, 'storageBelongsToSupplies' => function($query)
					{
					  	$query->select('id', 'office_supplies_name', 'stock_surplus', 'remind_max', 'remind_min', 'stock_total', 'specifications');
					}, 'storageBelongsToType' => function($query)
					{
						$query->select('id', 'type_name','parent_id');
					}]);

		if(isset($param['supplies_search'])) {
			$query = $query->whereHas('storageBelongsToSupplies', function($query) use ($param)
			{
				$query->wheres($param['supplies_search']);
			});
		}

		return $query->get()->toArray();
	}

	/**
	 * [getStoragePrice 获取入库最低或最高价格]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]             $officeSuppliesId [办公用品ID]
	 * @param  [string]          $priceType        [价格类型,min最低价max最高价]
	 *
	 * @since 2015-11-05 创建
	 *
	 * @return [float]                             [查询结果]
	 */
	public function getStoragePrice($officeSuppliesId, $priceType)
	{
		$query = $this->entity->where('office_supplies_id', $officeSuppliesId);

		if($priceType == 'min') {
			$query = $query->orderBy('price', 'asc');
		}else {
			$query = $query->orderBy('price', 'desc');
		}

		return $query->first();
	}

	/**
	 * [emptyStorageTable 清空入库表]
	 *
	 * @author 朱从玺
	 *
	 * @return [bool]            [清空结果]
	 */
	public function emptyStorageTable()
	{
		return $this->entity->truncate();
	}

	/*
	*自己获取入库列表数据
	*/
	public function getCustomStorageList(&$param)
    {
        $query = \DB::table('office_supplies_storage')->select("office_supplies_storage.*", "office_supplies.remind_max as storage_remind_max", "office_supplies.remind_min as storage_remind_min", "office_supplies.specifications")
            ->leftJoin('office_supplies', 'office_supplies.id', '=', 'office_supplies_storage.office_supplies_id');
        $query = $query->whereNull('office_supplies_storage.deleted_at');
        if (isset($param['search']['type_id'])) {
            $type_id = $param['search']['type_id'];
            unset($param['search']['type_id']);
            $param['search']['office_supplies.type_id'] = $type_id;
        }
        return $query;
    }

    /*
	*自己获取入库详情
	*/
    public function getCustomStorageDetail($storageId)
    {
        return \DB::table('office_supplies_storage')
            ->select("office_supplies_storage.*", "office_supplies.remind_max as storage_remind_max", "office_supplies.remind_min as storage_remind_min", "office_supplies.specifications")
            ->leftJoin('office_supplies', 'office_supplies.id', '=', 'office_supplies_storage.office_supplies_id')
            ->where('office_supplies_storage.id', $storageId);
	}

	public function getofficeSuppliesStorageName($id)
	{
		return $this->entity
		->leftJoin('office_supplies','office_supplies.id','=','office_supplies_storage.office_supplies_id')
		->whereIn('office_supplies_storage.id',$id)
		->get()
		->toArray();
	}
}
