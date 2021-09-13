<?php


namespace App\EofficeApp\Elastic\Entities;


use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\Elastic\Configurations\ElasticTables;

class ElasticStashIndexEntity  extends BaseEntity
{
    public $table = ElasticTables::ELASTIC_STASH_INDEX_TABLE;
}