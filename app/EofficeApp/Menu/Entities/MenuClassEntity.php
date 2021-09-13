<?php
namespace app\EofficeApp\Menu\Entities;

use App\EofficeApp\Base\BaseEntity;
//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 系统功能实体
 *
 * @author:白锦
 *
 * @since：2019-01-08
 *
 */
class MenuClassEntity extends BaseEntity {
   // use SoftDeletes;
    /** @var string $table 定义实体表 */
    public $table = 'menu_class';
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'id';

    public function menu()
    {
    	return  $this->HasMany('App\EofficeApp\Menu\Entities\MenuEntity','menu_class','id');
    }
   
}
