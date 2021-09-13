<?php

namespace App\EofficeApp\SystemSms\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\SystemSms\Entities\SystemSmsReceiveEntity;
use DB;

/**
 * 系统消息 资源库
 *
 * @author:喻威
 *
 * @since：2016-04-20
 *
 */
class SystemSmsReceiveRepository extends BaseRepository
{

    public function __construct(SystemSmsReceiveEntity $entity)
    {
        parent::__construct($entity);
    }

    public function mySystemSms($param)
    {

        //默认 待发送的时间不显示
        //收件人 -- 我
        //强制连表 system_reminds获取消息类型

        $default = [
            'fields'   => ['system_sms.*', 'recipients', 'remind_flag', 'system_sms_receive.id'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['system_sms.sms_id' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        if (isset($param['search']['remind_flag']) && ($param['search']['remind_flag'][0] == 'all')) {
            unset($param['search']['remind_flag']);
        }
        if (isset($param['search']['module_type']) && ($param['search']['module_type'][0] === 'all')) {
            unset($param['search']['module_type']);
        }
        $limit = $param['page'] * $param['limit'];
        $query = $this->entity;
        $query = $query->select($param['fields'])->leftJoin('system_sms', function ($join) {
            $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
        })
            ->where('system_sms_receive.deleted', "0")
            ->where('system_sms_receive.recipients', $param['user_id']);
        if (empty($param['search'])) {
            $system_sms = $query->orders($param['order_by'])
                ->parsePage($param['page'], $param['limit'])
                ->get()->toArray();
            foreach ($system_sms as $key => $value) {
                $system_reminds = DB::table('system_reminds')->where("remind_menu", $value['sms_menu'])->where("remind_type", $value[
                    'sms_type'])->first();
                $system_sms[$key]['remind_name']  = !empty($system_reminds->remind_name)?$system_reminds->remind_name:'';
                $system_sms[$key]['remind_state'] = !empty($system_reminds->remind_state)?$system_reminds->remind_state:'';
                $system_sms[$key]['remind_id']    = !empty($system_reminds->id)?$system_reminds->id:'';
            }
        } else {
            $system_sms = $query->wheres($param['search'])->orders($param['order_by'])
                ->parsePage($param['page'], $param['limit'])
                ->get()->toArray();
            foreach ($system_sms as $key => $value) {
                $system_reminds                   = DB::table('system_reminds')->where("remind_menu", $value['sms_menu'])->where("remind_type", $value['sms_type'])->first();
                $system_sms[$key]['remind_name']  =  !empty($system_reminds->remind_name)?$system_reminds->remind_name:'';
                $system_sms[$key]['remind_state'] = !empty($system_reminds->remind_state)?$system_reminds->remind_state:'';
                $system_sms[$key]['remind_id']    = !empty($system_reminds->id)?$system_reminds->id:'';
            }
        }
        return $system_sms;
        // return $query->select($param['fields'])->leftJoin('system_sms', function($join) {
        //                     $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
        //                 })
        //                 ->leftJoin('system_reminds', function($join) {
        //                     $join->on("system_reminds.remind_menu", '=', 'system_sms.sms_menu')
        //                     ->on("system_reminds.remind_type", '=', 'system_sms.sms_type');
        //                 })
        //                 ->wheres($param['search'])
        //                 ->where('system_sms_receive.deleted', "0")
        //                 ->where('system_sms_receive.recipients', $param['user_id'])
        //                 ->orders($param['order_by'])
        //                 ->parsePage($param['page'], $param['limit'])
        //                 ->get()->toArray();
    }

    public function signSystemSmsRead($param)
    {
        if (!empty($param['menu']) && $param['menu'] != "all") {
            $res = $this->entity->leftJoin('system_sms', function ($join) {
                $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
            })
                ->where("system_sms.send_time", "<=", date("Y-m-d H:i:s"))
                ->where('system_sms_receive.deleted', "0")
                ->where('system_sms_receive.recipients', $param['user_id']);
            $res = $res->where("system_sms.sms_menu", $param['menu']);
        } else {
            $res = $this->entity->where('system_sms_receive.recipients', $param['user_id']);
        }
        // 取消使用遍历
        $res = $res->update(['system_sms_receive.remind_flag' => "1"]);

        return $res;
    }

    public function mySystemSmsTotal($param)
    {
        $default = [

            'search'   => [],
            'order_by' => ['send_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        if (isset($param['search']['remind_flag']) && ($param['search']['remind_flag'][0] == 'all')) {
            unset($param['search']['remind_flag']);
        }
        if (isset($param['search']['module_type']) && ($param['search']['module_type'][0] === 'all')) {
            unset($param['search']['module_type']);
        }
        if (empty($param['search'])) {
            return $this->entity
                ->where('system_sms_receive.deleted', "0")
                ->where('system_sms_receive.recipients', $param['user_id'])
                ->count();
        } else {
            return $this->entity->leftJoin('system_sms', 'system_sms.sms_id', '=', 'system_sms_receive.sms_id')
                ->where('system_sms_receive.deleted', "0")
                ->where('system_sms_receive.recipients', $param['user_id'])
                ->wheres($param['search'])
                ->count();
        }
        // if (isset($param['end_date']) && !empty($param['end_date'])) {
        //     $query = $query->whereRaw("send_time<='" . $param['end_date'] . "'");
        // }
        // if (isset($param['start_date']) && !empty($param['start_date'])) {
        //     $query = $query->whereRaw("send_time>='" . $param['start_date'] . "'");
        // }
    }

    public function viewSystemSms($id)
    {

        return $this->entity->select(['system_sms.*', 'remind_name', 'remind_flag'])->leftJoin('system_sms', function ($join) {
            $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
        })->leftJoin('system_reminds', function ($join) {
            $join->on("system_reminds.remind_menu", '=', 'system_sms.sms_menu')
                ->on("system_reminds.remind_type", '=', 'system_sms.sms_type');
        })
            ->where('system_sms_receive.deleted', "0")
            ->find($id);
    }

    public function getSystemSmsUnread($param)
    {

        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select(DB::raw('count(*) as count, sms_menu'))->leftJoin('system_sms', function ($join) {
            $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
        });
        if (isset($param["search"]["sms_menu"])) {
            $val = isset($param["search"]["sms_menu"][0]) ? $param["search"]["sms_menu"][0] : "";
            if ($val == "other") {
                unset($param["search"]["sms_menu"]);
                $query = $query->whereNull('sms_menu')
                    ->orWhere(function ($query) {
                        $query->where('sms_menu', "");
                    });
            }
        }

        return $query->where('system_sms_receive.deleted', "0")
            ->wheres($param['search'])
            ->where('system_sms_receive.remind_flag', "0")
            ->where('system_sms_receive.recipients', $param["user_id"])
            ->groupBy('sms_menu')
            ->get()->toArray();
    }
    public function getLastMessage($user_id) {
        $param = [
            'fields' => ['system_sms.content', 'system_sms.sms_id as sid', 'system_sms.send_time', 'system_sms.sms_menu', 'system_sms.sms_type', 'system_sms.contentParam', 'system_sms_receive.id', 'system_sms_receive.sms_id', 'system_sms_receive.recipients', 'system_sms_receive.remind_flag', 'system_sms_receive.deleted'],
            'search' => []
        ];
        $query = $this->entity->select($param['fields'])->leftJoin('system_sms', function ($join) {
            $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
        });
        $list = $query->where('system_sms_receive.deleted', "0")
            ->wheres($param['search'])
            // ->where('system_sms_receive.remind_flag', "0")
            ->where('system_sms_receive.recipients', $user_id)
            ->orderBy('system_sms.sms_id', 'desc')
            ->first();
        if (!empty($list)) {
            return $list->toArray();
        }else{
            return [];
        }
    }

    public function getUnreadTotal($user_id)
    {
        return $this->entity->where('system_sms_receive.deleted', "0")
            ->where('system_sms_receive.remind_flag', "0")
            ->where('system_sms_receive.recipients', $user_id)
            ->count();
    }

    public function getNewDetailByGroupBySmsType($user_id, $module)
    {

        //默认 待发送的时间不显示
        //收件人 -- 我
        //强制连表 system_reminds获取消息类型

        $param = [
            'fields' => ['system_sms.content', 'system_sms.sms_id', 'system_sms.send_time', 'system_reminds.id', 'system_reminds.remind_menu', 'system_reminds.content'],
        ];
        $query = $this->entity;
        $max_id =  $query->select($param['fields'])->leftJoin('system_sms', function ($join) {
            $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
        })
            ->where('system_sms_receive.deleted', "0")
            ->where('system_sms_receive.recipients', $user_id)
            ->where('system_sms.sms_menu', $module)
            ->max('system_sms.sms_id');
           $smsData = DB::table('system_sms')->select(['system_sms.content', 'system_sms.sms_id', 'system_sms.sms_menu', 'system_sms.sms_type', 'system_sms.contentParam', 'system_sms.send_time'])->where('sms_id',$max_id)->first();

           if (!empty($smsData)) {
                if (($smsData->sms_menu .'-'. $smsData->sms_type != 'export-download')) {
                     $remind_data = DB::table("system_reminds")->select(['system_reminds.remind_content', 'system_reminds.id', 'system_reminds.remind_menu', 'system_reminds.remind_type'])->where('system_reminds.remind_menu', '=', $smsData->sms_menu)->where('system_reminds.remind_type', '=', $smsData->sms_type)->first();
                    $smsData->remind_id = isset($remind_data->id) ? $remind_data->id : '';
               }
           }
           return $smsData;
    }

    public function getMaxSmsIdGroupByModule($userId)
    {
        $sourceMaxSmsId = $this->entity->selectRaw('system_sms.sms_menu as module, max(system_sms.sms_id) as max_sms_id')->leftJoin('system_sms', function ($join) {
                    $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
                })->where('system_sms_receive.deleted', "0")
                ->where('system_sms_receive.recipients', $userId)
                ->groupBy('system_sms.sms_menu')->get();
        if(count($sourceMaxSmsId) > 0) {
           return $sourceMaxSmsId->mapWithKeys(function($item) {
                return [$item->module => $item->max_sms_id];
            });
        }
        return [];
    }

    public function getSmsRead($userId, $smsId)
    {
        return $this->entity->where('sms_id', $smsId)->where('recipients', $userId)->first();
    }

    public function moduleToReadSms($smsParam) {
        if (empty($smsParam['search'])) {
            return true;
        }
        return $this->entity->leftJoin('system_sms', function ($join) use($smsParam) {
                    $join->on("system_sms.sms_id", '=', 'system_sms_receive.sms_id');
                })->multiwheres($smsParam['search'])->update(['remind_flag' => 1]);
    }

}
