<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailOutboxEntity;

/**
 * 发件箱Repository类:提供发件箱相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailOutboxRepository extends BaseRepository
{
    public function __construct(WebmailOutboxEntity $entity)
    {
        parent::__construct($entity);

    }

    /**
     * 获取发件箱列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getOutboxs(array $param = [])
    {
        //手机端获取邮箱带搜索
        if (isset($param['type']) && $param['type'] == 'all') {
            return $this->getSendOutboxs($param);
        }
        $default = [
            'fields'   => ['*'],
            'search'   => [],
            'page'     => 1,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['outbox_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
        ->select($param['fields'])
        ->with(['creatorName' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }]);
        if (isset($param['addFields'])) {
            $query = $query->with(['hasOneMailCount' => function ($query) {
                $query->select('outbox_id')
                ->selectRaw("COUNT(folder='draft' OR NULL) as draft")
                ->selectRaw("COUNT(folder='sent' OR NULL) as sent")
                ->selectRaw("COUNT((folder='inbox' AND is_read=0) OR NULL) as unReadInbox")
                ->selectRaw("COUNT((folder='trash') OR NULL) as unReadTrash")
                ->selectRaw("COUNT((is_star='1') OR NULL) as unReadStar")
                ->groupBy('outbox_id');
            }]);
        }

        $query = $this->getOutboxsParseWhere($query, $param['search']);
        if($param['search'] && !isset($param['search']['outbox_id'])){
            $query->orWhere('is_public',1);//获取公共邮箱
        }
        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }
    /**
     * 手机端 搜索发件箱处理
     * @param [type] $param
     *
     * @return void
     * @author yuanmenglin
     * @since 
     */
    public function getSendOutboxs($param)
    {
        $account = isset($param['search']) && isset($param['search']['account']) ? $param['search']['account'] : [];
        $outbox_creator = isset($param['search']) && isset($param['search']['outbox_creator']) ? $param['search']['outbox_creator'] : [];
        unset($param['search']['outbox_creator']);
        $search = [
            'multiSearch' => [
                'outbox_creator' => $outbox_creator,
                'is_public' => [1],
                '__relation__' => 'or'
            ],
        ];
        if ($account) $search['account'] = $account;
        $query = $this->entity;
        $query = $this->entity->scopeMultiWheres($query, $search);
        return $query->get()->toArray();
    }

    /**
     * 获取发件箱数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getNum($param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return  $this->getOutboxsParseWhere($this->entity, $where)->count();
    }

    /**
     * 获取发件箱条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getOutboxsParseWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }


    /**
     * 定时收取获取全部邮件，剔除已删除，离职人员
     *
     * @param  array $where  定时收取
     *
     * @return array 全部邮件
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getAllOutbox(){
        $params['search'] = [
            'user_status'=>[[0,2],'not_in']
        ];
        $query = $this->entity->select('*');
        $query = $query->whereHas('userStatus', function($query) use ($params)
        {
            $query->wheres($params['search']);
        });
        return $query->get();
    }
}