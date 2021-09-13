<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryAttentionEntity;

/**
 * 微博关注Repository类:提供微博关注表操作
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryAttentionRepository extends BaseRepository
{
    public function __construct(DiaryAttentionEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查询微博关注人
     *
     * @param  array  $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function diaryAttentionList($param = [])
    {   
        $default = [
            'fields'     => ['attention_id','attention_person','attention_to_person','attention_status','created_at'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['attention_id' => 'asc'],
            'returntype' => 'array',
        ];

        $param = array_merge($default, $param);

        $query = $this->entity
        ->select($param['fields']);

        $query = $this->parseAttentionSearch($query, $param);

        if (!isset($param['search']['attention_person'])) {
            $query->with(['userAttention' => function($query) use ($param) {
                $query->select(['user_id', 'user_name']);
                if (isset($param['withDeptMy'])) {
                    $query->with('userToDept');
                }
                // $query->with('userHasOneSystemInfo');
                $query->with(['userHasOneSystemInfo' => function($query) use ($param) {
                    $query->select(['user_id', 'user_status']);
                }]);
            }]);
        }

        if (!isset($param['search']['attention_to_person'])) {
            $query->with(['userAttentionToPerson' => function($query) use ($param) {
                $query->select(['user_id', 'user_name']);
                if (isset($param['withDept'])) {
                    $query->with('userToDept');
                }
                $query->with(['userHasOneSystemInfo' => function($query) use ($param) {
                    $query->select(['user_id', 'user_status']);
                }]);
            }]);
        }
        $query = $query->orders($param['order_by']);
        $query = $query->parsePage($param['page'], $param['limit']);
        $query = $query->groupBy(['attention_person', 'attention_to_person']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "first") {
            return $query->get()->first();
        }
    }

    /**
     * 查询微博关注人
     *
     * @param  array  $where 查询条件
     * @param string $userName
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function diaryAttentionToPerson($where = [], $whereName = [])
    {
        $query = $this->entity;

        if (isset($where['diarySet']) && $where['diarySet']['dimission'] == 1) {
            $userSearch = ['user_status' => ['0', '>']];
        } else {
            $userSearch = ['user_status' => [['0', '2'], 'not_in']];
        }

        unset($where['diarySet']);

        return $query->wheres($where)
                ->whereHas('userAttentionToPerson', function($query) use ($whereName, $userSearch) {
                    if (!empty($whereName)) {
                        $query->wheres($whereName);
                    }

                    $query->whereHas('userHasOneSystemInfo', function($query) use ($userSearch) {
                        $query->wheres($userSearch);
                    });
                })
                ->pluck('attention_to_person')
                ->toArray();
    }

    /**
     * 获取微博关注人数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getAttentionTotal(array $param = [])
    {
        $res =  $this->parseAttentionSearch($this->entity, $param)->groupBy(['attention_person', 'attention_to_person'])->get();
        return count($res);
    }

    /**
     * 获取微博关注人数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getAttentionDetail(array $param = [])
    {
        return $this->entity->wheres($param)->first();
    }


    /**
     * 获取微博关注where条件解析
     *
     * @param  array $param  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-11
     */
    public function parseAttentionSearch($query, $param)
    {
        $query = $this->entity;

        if (isset($param['diarySet']) && $param['diarySet']['dimission'] == 1) {
            $userSearch = ['user_status' => ['0', '>']];
        } else {
            $userSearch = ['user_status' => [['0', '2'], 'not_in']];
        }
        if (isset($param['search']['user_name'])) {
            $userSearch['user_name'] = $param['search']['user_name'];
            unset($param['search']['user_name']);
        }

        if (isset($param['search']['attention_person'])) {
            $query = $query->whereHas('userAttentionToPerson.userHasOneSystemInfo', function ($query) use ($userSearch) {
                $query->wheres($userSearch);
            });
        } else if (isset($param['search']['attention_to_person'])) {
            $query = $query->whereHas('userAttention.userHasOneSystemInfo', function ($query) use ($userSearch) {
                $query->wheres($userSearch);
            });
        }

        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query;
    }

}
