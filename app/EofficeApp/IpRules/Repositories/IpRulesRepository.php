<?php

namespace App\EofficeApp\IpRules\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IpRules\Entities\IpRulesEntity;

/**
 * 访问控制 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class IpRulesRepository extends BaseRepository {

    public function __construct(IpRulesEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取访问控制列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getIpRulesList($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['ip_rules_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));


        return $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    /**
     * 获取具体的控制规则
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoIpRules($id) {
        $result = $this->entity->where('ip_rules_id', $id)->get()->toArray();
        return $result;
    }

    /**
     * 获取访问控制ip范围
     *
     * @param int $type 控制类型
     *
     * @return array
     *
     * @author 齐少博
     *
     * @since 2015-10-21
     */
    function getAccessControl($type)
    {
        return $this->entity
        ->select('ip_rules_begin_ip', 'ip_rules_end_ip', 'ip_rules_to_all', 'ip_rules_dept', 'ip_rules_role', 'ip_rules_user', 'control_platform_pc', 'control_platform_mobile', 'flow_ids')
        ->where('ip_rules_type', $type)
        ->get()
        ->toArray();
    }
}
