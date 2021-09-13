<?php
namespace App\EofficeApp\ThirdTest\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
/**
 * 第三方二次开发模块测试控制器类
 *
 * @author Administrator
 */
class ThirdTestController extends Controller
{
    private $request;
    public function __construct(Request $request) 
    {
        parent::__construct();
        $this->request = $request;
    }
    
    public function add()
    {
        echo '这是新建方法';
    }
    
    public function edit()
    {
        echo '这是编辑方法';
    }
    
    public function lists()
    {
        echo '这是获取列表方法';
    }
    
    public function detail($testId)
    {
        echo '这是获取详情方法';
    }
    
    public function delete($testId)
    {
        echo '这是删除方法';
    }
}

