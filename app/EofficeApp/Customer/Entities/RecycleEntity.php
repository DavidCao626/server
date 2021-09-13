<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 回收站Entity类:提供回收站实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RecycleEntity extends BaseEntity
{
    /**
     * @var string 客户回收站表 */
	public $table = 'customer_recycle';

    /**  @var string 主键 */
    public $primaryKey = 'customer_id';

    /**
     * 客户回收站和客户回收站自定义字段一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\RecycleSubEntity','customer_recycle_id','customer_id');
    }

    /**
     * 客户回收站客户经理和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function userManager()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','customer_manager');
    }

    /**
     * 客户回收站客服经理和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function userService()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','customer_service_manager');
    }

    /**
     * 客户回收站和省一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-22
     */
    public function hasOneProvince()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\ProvinceEntity','province_id','province');
    }

    /**
     * 客户回收站和市一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-22
     */
    public function hasOneCity()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CityEntity','city_id','city');
    }

    /**
     * 创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-06-30
     */
    public function userCreator()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','customer_creator');
    }

    /**
     * 客户和联系人一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-04-12
     */
    public function customerHasManyLinkman()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\LinkmanEntity', 'customer_id', 'customer_id');
    }
}