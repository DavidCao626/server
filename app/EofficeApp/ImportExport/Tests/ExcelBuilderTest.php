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
class ExcelBuilderTest extends UnitTest 
{
    public $callMethods = [
        'test1',
//        'test',
//        'testAdvanced'
    ];
    private $excelBuilder;
    public function __construct(ExcelBuilder $excelBuilder) 
    {
        parent::__construct();
        $this->excelBuilder = $excelBuilder;
    }
    public function test1()
    {
        $header = [
            'name'     => ['data' => '名称'],
            'detailed' => [
                'data'     => '明细',
                'children' => [
                    'age' => [
                        'data' => '年龄',
                        'children' => [
                            'age1' => ['data' => '年龄1'],
                            'age2' => ['data' => '年龄2'],
                        ]
                    ],
                    'sex' => ['data' => '性别'],
                ]
            ]
        ];

        $data = [
            [
                'name'     => [
                    'data' => 'xxg'
                ],
                'detailed' => [
                    'data' => [
                        [
                            'age' => [
                                'data' => [
                                    ['age1' => ['data' => 1], 'age2' => ['data' => 2]],
                                    ['age1' => ['data' => 2], 'age2' => ['data' => 3]],
                                    ['age1' => ['data' => 4], 'age2' => ['data' => 5]],
                                    ['age1' => ['data' => 4], 'age2' => ['data' => 5]],
                                    ['age1' => ['data' => 4], 'age2' => ['data' => 5]],
                                ]
                            ],
                            'sex' => ['data' => 'eeeeee']
                        ]
                    ]

                ]
            ]
        ];
        $this->excelBuilder
                ->setSuffix('xlsx')
                ->setTitle('测试')->setHeader($header)->setData($data)->generate();
    }
    
    public function testCombined()
    {
        $header = [
            'a' => ['data' => 'A', 'style' => ['width' => 60], 'comment' => 'https://www.baidu.com'],
            'b' => ['data' => 'B', 'style' => ['width' => 200]],
            'c' => ['data' => 'C', 'style' => ['width' => 60]],
            'd' => ['data' => 'D', 'style' => ['width' => 60]],
            'e' => ['data' => 'E', 'style' => ['width' => 60]],
            'f' => ['data' => 'F', 'style' => ['width' => 60]],
            'g' => ['data' => 'G','children' => [
                    'g_1' => [
                        'data' => 'G_1', 'style' => ['width' => 60]
                    ],
                    'g_2' => [
                        'data' => 'G_2', 'style' => ['width' => 60],'children' => [
                            'g_1_1' => [
                                'data' => 'G_1', 'style' => ['width' => 60]
                            ],
                            'g_2_1' => [
                                'data' => 'G_2', 'style' => ['width' => 60]
                            ],
                            'g_3_1' => [
                                'data' => 'G_3', 'style' => ['width' => 60],
                            ],
                            'g_4_1' => [
                                'data' => 'G_4', 'style' => ['width' => 60]
                            ]
                        ]
                    ],
                    'g_3' => [
                        'data' => 'G_3', 'style' => ['width' => 60],
                    ],
                    'g_4' => [
                        'data' => 'G_4', 'style' => ['width' => 60]
                    ]
                ]
            ],
            'h' => ['data' => 'H', 'style' => ['width' => 60]],
        ];
        
        $data = [];
        $item = [
                'a' => ['data' => '2020-12-12 12:12:12', 'dataType' => 'string'],
                'b' => ['data' => '测试', 'dataType' => 'string', 'style' => ['color' => 'ffffff', 'background' => '#dc3545']],
                'c' => ['data' => 1],
                'd' => ['data' => '1111111111.11111111'],
                'e' => ['data' => 1],
                'f' => ['data' => 1],
                'g' => ['data' => [
                        [
                            'g_1' => ['data' => 1],
                            'g_2' => ['data' => [
                                ['g_1_1' => ['data' => 1],'g_2_1' => ['data' => 1],'g_3_1' => ['data' => 1],'g_4_1' => ['data' => 1]],
                                ['g_1_1' => ['data' => 1],'g_2_1' => ['data' => 1],'g_3_1' => ['data' => 1],'g_4_1' => ['data' => 1]],
                                ['g_1_1' => ['data' => 1],'g_2_1' => ['data' => 1],'g_3_1' => ['data' => 1],'g_4_1' => ['data' => 1]],
                                ['g_1_1' => ['data' => 1],'g_2_1' => ['data' => 1],'g_3_1' => ['data' => 1],'g_4_1' => ['data' => 1]]
                            ]],
                            'g_3' => ['data' => 1],
                            'g_4' => ['data' => 1]
                        ],
                        [
                            'g_1' => ['data' => 1],
                            'g_2' => ['data' => []],
                            'g_3' => ['data' => 1],
                            'g_4' => ['data' => 1]
                        ],
                        [
                            'g_1' => ['data' => 1],
                            'g_2' => ['data' => []],
                            'g_3' => ['data' => 1],
                            'g_4' => ['data' => 1]
                        ]
                    ]
                ],
                'h' => ['data' => 1]
            ];
        for ($i = 0; $i<1; $i ++) {
            $data[] = $item;
        }
        $this->excelBuilder
                ->setSuffix('xlsx')
                ->setTitle('测试');
        $i = 0;
//        for ($i = 0; $i <= 10; $i ++) {
            $this->excelBuilder->setHeader($header, $i)
            ->setDescription("测试描述\r\n测试描述", $i)
            ->setSheetName('测试'. $i, $i)->setData($data, $i);
//        }
        $this->excelBuilder->generate();
        
        
        
    }
    private function yieldData()
    {
        $item = [
                'a' => ['value' => 1],
                'b' => ['value' => '测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你测试测你'],
                'c' => ['value' => 1],
                'd' => ['value' => 1],
                'e' => ['value' => 1],
                'f' => ['value' => 1],
                'g' => ['value' => [
                        [
                            'g_1' => ['value' => 1],
                            'g_2' => ['value' => 1],
                            'g_3' => ['value' => 1],
                            'g_4' => ['value' => 1]
                        ],
                        [
                            'g_1' => ['value' => 1],
                            'g_2' => ['value' => 1],
                            'g_3' => ['value' => 1],
                            'g_4' => ['value' => 1]
                        ],
                        [
                            'g_1' => ['value' => 1],
                            'g_2' => ['value' => 1],
                            'g_3' => ['value' => 1],
                            'g_4' => ['value' => 1]
                        ]
                    ]
                ],
                'h' => ['value' => 1]
            ];
        for($i = 1;$i< 100; $i++) {
            yield $item;
        }
    }
    public function testAdvanced()
    {
        $this->excelBuilder
                ->setSuffix('xlsx')
                ->setTitle('测试');
        $data = [
            [
                ['value' => '费用统计-2020年', 'colspan' => 20]
            ],
            [
                ['value' => '科目', 'colspan' => 4],
                ['value' => '1月'],
                ['value' => '2月'],
                ['value' => '3月'],
                ['value' => '4月'],
                ['value' => '5月'],
                ['value' => '6月'],
                ['value' => '7月'],
                ['value' => '8月'],
                ['value' => '9月'],
                ['value' => '10月'],
                ['value' => '11月'],
                ['value' => '12月'],
                ['value' => '统计', 'colspan' => 4],
            ],[
                ['value' => 'A', 'colspan' => 4],
                ['value' => 'E'],
                ['value' => 'E'],
                ['value' => 'E'],
                ['value' => 'E'],
                ['value' => 'E'],
                ['value' => 'E'],
                ['value' => 'E'],
                ['value' => 'F'],
                ['value' => 'G'],
                ['value' => 'H'],
                ['value' => 'G'],
                ['value' => 'H'],
                ['value' => 'I', 'colspan' => 4],
            ],
            [
                ['value' => 'A'],
                ['value' => [
                        [
                            ['value' => 'B-1'],
                            ['value' => [
                                [
                                    ['value' => 'C-1-1', 'colspan' => 2],
                                    ['value' => 'E'],
                                    ['value' => 'E'],
                                    ['value' => 'E'],
                                    ['value' => 'E'],
                                    ['value' => 'E'],
                                    ['value' => 'E'],
                                    ['value' => 'E'],
                                    ['value' => 'F'],
                                    ['value' => 'G'],
                                    ['value' => 'H'],
                                    ['value' => 'G'],
                                    ['value' => 'H'],
                                    ['value' => 'I-1-1', 'colspan' => 2],
                                ],
                                [
                                    ['value' => 'C-1-2'],
                                    ['value' => [
                                        [
                                            ['value' => 'D-1-1-1'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'F'],
                                            ['value' => 'G'],
                                            ['value' => 'H'],
                                            ['value' => 'G'],
                                            ['value' => 'H'],
                                            ['value' => 'I-1-1-1'],
                                        ],
                                        [
                                            ['value' => 'D-1-1-2'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'E'],
                                            ['value' => 'F'],
                                            ['value' => 'G'],
                                            ['value' => 'H'],
                                            ['value' => 'G'],
                                            ['value' => 'H'],
                                            ['value' => 'I-1-1-2'],
                                        ]
                                    ]],
                                    ['value' => 'I-1-2']
                                ]
                            ]],
                            ['value' => 'I-1'],
                        ],
                        [
                            ['value' => 'B-2'],
                            ['value' => 'C-2', 'colspan' => 2],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'F'],
                            ['value' => 'G'],
                            ['value' => 'H'],
                            ['value' => 'G'],
                            ['value' => 'H'],
                            ['value' => 'I-1', 'colspan' => 3],
                        ],
                        [
                            ['value' => 'B-2', 'colspan' => 3],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'E'],
                            ['value' => 'F'],
                            ['value' => 'G'],
                            ['value' => 'H'],
                            ['value' => 'G'],
                            ['value' => 'H'],
                            ['value' => 'I-1', 'colspan' => 3],
                        ]
                    ]],
                ['value' => 'I']
            ],
            [
                ['value' => 'A'],
                ['value' => [
                    [
                        ['value' => 'B-1', 'colspan' => 3],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'F'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'I-1', 'colspan' => 3],
                    ],[
                        ['value' => 'B-1', 'colspan' => 3],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'F'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'I-1', 'colspan' => 3],
                    ],[
                        ['value' => 'B-1', 'colspan' => 3],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'F'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'I-1', 'colspan' => 3],
                    ],[
                        ['value' => 'B-1', 'colspan' => 3],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'E'],
                        ['value' => 'F'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'G'],
                        ['value' => 'H'],
                        ['value' => 'I-1', 'colspan' => 3],
                    ],
                ]],
                ['value' => 'I']
            ]
        ];
        $i = 0;
        $this->excelBuilder->setSheetName('测试'. $i, $i)->setData($data, $i);
        $this->excelBuilder->generate();
    }
    public function test()
    {
        $header = [
            'a' => ['value' => 'A', 'style' => ['width' => '80px']],
            'b' => ['value' => 'B', 'style' => ['width' => '80px']],
            'c' => ['value' => 'C', 'style' => ['width' => '80px']],
            'd' => ['value' => 'D', 'style' => ['width' => '80px']],
            'e' => ['value' => 'E', 'style' => ['width' => '80px']],
            'f' => ['value' => 'F', 'style' => ['width' => '80px']],
            'g' => ['value' => 'G', 'style' => ['width' => '80px'],
                'children' => [
                    'g_1' => [
                        'value' => 'G_1', 'style' => ['width' => '80px']
                    ],
                    'g_2' => [
                        'value' => 'G_2', 'style' => ['width' => '80px']
                    ],
                    'g_3' => [
                        'value' => 'G_3', 'style' => ['width' => '80px']
                    ],
                    'g_4' => [
                        'value' => 'G_4', 'style' => ['width' => '80px'],
                    ]
                ]
            ],'h' => ['value' => 'H', 'style' => ['width' => '80px']],
        ];
        
        $title = '导出测试';
        $item = [
                'a' => 'a',
                'b' => 'b',
                'c' => 'c',
                'd' => 'd',
                'e' => 'e',
                'f' => 'f',
                'g' => [
                    [
                        'g_1' => 'g_1',
                        'g_2' => 'g_2',
                        'g_3' => 'g_3',
                        'g_4' => 'g_4'
                    ],
                    [
                        'g_1' => 'g_1',
                        'g_2' => 'g_2',
                        'g_3' => 'g_3',
                        'g_4' => 'g_4'
                    ],
                    [
                        'g_1' => 'g_1',
                        'g_2' => 'g_2',
                        'g_3' => 'g_3',
                        'g_4' => 'g_4'
                    ]
                ]
            ];
        $data = [];
        for($i = 0; $i<=10; $i++) {
            $data[] = $item;
        }
        $this->excelBuilder
                ->setSuffix('xlsx')
                ->setTitle($title);
        $i = 0;
//        for ($i = 0; $i <= 10; $i ++) {
            $this->excelBuilder->setHeader($header, $i)
            ->setDescription("测试描述\r\n测试描述", $i)
            ->setSheetName('测试'. $i, $i)
            ->setData($data, $i);
//        }
        $this->excelBuilder->generate();
        
//        $this->response($result);
    }
}
