<?php
namespace App\EofficeApp\PersonalSet\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PersonalSet\Entities\ClientSetEntity;
/**
 * 客户端设置资源库类
 * 
 * @author  李志军
 * 
 * @since 2015-10-30
 */
class ClientSetRepository  extends BaseRepository
{
	/**
	 * 注册实体
	 * 
	 * @param \App\EofficeApp\PersonalSet\Entities\ClientSetEntity $entity
	 * 
	 * @author  李志军
	 * 
	 * @since 2015-10-30
	 */
	public function __construct(ClientSetEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * 判断该用户客户端设置信息是否存在
	 * 
	 * @param string $userId
	 * 
	 * @return int 判断结果
	 * 
	 * @author  李志军
	 * 
	 * @since 2015-10-30
	 */
	public function clientSetExists($userId) {
		return $this->entity->where('user_id',$userId)->count();
	}
	/**
	 * 获取客户端设置信息
	 * 
	 * @param string $userId
	 * 
	 * @return object
	 * 
	 * @author  李志军
	 * 
	 * @since 2015-10-30
	 */
	public function getClientDoc($userId)
	{
		return $this->entity->where('user_id',$userId)->first();
	}
}
