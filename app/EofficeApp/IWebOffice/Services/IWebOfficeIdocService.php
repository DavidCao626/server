<?php
/**
 * 用来处理iweboffice2015插件请求的后端文件，用来和iweboffice2003隔离，从controller入口那里区分，路由不一样
 * 1、是 IWebOfficeService 复制过来后修改的
 * 2、已处理好的函数会用“已升级”进行标注
 */
namespace App\EofficeApp\IWebOffice\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\Document\DocumentManager;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\IWebOffice\Configurations\Constants;
use DB;
use Illuminate\Support\Facades\Redis;
use App\Utils\Utils;

class IWebOfficeIdocService extends BaseService
{
    private $attachmentRepository;
    private $userRepository;
    private $attachmentService;
    // iweboffice2015，此数据格式已经废弃，接口返回数据，不再需要格式化
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
            } else {
                return ['code' => ['0x011017', 'upload']];
            }
        }
    }

    /**
     * [打开服务器的文件][已升级]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function loadfile($params)
    {
        $attachmentId = isset($params['RECORDID']) ? $params['RECORDID'] : '';

        $attachmentName = isset($params['FILENAME']) ? $params['FILENAME'] : '';

        $file = $this->getMovePath($attachmentId, true, $attachmentName);

        if (isset($file['code'])) {
            $message = trans($file['code'][1].'.'.$file['code'][0]);
            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnIwebofficeData();
        }

        //$file = $movePath . $attachmentName;
        if (!file_exists($file)) {
            // $this->result['msgError'] = trans("iweboffice.file_not_exist") . $file; //设置错误信息
            // 新建的时候，loadfile没load到，返回405的话，前端会执行createfile，创建一个空文档，不这样处理低版本office会报错
            header('MsgError: 405');
            return ;
        } else {
            $fd = fopen($file, "rb");
            $mFileSize = filesize($file);
            $mFileBody = fread($fd, $mFileSize);
            header('Content-type: application/x-msdownload');
            header('Content-Length:'.$mFileSize);
            header("Content-Disposition: attachment; filename=".$attachmentName);
            ob_clean();
            flush();
            echo ($mFileBody);
            fclose($fd);
            return ;
        }
    }
    private function readTheFile($path) {
        $handle = fopen($path, "r");

        while(!feof($handle)) {
            yield fgets($handle);
        }

        fclose($handle);
    }
    /**
     * [加载套红模板][已升级]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function loadtemplate($params)
    {
        // 传入的模板的id（数据库存的，创建模板时候的唯一id）
        $template  = $params['TEMPLATE']; //取得模板文档类型

        $attachments = app($this->attachmentService)->getOneAttachmentById($template, false);
        $mFileName = isset($attachments['attachment_name']) ? $attachments['attachment_name'] : '';

        $templatePath = $this->getMovePath($template);
        if (isset($templatePath['code'])) {
            $message = trans($templatePath['code'][1].'.'.$templatePath['code'][0]);
            $this->result['msgError'] = $message;

            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnIwebofficeData();
        }

        $templateFile = $templatePath . $mFileName;

        if (isset($params['COMMAND']) && $params['COMMAND'] == "INSERTFILE") {
            // $message = "result=" . base64_encode("result") . "\r\n";

            if (!file_exists($templateFile)) {
                // $this->result['msgError'] = trans("iweboffice.template_not_exist") . $templateFile; //设置错误信息
            } else {
                // $fd = fopen($templateFile, "rb");

                // $this->result['fileSize'] = filesize($templateFile);
                // $this->result['file']     = fread($fd, filesize($templateFile));

                // fclose($fd);

                // $message .= "STATUS=" . base64_encode(trans("iweboffice.template_opened_success")) . "\r\n";

                // 打开套红模板文件，读取内容返回给2015插件
                $fd = fopen($templateFile, "rb");
                $mFileSize = filesize($templateFile);
                $mFileBody = fread($fd, $mFileSize);
                header('Content-type: application/x-msdownload');
                header('Content-Length:'.$mFileSize);
                header("Content-Disposition: attachment; filename=".$mFileName);
                ob_clean();
                flush();
                echo ($mFileBody);
                fclose($fd);
            }

            // $message .= "PATH=" . base64_encode(true) . "\r\n";


        } else {
            //从目录里调出模板
            if (!file_exists($templateFile)) {
                // $this->result['msgError'] = trans("iweboffice.template_not_exist") . $templateFile; //设置错误信息
            } else {
                // 打开套红模板文件，读取内容返回给2015插件
                $fd = fopen($templateFile, "rb");
                $mFileSize = filesize($templateFile);
                $mFileBody = fread($fd, $mFileSize);
                header('Content-type: application/x-msdownload');
                header('Content-Length:'.$mFileSize);
                header("Content-Disposition: attachment; filename=".$mFileName);
                ob_clean();
                flush();
                echo ($mFileBody);
                fclose($fd);
            }
        }

        // $this->result['body']     = $message;
        // $this->result['bodySize'] = strlen($message);

        return $this->returnIwebofficeData();
    }
    /**
     * [获取图片章列表][已升级]
     * @param  [type] $params [description]
     * @param  [type] $own    [description]
     * @return [type]         [description]
     */
    public function loadmarklist($params, $own)
    {
        $mUserName = app($this->userRepository)->getUserName($own['user_id']);

        $list = DB::table('signature')->select('signature_id', 'signature_name')->where('signature_onwer', $own['user_id'])->orderBy('signature_name')->get();

        $mMarkList = "";
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $mMarkList = $mMarkList . transEncoding($value->signature_name,'GB2312') . '\r\n';
            }
        }
        // 2015增加的，新的，图片章的数据返回方法
        // !!原来拼接的时候使用的  "\n" 要改成 '\r\n'
        header('MARKLIST:'.$mMarkList);
        // $this->result['body'] .= "MARKLIST=" . base64_encode($mMarkList) . "\r\n";
        // $this->result['bodySize'] = strlen($this->result['body']);
        // $this->result['fileSize'] = 0;
        // $this->result['msgError'] = '';

        return $this->returnIwebofficeData();
    }

    /**
     * [加载图片章详情][已升级]
     * @param  [type] $params [description]
     * @param  [type] $own    [description]
     * @return [type]         [description]
     */
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
            // $this->result['msgError'] = trans("iweboffice.seal_not_exist_or_wrong_pwd"); //设置错误信息
            $result = false;
        }

        if (isset($list) && !empty($list)) {
            $filePath = $list['attachment_relative_path'];
            $fileName = $list['affect_attachment_name'];
            $fileType = $list['attachment_type'];


            $mFullPath = iconv("utf-8", "gbk", (getAttachmentDir() . $filePath . $fileName));
            /*
            // 2003的处理
            $fp = fopen($mFullPath, "rb");
            if (!$fp) {
                // $this->result['msgError'] = trans("iweboffice.failed_or_not_exist");
            }

            $this->result['fileSize'] = filesize($mFullPath);

            $this->result['file'] = fread($fp, filesize($mFullPath));

            fclose($fp);

            $this->result['body'] .= "IMAGETYPE=" . base64_encode('.' . $fileType) . "\r\n";
            $this->result['body'] .= "POSITION=" . base64_encode("Manager") . "\r\n";
            // @var IWebOfficeConfigService $configService
            $configService = app('App\EofficeApp\IWebOffice\Services\IWebOfficeConfigService');
            $this->result['body'] .= "ZORDER=" . base64_encode($configService->getSignatureStyle()) . "\r\n";
            $this->result['bodySize'] = strlen($this->result['body']);
            */

            // 2015的处理
            $fp = fopen($mFullPath, "rb");
            $mFileSize = filesize($mFullPath);
            $mFileBody = fread($fp, $mFileSize);
            header('IMAGETYPE:'.$fileType);
            // 固定值
            header('POSITION:Manager');
            $configService = app('App\EofficeApp\IWebOffice\Services\IWebOfficeConfigService');
            // 4:在文字上方 5:在文字下方
            header('ZORDER:'.$configService->getSignatureStyle());

            ob_clean();
            flush();
            echo ($mFileBody);
            fclose($fp);

        } else {
            // $this->result['msgError'] = trans("iweboffice.seal_not_exist_or_wrong_pwd"); //设置错误信息
            $result = false;
        }

        return $this->returnIwebofficeData();
    }

    /**
     * [插入图片章之后回调的，保存图片章][已升级]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function savesignature($params)
    {
        /* {
          "MARKNAME": "admin印章bmp",
          "DATETIME": "2021-4-25 13:39:17",
          "MARKGUID": "{B4F76802-2C04-4518-8538-3BAFDABF07A4}",
          "USERNAME": "系统管理员",
          "FILENAME": "16191681909403706551.doc",
          "FILETYPE": ".doc",
          "RECORDID": "16191681909403706551",
          "EXTPARAM": "",
          "TEMPLATE": "",
          "OPTION": "SAVESIGNATURE"
        } */
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
            // $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.saved_successfully")) . "\r\n"; //设置状态信息ss
        } else {
            // $this->result['msgError'] = trans("iweboffice.failed_to_save"); //设置错误信息
        }

        return $this->returnIwebofficeData();
    }

    /**
     * [已升级]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function loadsignature($params)
    {
        $mRecordID = $params['RECORDID'];
          // 印章名称
        // $mMarkName = transEncoding(trans("iweboffice.seal_name"),'GB2312').'\r\n';
        $mMarkName = trans("iweboffice.seal_name").'\r\n';
        // 签名人
        // $mUserName = transEncoding(trans("iweboffice.signer"),'GB2312').'\r\n';
        $mUserName = trans("iweboffice.signer").'\r\n';
        // 签章时间
        // $mDateTime = transEncoding(trans("iweboffice.signing_time"),'GB2312').'\r\n';
        $mDateTime = trans("iweboffice.signing_time").'\r\n';
        // 客户端IP
        // $mHostName = transEncoding(trans("iweboffice.client_ip"),'GB2312').'\r\n';
        $mHostName = trans("iweboffice.client_ip").'\r\n';
        // 序列号
        // $mMarkGuid = transEncoding(trans("iweboffice.serial_number"),'GB2312').'\r\n';
        $mMarkGuid = trans("iweboffice.serial_number").'\r\n';
        //查找记录是否存在
        $list = DB::table('document_signature')->select('MarkName', 'UserName', 'DateTime', 'HostName', 'MarkGuid')->where('RecordID', $mRecordID)->get();

        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $mMarkName .= transEncoding($value->MarkName,'GB2312') . '\r\n';
                // $mMarkName .= $value->MarkName . '\r\n';
                // $mUserName .= transEncoding($value->UserName,'GB2312') . '\r\n';
                // $mUserName .= $value->UserName . '\r\n';
                // $mDateTime .= transEncoding('123','GB2312') . '\r\n';
                // $mDateTime .= '123' . '\r\n';
                // $mHostName .= transEncoding($value->HostName,'GB2312') . '\r\n'; //如果说明信息里有回车，则将回车变成>符号
                // $mHostName .= $value->HostName . '\r\n'; //如果说明信息里有回车，则将回车变成>符号
                // $mMarkGuid .= transEncoding($value->MarkGuid,'GB2312') . '\r\n'; //取得唯一编号
                // $mMarkGuid .= $value->MarkGuid . '\r\n'; //取得唯一编号
            }
        }
        /*
        $this->result['body'] .= "MARKNAME=" . base64_encode($mMarkName) . "\r\n"; //将签名名称列表打包
        $this->result['body'] .= "USERNAME=" . base64_encode($mUserName) . "\r\n"; //将用户名列表打包
        $this->result['body'] .= "DATETIME=" . base64_encode($mDateTime) . "\r\n"; //将时间列表打包
        $this->result['body'] .= "HOSTNAME=" . base64_encode($mHostName) . "\r\n"; //将说明信息列表打包
        $this->result['body'] .= "MARKGUID=" . base64_encode($mMarkGuid) . "\r\n"; //取得唯一编号
        $this->result['body'] .= "STATUS=" . base64_encode(trans("iweboffice.successfully_tuned_in")) . "\r\n"; //设置状态信息
        $this->result['bodySize'] = strlen($this->result['body']);
        $this->result['fileSize'] = 0;
        $this->result['msgError'] = '';
        */
        // echo "<pre>";
        // print_r(rawurlencode(transEncoding("印章名称","GB2312")."\r\n"."aaa"."\r\n"));
        // exit();
        // header('MARKNAME:'.'印章名称'.'\r\n'.'aaa'.'\r\n');
        // header('USERNAME:'.'签名人'.'\r\n'.'aaa'.'\r\n');
        // header('DATETIME:'.'签章时间'.'\r\n'.'2021-04-26 10:23:14'.'\r\n');
        // header('HOSTNAME:'.'客户端IP'.'\r\n'.'192.168.0.47'.'\r\n');
        // header('MARKGUID:'.'序列号'.'\r\n'.'{D730135C-90A9-4638-8FD9-684F76E6D2CF}'.'\r\n');

        // header('MARKNAME:'.transEncoding("印章名称","GB2312").'\r\n'."aaa"."\r\n");
        // header('STATUS:'.'调入印章成功!');
        // header('USERNAME:'.$mUserName);
        // header('DATETIME:'.$mDateTime);
        // header('HOSTNAME:'.$mHostName);
        // header('MARKGUID:'.$mMarkGuid);

        return $this->returnIwebofficeData();
    }
    public function listversion($params)
    {
        $mRecordID = $params['RECORDID'];
        $mFilePath = $this->getMovePath($mRecordID, true);
        if (isset($mFilePath['code'])) {
            $message = trans($mFilePath['code'][1].'.'.$mFilePath['code'][0]);
            $this->result['body'] = $message;
            $this->result['bodySize'] = strlen($message);
            return $this->returnIwebofficeData();
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

        return $this->returnIwebofficeData();
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
            return $this->returnIwebofficeData();
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

        return $this->returnIwebofficeData();
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
            return $this->returnIwebofficeData();
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

        return $this->returnIwebofficeData();
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

        return $this->returnIwebofficeData();
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

        return $this->returnIwebofficeData();
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
     * 保存文件[已升级]
     *
     * @param array $params
     * @param $file [fileData 对象]
     * $file 结构示例(json后)：
     * {"name":"56702.7379443699397780ea1990b52fa6d6b016c1b28eb70.docx","type":"application\/octet-stream","tmp_name":"D:\\e-office10\\temp\\php9EA9.tmp","error":0,"size":1462280}
     */
    public function savefile($params, $file)
    {
        if ($file && isset($file['tmp_name'])) {

            $attachmentId = $params['RECORDID'];

            $ext = $this->getSuffix($params['FILETYPE']);
            // 获取附件路径
            $movePath = $this->getMovePath($attachmentId);
            if (isset($movePath['code'])) {
                $message = trans($movePath['code'][1].'.'.$movePath['code'][0]);
                $this->result['body'] = $message;
                $this->result['bodySize'] = strlen($message);
                return $this->returnIwebofficeData();
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
            $fileSize = $file['size'] ?? 0;

            // iweboffice2003的写法，laravel的move，第一个参数是目标位置，第二个参数是要改的文件名
            // $result = $file->move($movePath, $affectAttachmentName);
            // iweboffice2015的写法，参考了iweboffice2015的phpdemo
            $tmpFileName = $file['tmp_name'];
            $savedFileFullName = $movePath.$affectAttachmentName;

            if(is_uploaded_file($tmpFileName)) {
                $result = move_uploaded_file($tmpFileName, $savedFileFullName);
            }

            if ($result) {
                // $this->result['body'] = "STATUS=" . base64_encode(trans("iweboffice.saved_successfully")) . "\r\n";

                // $this->result['bodySize'] = strlen($this->result['body']);

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

                // $fd        = fopen($movePath.$affectAttachmentName, "rb");
                // $this->result['fileSize'] = filesize($movePath.$affectAttachmentName);
                // $this->result['file'] = fread($fd, $this->result['fileSize']);
                // fclose($fd);
            } else {
                // $this->result['msgError'] = trans("iweboffice.failed_to_save");
            }
        } else {
            // $this->result['msgError'] = trans('upload.0x011017');
        }

        return $this->returnIwebofficeData();
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
    // iweboffice2015，此函数已经废弃，接口返回数据，不再需要格式化
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
    // iweboffice2015新增的数据格式化返回函数，目前空着
    private function returnIwebofficeData() {

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
            return $this->returnIwebofficeData();
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

        return $this->returnIwebofficeData();
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
