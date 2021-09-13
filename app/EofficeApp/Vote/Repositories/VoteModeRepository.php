<?php

namespace App\EofficeApp\Vote\Repositories;

use App\EofficeApp\Vote\Entities\VoteModeEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 调查表知识库
 *
 * @author 史瑶
 *
 * @since  2017-06-21 创建
 */
class VoteModeRepository extends BaseRepository {

    public function __construct(VoteModeEntity $entity) {
        parent::__construct($entity);
    }
    /** @var int 默认列表条数 */
    private $limit      = 10;

    /** @var int 默认列表页 */
    private $page       = 0;
    /**
     * 获取调查表管理列表数量
     *
     */
    public function getModeTotal($param = [])
    {
        $default = [
            'page'      => 0,
            'order_by'  => ['created_at' => 'desc'],
            'limit'     => 10,
            'fields'    => ['*']
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select($param['fields']);

        if(isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->count();
    }

    /**
     * 获取调查表管理列表
     *
     */
    public function getModeList($param)
    {
        $default = [
            'page'      => 0,
            'order_by'  => ['mode_type'=>'desc'],
            'limit'     => 10,
            'fields'    => ['*']
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select(['mode_id','mode_title','mode_type']);

        if(isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        $query = $query->orders($param['order_by']);
        if($param['page'] != 0) {
            $query = $query->forPage($param['page'], $param['limit']);
        }

        return $query->get()->toArray();
    }
}
