<?php
namespace app\EofficeApp\Charge\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 费用清单实体
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ChargeListEntity extends BaseEntity
{

    use SoftDeletes;
    /** @var string $table 定义实体表 */
    public $table = 'charge_list';
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'charge_list_id';

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

}
