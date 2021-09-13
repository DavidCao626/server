<?php
namespace App\EofficeApp\LogCenter\Services;

use Schema;
use Illuminate\Database\Schema\Blueprint;
use App\EofficeApp\LogCenter\Builders\OptionsBuilder;
use App\EofficeApp\LogCenter\Traits\LogTrait;
/**
 * Description of LogSchemeService
 *
 * 用户日志建表，删表，设置相关日志配置，获取相关日志配置等
 * 
 * @author lizhijun
 */
class LogSchemeService
{
    use LogTrait;
    private $logModuleConfigRepository;
    private $langService;
    private $userMenuService;
    public function __construct() 
    {
        $this->logModuleConfigRepository = 'App\EofficeApp\LogCenter\Repositories\LogModuleConfigRepository';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
    }
    /**
     * 创建日志表
     * 
     * @param type $moduleKey
     * @param type $moduleId
     * @param type $callback
     * 
     * @return boolean
     */
    public function createLogTable($moduleKey, $moduleId, $callback = null)
    {
        if (!$moduleKey || !$moduleId) {
            return false;
        }
        // 创建模块日志表
        $logTable = $this->makeLogTable($moduleKey);
        if (!Schema::hasTable($logTable)) {
            Schema::create($logTable, function (Blueprint $table) use($callback) {
                $table->increments('log_id')->comment('日志ID');
                $table->string('log_category', 50)->comment('日志来源，如login（登录），user');
                $table->string('log_operate', 50)->comment('日志触发操作，如login，add，delete等。');
                $table->char('creator', 12)->comment('日志创建人');
                $table->char('ip', 16)->nullable()->comment('生成日志的客户端IP');
                $table->string('relation_table', 100)->nullable()->comment('日志关联表');
                $table->char('relation_id',12)->nullable()->comment('日志关联数据ID');
                $table->string('relation_title', 255)->comment('变更数据title');
                $table->string('operate_path', 255)->comment('操作路径');
                $table->tinyInteger('log_level')->default(1)->comment('日志级别，1、info，2、waring，3、error，4、important');
                $table->tinyInteger('has_change')->default(0)->comment('是否有变更记录，1有，0无');
                $table->text('log_content')->nullable()->comment('日志内容');
                $table->tinyInteger('log_content_type')->comment('日志内容类型，1text，2json');
                $table->tinyInteger('platform')->comment('平台');
                $table->datetime('log_time')->comment('日志创建时间');
                $table->index('creator' , 'creator_index');
                $table->index('ip' , 'ip_index');
                $table->index('relation_table' , 'relation_table_index');
                $table->index('relation_id' , 'relation_id_index');
                if ($callback && is_callable($callback)) {
                    $callback($table);
                }
            });
        }

        // 创建模块数据变更表
        $dataChangeTable = $this->makeChangeTable($moduleKey);
        if (!Schema::hasTable($dataChangeTable)) {
            Schema::create($dataChangeTable, function (Blueprint $table){
                $table->integer('log_id')->comment('日志ID');
                $table->char('relation_id',12)->nullable()->comment('数据ID');
                $table->string('field', 50)->comment('变更字段');
                $table->text('from')->nullable()->comment('变更前内容');
                $table->text('to')->nullable()->comment('变更后内容');
                $table->text('original_from')->nullable()->comment('变更前未解析内容');
                $table->text('original_to')->nullable()->comment('变更后未解析内容');
                $table->string('relation_table', 100)->nullable()->comment('关联表');
                $table->index('log_id' , 'log_id_index');
                $table->index('relation_id' , 'relation_id_index');
            });
        }
        // 添加模块日志配置项
        $moduleConfig = [
            'module_key' => $moduleKey,
            'module_id' => $moduleId,
            'options' => json_encode(['category' => [], 'operate' => []])
        ];
 
        if (!app($this->logModuleConfigRepository)->isExists($moduleKey)) {
            return app($this->logModuleConfigRepository)->insertData($moduleConfig);
        }
        
        return Schema::hasTable($logTable);
    }
    public function getAllLogModules($type = '')
    {
        // 需要解析多语言
        $ids['menus']=[];
        $moduleConfig = app($this->logModuleConfigRepository)->getAllModuleConfig();
        foreach ($moduleConfig as $k => $v){
            $ids['menus'][] = $v['module_id'];
        }
        $menuNames = app($this->userMenuService)->getMenuByIdArray($ids);
        $moduleConfig = json_decode(json_encode($moduleConfig),true);
        $category = [];
        foreach ($moduleConfig as $key => $val){
            foreach ($menuNames as $k => $v){
                //这个排序的成本有点大，我觉得在数据库加个sort字段更好
                if($val['module_id'] == $v['menu_id']){
                    $arr = [ 'module_key'=> $val['module_key'] ,
                             'module_name' =>$v['menu_name'] ,
                             'module_id' => $val['module_id'],
                             'options' => $val['options']
                    ];
                    if($val['module_key'] == 'system'){
                        array_unshift($category , $arr);
                    }else{
                        array_push($category , $arr);
                    }

                    break;
                }
            }

        }
        $elasticService = app('App\EofficeApp\LogCenter\Services\ElasticService');
        if($type == 'mine' && $this->isElasticSearchRun() && $elasticService->exists(config('elastic.logCenter.index'))){
            $all = [
                'module_id' => 0,
                'module_key' => 'all',
                'module_name' => trans('logcenter.all') //trans('logcenter.all') 暂时写死，后期补多语言
            ];
            array_unshift($category , $all);
            return $category;
        }

        return $category;
    }

    public function getModuleConfigByModuleKey($moduleKey)
    {
        if (!$moduleKey) {
            return ['code' => ['0x0103003', 'systemlog']];
        }
        $config = app($this->logModuleConfigRepository)->findByModuleKey($moduleKey);
        if (!$config) {
            return ['code' => ['0x0103004', 'systemlog']];
        }
        $options = json_decode($config->options, true);
        
        $categorys = [];

        foreach ($options['operate'] as $key => $value) {
            $items = [];
            foreach ($value as $k => $v) {
                $items[] = [
                    'operate_key' => $k,
                    'parent_key' => $key,
                    'operate_name' => mulit_trans_dynamic('eo_log_module_config.options.operate.' . $v)
                ];
            }
            $categorys[] = [
                'category_key' => $key,
                'category_name' => mulit_trans_dynamic('eo_log_module_config.options.category.' . $options['category'][$key] ?? ''),
                'items' => $items
            ];
        }
        return $categorys;
    }
    
    public function getOneCategoryOperations($moduleKey, $categoryKey)
    {
        $categorys = $this->getModuleConfigByModuleKey($moduleKey);

        if (isset($categorys['code'])) {
            return $categorys;
        }
        $operations = [];
        foreach ($categorys as $category) {
            if (isset($category['category_key']) && $category['category_key'] === $categoryKey) {
                $operations = $category['items'] ?? [];
                break;
            }
        }
        return $operations;
    }

    // 根据模块获取所有操作类型，数据格式适用于二级下拉数据
    public function getModuleCategoryOperations($moduleKey) {
        if (!$moduleKey) {
            return ['code' => ['0x0103003', 'systemlog']];
        }
        $config = app($this->logModuleConfigRepository)->findByModuleKey($moduleKey);
        if (!$config) {
            return ['code' => ['0x0103004', 'systemlog']];
        }
        $options = json_decode($config->options, true);

        $categorys = [];

        foreach ($options['operate'] as $key => $value) {
            $categoryName = mulit_trans_dynamic('eo_log_module_config.options.category.' . $options['category'][$key] ?? '');
            foreach ($value as $k => $v) {
                $categorys[] = [
                    'operate_key' => $k,
                    'operate_key_unique' => "{$key}_{$k}",
                    'parent_key' => $key,
                    'operate_name' => mulit_trans_dynamic('eo_log_module_config.options.operate.' . $v),
                    'category_name' => $categoryName,
                ];
            }
        }
        return $categorys;
    }
    /**
     * 删除日志表
     * 
     * @param type $moduleKey
     * 
     * @return type
     */
    public function dropLogTable($moduleKey)
    {
        return app($this->logModuleConfigRepository)->deleteByModuleKey($moduleKey);
    }
    /**
     * 添加日志操作类型
     * 
     * @param type $moduleKey
     * @param type $categoryKey
     * @param type $callback
     * 
     * @return type
     */
    public function addLogOperate($moduleKey, $categoryKey, $callback)
    {
        return $this->updateConfigOptions($moduleKey, function($options) use($categoryKey) {
                    return $options['operate'][$categoryKey] ?? [];
                }, function($operateKey) use($moduleKey, $categoryKey) {
                    return $moduleKey . '_' . $categoryKey . '_' . $operateKey;
                }, function($options, $operate) use($categoryKey) {
                    $options['operate'][$categoryKey] = $operate;
                    return $options;
                }, $callback);
    }
    /**
     * 组装日志配置表相关多语言数据
     * 
     * @param type $langKey
     * @param type $langValue
     * @param type $option
     * 
     * @return type
     */
    private function combineConfigLangData($langKey, $langValue, $option = 'operate')
    {
        return [
            'table' => 'eo_log_module_config', 
            'column' => 'options', 
            'option' => $option, 
            'lang_key' => $langKey, 
            'lang_value' => $langValue
        ];
    }
    /**
     * 添加日志类别
     * 
     * @param type $moduleKey
     * @param type $callback
     * 
     * @return type
     */
    public function addLogCategory($moduleKey, $callback)
    {
        return $this->updateConfigOptions($moduleKey, function($options) {
                    return $options['category'] ?? [];
                }, function($categoryKey) use($moduleKey) {
                    return $moduleKey . '_' . $categoryKey;
                }, function($options, $categorys) {
                    $options['category'] = $categorys;
                    return $options;
                }, $callback, 'category');
    }
    /**
     * 更新配置选项公共函数
     * 
     * @param type $addOptionItems
     * @param type $getOptionItems
     * @param type $combineLangKey
     * 
     * @return boolean
     */
    private function updateConfigOptions($moduleKey, $parseOptions, $combineLangKey, $combineOptions, $callback, $optionType = 'operate')
    {
        $builder = new OptionsBuilder();
        if ($callback && is_callable($callback)) {
            $callback($builder);
        }
        $addOptions = $builder->getOptions();
        if (empty($addOptions)) {
            return false;
        }

        $config = app($this->logModuleConfigRepository)->findByModuleKey($moduleKey);
        if (!$config) {
            return false;
        }
        $options = json_decode($config->options, true);

        $optionItems = $parseOptions($options);

        $cnLangData = $enLangData = [];
        foreach ($addOptions as $key => $name) {
            $langKey = $combineLangKey($key);
            $optionItems[$key] = $langKey;
            $cnLangData[] = $this->combineConfigLangData($langKey, $name[0], $optionType);
            $enLangData[] = $this->combineConfigLangData($langKey, $name[1], $optionType);
        }
        $langService = app($this->langService);
        $langService->mulitAddDynamicLang($cnLangData, 'zh-CN');
        $langService->mulitAddDynamicLang($enLangData, 'en');
        
        $options = $combineOptions($options, $optionItems);
        return app($this->logModuleConfigRepository)->updateData(['options' => json_encode($options)], ['module_key' => [$moduleKey]]);
    }
}
