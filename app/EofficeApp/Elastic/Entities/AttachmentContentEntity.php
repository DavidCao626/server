<?php


namespace App\EofficeApp\Elastic\Entities;


use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\Elastic\Configurations\ElasticTables;

class AttachmentContentEntity extends BaseEntity
{
    public $table = ElasticTables::ATTACHMENT_CONTENT_TABLE;
}