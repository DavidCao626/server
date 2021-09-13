<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailShareConfigEntity;

/**
 * 邮件共享权限设置Repository类
 */
class WebmailShareConfigRepository extends BaseRepository
{
    public function __construct(WebmailShareConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @since  2019-08-30
     */
    public function getList(array $param = [])
    {
        $default = [
            'fields' => ['*'],
            'search' => [],
            'page' => 1,
            'limit' => config('eoffice.pagesize'),
            'order_by' => [],
        ];

//        $param = array_filter($param, function ($var) {
//            return $var !== '';
//        });
        $param = array_merge($default, $param);
        $query = $this->entity
            ->select($param['fields'])
            ->with(['outbox' => function ($query) {
                $query->select(['webmail_share_config_relation_outbox.*', 'webmail_outbox.account'])->leftJoin('webmail_outbox', 'webmail_share_config_relation_outbox.outbox_id', '=', 'webmail_outbox.outbox_id');
            }]);

        $query = $this->getParseWhere($query, $param['search']);

        $list = $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
        $folderNames = [
            'sent' => trans('webmail.hair_box'),
            'inbox' => trans('webmail.inbox')
        ];
        foreach ($list as $key =>$val) {
            // 是否全体人员
            $purview = json_decode($val['purview'], 1);
            $list[$key]['all_staff'] = $purview['all_staff'] == 1 ? 1 : 0;
            // 哪些邮箱
            $list[$key]['outboxs'] = implode(',', array_unique(array_column($val['outbox'], 'account')));
            $folders = array_unique(array_column($val['outbox'], 'folder'));
            foreach ($folders as $folderKey => $folder){
                $folders[] = $folderNames[$folder];
                unset($folders[$folderKey]);
            }
            $list[$key]['folders'] = implode(',', $folders);
        }
        return $list;
    }

    /**
     * 获取数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getNum(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getParseWhere($this->entity, $where)->count();
    }

    /**
     * 条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getParseWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }

    public function getShareconfigPremission($outbox, $mail, $user)
    {
        $configs = $this->entity->leftJoin('webmail_share_config_relation_outbox', 'id', '=', 'config_id')->where(['outbox_id' => $outbox->outbox_id, 'folder' => $mail->folder])->get()->toArray();
        if ($configs){
            foreach ($configs as $config) {
                if ($config['enable'] == 0) {
                    return false;
                }
                $purview = json_decode($config['purview'], 1);
                if($purview['all_staff'] == 1){
                    return true;
                } else {
                    $userIds = $purview['user_id'] ?? [];
                    $deptIds = $purview['dept_id'] ?? [];
                    $roleIds = $purview['role_id'] ?? [];
                    if (in_array($user['user_id'], $userIds) || in_array($user['dept_id'], $deptIds) || array_intersect($user['role_id'], $roleIds)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
