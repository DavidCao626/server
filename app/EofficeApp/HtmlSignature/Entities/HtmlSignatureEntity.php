<?php
namespace App\EofficeApp\HtmlSignature\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 契约锁签章签署信息日志表
 *
 * @author yml
 *
 * @since  2020-08-11 创建
 */
class HtmlSignatureEntity extends BaseEntity
{
    /**
     * 契约锁签章签署信息日志表
     *
     * @var string
     */
	public $table = 'htmlsignature';
	public $primaryKey = 'id';
	// public $timestamps = false;
}
