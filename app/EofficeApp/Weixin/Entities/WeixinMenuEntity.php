<?php

namespace app\EofficeApp\Weixin\Entities;

use App\EofficeApp\Base\BaseEntity;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 微信菜单实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class WeixinMenuEntity extends BaseEntity {

//    use SoftDeletes;
    /** @var string $table 定义实体表 */
    public $table = 'weixin_menu';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'id';

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

}
