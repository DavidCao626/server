<?php

namespace App\EofficeApp\Birthday\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Birthday\Entities\BirthdayEntity;

/**
 * 访问控制 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class BirthdayRepository extends BaseRepository {

    public function __construct(BirthdayEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取访问控制列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getBirthdayList($param) {
        $default = [
            'fields' => ['birthday.*', 'user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['birthday_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        $param['fields'] = ['birthday.*', 'user_name'];
        return $this->entity
                        ->select($param['fields'])->leftJoin('user', function($join) {
                            $join->on("user.user_id", '=', 'birthday.birthday_underwrite');
                        })->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->forPage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    /**
     * 获取具体的控制规则
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoBirthday($id) {
        $result = $this->entity->where('birthday_id', $id)->get()->toArray();
        return $result;
    }

    public function selectBrithday($id) {

        $this->entity->where('selected',1)->update(["selected" => 0]);
        return $this->entity->where('birthday_id', $id)->update(["selected" => 1]);
    }
    public function cancelSelectBrithday($id) {
        return $this->entity->where('birthday_id', $id)->update(["selected" => 0]);
    }
    public function updateSelectBrithdayRemind($smsType) {
        return $this->entity->where('selected', 1)->update(["sms_select" => $smsType]);
    }

}
