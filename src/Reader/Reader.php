<?php

namespace mantonio84\FreeImporter\Reader;

class Reader implements \Countable, \ArrayAccess{
    
    protected $container=array();
    protected $header=array();

    
    public function setHeader($index){
        if (is_array($index)){            
            if (count($index)!=count(reset($this->container))) throw new Exception("Index array size mismatch!");
            $this->header=$index;
        }else{        
            $index=intval($index);
            if (($index>=0) and ($index<count($this->container))){
                $this->header=$this->container[$index];
                $this->container=array_slice($this->container,$index+1);
            }
        }
    }
    
    public function removeHeader(){
        $this->header=array();
    }
    
    public function header($col=null){
        if (!is_numeric($col)){
            return $this->header;
        }else{
            return $this->header[intval($col)];
        }
    }
    
    public function hasHeader(){
        return (!empty($this->header));
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
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
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
            if (empty($this->header)){
                return $this->container[$offset];
            }else{
                return array_combine($this->header,$this->container[$offset]);
            }                                
        }else{
            return null;
        }     
    }
  
    public function isEmpty(){
        return ($this->count()==0);
    }  
    
    public function limitRows($start,$length=null){        
        $start=intval($start);
        if (is_numeric($length)){
            $this->container=array_slice($this->container,$start,$length);
        }else{
            $this->container=array_slice($this->container,$start);
        }
    }
    
    public function limitCols($start,$length=null){
        $start=intval($start);        
        $this->container=array_map(function ($itm) use ($start,$length){
            if (is_numeric($length)){
                $length=intval($length);
                return array_slice($itm,$start,$length);
            }else{
                return array_slice($itm,$start);
            }        
        },$this->container);        
    }
    
    public function toArray(){
        return $this->container;
    }
    
    public function fromArray(array $arr){
        $this->container=&$arr;
    }
    
    public function beginResume(){
        $data=json_encode(array("chk" => md5(microtime(true)), "container" => $this->container, "header" => $this->header));
        return base64_encode($data);        
    }
}
?>