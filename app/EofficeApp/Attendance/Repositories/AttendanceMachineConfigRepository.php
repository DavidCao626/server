<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Attendance\Entities\AttendanceMachineConfigEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 考勤机配置数据库
 *
 * @author 王炜锋
 *
 * @since 2019-11-1
 */
class AttendanceMachineConfigRepository extends BaseRepository
{
    public function __construct(AttendanceMachineConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    // 判断该id 的考勤机是否存在
    public function checkId($id)
    {
        if (!empty($id) && is_numeric($id)) {
            return $this->entity->find($id)->toArray();
        }
        return false;
    }
}
