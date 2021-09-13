<?php

namespace App\EofficeApp\Charge\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Charge\Entities\ChargeSubjectSettingEntity;

/**
 * 费用设置资源库
 *
 * @author:牛晓克
 *
 * @since：2017-09-29
 *
 */
class ChargeSubjectSettingRepository extends BaseRepository {

    public function __construct(ChargeSubjectSettingEntity $entity) {
        parent::__construct($entity);
    }


    /**
     * 获取费用详细
     *
     * @param  array
     *
     * @return array
     *
     * @author 牛晓克
     *
     */
    public function getDataByWhere($where) {
       return $this->entity->wheres($where)->get();
    }

    public function add($data) {
       return $this->entity->insert($data);
    }

    public function getChargeTypeValue($typeId, $underTaker, $userId, $deptId, $projectId, $settingId = '')
    {
        $query = $this->entity->select('type_value');

        if($underTaker == '1'){
            $query->where('user_id', $userId);
        }elseif($underTaker == '3'){
            $query->where('user_id', "")->where('dept_id', 0);
        }elseif ($underTaker == '4') {
            $query->where('project_id', $projectId);
        }else{
            $query->where('dept_id', $deptId);
        }

        if (!empty($settingId)) {
            $query->where('setting_id', $settingId);
        }

        return $query->where('type_id', $typeId)->first();
    }

    public function getChargeSubjectTotal($where) {
        return $this->entity->wheres($where)->sum("type_value");
    }

    public function updateSubjectData($data, $wheres) {
        return $this->entity->wheres($wheres)->update($data);
    }
}
