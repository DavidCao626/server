<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 联系记录Entity类:提供联系记录实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class ContactRecordEntity extends BaseEntity
{
    use SoftDeletes;

    /** @var string 客户联系记录表 */
	public $table = 'customer_contact_record';

    /** @var string 主键 */
    public $primaryKey = 'record_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

    /**
     * 联系记录和客户一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-23
     */
    public function contactRecordCustomer()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity','customer_id','customer_id');
    }

    /**
     * 联系记录和联系人
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-23
     */
    public function contactRecordLinkman()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\LinkmanEntity','linkman_id','linkman_id');
    }

    /**
     * 联系记录和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function contactRecordCreator()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','record_creator');
    }

    /**
     * 联系记录和评论一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-27
     */
    public function HasManyComment()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\ContactRecordCommentEntity','record_id','record_id');
    }
}