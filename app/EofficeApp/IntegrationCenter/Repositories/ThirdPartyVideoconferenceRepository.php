<?php
namespace App\EofficeApp\IntegrationCenter\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IntegrationCenter\Entities\ThirdPartyVideoconferenceEntity;

class ThirdPartyVideoconferenceRepository extends BaseRepository
{
    public function __construct(ThirdPartyVideoconferenceEntity $entity)
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
        $query = $query->addSelect('is_default');
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

    /**
     * 获取接口详情
     * @return array
     */
    public function getVideoconferenceInterfaceInfo($interfaceId, $param = [])
    {
        $query = $this->entity;
        $search = $param["search"] ?? [];
        if(!empty($search)) {
            $query = $query->wheres($search);
        }
        $query = $query->where(['videoconference_id' => $interfaceId])
                    ->with('hasOneTencentCloud')
                    ->first();
        return $query ? $query->toArray() : [];
    }

    /**
     * 获取默认接口
     * @return array
     */
    public function getVideoconferenceInterfaceIsUsed()
    {
        $interfaceInfo = $this->entity->with(['hasOneTencentCloud' => function($query) {
                        $query->select('third_party_videoconference_tencent_cloud.*');
                    }])
                    ->where(['type' => 1, 'is_default' => 1])
                    ->first();
        return $interfaceInfo ? $interfaceInfo->toArray() : [];
    }
}
