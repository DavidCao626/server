<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesDestroyEntity;

/**
 * 档案销毁Repository类:提供档案销毁相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesDestroyRepository extends BaseRepository
{

    public function __construct(ArchivesDestroyEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取档案文件附表数据
     *
     * @param  int $fileId  档案文件id
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDestroyDetail($where)
    {
    	return $this->entity->wheres($where)->first();
    }
}