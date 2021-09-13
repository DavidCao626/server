<?php
namespace App\EofficeApp\PersonalSet\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PersonalSet\Entities\WinexeEntity;
/**
 * 快捷运行资源库类
 * 
 * @author  李志军
 * 
 * @since 2015-10-30
 */
use DB;
class WinexeRepository  extends BaseRepository
{
	/**
	 * 注册实体
	 * 
	 * @param \App\EofficeApp\PersonalSet\Entities\WinexeEntity $entity
	 * 
	 * @author  李志军
	 * 
	 * @since 2015-10-30
	 */
	public function __construct(WinexeEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * 获取快捷运行列表
	 * 
	 * @param string $userId
	 * @param array $fields
	 * 
	 * @return array 快捷运行列表
	 * 
	 * @author  李志军
	 * 
	 * @since 2015-10-30
	 */
	public function listShortcutsRun($userId, $fields)
	{
		$fields = empty($fields) ? ['*'] : $fields;
		$data 	= $this->entity->select($fields)->where('creator',$userId)->orderBy('win_number', 'asc')->get();
		if(!empty($data)){
			$creatorName = get_user_simple_attr($userId);
			foreach($data as &$value){
				$value->creator_name = $creatorName;
			}
		}
		return $data;
	}
}
