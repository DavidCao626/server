<?php
namespace App\EofficeApp\Auth\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 用户实体
 *
 * @author lizhijun
 *
 * @since  2015-10-16 创建
 */
class LoginThemeEntity extends BaseEntity
{
    public $table = 'login_theme';

    public $primaryKey = 'id';

    protected $fillable = ['id', 'login_logo', 'login_tpl', 'background_type', 'background', 'background_attribute', 'form_left_text', 'form_left_background', 'elements', 'form_theme', 'form_theme_attribute'];
    
    public $timestamps = false;
}
