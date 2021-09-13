<?php

namespace App\EofficeApp\System\Signature\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 印章信息Entity类:提供印章信息数据表实体
 *
 * @author qishaobo
 *
 * @since  2016-01-22 创建
 */
class SignatureEntity extends BaseEntity
{
    /**
     * 印章信息数据表
     *
     * @var string
     */
	public $table = 'signature';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'signature_id';

    /**
     * 印章拥有者和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','signature_onwer');
    }

    /**
     * 印章拥有者和附件一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-01-25
     */
    public function hasOnePicture()
    {
        return  $this->HasOne('App\EofficeApp\Attachment\Entities\AttachmentEntity','attachment_id','signature_picture');
    }
 }