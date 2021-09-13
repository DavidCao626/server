<?php

namespace App\EofficeApp\Role\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Role\Entities\RoleCommunicateEntity;

/**
 * 角色通信Repository类:提供角色通信表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RoleCommunicateRepository extends BaseRepository
{
    public function __construct(RoleCommunicateEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取角色通信列表
     *
     * @param array $param
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRoleCommunicate($param)
    {
        $default = [
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['id' => 'desc'],
        ];
        $param = array_merge($default, $param);

        $query = $this->entity;

        if(isset($param['communicate_type']) && !empty($param['communicate_type'])) {
            if($param['communicate_type'] == 'sms') {
                $param['communicate_type'] = 1;
            }elseif($param['communicate_type'] == 'email') {
                $param['communicate_type'] = 3;
            }elseif($param['communicate_type'] == 'shortMessage'){
                $param['communicate_type'] = 5;
            }elseif($param['communicate_type'] == 'webMail'){
                $param['communicate_type'] = 9;
            }elseif($param['communicate_type'] == 'appPush'){
                $param['communicate_type'] = 7;
            }else{
                $param['communicate_type'] = '';
            }
            if(!empty($param['communicate_type'])) {
                $query = $query->whereRaw("FIND_IN_SET('". $param['communicate_type']."', communicate_type)");
            }
        }

        return $query
            ->Orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get();
    }

    /**
     * 获取可以通信的角色
     *
     * @param array $param
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-06-30 创建
     */
    public function getRoleTo($param)
    {
        $query = $this->entity->select(['role_to', 'communicate_type']);

        foreach ($param as $roleId) {
            $query = $query->orWhereRaw("FIND_IN_SET(?, role_from)", [$roleId]);
        }

        return $query->get()->toArray();
    }

    /**
     * 获取可以通信的角色
     *
     * @param array $param
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2017-03-28 创建
     */
    public function communicateRoles($roleFroms = [], $communicateTypes = [])
    {
        $query = $this->entity->select(['role_from', 'role_to', 'communicate_type']);

        if (!empty($roleFroms)) {
            $query->where(function ($query) use ($roleFroms) {
                foreach ($roleFroms as $roleId) {
                    $roleId = security_filter($roleId);
                    $query->orWhereRaw("FIND_IN_SET(?, role_from)", [$roleId]);
                }
            });
        }

        if (!empty($communicateTypes)) {
            $query->where(function ($query) use ($communicateTypes) {
                foreach ($communicateTypes as $communicateType) {
                    $communicateType = security_filter($communicateType);
                    $query->orWhereRaw("FIND_IN_SET(?, communicate_type)", [$communicateType]);
                }
            });
        }

        return $query->get()->toArray();
    }

    public function communicateCount($roleFroms = [],$roleTo = [],$communicateTypes = [])
    {
        $query = $this->entity->select(['role_from', 'role_to', 'communicate_type']);

        if (!empty($roleFroms)) {
            $query->where(function ($query) use ($roleFroms) {
                foreach ($roleFroms as $roleId) {
                    $roleId = security_filter($roleId);
                    $query->orWhereRaw("FIND_IN_SET(?, role_from)", [$roleId]);
                }
            });
        }

        if (!empty($roleTo)) {
            $query->where(function ($query) use ($roleTo) {
                foreach ($roleTo as $Id) {
                    $Id = security_filter($Id);
                    $query->orWhereRaw("FIND_IN_SET(?, role_to)", [$Id]);
                }
            });
        }

        if (!empty($communicateTypes)) {
            $query->where(function ($query) use ($communicateTypes) {
                foreach ($communicateTypes as $communicateType) {
                    $communicateType = security_filter($communicateType);
                    $query->orWhereRaw("FIND_IN_SET(?, communicate_type)", [$communicateType]);
                }
            });
        }

        return $query->count();
    }

    /**
     * 获取角色可见的字段
     * @param  array $roleIdArr 角色id数组
     * @param  string $table  配置表中的table
     * @return array         可见的字段
     */
    public function getControlFieldsByTable(array $roleIdArr, string $table)
    {
        $result = $controlUsers = $contrlFields = [];
        if (empty($roleIdArr)) {
            return $result;
        }
        $lists = $this->entity->select(['role_from', 'role_to', 'control_fields'])->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $key => $list) {
            if (!$list->role_from || !$list->role_to) {
                continue;
            }
            $roleFormIdArr = explode(',', $list->role_from);
            $diffArr = array_intersect($roleFormIdArr, $roleIdArr);
            if (empty($diffArr)) {
                continue;
            }
            if ($list->control_fields) {
                $controlFields = json_decode($list->control_fields, true);
            }
            if (isset($controlFields[$table])) {
                $roleToIdArr = explode(',', $list->role_to);
                foreach ($controlFields[$table] as $fieldIndex => $isDisable) {
                    foreach ($roleToIdArr as $roleId) {
                        if ($isDisable && (!isset($result[$roleId]) || !in_array($fieldIndex, $result[$roleId]))) {
                            $result[$roleId][] = $fieldIndex;
                        }
                    }
                }
            }
        }
        return $result;
    }
}