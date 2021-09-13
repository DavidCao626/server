<?php 
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议室实体
 * 
 * @author 李志军
 */
class MeetingSignWifiEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'meeting_wifi';
    protected $fillable = ['meeting_wifi_id', 'meeting_wifi_name', 'meeting_wifi_mac'];
    public $primaryKey		= 'meeting_wifi_id';
	
}
