<?php
namespace App\EofficeApp\ImportExport\Parsers;
interface ParserInterface
{
    public function checkUploadMatchTemplate($params);
    public function importUploadData($params);
    public function writeReportResult($params);
}
