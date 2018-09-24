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
        return (isset($this->schema[$name]));
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
            if (isset($this->schema[$index])){
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
            if (isset($this->schema[$index])) unset($this->schema[$index]);
        }
    }
    
    public function extractData($schemaArray=null, $cbDataProcessor=null){       
        if (!is_subclass_of($this->sourceData,__NAMESPACE__."\\Reader\\Reader")){
            //Oltre che fesso sei pure cornuto....
            throw new \Exception("Invalid sourceData given!");
        }                
        if ($this->sourceData->isEmpty()) return array(); //Sei un fesso...
        $tm=microtime(true);
        $ret=array();
        if (!is_array($schemaArray)) $schemaArray=null;
        $check=__NAMESPACE__."\Adapters\ColumnAdapter";    
        $allTargets=array();
        if ($schemaArray!==null){            
            foreach ($schemaArray as $h => $o){
                if ($this->schemaHas($o)){                    
                    $scm=$this->schemaGet($o);                                                 
                    if (is_subclass_of($scm,$check)){
                        $schemaArray[$h]=$scm;
                        $allTargets[]=$scm->target();
                    }else{
                        unset($schemaArray[$h]);
                    }
                }
            }            
        }else{
            $schemaArray=$this->guessColumns();            
        }                                
        if (empty($schemaArray)) return array();
        if (empty($allTargets)){
            $allTargets=array_map(function ($itm){
                return $itm->target();
            },$schemaArray);
        }
        $scmHeaders=array_keys($schemaArray);    
            $tms=0;
        foreach ($this->sourceData as $rowIndex => $rowData){   
            $tm=microtime(true);
            $row=array();
            $thisRowValidHeaders=array_intersect(array_keys($rowData),$scmHeaders);                        
            foreach ($thisRowValidHeaders as $colHeader){
                $value=$rowData[$colHeader];
                $scm=$schemaArray[$colHeader];                                                                                     
                if ($scm->validate($value)){                                        
                    $row[$scm->target()]=$scm->value($value);
                }                                                            
            }            
            if (is_callable($cbDataProcessor)) $row=call_user_func($cbDataProcessor,$row,$rowIndex,$rowData); 
            if (empty($row)){                
                $ret[]=$row;
            }else{
                $thisRowInvalidHeaders=array_diff($allTargets,array_intersect(array_keys($row),$allTargets));                
                if (!empty($thisRowInvalidHeaders)){
                    $row=array_merge($row,array_fill_keys($thisRowInvalidHeaders,null));
                }            
                ksort($row);
                $ret[]=$row;
            }
            $tms+=(microtime(true)-$tm);
        }
        echo number_format($tms/count($ret),4);
        die();
        return $ret;
    }    
    
    private function checkColumnHeld(&$scm,$colHeader){
        if ($scm->held($colHeader)) return true;
        $pr=$this->sourceData->headerPrefix();
        if (!empty($pr)){
            if ((stripos($colHeader,$pr)===0) and ($colHeader!=$pr)){
                return $scm->held(str_ireplace($pr,"",$colHeader));
            }
        }   
        return false;
    }
    
    private function guessColumn($colHeader){
        if (empty($this->schema)) return null;                        
        $ret=null;
         foreach ($this->schema as &$scm){            
            if ($this->checkColumnHeld($scm,$colHeader)){
                $ret=$scm;
                break;
            }  
         }
         return $ret;
    }   
    
    public function guessColumns(){
        $ret=array();
        $header=$this->sourceData->header();
        if (empty($this->schema)) return array(); //Sei un fesso...
        foreach ($header as $h) $ret[$h]=$this->guessColumn($h);
        return $ret;
    }         
    
   
}
?>