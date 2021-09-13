<?php
namespace App\EofficeApp\ImportExport\Traits;
/**
 * Description of ExportTrait
 *
 * @author 90536
 */
trait ExportTrait 
{
    private $prefix = 'e-office';
    /**
     * 生成导出文件名
     *
     * @param string $fileName 文件名
     *
     * @return string
     */
    public function makeExportFileName($fileName)
    {
        return $this->prefix . '_' . transEncoding($fileName, 'UTF-8') . '_' . date('YmdHis');
    }
    /**
     * 生成文件
     *
     * @param string $fileName 文件名
     * @param string $suffix 文件后缀
     *
     * @return string
     */
    public function createExportFile($fileName, $suffix)
    {
        return createExportDir() . $fileName . '.' . $suffix;
    }
}
