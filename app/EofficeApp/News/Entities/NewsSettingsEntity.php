<?php

namespace App\EofficeApp\News\Entities;

use App\EofficeApp\Base\BaseEntity;

class NewsSettingsEntity extends BaseEntity
{
    public $table = 'news_settings';

    protected $primaryKey = 'setting_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['setting_key', 'setting_value'];


}
