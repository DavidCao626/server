<?php

namespace App\EofficeApp\Sms\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Sms\Entities\SmsEntity;
use Schema;
use DB;

/**
 * 内部消息 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class SmsRepository extends BaseRepository {

    private $user_id;
    private $group;
    private $individuals;
    private $to_id;

    public function __construct(SmsEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取消息查询的列表
     * 
     * @param  array $param
     * 
     * @return array
     * 
     */
    public function getUnreadCountPerson($param) {

        $this->user_id = $param['recipients'];
 
        return DB::table("sms")->select(DB::raw('from_id,count(*) as count'))
                        ->leftJoin('sms_receive', 'sms_receive.sms_id', '=', 'sms.sms_id')
                        ->where('sms.sms_type', "user")
                        ->where('sms_receive.recipients', $this->user_id)
                        ->whereRaw('(find_in_set(\'' . $this->user_id . '\',sms_receive.reminds)) <= 0')
                        ->groupBy('sms.from_id')
                        ->get()
                        ->toArray();
    }

    public function getUnreadCountGroup($param) {

        $this->user_id = $param['recipients'];
        $this->group = $param['group'];
        $this->individuals = $param['individuals'];

        return DB::table("sms")->select(DB::raw('from_id,recipients,count(*) as count'))
                        ->leftJoin('sms_receive', 'sms_receive.sms_id', '=', 'sms.sms_id')
                        ->where('sms.sms_type', "personalGroup")
                        ->whereIn('sms_receive.recipients', $this->individuals)
                        ->orWhere(function ($query) {
                            $query->where('sms.sms_type', "publicGroup")
                            ->whereIn('sms_receive.recipients', $this->group);
                        })
                        ->whereRaw('(find_in_set(\'' . $this->user_id . '\',sms_receive.reminds)) <= 0')
                        ->groupBy('sms_receive.recipients')
                        ->get()->toArray();
    }

    public function getTalkList($param) {

        // user1 user2 type 默认都是未读列表
        $default = [
            'fields' => ['sms.*', 'recipients'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['send_time' => 'desc'],
            'getList' => "unread"
        ];
        //isset read
        $param = array_merge($default, array_filter($param));


        $query = $this->entity->select($param['fields'])->leftJoin('sms_receive', function($join) {
                    $join->on("sms.sms_id", '=', 'sms_receive.sms_id');
                })
                ->wheres($param['search']);
        if ($param["type"] == "user") {
            $query = $query->where('sms_receive.recipients', $param["user2"])->where('sms.sms_type', "user");
        } else if ($param["type"] == "personalGroup") {
            $query = $query->where('sms_receive.recipients', $param["user2"])->where('sms.sms_type', "personalGroup");
        } else if ($param["type"] == "publicGroup") {
            $query = $query->where('sms_receive.recipients', $param["user2"])->where('sms.sms_type', "publicGroup");
        }
        if (isset($param["getList"]) && $param["getList"] == "unread") {
            $query = $query->whereRaw('(find_in_set(\'' . $param["user2"] . '\',sms_receive.reminds)) <= 0');
        }

        $reminds = $param['user1'].",";
        DB::update("update  sms_receive  set  	reminds = concat(reminds,'{$reminds}') where sms_receive.recipients = '{$param['user2']}' and find_in_set('{$param['user1']}',sms_receive.reminds) <=0");
        
        return $query->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    public function getTalkListTotal($param) {
        // user1 user2 type 默认都是未读列表
        $default = [
            'search' => [],
            'getList' => "unread"
        ];
        //isset read
        $param = array_merge($default, array_filter($param));
        $query = $this->entity->leftJoin('sms_receive', function($join) {
                    $join->on("sms.sms_id", '=', 'sms_receive.sms_id');
                })
                ->wheres($param['search']);
        if ($param["type"] == "user") {
            $query = $query->where('sms_receive.recipients', $param["user2"])->where('sms.sms_type', "user");
        } else if ($param["type"] == "personalGroup") {
            $query = $query->where('sms_receive.recipients', $param["user2"])->where('sms.sms_type', "personalGroup");
        } else if ($param["type"] == "publicGroup") {
            $query = $query->where('sms_receive.recipients', $param["user2"])->where('sms.sms_type', "publicGroup");
        }
        if (isset($param["getList"]) && $param["getList"] == "unread") {
            $query = $query->whereRaw('(find_in_set(\'' . $param["user2"] . '\',sms_receive.reminds)) <= 0');
        }
        return $query->count();
    }

}
