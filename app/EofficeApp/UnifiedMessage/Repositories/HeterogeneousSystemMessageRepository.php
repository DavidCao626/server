<?php

namespace App\EofficeApp\UnifiedMessage\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\UnifiedMessage\Entities\HeterogeneousSystemMessageEntities;

/**
 * Class WechatReplyRepository
 * @package App\EofficeApp\Weixin\Repositories
 */
class HeterogeneousSystemMessageRepository extends BaseRepository
{

    public function __construct(HeterogeneousSystemMessageEntities $entity)
    {
        parent::__construct($entity);
    }

    public function getHeterogeneousSystemMessageTotal($params,$userAndSystem = [])
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($params));
        $query = $this->entity
            ->wheres($param['search']);
        if (!empty($userAndSystem)) {
            $query = $query->where(function ($query) use ($userAndSystem) {
                foreach ($userAndSystem as $value) {
                    $query = $query->orWhere(function ($query) use ($value) {
                        $query->where(['heterogeneous_system_id' => $value['system_id'], 'recipient' => $value['user']]);
                    });
                }
            });
        }
        return $query->count();
    }

    public function getHeterogeneousSystemMessageList($params, $userAndSystem = [])
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['heterogeneous_system_message.created_at' => 'desc'],
        ];
        $param = array_merge($default, array_filter($params));
        $query = $this->entity
            ->select($param['fields'])
            ->with([
                "systemHasOne" => function ($query) {
                    $query->select(["id", 'system_name', 'system_code', 'pc_domain', 'app_domain']);
                }
            ])
            ->with([
                "messageTypeHasOne" => function ($query) {
                    $query->select(["id", 'message_type']);
                }
            ])
            ->wheres($param['search']);
//        $userAndSystem = [
//            ['system_id' => '1', 'user' => 'a'],
//            ['system_id' => '2', 'user' => 'admin'],
//            ['system_id' => '1', 'user' => 'c'],
//        ];
        if (!empty($userAndSystem)) {
            $query = $query->where(function ($query) use ($userAndSystem) {
                foreach ($userAndSystem as $value) {
                    $query = $query->orWhere(function ($query) use ($value) {
                        $query->where(['heterogeneous_system_id' => $value['system_id'], 'recipient' => $value['user']]);
                    });
                }
            });
        }

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    //获取内容
    public function getData($where)
    {
        $result = $this->entity->where($where)->first();
        return $result;
    }

    //清空表
    public function clearWechatReply()
    {
        $this->entity->truncate();
        return true;
    }

    public function deleteMessageByWhere($where)
    {
        $result = $this->entity->where($where)->delete();
        return $result;
    }
}
