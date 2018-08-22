<?php
namespace mantonio84\FreeImporter;

use Illuminate\Database\Eloquent\Model;
use Reader\Reader;

class Importer {
    
    public $sourceData=null;
    private $colCount=0;
    private $rowCount=0;
    private $schema=array();
    private $lBoundRow=null;
    private $uBoundRow=null;
    
    public function __construct($src=null){
        if ($src instanceof Reader) $this->sourceData=&$src;
    }
    
    public function limitRows($start=null,$end=null){
        if (is_numeric($start)) $this->lBoundRow=intval($start);
        if (is_numeric($end)) $this->uBoundRow=intval($end);
        if ($start===false) $this->lBoundRow=null;
        if ($end===false) $this->uBoundRow=null;
    }
    
    public function setSchema($what){
        if (is_array($what)){            
            $j=range(intval(key($what)),$k+count($what));
            if ($j==array_keys($what)){
                foreach ($what as $h) $this->setSchema($h);
            }else{
                $this->setSchema(Field::fromArray($what));
            }
            
        }else if ($what instanceof Field){            
            $this->schema[]=&$what;
        }else if (is_string($what)){
            $this->setSchema(json_decode($what,true));        
        }else{
            throw new Exception("Invalid 'what' parameter given: expected array or Field object!");
        }
    }
    
    public function extractData(){
        if (!($this->sourceData instanceof Reader)){
            //Oltre che fesso sei pure cornuto....
            throw new Exception("Invalid sourceData given!");
        }
        if (empty($this->schema)) return array(); //Sei un fesso...
        if ($this->sourceData->isEmpty()) return array(); //Sei un fesso...
        $ret=array();
        if (is_null($this->lBoundRow)){
            $iStart=0;
        }else{
            $iStart=max($this->lBoundRow,0);
        }
        if (is_null($this->uBoundRow)){
            $iEnd=count($this->sourceData)-1;            
        }else{
            $iEnd=min(count($this->sourceData),$this->uBoundRow)-1;
        }
        $ret=array();        
        for ($r=$iStart;$r<=$iEnd;$r++){
            $row=array();
            foreach ($this->schema as $col) $row=array_merge($row,$col->toArray($this->sourceData[$r],$r));
            $ret[]=$row;
        }
        return $ret;
    }
    
    public function importData(Model $md){
        $data=$this->extractData();
        if (!empty($data)) $md::insert($data);        
    }
}
?>