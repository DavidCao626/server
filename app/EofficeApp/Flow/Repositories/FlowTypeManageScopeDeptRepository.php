<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowTypeManageScopeDeptEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 流程分表知识库 监控范围指定部门表
 *
 * @author 缪晨晨
 *
 * @since  2018-04-16 创建
 */
class FlowTypeManageScopeDeptRepository extends BaseRepository
{
    public function __construct(FlowTypeManageScopeDeptEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $where [description]
     *
     * @return [type]          [description]
     */
    function getList($where)
    {
        return $this->entity
                    ->wheres($where)
                    ->get();
    }
    /**
     * 获取办理人
     *
     */
    function getManageUserList($flowId,$all_quit_user_ids) {
        if($flowId == 'all') {
            return $this->entity->whereIn('user_id',$all_quit_user_ids)->get();
        }
        return $this->entity->whereIn('flow_id',$flowId)->whereIn('user_id',$all_quit_user_ids)->get();
    }
}
