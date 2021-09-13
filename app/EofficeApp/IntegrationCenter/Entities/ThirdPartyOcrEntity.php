<?php
namespace App\EofficeApp\IntegrationCenter\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 第三方OCR接口集成实体类
 * 
 * @author yml
 * 
 * @since 2015-10-17
 */
class ThirdPartyOcrEntity extends BaseEntity
{
    // 主键id
	public $primaryKey		= 'ocr_id';
	// 表名
	public $table 			= 'third_party_ocr';

    public function hasOneTencentOcr()
    {
        return $this->hasOne('App\EofficeApp\IntegrationCenter\Entities\ThirdPartyOcrTencentEntity', 'config_id', 'config_id');
    }
}
