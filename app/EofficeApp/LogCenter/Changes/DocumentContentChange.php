<?php
namespace App\EofficeApp\LogCenter\Changes;
use App\EofficeApp\LogCenter\Changes\ChangeInterface;
use App\EofficeApp\LogCenter\Changes\BaseChange;
/**
 * Description of DocumentContentChange
 *
 * @author lizhijun
 */
class DocumentContentChange extends BaseChange implements ChangeInterface
{
    public $id = 'document_id';

    /**
     * 字段名称转换
     */
    public function fields() 
    {
        $this->fields = [
            'document_type' => '文档类型',
            'content' => '文档内容',
            'folder_id' => '文件夹',
            'subject' => '文档标题',
            "tag_id"          => "标签",
            "attachment_id"   => "附件",
        ];
    }
    
//    public function currentData($id)
//    {
//        $currentData = app('App\EofficeApp\Document\Services\DocumentService')->getDocumentInfoApi($id);
//        return $currentData;
//    }
//
    /**
     * 字段内容转换 例如文件夹id解析成具体文件夹名称,标签id解析成标签名称
     * @param $data
     * @param bool $mulit
     * @return Array
     *
     */
    public function parseData($data, $logData = [] )
    {
        //todo 需要去解析具体数据 例如 文件夹id解析成具体文件夹名称,标签id解析成标签名称
        $parseData= [];
        foreach ($data as $key => $val){
            if($key == 'tag_id') {
                foreach ($val as $k => $v) {
                    $v = json_decode($v, true);
                    $tagNames = $this->getTagNameById($v);
                    $parseData[$key][$k] = json_encode(array_column($tagNames, 'tag_name'), true);
                }
            }
        }
        if(isset($data['tag_id'])){
            $data['tag_id'] = $parseData['tag_id'];
        }
        return $data;
    }

    public function getTagNameById($id){
        return app('App\EofficeApp\System\Tag\Entities\TagEntity')
            ->select('tag_name')
            ->whereIn('tag_id', $id)
            ->get()
            ->toArray();
    }

}
