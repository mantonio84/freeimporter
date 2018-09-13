<?php
namespace Mantonio84\FreeImporter;

use Illuminate\Database\Eloquent\Model;
use Reader\Reader;

class Importer {
    
    public $sourceData=null;
    protected $schema=array();
    
    public static function fromFile(string $filePath,array $params=array()){
    if (!is_file($filePath)) throw new Exception("Unable to open file '".$filePath."'!");
        $ext=strtoupper(pathinfo($filePath,PATHINFO_EXTENSION));
        $className=__NAMESPACE__.'\\Reader\\'.$ext;
        if (class_exists($ext)){
            array_unshift($params,$filePath);
            return new static(new $className(...$params));
        }else{
            throw new Exception("Unable to find a valid reader for '".$filePath."'!");
        }
    }
    
    public function __construct($src=null){
        if ($src instanceof Reader) $this->sourceData=&$src;
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
    
    public function OneToOne(array $targets){
        if (!($this->sourceData instanceof Reader)){
            //Oltre che fesso sei pure cornuto....
            throw new Exception("Invalid sourceData given!");
        }
        if ($this->sourceData->hasHeader()){
            $h=$this->sourceData->header();
        }else{
            $h=range(0,count($this->sourceData)-1);
        }
        if (!empty($h)){
            if (count($h)!=count($targets)){
                throw new Exception("Targets array size mismatch!");
            }
            $this->schema=array();
            foreach ($h as $i => $hc) {
                $f=new Field;
                $f->column($hc);
                $f->setTarget($targets[$i]);
                $this->setSchema($f);
            }
        }
    }
    
    public function unSetSchema(){
        $this->schema=array();
    }
    
    public function getSchema(){
        return $this->schema;
    }
    
    public function extractData(){
        if (!($this->sourceData instanceof Reader)){
            //Oltre che fesso sei pure cornuto....
            throw new Exception("Invalid sourceData given!");
        }
        if (empty($this->schema)) return array(); //Sei un fesso...
        if ($this->sourceData->isEmpty()) return array(); //Sei un fesso...        
        $ret=array();        
        for ($r=0;$r<count($this->sourceData);$r++){
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