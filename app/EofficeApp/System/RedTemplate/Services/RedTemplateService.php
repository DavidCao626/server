<?php

namespace App\EofficeApp\System\RedTemplate\Services;

use App;
use Eoffice;
use App\EofficeApp\Base\BaseService;

/**
 * 套红模板Service类:提供套红模板相关服务
 *
 * @author miaochenchen
 *
 * @since  2016-09-28 创建
 */
class RedTemplateService extends BaseService
{
    public function __construct()
    {
        $this->redTemplateRepository = 'App\EofficeApp\System\RedTemplate\Repositories\RedTemplateRepository';
        $this->attachmentService     = 'App\EofficeApp\Attachment\Services\AttachmentService';
    }

    /**
     * 添加套红模板
     *
     * @param  array $data 套红模板数据
     *
     * @return int|array    添加id或状态码
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    public function createRedTemplate($data)
    {
        if ($redTemplateObj = app($this->redTemplateRepository)->insertData($data)) {
            return $redTemplateObj->template_id;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除套红模板
     *
     * @param   int     $templateId    套红模板id
     *
     * @return  array          成功状态或状态码
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    public function deleteRedTemplate($templateId)
    {
        $templateIds = array_filter(explode(',', $templateId));
        $attachmentData = array('attach_ids' => array());
        foreach($templateIds as $key=>$value) {
            $redTemplateDetail = app($this->redTemplateRepository)->getDetail($value)->toArray();
            if(!empty($redTemplateDetail) && isset($redTemplateDetail['template_content']) && !empty($redTemplateDetail['template_content'])) {
                $attachmentData['attach_ids'][] = $redTemplateDetail['template_content'];
            }
        }
        if (app($this->redTemplateRepository)->deleteById($templateIds)) {
            // 删除附件
            if(!empty($attachmentData['attach_ids'])) {
                app($this->attachmentService)->removeAttachment($attachmentData);
            }
            return true;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 编辑套红模板数据
     *
     * @param   array   $input 编辑数据
     * @param   int     $templateId    套红模板id
     *
     * @return  array          成功状态或状态码
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    public function editRedTemplate($templateId, $data)
    {
        if (app($this->redTemplateRepository)->updateData($data, ['template_id' => $templateId]))
        {
            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取套红模板详情
     *
     * @param   int     $id    套红模板id
     *
     * @return  array          查询结果或状态码
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    public function getRedTemplateDetail($templateId)
    {
        $result = app($this->redTemplateRepository)->getDetail($templateId)->toArray();
        foreach($result as $key => $value) {
            if($key == 'dept_id' || $key == 'role_id') {
                if($value && $value != 'all') {
                    $result[$key] = explode(',', $value);
                    foreach($result[$key] as $k => $v) {
                        $result[$key][$k] = intval($v);
                    }
                }
            }elseif($key == 'user_id') {
                if($value && $value != 'all') {
                    $result[$key] = explode(',', $value);
                }
            }else{
                continue;
            }
        }
        return $result;
    }

    /**
     * 获取套红模板列表数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author miaochenchen
     *
     * @since  2016-09-28 创建
     */
    public function getRedTemplateList($param = [])
    {
        return $this->response(app($this->redTemplateRepository), 'getRedTemplateTotal', 'getRedTemplateList', $this->parseParams($param));
    }

    /**
     * 获取有权限的套红模板列表数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author nitianhua
     *
     * @since  2016-09-28 创建
     */
    public function getMyRedTemplateList($own, $param = [])
    {
        $param['own'] = $own;
        return $this->response(app($this->redTemplateRepository), 'getMyRedTemplateTotal', 'getMyRedTemplateList', $this->parseParams($param));
    }

}
