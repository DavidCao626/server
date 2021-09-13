<?php
namespace App\EofficeApp\HtmlSignature\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 金格签章keysn表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class SignaturePlugInQiyuesuoSettingEntity extends BaseEntity
{
    /**
     * 金格签章keysn表
     *
     * @var string
     */
	public $table = 'signature_plug_in_qiyuesuo_setting';
	public $primaryKey = 'setting_id';
	// public $timestamps = false;
}
