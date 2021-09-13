<?php
/**
 * 生成eml文件
 * 
 * author lizhijun
 */
namespace App\Utils;
class Eml 
{   
    public $Received = '来自e-office内部邮件 ( [127.0.0.1] )';
    public $XOriginatingIP = '127.0.0.1';
    public $XCoremailLocale = 'zh_CN';
    public $Date;
    public $From;
    public $To;
    public $Cc;
    public $Bcc;
    public $Subject;
    public $XPriority = 3;
    public $XMailer = 'www.e-office.cn';
    public $XCMCTRLDATA = 'nfZy0GZvb3Rlcl9odG09MTQyOjQyNQ==';
    public $MsgId;
    public $boundary = '----=_Part_181046_288535471.1497600579691';
    public $boundary1 = '----=_Part_181048_1822822909.1497600579691';
    public $LE = "\n";    
    public $error;
    public $CharSet = 'GBK';
    public $Encoding = 'base64';
    public $ContentType = 'text/html';
    public $body;
    private $attachment;
    const CRLF = "\r\n";
    public function createHeader()
    {
        $this->emlHeader = $this->headerLine('Received', $this->Received);
        $this->emlHeader .= $this->headerLine('X-Originating-IP', $this->XOriginatingIP);
        if (!$this->Date || !$this->From || !$this->To || !$this->Subject || !$this->MsgId) {
            $this->error = 'Date、From、To、Subject不能为空';

            return false;
        }
        $this->emlHeader .= $this->headerLine('Date', $this->Date);
        $this->emlHeader .= $this->headerLine('From', $this->From);
        $this->emlHeader .= $this->headerLine('To', $this->To);
        if ($this->Cc) {
            $this->emlHeader .= $this->headerLine('Cc', $this->Cc);
        }
        if ($this->Bcc) {
            $this->emlHeader .= $this->headerLine('Bcc', $this->Bcc);
        }
        $this->emlHeader .= $this->headerLine('Subject', $this->Subject);
        $this->emlHeader .= $this->headerLine('X-Priority', $this->XPriority);
        $this->emlHeader .= $this->headerLine('X-Mailer', $this->XMailer);
        $this->emlHeader .= $this->headerLine('X-CM-CTRLDATA', $this->XCMCTRLDATA);
        $this->emlHeader .= 'Content-Type: multipart/mixed;' . $this->LE;
        $this->emlHeader .= "\t".'boundary="' . $this->boundary . '"' . $this->LE;
        $this->emlHeader .= 'MIME-Version: 1.0' . $this->LE;
        $this->emlHeader .= $this->headerLine('Message-ID', '<' . md5($this->MsgId) . '>');
        $this->emlHeader .= $this->headerLine('X-Coremail-Locale', $this->XCoremailLocale);
        $this->emlHeader .= self::CRLF . '--' . $this->boundary . $this->LE;
        $this->emlHeader .= 'Content-Type: multipart/alternative;' . $this->LE;
        $this->emlHeader .= "\t".'boundary="' . $this->boundary1 . '"' . $this->LE . self::CRLF;
        return $this->emlHeader;
    }
    
    public function createBody()
    {
//        if(!$this->body){
//            $this->error = 'body 不能为空';
//
//            return false;
//        }
        $this->emlBody = $this->getBoundary($this->boundary1,'','','');
        $this->emlBody .= $this->encodeString($this->body) . '--' .$this->boundary1 . '--'.self::CRLF.$this->LE;

        $this->emlAttachment = '';
        if($this->attachment){
            $this->emlAttachment = $this->attachAll();
        }
    }
    
    public function generate()
    {
        $this->createHeader();
        $this->createBody();
        return $this->emlHeader . $this->emlBody . $this->emlAttachment;
    }
    protected function getBoundary($boundary, $charSet, $contentType, $encoding)
    {
        $result = '';
        if ($charSet == '') {
            $charSet = $this->CharSet;
        }
        if ($contentType == '') {
            $contentType = $this->ContentType;
        }
        if ($encoding == '') {
            $encoding = $this->Encoding;
        }
        $result .= $this->textLine('--' . $boundary);
        $result .= sprintf('Content-Type: %s; charset=%s', $contentType, $charSet);
        $result .= $this->LE;
        if ($encoding != '7bit') {
            $result .= $this->headerLine('Content-Transfer-Encoding', $encoding);
        }
        $result .= $this->LE;

        return $result;
    }
    public function textLine($value)
    {
        return $value . $this->LE;
    }
    public function addAttachment($path, $name = '', $encoding = 'base64', $type = '', $disposition = 'attachment')
    {
        if (!@is_file($path)) {
            $this->error = '不是一个文件';
            return false;
        }
        if ($type == '') {
            $type = self::filenameToType($path);
        }

        $filename = basename($path);
        if ($name == '') {
            $name = $filename;
        }else{
            $filename = $name;
        }

        $this->attachment[] = array(
            0 => $path,
            1 => $filename,
            2 => $name,
            3 => $encoding,
            4 => $type,
            5 => false, // isStringAttachment
            6 => $disposition,
            7 => 0
        );

        return true;
    }
    public function getAttachment()
    {
        return $this->attachment;
    }
    public static function filenameToType($filename)
    {
        $qpos = strpos($filename, '?');
        
        if (false !== $qpos) {
            $filename = substr($filename, 0, $qpos);
        }
        
        $pathinfo = self::mb_pathinfo($filename);
        
        return self::_mime_types($pathinfo['extension']);
    }
    public static function _mime_types($ext = '')
    {
        $mimes = array(
            'xl'    => 'application/excel',
            'js'    => 'application/javascript',
            'hqx'   => 'application/mac-binhex40',
            'cpt'   => 'application/mac-compactpro',
            'bin'   => 'application/macbinary',
            'doc'   => 'application/msword',
            'word'  => 'application/msword',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'sldx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'xlam'  => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb'  => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'class' => 'application/octet-stream',
            'dll'   => 'application/octet-stream',
            'dms'   => 'application/octet-stream',
            'exe'   => 'application/octet-stream',
            'lha'   => 'application/octet-stream',
            'lzh'   => 'application/octet-stream',
            'psd'   => 'application/octet-stream',
            'sea'   => 'application/octet-stream',
            'so'    => 'application/octet-stream',
            'oda'   => 'application/oda',
            'pdf'   => 'application/pdf',
            'ai'    => 'application/postscript',
            'eps'   => 'application/postscript',
            'ps'    => 'application/postscript',
            'smi'   => 'application/smil',
            'smil'  => 'application/smil',
            'mif'   => 'application/vnd.mif',
            'xls'   => 'application/vnd.ms-excel',
            'ppt'   => 'application/vnd.ms-powerpoint',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wmlc'  => 'application/vnd.wap.wmlc',
            'dcr'   => 'application/x-director',
            'dir'   => 'application/x-director',
            'dxr'   => 'application/x-director',
            'dvi'   => 'application/x-dvi',
            'gtar'  => 'application/x-gtar',
            'php3'  => 'application/x-httpd-php',
            'php4'  => 'application/x-httpd-php',
            'php'   => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'phps'  => 'application/x-httpd-php-source',
            'swf'   => 'application/x-shockwave-flash',
            'sit'   => 'application/x-stuffit',
            'tar'   => 'application/x-tar',
            'tgz'   => 'application/x-tar',
            'xht'   => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'zip'   => 'application/zip',
            'mid'   => 'audio/midi',
            'midi'  => 'audio/midi',
            'mp2'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'mpga'  => 'audio/mpeg',
            'aif'   => 'audio/x-aiff',
            'aifc'  => 'audio/x-aiff',
            'aiff'  => 'audio/x-aiff',
            'ram'   => 'audio/x-pn-realaudio',
            'rm'    => 'audio/x-pn-realaudio',
            'rpm'   => 'audio/x-pn-realaudio-plugin',
            'ra'    => 'audio/x-realaudio',
            'wav'   => 'audio/x-wav',
            'bmp'   => 'image/bmp',
            'gif'   => 'image/gif',
            'jpeg'  => 'image/jpeg',
            'jpe'   => 'image/jpeg',
            'jpg'   => 'image/jpeg',
            'png'   => 'image/png',
            'tiff'  => 'image/tiff',
            'tif'   => 'image/tiff',
            'eml'   => 'message/rfc822',
            'css'   => 'text/css',
            'html'  => 'text/html',
            'htm'   => 'text/html',
            'shtml' => 'text/html',
            'log'   => 'text/plain',
            'text'  => 'text/plain',
            'txt'   => 'text/plain',
            'rtx'   => 'text/richtext',
            'rtf'   => 'text/rtf',
            'vcf'   => 'text/vcard',
            'vcard' => 'text/vcard',
            'xml'   => 'text/xml',
            'xsl'   => 'text/xml',
            'mpeg'  => 'video/mpeg',
            'mpe'   => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'mov'   => 'video/quicktime',
            'qt'    => 'video/quicktime',
            'rv'    => 'video/vnd.rn-realvideo',
            'avi'   => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie'
        );
        if (array_key_exists(strtolower($ext), $mimes)) {
            return $mimes[strtolower($ext)];
        }
        return 'application/octet-stream';
    }
    public static function mb_pathinfo($path, $options = null)
    {
        $ret = array('dirname' => '', 'basename' => '', 'extension' => '', 'filename' => '');
        $pathinfo = array();
        if (preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $path, $pathinfo)) {
            if (array_key_exists(1, $pathinfo)) {
                $ret['dirname'] = $pathinfo[1];
            }
            if (array_key_exists(2, $pathinfo)) {
                $ret['basename'] = $pathinfo[2];
            }
            if (array_key_exists(5, $pathinfo)) {
                $ret['extension'] = $pathinfo[5];
            }
            if (array_key_exists(3, $pathinfo)) {
                $ret['filename'] = $pathinfo[3];
            }
        }
        switch ($options) {
            case PATHINFO_DIRNAME:
            case 'dirname':
                return $ret['dirname'];
            case PATHINFO_BASENAME:
            case 'basename':
                return $ret['basename'];
            case PATHINFO_EXTENSION:
            case 'extension':
                return $ret['extension'];
            case PATHINFO_FILENAME:
            case 'filename':
                return $ret['filename'];
            default:
                return $ret;
        }
    }
    protected function attachAll()
    {
        $mime = [];

        foreach ($this->attachment as $attachment) {
            if(is_file($attachment[0])){
                $attach = $this->attachHeader($attachment[2], $attachment[1], $attachment[4]);
                
                $attach .= $this->encodeString(file_get_contents($attachment[0]));   
                $mime[] = $attach;
            }
        }

        return implode('', $mime).'--' . $this->boundary . '--';
    }
    private function attachHeader($name,$filename,$type)
    {
        $header = '--' . $this->boundary . $this->LE;
        $header .= 'Content-Type: ' . $type.';' . $this->LE;
        $header .= ' name="' . $name .'"' . $this->LE;
        $header .= 'Content-Transfer-Encoding: base64' . $this->LE;
        $header .= 'Content-Disposition: attachment;' . $this->LE;
        $header .= ' filename="' . $filename . '"' . $this->LE . self::CRLF;
        
        return $header;
    }
    public function encodeString($str, $encoding = 'base64')
    {
        $encoded = '';
        switch (strtolower($encoding)) {
            case 'base64':
                $encoded = chunk_split(base64_encode($str), 76, $this->LE);
                break;
            case 'binary':
                $encoded = $str;
                break;
        }
        return $encoded;
    }
    private function headerLine($key, $value)
    {
        return $key . ':' . $value . $this->LE;
    }
}
