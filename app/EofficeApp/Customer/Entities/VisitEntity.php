<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;


class VisitEntity extends BaseEntity
{
    /** @var string 客户访问计划表 */
	public $table = 'customer_will_visit';

    /** @var string 主键 */
    public $primaryKey = 'visit_id';

    public $timestamps = false;

    /**
     * 访问计划和客户一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-03-25
     */
    public function willVisitCustomer()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity','customer_id','customer_id');
    }

    /**
     * 访问计划和联系人
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-03-25
     */
    public function willVisitLinkman()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\LinkmanEntity','linkman_id','linkman_id');
    }

    /**
    * 访问计划和用户信息一对一
    *
    * @return object
    *
    * @author qishaobo
    *
    * @since  2015-10-21
    */
    public function willVisitUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','visit_creator');
    }

   /**
    * 提醒和提醒人一对多
    *
    * @return object
    *
    * @author qishaobo
    *
    * @since  2016-06-17
    */
    public function hasManyReminder()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\VisitReminderEntity','visit_id','visit_id');
    }
}