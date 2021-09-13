<?php
namespace App\EofficeApp\IntegrationCenter\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 腾讯会议，接口主表
 *
 * @author dingp
 *
 * @since 2020-04-12
 */
class ThirdPartyVideoconferenceEntity extends BaseEntity
{
    // 主键id
    public $primaryKey = 'videoconference_id';
    // 表名
    public $table = 'third_party_videoconference';
    public function hasOneTencentCloud()
    {
        return $this->hasOne('App\EofficeApp\IntegrationCenter\Entities\ThirdPartyVideoconferenceTencentCloudEntity', 'config_id', 'config_id');
    }
}