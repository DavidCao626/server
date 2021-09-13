<?php 

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 借阅申请Entity类:提供借阅申请表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesLibraryPermissionEntity extends BaseEntity
{
    /**
     * 卷库权限表
     *
     * @var string
     */
	public $table = 'archives_library_permission';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */	
    public $timestamps = false;
}