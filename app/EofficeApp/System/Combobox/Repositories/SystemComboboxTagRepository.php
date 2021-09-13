<?php

namespace App\EofficeApp\System\Combobox\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Combobox\Entities\SystemComboboxTagEntity;

/**
 * 下拉标签Repository类:提供下拉标签表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SystemComboboxTagRepository extends BaseRepository
{

    public function __construct(SystemComboboxTagEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取下拉表标签列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
	 */
	public function getTagsList(array $param = [])
	{
		$default = [
			'fields' 	=> ['*'],
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['tag_id'=>'asc'],
			'combobox'	=> 1,
			'page'      => 1,
		];
		$param = array_merge($default, $param);
		$query = $this->entity
			->select($param['fields'])
			->wheres($param['search'])
			->orders($param['order_by'])
			->with('combobox');
		if ($param['combobox'] == 1) {
			$query = $query->with('combobox');
		}
		$result = $query->get()->toArray();

		if(is_array($result)){
			foreach($result as &$val){

				if($val['tag_id'] == 2){
					$ids = [];
					if(isset($val['combobox']) && is_array($val['combobox'])){
						$item = $this->array_sort($val['combobox'],'combobox_id');
						foreach($item as $v){
							$ids[] = $v;
						}
						$val['combobox'] = $ids;
					}
					break;
				}
			}
		}
		return $result;
	}


	public function array_sort($arr,$keys,$type='asc')
	{
		$keysvalue =array();
		$new_array = array();
		foreach($arr as $k=>$v){
			if(!isset($v[$keys])) return $arr;
			$keysvalue[$k] = $v[$keys];
		}
		if($type == 'asc'){
			asort($keysvalue);
		}else{
			arsort($keysvalue);
		}
		reset($keysvalue);
		foreach ($keysvalue as $k=>$v){
			$new_array[$k] = $arr[$k];
		}
		return $new_array;
	}

}
