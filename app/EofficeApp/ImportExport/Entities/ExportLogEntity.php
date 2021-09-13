<?php
namespace app\EofficeApp\ImportExport\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 导出日志
 *
 * @author: 齐少博
 *
 * @since：2017-04-17
 *
 */
class ExportLogEntity extends BaseEntity {

     /** @var string $table 定义实体表 */
    public $table = 'export_log';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'export_id';

}
