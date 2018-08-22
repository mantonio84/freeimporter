<?php

namespace mantonio84\FreeImporter\Reader;

class Reader implements \Countable, \ArrayAccess{
    
    protected $container=array();
    protected $header=array();
    
    public function setHeaderLine($index){
        $index=intval($index);
        if (($index>=0) and ($index<count($this->container))){
            $this->header=$this->container[$index];
            $this->container=array_slice($this->container,$index+1);
        }
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
    
}
?>