<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatAppEntity;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class WorkWechatAppRepository extends BaseRepository
{

    public function __construct(WorkWechatAppEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateWechat()
    {
        return $this->entity->truncate();
    }

    public function getAppById($id)
    {
        $result = $this->entity->where("agentid", $id)->first();
        return $result;
    }

    public function wechatAppGet($param)
    {
        $result = $this->entity->where('agent_type', $param['agent_type'])->forPage($param['page'], $param['limit'])->get()->map(function ($v) {
            if (isset($v->remind_menu)) {
                $v->remind_menu = json_decode($v->remind_menu, true);
            }
            return $v;
        });

        return $result;
    }

    public function wechatAppCount($param)
    {
        $result = $this->entity->where('agent_type', $param['agent_type'])->count();
        return $result;
    }

    public function wechatAppDelete($id)
    {
        $result = $this->entity->where('agentid', $id)->delete();
        return $result;
    }

    public function getWorkWechatApp($param)
    {
        $result = $this->entity->where('agent_type', $param['agent_type'])->get()->map(function ($v) {
            if (isset($v->remind_menu)) {
                $v->remind_menu = json_decode($v->remind_menu, true);
            }
            return $v;
        });
        return $result;
    }
}
