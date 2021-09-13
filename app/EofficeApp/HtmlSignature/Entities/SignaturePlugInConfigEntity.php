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
class SignaturePlugInConfigEntity extends BaseEntity
{
    /**
     * 金格签章keysn表
     *
     * @var string
     */
	public $table = 'signature_plug_in_config';
	public $primaryKey = 'config_id';
	public $timestamps = false;
}
