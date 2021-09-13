<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;


class LinkmanEntity extends BaseEntity
{
	public $table = 'customer_linkman';

    public $primaryKey = 'linkman_id';

    /**
     * 联系人和客户一对一
     */
    public function linkmanCustomer()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity','customer_id','customer_id');
    }

    /**
     * 联系人和客户一对一
     *
     */
    public function hasOneCustomer()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity','customer_id','customer_id');
    }

    /**
     * 联系人创建人和用户一对一
     *
     */
    public function linkmanCreatorHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','linkman_creator');
    }

    /**
     * 联系人和联系人附表关系
     *
     */
    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerLinkmanSubEntity','customer_linkman_id','linkman_id');
    }

    /**
     * 联系人照片和附件一对一
     *
     */
    public function hasOnePicture()
    {
        return  $this->HasOne('App\EofficeApp\Attachment\Entities\AttachmentEntity','attachment_id','linkman_picture');
    }

    /**
     * 联系人和联系记录一对多
     *
     */
    public function hasManyRecords()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerContactRecordEntity', 'linkman_id', 'linkman_id');
    }
}