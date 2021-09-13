<?php
namespace App\EofficeApp\ImportExport\Tests;
use App\Tests\UnitTest;
use App\EofficeApp\ImportExport\Builders\ExcelBuilder;
use App\EofficeApp\ImportExport\Facades\Export;
/**
 * Description of AttendanceOutSendTest
 *
 * @author lizhijun
 */
class ExportTest extends UnitTest 
{
    public $callMethods = [
        'saveAsZip'
    ];
    private $excelBuilder;
    public function __construct(ExcelBuilder $excelBuilder) 
    {
        parent::__construct();
        $this->excelBuilder = $excelBuilder;
    }
    
    public function exportTest()
    {
        $params = [
            'module' => 'exportTest',
            'param' => [
                'user_info' => [
                    'user_id' => 'admin'
                ]
            ]
        ];
        Export::export($params);
    }
    public function saveAsZip() {

        $attachments = [
          './1111/a.xlsx' => './public/export/e-office_个人通讯录_20210329153657.xlsx',
          './1111/b.xlsx' => './public/export/e-office_个人通讯录_20210329153754.xlsx'
        ];
        $result = Export::saveAsZip($attachments, 'ZIP');

        $this->response($result);
    }
}
