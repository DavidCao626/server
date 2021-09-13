<?php

namespace App\EofficeApp\HtmlSignature\Services;

use App;
use Eoffice;
use App\EofficeApp\Base\BaseService;
use DB;

/**
 * 公共模板Service类:提供公共模板相关服务
 *
 * @author dingp
 *
 * @since  2018-01-12 创建
 */
class HtmlSignatureService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->goldgridSignatureKeysnRepository = 'App\EofficeApp\HtmlSignature\Repositories\GoldgridSignatureKeysnRepository';
        $this->goldgridSignatureSetRepository   = 'App\EofficeApp\HtmlSignature\Repositories\GoldgridSignatureSetRepository';
    }

    /**
     * 获取签章list数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author dingp
     *
     * @since  2018-01-12 创建
     */
    public function getHtmlSignatureList($documentId, $param)
    {
        if($documentId < 0) {
            return "";
        }
        $param = $this->parseParams($param);
        // 根据 $documentId 获取list
        $info = DB::table('htmlsignature')->where("documentid",$documentId)->where('signature_type', 1)->get()->toArray();
        return $info;
    }

    /**
     * 添加签章
     *
     * @param  array $data 签章数据
     *
     * @return int|array    添加id或状态码
     *
     * @author dingp
     *
     * @since  2018-01-12 创建
     */
    public function createHtmlSignature($documentId,$signatureId,$data)
    {
        if($documentId < 0) {
            return "";
        }
        $signatureData = isset($data["signatureData"]) ? $data["signatureData"] : "";
        $controlId = isset($data["controlid"]) ? $data["controlid"] : "";
        if($documentId && $signatureId && $signatureData) {
            $data = [];
            $data[] = ['documentid' => $documentId, 'signatureid' => $signatureId, 'signature' => $signatureData, 'controlid' => $controlId];
            return DB::table('htmlsignature')->insert($data);
        }
    }

    /**
     * 编辑签章数据
     *
     * @param   array   $input 编辑数据
     * @param   int     $signatureId    签章id
     *
     * @return  array          成功状态或状态码
     *
     * @author dingp
     *
     * @since  2018-01-12 创建
     */
    public function editHtmlSignature($documentId,$signatureId, $data)
    {
        if($documentId < 0) {
            return "";
        }
        $signatureData = isset($data["signatureData"]) ? $data["signatureData"] : "";
        if($documentId && $signatureId && $signatureData) {
            return DB::table('htmlsignature')->where("signatureid",$signatureId)->where("documentid",$documentId)->update(['documentid'=>$documentId, 'signatureid'=>$signatureId, 'signature'=>$signatureData]);
        }
    }

    /**
     * 删除签章
     *
     * @param   int     $signatureId    签章id
     *
     * @return  array          成功状态或状态码
     *
     * @author dingp
     *
     * @since  2018-01-12 创建
     */
    public function deleteHtmlSignature($documentId,$signatureId)
    {
        if($documentId < 0) {
            return "";
        }
        $info = DB::table('htmlsignature')->where("signatureid",$signatureId)->where("documentid",$documentId)->first();
        $info = json_decode(json_encode($info),true);
        if(count($info) && isset($info["documentid"]) && $info["documentid"]) {
            return DB::table("htmlsignature")->where('documentid', $documentId)->where("signatureid",$signatureId)->delete();
        }
    }

    /**
     * 金格签章，签章设置
     * $param 示例： ["param" => "value","param1" => "value1"]
     * @return
     *
     * @author dingp
     *
     * @since  2018-01-12
     */
    function goldgridSignatureSet($param)
    {
        // 取已有的设置
        $setInfo = app($this->goldgridSignatureSetRepository)->getSignatureSet();
        $setInfo = $setInfo->pluck("param")->toArray();
        if(count($param)) {
            foreach ($param as $key => $value) {
                if(in_array($key, $setInfo)) {
                    // 有，update
                    app($this->goldgridSignatureSetRepository)->updateData(["value" => $value], ["param" => $key]);
                } else {
                    // 没有，insert
                    $insertData = [
                        "param" => $key,
                        "value" => $value
                    ];
                    app($this->goldgridSignatureSetRepository)->insertData($insertData);
                }
            }
        }
        return "1";
    }

    /**
     * 金格签章，获取签章设置
     *
     * @return
     *
     * @author dingp
     *
     * @since  2018-01-12
     */
    function getGoldgridSignatureSet($param)
    {
        $result = app($this->goldgridSignatureSetRepository)->getSignatureSet($param);
        return $result;
    }

    /**
     * 金格签章keysn，获取系统内签章keysn的list
     *
     * @return array keysn列表
     *
     * @author dingp
     *
     * @since  2018-01-12
     */
    function getGoldgridSignatureKeysnList($param)
    {
        $param  = $this->parseParams($param);
        $result = $this->response(app($this->goldgridSignatureKeysnRepository), 'getListTotal', 'getList', $param);
        return $result;
    }

    /**
     * 金格签章keysn，新建签章keysn
     *
     * @return int 新建签章keysn result
     *
     * @author dingp
     *
     * @since  2018-01-12
     */
    function createGoldgridSignatureKeysn($param)
    {
        if(isset($param["user_id"]) && isset($param["keysn"])) {
            $insertData = [
                "user_id" => $param["user_id"],
                "keysn"   => $param["keysn"]
            ];
            return app($this->goldgridSignatureKeysnRepository)->insertData($insertData);
        }
    }

    /**
     * 金格签章keysn，编辑签章keysn
     *
     * @param  int $userId 用户id
     *
     * @return array 签章keysn详情
     *
     * @author: dingp
     *
     * @since：2018-01-12
     */
    function editGoldgridSignatureKeysn($userId,$param)
    {
        return (bool)app($this->goldgridSignatureKeysnRepository)->updateData($param, ["user_id" => $userId]);
    }

    /**
     * 金格签章keysn，删除签章keysn
     *
     * @param  string $userId 用户id,多个用逗号隔开
     *
     * @return bool 操作是否成功
     *
     * @author dingp
     *
     * @since  2018-01-12
     */
    function deleteGoldgridSignatureKeysn($userId)
    {
        $userId = explode(',', trim($userId,","));
        $wheres = ["user_id" => [$userId,"in"]];
        return app($this->goldgridSignatureKeysnRepository)->deleteByWhere($wheres);
    }

    /**
     * 金格签章keysn，获取某用户的签章keysn
     *
     * @param  string $userId 用户id,多个用逗号隔开
     *
     * @return bool 操作是否成功
     *
     * @author dingp
     *
     * @since  2018-01-12
     */
    function getUserGoldgridSignatureKeysn($userId)
    {
        return app($this->goldgridSignatureKeysnRepository)->getUserKeysnDetail($userId);
    }
}
