<?php
namespace App\EofficeApp\IntegrationCenter\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 发票云接口集成配置实体类
 * 
 * @author yml
 * 
 * @since 2020-04-02
 */
class ThirdPartyInvoiceCloudEntity extends BaseEntity
{
    // 主键id
	public $primaryKey		= 'invoice_cloud_id';
	// 表名
	public $table 			= 'third_party_invoice_cloud';

    public function teamsYun()
    {
        return $this->hasOne('App\EofficeApp\IntegrationCenter\Entities\ThirdPartyInvoiceCloudTeamsYunEntity', 'config_id', 'config_id');
    }

}
