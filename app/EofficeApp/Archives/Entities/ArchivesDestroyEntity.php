<?php

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 档案销毁Entity类:提供档案销毁表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesDestroyEntity extends BaseEntity
{
    /**
     * 档案销毁表
     *
     * @var string
     */
	public $table = 'archives_destroy';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'destroy_id';
}