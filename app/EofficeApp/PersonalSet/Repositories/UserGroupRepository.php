<?php
namespace App\EofficeApp\PersonalSet\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PersonalSet\Entities\UserGroupEntity;
/**
 * 用户组资源库类
 *
 * @author  李志军
 *
 * @since 2015-10-30
 */
class UserGroupRepository  extends BaseRepository
{

	private $user_id;
	/**
	 * 注册实体
	 *
	 * @param \App\EofficeApp\PersonalSet\Entities\UserGroupEntity $entity
	 *
	 * @author  李志军
	 *
	 * @since 2015-10-30
	 */
	public function __construct(UserGroupEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * 获取用户组列表
	 *
	 * @param string $userId
	 * @param array $fields
	 *
	 * @return array 用户组列表
	 *
	 * @author  李志军
	 *
	 * @since 2015-10-30
	 */
	public function listUserGroup($params,$userId)
	{
            $default = [
            'fields' => ['*'],
            'page' => 0,
            //'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['group_id' => 'asc'],
        ];
        if (isset($params['search']['user_accounts'])) {
            unset($params['search']['user_accounts']);
        }
		$param = array_merge($default, array_filter($params));
		$query = $this->entity->select($param['fields']);

        if (isset($param["group_member"]) && $param["group_member"]) {
            $query = $query->whereRaw('find_in_set(?,group_member)', [intval($param["group_member"])]);
        }
        return $query->multiWheres($param['search'])
                        ->where('creator',$userId)
                        ->orders($param['order_by'])
                        //->forPage($param['page'], $param['limit'])
                        ->get()->toArray();
	}

    //获取用户组包含自己的组信息
    public function getUserGrop($param){

    	$this->user_id = $param["user_id"];
        return  $this->entity->whereRaw('find_in_set(?,group_member )', [$this->user_id])
                ->orWhere(function ($query) {
	                    $query->where('creator', $this->user_id);
	        })->get()->toArray();
    }

}
