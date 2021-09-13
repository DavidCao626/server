<?php
namespace App\EofficeApp\YonyouVoucher\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * U8集成
 *
 * @author yml
 *
 * @since  2019-04-17 创建
 */
class VoucherIntergrationU8CourseUploadConfigEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'voucher_intergration_u8_course_upload_config';

    public $primaryKey = 'upload_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];
}
