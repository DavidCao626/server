<?php
namespace App\EofficeApp\Lang\Services;
/**
 * 多语言管理模块服务代理类
 *
 * @access public
 * @category Lang
 * @author lizhijun 2018-01-15
 *
 * @method public exportLangPackage 导出语言包
 * @method public importLangPackage 导入语言包
 * @method public getConsultAndTransLangs 获取翻译的参照语言和翻译语言
 * @method public transOnline 在线翻译多语言
 * @method public getLangModules 获取多语言模块
 * @method public deleteLangPackage 删除语言包
 * @method public addLangPackage 添加语言包
 * @method public editLangPackage 编辑语言包
 * @method public updateLangPackage 更新语言包信息
 * @method public getLangPackages 获取语言包列表
 * @method public mulitAddDynamicLang 批量添加动态多语言
 * @method public addDynamicLang 添加动态多语言
 */
use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Lang;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Cache;
use Illuminate\Support\Facades\Redis;
class LangService extends BaseService
{
    private $langRepository;
    private $langPackageRepository;
    //动态多语言表前缀
    private $langTablePrefix = 'lang_';
    private $clientLangSuffix = '.json';
    private $clientLangSuffixSize = 5;
    private $serverLangSuffix = '.php';
    private $serverLangSuffixSize = 4;
    private $clientSonFolder = '';
    private $ctrl = "\r\n";
    private $langFolderRelativePath = '/assets/langs';
    private $langServerFolderRelativePath = '/lang';
    private $clientAppPath = '/../client/';
    private $buildJsPrefix = 'function(window, undefined) {"use strict";window.i18n.';
    private $buildJsSuffix = '}(window),';
    private $limit = 300;
    private $platforms = ['web', 'mobile', 'server'];
    //全世界语言代码表
    private $langCodes = [
        'af', 'af-ZA', 'ar', 'ar-AE', 'ar-BH', 'ar-DZ', 'ar-EG', 'ar-IQ', 'ar-JO', 'ar-KW', 'ar-LB',
        'ar-LY', 'ar-MA', 'ar-OM', 'ar-QA', 'ar-SA', 'ar-SY', 'ar-TN', 'ar-YE', 'az', 'az-AZ', 'be', 'be-BY',
        'bg', 'bg-BG', 'bs-BA', 'ca', 'ca-ES', 'cs', 'cs-CZ', 'cy', 'cy-GB', 'da', 'da-DK', 'de', 'de-AT',
        'de-CH', 'de-DE', 'de-LI', 'de-LU', 'dv', 'dv-MV', 'el', 'el-GR', 'en', 'en-AU', 'en-BZ', 'en-CA',
        'en-CB', 'en-GB', 'en-IE', 'en-JM', 'en-NZ', 'en-PH', 'en-TT', 'en-US', 'en-ZA', 'en-ZW', 'eo',
        'es', 'es-AR', 'es-BO', 'es-CL', 'es-CO', 'es-CR', 'es-DO', 'es-EC', 'es-ES', 'es-ES', 'es-GT', 'es-HN',
        'es-MX', 'es-NI', 'es-PA', 'es-PE', 'es-PR', 'es-PY', 'es-SV', 'es-UY', 'es-VE', 'et', 'et-EE',
        'eu', 'eu-ES', 'fa', 'fa-IR', 'fi', 'fi-FI', 'fo', 'fo-FO', 'fr', 'fr-BE', 'fr-CA', 'fr-CH', 'fr-FR',
        'fr-LU', 'fr-MC', 'gl', 'gl-ES', 'gu', 'gu-IN', 'he', 'he-IL', 'hi', 'hi-IN', 'hr', 'hr-BA',
        'hr-HR', 'hu', 'hu-HU', 'hy', 'hy-AM', 'id', 'id-ID', 'is', 'is-IS', 'it', 'it-CH', 'it-IT', 'ja', 'ja-JP',
        'ka', 'ka-GE', 'kk', 'kk-KZ', 'kn', 'kn-IN', 'ko', 'ko-KR', 'kok', 'kok-IN', 'ky', 'ky-KG',
        'lt', 'lt-LT', 'lv', 'lv-LV', 'mi', 'mi-NZ', 'mk', 'mk-MK', 'mn', 'mn-MN', 'mr', 'mr-IN', 'ms', 'ms-BN',
        'ms-MY', 'mt', 'mt-MT', 'nb', 'nb-NO', 'nl', 'nl-BE', 'nl-NL', 'nn-NO', 'ns', 'ns-ZA', 'pa',
        'pa-IN', 'pl', 'pl-PL', 'pt', 'pt-BR', 'pt-PT', 'qu', 'qu-BO', 'qu-EC', 'qu-PE', 'ro', 'ro-RO', 'ru', 'ru-RU',
        'sa', 'sa-IN', 'se', 'se-FI', 'se-NO', 'se-SE', 'sk', 'sk-SK', 'sl', 'sl-SI', 'sq', 'sq-AL',
        'sr-BA', 'sr-SP', 'sv', 'sv-FI', 'sv-SE', 'sw', 'sw-KE', 'syr', 'syr-SY', 'ta', 'ta-IN', 'te', 'te-IN', 'th',
        'th-TH', 'tl', 'tl-PH', 'tn', 'tn-ZA', 'tr', 'tr-TR', 'ts', 'tt', 'tt-RU', 'uk', 'uk-UA', 'ur',
        'ur-PK', 'uz', 'uz-UZ', 'vi', 'vi-VN', 'xh', 'xh-ZA', 'zh', 'zh-CN', 'zh-HK', 'zh-MO', 'zh-SG', 'zh-TW', 'zu', 'zu-ZA'
    ];
    private $baiduTransCode = [
        'zh-CN' => 'zh','zh' => 'zh','zh-HK' => 'yue', 'zh-TW' => 'cht',
        'ja' => 'jp', 'ja-JP' => 'jp',
        'ko-KR' => 'kor','ko' => 'kor',
        'fr' => 'fra',
        'es' => 'spa',
        'th' => 'th','th-TH' => 'th',
        'ar' => 'ara',
        'ru' => 'ru','ru-RU' => 'ru',
        'pt' => 'pt',
        'de' => 'de',
        'it' => 'it',
        'el' => 'el','el-GR' => 'el',
        'nl' => 'nl',
        'pl' => 'pl','pl-PL' => 'pl',
        'bg' => 'bul','bg-BG' => 'bul',
        'et' => 'est','et-EE' => 'est',
        'da' => 'dan', 'da-DK' => 'dan',
        'fi' => 'fin','fi-FI' => 'fin',
        'cs' => 'cs','cs-CZ' => 'cs',
        'ro' => 'rom', 'ro-RO' => 'rom',
        'sl' => 'slo','sl-SI' => 'slo',
        'sv' => 'swe','sv-SE' => 'swe',
        'hu' => 'hu','hu-HU' => 'hu',
        'vi' => 'vie','vi-VN' => 'vie',
        'en' => 'en'
    ];
    private $translatorType = 'baidu';
    private $translatorCachePath = '';
    private $moduleCacheFile = '';
    private $moduleCacheLang = [];
    private $notInCacheLang = [];
    public function __construct()
    {
        parent::__construct();

        $this->langRepository = 'App\EofficeApp\Lang\Repositories\LangRepository';
        $this->langPackageRepository = 'App\EofficeApp\Lang\Repositories\LangPackageRepository';
    }
    public function langExists($locale)
    {
        if (!$locale) {
            return true;
        }

        if(Cache::has('package_' . $locale)) {
            return true;
        }

        if(app($this->langPackageRepository)->packageExists(['lang_code' => [$locale]])) {
            Cache::forever('package_' . $locale, time());
            return true;
        }
    }
    /**
     * 多语言排序
     *
     * @param array $data
     *
     * @return array
     */
    public function sortLangPackage($data)
    {
        if (!isset($data['sort_data']) || empty($data['sort_data'])) {
            return ['code' => ['0x050013', 'lang']];
        }

        return array_map(function($item) {
                    return app($this->langPackageRepository)->updateData(['sort' => $item[1]], ['lang_id' => [$item[0]]]);
                }, $data['sort_data']);
    }
    /**
     * 将多语言环境与用户绑定
     *
     * @param string $locale
     * @param string $userId
     *
     * @return boolean
     */
    public function bindUserLocale($locale, $userId, $apiToken)
    {
        Cache::forever('locale_' . $userId, $locale);
        ecache('Lang:Local')->set($apiToken, $locale);
        return Cache::has('locale_' . $userId);
    }
    /**
     * 根据用户id获取对应绑定的多语言环境
     *
     * @param string $userId
     *
     * @return string
     */
    public function getUserLocale($userId)
    {
        if(Cache::has('locale_' . $userId)){
            return Cache::get('locale_' . $userId);
        }

        return 'zh-CN';
    }
    /**
     * 设置在线翻译api
     *
     * @param array $data
     *
     * @return boolean
     */
    public function setTransApi($data)
    {
        if(!isset($data['trans_app_id']) || empty($data['trans_app_id'])){
            return ['code' => ['0x050015', 'lang']];
        }
        if(!isset($data['trans_app_key']) || empty($data['trans_app_key'])){
            return ['code' => ['0x050016', 'lang']];
        }
        set_system_param('trans_app_id', $data['trans_app_id']);
        set_system_param('trans_app_key', $data['trans_app_key']);
        Cache::forever('trans_app_id', $data['trans_app_id']);
        Cache::forever('trans_app_key', $data['trans_app_key']);
        return true;
    }
    /**
     * 检查翻译api是否可用
     *
     * @return boolean
     */
    public function checkTransApi()
    {
        $translator = $this->getTranslator('zh-CN', 'en');

        if(!$translator){
            return false;
        }

        return $this->trans('测试', $translator);
    }
    /**
     * 获取在线翻译api
     *
     * @return string
     */
    public function getTransApi()
    {
        return [
                'trans_app_id' => get_system_param('trans_app_id', ''),
                'trans_app_key' => get_system_param('trans_app_key', '')
            ];
    }
    public function setLangVersion()
    {
        return Cache::forever('eoffice_lang_version', time());
    }
    public function getLangVersion()
    {
        $systemVersion = version();
        if(Cache::has('eoffice_lang_version')) {
            $langVersion = Cache::get('eoffice_lang_version');
        } else {
            $langVersion = time();
            Cache::forever('eoffice_lang_version', $langVersion);
        }
        return $systemVersion . '_' . $langVersion;
    }
    public function getLangFile($module, $locale)
    {
        $datas = [];

        foreach ($this->platforms as $platform) {
            $files = config('lang.' . $module . '.' . $platform);
            $data = [];
            if ($files && !empty($files)) {
                foreach ($files as $file) {
                    if($platform == 'server') {
                        $data[$file] = $this->getPhpLangArrayByModule($locale, $file);
                    } else {
                        $data[$file] = $this->getJsonDataByModule($locale, $platform, $file);
                    }
                }
            }
            $datas[$platform] = $data;
        }

        return $datas;
    }
    /**
     * 导出语言包
     *
     * @param array $param
     *
     * @return array
     */
    public function exportLangPackage(array $param)
    {
        $consultLocale  = $param['consult_lang'] && !empty($param['consult_lang']) ? $param['consult_lang'] : 'zh-CN';
        $targetLocale   = $param['trans_lang'] ?? '';
        $module         = $param['module'] ?? '';

        if (!$module) {
            return false;
        }
        //获取导出文件的头
        $this->exportHeader = $this->getExportHeader($consultLocale, $targetLocale);

        if ($module == 'custom') {
            //导出存储在数据库中的多语言
            return $this->getExportLangsFromDatabase($consultLocale, $targetLocale);
        } else {
            //导出保存在文件中的多语言
            return $this->getExportLangsFromFile($module, $consultLocale, $targetLocale);
        }
    }
    /**
     * 获取导出的头
     *
     * @param string $consultLocale
     * @param string $targetLocale
     *
     * @return array
     */
    private function getExportHeader($consultLocale, $targetLocale)
    {
        $header = ['lang_key' => ['data' => 'lang_keys', 'style' => ['width' => '30', 'height' => '22']]];

        $header[$consultLocale] = ['data' => $consultLocale, 'style' => ['width' => '70', 'height' => '22']];

        if ($targetLocale) {
            $header[$targetLocale] = ['data' => $targetLocale, 'style' => ['width' => '70', 'height' => '22']];
        }

        return $header;
    }
    /**
     * 从文件中获取导出的多语言数据
     *
     * @param string $module
     * @param string $consultLocale
     * @param string $targetLocale
     *
     * @return array
     */
    private function getExportLangsFromFile($module, $consultLocale, $targetLocale)
    {
        $sheets = $this->getLangArrayCommon($module, $consultLocale, $targetLocale, function($langs, $consultLocale, $targetLocale) {
            $data = [];
            if (!empty($langs)) {
                foreach ($langs as $lang) {
                    $temp = ['lang_key' => $lang['lang_key']];
                    $temp[$consultLocale] = $lang['consult_lang'];
                    $temp[$targetLocale] = $lang['trans_lang'];
                    $data[] = $temp;
                }
            }
            return $data;
        }, function($item, $data, &$datas) {
            $datas[] = $this->makeUpSheetData(trans('lang.' . $item) . '-' . $item, $this->exportHeader, $data);
        });
        return ['export_title' => trans('common.' . $module) . trans('lang.lang_package'), 'sheets' => $sheets];
    }
    /**
     * 获取多语言数据的公共处理函数
     *
     * @param string $module
     * @param string $consultLocale
     * @param string $targetLocale
     * @param callback $handle
     * @param callback $ternimal
     *
     * @return array
     */
    private function getLangArrayCommon($module, $consultLocale, $targetLocale, $handle, $ternimal)
    {
        $datas = [];

        foreach ($this->platforms as $item) {
            $files = config('lang.' . $module . '.' . $item);
            $data = [];
            if ($files && !empty($files)) {
                foreach ($files as $file) {
                    $langs = $this->getConsultAndTransLangsThen($file, $consultLocale, $targetLocale, $item);

                    $data = array_merge($data, $handle($langs, $consultLocale, $targetLocale));
                }
            }
            $ternimal($item,$data, $datas);
        }

        return $datas;
    }
    /**
     * 获取动态语言包导出数据
     *
     * @param string $consultLocale
     * @param string $targetLocale
     *
     * @return array
     */
    private function getExportLangsFromDatabase($consultLocale, $targetLocale)
    {
        $consultLangs = $this->getDatabaseLangKeyValueArray($consultLocale);

        $data = [];
        if (!empty($consultLangs)) {
            foreach ($consultLangs as $key => $value) {
                $item = ['lang_key' => $key];
                $item[$consultLocale] = $value;
                array_push($data, $item);
            }

            if ($targetLocale) {
                $targetLangs = $this->getDatabaseLangKeyValueArray($targetLocale);

                $i = 0;
                foreach ($consultLangs as $key => $value) {
                    $data[$i++][$targetLocale] = $targetLangs[$key] ?? '';
                }
            }
        }

        return ['header' => $this->exportHeader, 'data' => $data, 'export_title' => trans('common.custom')];
    }
    /**
     * 获取多语言键值对数组
     *
     * @param string $locale
     *
     * @return array
     */
    private function getDatabaseLangKeyValueArray($locale)
    {
        $consultLangs = app($this->langRepository)->getAllLangs($this->getLangTable($locale));

        return $this->getDynamicLangKeyValueMap($consultLangs);
    }
    /**
     * 获取动态语言包键值对映射数组
     *
     * @param object $langs
     *
     * @return array
     */
    private function getDynamicLangKeyValueMap($langs)
    {
        $map = [];

        if(count($langs) > 0){
            foreach ($langs as $lang) {
                $map[$this->getDynamicLangKeys($lang)] = $lang->lang_value;
            }
        }

        return $map;
    }
    /**
     * 获取动态语言键
     *
     * @param object $lang
     * @param string $demilter
     *
     * @return string
     */
    private function getDynamicLangKeys($lang,$isObject = true, $demilter = '.')
    {
        if($isObject){
            return $lang->table . $demilter . $lang->column . $demilter . $lang->option . $demilter . $lang->lang_key;
        } else {
            return $lang['table'] . $demilter . $lang['column'] . $demilter . $lang['option'] . $demilter . $lang['lang_key'];
        }
    }
    /**
     * 组合一个sheet的数据数组
     *
     * @param string $sheetName
     * @param array $header
     * @param array $data
     *
     * return array
     */
    private function makeUpSheetData($sheetName, array $header, array $data)
    {
        return compact('sheetName','header','data');
    }
    /**
     * 导入翻译的多语言数据
     *
     * @param array $data
     * @param array $own
     * @param array $param
     *
     * @return boolean
     */
    public function importLangPackage(array $data, $own, $param)
    {
        if (empty($data)) {
            return ['code' => ['0x050009', 'lang']];
        }

        if (!isset($data[0]['sheetFields']) || empty($data[0]['sheetFields'])) {
            return ['code' => ['0x050010', 'lang']];
        }
        //获取翻译的语言环境
        $transLocale = isset($data[0]['sheetFields'][2]) && trim($data[0]['sheetFields'][2]) ? trim($data[0]['sheetFields'][2]) : '';
        if (!$transLocale) {
            return true;
        }

        if (sizeof($data) == 1) {
            //单个sheet的处理
            if(!isset($data[0]['sheetName']) || $data[0]['sheetFields'][0] != 'lang_keys'){
                return true;
            }

            $oldLangs = $this->getDatabaseLangKeyValueArray($transLocale);
            if (!empty($data[0]['sheetData'])) {
                $insert = $update = [];
                foreach ($data[0]['sheetData'] as $item) {
                    $langKeys = explode('.', $item['lang_keys']);
                    if (isset($oldLangs[$item['lang_keys']])) {
                        if ($oldLangs[$item['lang_keys']] != $item[$transLocale]) {
                            $update[] = $this->getDynamicLangsUpdateData($langKeys, $item[$transLocale]);
                        }
                    } else {
                        $dynamicLangsInsertData = $this->getDynamicLangsInsertData($langKeys, $item[$transLocale]);
                        if(!empty($dynamicLangsInsertData)) {
                            $insert[] = $dynamicLangsInsertData;
                        }
                    }
                }
                return $this->insertAndUpdateDynamicLangs($this->getLangTable($transLocale), $insert, $update);
            }
        } else {
            //多个sheet的处理
            foreach ($data as $sheet) {
                if (!isset($sheet['sheetName']) || !isset($sheet['sheetData']) || empty($sheet['sheetData'])) {
                    continue;
                }

                $sheetNameArray = explode('-', $sheet['sheetName']);
                $sheetNameSize = sizeof($sheetNameArray);
                if ($sheetNameSize != 2 || ($sheetNameSize == 2 && !in_array($sheetNameArray[1], $this->platforms))) {
                    continue;
                }

                $langs = [];
                foreach ($sheet['sheetData'] as $item) {
                    $langs[$item['lang_keys']] = $item[$transLocale];
                }

                $this->saveFileLangNext($langs, $sheetNameArray[1], $transLocale);
            }
            //构建前端语言包
            $this->buildAllJsonLangs($transLocale, 'mobile');
            $this->buildAllJsonLangs($transLocale, 'web');
        }

        return true;
    }
    /**
     * 获取动态多语言的更新数据
     *
     * @param array $langKeys
     * @param string $langValue
     *
     * @return array
     */
    private function getDynamicLangsUpdateData(array $langKeys, $langValue)
    {
        $wheres = [
            'table' => [$langKeys[0]],
            'column' => [$langKeys[1]],
            'option' => [$langKeys[2]],
            'lang_key' => [$langKeys[3]]
        ];

        return ['data' => ['lang_value' => $langValue ?? ''], 'wheres' => $wheres];
    }
    /**
     * 获取动态多语言的插入数据
     *
     * @param array $langKeys
     * @param string $langValue
     *
     * @return array
     */
    private function getDynamicLangsInsertData(array $langKeys, $langValue)
    {
        if(!empty($langKeys) && isset($langKeys[0]) && isset($langKeys[1]) && isset($langKeys[2]) && isset($langKeys[3])) {
            return [
                'table' => $langKeys[0],
                'column' => $langKeys[1],
                'option' => $langKeys[2],
                'lang_key' => $langKeys[3],
                'lang_value' => $langValue ?? ''
            ];
        } else {
            return [];
        }
    }
    /**
     * 批量插入动态多语言数据
     *
     * @param array $data
     *
     * @return boolean
     */
    private function mulitInsertDynamicLang(array $data, $tableName)
    {

        $pages = ceil(sizeof($data) / $this->limit);

        for ($page = 1; $page <= $pages; $page ++) {
            $offset = $this->getDynamicLangOffset($page);

            $insertData = array_slice($data, $offset, $this->limit);

            app($this->langRepository)->insertMultipleData($insertData, $tableName);
        }

        return true;
    }
    /**
     * 保存手动翻译的多语言
     *
     * @param array $data
     *
     * @return array
     */
    public function saveTransLangPackage(array $data)
    {
        $locale = $data['local'] ?? '';
        $langs = $data['langs'] ?? [];
        $module = $data['module'] ?? '';
        if (!($locale && $langs && $module)) {
            return false;
        }
        if ($module == 'custom') {
            //保存存在数据库里的多语言
            return $this->saveDatabaseLangs($locale, $langs);
        } else {
            //保存存在文件里的多语言
            return $this->saveFileLangs($locale, $module, $langs);
        }
    }
    public function getLangKeysLikeValue($table,$column, $option, $langValue, $locale = null)
    {
        $locale = $locale ? : Lang::getLocale();

        $langTable = $this->getLangTable($locale);

        $langKeys = app($this->langRepository)->getLangKeysLikeValue($table, $column, $option, $langValue, $langTable);

        return array_column($langKeys->toArray(), 'lang_key');
    }

    public function getEntityIdsLikeColumnName($table,$column, $option, $langValue, $handle = null, $locale = null)
    {
        $langKeys = $this->getLangKeysLikeValue($table, $column, $option, $langValue, $locale);

        $ids = [];

        if (!empty($langKeys)) {
            if (is_callable($handle)) {
                foreach ($langKeys as $item) {
                    $ids[] = $handle($item);
                }
            } else {
                $ids = $langKeys;
            }
        }

        return $ids;
    }
    /**
     * 保存文件多语言
     * @param type $locale
     * @param type $module
     * @param type $langs
     * @return type
     */
    private function saveFileLangs($locale, $module, $langs)
    {
        //分别保存电脑端，移动端，和服务端多语言
        return array_map(function($platform) use ($locale, $module, $langs) {
            if(!empty($langs)){
                $this->saveFileLangNext($langs[$platform], $platform, $locale);
                if($platform == 'mobile' || $platform == 'web') {
                    $this->buildAllJsonLangs($locale, $platform);
                }
            }

            return true;
        }, $this->platforms);
    }
    /**
     * 保存多语言文件进一步处理
     *
     * @param array $langs
     * @param string $platform
     * @param string $locale
     *
     * @return boolean
     */
    private function saveFileLangNext($langs,$platform, $locale)
    {
        //多语言按文件名称进行分组
        $langsGroup = $this->getLangsGroupByFileName($langs);

        if (!empty($langsGroup)) {
            foreach ($langsGroup as $fileName => $items) {
                //获取对应的历史多语言
                $oldLangs = $platform == 'server' ? $this->getPhpLangArrayByModule($locale, $fileName) : $this->getJsonDataByModule($locale,$platform, $fileName);
                //对应的历史多语言和翻译的多语言进行合并
                $langs = $this->mergeTransLangs($oldLangs, $items);
                //获取对应的多语言文件的完整路径
                $file = $platform == 'server' ? $this->getLangFileFullPath($locale, $fileName) : $this->getLangFileFullPath($locale, $fileName, $platform);
                //保存多语言到对应的文件
                $platform == 'server' ? $this->saveArrayAsPhp($langs, $file) : $this->saveArrayAsJson($langs, $file);
            }
        }

        return true;
    }

    /**
     * 按文件名分组获取多语言
     *
     * @param array $langs
     *
     * @return array
     */
    private function getLangsGroupByFileName($langs)
    {
        $langsGroup = [];

        if (!empty($langs)) {
            foreach ($langs as $key => $lang) {
                $keyArray = explode('.', $key);
                $fileName = $keyArray[0];
                unset($keyArray[0]);
                $langKey = implode('.', $keyArray);
                $langsGroup[$fileName][$langKey] = $lang;
            }
        }

        return $langsGroup;
    }
    /**
     * 保存数据库多语言
     *
     * @param string $locale
     * @param string $module
     * @param array $transLangs
     *
     * @return boolean
     */
    private function saveDatabaseLangs($locale,array $transLangs)
    {
        $tableName = $this->getLangTable($locale);

        $oldTransLangs = $this->getRelationOldLangs($transLangs, $tableName);

        $insert = $update = []; //定义插入和更新数据数组
        //获取插入和更新的语言包数据
        foreach ($transLangs as $key => $value) {
            $langKeys = explode('.', $key);

            //更新的数据和新增的数据分类存放到两个数组中
            if (isset($oldTransLangs[$key])) {
                if($oldTransLangs[$key] != $value) {
                    $update[] = $this->getDynamicLangsUpdateData($langKeys, $value);
                }
            } else {
                $dynamicLangsInsertData = $this->getDynamicLangsInsertData($langKeys, $value);
                if(!empty($dynamicLangsInsertData)) {
                    $insert[] = $dynamicLangsInsertData;
                }
            }
        }
        $locale = $locale ?: Lang::getLocale();
        Redis::del('lang_' . $locale);
        Redis::del('mulitlang_' . $locale);
        return $this->insertAndUpdateDynamicLangs($tableName, $insert, $update);
    }
    /**
     * 插入和更新动态多语言
     *
     * @param string $tableName
     * @param array $insert
     * @param array $update
     *
     * @return boolean
     */
    private function insertAndUpdateDynamicLangs($tableName, $insert, $update)
    {
        if (!empty($insert)) {
            $this->mulitInsertDynamicLang($insert, $tableName);
        }

        if (!empty($update)) {
            array_map(function($data) use ($tableName){
                return app($this->langRepository)->updateData($data['data'], $data['wheres'], $tableName);
            }, $update);
        }

        return true;
    }
    /**
     * 获取翻译语言相关的历史语言
     *
     * @param type $transLangs
     * @return type
     */
    private function getRelationOldLangs($transLangs, $tableName)
    {
        $oldTransLangs = [];

        $conditions = $this->getDatabaseLangConditionsGroupByOption($transLangs);

        if (!empty($conditions)) {
            foreach ($conditions as $key => $langKey){
                list($table, $column, $option) = explode('.', $key);

                $oldLangsTemp = app($this->langRepository)->getLangs($tableName, $table, $column, $option, $langKey);

                if(count($oldLangsTemp) > 0) {
                    foreach($oldLangsTemp as $item) {
                        $oldTransLangs[$this->getDynamicLangKeys($item)] = $item->lang_value;
                    }
                }
            }
        }

        return $oldTransLangs;
    }
    /**
     * 将翻译的语言合并到原来的语言包中
     *
     * @param array $langs
     * @param array $transLangs
     *
     * @return array
     */
    private function mergeTransLangs(array $langs, array $transLangs)
    {
        foreach ($transLangs as $key => $value) {
            $keyArray = explode('.', $key);
            $keySize = sizeof($keyArray);
            if ($keySize == 1) {
                $langs[$keyArray[0]] = $value;
            } else if ($keySize == 2) {
                $langs[$keyArray[0]][$keyArray[1]] = $value;
            } else if ($keySize == 3) {
                $langs[$keyArray[0]][$keyArray[1]][$keyArray[2]] = $value;
            } else if ($keySize == 4) {
                $langs[$keyArray[0]][$keyArray[1]][$keyArray[2]][$keyArray[3]] = $value;
            } else if ($keySize == 5) {
                $langs[$keyArray[0]][$keyArray[1]][$keyArray[2]][$keyArray[3]][$keyArray[4]] = $value;
            } else if ($keySize == 6) {
                $langs[$keyArray[0]][$keyArray[1]][$keyArray[2]][$keyArray[3]][$keyArray[4]][$keyArray[5]] = $value;
            }
        }

        return $langs;
    }
    /**
     * 获取翻译的参照语言和翻译语言
     *
     * @param array $param
     *
     * @return array
     */
    public function getConsultAndTransLangs(array $param)
    {
        if($param['lang_package_type'] == 1){
            //服务端静态
            return $this->getServerStaticLangsByModule($param['consult_lang'], $param['trans_lang'], $param['module']);
        } else if($param['lang_package_type'] == 2) {
            //服务端动态
            return $this->getServerDynamicLangsByModule($param['consult_lang'], $param['trans_lang'], $param['module']);
        } else if($param['lang_package_type'] == 3) {
            //客户端网页版
            return $this->getClientLangsByModule($param['consult_lang'], $param['trans_lang'], $param['module'], 'web');
        } else if($param['lang_package_type'] == 4) {
            //客户端移动版
            return $this->getClientLangsByModule($param['consult_lang'], $param['trans_lang'], $param['module'], 'mobile');
        }
    }
    /**
     * 按模块获取翻译的参照语言和翻译语言
     *
     * @param string $module
     * @param array $param
     *
     * @return array
     */
    public function getConsultAndTransLangsByModule($module, $param)
    {
        if ($module == 'custom') {
            $search = (isset($param['search']) && $param['search']) ? json_decode($param['search'], true) : [];
            $page = $param['page'] ?? 1;
            return $this->getServerDynamicLangsByModule($param['consult_lang'], $param['trans_lang'], $page, $search);
        } else {
            return $this->getLangArrayCommon($module, $param['consult_lang'], $param['trans_lang'], function($langs, $consultLocale, $targetLocale) {
                        return $langs;
                    }, function($item, $data, &$datas) {
                        $datas[$item] = $data;
                    });
        }
    }
    /**
     * 处理翻译语言和参照语言映射关系数组
     *
     * @param string $fileName
     * @param array $consultLocale
     * @param array $transLocale
     * @param string $type
     *
     * @return array
     */
    private function getConsultAndTransLangsThen($fileName, $consultLocale, $transLocale,$type)
    {
        if($type == 'server') {
            $consult = $this->getPhpLangArrayByModule($consultLocale, $fileName);

            $trans = $this->getPhpLangArrayByModule($transLocale, $fileName);
        } else {
            $consult = $this->getJsonDataByModule($consultLocale, $type, $fileName);

            $trans = $this->getJsonDataByModule($transLocale, $type, $fileName);
        }

        return $this->getTransLangMaps($consult, $trans, '', [], $fileName);
    }
    /**
     * 获取动态多语言参照语言和翻译语言
     *
     * @param string $locale1
     * @param string $locale2
     * @param string $module
     *
     * @return array
     */
    private function getServerDynamicLangsByModule($localeOne, $localeTwo, $page, $search = [])
    {
        $locale1 = $localeOne;
        $locale2 = $localeTwo;
        if (!empty($search)) {
            $keys = array_keys($search);
            if ($keys[0] == 'trans_lang') {
                $locale1 = $localeTwo;
                $locale2 = $localeOne;
                $search['lang_value'] = $search['trans_lang'];
                unset($search['trans_lang']);
            } else {
                $search['lang_value'] = $search['consult_lang'];
                unset($search['consult_lang']);
            }
        }

        $langs1 = $this->getOnePageDynamicLangs($page, $locale1, $search);
        $langMaps = [];
        if (count($langs1) > 0) {
            $langs2 = $this->getRelationOldLangs($langs1, $this->getLangTable($locale2));

            $langMaps = $locale1 == $localeOne ? $this->getTransLangMaps($langs1, $langs2) : $this->getTransLangMaps($langs2, $langs1);
        }

        return $langMaps;
    }
    /**
     * 按选项option分组，数据库多语言查询条件
     *
     * @param array $langs
     *
     * @return array
     */
    private function getDatabaseLangConditionsGroupByOption(array $langs)
    {
        $conditions = [];
        if (!empty($langs)) {
            foreach ($langs as $key => $value) {
                list($table, $column, $option, $langKey) = explode('.', $key);
                if (isset($conditions[$table . '.' . $column . '.' . $option])) {
                    $conditions[$table . '.' . $column . '.' . $option][] = $langKey;
                } else {
                    $conditions[$table . '.' . $column . '.' . $option] = [$langKey];
                }
            }
        }
        return $conditions;
    }
    /**
     * 获取动态多语言的开始位置
     *
     * @param int $page
     *
     * @return int
     */
    private function getDynamicLangOffset($page)
    {
        return $page > 0 ? (($page - 1) * $this->limit) : 0;
    }
    /**
     * 获取客户端翻译的参照语言和翻译语言
     *
     * @param type $consultLocal
     * @param type $transLocal
     * @param type $module
     * @param type $type
     *
     * @return array
     */
    private function getClientLangsByModule($consultLocal, $transLocal, $module, $type = 'web')
    {
        return $this->getTransLangMaps($this->getJsonDataByModule($consultLocal, $type, $module), $this->getJsonDataByModule($transLocal, $type, $module));
    }
    /**
     * 获取服务端静态语言的翻译的参照语言和翻译语言
     *
     * @param string $consultLocal
     * @param string $transLocal
     * @param string $module
     *
     * @return array
     */
    private function getServerStaticLangsByModule($consultLocal,$transLocal, $module)
    {
        return $this->getTransLangMaps($this->getPhpLangArrayByModule($consultLocal, $module), $this->getPhpLangArrayByModule($transLocal, $module));
    }
    /**
     * 获取翻译语言，对照语言映射关系数组
     *
     * @param array $consultLangs
     * @param array $transLangs
     *
     * @return array
     */
    private function getTransLangMaps(array $consultLangs, array $transLangs, $keys = '', $maps = [], $prefix = '')
    {
        if (!empty($consultLangs)) {
            foreach ($consultLangs as $key => $value) {
                $joinKey = $keys ? $keys . '.' . $key : $key;

                if (is_array($value)) {
                    if (!empty($value)) {
                        // $maps = $this->getTransLangMaps($value, $transLangs[$key] ?? [], $joinKey, $maps, $prefix);
                        $maps = $this->getTransLangMaps($value, isset($transLangs[$key]) ? (is_array($transLangs[$key]) ? $transLangs[$key] : ['field_name' => $transLangs[$key]]) : [], $joinKey, $maps, $prefix); // 20190927,zyx,兼容不同语言包中同一参数不同结构导致的报错
                    }
                } else {
                    $item =  [
                        'lang_key' => $prefix ? $prefix .'.' .$joinKey : $joinKey,
                        'consult_lang' => $value,
                        'trans_lang' => $transLangs[$key] ?? ''
                    ];
                    array_push($maps, $item);
                }
            }
        }

        return $maps;
    }
    /**
     * 根据模块获取语言包json文件，并且解析为数组
     *
     * @param string $local
     * @param string $type
     * @param string $module
     *
     * @return array
     */
    private function getJsonDataByModule($local, $type, $module)
    {
        return $this->getArrayFromJson($this->getLangFileFullPath($local, $module, $type));
    }
    /**
     * 从php语言文件里获取语言数组
     *
     * @param type $local
     * @param type $module
     *
     * @return type
     */
    private function getPhpLangArrayByModule($local, $module)
    {
        return $this->getArrayFromPhp($this->getLangFileFullPath($local, $module));
    }
    /**
     * 从json文件获取数组
     *
     * @param string $file
     *
     * @return array
     */
    private function getArrayFromJson($file)
    {
        if (file_exists($file)) {
            $result = json_decode(file_get_contents($file), true);
			if($result && is_array($result)) {
				return $result;
			}
        }

        return [];
    }
    /**
     * 从PHP文件获取数组
     *
     * @param string $file
     *
     * @return array
     */
    private function getArrayFromPhp($file)
    {
        if(file_exists($file)){
            $array = require $file;

            if(is_array($array)){
                return $array;
            }
        }

        return [];
    }
    /**
     * 清空存在数据表里的全部多语言
     *
     * @param string $locale
     *
     * @return boolean
     */
    public function langClear($locale)
    {
        return app($this->langRepository)->clearLangs($this->getLangTable($locale));
    }
    /**
     * 多语言在线翻译， 默认调用google翻译api
     *
     * @param array $param
     *
     * @return boolean
     */
    public function transOnline($param)
    {
        //将数组中对应的值赋给变量
        $replace            = $param['replace'] ?? 1;
        $langPackageType    = $param['lang_package_type'];
        $consultLang        = $param['consult_lang'];
        $transLang          = $param['trans_lang'];
        $module             = $param['module'];
        //判断语言编码是否符合国际标准
        if(!in_array($transLang, $this->langCodes)){
            return ['code' => ['0x050014', 'lang']];
        }
        if (strpos($consultLang, '.') !== false || strpos($consultLang, '..') !== false || strpos($consultLang, '/') !== false){
            return ['code' => ['0x050014', 'lang']];
        }
        if (strpos($module, '.') !== false || strpos($module, '..') !== false || strpos($module, '/') !== false){
            return ['code' => ['0x050020', 'lang']];
        }
        if($langPackageType == 1){
            //服务端静态
            return $this->transServerStaticLangsByModule($consultLang, $transLang, $module, $replace);
        } else if($langPackageType == 2) {
            //服务端动态
            return $this->transServerDynamicLangs($consultLang, $transLang, $module, $replace);
        } else if($langPackageType == 3) {
            //客户端网页版
            return $this->transClientLangsByModule($consultLang, $transLang, $module, $replace, 'web');
        } else if($langPackageType == 4) {
            //客户端移动版
            return $this->transClientLangsByModule($consultLang, $transLang, $module, $replace, 'mobile');
        }
    }
    /**
     * 翻译客户端多语言
     *
     * @param string $source
     * @param string $target
     * @param string $module
     * @param int $replace
     * @param string $type
     *
     * @return boolean
     */
    private function transClientLangsByModule($source, $target, $module, $replace = 1, $type = 'web')
    {
        $consultLangs = $this->getJsonDataByModule($source, $type, $module);
        if(empty($consultLangs)){
           return true;
        }

        $translator = $this->getTranslator($source, $target);
        if(!is_object($translator)){
            return $translator;
        }

        $targetFile = $this->getLangFileFullPath($target, $module, $type);
        $this->setTransModuleCacheFile('client_' . $type . '_' . $module);
        if($replace){
            $data = $this->transLangs($translator, $consultLangs);
        } else {
            //新增翻译
            $oldTransLangs = $this->getArrayFromJson($targetFile);

            $data = $this->transAddLangs($translator, $consultLangs, $oldTransLangs);
        }
        $this->saveTransModuleCacheFile();
        if($this->saveArrayAsJson($data, $targetFile)) {
            return $this->buildAllJsonLangs($target, $type);
        }
        return false;
    }
    private function setTransModuleCacheFile($file)
    {
        $path = $this->translatorCachePath . '/' . $file.'.trans';
        if (!is_file($path)) {
            $langs = [];
            file_put_contents($path, json_encode($langs));
        }
        $this->moduleCacheFile = $path;
        $this->moduleCacheLang = json_decode(file_get_contents($path), true);
    }
    /**
     * 将json文件构建为可访问执行的js文件
     *
     * @param string $local
     * @param string $name
     * @param string $type
     *
     * @return boolean
     */
    private function buildAllJsonLangs($local, $type = 'web')
    {
        $clientLangPath = $this->getClientLangFolder('', $type) . '/';
        $modules = $this->getLangModulesByPath($this->getClientLangFolder($local, $type), $this->clientLangSuffix, $this->clientLangSuffixSize);
        if($type == 'web') {
            $appFile = $clientLangPath . $local .'/app.json';
            $webFile = $clientLangPath . $local .'/web.json';
            $appModules = ['common', 'eui', 'login', 'component', 'home-page', 'form-parse', 'registers'];
            $this->mergeLangsAndsaveFile($appModules, $local, $type, $appFile);
            if(!empty($modules)) {
                $webModules = [];
                foreach ($modules as $m) {
                    if(!in_array($m, $appModules) && !in_array($m, ['app', 'web'])){
                        $webModules[] = $m;
                    }
                }
                $this->mergeLangsAndsaveFile($webModules, $local, $type, $webFile);
            }

        } else {
            $mobileFile = $clientLangPath . $local .'/mobile.json';
            if(!empty($modules)) {
                $mobileModules = [];
                foreach ($modules as $m) {
                    if($m != 'mobile'){
                        $mobileModules[] = $m;
                    }
                }
                $this->mergeLangsAndsaveFile($mobileModules, $local, $type, $mobileFile);
            }
        }
        return true;
    }
    private function mergeLangsAndsaveFile($modules, $local, $type, $buildJsFile)
    {
        $buildContent = [];
        if(!empty($modules)) {
            foreach ($modules as $module) {
                $data = $this->getJsonDataByModule($local, $type, $module);
                $buildContent[$this->toCamelCase($module)] = $data;
            }
        }

        file_put_contents($buildJsFile, json_encode($buildContent, 320), LOCK_EX);

        return true;
    }
    /**
     * 转驼峰
     *
     * @param string $str
     * @param string $delimter
     *
     * @return string
     */
    private function toCamelCase($str, $delimter = '-')
    {
        $array = explode($delimter, $str);

        $name = array_reduce($array, function($carry, $item) {
            return $carry . ucfirst($item);
        });

        return lcfirst($name);
    }
    /**
     * 翻译服务端动态多语言
     *
     * @param string $source
     * @param string $target
     * @param int $page
     * @param int $replace
     *
     * @return boolean
     */
    private function transServerDynamicLangs($source, $target, $page, $replace = 1)
    {
        //获取参照多语言
        $consultLangs = $this->getOnePageDynamicLangs($page, $source);
        if (empty($consultLangs)) {
            return true;
        }
        //获取翻译器
        $translator = $this->getTranslator($source, $target);
        if (!is_object($translator)) {
            return $translator;
        }
        $this->setTransModuleCacheFile('server_dynamic');
        $tableName = $this->getLangTable($target);
        $insert = []; //定义插入和更新数据数组
        if ($replace == 1) {
            /**
             * 覆盖翻译
             */
            $data = $this->transLangs($translator, $consultLangs);
            //获取插入和更新的语言包数据
            foreach ($data as $key => $value) {
                $dynamicLangsInsertData = $this->getDynamicLangsInsertData(explode('.', $key), $value);
                if(!empty($dynamicLangsInsertData)) {
                    $insert[] = $dynamicLangsInsertData;
                }
            }
            $this->saveTransModuleCacheFile();
            // 清除门户缓存
            $this->clearPortalLayoutCache();
            return $this->mulitInsertDynamicLang($insert, $tableName);
        } else {
            /**
             * 新增翻译
             */
            //根据参照多语言获取对应的历史目标多语言
            $targetLangs = $this->getRelationOldLangs($consultLangs, $tableName);
            //判断对应的多语言是否是新增的，如果是则加入数组
            $insertTargetLangs = [];
            foreach ($consultLangs as $key => $value) {
                if (!isset($targetLangs[$key])) {
                    $insertTargetLangs[$key] = $value;
                }
            }

            $data = $this->transLangs($translator, $insertTargetLangs);
            //获取插入和更新的语言包数据
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $dynamicLangsInsertData = $this->getDynamicLangsInsertData(explode('.', $key), $value);
                    if(!empty($dynamicLangsInsertData)) {
                        $insert[] = $dynamicLangsInsertData;
                    }
                }
                $this->saveTransModuleCacheFile();
                // 清除门户缓存
                $this->clearPortalLayoutCache();
                return $this->mulitInsertDynamicLang($insert, $tableName);
            }
        }

        return true;
    }
    private function clearPortalLayoutCache()
    {
        $keys = Redis::keys('laravel:portal_layout_*');
        if ($keys) {
            Redis::del($keys);
        }
    }
    /**
     * 根据文本，及其对应的语言环境进行翻译
     *
     * @staticvar object $translator
     * @param string $text
     * @param string $source
     * @param string $target
     *
     * @return string
     */
    public function eofficeTranslate($text, $source = 'zh-CN', $target = 'en')
    {
        return $text;
//        static $translator;
//
//        if(!$translator){
//            $temp = $this->getTranslator($source, $target);
//            if(!is_object($temp)){
//                return $text;
//            }
//            $translator = $temp;
//        }
//
//        return $translator->translate($text) ?? '';
    }
    /**
     * 获取一页动态多语言
     *
     * @param int $page
     * @param string $locale
     * @param array $search
     *
     * @return array
     */
    private function getOnePageDynamicLangs($page, $locale, $search = [])
    {
        $offset = $this->getDynamicLangOffset($page);

        $consultLangs = app($this->langRepository)->getOnePageLangs($this->getLangTable($locale), $offset, $this->limit, $search);

        return $this->getDynamicLangKeyValueMap($consultLangs);
    }
    /**
     * 翻译服务端静态多语言
     *
     * @param string $source
     * @param string $target
     * @param int $module
     * @param string $replace
     *
     * @return boolean
     */
    private function transServerStaticLangsByModule($source, $target, $module, $replace = 1)
    {
        //获取翻译的参照语言
        $consultLangs = $this->getPhpLangArrayByModule($source, $module);
        if(empty($consultLangs)){
           return true;
        }
        //获取翻译器
        $translator = $this->getTranslator($source, $target);
        if(!is_object($translator)){
            return $translator;
        }

        $targetFile = $this->getLangFileFullPath($target, $module);
        $this->setTransModuleCacheFile('server_static_' . $module);
        if($replace){
            //覆盖翻译
            $data = $this->transLangs($translator, $consultLangs);
        } else {
            //新增翻译
            $oldTransLangs = $this->getArrayFromPhp($targetFile);

            $data = $this->transAddLangs($translator, $consultLangs, $oldTransLangs);
        }
        $this->saveTransModuleCacheFile();
        return $this->saveArrayAsPhp($data, $targetFile);
    }
    private function saveTransModuleCacheFile()
    {
        if(!empty($this->notInCacheLang)) {
            if(is_array($this->moduleCacheLang) && !empty($this->moduleCacheLang)) {
                $this->moduleCacheLang = array_merge($this->moduleCacheLang, $this->notInCacheLang);
            } else {
                $this->moduleCacheLang = $this->notInCacheLang;
            }

            file_put_contents($this->moduleCacheFile, json_encode($this->moduleCacheLang));
        }
    }
    /**
     * 获取多语言文件的完整路径
     *
     * @param string $locale
     * @param string $fileName
     * @param string $platform
     *
     * @return string
     */
    private function getLangFileFullPath($locale, $fileName, $platform = null)
    {
        if($platform){
            return $this->getClientLangFolder($locale, $platform) . '/' . $fileName . $this->clientLangSuffix;
        } else {
            return $this->getServerLangFolder($locale)  . '/' . $fileName . $this->serverLangSuffix;
        }
    }
    /**
     * 获取翻译器
     *
     * @param string $source
     * @param string $target
     *
     * @return httpClient
     */
    private function getTranslator($source, $target)
    {
        $appId = $this->getSystemParam('trans_app_id');
        $appKey = $this->getSystemParam('trans_app_key');
        $this->baseUrl = 'http://api.fanyi.baidu.com/api/trans/vip/translate';
        if(!$appId || !$appKey){
            return false;
        }
        if(!$this->checkUrl($this->baseUrl)){
            return false;
        }
        $this->httpClient = new GuzzleHttpClient(); // Create HTTP client
        if(!isset($this->baiduTransCode[$source]) || !isset($this->baiduTransCode[$target])){
            return false;
        }
        $this->sourceLang = $this->baiduTransCode[$source];
        $this->targetLang = $this->baiduTransCode[$target];
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->translatorCachePath = $this->getTranslatorCache($this->sourceLang, $this->targetLang);
        return $this->httpClient;
    }
    private function getTranslatorCache($source, $target)
    {
        $transMapFolder = str_replace('-', '_', $source) . '_' . str_replace('-', '_', $target);
        $cacheFolder = resource_path('trans/' . $this->translatorType . '/' . $transMapFolder);
        if (!is_dir($cacheFolder)) {
            mkdir($cacheFolder, 0777, true);
        }
        return $cacheFolder;
    }
    private function trans($text, $httpClient = null)
    {
        $httpClient = $httpClient ? $httpClient : $this->httpClient;
        $salt = random_int(1000, 9999);
        $data = [
            'q' => $text,
            'from' => $this->sourceLang,
            'to' => $this->targetLang,
            'appid' => $this->appId,
            'salt' => $salt,
            'sign' => md5($this->appId . $text . $salt . $this->appKey)
        ];
        try {
            $response = $httpClient->post($this->baseUrl, [
                    'form_params'  => $data,
                ]);
        } catch (GuzzleRequestException $e) {
            throw new ErrorException($e->getMessage());
        }
        $body = $response->getBody();
        $contents = $body->getContents();
        $transResult = json_decode($contents,true);

        return $transResult['trans_result'][0]['dst'];
    }
    private function getSystemParam($key)
    {
        if(Cache::has($key)){
            $appId = Cache::get($key);
        } else {
            $appId = get_system_param($key);
            Cache::forever($key, $appId);
        }
        return $appId;
    }
    public function checkUrl($url)
    {
        set_error_handler(function($errorNo, $errorStr, $errorFile, $errorLine) {
            return $errorStr;
        });
        $check = file_get_contents($url);
        if ($check) {
            $status = true;
        } else {
            $status = false;
        }
        return $status;
    }
    /**
     * 覆盖翻译
     *
     * @param TranslateClient $translator
     * @param array $langs
     * @param array $data
     *
     * @return array
     */
    private function transLangs($translator, $langs, &$data = [])
    {
        if(!empty($langs)) {
            foreach ($langs as $key => $value) {
                if(is_array($value)){
                    if(!empty($value)){
                        $data[$key] = [];
                        $this->transLangs($translator, $value, $data[$key]);
                    }
                } else {
                    //判断是否是消息提醒，消息提醒不翻译，因为包含了占位符。
                    if(strpos($key,'system_reminds.') !== false){
                        $data[$key] = $value;
                    } else {
                        if($result = $this->getLangFromCache($value)) {
                            $data[$key] = $result;
                        } else {
                            $result = $this->trans($value,$translator) ?? '';
                            if($result) {
                                $this->notInCacheLang[$value] = $result;
                            }
                            $data[$key] = $result;
                        }
                    }
                }
            }
        }

        return $data;
    }
    private function getLangFromCache($key)
    {
        if(empty($this->moduleCacheLang)) {
            return false;
        }
        if(isset($this->moduleCacheLang[$key]) && $this->moduleCacheLang[$key]) {
            return $this->moduleCacheLang[$key];
        }
        return false;
    }
    /**
     * 新增翻译
     *
     * @param  TranslateClient $translator
     * @param array $langs
     * @param array $oldTransLangs
     * @param array $data
     *
     * @return array
     */
    private function transAddLangs($translator, $langs, $oldTransLangs, &$data = [])
    {
        if (!empty($langs)) {
            foreach ($langs as $key => $value) {
                if (is_array($value)) {
                    if (!empty($value)) {
                        $oldTransLang = $oldTransLangs[$key] ?? [];
                        $data[$key] = [];
                        $this->transAddLangs($translator, $value, $oldTransLangs, $data[$key]);
                    }
                } else {
                    if(isset($oldTransLangs[$key])) {
                        $data[$key] = $oldTransLangs[$key];
                    } else {
                        if($result = $this->getLangFromCache($value)) {
                            $data[$key] = $result;
                        } else {
                            $result = $this->trans($value,$translator) ?? '';
                            if($result) {
                                $this->notInCacheLang[$value] = $result;
                            }
                            $data[$key] = $result;
                        }
                    }
                }
            }
        }
        return $data;
    }
    /**
     * 获取语言包模块
     *
     * @param string $locale
     * @param string $type
     *
     * @return array
     */
    public function getLangModules($locale, $type)
    {
        if ($type == 1) {
            //获取服务端静态语言包模块
            $modules = $this->getLangModulesByPath($this->getServerLangFolder($locale), $this->serverLangSuffix, $this->serverLangSuffixSize);
        } else if ($type ==2) {
            //获取服务端动态语言包分页
            $modules = $this->getDynamicLangsPageArray($locale);
        } else if ($type == 3) {
            //获取客户端web版语言包模块
            $modules = $this->getLangModulesByPath($this->getClientLangFolder($locale, 'web'), $this->clientLangSuffix, $this->clientLangSuffixSize);
            if(isset($modules['web'])){
                unset($modules['web']);
            }
            if(isset($modules['app'])){
                unset($modules['app']);
            }
        } else if ($type == 4) {
            //获取客户端移动版语言包模块
            $modules = $this->getLangModulesByPath($this->getClientLangFolder($locale, 'mobile'), $this->clientLangSuffix, $this->clientLangSuffixSize);
            if(isset($modules['mobile'])){
                unset($modules['mobile']);
            }
        }

        return ['total' => sizeof($modules), 'modules' => $modules];
    }
    /**
     * 获取多语言模块，不分前后端
     *
     * @return array
     */
    public function getTransModules()
    {
        $modulesConfig = config('lang');

        $moduleKeys = array_keys($modulesConfig);
        array_push($moduleKeys, 'custom');

        return array_map(function($data){
            return [
                'module_key' => $data,
                'module_name' => trans('common.' . $data)
            ];
        }, $moduleKeys);
    }
    /**
     * 获取动态语言分页数组
     *
     * @param string $locale
     *
     * @return array
     */
    private function getDynamicLangsPageArray($locale)
    {
        $total = app($this->langRepository)->getLangCount($this->getLangTable($locale));

        $pages = ceil($total / $this->limit);

        $pageArray = [];

        for ($page = 1; $page <= $pages; $page ++) {
            array_push($pageArray, $page);
        }

        return $pageArray;
    }
    /**
     * 根据语言包路径获取语言包所有模块
     *
     * @param string $langPath
     * @param string $langSuffix
     * @param int $langSuffixSize
     *
     * @return array
     */
    private function getLangModulesByPath($langPath, $langSuffix, $langSuffixSize)
    {
        $modeuls = [];

        $handler = opendir($langPath);
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (strpos($filename, $langSuffix)) {
                    $module = substr($filename, 0, -$langSuffixSize);

                    $modeuls[$module] = $module;
                }
            }
        }

        closedir($handler);
        asort($modeuls);

        return $modeuls;
    }
    /**
     * 删除语言包
     *
     * @param int $langId
     *
     * @return boolean
     */
    public function deleteLangPackage($langId)
    {
        $package = app($this->langPackageRepository)->getDetail($langId);

        if($package){
            if($package->is_default) {
                return ['code' => ['0x050018', 'lang']];
            }
            //删除语言包数据
            if(app($this->langPackageRepository)->deleteById([$langId])) {
                $locale = $package->lang_code;
                Cache::forget('package_' . $locale);
                //删除多语言表
                app($this->langRepository)->dropTable($this->getLangTable($locale));
                //清空多语言文件
                $this->cleanFolder($this->getServerLangFolder($locale));
                $this->cleanFolder($this->getClientLangFolder($locale,'web'));
                $this->cleanFolder($this->getClientLangFolder($locale,'mobile'));

                return true;
            }
        }

        return false;
    }
    /**
     * 清空文件夹
     *
     * @param string $folder
     */
    private function cleanFolder($folder)
    {
        $op = dir($folder);

        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (is_dir($op->path . '/' . $item)) {
                $this->cleanFolder($op->path . '/' . $item);
                rmdir($op->path . '/' . $item);
            } else {
                if (file_exists($op->path . '/' . $item)) {
                    unlink($op->path . '/' . $item);
                }
            }
        }
        try {
            rmdir($folder);
        } catch (Exception $e) {
            $this->cleanFolder($folder);
        }
    }
    /**
     * 新增语言包
     *
     * @param array $data
     *
     * @return array
     */
    public function addLangPackage(array $data, $copyFiles = false)
    {
        return $this->handleLangPackage($data, null, function($data, $langId)  use($copyFiles) {
                    $data['effect'] = 0;
                    $data['sort'] = 0;
                    //复制保存在文件中的多语言
                    $locale = $data['lang_code'];
                    if($copyFiles) {
                        array_map(function($platform) use ($locale){
                            if($platform != 'server') {
                                $sourcefolder = $this->getClientLangFolder('zh-CN', $platform);
                                $tragetfolder = $this->getClientLangFolder($locale, $platform);
								return $this->createEmptyFiles($sourcefolder, $tragetfolder);
							}
                        }, $this->platforms);
                    }
                    return app($this->langPackageRepository)->insertData($data);
                });
    }
    /**
     * 复制语言包
     *
     * @param array $data
     *
     * @return boolean
     */
    public function copyLangPackage(array $data)
    {
        //被复制的多语言代码
        $sourceLocale = $data['source_lang_code'] ?? '';
        if (!$sourceLocale) {
            return ['code' => ['0x050004', 'lang']];
        }

        if($this->addLangPackage($data)) {
            $locale = $data['lang_code'];
            //复制保存在数据库中的多语言
            $consultLangs = app($this->langRepository)->getAllLangs($this->getLangTable($sourceLocale));
            if(count($consultLangs) > 0){
                $databaseLangs = [];
                foreach ($consultLangs as $lang) {
                    $databaseLangs[] = [
                        'lang_key' => $lang->lang_key,
                        'lang_value' => $lang->lang_value,
                        'table' => $lang->table,
                        'column' => $lang->column,
                        'option' => $lang->option,
                    ];
                }
                $langTable = $this->getLangTable($locale);
                app($this->langRepository)->clearLangs($langTable);
                $this->mulitInsertDynamicLang($databaseLangs, $langTable);
            }
            //复制保存在文件中的多语言
            array_map(function($platform) use ($sourceLocale, $locale){
                if($platform == 'server') {
                    $sourcefolder = $this->getServerLangFolder($sourceLocale);
                    $tragetfolder = $this->getServerLangFolder($locale);
                } else {
                    $sourcefolder = $this->getClientLangFolder($sourceLocale, $platform);
                    $tragetfolder = $this->getClientLangFolder($locale, $platform);
                }
                return $this->copyFiles($sourcefolder, $tragetfolder);
            }, $this->platforms);
            //构建前端语言包
            $this->buildAllJsonLangs($locale, 'mobile');
            $this->buildAllJsonLangs($locale, 'web');
            return true;
        }

        return false;
    }
    private function createEmptyFiles($source, $target)
    {
        foreach (glob($source . "/*") as $file) {
            if (is_dir($file)) {
                $this->createEmptyFiles($file, $target);
            } else {
                $filename = substr($file, strripos($file, "/"));
                file_put_contents($target . $filename, '');
            }
        }
    }
    /**
     * 复制文件夹下的文件到新文件夹下
     *
     * @param string $source
     * @param string $target
     */
    private function copyFiles($source, $target)
    {
        foreach (glob($source . "/*") as $file) {
            if (is_dir($file)) {
                $this->copyFiles($file, $target);
            } else {
                $filename = substr($file, strripos($file, "/"));

                copy($file, $target . $filename);
            }
        }
    }

    /**
     * 编辑语言包
     *
     * @param array $data
     * @param int $langId
     *
     * @return boolean
     */
    public function editLangPackage(array $data, $langId)
    {
        return $this->handleLangPackage($data, $langId, function($data, $langId) {
                    return $this->updateLangPackage($data, $langId);
                });
    }
    /**
     * 处理语言包数据
     *
     * @param array $data
     * @param int $langId
     * @param func $then
     *
     * @return object
     */
    private function handleLangPackage(array $data, $langId = null, $then)
    {
        $formChecked = $this->checkFormData($data, $langId);

        if(isset($formChecked['code'])){
            return $formChecked;
        }

        Cache::forever('package_' . $data['lang_code'], time());

        $this->getClientLangFolder($data['lang_code'], 'web');
        $this->getClientLangFolder($data['lang_code'], 'mobile');
        $this->getServerLangFolder($data['lang_code']);

        return $then($data, $langId);
    }
    /**
     * 获取语言包列表
     *
     * @param array $param
     *
     * @return array
     */
    public function getLangPackages($param)
    {
        return $this->response(app($this->langPackageRepository), 'getLangPackagesTotal', 'getLangPackages', $this->parseParams($param));
    }
    /**
     * 获取生效的语言包
     *
     * @param array $param
     *
     * @return array
     */
    public function getEffectLangPackages($param)
    {
        $param = $this->parseParams($param);
        $param['page'] = 0;
        if (isset($param['search'])) {
            $param['search']['effect'] = [1];
        } else {
            $param['search'] = ['effect' => [1]];
        }
        return $this->response(app($this->langPackageRepository), 'getLangPackagesTotal', 'getLangPackages', $param);
    }
    /**
     * 获取所有的语言包
     *
     * @param array $param
     *
     * @return array
     */
    public function getAllLangPackages($param)
    {
        $all = $param['all'] ?? 1;
        $isTransOnline = $param['online'] ?? 0;

        $packages = app($this->langPackageRepository)->getAllLangPackages();
        if (count($packages) == 0) {
            return [];
        }

        $handlePackages = [];
        if ($all == 1) {
            if ($isTransOnline == 1) {
                foreach ($packages as $package) {
                    if (in_array($package->lang_code, ['zh-CN', 'en'])) {
                        $handlePackages[] = $package;
                    }
                }
            } else {
                $handlePackages = $packages;
            }
        } else {
            if ($isTransOnline == 1) {
                foreach ($packages as $package) {
                    if (in_array($package->lang_code, $this->langCodes) && !in_array($package->lang_code, ['zh-CN', 'en'])) {
                        $handlePackages[] = $package;
                    }
                }
            } else {
                foreach ($packages as $package) {
                    if (!in_array($package->lang_code, ['zh-CN', 'en'])) {
                        $handlePackages[] = $package;
                    }
                }
            }
        }

        return $handlePackages;
    }
    /**
     * 翻译所有生效的多语言（单个）
     *
     * @param string $langKeys
     *
     * @return array
     */
    public function transEffectLangs($langKeys, $mulit = false)
    {
        $packages = $this->getStaticEffectLangPackages();

        $keys = $this->getLangKeysArray($langKeys);

        $transLangs = [];
        if(count($packages) > 0) {
            foreach ($packages as $package) {
                if (is_string($keys)) {
                    $transLangs[$package->lang_code] = $keys;
                } else {
                    if($mulit){
                        $transLangs[$package->lang_code] = $this->getStaticLangValue($this->getLangTable($package->lang_code), $keys);
                    } else {
                        $lang = app($this->langRepository)->getLangValue($this->getLangTable($package->lang_code), $keys);
                        $transLangs[$package->lang_code] = $lang ? $lang->lang_value : '';
                    }

                }
            }
        }
        return $transLangs;
    }
    private function getStaticLangValue($langTable, $keys)
    {
        static $alreadyLangs = [];

        list($table, $column, $option, $langKey) = $keys;

        $staticKey = $langTable . '.' . $table;
        if (isset($alreadyLangs[$staticKey])) {
            $langs = $alreadyLangs[$staticKey];
        } else {
            $langObjs = app($this->langRepository)->getLangValueByTable($table, $langTable);

            if ($langObjs->isEmpty()) {
                $alreadyLangs[$staticKey] = $langs = [];
            } else {
                $alreadyLangs[$staticKey] = $langs = $langObjs->mapWithKeys(function($item) {
                    return [$item->column . '.' . $item->option . '.' . $item->lang_key => $item->lang_value];
                });
            }
        }

        return $langs[$column. '.' .$option. '.' .$langKey] ?? '';
    }
    private function getStaticEffectLangPackages()
    {
        static $packages;

        if($packages){
            return $packages;
        }

        return $packages = app($this->langPackageRepository)->getLangPackages(['page' => 0, 'search' => ['effect' => [1]]]);
    }
    /**
     * 将语言键拆为数组
     *
     * @param string $langKeys
     *
     * @return array | string
     */
    private function getLangKeysArray($langKeys)
    {
        if (empty($langKeys)) {
            return '';
        }

        $keys = explode('.', $langKeys);

        $kSize = sizeof($keys);

        if ($kSize < 3 || $kSize > 4) {
            return $langKeys;
        }

        if ($kSize == 3) {
            array_splice($keys, 2, 0, $keys[1]);
        }

        return $keys;
    }
    /**
     * 获取语言包详情
     *
     * @param int $langId
     *
     * @return object
     */
    public function getLangPackageDetail($langId)
    {
        return app($this->langPackageRepository)->getDetail($langId);
    }
    /**
     * 更新语言包
     *
     * @param array $data
     * @param int $langId
     *
     * @return boolean
     */
    public function updateLangPackage(array $data, $langId)
    {
        return app($this->langPackageRepository)->updateData($data, ['lang_id' => $langId]);
    }
    public function effectLangPackage(array $data, $langId)
    {
        $detail = $this->getLangPackageDetail($langId);
        if($detail->is_default) {
            return ['code' => ['0x050017', 'lang']];
        }
        return $this->updateLangPackage($data, $langId);
    }
    public function getDefaultLocale()
    {
        $package = app($this->langPackageRepository)->getDefaultLocale();

        return $package->lang_code;
    }
    public function setDefaultLocale(array $data, $langId)
    {
        if($data['is_default'] == 1) {
            app($this->langPackageRepository)->updateData(['is_default' => 0], []);

            return $this->updateLangPackage($data, $langId);
        }
        return true;
    }

    /**
     * 检查保存的语言包表单字段是否合法
     *
     * @param array $data
     * @param int $langId
     *
     * @return boolean
     */
    private function checkFormData(array $data, $langId = null)
    {
        if (!isset($data['lang_code']) || empty($data['lang_code'])) {
            return ['code' => ['0x050004', 'lang']];
        }

        if (!isset($data['lang_name']) || empty($data['lang_name'])) {
            return ['code' => ['0x050005', 'lang']];
        }

        $checkLangCodeWheres = ['lang_code' => [$data['lang_code']]];
        $checkLangNameWheres = ['lang_name' => [$data['lang_name']]];
        if ($langId) {
            $checkLangCodeWheres['lang_id'] = [$langId, '!='];
            $checkLangNameWheres['lang_id'] = [$langId, '!='];
        }

        if (app($this->langPackageRepository)->packageExists($checkLangCodeWheres)) {
            return ['code' => ['0x050007', 'lang']];
        }

        if (app($this->langPackageRepository)->packageExists($checkLangNameWheres)) {
            return ['code' => ['0x050006', 'lang']];
        }
        if(!preg_match("/^[a-z\d\-]*$/i",$data['lang_code'])){
            return ['code' => ['0x050008', 'lang']];
        }

        return true;
    }
    /**
     * 批量添加动态语言
     *
     * @param array $langArray
     * @param string $local
     *
     * @return boolean
     */
    public function mulitAddDynamicLang(array $langArray, $local = null)
    {
        if (!$langTable = $this->getLangTable($local)) {
            return ['code' => ['0x050001', 'lang']];
        }

        if (empty($langArray)) {
            return ['code' => ['0x050002', 'lang']];
        }

        $langDataGroup = [];
        foreach ($langArray as $key => $data) {
            if (!$langData = $this->checkLangData($data)) {
                // return ['code' => ['0x050002', 'lang']];
                continue;
            }
            $langDataGroup[$this->getDynamicLangKeys($langData, false)] = $langData['lang_value'];
        }
        if(empty($langDataGroup)) {
            return true;
        }
        return $this->saveDatabaseLangs($local, $langDataGroup);
    }
    /**
     * 添加动态语言
     *
     * @param array $langData
     * @param string $local
     *
     * @return boolean
     */
    public function addDynamicLang(array $langData, $local = null)
    {
        if (!$langTable = $this->getLangTable($local)) {
            return ['code' => ['0x050001', 'lang']];
        }

        if (!$langData = $this->checkLangData($langData)) {
            return ['code' => ['0x050002', 'lang']];
        }
        $local = $local ?: Lang::getLocale();
        Redis::del('lang_' . $local);
        Redis::del('mulitlang_' . $local);
        return app($this->langRepository)->addDynamicLang($langData, $langTable);
    }
    /**
     * 检查添加的动态语言数据是否合法
     *
     * @param array $langData
     *
     * @return boolean
     */
    private function checkLangData(array $langData)
    {
        if(!isset($langData['lang_key']) || empty($langData['lang_key']) ||
            !isset($langData['lang_value']) || $langData['lang_value'] === '' || $langData['lang_value'] === null ||
            !isset($langData['table']) || empty($langData['table']) ||
            !isset($langData['column']) || empty($langData['column'])){
            return false;
        }

        if(!isset($langData['option']) || empty($langData['option'])){
            $langData['option'] = $langData['column'];
        }

        return $langData;
    }
    /**
     * 获取存储动态语言的数据表
     *
     * @param string $locale 语言环境
     *
     * @return boolean|string
     */
    public function getLangTable($locale)
    {
        static $alreadyLangTable;

        if(isset($alreadyLangTable[$locale])){
            return $alreadyLangTable[$locale];
        }

        $langTable = $this->getAlreadyLangTable($locale);

        if (!app($this->langRepository)->tableExists($langTable)) {
            if (!app($this->langRepository)->createLangTable($langTable)) {
                return $alreadyLangTable[$locale] = false;
            }
        }

        return $alreadyLangTable[$locale] = $langTable;
    }
    public function deleteDynamicLang($table, $column, $option, $langKeys, $locale = 'zh-CN')
    {
        $langTable = $this->getLangTable($locale);

        return app($this->langRepository)->deleteDynamicLang($langTable, $table, $column, $option, $langKeys);
    }
    /**
     * 获取已经准备好的多语言数据表
     *
     * @param string $locale
     *
     * @return string
     */
    public function getAlreadyLangTable($locale)
    {
        $locale = str_replace('-', '_', $this->getLocal($locale));

        return $this->langTablePrefix . strtolower($locale);
    }

    /**
     * 获取服务端静态多语言目录
     *
     * @param string $locale
     *
     * @return string
     */
    private function getServerLangFolder($locale)
    {
        $parentLangFolder = base_path('resources' . $this->langServerFolderRelativePath);

        $locale = $this->getFolderRelativePath($locale);

        return $this->getLangFolder($parentLangFolder . $locale , $parentLangFolder . strtolower($locale));
    }
    /**
     * 获取客户端多语言目录
     *
     * @param string $locale
     * @param string $type
     *
     * @return string
     */
    private function getClientLangFolder($locale, $type = 'web', $sonFolder = '')
    {
        $locale = $this->getFolderRelativePath($locale);

        $parentLangFolder = base_path() . $this->clientAppPath . $type . $this->langFolderRelativePath . $this->getClientSonFolder($sonFolder);

        return $this->getLangFolder($parentLangFolder . $locale, $parentLangFolder . strtolower($locale));
    }
    /**
     * 获取子文件夹相对路径
     *
     * @param string $sonFolder
     *
     * @return string
     */
    private function getClientSonFolder($sonFolder)
    {
        return $this->getFolderRelativePath($sonFolder ?: $this->clientSonFolder);
    }
    /**
     * 获取文件夹相对路径
     *
     * @param string $folderName
     *
     * @return string
     */
    private function getFolderRelativePath($folderName = '')
    {
        return $folderName == '' ? '' : '/' . $folderName;
    }
    /**
     * 根据多语言目录，检测目录是否存在，不存在在创建
     *
     * @param string $path
     * @param string $lowerPath
     *
     * @return boolean | string
     */
    private function getLangFolder($path, $lowerPath)
    {

        if (file_exists($path)) {
            return $path;
        }

        if (file_exists($lowerPath)){
            return $lowerPath;
        }
        try {
            if (@mkdir($path, 0777, true)) {
                return $path;
            }
            echo json_encode(error_response('0x050019', 'lang'));
            exit;
        } catch(Exception $e) {
            echo json_encode(error_response('0x050019', 'lang'));
            exit;
        }
        return false;
    }
    /**
     * 获取当前的语言环境
     *
     * @param string $locale
     *
     * @return string
     */
    private function getLocal($locale = null)
    {
        return $locale ?: Lang::getLocale();
    }
    /**
     * 保存数组到PHP文件
     *
     * @param array $array
     * @param string $file
     *
     * @return boolean
     */
    private function saveArrayAsPhp(array $array, $file)
    {
        file_put_contents($file, '<?php' . $this->ctrl . ' return ' .  var_export($array, true) . ';', LOCK_EX);

        return true;
    }
    /**
     * 保存数组到json文件
     *
     * @param array $array
     * @param string $file
     *
     * @return boolean
     */
    private function saveArrayAsJson(array $array, $file)
    {
        file_put_contents($file, json_encode($array), LOCK_EX);
        $this->setLangVersion();
        return true;
    }
    /**
     * 根据多语言value查找key
     */
    public function getLangKey($langValue,$table)
    {
        $packages = app($this->langPackageRepository)->getAllLangPackages();
        if (count($packages) > 0) {
            foreach ($packages as $package) {
                $tableName = $this->getLangTable($package->lang_code);
                $langKey = app($this->langRepository)->getLangKey($tableName, $langValue, $table);
                if ($langKey && $langKey->lang_key) {
                    return $langKey->lang_key;
                }
            }
        }
        return '';
    }
    /**
     * 根据多语言value查找key
     */
    public function getLangMoreVlaueForKey($module,$key)
    {
        $packages = app($this->langPackageRepository)->getAllLangPackages();
        $result = [];
        if(count($packages)>0) {
            foreach ($packages as $k => $package) {
                $result[$k] = trans($module.'.'.$key,[],strtolower($package->lang_code));
            }
        }
        return $result;
    }
}
