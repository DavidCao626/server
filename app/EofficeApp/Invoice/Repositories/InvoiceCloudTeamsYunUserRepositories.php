<?php


namespace App\EofficeApp\Invoice\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Invoice\Entities\InvoiceCloudTeamsYunUserEntities;

class InvoiceCloudTeamsYunUserRepositories extends BaseRepository
{
    public function __construct(InvoiceCloudTeamsYunUserEntities $entity)
    {
        parent::__construct($entity);
    }

    public function getAll($cid)
    {
        return $this->entity->select(['userId', 'user_id', 'name', 'cid','account', 'role', 'state'])->where('cid', $cid)->get()->toArray();
    }

    public function getUserCount($param = [])
    {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getUserList($param);
    }

    public function getUserList($param)
    {
        $cid = $param['cid'] ?? '';
        unset($param['cid']);
        $query = $this->entity->select(['user.user_id', 'user.user_name',  'user.user_accounts', 'state', 'cid', 'account', 'name', 'role', 'userId', 'invoice_cloud_teamsyun_user.created_at', 'user_status', 'user_status.status_id', 'status_name'])
//            ->rightJoin('user', 'user.user_id', '=', 'invoice_cloud_teamsyun_user.user_id')
            ->rightJoin('user', function ($join) use($cid) {
                $join->on('user.user_id', '=','invoice_cloud_teamsyun_user.user_id')->where('invoice_cloud_teamsyun_user.cid', '=', $cid);
            })
            ->leftJoin('user_system_info', function($join) {
                $join->on('user_system_info.user_id', '=', 'user.user_id')
                ->where('user_status', '!=', 0);
            })->leftJoin('user_status', function ($join) {
                $join->on('user_system_info.user_status', '=','user_status.status_id');
            })
            ->whereNull('user.deleted_at')
            ->orderBy('user.user_id', 'asc');
        if (isset($param['search'])) {
            if (isset($param['search']['sync'])) {
                $sync = $param['search']['sync'];
                if ($sync == 1) {
                    $query = $query->whereNotNull('invoice_cloud_teamsyun_user.userId');
                } else {
                    $query = $query->whereNull('invoice_cloud_teamsyun_user.userId');
                }
                unset($param['search']['sync']);
            }
            if (isset($param['search']['user_status'])) {
                $userStatus = $param['search']['user_status'];
//                if ($userStatus == 1) {
//                    $query = $query->where('user_status', '!=', 2);
//                } else {
//                    $query = $query->where('user_status', 2);
//                }
                $query = $query->where('user_status', $userStatus);
                unset($param['search']['user_status']);
            }
            if (isset($param['search']['multiSearch'])) {
                $multiSearchs = $param['search']['multiSearch'];
                unset($param['search']['multiSearch']);
                if (isset($multiSearchs['__relation__'])) {
                    $multiSearchs['__relation__'] = 'and';
                }
                $query = $query->multiWheres($multiSearchs);
            }
            if (isset($param['search']['user_id'])) {
                $query = $query->wheres(['invoice_cloud_teamsyun_user.user_id' => $param['search']['user_id']]);
                unset($param['search']['user_id']);
            }            
            $query = $query->wheres($param['search']);
            
        }
        if (isset($param['page']) && $param['page'] != 0) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }
        if ($param["returntype"] == "array") {
            $list = $query->get()->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['status_name'] = mulit_trans_dynamic("user_status.status_name.user_status_" .$value['status_id']);
            }
            return $list;
        } else if ($param["returntype"] == "count") {
            if (isset($param['groupBy'])) {
                return $query->get()->count();
            } else {
                return $query->count();
            }
        } else if ($param["returntype"] == "object") {
            return $query->get();
        } else if ($param["returntype"] == "first") {
            if (isset($param['groupBy'])) {
                return $query->get()->first();
            } else {
                return $query->first();
            }
        }
    }
}