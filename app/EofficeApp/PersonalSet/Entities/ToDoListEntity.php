<?php
namespace App\EofficeApp\PersonalSet\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class ToDoListEntity extends BaseEntity
{

    use SoftDeletes;

    public $primaryKey = 'item_id';

    public $table = 'to_do_list';
}
