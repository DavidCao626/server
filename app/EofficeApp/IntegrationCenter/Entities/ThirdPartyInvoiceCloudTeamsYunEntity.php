<?php
namespace App\EofficeApp\IntegrationCenter\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * eteams发票云接口集成配置实体类
 * 
 * @author yml
 * 
 * @since 2020-04-02
 */
class ThirdPartyInvoiceCloudTeamsYunEntity extends BaseEntity
{
    // 主键id
	public $primaryKey		= 'config_id';
	// 表名
	public $table 			= 'third_party_invoice_cloud_teamsyun';

}
