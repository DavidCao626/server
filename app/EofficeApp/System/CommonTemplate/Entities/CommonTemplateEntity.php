<?php

namespace App\EofficeApp\System\CommonTemplate\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 公共模板Entity类:提供公共模板数据表实体
 *
 * @author qishaobo
 *
 * @since  2016-01-22 创建
 */
class CommonTemplateEntity extends BaseEntity
{
    /**
     * 公共模板数据表
     *
     * @var string
     */
	public $table = 'common_template';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'template_id';


    /**
     * 公共模板和附件一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-01-25
     */
    public function hasOnePicture()
    {
        return  $this->HasOne('App\EofficeApp\Attachment\Entities\AttachmentEntity','attachment_id','template_picture');
    }
 }