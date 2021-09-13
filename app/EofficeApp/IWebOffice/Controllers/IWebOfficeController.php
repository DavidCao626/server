<?php
namespace App\EofficeApp\IWebOffice\Controllers;

use \App\EofficeApp\Base\Controller;
use App\EofficeApp\IWebOffice\Services\IWebOfficeConfigService;
use \App\EofficeApp\IWebOffice\Services\IWebOfficeService;
use \App\EofficeApp\IWebOffice\Services\IWebOfficeIdocService;
use \Illuminate\Http\Request;

class IWebOfficeController extends Controller
{
    private $iWebOfficeService;

    public function __construct(
        IWebOfficeService $iWebOfficeService,
        IWebOfficeIdocService $iWebOfficeIdocService,
        Request $request) {
        parent::__construct();
        $userInfo = $this->own;
        $this->userId = $userInfo['user_id'];

        $this->iWebOfficeService = $iWebOfficeService;
        $this->iWebOfficeIdocService = $iWebOfficeIdocService;

        $this->request = $request;
    }
    public function download($attachmentId)
    {
        return $this->returnResult($this->iWebOfficeService->download($attachmentId, $this->request->input('mine')));
    }
    public function fileExists()
    {
        return $this->returnResult($this->iWebOfficeService->fileExists($this->request->all()));
    }
    // iweboffice2003使用的获取文档的路由
    public function main()
    {
        $params = $this->request->all();
        // file_put_contents("D:\\2003_params.txt", json_encode($params)."\r\n\r\n", FILE_APPEND);
        if (strtolower($params['OPTION']) == 'savefile') {
            return $this->returnResult($this->iWebOfficeService->{strtolower($params['OPTION'])}($params, $this->request->file('MsgFileBody'), $this->own));
        } elseif (strtolower($params['OPTION']) == 'loadmarklist' || strtolower($params['OPTION']) == 'loadmarkimage') {
            return $this->returnResult($this->iWebOfficeService->{strtolower($params['OPTION'])}($params, $this->own));
        } else {
            return $this->returnResult($this->iWebOfficeService->{strtolower($params['OPTION'])}($params));
        }
    }
    // iweboffice2015使用的获取文档的路由
    public function mainIdoc()
    {
        $requestAll = $this->request->all();
        // file_put_contents("D:\\2015_requestAll.txt", json_encode($requestAll)."\r\n\r\n", FILE_APPEND);
        // $requestAll = json_decode('{"FormData":"{\'USERNAME\':\'\u7cfb\u7edf\u7ba1\u7406\u5458\',\'FILENAME\':\'16195756639552095913.xls\',\'FILETYPE\':\'.xls\',\'RECORDID\':\'16195756639552095913\',\'EDITTYPE\':\'-1,0,0,0,0,0,1\',\'DATABASE\':\'\',\'OPTION\':\'LOADFILE\'}","api_token":"35adc76ea31c8959bfb891e222fdaa2618b0552d5ddb2349f5aa77c2c831c46c81647ef4d623d4f3b167fe12d3311309f9381ccdd05983c8587f19978642ca01","CASE_ID":"null"}', true);
        $formData = $requestAll['FormData'] ?? ''; //获取传过来的json值
        if(!$formData) {
            return $this->returnResult([]);
        }
        // $formDataFormat = iconv("GB2312","UTF-8",$formData);  //将带有中文的值转为标准的utf8编码格式（2015demo里面的处理）
        $formDataFormat = transEncoding($formData,'UTF-8'); // （按eoffice方式处理）
        $formDataFormat = str_replace("'","\"",$formDataFormat);
        $params = json_decode($formDataFormat, true); //解析json为数组
        // file_put_contents("D:\\2015_params.txt", json_encode($params)."\r\n\r\n", FILE_APPEND);

        // 调用iWebOfficeIdocService解析iweboffice2015的请求
        if (strtolower($params['OPTION']) == 'savefile') {
            $result = $this->returnResult($this->iWebOfficeIdocService->{strtolower($params['OPTION'])}($params, $_FILES['FileData'], $this->own));
        } elseif (strtolower($params['OPTION']) == 'loadmarklist' || strtolower($params['OPTION']) == 'loadmarkimage') {
            $result = $this->returnResult($this->iWebOfficeIdocService->{strtolower($params['OPTION'])}($params, $this->own));
        } else {
            // 2003的方式，函数的返回值外面包了一层returnResult
            // $result = $this->returnResult($this->iWebOfficeIdocService->{strtolower($params['OPTION'])}($params));
            // 2015的方式，尝试不要外面的返回层（会导致新建word类型的文档的时候，有个默认的内容）
            $result = $this->iWebOfficeIdocService->{strtolower($params['OPTION'])}($params);
        }
        // file_put_contents("D:\\2015_result.txt", json_encode($result)."\r\n\r\n", FILE_APPEND);
        return $result;
    }

    public function getContentSet()
    {
        $params = $this->request->all();
        return $this->returnResult($this->iWebOfficeService->getContentSet($params, $this->own));
    }

    /**
     * 获取金格签章样式
     */
    public function getSignatureStyle()
    {
        /** @var IWebOfficeConfigService $configService */
        $configService = app('App\EofficeApp\IWebOffice\Services\IWebOfficeConfigService');
        $configurations = $configService->getSignatureStyle();

        return $this->returnResult($configurations);
    }

    /**
     * 设置金格签章样式
     */
    public function setSignatureStyle()
    {
        /** @var Request $request */
        $request = $this->request;
        $style = $request->request->getInt('style');
        /** @var IWebOfficeConfigService $configService */
        $configService = app('App\EofficeApp\IWebOffice\Services\IWebOfficeConfigService');
        $configService->setSignatureStyle($style);

        return $this->returnResult([]);
    }
}
