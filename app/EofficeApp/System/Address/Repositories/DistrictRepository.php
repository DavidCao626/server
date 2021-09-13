<?php

namespace App\EofficeApp\System\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Address\Entities\DistrictEntity;

/**
 * 省Repository类:提供省相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DistrictRepository extends BaseRepository
{
    public function __construct(DistrictEntity $entity)
    {
        parent::__construct($entity);
    }

    public function showDistrict($id)
    {
        return $this->entity->find($id);
    }

    public function getDistrictList(array $param = [])
    {
        $default = [
            'fields' => ["*"],
            'page'   => 0,
            'limit'  => config('eoffice.pagesize'),
            'search' => [],
        ];

        if (isset($param['search']) && !is_array($param['search'])) {
            $param['search'] = json_decode($param['search'], true);
        }
        $param = array_merge($default, array_filter($param));
        if (empty($param['search']['multiSearch'])) {
            $list  = $this->entity->select(['*'])->wheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
            $total = $this->entity->wheres($param['search'])->count();
        } else {
            $list  = $this->entity->select(['*'])->multiwheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
            $total = $this->entity->multiwheres($param['search'])->count();
        }
        if (!empty($list)) {
            foreach ($list as $key => $item) {
                $item['district_name'] = mulit_trans_dynamic("district.district_name." . $item['district_name']);
                $list[$key]            = $item;
            }
        }
        $data          = [];
        $data['list']  = $list;
        $data['total'] = $total;
        return $data;
    }

}
