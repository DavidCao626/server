<?php

namespace App\EofficeApp\ElectronicSign\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * Class WfQysSealApplyAuthLogEntity
 * @package App\EofficeApp\ElectronicSign\Entities
 */
class QiyuesuoSealApplyCreateDocLogEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'qiyuesuo_seal_apply_create_doc_log';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'createDocLogId';

}
