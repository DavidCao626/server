<?php


namespace App\EofficeApp\Document\Services\WPS;


use App\EofficeApp\Attachment\Repositories\AttachmentRelRepository;
use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Document\Services\DocumentService;
use App\EofficeApp\Document\Services\WPS\WPSFileObject\FileObject;
use App\EofficeApp\Document\Services\WPS\WPSFileObject\UserObject;
use App\EofficeApp\Menu\Services\UserMenuService;
use App\EofficeApp\Notify\Permissions\NotifyPermission;
use App\EofficeApp\Notify\Repositories\NotifyRepository;
use App\EofficeApp\Notify\Services\NotifyService;
use App\EofficeApp\Portal\Services\PortalService;
use App\EofficeApp\System\Security\Services\SystemSecurityService;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\User\Services\UserService;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use DB;

class WPSFileService extends BaseService
{
    const PERMISSION_READ = 'read';
    const PERMISSION_WRITE = 'write';
    const VALIDATE_PERMISSION_MODEL = [self::PERMISSION_READ, self::PERMISSION_WRITE];  // 有效的请求模式

    /**
     * 获取文档Id(即系统中对应的附件id)
     *
     * @param HeaderBag $headers
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getFileIdFromHeaders(HeaderBag $headers): string
    {
        // 获取 x-weboffice-file-id
        $fileId = $headers->get('x-weboffice-file-id');
        if (!$fileId) {
            throw new \Exception('FileId can not be empty ', 401);
        }

        return $fileId;
    }

    /**
     * 获取请求参数
     *
     * @param ParameterBag $parameters
     * @param string $key
     *
     * @return mixed
     */
    public function getParamsParameter(ParameterBag $parameters, $key)
    {
        return $parameters->get($key);
    }

    /**
     * 获取文档下载Id
     *
     * @param string $fileId
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getDownloadId($fileId): string
    {
        // TODO 加密将有效时间加入 参考 @see https://www.php.net/manual/zh/refs.crypto.php
        $fid = base64_encode($fileId);
        $route = 'api/wps/v1/3rd/file';
        $downloadUrl = $this->getDownloadUrl($route);

        return $downloadUrl.'?fid='.$fid;
    }

    /**
     * 获取用户头像下载地址
     *
     * @param string $userId
     *
     * @return string
     */
    public function getUserAvatarUrl($userId): string
    {
        $uid = base64_encode($userId);
        $route = 'api/wps/v1/3rd/avatar';

        $downloadUrl = $this->getDownloadUrl($route);

        return $downloadUrl.'?uid='.$uid;
    }

    /**
     * 获取文件/头像下载地址
     *
     * @param string $route
     *
     * @return string
     */
    private function getDownloadUrl($route): string
    {

        $host = OA_SERVICE_HOST;
        $scheme = OA_SERVICE_PROTOCOL;
        $scriptName = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];

        // $scriptName 为 /eoffice10_dev/server/public/index.php 匹配第一对//包裹的路径
        $strPattern = '/\/.*?\//';
        $arrMatches = [];
        preg_match($strPattern, $scriptName, $arrMatches);
        $dir = isset($arrMatches[0]) ? $arrMatches[0] : '/eoffice10/';
        $downloadUrl = $scheme.'://'.$host.$dir.'server/public/'.$route;

        return $downloadUrl;
    }

    /**
     * 获取文件对象信息
     *
     * @return FileObject
     *
     * @throws \Exception
     */
    public function getFileObject($fileId): FileObject
    {
        // 查询附件信息
        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        $attachmentInfo = $attachmentService->getOneAttachmentById($fileId);

        if (!$attachmentInfo) {
            throw new \Exception('File does not exist', 404);
        }

        $time = strtotime($attachmentInfo['attachment_time']);

        // 设置对象属性
        $file = new FileObject();
        $file->setId($fileId);
        $file->setDownloadUrl($this->getDownloadId($fileId));
        $file->setName($attachmentInfo['attachment_name']);
        $file->setCreateTime($time);
        $file->setSize($attachmentInfo['attachment_size']);
        $file->setModifyTime($time);

        return $file;
    }

    /**
     * 获取文件历史信息
     *
     * @param string $fileId
     *
     * @return array
     */
    public function getHistoryFileArr($fileId, $userId)
    {
        // 查询附件信息
        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        $attachmentInfo = $attachmentService->getOneAttachmentById($fileId);


        if (!$attachmentInfo) {
            throw new \Exception('File does not exist', 404);
        }

        $time = strtotime($attachmentInfo['attachment_time']);


        $fileArr = [
            'id' => $fileId,
            'name' => $attachmentInfo['attachment_name'],
            'version' => 1,
            'size' => $attachmentInfo['attachment_size'],
            'download_url' => $this->getDownloadId($fileId),
            'create_time' => $time,
            'modify_time' => $time,
        ];
        // 查询用户信息
        /** @var UserService $userService */
        $userService = app('App\EofficeApp\User\Services\UserService');
        /** @var UserEntity $userEntity */
        $userEntity = $userService->getUserAllData($userId);

        if ($userEntity) {
            $fileArr['creator'] = $fileArr['modifier'] = [
                'id' => $userId,
                'name' => $userEntity->user_name,
                'avatar_url' => $this->getUserAvatarUrl($userId),
            ];
        } else {
            $fileArr['creator'] = $fileArr['modifier'] = [
                'id' => '',
                'name' => '',
                'avatar_url' => '',
            ];
        }

        return $fileArr;
    }

    /**
     * 获取用户对象信息
     *
     * @param string $userId
     * @param string $fileId
     * @param string $model
     *
     * @return UserObject
     *
     * @throws \Exception
     */
    public function getUserObject($userId, $fileId, $model): UserObject
    {
        // 查询用户信息
        /** @var UserService $userService */
        $userService = app('App\EofficeApp\User\Services\UserService');
        /** @var UserEntity $userEntity */
        $userEntity = $userService->getUserAllData($userId);

        // 设置对象属性
        $user = new UserObject();
        $user->setName($userEntity->user_name);
        $user->setId($userId);
//        $user->setAvatarUrl($this->getDownloadId($userEntity->userHasOneInfo->avatar_source));// TODO 获取头像信息
        $user->setAvatarUrl($this->getUserAvatarUrl($userId));
        if (in_array($model, self::VALIDATE_PERMISSION_MODEL)) {
            $permission = $model;
        } else {
            $permission = self::PERMISSION_READ;
        }
        $user->setPermission($permission);

        return $user;
    }

    /**
     * 获取用户头像
     *
     * @param string $uid
     */
    public function getUserAvatar($uid)
    {
        $userId = base64_decode($uid);

        /** @var PortalService $portalService */
        $portalService = app('App\EofficeApp\Portal\Services\PortalService');
        $avatar = $portalService->getUserAvatar($userId);

        return $avatar;
    }

    /**
     * 批量获取用户信息
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getUsersInfo($userIds)
    {
        if (!is_array($userIds)) {
            $userIds = (array) $userIds;
        }

        if (!$userIds) {
            throw new \Exception('Invalidate parameters', 401);
        }
        // 查询用户信息
        /** @var UserService $userService */
        $userService = app('App\EofficeApp\User\Services\UserService');

        return array_map(function ($userId) use ($userService){
            /** @var UserEntity $user */
            $user = $userService->getUserAllData($userId);

            if (empty($user)) {
                return ['name' => '', 'id' => '', 'avatar_url' => ''];
//                throw new \Exception('The target user does not exist', 404);
            }

            return ['name' =>$user->user_name, 'id' => $userId, 'avatar_url' => $this->getUserAvatarUrl($userId) ];
        }, $userIds);
    }

    /**
     * 判断是否为office文件
     *
     * @param string $type
     *
     * @return bool
     */
    public function isOfficeFile($type): bool
    {
        return in_array($type, [
            'xls', 'xlt', 'et', 'xlsx', 'xltx', 'csv', 'xlsm', 'xltm',
            'doc', 'dot', 'wps', 'wpt', 'docx', 'dotx', 'docm', 'dotm',
            'ppt', 'pptx', 'pptm', 'ppsx', 'ppsm', 'pps', 'potx', 'potm', 'dpt', 'dps',
            'pdf',
        ]);
    }

    /**
     * 获取用户信息
     *
     * @param string $userId
     *
     * @return array
     */
    public function getUserOwn($userId): array
    {
        // 获取对应用户信息 [user_id, role_id, dept_id]
        /** @var UserService $userService */
        $userService = app('App\EofficeApp\User\Services\UserService');
//        $userInfo = $userService->getUserAllData($userId)->toArray();
        $userInfo = $userService->getLoginUserInfo($userId);

        // 获取用户对应的菜单权限
        /** @var UserMenuService $userMenuService */
        $userMenuService = app('App\EofficeApp\Menu\Services\UserMenuService');
        $menus = $userMenuService->getUserMenus($userId);
        // 处理多个角色的情况
        $roleIds = [];
        $roleNames = [];
//        foreach ($userInfo['user_has_many_role'] as $role) {
//            $roleIds[] = $role['role_id'];
//        }
        foreach ($userInfo->roles as $role) {
            $roleIds[] = $role['role_id'];
            $roleNames[] = $role['role_name'];
        }

        $own = [
            'user_id' => $userId,
            'role_id' => $roleIds,
            'role_name' => $roleNames,
//            'dept_id' => $userInfo['user_has_one_system_info']['dept_id'], // TODO 处理多个系统信息
            'menus' => $menus,
            'dept_id' => $userInfo->dept_id,
            'dept_name' => $userInfo->dept_name,
            'user_name' => $userInfo->user_name,
            'user_accounts' => $userInfo->user_accounts,
            'phone_number' => $userInfo->phone_number,
        ];

        return $own;
    }

    /**
     * 是否拥有读写权限
     *
     * @param string $userId
     * @param string $fileId
     *
     * @return bool
     */
    public function isPermission($userId, $fileId): bool
    {
        // 根据各模块获取是否拥有权限

        /**
         * 带附件的模块
         *  1. 文档
         *  2. 公告
         *  3. 人事档案
         */

        return true;
    }

    /**
     * 下载文件
     *
     * @param string $fid
     *
     * @throws \Exception
     */
    public function downloadFile($fid)
    {
        // TODO 验证信息
        $attachmentId = base64_decode($fid);
        $attachmentInfo = $this->getAttachmentFile($attachmentId);
        $file = $attachmentInfo['temp_src_file'];
        $fileName = $attachmentInfo['attachment_name'];

        $this->downloadAttachmentFileWithRate($file, $fileName);
//        return ['file' => $file, 'fileName' => $fileName];    // 框架默认方法
    }

    /**
     * 下载文件
     *
     * @param $path
     * @param $filename
     */
    private function downloadAttachmentFileDirectly($path, $filename)
    {
//        以只读和二进制模式打开文件
        $fileHandler = fopen($path, "rb");
        //告诉浏览器这是一个文件流格式的文件
        Header("Content-type: application/octet-stream");
        //请求范围的度量单位
        Header("Accept-Ranges: bytes" );
        //Content-Length是指定包含于请求或响应中数据的字节长度
        $size = filesize($path);
        Header("Accept-Length: " . $size);
        //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$fileName该变量的值。
        Header("Content-Disposition: attachment; filename=" . $filename);
        //读取文件内容并直接输出到浏览器
        echo fread($fileHandler, $size);
        fclose($fileHandler);
        exit();
    }

    /**
     * 控制速率下载
     *
     * @param string $path
     * @param string $fileName
     * @param int    $rate
     */
    private function downloadAttachmentFileWithRate($path, $fileName, $rate = 1024)
    {
        $filesize = filesize($path);
        ob_clean();
        header('Cache-Control:public');
        header("Content-type: application/octet-stream");
        header('Content-Length:'.$filesize); //告诉浏览器，文件大小
        header("Content-Disposition: attachment; filename=" . $fileName);

        // 设置下载速率(1024 kb/s)
        $download_rate = $rate;
        // 每次读取文件的字节数为xx字节 直接输出数据
        $read_buffer = round($download_rate * 1024);
        $handle = fopen($path, 'rb');
        // 总的缓冲的字节数
        $sum_buffer = 0;
        // 只要没到文件尾 就一直读取
        while (!feof($handle) && $sum_buffer < $filesize) {
            echo fread($handle, $read_buffer);
            $sum_buffer += $read_buffer;
            flush(); // flush 内容输出到浏览器端
            sleep(1); // 终端1秒后继续
        }
        // 关闭句柄
        fclose($handle);
        exit;
    }

    /**
     * 替换为文件新版本
     *
     * @param string $fileId
     * @param string $file
     *
     * @throws \Exception
     */
    public function saveFileWithNewVersion($fileId, $file): void
    {
        // TODO 验证文件类型
        $attachmentInfo = $this->getAttachmentFile($fileId);
        if (empty($attachmentInfo)) {
            throw new \Exception('The target file does not exist', 404);
        }

        $oldTmpFile = $attachmentInfo['temp_src_file'];
        $tmpFilePath = $attachmentInfo['attachment_base_path'].$attachmentInfo['attachment_relative_path'];
        $newTmpFileName = md5($attachmentInfo['attachment_name'].time()).'.'.$attachmentInfo['attachment_type'];
        $newTmpFile = $tmpFilePath.$newTmpFileName;

        $out = @fopen($newTmpFile, "wb");

        if (!empty($file) && is_uploaded_file($file)) {
            $in = @fopen($file, "rb");
        } else {
            $in = @fopen("php://input", "rb");
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename($newTmpFile, $oldTmpFile);

        // 更新文档size
        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        /** @var AttachmentRelRepository $relRepository */
        $relRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRelRepository');
        $attachmentRel = $relRepository->getOneAttachmentRel(['attachment_id' => [$attachmentInfo['attachment_id']]]);
        $tableName = $attachmentService->getAttachmentTableName($attachmentRel->year, $attachmentRel->month);
        $attachmentService->updateAttachmentSize($tableName, $attachmentInfo['id'], $attachmentInfo['temp_src_file']);

        // 更新永中插件缓存
        $transRecord = DB::table('yozo_translate')->where('attachment_id', $fileId)->first();
        if (!empty($transRecord)){
            DB::table('yozo_translate')->where('attachment_id', $fileId)->update(['operate_count' => '-1']);
        }
    }

    /**
     * 根据附件id获取附件信息
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getAttachmentFile($fid): array
    {
        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        /** @var array|null $attachmentInfo */
        $attachmentInfo = $attachmentService->getOneAttachmentById($fid);

        if (empty($attachmentInfo)) {
            throw new \Exception('The target file does not exists', 404);
        }

        return $attachmentInfo;
    }

    /**
     * 记录回调日志
     *
     * @return bool
     */
    public function createNotifyLog($params)
    {
        try {
            $date = date('Y-m-d');
            $wpsDir= storage_path('logs/wps/');

            if (!is_dir($wpsDir)) {
                dir_make($wpsDir);
            }

            $content = json_encode($params);
            $content .= "\r\n";

            $wpsLog = $wpsDir.$date. '.log';
            file_put_contents($wpsLog, $content, FILE_APPEND);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}