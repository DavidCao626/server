<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormTemplateRuleUserEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormTemplateRuleUserRepository extends BaseRepository
{
    public function __construct(FlowFormTemplateRuleUserEntity $entity)
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
     * 获取管理人
     *
     */
    function getRunManageUserList($all_quit_user_ids,$nodeId) {
        $query = $this->entity
        ->leftJoin('flow_form_template_rule', 'flow_form_template_rule.rule_id', '=', 'flow_form_template_rule_user.rule_id')
        ->select(['flow_form_template_rule_user.auto_id','flow_form_template_rule_user.user_id','flow_form_template_rule.node_id','flow_form_template_rule.flow_id'])
        ->where('flow_form_template_rule.template_type','run');
        if (is_array($nodeId) && count($nodeId) > 1000) {
            $chunks = array_chunk($nodeId, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $ch) {
                    $query = $query->orWhereIn('flow_form_template_rule.node_id',$ch);
                }
            });
            unset($chunks);
            unset($nodeId);
        }else{
            $query = $query->whereIn('flow_form_template_rule.node_id',$nodeId);
        }
        if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
            $_chunks = array_chunk($all_quit_user_ids, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $ch) {
                    $query = $query->orWhereIn('flow_form_template_rule_user.user_id',$ch);
                }
            });
            unset($_chunks);
            unset($all_quit_user_ids);
        }else{
            $query = $query->whereIn('flow_form_template_rule_user.user_id',$all_quit_user_ids);
        }
        return $query->get();
    }
    /**
     * 获取管理人
     *
     */
    function getfilingManageUserList($all_quit_user_ids,$flowId) {
        $query = $this->entity
        ->leftJoin('flow_form_template_rule', 'flow_form_template_rule.rule_id', '=', 'flow_form_template_rule_user.rule_id')
        ->select(['flow_form_template_rule_user.auto_id','flow_form_template_rule_user.user_id','flow_form_template_rule.node_id','flow_form_template_rule.flow_id'])
        ->where('flow_form_template_rule.template_type','filing');
        if($flowId && $flowId != 'all') {
            if (is_array($flowId) && count($flowId) > 1000) {
                $chunks = array_chunk($flowId, 1000);
                $query = $query->where(function ($query) use ($chunks) {
                    foreach ($chunks as $ch) {
                        $query = $query->orWhereIn('flow_form_template_rule.flow_id',$ch);
                    }
                });
                unset($chunks);
                unset($flowId);
            }else{
                $query = $query->whereIn('flow_form_template_rule.flow_id',$flowId);
            }
        }
        if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
            $_chunks = array_chunk($all_quit_user_ids, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $ch) {
                    $query = $query->orWhereIn('flow_form_template_rule_user.user_id',$ch);
                }
            });
            unset($_chunks);
            unset($all_quit_user_ids);
        }else{
            $query = $query->whereIn('flow_form_template_rule_user.user_id',$all_quit_user_ids);
        }
        return $query->get();
    }
    /**
     * 获取管理人
     *
     */
    function getPrintManageUserList($all_quit_user_ids,$flowId) {
        $query = $this->entity
        ->leftJoin('flow_form_template_rule', 'flow_form_template_rule.rule_id', '=', 'flow_form_template_rule_user.rule_id')
        ->select(['flow_form_template_rule_user.auto_id','flow_form_template_rule_user.user_id','flow_form_template_rule.node_id','flow_form_template_rule.flow_id'])
        ->where('flow_form_template_rule.template_type','print');
        if($flowId && $flowId != 'all') {
            if (is_array($flowId) && count($flowId) > 1000) {
                $chunks = array_chunk($flowId, 1000);
                $query = $query->where(function ($query) use ($chunks) {
                    foreach ($chunks as $ch) {
                        $query = $query->orWhereIn('flow_form_template_rule.flow_id',$ch);
                    }
                });
                unset($chunks);
                unset($flowId);
            }else{
                $query = $query->whereIn('flow_form_template_rule.flow_id',$flowId);
            }
        }
        if (is_array($all_quit_user_ids) && count($all_quit_user_ids) > 1000) {
            $_chunks = array_chunk($all_quit_user_ids, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $ch) {
                    $query = $query->orWhereIn('flow_form_template_rule_user.user_id',$ch);
                }
            });
            unset($_chunks);
            unset($all_quit_user_ids);
        }else{
            $query = $query->whereIn('flow_form_template_rule_user.user_id',$all_quit_user_ids);
        }
        return $query->get();
    }
}
