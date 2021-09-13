<?php

namespace App\EofficeApp\Email\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Email\Entities\EmailEntity;
use App\Utils\Utils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
/**
 * 邮件资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class EmailRepository extends BaseRepository
{

    public function __construct(EmailEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取邮件的详细by email_id
     *
     * @param int $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     *
     */
    public function getEmailInfo($id)
    {
        $emailResult = $this->entity->newQuery()->select(['email.*', 'user.user_name', 'user_role.role_id', 'system_reminds.id as sid', 'system_reminds.remind_menu'])->leftJoin('user', function ($join) {
            $join->on("email.from_id", '=', 'user.user_id');
        })->leftJoin('user_role', function ($join) {
            $join->on("email.from_id", '=', 'user_role.user_id');
        })->leftJoin('system_reminds', function ($join) {
            $join->on("email.subject", '=', 'system_reminds.remind_content');
        })->where('email_id', $id)->get()->toArray();
        return $emailResult;
    }

    /**
     * 获取草稿箱，已发件箱删除邮件 by email_id
     *
     * @param int $id
     *
     * @return array
     *
     * @author zhangwei
     *
     * @since 2018-03-23
     *
     */
    public function getEmailStatus($email_id,$user_id){
        return $this->entity->newQuery()->where(function ($query) use(&$email_id,&$user_id){
            $query->where('deleted',1)->where('email_id',$email_id)->where('from_id',$user_id);
        })->get()->toArray();
    }

    //归1 假删除
    public function deleteEmail($param)
    {
        $data = [
            'deleted' => 1,
        ];
        return $this->entity->newQuery()->wheres($param)->update($data);
    }

    //发件箱
    public function getOutEmailNum($user_id)
    {

        $query = $this->entity->newQuery();
        $query = $query->select(['email.email_id'])
            ->where("email.from_id", "=", $user_id)
            ->where("email.deleted", 0)
            ->where("email.send_flag", 1);
        $result = $query->count();

        return $result;
    }

    //草稿箱
    public function getTempEmailNum($user_id)
    {
        $query = $this->entity->newQuery();
        $query = $query->select(['email.email_id'])
            ->where("email.from_id", "=", $user_id)
            ->where("email.deleted", 0)
            ->where("email.send_flag", 0);
        $result = $query->count();
        return $result;
    }

    //已删除
    public function gettrashEmailNum($user_id)
    {

        $trashIds = $this->getTrashEmailIds($user_id);
        return count($trashIds);
    }

    public function getTrashEmailIds($user_id)
    {

        $this->user_id = $user_id;
        $temp1         = $this->entity->newQuery()->select(['email.email_id'])->where('email.from_id', $this->user_id)
            ->where('email.deleted', 1)->get();
        $temp2 = $this->entity->newQuery()->select(['email.email_id'])->where('send_flag', 1);
        $this->leftJoinEmailReceive($temp2);
        $temp2 = $temp2->where('email_receive.recipients', $this->user_id)
            ->where('email_receive.deleted', 1)->get();

        $emailIds = [];
        foreach ($temp1 as $t1) {
            $emailIds[] = $t1->email_id;
        }
        foreach ($temp2 as $t2) {
            $emailIds[] = $t2->email_id;
        }

        return array_unique($emailIds);
    }

    /**
     * 收件箱数目
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function inBoxAllTotal($param)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity->newQuery()->select(['email.email_id']);
        $this->leftJoinEmailReceive($query);
        $lists = $query->wheres($param['search']) //前端搜索条件
            ->where("email_receive.recipients", "=", $param['user_id'])
            ->where("email.send_flag", 1)
            ->where("email_receive.deleted", 0)
            ->where("email_receive.box_id", 0)
            ->groupBy("email.email_id")
            ->get();
        return count($lists);
    }

    /**
     * 收件箱记录
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function inBoxAllList($param, $returnIds = false)
    {

        $default = [
            'fields'   => ['email.email_id', 'email.from_id', 'email.send_flag', 'email.subject','email.send_time', 'email_receive.read_flag', 'email_receive.id', 'user_name', 'star_flag'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['email.send_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity->newQuery();
        $query = $query->select($param['fields']);
        $this->leftJoinEmailReceive($query);
        $query->leftJoin('user', function ($join) {
            $join->on("user.user_id", '=', 'email.from_id');
        });
        $query = $query
            ->wheres($param['search']) //前端搜索条件
            ->where("email_receive.recipients", "=", $param['user_id'])
            ->where("email.send_flag", 1)
            // ->where("email.from_id", "!=", "systemAdmin")
            ->where("email_receive.deleted", 0)
            ->where("email_receive.box_id", 0)
            ->groupBy("email.email_id");

        $query->orders($param['order_by'])->parsePage($param['page'], $param['limit']);

        return $this->getListOrEmailIds($query, $returnIds);
    }

    /**
     * 草稿箱数目
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function tempBoxAllTotal($param)
    {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity->newQuery();
        $query = $query->select(['email.email_id']);
        $query = $query
            ->wheres($param['search']) //前端搜索条件
            ->where("email.from_id", "=", $param['user_id'])
            ->where("email.send_flag", 0)
            ->where("email.deleted", 0);

        $result = $query->count();

        return $result;
    }

    /**
     * 草稿箱记录
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function tempBoxAllList($param, $returnIds = false)
    {
        $default = [
            'fields'   => ['email.*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['email.send_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        //if(orderby == ")
        $query = $this->entity->newQuery();
        $query = $query->select($param['fields']);
        $this->leftJoinEmailReceive($query);
        $query->wheres($param['search']) //前端搜索条件
            ->where("email.from_id", "=", $param['user_id'])
            ->where("email.send_flag", 0)
            ->where("email.deleted", 0)->groupBy("email_id");

        $query->orders($param['order_by'])->parsePage($param['page'], $param['limit']);

        return $this->getListOrEmailIds($query, $returnIds);
    }

    /**
     * 已删除列表
     */
    public function trashBoxAllList($param, $returnIds = false)
    {

        $default = [
            'fields'   => ['email.*', 'email_receive.read_flag', 'email_receive.id', 'user_name'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['email.send_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        $this->user_id = $param["user_id"];

        $trashIds = $param["trash_ids"];

        $query = $this->entity->newQuery();
        $query = $query->select($param['fields']);
        $this->leftJoinEmailReceive($query, $param["user_id"]);
        $query->leftJoin('user', function ($join) {
            $join->on("user.user_id", '=', 'email.from_id');
        });
        $query->wheres($param['search']) //前端搜索条件
            ->whereIn("email.email_id", $trashIds)
            ->groupBy("email.email_id")
            ->orders($param['order_by'])->parsePage($param['page'], $param['limit']);

        return $this->getListOrEmailIds($query, $returnIds);
    }

    public function trashBoxAllTotal($param)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));

        $this->user_id = $param["user_id"];

        $trashIds = $param["trash_ids"];

        $query = $this->entity->newQuery();
        $query = $query->select(['email.email_id']);
        $this->leftJoinEmailReceive($query);
        $count = $query->wheres($param['search']) //前端搜索条件
            ->whereIn("email.email_id", $trashIds)
            ->groupBy("email.email_id")->get();
        return count($count);
    }

    /**
     * 发件箱数目
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function outBoxAllTotal($param = [])
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->newQuery();
        $query = $query->select(['email.email_id']);

        $hasSearchUser = isset($param['search']['recipients']);
        if ($hasSearchUser) {
            $this->leftJoinEmailReceive($query);
            $query->groupBy("email_id");
        }

        $query->wheres($param['search']) //前端搜索条件
            ->where("email.from_id", "=", $param['user_id'])
            ->where("email.send_flag", 1)
            ->where("email.deleted", 0);

        $result = $hasSearchUser ? $query->get()->count() : $query->count();

        return $result;
    }

    /**
     * 发件箱记录
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function outBoxAllList($param, $returnIds = false)
    {

        $default = [
            'fields'   => ['email.*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['email.send_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity->newQuery();
        $query = $query->select($param['fields']);

        $this->leftJoinEmailReceive($query);
        $query->wheres($param['search']) //前端搜索条件
            ->where("email.from_id", "=", $param['user_id'])
            ->where("email.send_flag", 1)
            ->where("email.deleted", 0)
            ->groupBy("email_id");

        $query->orders($param['order_by'])->parsePage($param['page'], $param['limit']);

        return $this->getListOrEmailIds($query, $returnIds);
    }

    public function emailListsTotal($param)
    {

        $this->user_id = $param['user_id'];
        $default       = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity->newQuery()->select(['email.email_id']);
        if (isset($param['send_time']) && !empty($param['send_time'])) {
            $dateTime = json_decode($param['send_time'], true);
            $end      = date('Y-m-d', strtotime('+1 day', strtotime($dateTime['endDate'])));
            $query    = $query->whereBetween('send_time', [$dateTime['startDate'], $end]);
        }

        if (isset($param['email_box']) && !empty($param['email_box'])) {
            switch ($param['email_box']) {
                case "-1":
                    //收件箱
                    $query = $query->where('recipients', $param['user_id'])->where('send_flag', 1)->where('email_receive.deleted', "0")->where('box_id', 0);
                    break;
                case "-2":
                    //草稿箱
                    $query = $query->where('from_id', $param['user_id'])->where('email.deleted', "0")->where('send_flag', 0);
                    break;
                case "-3":
                    //发件箱
                    $query = $query->where('from_id', $param['user_id'])->where('email.deleted', "0")->where('send_flag', 1);
                    break;
                default:
                    $query = $query->where('recipients', $param['user_id'])->where('send_flag', 1)->where('email_receive.deleted', "0")->where('box_id', $param['email_box']);
                    break;
            }
        }

        if (isset($param['attatment']) && !empty($param['attatment'])) {
            $query = $query->where('attachment_name', 'like', "%" . $param['attatment'] . "%");
        }
        if (isset($param['content']) && !empty($param['content'])) {
            $query = $query->where('content', 'like', "%" . $param['content'] . "%");
        }
        if (isset($param['email_status']) && !empty($param['email_status'])) {
            switch ($param['email_status']) {
                case "all":
                    break;
                case "unread":
                    $query = $query->where('read_flag', 0);
                    break;
                case "read":
                    $query = $query->where('read_flag', 1);
                    break;
            }
        }
        if (isset($param['subject']) && !empty($param['subject'])) {
            $query = $query->where('subject', 'like', "%" . $param['subject'] . "%");
        }

        if (isset($param['from_id']) && !empty($param['from_id'])) {

            if ($param['from_id'] == "all") {
                $param['from_id'] = Utils::getUserIds();
            }

            $from_arrary = explode(",", $param['from_id']);
            $query->whereIn('from_id', $from_arrary);
        }

        if (isset($param['recipients']) && !empty($param['recipients'])) {
            if ($param['recipients'] == "all") {
                $param['recipients'] = Utils::getUserIds();
            }
            $recipients_arrary = explode(",", $param['recipients']);
            $query->whereIn('recipients', $recipients_arrary);
        }

        $this->leftJoinEmailReceive($query);
        $rs = $query->leftJoin('user', function ($join) {
            $join->on("email.from_id", '=', 'user.user_id');
        })
            ->wheres($param['search'])

        //->where("email.from_id", "!=", "systemAdmin")
            ->where(function ($query) {
                $query->orWhere('email_receive.recipients', $this->user_id)
                    ->orWhere('email.from_id', $this->user_id);
            })->orders($param['order_by'])
            ->groupBy('email.email_id')
            ->get();



        return count($rs);
    }

    public function emailLists($param)
    {

        $this->user_id = $param['user_id'];
        $default       = [
            'fields'   => ['email.email_id', 'email.from_id', 'email.send_flag', 'email.subject', 'email.send_time', 'recipients', 'read_flag', 'receive_type', 'user_name', 'email_receive.id',],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['send_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->newQuery();

        if (isset($param['send_time']) && !empty($param['send_time'])) {
            $dateTime = json_decode($param['send_time'], true);
            $end      = date('Y-m-d', strtotime('+1 day', strtotime($dateTime['endDate'])));
            $query    = $query->whereBetween('send_time', [$dateTime['startDate'], $end]);
        }

        if (isset($param['email_box']) && !empty($param['email_box'])) {
            switch ($param['email_box']) {
                case "-1":

                    //收件箱
                    $query = $query->where('recipients', $param['user_id'])->where("send_flag", 1)->where('email_receive.deleted', "0")->where('box_id', 0);
                    break;
                case "-2":
                    //草稿箱
                    $query = $query->where('from_id', $param['user_id'])->where('email.deleted', "0")->where('send_flag', 0);
                    break;
                case "-3":
                    //发件箱
                    $query = $query->where('from_id', $param['user_id'])->where('email.deleted', "0")->where('send_flag', 1);
                    break;
                default:

                    $query = $query->where('recipients', $param['user_id'])->where("send_flag", 1)->where('email_receive.deleted', "0")->where('box_id', $param['email_box']);
                    break;
            }
        }

        if (isset($param['content']) && !empty($param['content'])) {
            $query = $query->where('content', 'like', "%" . $param['content'] . "%");
        }
        if (isset($param['email_status']) && !empty($param['email_status'])) {
            switch ($param['email_status']) {
                case "all":
                    break;
                case "unread":
                    $query = $query->where('read_flag', 0);
                    break;
                case "read":
                    $query = $query->where('read_flag', 1);
                    break;
            }
        }
        if (isset($param['subject']) && !empty($param['subject'])) {
            $query = $query->where('subject', 'like', "%" . $param['subject'] . "%");
        }

        if (isset($param['from_id']) && !empty($param['from_id'])) {
            if ($param['from_id'] == "all") {
                $param['from_id'] = Utils::getUserIds();
            }
            $from_arrary = explode(",", $param['from_id']);
            $query->whereIn('from_id', $from_arrary);
        }

        if (isset($param['recipients']) && !empty($param['recipients'])) {
            if ($param['recipients'] == "all") {
                $param['recipients'] = Utils::getUserIds();
            }
            $recipients_arrary = explode(",", $param['recipients']);
            $query->whereIn('recipients', $recipients_arrary);
        }
        $this->leftJoinEmailReceive($query);
        return $query->select($param['fields'])->leftJoin('user', function ($join) {
            $join->on("email.from_id", '=', 'user.user_id');
        })->wheres($param['search'])->where(function ($query) {
            $query->orWhere('email_receive.recipients', $this->user_id)->orWhere('email.from_id', $this->user_id);
        })->orders($param['order_by'])
            ->groupBy('email.email_id')
            ->parsePage($param['page'], $param['limit'])
            ->get()->toArray();
    }

    /**
     * 个人文件夹数目
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function selfFileBoxAllTotal($param)
    {

        if ($param['box_id'] <= 0) {
            return 0;
        }

        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->newQuery();
        $query = $query->select(['email.email_id']);
        $this->leftJoinEmailReceive($query);
        $query = $query
            ->wheres($param['search']) //前端搜索条件
            ->where("email_receive.recipients", "=", $param['user_id'])
            ->where("email.send_flag", 1)
            ->where("email_receive.deleted", 0)
            // ->where("email.from_id", "!=", "systemAdmin")
            ->where("email_receive.box_id", "=", $param['box_id'])
            ->groupBy('email.email_id')
            ->get();

        return count($query);
    }

    /**
     * 个人文件夹记录
     *
     * @param type $param
     *
     * @return array
     *
     * @author yuwei
     *
     * @since 2015-10-21
     */
    public function selfFileBoxAllList($param, $returnIds = false)
    {

        if ($param['box_id'] <= 0) {
            return [];
        }

        $default = [
            'fields'   => ['email.*', 'email_receive.read_flag', 'email_receive.star_flag', 'email_receive.id', 'user_name','email.contentParam'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['email.send_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity->newQuery();
        $query = $query->select($param['fields']);
        $this->leftJoinEmailReceive($query);
        $query->leftJoin('user', function ($join) {
            $join->on("user.user_id", '=', 'email.from_id');
        });
        $query = $query
            ->wheres($param['search']) //前端搜索条件
            ->where("email_receive.recipients", "=", $param['user_id'])
            ->where("email.send_flag", 1)
            // ->where("email.from_id", "!=", "systemAdmin")
            ->where("email_receive.deleted", 0)
            ->where("email_receive.box_id", "=", $param['box_id'])
            ->groupBy('email.email_id');

        $query->orders($param['order_by'])->parsePage($param['page'], $param['limit']);

        return $this->getListOrEmailIds($query, $returnIds);
    }

    private function leftJoinEmailReceive($query, $userId = null)
    {
        $query->leftJoin('email_receive', function ($join) use ($userId) {
            $join->on('email_receive.email_id', '=', 'email.email_id')
                ->whereNull('email_receive.deleted_at');
            // 回收站中包含收件箱与发件箱，发件箱是没有这条数据的，所以需要在join内部写这个过滤，在外面写会导致整条数据消失。
            if ($userId) {
                $join->where('email_receive.recipients', $userId);
            }
        });
    }

    public static function buildQuery($params, $query = null): Builder
    {
        $query = $query ?: EmailEntity::query();
        if (Arr::has($params, 'email_id')) {
            $query->where('email_id', $params['email_id']);
        }
        if (Arr::has($params, 'from_id')) {
            $query->where('from_id', $params['from_id']);
        }

        return $query;
    }

    private function getListOrEmailIds($query, $returnIds)
    {
        if ($returnIds) {
            return $query->pluck('email_id')->toArray();
        } else {
            return $query->get()->toArray();
        }
    }

    public function getEmailListIds($boxId, $params = [])
    {
        switch ($boxId) {
            case -1:
                return $this->inBoxAllList($params, true);
            case -2:
                return $this->tempBoxAllList($params, true);
            case -3:
                return $this->outBoxAllList($params, true);
            case -4:
                $tempTrashIds = $this->getTrashEmailIds($params["user_id"]);
                $params['trash_ids'] = $tempTrashIds ?: [0];
                return $this->trashBoxAllList($params, true);
            default:
                return $this->selfFileBoxAllList($params, true);
        }
    }

    // 获取某用户的收件箱邮件与发件箱邮件id
    public static function getUserEmailIds($userId) {
        $receiveIds = EmailReceiveRepository::buildQuery()
            ->where('recipients', $userId)
            ->where('deleted', 0)
            ->pluck('email_id')
            ->toArray();
        // 过滤未发送
        $receiveIds = self::buildQuery([])
            ->whereIn('email_id', $receiveIds)
            ->where('send_flag', 1)
            ->pluck('email_id')
            ->toArray();
        $myEmailIds = self::buildQuery([])
            ->where('from_id', $userId)
            ->where('deleted', 0)
            ->pluck('email_id')
            ->toArray();

        $emailIds = array_unique(array_merge($receiveIds, $myEmailIds));
        $emailIds = array_values($emailIds);

        return $emailIds;
    }
}
