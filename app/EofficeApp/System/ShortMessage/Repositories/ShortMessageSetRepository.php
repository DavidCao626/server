<?php

namespace App\EofficeApp\System\ShortMessage\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\ShortMessage\Entities\ShortMessageSetEntity;

/**
 * 手机短信Repository类:提供手机短信表操作资源
 *
 * @author qishaobo
 *
 * @since  2017-03-06 创建
 */
class ShortMessageSetRepository extends BaseRepository
{
    public function __construct(ShortMessageSetEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查询手机短信详情
     *
     * @param  array $where 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function getSMSSetDetail($where = [])
    {
        return $this->entity->where($where)->limit(1)->get();
    }

    /**
     * 查询手机短信列表
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function getSMSSetList($param)
    {
        $where = empty($param['search']) ? [] : $param['search'];
        return $this->entity->wheres($where)->get()->toArray();
    }

     public function getSMSSet()
    {
        return $this->entity->first();
    }

    /**
     * 获取短信配置总数
     * @param  array  $param 
     * @return string 配置总数量
     */
    public function getSMSSetNum(array $param = [])
    {
        return  $this->entity->count();
    }

    /**
     * 获取短信设置列表
     * @param  array  $param 
     * @return array  列表
     */
    public function getSMSSets(array $param = [])
    {
        $default = [
            'fields'   => ['*'],
            'search'   => [],
            'page'     => 1,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['sms_id' => 'DESC'],
        ];

        $param = array_merge($default, $param);

        $query = $this->entity
        ->select($param['fields']);

        $query = $query->join('short_message_type','short_message_set.relation_type_id','=','short_message_type.sms_type_id')
                ->orders($param['order_by']);
        if (isset($param['page']) && isset($param['limit'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }        
        $result= $query->get()->toArray();
        return $result;
    }
}