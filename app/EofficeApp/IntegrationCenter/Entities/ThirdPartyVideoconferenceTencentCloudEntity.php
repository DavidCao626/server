<?php
namespace App\EofficeApp\IntegrationCenter\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 腾讯会议，配置详情表
 *
 * @author dingp
 *
 * @since 2020-04-12
 */
class ThirdPartyVideoconferenceTencentCloudEntity extends BaseEntity
{
    // 主键id
    public $primaryKey = 'config_id';
    // 表名
    public $table = 'third_party_videoconference_tencent_cloud';
    public $timestamps = false;
}