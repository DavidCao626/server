<?php

namespace App\EofficeApp\Sso\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Sso\Entities\SsoEntity;
use DB;
/**
 * 外部系统：配置用户项资源库
 *
 * @author:喻威
 *
 * @since：2015-10-27
 *
 */
class SsoRepository extends BaseRepository {

    private $user_id;

    public function __construct(SsoEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 二级菜单 构建ssoList
     * 
     */
    public function getSsoTree($where) {

        return $this->entity->select(['sso.*', 'sso_login.sso_login_name', 'sso_login.sso_login_pass'])
                        ->leftJoin('sso_login', function($join) {
                            $join->on("sso_login.sso_id", '=', 'sso.sso_id');
                        })
                        ->wheres($where)
                        ->groupBy("sso.sso_id")
                        ->get()->toArray();
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
    public function getSsoList($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['sso_id' => 'asc'],
        ];

        $param = array_merge($default, array_filter($param));


        return $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->forPage($param['page'], $param['limit'])
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
    public function infoSso($id) {
        $result = $this->entity->where('sso_id', $id)->get()->toArray();
        return $result;
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
    public function getSsoLogin($sso_id, $user_id = null) {
        $this->sso_id = $sso_id;
        $query = $this->entity;
        $query = $query->select(['sso_login.sso_login_id', 'sso_login.sso_login_name', 'sso_login.sso_login_pass', 'sso.*']);
        $query = $query->leftJoin('sso_login', function($join) {
            $join->on("sso.sso_id", '=', 'sso_login.sso_id')
                    ->where("sso_login.sso_id", '=', $this->sso_id);
        });

        if ($user_id) {
            $query = $query->where("sso_login.sso_login_user_id", '=', $user_id);
        }

        return $query->get()->toArray();
    }

    /**
     * 当没有设置用户登录密码时，才用sso这个表为左连接，获取具体的控制规则
     * 
     * @param type $id
     * 
     * @return array
     * 
     */
    public function getSsoLeftJoinLogin($sso_id, $user_id = null) {
        $this->sso_id = $sso_id;
        $query = $this->entity;
        $query = $query->select('*')->where('sso_id',$this->sso_id);
        $result = $query->get()->toArray();
        // $result = DB::table('sso')->select('*')->where('sso_id',$this->sso_id)->get();
        // $result = json_decode(json_encode($result),true);
        if (isset($result[0])) {
            $result[0]['sso_login_name'] = '';
            $result[0]['sso_login_pass'] = '';
        } else {
            $result[0] = [];
        }
        return $result;
    }

    /**
     * 外部账户数据配置不存在ID时 进行插入操作
     * 
     * @param type $user_id
     * 
     * @return array
     * 
     * @author 喻威
     * 
     * @since 2015-10-27
     */
    public function insertSsoLogin($user_id) {
        $this->user_id = $user_id;
        $query = $this->entity;
        $query = $query->select(['sso_login.sso_login_id', 'sso_login.sso_login_name', 'sso_login.sso_login_pass', 'sso.*']);
        $query = $query->leftJoin('sso_login', function($join) {
            $join->on("sso.sso_id", '=', 'sso_login.sso_id')
                    ->where("sso_login.sso_login_user_id", '=', $this->user_id);
        });

        return $query->get()->toArray();
    }

    /**
     * 获取外部系统用户列表
     * 
     * @param array $param
     * 
     * @author 喻威
     * 
     * @since 2015-10-19
     */
    public function getSsoLoginList($param) {


        $default = [
            'fields' => ['sso_login.sso_login_id', 'sso_login.sso_login_name', 'sso_login.sso_login_pass', 'sso.*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['sso_login.sso_login_id' => 'asc'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity;
        $query = $query->select($param['fields']);
        $query = $query->leftJoin('sso_login', function($join) {
            $join->on("sso.sso_id", '=', 'sso_login.sso_id');
        });

        $result = $query
                        ->where("sso_login.sso_login_user_id", $param['sso_login_user_id'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->forPage($param['page'], $param['limit'])
                        ->get()->toArray();

        return $result;
    }

    /**
     * 获取外部系统用户列表数目
     * 
     * @param array $param
     * 
     * @author 喻威
     * 
     * @since 2015-10-19
     */
    public function getSsoLoginTotal($param) {

        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        $query = $query->leftJoin('sso_login', function($join) {
            $join->on("sso.sso_id", '=', 'sso_login.sso_id');
        });
        $result = $query
                ->where("sso_login.sso_login_user_id", $param['sso_login_user_id'])
                ->wheres($param['search'])
                ->count();

        return $result;
    }

}
