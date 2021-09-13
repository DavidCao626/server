<?php

namespace App\EofficeApp\Elastic\Services\Document\Contract;

interface DocumentReaderInterface
{
    /**
     * @param $realPath
     * @return string
     */
    public function readContent($realPath);
}
