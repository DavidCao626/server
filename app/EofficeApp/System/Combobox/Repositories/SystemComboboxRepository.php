<?php

namespace App\EofficeApp\System\Combobox\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Combobox\Entities\SystemComboboxEntity;

/**
 * 下拉框Repository类:提供下拉框表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SystemComboboxRepository extends BaseRepository
{

    public function __construct(SystemComboboxEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取下拉表列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
	 */
	public function getComboboxList(array $param = [])
	{
		$default = [
			'fields'	=> ['*'],
			'page'  	=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['combobox_id'=>'asc'],
		];

		$param = array_merge($default, $param);

		return $this->entity
			->select($param['fields'])
			->wheres($param['search'])
			->orders($param['order_by'])
			->forPage($param['page'], $param['limit'])
			->get()
			->toArray();

	}

    /**
     * 获取下拉字段
     *
     * @param  array  $where 查询条件
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
	public function comboboxFields($where)
	{
		return $this->entity
		->select(['combobox_id', 'combobox_name'])
		->with(['comboboxFields' => function ($query) {
			$query->select(['field_id', 'combobox_id', 'field_name', 'field_value', 'field_order']);
		}])
		->wheres($where)
		->first();
	}
	
	public function getComboboxIdByIdentify($identify){
		if(!empty($identify)){
			$db_res = $this->entity->where('combobox_identify',$identify)->get()->toArray();
			if(isset($db_res[0]['combobox_id'])) return $db_res[0]['combobox_id'];
		}
		return 0;
	}

    /**
     * 获取所有下拉字段id
     *
     * @return array  查询结果
     *
     * @author qianxiaoyu
     *
     * @since  2017-09-11 创建
     */
    public function comboboxAllFieldsId(){
        return $this->entity->select(['combobox_id'])->get()->toArray();
    }
}
