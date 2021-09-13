<?php
namespace app\EofficeApp\Attachment\Entities;

use App\EofficeApp\Base\BaseEntity;
class AttachmentRelEntity extends BaseEntity 
{
    public $table = 'attachment_rel';

    public $primaryKey = 'rel_id';

    protected $fillable = ['attachment_id', 'year', 'month'];
    public $timestamps = false;
}




