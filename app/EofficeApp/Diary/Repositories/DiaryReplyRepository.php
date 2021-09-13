<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryReplyEntity;

/**
 * 微博回复Repository类:提供微博回复表操作
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryReplyRepository extends BaseRepository
{
    public function __construct(DiaryReplyEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查询微博回复
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function diaryReplysList($param = [])
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['diary_reply_id' => 'desc'],
        ];

        $param = array_merge($default, $param);

        return $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->with(['hasOneUser' => function($query) {
                $query->select(['user_id', 'user_name']);
            }])
            ->orders($param['order_by'])
            ->forPage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }
}