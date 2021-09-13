<?php
namespace App\EofficeApp\Kingdee\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * K3日志表
 *
 * @author wwf
 *
 * @since  2020-05-19 创建
 */
class KingdeeK3LogEntity extends BaseEntity
{
    use SoftDeletes;
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'kingdee_k3_log';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    public function k3table()
    {
        return $this->hasOne('App\EofficeApp\Kingdee\Entities\KingdeeK3TableEntity', 'kingdee_table_id', 'table_id');
    }

    public function flow()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'flow_id');
    }
}
