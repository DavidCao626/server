<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryTemplateSetEntity;

/**
 * 模板设置信息表
 *
 * @author dp
 *
 * @since  2015-10-20 创建
 */
class DiaryTemplateSetRepository extends BaseRepository
{
    public function __construct(DiaryTemplateSetEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查看模板设置信息
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果
     *
     * @author dp
     *
     * @since  2015-10-21
     */
    public function diaryTemplateSetList($param = [])
    {
        $default = [
            'fields'    => ['*'],
            // 'page'      => 0,
            // 'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            // 'order_by'  => ['revert_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
                ->select($param['fields'])
                ->wheres($param['search'])
                ->with("hasManyUser")
                // ->orders($param['order_by'])
                ;
        // 翻页判断
        // $query = $query->parsePage($param['page'], $param['limit']);
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
}
