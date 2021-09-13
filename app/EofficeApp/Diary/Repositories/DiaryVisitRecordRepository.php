<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryVisitRecordEntity;

/**
 * 微博浏览记录Repository类:提供微博浏览记录操作
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryVisitRecordRepository extends BaseRepository
{
    public function __construct(DiaryVisitRecordEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查询微博浏览记录
     *
     * @param  array $param 查询条件
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function diaryVisitList($param = [])
    {
        $default = [
            'fields'    => ['visit_id','visit_person','visit_to_person','visit_num', 'updated_at'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['updated_at' => 'desc'],
        ];

        $param = array_merge($default, $param);

        $query = $this->entity
        ->select($param['fields']);

        $query = $this->parseVisitSearch($query, $param);

        if (!isset($param['search']['visit_person'])) {
            $query->with(['userVisitPerson' => function($query) {
                $query->select(['user_id', 'user_name','user_accounts']);
            }]);
        }

        if (!isset($param['search']['visit_to_person'])) {
            $query->with(['userVisitToPerson' => function($query) {
                $query->select(['user_id', 'user_name','user_accounts']);
            }]);
        }

        return $query->parsePage($param['page'], $param['limit'])
        ->orders($param['order_by'])->get()->toArray();
    }

    /**
     * 获取微博查看人数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getVisitTotal(array $param = [])
    {
        return $this->parseVisitSearch($this->entity, $param)->count();
    }

    /**
     * 更新微博浏览数
     *
     * @param  array $where 更新条件
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function diaryVisitIncrement(array $where = [])
    {
        return (bool) $this->entity->wheres($where)->increment('visit_num');
    }

    /**
     * 查询微博浏览数
     *
     * @param  array $where 更新条件
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-11-24
     */
    public function diaryHasVisit(array $where = [])
    {
        return (bool) $this->entity->wheres($where)->first();
    }

    /**
     * 获取微博查看where条件解析
     *
     * @param  array $param  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-11
     */
    public function parseVisitSearch($query, $param)
    {
        $query = $this->entity;

        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        if (isset($param['diarySet']) && $param['diarySet']['dimission'] == 1) {
            $userSearch = ['user_status' => ['0', '>']];
        } else {
            $userSearch = ['user_status' => [['0', '2'], 'not_in']];
        }

        if (isset($param['search']['visit_person'])) {
            $query = $query->whereHas('userVisitToSystemPerson', function ($query) use ($userSearch) {
                $query->wheres($userSearch);
            });
        } else if (isset($param['search']['visit_to_person'])) {
            $query = $query->whereHas('userVisitSystemPerson', function ($query) use ($userSearch) {
                $query->wheres($userSearch);
            });
        }

        return $query;
    }
}
