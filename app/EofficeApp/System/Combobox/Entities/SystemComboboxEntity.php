<?php 

namespace App\EofficeApp\System\Combobox\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 系统下拉框Entity类:提供系统下拉框数据表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SystemComboboxEntity extends BaseEntity
{
    /**
     * 系统下拉框数据表
     *
     * @var string
     */
	public $table = 'system_combobox';

    /**
     * 主键
     *
     * @var string
     */    
    public $primaryKey = 'combobox_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */    
    public $dates = ['deleted_at'];

    /**
     * 下拉框与字段一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function comboboxFields() 
    {
        return  $this->hasMany('App\EofficeApp\System\Combobox\Entities\SystemComboboxFieldEntity', 'combobox_id','combobox_id');
    }
}
