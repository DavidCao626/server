<?php

namespace App\EofficeApp\Email\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Email\Entities\EmailReceiveEntity;
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
class EmailReceiveRepository extends BaseRepository
{

    public function __construct(EmailReceiveEntity $entity)
    {
        parent::__construct($entity);
    }

    //未读数
    public function getUnreadOherFilefolderMailCout($user_id, $box_id)
    {

        $query = $this->entity;
        $query = $query->select(['email_receive.email_id']);
        $query = $query->leftJoin('email', function ($join) {
            $join->on("email_receive.email_id", '=', 'email.email_id');
        });

        $query = $query->where("email_receive.recipients", "=", $user_id)
            ->where("email_receive.read_flag", 0)
            ->where("email_receive.deleted", 0)
        // ->where("email.from_id", "!=","systemAdmin")
            ->where("email.send_flag", 1)
            ->where("email_receive.box_id", $box_id)
            ->groupBy('email.email_id');

        $result = $query->get();

        return count($result);
    }

    //总的封数
    public function getOherFilefolderMailCout($user_id, $box_id)
    {
        $query = $this->entity;
        $query = $query->select(['email_receive.email_id']);
        $query = $query->leftJoin('email', function ($join) {
            $join->on("email_receive.email_id", '=', 'email.email_id');
        });

        $query = $query->where("email_receive.recipients", "=", $user_id)
            ->where("email_receive.deleted", 0)
            ->where("email.send_flag", 1)
        // ->where("email.from_id", "!=","systemAdmin")
            ->where("email_receive.box_id", $box_id)
            ->groupBy('email.email_id');

        $result = $query->get();
        return count($result);
    }

    public function transferEmail($where, $data)
    {
        //这里更新box_id
        return $this->entity->wheres($where)->update($data);
    }

    /**
     * 标记已读或还原未读
     * @param string $ids email_receive的id来
     * @param bool $setRead 标记已读或未读
     * @return mixed
     */
    public function readEmail($ids, $setRead = true)
    {
        $ids        = explode(",", $ids);
        $emailLists = $this->entity->whereIn('id', $ids)->get();
        $emailIds   = $emailIdsTemp   = $recipientsTemp   = [];
        if (!empty($emailLists)) {
            foreach ($emailLists as $key => $value) {
                $emailIdsTemp[]   = $value->email_id;
                $recipientsTemp[] = $value->recipients;
            }
            $lists = $this->entity->whereIn('email_id', $emailIdsTemp)->whereIn('recipients', $recipientsTemp)->get();
            foreach ($lists as $i => $list) {
                foreach ($emailLists as $key => $value) {
                    if ($list->email_id == $value->email_id && $list->recipients == $value->recipients) {
                        $emailIds[] = $list->id;
                    }
                }
            }
        }
        if ($setRead) {
            $this->entity->whereIn('id', $emailIds)
                ->where('read_flag', 0)
                ->update([
                    'read_flag' => 1,
                    'read_time' => date('Y-m-d H:i:s')
                ]);
        } else {
            $this->entity->whereIn('id', $emailIds)
                ->where('read_flag', 1)
                ->update([
                    'read_flag' => 0,
//                    'read_time' =>'' 不清空时间，可区分是否是被还原的未读
                ]);
        }

    }

    public function readEmailWay1($emailId, $userId)
    {
        return $this->entity
            ->where('recipients', $userId)
            ->where('email_id', $emailId)
            ->update([
                'read_flag' => 1,
                'read_time' => date('Y-m-d H:i:s')
            ]);
    }

    public function getEmailId($where)
    {
        $result = $this->entity->wheres($where)->first();
        if (empty($result)) {
            return -1;
        }
        return $result->id;
    }

    //删除box_id时回收对应的邮件
    public function recycleByWhere($where)
    {
        $data = [
            'box_id' => 0,
        ];
        return $this->entity->wheres($where)->update($data);
    }

    public function deleteEmail($where)
    {
        $data = [
            'deleted' => 1,
        ];
        return $this->entity->wheres($where)->update($data);
    }

    public function getOneEmail($email_id, $user_id)
    {
        return $this->entity
        //->where("deleted", 0)
            ->where("email_id", $email_id)->where("recipients", $user_id)->get()->toArray();
    }

    public function getRecipients($email_id, $doSort = false)
    {
        //把用户加进来
        $recipients = $this->entity->select(['email_receive.*', 'user.user_name', 'user.list_number'])->leftJoin('user', function ($join) {
            $join->on("email_receive.recipients", '=', 'user.user_id');
        })->where("email_id", $email_id)->withTrashed()->get()->toArray();
        $doSort && $this->sortByUserListNumber($recipients);
        return $recipients;
    }

    public function getGroupRecipients($email_id)
    {
        //获取分组用户 显示在列表上 不重复
        return $this->entity->select(['email_receive.recipients', 'user.user_name','user.list_number'])->leftJoin('user', function ($join) {
            $join->on("email_receive.recipients", '=', 'user.user_id');
        })->where("email_id", $email_id)->groupBy("recipients")
        ->withTrashed()
        ->get()->toArray();
    }

    public function getEmailsByWhere($where)
    {
        return $this->entity
        //->where("deleted", 0)
            ->wheres($where)->get()->toArray();
    }


    public static function buildQuery(array $params = [], Builder $query = null): Builder
    {
        $query = $query ?: EmailReceiveEntity::query();
        if (Arr::has($params, 'email_id')) {
            $query->where('email_id', $params['email_id']);
        }
        if (Arr::has($params, 'read_flag')) {
            $query->where('read_flag', $params['read_flag']);
        }
        if (Arr::has($params, 'ids')) {
            $query->whereIn('id', $params['ids']);
        }

        return $query;
    }

    /**
     * 读取数量
     * @param $emailId
     * @param null|1|0 $readFlag null:总数 1:已读数 0:未读数
     * @return int
     */
    public static function readCount($emailId, $readFlag = null)
    {
        $query = self::buildQuery(['email_id' => $emailId]);
        if (!is_null($readFlag)) {
            $query->where('read_flag', $readFlag);
        }
        return $query->count();
    }

    /**
     * 构建可以被标记星标的query
     * @param $userId
     * @param array|int $ids
     * @param $starFlag 0|1
     * @param null $query
     * @return Builder
     */
    public static function buildCanStarQuery($userId, $ids = null, $starFlag = null, $query = null): Builder
    {
        $query = $query ?: self::buildQuery();
        if (!is_null($ids)) {
            is_array($ids) ? $query->whereIn('id', $ids) : $query->where('id', $ids);
        }
        if (!is_null($starFlag)) {
            $query->where('star_flag', $starFlag);
        }
        $query->where('recipients', $userId)
            ->where('deleted', 0);

        return $query;
    }

    public static function starCount($userId, $boxId = 0) {
        return self::buildCanStarQuery($userId, null, 1)
            ->where('box_id', $boxId)->count();
    }

    public function getEmailReceiveList($emailId, $params = []) {
        $search = Arr::get($params, 'search', []);
        $search['email_id'] = [$emailId];
        $query = $this->entity->newQuery()->wheres($search)
            ->with('user:user_name,user_id')
            ->select('id', 'recipients', 'receive_type', 'read_time', 'read_flag');
        if (isset($params['order_by'])) {
            $query->orders($params['order_by']);
        }
        return $query;
    }

    // 根据用户序号与用户id正序排序
    private function sortByUserListNumber(&$recipients)
    {
        if ($recipients) {
            if($recipients && is_array($recipients)){
                foreach ($recipients as $item){
                    $sort[]=$item["list_number"] ? : 0;
                    $sort_user_id[]=$item["recipients"];
                }
            }
            array_multisort($sort,SORT_ASC,$sort_user_id,SORT_ASC,$recipients);
        }
    }
}
