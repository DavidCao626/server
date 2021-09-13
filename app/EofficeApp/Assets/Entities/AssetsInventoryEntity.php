<?php

namespace App\EofficeApp\Assets\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 档案审核Entity类:提供档案审核实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class AssetsInventoryEntity extends BaseEntity
{
    /** @var string $table 档案鉴定表 */
	public $table = 'assets_inventory';

    /** @var bool $timestamps 是否使用created_at和updated_at */
    public $timestamps = true;

    /** @var string $primaryKey 主键 */
    public $primaryKey = 'id';


    /**
     * 关联资产主表
     *
     * @return object
     *
     * @author zw
     *
     * @since  2016-6-9
     */
    public function assets()
    {
        return  $this->HasOne('App\EofficeApp\Assets\Entities\AssetsEntity','id', 'assets_id');
    }

    /**
     * 关联流程表
     *
     * @return object
     *
     * @author zw
     *
     * @since  2016-6-9
     */
    public function run_flow()
    {
        return  $this->HasOne('App\EofficeApp\Assets\Entities\AssetsFlowEntity','assets_id','assets_id');
    }

    /**
     * 关联资产分类表
     *
     * @return object
     *
     * @author zw
     *
     * @since  2016-6-9
     */
    public function assets_type()
    {
        return  $this->HasOne('App\EofficeApp\Assets\Entities\AssetsTypeEntity','id','type');
    }

    /**
     * 鉴定人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-6-9
     */
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','operator');
    }

    /**
     * [applyBelongsToUser 申请记录与用户多对一关系]
     *
     * @author zw
     *
     * @since  2016-6-9 创建
     *
     * @return [object]             [关联关系]
     */
    public function applyBelongsToUser()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'apply_user', 'user_id');
    }

    /**
     * [applyBelongsToUserSystemInfo 申请记录与用户系统信息多对一关系]
     *
     * @author miaochenchen
     *
     * @since  2016-6-9 创建
     *
     * @return [object]             [关联关系]
     */
    public function applyBelongsToUserSystemInfo()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserSystemInfoEntity','apply_user','user_id');
    }
}