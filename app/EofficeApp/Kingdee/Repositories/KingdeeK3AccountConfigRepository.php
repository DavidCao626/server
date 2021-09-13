<?php
namespace App\EofficeApp\Kingdee\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Kingdee\Entities\KingdeeK3AccountConfigEntity;

class KingdeeK3AccountConfigRepository extends BaseRepository
{
    public function __construct(KingdeeK3AccountConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    // public function getInfoByDatabaseConfig($databaseIds)
    // {
    //     $info = $this->entity->whereIn('database_config', $databaseIds)->get()->toArray();
    //     return $info;
    // }

    /**
     * 获取账套列表
     */
    public function getAccountList($param = [])
    {
        if(isset($param['return_type']) && $param['return_type'] == 'count'){
            return $this->entity->count();
        }
        return $this->entity->with(['tables'=>function($search){
            $search = $search->select('account_id'); 
        }])->get()->toArray();
    }


    /**
     * 新增账套信息
     */
    public function addAccount($data)
    {
        return $this->entity->create($data);
    }

    /**
     * 获取账套信息
     */
    public function getAccountDetail($accountId)
    {
        if(!$accountId){
            return false;
        }
        return $this->entity->where('kingdee_account_id',$accountId)->first();
    }
    /**
     * 删除单个账套信息
     */
    public function deleteAccount($accountId)
    {
        if(!$accountId){
            return false;
        }
        return $this->entity->where('kingdee_account_id',$accountId)->delete();
    }
    /**
     * 更新单个账套信息
     */
    public function updateAccount($accountId,$data)
    {
        if(!$accountId){
            return false;
        }
        return $this->entity->where('kingdee_account_id',$accountId)->update($data);
    }

    /**
     * 更新所有账套为非默认账套
     */
    public function updateAllNotDefalut()
    {
        $data = [
            'default' => 0
        ];
        return $this->entity->where(['default' => 1])->update($data);
        // return $this->entity->update($data);
    }

    /**
     * 获取默认账套信息
     * 
     */
    public function getDefaultAccount()
    {
        return $this->entity->where(['default' => 1])->first();
    }
}
