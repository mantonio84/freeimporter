<?php
namespace Mantonio84\FreeImporter\Adapters;

class SimpleColumn implements ColumnAdapter {
    
    protected $src=array();
    protected $tgt=null;
    
            
    protected $lbl=null;
    
    public function __construct($source,$target,$label){                
        $this->tgt=$target;        
        if (!is_array($source)) $source=array($source);        
        foreach ($source as $s){
            $s=$this->removeSpaces($s);
            if (!empty($s)) $this->src[]=$s;
        }       
        if (is_array($label)){
            if (isset($label[$target])){
                $this->lbl=$label[$target];
            }else{
                $this->lbl="";
            }
        }else{
            $this->lbl=$label;
        }        
    }
    
    public function label(){
        return $this->lbl;
    } 
    
    public function held($sourceColumnHeader){        
        if (empty($sourceColumnHeader)) return false;        
        return in_array($this->removeSpaces($sourceColumnHeader),$this->src);
    }
    
    public function target() {
        return $this->tgt;
    }
    
    public function validate($input){
        return true;
    }
    
    public function value($input){
        return $input;
    }
        
    public function name(){
        return $this->removeSpaces($this->tgt);
    }
    
    public function __toString() {
        return $this->name();
    }
    
    protected function removeSpaces($original){     
        if (empty($original)) return $original;
        $spaces=array("_","-","/","|",".");
        $none=array_fill(0,count($spaces)," ");
        $ret=ucwords(str_replace($spaces,$none,strtolower(trim($original))));
        $ret=str_replace(" ","",$ret);
        $ret=preg_replace("/[^A-Za-z0-9\\s]/","",$ret);   
        return $ret;     
    }
}

?>