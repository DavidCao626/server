<?php

namespace App\EofficeApp\Sso\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Sso\Entities\SsoLoginEntity;

/**
 * 系统配置：单点登录设置资源库
 *
 * @author:喻威
 *
 * @since：2015-10-27
 *
 */
class SsoLoginRepository extends BaseRepository {

    public function __construct(SsoLoginEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取具体的外部系统用户
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoSsoLogin($id) {
        $result = $this->entity->where('sso_login_id', $id)->get()->toArray();
        return $result;
    }

    public function getMySsoLoginDetail($ssoId, $userId)
    {
        return $this->entity->where('sso_id', $ssoId)
            ->where('sso_login_user_id', $userId)
            ->with('ssoSystem')
            ->first();
    }
    public function getSsoLoginList($params){
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['created_at' => 'desc'],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }


}
