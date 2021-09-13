<?php

namespace App\EofficeApp\IWebOffice\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\Document\DocumentManager;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\IWebOffice\Configurations\Constants;
use DB;
use Illuminate\Support\Facades\Redis;
use App\Utils\Utils;

class IWebOfficeService extends BaseService
{
    private $attachmentRepository;
    private $userRepository;
    private $attachmentService;
    private $result = [
        'file'     => '',
        'body'     => '',
        'bodySize' => '',
        'fileSize' => '',
        'msgError' => '',
    ];

    public function __construct() {
        parent::__construct();

        $this->attachmentRepository = 'App\EofficeApp\Attachment\Repositories\AttachmentRepository';
        $this->userRepository       = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->attachmentService    = 'App\EofficeApp\Attachment\Services\AttachmentService';
    }

    public function download($attachmentId, $mime)
    {
        $url = $this->getMovePath($attachmentId, true);
        if (isset($url['code'])) {
            return $url;
        }

        if (!(file_exists($url) && is_file($url))) {
            return ['code' => ['0x011017', 'upload']]; //文件异常
        }

        if (!function_exists('finfo_open')) {
            return ['code' => ['0x011018', 'upload']]; //异常
        }

        $file = fopen($url, "r");

        if ($mime == "doc" || $mime == "docx") {
            header('Content-Type: application/msword');
        } else if ($mime == "xls" || $mime == "csv") {
            header('Content-Type: application/vnd.ms-excel');
        }

        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($url));
        header("Content-Disposition: attachment; filename=\"" . $attachmentId . ".$mime\"");

        echo fread($file, filesize($url));

        fclose($file);

        return true;
    }
    public function fileExists($params){
        $attachmentId = $params['attachment_id'];

        $url = $this->getMovePath($attachmentId, true);
        if (isset($url['code'])) {
            return $url;
        }

        if (!(file_exists($url) && is_file($url))) {
            return ['code' => ['0x011017', 'upload']]; //文件异常
        }else{
            $attachmentInfo = app($this->attachmentService)->getOneAttachmentById($attachmentId, false);

            if(!empty($attachmentInfo)){
                $max = envOverload('IWEBOFFICE_MAX_SIZE', 64);
                // 设置最大打开64M的文件
                $maxSize = $max * 1024 * 1024;
                if ($maxSize < $attachmentInfo['attachment_size']) {
                    return ['code' => ['file_is_too_large_to_open_online', 'iweboffice']];
                }
                return array_merge($attachmentInfo, ['max' => $max]);
                // $memory = ini_get('memory_limit');
                // preg_match('/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i', $memory, $matches);
                // if (isset($matches[2])) {
                //     $maxSize = $matches[1];
                //     $mSize = '';
                //     if (!empty($matches[2])) {
                //         if (strtolower($matches[2]) == 'm') {
                //             $maxSize = $matches[1] * 1024 * 1024;
                //             $mSize = $matches[1];
                //         } elseif (strtolower($matches[2]) == 'g') {
                //             $maxSize = $matches[1] * 1024 * 1024 * 1024;
                //             $mSize = $matches[1] * 1024;
                //         } elseif (strtolower($matches[2]) == 'k') {
                //             $maxSize = $matches[1] * 1024;
                //             $mSize = $matches[1] / 1024;
                //         }
                //     }
                //     if ($maxSize < $attachmentInfo['attachment_size']) {
                //         return ['code' => ['file_is_too_large_to_open_online', 'iweboffice']];
                //     }
                //     return ['max' => $mSize];
                // } else {
                //     return ['max' => ''];
                // }
            } else {
                return ['code' => ['0x011017', 'upload']];
            }
        }
    }

    public function loadfile($params)
    {
        $attachmentId = isset($params['RECORDID']) ? $params['RECORDID'] : '';

        $attachmentName = isset($params['FILENAME']) ? $params['FILENAME'] : '';

        $file = $this->getMovePath($attachmentId, true, $attachmentName);
        if (isset($file['code'])) {
            $message = trans($file['code'][1].'.'.$file['code'][0]);
            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnXml();
        }

        //$file = $movePath . $attachmentName;
        if (!file_exists($file)) {
            $this->result['msgError'] = trans("iweboffice.file_not_exist") . $file; //设置错误信息
        } else {
            $message = '';

            try {
                $this->result['fileSize'] = filesize($file);

                // $this->result['fileSize'] = filesize($file);

                // $this->result['file'] = fread($fd, filesize($file));

                // fclose($fd);
            } catch(\Exception $e) {
                return ['code' => ['failed_or_not_exist', 'iweboffice']];
            }

            $message = $message . "STATUS=" . base64_encode(trans("iweboffice.document_opened_success")) . "\r\n"; //设置状态信息

            $this->result['body'] = $message;

            $this->result['bodySize'] = strlen($message);
        }

        return $this->returnXml(true, $file);
    }
    private function readTheFile($path) {
        $handle = fopen($path, "r");

        while(!feof($handle)) {
            yield fgets($handle);
        }

        fclose($handle);
    }
    public function loadtemplate($params)
    {
        $template  = $params['TEMPLATE']; //取得模板文档类型

        $attachments = app($this->attachmentService)->getOneAttachmentById($template, false);
        $mFileName = isset($attachments['attachment_name']) ? $attachments['attachment_name'] : '';

        $templatePath = $this->getMovePath($template);
        if (isset($templatePath['code'])) {
            $message = trans($templatePath['code'][1].'.'.$templatePath['code'][0]);
            $this->result['msgError'] = $message;

            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnXml();
        }

        $templateFile = $templatePath . $mFileName;

        if (isset($params['COMMAND']) && $params['COMMAND'] == "INSERTFILE") {
            $message = "result=" . base64_encode("result") . "\r\n";

            if (!file_exists($templateFile)) {
                $this->result['msgError'] = trans("iweboffice.template_not_exist") . $templateFile; //设置错误信息
            } else {
                $fd = fopen($templateFile, "rb");

                $this->result['fileSize'] = filesize($templateFile);
                $this->result['file']     = fread($fd, filesize($templateFile));

                fclose($fd);

                $message .= "STATUS=" . base64_encode(trans("iweboffice.template_opened_success")) . "\r\n";
            }

            $message .= "PATH=" . base64_encode(true) . "\r\n";


        } else {
            //从目录里调出模板
            $message = "sun=" . base64_encode('sun') . "\r\n";

            if (!file_exists($templateFile)) {
                $this->result['msgError'] = trans("iweboffice.template_not_exist") . $templateFile; //设置错误信息
            } else {
                $fd = fopen($templateFile, "rb");

                $this->result['fileSize'] = filesize($templateFile);
                $this->result['file']     = fread($fd, filesize($templateFile));

                fclose($fd);

                $message .= "STATUS=" . base64_encode(trans("iweboffice.template_opened_success")) . "\r\n";
            }
        }

        $this->result['body']     = $message;
        $this->result['bodySize'] = strlen($message);

        return $this->returnXml();
    }
    public function loadmarklist($params, $own)
    {
        $mUserName = app($this->userRepository)->getUserName($own['user_id']);

        $list = DB::table('signature')->select('signature_id', 'signature_name')->where('signature_onwer', $own['user_id'])->orderBy('signature_name')->get();

        $mMarkList = "";
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $mMarkList = $mMarkList . $value->signature_name . "\n";
            }
        }

        $this->result['body'] .= "MARKLIST=" . base64_encode($mMarkList) . "\r\n";
        $this->result['bodySize'] = strlen($this->result['body']);
        $this->result['fileSize'] = 0;
        $this->result['msgError'] = '';

        return $this->returnXml();
    }

    public function loadmarkimage($params, $own)
    {
        $mMarkName = $params['IMAGENAME']; //取得签名名称
        $mPassWord = isset($params['PASSWORD']) ? $params['PASSWORD'] : ''; //取得用户密码

        $temp = DB::table('signature')->select('signature_picture')
            ->where('signature_onwer', $own['user_id'])
            ->where('signature_name', $mMarkName)
            ->first();
        if(!empty($temp)){
            $list = app($this->attachmentService)->getOneAttachmentById($temp->signature_picture);
        } else {
            $this->result['msgError'] = trans("iweboffice.seal_not_exist_or_wrong_pwd"); //设置错误信息
            $result                   = false;
        }

        if (isset($list) && !empty($list)) {
            $filePath = $list['attachment_relative_path'];
            $fileName = $list['affect_attachment_name'];
            $fileType = $list['attachment_type'];


            $mFullPath = iconv("utf-8", "gbk", (getAttachmentDir() . $filePath . $fileName));

            $fp = fopen($mFullPath, "rb");
            if (!$fp) {
                $this->result['msgError'] = trans("iweboffice.failed_or_not_exist");
            }

            $this->result['fileSize'] = filesize($mFullPath);

            $this->result['file'] = fread($fp, filesize($mFullPath));

            fclose($fp);

            $this->result['body'] .= "IMAGETYPE=" . base64_encode('.' . $fileType) . "\r\n";
            $this->result['body'] .= "POSITION=" . base64_encode("Manager") . "\r\n";
            /** @var IWebOfficeConfigService $configService */
            $configService = app('App\EofficeApp\IWebOffice\Services\IWebOfficeConfigService');
            $this->result['body'] .= "ZORDER=" . base64_encode($configService->getSignatureStyle()) . "\r\n";
            $this->result['bodySize'] = strlen($this->result['body']);

        } else {
            $this->result['msgError'] = trans("iweboffice.seal_not_exist_or_wrong_pwd"); //设置错误信息
            $result                   = false;
        }

        return $this->returnXml();
    }

    public function savesignature($params)
    {
        $mRecordID = $params['RECORDID'];
        $mFileName = $params['FILENAME']; //取得文件名称
        $mMarkName = $params['MARKNAME']; //取得签名名称
        $mUserName = $params['USERNAME']; //取得用户名称
        $mDateTime = $params['DATETIME']; //取得签名时间
        $mHostName = $_SERVER["REMOTE_ADDR"]; //取得用户IP
        $mMarkGuid = $params['MARKGUID']; //取得唯一编号
        //保存签章基本信息
        $data = [
            'RecordID' => $mRecordID,
            'MarkName' => $mMarkName,
            'UserName' => $mUserName,
            'DateTime' => $mDateTime,
            'HostName' => $mHostName,
            'MarkGuid' => $mMarkGuid,
        ];

        if (DB::table('document_signature')->insert($data)) {
            $result = true;
        } else {
            $result = false;
        }

        if ($result) {
            $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.saved_successfully")) . "\r\n"; //设置状态信息ss
        } else {
            $this->result['msgError'] = trans("iweboffice.failed_to_save"); //设置错误信息
        }

        return $this->returnXml();
    }

    public function loadsignature($params)
    {
        $mRecordID = $params['RECORDID'];
        $mMarkName = trans("iweboffice.seal_name")."\n";
        $mUserName = trans("iweboffice.signer")."\n";
        $mDateTime = trans("iweboffice.signing_time")."\n";
        $mHostName = trans("iweboffice.client_ip")."\n";
        $mMarkGuid = trans("iweboffice.serial_number")."\n";
        //查找记录是否存在
        $list = DB::table('document_signature')->select('MarkName', 'UserName', 'DateTime', 'HostName', 'MarkGuid')->where('RecordID', $mRecordID)->get();

        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $mMarkName .= $value->MarkName . "\n"; //文件号列表
                $mUserName .= $value->UserName . "\n"; //日期列表
                $mDateTime .= $value->DateTime . "\n"; //用户名列表
                $mHostName .= $value->HostName . "\n"; //如果说明信息里有回车，则将回车变成>符号
                $mMarkGuid .= $value->MarkGuid . "\n"; //取得唯一编号
            }
        }

        $this->result['body'] .= "MARKNAME=" . base64_encode($mMarkName) . "\r\n"; //将签名名称列表打包
        $this->result['body'] .= "USERNAME=" . base64_encode($mUserName) . "\r\n"; //将用户名列表打包
        $this->result['body'] .= "DATETIME=" . base64_encode($mDateTime) . "\r\n"; //将时间列表打包
        $this->result['body'] .= "HOSTNAME=" . base64_encode($mHostName) . "\r\n"; //将说明信息列表打包
        $this->result['body'] .= "MARKGUID=" . base64_encode($mMarkGuid) . "\r\n"; //取得唯一编号
        $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.successfully_tuned_in")) . "\r\n"; //设置状态信息
        $this->result['bodySize'] = strlen($this->result['body']);
        $this->result['fileSize'] = 0;
        $this->result['msgError'] = '';

        return $this->returnXml();
    }
    public function listversion($params)
    {
        $mRecordID = $params['RECORDID'];
        $mFilePath = $this->getMovePath($mRecordID, true);
        if (isset($mFilePath['code'])) {
            $message = trans($mFilePath['code'][1].'.'.$mFilePath['code'][0]);
            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnXml();
        }

        $mFileID   = "\n";
        $mDateTime = trans("iweboffice.save_time")."\n";
        $mUserName = trans("iweboffice.user_name")."\n";
        $mDescript = trans("iweboffice.release_notes")."\n";

        $list = DB::table('Version_File')->select('FileID', 'FileDate', 'UserName', 'Descript')->where('RecordID', $mRecordID)->get();

        if (!empty($list)) {
            //生成版本列表
            foreach ($list as $key => $value) {

                $mFileID = $mFileID . $value->FileID . "\n"; //文件号列表

                $mDateTime = $mDateTime . $value->FileDate . "\n"; //日期列表

                $mUserName = $mUserName . $value->UserName . "\n"; //用户名列表

                $mDescript = $mDescript . $value->Descript . "\n"; //如果说明信息里有回车，则将回车变成>符号
            }

        }
        $this->result['body'] .= "FILEID=" . base64_encode($mFileID) . "\r\n";
        $this->result['body'] .= "DATETIME=" . base64_encode($mDateTime) . "\r\n";
        $this->result['body'] .= "USERNAME=" . base64_encode($mUserName) . "\r\n";
        $this->result['body'] .= "DESCRIPT=" . base64_encode($mDescript) . "\r\n";
        $this->result['body'] .= "STATUS=" . base64_encode("!") . "\r\n";
        $this->result['bodySize'] = strlen($this->result['body']);
        $this->result['fileSize'] = filesize($mFilePath);
        $this->result['msgError'] = '';

        return $this->returnXml();
    }
    public function loadversion($params)
    {
        $mRecordID = $params['RECORDID'];
        $mFileID   = $params['FILEID']; //取得版本文档号
        $mFileName = $params['FILENAME'];
        $mFullPath = $this->getMovePath($mRecordID);
        if (isset($mFullPath['code'])) {
            $message = trans($mFullPath['code'][1].'.'.$mFullPath['code'][0]);
            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnXml();
        }

        $lists = DB::table('Version_File')->where('RecordID', $mRecordID)->where('FileID', $mFileID)->first();

        if (!empty($lists)) {
            if (!is_file($mFullPath)) {
                $mFullPath .= $lists->FileName;
            }

            $result = file_exists($mFullPath);

            $this->result['body'] .= "FPATH=" . base64_encode($mFullPath) . "\r\n";
            $this->result['body'] .= "FID=" . base64_encode($mFileID) . "\r\n";

            if (!$result) {
                $this->result['msgError'] = trans("iweboffice.file_not_exist") . $mFullPath; //设置错误信息
            } else {
                $fd                       = fopen($mFullPath, "rb");
                $this->result['fileSize'] = filesize($mFullPath);
                $this->result['file']     = fread($fd, filesize($mFullPath));
                fclose($fd);
            }
        } else {
            $this->result['msgError'] = trans("iweboffice.record_not_exist");
            $result                   = false;
        }

        if ($result) {
            $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.open_version_success")) . "\r\n"; //设置状态信息
            $this->result['bodySize'] = strlen($this->result['body']);
            $this->result['msgError'] = "";
        }

        return $this->returnXml();
    }
    public function saveversion($params)
    {
        $mRecordID = $params['RECORDID'];
        $mUserName = $params['USERNAME']; //取得用户名称
        $mFileName = $params['FILENAME']; //取得文档名称
        $mFileType = $params['FILETYPE']; //取得文档类型
        $mFilePath = $this->getMovePath($mRecordID, true);
        if (isset($mFilePath['code'])) {
            $message = trans($mFilePath['code'][1].'.'.$mFilePath['code'][0]);
            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnXml();
        }
        if (is_file($mFilePath)) {
            $mFilePath = str_replace($mFileName, "", $mFilePath);
        }
        $mDescript = $params['DESCRIPT']; //版本信息
        $mFileDate = date("Y-m-d H:i:s"); //保存版本文档

        $insertData = [
            'RecordID' => $mRecordID,
            'UserName' => $mUserName,
            'FilePath' => $mFilePath,
            'FileType' => $mFileType,
            'Descript' => $mDescript,
            'FileDate' => $mFileDate,
        ];

        if (DB::table('Version_File')->insert($insertData)) {
            $result = true;
        } else {
            $result = false;
        }

        $maxFieldId = DB::table('Version_File')->selectRaw('Max(FileID) as FileID')->where('RecordID', $mRecordID)->first();

        if (!empty($maxFieldId)) //如果存在，则更新该记录
        {
            $mFileID = $maxFieldId->FileID;
        }

        $mFileName = $mRecordID . $mFileID . $mFileType;

        if (move_uploaded_file($_FILES['MsgFileBody']['tmp_name'], $mFilePath.$mFileName)) {
            //保存文件到指定目录

            $mFileSize = $_FILES['MsgFileBody']['size']; //取得文档大小
            $result    = true;

        } else {
            $this->result['msgError'] = trans("iweboffice.failed_to_save");
            $result                   = false;
        }


        $updateData = [
            'FileName' => $mFileName,
            'FileSize' => $mFileSize,
        ];
        if (DB::table('Version_File')->where('RecordID', $mRecordID)->where('FileID', $mFileID)->update($updateData)) {
            $result = true;
        } else {
            $result = false;
        }

        if ($result) {
            $this->result['body'] = "STATUS=" . base64_encode(trans("iweboffice.save_version_success")) . "\r\n"; //设置状态信息
            $this->result['bodySize'] = strlen($this->result['body']);
        }

        return $this->returnXml();
    }
    public function sendmessage($params)
    {
        $mCommand = isset($_POST['COMMAND'])?$_POST['COMMAND']:''; //取得操作类型 InportText
        $mInput   = isset($_POST['CONTENT'])?$_POST['CONTENT']:'';
        $mContent = trans("iweboffice.test_chinese");
        if ($mCommand == "TEST") {
            // $MsgObj=$MsgObj."CONTENT=".base64_encode("111")."\r\n";
            $this->result['body'] .= "CONTENT=" . base64_encode($mContent) . "\r\n";
            $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.test_successfully")) . "\r\n";
        } elseif ($mCommand == "ZW") {
            //$MsgObj=$MsgObj."CONTENT=".base64_encode("This is ZhongWen! 好的")."\r\n";
            $this->result['body'] .= "CONT=" . base64_encode($mInput) . "\r\n";
            $this->result['body'] .= "STATUS=" . base64_encode("This is ZhongWen!") . "\r\n";
        } elseif ($mCommand == "INPORTTEXT") {
            $this->result['body'] .= "CONTENT=" . base64_encode($mInput) . "\r\n";
            $this->result['body'] .= "STATUS=" . base64_encode("This is English!") . "\r\n";
        } else {
            $this->result['body'] .= "CONTENT=" . base64_encode("NO Message") . "\r\n";
            $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.no_command_value")) . "\r\n";
        }
        // 判断是否显示痕迹
        if(isset($params['revisions'])){
            $data = [
                'attachment_id' => $params['RECORDID'],
                'revision'    => $params['revisions']
            ];

            if(DB::table('document_set')->where('attachment_id', $params['RECORDID'])->count()){
                DB::table('document_set')->where('attachment_id', $params['RECORDID'])->update($data);
            }else{
                DB::table('document_set')->insert($data);
            }
        }
        // 判断是否允许拷贝
        if(isset($params['copy_power'])){
            $data = [
                'attachment_id' => $params['RECORDID'],
                'copy_power'    => $params['copy_power']
            ];

            if(DB::table('document_set')->where('attachment_id', $params['RECORDID'])->count()){
                DB::table('document_set')->where('attachment_id', $params['RECORDID'])->update($data);
            }else{
                DB::table('document_set')->insert($data);
            }
        }

        return $this->returnXml();
    }
    public function insertimage($params)
    {
        $mRecordID = $params['RECORDID'];
        $mLabelName = $params['LABELNAME']; //标签名

        $mImageName = $params['IMAGENAME']; //图片名

        $mFullPath = $mFilePath . "/" . $mImageName; //图片在服务器的完整路径

        $mFileType = ".jpg"; //获得图片的格式类型，如jpg
        $result    = file_exists($mFullPath);
        if ($result) {
            $fd        = fopen($mFullPath, "rb");
            $mFileSize = filesize($mFullPath);
            $mFileBody = fread($fd, filesize($mFullPath));
            fclose($fd);
            //指定图片的类型
            $this->result['body'] .= "IMAGETYPE=" . base64_encode($mFileType) . "\r\n";
            //设置插入的位置[书签对象名]
            $this->result['body'] .= "POSITION=" . base64_encode($mLabelName) . "\r\n";
            //设置状态信息
            $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.insert_picture_successfully")) . "\r\n";
            //清除错误信息
            $this->result['msgError'] = "";
        } else {
            $this->result['msgError'] = trans("iweboffice.fail_to_import_picture");
        }

        return $this->returnXml();
    }
    public function savetemplate($params)
    {
        $mRecordID  = $params['TEMPLATE']; //取得文档编号
        $mFileName  = $params['FILENAME']; //取得文档名称
        $mFileType  = $params['FILETYPE']; //取得文档类型
        $mFullPath  = $mFilePath . "/" . $mRecordID . $mFileType; //设置要保存的目录和文件名称
        $mFullPath  = iconv("utf-8", "gbk", $mFullPath);
        $mFileType2 = ".docx";
        if ($mFileType == $mFileType2) {
            $mFileType2 = ".doc";
        }

        $mFullPath2 = $mFilePath . "/" . $mRecordID . $mFileType2;
        $mFullPath2 = iconv("utf-8", "gbk", $mFullPath2);
        $this->result['body'] .= "mFullPath=" . base64_encode("mFullPath") . "\r\n";
        $mDescript = $params['DESCRIPT']; //版本信息
        $mFileDate = $params['FileDate']; //取得文档时间
        $mUserName = $params['UserName']; //取得保存用户名称
        if (is_uploaded_file($_FILES['MsgFileBody']['tmp_name'])) {
            //保存文档内容
            if (move_uploaded_file($_FILES['MsgFileBody']['tmp_name'], $mFullPath)) {
                copy($mFullPath, $mFullPath2);
                $mFileSize = $_FILES['MsgFileBody']['size']; //取得文档大小
                $result    = true;
            } else {
                $this->result['msgError'] = "Save File Error"; //设置错误信息
                $result                   = false;
            }
        } else {
            $this->result['msgError'] = "Uploaded_File Error"; //设置错误信息
            $result                   = false;
        }

        $list = DB::table('template_file')->where('RecordID', $mRecordID)->first();

        // $rs = exequery($connection, $mSql);
        if (!empty($list)) {
            //如果存在，则更新该记录
            $updateData = [
                'FileName' => $mFileName,
                'FileType' => $mFileType,
                'FileSize' => $mFileSize,
                'FileDate' => $mFileDate,
                'FileType' => $mFileType,
                'FilePath' => $mFilePath,
                'UserName' => $mUserName,
                'Descript' => $mDescript,
            ];
            $query = DB::table('template_file')->where('RecordID', $mRecordID)->update($updateData);
            // $query = "update Template_File set FileName='" . $mFileName . "',FileType='" . $mFileType . "',FileSize=" . $mFileSize . ",FileDate='" . $mFileDate . "',FileType='" . $mFileType . "',FilePath='" . $mFilePath . "',UserName='" . $mUserName . "',Descript='" . $mDescript . "' where RecordID='" . $mRecordID . "'";
        } else {
            //如果不存在，则插入该记录
            $insertData = [
                'RecordID' => $mRecordID,
                'FileName' => $mFileName,
                'FileType' => $mFileType,
                'FileSize' => $mFileSize,
                'FileDate' => $mFileDate,
                'FileType' => $mFileType,
                'FilePath' => $mFilePath,
                'UserName' => $mUserName,
                'Descript' => $mDescript,
            ];
            $query = DB::table('template_file')->insert($insertData);
            // $mSql = "insert into Template_File (RecordID, FileName, FileType, FileSize, FileDate, FilePath, UserName, Descript) values ('" . $mRecordID . "','" . $mFileName . "','" . $mFileType . "','" . $mFileSize . "','" . $mFileDate . "','" . $mFilePath . "','" . $mUserName . "','" . $mDescript . "')";
        }

        if ($query) {
            $result = true;
        } else {
            $result = false;
        }

        if ($result) {
            $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.saved_successfully") . $mFullPath) . "\r\n";
            //设置状态信息
        }
    }

    /**
     * 保存文件
     *
     * @param array $params
     * @param $file
     */
    public function savefile($params, $file)
    {
        if ($file->isValid()) {

            $attachmentId = $params['RECORDID'];

            $ext = $this->getSuffix($params['FILETYPE']);
            // 获取附件路径
            $movePath = $this->getMovePath($attachmentId);
            if (isset($movePath['code'])) {
                $message = trans($movePath['code'][1].'.'.$movePath['code'][0]);
                $this->result['body'] = $message;
                $this->result['bodySize'] = strlen($message);
                return $this->returnXml();
            }

            $fileName = explode('.', $params['FILENAME']);

            $attachmentId = isset($fileName[0]) ? $fileName[0] : '';

            $attachmentName = app($this->attachmentService)->getOneAttachmentById($attachmentId, false);
            $affectAttachmentName = $params['RECORDID'].$params['FILETYPE'];

            // 下面要update attachment，在这里重新生成 attachment_name ，为了解决用了模板之后，文件类型变了的问题。--dp-20180823
            if(empty($attachmentName)) {
                $newAttachmentName = $affectAttachmentName;
            } else {
                $newAttachmentName = "";
                if(isset($attachmentName[0])) {
                    $newAttachmentNameTemplate = $attachmentName[0]['attachment_name'];
                } else {
                    $newAttachmentNameTemplate = $attachmentName['attachment_name'];
                }
                if($newAttachmentNameTemplate) {
                    $newAttachmentNameTemplateArray = pathinfo($newAttachmentNameTemplate);
                    $newAttachmentFileName = isset($newAttachmentNameTemplateArray["filename"]) ? $newAttachmentNameTemplateArray["filename"] : "";
                    if($newAttachmentFileName && $params['FILETYPE']) {
                        $newAttachmentName = $newAttachmentFileName.$params['FILETYPE'];
                    } else {
                        $newAttachmentName = $affectAttachmentName;
                    }
                }
            }
            $fileSize = $file->getSize();

            $result = $file->move($movePath, $affectAttachmentName);

            if ($result) {
                $this->result['body'] = "STATUS=" . base64_encode(trans("iweboffice.saved_successfully")) . "\r\n";

                $this->result['bodySize'] = strlen($this->result['body']);

                $attachmentBasePath = $this->getAttachBasePath($attachmentId);

                $attachmentPath = str_replace($attachmentBasePath, '', $movePath);

                $attachmentFileType = $this->getAttachmentFileType($ext);

                $data = [
                    "attachment_id"          => $attachmentId,
                    "attachment_name"        => $newAttachmentName,

                    "affect_attachment_name" => $affectAttachmentName,
                    "new_full_file_name"     => $movePath.$affectAttachmentName,
                    "thumb_attachment_name"  => '',
                    "attachment_size"        => $fileSize,
                    "attachment_type"        => $ext,
                    "attachment_create_user" => '',
                    "attachment_base_path"   => $attachmentBasePath,
                    "attachment_path"        => $attachmentPath,
                    "attachment_mark"        => $attachmentFileType,
                    "attachment_time"        => date("Y-m-d H:i:s"),
                    "relation_table"         => 'document_content',
                    "rel_table_code"         => ''
                ];
                // 永中插件更新编辑标识
                $readOption = app($this->attachmentService)->getOnlineReadOption();
                if (isset($readOption['online_read_type']) && $readOption['online_read_type'] == 1) {
                    $transRecord = DB::table('yozo_translate')->where('attachment_id', $attachmentId)->first();
                    if (!empty($transRecord)){
                        DB::table('yozo_translate')->where('attachment_id', $attachmentId)->update(['operate_count' => '-1']);
                    }
                }

                // 更新数据库中附件相关数据
                $res = $this->addAttachment($data);

                $fd        = fopen($movePath.$affectAttachmentName, "rb");
                $this->result['fileSize'] = filesize($movePath.$affectAttachmentName);
                $this->result['file'] = fread($fd, $this->result['fileSize']);
                fclose($fd);
            } else {
                $this->result['msgError'] = trans("iweboffice.failed_to_save");
            }
        } else {
            $this->result['msgError'] = trans('upload.0x011017');
        }

        return $this->returnXml();
    }
    public function revisions($params){
        dd(111);
    }
    private function getAttachBasePath($attachmentId)
    {
        $attachments = app($this->attachmentService)->getOneAttachmentById($attachmentId, false);
        if (empty($attachments)) {
            return getAttachmentDir();
        }

        if (!$attachments['attachment_base_path']) {
            $attachments['attachment_base_path'] = getAttachmentDir();
        }

        return $attachments['attachment_base_path'];
    }
    private function getSuffix($fileType)
    {
        return substr($fileType, 1);
    }

    /**
     * 获取附件路径
     *
     * @param string $attachmentId      附件id
     * @param bool $getAttachmentName   附件路径是否包括附件名
     * @param string $attachmentName    指定附件名
     * @return array|false|string|string[]|null
     */
    private function getMovePath($attachmentId, $getAttachmentName = false, $attachmentName = '')
    {
        $attachments = app($this->attachmentService)->getOneAttachmentById($attachmentId, false);
        if (empty($attachments)) {
            try {
                $path = app($this->attachmentService)->createCustomDir($attachmentId);
                if (isset($path['code'])) {
                    return $path;
                }
            } catch (\Exception $e) {
                return ['code' => ['failed_or_not_exist', 'iweboffice']];
            }

            if ($attachmentName) {
                return $path . $attachmentName;
            } else {
                return $path;
            }
        }

        if (isset($attachments[0])) {
            $attachment = $attachments[0];
        }else{
            $attachment = $attachments;
        }

        if (!$attachment['attachment_base_path']) {
            $attachment['attachment_base_path'] = getAttachmentDir();
        }
        if ($getAttachmentName) {
            $fileName = trim($attachment['attachment_base_path'], "\\") .DIRECTORY_SEPARATOR. ltrim($attachment['attachment_relative_path'], "\\") . $attachment['affect_attachment_name'];
            if (!file_exists($fileName)) {
                $attachment['attachment_base_path'] = Utils::getAttachmentDir('attachment');
                $fileName = trim($attachment['attachment_base_path'], "\\") .DIRECTORY_SEPARATOR. ltrim($attachment['attachment_relative_path'], "\\") . $attachment['affect_attachment_name'];
                if (!file_exists($fileName)) {
                    return $this->transEncoding($fileName,'GBK');
                } else {
                    return $fileName;
                }
            } else {
                return $fileName;
            }
        } else {
            return $attachment['attachment_base_path'] . $attachment['attachment_relative_path'];
        }
    }
    private function transEncoding($string, $target)
    {
        $encoding = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);

        return mb_convert_encoding($string, $target, $encoding);
    }

    /**
     * 附件替换完成后处理数据库中相关数据
     *
     * @param array $data
     * @return bool
     */
    private function addAttachment($data)
    {
        // $attachmentData = array_intersect_key($data, array_flip(app($this->attachmentRepository)->getTableColumns()));
        $attachments = app($this->attachmentService)->getOneAttachmentById($data['attachment_id'], false);

        if (empty($attachments)) {
            app($this->attachmentService)->handleAttachmentDataTerminal($data);
        } else {
            app($this->attachmentService)->batchDeleteAttachmentData([$data['attachment_id']]);
            app($this->attachmentService)->handleAttachmentDataTerminal($data);
        }

        // 更新对应索引
        if (isset($data['attachment_id']) && isset($data['relation_table'])) {
            ElasticsearchProducer::indexUpdateByAttachmentIdAndTable($data['attachment_id'],$data['relation_table']);
        }

        /** @var DocumentManager $manager */
//        $manager = app('App\EofficeApp\Elastic\Services\Document\DocumentManager');
//        $manager->updateAttachmentRepositoryByAttachmentId($data['attachment_id']);

        return true;
    }

    private function getAttachmentFileType($ext)
    {
        $uploadFileStatus = config('eoffice.uploadFileStatus');

        if (in_array($ext, $uploadFileStatus[1])) {
            return 1;
        } else if (in_array($ext, $uploadFileStatus[2])) {
            return 2;
        } else if (in_array($ext, $uploadFileStatus[3])) {
            return 3;
        } else {
            return 9;
        }
    }
    private function returnXml($flag = false, $path = '')
    {
        //返回输出结果
        echo ("<DBSTEP>");
        echo ("<HEAD>");
        echo ("VERSION=DBSTEP V3.0\r\n");
        echo ("BODYSIZE=" . $this->result['bodySize'] . "\r\n");
        echo ("FILESIZE=" . $this->result['fileSize'] . "\r\n");
        echo ("MSGERROR=" . $this->result['msgError'] . "\r\n");
        echo ("</HEAD>");
        echo ("<BODY>");
        echo ($this->result['body']);
        echo ("</BODY>");
        echo ("<FILE>");
        if ($flag) {
            foreach ($this->readTheFile($path) as $value) {
                echo $value;
            }
        } else {
            echo ($this->result['file']);
        }
        echo ("</FILE>");
        echo ("</DBSTEP>");
        exit;
    }

    public function insertfile($params)
    {
        $mFileName = isset($params["FILENAME"]) ? $params["FILENAME"] : '';
        $mRecordID = isset($params['RECORDID']) ? $params["RECORDID"] : '';
        // 获取保存的文档路径
        $mFilePath = $this->getMovePath($mRecordID, true, $mFileName);
        if (isset($mFilePath['code'])) {
            $message = trans($mFilePath['code'][1].'.'.$mFilePath['code'][0]);
            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnXml();
        }
        // $mFullPath = $mFilePath."/".$mRecordID."/".$mFileName;
        if (!isset($mFilePath)) {
            $mFilePath = '';                                                        //如果找不到文件，则给予空
        }
        $mFullPath = iconv("utf-8","gbk",$mFilePath);

        $result    = file_exists($mFullPath);
        $message = "result=" . base64_encode("result") . "\r\n";
        if(!$result){
            $message .= base64_encode(trans("iweboffice.file_not_exist")).$mFullPath;                     //设置状态信息
        }
        else
        {
            $message   = $message."POSITION=".base64_encode("Content")."\r\n";
            $fd        = fopen($mFullPath, "rb" );
            $this->result['fileSize'] = filesize($mFullPath);
            $this->result['file'] = fread($fd, filesize($mFullPath));
            fclose($fd);
            $message = $message . "STATUS=" . base64_encode(trans("iweboffice.document_opened_success")) . "\r\n"; //设置状态信息

            $this->result['body'] = $message;

            $this->result['bodySize'] = strlen($message);

            $message    = $message."STATUS=".base64_encode(trans("iweboffice.file_import_successfully"))."\r\n"; //设置状态信息
        }

        return $this->returnXml();
    }

    public function getContentSet($data, $own)
    {
        $attachmentId = isset($data['record_id'])?$data['record_id']:(isset($data['attachment_id'])?$data['attachment_id']:$data);
        $print = app($this->attachmentService)->getPrintPower($attachmentId, $own);
        $result = ['print' => $print];
        if($set = DB::table('document_set')->where('attachment_id', $attachmentId)->first()){
            $result = array_merge($result, [
                'revision'   => $set->revision,
                'copy_power' => $set->copy_power,
            ]);
        }
        return $result;
    }
}
