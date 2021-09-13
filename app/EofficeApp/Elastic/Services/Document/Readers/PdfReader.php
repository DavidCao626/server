<?php

namespace App\EofficeApp\Elastic\Services\Document\Readers;

use App\EofficeApp\Elastic\Services\Document\Contract\DocumentReaderInterface;

class PdfReader implements DocumentReaderInterface
{

    public function readContent($realPath)
    {
        if (file_exists($realPath)) {
            try {
                $eofficeRoot = dirname(getAttachmentDir());
                $elasticRoot = $eofficeRoot.'/elastic';
                $xpdfExeRoot = $elasticRoot . '/xpdf';
                $cdXpdfExeRoot = '"'.$xpdfExeRoot.'"';  // 进入带空格目录需带"", 否则找不到 TODO 存在问题 部分无法更新 需 测试

                $order = 'cd ' . $cdXpdfExeRoot . ' && ';
                $name = 'pdftotext.exe';
//                $name = is_win() ? 'pdftotext.exe' : 'pdftotext';
                $order .= $xpdfExeRoot . "/{$name}" . ' -enc GBK ';
                $order .= $realPath . ' -';//-可替换为文件，则pdf内容读取后存入文件中

                $content = shell_exec($order);
                $content = transEncoding($content, 'UTF-8');

                return $content;
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }
}