<?php


namespace App\EofficeApp\Elastic\Entities;


use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\Elastic\Configurations\ElasticTables;

/**
 * 搜索引擎配置表。
 *
 */
class ElasticSearchConfigEntity extends BaseEntity
{
    public $primaryKey = 'id';

    public $table = ElasticTables::ELASTIC_CONFIG_TABLE;

    public $fillable = ['key', 'value', 'type', 'default', 'enable'];

    /**
     *
     * @var bool
     */
    public $timestamps = false;
}