<?php
namespace App\EofficeApp\Home\Services;

use App\EofficeApp\Base\BaseService;
use ZipArchive;
use DB;
use Schema;
use Illuminate\Support\Facades\File;
use App\Jobs\{SceneSeederJob, EofficeUpdateJob};
use Queue;
use GuzzleHttp\Client;
use Cache;
/**
 * Description of HomeService
 *
 * @author lizhijun
 */
class HomeService extends BaseService
{
    const SCENE_TEMP_ZIP = '../../scene.zip';
    const SCENE_TEMP_UNZIP = '../../scene/';
    const EMPTY_SCENE = '../../empty_scene/';
    const SERVER_MANAGE_SERVICE = 'App\EofficeApp\ServerManage\Services\ServerManageService';
    const API_ACCESS_TIMES = 10;
    public function checkSystemVersion()
    {
        return app(self::SERVER_MANAGE_SERVICE)->getNewVersionInfo([],['user_id' => 'admin']);
    }
    
    public function updateSystem()
    {
        return app(self::SERVER_MANAGE_SERVICE)->operateExe(2, ['user_id' => 'admin'], ['time' => '0']);
    }
    /**
     * 获取系统启动页状态
     * 
     * @return int
     */
    public function getBootPageStatus()
    {
        $firstLogin = get_system_param('first_login', 0);
        if ($firstLogin == 0) {
            return ecache('Home:BootPageStatus')->get();
        }
        return 1;
    }
    /**
     * 设置系统启动页状态
     * 
     * @return boolean
     */
    public function setBootPageStatus()
    {
        if (set_system_param('boot_page_status', 1)) {
            return ecache('Home:BootPageStatus')->set(1);
        }
        
        return false;
    }
    /**
     * 获取场景迁移进度
     * 
     * @return array
     */
    public function sceneSeederProgress()
    {
        return ecache('Home:SceneSeederProgress')->get();
    }
    public function emptySceneSeeder()
    {
        if (!file_exists(self::EMPTY_SCENE)){
            return false;
        }
        try {
            File::copyDirectory(self::EMPTY_SCENE . 'access', public_path('access'));
            File::copyDirectory(self::EMPTY_SCENE . 'attachment', getAttachmentDir());
        } catch (\Exception $e) {
            
        }
        $this->setSeederProgress(1, 'seeder', 10);
        $this->seederTable(self::EMPTY_SCENE);
        $this->seederData(self::EMPTY_SCENE);
        $this->setSeederProgress(1, 'seeder', 100);
        Cache::forget('eoffice_system_version');
        //当代码版本比数据库版本高时需要升级。
        Queue::push(new EofficeUpdateJob());
        $this->setBootPageStatus();
        return true;
    }
    /**
     * 场景数据迁移入口
     * 
     * @param type $sceneUrl
     * 
     * @return boolean
     */
    public function sceneSeeder($sceneUrl = null, $fileSize)
    {
        if (!$sceneUrl) {
            return false;
        }
        ecache('Home:SceneSeederProgress')->clear();
        Queue::push(new SceneSeederJob($sceneUrl, $fileSize));
        
        return true;
    }
    /**
     * 场景数据迁移
     * 
     * @param type $sceneUrl
     * 
     * @return boolean
     */
    public function handleSceneSeeder($sceneUrl, $fileSize)
    {
        ini_set('memory_limit', '1024M');
        $this->downScene($sceneUrl, $fileSize);
        if ($this->unzipScene()) {
            $this->setSeederProgress(1, 'seeder', 0);
            try {
                File::copyDirectory(self::SCENE_TEMP_UNZIP . 'access', public_path('access'));
                File::copyDirectory(self::SCENE_TEMP_UNZIP . 'attachment', getAttachmentDir());
            } catch(\Exception $e) {
                // 兼容复制失败情况不作处理
            }
            $this->setSeederProgress(1, 'seeder', 10);
            $this->seederTable(self::SCENE_TEMP_UNZIP);
            $this->seederData(self::SCENE_TEMP_UNZIP);
            $this->setSeederProgress(1, 'seeder', 100);
            Cache::forget('eoffice_system_version');
            //当代码版本比数据库版本高时需要升级。
            Queue::push(new EofficeUpdateJob());
            $this->setBootPageStatus();
        }
        return true;
    }
    /**
     * 通过url获取数据
     * 
     * @param array $param
     * 
     * @return array
     */
    public function getUrlData($param) 
    {
        $param['handle'] = 'data';
        
        if (!isset($param['url'])) {
            return ['code' => ['0x000019', 'common']];
        }
        
        return $this->request($param);
    }
    /**
     * 检测url是否在白名单里
     * 
     * @param type $url
     * 
     * @return boolean
     */
    private function checkWhiteUrl($url)
    {
        if (check_white_list($url)) {
            return true;
        }
        if ($this->checkBaseUrl($url, '?')) {
            return true;
        }
        if ($this->checkBaseUrl($url, '#')) {
            return true;
        }
        return false;
    }
    /**
     * 检测基础url是否在白名单里
     * 
     * @param type $url
     * @param type $needle
     * 
     * @return boolean
     */
    private function checkBaseUrl($url, $needle = '?')
    {
        if (strpos($url, $needle) !== false) {
            $domainUrl = substr($url, 0, strpos($url, $needle));
            if (check_white_list($domainUrl)) {
                return true;
            }
        }
        return false;
    }
    /**
     * 发起http请求
     * 
     * @param type $param
     * 
     * @return int
     */
    private function request($param)
    {
        if (!$this->checkWhiteUrl($param['url'])) {
            return ['code' => ['0x000025','common']];
        }
        //
        $method = $param['method'] ?? 'GET';
        try {
            $handle = $param['handle'] ?? 'test';

            $guzzleParam = [
                'allow_redirects' => true,
                'timeout' => config("app.url_request_time")
            ];
            $url = parse_relative_path_url($param['url']);
            $urlParse = parse_url($url);
            $url .= (isset($urlParse["query"]) && $urlParse["query"]) ? '&' : '?';
            $otherParamsString = '';
            if (count($param)) {
                foreach ($param as $key => $value) {
                    if ($key != 'url' && $key != 'handle') {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $otherParamsString .= $key . '=' . $value . '&';
                    }
                }
            }
            $url .= rtrim($otherParamsString, '&');
            $client = (new Client())->request($method, $url, $guzzleParam);
            $status = $client->getStatusCode();
            $body = $client->getBody();
            if ($status != '200' && empty($body->getContents())) {
                return 0;
            }
            if ($handle == 'test') {
                return 1;
            }
            return ['content' => $body->getContents()];
        } catch (\Exception $e) {
            return 0;
        }
    }
    /**
     * 下载场景数据
     * 
     * @param type $downPath
     * 
     * @return boolean
     */
    public function downScene($downPath, $fileSize = null)
    {
        $this->setSeederProgress(1, 'download', 0);
        $remote = fopen($downPath, 'r');
        $fileSize = $fileSize ? $fileSize : 64 * 1024 * 1024;
        $local = fopen(self::SCENE_TEMP_ZIP, 'w');
        $bytes = 0;
        while (!feof($remote)) {
            $buffer = fread($remote, 2048);
            fwrite($local, $buffer);
            $bytes += 2048;
            $progress = round(min(99.99, 100 * $bytes / $fileSize), 2);
            $this->setSeederProgress(1, 'download', $progress);
        }
        $this->setSeederProgress(1, 'download', 100);
        fclose($remote);
        fclose($local);
        return true;
    }
    /**
     * 解压场景数据
     * 
     * @return boolean
     */
    private function unzipScene()
    {
        if (file_exists(self::SCENE_TEMP_UNZIP)) {
            File::deleteDirectory(self::SCENE_TEMP_UNZIP);
        }
        $this->setSeederProgress(1, 'unzip', 0);
        $zip = new ZipArchive;
        if ($zip->open(self::SCENE_TEMP_ZIP) === true) {
            $result = true;
            $this->setSeederProgress(1, 'unzip', 10);
            if ($zip->extractTo(self::SCENE_TEMP_UNZIP)) {
                $this->setSeederProgress(1, 'unzip', 100);
            } else {
                $result = false;
                $this->setSeederProgress(0, 'unzip', 100);
            }
            $zip->close();

            return $result;
        }
        return false;
    }
    /**
     * 迁移场景数据
     */
    private function seederData($path)
    {
        $this->setSeederProgress(1, 'seeder', 22);
        $files = $this->getSeederFiles($path);
        $progress = 22;
        $average = round(76 / count($files), 2);
        foreach ($files as $file) {
            $tableName = str_replace('.json', '', $file);
            $items = json_decode(file_get_contents($path . 'db/data/' . $file), true);
            DB::table($tableName)->truncate();
            if ($tableName == 'document_mode') {
                foreach ($items as $item) {
                    DB::table('document_mode')->insert($item);
                    if ($item['mode_id'] == 0) {
                        DB::table('document_mode')->where('mode_id', 1)->update(['mode_id' => 0]);
                    }
                }
            } else {
                $maxInsert = 50;
                $count = count($items);
                if ($count < $maxInsert) {
                    DB::table($tableName)->insert($items);
                } else {
                    $pages = ceil($count / $maxInsert);

                    for ($page = 1; $page <= $pages; $page ++) {
                        $offset = $page > 0 ? (($page - 1) * $maxInsert) : 0;

                        $insertData = array_slice($items, $offset, $maxInsert);

                        DB::table($tableName)->insert($insertData);
                    }
                }
            }
            $progress += $average;
            $this->setSeederProgress(1, 'seeder', $progress);
        }
    }
    /**
     * 获取所有场景数据文件
     * 
     * @return type
     */
    private function getSeederFiles($path) 
    {
        $files = [];
        $handler = opendir($path . 'db/data/');
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (substr($filename, -strlen('.json')) === '.json') {
                    $files[] = $filename;
                }
            }
        }
        closedir($handler);
        asort($files);
        
        return $files;
    }
    /**
     * 迁移场景数据表
     */
    private function seederTable($path) 
    {
        $fromSchema = unserialize(file_get_contents($path . '/db/schema.obj'));
        $databasePlatform = Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform();
        $sqls = $fromSchema->toSql($databasePlatform);
        $regexStr = '/CREATE TABLE (.*?) \(/';
        $this->setSeederProgress(1, 'seeder', 11);
        $progress = 11;
        $average = round(20 / count($sqls), 2);
        foreach ($sqls as $sql) {
            preg_match_all($regexStr, $sql, $matchs);
            $table = $matchs[1][0];
            $this->renameTable($table, 'copysss_' . $table);
            if (!Schema::hasTable($table)) {
                DB::statement(str_replace('DATETIME DEFAULT CURRENT_TIMESTAMP', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', $sql));
            }
            $progress += $average;
            $this->setSeederProgress(1, 'seeder', $progress);
        }
    }
    /**
     * 数据表重命名
     * 
     * @param type $from
     * @param type $to
     * 
     * @return boolean
     */
    private function renameTable($from, $to)
    {
        if (Schema::hasTable($from) && !Schema::hasTable($to)) {
            return Schema::rename($from, $to);
        }
        return true;
    }
    /**
     * 设置场景迁移进度
     * 
     * @param type $status
     * @param type $step
     * @param type $percentage
     * 
     * @return boolean
     */
    private function setSeederProgress($status, $step, $percentage = 0)
    {
        $progress = ['step' => $step, 'percentage' => $percentage, 'status' => $status];
        
        return ecache('Home:SceneSeederProgress')->set($progress);
    }
}
