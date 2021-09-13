<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailCustomerRecordLogEntity;

/**
 * 邮件服务器Repository类:提供邮件服务器相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailCustomerRecordRepository extends BaseRepository
{
    public function __construct(WebmailCustomerRecordLogEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getList(array $param = [])
    {
        $default = [
            'fields' => ['*'],
            'search' => [],
            'page' => 1,
            'limit' => config('eoffice.pagesize'),
            'order_by' => [],
        ];

        $param = array_filter($param, function ($var) {
            return $var !== '';
        });
        $param = array_merge($default, $param);
        $query = $this->entity
            ->select($param['fields'])
            ->with(['customer' => function($query) {
                $query->select(['customer_id', 'customer_name']);
            }])
            ->with(['linkman' => function($query) {
                $query->select(['linkman_id', 'linkman_name']);
            }]);

        $query = $this->getParseWhere($query, $param['search']);

        $list = $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
        foreach ($list as $key => $value) {
            $list[$key]['result'] = json_decode($value['result'], 1);
        }
        return $list;
    }

    /**
     * 获取数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getNum(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getParseWhere($this->entity, $where)->count();
    }

    /**
     * 条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getParseWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }
}