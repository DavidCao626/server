<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8CompanyConfigEntity;

class VoucherIntergrationU8CompanyConfigRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8CompanyConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getCount($param = [])
    {
        $query = $this->entity;
        $query = $this->getParseWhere($query, $param);
        return $query->count();
    }

    public function getList($param = [])
    {
        $default = [
            'page' => 0,
            'order_by' => ['company_id' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];
        if ($param){
            $param = array_merge($default, array_filter($param));
        }
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        $query = $this->getParseWhere($query, $param);
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }

        return $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 查询条件解析 where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author yml
     *
     * @since  2019-04-17
     */
    public function getParseWhere($query, $param)
    {
        $search = $param['search'] ?? [];
        if ($search){
            $query = $query->wheres($search);
        }
        return $query;
    }
}
