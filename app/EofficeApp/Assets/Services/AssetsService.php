<?php

namespace App\EofficeApp\Assets\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Assets\Repositories\AssetsRepository;
use App\EofficeApp\Assets\Permissions\AssetsPermission;
use DB;
use Eoffice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use QrCode;
use Illuminate\Support\Facades\Redis;
use Schema;

class AssetsService extends BaseService
{

    // redis 资产id
    const RS_NUMBER_PREFIX = 'assets:number';

    public function __construct()
    {
        parent::__construct();
        $this->assetsTypeRepository         = 'App\EofficeApp\Assets\Repositories\AssetsTypeRepository';
        $this->assetsRepository             = 'App\EofficeApp\Assets\Repositories\AssetsRepository';
        $this->assetsRuleSettingRepository  = 'App\EofficeApp\Assets\Repositories\AssetsRuleSettingRepository';
        $this->userRepository               = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->assetsListRepository         = 'App\EofficeApp\Assets\Repositories\AssetsListRepository';
        $this->assetsApplysRepository       = 'App\EofficeApp\Assets\Repositories\AssetsApplysRepository';
        $this->assetsFlowRepository         = 'App\EofficeApp\Assets\Repositories\AssetsFlowRepository';
        $this->assetsChangeRepository       = 'App\EofficeApp\Assets\Repositories\AssetsChangeRepository';
        $this->assetsInventoryRepository    = 'App\EofficeApp\Assets\Repositories\AssetsInventoryRepository';
        $this->assetsFormSetRepository      = 'App\EofficeApp\Assets\Repositories\AssetsFormSetRepository';
        $this->assetsRecordsRepository      = 'App\EofficeApp\Assets\Repositories\AssetsRecordsRepository';
        $this->assetsInventoryRecordsRepository = 'App\EofficeApp\Assets\Repositories\AssetsInventoryRecordsRepository';
        $this->UserService                  = 'App\EofficeApp\User\Services\UserService';
        $this->formModelingService          = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->key                          = 'assets:type';
        $this->flowPermissionService        = 'App\EofficeApp\Flow\Services\FlowPermissionService';
        $this->flowRunService               = 'App\EofficeApp\Flow\Services\FlowRunService';
        $this->flowService                  = 'App\EofficeApp\Flow\Services\FlowService';
    }

    /**
     * 中间列资产管理分类
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function getAssetsType($data)
    {
        $result = [
            'name' => '全部分类',
            'list' => $data ? app($this->assetsTypeRepository)->getType() : [],
        ];
        $data = app($this->assetsTypeRepository)->getType();
        $this->set_hset($result['list']);
        return $data;
    }

    private function set_hset($type)
    {
        if (!Redis::exists($this->key) && $type) {
            foreach ($type as $key => $vo) {
                Redis::hset($this->key, $vo['id'], serialize(['type_name' => $vo['type_name'], 'date' => $vo['default_date']]));
            }
        }

    }

    /**
     * 创建分类类型
     *
     * @author zw
     *
     * @since  2018-06-05 创建
     */
    public function createAssetsType($data)
    {
        $param = $this->parseParams($data);
        if ($param && is_array($param)) {
            foreach ($param as $vo) {
                $vo['create_time'] = date('Y-m-d H:i:s', time());
                $vo['default_date'] = (isset($vo['default_date']) && $vo['default_date']) ? intval($vo['default_date']) : 60;
                $typeData = app($this->assetsTypeRepository)->insertData($vo);
                if ($typeData->id) {
                    app($this->assetsTypeRepository)->updateData(['type' => $typeData->id], ['id' => $typeData->id]);
                }

                Redis::hset($this->key, $typeData->id, serialize(['type_name' => $typeData->type_name, 'date' => $typeData->default_date]));
            }
        }
        return true;
    }

    /**
     * 资产分类删除
     *
     * @author zw
     *
     * @since  2018-06-05 创建
     */
    public function deleteType($id)
    {
        $typeData = app($this->assetsTypeRepository)->getDetail($id);
        $assetsData = app($this->assetsRepository)->getAssetsData(['type' => $id]);
        if ($assetsData) {
            //该资产分类下如何存在数据，则不能删除
            return ['code' => ['cant_delete', 'assets']];
        } else {
            if ($typeData) {
                $result = app($this->assetsTypeRepository)->updateData(['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
                if (!$result) {
                    return ['code' => ['0x000003', 'common']];
                }
                return $result;
            }
        }
    }

    /**
     * 资产分类详情
     *
     * @author zw
     *
     * @since  2018-06-05 创建
     */
    public function getTypeDetail($id)
    {
        $result = app($this->assetsTypeRepository)->getTypeData($id);
        return $result;

    }

    /**
     * 资产分类编辑
     *
     * @author zw
     *
     * @since  2018-06-05 创建
     */
    public function editType($id, $data)
    {

        app($this->assetsTypeRepository)->updateData(['type_name' => trim($data['type_name']), 'default_date' => intval($data['default_date'])], ['id' => $id]);
        Redis::hset($this->key, $id, serialize(['type_name' => $data['type_name'], 'date' => $data['default_date']]));
        //自定义页面处理，当变更了数据同步清除redis缓存数据，然后从新读取
        if (Redis::hexists('parse:data_select_systemData_assets_type', $id)) {
            Redis::hdel('parse:data_select_systemData_assets_type', $id);
        }
        return true;
    }

    /**
     * 资产外发系统数据下拉使用
     *
     * @author zw
     *
     * @since  2018-06-29 创建
     */
    public function getSelectDate($type, $user_info, $data)
    {
        $params = [];
        $result = app($this->userRepository)->getUserAllData($data['user_id']);
        $role_ids = [];
        if ($result) {
            $result = json_decode($result, 1);
            foreach ($result['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
        }
        switch ($type) {
            case 1: //资产申请下拉框参数
                $params = [
                    'search' => [
                        'dept' => [$result ? $result['user_has_one_system_info']['dept_id'] : $user_info['dept_id']],
                        'role' => [$role_ids ?: $user_info['role_id']],
                        'users' => [$result ? $result['user_id'] : $user_info['user_id']],
                        'status' => [0],
                    ],
                    'orSearch' => [
                        'is_all' => [1],
                        'status' => [0],
                    ],
                ];
                break;
            case 2: //资产维护下拉框参数
                $params = [
                    'search' => [
                        'status' => [0],
                        'managers' => [$result ? $result['user_id'] : $user_info['user_id'], 'like'],
                    ],
                ];
                break;
            case 3: //资产退库下拉框参数
                $params = [
                    'search' => [
                        'status' => [0],
                        'managers' => [$result ? $result['user_id'] : $user_info['user_id'], 'like'],
                    ],
                ];
                break;
        }
        //移动端自定义页面详情数据
        if (isset($data['search']) && $data['search']) {
            $params['search'] = json_decode($data['search'], 1);
            if (isset($params['orSearch']) && $params['orSearch']) {
                unset($params['orSearch']);
            }
        }
        $result = $this->response(app($this->assetsRepository), 'selectDateTotal', 'selectDateLists', $this->parseParams($params));
        return $result;
    }

    /**
     * 资产选择器使用
     *
     * @author zw
     *
     * @since  2018-12-17 创建
     */

    public function assetChoiceList($type, $user_info, $default)
    {
        if (isset($default['search'])) {
            $default['search'] = json_decode($default['search'], 1);
        } else {
            $default['search'] = [];
        }
        switch ($type) {
            case 'apply': //资产申请系统选择器参数
                $data['search'] = [
                    'dept' => [$user_info['dept_id']],
                    'role' => [$user_info['role_id']],
                    'users' => [$user_info['user_id']],
                    'status' => [0]
                ];
                $default['orSearch'] = [
                    'is_all' => [1],
                    'status' => [0]
                ];
                break;
            case 'repair': //资产维护系统选择器参数
                $data['search'] = [
                    'managers' => [$user_info['user_id'], 'like'],
                    'status' => [0]
                ];
                break;
            case 'retire': //资产退库系统选择器参数
                $data['search'] = [
                    'managers' => [$user_info['user_id'], 'like'],
                    'status' => [0]
                ];
                break;
        }
        $default['search'] = array_merge($default['search'], $data['search']);
//        if (isset($default['search']['id'])) {
//            unset($default['orSearch']);
//            $params['search']['id'] = $default['search']['id'];
//        } else {
//            $params = $default;
//        }
        $params = $default;
        $result = $this->response(app($this->assetsRepository), 'selectDateTotal', 'selectDateLists', $params);
        return $result;
    }

    /**
     * 资产列表
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function  getAssetsList($data)
    {
        $data = $this->parseParams($data);
        $data = $this->parseViewData($data,'change_user');
        if(!$data){
            return [];
        }
        $result = $this->response(app($this->assetsRepository), 'assetsListsTotal', 'assetsLists', $this->parseParams($data));
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $result['list'][$key]['change_user'] = $vo['change_user'] ? app($this->userRepository)->getUserAllData($vo['change_user']) : '';
                $leftTime = $vo['user_time'] - $this->getMonthNum($vo['product_at'] ?? '2037-08-16', time());
                $result['list'][$key]['leftover'] = $leftTime > 0 ? $leftTime : 0;
                if (empty($vo['assets_type'])) {
                    $result['list'][$key]['assets_type']['type_name'] = trans('assets.delete_type');
                }
            }
        }
        return $result;
    }

    //获取指定两个时间之间相差的月份
    public function getMonthNum($start, $end)
    {
        if($start == '0000-00-00' || !$start){
            $start = '2037-08-16';
        }
        $start_stamp = is_numeric($start) ? $start : strtotime($start);
        $end_stamp = is_numeric($end) ? $end : strtotime($end);
        list($start_1['y'], $start_1['m']) = explode("-", date('Y-m', $start_stamp));
        list($end_2['y'], $end_2['m']) = explode("-", date('Y-m', $end_stamp));
        return abs(($end_2['y'] - $start_1['y']) * 12 + $end_2['m'] - $start_1['m']);
    }

    /**
     * 资产列表(门户专门用)
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function getportalList($data)
    {
        $data = $this->parseParams($data);
        if(!isset($data['search'])){
            $data['search'] = [];
        }
        $result = $this->response(app($this->assetsRepository), 'portalListsTotal', 'portalLists', $data);
        return $result;
    }

    /**
     * 资产入库
     *
     * @author zw
     *
     * @since  2018-06-05 创建
     */
    public function creatAsset($data)
    {
        if($validate = self::parseData($data)){
            return $validate;
        };
        $result = app($this->formModelingService)->addCustomData($data, 'assets');
        if(isset($result['code'])){
            return $result;
        }
        list($assets_name_py,$assets_name_zm) = convert_pinyin($data['assets_name']);
        $update = [
            'status' => isset($data['status']) ? $data['status'] : 0,
            'assets_name_py' => $assets_name_py,
            'assets_name_zm' => $assets_name_zm,
        ];
        app($this->assetsRepository)->updateData($update, ['id' => $result]);
        if ($result) {
            $recordData = [
                'assets_id' => $result,
                'status' => 1,
                'apply_type' => 'storage',
                'apply_user' => $data['operator_id'],
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => isset($data['remark']) ? trim($data['remark']) : '',
            ];
            app($this->assetsRecordsRepository)->insertData($recordData); //记录入库
        }
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }
        return $result;
    }

    public function parseData(&$data){
        $data['assets_code'] = $data['assets_code'] ? $data['assets_code'] : $this->getRuleSet($data['type'])['rulecode'];
        if(empty($data['assets_code'])){
            return ['code' => ['assets_code_empty', 'assets']];
        }
        if(strlen($data['assets_code']) > 30){
            return ['code' => ['assets_code_length', 'assets']];
        }
        $data['operator_id'] = own()['user_id'];
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['run_id'] = is_array($data['run_id']) ? implode(',',$data['run_id']) : $data['run_id'];
        if($data['expire_at'] == '0000-00-00'){
            $data['expire_at'] = Carbon::parse($data['product_at'])->addMonths($data['user_time'])->toDateString();
        }
        if(app($this->assetsRepository)->getDetailByCode(['assets_code'=>$data['assets_code']])){
            return ['code' => ['assets_code_repeat', 'assets']];
        };
    }

    /**
     * 资产详情
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function getDetail($id)
    {
        $result = app($this->assetsRepository)->assetsDetail($id);
        if ($result) {
            $result['attachment_id'] = [];
            if (Schema::hasTable('attachment_relataion_assets')) {
                $attachment_ids = DB::table('attachment_relataion_assets')->where(['entity_id' => $id, 'entity_column' => 'attachment_id'])->get()->toArray();
                if ($attachment_ids && is_array($attachment_ids)) {
                    foreach ($attachment_ids as $attachment_id) {
                        $attachments[] = $attachment_id->attachment_id;
                    }
                    $result['attachment_id'] = $attachments;
                }
            }
            $type = app($this->assetsTypeRepository)->getTypeData($result['type']);
            $result['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
            $result['end_time'] = date('Y-m-d', strtotime($result['product_at'] . " +" . $result['user_time'] . " month -1 day"));
//            $result['run_ids'] = $result['run_id'] ? app($this->assetsFlowRepository)->getFlowRuns(explode(',', str_replace('"', '', $result['run_id']))) : '';
            $result['dept'] = $result['dept'] ? explode(',', $result['dept']) : [];
            $result['users'] = $result['users'] ? explode(',', $result['users']) : [];
            $result['role'] = $result['role'] ? explode(',', $result['role']) : [];
            //本月折价
            $discount = sprintf("%.2f", (($result['price'] - $result['residual_amount']) / $result['user_time']));
            $countAmount = sprintf("%.2f", ($discount * $this->getMonthNum($result['product_at'] ?? '2037-08-16', time())));
            $result['amount'] = sprintf("%.2f", ($result['price'] - $countAmount));
            //过期未退库
            if (time() > strtotime($result['expire_at'])) {
                $result['amount'] = $result['residual_amount'];
            }
        }

        return $result;
    }

    /**
     * 使用申请
     *
     * @author zw
     *
     * @since  2018-06-05 创建
     */
    public function creatApply($data)
    {
        $result = app($this->assetsApplysRepository)->insertData($data);
        if ($result) {
            $recordData = [
                'assets_id' => $result->assets_id,
                'status' => (isset($data['apply_way']) && $data['apply_way'] == 1) ? 2 : 1, // 调拨
                'apply_type' => 'apply',
                'apply_user' => $result->apply_user,
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => $result->remark ? trim($result->remark) : '',
            ];
            //申请后，资产状态置为4，无法被变更，防止资产此时被变更，导致无法找到管理员,同时记录申请人只change_user字段
            app($this->assetsRepository)->updateData(['status' => 4, 'change_user' => $result->apply_user], ['id' => $result->assets_id]);
            app($this->assetsRecordsRepository)->insertData($recordData); //记录资产申请记录
            $toUsers = explode(',', $data['managers']);
            $apply_name = app($this->userRepository)->getUserName($result->apply_user);
            $assets_name = app($this->assetsRepository)->getDetail($data['assets_id'])['assets_name'];
            foreach ($toUsers as $key => $toUser) {
                $sendData['remindMark'] = 'assets-apply';
                $sendData['toUser'] = $toUser;
                $sendData['contentParam'] = ['assets_name' => $assets_name, 'user_name' => $apply_name];
                $sendData['stateParams'] = ['id' => $result->id];
                Eoffice::sendMessage($sendData);
            }
        }
        return $result;
    }

    /**
     * 下拉框资产分类列表
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function selectType($data)
    {
        return app($this->assetsTypeRepository)->getType($this->parseParams($data));
    }

    /**
     * 资产分类table列表
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function typeList($param)
    {
        return $this->response(app($this->assetsTypeRepository), 'typeListTotal', 'typeList', $this->parseParams($param));
    }

    /**
     * 资产条码规则生成
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function getRuleSet($type,$id = null,$import = false)
    {
        $res = app($this->assetsRuleSettingRepository)->getData();
        if (!$res) {
            return ['code' => ['setrule', 'assets']];
        }
        if ($res) {
            $res['order'] = $res['order'] ? unserialize($res['order']) : '';
            $res['field'] = $res['field'] ? unserialize($res['field']) : '';
            $res['length'] = intval($res['length']);
        }
        $str = '';
        $code['rulecode'] = '';
        if ($res['order'] && is_array($res['order'])) {
            foreach ($res['order'] as $key => $vo) {
                switch ($vo) {
                    case 'n':$str .= '0' . $type;
                        break;
                    case 'y':$str .= date('Y', time());
                        break;
                    case 'm':$str .= date('m', time());
                        break;
                    case 'd':$str .= date('d', time());
                        break;
                }
            }
            if($import){
                if(Redis::exists(self::RS_NUMBER_PREFIX)){
                    $latestId = Redis::get(self::RS_NUMBER_PREFIX);
                }else{
                    $latestId = app($this->assetsRepository)->assetsLatest();
                    Redis::set(self::RS_NUMBER_PREFIX,$latestId);
                    Redis::expire(self::RS_NUMBER_PREFIX,60);
                }
            }else{
                $latestId = app($this->assetsRepository)->assetsLatest();
            }
            $length = $latestId ? (strlen($latestId + 1)) : 1; //999时下一位是1000，长度应该是4
            $zero = '';
            for ($i = 0; $i < $res['length'] - $length; $i++) {
                $zero .= 0;
            }
            if($id){
                $str .= $zero . $id;
            }else{
                $str .= $zero . (($latestId ? $latestId : 0) + 1);
            }
            $code['rulecode'] = $str;
        }

        return $code;
    }

    /**
     * 资产条码规则设置
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function setCode($param,$own)
    {
//        if ($param['order'] == '') {
//            return ['code' => ['error_code', 'assets']];
//        }
        $res = app($this->assetsRuleSettingRepository)->getData();
        $fields = $order = $fieldArray = [];

        preg_match_all('/<(\S*?) [^>]*>(.*?)<\/\1>/', $param['order'], $orderMatch);
        if ($orderMatch[0] && is_array($orderMatch[0])) {
            foreach ($orderMatch[0] as $key => $value) {
                //生成规则处理
                preg_match('/id=[\'"](.*?)[\'"]/', $value, $idMatch);
                if ($idMatch && $idMatch[1] == 'number') {
                    preg_match('/data-number=[\'"](.*?)[\'"]/', $value, $numbers);
                    $param['length'] = intval($numbers[1]);
                } else {
                    $param['length'] = 5;
                }
                ($idMatch && !is_numeric($idMatch[1])) && $order[] = $idMatch[1];
                $orders[] = $idMatch;
            }
        }
//        if (!$order) {
//            return ['code' => ['assets_rule', 'assets']];
//        }
        preg_match_all('/<(\S*?) [^>]*>(.*?)<\/\1>/', $param['field'], $fieldsMatch);
        if ($fieldsMatch[0] && is_array($fieldsMatch[0])) {
            foreach ($fieldsMatch[0] as $ky => $item) {
                //生成显示字段处理
                preg_match('/id=[\'"](.*?)[\'"]/', $item, $fieldMatch);
                $fieldMatch && $fields[] = $fieldMatch[1];
            }
        }

        $fieldArray = [];
        if ($fields && is_array($fields)) {
            foreach ($fields as $kk => $vv) {
                $fieldArray = array_merge([$vv], $fieldArray);

            }
        }
        $fieldArray = array_unique($fieldArray);
        $res->order = $order ? serialize($order) : '';
        $res->field = serialize($fieldArray);
        $res->preview_order = $param['order'] ? $param['order'] : '';
        $res->preview_field = $param['field'] ? $param['field'] : '';
        $res->is_code = $param['is_code'] ? intval($param['is_code']) : 0;
        $res->length = $param['length'] ? intval($param['length']) : 5;
        !empty($res) && $res->created_at = date('Y-m-d H:i:s', time());
        empty($res) && $res->updated_at = date('Y-m-d H:i:s', time());
        return $res->save();
    }

    /**
     * 资产条码规则设置
     *
     * @author zw
     *
     * @since  2018-03-29 创建
     */
    public function getRule()
    {
        $result = app($this->assetsRuleSettingRepository)->getData();
        if ($result) {
            $result['field'] = $result['field'] ? unserialize($result['field']) : '';
            $result['order'] = $result['order'] ? unserialize($result['order']) : '';
            $result['is_code'] = $result['is_code'] ? intval($result['is_code']) : 0;
            $result['length'] = $result['length'] ? intval($result['length']) : 0;
        }
        $fieldsList = app($this->formModelingService)->listCustomFields(['search' => []], 'assets');
        $fields = $field_code = $result['fields'] = [];
        if ($fieldsList) {
            foreach ($fieldsList as $key => $vo) {
                $field_options = json_decode($vo->field_options, 1);
                if (in_array($vo->field_code, $result['field']) || $field_options['type'] == 'tabs') {
                    $vo->field_options = $field_options;
                    $fields[] = (array) $vo->toArray();
                    $field_code[] = $vo->field_code;
                }
            }
        }
        $result['fields'] = $fields;
        return $result ?: [];
    }

    /**
     * 申请记录
     *
     * @author zw
     *
     * @since  2018-06-06 创建
     */
    public function getApplyList($data, $own = null)
    {
        $param = $this->parseParams($data);
        if (isset($param['sign']) && $param['sign'] == 'approval') {
            $param['search']['managers'] = [$own['user_id'], 'like'];
        }
        if (isset($param['sign']) && $param['sign'] == 'apply') {
            $param['search']['apply_user'] = [$own['user_id']];
        }

        if (isset($param['search']['assets_code']) && $param['search']['assets_code']) {
            $param['senior']['assets_code'] = $param['search']['assets_code'];
            unset($param['search']['assets_code']);
        }
        if (isset($param['search']['assets_name']) && $param['search']['assets_name']) {
            $param['senior']['assets_name'] = $param['search']['assets_name'];
            unset($param['search']['assets_name']);
        }
        if (isset($param['search']['type']) && $param['search']['type']) {
            $param['senior']['type'] = $param['search']['type'];
            unset($param['search']['type']);
        }
        $result = $this->response(app($this->assetsApplysRepository), 'applysListsTotal', 'applysLists', $this->parseParams($param));
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $result['list'][$key]['assets']['managers'] = $vo['assets']['managers'] ? explode(',', $vo['assets']['managers']) : '';
                $result['list'][$key]['apply_name'] = $vo['apply_user'] ? app($this->userRepository)->getUserName($vo['apply_user']) : '';
                $result['list'][$key]['is_return'] = ($vo['return_at'] && $vo['return_at'] != '0000-00-00') ? true : false ;
//                $type_name = app($this->assetsTypeRepository)->getTypeData($vo['assets']['type']);
//                $result['list'][$key]['assets_type']['type_name'] = $type_name ? $type_name->type_name : trans('assets.delete_type');
            }
        }
        return $result;
    }

    /**
     * 申请记录详情
     *
     * @author zw
     *
     * @since  2018-06-06 创建
     */
    public function getApplyDetail($id)
    {
        $result = app($this->assetsApplysRepository)->ApplysDetail($id); //合并表
        if ($result && (is_array($result) || is_object($result))) {
            $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
            $result['attachment_id'] = $attachmentService->getAttachmentIdsByEntityId(['entity_table' => 'assets', 'entity_id' => $result['assets_id']]);
            $result['assets']['managers'] = $result['assets']['managers'] ? explode(',', $result['assets']['managers']) : '';
        }
        return $result;
    }

    /**
     * 申请删除
     *
     * @author zw
     *
     * @since  2018-06-07 创建
     */
    public function deleteApply($id)
    {
        if($apply = app($this->assetsApplysRepository)->getDetail($id)){
            if(($apply['status'] == 0 || $apply['status'] == 3 || $apply['status'] == 5)){

                $result = app($this->assetsApplysRepository)->deleteById($id);
                if ($apply['status'] == 0) {
                    //当未审核时，申请被删除
                    app($this->assetsRepository)->updateData(['status' => 0, 'change_user' => ''], ['id' => $apply['assets_id']]);
                }
                if (!$result) {
                    return ['code' => ['0x000003', 'common']];
                }
                return true;
            }
            return ['code' => ['cant_delete_data', 'assets']];
        };
        return ['code' => ['delete_type', 'assets']];
    }

    /**
     * 申请批准
     *
     * @author zw
     *
     * @since  2018-06-07 创建
     */
    public function approvalApply($id, $approvalData, $user_info)
    {
        $applyData = app($this->assetsApplysRepository)->getDetail($id); //合并表
        if (!$applyData) {
            return ['code' => ['0x043005', 'assetsstorage']];
        }

        if (isset($approvalData['status'])) {
            if (isset($approvalData['status']) && $approvalData['status']) {
                $applyData->status = $approvalData['status'];
            }
            if (isset($approvalData['approver']) && $approvalData['approver']) {
                $applyData->approver = $user_info['user_id'];
            }
            if (isset($approvalData['approval_opinion']) && $approvalData['approval_opinion']) {
                $applyData->approval_opinion = $approvalData['approval_opinion'];
            }
            if (isset($approvalData['updated_at']) && $approvalData['updated_at']) {
                $applyData->updated_at = $approvalData['updated_at'];
            }
            if (isset($approvalData['approvalData']) && $approvalData['approvalData']) {
                $applyData->approvalData = $approvalData['approvalData'];
            }

            if ($approvalData['status'] == 2) {
                //当status == 2时(已批准) ，同时变更assets里资产状态，防止同一资产被重复使用
                app($this->assetsRepository)->updateData(['status' => 1], ['id' => $applyData['assets_id']]);
            }
            if ($approvalData['status'] == 3) {
                //当status == 3时(未通过) ，同时变更assets里资产状态为正常使用，申请人为空
                app($this->assetsRepository)->updateData(['status' => 0, 'change_user' => ''], ['id' => $applyData['assets_id']]);
            }
            $returnData = $applyData->save();
            $apply_name = app($this->userRepository)->getUserName($applyData['apply_user']);
            if ($approvalData['status'] == 2) {
                //审批通过提醒
                $sendData['remindMark'] = 'assets-pass';
                $sendData['toUser'] = $applyData['apply_user'];
                $sendData['contentParam'] = ['user_name' => $user_info['user_name']];
                $sendData['stateParams'] = ['id' => $id];
                Eoffice::sendMessage($sendData);
            }

            if ($approvalData['status'] == 3) {
                //审批未通过提醒
                $sendData['remindMark'] = 'assets-refuse';
                $sendData['toUser'] = $applyData['apply_user'];
                $sendData['contentParam'] = ['user_name' => $user_info['user_name']];
                $sendData['stateParams'] = ['id' => $id];
                Eoffice::sendMessage($sendData);
            }
            return $returnData;
        }
    }

    /**
     * 申请归还/验收
     *
     * @author zw
     *
     * @since  2018-06-07 创建
     */
    public function returnApply($id, $approvalData, $user_info)
    {
        $applyData = app($this->assetsApplysRepository)->getDetail($id); //合并表
        if (!$applyData) {
            return ['code' => ['0x043005', 'assetsstorage']];
        }

        if ($approvalData['status']) {
            $applyData->status = $approvalData['status']; //4-已归还
            if ($approvalData['status'] == 5) {
                if (isset($approvalData['return_approver']) && $approvalData['return_approver']) {
                    $applyData->return_approver = $approvalData['return_approver']; //归还审批人
                    $applyData->real_return_at = date('Y-m-d H:i:s', time()); //确认归还日期
                }
            }
            $applyData->save();
            if ($approvalData['status'] == 5) {
                //当status == 5时(已归还) ，同时变更assets里资产status状态，使用人清空
                app($this->assetsRepository)->updateData(['status' => 0, 'change_user' => ''], ['id' => $applyData['assets_id']]);
            }
            $assetsData = app($this->assetsRepository)->getDetail($applyData['assets_id']);

            $apply_name = app($this->userRepository)->getUserName($applyData['apply_user']);
            $toUsers = explode(',', $applyData['managers']);
            if ($approvalData['status'] == 4) {
                if ($toUsers && is_array($toUsers)) {
                    foreach ($toUsers as $key => $toUser) {
                        $sendData['remindMark'] = 'assets-return';
                        $sendData['toUser'] = $toUser;
                        $sendData['contentParam'] = ['assets_name' => $assetsData['assets_name'], 'user_name' => $apply_name];
                        $sendData['stateParams'] = ['id' => $id];
                        Eoffice::sendMessage($sendData);
                    }
                }
            }
            if ($approvalData['status'] == 5) {
                $sendData['remindMark'] = 'assets-recovery';
                $sendData['toUser'] = $applyData['apply_user'];
                $sendData['contentParam'] = ['user_name' => $user_info['user_name']];
                $sendData['stateParams'] = ['id' => $id];
                Eoffice::sendMessage($sendData);
            }

        }
        return true;
    }

    /**
     * 资产变更
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function assetsChange($id, $changeData)
    {
        // 验证参数
        if($validate = $this->checkData($changeData)){
            return $validate;
        };
        $assetsData = app($this->formModelingService)->getCustomDataDetail('assets', $id);
        $this->saveOriginalData($id, $assetsData, $changeData); //保存原始数据到变更表
        if($code = $this->changeData($assetsData, $changeData)){ //变更数据到资产主表
            return $code;
        }
        $this->recordsChange($id, $changeData);
        //自定义页面处理，当变更了数据同步清除redis缓存数据，然后从新读取
        if (Redis::hexists('parse:data_select_systemData_assets', $id)) {
            Redis::hdel('parse:data_select_systemData_assets', $id);
        }
        return true;
    }

    private function checkData($data){
        if($data['residual_amount'] > $data['price']){
            return ['code' => ['error_price', 'assets']];
        }
        if (!isset($data['user_time']) || $data['user_time'] == '' || $data['user_time'] == 0) {
            return ['code' => ['user_time_not_null', 'assets']];
        }
        if($data['assets_code'] == ''){
            return ['code' => ['assets_code_not_null', 'assets']];
        }
        if(strlen($data['assets_code']) > 30){
            return ['code' => ['assets_code_length', 'assets']];
        }
    }

    private function saveCustomFields()
    {
        $primaryFields = DB::table('custom_fields_table')->select('field_table_key', 'field_code', 'field_data_type', 'field_options', 'field_directive')->where('field_table_key', 'assets')->where('field_code', 'like', '%' . 'sub_' . '%')->get()->toArray();
        $fields = Schema::getColumnListing('assets_change'); //获取变更表字段
        $assetsfields = Schema::getColumnListing('assets'); //获取资产主表字段
        $assetsFields = $changeFields = [];
        if ($fields) {
            foreach ($fields as $ks => $value) {
                if (substr($value, 0, 4) == 'sub_') {
                    $changeFields[] = $value;
                }
            }
        }
        if ($assetsfields) {
            foreach ($assetsfields as $vv) {
                if (substr($vv, 0, 4) == 'sub_') {
                    $assetsFields[] = $vv;
                }
            }
        }
        $diffs = array_diff($changeFields, $assetsFields); //change表与主表比较，若存在数据，自定义字段有删除数据，对应change表删除对应的字段
        if ($primaryFields && is_array($primaryFields)) {
            if (Schema::hasTable('assets_change')) {
                foreach ($primaryFields as $key => $vo) {
                    $field_options = json_decode($vo->field_options, 1);
                    if ($field_options['type'] == 'area' && $vo->field_directive == 'area') {
                        foreach ($field_options['relation'] as $k => $vv) {
                            if (!Schema::hasColumn('assets_change', $vv['field_code'])) {
                                Schema::table('assets_change', function ($table) use ($vv) {
                                    $table->string($vv['field_code'])->comment($vv['sourceValue'])->nullable();
                                });
                            }
                        }
                    } else {
                        if (!Schema::hasColumn('assets_change', $vo->field_code) && $vo->field_directive != 'area') {
                            Schema::table('assets_change', function ($table) use ($vo) {
                                switch ($vo->field_data_type) {
                                    case 'varchar':
                                        $table->string($vo->field_code)->comment('自定义字段')->nullable();
                                        break;
                                    case 'text':
                                        $table->text($vo->field_code)->comment('自定义字段')->nullable();
                                        break;
                                }
                            });
                        }
                    }
                }
            }
        }
        if ($diffs) {
            sort($diffs);
            //删除对应字段
            foreach ($diffs as $diff) {
                Schema::table('assets_change', function (Blueprint $table) use ($diff) {
                    $table->dropColumn($diff);
                });
            }
        }
    }

    /**
     * 资产变更列表
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function changeList($param, $own)
    {
        $param = $this->parseParams($param);
        $param['search']['managers'] = [$own['user_id'], 'like'];
        if (isset($param['senior']) && $param['senior']) {
            if (!is_array($param['senior'])) {
                $param['senior'] = json_decode($param['senior'], 1);
            }
        }
        if (isset($param['search']['assets_code']) && $param['search']['assets_code']) {
            $param['senior']['assets_code'] = $param['search']['assets_code'];
            unset($param['search']['assets_code']);
        }
        if (isset($param['search']['assets_name']) && $param['search']['assets_name']) {
            $param['senior']['assets_name'] = $param['search']['assets_name'];
            unset($param['search']['assets_name']);
        }
        if (isset($param['search']['type']) && $param['search']['type']) {
            $param['senior']['type'] = $param['search']['type'];
            unset($param['search']['type']);
        }
        $result = $this->response(app($this->assetsChangeRepository), 'changeListTotal', 'changeList', $param);

        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $result['list'][$key]['assets']['type_name'] = app($this->assetsTypeRepository)->getTypeData($vo['assets']['type'])['type_name'];
                $result['list'][$key]['assets']['change_name'] = $vo['assets']['change_user'] ? app($this->userRepository)->getUserName($vo['assets']['change_user']) : '';
            }
        }
        return $result;
    }

    private function recordsChange($id, $changeData)
    {
        $data = [
            'assets_id' => $id,
            'status' => 1, //变更没有状态，默认为1
            'apply_type' => 'change',
            'apply_user' => $changeData['change_user'],
            'create_time' => date('Y-m-d H:i:s', time()),
            'remark' => '',
        ];
        app($this->assetsRecordsRepository)->insertData($data); //记录资产变更记录
    }

    private function saveOriginalData($id, $assetsData, $changeData)
    {
        $originalData = app($this->assetsChangeRepository)->getChangeData($id);
        /*
        $keys = array_keys($assetsData->toArray());
        $CustomFields = $fieldsData = [];
        if ($keys && is_array($keys)) {
            foreach ($keys as $vv) {
                (substr($vv, 0, 3) == 'sub') && $CustomFields[] = $vv;
            }
        }
        $this->customFields = $CustomFields;
        //处理自定义数据
        if ($CustomFields && is_array($CustomFields)) {
            foreach ($CustomFields as $value) {
                $fieldsData[$value] = $assetsData[$value] ?: '';
            }
        }
        */
        $fields =  $assetsData;
        unset($fields['id']);
        if (empty($originalData)) {
            $saveData = [
                'assets_id' => $id,
                'bill' => $changeData['new_bill'],
                'assets_code' => $assetsData['assets_code'],
                'assets_name' => $assetsData['assets_name'],
                'managers' => $assetsData['managers'],
                'price' => $assetsData['price'],
                'product_at' => $assetsData['product_at'],
                'remark' => $assetsData['remark'],
                'residual_amount' => $assetsData['residual_amount'],
                'run_id' => is_array($assetsData['run_id']) ? implode(',',$assetsData['run_id']) : $assetsData['run_id'],
                'type' => $assetsData['type'],
                'is_all' => $assetsData['is_all'] ? intval($assetsData['is_all']) : 0,
                'dept' => $assetsData['dept'],
                'role' => $assetsData['role'],
                'users' => $assetsData['users'],
                'user_time' => $assetsData['user_time'],
                'change_user' => $changeData['change_user'],
                'created_at' => date('Y-m-d H:i:s', time()),
                'fields' => json_encode($fields),
            ];
//            $saveData = array_merge($saveData, $fieldsData);
            //如果更新资产之前没有更新过，则插入数据
            app($this->assetsChangeRepository)->insertData($saveData);
        } else {
            //如果更新资产之前有更新过，则更新数据
            $originalData->assets_id = $id;
            $originalData->bill = $changeData['new_bill'];
            $originalData->assets_code = $assetsData['assets_code'];
            $originalData->assets_name = $assetsData['assets_name'];
            $originalData->managers = $assetsData['managers'];
            $originalData->price = $assetsData['price'];
            $originalData->product_at = $assetsData['product_at'];
            $originalData->remark = $assetsData['remark'];
            $originalData->residual_amount = $assetsData['residual_amount'];
            $originalData->run_id = is_array($assetsData['run_id']) ? implode(',',$assetsData['run_id']) : $assetsData['run_id'];
            $originalData->type = $assetsData['type'];
            $originalData->is_all = $assetsData['is_all'] ? intval($assetsData['is_all']) : 0;
            $originalData->dept = $assetsData['dept'];
            $originalData->role = $assetsData['role'];
            $originalData->users = $assetsData['users'];
            $originalData->user_time = $assetsData['user_time'];
            $originalData->change_user = $changeData['change_user'];
            $originalData->created_at = date('Y-m-d H:i:s', time());
            $originalData->fields = json_encode($fields);
            //自定义字段信息
            /*
            if ($CustomFields && is_array($CustomFields)) {
                foreach ($CustomFields as $value) {
                    $originalData->$value = $assetsData->$value ? $assetsData->$value : '';
                }
            }
            */
            $originalData->save();
        }

    }

    private function changeData($assetsData, $changeData)
    {
        if ($assetsData) {
            //自定义字段数据更新
            $result = app($this->formModelingService)->editCustomData($changeData, 'assets', $assetsData['id']);
            if(isset($result['code'])){
                return $result;
            }
            $assetsData['change_user'] = $changeData['change_user'];
            app($this->assetsRepository)->updateData(['change_user'=>$changeData['change_user']],['id'=>$assetsData['id'] ]);
//            $assetsData->save();
        }
    }

    /**
     * 资产变更
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function getChangeList($data)
    {
        $result = $this->response(app($this->assetsChangeRepository), 'changeListsTotal', 'changeLists', $this->parseParams($data));
        return $result;
    }

    public function changeDetail($id)
    {
        $changeData = app($this->assetsChangeRepository)->getChangeData($id)->toArray();
        if ($changeData) {
            if(isset($changeData['fields']) && $changeData['fields']){
                $changeData['fields'] = json_decode($changeData['fields'],1);
                $returnData = $changeData['fields'];
                $returnData['id'] = $changeData['id'];
                $returnData['assets_id'] = $changeData['assets_id'];
                return $returnData;
            }else{
                $type = app($this->assetsTypeRepository)->getTypeData($changeData['type']);
                $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
                $changeData['attachment_id'] = $attachmentService->getAttachmentIdsByEntityId(['entity_table' => 'assets', 'entity_id' => $changeData['id']]);
                $changeData['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
                $changeData['dept'] = $changeData['dept'] ? explode(',', str_replace('"', '', $changeData['dept'])) : '';
                $changeData['role'] = $changeData['role'] ? explode(',', str_replace('"', '', $changeData['role'])) : '';
                $changeData['users'] = $changeData['users'] ? explode(',', str_replace('"', '', $changeData['users'])) : '';
                $changeData['managers'] = $changeData['managers'] ? explode(',', str_replace('"', '', $changeData['managers'])) : '';
                $changeData['run_ids'] = $changeData['run_id'] ? app($this->assetsFlowRepository)->getFlowRuns(explode(',', str_replace('"', '', $changeData['run_id']))) : '';
                foreach ($changeData as $kk => $vo) {
                    if (substr($kk, 0, 4) == 'sub_') {
                        $fieldData = DB::table('custom_fields_table')->where(['field_table_key' => 'assets', 'field_code' => $kk])->first();
                        if ($vo && $fieldData && ($fieldData->field_directive == 'selector' || $fieldData->field_directive == 'checkbox')) {
                            $changeData[$kk] = json_decode($vo, 1);
                        }
                    }

                    if (substr($kk, 0, 4) == 'sub_' && count(explode('_', $kk)) == 3) {
                        $sub[$kk] = $vo;
                        $keys = explode('_', $kk);
                        $areaKey = $keys[0] . '_' . $keys[1];
                        $changeData[$areaKey] = $sub;
                    }
                }
            }
            return $changeData;
        }
    }

    /**
     * 资产维护申请
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function creatRepair($data)
    {
        $result = app($this->assetsApplysRepository)->insertData($data);
        if ($result) {
            //对应资产状态置为2-维修中,同时记录操作人至change_user
            app($this->assetsRepository)->updateData(['status' => 2, 'change_user' => $result->operator], ['id' => $data['assets_id']]);
            $RecordData = [
                'assets_id' => $result->assets_id,
                'status' => 1,
                'apply_type' => 'repair',
                'apply_user' => $result->operator,
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => $result->remark ? trim($result->remark) : '',
            ];
            app($this->assetsRecordsRepository)->insertData($RecordData); //记录入库

            $assetsData = app($this->assetsRepository)->getDetail($result->assets_id);
            $sendData['remindMark'] = 'assets-repair';
            $sendData['toUser'] = $data['apply_user'];
            $sendData['contentParam'] = ['assets_name' => $assetsData['assets_name'], 'repairStatus' => trans('assets.report')];
            $sendData['stateParams'] = ['id' => $result->id];
            Eoffice::sendMessage($sendData);
            return $result;
        }
    }

    /**
     * 获取维护申请列表
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function repairList($data, $own)
    {
        $param = $this->parseParams($data);
        $param['search']['managers'] = [$own['user_id'], 'like'];

        if (isset($param['senior']) && $param['senior']) {
            $param['senior'] = json_decode($param['senior'], 1);
        }
        if (isset($param['search']['assets_code']) && $param['search']['assets_code']) {
            $param['senior']['assets_code'] = $param['search']['assets_code'];
            unset($param['search']['assets_code']);
        }
        if (isset($param['search']['assets_name']) && $param['search']['assets_name']) {
            $param['senior']['assets_name'] = $param['search']['assets_name'];
            unset($param['search']['assets_name']);
        }
        if (isset($param['search']['type']) && $param['search']['type']) {
            $param['senior']['type'] = $param['search']['type'];
            unset($param['search']['type']);
        }

        $result = $this->response(app($this->assetsApplysRepository), 'applysListsTotal', 'applysLists', $param);
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $result['list'][$key]['apply_user_name'] = $vo['apply_user'] ? app($this->userRepository)->getUserName($vo['apply_user']) : '';
                $result['list'][$key]['operator_name'] = $vo['operator'] ? app($this->userRepository)->getUserName($vo['operator']) : '';
            }
        }
        return $result;
    }

    /**
     * 获取维护申请详情数据
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function repairDetail($id)
    {
        $result = app($this->assetsApplysRepository)->ApplysDetail($id); //合并表
        if ($result) {
            $result['run_ids'] = $result['run_id'] ? app($this->assetsFlowRepository)->getFlowRuns(explode(',', $result['run_id'])) : [];
            $result['run_id'] = $result['run_id'] ? explode(',', $result['run_id']) : [];
        }

        return $result;
    }

    /**
     * 维护申请审批完成
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function repairEdit($id, $editData)
    {
        $repairData = app($this->assetsApplysRepository)->ApplysDetail($id); //合并表
        if (!$repairData) {
            return ['code' => ['0x043005', 'officesupplies']];
        }

        if ($repairData) {
            (isset($editData['repair_fee']) && $editData['repair_fee']) ? $repairData->repair_fee = $editData['repair_fee'] : $repairData->repair_fee = '';
            (isset($editData['status']) && $editData['status']) ? $repairData->status = $editData['status'] : $repairData->status = '';
            if(isset($editData['run_id']) && $editData['run_id']){
                if($repairData->run_id){
                    $mergeRunId = array_merge(explode(',',$repairData->run_id),explode(',',$editData['run_id']));
                    $repairData->run_id = implode(',',array_unique($mergeRunId));
                }else{
                    $repairData->run_id = $editData['run_id'];
                }
            }
            $repairData->save();
            //维修完成，同步assets资产表资产状态,操作人置为空
            $returnData = app($this->assetsRepository)->updateData(['status' => 0, 'change_user' => ''], ['id' => $editData['assets_id']]);
            $sendData['remindMark'] = 'assets-repair';
            $sendData['toUser'] = $repairData['apply_user'];
            $sendData['contentParam'] = ['assets_name' => $repairData['assets']['assets_name'], 'repairStatus' => trans('assets.repair_complete')];
            $sendData['stateParams'] = ['id' => $id];
            Eoffice::sendMessage($sendData);
            return $returnData;
        }
    }

    /**
     * 资产退库创建
     *
     * @author zw
     *
     * @since  2018-06-08 创建
     */
    public function createRetiring($data, $own)
    {
        $data['apply_user'] = $own['user_id'];
        $result = app($this->assetsApplysRepository)->insertData($data);
        if ($result) {
            app($this->assetsRepository)->updateData(['status' => 3], ['id' => $data['assets_id']]); //退库时，同步assets资产表资产状态
            $RecordData = [
                'assets_id' => $result->assets_id,
                'status' => 1, //退库不需要审批
                'apply_type' => 'retiring',
                'apply_user' => $result->apply_user,
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => $result->remark ? $result->remark : '',
            ];
            app($this->assetsRecordsRepository)->insertData($RecordData); //记录入库
            return $result;
        }
    }

    /**
     * 资产退库列表
     *
     * @author zw
     *
     * @since  2018-06-12 创建
     */
    public function retirList($data, $own)
    {
        $param = $this->parseParams($data);
        $param['search']['managers'] = [$own['user_id'], 'like'];
        if (isset($param['search']['assets_code']) && $param['search']['assets_code']) {
            $param['senior']['assets_code'] = $param['search']['assets_code'];
            unset($param['search']['assets_code']);
        }
        if (isset($param['search']['assets_name']) && $param['search']['assets_name']) {
            $param['senior']['assets_name'] = $param['search']['assets_name'];
            unset($param['search']['assets_name']);
        }
        if (isset($param['senior']) && $param['senior']) {
            if (!is_array($param['senior'])) {
                $param['senior'] = json_decode($param['senior'], 1);
            }
        }
//        return $this->response($this->assetsWithdrawalRepository, 'retirListTotal', 'retirList', $this->parseParams($param));
        $result = $this->response(app($this->assetsApplysRepository), 'applysListsTotal', 'applysLists', $this->parseParams($param));
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $result['list'][$key]['apply_user_name'] = $vo['apply_user'] ? app($this->userRepository)->getUserName($vo['apply_user']) : '';
            }
        }
        return $result;
    }

    /**
     * 资产退库详情
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function retirDetail($id)
    {
        $result = app($this->assetsApplysRepository)->ApplysDetail($id); //合并表
        if ($result) {
            $result['run_ids'] = $result['run_id'] ? app($this->assetsFlowRepository)->getFlowRuns(explode(',', $result['run_id'])) : '';
            $result['type'] = $result['type'] ? unserialize(Redis::hget($this->key, $result['type'])) : '';
        }
        return $result;
    }

    /**
     * 新增盘点
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function createInvent($data, $own)
    {
        $data['operator'] = $own['user_id'];
        $res = app($this->assetsInventoryRepository)->insertData($data);
        $this->addViewList($res);
        return true;
    }

    /**
     * 盘点列表
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function inventoryList($data)
    {
        $param = $this->parseParams($data);
        return $this->response(app($this->assetsInventoryRepository), 'listsTotal', 'lists', $this->parseParams($param));
    }

    /**
     * 盘点清单
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function viewList($id)
    {
        $result = app($this->assetsInventoryRepository)->getDetail($id);
        if ($result) {
            $type = app($this->assetsTypeRepository)->getTypeData($result['type']);
            $result['type_name'] = $type ? $type : trans('assets.delete_type');
            $result['manager'] = $result['managers'] ? app($this->userRepository)->getUserName($result['managers']) : '';
        }
        return $result;
    }

    /**
     * 盘点清单内容列表
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function getView($id, $data)
    {
        $params = $this->parseParams($data);
        $params = $this->parseViewData($params,'apply_user');
        if(empty($params)){
            return [];
        }
        if($recordData = DB::table('assets_inventory_records')->where('inventory_id', $id)->first()){
            return $recordData = $this->getCacheData($params, $id);
        }
        return [];
        /*
        $res = app($this->assetsInventoryRepository)->getDetail($id);
        if (!$res) {
            return ['code' => ['0x043005', 'assets']];
        }
        ($res->dept) && $res->depts = DB::table('user_system_info')->whereNull('deleted_at')->where('dept_id',$res->dept)->pluck('user_id')->toArray();
        $params['search'] = $res;
        $returnData = app($this->assetsRepository)->inViewList($params);
        if ($returnData && is_array($returnData)) {
            $insertData = [];
            foreach ($returnData as $key => $vo) {
                $insertData[$key]['assets_id'] = intval($vo['id']);
                $insertData[$key]['inventory_id'] = $id;
                $insertData[$key]['assets_name'] = trim($vo['assets_name']);
                $insertData[$key]['assets_code'] = trim($vo['assets_code']);
                $insertData[$key]['product_at'] = $vo['product_at'];
                $insertData[$key]['price'] = $vo['price'];
                $insertData[$key]['user_time'] = $vo['user_time'];
                $insertData[$key]['is_inventory'] = 0;
                $insertData[$key]['managers'] = $vo['managers'];
                $insertData[$key]['created_at'] = $vo['created_at'];
                $insertData[$key]['status'] = $vo['status'];
                $insertData[$key]['type'] = $vo['type'];
                if ($vo['status'] == 0) {
                    $insertData[$key]['apply_user'] = '';
                } else {
                    $insertData[$key]['apply_user'] = (isset($vo['change_user']) && $vo['change_user']) ? $vo['change_user'] : '';
                }
            }
            //记录当前盘点数据到盘点记录表
            if (array_chunk($insertData, 20) && is_array(array_chunk($insertData, 20))) {
                foreach (array_chunk($insertData, 20) as $kk => $item) {
                    app($this->assetsInventoryRecordsRepository)->insertMultipleData($item);
                }
            }
            $params['search'] = [];
            return $recordData = $this->getCacheData($params, $id);
        }else{
            $returnData = [
                'list' => [],
                'total'=> 0
            ];
            return $returnData;
        }
        */

    }

    public function parseViewData($params,$column){
        $user_id = $status = [];
        if (isset($params['search']['status'])){
            if(isset($params['search']['department']) ||isset($params['search'][$column]) ){
                if(!$status = array_intersect($params['search']['status'][0],[1,4])){
                    return [];
                };
            }
        }
        if(isset($params['search']['department'])){
            $user_id = DB::table('user_system_info')->whereNull('deleted_at')->whereIn('dept_id',$params['search']['department'][0])->pluck('user_id')->toArray();
            if(isset($params['search'][$column])){
                if(!$merge_user_id = array_intersect($user_id,$params['search'][$column][0])){
                    return [];
                };
                $params['search'][$column] = [$merge_user_id,'in'];
            }else{
                $params['search'][$column] = [$user_id,'in'];
            }
            $params['search']['status'] = $status ? [$status,'in'] : [[1,4],'in'];
            unset($params['search']['department']);
        }
        if(isset($params['search'][$column])){
            if($user_id){
                $user_id = array_intersect($user_id,$params['search'][$column][0]);
                if(!$user_id){
                    return [];
                }
                $params['search'][$column] = [$user_id,'in'];
            }
            $params['search']['status'] = $status ? [$status,'in'] : [[1,4],'in'];
        }
//        pre($params);
        return $params;



//        if(isset($params['search']['department']) && $params['search']['department']){
//            if($user_id = DB::table('user_system_info')->whereNull('deleted_at')->whereIn('dept_id',$params['search']['department'][0])->pluck('user_id')->toArray()){
//                if(isset($params['search']['apply_user']) && $params['search']['apply_user'][0]){
//                    $user_id = array_intersect($user_id,$params['search']['apply_user'][0]);
//                }
//                $params['search'][$column] = [$user_id,'in'];
//                unset($params['search']['department']);
//            }
//            if(!isset($params['search']['statusAssets'])){
//                $params['search']['status'] = [[1,4],'in'];
//            }
//
//        }
//        if(isset($params['search']['statusAssets'])){
//            if(isset($params['search']['status'])){
//                if($status = array_intersect($params['search']['statusAssets'][0],[1,4])){
//                    $params['search']['status'][0] = $status;
//                }else{
//                    $params['search']['status'] = [[1,4],'in'];
//                }
//            }else{
//                $params['search']['status'][0] = $params['search']['statusAssets'][0];
//            }
//            unset($params['search']['statusAssets']);
//        }
        return $params;
    }

    /**
     * 盘点清单内容列表
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function inventoryEdit($id, $data)
    {
        $params = $this->parseParams($data);
        $where = [
            'inventory_id' => $id,
            'assets_id' => $params['assets_id'],
        ];
        $assetsData = app($this->assetsRepository)->getDetail($where['assets_id']);
        if ($assetsData['status'] == 3) {
            return ['type' => 4];
        }
        $returnData = app($this->assetsInventoryRecordsRepository)->getInventory($where);
        if ($returnData) {
            if ($returnData->is_inventory == 1) {
                return ['type' => 2];
            } else {
                app($this->assetsInventoryRecordsRepository)->updateData(['is_inventory' => $params['is_inventory']], $where);
                return ['type' => 1];
            }
        } else {
            return ['type' => 3];
        }
    }

    private function getCacheData($params, $id)
    {
        $params['search'] = array_merge($params['search'], ['inventory_id' => [$id]]);
        $recordData = $this->response(app($this->assetsInventoryRecordsRepository), 'recordDataTotal', 'recordData', $params);
        if ($recordData && is_array($recordData)) {
            foreach ($recordData['list'] as $key => $vo) {
                $res = app($this->userRepository)->getUserAllData($vo['apply_user']);
                $recordData['list'][$key]['apply_user'] = $res ? $res->toArray() : '';
//                $recordData['list'][$key]['managers'] = $vo['managers'] ? app($this->userRepository)->getUserName($vo['managers']) : '';
//                $type = app($this->assetsTypeRepository)->getTypeData($vo['type']);
                $recordData['list'][$key]['type_name'] = $vo['assets_type'] ? $vo['assets_type']['type_name'] : trans('assets.delete_type');
            }
        }
        return $recordData;
    }

    /**
     * 清单履历列表
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function getResumeList($data)
    {
        $param = $this->parseParams($data);
        $result = $this->response(app($this->assetsRecordsRepository), 'listsTotal', 'lists', $this->parseParams($param));
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $result['list'][$key]['apply_user'] = $vo['apply_user'] ? app($this->userRepository)->getUserName($vo['apply_user']) : '';
            }
        }
        return $result;
    }

    /**
     * 对账表列表
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function accountList($data)
    {
        $data = $this->parseParams($data);
        if (isset($data['month']) && isset($data['year']) && $data['month'] && $data['year']) {
            $firstday = date('Y-m-01 00:00:00', strtotime($data['year'] . '-' . $data['month']));
            $lastday = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));
            $data['search']['created_at'] = [[$firstday, $lastday], 'between'];
        }
        if (isset($data['search']['date']) && $data['search']['date']) {
            $firstday = date('Y-m-d 00:00:00', strtotime($data['search']['date'][0]));
            $lastday = date('Y-m-d 23:59:59', strtotime($data['search']['date'][0]));
            $data['search']['created_at'] = [[$firstday, $lastday], 'between'];
            unset($data['search']['date']);
        }
        $result = $this->response(app($this->assetsRepository), 'accountTotal', 'accountlists', $this->parseParams($data));
        if ($result && is_array($result)) {
            foreach ($result['list'] as $key => $vo) {
                if($vo['expire_at'] == '0000-00-00' || !$vo['expire_at']){
                    $vo['expire_at'] = '2037-08-16';
                }
                //每个月均值折价
                $discount = sprintf("%.5f", (($vo['price'] - $vo['residual_amount']) / $vo['user_time']));
                //当该资产到期未退库
                if ($vo['status'] != 3 && (time() - (Carbon::parse($vo['expire_at'])->timestamp) > 0)) {
                    //使用月份
                    $result['list'][$key]['isuser_time'] = $vo['user_time'];
                    //本月折价
                    if ($this->getMonthNum($vo['expire_at'] ?? '2037-08-16', time()) > 0) {
                        $result['list'][$key]['discount'] = 0.00;
                        $result['list'][$key]['surplus_time'] = 0;
                    } else {
                        $result['list'][$key]['discount'] = sprintf("%.5f", $discount);
                        $result['list'][$key]['surplus_time'] = ($vo['user_time'] - $result['list'][$key]['isuser_time']) > 0 ? ($vo['user_time'] - $result['list'][$key]['isuser_time']) : 0;
                    }

                    //累计折价
                    $result['list'][$key]['alldiscount'] = sprintf('%.5f', $discount * $vo['user_time']);
                    //净残值
                    $result['list'][$key]['residual'] = $vo['residual_amount'];
                    $result['list'][$key]['user_time'] = $vo['user_time'];
                    $type = app($this->assetsTypeRepository)->getTypeData($vo['type']);
                    $result['list'][$key]['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
                } else if ($vo['status'] == 3) {
                    //当该资产已经退库，只统计到退库时间那天
                    //已使用月份                         2018-10-19     2018-09-20
                    if (strtotime($vo['assets_applys']['created_at']) > time()) {
                        //已使用时长
                        $result['list'][$key]['isuser_time'] = $this->getMonthNum($vo['product_at'] ?? '2037-08-16', time());
                        if ($this->getMonthNum($vo['assets_applys']['created_at'] ?? '2037-08-16', time()) > 0) {
                            //本月折价
                            $result['list'][$key]['discount'] = 0.00;
                        } else {
                            $result['list'][$key]['discount'] = sprintf("%.5f", $discount);
                        }
                        //累计折价
                        $result['list'][$key]['alldiscount'] = sprintf('%.5f', $discount * $result['list'][$key]['isuser_time']);
                        //净残值
                        $result['list'][$key]['residual'] = ($vo['price'] - $result['list'][$key]['alldiscount']) > 0 ? sprintf("%.5f", $vo['price'] - $result['list'][$key]['alldiscount']) : 0;
                        $result['list'][$key]['user_time'] = $vo['user_time'];
                        $result['list'][$key]['surplus_time'] = ($vo['user_time'] - $result['list'][$key]['isuser_time']) > 0 ? ($vo['user_time'] - $result['list'][$key]['isuser_time']) : 0;
                        $type = app($this->assetsTypeRepository)->getTypeData($vo['type']);
                        $result['list'][$key]['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
                    } else {
                        //已使用时长
                        $result['list'][$key]['isuser_time'] = $this->getMonthNum($vo['product_at'] ?? '2037-08-16', $vo['assets_applys']['created_at']);
                        //本月折价
                        if ($this->getMonthNum($vo['assets_applys']['created_at'] ?? '2037-08-16', time()) > 0) {
                            $result['list'][$key]['discount'] = 0.00;
                            $result['list'][$key]['surplus_time'] = 0;
                        } else {
                            $result['list'][$key]['discount'] = sprintf("%.5f", $discount);
                            $result['list'][$key]['surplus_time'] = ($vo['user_time'] - $result['list'][$key]['isuser_time']) > 0 ? ($vo['user_time'] - $result['list'][$key]['isuser_time']) : 0;
                        }

                        //累计折价
                        $result['list'][$key]['alldiscount'] = sprintf('%.5f', $discount * $result['list'][$key]['isuser_time']);
                        //净残值
                        $result['list'][$key]['residual'] = ($vo['price'] - $result['list'][$key]['alldiscount']) > 0 ? sprintf("%.5f", $vo['price'] - $result['list'][$key]['alldiscount']) : 0;
                        $result['list'][$key]['user_time'] = $vo['user_time'];
                        $type = app($this->assetsTypeRepository)->getTypeData($vo['type']);
                        $result['list'][$key]['type_name'] = $type ? $type->type_name : trans('assets.delete_type');

                    }
                } else {
                    //使用月份
                    $result['list'][$key]['isuser_time'] = $this->getMonthNum($vo['product_at'] ?? '2037-08-16', time());
                    //本月折价
                    $result['list'][$key]['discount'] = $discount;
                    //累计折价
                    $result['list'][$key]['alldiscount'] = sprintf("%.5f", ($result['list'][$key]['discount'] * $result['list'][$key]['isuser_time']));
                    //生产日期大于当前时间
                    if ($vo['product_at'] > date('Y-m-d', time())) {
                        //使用月份
                        $result['list'][$key]['isuser_time'] = 0;
                        //本月折价
                        $result['list'][$key]['discount'] = 0;
                        //累计折价
                        $result['list'][$key]['alldiscount'] = 0;
                    }
                    //净残值
                    $result['list'][$key]['residual'] = ($vo['price'] - $result['list'][$key]['alldiscount']) > 0 ? sprintf("%.5f", $vo['price'] - $result['list'][$key]['alldiscount']) : 0;
                    $result['list'][$key]['user_time'] = $vo['user_time'];
                    $result['list'][$key]['surplus_time'] = ($vo['user_time'] - $result['list'][$key]['isuser_time']) > 0 ? ($vo['user_time'] - $result['list'][$key]['isuser_time']) : 0;
                    $type = app($this->assetsTypeRepository)->getTypeData($vo['type']);
                    $result['list'][$key]['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
                }
            }
        }

        // 将价格类小数保留2位
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $item) {
                $result['list'][$key]['alldiscount'] = sprintf("%.2f", $item['alldiscount']);
                $result['list'][$key]['price'] = sprintf("%.2f", $item['price']);
                $result['list'][$key]['residual'] = sprintf("%.2f", $item['residual']);
                $result['list'][$key]['residual_amount'] = sprintf("%.2f", $item['residual_amount']);
            }
        }

        return $result;
    }

    /**
     * 折旧详情
     *
     * @author zw
     *
     * @since  2018-06-13 创建
     */
    public function accountDetail($id)
    {
        $result = app($this->assetsRepository)->accountDetail($id);
        //每个月均值折价
        if ($result) {
            $discount = sprintf("%.2f", (($result['price'] - $result['residual_amount']) / $result['user_time']));
            //当该资产到期未退库
            if ($result['status'] != 3 && (time() - strtotime($result['expire_at'])) > 0) {
                //使用月份
                $result['isuser_time'] = $result['user_time'];
                //本月折价
                $result['discount'] = 0.00;

                //本月折价
                if ($this->getMonthNum($result['expire_at'] ?? '2037-08-16', time()) > 0) {
                    $result['discount'] = 0.00;
                    $result['surplus_time'] = 0;
                } else {
                    $result['discount'] = $discount;
                    $result['surplus_time'] = ($result['user_time'] - $result['isuser_time']) > 0 ? ($result['user_time'] - $result['isuser_time']) : 0;
                }
                //累计折价
                $result['alldiscount'] = sprintf('%.2f', $discount * $result['user_time']);
                //净残值
                $result['residual'] = $result['residual_amount'];
                $type = app($this->assetsTypeRepository)->getTypeData($result['type']);
                $result['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
            } elseif ($result['status'] == 3) {
                //当该资产已经退库，只统计到退库时间那天
                //已使用月份                         2018-08-19     2018-09-20
                if (strtotime($result['assets_applys']['created_at']) > time()) {
                    //已使用时长
                    $result['isuser_time'] = $this->getMonthNum($result['product_at'] ?? '2037-08-16', time());
                    //本月折价
                    if ($this->getMonthNum($result['assets_applys']['created_at'] ?? '2037-08-16', time()) > 0) {
                        //本月折价
                        $result['discount'] = 0.00;
                    } else {
                        $result['discount'] = sprintf("%.2f", $discount);
                    }
                    //累计折价
                    $result['alldiscount'] = sprintf('%.2f', $discount * $result['isuser_time']);
                    //净残值
                    $result['residual'] = ($result['price'] - $result['alldiscount']) > 0 ? sprintf("%.2f", $result['price'] - $result['alldiscount']) : 0;
                    $result['surplus_time'] = ($result['user_time'] - $result['isuser_time']) > 0 ? (['user_time'] - $result['isuser_time']) : 0;
                    $type = app($this->assetsTypeRepository)->getTypeData($result['type']);
                    $result['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
                } else {
                    //已使用时长
                    $result['isuser_time'] = $this->getMonthNum($result['product_at'], $result['assets_applys']['created_at']);
                    //本月折价
                    $result['discount'] = 0.00;

                    //本月折价
                    if ($this->getMonthNum($result['assets_applys']['created_at'] ?? '2037-08-16', time()) > 0) {
                        $result['discount'] = 0.00;
                        $result['surplus_time'] = 0;
                    } else {
                        $result['discount'] = sprintf("%.2f", $discount);
                        $result['surplus_time'] = ($result['user_time'] - $result['isuser_time']) > 0 ? ($result['user_time'] - $result['isuser_time']) : 0;
                    }
                    //累计折价
                    $result['alldiscount'] = sprintf('%.2f', $discount * $result['isuser_time']);
                    //净残值
                    $result['residual'] = ($result['price'] - $result['alldiscount']) > 0 ? sprintf("%.2f", $result['price'] - $result['alldiscount']) : 0;
                    $type = app($this->assetsTypeRepository)->getTypeData($result['type']);
                    $result['type_name'] = $type ? $type->type_name : trans('assets.delete_type');

                }
            } else {
                //使用月份
                $result['isuser_time'] = $this->getMonthNum($result['product_at'] ?? '2037-08-16', time());
                //本月折价
                $result['discount'] = sprintf("%.2f", (($result['price'] - $result['residual_amount']) / $result['user_time']));
                //累计折价
                $result['alldiscount'] = sprintf("%.2f", ($result['discount'] * $result['isuser_time']));

                if ($result['product_at'] > date('Y-m-d', time())) {
                    //使用月份
                    $result['isuser_time'] = 0;
                    //本月折价
                    $result['discount'] = 0;
                    //累计折价
                    $result['alldiscount'] = 0;
                }
                //净残值
                $result['residual'] = ($result['price'] - $result['alldiscount']) > 0 ? sprintf("%.2f", $result['price'] - $result['alldiscount']) : 0;
                $result['surplus_time'] = ($result['user_time'] - $result['isuser_time']) > 0 ? ($result['user_time'] - $result['isuser_time']) : 0;
                $type = app($this->assetsTypeRepository)->getTypeData($result['type']);
                $result['type_name'] = $type ? $type->type_name : trans('assets.delete_type');
            }
        }
        return $result;
    }

    /**
     * 资产汇总
     *
     * @return array 资产汇总
     *
     * @author zw
     *
     * @since  2018-06-11
     */
    public function getSummary($data)
    {
        $typeData = $this->response(app($this->assetsTypeRepository), 'typeListTotal', 'typeList', $this->parseParams($data));
        $types = [];
        if ($typeData['list'] && is_array($typeData['list'])) {
            foreach ($typeData['list'] as $ky => $value) {
                $types[] = $value['id'];
            }
        }
        $params = [
            'search' => [
                'type' => [$types, 'in'],
            ],
        ];
        $res = app($this->assetsRepository)->summaryLists($params);
        $result['list'] = $this->processSummary($res, $types);
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $sort[] = $vo['index'];
                $result['list'][$key]['residual'] = sprintf("%.2f", $vo['residual']);
                $result['list'][$key]['total_price'] = sprintf("%.2f", $vo['total_price']);
            }
        }
        array_multisort($sort, SORT_ASC, $result['list']); //根据type进行从小到大排序
        $result['total'] = $typeData['total'];
        return $result;
    }

    /**
     * 将资产汇总运算
     *
     * @param $assestList
     * @param $typeList
     * @return array
     */
    private function processSummary($assetsList, $typeList)
    {
        $assetsSummaryByType = $assetsSummary = $types = $diffs = [];
        if ($assetsList && is_array($assetsList)) {
            // 遍历更新资产类型名 类型索引 累计折价 资产残值
            foreach ($assetsList as $key => $assets) {
                $types[] = $assets['type'];
                $assetsList[$key]['type_name'] = app($this->assetsTypeRepository)->getTypeData($assets['type'])->type_name;
                $assetsList[$key]['index'] = $assets['type'];

                // 已使用的月份，生产和现在相差几个月，返回整数
                $isUsedTime = $this->getMonthNum($assets['product_at'] ?? '2037-08-16', time());
                $isUsedTime = $isUsedTime > $assets['user_time'] ? $assets['user_time'] : $isUsedTime; // 如果已使用月份大于使用月限，则使用使用月限

                // 每月折价 后续需进行运算，为增加精确度，保留丝五位小数，上一层级再处理为两位
                $discount = sprintf("%.5f", (($assets['price'] - $assets['residual_amount']) / $assets['user_time']));

                // 累计折价
                $assetsList[$key]['alldiscount'] = $discount * $isUsedTime;

                // 资产残值
                $assetsList[$key]['residual'] = ($assets['price'] - $assetsList[$key]['alldiscount']) > 0 ? sprintf("%.4f", ($assets['price'] - $assetsList[$key]['alldiscount'])) : 0;
                $assetsSummaryByType[$assetsList[$key]['type']][] = $assetsList[$key];
            }

            // 按照type进行求和
            foreach ($assetsSummaryByType as $k => $item) {
                $assetsSummary[$k]['total_price'] = $assetsSummary[$k]['residual'] = 0;
                foreach ($item as $kk => $vv) {
                    $assetsSummary[$k]['type_name'] = $vv['type_name'];
                    $assetsSummary[$k]['index'] = $vv['index'];
                    $assetsSummary[$k]['count'] = count($item);
                    $assetsSummary[$k]['total_price'] += ($vv['price']);
                    $assetsSummary[$k]['residual'] += ($vv['residual']);
                }
            }
        }

        $diff = array_merge(array_diff($types, $typeList), array_diff($typeList, $types));
        if ($diff && is_array($diff)) {
            foreach ($diff as $dd => $value) {
                $diffs[] = [
                    'type_name' => app($this->assetsTypeRepository)->getTypeData($value)->type_name,
                    'index' => $value,
                    'count' => 0,
                    'total_price' => 0,
                    'residual' => 0,
                ];
            }
        }

        return array_merge($assetsSummary, $diffs);
    }

    /**
     * 二维码生成
     *
     * @return array 二维码生成
     *
     * @author zw
     *
     * @since  2018-06-19
     */
    public function assetsQrCode($id)
    {
        $qrcodePath = base_path('public/assets-qrcode/');
        if (!is_dir($qrcodePath)) {
            mkdir($qrcodePath, 0777);
            chmod($qrcodePath, 0777); //umask（避免用户缺省权限属性与运算导致权限不够）
        }
        $ids = is_array($id) ? $id : explode(',', $id);
        $qrCodeUrl = [];
        if ($ids && is_array($ids)) {
            $field = '';
            $fields = app($this->assetsRuleSettingRepository)->getData();
            $fields && $field = unserialize($fields['field']);
            foreach ($ids as $key => $vo) {
                $returnData = $this->getDisplay($vo, $field);
                $tempQRCode = $qrcodePath . $returnData['assets_code'] . '.png';

                $data = [
                    'mode' => 'router',
                    'body' => [
                        'commands' => ['assets/qrcode', $vo]
                    ],
                    'timestamp' => time(),
                    'ttl' => 0,
                    'assets_id' => $vo,
                    'sign' => 'assets'
                ];
                QrCode::format('png')->size(200)->encoding('UTF-8')->generate(json_encode($data), $tempQRCode);
                if (!file_exists($tempQRCode)) {
                    return ['code' => ['0x003021', 'auth']];
                }
                $qrCodeInfo = [
                    'assets_code' => $fields['is_code'] ? $returnData['assets_code'] : '',
                    'assets_name' => $returnData['assets_name'],
                    'qrcode' => imageToBase64($tempQRCode),
                ];
                $qrCodeUrl[] = $qrCodeInfo;
            }
            return $qrCodeUrl;
        }
    }

    private function getDisplay($vo, $field)
    {
        $result = app($this->assetsRepository)->assetsDetail($vo);
        $returnData = [];
        if($result){
            $returnData = [
                'assets_code' => $result['assets_code'],
                'assets_name' => $result['assets_name'],
            ];
        }
        return $returnData;
    }

    /**
     * 条形码生成
     *
     * @return array 条形码生成
     *
     * @author zw
     *
     * @since  2018-06-19
     */
//    public function assetsQrCode11($id){
    //        $qrcodePath = base_path('public/assets-qrcode/');
    //        if (!is_dir($qrcodePath)) {
    //            mkdir($qrcodePath, 0777);
    //            chmod($qrcodePath, 0777);//umask（避免用户缺省权限属性与运算导致权限不够）
    //        }
    //        $ids = is_array($id) ? $id : explode(',',$id);
    //        foreach ($ids as $key => $vo){
    //            $result = app($this->assetsRepository)->assetsDetail($vo);
    //            $qrcodeinfo = [
    //                'assets_code'=> $result['assets_code'],
    //                'qrcode' => "data:image/png;base64,".DNS1D::getBarcodePNG('22', "C39+"),
    //            ];
    //            $qrcodeurl[] = $qrcodeinfo;
    //        }
    //        return $qrcodeurl;
    //    }

    /**
     * 资产入库记录导出
     *
     * @return array 资产入库记录导出
     *
     * @author zw
     *
     * @since  2018-06-19
     */
    public function exportStorage($param)
    {
        $own = $param['user_info']['user_info'];
        return app($this->formModelingService)->exportFields('assets', $param, $own, trans('assets.storage_custom_field'));
    }

    /**
     * 资产申请记录导出
     *
     * @return array 资产申请记录导出
     *
     * @author zw
     *
     * @since  2018-06-19
     */
    public function exportApply($param)
    {
        $applyData = $this->getApplyList($param, $param['user_info']);
        $header = [
            'assets_name' => ['data' => trans('assets.assets_name'), 'style' => ['width' => '30']],
            'assets_code' => ['data' => trans('assets.assets_code'), 'style' => ['width' => '30']],
            'type' => ['data' => trans('assets.type'), 'style' => ['width' => '15']],
            'bill' => ['data' => trans('assets.bill'), 'style' => ['width' => '15']],
            'status' => ['data' => trans('assets.status'), 'style' => ['width' => '15']],
            'receive_at' => ['data' => trans('assets.receive_at'), 'style' => ['width' => '15']],
            'apply_way' => ['data' => trans('assets.apply_way'), 'style' => ['width' => '15']],
        ];
        $data = [];
        if ($applyData['list'] && is_array($applyData['list'])) {
            foreach ($applyData['list'] as $key => $vo) {
                $data[$key]['assets_name'] = $vo['assets']['assets_name'];
                $data[$key]['assets_code'] = $vo['assets']['assets_code'];
                $data[$key]['type'] = $vo['assets_type']['type_name'];
                $data[$key]['bill'] = $vo['bill'];
                $data[$key]['status'] = $this->getStatus($vo['status']);
                $data[$key]['receive_at'] = $vo['receive_at'];
                $data[$key]['apply_way'] = $vo['apply_way'] ? trans('assets.transfers') : trans('assets.apply');
            }
        }
        return compact('header', 'data');
    }

    private function getStatus($status)
    {
        switch ($status) {
            case 0:return trans('assets.not_approval');
                break;
            case 1:return trans('assets.approvaling');
                break;
            case 2:return trans('assets.pass');
                break;
            case 3:return trans('assets.noPass');
                break;
            case 4:return trans('assets.return');
                break;
            case 5:return trans('assets.checkd');
                break;

        }
    }

    /**
     * 资产申请审批导出
     *
     * @return array 资产申请记录导出
     *
     * @author zw
     *
     * @since  2018-06-19
     */
    public function exportApproval($param)
    {
        $approvalData = $this->getApplyList($param, $param['user_info']);
        $header = [
            'assets_name' => ['data' => trans('assets.assets_name'), 'style' => ['width' => '30']],
            'assets_code' => ['data' => trans('assets.assets_code'), 'style' => ['width' => '30']],
            'type' => ['data' => trans('assets.type'), 'style' => ['width' => '15']],
            'status' => ['data' => trans('assets.status'), 'style' => ['width' => '15']],
            'apply_user' => ['data' => trans('assets.applicant'), 'style' => ['width' => '15']],
            'receive_at' => ['data' => trans('assets.receive_at'), 'style' => ['width' => '15']],
            'return_at' => ['data' => trans('assets.return_at'), 'style' => ['width' => '15']],
            'real_return_at' => ['data' => trans('assets.real_return_at'), 'style' => ['width' => '15']],
            'apply_way' => ['data' => trans('assets.apply_way'), 'style' => ['width' => '15']],
        ];
        $data = [];
        if ($approvalData['list'] && is_array($approvalData['list'])) {
            foreach ($approvalData['list'] as $key => $vo) {
                $data[$key]['assets_name'] = $vo['assets']['assets_name'];
                $data[$key]['assets_code'] = $vo['assets']['assets_code'];
                $data[$key]['type'] = $vo['assets_type']['type_name'];
                $data[$key]['status'] = $this->getStatus($vo['status']);
                $data[$key]['apply_user'] = $vo['apply_user'] ? app($this->userRepository)->getUserName($vo['apply_user']) : '';
                $data[$key]['receive_at'] = $vo['receive_at'];
                $data[$key]['return_at'] = $vo['return_at'] == '0000-00-00' ? '' : $vo['return_at'];
                $data[$key]['real_return_at'] = $vo['real_return_at'] == '0000-00-00 00:00:00' ? '' : $vo['real_return_at'];
                $data[$key]['apply_way'] = $vo['apply_way'] ? trans('assets.transfers') : trans('assets.apply');
            }
        }
        return compact('header', 'data');
    }

    public function exportRepair($param){
        $result = $this->repairList($param,$param['user_info']);
        $header = [
            'assets_name' => ['data' => trans('assets.assets_name'), 'style' => ['width' => '30']],
            'assets_code' => ['data' => trans('assets.assets_code'), 'style' => ['width' => '30']],
            'bill' => ['data' => trans('assets.repair_number'), 'style' => ['width' => '30']],
            'apply_user_name' => ['data' => trans('assets.report_user'), 'style' => ['width' => '15']],
            'status' => ['data' => trans('assets.status'), 'style' => ['width' => '15']],
            'operator_name' => ['data' => trans('assets.operator_user'), 'style' => ['width' => '15']],
            'created_at' => ['data' => trans('assets.operator_time'), 'style' => ['width' => '15']],
            'repair_fee' => ['data' => trans('assets.repair_money'), 'style' => ['width' => '15']],
        ];
        $data = [];
        if($result['list'] && is_array($result['list'])){
            foreach ($result['list'] as $key => $item){
                $data[$key]['assets_name']     = $item['assets']['assets_name'];
                $data[$key]['assets_code']     = $item['assets']['assets_code'];
                $data[$key]['bill']            = $item['bill'];
                $data[$key]['apply_user_name'] = $item['apply_user_name'];
                $data[$key]['status']          = $item['status'] ? trans('assets.completed') : trans('assets.repairing');
                $data[$key]['operator_name']   = $item['operator_name'];
                $data[$key]['created_at']      = $item['created_at'];
                $data[$key]['repair_fee']      = $item['repair_fee'];
            }
        }
        return compact('header', 'data');
    }

    /**
     * 折旧对账表导出
     *
     * @return array 折旧对账表导出
     *
     * @author zw
     *
     * @since  2018-06-19
     */
    public function exportAccount($param)
    {
        $result = $this->accountList($param);
        $header = [
            'assets_name' => ['data' => trans('assets.assets_name'), 'style' => ['width' => '30']],
            'assets_code' => ['data' => trans('assets.assets_code'), 'style' => ['width' => '30']],
            'type' => ['data' => trans('assets.type'), 'style' => ['width' => '15']],
            'price' => ['data' => trans('assets.original_price'), 'style' => ['width' => '15']],
            'discount' => ['data' => trans('assets.discount'), 'style' => ['width' => '15']],
            'alldiscount' => ['data' => trans('assets.alldiscount'), 'style' => ['width' => '15']],
            'residual' => ['data' => trans('assets.residual'), 'style' => ['width' => '15']],
            'user_time' => ['data' => trans('assets.user_times'), 'style' => ['width' => '15']],
            'isuser_time' => ['data' => trans('assets.isuser_time'), 'style' => ['width' => '15']],
            'surplus_time' => ['data' => trans('assets.surplus_time'), 'style' => ['width' => '15']],
        ];
        $data = [];
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $data[$key]['assets_name'] = $vo['assets_name'];
                $data[$key]['assets_code'] = $vo['assets_code'];
                $data[$key]['type'] = $vo['type_name'];
                $data[$key]['price'] = $vo['price'];
                $data[$key]['discount'] = $vo['discount'];
                $data[$key]['alldiscount'] = $vo['alldiscount'];
                $data[$key]['residual'] = $vo['residual'];
                $data[$key]['user_time'] = $vo['user_time'];
                $data[$key]['isuser_time'] = $vo['isuser_time'];
                $data[$key]['surplus_time'] = $vo['surplus_time'];
            }
        }
        return compact('header', 'data');
    }

    /**
     * 资产盘点列表导出
     *
     * @return array 资产盘点列表导出
     *
     * @author zw
     *
     * @since  2018-06-19
     */
    public function exportInventory($param)
    {
        $result = $this->getView($param['id'], $param);
        if(!$result){
            return [];
        }
        $newData = [];
        foreach ($result['list'] as $key => $vo) {
            $newData[$key]['is_inventory'] = $vo['is_inventory'] == 0 ? trans('assets.not_inventory') : trans('assets.alerady_inventory');
            $newData[$key]['assets_name'] =  $vo['assets'] ? $vo['assets']['assets_name'] : '';
            $newData[$key]['assets_code'] = $vo['assets'] ? $vo['assets']['assets_code'] : '';
            $newData[$key]['apply_user'] = (isset($vo['apply_user']) && $vo['apply_user']) ? $vo['apply_user']['user_name'] : '';
            $newData[$key]['apply_department'] = (isset($vo['apply_user']) && $vo['apply_user']) ? $vo['apply_user']['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] : '';
            $newData[$key]['status'] = $this->getInvStatus($vo['status']);
            $newData[$key]['type'] = $vo['type_name'];
        }
        // 处理一下数组，后面数组合并用
        $id = array_column($result['list'],'assets_id');
        $param['search']['id'] = [$id, 'in'];
        $own         = $param['user_info'];
        $exportDatas = app($this->formModelingService)->exportFields('assets', $param, $own);
        if (empty($exportDatas) || !isset($exportDatas['header']) || !isset($exportDatas['data'])) {
            return $exportDatas;
        }
        $header = [
            'assets_name' => ['data' => trans('assets.assets_name'), 'style' => ['width' => '30']],
            'assets_code' => ['data' => trans('assets.assets_code'), 'style' => ['width' => '30']],
            'apply_user' => ['data' => trans('assets.now_receiver'), 'style' => ['width' => '30']],
            'apply_department' => ['data' => trans('assets.apply_department'), 'style' => ['width' => '30']],
            'status' => ['data' => trans('assets.status'), 'style' => ['width' => '30']],
            'type' => ['data' => trans('assets.due_to_type'), 'style' => ['width' => '15']],
            'is_inventory' => ['data' => trans('assets.inventory_status'), 'style' => ['width' => '15']],
        ];
        $header      = array_merge($exportDatas['header'], $header);
        $datas        = $exportDatas['data'];
        $data = [];
        foreach($newData as $ks => $item){
            foreach($datas as $value){
                if(($value['assets_code'] == $item['assets_code']) && ($value['assets_name'] == $item['assets_name'])){
                    $data[$ks] = array_merge($item,$value);
                }
            }
        }
        return compact('header', 'data');
    }

    private function getInvStatus($status)
    {
        $statusStr = '';
        switch ($status) {
            case 0:$statusStr = trans('assets.normal_use');
                break;
            case 1:$statusStr = trans('assets.no_return');
                break;
            case 2:$statusStr = trans('assets.repairing');
                break;
            case 3:$statusStr = trans('assets.alerady_withdraw');
                break;
            case 4:$statusStr = trans('assets.approvaling');
                break;
        }
        return $statusStr;
    }

    // 资产编码重复检测
    public function getDetailByCode($code){
        if($code){
            return app($this->assetsRepository)->getDetailByCode(['assets_code'=>$code]);
        }
        return '';
    }

    private function parseCode(){
        if(Redis::exists(self::RS_NUMBER_PREFIX)){
            $latestId = Redis::get(self::RS_NUMBER_PREFIX);
        }else{
            $latestId = app($this->assetsRepository)->assetsLatest();
            Redis::set(self::RS_NUMBER_PREFIX,$latestId);
            Redis::expire(self::RS_NUMBER_PREFIX,60);
        }
    }
    /**
     * 导入数据处理
     *
     * @return array
     *
     * @author zw
     *
     * @since 2017-12-19
     */
    public function importStorageFilter($data, $param)
    {
        if ($data && is_array($data)) {
            $codeFlag = false;
            $types = $autoTempCodes = $handTempCodes = [];
            $typeData = app($this->assetsTypeRepository)->getType();
            $res = app($this->assetsRuleSettingRepository)->getData();
            if($res && $res['order'] == ''){
                $codeFlag = true;
            }

            if ($typeData && is_array($typeData)) {
                foreach ($typeData as $items) {
                    $types[] = $items['id'];
                }
            }
            $model = app($this->formModelingService);
            foreach ($data as $key => $vo) {
                if(empty($data[$key]['assets_code']) && $codeFlag){
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.assets_code_empty'));
                    continue;
                }
                // 为空自动生成一个编码
                if(empty($data[$key]['assets_code'])){
                    $data[$key]['assets_code'] = $this->getRuleSet($vo['type'],null,true)['rulecode'];
                    if($autoTempCodes && in_array($data[$key]['assets_code'],$autoTempCodes)){
                        if(Redis::incr(self::RS_NUMBER_PREFIX)){
                            $data[$key]['assets_code'] = $this->getRuleSet($vo['type'],null,true)['rulecode'];;
                        }
                    }
                    // 缓存编码数组，判断导入编码是否重复
                    $autoTempCodes[] = $data[$key]['assets_code'];
                }else{
                    // 不为空判断编码是否重复
                    if($handTempCodes && in_array($data[$key]['assets_code'],$handTempCodes)){
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans('assets.assets_code_repeat'));
                        continue;
                    }
                    $handTempCodes[] = $data[$key]['assets_code'];
                }
                // 判断资产编码是否重复
                if($this->getDetailByCode($data[$key]['assets_code'])){
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.assets_code_repeat'));
                    continue;
                }

                if($vo['residual_amount'] > $vo['price']){
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.error_price'));
                    continue;
                }
                if($vo['user_time'] == 0 || $vo['user_time'] == ''){
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.error_user_time'));
                    continue;
                }
                if (isset($vo['run_id']) && $vo['run_id']) {
                    //判断是否是合法的流程id
                    $run_ids = explode(',', trim(str_replace('，', ',', $vo['run_id']), ','));
                    $strs = '';
                    foreach ($run_ids as $ks => $id){
                        $search = [
                            'user_id' => $param['user_info']['user_id'],
                            'run_id' => $id,
                            'type' => 'view'
                        ];
                        $ownRunIds = app($this->flowPermissionService)->verifyFlowHandleViewPermission($search);
                        if(!$ownRunIds){
                            $strs .= ',' . $id;
                        }
                    }
                    if($strs){
                        $errorArray = [
                            'false' => true,
                            'error_id' => trim($strs, ','),
                        ];
                    }
                    if (isset($errorArray['false'])) {
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($errorArray['error_id'] . '-' . trans('assets.error_run_id'));
                        continue;
                    }
                }
                $created_at = date('Y-m-d', time());
                if (strtotime(date('Y-m-d', strtotime($vo['product_at']))) !== strtotime($vo['product_at'])) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.date_error'));
                    continue;
                }

                if ($created_at < $vo['product_at']) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.error_product_time'));
                    continue;
                }
                if (!in_array(intval($vo['type']), $types)) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.type_null'));
                    continue;
                }
                // 缓存编码数组，判断导入编码是否重复
                if($data[$key]['assets_code']){
                    $tempCodes[] = $data[$key]['assets_code'];
                }
                $result = $model->importDataFilter('assets', $data[$key], $param);
                if ($result) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail($result);
                    continue;
                }
                $data[$key]['importResult'] = importDataSuccess();
            }
        }
        return $data;
    }


    /**
     * 资产导入回调
     *
     * @return array
     *
     * @author zw
     *
     * @since 2017-12-19
     */
    public function afterImport($importData){
        $expire_at = Carbon::parse($importData['data']['product_at'])->addMonths($importData['data']['user_time'])->toDateString();
        $assets_code = $this->getRuleSet($importData['data']['type'],$importData['id']);
        list($assets_name_py,$assets_name_zm) = convert_pinyin($importData['data']['assets_name']);
        $upData = [
            'expire_at' => $expire_at,
//            'assets_code'=>$assets_code['rulecode'],
            'assets_name_py' => $assets_name_py,
            'assets_name_zm' => $assets_name_zm,
        ];
        app($this->assetsRepository)->updateData($upData, ['id' => $importData['id']]);
        $recordData = [
            'assets_id' => $importData['id'],
            'status' => 1,
            'apply_type' => 'storage',
            'apply_user' => $importData['data']['operator_id'],
            'create_time' => $importData['data']['created_at'],
            'remark' => '',
        ];
        app($this->assetsRecordsRepository)->insertData($recordData); //记录入库
    }

    /**
     * 资产入库数据导入
     *
     * @return array
     *
     * @author zw
     *
     * @since 2017-12-19
     */
    public function importStorage($data, $param)
    {
        if ($data && is_array($data)){
            foreach ($data as $key => $vo){
                $data[$key]['registr_number'] = 'ZCDJ' . date('YmdHis', time()) . $this->createNonceStr(3);
                $data[$key]['assets_code'] = (isset($vo['assets_code']) && $vo['assets_code']) ? $vo['assets_code'] : $this->getRuleSet($vo['type'])['rulecode'];
                $data[$key]['user_time'] = $vo['user_time'] ? intval($vo['user_time']) : 60;
                $data[$key]['managers'] = str_replace('，', ',', $vo['managers']);
                $data[$key]['dept'] = $vo['dept'] ? trim(str_replace('，', ',', $vo['dept']), ',') : '';
                $data[$key]['users'] = $vo['users'] ? trim(str_replace('，', ',', $vo['users']), ',') : '';
                $data[$key]['role'] = $vo['role'] ? trim(str_replace('，', ',', $vo['role']), ',') : '';
                $data[$key]['remark'] = (isset($vo['remark']) && $vo['remark']) ? trim($vo['remark']) : '';
                $data[$key]['run_id'] = isset($vo['run_id']) ? trim(str_replace('，', ',', $vo['run_id']), ',') : '';
                $data[$key]['expire_at'] = Carbon::parse($vo['product_at'])->addMonths($data[$key]['user_time'])->toDateString();
                $data[$key]['operator_id'] = $param['user_info']['user_id'];
                $data[$key]['created_at'] =  date('Y-m-d H:i:s', time());
                $data[$key]['change_user'] = ''; //变更人员默认为空
                if ($data[$key]['is_all'] == 1) {
                    $data[$key]['dept'] = '';
                    $data[$key]['role'] = '';
                    $data[$key]['users'] = '';
                }
            }
            app($this->formModelingService)->importCustomData('assets', $data, $param);
            return ['data' => $data];
        }
        return ['data' => $data];
    }

    /**
     * 获取资产入库模板字段
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function getStorageFields($param)
    {
        return app($this->formModelingService)->getImportFields('assets', $param, trans("assets.assets_export_in"));
    }

    /**
     * 获取资产退库模板字段
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function getRetFields()
    {
        $template = [
            [
                'sheetName' => trans('assets.product_retiring_export_in'),
                'header' => [
                    'id' => ['data' => trans('assets.assets_id') . trans('assets.required'), 'style' => ['width' => '50']],
                    'remark' => ['data' => trans('assets.remark'), 'style' => ['width' => '20']],
                ],
            ],
        ];

        $params = [
            'fields' => ['id', 'type', 'assets_name', 'assets_code'],
            'page' => 0,
            'limit' => 10000,
            'search' => [
                'status' => [0],
            ],
            'order_by' => ['created_at' => 'desc'],
        ];
        $assetsInfos = app($this->assetsRepository)->assetsLists($params);
        $assetsInfo = [];
        if ($assetsInfos && is_array($assetsInfos)) {
            foreach ($assetsInfos as $key => $vo) {
                $assetsInfo[$key]['id'] = $vo['id'];
                $assetsInfo[$key]['assets_name'] = trim($vo['assets_name']);
//                $assetsInfo[$key]['assets_code'] = trim($vo['assets_code']);
                $assetsInfo[$key]['type'] = trim($vo['assets_type']['type_name']);

            }
        }
        $assetsArray = [
            [
                'sheetName' => trans('assets.assets_info'),
                'header' => [
                    'id' => ['data' => trans('assets.assets_id'), 'style' => ['width' => '30']],
                    'assets_name' => ['data' => trans('assets.assets_name'), 'style' => ['width' => '30']],
//                    'assets_code' => ['data' => trans('assets.assets_code'), 'style' => ['width' => '30']],
                    'type' => ['data' => trans('assets.due_to_type'), 'style' => ['width' => '30']],
                ],
                'data' => $assetsInfo,
            ],

        ];
        $arr = array_merge($template, $assetsArray);
        return $arr;
    }

    public function NumToStr($num)
    {
        if (stripos($num, 'e') === false) {
            return $num;
        }

        $num = trim(preg_replace('/[=\'"]/', '', $num, 1), '"');
        $result = "";
        while ($num > 0) {
            $v = $num - floor($num / 10) * 10;
            $num = floor($num / 10);
            $result = $v . $result;
        }
        return $result;
    }

    /**
     * 资产退库数据导入
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function importRet($data, $param)
    {
        if ($data && is_array($data)) {
            $info = ['success' => 0, 'error' => 0];
            foreach ($data as $key => $vo) {
                if (!$data[$key]['id']) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('assets.not_null'));
                    $info['error'] += 1;
                    continue;
                } else {
                    //根据资产编码获取资产是否存在
                    $assetsData = app($this->assetsRepository)->getAssetsData(['id' => $vo['id']]);
                    if ($assetsData && !in_array($param['user_info']['user_id'], explode(',', $assetsData->managers))) {
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans('assets.retring_operator_priv'));
                        $info['error'] += 1;
                        continue;
                    }
                    if (empty($assetsData) || $assetsData->status != 0) {
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans('assets.error_retire'));
                        $info['error'] += 1;
                        continue;
                    } else {
                        $filterData[$key]['assets_id'] = $assetsData->id;
                        $filterData[$key]['bill'] = 'ZCTK' . date('YmdHis', time()) . $this->createNonceStr(3);
                        $filterData[$key]['remark'] = $vo['remark'] ?? '';
                        $filterData[$key]['created_at'] = date('Y-m-d H:i:s', time());
                        $filterData[$key]['apply_user'] = $param['user_info']['user_id'];
                        $filterData[$key]['apply_type'] = 'retiring';
                        $filterData[$key]['run_id'] = '';
                        $filterData[$key]['type'] = $assetsData->type;
                        $filterData[$key]['status'] = 1; //退库
                        $filterData[$key]['managers'] = $assetsData->managers; //退库
                        $result = app($this->assetsApplysRepository)->insertData($filterData[$key]);
                        if ($result) {
                            app($this->assetsRepository)->updateData(['status' => 3], ['id' => $assetsData->id]); //退库时，同步assets资产表资产状态
                            $recordData = [
                                'assets_id' => $assetsData->id,
                                'status' => 1, //退库不需要审批
                                'apply_type' => 'retiring',
                                'apply_user' => $param['user_info']['user_id'],
                                'create_time' => date('Y-m-d H:i:s', time()),
                                'remark' => $result->remark ? trim($result->remark) : '',
                            ];
                            app($this->assetsRecordsRepository)->insertData($recordData); //记录入库
                        }
                        $info['success']++;
                        $data[$key]['importResult'] = importDataSuccess();
                    }
                }
            }
            return compact('data', 'info');
        }
        return ['data' => $data];
    }

    /**
     * 生成随机字符串
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function createNonceStr($length = '')
    {
        $chars = "0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 资产入库流程外发
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function assetStorageOutSend($assetsData)
    {
        if (isset($assetsData['data']) && isset($assetsData['tableKey'])) {
            $isSingle = true;
            $tableKey = isset($assetsData['tableKey']) ? $assetsData['tableKey'] : "";
            $currentUserId = isset($assetsData['current_user_id']) ? $assetsData['current_user_id'] : "";
            $outsource = $assetsData['data']['outsource'];
            if ($assetsData['data']) {
                $countMax = count($assetsData['data']) - 1; //获取数组内最大数组数量
                $returnDara = [];
                foreach ($assetsData['data'] as $key => $vo) {
                    $operator_id = $currentUserId ? $currentUserId : own()['user_id'];
                    //明细外发
                    if (is_array($vo) && isset($assetsData['data'][0])) {
                        $error = $this->checkFields($vo, $key + 1); //必填字段验证
                        if ($error) {
                            array_push($returnDara, ($key + 1));
                            if ($countMax == ($key + 1) && $returnDara) {
                                if (count($returnDara) == $countMax) {
                                    return ['code' => implode(',', $returnDara) . trans('assets.error_exit_all')];
                                }
                                return ['code' => implode(',', $returnDara) . trans('assets.error_exit')];
                            } else {
                                continue;
                            }
                        }

                        if (!isset($vo['type']) || $vo['type'] == null) {
                            $vo['type'] = 1;
                        }
                        $vo['assets_code'] = (isset($vo['assets_code']) && $vo['assets_code']) ? $vo['assets_code'] : $this->getRuleSet($vo['type'])['rulecode'];
                        if($vo['assets_code'] == ''){
                            return ['code' => ['assets_code_empty', 'assets']];
                        }
                        // 判断资产编码是否重复
                        if($this->getDetailByCode($vo['assets_code'])){
                            return ['code' => ['assets_code_repeat', 'assets']];
                        }
                        $created_at = (isset($vo['created_at']) && $vo['created_at']) ? $vo['created_at']: date('Y-m-d H:i:s', time());
                        $vo['created_at'] = $created_at;
                        $vo['operator_id'] = (isset($vo['operator_id']) && $vo['operator_id']) ? $vo['operator_id'] : $operator_id;
                        $vo['registr_number'] = 'ZCDJ' . date('YmdHis', time()) . $this->createNonceStr(3);

                        $vo['expire_at'] = Carbon::parse($vo['product_at'])->addMonths($vo['user_time'])->toDateString();

                        $vo['status'] = 0;
                        $vo['is_old'] = 0;
                        if (isset($vo['is_all']) && $vo['is_all'] && $vo['is_all'] == 1) {
                            $vo['dept'] = '';
                            $vo['role'] = '';
                            $vo['users'] = '';
                        }
                        $multiplyData = [
                            'data' => $vo,
                            'tableKey' => $tableKey,
                            'current_user_id' => $currentUserId,
                            'flowInfo' => (isset($assetsData['flowInfo']) && $assetsData['flowInfo']) ? $assetsData['flowInfo'] : '',
                        ];
                        $multiplyData['data']['outsource'] = $outsource;
                        $result = $this->insertassetsData($multiplyData);
                        $isSingle = false;
                        if ($countMax == ($key + 1) && $returnDara) {
                            return ['code' => trans('assets.next') . implode(',', $returnDara) . trans('assets.error_exit')];
                        }
                    }
                }
                if ($isSingle) {
                    //非明细外发(单外发)
                    //如果没有选择分类，默认分类为未分类
                    $error = $this->checkFields($assetsData['data']); //必填字段验证
                    if ($error) {
                        return $error;
                    }
                    if (!isset($assetsData['data']['type']) || $assetsData['data']['type'] == null) {
                        $assetsData['data']['type'] = 1;
                    }
                    $assetsData['data']['assets_code'] = (isset($assetsData['data']['assets_code']) && $assetsData['data']['assets_code']) ? $assetsData['data']['assets_code'] : $this->getRuleSet($assetsData['data']['type'])['rulecode'];

                    if($assetsData['data']['assets_code'] == ''){
                        return ['code' => ['assets_code_not_null', 'assets']];
                    }

                    // 判断资产编码是否重复
                    if($this->getDetailByCode($assetsData['data']['assets_code'])){
                        return ['code' => ['assets_code_repeat', 'assets']];
                    }

                    $operator_id = $currentUserId ? $currentUserId : own()['user_id'];
                    $assetsData['data']['created_at'] = (isset($assetsData['data']['created_at']) && $assetsData['data']['created_at']) ? date('Y-m-d H:i:s', strtotime($assetsData['data']['created_at'])) : date('Y-m-d H:i:s', time());
                    $assetsData['data']['operator_id'] = (isset($assetsData['data']['operator_id']) && $assetsData['data']['operator_id']) ? $assetsData['data']['operator_id'] : $operator_id;
                    $assetsData['data']['registr_number'] = 'ZCDJ' . date('YmdHis', time()) . $this->createNonceStr(3);
                    $assetsData['data']['expire_at'] = Carbon::parse($assetsData['data']['product_at'])->addMonths($assetsData['data']['user_time'])->toDateString();
                    $assetsData['data']['status'] = 0;
                    $assetsData['data']['is_old'] = 0;
                    if (isset($assetsData['data']['is_all']) && $assetsData['data']['is_all'] && $assetsData['data']['is_all'] == 1) {
                        $assetsData['data']['dept'] = '';
                        $assetsData['data']['role'] = '';
                        $assetsData['data']['users'] = '';
                    }
                    $result = $this->insertassetsData($assetsData);
                }
            }
        }
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'assets',
                    'field_to' => 'id',
                    'id_to' => $result
                ]
            ]
        ];
    }

    //必填字段验证
    private function checkFields($data, $key = null)
    {
        $result = [];
        if (!isset($data['assets_name']) || $data['assets_name'] == '') {
            $result = ['code' => ['assets_name_not_null', 'assets']];

        }
        if (!isset($data['product_at']) || $data['product_at'] == '') {
            $result = ['code' => ['product_time_not_null', 'assets']];
        }
        if (!isset($data['price']) || $data['price'] == '') {
            $result = ['code' => ['price_not_null', 'assets']];
        }

        if (!isset($data['user_time']) || $data['user_time'] == '' || $data['user_time'] == 0) {
            $result = ['code' => ['user_time_not_null', 'assets']];
        }

        if (!isset($data['residual_amount']) || $data['residual_amount'] == '') {
            $result = ['code' => ['residual_amount_not_null', 'assets']];
        }

        if (!isset($data['managers']) || $data['managers'] == '') {
            $result = ['code' => ['managers_not_null', 'assets']];
        }

        $created_at = (isset($data['created_at']) && $data['created_at']) ? date('Y-m-d', strtotime($data['created_at'])) : date('Y-m-d', time());
        if (isset($data['product_at']) && $data['product_at'] && (date('Y-m-d', strtotime($data['product_at'])) > $created_at)) {
            $result = ['code' => ['error_product_time', 'assets']];
        }

        if($data['residual_amount'] > $data['price']){
            return ['code' => ['error_price', 'assets']];
        }
        if ($key) {
            if ($result) {
                return $key;
            }
        } else {
            return $result;
        }
    }

    private function insertassetsData($insertData)
    {
        $result = app($this->formModelingService)->addCustomData($insertData['data'], 'assets');
        if(isset($result['code'])){
            return $result;
        }
//        file_put_contents('../storage/logs/debug.txt',var_export($result,1).PHP_EOL,FILE_APPEND);
        if ($result && is_numeric($result)) {
            //单独处理特殊字段
            list($assets_name_py,$assets_name_zm) = convert_pinyin($insertData['data']['assets_name']);
            $update = [
                'expire_at' => $insertData['data']['expire_at'],
                'run_id' => (isset($insertData['flowInfo']) && $insertData['flowInfo']) ? $insertData['flowInfo']['run_id'] : '',
                'dept' => (isset($insertData['data']['dept']) && $insertData['data']['dept']) ? $insertData['data']['dept'] : '',
                'role' => (isset($insertData['data']['role']) && $insertData['data']['role']) ? $insertData['data']['role'] : '',
                'users' => (isset($insertData['data']['users']) && $insertData['data']['users']) ? $insertData['data']['users'] : '',
                'managers' => (isset($insertData['data']['managers']) && $insertData['data']['managers']) ? $insertData['data']['managers'] : '',
                'assets_name_py' => $assets_name_py,
                'assets_name_zm' => $assets_name_zm,
            ];
            if (isset($insertData['data']['run_id']) && $insertData['data']['run_id']) {
                $update['run_id'] .= ',' . $insertData['data']['run_id'];
            }
            app($this->assetsRepository)->updateData($update, ['id' => $result]);

            $RecordData = [
                'assets_id' => $result,
                'status' => 1,
                'apply_type' => 'storage',
                'apply_user' => $insertData['current_user_id'],
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => '',
            ];
            app($this->assetsRecordsRepository)->insertData($RecordData); //记录入库
        }
        return $result;
    }

    /**
     * 资产申请流程外发申请记录
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function assetsApplyOutSend($data)
    {

        if (!isset($data['id']) || $data['id'] == '') {
            return ['code' => ['assets_name_not_null', 'assets']];
        }

        if (!isset($data['receive_at']) || $data['receive_at'] == '') {
            return ['code' => ['receive_at_not_null', 'assets']];
        }

        if(isset($data['apply_way']) && $data['apply_way'] != 1){
            if (!isset($data['return_at']) || $data['return_at'] == '') {
                return ['code' => ['return_at_not_null', 'assets']];
            }
        }
        
        if (!isset($data['apply_user']) || $data['apply_user'] == '') {
            return ['code' => ['apply_user_not_null', 'assets']];
        }
        if (!isset($data['approver']) || $data['approver'] == '') {
            return ['code' => ['approver_not_null', 'assets']];
        }
        if(!isset($data['apply_way']) && isset($data['return_at']) && !$data['return_at']){
            return ['code' => ['return_at_null', 'assets']];
        }
        if(isset($data['apply_way']) && $data['apply_way'] == 1){
            $data['return_at'] = '';
        }
        if (isset($data['current_user_id'])) {
            unset($data['current_user_id']);
        }
        if(isset($data['return_at']) && $data['return_at'] && $data['receive_at'] > $data['return_at']){
            return ['code' => ['error_return_at', 'assets']];
        }
        $array = explode(',',$data['approver']);
        if(count($array) > 1){
            return ['code' => ['err_number_approver', 'assets']];
        }else{
            $data['approver'] = reset($array);
        }
        $assetsData = app($this->assetsRepository)->getDetail($data['id']);

        if ($assetsData['status'] != 0) {
            return ['code' => ['not_apply_assets', 'assets']];
        }
        if (!in_array($data['approver'], explode(',', $assetsData['managers']))) {
            return ['code' => ['approver_error', 'assets']];
        }
        //验证该申请人是否有使用该资产的使用权限
        if ($assetsData['is_all'] == 0) {
            $assetsDept = $assetsData['dept'] ? explode(',', $assetsData['dept']) : [];
            $assetsRole = $assetsData['role'] ? explode(',', $assetsData['role']) : [];
            $assetsUsers = $assetsData['users'] ? explode(',', $assetsData['users']) : [];

            $result = app($this->userRepository)->getUserAllData($data['apply_user'])->toArray();
            if (!$result) {
                return ['code' => ['error_apply_user', 'assets']];
            }
            $permissDept = $permissUsers = $permissRole = '';
            $dept = $result['user_has_one_system_info']['dept_id'];
            $user_id = $result['user_id'];
            foreach ($result['user_has_many_role'] as $key => $vo) {
                if (in_array($vo['role_id'], $assetsRole)) {
                    $permissRole = true;
                };

            }
            $assetsDept && $permissDept = in_array($dept, $assetsDept);
            $assetsUsers && $permissUsers = in_array($user_id, $assetsUsers);
            if (!$permissDept && !$permissUsers && !$permissRole) {
                return ['code' => ['apply_user_error', 'assets']];
            }
        }
        $data['type'] = $assetsData['type'];
        $data['managers'] = $assetsData['managers'];
        $data['assets_id'] = intval($data['id']);
        $data['bill'] = 'ZCSQ' . date('YmdHis', time()) . $this->createNonceStr(3);
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $data['updated_at'] = date('Y-m-d H:i:s', time());
        $data['apply_type'] = 'apply';
        $data['approval_opinion'] = trans('assets.data_outsend_approval');
        $data['status'] = 2;
        $data['return_at'] = (isset($data['apply_way']) && $data['apply_way']) ? '' : $data['return_at'];
        if (isset($data['run_id'])) {
            $data['run_id'] = $data['run_id'];
        } else if (isset($data['run_ids'])) {
            $data['run_id'] = $data['run_ids'];
            unset($data['run_ids']);
        } else {
            $data['run_id'] = '';
        }
        $result = app($this->assetsApplysRepository)->insertData($data);
        if ($result) {
            $recordData = [
                'assets_id' => $result->assets_id,
                'status' => (isset($data['apply_way']) && $data['apply_way'] == 1) ? 2 : 1, // 调拨,
                'apply_type' => 'apply',
                'apply_user' => $result->apply_user,
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => $result->remark ? trim($result->remark) : '',
            ];
            app($this->assetsRepository)->updateData(['status' => 1, 'change_user' => $data['apply_user']], ['id' => $data['id']]);
            app($this->assetsRecordsRepository)->insertData($recordData); //记录资产申请记录
        }
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'assets_applys',
                    'field_to' => 'id',
                    'id_to' => $result['id']
                ]
            ]
        ];
    }

    /**
     * 资产维护外发
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function assetsRepairOutSend($data)
    {

        if (!isset($data['id']) || $data['id'] == '') {
            return ['code' => ['assets_name_not_null', 'assets']];
        }

        if (!isset($data['apply_user']) || $data['apply_user'] == '') {
            return ['code' => ['report_user_not_null', 'assets']];
        }

        if (!isset($data['operator']) || $data['operator'] == '') {
            return ['code' => ['operator_user_not_null', 'assets']];
        }

        if (isset($data['current_user_id'])) {
            unset($data['current_user_id']);
        }
        $assetsData = app($this->assetsRepository)->getDetail($data['id']);
        if(!in_array($data['operator'],explode(',',$assetsData['managers']))){
            return ['code' => ['operator_error', 'assets']];
        }
        $data['type'] = $assetsData['type'];
        $data['managers'] = $assetsData['managers'];
        $data['assets_id'] = intval($data['id']);
        $data['bill'] = 'ZCWH' . date('YmdHis', time()) . $this->createNonceStr(3);
        $data['apply_type'] = 'repair';
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $data['status'] = 0;
        $result = app($this->assetsApplysRepository)->insertData($data);
        if ($result) {
            app($this->assetsRepository)->updateData(['status' => 2, 'change_user' => $data['operator']], ['id' => $data['assets_id']]); //对应资产状态置为2-维修中
            $RecordData = [
                'assets_id' => $result->assets_id,
                'status' => 1,
                'apply_type' => 'repair',
                'apply_user' => $result->apply_user,
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => $result->remark ? trim($result->remark) : '',
            ];
            app($this->assetsRecordsRepository)->insertData($RecordData); //记录入库
            if(isset($result['code'])){
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'assets_applys',
                        'field_to' => 'id',
                        'id_to' => $result['id']
                    ]
                ]
            ];
        }
    }

    /**
     * 资产退库外发
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function assetsRetOutSend($data)
    {

        if (!isset($data['id']) || $data['id'] == '') {
            return ['code' => ['assets_name_not_null', 'assets']];
        }
        if (!isset($data['apply_user']) || $data['apply_user'] == '') {
            return ['code' => ['ret_user_not_null', 'assets']];
        }
        if (isset($data['current_user_id'])) {
            unset($data['current_user_id']);
        }
        $assetsData = app($this->assetsRepository)->getDetail($data['id']);
        if(!in_array($data['apply_user'],explode(',',$assetsData['managers']))){
            return ['code' => ['recovery_error', 'assets']];
        }
        $data['type'] = $assetsData['type'];
        $data['managers'] = $assetsData['managers'];
        $data['assets_id'] = intval($data['id']);
        $data['bill'] = 'ZCTK' . date('YmdHis', time()) . $this->createNonceStr(3);
        $data['apply_type'] = 'retiring';
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $result = app($this->assetsApplysRepository)->insertData($data);
        if ($result) {
            app($this->assetsRepository)->updateData(['status' => 3], ['id' => $data['assets_id']]); //退库时，同步assets资产表资产状态
            $RecordData = [
                'assets_id' => $result->assets_id,
                'status' => 1,
                'apply_type' => 'retiring',
                'apply_user' => $result->apply_user,
                'create_time' => date('Y-m-d H:i:s', time()),
                'remark' => $result->remark ? trim($result->remark) : '',
            ];
            app($this->assetsRecordsRepository)->insertData($RecordData); //记录入库
            if(isset($result['code'])){
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'assets',
                        'field_to' => 'id',
                        'id_to' => $result['id']
                    ]
                ]
            ];
        }
    }

    /**
     * 资产盘点外发
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function assetsInventoryOutSend($data)
    {
        if (!isset($data['name']) || $data['name'] == '') {
            return ['code' => ['inventory_name_not_null', 'assets']];
        }

        if (!isset($data['created_at']) || $data['created_at'] == '') {
            $data['created_at'] = Carbon::now()->toDateTimeString();
        } else {
            $data['created_at'] = Carbon::createFromTimestamp(strtotime($data['created_at']))->toDateTimeString();
        }
        if (!isset($data['operator']) || $data['operator'] == '') {
            $data['operator'] = $data['current_user_id'];
        }
        if (isset($data['current_user_id'])) {
            unset($data['current_user_id']);
        }
        if (isset($data['start_at']) && $data['start_at'] == '') {
            unset($data['start_at']);
        }
        if (isset($data['end_at']) && $data['end_at'] == '') {
            unset($data['end_at']);
        }
        $res = app($this->assetsInventoryRepository)->insertData($data);
        return $this->addViewList($res);


    }

    public function addViewList($res){
        if ($res) {
            $data = app($this->assetsInventoryRepository)->getDetail($res->id);
            ($data->dept) && $data->depts = DB::table('user_system_info')->whereNull('deleted_at')->where('dept_id',$data->dept)->pluck('user_id')->toArray();
            $params['search'] = $data;
            $returnData = app($this->assetsRepository)->inViewList($params);
            if ($returnData && is_array($returnData)) {
                $insertData = [];
                foreach ($returnData as $key => $vo) {
                    $insertData[$key]['assets_id'] = intval($vo['id']);
                    $insertData[$key]['inventory_id'] = $res->id;
                    $insertData[$key]['assets_name'] = trim($vo['assets_name']);
                    $insertData[$key]['assets_code'] = trim($vo['assets_code']);
                    $insertData[$key]['product_at'] = $vo['product_at'];
                    $insertData[$key]['price'] = $vo['price'];
                    $insertData[$key]['user_time'] = $vo['user_time'];
                    $insertData[$key]['is_inventory'] = 0;
                    $insertData[$key]['managers'] = $vo['managers'];
                    $insertData[$key]['created_at'] = $vo['created_at'];
                    $insertData[$key]['status'] = $vo['status'];
                    $insertData[$key]['type'] = $vo['type'];
                    if ($vo['status'] == 0) {
                        $insertData[$key]['apply_user'] = '';
                    } else {
                        $insertData[$key]['apply_user'] = (isset($vo['change_user']) && $vo['change_user']) ? $vo['change_user'] : '';
                    }
                }
                //记录当前盘点数据到盘点记录表
                if (array_chunk($insertData, 20) && is_array(array_chunk($insertData, 20))) {
                    foreach (array_chunk($insertData, 20) as $kk => $item) {
                        app($this->assetsInventoryRecordsRepository)->insertMultipleData($item);
                    }
                }
            }
            if(isset($res['code'])){
                return $res;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'assets_inventory',
                        'field_to' => 'id',
                        'id_to' => $res->id
                    ]
                ]
            ];
        }
    }

    /**
     * 扫码详情页
     *
     * @return array
     *
     * @author zw
     *
     * @since 2018-06-22
     */
    public function qrcodeDetail($id)
    {
        if($id){
            $result = app($this->formModelingService)->getCustomDataDetail('assets', $id);
            if ($result) {
                $type_name = DB::table('assets_type')->where('type',$result['type'])->value('type_name');
                $result['type_name'] = $type_name ? $type_name : trans('assets.delete_type');
                $result['dept'] = $result['dept'] ? explode(',', $result['dept']) : [];
                $result['users'] = $result['users'] ? explode(',', $result['users']) : [];
                $result['role'] = $result['role'] ? explode(',', $result['role']) : [];
                $result['status'] = DB::table('assets')->where('id',$id)->value('status');
                $res = app($this->assetsRuleSettingRepository)->getData();
                $field = $res['field'] ? unserialize($res['field']) : '';
                $result['fields'] = array_values($field);
                return $result;
            }
            return ['code'=>['delete_type','assets']];
        }
        return ['code'=>['not_find_assets','assets']];
    }

    public function customFields($params, $table)
    {
        $filterArray = [];
        $data = app($this->formModelingService)->listCustomFields($params, 'assets');
        if (isset($params['filters']['fields']) && $params['filters']['fields']) {
            $filterArray = explode(',', $params['filters']['fields']);
        }
        if ($data) {
            foreach ($data as $key => $vo) {
                $vo['field_options'] = json_decode($vo['field_options'], 1);
                if ($vo['field_options']['type'] == 'tabs' || in_array($vo['field_code'], $filterArray)) {
                    unset($data[$key]);
                    continue;
                }
                $data[$key] = $vo;
            }
        }
        $result = array_merge($data);
        return $result ?: [];
    }

    public function flowData(){
        $result = DB::table('assets_form_set')->select('*')->get()->toArray();
        foreach ($result as $key => $vo){
            $params = json_decode($vo->params,true);
            $result[$key]->fields = json_decode($vo->fields,true);
            //判断是否关联流程
            $result[$key]->is_relation_flow = (isset($params['flow_id']) && ($params['flow_id'])) ? 1 : 0;
        }
        return [
            'list' => $result,
            'total' => count($result)
        ];
    }

    public function getFlow($key,$params,$own){
        if($result = DB::table('assets_form_set')->where('key',$key)->first()){
            $result->fields = json_decode($result->fields,true);
            $result->params = $result->params ? json_decode($result->params,true) : '';
        }
        return $result ? $result :[];
    }

    public function addFlowDefine($key,$id,$params,$own){
        $result = $this->getFlow($key,$params,$own);
        $form_data = [];
        if(!$result->params || ($result->params['flow_id'] == '')){
            return ['code'=>['empty_form_params','assets']];
        }
        $assetsData = app($this->formModelingService)->getCustomDataDetail('assets', $id);;

        $result->creator = $own['user_id'];
        $flowRunName = $this->flowNewPageGenerateFlowRunName($result->params,$own['user_id']);
        if(!$flowRunName || $flowRunName == ''){
            $flowRunName = $this->getDefaultName($key);

        }
        array_map(function ($key,$column) use(&$form_data,$assetsData){
            $form_data += [$column => $assetsData[$key]];
        },$result->params['form_data'],array_keys($result->params['form_data']));

        $defaultData = [
            'creator'       => $own['user_id'],
            'create_type'   => '',              // 创建流程类型，可选参数，传"sonFlow"时，表明是创建子流程,
            'flow_id'       => $result->params['flow_id'],
            'flow_run_name' => $flowRunName,    // 流程标题
            'run_name_html' => '<div>'.$flowRunName.'</div>',
            'instancy_type' => 0,               // 紧急程度 0：正常、1：重要、2：紧急
            'agentType'     => 2,               // 用户在选择框里选择的委托处理方式。1：委托掉，2：保留
            'form_data'     => $form_data

        ];

        $returnData = app($this->flowService)->newPageSaveFlowInfo($defaultData, $own);
        return array_merge($returnData,['flow_id' =>$result->params['flow_id']]);
    }

    public function flowNewPageGenerateFlowRunName($data, $userId)
    {
        $userName = app($this->userRepository)->getUserName($userId);

        return app($this->flowRunService)->flowNewPageGenerateFlowRunName([
            'flow_id' => $data['flow_id'],
            'creator' => $userId,
            'user_name' => $userName,
            'form_data' => $data['form_data'],
            'form_structure' => '',//暂时先不传
        ]);
    }

    private function getDefaultName($key){
        switch ($key){
            case 'apply_create':return "资产申请外发";break;
            case 'repair_create':return "资产维护外发";break;
            case 'recycle_create':return "资产退库外发";break;
            default:return "资产外发";break;
        }
    }

    public function deleteStorage($id,$user_id = null){
        $own = own();
        $assetsData = app($this->formModelingService)->getCustomDataDetail('assets', $id);
        if(!$assetsData) return ['code' => ['0x024011', 'customer']];
        // 不是管理员无法删除
        $managers = is_array($assetsData['managers']) ? $assetsData['managers'] : explode(',',$assetsData['managers']);
        $userId = $user_id ? $user_id : $own['user_id'];
        if(!in_array($userId,$managers)){
            return ['code' => ['0x000017','common']];
        }
        $applyData = app($this->assetsApplysRepository)->existsApplyData($id);

        $changeData = app($this->assetsChangeRepository)->existsChangeData($id);
        if($applyData || $changeData){
            return ['code' => ['cant_delete_data','assets']];
        }

        return app($this->assetsRepository)->deleteById(['id'=>$id]);
    }

    // 资产管理外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }

        $own = own();
        $user_id = (isset($data['current_user_id']) && $data['current_user_id']) ? $data['current_user_id'] : $own['user_id'];
        $updateData = $data['data'] ?? [];
        $updateData['change_user'] = $user_id;
        $updateData['new_bill'] = 'ZCBG'.date('YmdHis').rand(100,999);
        unset($updateData['current_user_id']);
        // 外发更新，去除创建人，创建时间字段
        if(isset($updateData['operator_id'])){
            unset($updateData['operator_id']);
        }
        if(isset($updateData['created_at'])){
            unset($updateData['created_at']);
        }
        $assetsData = app($this->formModelingService)->getCustomDataDetail('assets', $data['unique_id']);
        if(!$assetsData){
            return ['code' => ['0x024011', 'customer']];
        }
        // 判断资产状态
        if($assetsData['status'] != 0){

            if($assetsData['status'] == 1){
                return ['code' => ['cant_change_status_1','assets']];
            }
            if($assetsData['status'] == 2){
                return ['code' => ['cant_change_status_2','assets']];
            }
            if($assetsData['status'] == 3){
                return ['code' => ['cant_change_status_3','assets']];
            }
            if($assetsData['status'] == 4){
                return ['code' => ['cant_change_status_1','assets']];
            }
        }

        // 不是管理员无法变更
        $managers = is_array($assetsData['managers']) ? $assetsData['managers'] : explode(',',$assetsData['managers']);
        if(!in_array($user_id,$managers)){
            return ['code' => ['0x000017','common']];
        }
        $this->saveOriginalData($data['unique_id'], $assetsData, $updateData); //保存原始数据到变更表
        $result = $this->changeData($assetsData, $updateData); //变更数据到资产主表
        if(isset($result['code'])){
            return $result;
        }
        $this->recordsChange($data['unique_id'], $updateData);
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'assets',
                    'field_to' => 'id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 资产管理外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $result = $this->deleteStorage($data['unique_id'],$data['current_user_id']);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'assets',
                    'field_to' => 'id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 资产申请删除
    public function flowOutApplyDelete($data) : array {
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $id = '';
        if(isset($data['data']) && $data['data']['current_user_id']){
            $id = $data['data']['current_user_id'];
        }
        $user_id = $own ? $own['user_id'] : $id;
        $result = app($this->assetsApplysRepository)->getDetail($data['unique_id']);
        //当资产申请用户删除数据时
        if(!$result) return ['code' => ['0x024011', 'customer']];

        $assetsData = app($this->assetsRepository)->getDetail($result['assets_id']);

        //0-待审核  3-未通过 5-已验收 这些情况可以删除
        if(($result->status ==3) || ($result->status ==5) || ($result->status ==0)){
            if(($result->apply_user == $user_id) || in_array($user_id,explode(',',$assetsData->managers))){
                $result = $this->deleteApply($data['unique_id']);
                if(isset($result['code'])){
                    return $result;
                }
                return [
                    'status' => 1,
                    'dataForLog' => [
                        [
                            'table_to' => 'assets',
                            'field_to' => 'contract_id',
                            'id_to'    => $data['unique_id']
                        ]
                    ]
                ];
            }
        }
        return ['code' => ['0x000006', 'common']];
    }
    
    public function dataSourceByAssetsId($data, $own){
        $assets_id = isset($data["assets_id"]) ? $data["assets_id"] : "";
        $name = [];
        if (empty($assets_id)) {
            return ["total" => 0, "list" => []];
        }
        $data = $this->parseParams($data);
        $params = [
            'fields' => ["id", "managers"],
            'search' => ["id" => [$assets_id]],
            'limit'  => 100,
        ];
        isset($data['search']) && $params['search'] = array_merge($data['search'],$params['search']);
        $result = $this->response(app($this->assetsRepository), 'assetsListsTotal', 'assetsLists', $this->parseParams($params));
        if($result['list']){
            $managers = explode(',',$result['list'][0]['managers']);
            if($managers){
                foreach ($managers as $manager){
                    $name[] =[
                        'id' => $manager,
                        'approver' => app($this->userRepository)->getUserName($manager)
                    ];
                }
            }

            return ["total" => count($managers), "list" => $name];
        }
        return ["total" => 0, "list" => []];
    }
    public function getManagerName($data){
        if(isset($data['search']['user_id']) && $data['search']['user_id'][0]){
            $result = [];
            array_map(function ($vo) use(&$result){
                $result[] = ['approver'=>app($this->userRepository)->getUserName($vo)];
            },$data['search']['user_id'][0]);
            return $result;
        }
        return [];
    }

    public function assetsAfterDetail($data){
        // 获取当前资产状态
        $data['status'] = AssetsRepository::getSignleFields('status',['id'=>$data['id']]);
        return $data;
    }

    public function deleteInventory($id){
        // 盘点列表删除
        app($this->assetsInventoryRepository)->deleteById(['id'=>$id]);

        // 删除已生成等盘点数据
        return app($this->assetsInventoryRecordsRepository)->deleteRecords($id);
    }

    public function applyWay($data = []){
        $applyWay = [trans('assets.apply'),trans('assets.transfers')];
        $result = [];
        if(isset($data['search']['id'])){
            if(($data['search']['id'][0])){
                array_map(function ($vo) use(&$result,$applyWay){
                    if($vo || $vo == '0'){
                        $result[] = ['name'=>$applyWay[$vo]];
                    }
                },$data['search']['id'][0]);
            }
            return $result;
        }else{
            return [
                ['id'=> 0,'name' => trans('assets.apply')],
                ['id'=> 1,'name' => trans('assets.transfers')],
            ];
        }
    }

    // 资产退库导出
    public function assetsExportRet($param){
        $result = $this->retirList($param, $param['user_info']);
        $header = [
            'assets_name' => ['data' => trans('assets.assets_name'), 'style' => ['width' => '30']],
            'assets_code' => ['data' => trans('assets.assets_code'), 'style' => ['width' => '30']],
            'created_at' => ['data' => trans('assets.withdrawing_time'), 'style' => ['width' => '30']],
            'apply_user' => ['data' => trans('assets.withdrawing_user'), 'style' => ['width' => '30']],
            'type' => ['data' => trans('assets.type'), 'style' => ['width' => '15']],
        ];
        $data = [];
        if ($result['list'] && is_array($result['list'])) {
            foreach ($result['list'] as $key => $vo) {
                $data[$key]['assets_name'] = $vo['assets']['assets_name'];
                $data[$key]['assets_code'] = $vo['assets']['assets_code'];
                $data[$key]['created_at'] = $vo['created_at'];
                $data[$key]['apply_user'] = $vo['apply_user_name'];
                $data[$key]['type'] = $vo['assets_type'] ? $vo['assets_type']['type_name'] : '';
            }
        }
        return compact('header', 'data');
    }
}
