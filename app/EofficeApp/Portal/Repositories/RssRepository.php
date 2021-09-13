<?php
namespace App\EofficeApp\Portal\Repositories;


class RssRepository
{	
	public $defaultEncode = 'GBK';
    public $CDATA = 'nochange';
    public $encode = 'UTF-8';
	public $rssEncode = '';
    public $itemsLimit = 0;
    public $stripHTML = true;
    public $dateFormat = '';
 
    private $channelTags = ['title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs'];
    private $itemTags = ['title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source'];
	
	public function __construct()
	{	
	}
	
	public function myPregMath($pattern, $subject) 
    {
        preg_match($pattern, $subject, $out);// 开始匹配
        
        if (!isset($out[1])) {
            return false;
        }
        // 处理 CDATA (如果存在)
        if ($this->CDATA == 'content') { // 获取 CDATA内容 (不存在 CDATA 标签)
            $out[1] = strtr($out[1], array('<![CDATA[' => '', ']]>' => ''));
        } else if ($this->CDATA == 'strip') { // 去除 CDATA
            $out[1] = strtr($out[1], array('<![CDATA[' => '', ']]>' => ''));
        }
        //转换成设置的编码
        if ($this->encode != '') {
            $out[1] = @iconv($this->rssEncode, $this->encode . '//TRANSLIT', $out[1]);
        }

        return trim($out[1]);
    }

    public function unhtmlentities ($string) 
    {
        $transTbl = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES));

        $transTbl += ["'" => "'"];
        
        return strtr($string, $transTbl);
    }
    function checkUrl($url){
        if(!check_white_list($url)) {
            return ['code' => ['0x000025','common']];
        }
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);//设置超时时间
        curl_exec($handle);
        //检查是否404（网页找不到）
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        
        if($httpCode == 200) {
            return true;
        }
        
        return false;    
    }
    public function parseRss ($rssUrl) 
    {
        $rssUrl = urldecode($rssUrl);
        // 添加http和https验证过滤。
        if(!(strpos($rssUrl, 'http://') === 0 || strpos($rssUrl, 'https://') === 0)) {
            $rssUrl = 'http://' . $rssUrl;
        }
        $result = $this->checkUrl($rssUrl);
        if(!$result){
            return false;
        }
        if(isset($result['code'])) {
            return $result;
        }
        if (!$handle = fopen($rssUrl, 'r')){
            return false;
        }

        $rssContent = '';
        
        while (!feof($handle)) {
            $rssContent .= fgets($handle, 4096);
        }
        
        fclose($handle);
        
        if($rssContent == '') {
            return false;
        }
        $result = [];
        $result['encoding'] = $this->myPregMath("'encoding=[\'\"](.*?)[\'\"]'si", $rssContent);// 解析文件编码 
        
        $this->rssEncode = $result['encoding'] == '' ? $this->defaultEncode : $result['encoding'];               
        
        preg_match("'<channel.*?>(.*?)</channel>'si", $rssContent, $outChannel);// 解析 CHANNEL信息
        
        foreach($this->channelTags as $channelTag){
            $temp = isset($outChannel[1]) ? $this->myPregMath("'<$channelTag.*?>(.*?)</$channelTag>'si", $outChannel[1]) : '';

            if ($temp != '') {
                $result[$channelTag] = $temp;    
            }
        }
        // 解析 ITEMS
        preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rssContent, $items);
        
        $rssItems = $items[2];
        
        $i = 0;
        
        $result['items'] = array(); 
        
        foreach($rssItems as $rssItem) {
            if ($i < $this->itemsLimit || $this->itemsLimit == 0) {
                foreach($this->itemTags as $itemTag) {
                    $temp = $this->myPregMath("'<$itemTag.*?>(.*?)</$itemTag>'si", $rssItem);
                    
                    if ($temp != '') {
                        $result['items'][$i][$itemTag] = $temp; 
                    }
                }
                
                $i++;
            }
        }
        
        $result['items_count'] = $i;
        
        return $result;
    }
}
