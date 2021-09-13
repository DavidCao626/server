<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectTeamEntity;

/**
 * 项目团队 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectTeamRepository extends BaseRepository {

    public function __construct(ProjectTeamEntity $entity) {
        parent::__construct($entity);
    }





    /**
     * 获取项目成员
     *
     * @param array
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoProjectTeambyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }

    public function getOneProjectTeam($where) {
        return $this->entity->select(['project_team.team_person','project_team.team_project','project_team.team_id', 'project_manager.*'])
                ->leftJoin('project_manager', function($join) {
                    $join->on("project_manager.manager_id", '=', 'project_team.team_project');
                })->wheres($where)->get()->toArray();
    }

    //获取改用户参与的项目
    public function getProjectsByUser($user_id) {
        return $this->entity->WhereRaw('(find_in_set(\'' . $user_id . '\',team_person))')->select('team_project', 'team_id')->get()->toArray();
    }

}
