<?php
namespace app\EofficeApp\Attachment\Entities;

use App\EofficeApp\Base\BaseEntity;
class AttachmentRelSearchEntity extends BaseEntity
{
    public $table = 'attachment_rel_search';
    public $primaryKey = 'rel_id';
    protected $fillable = ['rel_id','attachment_name', 'attachment_type', 'attachment_mark','rel_table_code','creator'];
    public $timestamps = false;
}




