<?php
namespace app\EofficeApp\Attachment\Entities;

use App\EofficeApp\Base\BaseEntity;
class AttachmentShareEntity extends BaseEntity
{
    public $table = 'attachment_share';

    public $primaryKey = 'id';

    protected $fillable = ['attachment_id', 'share_attachment_id', 'share_token','expire_date','user_id'];
    public $timestamps = false;
}




