<?php

namespace App\EofficeApp\System\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Address\Entities\ProvinceEntity;
use DB;
use Schema;

/**
 * 省Repository类:提供省相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class ProvinceRepository extends BaseRepository
{
    public function __construct(ProvinceEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取省列表
     *
     * @param  array $param 查询条件
     *
     * @since  2018-01-30
     */
    public function getProvinceList($data = array())
    {
        $this->create_province_name();
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
        //var_dump($param);die;
        if (empty($param['search']['multiSearch'])) {
            $list = $this->entity->select(['*'])->wheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
            // return $this->entity->select(['*'])->wheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
        } else {
            $list = $this->entity->select(['*'])->multiwheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
            // return $this->entity->select(['*'])->multiwheres($param['search'])->parsePage($param['page'], $param['limit'])->get()->toArray();
        }
        $total = $this->entity->count();
        $data  = [];
        if (!empty($list)) {
            foreach ($list as $key => $item) {
                $item['province_name'] = mulit_trans_dynamic("province.province_name." . $item['province_name']);
                $list[$key]            = $item;
            }
        }
        $data['list']  = $list;
        $data['total'] = $total;
        return $data;
    }

    /**
     * 获取省详情
     *
     * @param  int $provinceId 省id
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-22
     */
    public function getProvinceDetail($provinceId)
    {
        return $this->entity->find($provinceId);
    }

    public function create_province_name()
    {
        if (Schema::hasTable('province')) {
            if (!Schema::hasColumn('province', 'province_name_py')) {
                Schema::table('province', function ($table) {
                    $table->string('province_name_py', 200);
                });
                if (!Schema::hasColumn('province', 'province_name_zm')) {
                    Schema::table('province', function ($table) {
                        $table->string('province_name_zm', 200);
                    });
                }
                $total = 0;
                $this->batchInsertData('province', 'province_id', 100, function ($lists) use ($total) {
                    $data = array();
                    foreach ($lists as $list) {
                        $province_id              = $list->province_id;
                        $names                    = convert_pinyin($list->province_name);
                        $data['province_name_py'] = $names[0];
                        $data['province_name_zm'] = $names[1];
                        DB::table('province')->where(['province_id' => $province_id])->update($data);
                    }
                    //$insert[] = ['city_id'=>9999];
                    //DB::table('city')->insert($insert);
                });

            }
        }
    }

    public function batchInsertData($table, $order_by, $chunk, $fun)
    {
        DB::table($table)->orderBy($order_by)->chunk($chunk, $fun);
    }

}
