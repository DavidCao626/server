<?php

namespace App\EofficeApp\System\CommonTemplate\Services;

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
class CommonTemplateService extends BaseService
{
    public function __construct()
    {
        $this->commonTemplateRepository = 'App\EofficeApp\System\CommonTemplate\Repositories\CommonTemplateRepository';
        $this->attachmentService        = 'App\EofficeApp\Attachment\Services\AttachmentService';
    }

    /**
     * 添加公共模板
     *
     * @param  array $data 公共模板数据
     *
     * @return int|array    添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function createCommonTemplate($data)
    {
        if(isset($data['attachment_id'])) {
            $data['template_picture'] = $data['attachment_id'][0];
            $attachementInfo = $data['attachment_id'];
            unset($data['attachment_id']);
        }
        if ($commonTemplateObj = app($this->commonTemplateRepository)->insertData($data)) {
            if(isset($attachementInfo) && $attachementInfo) {
                app($this->attachmentService)->attachmentRelation("common_template", $commonTemplateObj->template_id, $attachementInfo);
            }
            return $commonTemplateObj->template_id;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除公共模板
     *
     * @param   int     $templateId    公共模板id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function deleteCommonTemplate($templateId)
    {
        $templateIds = array_filter(explode(',', $templateId));

        if (app($this->commonTemplateRepository)->deleteById($templateIds)) {
            // 删除附件
            foreach($templateIds as $key=>$value) {
                $commonTemplateAttachmentData = ['entity_table' => 'common_template', 'entity_id' => $value];
                app($this->attachmentService)->deleteAttachmentByEntityId($commonTemplateAttachmentData);
            }
            return true;
        }
        return ['code' => ['0x000003','common']];
        return ['code' => ['0x000003','common']];
    }

    /**
     * 编辑公共模板数据
     *
     * @param   array   $input 编辑数据
     * @param   int     $templateId    公共模板id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function editCommonTemplate($templateId, $data)
    {
        if(isset($data['attachment_id']) && $data['attachment_id']) {
            $data['template_picture'] = $data['attachment_id'][0];
        }else{
            $data['template_picture'] = '';
        }
        if (app($this->commonTemplateRepository)->updateData($data, ['template_id' => $templateId]))
        {
            // 删除原来的附件
            if(isset($data['attachment_id'])) {
                if ($result = app($this->commonTemplateRepository)->getDetail($templateId)) {
                    $result = $result->toArray();
                    $result['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'common_template', 'entity_id'=>$result['template_id']]);
                    if($result['attachment_id'] != $data['attachment_id']) {
                        // 如果变更了附件，删除原来的附件
                        $commonTemplateAttachmentData = ['entity_table' => 'common_template', 'entity_id' => $templateId];
                        app($this->attachmentService)->deleteAttachmentByEntityId($commonTemplateAttachmentData);
                    }
                }
                app($this->attachmentService)->attachmentRelation("common_template", $templateId, $data['attachment_id']);
            }
            return true;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取公共模板详情
     *
     * @param   int     $id    公共模板id
     *
     * @return  array          查询结果或状态码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function getCommonTemplateDetail($templateId)
    {
        if ($result = app($this->commonTemplateRepository)->getDetail($templateId)) {
            $data = $result->toArray();
            $data['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'common_template', 'entity_id'=>$result['template_id']]);
            return $data;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取公共模板数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-01-22 创建
     */
    public function getCommonTemplateList($param = [])
    {
        $param = $this->parseParams($param);
        $data = $this->response(app($this->commonTemplateRepository), 'getCommonTemplateTotal', 'getCommonTemplateList', $param);
        if (!empty($data['list'])) {
            foreach ($data['list'] as $k => $v) {
                if(!isset($v['template_picture']) || empty($v['template_picture'])){
                    $data['list'][$k]['thumb_attachment_name'] = '';
                    continue;
                }
                $data['list'][$k]['thumb_attachment_name'] = app($this->attachmentService)->getThumbAttach($v['template_picture']);
//                $record = app($this->attachmentService)->getOneAttachmentById($v['template_picture']);
//                if (isset($record) && !empty($record)) {
//                    $path = getAttachmentDir() . $record['attachment_relative_path'] . $record['affect_attachment_name'];
//                    $data['list'][$k]['thumb_attachment_name'] = imageToBase64($path);
//                } else {
//                    $data['list'][$k]['thumb_attachment_name'] = '';
//                }
//                if(isset($v['attachment_name'])) {
//                    if(!empty($v['attachment_base_path'])) {
//                        // 兼容9.0升级过来的
//                        $path = $v['attachment_base_path'].$v['attachment_path'].$v['attachment_name'];
//                    }else{
//                        $fileName = !empty($v['thumb_attachment_name']) ? $v['thumb_attachment_name'] : $v['affect_attachment_name'];
//                        $path = getAttachmentDir().$v['attachment_path'].$fileName;
//                    }
//                    $data['list'][$k]['thumb_attachment_name'] = imageToBase64($path);
//                }else{
//                    $data['list'][$k]['thumb_attachment_name'] = '';
//                }
            }
        }
        return $data;
    }
     // 导入内容模板
     public function importContentTemplate($data) {
        $from = isset($data['from']) ? $data['from'] : '';
        if ($from == 'online') {
            if (isset($data['content']) && !empty($data['content'])) {
                $name = isset($data['content']['name']) && !empty($data['content']['name']) ? $data['content']['name'] : trans('portal.unknown');
                $fileContent = isset($data['content']['file_content']) ? $data['content']['file_content'] : '';
                return $this->handleImport($fileContent, $name);
            }
        } else {
            if (isset($data['attachment_id']) && !empty($data['attachment_id'])) {
                $attachmentFile = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
                $name = isset($attachmentFile['attachment_name']) && !empty($attachmentFile['attachment_name']) ? trim($attachmentFile['attachment_name'],'.style') : trans('portal.unknown');
                $fileContent = '';
                if (isset($attachmentFile['temp_src_file'])) {
                    $fileContent = convert_to_utf8(file_get_contents($attachmentFile['temp_src_file']));
                }
                return $this->handleImport($fileContent, $name);
            }
        }
        return ['code' => ['0x000003', 'common']];
    }
    private function handleImport($fileContent, $name) {
        list($templateName, $content, $description, $cover, $version) = $this->parseContent($fileContent);
        if ($cover) {
            $attachment = app($this->attachmentService)->base64Attachment([
                'image_file' => $cover, 
                'image_name' => $name,
                'attachment_table' => 'common_template'
            ], own()['user_id']);
            if (isset($attachment['attachment_id'])) {
                $cover = $attachment['attachment_id'];
            }
        }
        if (!$version) {
            return [ 'code' => ['0x041034', 'portal'], 'dynamic' => ['【'.$name.'】'.trans('document.0x041034')] ];
        }
        $result = $this->createCommonTemplate([
            'template_name' => $templateName, 
            'template_content' => $content,
            'template_description' => $description,
            'template_picture' => $cover
        ]);
        if (isset($result['code'])) {
            return $result;
        }
        if ($cover) {
            app($this->attachmentService)->attachmentRelation("common_template", $result, [$cover]);
        }
        return $result;
    }
    // 解析导入文件
    private function parseContent($fileContent) {
        $name = '';
        $content = '';
        $description = '';
        $cover = '';
        $version = '';
        if (!empty($fileContent)) {
            if (!is_array($fileContent)) {
                $fileContent = json_decode($fileContent, true);
            }
            $name = $fileContent['name'] ?? '';
            $content = $fileContent['content'] ?? '';
            $description = $fileContent['description'] ?? '';
            $cover = $fileContent['cover'] ?? '';
            $version = $fileContent['version'] ?? '';
        }
        /**
         * 当解析到版本时：
            * 素材版本大于当前e-office版本时，不允许导入，提示：素材版本不支持当前e-office版本，请升级当前e-office系统！
            * 素材版本小于或等于当前e-office版本时，允许正常导入。
         * 当未解析到版本时：
            * 直接导入
         */
        $version = empty($version) ? true : ($version > version() ? false : true);
        
        return [$name, $content, $description, $cover, $version];
    }
    // 导出样式模板
    public function exportContentTemplate($id) {
        $template = $this->getCommonTemplateDetail($id);
        if (isset($template['code'])) {
            return $template;
        }
        $cover = '';
        if (isset($template['attachment_id'])) {
            $attachmentId = $template['attachment_id'];
            if (!empty($attachmentId) && isset($attachmentId[0])) {
                $attachment = app($this->attachmentService)->getOneAttachmentById($attachmentId[0]);
                $cover = $attachment['thumb_attachment_name'] ?? '';
            }
        }
        return json_encode([
            'name' => $template['template_name'],
            'content' => $template['template_content'],
            'version' => version(),
            'cover' => $cover,
            'description' => $template['template_description']
        ]);
    }
}
