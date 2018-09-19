<?php
namespace Mantonio84\FreeImporter;

use Reader\Reader;
use Adapters\ColumnAdapter;

class Importer {
    
    public $sourceData=null;
    protected $schema=array();
   
    
    public static function fromFile(string $filePath,array $params=array(), array $remapExtensions = array()){
    if (!is_file($filePath)) throw new \Exception("Unable to open file '".$filePath."'!");
        $ext=strtoupper(pathinfo($filePath,PATHINFO_EXTENSION));        
        if (!empty($remapExtensions)){
            $remapExtensions=array_change_key_case($remapExtensions,CASE_UPPER);
            $remapExtensions=array_filter(array_map(function ($t){
                if (!class_exists(__NAMESPACE__.'\\Reader\\'.$t)) return false;
                return $t;
            },$remapExtensions));
            if (array_key_exists($ext,$remapExtensions)) $ext=$remapExtensions[$ext];
        }
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
                $name=$a->name();
                if (!empty($name)){                                                            
                    $this->schema[$name]=&$a;                  
                }
                $valid=true;
            }   
        }        
        if (!$valid) throw new \InvalidArgumentException("Argument of schemaAdd must be an object that implements ColumnAdapter interface!");
    }
    
    public function schemaHas($name){
        return array_key_exists($name,$this->schema);
    }
    
    public function schemaClear(){
        $this->schema=array();        
    }
    
    public function schemaGet($index=null){
        if (is_int($index)){
            $index=intval($index);
            if (($index>=0) and ($index<count($this->schema))){
                $k=array_keys($this->schema);
                return $this->schemaGet($k[$index]);
            }            
        }else if (is_string($index)){
            if (array_key_exists($index,$this->schema)){
                return $this->schema[$index];
            }
        }else if (is_null($index)){
            return $this->schema;            
        }
        return null;
    }
    
    public function schemaSet(array $arr){
        $this->schemaClear();
        foreach ($arr as &$a) $this->schemaAdd($a);
    }
    
    public function schemaRemove($index){
        if (is_int($index)){
            if (($index>=0) and ($index<count($this->schema))){
                $k=array_keys($this->schema);
                $this->schemaRemove($k[$index]);
            }
        }else if (is_string($index)){
            if (array_key_exists($index,$this->schema)) unset($this->schema[$index]);
        }
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
                        if (is_string($scm)){
                            if ($this->schemaHas($scm)){
                                $scm=$this->schemaGet($scm);
                            }
                        }                     
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