<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectDiscussEntity;

/**
 * 项目讨论 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectDiscussRepository extends BaseRepository {

    public function __construct(ProjectDiscussEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取项目讨论列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getProjectDiscussList($param) {
        $default = [
            'fields' => ['project_discuss.*', 'user.user_name as user_name', 'avatar_thumb'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['discuss_time' => 'desc']
        ];

        $param = array_merge($default, array_filter($param));
        return $this->entity->select($param['fields'])->leftJoin('user', function($join) {
                            $join->on("project_discuss.discuss_person", '=', 'user.user_id');
                        })->leftJoin('user_info', function($join) {
                            $join->on("user_info.user_id", '=', 'user.user_id');
                        })->wheres($param['search'])
                        ->where("discuss_project", $param['discuss_project'])
                        ->where("discuss_replyid", "0")
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    public function getProjectDiscussTotal($param) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->where("discuss_project", $param['discuss_project'])->where("discuss_replyid", "0")->wheres($param['search'])->count();
    }

    /**
     * 获取
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoProjectDiscussbyWhere($where) {

        return $this->entity->select(['project_discuss.*', 'user.user_name as user_name'])->leftJoin('user', function($join) {
                    $join->on("project_discuss.discuss_person", '=', 'user.user_id');
                })->wheres($where)->get()->toArray();

        // $result = $this->entity->wheres($where)->get()->toArray();
        //  return $result;
    }

    public function getMaxOrder() {
        return $this->entity->orderBy("discuss_order", 'desc')->get()->toArray();
    }

    public function updateDiscuss($data) {
        return $this->entity->where("discuss_person", "!=", $data["user_id"])
                        ->where("discuss_readtime", "=", "0000-00-00")
                        ->update([
                            'discuss_readtime' => date("Y-m_d H:i:s", time())
        ]);
    }

    public function getInfobyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }

    public function projectDisscussCount($manager_id) {
        return $this->entity->where("discuss_project", $manager_id)->where("discuss_replyid","0")->count();
    }

}
