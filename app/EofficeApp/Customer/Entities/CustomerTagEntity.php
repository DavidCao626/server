<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 客户标签Entity类:提供客户标签实体。
 *
 * @author qishaobo
 *
 * @since  2016-05-27 创建
 */
class CustomerTagEntity extends BaseEntity
{
    /** @var string 客户标签表 */
	public $table = 'customer_tag';

    public $timestamps = false;

    /**
     * 客户标签和标签一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-27
     */
    public function hasOneTag()
    {
        return  $this->HasOne('App\EofficeApp\System\Tag\Entities\TagEntity', 'tag_id', 'tag_id');
    }
}