<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Attendance\Entities\AttendanceMachineCaseEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 班次排班映射资源库类
 *
 * @author 王炜锋
 *
 * @since 2019-09-12
 */
class AttendanceMachineCaseRepository extends BaseRepository
{
    public function __construct(AttendanceMachineCaseEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     *
     * 获取考勤机配置案例列表接口
     */
    public function getAttendanceCaseList($where, $transArray = true, $fields = ['*'])
    {
        $model = $this->entity;
        if (!empty($fields) && $fields != ['*']) {
            // 字符串拆数组并去重
            $fields = explode(',', $fields);
            $fields = array_unique($fields);
            $model  = $model->select($fields)->distinct();
        }
        if (!empty($where)) {
            $model = $model->where($where);
        }
        if ($transArray) {
            return $model->get()->toArray();
        }
        return $model->get();
    }
}
