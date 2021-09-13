<?php

namespace App\EofficeApp\Document\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 文档标签Entity类:提供文档标签实体。
 *
 * @author niuxiaoke
 *
 * @since  2017-08-01 创建
 */
class DocumentTagEntity extends BaseEntity
{
    /** @var string 文档标签表 */
	public $table = 'document_tag';

    public $timestamps = false;

    /**
     * 文档标签和标签一对一
     *
     * @return object
     *
     * @author niuxiaoke
     *
     * @since  2017-08-01
     */
    public function hasOneTag()
    {
        return  $this->HasOne('App\EofficeApp\System\Tag\Entities\TagEntity', 'tag_id', 'tag_id');
    }
}