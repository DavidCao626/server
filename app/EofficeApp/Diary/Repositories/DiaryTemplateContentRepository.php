<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryTemplateContentEntity;
use DB;

/**
 * 微博Repository类:提供微博模板内容表操作
 *
 * @author lixuanxuan
 *
 * @since  2018-11-24 创建
 */
class DiaryTemplateContentRepository extends BaseRepository
{
    public function __construct(DiaryTemplateContentEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 判断模板内容是否存在
     * @param $param
     * @return mixed
     */
    public function tmplateContentExists($param)
    {
        return $this->entity->wheres($param)->exists();
    }

    /**
     * 获取模板内容
     * @param $param
     * @return mixed
     */
    public function getContent($param)
    {
        return $this->entity->wheres($param)->get()->toArray();
    }

}
