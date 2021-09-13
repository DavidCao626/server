<?php

namespace App\EofficeApp\Attachment\Services;

use App\EofficeApp\Attachment\Repositories\AttachmentRelRepository;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Document\Services\WPS\WPSAuthService;
use App\EofficeApp\IntegrationCenter\Services\ThirdPartyInterfaceService;
use App\EofficeApp\System\Security\Services\SystemSecurityService;
use App\EofficeApp\User\Repositories\UserRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Request;
use DB;
use Log;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Ocr\V20181119\Models\TableOCRRequest;
use TencentCloud\Ocr\V20181119\OcrClient;
use GuzzleHttp\Client;
use Cache;
use App\Utils\Utils;

class AttachmentService extends BaseService
{
    private $attachmentRepository;
    private $attachmentRelRepository;
    private $attachmentRelSearchRepository;
    private $attachmentShareRepository;

    private $uploadChunkTmpPath = 'upload_tmp';//切片上传文件存放的临时目录
    private $cleanChunkTmpFile = true;//是否清空切片上传的临时文件
    private $chuckTmpFileMaxAge = 18000;//切片上传临时文件的过期时间，单位秒
    private $timeLimit = 300;//超时时间
    private $specialReltionTable = ['auth', 'editor'];//特殊的附件关系表

    const SECRET_ID = 'AKIDoXAdvFnWRBk8LNs5mIsJblnfMsDNqCqK';
    const SECRET_KEY = 'Tmu0ymOp5CE80iEZjiWCpIanoeT8dx64';
    const PLATFORM = [
        'tencent' => 0,
        'baidu' => 1,
        'youdao' => 2,
    ];
    const APP_KEY = '122e129c20c70506';
    const SEC_KEY = 'NtFHIzn7Ir7kmYv9umtjCftNezi4Wd4f';
    const CURL_TIMEOUT = 2000;

    const URL = 'https://openapi.youdao.com/ocr_table';

    public function __construct()
    {
        $this->attachmentRepository = 'App\EofficeApp\Attachment\Repositories\AttachmentRepository';
        $this->attachmentRelSearchRepository = 'App\EofficeApp\Attachment\Repositories\AttachmentRelSearchRepository';
        $this->attachmentRelRepository = 'App\EofficeApp\Attachment\Repositories\AttachmentRelRepository';
        $this->attachmentShareRepository = 'App\EofficeApp\Attachment\Repositories\AttachmentShareRepository';
    }

    /**
     * 上传附件
     *
     * @param type $data
     * @param type $own
     * @param type $files
     *
     * @return array
     */
    public function upload($data, $own, $files)
    {
        set_time_limit($this->timeLimit);

        $tmpPath = $this->makeChunkTmpPath();//创建临时目录
        $fileName = $this->getChunkFileName($data, $files);//获取切片上传文件名称
        if (!$this->isSafeFile($fileName, $data,$files)) {
            return ['code' => ['0x011028', 'upload']];
        }
        $fileName = $this->checkFileNameRules($fileName, $data, $own);//获取附件名称规则新名称

        $md5FileName = $this->getChunkMd5FileName($fileName);//获取文件的md5文件名

        $fullFileName = $this->getFullChunkFileName($md5FileName, $tmpPath);//获取切片上传文件的完整路径

        $gbkFileName = $this->transEncoding($fileName, 'gbk');//将切片上传文件名称转为gbk编码

        $chunk = $data["chunk"] ?? 0;

        $chunks = isset($data['chunks']) && $data['chunks'] ? $data['chunks'] : 1;

        $chuckPart = $this->makeChunkPart($fullFileName, $chunk);
        $chunkPartTmp = $this->makeChunkPart($fullFileName, $chunk, '.parttmp');

        if (!$this->cleanUpChunkTmpFile($tmpPath, $fullFileName, $chuckPart, $chunkPartTmp)) {
            return ['code' => ['0x011006', 'upload']];
        }
        /**
         * 将临时目录里的切片文件移动到切片文件的临时目录里
         */
        $result = $this->moveChunkFile($files, $chunkPartTmp, $chuckPart);
        if (isset($result['code'])) {
            return $result;
        }
        //合并所有的切片
        if (!$this->mergeChunkParts($chunks, $fullFileName)) {
            return ['code' => ['0x011007', 'upload']];
        }
        // 判断切片上传大小是否超出了文件上传大小限制。
        if (file_exists($fullFileName) && filesize($fullFileName) > intval(ini_get('upload_max_filesize')) * 1024 * 1024) {
            return ['code' => ['0x011012', 'upload']];
        }
        return $this->uploadThen($chunk, $chunks, $own['user_id'], $gbkFileName, $fullFileName, $fileName, $data);
    }

    /**
     * 判断文件后缀是否合法
     * @param $fileName
     * @param $module
     * @return bool
     */
    private function isSafeFile($fileName, $data, $files)
    {
        //1.先比较传入的文件名的后缀是否和file里面的文件名后缀一致
        $formFileName = $files["Filedata"]["name"] ?? null;
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($formFileName&&$formFileName!='blob') {
            $formFileType = strtolower(pathinfo($formFileName, PATHINFO_EXTENSION));
            if ($fileType != $formFileType) {
                return false;
            }
        }
        //2.验证是不是在性能安全设置里面控制的后缀
        $abbreviation = $data['abbreviation'] ?? 'other';
        $rule = app('App\EofficeApp\System\Security\Repositories\SystemUploadRepository')->getOneModule($abbreviation);
        if (!$rule) {
            return false;
        }
        $suffix = explode('|', strtolower($rule->suffix));
        $suffixStatus = $rule->suffix_status;
        $isSafe = $suffixStatus == 0 ? !in_array($fileType, $suffix) : in_array($fileType, $suffix);
        $systemSuffix = config('eoffice.uploadDeniedExtensions');//系统禁止的附件格式
        if ($isSafe) {
            $isSafe = !in_array($fileType, $systemSuffix);
        }
        // OA实例模式下，禁止上传部分类型文件
        $isCasePlatform = envOverload('CASE_PLATFORM', false);
        if ($isCasePlatform && $isSafe) {
            $dangerousFileType = [
                'exe', 'bat', 'dll', 'php', 'inc', 'js', 'msi', 'cmd', 'vbs', 'sh', 'jsp',
            ];
            $isSafe = !in_array($fileType, $dangerousFileType);
        }
        return $isSafe;
    }

    private function checkFileNameRules($fileName, $data, $own)
    {
        $abbreviation = $data['abbreviation'] ?? 'other';
        $rule = app('App\EofficeApp\System\Security\Repositories\SystemUploadRepository')->getOneModule($abbreviation);
        if (!isset($rule->file_name_rules) || empty($rule->file_name_rules) || $rule->file_name_rules == '[]') {
            return $fileName;
        }
        $newFileName = '';
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $oldFileName = str_replace('.'.$fileType, '', $fileName);
        $date = date('Y-m-d', time());
        if(Cache::has('upload_time')){
            $time = Cache::get('upload_time');
        }else{
            $time = date('H_i_s', time());
            Cache::set('upload_time', $time, 120);
        }
        $fileNameRules = json_decode($rule->file_name_rules,true);
        if(!empty($fileNameRules)){
            foreach ($fileNameRules as $rule){
                if($rule['type'] == 'txt'){
                    $newFileName.= $rule['title'];
                }else if($rule['type'] == 'date'){
                    if($rule['control_id'] == 'date'){
                        $newFileName.= $date;
                    }else if($rule['control_id'] == 'time'){
                        $newFileName.= $time;
                    }else if($rule['control_id'] == 'dateTime'){
                        $newFileName.= $date.' '.$time;
                    }
                }else if($rule['type'] == 'fileInfo'){
                    if($rule['control_id'] == 'fileName'){
                        $newFileName.= $oldFileName;
                    }else if($rule['control_id'] == 'creator'){
                        $newFileName.= $own['user_name'] ?? '';
                    }else if($rule['control_id'] == 'jobNumber'){
                        $newFileName.= $own['user_job_number'] ?? '';
                    }
                }
            }
        }else{
            return $fileName;
        }
        return trim($newFileName).'.'.$fileType;
    }



    /**
     * 多个附件复制
     *
     * @param array $data
     *
     * @return array
     */
    public function attachmentCopy($data, $own)
    {
        /*$data = [
            ['source_attachment_id'=>'fc1743d8a92a5c0e8cec4c559eff97e5','attachment_table'=>'test','attachment_name'=>'测试的'],
            ['source_attachment_id'=>'e6588587515c24b385fce8f9082f01d3','attachment_table'=>'auth'],
            ['source_attachment_id'=>'cf91aa507d41b4f2f4d5e8951542beb8','attachment_table'=>'auth']
        ];*/
        if (empty($data)) {
            return ['code' => ['0x011017', 'upload']];
        }
        foreach ($data as $v) {
            $copy[] = $this->attachmentSingleCopy($v, $own);
        }
        return $copy;
    }

    /**
     * 单个附件复制
     *
     * @param array $data
     *
     * @return array
     */
    private function attachmentSingleCopy($data, $own)
    {
        /**
         * | -------------------------------------------------------------------
         * | 获取复制源附件，并且判断是否存
         * | -------------------------------------------------------------------
         */
        if (!isset($data['source_attachment_id']) || empty($data['source_attachment_id'])) {
            return ['code' => ['0x011017', 'upload']];
        }
        $sourceAttachment = $this->getOneAttachmentById($data['source_attachment_id'], false);
        if (!$sourceAttachment || !$sourceAttachment['temp_src_file'] || !file_exists($sourceAttachment['temp_src_file'])) {
            return ['code' => ['0x011017', 'upload']];
        }
        $attachmentId = $this->makeAttachmentId($own['user_id']);
        $attachmentType = $sourceAttachment['attachment_type'];
        $sourceAttachmentName = (isset($data['attachment_name']) && $data['attachment_name']) ? $data['attachment_name'] . '.' . $attachmentType : $sourceAttachment['attachment_name'];//支持附件改名
        $md5FileName = $this->getMd5FileName($sourceAttachmentName);
        $newFullFileName = $this->createCustomDir($attachmentId) . DIRECTORY_SEPARATOR . $md5FileName;
        if (isset($newFullFileName['code'])) {
            return $newFullFileName;
        }
        copy($sourceAttachment['temp_src_file'], $newFullFileName); //拷贝到新目录
        $fileType = pathinfo($newFullFileName, PATHINFO_EXTENSION);
        $attachmentPaths = $this->parseAttachmentPath($newFullFileName);
        $attachmentInfo = [
            "attachment_id" => $attachmentId,
            "attachment_name" => $sourceAttachmentName,
            "affect_attachment_name" => $md5FileName,
            'new_full_file_name' => $newFullFileName,
            "thumb_attachment_name" => $this->generateImageThumb($fileType, null, $newFullFileName),
            "attachment_size" => filesize($newFullFileName),
            "attachment_type" => $fileType,
            "attachment_create_user" => $own['user_id'],
            "attachment_base_path" => $attachmentPaths[0],
            "attachment_path" => $attachmentPaths[1],
            "attachment_mark" => $this->getAttachmentMark($fileType),
            "relation_table" => $data["attachment_table"] ?? $sourceAttachment['relation_table'],
            "rel_table_code" => isset($data["attachment_table"]) ? $this->getRelationTableCode($data["attachment_table"]) : $this->getRelationTableCode($sourceAttachment['relation_table']),
        ];
        return $this->handleAttachmentDataTerminal($attachmentInfo);
    }

    /**
     * 附件替换
     *
     * @param array $data
     *
     * @return boolean
     */
    public function attachmentReplace($data)
    {
        /**
         * | -------------------------------------------------------------------
         * | 获取替换源附件，并且判断是否存
         * | -------------------------------------------------------------------
         */
        $sourceAttachment = $this->getOneAttachmentById($data['source_attachment_id']);

        if (!$sourceAttachment || !$sourceAttachment['temp_src_file'] || !file_exists($sourceAttachment['temp_src_file'])) {
            return ['code' => ['0x011017', 'upload']];
        }
        /**
         * | -------------------------------------------------------------------
         * | 获取替换目标附件，并且判断是否存
         * | -------------------------------------------------------------------
         */
        $destAttachment = $this->getOneAttachmentById($data['dest_attachment_id']);
        if (!$destAttachment || !$destAttachment['temp_src_file']) {
            return ['code' => ['0x011017', 'upload']];
        }
        /**
         * | -------------------------------------------------------------------
         * | 附件替换，先复制，再删除替换源附件
         * | -------------------------------------------------------------------
         */
        $source = $sourceAttachment['temp_src_file'];
        $dest = $destAttachment['temp_src_file'];

        if (copy($source, $dest)) {
            unlink($source);

            // 更新文档size
            /** @var AttachmentRelRepository $relRepository */
            $relRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRelRepository');
            $attachmentRel = $relRepository->getOneAttachmentRel(['attachment_id' => [$destAttachment['attachment_id']]]);
            $tableName = $this->getAttachmentTableName($attachmentRel->year, $attachmentRel->month);
            $this->updateAttachmentSize($tableName, $destAttachment['id'], $destAttachment['temp_src_file']);

            return true;
        }

        return false;
    }

    /**
     * 获取附件列表
     *
     * @param type $params
     * @return type
     */
    public function getAttachments($params,$hideRelPath=false)
    {
        if (isset($params["attach_ids"]) && $params["attach_ids"]) {
            $attachmentIds = is_array($params["attach_ids"]) ? $params["attach_ids"] : explode(",", rtrim($params["attach_ids"], ','));
        } else {
            $attachmentIds = $this->getAttachmentIdsByEntityId($params);
        }

        $data = $this->getMoreAttachmentById($attachmentIds);
        if (!$hideRelPath) {
            return $data;
        }
        //对api不暴露物理地址
        return array_map(function ($item) {
            unset($item['attachment_base_path']);
            unset($item['temp_src_file']);
            return $item;
        }, $data);
    }

    /**
     * 根据实体id获取附件id
     * @param type $params
     * @param bool $isBatch 是否批量模式，如果是，则entity_id可以传一个id数组，返回时key是id，value是每个id对应的附件数组
     * @return type
     */
    public function getAttachmentIdsByEntityId($params, $isBatch = false)
    {
        $attachmentIds = [];

        if (isset($params['entity_table']) && isset($params['entity_id']) && $params['entity_table'] && $params['entity_id']) {
            $tableName = $this->getRelationTableName($params['entity_table']);

            if (app($this->attachmentRepository)->tableExists($tableName)) {
                $wheres = is_array($params['entity_id']) ? $params['entity_id'] : ["entity_id" => [$params['entity_id']]]; // entity_id
                if (!$isBatch) {
                    $attachmentIds = $this->getAttachmentIds($tableName, $wheres);
                } else {
                    $attachmentIds = app($this->attachmentRepository)->getEntityIdsByEntityIds($wheres, $tableName);
                    $attachmentIds = collect($attachmentIds)->groupBy('entity_id');
                    foreach ($attachmentIds as $key => $item) {
                        $attachmentIds[$key] = $item->pluck('attachment_id')->toArray();
                    }
                    $attachmentIds = $attachmentIds->toArray();
                }

            }
        }

        return $attachmentIds;
    }

    /**
     * 更新表数据关联
     *
     * @param type $entityTableData
     * @param type $entityKeys
     * @param type $attachmentIds
     * @param type $flag
     * @return boolean
     * @throws Exception
     */
    public function attachmentRelation($entityTableData, $entityKeys, $attachmentIds, $flag = "update")
    {
        if (!is_array($attachmentIds)) {
            $attachmentIds = explode(",", rtrim($attachmentIds, ','));
        }
        //解析查询条件
        if (is_array($entityKeys)) {
            $wheres = $entityKeys["wheres"];
            unset($entityKeys["wheres"]);
        } else {
            $wheres = ["entity_id" => [$entityKeys]];
        }

        try {
            list($entityTable, $relationTableName) = $this->createAttachmentRelationTable($entityTableData);
            //如果flag=add 不判断直接追加
            if ($flag == "add") {
                $addAttachmentIds = $attachmentIds;
            } else {
                $oldAttachmentIds = $this->getAttachmentIds($relationTableName, $wheres); //原来的附件ID $oldAttachmentIds
                $deleteAttachmentIds = array_diff($oldAttachmentIds, $attachmentIds);
                $addAttachmentIds = array_diff($attachmentIds, $oldAttachmentIds);
                $log2 = storage_path('logs/') . 'relation_attach.log';
                file_put_contents($log2, json_encode($entityKeys) . ' - ' . json_encode($oldAttachmentIds) . '-' . json_encode($attachmentIds) . '-' . json_encode($deleteAttachmentIds) . '-' . json_encode($addAttachmentIds) . '-' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
                //删除附件
                $this->deleteMoreAttachmentById($deleteAttachmentIds);
                if (app($this->attachmentRepository)->tableExists($relationTableName)) {
                    app($this->attachmentRepository)->deleteAttachment($relationTableName, $deleteAttachmentIds, 'attachment_id');
                }
            }

            foreach ($addAttachmentIds as $attachmentId) {
                if ($attachmentId) {
                    //解析关系表数据
                    $relationData = [];
                    $attachmentExistsWheres = [];
                    if (is_array($entityKeys)) {
                        foreach ($entityKeys as $key => $value) {
                            $handleValue = is_array($value) ? $value[0] : $value;
                            $attachmentExistsWheres[$key] = [$handleValue];
                            $relationData[$key] = $handleValue;
                        }
                    } else {
                        $relationData['entity_id'] = $entityKeys;
                        $attachmentExistsWheres['entity_id'] = [$entityKeys];
                    }
                    $relationData['attachment_id'] = $attachmentId;
                    $attachmentExistsWheres['attachment_id'] = $attachmentId;
                    // 判断关系表里的数据是否存在，存在则不执行插入操作。
                    $attachmentRelationExist = app($this->attachmentRepository)->getOneAttachment($relationTableName, $attachmentExistsWheres);
                    if (!$attachmentRelationExist) {
                        app($this->attachmentRepository)->saveRelationData($relationTableName, $relationData); //保存关系表数据
                    }
                    $attachmentRel = app($this->attachmentRelRepository)->getOneAttachmentRel(['attachment_id' => [$attachmentId]]);
                    app($this->attachmentRelSearchRepository)->updateData(['rel_table_code' => $this->getRelationTableCode($relationTableName)], ['rel_id' => [$attachmentRel->rel_id]]);
                    app($this->attachmentRepository)->updateAttachmentData(
                        $this->getAttachmentTableName($attachmentRel->year, $attachmentRel->month), ['relation_table' => $entityTable], ['rel_id' => [$attachmentRel->rel_id]]
                    );
                }
            }

            return true;
        } catch (\Exception $e) {
            throw new Exception(new JsonResponse(error_response('0x011014', $e->getMessage()), 500));
        }
    }

    /**
     * base64转附件
     * @param type $data
     * @param type $userId
     * @return type
     */
    public function base64Attachment($data, $userId = null)
    {

        return $this->handleBase64Attachment($data, $userId, function($base64Content) {
                    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Content, $result)) {
                        $suffix = $result[2];
                        $imageSuffix = config('eoffice.uploadImages', ['jpg', 'png', 'gif', 'jpeg']);
                        if (!in_array(strtolower($suffix), $imageSuffix)) {
                            return false;
                        }
                        return [str_replace($result[1], '', $base64Content), $result[2]];
                    }
                    return false;
                });
    }
    private function handleBase64Attachment($data, $userId, $_suffix = null)
    {
        $base64Content = isset($data["image_file"]) && $data["image_file"] ? $data["image_file"] : "";
        if (!$base64Content) {
            return ['code' => ['0x011020', 'upload']];
        }
        // 有的需要使用文件本来的名字作为文件名
        $base64Name = isset($data["image_name"]) && $data["image_name"] ? $data["image_name"] : "";
        if (strpos($base64Name, '..') > -1) {
            return ['code' => ['0x011020', 'upload']];
        }
        if (is_callable($_suffix)) {
            list($base64Content, $suffix) = $_suffix($base64Content);
            if (!$suffix) {
                return ['code' => ['0x011020', 'upload']];
            }
        } else {
            $suffix = $_suffix;
        }
        //生成附件信息
        $attachmentId = $this->makeAttachmentId($userId);
        $newPath = $this->createCustomDir($attachmentId);
        $isWriteable = $this->isWriteable($newPath);
        if (!$newPath || !$isWriteable) {
            return ['code' => ['0x011010', 'upload']];
        }
        $attachmentTable = $data["attachment_table"] ?? 'editor';
        $fileName = ($base64Name ? $base64Name : ($attachmentTable . "_" . md5($attachmentId))) . '.' . $suffix;
        $newFullFileName = $newPath . $fileName;
        if (file_put_contents($newFullFileName, base64_decode($base64Content))) {
            list($basePath, $attachmentPath) = $this->parseAttachmentPath($newFullFileName);
            $attachment = [
                "attachment_id" => $attachmentId,
                "attachment_name" => $fileName,
                "affect_attachment_name" => $fileName,
                'new_full_file_name' => $newFullFileName,
                "thumb_attachment_name" => $this->generateImageThumb($suffix, $data, $newFullFileName),
                "attachment_size" => filesize($newFullFileName),
                "attachment_type" => $suffix,
                "attachment_create_user" => $userId,
                "attachment_base_path" => $basePath,
                "attachment_path" => $attachmentPath,
                "attachment_mark" => $this->getAttachmentMark($suffix),
                "relation_table" => $attachmentTable,
                "rel_table_code" => $this->getRelationTableCode($attachmentTable)
            ];
            return $this->handleAttachmentDataTerminal($attachment);
        }

        return ['code' => ['0x011020', 'upload']];
    }
    // 保存高拍仪生成的PDF文件
    public function base64AttachmentPdf($data, $userId = null)
    {
        return $this->handleBase64Attachment($data, $userId, 'pdf');
    }

    /**
     * 批量base64转附件
     * @param type $data
     * @param type $userId
     * @return type
     */
    public function base64Attachments($data, $userId = null)
    {
        $attachmentIds = [];
        if (!empty($data)) {
            foreach ($data as $image) {
                if (is_string($image)) {
                    $attachment = $this->base64Attachment(['image_file' => $image], $userId);
                } else if (is_array($image) && isset($image['src'])){
                    $attachment = $this->base64Attachment(['image_file' => $image['src'], 'image_name' => isset($image['name']) ? $image['name'] : ''], $userId);
                }
                if ($attachment && isset($attachment['attachment_id'])) {
                    $attachmentIds[] = $attachment;
                }
            }
        }
        return $attachmentIds;
    }

    /**
     * 加载附件
     *
     * @param type $attachmentId
     * @param type $params
     * @return boolean
     */
    public function loadAttachment($attachmentId, $params, $own, $encrypt = true)
    {

        //分享到外部的附件不验证权限
        if (!is_bool($own) || $own !== true) {
            if (!$this->canDownload($attachmentId, $params, $own)) {
                return ['code' => ['0x011024', 'upload']];
            }
        }
        if (app('App\EofficeApp\Auth\Services\AuthService')->isMobile()) {
            $encrypt = false;
        }
        if (isset($params['encrypt']) && $params['encrypt'] == 0) {
            $encrypt = false;
        }
        if ($encrypt) {
            if (!isset($params['attachment_id']) || empty($params['attachment_id'])) {
                return ['code' => ['0x011017', 'upload']];
            }
            $newAttachmentId = decrypt_params($params['attachment_id'], false, true);
            if (!$newAttachmentId || $attachmentId != $newAttachmentId) {
                return ['code' => ['0x011017', 'upload']];
            }
        }
        if (!$this->canDownload($attachmentId, $params, $own)) {
            return ['code' => ['0x011024', 'upload']];
        }

        if (!$attachment = $this->getAttachmentInfo($attachmentId, $params)) {
            return ['code' => ['0x011017', 'upload']];
        }
        $url = $attachment['url'];
        $filename = $attachment['name'];
        $type = $attachment['type'];
        if (isset($params['operate']) && ($params['operate'] == "download")) {
            //下载，记录文档日志
            if($attachmentId){
                $attachmentInfo = $this->getOneAttachmentById($attachmentId, false);
                if (isset($attachmentInfo['relation_table']) && $attachmentInfo['relation_table'] == "document_content") {
                    $record = DB::table('attachment_relataion_document_content')->where('attachment_id', $attachmentId)->first();
                    if (isset($record->entity_id) && $record->entity_id) {
                        $logs = app('App\EofficeApp\LogCenter\Services\LogCenterService')->getLogs('document', [$record->entity_id], 'document_content', ['userId' => $own['user_id'], 'operate' => ['download']]);
                        if(empty($logs) || empty($logs[$record->entity_id]) || (isset($logs[$record->entity_id][0]) && (time() - strtotime($logs[$record->entity_id][0]->log_time) > 60))){
                            $log_info = json_encode(['attachment_name' => $attachmentInfo['attachment_name'] ?? '']);
                            app('App\EofficeApp\Document\Services\DocumentService')->addLog($record->entity_id, 8, $own['user_id'], $log_info);
                        }
                    }
                }
            }
            $allowType = [
                'doc',
                'docx',
                'xls',
                'xlsx',
                'DOC',
                'DOCX',
                'XLS',
                'XLSX',
                'wps',
                'wpt',
                'WPS',
                'WPT',
            ];
            if (in_array($type, $allowType)) {
                $this->downAttach($url, $filename);
            } else {
                return response()->download($url, $filename);
            }
        } else {
            //预览
            if ($attachment['attachment_mark'] == 4) {
                return response()->download($url, $filename, ['Content-Disposition' => "attachment"]);
            } else if ($attachment['attachment_mark'] == 2) {
                $file = fopen($url, "r");
                Header("Content-type: application/pdf");
                echo fread($file, filesize($url));
                fclose($file);
                exit;
            } else {
                $this->downAttach($url, $filename);
            }
            return true;
        }
    }

    /**
     * 加载压缩图片
     * @param $attachmentId
     * @param $params
     * @return array|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function loadCompressImage($attachmentId, $type = 'thumb_original' )
    {
        $attachment = $this->getOneAttachmentById($attachmentId, false);
        $url = $attachment['attachment_base_path'] . $attachment['attachment_relative_path'] .  $type . '_' . $attachment['affect_attachment_name'] ;
        $filename = $attachment['attachment_name'];
        if(file_exists($url)){
            return response()->download($url, $filename);
        }else{
            $url = $attachment['attachment_base_path'] . $attachment['attachment_relative_path'] .  $attachment['affect_attachment_name'] ;
            return response()->download($url, $filename);
        }

    }

    private function canDownload($attachmentId, $params, $own) {
        $authKey = $params['auth_key'] ?? false;

        if ($authKey) {
            $classMethod = config('attachment.permissions.' . $authKey);
            if ($classMethod && is_array($classMethod) && sizeof($classMethod) == 3) {
                list($class, $method, $entityTable) = $classMethod;
                if (class_exists($class)) {
                    $classObject = app($class);
                    if (method_exists($classObject, $method)) {
                        $relationTableName = $this->getRelationTableName($entityTable);

                        if ($relationInfos = app($this->attachmentRepository)->getOneAttachment($relationTableName, ['attachment_id' => [$attachmentId]])) {
                            return $classObject->{$method}($attachmentId, $relationInfos->entity_id, $params, $own);
                        }
                    }
                }
            }
        }

        return true;
    }

    public function attachmentPermission($attachmentId, $params, $own, $type)
    {
        // 没有传auth_key，要先查到关联表
        if (is_array($attachmentId)) {
            $attachmentInfos = $this->getMoreAttachmentById($attachmentId, false);
            $relationTable = $attachmentInfos[0]['relation_table'] ?? '';
        } else {
            $attachmentInfo = $this->getOneAttachmentById($attachmentId, false);
            $relationTable = $attachmentInfo['relation_table'] ?? '';
        }

        // 附件没有关联表，没有模块权限限制
        if (empty($relationTable)) {
            return true;
        }

        $classMethod = config('attachment.' . $type . '.' . $relationTable);

        if ($classMethod) {
            if (is_array($classMethod) && sizeof($classMethod) == 2) {
                list($class, $method) = $classMethod;

                if (class_exists($class)) {
                    $classObject = app($class);
                    $relationTableName = $this->getRelationTableName($relationTable);

                    if (method_exists($classObject, $method)) {
                        if (is_array($attachmentId)) {
                            $relationInfos = app($this->attachmentRepository)->getEntityIdsByAttachmentIds($attachmentId, $relationTableName);

                            if (is_array($relationInfos) && !empty($relationInfos) && count($relationInfos) == count($attachmentId)) {
                                return $classObject->{$method}($attachmentId, array_column($relationInfos, 'entity_id'), $params, $own);
                            }
                        } else {
                            $relationInfos = app($this->attachmentRepository)->getOneAttachment($relationTableName, ['attachment_id' => [$attachmentId]]);

                            if ($relationInfos) {
                                return $classObject->{$method}($attachmentId, $relationInfos->entity_id, $params, $own);
                            }
                        }
                    }
                }
            } elseif (is_bool($classMethod)) {
                return $classMethod;
            }
        }


        return false;
    }

    /**
     * 下载zip文件
     * @param $params
     * @param null $userId
     * @return type|array|bool
     */
    public function downZip($params, $userId=null)
    {
        set_time_limit($this->timeLimit);
        ini_set('memory_limit', '-1');
        $attachments = $this->getAttachments($params);
        if (isset($attachments['code'])) {
            return $attachments;
        }
        $attachmentIds = array_column($attachments, 'attachment_id');
        $zipmd5 = md5($userId . time() . rand(100000, 999999));
        $zipFileArray = array();
        $zip = new \ZipArchive;
        // 创建默认附件根目录
        $path = getAttachmentDir('attachment') . DIRECTORY_SEPARATOR . 'downzipTemp';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $zipName = $path . DIRECTORY_SEPARATOR . $zipmd5 . ".zip"; //临时压缩文件名称
        if ($zip->open($zipName, \ZIPARCHIVE::CREATE)) {
            foreach ($attachments as $attachment) {
                $file = $this->makeAttachmentFullPath($attachment['attachment_base_path'], $attachment['attachment_relative_path'], $attachment['affect_attachment_name'], true);
                if (!file_exists($file)) {
                    $file = $this->getCurrentEnvironmentEncodingName($file);
                    //可能手动删除了文件
                    if(!file_exists($file)){
                        continue;
                    }
                }
                $zip->addFile($file, $attachment['attachment_name']);
                $zipFileArray[$attachment['attachment_name']] = $file;
            }
            $zip->close();
        }
        if(!$zipFileArray){
            return ['code' => ['0x011017', 'upload']];
        }
        //下载zip文件
        header("Content-type: application/zip");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($zipName));
        header("Content-Disposition: attachment; filename=attachment.zip");
        @readfile($zipName);

        unlink($zipName); //删除zip文件

        //是否是文档附件，记录下载日志
        if($attachments[0] && isset($attachments[0]['attachment_id'])){
            $record = DB::table('attachment_relataion_document_content')->where('attachment_id', $attachments[0]['attachment_id'])->first();
            if (isset($record->entity_id) && $record->entity_id) {
                $log_info = json_encode(['attachment_name' => trans('document.all_attachment') ?? '']);
                app('App\EofficeApp\Document\Services\DocumentService')->addLog($record->entity_id, 8, $userId, $log_info);
            }
        }

        return true;
    }

    /**
     * 下载zip文件包含一级文件夹
     *
     * @param type $params
     * @param type $userId
     *
     * @return boolean
     */
    public function downZipByFolder($params, $userId = null)
    {
        /*$params = [
            'a' => ['7e9b9f85495fe4f702d26fc4d91a8243'],
            'b' => ['6c3693f4cdf1ae100e6f720d257f1362', '7e9b9f85495fe4f702d26fc4d91a8243']
        ];*/

        set_time_limit($this->timeLimit);

        ini_set('memory_limit', '-1');

        $folderAttachments = array_map(function ($attachmentIds) {
            return $this->getAttachments(['attach_ids' => $attachmentIds]);
        }, $params);

        $zipmd5 = md5($userId . time() . rand(100000, 999999));
        $zip = new \ZipArchive;
        // 创建默认附件根目录
        $path = getAttachmentDir('attachment') . DIRECTORY_SEPARATOR . 'downzipTemp';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $zipName = $path . DIRECTORY_SEPARATOR . $zipmd5 . ".zip"; //临时压缩文件名称
        if ($zip->open($zipName, \ZIPARCHIVE::CREATE)) {
            foreach ($folderAttachments as $folder => $attachments) {
                $folderFileArray = array();
                $zip->addEmptyDir($folder);
                foreach ($attachments as $attachment) {
                    $file = $this->makeAttachmentFullPath($attachment['attachment_base_path'], $attachment['attachment_relative_path'], $attachment['affect_attachment_name'], true);
                    if (!file_exists($file)) {
                        $file = $this->getCurrentEnvironmentEncodingName($file);
                    }
                    $zip->addFile($file, $folder.'/'.$attachment['attachment_name']);
                    $folderFileArray[$attachment['attachment_name']] = $file;
                }
            }
            $zip->close();
        }

        header("Content-type: application/zip");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($zipName));
        header("Content-Disposition: attachment; filename=attachment.zip");
        @readfile($zipName);

        unlink($zipName); //删除zip文件

        return true;
    }

    private function delFolderAndFile($dir)
    {
        //先删除目录下的文件：
        $dh = @opendir($dir);//打开失败返回false
        if ($dh === false) {
            return false;
        }
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullPath = $dir . "/" . $file;
                if (!is_dir($fullPath)) {
                    unlink($fullPath);
                } else {
                    $this->delFolderAndFile($fullPath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹：
        if (@rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据附件ID获取一个附件
     *
     * @param type $attachmentId
     * @param type $mulit
     * @return type
     */
    public function getOneAttachmentById($attachmentId, $mulit = true)
    {
        return $this->handleOneAttachmenCommon($attachmentId, $mulit, function ($attachmentId) {
            return app($this->attachmentRelRepository)->getOneAttachmentRel(['attachment_id' => [$attachmentId]]);
        });
    }

    /**
     * 根据自增ID获取多个附件
     *
     * @param array $relId
     * @param type $mulit
     * @return type
     */
    public function getMoreAttachmentByRelId(array $relId, $mulit = true)
    {
        return $this->handleAttachmentsCommon($relId, $mulit, function ($relId) {
            return app($this->attachmentRelRepository)->getAttachmentRelList(['rel_id' => [$relId, 'in']]);
        });
    }

    /**
     * 根据自增ID获取一个附件
     *
     * @param type $relId
     * @param type $mulit
     * @return type
     */
    public function getOneAttachmentByRelId($relId, $mulit = true)
    {
        return $this->handleOneAttachmenCommon($relId, $mulit, function ($relId) {
            return app($this->attachmentRelRepository)->getOneAttachmentRel(['rel_id' => [$relId]]);
        });
    }

    /**
     * 根据用户ID获取附件
     * @param type $userId
     * @param type $data
     * @return boolean
     */
    public function getAttachmentByUserId($userId, $data)
    {
        if (!$userId) {
            return false;
        }

        $table = isset($data["attachment_table"]) ? $this->getRelationTableCode($data["attachment_table"]) : "";
        $wheres = [
            "creator" => [$userId],
            "rel_table_code" => [$table],
        ];
        $attachmentSearch = app($this->attachmentRelSearchRepository)->getAttachmentList(['search' => $wheres]);
        if (count($attachmentSearch) > 0) {
            $getParam['search'] = ['rel_id' => [array_column($attachmentSearch->toArray(), 'rel_id'), 'in']];
            $getParam['page'] = $data['page'];
            $getParam['limit'] = $data['limit'];
            $result ['list'] = app($this->attachmentRelRepository)->getAttachmentRelListData($getParam);
            $all = app($this->attachmentRelRepository)->getAttachmentRelList($getParam['search']);
            $result ['total'] = count($all);
            return $result;
            //return app($this->attachmentRelRepository)->getAttachmentRelList(['rel_id' => [array_column($attachmentSearch->toArray(), 'rel_id'), 'in']]);
        }

        return [];
    }

    public function deleteAttachmentRel($userId, $data)
    {
        if (!$userId) {
            return false;
        }
        if (isset($data['rel_id'])&&!empty($data['rel_id']) ){
            $where = ['rel_id' => [$data['rel_id'], 'in'], "creator" => [$userId]];
            return app($this->attachmentRelSearchRepository)->deleteByWhere($where);
        }
        return false;
    }
    /**
     * 删除附件
     *
     * @param type $data
     * @return type
     */
    public function removeAttachment($data)
    {
        if (!isset($data["attachment_id"]) || empty($data["attachment_id"])) {
            return ['code' => ['0x011009', 'upload']]; //请求异常
        }
        $attachmentId = is_array($data["attachment_id"]) ? $data["attachment_id"] : explode(",", rtrim($data["attachment_id"], ','));

        return $this->deleteMoreAttachmentById($attachmentId);
    }

    /**
     * 根据附件ID获取附件
     *
     * @param array $attachmentId
     * @param type $mulit
     * @return type
     */
    public function getMoreAttachmentById(array $attachmentId, $mulit = true)
    {
        return $this->handleAttachmentsCommon($attachmentId, $mulit, function ($attachmentId) {
            return app($this->attachmentRelRepository)->getAttachmentRelList(['attachment_id' => [$attachmentId, 'in']]);
        });
    }

    /**
     * 上传授权文件
     * @param type $file
     * @param type $data
     * @return type
     * @throws Exception
     */
    public function attachmentAuthFile($file, $data)
    {
        //移动文件到指定的目录
        $fileErrCode = $file->getError();
        if ($fileErrCode > 0) {
            //上传文件错误
            return ['code' => ['0x01100' . $fileErrCode, 'upload']]; // 1-7 错误
        }
        $userId = isset($data['user_id']) ? $data['user_id'] : "";
        //文件路径
        $attachmentId = $this->makeAttachmentId($userId);
        $newPath = $this->createCustomDir($attachmentId);
        if (isset($newPath['code'])) {
            return $newPath;
        }
        $isWriteable = $this->isWriteable($newPath);
        if (!$newPath || !$isWriteable) {
            return ['code' => ['0x011010', 'upload']];
        }
        $originName = $file->getClientOriginalName();
        $fileType = strtolower($file->getClientOriginalExtension());
        //判断是否为授权文件
        if ($fileType == "inc" || $fileType == "evf" || $fileType == 'license') {
            if ($fileType == "inc" && $originName != "register.inc") {
                return ['code' => ['0x011021', 'upload']];
            }
        } else {
            return ['code' => ['0x011011', 'upload']];
        }
        //文件加密
        $originNameSerect = md5(time() . $originName) . "." . $fileType;
        try {
            $file->move($newPath, $originNameSerect);

            $newFullFileName = $newPath . $originNameSerect;

            $attachmentPaths = $this->parseAttachmentPath($newFullFileName);

            $attachmentInfo = [
                "attachment_id" => $attachmentId,
                "attachment_name" => $originName,
                "affect_attachment_name" => $originNameSerect,
                'new_full_file_name' => $newFullFileName,
                "thumb_attachment_name" => '',
                "attachment_size" => filesize($newFullFileName),
                "attachment_type" => $fileType,
                "attachment_create_user" => $userId,
                "attachment_base_path" => $attachmentPaths[0],
                "attachment_path" => $attachmentPaths[1],
                "attachment_mark" => 9,
                "relation_table" => 'auth',
                "rel_table_code" => $this->getRelationTableCode('auth')
            ];
            return $this->handleAttachmentDataTerminal($attachmentInfo);
        } catch (\Exception $e) {
            throw new Exception(new JsonResponse(error_response('0x011013', $e->getMessage()), 500));
        }
    }

    /**
     * 根据附件ID删除多个附件
     *
     * @param array $attachmentId
     * @return boolean
     */
    private function deleteMoreAttachmentById(array $attachmentId)
    {
        if (empty($attachmentId)) {
            return true;
        }
        //删除附件
        $attachments = $this->getMoreAttachmentById($attachmentId, false);
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $this->deleteOneAttachment($attachment);
            }
        }

        return $this->batchDeleteAttachmentData($attachmentId);
    }

    /**
     * 删除一个附件
     * @param type $attachment
     * @return boolean
     */
    private function deleteOneAttachment($attachment)
    {
        // $attachment['attachment_relative_path'] 替换 $attachment['attachment_path']路径
        $attachmentFullPath = $this->makeAttachmentFullPath($attachment['attachment_base_path'], $attachment['attachment_relative_path'], $attachment['affect_attachment_name']);
        @unlink($attachmentFullPath);
        @rmdir(rtrim($attachment['attachment_base_path'], "/") . DIRECTORY_SEPARATOR . rtrim($attachment['attachment_relative_path'], "/"));
        if ($attachment["relation_table"]) {
            if (!in_array($attachment["relation_table"], $this->specialReltionTable)) {
                $relationTableName = $this->getRelationTableName($attachment["relation_table"]);
                if (app($this->attachmentRepository)->tableExists($relationTableName)) {
                    app($this->attachmentRepository)->deleteAttachment($relationTableName, $attachment['attachment_id'], 'attachment_id');
                }
            }
        }

        return true;
    }

    /**
     * 批量删除附件数据
     * @param type $attachmentId
     * @return boolean
     */
    public function batchDeleteAttachmentData($attachmentId)
    {
        $list = app($this->attachmentRelRepository)->getAttachmentRelList(['attachment_id' => [$attachmentId, 'in']]);
        if (count($list) > 0) {
            $map = $relIds = [];
            foreach ($list as $item) {
                if (!$item->rel_id) {
                    continue;
                }
                $map[$item->year . '_' . $item->month][] = $item->rel_id;
                $relIds[] = $item->rel_id;
            }
            foreach ($map as $tableSuffix => $relId) {
                list($year, $month) = explode('_', $tableSuffix);
                $tableName = $this->getAttachmentTableName($year, $month);
                if (app($this->attachmentRepository)->tableExists($tableName)) {
                    app($this->attachmentRepository)->deleteAttachment($tableName, $relId, 'rel_id');
                }
            }
            app($this->attachmentRelRepository)->deleteById($relIds);
            app($this->attachmentRelSearchRepository)->deleteById($relIds);
        }

        return true;
    }

    private function handleAttachmentsCommon($ids, $mulit, $before)
    {
        if (empty($ids)) {
            return [];
        }

        $list = $before($ids);

        if (count($list) > 0) {
            $map = $relId2AttachmentId = [];
            foreach ($list as $item) {
                $tableSuffix = $item->year . '_' . $item->month;
                if (isset($map[$tableSuffix])) {
                    $map[$tableSuffix][] = $item->rel_id;
                } else {
                    $map[$tableSuffix] = [$item->rel_id];
                }
                $relId2AttachmentId[$item->rel_id] = $item->attachment_id;
            }

            $allAttachments = [];
            foreach ($map as $tableSuffix => $relIds) {
                list($year, $month) = explode('_', $tableSuffix);
                $tableName = $this->getAttachmentTableName($year, $month);
                $attachments = app($this->attachmentRepository)->getAttachments($tableName, ['rel_id' => [$relIds, 'in']]);
                $allAttachments = array_merge($allAttachments, $this->handleAttachments($attachments, $tableName, $relId2AttachmentId, $mulit));
            }

            return $allAttachments;
        }

        return [];
    }

    private function handleOneAttachmenCommon($id, $mulit = true, $before)
    {
        if (empty($id)) {
            return null;
        }

        if (!$attachmentRel = $before($id)) {
            return null;
        }

        $tableName = $this->getAttachmentTableName($attachmentRel->year, $attachmentRel->month);

        $attachment = app($this->attachmentRepository)->getOneAttachment($tableName, ['rel_id' => [$attachmentRel->rel_id]]);

        return $this->handleOneAttachment($attachment, $tableName, $attachmentRel->attachment_id, $mulit);
    }

    /**
     * 处理多个附件信息
     *
     * @param type $attachments
     * @param type $tableName
     * @param type $relId2AttachmentId
     * @param type $mulit
     * @return type
     */
    private function handleAttachments($attachments, $tableName, $relId2AttachmentId, $mulit)
    {
        $handleAttachments = [];

        if (count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                $handleAttachment = $this->handleOneAttachment($attachment, $tableName, $relId2AttachmentId[$attachment->rel_id], $mulit);
                if (!empty($handleAttachment)) {
                    $handleAttachments[] = $handleAttachment;
                }
            }
        }

        return $handleAttachments;
    }

    /**
     * 处理一个附件信息
     *
     * @param type $attachment
     * @param type $tableName
     * @param type $attachmentId
     * @param type $mulit
     * @return type
     */
    private function handleOneAttachment($attachment, $tableName, $attachmentId, $mulit)
    {
        $handleAttachment = [];

        if ($attachment) {
            $attachmentBasePath = $attachment->attachment_base_path ? $attachment->attachment_base_path : getAttachmentDir('attachment');
            $handleAttachment['id'] = $attachment->rel_id;
            $handleAttachment['attachment_id'] = $attachmentId;
            $handleAttachment['attachment_name'] = $this->transEncoding($attachment->attachment_name, 'utf-8');
            $handleAttachment['affect_attachment_name'] = $attachment->affect_attachment_name;
            $handleAttachment['attachment_base_path'] = $attachmentBasePath;
            $handleAttachment['attachment_relative_path'] = $attachment->attachment_path;
            $handleAttachment['attachment_type'] = $attachment->attachment_type;
            $handleAttachment['attachment_mark'] = $attachment->attachment_mark;
            $handleAttachment['category'] = $attachment->attachment_mark;
            $handleAttachment['relation_table'] = $attachment->relation_table;
            //解析更多的附件信息
            if ($mulit) {
                //如果大小为空，则获取一下附件的大小 edit by 许杨
                if (!$attachment->attachment_size) {
                    $attachmentFullPath = $this->makeAttachmentFullPath($attachmentBasePath, $attachment->attachment_path, $attachment->attachment_name, true);

                    $attachment->attachment_size = $this->updateAttachmentSize($tableName, $attachment->rel_id, $attachmentFullPath);
                }
                $handleAttachment['thumb_attachment_name'] = $this->makeAttachmentBase64($attachmentBasePath, $attachment);
            } else {
                $handleAttachment['thumb_attachment_name'] = $attachment->thumb_attachment_name;
            }
            $handleAttachment['attachment_size'] = $attachment->attachment_size;
            $handleAttachment['attachment_time'] = $attachment->created_at;
            $handleAttachment['attachment_path'] = $this->makeAttachmentPath($attachmentId);
            $handleAttachment['temp_src_file'] = $this->makeAttachmentFullPath($attachmentBasePath, $attachment->attachment_path, $attachment->affect_attachment_name);
            $handleAttachment['attachment_base_path'] = $this->makeAttachmentBasePath($attachmentBasePath, $attachment->attachment_path, $attachment->affect_attachment_name);
            if (!file_exists($handleAttachment['temp_src_file'])) {
                $handleAttachment['attachment_base_path'] = getAttachmentDir('attachment');
                $handleAttachment['temp_src_file'] = $handleAttachment['attachment_base_path'] . $attachment->attachment_path . $attachment->affect_attachment_name;
                if (!file_exists($handleAttachment['temp_src_file'])) {
                    $handleAttachment['attachment_base_path'] = Utils::getAttachmentDir('attachment');
                    $handleAttachment['temp_src_file'] = $handleAttachment['attachment_base_path'] . $attachment->attachment_path . $attachment->affect_attachment_name;
                }
            }
        }

        return $handleAttachment;
    }

    /**
     * 拼接附件访问路径
     *
     * @param type $attachmentId
     * @return type
     */
    private function makeAttachmentPath($attachmentId)
    {
        return "attachment" . DIRECTORY_SEPARATOR . "index" . DIRECTORY_SEPARATOR . $attachmentId;
    }

    /**
     * 拼接附件完整的存储路径
     * @param type $attachmentBasePath
     * @param type $attachmentPath
     * @param type $attachmentName
     * @param type $transEncode
     * @return string
     */
    private function makeAttachmentFullPath($attachmentBasePath, $attachmentPath, $attachmentName, $transEncode = false)
    {
        $attachmentFullPath = rtrim($attachmentBasePath, "/") . DIRECTORY_SEPARATOR . rtrim($attachmentPath, "/") . DIRECTORY_SEPARATOR . $attachmentName;
        if (!file_exists($attachmentFullPath)) {
            $attachmentFullPath = rtrim(getAttachmentDir('attachment'), "/") . DIRECTORY_SEPARATOR . rtrim($attachmentPath, "/") . DIRECTORY_SEPARATOR . $attachmentName;
            if (!file_exists($attachmentFullPath)) {
                $attachmentFullPath = rtrim(Utils::getAttachmentDir('attachment'), "/") . DIRECTORY_SEPARATOR . rtrim($attachmentPath, "/") . DIRECTORY_SEPARATOR . $attachmentName;
            }
        }
        if ($transEncode) {
            return $this->transEncoding($attachmentFullPath, 'GBK');
        }
        return $attachmentFullPath;
    }

    private function makeAttachmentBasePath($attachmentBasePath, $attachmentPath, $attachmentName, $transEncode = false)
    {
        $attachmentFullPath = rtrim($attachmentBasePath, "/") . DIRECTORY_SEPARATOR . rtrim($attachmentPath, "/") . DIRECTORY_SEPARATOR . $attachmentName;
        $attachmentNewBasePath = $attachmentBasePath;
        if (!file_exists($attachmentFullPath)) {
            $attachmentFullPath = rtrim(getAttachmentDir('attachment'), "/") . DIRECTORY_SEPARATOR . rtrim($attachmentPath, "/") . DIRECTORY_SEPARATOR . $attachmentName;
            $attachmentNewBasePath = getAttachmentDir('attachment');
            if (!file_exists($attachmentFullPath)) {
                $attachmentFullPath = rtrim(Utils::getAttachmentDir('attachment'), "/") . DIRECTORY_SEPARATOR . rtrim($attachmentPath, "/") . DIRECTORY_SEPARATOR . $attachmentName;
                $attachmentNewBasePath = Utils::getAttachmentDir('attachment');
            }
        }
        if ($transEncode) {
            return $this->transEncoding($attachmentFullPath, 'GBK');
        }
        return $attachmentNewBasePath;
    }

    /**
     * 将附件转为base64编码格式
     * @param type $attachmentBasePath
     * @param type $attachment
     * @return string
     */
    private function makeAttachmentBase64($attachmentBasePath, $attachment)
    {
        if (!empty($attachment->thumb_attachment_name)) {
            if ($attachment->attachment_mark == 1) {
                $path = $this->makeAttachmentFullPath($attachmentBasePath, $attachment->attachment_path, $attachment->thumb_attachment_name);

                return imageToBase64($path);
            }
        }
        return '';
    }

    /**
     * 更新附件表中附件大小
     *
     * @param type $tableName
     * @param type $relId
     * @param type $attachmentPath
     * @return int
     */
    public function updateAttachmentSize($tableName, $relId, $attachmentPath)
    {
        if (file_exists($attachmentPath)) {
            $fileSize = filesize($attachmentPath);

            app($this->attachmentRepository)->updateAttachmentData($tableName, ["attachment_size" => $fileSize], ['rel_id' => [$relId]]);

            return $fileSize;
        }

        return 0;
    }

    /**
     * 获取附件ID
     *
     * @param type $tableName
     * @param type $wheres
     * @return type
     */
    private function getAttachmentIds($tableName, $wheres)
    {
        $attachments = app($this->attachmentRepository)->getAttachmentIds($tableName, $wheres);

        return array_unique(array_column($attachments->toArray(), 'attachment_id'));
    }

    /**
     * 附件切片上传完成后处理附件相关数据
     *
     * @param type $chunk
     * @param type $chunks
     * @param type $userId
     * @param type $gbkFileName
     * @param type $fullFileName
     * @param type $fileName
     * @param type $data
     *
     * @return boolean
     */
    public function uploadThen($chunk, $chunks, $userId, $gbkFileName, $fullFileName, $fileName, $data)
    {
        if ($chunk == $chunks - 1 || empty($chunks)) {
            $attachmentId = $this->makeAttachmentId($userId);

            $md5FileName = $this->getMd5FileName($gbkFileName);
            $newFullFileName = $this->createCustomDir($attachmentId) . DIRECTORY_SEPARATOR . $md5FileName;
            if (isset($newFullFileName['code'])) {
                return $newFullFileName;
            }
            $this->moveAttachment($fullFileName, $newFullFileName);
            $fileType = pathinfo($newFullFileName, PATHINFO_EXTENSION);
            $attachmentPaths = $this->parseAttachmentPath($newFullFileName);
            $this->generateImageCompress($fileType, $newFullFileName);
            $attachmentInfo = [
                "attachment_id" => $attachmentId,
                "attachment_name" => $fileName,
                "affect_attachment_name" => $md5FileName,
                'new_full_file_name' => $newFullFileName,
                "thumb_attachment_name" => $this->generateImageThumb($fileType, $data, $newFullFileName),
                "attachment_size" => filesize($newFullFileName),
                "attachment_type" => $fileType,
                "attachment_create_user" => $userId,
                "attachment_base_path" => $attachmentPaths[0],
                "attachment_path" => $attachmentPaths[1],
                "attachment_mark" => $this->getAttachmentMark($fileType),
                "relation_table" => $data["attachment_table"] ?? '',
                "rel_table_code" => isset($data["attachment_table"]) ? $this->getRelationTableCode($data["attachment_table"]) : ""
            ];

            return $this->handleAttachmentDataTerminal($attachmentInfo);
        }

        return false;
    }

    /**
     * 创建附件表
     *
     * @param type $year
     * @param type $month
     * @param type $prefix
     * @return type
     */
    private function makeAttachmentTable($year, $month)
    {
        return app($this->attachmentRepository)->makeAttachmentTable($this->getAttachmentTableName($year, $month));
    }

    /**
     * 获取附件表名称
     *
     * @param type $year
     * @param type $month
     * @param type $prefix
     * @return type
     */
    public function getAttachmentTableName($year, $month, $prefix = 'attachment_')
    {
        return $prefix . $year . '_' . $month;
    }

    /**
     * 获取md5加密的附件名称
     *
     * @param type $gbkFileName
     * @return type
     */
    public function getMd5FileName($gbkFileName)
    {
        $name = substr($gbkFileName, 0, strrpos($gbkFileName, "."));

        return md5(time() . $name) . strrchr($gbkFileName, '.');
    }

    /**
     * 获取切片上传时切片的md5值，防止特殊字符乱码,去除随机时间，否则生成的名字不同无法合并
     */
    private function getChunkMd5FileName($gbkFileName)
    {
        $name = substr($gbkFileName, 0, strrpos($gbkFileName, "."));

        return md5($name) . strrchr($gbkFileName, '.');
    }

    /**
     * 移动附件到附件目录
     *
     * @param type $src
     * @param type $desc
     */
    private function moveAttachment($src, $desc)
    {
        copy($src, $desc); //拷贝到新目录
        @unlink($src); //删除旧目录下的文件
    }

    /**
     * 将附件数据保存到关联表
     *
     * @param array $attachmentInfo
     * @param \Closure $start
     * @param \Closure $next
     * @param \Closure $terminal
     * @return boolean
     */
    private function saveAttachmentData($attachmentInfo, $start, $next, $terminal)
    {
        $year = date('Y');
        $month = intval(date('m'));
        $startResult = $start($attachmentInfo['attachment_id'], $year, $month);
        if ($startResult) {
            $next(
                $startResult->rel_id,
                $attachmentInfo['attachment_name'],
                $attachmentInfo['attachment_type'],
                $attachmentInfo['attachment_mark'],
                $attachmentInfo['rel_table_code'],
                $attachmentInfo['attachment_create_user']
            );

            $attachmentInfo['rel_id'] = $startResult->rel_id;

            return $terminal($this->makeAttachmentTable($year, $month), $attachmentInfo);
        }

        return false;
    }

    /**
     * 创建附件ID
     * @param type $userId
     * @return type
     */
    public function makeAttachmentId($userId)
    {
        return md5(time() . $userId . rand(1000000, 9999999));
    }

    /**
     * 获取附件关联表编码
     * @param type $tableName
     * @return type
     */
    private function getRelationTableCode($tableName)
    {
        return md5($tableName);
    }

    /**
     * 获取附件关联表编码
     *
     * @param type $entityTable
     *
     * @return type
     */
    public function getRelTableCode($entityTable)
    {
        $relTable = $this->getRelationTableName($entityTable);

        return $this->getRelationTableCode($relTable);
    }

    /**
     * 获取附件关系表名称
     *
     * @param type $entityTable
     * @param type $prefix
     * @return type
     */
    private function getRelationTableName($entityTable, $prefix = 'attachment_relataion_')
    {
        if (in_array($entityTable, $this->specialReltionTable)) {
            return $entityTable;
        }

        return $prefix . $entityTable;
    }

    /**
     * 创建切片上传的临时目录
     * @return type
     */
    private function makeChunkTmpPath()
    {
        $tmpPath = base_path('public/' . $this->uploadChunkTmpPath);

        if (!file_exists($tmpPath)) {
            @mkdir($tmpPath);
        }

        return $tmpPath;
    }

    /**
     * 获取上传的切片文件名
     * @param type $data
     * @param type $files
     * @return type
     */
    private function getChunkFileName($data, $files)
    {
        if (isset($data["name"])) {
            return $data["name"];
        } else if (!empty($files)) {
            return $files["Filedata"]["name"];
        } else {
            return uniqid("file_");
        }
    }

    /**
     * 获取切片文件的完整路径名称
     * @param type $fileName
     * @param type $tmpPath
     * @return type
     */
    private function getFullChunkFileName($fileName, $tmpPath)
    {
        $gbkName = strlen($fileName) > 200 ? $this->transEncoding(mb_substr($fileName, -100), 'gbk') : $this->transEncoding($fileName, 'gbk');

        return $tmpPath . DIRECTORY_SEPARATOR . $gbkName;
    }

    /**
     * 清空切片临时文件
     *
     * @param type $tmpPath
     * @param type $fullFileName
     * @param type $chunk
     * @return boolean
     */
    private function cleanUpChunkTmpFile($tmpPath, $fullFileName, $chunk)
    {
        if ($this->cleanChunkTmpFile) {
            if (!is_dir($tmpPath) || !$dir = opendir($tmpPath)) {
                return false;
            }

            $chuckPart = $this->makeChunkPart($fullFileName, $chunk);

            $chunkPartTmp = $this->makeChunkPart($fullFileName, $chunk, '.parttmp');

            while (($file = readdir($dir)) !== false) {
                $tmpfilePart = $tmpPath . DIRECTORY_SEPARATOR . $file;
                //判断临时目录中的切片是否和当前切片相同
                if ($tmpfilePart == $chuckPart || $tmpfilePart == $chunkPartTmp) {
                    continue;
                }
                //删除切片上传的临时文件
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePart) < time() - $this->chuckTmpFileMaxAge)) {
                    @unlink($tmpfilePart);
                }
            }

            closedir($dir);
        }

        return true;
    }

    /**
     * 创建切片文件
     *
     * @param type $fullFileName
     * @param type $chunk
     * @param type $suffix
     * @return type
     */
    private function makeChunkPart($fullFileName, $chunk, $suffix = '.part')
    {
        return $fullFileName . '_' . $chunk . $suffix;
    }

    /**
     * 移动切片文件
     *
     * @param type $files
     * @param type $chunkPartTmp
     * @param type $chuckPart
     * @return boolean
     */
    private function moveChunkFile($files, $chunkPartTmp, $chuckPart)
    {
        if (!$out = @fopen($chunkPartTmp, "wb")) {
            return ['code' => ['0x011007', 'upload']];
        }

        if (!empty($files)) {
            if ($files["Filedata"]["error"] || !is_uploaded_file($files["Filedata"]["tmp_name"])) {
                return ['code' => ['0x011023', 'upload']];
            }
            if (!$in = @fopen($files["Filedata"]["tmp_name"], "rb")) {
                return ['code' => ['0x011022', 'upload']];
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                return ['code' => ['0x011022', 'upload']];
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);

        rename($chunkPartTmp, $chuckPart);

        return true;
    }

    /**
     * 合并切片
     * @param type $chunks
     * @param type $fullFileName
     * @return boolean
     */
    private function mergeChunkParts($chunks, $fullFileName)
    {
        $index = 0;
        $done = true;
        for ($index = 0; $index < $chunks; $index++) {
            if (!file_exists($this->makeChunkPart($fullFileName, $index))) {
                $done = false;
                break;
            }
        }

        if ($done) {
            if (!$out = @fopen($fullFileName, "wb")) {
                return false;
            }

            if (flock($out, LOCK_EX)) {
                for ($index = 0; $index < $chunks; $index++) {
                    $chuckPart = $this->makeChunkPart($fullFileName, $index);
                    if (!$in = @fopen($chuckPart, "rb")) {
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink($chuckPart);
                }

                flock($out, LOCK_UN);
            }
            @fclose($out);
        }

        return true;
    }

    /**
     * 获取附件标记
     *
     * @param type $fileType
     * @return int
     * 修改属性为public方便其他模块调用
     */
    public function getAttachmentMark($fileType)
    {
        $uploadFileStatus = config('eoffice.uploadFileStatus');

        foreach ($uploadFileStatus as $key => $status) {
            if (in_array(strtolower($fileType), $status)) {
                return $key;
            }
        }

        return 9;
    }

    /**
     * 如果附件是图片则创建缩略图
     *
     * @param type $fileType
     * @param type $data
     * @param type $sourcFile
     * @return string
     */
    private function generateImageThumb($fileType, $data, $sourcFile)
    {
        if (in_array($fileType, config('eoffice.uploadImages'))) {
            $thumbWidth = isset($data["thumbWidth"]) && $data["thumbWidth"] ? $data["thumbWidth"] : config('eoffice.thumbWidth', 100);
            $thumbHight = isset($data["thumbHight"]) && $data["thumbHight"] ? $data["thumbHight"] : config('eoffice.thumbHight', 40);
            $thumbPrefix = config('eoffice.thumbPrefix', "thumb_");
            return scaleImage($sourcFile, $thumbWidth, $thumbHight, $thumbPrefix);
        }

        return '';
    }

    /**
     * 如果附件是图片则创建压缩图
     * @param $fileType
     * @param $sourcFile
     * @return string
     * 2021-05-11 手机端上传附件需要使用调整为公有函数
     */
    public function generateImageCompress($fileType, $sourcFile)
    {
        if (in_array($fileType, config('eoffice.uploadImages'))) {
            // 20210312 客户图片dpi较高导致报出 notice 先屏蔽此错误 DT202103110053
            $imgSize = @getimagesize($sourcFile);
            $thumbPrefix = config('eoffice.originalThumbPrefix', "thumb_original_");
            return scaleImage($sourcFile, $imgSize[0], $imgSize[1], $thumbPrefix);
        }

        return '';
    }
    /**
     * 解析附件路径
     *
     * @param type $fullFileName
     * @return type
     */
    public function parseAttachmentPath($fullFileName)
    {
        $attachmentBasePath = getAttachmentDir();

        $attachmentPath = str_replace($attachmentBasePath, '', pathinfo($fullFileName, PATHINFO_DIRNAME));

        if (!config('eoffice.attachmentDir')) {
            $attachmentBasePath = "";
        }

        return [$attachmentBasePath, $attachmentPath . '/'];
    }

    /**
     * 检查自定义路径 //微信中使用 放开priavte->public
     * @param string $attachmentId
     * @return string|array
     */
    public function createCustomDir($attachmentId)
    {
//        $match = preg_match('/[^a-zA-Z0-9]/', $attachmentId); //因为系统中暂时存在 / 和 - 等特殊符号，暂时改成带.均不通过
        $match = preg_match('/\./', $attachmentId);
        if ($match) {
            return ['code' => ['0x000003', 'attachment']]; //非法字符串
        }

        $path = date("Y") . DIRECTORY_SEPARATOR . date("m") . DIRECTORY_SEPARATOR . date("d") . DIRECTORY_SEPARATOR . $attachmentId;

        return createCustomDir($path);
    }

    /**
     * 根据实体数据删除附件信息
     *
     * @param type $data
     * @return boolean
     * @throws Exception
     */
    public function deleteAttachmentByEntityId($data)
    {
        if (!isset($data['entity_table'])) {
            return false;
        }

        try {
            $tableName = $this->getRelationTableName($data['entity_table']);
            //判断表存在 不存在直接创建
            if (!app($this->attachmentRepository)->tableExists($tableName)) {
                return true;
            }
            if (isset($data['entity_id']) && $data['entity_id']) {
                $wheres = is_array($data['entity_id']) ? $data['entity_id'] : ["entity_id" => [$data['entity_id']]];
                $attachmentIds = $this->getAttachmentIds($tableName, $wheres);
                $this->deleteMoreAttachmentById($attachmentIds);
            }
            return true;
        } catch (\Exception $e) {
            throw new Exception(new JsonResponse(error_response('0x011009', $e->getMessage()), 500));
        }
    }

    /**
     * 获取附件缩略图
     *
     * @param type $attachmentId
     * @return type
     */
    public function getThumbAttach($attachmentId)
    {
        $attachment = $this->getOneAttachmentById($attachmentId);
        if (empty($attachment)) {
            return ['code' => ['0x011017', 'upload']]; //文件不存在
        }

        return $attachment['thumb_attachment_name'];
    }

    /**
     * 下载附件
     *
     * @param type $url
     * @param type $filename
     */
    private function downAttach($url, $filename)
    {
        $filename = preg_replace("#[\\\/\:\*\?\<\>\|]#",'_',$filename);
        ini_set('memory_limit', '-1');
        $ua = $_SERVER["HTTP_USER_AGENT"] ?? '';
        $encodedFilename = str_replace("+", "%20", urlencode($filename));
        header('Content-Type: application/octet-stream');
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encodedFilename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else if (preg_match("/rv/", $ua)) {
            $filename = $this->transEncoding($filename, 'GBK');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        readfile($url);
        exit;
    }

    /**
     * 获取一个附件信息
     * @param type $attachmentId
     * @param type $data
     * @return boolean
     */
    public function getAttachmentInfo($attachmentId, $data)
    {
        $attachment = $this->getOneAttachmentById($attachmentId);

        if (!$attachment) {
            return false;
        }
        $attachmentMark = $attachment["attachment_mark"];
        $url = $this->makeAttachmentFullPath($attachment['attachment_base_path'], $attachment['attachment_relative_path'], $attachment['affect_attachment_name'], true);
        if (!file_exists($url)) {
            $url = mb_convert_encoding($url, 'utf-8', 'gbk');
            if (!file_exists($url)) {
                return false;
            }
        }
        //如果是图片 需要进行需要展示不同尺寸的
        if ($attachmentMark == 1 && isset($data["picWidth"]) && isset($data["picHight"])) {
            $width = $data["picWidth"];
            $height = $data["picHight"];
            if ($width > 0 && $height > 0) {
                $pre = $width . "x" . $height . "_";
                $newSizePic = $this->makeAttachmentFullPath($attachment['attachment_base_path'], $attachment['attachment_relative_path'], $pre . $attachment['affect_attachment_name'], true);
                if (!file_exists($newSizePic)) {
                    scaleImage($url, $width, $height, $pre);
                }
                $url = $newSizePic;
            }
        }
        return ["url" => $url, "name" => $attachment['attachment_name'], 'attachment_mark' => $attachmentMark, 'type' => $attachment["attachment_type"]];
    }

    /**
     * 字符串转码
     * @param type $string
     * @param type $target
     * @return type
     */
    private function transEncoding($string, $target)
    {
        $encoding = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        return mb_convert_encoding($string, $target, $encoding);
    }

    /**
     * 清空目录
     *
     * @param type $folder
     */
    private function cleanFolder($folder)
    {
        $op = dir($folder);

        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (is_dir($op->path . '/' . $item)) {
                $this->cleanFolder($op->path . '/' . $item);
                rmdir($op->path . '/' . $item);
            } else {
                if (file_exists($op->path . '/' . $item)) {
                    unlink($op->path . '/' . $item);
                }
            }
        }
        try {
            rmdir($folder);
        } catch (Exception $e) {
            $this->cleanFolder($folder);
        }
    }

    public function getAttachmentRelations($entityTable, $fields, $wheres = [])
    {
        $relationTableName = $this->getRelationTableName($entityTable);

        return app($this->attachmentRepository)->getAttachmentRelations($relationTableName, $fields, $wheres);
    }

    public function getEntityIdsFromAttachRelTable($entityTable, $wheres = [])
    {
        $relations = $this->getAttachmentRelations($entityTable, ['entity_id'], $wheres);

        if (is_array($relations) && empty($relations)) {
            return [];
        }

        return array_column($relations->toArray(), 'entity_id');
    }

    /**
     * 根据附件名称获取实体ID
     * @param type $attachmentName
     * @param type $entityTable
     * @return type
     */
    public function getEntityIdsByAttachmentName($attachmentName, $entityTable, $parsePage = true)
    {
        $tableName = $this->getRelationTableName($entityTable);
        $params = ['search' => ['attachment_name' => [$attachmentName, 'like']]];
        if ($parsePage) {
            $params['parsePage'] = 1;
        }
        $attachmentRelSearch = app($this->attachmentRelSearchRepository)->getAttachmentList($params);
        $relIds = array_column($attachmentRelSearch->toArray(), 'rel_id');

        if (!empty($relIds)) {
            $attachmentRel = app($this->attachmentRelRepository)->getAttachmentRelList(['rel_id' => [$relIds, 'in']]);

            $entitys = app($this->attachmentRepository)->getEntityIdsByAttachmentIds(array_column($attachmentRel->toArray(), 'attachment_id'), $tableName);

            return array_column($entitys, 'entity_id');
        }

        return [];
    }

    /**
     * 判断文件是否可写
     * @param type $file
     * @return int
     */
    public function isWriteable($file)
    {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        }

        return $writeable;
    }

    /**
     * 处理附件数据结束
     *
     * @param type $attachmentInfo
     * @return type
     */
    public function handleAttachmentDataTerminal($attachmentInfo)
    {
        $result = $this->saveAttachmentData($attachmentInfo, function ($attachmentId, $year, $month) {
            $data = [
                'attachment_id' => $attachmentId,
                'year' => $year,
                'month' => $month
            ];
            return app($this->attachmentRelRepository)->insertData($data);
        }, function ($relId, $attachmentName, $attachmentType, $attachmentMark, $tableCode, $userId) {
            $data = [
                'rel_id' => $relId,
                'attachment_name' => $attachmentName,
                'attachment_type' => $attachmentType,
                'attachment_mark' => $attachmentMark,
                'rel_table_code' => $tableCode,
                'creator' => $userId
            ];
            return app($this->attachmentRelSearchRepository)->insertData($data);
        }, function ($tableName, $data) {
            return app($this->attachmentRepository)->saveAttachmentData($tableName, $data);
        });

        if ($result) {
            return [
                'attachment_id' => $attachmentInfo['attachment_id'],
                //'attachment_file' => $this->transEncoding($attachmentInfo['new_full_file_name'], 'utf-8'),
                'attachment_name' => $attachmentInfo['attachment_name']
            ];
        }

        return false;
    }

    /**
     * 创建附件关系表
     *
     * @param type $entityTableData
     * @return type
     */
    public function createAttachmentRelationTable($entityTableData)
    {
        //流程特别的  参考FlowService \ postFlowPublicAttachmentService
        if (isset($entityTableData["table_name"])) {
            $entityTable = $entityTableData["table_name"];
            $entityFields = $entityTableData["fileds"];
        } else {
            $entityTable = $entityTableData;
            $entityFields = [[
                "field_name" => "entity_id",
                "field_type" => "integer",
                "field_length" => "11",
                "field_comment" => "关联表记录ID",
            ]];
        }
        $relationTableName = $this->getRelationTableName($entityTable);

        app($this->attachmentRepository)->makeRelationTable($relationTableName, $entityFields);

        return [$entityTable, $relationTableName];
    }

    /**
     * 获取文档水印配置
     *
     * @param array $own
     * @return array
     */
    public function getDocumentWatermark($own)
    {
        /** @var SystemSecurityService $securityService */
        $securityService = app('App\EofficeApp\System\Security\Services\SystemSecurityService');
        $watermarkInfo = $securityService->getWatermarkSettingInfo(['parse' => 1], ['all'], $own);

        // 若未设置水印或者未开启水印 则水印不设置
        if (!isset($watermarkInfo['toggle']) || ($watermarkInfo['toggle'] === 'off')) {
            return ['isShowWatermark' => 0, 'watermarkConfig' => []];
        }

        if (($watermarkInfo['toggle'] == 'on') &&
            isset($watermarkInfo['scope']['document']) &&
            ($watermarkInfo['scope']['document'] == 1)) {

            $userId = $own['user_id'];
            /** @var UserRepository $userRepository */
            $userRepository= app('App\EofficeApp\User\Repositories\UserRepository');
            $userName = $userRepository->getUserName($userId);
            $value = $watermarkInfo['content_parse'] ?? $userId.$userName;
            $date = date("Y-m-d");
            $time = date("H:i:s");
            $value = str_replace("[DATE]", "{$date}", $value); // 当前日期
            $value = str_replace("[TIME]", "{$time}", $value); // 当前时间

            /**
             * OA系统中透明度取百分比, 永中为 0 - 1
             */
            $dmAlpha = intval($watermarkInfo['opaqueness']) / 100;
            if ($dmAlpha <= 0) {
                $dmAlpha = 0;
            } elseif ($dmAlpha >= 1) {
                $dmAlpha = 1;
            }
            /**
             * OA系统中旋转为逆时针旋转  永中为顺时针
             */
            $wmRotate = 360 - $watermarkInfo['rotate'];

            return [
                'isShowWatermark' => 1,
                'watermarkConfig' => [
                    'wmContent' => $value,
                    'wmTransparency' => $dmAlpha,   // 水印透明度
                    'wmRotate' => $wmRotate, // 水印旋转角度
                ],
            ];
        }

        return ['isShowWatermark' => 0, 'watermarkConfig' => []];
    }

    public function transToHtmlView($param, $own)
    {
        if (!isset($param['attachment_id'])) {
            return ['code' => ['0x011009', 'upload']];
        }
        $attachmentId = $param['attachment_id'];
        $tempAttachment = $this->getOneAttachmentById($attachmentId);
        if (empty($tempAttachment)) {
            return ['code' => ['0x011017', 'upload']]; //文件不存在
        }

        // ppt格式禁止复制和水印只能二选一
        $isPPT = false;
        if (isset($tempAttachment['attachment_type']) &&
            in_array($tempAttachment['attachment_type'], ['ppt', 'pptx', 'PPT', 'PPTX'])) {
            $isPPT = true;
        }

        $tomcatAddr = get_system_param('tomcat_addr');
        $tomcatAddrOut = get_system_param('tomcat_addr_out');
        $port = get_system_param('apache_listen_port');
        $deploy = get_system_param('deploy_service', 0);
//        $oaAddress = $deploy == 1 ? get_system_param('oa_addr_out') : 'http://127.0.0.1:'.$port;
        if ($deploy == 1) {
            // 在不同服务器部署
            $oaIntranet = get_system_param('oa_addr_inner'); // oa内网地址
            $oaExtranet = get_system_param('oa_addr_out'); // oa外网地址
            $oaIntranetIp = strpos($oaIntranet, '://') !== false ? substr($oaIntranet, strpos($oaIntranet, "://") + 3) : $oaIntranet;
            $oaExtranetIp = strpos($oaExtranet, '://') !== false ? substr($oaExtranet, strpos($oaExtranet, "://") + 3) : $oaExtranet;
            if ($_SERVER['HTTP_HOST'] == $oaIntranetIp) {
                $oaAddress = $oaIntranet;
                $tomcatAddrOut = $tomcatAddr;
            } elseif ($_SERVER['HTTP_HOST'] == $oaExtranetIp) {
                $oaAddress = $oaIntranet;
            } else {
                $oaAddress = $oaIntranet;
//                $oaAddress = 'http://127.0.0.1:'.$port; // 访问本地会出现问题
            }
        } else {
            $oaAddress = 'http://127.0.0.1:'.$port;
        }
        if (empty($tomcatAddr)) {
            return ['code' => ['0x011026', 'upload']];
        }
        if (empty($oaAddress)) {
            return ['code' => ['0x011022', 'upload']];
        }
        $addrFilter = strpos($tomcatAddr, "http://");
        if ($addrFilter !== 0) {
            $tomcatAddr = "http://" . $tomcatAddr;
        }
        $url = trim($tomcatAddr, '/') . "/dcs.web/onlinefile";
        // 检查tomcat是否启动
        try {
            file_get_contents($url);
        } catch (\Exception $e) {
            return ['code' => ['0x011025', 'upload']];
        }
        $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
        $token = Request::input('api_token');
        if (empty($token)) {
            $token = Request::bearerToken();
        }
        if (empty($token)) {
            $token = Request::getPassword();
        }
        $uri = $oaAddress . '/eoffice10/server/public/api/attachment/index/'.$attachmentId. '?api_token=' . $token.'&encrypt=0';
        // 从中间表查询是否有转换记录
        $record = DB::table('yozo_translate')->where('attachment_id', $attachmentId)->first();
        $addr = '';
        $flag = false;
        if (!empty($record) && isset($record->operate_count)) {
            if ($record->operate_count < 0) {
                // 文档修改过，重新转换
                $addr = $this->doTran($url, $uri, $domain, $attachmentId, $token, $own, $isPPT);
                if (isset($addr['code'])) {
                    return $addr;
                }
                DB::table('yozo_translate')->where('id', $record->id)->update(['attachment_addr' => $addr, 'operate_count' => 0]);
            } else {
                $addr = $record->attachment_addr;
                $file = trim($tomcatAddr, '/') . '/' . $addr;

                if (strpos($file, '?') !== false) {  // 若addr中拼接参数，则先过滤
                    $url = substr($file,0, strpos($file, '?'));
                } else { // 若无拼接参数 则直接请求缓存addr
                    $url = $file;
                }
                if ($resource = @fopen($url, 'r')) {
                    fclose($resource);
                    $file = str_replace($tomcatAddr, $tomcatAddrOut, $file);
                    // 动态水印需替换 ?watermark_txt=水印内容
                    $documentWatermark = $this->getDocumentWatermark($own);
                    $watermarkConfig = $documentWatermark['watermarkConfig'];
                    if ($documentWatermark['isShowWatermark'] && get_system_param('tomcat_watermark', 0)) {
                        if (strpos($file,'watermark_txt')) {
                            // 存在动态水印则替换
                            /** @var PathDealService $service */
                            $service = app('App\EofficeApp\Attachment\Services\PathDealService');
                            $file = $service->replaceUrlParam($file,'watermark_txt',urlencode($watermarkConfig['wmContent']));
                            $file = $service->replaceUrlParam($file,'watermark_alpha',$watermarkConfig['wmTransparency']);
                            $file = $service->replaceUrlParam($file,'watermark_angle',$watermarkConfig['wmRotate']);
                        }
                    } else {
                        if (strpos($file,'?watermark_txt')) {
                            // 如果存在动态水印参数则清空
                            /** @var PathDealService $service */
                            $service = app('App\EofficeApp\Attachment\Services\PathDealService');
                            $file = $service->replaceUrlParam($file,'watermark_txt', '');
//                            $file = $service->deleteUrlParam($file,'watermark_txt');
//                            $file = $service->deleteUrlParam($file,'watermark_alpha');
//                            $file = $service->deleteUrlParam($file,'watermark_angle');
                        }
                    }
                    return $file;
                } else {
                    $flag = true;
                    DB::table('yozo_translate')->where('attachment_id', $attachmentId)->delete();
                    $url = trim($tomcatAddr, '/') . "/dcs.web/onlinefile";
                }
            }
        } else {
            $flag = true;
        }

        if ($flag) {
            $addr = $this->doTran($url, $uri, $domain, $attachmentId, $token, $own, $isPPT);
            if (isset($addr['code'])) {
                return $addr;
            }
            DB::table('yozo_translate')->insert(['attachment_id' => $attachmentId, 'attachment_addr' => $addr]);
        }
        $str = trim($tomcatAddr, '/') . '/' . $addr;
        return str_replace($tomcatAddr, $tomcatAddrOut, $str);
    }

    public function doTran(&$url, &$uri, &$domain, &$attachmentId, &$token, $own, $isPPT = false)
    {
        $result = $this->onlinefile($url, $uri, $own, $isPPT);
        if (isset($result['code'])) {
            return $result;
        }
        if (is_string($result)) {
            $result = json_decode($result, true);
        }

        if (!isset($result['data'][0])) {
            $uri = trim($domain, '/') . '/eoffice10/server/public/api/attachment/index/' . $attachmentId . '?api_token=' . $token . '&encrypt=0';
            $result = $this->onlinefile($url, $uri, $own, $isPPT);
            if (isset($result['code'])) {
                return $result;
            }
            if (is_string($result)) {
                $result = json_decode($result, true);
            }
            if (!isset($result['data'][0])) {
                $error = ['code' => ['0x011024', 'upload']];
                if (isset($result['message']) && !empty($result['message'])) {
                    $error['dynamic'] = $result['message'];
                }
                return $error;
            }
        }
        $year = date('Y');
        return strchr($result['data'][0], $year);
    }

    /**
     * 获取永中在线文档查看
     *
     * @param string $uri
     * @param array $own
     * @param bool $isPPT
     * @return array
     */
    public function getPostData($uri, $own, $isPPT = false)
    {
        // 静态水印无法使用 暂时隐藏 后面改为动态水印
        $documentWatermark = $this->getDocumentWatermark($own);
        $postData = [
            'downloadUrl' => $uri,
            'convertType' => '0',
        ];

        if ($documentWatermark['isShowWatermark'] && get_system_param('tomcat_watermark', 0)) {
            $watermarkConfig = $documentWatermark['watermarkConfig'];
            $postData['dynamicMark'] = urlencode($watermarkConfig['wmContent']);  // 内容
            $postData['dmAlpha'] = $watermarkConfig['wmTransparency']; // 透明度
            $postData['dmAngle'] = $watermarkConfig['wmRotate'];   // 旋转角度
            $postData['dmXextra'] = 200;
            $postData['dmYextra'] = 200;

            // ppt格式文件水印在开启防复制情况下无法使用
            if (!$isPPT) {
                $postData['isCopy'] = get_system_param('tomcat_copy_disable', 1); // 是否防复制(1:是 0:否, 默认0)
            }
        } else {
            $postData['isCopy'] = get_system_param('tomcat_copy_disable', 1); // 是否防复制(1:是 0:否, 默认0)
        }

        return $postData;
    }

    /**
     * 请求永中插件
     *
     * @param string $url 永中插件请求地址
     * @param string $uri 附件下载地址
     * @param string $own 用户信息，用于生成水印
     * @param bool $isPPT 是否为ppt格式
     * @return bool|string|array
     */
    public function onlinefile(&$url, &$uri, $own, $isPPT = false)
    {
        try {
            // 使用curl获取
            $curl = curl_init();
            $timeout = 5;
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.82 Safari/537.36");
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            // 获取请求参数 (下载地址，转换方式，水印和防复制等)
            $postData = $this->getPostData($uri, $own, $isPPT);
            $postData = http_build_query($postData);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
            $file_contents = curl_exec($curl);
            if ($errorMsg = curl_error($curl)) {
                \Illuminate\Support\Facades\Log::error($errorMsg);
            }
            curl_close($curl);

            /**
             * 转换成功相应demo
             *      "{"result":0,"data":["http://localhost:8080/2021/04/14/MjEwNDE0NzA1NDU4MzM0.html?watermark_txt=系统管理员2021-04-14admin&watermark_x_extra=200&watermark_y_extra=200&watermark_alpha=1&watermark_angle=325"],"message":"转换成功","type":0}
             *  格式为
             *  {
             *    "result": 0,
             *    "data": [
             *        "http://localhost:8080/2021/04/14/MjEwNDE0NzA1NDU4MzM0.html?watermark_txt=系统管理员2021-04-14admin&watermark_x_extra=200&watermark_y_extra=200&watermark_alpha=1&watermark_angle=325"
             *     ],
             *    "message": "转换成功",
             *    "type": 0
             *  }
             */
            return $file_contents;
        } catch (\Exception $exception) {
            \Illuminate\Support\Facades\Log::error($exception->getMessage());
            \Illuminate\Support\Facades\Log::error($exception->getTraceAsString());

            return ['code' => ['0x011024', 'upload']];
        }
    }

    /**
     * 因system获取多个参数需反复查询数据库，废弃，查看systemParam模块service
     *
     * @deprecated
     * @return array
     */
    public function getOnlineReadOption()
    {
        return [
            'online_read_type' => get_system_param('online_read_type', 0),
            'tomcat_addr' => get_system_param('tomcat_addr'),
            'tomcat_addr_out' => get_system_param('tomcat_addr_out'),
            'tomcat_copy_disable' => get_system_param('tomcat_copy_disable', 1),
            'tomcat_watermark' => get_system_param('tomcat_watermark', 0),
            'apache_listen_port' => get_system_param('apache_listen_port'),
            'wps_app_id' => get_system_param('wps_app_id'),
            'wps_app_key' => get_system_param('wps_app_key'),
            'wps_parse_type' => get_system_param('wps_parse_type', 0),
            'wps_npapi_domain' => get_system_param('wps_npapi_domain', ''),
            'deploy_service' => get_system_param('deploy_service', 0),
            'oa_addr_inner' => get_system_param('oa_addr_inner'),
            'oa_addr_out' => get_system_param('oa_addr_out'),
        ];

    }

    public function saveOnlineReadOption($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                app('App\EofficeApp\System\Security\Repositories\SystemParamsRepository')->updateData([
                    'param_value' => $value
                ], [
                    'param_key' => $key
                ]);
            }
        }

        return true;
    }

    /**
     *  根据entityIds返回entity_id => [attachmentid, ...]
     * @return array
     */
    public function getAttachmentsByEntityIds($tableMark, $entityIds)
    {
        $result = [];
        list($tableMark, $tableName) = $this->createAttachmentRelationTable($tableMark);
        $lists = DB::table($tableName)->whereIn('entity_id', $entityIds)->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $index => $item) {
            if (!isset($result[$item->entity_id])) {
                $result[$item->entity_id] = [];
            }
            $result[$item->entity_id][] = $item->attachment_id;
        }
        return $result;
    }

    public function getPrintPower($attachmentId, $own)
    {
        $attachmentInfo = $this->getOneAttachmentById($attachmentId, false);
        if (isset($attachmentInfo['relation_table']) && $attachmentInfo['relation_table'] == "document_content") {
            $record = DB::table('attachment_relataion_document_content')->where('attachment_id', $attachmentId)->first();
            if (isset($record->entity_id) && $record->entity_id) {
                if (app('App\EofficeApp\Document\Services\DocumentService')->hasDownPurview($record->entity_id, $own)) {
                    return 'print';
                } else {
                    return 'shield';
                }
            } else {
                // word/excel类型的文档，传进来的附件id，在attachment_relataion_document_content查不到信息，附件id直接去文档主表查对应文档id，进而判断权限
                $record = DB::table('document_content')->where('content', $attachmentId)->first();
                if (isset($record->document_id) && $record->document_id) {
                    if (app('App\EofficeApp\Document\Services\DocumentService')->hasDownPurview($record->document_id, $own)) {
                        return 'print';
                    } else {
                        return 'shield';
                    }
                }
            }
        }
        return '';
    }

    /**
     * 根据附件id获取对应的图片路劲
     * @return string
     */
    public function getCustomerFace($attachmentId, $origin = false)
    {
        $paths = $this->getOneAttachmentById($attachmentId, false);
        $thumbPath = '';
        if (isset($paths['thumb_attachment_name'])) {
            $paths['attachment_base_path'] = $paths['attachment_base_path'] ?? getAttachmentDir('attachment');
            // 获取原图的base64数据流  默认获取缩略图  20200901修改 yml
            if ($origin) {
                $thumb = $paths['temp_src_file'];
            } else {
                $thumb = $paths['attachment_base_path'] . $paths['attachment_relative_path'] . DIRECTORY_SEPARATOR . $paths['thumb_attachment_name'];
            }
            if (file_exists($thumb)) {
                $thumbPath = $thumb;
            }
        }
        return !empty($thumbPath) ? imageToBase64($thumbPath) : '';
    }

    /**
     *判断文件名是否需要转码，需要转码返回转码名，不需要返回原编码名
     */
    private function getCurrentEnvironmentEncodingName($name)
    {
        try {
            $folder = createCustomDir('encodingtmp/' . $name);
            $this->cleanFolder($folder);
            return $name;
        } catch (\Exception $e) {
            $gbkName = $this->transEncoding($name, 'GBK'); //中文编码转换
            return $gbkName;
        }
    }

    /**
     * 分享文件，生成对我访问的token
     * @param $attachmentId 源附件id
     * @param $params =[
     *    'mode'=>1,//引用分享，2副本分享，
     *    'expire_days' //过期天数，不传永不过期
     * ]
     * @param $own
     * @return array
     */
    public function shareAttachment($attachmentId, $params, $userId)
    {
        $attachment = $this->getOneAttachmentById($attachmentId);
        if (!$attachment) {
            return ['code' => ['0x011017', 'upload']];
        }
        $mode = $params['mode'] ?? 1;//分享模式，1引用，2副本
        if (isset($params['expire_days']) && !empty($params['expire_days'])) {
            $days = $params['expire_days'];
            $expireDate = date('Y-m-d', time() + $days * 24 * 3600);//过期日期
        } else {
            $expireDate = null;//永不过期
        }
        //复制一份副本出来
        if ($mode == 2) {
            $shareAttachment = $this->attachmentSingleCopy(['source_attachment_id' => $attachmentId], ['user_id' => $userId]);
            //复制出错了
            if (isset($shareAttachment['code'])) {
                return $shareAttachment;
            }
            $shareAttachmentId = $shareAttachment['attachment_id'];
        } else {
            //引用源文件
            $shareAttachmentId = $attachment['attachment_id'];
        }
        $data = [
            'attachment_id' => $attachment['attachment_id'],
            'share_attachment_id' => $shareAttachmentId,
            'share_token' => $this->getShareToken($userId),
            'expire_date' => $expireDate,
            'user_id' => $userId
        ];
        return app($this->attachmentShareRepository)->insertData($data);
    }

    /**
     * 读取分享的附件
     */
    public function loadShareAttachment($shareToken,$params)
    {
        $wheres = [
            'share_token' => [$shareToken]
        ];
        $share = app($this->attachmentShareRepository)->getOneFieldInfo($wheres);
        if (!$share) {
            return ['code' => ['0x011017', 'upload']];
        }
        //分享链接过期
        if ($share->expire_date && $share->expire_date < date('Y-m-d')) {
            return ['code' => ['0x011017', 'upload']];
        }
        $shareAttachmentId = $share->share_attachment_id;
        //return $this->loadAttachment($shareAttachmentId, $params, true);
        return $this->loadAttachment($shareAttachmentId, $params, true,false);
    }

    /**
     * 删除某个分享的链接（失效）
     * @param $shareToken
     */
    public function deleteOneShareLink($shareToken)
    {
        $wheres = [
            'share_token' => [$shareToken]
        ];
        return app($this->attachmentShareRepository)->deleteByWhere($wheres);
    }

    /**
     * 删除某个附件所有额
     * @param $soucreAttachmentId 删除某个附件的所有分享链接，让所有分享失效
     */
    public function deleteAllShareLinks($soucreAttachmentId)
    {
        $wheres = [
            'attachment_id' => [$soucreAttachmentId]
        ];
        return app($this->attachmentShareRepository)->deleteByWhere($wheres);
    }

    /**
     * 分享出的链接不直接拼上id，生成访问的秘钥更安全，防盗链
     */
    private function getShareToken($userId)
    {
        $letter = ['h', 'i', 'j', 'k', 'l', 'm', 'n', 'x', 'y', 'z'];
        $token = uniqid() . '_' . md5($userId . time() . rand(10000, 99999));
        for ($i = 0; $i < strlen($token); $i++) {
            if (is_numeric($token[$i])) {
                $index = intval($token[$i]);
                if ($i % 5 != 0) {
                    $s = $letter[$index];
                    if ($i % 2 == 0) {
                        $s = strtoupper($s);
                    }
                    $token[$i] = $s;
                }
            }
        }
        return $token;
    }

    /**
     * 判断ocr接口是否配置
     * @return array|bool
     */
    public function getOcrConfig()
    {
        $config = (new ThirdPartyInterfaceService())->getTencentOcrConfig();
        if (empty($config)) {
            return ['code' => ['0x011029', 'upload']];
        }
        return true;
    }

    /**
     * 获取图片附件的 ocr 识别信息
     * @param $params
     * @return array|false|string
     */
    public function getOcrInfo($params)
    {
        $attachmentInfo = $this->getOneAttachmentById($params['attachment_id']);
        if (!in_array(strtolower($attachmentInfo['attachment_type']), ['jpeg', 'jpg', 'gif', 'gpeg', 'png'])) {
            return ['code' => ['0x011009', 'upload']]; //请求异常
        }
        // 百度API
        switch ($params['platform_key']) {
            case self::PLATFORM['baidu']:
                return $this->baiduOcr($attachmentInfo);
                break;
            case self::PLATFORM['tencent']:
                return $this->tencentOcr($attachmentInfo);
                break;
            case self::PLATFORM['youdao']:
                return $this->youdaoOcr($attachmentInfo);
                break;
            default:
                break;
        }
        //return $client->form(file_get_contents($attachmentInfo['temp_src_file']));
    }

    /**
     * 百度云 ocr 识别
     * @param $attachmentInfo
     * @return mixed
     */
    public function baiduOcr($attachmentInfo)
    {
        $http = new Client();
        $responseToken = $http->post('https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=ycM9IUceAZw8sb4gQV4c8779&client_secret=5FfVbfYcurxVYr1ZoGHyTdh54n5iScMD');

        $accessToken = (\GuzzleHttp\json_decode($responseToken->getBody()->getContents(), true))['access_token'];

        $response = $http->post('https://aip.baidubce.com/rest/2.0/ocr/v1/form?access_token=' . $accessToken, [
            'header' => ['Content-Type' => 'application/x-www-form-urlencoded'], 'form_params' => [
                'image' => base64_encode(file_get_contents($attachmentInfo['temp_src_file'])),
                'request_type' => 'json'
            ]
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;

    }

    /**
     * 腾讯表格OCR识别
     * @param $attachmentInfo
     * @return array|mixed
     */
    public function tencentOcr($attachmentInfo)
    {
        $config = (new ThirdPartyInterfaceService())->getTencentOcrConfig();
        if (empty($config)) {
            return ['code' => ['0x011029', 'upload']];
        }
        $secretId = $config['secret_id'];
        $secretKey = $config['secret_key'];
        try {
            $cred = new Credential($secretId, $secretKey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("ocr.tencentcloudapi.com");
            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new OcrClient($cred, "ap-shanghai", $clientProfile);
            $req = new TableOCRRequest();
            $imageBase64 = base64_encode(file_get_contents($attachmentInfo['temp_src_file']));
            $params = '{"ImageBase64": "' . $imageBase64 . '"}';
            $req->fromJsonString($params);
            $resp = $client->TableOCR($req);
            return json_decode($resp->toJsonString());
        } catch (TencentCloudSDKException $e) {
            Log::error($e->getMessage());
            return ['code' => ['0x011030', 'upload'], 'dynamic' => trans('upload.0x011030', ['message' => $e->getMessage()])];
        }
    }
    // 有道云ocr sdk
    public function youdaoOcr($attachmentInfo)
    {
        $result =  $this->do_request(base64_encode(file_get_contents($attachmentInfo['temp_src_file'])));
        return $result['Result'];
    }

    // 有道云ocr sdk
    private function do_request($q)
    {
        $salt = $this->create_guid();
        $args = array(
            'appKey' => self::APP_KEY,
            'salt' => $salt,
        );
        $type = '1';
        $args['type'] = $type;
        $args['q'] = $q;
        $args['docType'] = 'json';
        $args['signType'] = 'v3';
        $curtime = strtotime("now");
        $args['curtime'] = $curtime;
        $signStr = self::APP_KEY . $this->truncate($q) . $salt . $curtime . self::SEC_KEY;
        $args['sign'] = hash("sha256", $signStr);
        $ret = $this->call(self::URL, $args);
        $ret = json_decode($ret, true);
        return $ret;
    }

    // 有道云ocr sdk
    private function call($url, $args = null, $method = "post", $testflag = 0, $timeout = self::CURL_TIMEOUT, $headers = array())
    {
        $ret = false;
        $i = 0;
        while ($ret === false) {
            if ($i > 1)
                break;
            if ($i > 0) {
                sleep(1);
            }
            $ret = $this->callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }
        return $ret;
    }
    // 有道云ocr sdk
    private function callOnce($url, $args = null, $method = "post", $withCookie = false, $timeout = self::CURL_TIMEOUT, $headers = array())
    {
        $ch = curl_init();
        if ($method == "post") {
            $data = $this->convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            $data = $this->convert($args);
            if ($data) {
                if (stripos($url, "?") > 0) {
                    $url .= "&$data";
                } else {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }
    // 有道云ocr sdk
    private function convert(&$args)
    {
        $data = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $data .= $key . '[' . $k . ']=' . rawurlencode($v) . '&';
                    }
                } else {
                    $data .= "$key=" . rawurlencode($val) . "&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }

    // uuid generator
    private function create_guid()
    {
        $microTime = microtime();
        list($a_dec, $a_sec) = explode(" ", $microTime);
        $dec_hex = dechex($a_dec * 1000000);
        $sec_hex = dechex($a_sec);
        $this->ensure_length($dec_hex, 5);
        $this->ensure_length($sec_hex, 6);
        $guid = "";
        $guid .= $dec_hex;
        $guid .= $this->create_guid_section(3);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $sec_hex;
        $guid .= $this->create_guid_section(6);
        return $guid;
    }
    // 有道云ocr sdk
    private function truncate($q)
    {
        $len = $this->abslength($q);
        return $len <= 20 ? $q : (mb_substr($q, 0, 10) . $len . mb_substr($q, $len - 10, $len));
    }
    // 有道云ocr sdk
    private function abslength($str)
    {
        if (empty($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        } else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }
    // 有道云ocr sdk
    private function ensure_length(&$string, $length)
    {
        $strlen = strlen($string);
        if ($strlen < $length) {
            $string = str_pad($string, $length, "0");
        } else if ($strlen > $length) {
            $string = substr($string, 0, $length);
        }
    }
    // 有道云ocr sdk
    private function create_guid_section($characters)
    {
        $return = "";
        for ($i = 0; $i < $characters; $i++) {
            $return .= dechex(mt_rand(0, 15));
        }
        return $return;
    }
    /**
     * 迁移附件目录
     * @param type $data
     * @return boolean
     */
    public function migrateAttachmentPath($data)
    {
        if (!isset($data['source_path']) || !isset($data['desc_path'])) {
            return false;
        }
        // 记录修改的日志
        $logerPath = storage_path('logs/') . 'attachment_migrate.log';
        file_put_contents($logerPath, date('Y-m-d H:i:s'). ' >> source_path:'. $data['source_path'].' -- desc_path:' . $data['desc_path'] . "\r\n", FILE_APPEND);

        $sourcePath = $data['source_path'] ? rtrim($data['source_path'], '/') . '/' : '';
        $descPath = $data['desc_path'] ? rtrim($data['desc_path'], '/') . '/' : '';
        if($sourcePath === $descPath) {
            return true;
        }
        // 获取所有附件分表，并更新附件目录
        $attachmentTables = app($this->attachmentRelRepository)->getAttachmentTables();
        if (!empty($attachmentTables)) {
            $attachmentRepository = app($this->attachmentRepository);
            return array_map(function($table) use ($sourcePath, $descPath, $attachmentRepository) {
                return $attachmentRepository->migrateAttachmentPath($table, $sourcePath, $descPath);
            }, $attachmentTables);
        }

        return true;
    }
}
