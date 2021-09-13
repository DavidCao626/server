<?php
namespace App\EofficeApp\ImportExport\Parsers;
class BaseParser{
    public function validateImportExcel(array $params)
    {
        $err = false;
        $module = $params['module'] ?? null;
        $file = $params['file'] ?? null;
        $uid = $params['user_info']['user_id'] ?? '';
        if ($module === null || $file === null) {
            $err = true;
        }
        return [$err, $uid, $file, $module];
    }
}