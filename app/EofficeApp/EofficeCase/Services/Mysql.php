<?php

namespace App\EofficeApp\EofficeCase\Services;

use Illuminate\Support\Facades\DB;
use App\EofficeApp\EofficeCase\Services\lib\Utils;

class Mysql
{
    // 数据库绝对路径
    private $databaseDir;
    // 工具类
    private $utils;

    public function __construct()
    {
        $this->utils = new Utils();
        $config = include(__DIR__ . '/conf/config.php');
        $this->databaseDir = $config['mysql_data_dir'];
    }

    // 清Mysql缓存
    public function flushMysql()
    {
        DB::statement('FLUSH TABLES');
    }

    // 分配数据库文件夹
    public function allocDatabaseName($caseId)
    {
        $caseId = str_replace('-', '_', $caseId);
        return 'eoffice_case_database_' . $caseId;
    }

    // 删除数据库
    public function deleteDatabase($caseId)
    {
        $eofficeConfigIni = new EofficeConfigIni();
        $caseInfo         = $eofficeConfigIni->getCaseConfigInfo($caseId);
        $databaseName     = $caseInfo['db_database'] ?? '';

        if (!is_string($databaseName) || empty($databaseName)) {
            return false;
        }

        $databasePath = $this->databaseDir . DIRECTORY_SEPARATOR . $databaseName;
        if (file_exists($databasePath)) {
            $this->flushMysql();
            $this->utils->dir_del($databasePath);
        }
        return true;
    }

    // 新增数据库
    public function addDatabase($newDatabaseName, $extractDir)
    {
        $databasePathTo = $this->databaseDir . DIRECTORY_SEPARATOR . $newDatabaseName;
        // win
        @exec("move /y {$extractDir}database {$databasePathTo}");
        // linux
        // @exec("mv {$extractDir}database {$databasePathTo}");
        // 修改权限
        // @exec("chmod -R 777 {$databasePathTo}");
        return true;
    }

    // 拷贝数据库文件
    public function copyDatabase($databaseName, $copyToPath)
    {
        $this->flushMysql();
        // exec("cp -R {$databaseDir} ".$caseDirPathTo.'/database');
        $databaseDir = $this->databaseDir . DIRECTORY_SEPARATOR . $databaseName;
        if (file_exists($databaseDir)) {
            $this->utils->dir_copy($databaseDir, $copyToPath);
        } else {
            return false;
        }
    }

    /**
     * 拷贝数据
     */
    public function copyData($databaseName, $copyToPath)
    {
        if (!is_dir($copyToPath)) {
            mkdir($copyToPath, 0777, true);
        }
        $copyToDataPath = $copyToPath . DS . 'data';
        if (!is_dir($copyToDataPath)) {
            mkdir($copyToDataPath, 0777, true);
        }
        $this->flushMysql();
        $defaultDB = config('database.default', '');
        $dbConfig = config('database.connections.' . $defaultDB, []);
        $connectionParams = array(
            'dbname'   => $databaseName,
            'user'     => $dbConfig['username'],
            'password' => $dbConfig['password'],
            'host'     => $dbConfig['host'],
            'port'     => $dbConfig['port'],
            'driver'   => 'pdo_' . $defaultDB,
            'charset'  => $dbConfig['charset']
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $sm = $conn->getSchemaManager();
        $fromSchema = $sm->createSchema();
        $sql = $fromSchema->toSql(new \Doctrine\DBAL\Platforms\MySQLPlatform());
        $fs = serialize($fromSchema);
        file_put_contents($copyToPath . DS . 'schema.obj', $fs);
        file_put_contents($copyToPath . DS . '/schema_mysql.sql', implode(";\r\n", $sql));

        // 导出数据
        $tables = $sm->listTables();
        $this->reconnectDB($databaseName);

        // 不导出的表
        $disbledTables =  [
            'system_log',
            'system_login_log',
            'system_login_log',
            'system_sms',
            'system_sms_recive',
        ];
        foreach ($tables as $table) {
            $tableName = $table->getName();
            if (in_array($tableName, $disbledTables)) {
                continue;
            }
            // 日志表不导出
            if (preg_match('/^eo_log/', $tableName) != 0) {
                continue;
            }
            $data = DB::table($tableName)->get()->toArray();
            if (is_array($data) && count($data) > 0) {
                file_put_contents($copyToDataPath . DS . $tableName . '.json', json_encode($data));
            }
        }

        return true;
    }

    /**
     * 重连DB
     */
    public function reconnectDB($dbName)
    {
        $defaultDB = config('database.default', '');
        $dbConfig = config('database.connections.' . $defaultDB, []);

        $tmpDatabaseConfigKey = 'mysql_tmp_' . mt_rand(0, 100);
        $tmpDatabaseConfig = $dbConfig;
        $tmpDatabaseConfig['database'] = $dbName;
        config(['database.connections.' . $tmpDatabaseConfigKey => $tmpDatabaseConfig]);
        config(['database.default' => $tmpDatabaseConfigKey]);
        DB::reconnect();
        return true;
    }

    /**
     * 获取数据库大小
     */
    public function getSize($databaseName)
    {
        $databaseDir = $this->databaseDir . DIRECTORY_SEPARATOR . $databaseName;
        if (file_exists($databaseDir)) {
            return $this->utils->dirSize($databaseDir);
        }
        return 0;
    }

    /**
     * 数据库是否存在
     */
    public function mysqlDatabaseExists($databaseName)
    {
        $databaseDir = $this->databaseDir . DIRECTORY_SEPARATOR . $databaseName;
        if (file_exists($databaseDir)) {
            return true;
        }
        return false;
    }
}
