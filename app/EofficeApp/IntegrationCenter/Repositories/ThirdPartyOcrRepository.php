<?php
namespace App\EofficeApp\IntegrationCenter\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IntegrationCenter\Entities\ThirdPartyOcrEntity;

class ThirdPartyOcrRepository extends BaseRepository
{
    public function __construct(ThirdPartyOcrEntity $entity)
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
            'order_by' => ['id' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        $query = $this->getParseWhere($query, $param);

        return $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 查询条件解析 where条件解析
     *
     * @param array $param 查询条件
     *
     * @return mixed
     *
     * @author [dosy]
     */
    public function getParseWhere($query, $param)
    {
        $search = $param['search'] ?? [];
        if ($search) {
            $query = $query->wheres($search);
        }
        return $query;
    }

    /** 获取启用
     * @return array
     */
    public function getTencentOcr()
    {
        $ocr = $this->entity->with(['hasOneTencentOcr' => function($query) {
            $query->select('third_party_ocr_tencent.*');
        }])->where(['type' => 1, 'is_use' => 1])->first();
        return $ocr ? $ocr->toArray() : [];
    }
}
