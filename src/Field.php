<?php
namespace mantonio84\FreeImporter;


class Field {
    private $index=null;
    private $fixed="";
    private $destFieldName="";
    private $cbalter=null; 
        
    public static function &fromArray($data){
        if (!is_array($data)) throw new Exception("Invalid 'what' parameter given: expected array or Field object!");
        if (count($data)!=3) throw new Exception("Invalid field constructor: data array size mismatch!");
        list($type,$val,$fieldName)=$data;
        $type=strtolower(trim($type));
        $n=new Field();
        switch ($type){
            case "fixed":
                $n->fixed($val);
                break;
            case "column":
                $n->column($val);
                break;
            default:
               throw new Exception("Invalid field constructor: unknown type!"); 
        }
        $n->setTarget($fieldName);
        return $n;
    }
    
    public function fixed($val){
        $this->index=null;
        $this->fixed=$val;
    }
    
    public function column($index){
        $this->index=$index;
        $this->fixed=null;
    }
    
    public function isFixed(){
        return is_null($this->index);
    }
    
    public function setTarget($fieldName){
        $this->destFieldName=$fieldName;
    }
    
    public function getContent(array $row, $rowIndex=null){
        $ret=null;
        if ($this->isFixed()){
            $ret=$this->fixed;
        }else{
            if (isset($row[$this->index])){
                $ret=$row[$this->index];
            }else{
                if (is_null($this->cbalter)) throw new OutOfBoundsException("Column '".$this->index.'" not found in source row #'.$rowIndex.'!');
            }
        }
        if (!is_null($this->cbalter)) $ret=call_user_func($this->cbalter,$ret,$row,$rowIndex);
        return $ret;
    }
    
    public function setAlterProcessor($cb){
        if (is_callable($cb)) $this->cbalter=&$cb;
    }
    
    public function toArray(array $row, $rowIndex=null){
        if (empty($this->destFieldName)) return array();
        $ret=array();
        $ret[$this->destFieldName]=$this->getContent($row,$rowIndex);
    }    
}

?>