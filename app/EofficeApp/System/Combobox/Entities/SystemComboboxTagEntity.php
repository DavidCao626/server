<?php 

namespace App\EofficeApp\System\Combobox\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 系统下拉框标签Entity类:提供系统下拉框标签数据表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class SystemComboboxTagEntity extends BaseEntity
{
    /**
     * 系统下拉框标签数据表
     *
     * @var string
     */
	public $table = 'system_combobox_tag';

    /**
     * 主键
     *
     * @var string
     */    
    public $primaryKey = 'tag_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */    
    public $dates = ['deleted_at'];

    /**
     * 与下拉框关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function combobox() 
    {
        return  $this->hasMany('App\EofficeApp\System\Combobox\Entities\SystemComboboxEntity', 'tag_id', 'tag_id');
    }

}
