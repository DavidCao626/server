<?php
namespace app\EofficeApp\Empower\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 模块授权实体
 *
 * @author: qishaobo
 *
 * @since：2017-03-17
 *
 */
class ModuleVerifyAuthEntity extends BaseEntity {

     /** @var string $table 定义实体表 */
    public $table = 'module_verify_auth';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'id';

}
