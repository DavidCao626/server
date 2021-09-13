<?php
namespace App\Jobs\Export;
use App\Jobs\Export\Base;

class Eml extends Base
{
    public function export($config, $data)
    {
        $title = $config['title'];
        if (isset($data[0])) {
            $zip = new \ZipArchive;
            $fileName = $this->makeFileName($title);
            $fileType = 'zip';
            $zipFile = $this->createFile(transEncoding($fileName, 'UTF-8'), $fileType);
            if ($zip->open($zipFile, \ZIPARCHIVE::CREATE)) {
                foreach ($data as $v) {
                    $file = $this->createEml($title, $v);
                    $filePath = $this->createFile($file['file_name'], $file['file_type']);
                    $zip->addFile($filePath);
                }
                $zip->close();
            }
            return [
                'file_name' => $fileName,
                'file_type' => $fileType,
            ];
        }

        return $this->createEml($title, $data);
    }

    public function createEml($title, $data)
    {
        $title    = empty($data['file_name']) ? $title : $data['file_name'];
        $fileName = $this->makeFileName($title);
        $fileType = 'eml';

        $file    = $this->createFile(transEncoding($fileName, 'UTF-8'), $fileType);
        $content = transEncoding($data['data'], 'UTF-8');

        file_put_contents($file, $content);

        return [
            'file_name' => $fileName,
            'file_type' => $fileType,
        ];
    }
}
