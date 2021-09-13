<?php

namespace App\EofficeApp\Elastic\Services\Document\Readers;

use App\EofficeApp\Elastic\Services\Document\Contract\DocumentReaderInterface;

class TxtReader implements DocumentReaderInterface
{

    public function readContent($realPath)
    {
        if (file_exists($realPath)) {
            try {
                $content = file_get_contents($realPath);
                return transEncoding($content, 'UTF-8');
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }
}