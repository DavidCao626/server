<?php
namespace app\EofficeApp\Empower\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 系统版本实体
 *
 * @author: qishaobo
 *
 * @since：2017-03-17
 *
 */
class VersionEntity extends BaseEntity {

     /** @var string $table 定义实体表 */
    public $table = 'version';

    public $timestamps = false;
}
