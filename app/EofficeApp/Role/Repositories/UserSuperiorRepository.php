<?php

namespace App\EofficeApp\Role\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Role\Entities\UserSuperiorEntity;

/**
 * 用户上下级Repository类:提供用户上下级表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class UserSuperiorRepository extends BaseRepository {

    public function __construct(UserSuperiorEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取上下级
     *
     * @param  string $userId
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getUserSuperior($userId) {
        return $this->entity
                        ->where('user_id', $userId)
                        ->orWhere('superior_user_id', $userId)
                        ->get()
                        ->toArray();
    }

    /**
     * 获取直接上级
     *
     * @param  string $userId
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since  2016-07-26 创建
     */
    public function getUserImmediateSuperior($userId, $param=[]) {
        $query = $this->entity
                      ->select(['user_superior.*']);
            if (is_array($userId)) {
                $query = $query->whereIn('user_superior.user_id', $userId);
            } else {
                $query = $query->where('user_superior.user_id', $userId);
            }
                      
        if (isset($param['include_leave']) && !$param['include_leave']) {
            // 排除离职的
            $query = $query->join('user', 'user.user_id', '=', 'user_superior.superior_user_id')
                           ->where('user.user_accounts', '!=', '');
        }
        return $query->get()->toArray();
    }

    /**
     * 获取直接下级
     *
     * @param  string $userId
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since  2016-07-26 创建
     */
    public function getUserImmediateSubordinate($userId, $param=[]) {
        if (isset($param['search']['user_accounts'])) {
            unset($param['search']['user_accounts']);
        }
        $query = $this->entity->where('user_superior.superior_user_id', $userId);
        if(isset($param['search']) && !empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->get()->toArray();
    }

    /**
     * 删除上下级
     *
     * @param  string $userId
     *
     * @return integer
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteUserSuperior($userId) {
        return $this->entity
                        ->where('user_id', $userId)
                        ->orWhere('superior_user_id', $userId)
                        ->delete();
    }
    public function deleteUserSuperiorarr($userId) {
        return $this->entity
                        ->where('user_id', $userId)
                        ->delete();
    }

    /**
     * 获取下级
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-12-24 创建
     */
    public function getUserSuperiorList($param) {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, $param);

        $where = [];
        if (isset($param['user_name']) || isset($param['search']['user_name'])) {

            if (isset($param['search']['user_name'])) {
                $where['user_name'] = $param['search']['user_name'];
                unset($param['search']['user_name']);
            } else {
                $where['user_name'] = $param['user_name'];
                unset($param['user_name']);
            }
        }

        $query = $this->entity->wheres($param['search']);

        if (!empty($where)) {
            $query = $query->whereHas('subordinateHasOneUser', function ($query) use ($where) {
                if (!empty($where)) {
                    $query->wheres($where);
                }
            });
        } else {
            if (isset($param['diarySet']) && $param['diarySet']['dimission'] == 1) {
                $userSearch = ['user_status' => ['0', '>']];
            } else {
                $userSearch = ['user_status' => [['0', '2'], 'not_in']];
            }

            $query = $query->whereHas('userHasOneSystemInfo', function ($query) use ($userSearch) {
                $query->wheres($userSearch);
            });
        }

        $query = $query->with(['subordinateHasOneUser' => function($query) use ($param) {
        $query = $query->select(['user_id', 'user_name'])
                ->with('userHasOneInfo');
        if (isset($param['withUserDept'])) {
            $query->with('userToDept');
        }
        $query->with(['userHasOneSystemInfo' => function($query) use ($param) {
            $query->select(['user_id', 'user_status']);
        }]);
    }]);

        if (isset($param['page']) && $param['page'] !== 0) {
            $query = $query->forPage($param['page'], $param['limit']);
        }
        return $query->get()->toArray();
    }
    public function deleteUserSubordinate($userId) {
        return $this->entity
                        ->where('superior_user_id', $userId)
                        ->delete();
    }
}
