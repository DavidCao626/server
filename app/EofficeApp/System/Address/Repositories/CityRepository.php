<?php

namespace App\EofficeApp\System\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Address\Entities\CityEntity;
use DB;
use Schema;

/**
 * 市Repository类:提供市相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class CityRepository extends BaseRepository
{
    public function __construct(CityEntity $entity)
    {
        parent::__construct($entity);
    }

    public function showCity($id)
    {
        return $this->entity->find($id);
    }

    /**
     * 获取市列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2015-12-22
     */
    public function getCityList($data = array())
    {
        $this->create_city_name();
        $default = [
            'fields' => ["*"],
            'page'   => 0,
            'limit'  => config('eoffice.pagesize'),
            'search' => [],
        ];
        if (isset($data['search']) && !is_array($data['search'])) {
            $data['search'] = json_decode($data['search'], true);
        }
        $param = array_merge($default, array_filter($data));
        if (empty($param['search']['multiSearch'])) {
            $list  = $this->entity->select(['*'])->wheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
            $total = $this->entity->wheres($param['search'])->count();
            // return $this->entity->select(['*'])->wheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
        } else {
            $list  = $this->entity->select(['*'])->multiwheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
            $total = $this->entity->multiwheres($param['search'])->count();
            // return $this->entity->select(['*'])->multiwheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
        }
        $data = [];
        if (!empty($list)) {
            foreach ($list as $key => $item) {
                $item['city_name'] = mulit_trans_dynamic("city.city_name." . $item['city_name']);
                $list[$key]        = $item;
            }
        }
        $data['list']  = $list;
        $data['total'] = $total;
        return $data;
        // return $query->get()->toArray();
    }

    public function batchInsertData($table, $order_by, $chunk, $fun)
    {
        DB::table($table)->orderBy($order_by)->chunk($chunk, $fun);
    }

    public function create_city_name()
    {
        if (Schema::hasTable('city')) {
            if (!Schema::hasColumn('city', 'city_name_py')) {
                Schema::table('city', function ($table) {
                    $table->string('city_name_py', 200);
                });
                if (!Schema::hasColumn('city', 'city_name_zm')) {
                    Schema::table('city', function ($table) {
                        $table->string('city_name_zm', 200);
                    });
                }
                $total = 0;
                $this->batchInsertData('city', 'city_id', 100, function ($lists) use ($total) {
                    $data = array();
                    foreach ($lists as $list) {
                        $city_id              = $list->city_id;
                        $names                = convert_pinyin($list->city_name);
                        $data['city_name_py'] = $names[0];
                        $data['city_name_zm'] = $names[1];
                        DB::table('city')->where(['city_id' => $city_id])->update($data);
                    }
                    //$insert[] = ['city_id'=>9999];
                    //DB::table('city')->insert($insert);
                });
            }
        }
    }
    /**
     * 获取市详情
     *
     * @param  int $cityId 市id
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-22
     */
    public function getCityDetail($cityId)
    {
        return $this->entity->find($cityId);
    }
}
