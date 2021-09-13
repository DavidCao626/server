<?php
namespace App\EofficeApp\ImportExport\Tests;
use App\Tests\UnitTest;
use App\EofficeApp\ImportExport\Facades\Export;
/**
 * Description of AttendanceOutSendTest
 *
 * @author lizhijun
 */
class ExportExampleTest
{
    public function exportTest($builder, $params)
    {
        // 测试eml
        $this->exportOriginalData($builder, $params);
    }
    
    public function exportEml($builder, $params)
    {
        $data = '测试数据eml';
        return $builder->setTitle('测试Eml')->setData($data)->generate();
    }
    
    public function exportEmlZip($builder, $params)
    {
        $files = [];
        
        for($i = 1; $i <= 10; $i ++) {
            $data = '测试数据eml';
            list($fileName, $filePath) = $builder->setTitle('测试eml' . $i)->setData($data)->generate();
            $files[] = $filePath;
        }
        return Export::saveAsZip($files, '测试eml导出zip');
    }
    
    public function exportTxtOrOther($builder, $params)
    {
        $data = '测试数据txt';
        return $builder->setTitle('测试txt获取其他文本数据后缀')->setData($data)->generate();
    }
    
    public function exportTxtOrOtherZip($builder, $params)
    {
        $files = [];
        
        for($i = 1; $i <= 10; $i ++) {
            $data = '测试数据txt';
            list($fileName, $filePath) = $builder->setTitle('测试txt获取其他文本数据后缀' . $i)->setData($data)->generate();
            $files[] = $filePath;
        }
        return Export::saveAsZip($files, '测试txt导出zip');
    }
    
    private function exportOriginalData($builder, $params)
    {
        $header = [
            'user_name' => ['data' => '用户名', 'style' => ['width' => 80]],
            'user_account' => ['data' => '用户账号', 'style' => ['width' => 60]],
            'dept_name' => ['data' => '所属部门', 'style' => ['width' => 100]],
            'role_name' => ['data' => '所属角色', 'style' => ['width' => 60]],
            'sex' => ['data' => '性别', 'style' => ['width' => 40]],
            'mobile_number' => ['data' => '手机号', 'style' => ['width' => 100]],
            'sub' => ['data' => '下属', 'children' => [
                    'user_name' => [
                        'data' => '下属姓名', 'style' => ['width' => 80]
                    ],
                    'mobile_number' => [
                        'data' => '下属手机号', 'style' => ['width' => 100]
                    ],
                    'role_name' => [
                        'data' => '下属角色', 'style' => ['width' => 60]
                    ],
                    'sex' => [
                        'data' => '下属性别', 'style' => ['width' => 60]
                    ]
                ]
            ],
            'created_at' => ['data' => '创建时间', 'style' => ['width' => 120]],
        ];
        
        $item = [
            'user_name' => '测试用户',
            'user_account' => 'admin',
            'dept_name' => 'e-office研发部',
            'role_name' => '测试',
            'sex' => '男',
            'mobile_number' => '1888888888',
            'sub' => [
                ['user_name' => '用户1', 'mobile_number' => '1188888888', 'role_name' => '测试', 'sex' => '男'],
                ['user_name' => '用户2', 'mobile_number' => '1288888888', 'role_name' => '测试', 'sex' => '女'],
                ['user_name' => '用户2', 'mobile_number' => '1388888888', 'role_name' => '测试', 'sex' => '男'],
                ['user_name' => '用户3', 'mobile_number' => '1488888888', 'role_name' => '测试', 'sex' => '女'],
            ],
            'created_at' => '2020-12-18 12:12:00'
        ];
        $data = [];
        for ($i = 0; $i <= 10; $i++) {
            $data[] = $item;
        }
        // 单个sheet
        //return $builder->setTitle('测试')->setHeader($header)->setDescription("测试描述\r\n测试描述")->setData($data)->generate();
        
        // 导出多个excel的zip包
        $files = [];
        
        for($i = 1; $i <= 10; $i ++) {
            list($fileName, $filePath) = $builder->setTitle('测试' . $i)->setHeader($header)->setDescription("测试描述\r\n测试描述")->setData($data)->generate();
            $files[] = $filePath;
        }
        return Export::saveAsZip($files, '测试Excel导出zip');
        // 多个sheet
        
        $builder->setTitle('测试');
        $builder->setActiveSheet(0)->setSheetName('sheet1')->setHeader($header)->setDescription('描述')->setData($data);
        $builder->setActiveSheet(1)->setSheetName('sheet2')->setHeader($header)->setDescription('描述')->setData($data);
        $builder->setActiveSheet(2)->setSheetName('sheet3')->setHeader($header)->setDescription('描述')->setData($data);
        $builder->setActiveSheet(3)->setSheetName('sheet4')->setHeader($header)->setDescription('描述')->setData($data);
        return $builder->generate();
        
        
    }
    
    private function exportCombinedData($builder, $params)
    {
        $header = [
            'user_name' => ['data' => '用户名', 'style' => ['width' => 80]],
            'user_account' => ['data' => '用户账号', 'style' => ['width' => 60]],
            'dept_name' => ['data' => '所属部门', 'style' => ['width' => 100]],
            'role_name' => ['data' => '所属角色', 'style' => ['width' => 60]],
            'sex' => ['data' => '性别', 'style' => ['width' => 40]],
            'mobile_number' => ['data' => '手机号', 'style' => ['width' => 100]],
            'sub' => ['data' => '下属', 'children' => [
                    'user_name' => [
                        'data' => '下属姓名', 'style' => ['width' => 80]
                    ],
                    'mobile_number' => [
                        'data' => '下属手机号', 'style' => ['width' => 100]
                    ],
                    'role_name' => [
                        'data' => '下属角色', 'style' => ['width' => 60]
                    ],
                    'sex' => [
                        'data' => '下属性别', 'style' => ['width' => 60]
                    ]
                ]
            ],
            'created_at' => ['data' => '创建时间', 'style' => ['width' => 120]],
        ];
        
        
        $item = [
            'user_name' => ['data' => '测试用户', 'dataType' => 'string', 'comment' => '测试', 'url' => 'https://e-office.cn'],
            'user_account' => ['data' => 'admin'],
            'dept_name' => ['data' => 'eoffice研发部'],
            'role_name' => ['data' => '测试'],
            'sex' => ['data' => '男', 'style' => ['color' => 'ffffff', 'background' => '#5e90ff']],
            'mobile_number' => ['data' => 1],
            'sub' => ['data' => [
                [
                    'user_name' => ['data' => '用户1'],
                    'mobile_number' => ['data' => '1188888888'],
                    'role_name' => ['data' => '测试'],
                    'sex' => ['data' => '男', 'style' => ['color' => 'ffffff', 'background' => '#5e90ff']]
                ],[
                    'user_name' => ['data' => '用户1'],
                    'mobile_number' => ['data' => '1288888888'],
                    'role_name' => ['data' => '测试'],
                    'sex' => ['data' => '女', 'style' => ['color' => 'ffffff', 'background' => 'dc3545']]
                ],[
                    'user_name' => ['data' => '用户1'],
                    'mobile_number' => ['data' => '1388888888'],
                    'role_name' => ['data' => '测试'],
                    'sex' => ['data' => '男', 'style' => ['color' => 'ffffff', 'background' => '#5e90ff']]
                ],
            ]],
            'created_at' => ['data' => '2020-12-18 12:12:00', 'dataType' => 'string']
        ];
        $data = [];
        for ($i = 0; $i < 10; $i ++) {
            $data[] = $item;
        }
        return $builder->setTitle('测试')->setHeader($header)
                ->setDescription("测试描述\r\n测试描述")->setData($data)->generate();
    }
    private function exportAdvancedExcel($builder, $params)
    {
        $data = [
            [['data' => '费用统计-2020年', 'colspan' => 20] ],
            [
                ['data' => '科目', 'colspan' => 4],
                ['data' => '1月'],['data' => '2月'],['data' => '3月'],['data' => '4月'],
                ['data' => '5月'],['data' => '6月'],['data' => '7月'],['data' => '8月'],
                ['data' => '9月'],['data' => '10月'],['data' => '11月'],['data' => '12月'],
                ['data' => '统计', 'colspan' => 4],
            ],[
                ['data' => '一级科目', 'colspan' => 4],
                ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                ['data' => 12, 'colspan' => 4],
            ],
            [
                ['data' => '一级科目'],
                ['data' => [
                    [
                        ['data' => '二级科目'],
                        ['data' => [
                            [
                                ['data' => '三级科目', 'colspan' => 2],
                                ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                ['data' => 12, 'colspan' => 2],
                            ],
                            [
                                ['data' => '三级科目'],
                                ['data' => [
                                    [
                                        ['data' => '四级科目'],
                                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                        ['data' => 12],
                                    ],
                                    [
                                        ['data' => '四级科目'],
                                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                                        ['data' => 12],
                                    ]
                                ]],
                                ['data' => 24]
                            ]
                        ]],
                        ['data' => 36],
                    ],
                    [
                        ['data' => '二级科目'],
                        ['data' => '三级科目', 'colspan' => 2],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 12, 'colspan' => 3],
                    ],
                    [
                        ['data' => '二级科目', 'colspan' => 3],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 12, 'colspan' => 3],
                    ]
                ]],
                ['data' => 60]
            ],
            [
                ['data' => '一级科目'],
                ['data' => [
                    [
                        ['data' => '二级科目', 'colspan' => 3],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 12, 'colspan' => 3],
                    ],[
                        ['data' => '二级科目', 'colspan' => 3],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 12, 'colspan' => 3],
                    ],[
                        ['data' => '二级科目', 'colspan' => 3],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 12, 'colspan' => 3],
                    ],[
                        ['data' => '二级科目', 'colspan' => 3],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 1], ['data' => 1],['data' => 1], ['data' => 1],
                        ['data' => 12, 'colspan' => 3],
                    ],
                ]],
                ['data' => 48]
            ]
        ];
        return $builder->setTitle('测试excel合并行列高级模式')->setData($data)->generate();
    }
}
