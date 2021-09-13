<?php

namespace App\EofficeApp\Email\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Email\Entities\EmailEntity;
use App\EofficeApp\Email\Entities\EmailOperateEntity;
use Illuminate\Database\Eloquent\Builder;

class EmailOperatesRepository extends BaseRepository
{

    public function __construct(EmailEntity $entity)
    {
        parent::__construct($entity);
    }

    public static function buildQuery($params, $query = null): Builder
    {
        $query = $query ?: EmailOperateEntity::query();

        return $query;
    }

    public static function createOperateData($operateType, $originEmailId, $newEmailId, $userId)
    {
        return (new EmailOperateEntity())->fill([
            'origin_email_id' => $originEmailId,
            'email_id' => $newEmailId,
            'user_id' => $userId,
            'type' => $operateType,
        ])->save();
    }

    public static function buildUserEmailQuery($userId, $emailId, $query = null)
    {
        $query = self::buildQuery($query)
            ->where('user_id', $userId);
        $whereKey = is_array($emailId) ? 'whereIn' : 'where';
        $query->$whereKey('origin_email_id', $emailId);
        $query->join('email', 'email.email_id', '=', 'email_operates.email_id')
            ->where('email.send_flag', 1);

        return $query;
    }
}
