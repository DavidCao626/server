<?php
namespace App\EofficeApp\Base;

use DB;
use Illuminate\Http\Request;
use Cache;
class BaseService
{
    protected $loginUserId;
    protected $loginDeptId;
    protected $loginRoleId;
    protected $currentUser;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->loginLang = 'zh-cn';
    }
    /**
     * 获取返回数据数据
     *
     * @param  object   $repositoryObj  查询资源对象
     * @param  string   $getCount       查询数量方法
     * @param  string   $getData        查询数据方法
     * @param  array    $param          查询条件
     *
     * @return array                    查询结果
     *
     * @author 齐少博
     */
    public function response($repositoryObj, $getCount, $getData, $param = [])
    {
        $result = [];

        if (!isset($param['response']) || $param['response'] == 'count') {
            $result['total'] = $repositoryObj->$getCount($param);
        }

        if (!isset($param['response']) || $param['response'] == 'data') {
            if (isset($param['autoFixPage']) && isset($result['total']) && isset($param['limit']) && isset($param['page']) && $param['page'] > 1) {
                if ($result['total'] == 0) {
                    $param['page'] = 1;
                }

                $totalPage = ceil($result['total']/$param['limit']);
                if ($totalPage < $param['page']) {
                    $param['page'] = $totalPage;
                }
            }

            $result['list'] = $repositoryObj->$getData($param);
        }

        return $result;
    }

    /**
     * @解析列表页查询参数
     *
     * @author 李志军
     *
     * @param array $param
     * @param boolean $assoc true解析为数组,false解析为对象
     *
     * @return array 解析后列表页查询参数
     */
    protected function parseParams($param, $assoc = true)
    {
        if(isset($param['fields']) && is_string($param['fields'])) {
            $param['fields'] = explode(',',rtrim($param['fields'],','));
        }

        if(isset($param['search']) && !is_array($param['search'])) {
            $param['search'] = json_decode($param['search'], $assoc);
        }

        if(isset($param['order_by']) && !is_array($param['order_by'])) {
            $param['order_by'] = json_decode($param['order_by'], $assoc);
        }

        if(isset($param['with']) && is_string($param['with'])) {
            $param['with'] = explode(',',rtrim($param['with'],','));
        }
        return $param;
    }

    /**
     * 导入数据
     *
     * @param array $tables          数据表
     * @param array $data             导入数据
     * @param array $param            导入条件
     * @param array $defaultFields    默认导入字段
     *
     * @return array 解析后列表页查询参数
     *
     * @author 齐少博
     */
    function importData($tables, $data, $param, $defaultFields) {
        $info = [
            'total'     => count($data),
            'success'   => 0,
            'error'     => 0,
        ];

        $table = $tables['table'];

        if ($param['type'] == 3) {                                                      //新增数据并清除原有数据
            DB::table($table)->delete();
            if (!empty($tables['sub_table'])) {
                foreach ($tables['sub_table'] as $k => $v) {
                    DB::table($k)->delete();
                }
            }
        }

        foreach ($data as $key => $row) {
            if (isset($row['importResult'])) {
                $info['error']++;
                continue;
            }

            $success = false;

            $primaryTable = $relationalTable = [];

            foreach ($row as $k => $v) {
                if (strpos($k, '.') === false) {
                    $primaryTable[$k] = $v ? $v : ($v === 0 || $v === '0' ? 0 : '');
                } else {
                    list($subTable, $subField) = explode('.', $k);
                    $relationalTable[$subTable][$subField] = $v ? $v : ($v === 0 || $v === '0' ? 0 : '');
                }

                if (!empty($defaultFields)) {
                    foreach($defaultFields as $k => $v) {
                        if (strpos($k, '.') === false) {
                            $primaryTable[$k] = $v;
                        } else {
                            list($subTable, $subField) = explode('.', $k);
                            $relationalTable[$subTable][$subField] = $v;
                        }
                    }
                }
            }

            if ($param['type'] == 1 || $param['type'] == 3) {                               //仅新增数据
                $success = $this->addImportData($table, $primaryTable, $param, $relationalTable, $tables);
            } else if ($param['type'] == 2) {
                $where = [$param['primaryKey'] => $primaryTable[$param['primaryKey']]];     //仅更新数据
                $success = $this->updateImportData($table, $where, $primaryTable, $param);
            } else if ($param['type'] == 4) {                                               //新增数据并更新已有数据
                $where = [$param['primaryKey'] => $primaryTable[$param['primaryKey']]];
                $oldData = DB::table($table)->where($where)->first();

                if (!empty($oldData)) {
                    $success = $this->updateImportData($table, $where, $primaryTable, $param);
                } else {
                    $success = $this->addImportData($table, $primaryTable, $param);
                }
            }

            if ($success) {
                $info['success']++;
                $data[$key]['importResult'] = importDataSuccess();
            } else {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("import.import_data_fail"));
            }
        }

        return compact('data', 'info');
    }

    /**
     * 插入数据
     *
     * @param string $tables          数据表
     * @param array $data             数据
     * @param array $param            导入条件
     * @param array $relationalTable  关联表
     * @param array $tables           数据表
     *
     * @return bool
     *
     * @author 齐少博
     *
     * @since  2017-03-03
     */
    function addImportData($table, $data, $param, $relationalTable = [], $tables = []) {
        $success = false;

        if ($id = DB::table($table)->insertGetId($data)) {
            $success = true;
        }

        if (isset($param['after']) && $success) {
            $className = isset($param['after'][0]) ? $param['after'][0] : '';
            $method = isset($param['after'][1]) ? $param['after'][1] : '';
            if ($className === '' || $method === '') {
                return $success;
            }
            app($className)->$method([
                'data'  => $data,
                'id'    => $id,
                'param' => $param
            ]);
        }

        if (!empty($relationalTable)) {
            foreach ($relationalTable as $subTable => $subField) {
                $relationKey = $tables['sub_table'][$subTable];
                $subField[$relationKey] = $id;
                DB::table($subTable)->insertGetId($subField);
            }
        }

        return $success;
    }

    /**
     * 更新数据
     *
     * @param string $tables          数据表
     * @param array $where            条件
     * @param array $data             更新数据
     * @param array $param            导入条件
     *
     * @return bool
     *
     * @author 齐少博
     *
     * @since  2017-03-03
     */
    function updateImportData($table, $where, $data, $param) {
        $success = false;

        if (DB::table($table)->where($where)->update($data)) {
            $success = true;
        }

        if (isset($param['after']) && $success) {
            app($param['after'][0])->{$param['after'][1]}([
                'data'  => $data,
                'param' => $param,
                'table' => $table
            ]);
        }

        return $success;
    }

    /**
     * 导入数据过滤(用Requests条件验证数据)
     *
     * @param array $data             导入数据
     * @param array $rules            验证规则
     *
     * @return bool
     *
     * @author 齐少博
     *
     * @since  2017-03-03
     */
    function importFilter($data, $rules) {
        $validator = app('validator')->make($data, $rules);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

    //创建时间距离当前时间是否在$minute分钟内
    public function testTime($createdAt, $minute = 10)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$minute}minute"));
        return $date <= $createdAt;
    }

    /**
     * 外发返回信息格式化
     * @param array|int $res 错误信息或者相关id信息
     * @param string $entityClass 操作的表结构
     * @param null $dynamic 额外说明
     * @return array|string
     */
    protected function handFlowOutSendResult($res, $entityClass, $dynamic = null)
    {
        if (isset($res['code'])) {
            return $res;
        } else {
            $class = app($entityClass);
            $tableName = $class->getTable();
            $primaryKey = $class->getKeyName();
            is_array($res) && $res = implode(',', $res); // 多个id
            $return = [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => $tableName,
                        'field_to' => $primaryKey,
                        'id_to' => $res
                    ]
                ]
            ];
            !is_null($dynamic) && $return['dynamic'] = $dynamic;
            return $return;
        }
    }
}
