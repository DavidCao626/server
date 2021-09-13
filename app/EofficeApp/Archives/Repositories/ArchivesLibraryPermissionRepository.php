<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesLibraryPermissionEntity;

/**
 * 档案借阅申请Repository类:提供档案借阅申请相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesLibraryPermissionRepository extends BaseRepository
{

    public function __construct(ArchivesLibraryPermissionEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取卷库权限数据
     *
     * @param  int          $libraryId  卷库id
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getPermission($libraryId)
    {
    	return $this->entity->where('library_id', $libraryId)->pluck('department_id');
    }

    /**
     * 获取卷库id
     *
     * @param  int          $departmentId  部门id
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getLibraryIds($departmentId)
    {
        return $this->entity->where('department_id', $departmentId)->pluck('library_id');
    }

}