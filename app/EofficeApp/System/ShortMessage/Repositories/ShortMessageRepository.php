<?php

namespace App\EofficeApp\System\ShortMessage\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\ShortMessage\Entities\ShortMessageEntity;

/**
 * 手机短信Repository类:提供手机短信表操作资源
 *
 * @author qishaobo
 *
 * @since  2017-03-06 创建
 */
class ShortMessageRepository extends BaseRepository
{
    public function __construct(ShortMessageEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取短信列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getSMSs(array $param = [])
    {
        $default = [
            'fields'   => ['*'],
            'search'   => [],
            'page'     => 1,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['sms_send_id' => 'DESC'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity
        ->select($param['fields'])
        ->with(['fromOneUser' => function($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->with(['toOneUser' => function($query) {
            $query->select(['user_id', 'user_name']);
        }]);

        $query = $this->getSMSsParseWhere($query, $param);

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 获取短信数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getNum(array $param = [])
    {
        return  $this->getSMSsParseWhere($this->entity, $param)->count();
    }

    /**
     * 获取短信条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getSMSsParseWhere($query, $param)
    {
        $where = isset($param['search']) ? $param['search'] : [];

        if (isset($param['withUser'])) {
            $userId = $param['withUser'];
            $smsType = !empty($param['sms_type']) ? $param['sms_type'] : '';

            $query = $query->where(function ($query) use ($userId, $smsType) {
                if (empty($smsType) || $smsType == 'receive') {
                    $query->orWhere('user_to', $userId);
                }

                if (empty($smsType) || $smsType == 'send') {
                    $query->orWhere('user_from', $userId);
                }
            });
        }

        if (isset($where['user_from_name'])) {
            $search = $where['user_from_name'];
            $query = $query->whereHas('fromOneUser', function ($query) use ($search) {
                $query = $query->wheres(['user_name' => $search]);
            });

            unset($where['user_from_name']);
        } else if (isset($where['user_to_name'])) {
            $search = $where['user_to_name'];
            $query = $query->whereHas('toOneUser', function ($query) use ($search) {
                $query = $query->wheres(['user_name' => $search]);
            });

            unset($where['user_to_name']);
        }

        return $query->wheres($where);
    }

    /**
     * 获取短信
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getSMSDetail($smsId)
    {
        return  $this->entity
        ->with(['fromOneUser' => function($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->with(['toOneUser' => function($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->find($smsId);
    }

}