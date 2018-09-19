<?php

namespace Mantonio84\FreeImporter\Reader;

class Reader implements \Countable, \ArrayAccess, \Iterator{
    
    protected $container=array();
    protected $header=array();
    public $fileHash="";
    private $position=0;
    private $hPrefix=null;
    
    private function strIntersect($a, $b){
        $m=min(strlen($a),strlen($b));
        if ($m==0) return "";
        $a=str_split(substr($a,0,$m));
        $b=str_split(substr($b,0,$m));            
        return implode("",array_intersect_assoc($a,$b));
    }

    
    private function guessStringPrefix(array $arr){
        $ret="";
        if ((count($arr)>1) and (count($arr)<=50)){
            $ret=array();
            for ($i=0;$i<count($arr)-1;$i++){
                for ($k=$i+1;$k<count($arr);$k++){
                    if ($k==$i) continue;
                    $pr=$this->strIntersect($arr[$i],$arr[$k]);
                    if (!isset($ret[$pr])){
                        $ret[$pr]=1;
                    }else{
                        $ret[$pr]++;
                    }
                }
            }
            arsort($ret);           
            $ret=key($ret);            
        }
        
        return $ret;
    }
    
    protected function calculateFileHash($filePath){
        $this->fileHash=sha1_file($filePath);
    }
    
    public function setHeader($index){
        if (is_array($index)){   
            $c=count(reset($this->container));
            
            if ((count($index)!=$c) and ($c>0)) throw new Exception("Index array size mismatch!");
            $this->header=$index;
            $this->hPrefix=null;
        }else{        
            $index=intval($index);
            if (($index>=0) and ($index<count($this->container))){
                $this->header=$this->container[$index];
                unset($this->container[$index]);
                $this->container=array_values($this->container);
                $this->hPrefix=null;
            }
        }
    }
    
    public function headerPrefix(){
        if (!$this->hasHeader()) return null;
        if ($this->hPrefix===null) $this->hPrefix=$this->guessStringPrefix($this->header);
        return $this->hPrefix;
    }
    
    public function removeHeader(){
        $this->header=array();
    }
    
    public function header($col=null){
        if ($this->hasHeader()){                    
            if (!is_int($col)){
                return $this->header;
            }else{
                return $this->header[intval($col)];
            }
        }else{
            if (empty($this->container)) return array();
            $a=array_keys($this->container[0]);
            if (!is_int($col)){
                return $a;
            }else{
                return $a[intval($col)];
            }
        }
    }
    
    public function hasHeader(){
        return (!empty($this->header));
    }
    
    public function hash(){
        if (!$this->hasHeader()) return null;
        $h=array_map('strtolower', $this->header);
        sort($h);
        return sha1(get_class()."::".json_encode($h));
    }
    
    public function colCount(){
        return count(reset($this->container));
    }    
    
    public function removeColumn($index){
        if ($this->hasHeader()){
            $index=array_search($index,$this->header);
        }else{
            $index=intval($index);            
        }
        if ($index!==false){
            if (($index>=0) and ($index<$this->colCount())) {
                $this->container=array_map(function ($itm) use ($index){
                    unset($itm[$index]);
                    return array_values($itm);
                },$this->container);             
                if ($this->hasHeader()){
                    unset($this->header[$index]);
                    $this->header=array_values($this->header);
                }
            }
        }
    }
    
    public function addColumn($index,array $data){
        $a=count($this->container);
        if (count($data)!=$a) throw new Exception("Data array must contains $a rows!");        
        if ($index===null) {
            if ($this->hasHeader()){
                throw new Exception("An index must be given for tables with headers!");
            }else{
                $index=$this->colCount();
            }
        }
        if ($this->hasHeader()){
            $this->header[]=$index;
            $index=$this->colCount();
        }
        for ($i=0;$i<$a;$i++) $this->container[$i][$index]=$data[$i];
    }
    
    public function getColumn($index){
        $ret=array();
        if ($this->hasHeader()){
            $index=array_search($index,$this->header);
        }else{
            $index=intval($index);            
        }
        if ($index!==false){
            if (($index>=0) and ($index<$this->colCount())) {
                foreach ($this->container as $v) $ret[]=$v[$index];                
            }
        }
        return $ret;
    }
    
    public function count(){
        
        return count($this->container);
    }
    
    public function offsetSet($offset, $value) {
        $offset=intval($offset);
        if (is_array($value)){
            $value=array_values($value);
            if ($this->hasHeader()){
                $c=count($this->header);
            }else{
                $c=count(reset($this->container));
            }
            if ((count($value)!=$c) and ($c>0)){
                 throw new Exception("Value array size mismatch!");
            }
            if (is_null($offset)) {
                $this->container[] = $value;
            } else {
                $this->container[$offset] = $value;
            }
        }
    }

    public function offsetExists($offset) {
        $offset=intval($offset);
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset) {
        $offset=intval($offset);
        unset($this->container[$offset]);
    }

    public function offsetGet($offset) {
        $offset=intval($offset);
        if ($this->offsetExists($offset)){
            if (!$this->hasHeader()){
                return $this->container[$offset];
            }else{
                return array_combine($this->header,$this->container[$offset]);
            }                                
        }else{
            return null;
        }     
    }
    
    public function rewind() {        
        $this->position = 0;
    }

    public function current() {        
        return $this->offsetGet($this->position);
    }

    public function key() {        
        return $this->position;
    }

    public function next() {        
        $this->position++;
    }

    public function valid() {        
        return $this->offsetExists($this->position);
    }
  
    public function isEmpty(){
        return ($this->count()==0);
    }  
    
    public function limitRows($start,$length=null){        
        $start=intval($start);
        $length=intval($length);
        if ($length>0){
            $this->container=array_slice($this->container,$start,$length);            
        }else if ($start>0){
            $this->container=array_slice($this->container,$start);
        }
    }
    
    public function limitCols($start,$length=null){
        $start=intval($start);        
        $length=intval($length);
        if (($start>0) or ($length>0)){
            $this->container=array_map(function ($itm) use ($start,$length){
                if ($length>0){                
                    return array_slice($itm,$start,$length);
                }else if ($start>0){
                    return array_slice($itm,$start);
                }        
                return $itm;
            },$this->container);
        }        
    }
    
    public function toArray(){
        return $this->container;
    }
    
    public function fromArray(array $arr){
        $this->container=&$arr;
    }
    
    public function beginResume(){
        $data=json_encode(array("chk" => md5(microtime(true)), "container" => $this->container, "header" => $this->header, "fileHash" => $this->fileHash));
        return base64_encode($data);        
    }
    
    
}
?>