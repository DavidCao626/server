<?php

namespace App\EofficeApp\FrontComponent\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\FrontComponent\Entities\ComponentSearchEntity;

/**
 * 内部消息 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ComponentSearchRepository extends BaseRepository {

    public function __construct(ComponentSearchEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getComponentSearchList($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['created_at' => 'desc'],
            'page_state'=>''
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity->select($param['fields'])
                        ->where('user_id', $param['user_id'])
                        ->where('page_state', $param['page_state'])
                        ->wheres($param['search'])
                        ->orders($param['order_by']);
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        return $query->get()->toArray();
    }

    public function getComponentSearchTotal($param) {
        $default = [
            'search' => [],
            'page_state'=>''
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity
                        ->wheres($param['search'])
                        ->where('user_id', $param['user_id'])
                        ->where('page_state', $param['page_state'])
                        ->count();
    }

}
