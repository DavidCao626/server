<?php 

namespace App\EofficeApp\System\Combobox\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 系统下拉框字段Entity类:提供系统下拉框字段数据表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SystemComboboxFieldEntity extends BaseEntity
{
    /**
     * 系统下拉框字段数据表
     *
     * @var string
     */
	public $table = 'system_combobox_field';

    /**
     * 主键
     *
     * @var string
     */    
    public $primaryKey = 'field_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */    
    public $dates = ['deleted_at'];

    /**
     * 字段与下拉框一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function fieldsCombobox() 
    {
        return  $this->hasOne('App\EofficeApp\System\Combobox\Entities\SystemComboboxEntity', 'combobox_id','combobox_id');
    }
}
