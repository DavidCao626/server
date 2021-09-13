<?php
namespace App\EofficeApp\Portal\Services;

use App\EofficeApp\Base\BaseService;
use Lang;
use Cache;
use Illuminate\Support\Facades\Redis;
/**
 * 门户服务类
 *
 * @author 李志军
 *
 * @since 2015-10-27
 */
class PortalService extends BaseService {

    /** @var object 门户资源库对象  */
    private $portalRepository;

    /** @var object 门户布局资源库对象  */
    private $portalLayoutRepository;
    private $userRepository;
    private $rssRepository;
    private $userInfoRepository;
    private $personalSetService;
    private $userSystemInfoRepository;
    private $userMenuRepository;
    private $userMenuService;
    private $langService;
    private $attachmentService;
    private $avatarPath;
    /**
     * 注册门户相关资源库类对象
     *
     * @param \App\EofficeApp\Portal\Repositories\PortalRepository $portalRepository
     * @param \App\EofficeApp\Portal\Repositories\PortalLayoutRepository $portalLayoutRepository
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit','-1');
        $this->portalRepository = 'App\EofficeApp\Portal\Repositories\PortalRepository';
        $this->portalLayoutRepository = 'App\EofficeApp\Portal\Repositories\PortalLayoutRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->rssRepository = 'App\EofficeApp\Portal\Repositories\RssRepository';
        $this->userInfoRepository = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userMenuRepository = 'App\EofficeApp\Menu\Repositories\UserMenuRepository';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->personalSetService = 'App\EofficeApp\PersonalSet\Services\PersonalSetService';
        $this->weixinService = 'App\EofficeApp\Weixin\Services\WeixinService';
        $this->workWechatService = 'App\EofficeApp\WorkWechat\Services\WorkWechatService';
        $this->empowerService = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->promptService = 'App\EofficeApp\System\Prompt\Services\PromptService';
        $this->avatarPath = access_path('images/avatar/');
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
    }

    /**
     * 获取门户列表
     *
     * @param array $fields
     *
     * @return array 门户列表
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function listPortal($fields, $userId, $deptId, $roleId) {
        return $this->getPortalLists(json_decode($fields, true), $userId, $deptId, $roleId);
    }

    public function listMenuPortal($userId, $deptId, $roleId) {
        $fields = ['portal_id', 'portal_name', 'portal_icon', 'portal_default', 'edit_priv_scope'];

        return $this->getPortalLists($fields, $userId, $deptId, $roleId, 'menu');
    }

    public function listManagePortal($fields, $own) {
        $editIds = $this->getEditPrivPortalIds($own['user_id'], $own['dept_id'], $own['role_id']);
        $portals =  app($this->portalRepository)->listPortal(json_decode($fields, true), 'all');
        foreach($portals as $key => $portal){
            $portals[$key]->edit_priv = (in_array($portal->portal_id, $editIds) || $portal->edit_priv_scope == 1) ? 1 : 0;
            $portals[$key]->portal_name = mulit_trans_dynamic('portal.portal_name.portal_name_' . $portal->portal_id);
            $portals[$key]->portal_name_lang = app($this->langService)->transEffectLangs('portal.portal_name.portal_name_' . $portal->portal_id, true);
        }
        return $portals;
    }

    private function getPortalLists($fields, $userId, $deptId, $roleId, $type = 'show') {
        $lists = [];
        $editIds = $this->getEditPrivPortalIds($userId, $deptId, $roleId);

        $portals = app($this->portalRepository)->listPortal($fields, $type, $userId, $deptId, $roleId);

        foreach ($portals as $portal) {
            $portal->portal_name = mulit_trans_dynamic('portal.portal_name.portal_name_' . $portal->portal_id);

            if($type === 'show') {
                $portal->portal_name_lang = app($this->langService)->transEffectLangs('portal.portal_name.portal_name_' . $portal->portal_id,true);
            }

            $portal->edit_priv = (in_array($portal->portal_id, $editIds) || $portal->edit_priv_scope == 1) ? 1 : 0;

            if ($type == 'menu') {
                $portal->portal_layout_content = $this->getLayoutContent($portal->portal_id, $userId);
            }

            $lists[] = $portal;
        }

        return $lists;
    }
    public function getPortalMenus($userId, $deptId, $roleId)
    {
        $editIds = $this->getEditPrivPortalIds($userId, $deptId, $roleId);
        $portals = app($this->portalRepository)->listPortal(['portal_id', 'portal_name', 'portal_icon', 'portal_default', 'edit_priv_scope'], 'menu', $userId, $deptId, $roleId);
        $lists = [];
        foreach ($portals as $portal) {
            $portal->portal_name = mulit_trans_dynamic('portal.portal_name.portal_name_' . $portal->portal_id);
            list($py, $zm) = convert_pinyin($portal->portal_name);
            $portal->portal_name_zm = $zm;
            $portal->edit_priv = (in_array($portal->portal_id, $editIds) || $portal->edit_priv_scope == 1) ? 1 : 0;
            array_push($lists, $portal);
        }
        return $lists;
    }
    /**
     * 新建门户
     *
     * @param array $data
     *
     * @return array 门户id
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function addPortal($data, $userId) {
        $portalData = [
            'portal_name' => $data['portal_name'],
            'portal_icon' => $this->defaultValue('portal_icon', $data, ''),
            'portal_sort' => $this->getSort(),
            'creator' => $userId,
            'view_priv_scope' => $this->defaultValue('view_priv_scope', $data, 0),
            'edit_priv_scope' => $this->defaultValue('edit_priv_scope', $data, 0),
            'portal_default' => 0
        ];

        if ($result = app($this->portalRepository)->insertData($portalData)) {
            //处理门户名称多语言
            $portalNameKey = 'portal_name_' . $result->portal_id;
            app($this->portalRepository)->updateData(['portal_name' => $portalNameKey], ['portal_id' => [$result->portal_id]]);
            if(isset($data['portal_name_lang']) && !empty($data['portal_name_lang'])){
                foreach ($data['portal_name_lang'] as $locale => $langValue) {
                    app($this->langService)->addDynamicLang(['table' => 'portal', 'column' => 'portal_name', 'lang_key' => $portalNameKey, 'lang_value' => $langValue], $locale);
                }
            } else {
                app($this->langService)->addDynamicLang(['table' => 'portal', 'column' => 'portal_name', 'lang_key' => $portalNameKey, 'lang_value' => $data['portal_name']]);
            }
            if ($portalData['view_priv_scope'] == 0) {
                if (!$this->setPortalViewPurview($result->portal_id, $data)) {
                    return ['code' => ['0x037015', 'portal']];
                }
            }

            if ($portalData['edit_priv_scope'] == 0) {
                if (!$this->setPortalEditPurview($result->portal_id, $data)) {
                    return ['code' => ['0x037015', 'portal']];
                }
            }

            if (!$this->createPortalLayout($result->portal_id)) {
                return ['code' => ['0x037006', 'portal']];
            }

            return ['portal_id' => $result->portal_id];
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除门户
     *
     * @param int $portalId
     *
     * @return int 删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function deletePortal($portalId, $userId, $deptId, $roleId) {
        if ($portalId == 1) {
            return ['code' => ['0x037007', 'portal']];
        }

        if (app($this->portalRepository)->deleteById($portalId)) {
            $conditions = ['portal_id' => [$portalId], 'user_id' => [$userId]];

            app($this->portalLayoutRepository)->deleteByWhere($conditions);

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 删除门户布局
     * @param type $userId
     * @return boolean
     */
    public function dropPortalLayout($userId)
    {
        if ($userId == 'admin') {
            return true;
        }
        $conditions = ['portal_id' => [1], 'user_id' => [$userId]];

        app($this->portalLayoutRepository)->deleteByWhere($conditions);

        return true;
    }
    public function getPortalInfo($portalId, $own, $type = 'all') {
        $portalInfo = app($this->portalRepository)->getPortalInfo($portalId);
        if(!$portalInfo) {
            return $portalInfo;
        }

        if($type == 'simple') {
            return $portalInfo;
        }
        $portalInfo['portal_name'] = trans_dynamic('portal.portal_name.portal_name_' . $portalId);
        $portalInfo['portal_name_lang'] = app($this->langService)->transEffectLangs('portal.portal_name.portal_name_' . $portalId);
        $purviewMaps = [
            'view_user_priv' => ['user_view_purview', 'user_id'],
            'edit_user_priv' => ['user_edit_purview', 'user_id'],
            'view_role_priv' => ['role_view_purview', 'role_id'],
            'edit_role_priv' => ['role_edit_purview', 'role_id'],
            'view_dept_priv' => ['dept_view_purview', 'dept_id'],
            'edit_dept_priv' => ['dept_edit_purview', 'dept_id']
        ];

        foreach ($purviewMaps as $key => $value) {
            $portalInfo[$key] = isset($portalInfo[$value[0]]) ? $this->handlePrivInfo($portalInfo[$value[0]], $value[1]) : [];

            unset($portalInfo[$value[0]]);
        }

        unset($portalInfo['deleted_at'], $portalInfo['created_at'], $portalInfo['updated_at']);

        return $portalInfo;
    }

    private function handlePrivInfo($privInfo, $field) {
        if (!$privInfo || count($privInfo) == 0) {
            return [];
        }

        $priv = [];

        foreach ($privInfo as $value) {
            $priv[] = $value[$field];
        }

        return $priv;
    }
    /**
     * 编辑门户
     *
     * @param array $data
     * @param int $portalId
     *
     * @return int 编辑结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function editPortal($data, $portalId) {
        $portalData = [
            'view_priv_scope' => $this->defaultValue('view_priv_scope', $data, 0),
            'edit_priv_scope' => $this->defaultValue('edit_priv_scope', $data, 0),
            'portal_icon' => $this->defaultValue('portal_icon', $data, '')
        ];

        if (app($this->portalRepository)->updateData($portalData, ['portal_id' => $portalId])) {
            if ($portalData['view_priv_scope'] == 0) {
                if (!$this->setPortalViewPurview($portalId, $data)) {
                    return ['code' => ['0x037015', 'portal']];
                }
            }

            if ($portalData['edit_priv_scope'] == 0) {
                if (!$this->setPortalEditPurview($portalId, $data)) {
                    return ['code' => ['0x037015', 'portal']];
                }
            }
            $this->editPortalName($data, $portalId);
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function editPortalName($data, $portalId) {
        $portalNameKey = 'portal_name_' . $portalId;

        if(isset($data['portal_name_lang']) && !empty($data['portal_name_lang'])){
            foreach ($data['portal_name_lang'] as $locale => $langValue) {
                app($this->langService)->addDynamicLang(['table' => 'portal', 'column' => 'portal_name', 'lang_key' => $portalNameKey, 'lang_value' => $langValue], $locale);
            }
        } else {
            app($this->langService)->addDynamicLang(['table' => 'portal', 'column' => 'portal_name', 'lang_key' => $portalNameKey, 'lang_value' => $data['portal_name']]);
        }
        return true;
    }
    public function editPortalElementMargin($data, $portalId, $userId)
    {
        $portalData = [
            'element_margin' => $this->defaultValue('element_margin', $data, 5)
        ];
        if ($portalId == 1) {
            $cacheKey = $this->getElementMarginCacheKey($portalId, $userId);
            if (app($this->portalLayoutRepository)->updateData($portalData, ['portal_id' => $portalId, 'user_id' => $userId])) {
                Cache::forget($cacheKey);
                return true;
            }
        } else {
            $cacheKey = $this->getElementMarginCacheKey($portalId, 'admin');
            if (app($this->portalLayoutRepository)->updateData($portalData, ['portal_id' => $portalId])) {
                Cache::forget($cacheKey);
                return true;
            }
        }
        Cache::forget($cacheKey);
        return ['code' => ['0x000003', 'common']];
    }
    public function getPortalElementMargin($portalId, $userId)
    {
        if ($portalId != 1) {
            $userId = 'admin';
        }
        $cacheKey = $this->getElementMarginCacheKey($portalId, $userId);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $portalLayout = app($this->portalLayoutRepository)->getLayouElementMargin($portalId, $userId);
        if ($portalLayout) {
            Cache::forever($cacheKey, $portalLayout->element_margin);
            return $portalLayout->element_margin;
        }
        return 5;
    }
    private function getElementMarginCacheKey($portalId, $userId)
    {
        return 'portal_element_margin:' . $portalId . ':' . $userId;
    }
    public function editPortalIcon($data, $portalId) {
        $portalData = [
            'portal_icon' => $this->defaultValue('portal_icon', $data, '')
        ];

        if (app($this->portalRepository)->updateData($portalData, ['portal_id' => $portalId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 设置门户布局
     *
     * @param array $data
     *
     * @return int 设置布局结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function setPortalLayout($data, $loginUserId)
    {
        $portalId = $data['portal_id'];

        $userId = $portalId == 1 ? $loginUserId : false;

        $locale = Lang::getLocale();
        $langs = [];
        $content = $this->parseLayoutContent($data['portal_layout_content'], $locale, function($element, $rowKey, $columnKey, $eleKey) use(&$langs, $locale) {
            // 生产唯一的元素ID，并绑定多语言到对应的ID。
            if(!isset($element['element_id']) || !$element['element_id']) {
                $element['element_id'] = md5($rowKey . '_' . $columnKey . '_' . $eleKey . '_' . time());
            }
            // 拼装元素标题多语言数据
            $titleLang = $element['attribute']['title_lang'] ?? [];
            if(isset($element['attribute']['title'])) {
                $titleLang[$locale] = $element['attribute']['title'];
            }
            foreach ($titleLang as $_locale => $langValue) {
                $langData = ['table' => 'portal', 'column' => 'element_name', 'lang_key' => $element['element_id'], 'lang_value' => $langValue];
                $langs[$_locale][] = $langData;
            }
            unset($element['attribute']['title']);
            return $element;
        });
        // 保存门户布局
        if(app($this->portalLayoutRepository)->layoutExists($portalId, $userId)){
            $result = app($this->portalLayoutRepository)->updateLayout($content, $portalId, $userId);
        } else {
            $result = $this->insertPortallayout($portalId, $content, $userId);
        }
        // clear缓存
        $this->clearAllLayoutCache($userId, $portalId);
        //保存多语言数据
        foreach ($langs as $_locale => $langDatas) {
            app($this->langService)->mulitAddDynamicLang($langDatas, $_locale);
        }
        return $result;
    }
    /**
     * 清除门户布局缓存
     * @param type $userId
     * @param type $portalId
     */
    private function clearAllLayoutCache($userId, $portalId)
    {
        $allLocales = app($this->langService)->getEffectLangPackages([])['list'];

        return $this->clearLayoutCacheByLocales($allLocales, $userId, $portalId);
    }
    private function clearLayoutCacheByLocales($locales, $userId, $portalId)
    {
        $locales->map(function($lang) use($userId, $portalId){
            $cacheKey = $this->_makeLayoutCacheKey($userId, $portalId, $lang->lang_code);
            return Cache::forget($cacheKey);
        });
    }
    /**
     * 获取门户布局缓存的key
     *
     * @param type $userId
     * @param type $portalId
     * @param type $locale
     * @param type $prefix
     * @param type $glue
     * @return type
     */
    private function _makeLayoutCacheKey($userId, $portalId, $locale ='zh-CN', $prefix = 'portal_layout', $glue = '_')
    {
        if ($portalId == 1) {
            return $prefix . $glue . $locale . $glue . $userId . $glue . $portalId;
        }

        return $prefix . $glue . $locale . $glue . $portalId;
    }
    /**
     * 获取门户布局信息
     *
     * @param int $portalId
     *
     * @return string 门户布局信息
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function getLayoutContent($portalId, $userId)
    {
        $locale = Lang::getLocale();
        if($portalId == 1) {
            $cacheKey = $this->_makeLayoutCacheKey($userId, $portalId, $locale);

            return $this->_getLayoutContent($cacheKey, $portalId, $userId, $locale , function($portalId, $userId) use($locale) {
                $defaultPortalLayout = app($this->portalLayoutRepository)->getLayoutContent(0, 'admin');

                $layoutContent = $defaultPortalLayout ? $defaultPortalLayout->portal_layout_content : json_encode([]);

                $_parseLayoutContent = $this->parseLayoutContent($layoutContent, $locale);
                $langs = [];
                $parseLayoutContent = $this->parseLayoutContent($_parseLayoutContent, $locale, function($element, $rowKey, $columnKey, $eleKey) use(&$langs, $locale) {
                    // 生产唯一的元素ID，并绑定多语言到对应的ID。
                    $element['element_id'] = md5($rowKey . '_' . $columnKey . '_' . $eleKey . '_' . time());
                    // 拼装元素标题多语言数据
                    $titleLang = $element['attribute']['title_lang'] ?? [];
                    $titleLang[$locale] = $element['attribute']['title'];
                    foreach ($titleLang as $_locale => $langValue) {
                        $langData = ['table' => 'portal', 'column' => 'element_name', 'lang_key' => $element['element_id'], 'lang_value' => $langValue];
                        $langs[$_locale][] = $langData;
                    }
                    unset($element['attribute']['title']);
                    return $element;
                });
                $this->insertPortallayout($portalId, $parseLayoutContent, $userId);
                //保存多语言数据
                foreach ($langs as $_locale => $langDatas) {
                    app($this->langService)->mulitAddDynamicLang($langDatas, $_locale);
                }
                return $_parseLayoutContent;
            });
        } else {
            $cacheKey = $this->_makeLayoutCacheKey($userId, $portalId, $locale);

            return $this->_getLayoutContent($cacheKey, $portalId, 'admin', $locale);
        }
    }
    private function _getLayoutContent($cacheKey, $portalId, $userId, $locale='zh-CN', $callback = false)
    {
        if(Cache::has($cacheKey)){
            return Cache::get($cacheKey);
        }

        $portalLayout = app($this->portalLayoutRepository)->getLayoutContent($portalId, $userId);

        if($portalLayout){
            $parseLayoutContent = $this->parseLayoutContent($portalLayout->portal_layout_content, $locale);
        } else {
            $parseLayoutContent = is_callable($callback) ? $callback($portalId, $userId) : json_encode([]);
        }

        Cache::forever($cacheKey, $parseLayoutContent);

        return $parseLayoutContent;
    }
    /**
     * 解析门户布局
     * @param type $portalLayoutContent
     * @param type $locale
     * @param type $callback
     * @return type
     */
    private function parseLayoutContent($portalLayoutContent, $locale, $callback = false)
    {
        $portalLayoutContent = $portalLayoutContent ? json_decode($portalLayoutContent, true) : [];
        if (!empty($portalLayoutContent)) {
            foreach ($portalLayoutContent as $rowKey => $row) {
                if(!empty($row) && is_array($row)) {
                    foreach ($row as $columnKey => $column) {
                        $elements = $column['elements'];
                        if(!empty($elements)) {
                            foreach ($elements as $eleKey => $element) {
                                if($callback && is_callable($callback)){
                                    $portalLayoutContent[$rowKey][$columnKey]['elements'][$eleKey] = $callback($element, $rowKey, $columnKey, $eleKey);
                                } else {
                                    $element['attribute']['title'] = mulit_trans_dynamic('portal.element_name.' . $element['element_id'], [], $locale);
                                    $element['attribute']['title_lang'] = app($this->langService)->transEffectLangs('portal.element_name.' . $element['element_id'], true);
                                    $portalLayoutContent[$rowKey][$columnKey]['elements'][$eleKey] = $element;
                                }
                            }
                        }
                    }
                }
            }
        }

        return json_encode($portalLayoutContent);
    }

    private function insertPortallayout($portalId, $content, $userId) {
        $insertData = [
            'portal_id' => $portalId,
            'portal_layout_content' => $content,
            'user_id' => $userId
        ];

        return app($this->portalLayoutRepository)->insertData($insertData);
    }

    /**
     * 设置门户权限
     *
     * @param array $data
     * @param int $portalId
     *
     * @return int 设置结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function setPortalPriv($data, $portalId) {
        $portalData = [
            'view_priv_scope' => $this->defaultValue('view_priv_scope', $data, 0),
            'edit_priv_scope' => $this->defaultValue('edit_priv_scope', $data, 0),
        ];

        if (app($this->portalRepository)->updateData($portalData, ['portal_id' => $portalId])) {
            if (!$this->setPortalViewPurview($portalId, $data)) {
                return ['code' => ['0x037015', 'portal']];
            }

            if (!$this->setPortalEditPurview($portalId, $data)) {
                return ['code' => ['0x037015', 'portal']];
            }

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 门户排序
     *
     * @param array $sortData
     *
     * @return int 排序结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function sortPortal($sortData)
    {
        $flag = true;

        foreach ($sortData as $v) {
            if ($v[0] == 0) {
                continue;
            }
            if (!app($this->portalRepository)->updateData(['portal_sort' => $v[1]], ['portal_id' => $v[0]])) {
                $flag = false;
            }
        }

        if ($flag == false) {
            return ['code' => ['0x037010', 'portal']];
        }

        return true;
    }

    /**
     * 恢复默认门户
     *
     * @param int $portalId
     *
     * @return int 恢复默认门户结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function recoverDefaultPortal($portalId, $userId, $deptId, $roleId)
    {
        if ($portalId != 1) {
            return ['code' => ['0x037011', 'portal']];
        }

        $defaultLayout = app($this->portalLayoutRepository)->getLayoutInfo(0, 'admin');
        $data = [
            'portal_layout_content' => $defaultLayout->portal_layout_content,
            'element_margin' => $defaultLayout->element_margin
        ];

        if (!app($this->portalLayoutRepository)->updateData($data,['portal_id' => [$portalId], 'user_id' => [$userId]])) {
            return ['code' => ['0x000003', 'common']];
        }
        $this->clearAllLayoutCache($userId, $portalId);
        return true;
    }

    /**
     * 统一门户
     *
     * @param int $portalId
     *
     * @return int 统一门户结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function unifyPortal($portalId, $userId, $deptId = null, $roleId = null)
    {
        /**
         * 统一门户涉及到多语言的更新，解析，删除等有点复杂，后续修改需要注意！
         */
        if ($portalId != 1) {
            return ['code' => ['0x037016', 'portal']];
        }
        $portalLayoutRepository = app($this->portalLayoutRepository);
        $langService = app($this->langService);
        $currentLayout = $portalLayoutRepository->getLayoutInfo($portalId, $userId);

        $allUserId = array_column(app($this->userSystemInfoRepository)->getInfoByWhere(['user_status' => [[0,2],'not_in']],['user_id']),'user_id');
        $portalLayoutRepository->clearDeleteUserLayout();
        $lists = $portalLayoutRepository->getList(['user_id' => [$userId, '!='], 'portal_id' => [1]]);
        $elementIds = $this->getElementIdFromLayouts($lists);
        if(!empty($allUserId)){
            $locale = Lang::getLocale();
            $allLocales = $langService->getEffectLangPackages([])['list'];
            $parseLayoutContent = $this->parseLayoutContent($currentLayout->portal_layout_content, $locale);
            $langs = [];
            foreach($allUserId as $uId){
                if($uId == $userId) {
                    continue;
                }
                $this->saveUnifyPortalLayout($langs, $portalLayoutRepository, $parseLayoutContent, $allLocales, $locale, $uId, 1);
            }
            // 统一默认门户
            $this->saveUnifyPortalLayout($langs, $portalLayoutRepository, $parseLayoutContent, $allLocales, $locale, 'admin', 0);
            //保存多语言数据
            foreach ($langs as $_locale => $langDatas) {
                $langService->mulitAddDynamicLang($langDatas, $_locale);
                $langService->deleteDynamicLang('portal', 'element_name', 'element_name', $elementIds, $_locale);
            }
        }
        if (!$portalLayoutRepository->batchUpdateLayout($currentLayout->element_margin, $portalId)) {
            return ['code' => ['0x000003', 'common']];
        }
        return true;
    }
    private function saveUnifyPortalLayout(&$langs, $portalLayoutRepository, $parseContent, $allLocales, $locale, $uId, $portalId = 1)
    {
        $content = $this->parseLayoutContent($parseContent, $locale, function($element, $rowKey, $columnKey, $eleKey) use(&$langs, $locale, $uId) {
            // 生产唯一的元素ID，并绑定多语言到对应的ID。
            $element['element_id'] = md5($rowKey . '_' . $columnKey . '_' . $eleKey . '_' . time() . $uId);
            // 拼装元素标题多语言数据
            $titleLang = $element['attribute']['title_lang'] ?? [];
            if (isset($element['attribute']['title']) && $element['attribute']['title']) {
                $titleLang[$locale] = $element['attribute']['title'];
            }
            foreach ($titleLang as $_locale => $langValue) {
                $langs[$_locale][] = ['table' => 'portal', 'column' => 'element_name', 'lang_key' => $element['element_id'], 'lang_value' => $langValue];
            }
            unset($element['attribute']['title']);
            return $element;
        });
        // 保存门户布局
        $portalLayoutRepository->layoutExists($portalId, $uId) 
                ? $portalLayoutRepository->updateLayout($content, $portalId, $uId) 
                : $this->insertPortallayout($portalId, $content, $uId);
        // clear缓存
        $this->clearLayoutCacheByLocales($allLocales, $uId, $portalId);
    }
    private function getElementIdFromLayouts($lists)
    {
        $elementIds = [];
        if (count($lists) > 0) {
            foreach ($lists as $item) {
                $portalLayoutContent = json_decode($item->portal_layout_content, true);
                if (!empty($portalLayoutContent)) {
                    foreach ($portalLayoutContent as $rowKey => $row) {
                        if(!empty($row) && is_array($row)) {
                            foreach ($row as $columnKey => $column) {
                                $elements = $column['elements'];
                                if(!empty($elements)) {
                                    foreach ($elements as $eleKey => $element) {
                                        if(isset($element['element_id']) && $element['element_id']) {
                                            $elementIds[] = $element['element_id'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $elementIds;
    }
    /**
     * 设置默认显示门户
     *
     * @param int $portalId
     *
     * @return int 设置默认显示门户结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function setDefaultPortal($portalId, $userId, $deptId, $roleId)
    {
        if (app($this->portalRepository)->setDefaultPortal($portalId)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function setUserAvatar($_data, $userId)
    {
        $body = substr($_data['avatar_thumb'], strpos($_data['avatar_thumb'], ',') + 1);
        //获取图片后缀名
        $headerOne = explode(';', substr($_data['avatar_thumb'], 0, strpos($_data['avatar_thumb'], ',')));
        $headerTwo = explode('/', $headerOne[0]);
        $suffix = $headerTwo[1];
        $avatar = base64_decode($body);
        $data = [
            'avatar_source' => $_data['avatar_source'],
            'avatar_thumb' => $suffix
        ];

        if (app($this->userInfoRepository)->updateData($data, ['user_id' => $userId])) {
            file_put_contents($this->getAvatarPath($userId), $avatar);
            @file_put_contents($this->avatarPath . 'default/'. $userId.'.png', $avatar);
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    private function getAvatarPath($userId, $prefix = 'EO')
    {
        $userIdCode = 0;
        $numberJoin = '';
        for ($i = 0; $i < strlen($userId); $i++) {
            $charAscii = ord($userId[$i]);
            if(is_numeric($userId[$i])){
                $numberJoin .= $userId[$i];
            }
            $userIdCode += $charAscii;
        }
        $prefixCode = '';
        for ($i = 0; $i < strlen($prefix); $i++) {
            $charAscii = ord($prefix[$i]);
            $prefixCode .= $charAscii;
        }
        return $this->avatarPath . $prefix . (($userIdCode * $prefixCode) + intval($numberJoin)) . '.png';
    }
    public function getEofficeAvatar($userId)
    {
        if (get_system_param('default_avatar_type') == 2) {
            $avatarPath = $this->avatarPath . 'default/';
            if(!is_dir($avatarPath)){
                @mkdir($avatarPath, 0777);
            } else {
                // D:\e-office10\www\eoffice10\server/public/access/images/avatar/default/
                // @chmod($avatarPath, 0777);
                // 替换为会报错的dir验证
                $dirPermission = verify_dir_permission($avatarPath);
                if(is_array($dirPermission) && isset($dirPermission['code'])) {
                    return $dirPermission;
                }
            }
            $avatar = $avatarPath . $userId . '.png';
            $defaultAvatar = get_system_param('default_avatar', 'default_avatar.png');
            if(!file_exists($avatar)) {
                $avatar = $avatarPath . $defaultAvatar;
            }
            if(file_exists($avatar)) {
                $file = fopen($avatar, "r");

                header("Content-Type: image/jpeg");

                echo fread($file, filesize($avatar));

                fclose($file);
            } else {
                header("Content-Type: image/jpeg");
                echo ' ';
            }
        } else {
            if ($userId == "systemAdmin") {
                $userAvatar = '';
            }
            if (!empty($userAvatar)) {
                return $userAvatar;
            } else {
                $prefix = 'EO';
                $userIdCode = 0;
                $numberTotal = '';
                $reg = '/^[0-9]+.?[0-9]*$/';
                for ($i = 0; $i < strlen($userId); $i++) {
                    $char = $userId[$i];
                    if (preg_match($reg, $char)) {
                        $numberTotal .= $char;
                    }
                    $charAscii = $this->charCodeAt($userId, $i);
                    $userIdCode += $charAscii;
                }
                $prefixCode = '';
                for ($i = 0; $i < strlen($prefix); $i++) {
                    $charAscii = $this->charCodeAt($prefix, $i);
                    $prefixCode .= $charAscii;
                }
                $numberTotalNumber = $numberTotal === '' ? 0 : intval($numberTotal);
                $img = $prefix . (($userIdCode * intval($prefixCode)) + $numberTotalNumber) . '.png';
                $accessPath = envOverload('ACCESS_PATH', 'access');
                $avatar = './' . $accessPath . '/images/avatar/' . $img;
                if (!file_exists($avatar)) {
                    header("Content-Type: image/jpeg");
                    echo ' ';
                } else {
                    $file = fopen($avatar, "r");

                    header("Content-Type: image/jpeg");

                    echo fread($file, filesize($avatar));

                    fclose($file);
                }
            }
        }

    }
    private function charCodeAt($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');
        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        } else {
            return null;
        }
    }
    public function getUserAvatar($userId) {
        if (!$path = createCustomDir("user-avatar")) {
            return ['code' => ['0x000006', 'common']];
        }

        $avatar = '';
        if ($userId == "systemAdmin") {
            $suffix = "png";
        } else {
            if ($userId && ($userInfo = app($this->userInfoRepository)->getDetail($userId))) {
                if (($suffix = $userInfo->avatar_thumb) && file_exists($path . $userId . '.' . $suffix)) {
                    $avatar = $path . $userId . '.' . $suffix;
                }
            }
        }


        if (!$avatar) {
            $font = base_path() . '/public/fonts/msyhbd.ttc';
            $userName = $this->getAvatarText($userId);
            $rgb = $this->getRandomRGBColor();
            $avatar = imagecreatetruecolor(60, 60);

            $backgroundColor = imagecolorallocate($avatar, $rgb[0], $rgb[1], $rgb[2]);
            $textColor = imagecolorallocate($avatar, 255, 255, 255);

            imagefilledrectangle($avatar, 0, 0, 60, 60, $backgroundColor);

            imagettftext($avatar, 15, 0, 10, 35, $textColor, $font, $userName);

            header("Content-type: image/png");

            imagepng($avatar);

            imagedestroy($avatar);
        } else {
            $file = fopen($avatar, "r");

            header("Content-Type: image/jpeg");

            echo fread($file, filesize($avatar));

            fclose($file);
        }

        return true;
    }

    private function getRandomRGBColor() {
        $colors = [
            [42, 181, 246], [255, 163, 44], [237, 88, 84], [125, 198, 83],
            [113, 144, 250], [37, 198, 216], [253, 198, 46], [37, 168, 154], [245, 113, 65]
        ];
        return $colors[array_rand($colors, 1)];
    }

    private function getAvatarText($userId) {
        if (!$userId) {
            return trans('portal.unknown');
        } else if ($userId == "systemAdmin") {
            return trans('portal.system');
        }

        $userName = app($this->userRepository)->getUserName($userId);
        if (strlen($userName) <= 4) {
            return $userName;
        }

        preg_match_all("/./u", $userName, $matches);

        $texts = $matches[0];
        $limit = 0;
        $filterTexts = [];

        for ($i = sizeof($texts) - 1; $i >= 0; $i--) {
            $limit = preg_match("/[0-9a-zA-Z]/", $texts[$i]) ? $limit + 1.5 : $limit + 3;
            if ($limit > 6) {
                break;
            }
            $filterTexts[] = $texts[$i];
        }

        $avatarText = '';
        for ($i = sizeof($filterTexts) - 1; $i >= 0; $i--) {
            $avatarText .= $filterTexts[$i];
        }

        return $avatarText;
    }

    /**
     * [getUserQrCode 获取用户二维码]
     *
     * @method 朱从玺
     *
     * @param  [string]          $userId [用户ID]
     *
     * @return [resource]                [获取结果]
     */
    public function getUserQrCode($userId) {
        $qrCodePath = createCustomDir("qrcode");

        if (!$qrCodePath) {
            return ['code' => ['0x000006', 'common']];
        }

        $qrCode = $qrCodePath . $userId . '.png';

        //如果二维码图片不存在,生成一个
        if (!file_exists($qrCode)) {
            app($this->personalSetService)->setUserQrCode($userId);
        }

        $file = fopen($qrCode, "r");

        header("Content-Type: image/jpeg");
        echo fread($file, filesize($qrCode));

        fclose($file);

        return true;
    }

    private function substr_utf8($string, $start, $length) {
        $chars = $string;
        $i = 0;
        do {
            if (preg_match("/[0-9a-zA-Z]/", $chars[$i])) {//纯英文
                $m++;
            } else {
                $n++;
            }//非英文字节,
            $k = $n / 3 + $m / 2;
            $l = $n / 3 + $m; //最终截取长度；$l = $n/3+$m*2？
            $i++;
        } while ($k < $length);
        $str1 = mb_substr($string, $start, $l, 'utf-8'); //保证不会出现乱码
        return $str1;
    }

    public function setSystemLogo($data, $userId) {
        $param = [
            'sys_logo' => $data['sys_logo'],
            'sys_logo_type' => $data['sys_logo_type']
        ];

        if (app($this->portalRepository)->updateSystemParams($param)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 为参数赋予默认值
     *
     * @param type $key 键值
     * @param array $data 原来的数据
     * @param type $default 默认值
     *
     * @return type 处理后的值
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    private function defaultValue($key, $data, $default) {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * 获取默认排序号
     *
     * @return int 默认排序号
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    private function getSort() {
        return app($this->portalRepository)->getMaxSort() + 1;
    }

    /**
     * 获取所有的有编辑权限的id
     *
     * @return array 所有的有编辑权限的id
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    private function getEditPrivPortalIds($userId, $deptId, $roleId) {
        $portals = app($this->portalRepository)->getEditPrivPortal($userId, $deptId, $roleId);
        if (count($portals) > 0) {
            $portalIds = [];

            foreach ($portals as $portal) {
                $portalIds[] = $portal->portal_id;
            }

            return $portalIds;
        }

        return [];
    }

    private function createPortalLayout($portalId) {
        $layoutData = [
            'portal_id' => $portalId,
            'portal_layout_content' => '',
            'user_id' => 'admin'
        ];

        if (!app($this->portalLayoutRepository)->insertData($layoutData)) {
            return false;
        }

        return true;
    }

    private function setPortalViewPurview($portalId, $data) {
        $flag = true;

        if (!app($this->portalRepository)->setUserViewPurview($portalId, $this->defaultValue('view_user_priv', $data, ''))) {
            $flag = false;
        }

        if (!app($this->portalRepository)->setDeptViewPurview($portalId, $this->defaultValue('view_dept_priv', $data, ''))) {
            $flag = false;
        }

        if (!app($this->portalRepository)->setRoleViewPurview($portalId, $this->defaultValue('view_role_priv', $data, ''))) {
            $flag = false;
        }

        return $flag;
    }

    private function setPortalEditPurview($portalId, $data) {
        $flag = true;

        if (!app($this->portalRepository)->setUserEditPurview($portalId, $this->defaultValue('edit_user_priv', $data, ''))) {
            $flag = false;
        }

        if (!app($this->portalRepository)->setDeptEditPurview($portalId, $this->defaultValue('edit_dept_priv', $data, ''))) {
            $flag = false;
        }

        if (!app($this->portalRepository)->setRoleEditPurview($portalId, $this->defaultValue('edit_role_priv', $data, ''))) {
            $flag = false;
        }

        return $flag;
    }

    public function getReportType() {
        return [
            ['type_id' => 1, 'type_name' => 'common', 'type_name_cn' => trans('portal.common'), 'has_children' => 1],
            ['type_id' => 2, 'type_name' => 'task', 'type_name_cn' => trans('portal.task'), 'has_children' => 1],
            ['type_id' => 3, 'type_name' => 'project', 'type_name_cn' => trans('portal.project'), 'has_children' => 1],
            ['type_id' => 4, 'type_name' => 'customer', 'type_name_cn' => trans('portal.customer'), 'has_children' => 1],
        ];
    }

    public function getReportsByTypeId($params) {
        $params = $this->parseParams($params);
        $reports = [
            [
                'report_id' => 1,
                'report_name' => 'incomeExpense',
                'type_id' => 3,
                'type_name' => 'project',
                'report_name_cn' => trans('portal.Project_revenue_expenditure_statistics')
            ],
            ['report_id' => 2, 'report_name' => 'travel', 'type_id' => 1, 'type_name' => 'common', 'report_name_cn' => trans('portal.Employee_travel')],
            ['report_id' => 6, 'report_name' => 'vote', 'type_id' => 1, 'type_name' => 'common', 'report_name_cn' => trans('portal.Polling_statistics')],
            ['report_id' => 7, 'report_name' => 'areaSale', 'type_id' => 4, 'type_name' => 'customer', 'report_name_cn' => trans('portal.Regional_sales_performance_statistics')],
            ['report_id' => 8, 'report_name' => 'areaSalePie', 'type_id' => 4, 'type_name' => 'customer', 'report_name_cn' => trans('portal.Regional_sales_performance_pie_chart')],
            ['report_id' => 4, 'report_name' => 'taskDirection', 'type_id' => 2, 'type_name' => 'task', 'report_name_cn' => trans('portal.Task_trend_chart')],
            ['report_id' => 5, 'report_name' => 'taskOver', 'type_id' => 2, 'type_name' => 'task', 'report_name_cn' => trans('portal.Task_completion')]
        ];
        if (isset($params['search']) && isset($params['search']['type_id'])) {
            $typeId = $params['search']['type_id'][0];
            $returns = [];
            foreach ($reports as $key => $value) {
                if ($value['type_id'] == $typeId) {
                    $returns[] = $value;
                }
            }
            return $returns;
        } else if (isset($params['search']) && isset($params['search']['report_id'])) {
            $reportId = $params['search']['report_id'][0];
            $returns = [];
            foreach ($reports as $key => $value) {
                if ($value['report_id'] == $reportId) {
                    $returns[] = $value;
                }
            }
            return $returns;
        }

        return $reports;
    }

    public function getRssContent($param) {
        if(!isset($param['rssUrl'])){
            return [];
        }
        $rss = app($this->rssRepository)->parseRss($param['rssUrl']);
        if ($rss == false) {
            return [];
        }
        if(isset($rss['code'])){
            return $rss;
        }
        $items = [];
        $limit = isset($param['limit']) ? $param['limit'] : 6;
        $i = 1;
        foreach ($rss['items'] as $item) {
            if ($i > $limit) {
                break;
            }
            $i++;
            $temp['title'] = strtr($item['title'], ['<![CDATA[' => '', ']]>' => '']);
            $temp['link'] = strtr($item['link'], ['<![CDATA[' => '', ']]>' => '']);
            $description = isset($item['description']) ? strtr($item['description'], ['<![CDATA[' => '', ']]>' => '']) : '';
            $temp['author'] = isset($item['author']) ? strtr($item['author'], ['<![CDATA[' => '', ']]>' => '']) : '';
            $temp['pubDate'] = isset($item['pubDate']) ? date('Y-m-d H:i:s', strtotime(strtr($item['pubDate'], ['<![CDATA[' => '', ']]>' => '']))) : '';
//			$temp['source']			= isset($item['source']) ? strtr($item['source'],['<![CDATA[' => '',']]>' => '']) : '';
//			$temp['category']		= isset($item['category']) ? strtr($item['category'],['<![CDATA[' => '',']]>' => '']) : '';
            $items[] = $temp;
        }
        return $items;
    }
    /**
     * 可能是废弃函数
     * @param type $data
     * @return type
     */
    public function setMenuPortal($data)
    {
      return  app($this->userMenuRepository)->setMenuPortal($data);
    }
    public function getMenuPortal(){
        $menus = app($this->userMenuService)->getUserMenus(own('user_id'));
        return  app($this->userMenuRepository)->getMenuPortal($menus['menu']);
    }
    public function setFavorite($data){
        if (isset($data['menu_id']) && !empty($data['menu_id'])) {
           return  app($this->userMenuRepository)->setFavorite($data['menu_id']);
        }

    }
    public function setUserCommonMenu($data, $loginUserId)
    {
        if (isset($data['menu_id']) && is_array($data['menu_id'])) {
            $commonMenuId = $data['menu_id'];
            return  app($this->userMenuRepository)->setUserCommonMenu($commonMenuId, $loginUserId);
        }
    }
    public function cancelFavorite($data){
        if (isset($data['menu_id']) && !empty($data['menu_id'])) {
           return  app($this->userMenuRepository)->cancelFavorite($data['menu_id']);
        }

    }
    public function getHomeInitData($own)
    {
        return [
            'system_title' => get_system_param('system_title', ''),
            'system_version' => app($this->empowerService)->getSystemVersion(),
            'home_layout' => $this->getMenuPortal(),
            'login_prompts' => app($this->promptService)->getLoginPrompts()
        ];
    }
    public function checkWeChat()
    {
        return [
            'wechat_check' => app($this->weixinService)->weixinCheck(),
            'work_wechat_check' => app($this->workWechatService)->workwechatCheck()
        ];
    }

    /**
     * @param array $data
     * @param array $own
     * @return bool
     */
    public function setSearchItem($data, $own)
    {
        $data = $this->filterSearchItem($data, $own);
        if(set_system_param('global_search_item', json_encode($data))) {
            ecache('Portal:GlobalSearchItem')->delAll();
            return true;
        }
        return false;
    }

    /**
     * 获取全站搜索项目
     * @param array $own
     *
     * @return array|bool|mixed|string|null
     */
    public function getSearchItem($own)
    {
        $userId = $own['user_id'];
        if(ecache('Portal:GlobalSearchItem')->get($userId)) {
            return ecache('Portal:GlobalSearchItem')->get($userId);
        }
        if($data = get_system_param('global_search_item', null)) {
            $data = json_decode($data, true);
            $data = $this->filterSearchItem($data, $own);
            ecache('Portal:GlobalSearchItem')->set($userId, $data);
            return $data;
        }

        return null;
    }

    /**
     * 根据权限过滤全站搜索相关item
     *
     * @param array $data
     * @param array $own
     */
    public function filterSearchItem($data, $own)
    {
        /**
         * 从 $own['menus']['menu'] 中获取对应id
         * 全文索引 => 0
         * 用户 => 98     用户无单独授权，在系统管理下，用户不一定有系统管理权限，但应该始终有用户搜索权限，因为暂无授权相关
         * 流程管理 => 1
         * 文档中心 => 6
         * 消息 对应多个模块  无授权相关
         * 邮件 => 11  内部邮件
         * 新闻 => 237
         * 公告 => 320
         * 客户 => 44
         * 通讯 => 216 通讯录
         * 人事 => 415 人事档案
         */
        $map = [
            'website' => 0,
            'user' => 0,
            'flow' => 1,
            'document' => 6,
            'sms' => 0,
            'email' => 11,
            'news' => 237,
            'notify' => 320,
            'customer' => 44,
            'address' => 216,
            'personnel' => 415,
        ];

        if (isset($own['menus']['menu'])) {
            $menu = $own['menus']['menu'];  // 用户可访问的菜单
            $all = $data['all'];            // 配置中全部的搜索项
            $checked = $data['checked'];    // 配置中选中的搜索项
            $filterAll = [];                // 根据权限过滤后的全部搜索项
            $filterChecked = [];            // 根据权限过滤后的选中搜索项
            // 过滤全部项
            array_map(function ($item) use (&$filterAll, $map, $menu){
                if (key_exists($item, $map)) {
                    $menuId = $map[$item];
                    if ($menuId) {
                        if (in_array($menuId, $menu)) {
                            $filterAll[] = $item;
                        }
                    } else {
                        $filterAll[] = $item;
                    }
                }
            }, $all);
            // 过滤选中项
            array_map(function ($item) use (&$filterChecked, $map, $menu){
                if (key_exists($item, $map)) {
                    $menuId = $map[$item];
                    if ($menuId) {
                        if (in_array($menuId, $menu)) {
                            $filterChecked[] = $item;
                        }
                    } else {
                        $filterChecked[] = $item;
                    }
                }
            }, $checked);

            return ['all' => $filterAll, 'checked' => $filterChecked];
        }

        return ['all' => [], 'checked' => []];
    }

    public function setNavbar($data){
        $type = isset($data['save_type']) ? $data['save_type'] : 'web_home';
        if($type == 'web_home') {
            $insert['web_home'] = isset($data['web_home'])?$data['web_home']:'icon';
            $insert['home_text'] = isset($data['home_text'])?"home_text":'';
            $insert['web_home_module_type'] = $data['web_home_module_type'] ??  'portal';
            $home_text_lang = isset($data['home_text_lang']) ? $data['home_text_lang'] : '';
            if (!empty($home_text_lang) && is_array($home_text_lang)) {
                foreach ($home_text_lang as $key => $value) {
                    $langData = [
                        'table'      => 'system_params',
                        'column'     => 'param_value',
                        'lang_key'   => "home_text",
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            } else {
                $langData = [
                    'table'      => 'system_params',
                    'column'     => 'param_value',
                    'lang_key'   => "home_text",
                    'lang_value' => isset($data['home_text'])?$data['home_text']:'',
                ];
                app($this->langService)->addDynamicLang($langData);
            }
            if ($insert['web_home'] !="text") {
                $insert['home_text'] = '';
                remove_dynamic_langs('system_params.param_value.home_text');
            }
            if ($insert['web_home_module_type'] == 'custom') {
                $insert['home_module'] = $data['home_module'] ??  null;
            }
        } else {
            $insert['navbar_type'] = isset($data['navbar_type'])?$data['navbar_type']:'click';
            $insert['navigate_menus'] = isset($data['navigate_menus'])?$data['navigate_menus']:'';
            if ($insert['navbar_type'] != 'custom') {
                $insert['navigate_menus'] = '';
            }
        }
        app($this->portalRepository)->setNavbar($insert);
        Cache::forget('eoffice10:home_navbar');
        return true;
    }

    public function getNavbar(){
        if(Cache::has('eoffice10:home_navbar')) {
            return Cache::get('eoffice10:home_navbar');
        }
        $result = app($this->portalRepository)->getNavbar();
        if (!empty($result['home_text'])) {
            $result['home_text_lang'] = app($this->langService)->transEffectLangs("system_params.param_value.home_text");
        }
        Cache::forever('eoffice10:home_navbar', $result);
        return  $result;
    }

    /*
     * 获取门户导出布局信息
    */
    public function getExportLayout($portalId, $userId) {
        if ($portalId == 1) {
            $layout = app($this->portalLayoutRepository)->getLayoutInfo($portalId, $userId);
        } else {
            $layout = app($this->portalLayoutRepository)->getOneFieldInfo(['portal_id' => $portalId]);
        }

        if (empty($layout)) {
            return '';
        }
        $content = json_decode($layout->portal_layout_content, true);
        $content['element_margin'] = $layout->element_margin;
        $content['version'] = version();

        return json_encode($content);
    }
    /*
     * 返回导入的门户布局内容
     *
     */
    public function getImportLayout($data, $userId)
    {
        $from = isset($data['from']) ? $data['from'] : '';
        if ($from == 'online') {
            if (isset($data['content']) && !empty($data['content'])) {
                $portalName = isset($data['content']['name']) && !empty($data['content']['name']) ? $data['content']['name'] : trans('portal.unknown');
                $fileContent = isset($data['content']['file_content']) ? $data['content']['file_content'] : '';
                list($layoutContent, $margin, $version) = $this->parsePortalLayout($fileContent, $userId);
                if (!$version) {
                    return ['code' => ['0x037017', 'portal'], 'dynamic' => ['【'.$portalName.'】'.trans('portal.0x037017')]];
                }

                if ( !isset($data['portal_id']) ) {
                    // 新建临时门户
                    $result = $this->addPortal(['portal_name' => $portalName], $userId);
                    if (!isset($result['portal_id'])) {
                        return $result;
                    }
                    $portalId = $result['portal_id'];
                } else {
                    $portalId = $data['portal_id'];
                }

                if ($portalId == 1) {
                    app($this->portalLayoutRepository)->updateData(['element_margin' => $margin], ['portal_id' => [$portalId], 'user_id' => [$userId]]);
                } else {
                    app($this->portalLayoutRepository)->updateData(['element_margin' => $margin], ['portal_id' => [$portalId] ]);
                }

                $this->setPortalLayout([
                    'portal_layout_content' => $layoutContent,
                    'portal_id' => $portalId
                ], $userId);

                return [ $data['content']['resource_id'] => $portalId ];
            }
        } else {
            if (isset($data['attachment_id']) && !empty($data['attachment_id'])) {
                $attachmentFile = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
                $portalName = isset($attachmentFile['attachment_name']) && !empty($attachmentFile['attachment_name']) ? trim($attachmentFile['attachment_name'],'.portal') : trans('portal.unknown');
                $fileContent = '';
                if (isset($attachmentFile['temp_src_file'])) {
                    $fileContent = convert_to_utf8(file_get_contents($attachmentFile['temp_src_file']));
                }

                list($layoutContent, $margin, $version) = $this->parsePortalLayout($fileContent, $userId);
                if (!$version) {
                    return [ 'code' => ['0x037017', 'portal'], 'dynamic' => ['【'.$portalName.'】'.trans('portal.0x037017')] ];
                }

                if ( !isset($data['portal_id']) ) {
                    // 新建临时门户
                    $result = $this->addPortal(['portal_name' => $portalName], $userId);
                    if (!isset($result['portal_id'])) {
                        return $result;
                    }
                    $portalId = $result['portal_id'];
                } else {
                    $portalId = $data['portal_id'];
                }

                if ($portalId == 1) {
                    app($this->portalLayoutRepository)->updateData(['element_margin' => $margin], ['portal_id' => [$portalId], 'user_id' => [$userId]]);
                } else {
                    app($this->portalLayoutRepository)->updateData(['element_margin' => $margin], ['portal_id' => [$portalId] ]);
                }

                $this->setPortalLayout([
                    'portal_layout_content' => $layoutContent,
                    'portal_id' => $portalId
                ], $userId);

                return [ $data['attachment_id'] => $portalId ];
            }
        }

        return ['code' => ['0x000003', 'common']];
    }
    // 解析导入文件
    private function parsePortalLayout($fileContent, $userId) {
        $margin = 5;
        $version = '';
        if (!empty($fileContent)) {
            if (!is_array($fileContent)) {
                $fileContent = json_decode($fileContent, true);
            }
            if (isset($fileContent['element_margin'])) {
                $margin = $fileContent['element_margin'];
                unset($fileContent['element_margin']);
            }
            if (isset($fileContent['version'])) {
                $version = $fileContent['version'];
                unset($fileContent['version']);
            }
            foreach ($fileContent as $rowKey => $row) {
                if(!empty($row)) {
                    foreach ($row as $columnKey => $column) {
                        $elements = $column['elements'];
                        if(!empty($elements)) {
                            foreach ($elements as $eleKey => $element) {
                                if (isset($element['module'])) {
                                    // 检查导入的元素dirId在本系统是否有;如果没有，删除掉
                                    if ($element['module'] == 'album') {
                                        if (isset($element['attribute']['dirId'])) {
                                            $purview = app('App\EofficeApp\PhotoAlbum\Services\PhotoAlbumService')->hasManagePur($userId, $element['attribute']['dirId']);
                                            if (!$purview) {
                                                unset($fileContent[$rowKey][$columnKey]['elements'][$eleKey]['attribute']['dirId']);
                                            }
                                        }
                                    } else if ($element['module'] == 'product') {
                                        if (isset($element['attribute']['dirId'])) {
                                            $result = app('App\EofficeApp\Product\Services\ProductService')->getParentType($element['attribute']['dirId']);
                                            if (empty($result) || isset($result['code'])) {
                                                unset($fileContent[$rowKey][$columnKey]['elements'][$eleKey]['attribute']['dirId']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $fileContent = json_encode($fileContent);
        }
        /**
         * 当解析到版本时：
            * 素材版本大于当前e-office版本时，不允许导入，提示：素材版本不支持当前e-office版本，请升级当前e-office系统！
            * 素材版本小于或等于当前e-office版本时，允许正常导入。
         * 当未解析到版本时：
            * 直接导入
         */
        $version = empty($version) ? true : ($version > version() ? false : true);

        return [$fileContent, $margin, $version];
    }
}
