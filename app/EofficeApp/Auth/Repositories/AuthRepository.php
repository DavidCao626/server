<?php
namespace App\EofficeApp\Auth\Repositories;

use App\EofficeApp\Auth\Entities\AuthEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 用户表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class AuthRepository extends BaseRepository
{
    public function __construct(AuthEntity $entity)
    {
        parent::__construct($entity);
    }
	/**
	 * @根据账号获取用户信息
	 *
	 * @author 李志军
	 *
	 * @param string $userAccount
	 *
	 * @return object 用户对象
	 */
	public function getUserByAccount($userAccount)
	{
        return $this->entity->where('user_accounts',$userAccount)->first();
    }
    public function getUserByAccountOrMobile($userAccount)
    {
    	return $this->entity->leftJoin('user_info','user_info.user_id','=','user.user_id')
    						->leftJoin('user_system_info','user_system_info.user_id','=','user.user_id')
    						->where('user_system_info.user_status', '!=', '2')
    						->where('user_system_info.user_status', '!=', '0')
    						->where(function($query) use($userAccount){
    							$query->orWhere('user.user_accounts',$userAccount)
    								  ->orWhere('user_info.phone_number',$userAccount);
    						})
    						->first();
    }
}
