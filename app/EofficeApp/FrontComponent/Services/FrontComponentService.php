<?php

namespace App\EofficeApp\FrontComponent\Services;

use App\EofficeApp\Base\BaseService;
use DB;
/**
 * 组建查询服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class FrontComponentService extends BaseService {

    /** @var object 组建查询资源库变量 */
    private $componentSearchRepository;

    public function __construct() {
        $this->componentSearchRepository = 'App\EofficeApp\FrontComponent\Repositories\ComponentSearchRepository';
        $this->customizeSelectorRepository = 'App\EofficeApp\FrontComponent\Repositories\CustomizeSelectorRepository';
        $this->langService      = 'App\EofficeApp\Lang\Services\LangService';
    }

    /**
     * 获取消息列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getComponentSearchlist($data) {
        if (isset($data['user_id'])) {
            $data['user_id'] = own("user_id");
        }
        // 用户 page_state
        $list = $this->response(app($this->componentSearchRepository), 'getComponentSearchTotal', 'getComponentSearchList', $this->parseParams($data));
        return $list;

    }

    /**
     * 增加组建查询
     *
     * @param array   $data
     *
     * @return  int
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addComponentSearch($data) {
        $data['content'] = json_encode($data['content']);
        $componentData = array_intersect_key($data, array_flip(app($this->componentSearchRepository)->getTableColumns()));
        $result = app($this->componentSearchRepository)->insertData($componentData);
        return $result->id;
    }

    /**
     * 删除组建查询
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function deleteComponentSearch($data,$id) {
        //用户 pagestatus
        $destroyIds = explode(",", $id);
        $where = [
            'id' => [$destroyIds, 'in']
        ];
        return app($this->componentSearchRepository)->deleteByWhere($where);
    }


    /**
     * 编辑
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editComponentSearch($data,$id) {
        $componentData = [
            "name" => $data['name'],
            "content" => $data['content']
        ];
        return app($this->componentSearchRepository)->updateData($componentData, ['id' => $id]);
    }
    /**
     * 获取grid设置
     */
    public function getWebGridSet($key)
    {
        if ($key) {
            $info = DB::table('web_grid_style_setting')->where('key',$key)->first();
            if ($info && count((array) $info)) {
                return $info;
            }
        }
        return '';
    }
    /**
     * 保存grid设置
     */
    public function saveWebGridSet($param)
    {
        if (isset($param['key']) && !empty($param['key'])) {
            $oldInfo = DB::table('web_grid_style_setting')->where('key',$param['key'])->get();
            if ($oldInfo && count($oldInfo)) {
                DB::table('web_grid_style_setting')->where('key',$param['key'])->update(['content'=> $param['content'] ?? '', 'version' => $param['version'] ?? '']);
                return true;
            }else {
                DB::table('web_grid_style_setting')->insert(['key'=> $param['key'], 'content'=> $param['content'] ?? '', 'version' => $param['version'] ?? '']);
                return true;
            }
        }
        return ['code' => ['0x000003','common']];
    }
    /**
     * 添加系统数据
     *
     */
    public function addCustomizeSelector($param)
    {
        if (!isset($param['name']) || empty($param['name'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($param['config'])) {
            $param['config'] = '';
        }
        if (!isset($param['setting'])) {
            $param['setting'] = '';
        }
        if (!isset($param['is_system'])) {
            $param['is_system'] = 0;
        }
        $insertData = [
            'name'      => $param['name'],
            'config'    => $param['config'],
            'setting'   => $param['setting'],
            'is_system' => $param['is_system'],
        ];
        if($result = app($this->customizeSelectorRepository)->insertData($insertData)) {
            //保存多语言信息
            if (isset($param['setting_lang']) && !empty($param['setting_lang'])) {
                $langData = [];
                foreach ($param['setting_lang'] as $control_id_temp => $lang) {
                    foreach ($lang as $lang_key => $lang_value) {
                        if ($lang_value !== '') {
                            $langData[$lang_key][] = [
                                'table'      => 'customize_selector',
                                'column'     => 'setting',
                                'lang_key'   => $result->id . '_' . $control_id_temp,
                                'lang_value' => $lang_value,
                            ];
                        }
                    }
                }
                if (!empty($langData)) {
                    foreach ($langData as $key => $value) {
                        app($this->langService)->mulitAddDynamicLang($value, $key);
                    }
                }
            }
            //保存多语言信息
            if (isset($param['name_lang']) && !empty($param['name_lang'])) {
                foreach ($param['name_lang'] as $_lang_key => $_lang_value) {
                    $_langData = [
                        'table'      => 'customize_selector',
                        'column'     => 'name',
                        'lang_key'   => $result->id . '_name',
                        'lang_value' => $_lang_value,
                    ];
                    app($this->langService)->addDynamicLang($_langData, $_lang_key);
                }
            }
            $identifier = 'customize_' . $result->id;
            if(app($this->customizeSelectorRepository)->updateData(['identifier' => $identifier],['id'=>[$result->id]])) {
                return ['id' => $result->id, 'identifier' => $identifier];
            }
        }
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 编辑系统数据
     *
     */
    public function editCustomizeSelector($id, $param)
    {
        if (empty($param)) {
            return true;
        }
        $editData = [];
        if (!isset($id) || empty($id)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (isset($param['name']) && !empty($param['name'])) {
            $editData['name'] = $param['name'];
        }
        if (isset($param['config']) && !empty($param['config'])) {
            $editData['config'] = $param['config'];
        }
        if (isset($param['setting']) && !empty($param['setting'])) {
            $editData['setting'] = $param['setting'];
        }
        if (isset($param['is_system']) && !empty($param['is_system'])) {
            $editData['is_system'] = $param['is_system'];
        }

        if (!empty($editData) && app($this->customizeSelectorRepository)->updateData($editData, ['id' => [$id]])) {
            //保存多语言信息
            if (isset($param['setting_lang']) && !empty($param['setting_lang'])) {
                $langData = [];
                foreach ($param['setting_lang'] as $control_id_temp => $lang) {
                    foreach ($lang as $lang_key => $lang_value) {
                        $langData[$lang_key][] = [
                            'table'      => 'customize_selector',
                            'column'     => 'setting',
                            'lang_key'   => $id . '_' . $control_id_temp,
                            'lang_value' => $lang_value,
                        ];
                    }
                }
                if (!empty($langData)) {
                    foreach ($langData as $key => $value) {
                        app($this->langService)->mulitAddDynamicLang($value, $key);
                    }
                }
            }
            //保存多语言信息
            if (isset($param['name_lang']) && !empty($param['name_lang'])) {
                foreach ($param['name_lang'] as $_lang_key => $_lang_value) {
                    if ($_lang_value !== '') {
                        $_langData = [
                            'table'      => 'customize_selector',
                            'column'     => 'name',
                            'lang_key'   => $id . '_name',
                            'lang_value' => $_lang_value,
                        ];
                        app($this->langService)->addDynamicLang($_langData, $_lang_key);
                    }
                }
            }
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 删除系统数据
     *
     */
    public function deleteCustomizeSelector($id)
    {
        if (!isset($id) || empty($id)) {
            return ['code' => ['0x000003', 'common']];
        }
        return app($this->customizeSelectorRepository)->deleteById($id);
    }
    /**
     * 获取系统数据
     *
     */
    public function getListCustomizeSelector($params)
    {
        $list = app($this->customizeSelectorRepository)->getList($params);
        foreach ($list as $key => $result) {
            $name = mulit_trans_dynamic('customize_selector.name.' . $result->id . '_name');
            if (!empty($name)) {
                $result->name = $name;
            }
            $pattern  = '/\$(.*?)\$/i';
            $matches  = [];
            $langInfo = [];
            preg_match_all($pattern, $result->setting, $matches);
            if (!empty($matches)) {
                if (isset($matches[1]) && isset($matches[0])) {
                    foreach ($matches[1] as $key => $value) {
                        $langInfo[$matches[0][$key]] = mulit_trans_dynamic('customize_selector.setting.' . $result->id . '_' . $matches[1][$key]);
                    }
                }
            }
            if (!empty($langInfo)) {
                foreach ($langInfo as $_key => $_value) {
                    if (isset($params['mode'])) {
                        if ($params['mode'] == 'config') {
                            $result->config = str_replace($_key, $_value, $result->config);
                        }
                    } else {
                        $result->setting = str_replace($_key, $_value, $result->setting);
                        $result->config  = str_replace($_key, $_value, $result->config);
                    }
                }
            }

            if (isset($params['mode']) && $params['mode'] == 'config') {
                unset($result->setting);
            }
        }
        return $list;
    }
    /**
     * 获取系统数据
     *
     */
    public function getOneCustomizeSelector($identifier, $params)
    {
        if (!$identifier) {
            return ['code' => ['0x000003', 'common']];
        }
        $result = app($this->customizeSelectorRepository)->getOne($identifier);
        if(!$result) {
            return [];
        }
        $name   = mulit_trans_dynamic('customize_selector.name.' . $result->id . '_name');
        if (!empty($name)) {
            $result->name = $name;
        }
        $pattern      = '/\$(.*?)\$/i';
        $matches      = [];
        $langInfo     = [];
        $setting_lang = [];
        $name_lang    = app($this->langService)->transEffectLangs('customize_selector.name.' . $result->id . '_name', true);
        preg_match_all($pattern, $result->setting, $matches);
        if (!empty($matches)) {
            if (isset($matches[1]) && isset($matches[0])) {
                foreach ($matches[1] as $key => $value) {
                    $langInfo[$matches[0][$key]]     = mulit_trans_dynamic('customize_selector.setting.' . $result->id . '_' . $matches[1][$key]);
                    $setting_lang[$matches[1][$key]] = app($this->langService)->transEffectLangs('customize_selector.setting.' . $result->id . '_' . $matches[1][$key], true);
                }
            }
        }
        if (!empty($langInfo)) {
            foreach ($langInfo as $_key => $_value) {
                if (isset($params['mode'])) {
                    if ($params['mode'] == 'setting') {
                        $result->setting = str_replace($_key, $_value, $result->setting);
                        unset($result->config);
                    } else if ($params['mode'] == 'config') {
                        $result->config = str_replace($_key, $_value, $result->config);
                        unset($result->setting);
                    }
                } else {
                    $result->setting = str_replace($_key, $_value, $result->setting);
                    $result->config  = str_replace($_key, $_value, $result->config);
                }
            }
        }

        if (isset($params['mode'])) {
            if ($params['mode'] == 'setting') {
                unset($result->config);
                $result->setting_lang = $setting_lang;
                $result->name_lang    = $name_lang;
            } else if ($params['mode'] == 'config') {
                unset($result->setting);
            }
        } else {
            $result->setting_lang = $setting_lang;
            $result->name_lang    = $name_lang;
        }
        return $result;
    }
}
