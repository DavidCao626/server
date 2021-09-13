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
class GoldgridSignatureKeysnEntity extends BaseEntity
{
    /**
     * 金格签章keysn表
     *
     * @var string
     */
	public $table = 'goldgrid_signature_keysn';
	public $primaryKey = 'auto_id';
	// public $timestamps = false;
}
