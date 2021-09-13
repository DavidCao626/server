<?php

namespace App\EofficeApp\Email\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Email\Entities\EmailBoxEntity;

/**
 * 邮件-文件夹资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class EmailBoxRepository extends BaseRepository {

    public function __construct(EmailBoxEntity $entity) {
        parent::__construct($entity);
    }
    
    public function getEmailTypes($param){
         $default = [
            'search' => [],
             'fields' => ['*']
        ];

        $param = array_merge($default, array_filter($param));

        return $this->entity->select($param['fields'])
                        ->where("user_id", $param['user_id'])
                        ->wheres($param['search'])
                        ->get()
                        ->toArray();
    }

     public function getEmailTypesTotal($param){
          $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));
        return $this->entity->where("user_id", $param['user_id'])
                        ->wheres($param['search'])->count();
    }
    /**
     * 获取用户的文件夹
     * 
     * @param array $data
     * 
     * @return array
     * 
     * @author 喻威
     * 
     * @since 2015-10-21
     */
    public function getEmailBoxList($data) {

        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['box_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($data));

        return $this->entity
                        ->select($param['fields'])
                        ->where("user_id", $data['user_id'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()
                        ->toArray();


//         $boxResult = $this->entity->where('user_id',$data['user_id'])->get()->toArray();
//         return $boxResult;
    }
    

    public function getEmailBoxTotal($param) {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));
        return $this->entity->where("user_id", $param['user_id'])
                        ->wheres($param['search'])->count();
    }

    public function getEmailBoxListAll($data) {
        return $this->entity->where("user_id", $data['user_id'])
                        ->orderBy("box_id", "desc")
                        ->get()
                        ->toArray();
    }

    /**
     * 
     * @param type $id
     * @param type $user_id
     * @return type
     */
    public function infoEmailBox($id, $user_id) {
        $boxResult = $this->entity->where('box_id', $id)->where('user_id', $user_id)->get()->toArray();
        return $boxResult;
    }

    /**
     * 获取当前用户是否已经存在该目录文件夹
     * 
     * @param string $name    文件夹名字
     * 
     * @param string $user_id 用户ID
     * 
     * @return array
     * 
     * @author 喻威
     * 
     * @since 2015-10-21
     */
    public function infoEmailBoxByName($name, $user_id) {
        $boxResult = $this->entity->where('box_name', $name)->where('user_id', $user_id)->get()->toArray();
        return $boxResult;
    }

}
