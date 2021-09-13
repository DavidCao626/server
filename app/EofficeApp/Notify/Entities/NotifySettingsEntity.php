<?php

namespace App\EofficeApp\Notify\Entities;

use App\EofficeApp\Base\BaseEntity;

class NotifySettingsEntity extends BaseEntity
{
    public $table = 'notify_settings';

    protected $primaryKey = 'setting_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['setting_key', 'setting_value'];


}
