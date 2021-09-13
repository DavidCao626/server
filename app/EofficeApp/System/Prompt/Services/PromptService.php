<?php

namespace App\EofficeApp\System\Prompt\Services;

use App\EofficeApp\Base\BaseService;
use DB;

/**
 * 提示语Service类:提供提示语相关服务
 *
 * @author qishaobo
 *
 * @since  2016-12-28 创建
 */
class PromptService extends BaseService
{
    /**
     * 提示语资源
     * @var object
     */
    private $promptRepository;

    /**
     * 提示语类别资源
     * @var object
     */
	private $promptTypeRepository;

    public function __construct() {
        $this->promptRepository     = 'App\EofficeApp\System\Prompt\Repositories\PromptRepository';
    	$this->promptTypeRepository = 'App\EofficeApp\System\Prompt\Repositories\PromptTypeRepository';
    }

    /**
     * 获取提示语类别列表
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function getPromptTypes($param = [])
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->promptTypeRepository), 'getTotal', 'getPromptTypes', $param);
    }

    /**
     * 获取提示语类别详情
     *
     * @param  int  $typeid 提示语类别id
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function getPromptType($typeid, $param = [])
    {
        $param = $this->parseParams($param);
        return app($this->promptTypeRepository)->getPromptType($typeid, $param);
    }

    /**
     * 添加提示语类别
     *
     * @param   array  $data 提示语数据
     *
     * @return  int|array    添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function createPromptType($data)
    {

        if(!empty($data['prompt_type_name'])) {
            if ($tagObj = app($this->promptTypeRepository)->insertData($data)) {
                if ($tagObj) {
                    $type = [];
                    $type['prompt_content'] = '';
                    $type['prompt_type_id']  =  $tagObj->prompt_type_id ? $tagObj->prompt_type_id : '';
                    $type['handle'] = 'insert';
                    $this->createDefaultPrompt($type);
                }

                return $tagObj->prompt_type_id;

            }
        }else{
            return ['code' => ['0x015028','system']];
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 编辑提示语类别
     *
     * @param   array  $data 提示语数据
     * @param   int  $typeid 提示语类别id
     *
     * @return  array        成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function updatePromptType($data, $typeId)
    {
        if (app($this->promptTypeRepository)->updateData($data, ['prompt_type_id' => $typeId])) {
            return true;
        }
        return true;
        // return ['code' => ['0x000003','common']];
    }

    /**
     * 删除提示语类别
     *
     * @param   int  $typeid 提示语类别id
     *
     * @return array         成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function deletePromptType($typeId)
    {
        if (app($this->promptTypeRepository)->deleteById($typeId)) {
            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取提示语列表
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function getPrompts($param = [])
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->promptRepository), 'getTotal', 'getPrompts', $param);
    }

    /**
     * 获取登录提示语列表
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-30 创建
     */
    public function getLoginPrompts()
    {
        return app($this->promptRepository)->getLoginPrompts();
    }

    /**
     * 添加提示语
     *
     * @param  array $data  提示语数据
     *
     * @return int|array    添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function createPrompt($datas)
    {
        foreach ($datas as $input) {
            $data = [
                        'prompt_content'   =>  $input['prompt_content'],
                        'prompt_type_id'   =>  $input['prompt_type_id']
                    ];

            if (empty($data['prompt_content']) || empty($data['prompt_type_id']) || empty($input['handle'])) {
                continue;
            }

            if ($input['handle'] == 'insert') {
                app($this->promptRepository)->insertData($data);
            } else if ($input['handle'] == 'delete') {
                app($this->promptRepository)->deleteById($input['prompt_id']);
            } else if ($input['handle'] == 'update') {
                app($this->promptRepository)->updateData($data, ['prompt_id' => $input['prompt_id']]);
            }
        }

        return true;
    }
    public function createDefaultPrompt($datas)
    {
            $data = [
                        'prompt_content'   =>  $datas['prompt_content'],
                        'prompt_type_id'   =>  $datas['prompt_type_id']
                    ];
            if ($datas['handle'] == 'insert') {
                app($this->promptRepository)->insertData($data);
            } else if ($input['handle'] == 'delete') {
                app($this->promptRepository)->deleteById($datas['prompt_id']);
            } else if ($datas['handle'] == 'update') {
                app($this->promptRepository)->updateData($data, ['prompt_id' => $datas['prompt_id']]);
            }

        return true;
    }

    /**
     * 删除提示语
     *
     * @param   int     $id    提示语id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建

     */
    public function deletePrompt($id)
    {
        $detail = app($this->promptRepository)->getDetail($id);
        $typeId = isset($detail->prompt_type_id) ? $detail->prompt_type_id : '';
        if (app($this->promptRepository)->deleteById($id)) {
            $details = app($this->promptRepository)->getListByTypeId($typeId);
            if (empty($details)) {
                $type = [];
                $type['prompt_content'] = '';
                $type['prompt_type_id']  =  $typeId;
                $type['handle'] = 'insert';
                $this->createDefaultPrompt($type);
            }
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑提示语
     *
     * @param   array   $data  提示语数据
     * @param   int     $id    提示语id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function updatePrompt($data, $id)
    {
        if (app($this->promptRepository)->updateData($data, ['prompt_id' => $id])) {
            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取模块新手指引触发标识
     *
     * @param   $route     路由，用英文逗号分隔，这里用于标识在哪个页面
     *
     * @return  int        0:需要提示，1:不需要提示
     *
     * @author miaochenchen
     *
     * @since  2020-04-08 创建
     */
    public function getNewUserGuideFlag($userId, $route)
    {
        return DB::table('new_user_guide')->where('user_id', $userId)->where('route', $route)->count();
    }

    /**
     * 设置模块新手指引触发标识
     *
     * @param   {route}     路由，用英文逗号分隔，这里用于标识在哪个页面
     *
     * @return  boolean
     *
     * @author miaochenchen
     *
     * @since  2020-04-08 创建
     */
    public function setNewUserGuideFlag($data)
    {
        $userId = $data['user_id'];
        $route  = $data['route'];
        $newUserCount = DB::table('new_user_guide')->where('user_id', $userId)->where('route', $route)->count();
        if (!$newUserCount) {
            $insertData = [
                'user_id'          => $userId,
                'route'            => $route,
            ];
            return DB::table('new_user_guide')->insert($insertData);
        }
    }
}