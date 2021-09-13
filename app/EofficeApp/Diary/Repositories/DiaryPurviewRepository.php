<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryPurviewEntity;

/**
 * 微博便签Repository类:提供微博便签表操作
 *
 * @author qishaobo
 *
 * @since  2016-04-15 创建
 */
class DiaryPurviewRepository extends BaseRepository
{
    public function __construct(DiaryPurviewEntity $entity)
    {
        parent::__construct($entity);
    }

    

    public function getPurviewGroupTotal($param)
    {
        $query = $this->entity;

        if(isset($param['search']) && !empty($param['search'])){
            $query = $query->wheres($param['search']);
        }

        return $query->count();
    }
    public function getPurviewGroupList($param)
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['id' => 'desc'],
        ];
        $param = array_merge($default, $param);
        $query = $this->entity;

        if(isset($param['search']) && !empty($param['search'])){
            $query = $query->wheres($param['search']);
        }

        $query->orders($param['order_by']);

        if($param['page'] == 0){
            return $query->get();
        }
        return $query->parsePage($param['page'], $param['limit'])->get();
    }
}