<?php

namespace App\Jobs\Export;

use App\Jobs\Export\Base;

class Other extends Base
{
    public function export($config, $data)
    {
        $fileType = $config['fileType'];

        $fileName = str_replace(['/', '\\', '"', "'"], ['_', '_', '_', '_'], $this->makeFileName($config['title']));
        $file     = $this->createFile(transEncoding($fileName, 'UTF-8'), $fileType);

        if (is_array($data)) {
            $data = var_export($data, true);
        }

        if (is_string($data)) {
            file_put_contents($file, $data);
            return [
                'file_name' => $fileName,
                'file_type' => $fileType,
            ];
        }
    }
}
