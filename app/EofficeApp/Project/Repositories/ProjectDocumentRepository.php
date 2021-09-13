<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectDocumentEntity;
use DB;
/**
 * 项目文档 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectDocumentRepository extends BaseRepository {

    public function __construct(ProjectDocumentEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取项目文档列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getProjectDocmentList($param) {
        $default = [
            'fields' => ['project_document.*', 'user.user_name as user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['doc_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select($param['fields'])->leftJoin('user', function($join) {
            $join->on("project_document.doc_creater", '=', 'user.user_id');
        });
        if (isset($param['doc_creattime']) && !empty($param['doc_creattime'])) {

            $dateTime = json_decode($param['doc_creattime'], true);

            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('doc_creattime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }


        return $query->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    public function getProjectDocmentTotal($param) {
        $default = [

            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->leftJoin('user', function($join) {
            $join->on("project_document.doc_creater", '=', 'user.user_id');
        });
        if (isset($param['doc_creattime']) && !empty($param['doc_creattime'])) {
            $dateTime = json_decode($param['doc_creattime'], true);
            if ($dateTime['startDate'] && $dateTime['endDate']) {
                $query = $query->whereBetween('doc_creattime', [$dateTime['startDate'], $dateTime['endDate']]);
            }
        }


        return $query->wheres($param['search'])
                        ->count();
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
    public function infoProjectDocumentbyWhere($where) {


        $result = $this->entity->select(['project_document.*', 'user.user_name as user_name', 'project_manager.manager_name as manger_name'])
                        ->leftJoin('user', function($join) {
                            $join->on("project_document.doc_creater", '=', 'user.user_id');
                        })->leftJoin('project_manager', function($join) {
                    $join->on("project_document.doc_project", '=', 'project_manager.manager_id');
                })->wheres($where)->get()->toArray();

        return $result;
    }

    public function projectDocumentCount($manager_id) {
        return $this->entity->where("doc_project", $manager_id)->count();
    }

    /**
     * 获取全部项目下属文档个数，按项目id分组
     * @param $managerIds [项目id]
     * @return [type] [description]
     */
    public function getDocumentCountGroupByProject(array $managerIds = null) {
        $query = $this->entity;
        $query = $query->select(['doc_project',DB::raw('COUNT(doc_id) as doc_count')])
        ->groupBy('doc_project');

        if (!is_null($managerIds)) {
            $query->whereIn('doc_project', $managerIds);
        }

        return $query->get()->toArray();
    }

    public function getInfobyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }

}
