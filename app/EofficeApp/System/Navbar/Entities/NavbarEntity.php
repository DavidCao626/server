<?php 

namespace App\EofficeApp\System\Navbar\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 公司信息Entity类:提供公司信息数据表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NavbarEntity extends BaseEntity
{
    /**
     * 公司信息数据表
     *
     * @var string
     */
	public $table = 'navbar_guid';

    /**
     * 主键
     *
     * @var string
     */    
    public $primaryKey = 'id';


    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['id', 'navbar_name', 'navbar_icon', 'is_select', 'operate_value', 'operate_type'];
 }