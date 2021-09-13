<?php

namespace App\EofficeApp\System\Signature\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Signature\Entities\SignatureEntity;

/**
 * 印章Repository类:提供印章表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-01-22 创建
 */
class SignatureRepository extends BaseRepository
{
    public function __construct(SignatureEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取印章列表
     *
     * @param  array  $param  查询参数
     *
     * @return array  印章列表
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    public function getSignatureList(array $param = [])
    {
        $default = [
            'fields'    => ['signature_id', 'signature_onwer', 'signature_picture','signature_name'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['signature_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity
        ->select($param['fields'])
        ->orders($param['order_by'])
        ->parsePage($param['page'], $param['limit']);

        // 处理从个性设置-金格图片章获取列表的情况
        $personalSetFlag = isset($param["personal_set_flag"]) ? $param["personal_set_flag"] : "";
        if ($personalSetFlag == "1") {
            unset($param["personal_set_flag"]);
            $ownUser = own();
            $ownUserId = isset($ownUser['user_id']) ? $ownUser['user_id'] : '';
            $query = $query->wheres(["signature_onwer" => [$ownUserId]]);
        }

        $query = $query->with(['hasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }]);

        $query = $this->parseSignatureWhere($query, $param['search']);

        return $query->get()
        ->toArray();
    }

    /**
     * 查询数量
     *
     * @param  array  $param 查询条件
     *
     * @return int    数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSignatureTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];

        $query = $this->parseSignatureWhere($this->entity, $where);

        // 处理从个性设置-金格图片章获取列表的情况
        $personalSetFlag = isset($param["personal_set_flag"]) ? $param["personal_set_flag"] : "";
        if ($personalSetFlag == "1") {
            unset($param["personal_set_flag"]);
            $ownUser = own();
            $ownUserId = isset($ownUser['user_id']) ? $ownUser['user_id'] : '';
            $query = $query->wheres(["signature_onwer" => [$ownUserId]]);
        }

        return $query->count();
    }

    /**
     * 获取印章where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function parseSignatureWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }

}
