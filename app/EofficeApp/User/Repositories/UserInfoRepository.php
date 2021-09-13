<?php
namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserInfoEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 用户info表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserInfoRepository extends BaseRepository
{
    public function __construct(UserInfoEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 删除用户信息
     *
     * @param  int $userId 用户id
     *
     * @return integer
     *
     * @author miaochenchen
     *
     * @since  2016-05-17
     */
    public function deleteUserInfoByUserId($userId)
    {
    	return $this->entity->where('user_id',$userId)->delete();
    }

    public function getUserInfoLists($fields = ['*'])
    {
        return $this->entity->select($fields)->leftJoin('user', 'user.user_id', '=', 'user_info.user_id')->get();
    }

    public function getUserSignaturPicture($userId) {
        return $this->entity->select(['signature_picture','user_id'])->where('user_id', $userId)->get()->toArray();
    }

    /**
     * @根据条件判断该手机号在user_info表是否存在
     *
     * @author miaochenchen
     *
     * @param array $where
     *
     * @return boolean
     */
    public function judgeUserPhoneNumberExists($where) {
        $result = $this->entity->select(['user_info.user_id'])
                               ->multiWheres($where)
                               ->whereHas('userInfoHasOneSystemInfo', function($query) {
                                    $query = $query->where('user_status', '>', '0');
                               })
                               ->first();
        if(empty($result)) {
            return false;
        }else{
            return $result['user_id'];
        }
    }
    /**
     * 获取当天生日用户
     */
    function getThisDateBrithday($date) {
        $query= $this->entity;
        $query = $query->select(['user_id'])
        ->whereHas('userInfoHasOneSystemInfo', function($query) {
            $query = $query->where('user_status', '>', '0')->where('user_status', '!=', '2');
        });
        $query = $query->whereRaw("substring_index(birthday, '-', -2) = '".$date."'");
        return $query->get()->toArray();
    }
    
    function getUsersByUserId($userId, $fields = ['*']) 
    {
        return $this->entity->select($fields)->whereIn('user_id', $userId)->get();
    }
}
