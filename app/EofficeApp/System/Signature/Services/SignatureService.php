<?php

namespace App\EofficeApp\System\Signature\Services;

use App;
use Eoffice;
use App\EofficeApp\Base\BaseService;

/**
 * 公共模板Service类:提供公共模板相关服务
 *
 * @author qishaobo
 *
 * @since  2016-01-22 创建
 */
class SignatureService extends BaseService
{
    public function __construct()
    {
        $this->signatureRepository = 'App\EofficeApp\System\Signature\Repositories\SignatureRepository';
        $this->attachmentService   = 'App\EofficeApp\Attachment\Services\AttachmentService';
    }

    /**
     * 添加印章
     *
     * @param  array $data 印章数据
     *
     * @return int|array    添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function createSignature($data)
    {
        if(isset($data['attachment_id'])) {
            $data['signature_picture'] = $data['attachment_id'][0];
            $attachementInfo = $data['attachment_id'];
            unset($data['attachment_id']);
        }
        if ($signatureObj = app($this->signatureRepository)->insertData($data)) {
            if(isset($attachementInfo) && $attachementInfo) {
                app($this->attachmentService)->attachmentRelation("signature", $signatureObj->signature_id, $attachementInfo);
            }
            return $signatureObj->signature_id;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除印章
     *
     * @param   int     $signatureId    印章id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function deleteSignature($signatureId)
    {
        $signatureIds = array_filter(explode(',', $signatureId));

        if (app($this->signatureRepository)->deleteById($signatureIds)) {
            // 删除附件
            foreach($signatureIds as $key=>$value) {
                $signatureAttachmentData = ['entity_table' => 'signature', 'entity_id' => $value];
                app($this->attachmentService)->deleteAttachmentByEntityId($signatureAttachmentData);
            }
            return true;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 编辑印章数据
     *
     * @param   array   $input 编辑数据
     * @param   int     $signatureId    印章id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function editSignature($signatureId, $data)
    {
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            $data['signature_picture'] = $data['attachment_id'][0];
        }else{
            $data['signature_picture'] = '';
        }
        if (app($this->signatureRepository)->updateData($data, ['signature_id' => $signatureId])) {
            if(isset($data['attachment_id'])) {
                if ($result = app($this->signatureRepository)->getDetail($signatureId)) {
                    $result = $result->toArray();
                    $result['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'signature', 'entity_id'=>$result['signature_id']]);
                    if($result['attachment_id'] != $data['attachment_id']) {
                        // 如果变更了附件，删除原来的附件
                        $signatureAttachmentData = ['entity_table' => 'signature', 'entity_id' => $signatureId];
                        app($this->attachmentService)->deleteAttachmentByEntityId($signatureAttachmentData);
                    }
                }
                app($this->attachmentService)->attachmentRelation("signature", $signatureId, $data['attachment_id']);
            }
            return true;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取印章详情
     *
     * @param   int     $id    印章id
     *
     * @return  array          查询结果或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function getSignatureDetail($signatureId, $loginUserInfo)
    {
        if ($result = app($this->signatureRepository)->getDetail($signatureId)) {
            $result = $result->toArray();
            if(isset($loginUserInfo) && !empty($loginUserInfo)) {
                // 20200225-dingpeng-去掉此处对所属者的判断，现在只要有菜单283的权限，就可以管理所有图片章。
                // if($result['signature_onwer'] != $loginUserInfo['user_id']) {
                //     return ['code' => ['0x000006','common']];
                // }
            }
            $result['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'signature', 'entity_id'=>$result['signature_id']]);
            return $result;
        }
        return ['code' => ['0x000006','common']];
    }

    /**
     * 获取印章数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function getSignatureList($param = [], $loginUserInfo = [])
    {
        $param = $this->parseParams($param);
        $data = array();
        if(isset($loginUserInfo) && !empty($loginUserInfo)) {
            $data = $this->response(app($this->signatureRepository), 'getSignatureTotal', 'getSignatureList', $param);
            if (!empty($data)) {
                foreach ($data['list'] as $k => $v) {
                    if (!isset($v['signature_picture']) || empty($v['signature_picture'])) {
                        $data['list'][$k]['thumb_attachment_name'] = '';
                        continue;
                    }
                    $data['list'][$k]['thumb_attachment_name'] = app($this->attachmentService)->getThumbAttach($v['signature_picture']);
                }
            }
        }
        return $data;
    }
}
