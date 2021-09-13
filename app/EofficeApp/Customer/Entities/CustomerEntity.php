<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 客户Entity类:提供客户实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class CustomerEntity extends BaseEntity
{
    use CustomerEntityTrait;
    use SoftDeletes;

    /** @var string 客户表 */
	public $table = 'customer';

    /** @var string 主键 */
    public $primaryKey = 'customer_id';

    /**  @var array 关联字段 */
    public $relationFields = [
        'fields' => [
            'userManager'                       => ['customer_manager_name' => 'user_name'],
            'userService'                       => ['customer_service_manager_name' => 'user_name'],
            'userCreator'                       => ['customer_creator_name'=> 'user_name'],
            'hasManyDept.hasOneDept'            => ['dept_name'],
            'hasManyRole.hasOneRole'            => ['role_name'],
            'hasManyUser.hasOneUser'            => ['user_name'],
            'hasOneProvince'                    => ['province_name'],
            'hasOneCity'                        => ['city_name'],
            'customerhasManyApplyPermission'    => ['apply_permission', 'proposer', 'apply_status'],
            'hasManyVisits'                     => ['visit_time'],
            'customerHasManyContactRecord'      => ['contact_records' => 'record_id'],
            'hasManyTags'                       => ['tag_id'],
        ],
        'relation' => [
            'userManager'                       => ['user_id', 'customer_manager'],
            'userService'                       => ['user_id', 'customer_service_manager'],
            'userCreator'                       => ['user_id', 'customer_creator'],
            'hasManyDept.hasOneDept'            => ['customer_id', 'customer_id'],
            'hasManyRole.hasOneRole'            => ['customer_id', 'customer_id'],
            'hasManyUser.hasOneUser'            => ['customer_id', 'customer_id'],
            'hasOneProvince'                    => ['province_id', 'province'],
            'hasOneCity'                        => ['city_id', 'city'],
            'customerhasManyApplyPermission'    => ['customer_id', 'customer_id'],
            'hasManyVisits'                     => ['customer_id', 'customer_id'],
            'customerHasManyContactRecord'      => ['customer_id', 'customer_id'],
            'hasManyTags'                       => ['customer_id', 'customer_id'],
        ]
    ];

    /**
     * 客户和客户自定义字段一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerSubEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户经理和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function userManager($operate = 0)
    {
        return $this->hasOneModel('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'customer_manager', $operate);
    }

    /**
     * 客服经理和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function userService()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'customer_service_manager');
    }

   /**
     * 客户创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function userCreator()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'customer_creator');
    }

    /**
     * 客户和客户日志一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function customerLogs()
    {
        return  $this->HasMany('App\EofficeApp\System\Log\Entities\LogEntity', 'log_relation_id', 'customer_id');
    }

    /**
     * 多个客户共享部门
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-18
     */
    public function hasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerPermissionDepartmentEntity', 'customer_id', 'customer_id');
    }

    /**
     * 多个客户共享角色
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-18
     */
    public function hasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerPermissionRoleEntity', 'customer_id', 'customer_id');
    }

    /**
     * 多个客户共享用户
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-18
     */
    public function hasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerPermissionUserEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户和省一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-22
     */
    public function hasOneProvince()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\ProvinceEntity', 'province_id', 'province');
    }

    /**
     * 客户和市一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-22
     */
    public function hasOneCity()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CityEntity', 'city_id', 'city');
    }

    /**
     * 客户和访问计划一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function hasManyVisits()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerWillVisitEntity', 'customer_id', 'customer_id');
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
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerLinkmanEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户和权限申请一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function customerhasManyApplyPermission()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerApplyPermissionEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户和联系记录一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-20
     */
    public function customerHasManyContactRecord()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerContactRecordEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户和客户关注一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-20
     */
    public function customerHasManyAttention()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\AttentionEntity', 'customer_id', 'customer_id');
    }

    /**
     * 我是否关注
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-23
     */
    public function customerHasOneAttention()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerAttentionEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户和访问计划一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-20
     */
    public function willVisit()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerWillVisitEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户logo和附件一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-23
     */
    public function hasOneLogo()
    {
        return  $this->HasOne('App\EofficeApp\Attachment\Entities\AttachmentEntity', 'attachment_id', 'customer_logo');
    }

    /**
     * 客户和合同一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-23
     */
    public function customerHasManyContract()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerContractEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户和业务机会一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-23
     */
    public function customerHasManyChances()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerBusinessChanceEntity', 'customer_id', 'customer_id');
    }

    /**
     * 客户和客户日志一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-23
     */
    public function hasManyLogs()
    {
        return  $this->HasMany('App\EofficeApp\System\Log\Entities\LogEntity', 'log_relation_id', 'customer_id');
    }

    /**
     * 客户和客户标签一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-27
     */
    public function hasManyTags()
    {
        return  $this->HasMany('App\EofficeApp\Customer\Entities\CustomerTagEntity', 'customer_id', 'customer_id');
    }
}