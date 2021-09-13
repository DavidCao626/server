<?php
/**
 * 用于外部数据库Oracle类型字符解码
 */
class OracleTransEncode
{
    public $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function transEncoding($data)
    {
        $charSet = $this->database['charset'] ?? ''; // 字符编码
        // 是否转码 0 不转 1转
        $transFlag = 1;
        // $transFlag = 0;
        if (!empty($charSet) && $transFlag && $charSet != 'utf8') {
             // 转码
             foreach ($data as $key => $value) {
                $data[$key] = transEncoding($value, $charSet);
            }           
        }
        return $data;
    }
}
