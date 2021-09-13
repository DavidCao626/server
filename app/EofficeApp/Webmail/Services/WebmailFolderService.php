<?php


namespace App\EofficeApp\Webmail\Services;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Webmail\Repositories\WebmailFolderRepository;
use App\EofficeApp\Webmail\Repositories\WebmailMailRepository;
use App\Jobs\EmailJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class WebmailFolderService extends BaseService
{
    public $email;
    public $webmailFolderRepository;
    public $webmailService;
    public $webmailMailRepository;
    const SYSTEMFOLDERS = ['inbox', 'drafts', 'junk', 'rubbishs', 'sent messages', 'deleted messages', '寄件備份'];
    const SYSTEMFOLDERS163 = ['草稿箱', '垃圾邮件', '已删除', '已发送', '病毒文件夹'];
    const SYSTEMFOLDERALIAS = ['inbox', 'draft', 'trash', 'sent', 'deleted'];
    const DRAFTFOLDERS = ['drafts', '草稿箱', '草稿'];
    const TRASHFOLDERS = ['垃圾邮件', 'trash', 'junk', 'rubbishs'];
    const DELETEDFOLDERS = ['deleted messages', '已删除', '刪除的郵件'];
    const SENTFOLDERS = ['sent messages', '已发送', '寄件備份'];
    public function __construct() {
        parent::__construct();
        $this->email                     = 'App\Utils\Email';
        $this->webmailFolderRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailFolderRepository';
        $this->webmailService = 'App\EofficeApp\Webmail\Services\WebmailService';
        $this->webmailMailRepository = 'App\EofficeApp\Webmail\Repositories\WebmailMailRepository';
    }

    public function getListByServer($param)
    {
        $outboxId = $param['outbox_id'] ?? '';
        if (Cache::get('get_list_by_server'.$outboxId)) {
            return ['code' => ['getting_list_by_server', 'webmail']];
        }
        Cache::add('get_list_by_server'.$outboxId, 1);
        /**
         * @var WebmailService $webmailService
         */
        $webmailService = app($this->webmailService);
        $inboxServer = $webmailService->getInboxServer($outboxId, '');
        if (isset($inboxServer['code'])) {
            Cache::forget('get_list_by_server'.$outboxId);
            return $inboxServer;
        }
        try{
            $folders = app($this->email)->getWebMailFolders($inboxServer);
            Cache::forget('get_list_by_server'.$outboxId);
            return $folders;
        }catch (\Exception $e){
            $message = $webmailService->handleMessage($e->getMessage(), 'receive');
            Cache::forget('get_list_by_server'.$outboxId);
            return ['code' => ['', $inboxServer['account'] . $message], 'dynamic' => $inboxServer['account'] . $message];
        }
    }

    public function getListByDatabase($param)
    {
        /** @var WebmailFolderRepository $webmailFolderRepository */
        $webmailFolderRepository = app($this->webmailFolderRepository);
        $param['is_email'] = 1;
        $returnType = $param['returnType'] ?? '';
        unset($param['returnType']);
        $list = $webmailFolderRepository->getFieldInfo($param);
        if (!$list) {
            $list = $this->getListByServer($param);
            if (!isset($list['code'])){
                $res = $this->saveFolders($list, $param['outbox_id']);
                if ($res) {
                    $list = $this->getListByDatabase($param);
                } else {
                    return ['code' => ['get_folder_failed', 'webmail']];
                }
            } else {
                return $list;
            }
        }
        if ($returnType == 'category') {
            $outbox = app($this->webmailService)->getOutbox($param['outbox_id']);
            $folders = [
                'folder_id' => 0,
                'folder_name' => $outbox['account'],
                'alias' => $outbox['account'],
                'has_children' => 1,
                'can_delete' => 0,
                'can_add' => 1,
                'children' => []
            ];
            $folderSort = [
                'inbox' => 0,
                'sent' => 1,
                'draft' => 2,
                'trash' => 3,
                'deleted' => 4
            ];
            $otherSort = 5;
            $others = [];
            foreach ($list as $value) {
                $value['can_delete'] = 1;
                $value['can_add'] = 0;
                if (in_array(strtolower($value['folder_name']), self::SYSTEMFOLDERS) || in_array(strtolower($value['folder_name']), self::SYSTEMFOLDERS163)) {
                    $value['can_delete'] = 0;
                    $value['can_add'] = 0;
                }
                $valueArray = explode('/', $value['folder_name']);
                if (count($valueArray) > 1) {
                    $children = [
                        'folder_id' => $value['folder_id'],
                        'folder_name' => $valueArray[1],
                        'alias' => $valueArray[1],
                        'has_children' => 0,
                        'can_delete' => 1,
                        'can_add' => 0
                    ];
                    if ($valueArray[1] == 'QQ邮件订阅') {
                        $children['can_delete'] = 0;
                    }
                    if (isset($others[$valueArray[0]])) {
                        $others[$valueArray[0]]['children'][] = $children;
                    } else {
                        $folder = [
                            'folder_id' => $value['folder_id'],
                            'folder_name' => $valueArray[0],
                            'alias' => $valueArray[0],
                            'has_children' => 1,
                            'can_delete' => 0,
                            'can_add' => 1
                        ];
                        $folder['children'][] = $children;
                        $others[$valueArray[0]] = $folder;
                    }
                } else {
                    $folder = $value;
                    $folder['has_children'] = 0;
//                    $folders['children'][] = $folder;
                    if (isset($folderSort[$value['folder_name_alias']])){
                        $folders['children'][$folderSort[$value['folder_name_alias']]] = $folder;
                    } else {
                        $folders['children'][] = $folder;
                        $otherSort++;
                    }
                }
            }
            array_map(function($value) use(&$folders){
                $folders['children'][] = $value;
            }, $others);
            mult_array_sort($folders['children'], 'has_children', SORT_ASC);
            ksort($folders['children']);
//            $folders['children'] = array_values($folders['children']);
            return $folders;
        } else {
            return $list;
        }
    }

    public function addWebmailEmailFolders($outboxId)
    {
        $folders = $this->getListByServer(['outbox_id' => $outboxId]);
        if (!isset($folders['code'])){
            $res = $this->saveFolders($folders, $outboxId);
            if ($res) {
                return true;
            } else {
                return ['code' => ['save_failed', 'webmail']];
            }
        } else {
            return $folders;
        }
    }

    /** 更新邮箱文件夹
     * @param $outboxId
     * @param $userId
     * @return array
     */
    public function updateWebmailFolders($outboxId, $userId)
    {
        $param = ['outbox_id' => $outboxId];
        $folders = $this->getListByServer($param);
//        \Log::info('$folders'. json_encode($folders));
        if (!isset($folders['code'])){
            $old = $this->getListByDatabase($param);
            $oldServers = array_column($old, 'server');
//            \Log::info('old'. json_encode($oldServers));
            $newDiff = array_diff($folders, $oldServers);
//            \Log::info('$newDiff'. json_encode($newDiff));
            $oldDiff = array_diff($oldServers, $folders);
//            \Log::info('$oldDiff'. json_encode($oldDiff));
            // 邮箱服务器端有新增
            if ($newDiff) {
                $this->saveFolders($newDiff, $outboxId);
            }
            // 邮箱服务器端有删除 -- 删除邮箱 对应邮件转入收件箱
            if ($oldDiff) {
                $oldDiffFolders = app($this->webmailFolderRepository)->getFieldInfo([], [],['server' => [$oldDiff, 'in']]);
//                \Log::info('$oldDiffFolders'. json_encode($oldDiffFolders));
                $this->deleteWebmailFolder(array_column($oldDiffFolders, 'folder_id'), $outboxId);
            }
        } else {
            return ['code' => ['get_folder_failed', 'webmail']];
        }
    }

    /** 保存某个邮箱的文件夹
     * @param $emailFolders
     * @param $outboxId
     * @return bool
     */
    public function saveFolders($emailFolders, $outboxId)
    {
        /** @var WebmailFolderRepository $webmailFolderRepository */
        $webmailFolderRepository = app($this->webmailFolderRepository);
        $data = [];
        foreach ($emailFolders as $emailFolder) {
            $name = $this->handleName($emailFolder);
            $data[] = [
                'outbox_id' => $outboxId,
                'server' => $emailFolder,
                'folder_name' => $name[0],
                'folder_name_alias' => $name[2],
                'alias' => $name[1],
                'is_email' => 1,
                'folder_create_time' => date('Y-m-d H:i:s')
            ];
        }
//        \Log::info($data);
        if ($data) {
            $webmailFolderRepository->insertMultipleData($data);
            return true;
        } else {
            return false;
        }
    }

    /** 删除某个邮箱文件夹
     * @param $folderId
     * @param $outboxId
     * @return bool
     */
    public function deleteWebmailFolder($folderId, $outboxId)
    {
        /** @var WebmailFolderRepository $webmailFolderRepository */
        $webmailFolderRepository = app($this->webmailFolderRepository);
        $res = $webmailFolderRepository->deleteByWhere(['folder_id' => [$folderId, 'in']]);
        if ($res) {
            /** @var WebmailMailRepository $webmailMailRepository */
            $webmailMailRepository = app($this->webmailMailRepository);
            $webmailMailRepository->updateData(['folder_id' => 'inbox'], ['outbox_id' => $outboxId, 'folder_id' => $folderId]);
        }
        return $res;
    }

    /** 邮箱名称及别名处理
     * @param $emailFolder
     * @return array
     */
    public function handleName($emailFolder)
    {
        $folder = explode('}', $emailFolder);
        $name = $folder[1];
        $folderAlias = $alias = strtolower($name);
        if ($alias == 'inbox') {
            $alias = trans('webmail.inbox');
        } else if(in_array($alias, self::DRAFTFOLDERS)) {
            if (in_array($alias, ['drafts', '草稿箱'])) {
                $alias = trans('webmail.drafts');
            } else {
                $alias = $name;
            }
            $folderAlias = 'draft';
        } else if(in_array($alias, self::TRASHFOLDERS)) {
            if (in_array($alias, ['junk', 'rubbishs'])) {
                $alias = trans('webmail.dustbin');
            } else {
                $alias = $name;
            }
            $folderAlias = 'trash';
        } else if(in_array($alias, self::SENTFOLDERS)) {
            if(in_array($alias, ['sent messages', '已发送'])) {
                $alias = trans('webmail.hair_box');
            } else {
                $alias = $name;
            }
            $folderAlias = 'sent';
        } else if(in_array($alias, self::DELETEDFOLDERS)) {
            if(in_array($alias, ['deleted messages', '已删除'])) {
                $alias = trans('webmail.deleted_boxes');
            } else {
                $alias = $name;
            }
            $folderAlias = 'deleted';
        } else {
            $alias = $name;
            $folderAlias = '';
        }
        return [$name, $alias, $folderAlias];
    }

    /** 新增一个邮箱文件夹
     * @param $param
     * @param $userId
     * @return mixed
     */
    public function addOneEmailFolder($param, $userId)
    {
        $outboxId= $param['outbox_id'];
        $inboxServer = app($this->webmailService)->getInboxServer($outboxId, '');
        $inboxServer['folder_name'] = $name = $param['folder_name'];
        if (isset($inboxServer['code'])) {
            return $inboxServer;
        }
        $data = [
            'outbox_id' => $outboxId,
            'server' => $inboxServer['server'],
            'folder_name' => $name,
            'alias' => $name,
            'is_email' => 1,
            'folder_create_time' => date('Y-m-d H:i:s')
        ];
//        try {
//            app($this->email)->handleWebmail($inboxServer, 'createFolder', $userId);
//        } catch (\Exception $exception) {
//            \Log::info($exception->getMessage());
//            $message = $exception->getMessage();
//            return ['code' => ['', $message], 'dynamic' => $message];
//        }
        $result = app($this->email)->handleWebmail($inboxServer, 'createFolder', $userId);
        if (is_array($result)) {
            return $result;
        }
        $data['server'] = $data['server'].$data['folder_name'];
        /** @var WebmailFolderRepository $webmailFolderRepository */
        $webmailFolderRepository = app($this->webmailFolderRepository);
        $res = $webmailFolderRepository->insertData($data);
//        $inboxServer['type'] = 'createFolder';
//        Queue::push(new EmailJob(['handle' => 'folder', 'param' => $inboxServer]));
//        return app($this->email)->handleWebmail($inboxServer, 'createFolder', $userId);
        return true;
    }

    /** 给已有的一个邮箱文件夹重命名
     * @param $param
     * @param $userId
     * @return mixed
     */
    public function editOneWebmailFolder($param, $userId)
    {
        $outboxId= $param['outbox_id'];
        $inboxServer = app($this->webmailService)->getInboxServer($outboxId, '');
        $inboxServer['new_name'] = $newName = $param['new_name'];
        $inboxServer['old_name'] = $oldName = $param['old_name'];
        $nerNameArray = explode('/', $newName);
        $alias = $newName;
        if (count($nerNameArray) > 1) {
            $alias = $nerNameArray[1];
        }
        if (isset($inboxServer['code'])) {
            return $inboxServer;
        }
//        try {
//            app($this->email)->handleWebmail($inboxServer, 'renameFolder', $userId);
//        } catch (\Exception $exception) {
//            \Log::info($exception->getMessage());
//            $message = $exception->getMessage();
//            return ['code' => ['', $message], 'dynamic' => $message];
//        }
        $result = app($this->email)->handleWebmail($inboxServer, 'renameFolder', $userId);
        if (is_array($result)) {
            return $result;
        }
        /** @var WebmailFolderRepository $webmailFolderRepository */
        $webmailFolderRepository = app($this->webmailFolderRepository);
        $res = $webmailFolderRepository->updateData(['folder_name' => $newName, 'alias' => $alias], ['outbox_id' => [$outboxId], 'folder_name' => [$oldName]]);
//        $inboxServer['type'] = 'renameFolder';
//        Queue::push(new EmailJob(['handle' => 'folder','param' => $inboxServer]));
//        return app($this->email)->handleWebmail($inboxServer, 'renameFolder', $userId);
        return true;
    }

    /** 删除一个已有的邮箱文件夹
     * @param $outboxId
     * @param $folderId
     * @param $userId
     * @return mixed
     */
    public function deleteOneWebmailFolder($outboxId, $folderId, $userId)
    {
        /** @var WebmailFolderRepository $webmailFolderRepository */
        $webmailFolderRepository = app($this->webmailFolderRepository);
        $folder = $webmailFolderRepository->getDetail($folderId);
        if (!$folder || $folder->outbox_id != $outboxId) {
            return ['code' => ['folder_is_not_belongs_to_outbox', 'webmail']];
        }
        $inboxServer = app($this->webmailService)->getInboxServer($outboxId, '');
        $inboxServer['folder_name'] = $name = $folder->folder_name;
        if (isset($inboxServer['code'])) {
            return $inboxServer;
        }
//        try {
//            $result = app($this->email)->handleWebmail($inboxServer, 'deleteFolder', $userId);
//        } catch (\Exception $exception) {
//            \Log::info($exception->getMessage());
//            $message = $exception->getMessage();
//            return ['code' => ['', $message], 'dynamic' => $message];
//        }
        $result = app($this->email)->handleWebmail($inboxServer, 'deleteFolder', $userId);
        if (is_array($result)) {
            return $result;
        }
        $res = $webmailFolderRepository->deleteById($folderId);
        if ($res) {
            /** @var WebmailMailRepository $webmailMailRepository */
            $webmailMailRepository = app($this->webmailMailRepository);
            $webmailMailRepository->updateData(['folder_id' => 'inbox'], ['outbox_id' => $outboxId, 'folder_id' => $folderId]);
        }
//        $inboxServer['type'] = 'deleteFolder';
//        Queue::push(new EmailJob(['handle' => 'folder','param' => $inboxServer]));
        return true;
    }

    /** 获取某个邮箱的文件夹信息
     * @param $outboxId
     * @param string $type
     * @return array|array
     */
    public function getOneOutboxFolderAndEmailCount($outboxId, $type = '')
    {
        /**
         * @var WebmailService $webmailService
         */
        $webmailService = app($this->webmailService);
        $outbox = $webmailService->getOutbox($outboxId);
        if (!$outbox) {
            return ['code' => ['get_outbox_failed', 'webmail']];
        }
        $outbox['folder'] = [];
        if ($outbox['imap_sync'] && $outbox['imap_sync'] == 1) {
            $folders = $this->getListByDatabase(['outbox_id' => $outboxId]);
            if (isset($folders['code'])) {
                return $folders;
            }
            $outbox['folder'] = $folders;
        } else {
            $outbox['folder'] = [
                [
                    'folder_id' => 'inbox',
                    'folder_name' => 'inbox',
                    'alias' => trans('webmail.inbox')
                ],
                [
                    'folder_id' => 'sent',
                    'folder_name' => 'sent',
                    'alias' => trans('webmail.hair_box'),
                ],
                [
                    'folder_id' => 'draft',
                    'folder_name' => 'draft',
                    'alias' => trans('webmail.drafts'),
                ],
                [
                    'folder_id' => 'trash',
                    'folder_name' => 'trash',
                    'alias' => trans('webmail.dustbin'),
                ],
                [
                    'folder_id' => 'deleted',
                    'folder_name' => 'deleted',
                    'alias' => trans('webmail.deleted_boxes'),
                ]
            ];
        }
        $mails = app($this->webmailMailRepository)->entity->selectRaw('count(mail_id) as count, folder')->where('outbox_id', $outboxId)->where(function ($query) {
            $query->where(['folder'=> 'inbox', 'is_read' => 0])
                ->orWhere([['folder', '!=', 'inbox']]);
        })->groupBy('folder')->get()->toArray();
        $mails = array_column($mails, 'count', 'folder');
        foreach ($outbox['folder'] as $key => $folder) {
            $folderName = $this->getFolderNameByName(strtolower($folder['folder_name']));
            if ($folderName) {
                $outbox['folder'][$key]['count'] = $mails[$folderName] ?? 0;
                $outbox['folder'][$key]['folder_id'] = $folderName;
            } else {
                $outbox['folder'][$key]['count'] = $mails[$folder['folder_id']] ?? 0;
            }
        }
        // 星标邮箱未读个数处理
        $starCount = app($this->webmailMailRepository)->entity->selectRaw('count(mail_id) as count, folder')->where('outbox_id', $outboxId)->where(['is_star' => 1, 'is_read' => 0])->count();
        $star = [
            'folder_id' => 'star',
            'folder_name' => 'star',
            'alias' => trans('webmail.star_box'),
            'count' => $starCount,
            'has_children' => 0
        ];
        $outbox['folder_ids'] = array_column(array_merge($outbox['folder'], [$star]), 'folder_id');
        // 考虑到子文件夹的情况 需进行数据处理
        $folders = $others = [];
        $folderSort = [
            'inbox' => 0,
            'sent' => 1,
            'draft' => 2,
            'trash' => 3,
            'deleted' => 4
        ];
        $otherSort = 5;
        foreach ($outbox['folder'] as $key => $value) {
            $valueArray = explode('/', $value['folder_name']);
            if (count($valueArray) > 1) {
                $value['folder_name'] = $valueArray[1];
                $value['alias'] = $valueArray[1];
                $value['has_children'] = 0;
                $children = $value;
                if (isset($others[$valueArray[0]])) {
                    $others[$valueArray[0]]['children'][] = $children;
                } else {
                    $folder = [
                        'folder_id' => $value['folder_id'],
                        'folder_name' => $valueArray[0],
                        'alias' => $valueArray[0],
                        'has_children' => 1,
                    ];
                    $folder['children'][] = $children;
                    $others[$valueArray[0]] = $folder;
                }
            } else {
                $folder = $value;
                $folder['has_children'] = 0;
                if (isset($value['folder_name_alias']) && isset($folderSort[$value['folder_name_alias']])){
                    $folders[$folderSort[$value['folder_name_alias']]] = $folder;
                } else {
                    $folders[$otherSort] = $folder;
                    $otherSort++;
                }

            }
        }
        ksort($folders);
        array_map(function($value) use(&$folders) {
            $folders[] = $value;
        }, $others);
        $folders[] = $star;
        $outbox['folder'] = array_values($folders);
        return $outbox;
    }

    /** 获取文件夹信息
     * @param $folderId
     * @param $outboxId
     * @return mixed
     */
    public function getFolder($folderId, $outboxId)
    {
        if (in_array($folderId, self::SYSTEMFOLDERALIAS)) {
            $folder = app($this->webmailFolderRepository)->getOneFieldInfo(['folder_name_alias' => $folderId, 'outbox_id' => $outboxId]);
        } else {
            $folder = app($this->webmailFolderRepository)->getDetail($folderId);
        }
        return $folder;
    }

    public function getFolderNameByName($folderName)
    {
        if ($folderName == 'inbox') {
        }else if (in_array($folderName,self::DRAFTFOLDERS)) {
            $folderName = 'draft';
        } else if (in_array($folderName,self::SENTFOLDERS)) {
            $folderName = 'sent';
        } else if (in_array($folderName, self::TRASHFOLDERS)) {
            $folderName = 'trash';
        } else if (in_array($folderName, self::DELETEDFOLDERS)) {
            $folderName = 'deleted';
        } else {
            $folderName = '';
        }
        return $folderName;
    }
}