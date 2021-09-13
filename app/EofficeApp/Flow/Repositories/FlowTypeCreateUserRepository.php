<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowTypeCreateUserEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowTypeCreateUserRepository extends BaseRepository
{
    public function __construct(FlowTypeCreateUserEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getList($flowId)
    {
        return $this->entity
                    ->where("flow_id",$flowId)
                    ->get();
    }
    /**
     * 获取办理人
     *
     */
    function getfreeCreateList($flowId,$all_quit_user_ids) {
        $query = $this->entity;
        if($flowId == 'all') {
            if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
                $_chunks = array_chunk($all_quit_user_ids, 1000);
                $query = $query->where(function ($query) use ($_chunks) {
                    foreach ($_chunks as $_ch) {
                        $query = $query->orWhereIn('user_id',$_ch);
                    }
                });
                unset($_chunks);
                unset($all_quit_user_ids);
            }else{
                $query = $query->whereIn('user_id',$all_quit_user_ids);
            }
            return $query->get();
        }
        if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
            $_chunks = array_chunk($all_quit_user_ids, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $_ch) {
                    $query = $query->orWhereIn('user_id',$_ch);
                }
            });
            unset($_chunks);
            unset($all_quit_user_ids);
        }else{
            $query = $query->whereIn('user_id',$all_quit_user_ids);
        }
        if (is_array($flowId) && count($flowId) > 1000) {
            $chunks = array_chunk($flowId, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $_ch) {
                    $query = $query->orWhereIn('flow_id',$_ch);
                }
            });
            unset($chunks);
            unset($flowId);
        }else{
            $query = $query->whereIn('flow_id',$flowId);
        }
        return $query->get();
    }
}
