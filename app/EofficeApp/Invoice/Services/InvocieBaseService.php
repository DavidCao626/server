<?php


namespace App\EofficeApp\Invoice\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

abstract class InvocieBaseService
{
    public $errors = [];
    // 获取发票列表
    abstract public function getInvoiceList($param);
    // 获取发票详情
    abstract public function getInvoice($param);
    // 获取发票抬头列表
    abstract public function getInvoiceTitles($param);
    // 同步人员
    abstract public function syncUser($param);
    // 批量同步人员
    abstract public function batchSyncUser($param);

    public function msec_time() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    public function request($method, $url, $body, $header, $multipart = [])
    {
        // 如果需要发送文件 使用 multipart 请求参数来发送表单文件
        // 该参数接收一个包含多个关联数组的数组，每个关联数组包含一下键名：
        // name: (必须，字符串) 映射到表单字段的名称。
        // contents: (必须，混合) 提供一个字符串，可以是 fopen 返回的资源、或者一个Psr\Http\Message\StreamInterface 的实例
        try {
            $client = new Client();
            if (!$multipart) {
                $guzzleHttpResponse = $client->request($method, $url, [
                    'body' => $body,
                    'headers' => $header
                ]);
            } else {
                $guzzleHttpResponse = $client->request($method, $url, [
                    'multipart' => $multipart,
                ]);
            }

        } catch (GuzzleException $exception) {
             throw new \ErrorException($exception->getMessage());
        }
        return $guzzleHttpResponse->getBody()->getContents();
    }

    abstract public function checkAppInfo();
}