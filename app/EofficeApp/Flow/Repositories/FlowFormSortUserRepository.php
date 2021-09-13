<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormSortUserEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单分类权限部门表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormSortUserRepository extends BaseRepository
{
    public function __construct(FlowFormSortUserEntity $entity)
    {
        parent::__construct($entity);
    }
    /**
     * 获取管理人
     *
     */
    function getManageUserList($all_quit_user_ids,$flowId) {
        $query = $this->entity
        ->leftJoin('flow_form_sort', 'flow_form_sort.id', '=', 'flow_form_sort_user.type_id');
        if($flowId != 'all') {
            $query = $query->leftJoin('flow_form_type', 'flow_form_type.form_sort', '=', 'flow_form_sort.id')
            ->leftJoin('flow_type', 'flow_form_type.form_id', '=', 'flow_type.form_id');
        }
        $query = $query->select(['flow_form_sort.title','flow_form_sort_user.id','flow_form_sort_user.user_id','flow_form_sort_user.type_id']);
        if($flowId != 'all') {
            $query = $query->whereIn('flow_type.flow_id', $flowId);
        }
        if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
            $chunks = array_chunk($all_quit_user_ids, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $ch) {
                    $query = $query->orWhereIn('flow_form_sort_user.user_id',$ch);
                }
            });
            unset($chunks);
            unset($all_quit_user_ids);
        }else{
            $query = $query->whereIn('flow_form_sort_user.user_id',$all_quit_user_ids);
        }
        return $query->get();
    }

}
