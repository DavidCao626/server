<?php

namespace App\EofficeApp\System\Security\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Security\Entities\SystemSecurityWhiteAddressEntity;

/**
 * SystemSecurityWhiteAddress表资源库
 *
 * @author  lixx
 *
 * @since  2018-10-24 创建
 */
class SystemSecurityWhiteAddressRepository extends BaseRepository
{
    public function __construct(SystemSecurityWhiteAddressEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取数据库操作日志列表数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author lixx
     *
     * @since  2018-10-24
     */
    public function getWhiteAddressTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->parseWhere($this->entity, $where)->count();
    }
    /**
     * 获取数据库where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author lixx
     *
     * @since  2018-10-24
     */
    public function parseWhere($query, array $where = [])
    {
        return $query = $query->wheres($where);
    }
    /**
     * 获取白名单列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author lixx
     *
     * @since  2016-07-06
     */
    public function getWhiteAddressList(array $param = [])
    {
        $default = [
            'fields'      => ['white_address_id', 'white_address_url', 'user_id', 'created_at'],
            'search'      => [],
            'page'        => 1,
            'limit'       => config('eoffice.pagesize'),
            'order_by'    => ['white_address_id' => 'DESC'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select($param['fields']);
        $query = $this->parseWhere($query, $param['search']);

        $query = $query->with(['belongsToUser' => function ($query) {
            $query->select('user_id', 'user_name');
        }]);

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }
    /**
     * 新增白名单
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author lixx
     *
     * @since  2018-10-24
     */
    public function addWhiteAddress($data)
    {
        return $this->entity->insert($data);
    }

    /**
     * 更新白名单
     *
     * @param $data
     * @param $where
     * @return mixed
     */
    public function updateWhiteAddress($data,$where)
    {
        return $this->entity->where($where)->update($data);
    }

    /**
     * 白名单是否存在
     * @param $whiteAddress
     * @return bool
     */
    public function whiteAddressExists($whiteAddress)
    {
        $count = $this->entity->where("white_address_url",$whiteAddress)->count();
        return $count > 0 ? true:false;
    }

    /**
     * 白名单是否存在
     * @param $whiteAddressId
     */
    public function whiteAddressIdExists($whiteAddressId)
    {
        $count = $this->entity->where("white_address_id",$whiteAddressId)->count();
        return $count > 0 ? true:false;
    }


    /**
     * 删除一条白名单
     */
    public function deleteAWhiteAddress($whiteAddressId){
        return $this->entity->where("white_address_id",$whiteAddressId)->delete();
    }
}