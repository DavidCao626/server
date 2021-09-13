<?php
namespace App\EofficeApp\IntegrationCenter\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 腾讯云OCR接口集成配置实体类
 * 
 * @author yml
 * 
 * @since 2020-04-02
 */
class ThirdPartyOcrTencentEntity extends BaseEntity
{
    // 主键id
	public $primaryKey		= 'config_id';
	// 表名
	public $table 			= 'third_party_ocr_tencent';

	public $timestamps = false;
}
