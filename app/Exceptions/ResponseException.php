<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ResponseException extends HttpException
{
    private $langCode;
    private $langModule;
    private $dynamic = '';

    public function __construct(int $statusCode = 200, string $message = null, \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function setErrorResponse($langCode, $langModule, $dynamic = '') {
        $this->langCode = $langCode;
        $this->langModule = $langModule;
        $this->dynamic = $dynamic;
        return $this;
    }

    // 直接输出错误
    public function returnErrorResponse() {
        echo json_encode(error_response($this->langCode, $this->langModule, $this->dynamic));
    }

    public function getLangCode()
    {
        return $this->langCode;
    }
    public function getLangModule()
    {
        return $this->langModule;
    }
    public function getDynamic()
    {
        return $this->dynamic;
    }

//    public function getData()
//    {
//        return $this->data;
//    }
//
//    public function setData($data)
//    {
//        $this->data = $data;
//    }
//
//    public function getDataMessage()
//    {
//        $message = isset($this->data['message']) ? $this->data['message'] : '';
//        return $message;
//    }
}
