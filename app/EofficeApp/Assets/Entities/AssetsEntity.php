<?php

namespace App\EofficeApp\Assets\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 档案审核Entity类:提供档案审核实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class AssetsEntity extends BaseEntity
{
    /** @var string $table 入库表 */
	public $table = 'assets';
    use SoftDeletes;
    /** @var bool $timestamps 是否使用created_at和updated_at */
    public $timestamps = false;

    /** @var string $primaryKey 主键 */
    public $primaryKey = 'id';


    /**
     * 鉴定人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-16
     */
//    public function hasOneUser()
//    {
//        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
//    }


    /**
     * 关联资产分类表
     *
     * @return object
     *
     * @author zw
     *
     * @since  2015-12-16
     */
    public function assets_type()
    {
        return  $this->HasOne('App\EofficeApp\Assets\Entities\AssetsTypeEntity','id','type');
    }

    /**
     * 关联资产使用表
     *
     * @return object
     *
     * @author zw
     *
     * @since  2015-12-16
     */
    public function assets_applys(){
        return  $this->HasOne('App\EofficeApp\Assets\Entities\AssetsApplysEntity','assets_id','id');
    }

    /**
     * 关联资产使用表
     *
     * @return object
     *
     * @author zw
     *
     * @since  2015-12-16
     */
    public function applyTo(){
        return  $this->HasMany('App\EofficeApp\Assets\Entities\AssetsApplysEntity','assets_id','id');
    }

}