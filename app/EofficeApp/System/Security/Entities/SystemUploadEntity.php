<?php

namespace App\EofficeApp\System\Security\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * system_upload表实体
 *
 * @author  朱从玺
 *
 * @since  2015-10-28
 */
class SystemUploadEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     * 
     * @var string
     */
    protected $table = 'system_upload';

    /**
     * [$guarded 允许被赋值的字段]
     * 
     * @var [array]
     */
    protected $fillable = ['upload_max_num', 'upload_single_max_size', 'upload_total_max_size', 'suffix', 'suffix_status', 'upload_full_src', 'file_name_rules', 'file_name_rules_html'];
    
    /**
     * [menuLang 与menu_lang表的关联关系]
     *
     * @method 朱从玺
     *
     * @since  2015-10-29 创建
     *
     * @return [object]   [关联关系]
     */
    public function menuLang()
    {
        return $this->hasMany('App\EofficeApp\Entities\MenuLangEntity', 'sys_func_no', 'function_id');
    }
}