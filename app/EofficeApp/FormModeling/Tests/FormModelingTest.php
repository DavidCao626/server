<?php
namespace App\EofficeApp\FormModeling\Tests;

use App\Tests\UnitTest;

/**
 * Description of FormModelingTest
 *
 * @author lizhijun
 */
class FormModelingTest extends UnitTest
{
    //put your code here
    public $callMethods = [
        'outsendEditTest'
    ];
    private $formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    /**
     * 外发测试，
     * 测试编辑数据到表单建模
     */
    public function outsendEditTest()
    {
        $data = [
            'data' => [// 外发数据
                'field_1' => '1212',
                'field_2' => '33333'
            ],
            'unique_id' => 60,//数据ID
            'current_user_id' => 'admin'//当前用户ID
        ];
        $response = app($this->formModelingService)->editOutsendData($data,1077);
        $this->response($response);
    }
}
