<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * @会议室实体
 *
 * @author 李志军
 */
class MeetingExternalUserTypeInfoEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'meeting_external_user_type';

    public $primaryKey	= 'external_user_type_id';

}
