<?php
namespace App\Utils;

class DocParser {  
    private $params = array ();  
    private $keys = array();
    private $ctrl = "\r\n";
    function parse($doc = '') {  
        $this->params = array();
        $this->keys = array();
        if ($doc == '') {  
            return $this->params;  
        }  
        // Get the comment  
        if (preg_match ( '#^/\*\*(.*)\*/#s', $doc, $comment ) === false)  
            return $this->params;  
        $comment = trim ( $comment [1] );  
        // Get all the lines and strip the * from the first character  
        if (preg_match_all ( '#^\s*\*(.*)#m', $comment, $lines ) === false)  
            return $this->params;  
        
        $this->parseLines ( $lines [1] );  
        return $this->params;  
    }  
    private function parseLines($lines) {  
        $dealLines = [];
        foreach ($lines as $line){
            if(trim($line) != ''){
                $dealLines[] = $line;
            }
        }
        foreach ( $dealLines as $key => $line ) {  
            $parsedLine = $this->parseLine ($key, $line ); // Parse the line  
              
            if ($parsedLine === false && ! isset ( $this->params ['description'] )) {  
                if (isset ( $desc )) {  
                    // Store the first line in the short description  
                    $this->params ['description'] = implode ( '', $desc );  
                }  
                $desc = array ();  
            } elseif ($parsedLine !== false) {  
                $desc [$key] = $parsedLine; // Store the line in the long description  
            }  
        }  
        if(isset($desc) && !empty ( $desc )){
            $kKeys = array_keys($this->keys);
            foreach ($this->keys as $k => $v){
                if(in_array($v, ['errorExample','successExample','paramExample'])){
                    $firstKey = $k;
                    $lastKey = $this->getNextKeys($kKeys, $k);
                    $this->params[$v . 'Body'] = $this->getExampleBody($desc, $firstKey, $lastKey);
                }
            }
        }
    }  
    private function getExampleBody($desc, $firstKey, $lastKey)
    {
        $text = '';
        if($firstKey == $lastKey){
            $i = 0;
            foreach($desc as $key => $value){
                if($key > $firstKey){
                    if($i == 0) {
                        $text .= $value;
                    } else {
                        $text .= '*' . $value;
                    }
                    $i ++;
                }
            }
        } else {
            for ($i = $firstKey + 1; $i < $lastKey; $i++){
                if(isset( $desc[$i])) {
                    if($i == $firstKey + 1) {
                        $text .=  $desc[$i];
                    } else {
                        $text .= '*' . $desc[$i];
                    }
                }
            }
        }
        return $text;
    }
    private function getNextKeys($kKeys, $currKey)
    {
        foreach ($kKeys as $k => $key){
            if($key == $currKey){
                if($k == sizeof($kKeys) - 1){
                    return $currKey;
                } else {
                    return $kKeys[$k + 1];
                }
            }
        }
    }
    private function parseLine($key,$srcLine) {  
        // trim the whitespace from the line  
        $line = trim ( $srcLine );  
          
        if (empty ( $line ))  
            return false; // Empty line  
          
        if (strpos ( $line, '@' ) === 0) {  
            if (strpos ( $line, ' ' ) > 0) {  
                // Get the parameter name  
                $param = substr ( $line, 1, strpos ( $line, ' ' ) - 1 );  
                $value = substr ( $line, strlen ( $param ) + 2 ); // Get the value  
            } else {  
                $param = substr ( $line, 1 );  
                $value = '';  
            }  
            $this->keys[$key] = $param;
            // Parse the line and return false if the parameter is valid  
            if ($this->setParam ( $param, $value ))  
                return false;  
        }  
          
        return $srcLine;  
    }  
    private function setParam($param, $value) {  
        if (in_array($param, ['param','success','error'])) {
            $value = $this->formatParamOrReturn ( $value );  
        }
        if ($param == 'class')  
            list ( $param, $value ) = $this->formatClass ( $value );  
          
        if (empty ( $this->params [$param] )) {  
            $this->params [$param] = $value;  
        } else if (in_array($param, ['param','success','error'])) {  
            if(is_string($this->params [$param])){
                $this->params [$param] = [$this->params [$param]];
            }
            $this->params [$param][] = $value;
        } else {  
            $this->params [$param] = $value + $this->params [$param];  
        }  
        return true;  
    }  
    private function formatClass($value) {  
        $r = preg_split ( "[|]", $value );  
        if (is_array ( $r )) {  
            $param = $r [0];  
            parse_str ( $r [1], $value );  
            foreach ( $value as $key => $val ) {  
                $val = explode ( ',', $val );  
                if (count ( $val ) > 1)  
                    $value [$key] = $val;  
            }  
        } else {  
            $param = 'Unknown';  
        }  
        return array (  
                $param,  
                $value   
        );  
    }  
    private function formatParamOrReturn($string) {  
        $string = preg_replace ( "/\s(?=\s)/","\\1", $string );
        $string = str_replace("\t", ' ', $string);
        $pos = strpos ( $string, ' ' );  
          
        $type = substr ( $string, 0, $pos );  
        if(!$type || $type == '' || $type == ' '){
            $type = 'Type';
        }
        if($type == 'int'){
            $type = 'Number';
        }
        $type = str_replace('|', 'Or', $type);
        $param = ltrim(substr ( $string, $pos + 1 ),'$');
        return '{' . ucfirst(ltrim(rtrim(ltrim(rtrim($type,']'),'['),'}'),'{')) . '} ' . ($param ? $param : 'no_param');  
    }  
}  