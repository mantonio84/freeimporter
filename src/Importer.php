<?php
namespace Mantonio84\FreeImporter;

use Reader\Reader;
use Adapters\ColumnAdapter;

class Importer {
    
    public $sourceData=null;
    protected $schema=array();
    
    public static function fromFile(string $filePath,array $params=array()){
    if (!is_file($filePath)) throw new \Exception("Unable to open file '".$filePath."'!");
        $ext=strtoupper(pathinfo($filePath,PATHINFO_EXTENSION));        
        $className=__NAMESPACE__.'\\Reader\\'.$ext;
        if (class_exists($className)){
            array_unshift($params,$filePath);            
            return new static(new $className(...$params));
        }else{
            throw new \Exception("Unable to find a valid reader for '".$filePath."'!");
        }
    }
    
    public function __construct($src=null){        
        if (is_subclass_of($src,__NAMESPACE__."\Reader\Reader")) {            
            $this->sourceData=&$src;
        }
        
    }
            
   public function schemaAdd($a){
        $valid=false;
        if (is_object($a)){
            $check=__NAMESPACE__."\Adapters\ColumnAdapter";
            if (is_subclass_of($a,$check)){
                $this->schema[]=&$a;    
                $valid=true;   
            }   
        }        
        if (!$valid) throw new \InvalidArgumentException("Argument of schemaAdd must be an object that implements ColumnAdapter interface!");
    }
    
    public function schemaClear(){
        $this->schema=array();
    }
    
    public function schemaGet($index=null){
        if (!is_int($index)){
            return $this->schema;
        }else{
            return $this->schema[intval($index)];
        }
    }
    
    public function schemaSet(array $a){
        $check=__NAMESPACE__."\Adapters\ColumnAdapter";
        $this->schema=array_values(array_filter(array_map(function ($itm) use ($check){            
            if (is_subclass_of($itm,$check)){
                return $itm;
            }else{
                return null;
            }
        },$a)));
    }
    
    public function schemaRemove(integer $index){
        if (($index>=0) and ($index<count($this->schema))) $this->schema=array_splice($this->schema,$index,1);
    }
    
    public function extractData($schemaArray=null){       
        if (!is_subclass_of($this->sourceData,__NAMESPACE__."\\Reader\\Reader")){
            //Oltre che fesso sei pure cornuto....
            throw new \Exception("Invalid sourceData given!");
        }                
        if ($this->sourceData->isEmpty()) return array(); //Sei un fesso...        
        $ret=array();
        if (!is_array($schemaArray)) $schemaArray=null;
        $check=__NAMESPACE__."\Adapters\ColumnAdapter";        
        foreach ($this->sourceData as $rowIndex => $rowData){
            $row=array();
            foreach ($rowData as $colHeader => $value){
                if ($schemaArray===null){
                    $row=array_merge($ret,$this->parseSchema($rowIndex,$rowData,$colHeader,$value));
                }else{                    
                    if (array_key_exists($colHeader,$schemaArray)){
                        $scm=$schemaArray[$colHeader];                        
                        if (is_subclass_of($scm,$check)){                            
                            $scm->prepare($rowIndex,$rowData);
                            if ($scm->validate($value)){
                                $row[$scm->target()]=$scm->value($value);
                            }
                        }
                    }
                }
            }
            $ret[]=$row;
        }
        return $ret;
    }    
    
    public function guessColumn($colHeader){
        if (empty($this->schema)) return null;
        $ret=null;
         foreach ($this->schema as &$scm){
            $scm->prepare(null,null);
            if ($scm->held($colHeader)){
                $ret=$scm;
                break;
            }  
         }
         return $ret;
    }   
    
    public function guessColumns(){
        $ret=array();
        $header=$this->sourceData->header();
        if (empty($this->schema)) return array_combine($header,array_fill(0,count($header),null)); //Sei un fesso...
        foreach ($header as $h) $ret[$h]=$this->guessColumn($h);
        return $ret;
    }         
    
    protected function parseSchema($rowIndex, $rowData, $colHeader, $value){
        if (empty($this->schema)) return array(); //Sei un fesso...
        if (empty($value)) return array();
        $ret=array();
        foreach ($this->schema as &$scm){
            $scm->prepare($rowIndex,$rowData);
            if ($scm->held($colHeader)){
                if ($scm->validate($value)){                    
                    $ret[$scm->target()]=$scm->value();
                    break;
                }
            }
        }
        return $ret;
    }
}
?>