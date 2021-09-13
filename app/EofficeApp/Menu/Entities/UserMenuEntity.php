<?php
namespace app\EofficeApp\Menu\Entities;

use App\EofficeApp\Base\BaseEntity;
//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户菜单实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class UserMenuEntity extends BaseEntity {
    //use SoftDeletes;
    /** @var string $table 定义实体表 */
    public $table = 'user_menu';
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'user_menu_id';
    /** @var string $dates 删除日期字段 */
    protected $dates = ['deleted_at'];
    protected $fillable = ['user_id', 'menu_id', 'menu_order', 'is_favorite'];
 
}
